<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';

$api_key_secret = "VRX_4d9Fh7Kq2LmX8vR5tYp3Za6WcN1eB0saf";

/**
 * Send JSON response and exit
 */
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * General log
 */
function writeLog($dirPath, $message) {
    file_put_contents(
        $dirPath . "webhook_log.txt",
        date("Y-m-d H:i:s") . " | " . $message . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Request log (full payload)
 */
function writeRequestLog($fileName, $data) {
    file_put_contents(
        __DIR__ . '/' . $fileName,
        date('Y-m-d H:i:s') . "\n" . print_r($data, true) . "\n\n",
        FILE_APPEND
    );
}

/**
 * Normalize phone number
 */
function normalizePhone($phone) {
    if (empty($phone)) {
        return '';
    }

    $phone = preg_replace('/\D/', '', $phone);

    // Remove India country code 91 if 12 digits
    if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
        $phone = substr($phone, 2);
    }

    // If multiple numbers got merged, split by 10 digits
    if (strlen($phone) > 10 && strlen($phone) % 10 === 0) {
        $chunks = str_split($phone, 10);
        $phone = implode(',', $chunks);
    }

    return $phone;
}

/**
 * Convert incoming datetime to MySQL DATETIME or fallback to NOW
 */
function normalizeDateTime($dateStr = '') {
    if (empty($dateStr)) {
        return date('Y-m-d H:i:s');
    }

    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return date('Y-m-d H:i:s');
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Insert lead status history
 */
function insertStatusHistory($conn, $lead_main_id, $lead_id, $old_status, $new_status, $old_comments, $new_comments, $crm_updated, $input) {
    $raw_payload = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $status_changed = ((string)$old_status !== (string)$new_status) ? 1 : 0;
    $comment_changed = ((string)$old_comments !== (string)$new_comments) ? 1 : 0;

    $sql = "INSERT INTO vrx_crm_lead_status_history
            (lead_main_id, lead_id, old_status, new_status, old_comments, new_comments, crm_updated, source, raw_payload, status_changed, comment_changed, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'crm_webhook', ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param(
            "isssssssii",
            $lead_main_id,
            $lead_id,
            $old_status,
            $new_status,
            $old_comments,
            $new_comments,
            $crm_updated,
            $raw_payload,
            $status_changed,
            $comment_changed
        );
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Get all headers safely
 */
function getRequestHeadersSafe() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }

    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$headerName] = $value;
        }
    }

    return $headers;
}

/**
 * Read input: supports JSON + form-data + x-www-form-urlencoded
 */
function getInputData() {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);

    if (is_array($jsonInput) && !empty($jsonInput)) {
        return $jsonInput;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return [];
}

/* ----------------------------------------------------------
   START
---------------------------------------------------------- */

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, [
        "status" => "error",
        "message" => "Method not allowed. Use POST."
    ]);
}

// Read input
$input = getInputData();

// Log full request
writeRequestLog('maze_lp_log.txt', $input);

// Validate DB connection
if (!$conn || $conn->connect_error) {
    writeLog($dirPath, "DB_CONNECTION_FAIL - " . $conn->connect_error);
    sendResponse(500, [
        "status" => "error",
        "message" => "Database connection failed"
    ]);
}

// Read API key from POST/JSON body OR header
$headers = getRequestHeadersSafe();
$headersUpper = array_change_key_case($headers, CASE_UPPER);

$body_api_key = isset($input['api_key']) ? trim($input['api_key']) : '';
$header_api_key = isset($headersUpper['X-API-KEY']) ? trim($headersUpper['X-API-KEY']) : '';

$final_api_key = !empty($body_api_key) ? $body_api_key : $header_api_key;

if ($final_api_key !== $api_key_secret) {
    writeLog($dirPath, "UNAUTHORIZED - Invalid API Key - " . json_encode($input));
    sendResponse(401, [
        "status" => "error",
        "message" => "Invalid API Key"
    ]);
}

/* ----------------------------------------------------------
   FIELD MAPPING
   Supports CRM field names + generic names
---------------------------------------------------------- */

$lead_id = '';
if (isset($input['LeadId'])) {
    $lead_id = trim($input['LeadId']);
} elseif (isset($input['lead_id'])) {
    $lead_id = trim($input['lead_id']);
}

$name = '';
if (isset($input['LeadName'])) {
    $name = trim($input['LeadName']);
} elseif (isset($input['name'])) {
    $name = trim($input['name']);
}

$email = '';
if (isset($input['LeadEmail'])) {
    $email = trim($input['LeadEmail']);
} elseif (isset($input['email'])) {
    $email = trim($input['email']);
}

$phone = '';
if (isset($input['LeadPhone'])) {
    $phone = trim($input['LeadPhone']);
} elseif (isset($input['phone'])) {
    $phone = trim($input['phone']);
}

$lead_feedback = '';
if (isset($input['LeadStatus'])) {
    $lead_feedback = trim($input['LeadStatus']);
} elseif (isset($input['lead_feedback'])) {
    $lead_feedback = trim($input['lead_feedback']);
}

$comments = '';
if (isset($input['Call_Comments__c'])) {
    $comments = trim($input['Call_Comments__c']);
} elseif (isset($input['comments'])) {
    $comments = trim($input['comments']);
}

$crm_updated = '';
if (isset($input['crm_updated'])) {
    $crm_updated = trim($input['crm_updated']);
} elseif (isset($input['updated'])) {
    $crm_updated = trim($input['updated']);
} elseif (isset($input['LastModifiedDate'])) {
    $crm_updated = trim($input['LastModifiedDate']);
}
$crm_updated = normalizeDateTime($crm_updated);

// Optional UTM fields
$utm_source   = isset($input['utm_source']) ? trim($input['utm_source']) : '';
$utm_medium   = isset($input['utm_medium']) ? trim($input['utm_medium']) : '';
$utm_campaign = isset($input['utm_campaign']) ? trim($input['utm_campaign']) : '';
$utm_content  = isset($input['utm_content']) ? trim($input['utm_content']) : '';
$utm_term     = isset($input['utm_term']) ? trim($input['utm_term']) : '';

// Normalize phone
$phone = normalizePhone($phone);

// Validations
if (empty($phone) && empty($email)) {
    writeLog($dirPath, "VALIDATION_FAIL - Missing phone/email - " . json_encode($input));
    sendResponse(422, [
        "status" => "error",
        "message" => "Either phone or email is required"
    ]);
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(422, [
        "status" => "error",
        "message" => "Invalid email format"
    ]);
}

/* ----------------------------------------------------------
   FIND EXISTING LEAD
---------------------------------------------------------- */

$existing_id = 0;
$old_status = '';
$old_comments = '';
$old_lead_id = '';

if (!empty($lead_id)) {
    $check = $conn->prepare("SELECT id, lead_id, lead_feedback, comments FROM vrx_crm_leads WHERE lead_id = ? LIMIT 1");
    if ($check) {
        $check->bind_param("s", $lead_id);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $existing_id = (int)$row['id'];
            $old_lead_id = $row['lead_id'] ?? '';
            $old_status = $row['lead_feedback'] ?? '';
            $old_comments = $row['comments'] ?? '';
        }
        $check->close();
    }
}

// If not found by lead_id, try by phone
if ($existing_id === 0 && !empty($phone)) {
    $check = $conn->prepare("SELECT id, lead_id, lead_feedback, comments FROM vrx_crm_leads WHERE phone = ? LIMIT 1");
    if ($check) {
        $check->bind_param("s", $phone);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $existing_id = (int)$row['id'];
            $old_lead_id = $row['lead_id'] ?? '';
            $old_status = $row['lead_feedback'] ?? '';
            $old_comments = $row['comments'] ?? '';
        }
        $check->close();
    }
}

// If not found by email, try by email
if ($existing_id === 0 && !empty($email)) {
    $check = $conn->prepare("SELECT id, lead_id, lead_feedback, comments FROM vrx_crm_leads WHERE email = ? LIMIT 1");
    if ($check) {
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $existing_id = (int)$row['id'];
            $old_lead_id = $row['lead_id'] ?? '';
            $old_status = $row['lead_feedback'] ?? '';
            $old_comments = $row['comments'] ?? '';
        }
        $check->close();
    }
}

/* ----------------------------------------------------------
   INSERT OR UPDATE MAIN TABLE
---------------------------------------------------------- */

$action = '';
$stmt = null;

if ($existing_id > 0) {
    // If lead_id was blank before and now received, preserve new one
    $final_lead_id = !empty($lead_id) ? $lead_id : $old_lead_id;

    $sql = "UPDATE vrx_crm_leads 
            SET lead_id = ?, name = ?, email = ?, phone = ?, lead_feedback = ?, comments = ?, crm_updated = ?, 
                utm_source = ?, utm_medium = ?, utm_campaign = ?, utm_content = ?, utm_term = ?, updated_at = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        writeLog($dirPath, "SQL_UPDATE_PREP_FAIL - " . $conn->error);
        sendResponse(500, [
            "status" => "error",
            "message" => "SQL update preparation failed",
            "debug" => $conn->error
        ]);
    }

    $stmt->bind_param(
        "ssssssssssssi",
        $final_lead_id,
        $name,
        $email,
        $phone,
        $lead_feedback,
        $comments,
        $crm_updated,
        $utm_source,
        $utm_medium,
        $utm_campaign,
        $utm_content,
        $utm_term,
        $existing_id
    );

    $action = "updated";
} else {
    $sql = "INSERT INTO vrx_crm_leads 
            (lead_id, name, email, phone, lead_feedback, comments, crm_updated, utm_source, utm_medium, utm_campaign, utm_content, utm_term, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        writeLog($dirPath, "SQL_INSERT_PREP_FAIL - " . $conn->error);
        sendResponse(500, [
            "status" => "error",
            "message" => "SQL insert preparation failed",
            "debug" => $conn->error
        ]);
    }

    $stmt->bind_param(
        "ssssssssssss",
        $lead_id,
        $name,
        $email,
        $phone,
        $lead_feedback,
        $comments,
        $crm_updated,
        $utm_source,
        $utm_medium,
        $utm_campaign,
        $utm_content,
        $utm_term
    );

    $action = "inserted";
}

/* ----------------------------------------------------------
   EXECUTE + HISTORY
---------------------------------------------------------- */

if ($stmt->execute()) {
    $main_lead_id = ($action === 'inserted') ? $stmt->insert_id : $existing_id;

    // For NEW lead -> insert first history row
    if ($action === 'inserted') {
        insertStatusHistory(
            $conn,
            $main_lead_id,
            $lead_id,
            null,
            $lead_feedback,
            null,
            $comments,
            $crm_updated,
            $input
        );
    }

    // For UPDATED lead -> only insert history if status/comments changed
    if ($action === 'updated') {
        if ((string)$old_status !== (string)$lead_feedback || (string)$old_comments !== (string)$comments) {
            $final_lead_id_for_history = !empty($lead_id) ? $lead_id : $old_lead_id;

            insertStatusHistory(
                $conn,
                $main_lead_id,
                $final_lead_id_for_history,
                $old_status,
                $lead_feedback,
                $old_comments,
                $comments,
                $crm_updated,
                $input
            );
        }
    }

    writeLog($dirPath, strtoupper($action) . " - " . json_encode($input));

    sendResponse(200, [
        "status" => "success",
        "message" => "Lead " . $action . " successfully",
        "data" => [
            "id" => $main_lead_id,
            "lead_id" => !empty($lead_id) ? $lead_id : $old_lead_id,
            "name" => $name,
            "email" => $email,
            "phone" => $phone,
            "lead_feedback" => $lead_feedback,
            "comments" => $comments,
            "crm_updated" => $crm_updated,
            "utm_source" => $utm_source,
            "utm_medium" => $utm_medium,
            "utm_campaign" => $utm_campaign,
            "utm_content" => $utm_content,
            "utm_term" => $utm_term,
            "action" => $action
        ]
    ]);
} else {
    writeLog($dirPath, "DB_EXEC_FAIL - " . $stmt->error . " - INPUT: " . json_encode($input));

    sendResponse(500, [
        "status" => "error",
        "message" => "Database operation failed",
        "debug" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();

?>