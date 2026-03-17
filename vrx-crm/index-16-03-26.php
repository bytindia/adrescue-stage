<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';

$api_key_secret = "VRX_4d9Fh7Kq2LmX8vR5tYp3Za6WcN1eB0saf";

function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$input = file_get_contents('php://input');
$postdata = json_decode($input, true);

file_put_contents(
    __DIR__ . '/maze_lp_log.txt',
    date('Y-m-d H:i:s') . "\n" . print_r($postdata, true) . "\n\n",
    FILE_APPEND
);

function writeLog($dirPath, $message) {
    file_put_contents(
        $dirPath . "webhook_log.txt",
        date("Y-m-d H:i:s") . " | " . $message . PHP_EOL,
        FILE_APPEND
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, [
        "status" => "error",
        "message" => "Method not allowed. Use POST."
    ]);
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$headers = array_change_key_case($headers, CASE_UPPER);

if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $api_key_secret) {
    writeLog($dirPath, "UNAUTHORIZED - Invalid API Key");
    sendResponse(401, [
        "status" => "error",
        "message" => "Invalid API Key"
    ]);
}

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    writeLog($dirPath, "INVALID_JSON - " . $rawInput);
    sendResponse(400, [
        "status" => "error",
        "message" => "Invalid JSON format"
    ]);
}

$lead_id       = isset($input['lead_id']) ? trim($input['lead_id']) : '';
$name          = isset($input['name']) ? trim($input['name']) : '';
$email         = isset($input['email']) ? trim($input['email']) : '';
$phone         = isset($input['phone']) ? trim($input['phone']) : '';
$lead_feedback = isset($input['lead_feedback']) ? trim($input['lead_feedback']) : '';
$comments      = isset($input['comments']) ? trim($input['comments']) : '';
$crm_updated   = isset($input['crm_updated']) ? trim($input['crm_updated']) : (isset($input['updated']) ? trim($input['updated']) : '');

$utm_source    = isset($input['utm_source']) ? trim($input['utm_source']) : '';
$utm_medium    = isset($input['utm_medium']) ? trim($input['utm_medium']) : '';
$utm_campaign  = isset($input['utm_campaign']) ? trim($input['utm_campaign']) : '';
$utm_content   = isset($input['utm_content']) ? trim($input['utm_content']) : '';
$utm_term      = isset($input['utm_term']) ? trim($input['utm_term']) : '';

if (!empty($phone)) {
    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
        $phone = substr($phone, 2);
    }

    if (strlen($phone) > 10 && strlen($phone) % 10 === 0) {
        $chunks = str_split($phone, 10);
        $phone = implode(',', $chunks);
    }
}

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

if (!$conn || $conn->connect_error) {
    writeLog($dirPath, "DB_CONNECTION_FAIL");
    sendResponse(500, [
        "status" => "error",
        "message" => "Database connection failed"
    ]);
}

$existing_id = 0;

if (!empty($lead_id)) {
    $check = $conn->prepare("SELECT id FROM vrx_crm_leads WHERE lead_id = ? LIMIT 1");
    if ($check) {
        $check->bind_param("s", $lead_id);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $existing_id = (int)$row['id'];
        }
        $check->close();
    }
}

if ($existing_id === 0 && !empty($phone)) {
    $check = $conn->prepare("SELECT id FROM vrx_crm_leads WHERE phone = ? LIMIT 1");
    if ($check) {
        $check->bind_param("s", $phone);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $existing_id = (int)$row['id'];
        }
        $check->close();
    }
}

if ($existing_id === 0 && !empty($email)) {
    $check = $conn->prepare("SELECT id FROM vrx_crm_leads WHERE email = ? LIMIT 1");
    if ($check) {
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $existing_id = (int)$row['id'];
        }
        $check->close();
    }
}

if ($existing_id > 0) {
    $sql = "UPDATE vrx_crm_leads 
            SET lead_id = ?, name = ?, email = ?, phone = ?, lead_feedback = ?, comments = ?, crm_updated = ?, utm_source = ?, utm_medium = ?, utm_campaign = ?, utm_content = ?, utm_term = ?, updated_at = NOW()
            WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        writeLog($dirPath, "SQL_UPDATE_PREP_FAIL - " . $conn->error);
        sendResponse(500, [
            "status" => "error",
            "message" => "SQL preparation failed",
            "debug" => $conn->error
        ]);
    }

    $stmt->bind_param(
        "ssssssssssssi",
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
            "message" => "SQL preparation failed",
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

if ($stmt->execute()) {
    writeLog($dirPath, strtoupper($action) . " - " . json_encode($input));

    sendResponse(200, [
        "status" => "success",
        "message" => "Lead " . $action . " successfully",
        "data" => [
            "lead_id" => $lead_id,
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
            "utm_term" => $utm_term
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