<?php
date_default_timezone_set('Asia/Kolkata');

// CORS
header("Access-Control-Allow-Origin: https://school.rockstarshub.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
$conn =  mysqli_connect('localhost', 'digitalb2k_adsninja', getenv('DB_PASS'), 'digitalb2k_adsninja'); 
mysqli_set_charset($conn, "utf8mb4");

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';
    $phone    = $_POST['phone'] ?? '';
    $message  = $_POST['message'] ?? '';
   
    $cta = $_POST['cta_source'] ?? 'unknown';

if ($cta === 'footer-sign-up-button') {
    $btn_source= "footer-sign-up-button";
}

if ($cta === 'sign-up') {
     $btn_source= "sign-up-button";
}
else{
     $btn_source= "Delay popup";
}


    $source   = $_POST['source'] ?? '';
    $medium   = $_POST['medium'] ?? '';
    $campaign = $_POST['campaign'] ?? '';
    $gclid    = $_POST['gclid'] ?? '';
    $fbclid    = $_POST['fbclid'] ?? '';
    $ad_id    = $_POST['ad_id'] ?? '';
    $page_url    = $_POST['page_url'] ?? '';
    $landing_page    = $_POST['landing_page'] ?? '';
    $form_id     = $_POST['form_id'] ?? '';

    if (empty($name) || empty($email) || empty($phone)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Name, Email, and Phone are required"]);
        exit;
    }

    // Check for existing lead
    $checkSql = "SELECT id FROM rockstar_leads WHERE email='".mysqli_real_escape_string($conn, $email)."' OR phone='".mysqli_real_escape_string($conn, $phone)."' LIMIT 1";
    $result = mysqli_query($conn, $checkSql);

    if (mysqli_num_rows($result) > 0) {
       // echo json_encode(["status" => "exists", "message" => "Thankyou!"]);
       // exit;
    }

    // Insert new lead
    $sql = "
   INSERT INTO rockstar_leads 
(
    `name`,
    `email`,
    `phone`,
    `message`,
    `source`,
    `medium`,
    `campaign`,
    `gclid`,
    `fbclid`,
    `ad_id`,
    `form_id`,
    `btn_source`,
    `page_url`,
    `lp_url`
) 
VALUES (
    '".mysqli_real_escape_string($conn, $name)."',
    '".mysqli_real_escape_string($conn, $email)."',
    '".mysqli_real_escape_string($conn, $phone)."',
    '".mysqli_real_escape_string($conn, $message)."',
    '".mysqli_real_escape_string($conn, $source)."',
    '".mysqli_real_escape_string($conn, $medium)."',
    '".mysqli_real_escape_string($conn, $campaign)."',
    '".mysqli_real_escape_string($conn, $gclid)."',
    '".mysqli_real_escape_string($conn, $fbclid)."',
    '".mysqli_real_escape_string($conn, $ad_id)."',
    '".mysqli_real_escape_string($conn, $form_id)."',
    '".mysqli_real_escape_string($conn, $cta)."',
    '".mysqli_real_escape_string($conn, $page_url)."',
    '".mysqli_real_escape_string($conn, $landing_page)."'
)";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
}
