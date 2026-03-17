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
if ($_POST['url'] == 'https://oldkentresorts.com/lp/') {

    $spreadsheetId = '1_qZtRFRuXS6091sXup7-rYE-mHTTrIG8obi8jt7Sq1g';
    $sheetTab = 'OKE-LP';

    $source = '';
    
    $created_at = date('Y-m-d H:i:s');

    // Sanitize and format phone number
    $ph = isset($_POST['phone']) ? str_replace(' ', '', $_POST['phone']) : '';
    $ph = str_replace('+91', 'p:+91', $ph);

    // Safely get UTM parameters
    $source = isset($_POST['source']) ? $_POST['source'] : '';
  
    // Prepare values and sanitize
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $formatted_date = date('d-m-Y, h:i a');

    // Create the lead data array
    $leadV = [[$source, $formatted_date ,$email , $name,$ph]];


    append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
    //$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code' => 200, 'response' => "success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
