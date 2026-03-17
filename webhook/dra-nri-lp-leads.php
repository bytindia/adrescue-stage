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



$input = file_get_contents('php://input');
$lead = json_decode($input, true);

//print_r($_POST); print_r($lead); exit;
if ( $_POST['url'] == 'https://draiheart.com/nri') {

    $spreadsheetId = '1oayfSOgH-20hLycRXsgGm0IJv756W27UtJdlbh9iUlo';
    $sheetTab = 'DRA-NRI-LP-LEADS';

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
        $srd = isset($_POST['srd']) ? $_POST['srd'] : '';
        $term = isset($_POST['term']) ? $_POST['term'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';

    // Prepare values and sanitize
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $form = isset($_POST['form']) ? $_POST['form'] : '';
    $formatted_date = date('d-m-Y, h:i a');

    // Create the lead data array
    $leadV = [[$name, $email, $ph, $form,$srd, $source, $medium, $campaign, $keyword, $content, $term, $formatted_date]];


    append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
    //$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code' => 200, 'response' => "success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
