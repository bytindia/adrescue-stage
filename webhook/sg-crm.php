<?php

// =====================================================
// SOUNDSGOOD CRM → ADSNINJA WEBHOOK
// =====================================================

// Always return JSON
header("Content-Type: application/json");

// Optional: Allow CORS if needed
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

// =====================================================
// CONFIGURATION
// =====================================================

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php'; // Must create $conn

$api_key_secret = "SG_2026_Soundsgood_4d9Fh7Kq2LmX8vR5tYp3Za6WcN1eB0";

// =====================================================
// FUNCTION: JSON RESPONSE
// =====================================================

function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// =====================================================
// 1️⃣ VALIDATE REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, [
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}

// =====================================================
// 2️⃣ VALIDATE API KEY
// =====================================================

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $api_key_secret) {
    sendResponse(401, [
        "status" => "error",
        "message" => "Invalid API Key"
    ]);
}

// =====================================================
// 3️⃣ GET RAW INPUT
// =====================================================

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(400, [
        "status" => "error",
        "message" => "Invalid JSON format"
    ]);
}

// =====================================================
// 4️⃣ VALIDATE FIELDS
// =====================================================

$email         = isset($input['email']) ? trim($input['email']) : '';
$phone         = isset($input['phone']) ? trim($input['phone']) : '';
$lead_feedback = isset($input['lead_feedback']) ? trim($input['lead_feedback']) : '';

if (!empty($phone)) {

    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) > 10 && substr($phone, 0, 2) == '91') {
        $phone = substr($phone, 2);
    }

    if (strlen($phone) > 10 && strlen($phone) % 10 == 0) {
        $phone = implode(',', str_split($phone, 10));
    }
}

if (empty($phone)) {
    sendResponse(422, [
        "status" => "error",
        "message" => "Phone is required"
    ]);
}

// Optional: Validate email format
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(422, [
        "status" => "error",
        "message" => "Invalid email format"
    ]);
}

// =====================================================
// 5️⃣ CHECK DB CONNECTION
// =====================================================

if (!$conn || $conn->connect_error) {
    sendResponse(500, [
        "status" => "error",
        "message" => "Database connection failed"
    ]);
}

// =====================================================
// 6️⃣ INSERT / UPDATE LEAD
// =====================================================

$stmt = $conn->prepare("
    INSERT INTO sg_crm_leads (email, phone, lead_feedback, created_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        lead_feedback = VALUES(lead_feedback),
        updated_at = NOW()
");

if (!$stmt) {
    sendResponse(500, [
        "status" => "error",
        "message" => "SQL preparation failed"
    ]);
}

$stmt->bind_param("sss", $email, $phone, $lead_feedback);

if ($stmt->execute()) {

    // Optional: Log success
    file_put_contents(
        $dirPath . "webhook_log.txt",
        date("Y-m-d H:i:s") . " SUCCESS - " . json_encode($input) . PHP_EOL,
        FILE_APPEND
    );

    sendResponse(200, [
        "status" => "success",
        "message" => "Lead stored successfully",
        "data" => [
            "email" => $email,
            "phone" => $phone,
            "feedback" => $lead_feedback
        ]
    ]);

} else {

    file_put_contents(
        $dirPath . "webhook_log.txt",
        date("Y-m-d H:i:s") . " ERROR - DB Insert Failed - " . json_encode($input) . PHP_EOL,
        FILE_APPEND
    );

    sendResponse(500, [
        "status" => "error",
        "message" => "Database operation failed"
    ]);
}

$stmt->close();
$conn->close();