<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 0);
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
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
if (count($_POST) > 0 && $_POST['phone'] != '') {

    //$spreadsheetId = '1AuhaPjEBlRRUXNFq4FJ2RH5Td5gvgylmyDrLN7ti6-0'; $sheetTab = 'LP - Leads';

    if ($_POST['project'] == 'iYRA Spire') { 
        $spreadsheetId = '19iiBelCQG0NjHKQC8s-ZvpsXT0eF3pP0z3iGzeTg1WQ';
        $sheetTab = 'Spire-Google';
       
    } 
    //$spreadsheetId = '1ib0T9Pf8iSpTTanv7bWcJ9U9sEw94HgK_yLNZynKnfg';
    //$sheetTab = 'Google Lead';

    $source = $medium = $campaign = '';
    
    $created_at = date('Y-m-d H:i:s');

    // Sanitize and format phone number
    $ph = isset($_POST['phone']) ? str_replace(' ', '', $_POST['phone']) : '';
    $ph = str_replace('+91', 'p:+91', $ph);

    // Safely get UTM parameters
    $source = isset($_POST['source']) ? $_POST['source'] : '';
    $medium = isset($_POST['medium']) ? $_POST['medium'] : '';
    $campaign = isset($_POST['campaign']) ? $_POST['campaign'] : '';
    $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : '';

    // Prepare values and sanitize
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $property = isset($_POST['property']) ? $_POST['property'] : '';
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $formatted_date = date('d-m-Y, h:i a');

    // --------------------
    // Mint360 API PUSH
    // --------------------

    // $mintUrl = "https://api.mint360.in/api/property-portal-lead";

    // $mintPostData = http_build_query([
    //     'name'              => $name,
    //     'mobile'            => preg_replace('/\D/', '', $ph),
    //     'email'             => $email,
    //     'project_key'       => $project_key,
    //     'country_code'      => 'IN',
    //     'minimum_budget'    => '',
    //     'maximum_budget'    => '',
    //     'remarks'           => '',
    //     'alternate_contact' => '',
    //     'current_city'      => ''
    // ]);

    // $ch = curl_init($mintUrl);
    // curl_setopt_array($ch, [
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_POST           => true,
    //     CURLOPT_POSTFIELDS     => $mintPostData,
    //     CURLOPT_HTTPHEADER     => [
    //         'x-access-token: ' . $accessToken,
    //         'Content-Type: application/x-www-form-urlencoded'
    //     ],
    //     CURLOPT_TIMEOUT        => 15
    // ]);

    // $mintResponse = curl_exec($ch);
    // $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // $curlError    = curl_error($ch);
    // curl_close($ch);

    // if ($mintResponse === false) {
    //     $apiResponse = json_encode([
    //         'status' => 'curl_error',
    //         'error'  => $curlError
    //     ]);
    // } else {
    //     $apiResponse = $mintResponse;
    // }

    // Create the lead data array
    $leadV = [[$name,  $ph, $email, $source, $medium, $campaign, $formatted_date, '']];
    append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;

    //$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $cirSql = "INSERT INTO iyra_leads (name, email, phone, project, src, med, camp, created_at, created, api_res) VALUES ('".mysqli_real_escape_string($conn, $name)."',  '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, $ph)."', '".mysqli_real_escape_string($conn, $_POST['project'])."', '".mysqli_real_escape_string($conn, $source)."', '".mysqli_real_escape_string($conn, $medium)."', '".mysqli_real_escape_string($conn, $campaign)."', '".$created_at."', now(), '".mysqli_real_escape_string($conn, $apiResponse)."');"; 
    error_log($cirSql);
    mysqli_query($conn, $cirSql) or die(mysqli_error());

    $data = array('code' => 200, 'response' => "success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
