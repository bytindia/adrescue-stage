<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dirPath = '/home/digitalb2k/stage.adrescue.in/';

include $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
include $dirPath . 'google-sheets-api/insert-row.php';

$uId = 2;
$tbl_id = 2;

// Google Sheet ID
$spreadsheetId = '1MvNFRMFDiUKeOZjNzyFfQBCXjc61Dz__ka25QSe4wIo';

// Get LP type from URL parameter
$lp = isset($_GET['lp']) ? strtolower(trim($_GET['lp'])) : '';

// Decide sheet tab based on lp
if ($lp == 'chennai') {
    $sheetTab = 'Chennai - LP';
} elseif ($lp == 'bangalore') {
    $sheetTab = 'Bangalore - LP';
} else {
    $data = array('code' => 400, 'response' => 'Invalid or missing lp parameter');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Read Elementor webhook JSON payload
$input = file_get_contents('php://input');
$lead = json_decode($input, true);

// Optional fallback if JSON is empty
if (!$lead || !is_array($lead)) {
    $lead = $_POST;
}

// Debug log (VERY USEFUL for first 2-3 tests)
 file_put_contents(__DIR__ . '/maze_lp_log.txt', date('Y-m-d H:i:s') . "\n" . print_r($lead, true) . "\n\n", FILE_APPEND);

/**
 * Helper function to fetch Elementor Advanced Data values
 * Advanced Data structure:
 * $lead['data']['field_id']['value']
 */
function getFieldValue($lead, $fieldKey) {
    if (isset($lead['fields'][$fieldKey]['value'])) {
        return trim((string)$lead['fields'][$fieldKey]['value']);
    }
    return '';
}

$name         = getFieldValue($lead, 'name');
$email        = getFieldValue($lead, 'email');
$phone        = getFieldValue($lead, 'field_6814ef8');
$subject      = getFieldValue($lead, 'field_1260f14');
$message      = getFieldValue($lead, 'message');

$utm_source   = getFieldValue($lead, 'utm_source');
$utm_medium   = getFieldValue($lead, 'utm_medium');
$utm_campaign = getFieldValue($lead, 'utm_campaign');
$utm_content  = getFieldValue($lead, 'utm_content');
$utm_term     = getFieldValue($lead, 'utm_term');

$page_url = '';
if (isset($lead['meta']['page_url']['value'])) {
    $page_url = $lead['meta']['page_url']['value'];
}

// Optional: format phone
$phone = str_replace(' ', '', $phone);

// Created date
$created_at = date('Y-m-d H:i:s');

// Prepare row for Google Sheet
$leadV = [[
    $created_at,
    $name,
    $email,
    $phone,
    $subject,
    $message,
    $utm_source,
    $utm_medium,
    $utm_campaign
]];

// Append to correct sheet tab
append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);

// Return success response
$data = array(
    'code' => 200,
    'response' => 'success',
    'lp' => $lp,
    'sheet_tab' => $sheetTab
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
exit;
?>