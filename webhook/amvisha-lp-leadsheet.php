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
$uId =2;
$tbl_id = 2;



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 405, 'response' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (!is_array($data)) {
    $data = [];
}

// Support form-urlencoded POST (WordPress sometimes sends this)
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

/**
 * Get value by field id from WordPress/Elementor payload.
 * Supports: (1) $data['fields'] = [ ['id'=>'name', 'value'=>'x'], ... ]
 *           (2) Flat $data['name'], $data['email'], $data['field_95c98f4'], etc.
 */
function get_field_value($data, $field_id) {
    if (empty($data)) return '';
    if (!empty($data['fields']) && is_array($data['fields'])) {
        foreach ($data['fields'] as $field) {
            $id = isset($field['id']) ? $field['id'] : '';
            if ((string)$id === (string)$field_id) {
                return isset($field['value']) ? trim((string)$field['value']) : '';
            }
        }
    }
    return isset($data[$field_id]) ? trim((string)$data[$field_id]) : '';
}

$spreadsheetId = '1MlH33MEb2gy0odgZdjpOB77hAstLt8Xs1SMKua_NR6k';
$sheetTab = 'Google';

// WordPress field ids: name, email, field_95c98f4, field_07dbcab, utm_source, utm_medium, utm_campaign, utm_term, utm_content
$name         = get_field_value($data, 'name');
$email        = get_field_value($data, 'email');
$phone        = get_field_value($data, 'field_95c98f4');   
$property = get_field_value($data, 'field_07dbcab');
$message      = get_field_value($data, 'message');
$utm_source   = get_field_value($data, 'utm_source');
$utm_medium   = get_field_value($data, 'utm_medium');
$utm_campaign = get_field_value($data, 'utm_campaign');
$utm_term     = get_field_value($data, 'utm_term');
$utm_content  = get_field_value($data, 'utm_content');

$formatted_date = date('Y-m-d H:i:s');

// Build row: name, email, phone, form, page_url, source, medium, campaign, keyword, content, term, date
$leadV = [[$name, $email, $phone,$property, $utm_source, $utm_medium, $utm_campaign, $utm_term, $utm_content,  $formatted_date, '', '']];

append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['code' => 200, 'response' => 'success']);
