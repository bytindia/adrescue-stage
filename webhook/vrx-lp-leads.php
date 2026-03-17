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



$input = file_get_contents('php://input');
$lead = json_decode($input, true);

//print_r($_POST); print_r($lead); exit;
if (count($_POST) > 0 && $_POST['url'] == 'vrx-lp-leads') {

    $spreadsheetId = '1wPfPMcZw6mnbag9C5mklOje2qfChvQzO_m7ak5DHvgw';
    $sheetTab = 'Google_leads';

    $source = $medium = $campaign = '';
    
    $created_at = date('Y-m-d H:i:s');

    // Sanitize and format phone number
    $ph = trim($_POST['phone'] ?? ($_POST['mobile'] ?? ''));

// remove everything except numbers
$ph = preg_replace('/\D/', '', $ph);

// remove existing 91 if already added
$ph = preg_replace('/^91/', '', $ph);

// add +91 at start
$ph = '+91' . $ph;
    
 $project = isset($_POST['project']) ? $_POST['project'] : '';
$unit_type = isset($_POST['unit_type']) ? $_POST['unit_type'] : '';
    // Safely get UTM parameters
    $source = isset($_POST['source']) ? $_POST['source'] : '';
    $medium = isset($_POST['medium']) ? $_POST['medium'] : '';
    $campaign = isset($_POST['campaign']) ? $_POST['campaign'] : '';
    $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : '';
    $term = isset($_POST['term']) ? $_POST['term'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    // Prepare values and sanitize
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $property = isset($_POST['property']) ? $_POST['property'] : '';
    $form = isset($_POST['form']) ? $_POST['form'] : '';
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $sf_lead_id = isset($_POST['sf_lead_id']) ? $_POST['sf_lead_id'] : '';
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $formatted_date = date('d-m-Y, h:i a');
    
    
    /* ---------------- DATABASE INSERT ---------------- */

$stmt = $conn->prepare("INSERT INTO vrx_leads_lp 
(name,email,phone,form,unit,project,src,med,camp,content,budget,full_url,created_at,created,api_res)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
"sssssssssssssss",
$name,
$email,
$ph,
$form,
$unit_type,
$project,
$source,
$medium,
$campaign,
$content,
$term,
$url,
$created_at,
$created_at,
$sf_lead_id
);

$stmt->execute();
$stmt->close();
    
// Create the lead data array
    $leadV = [[$name, $email, $ph,$form,$project,$city, $source, $medium, $campaign, $keyword,$term,$content, $formatted_date, $sf_lead_id]];


    append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
    //$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code' => 200, 'response' => "success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
