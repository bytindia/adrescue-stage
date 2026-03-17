<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1_S2XqcuqqnNBgZdB2nUFMYqMhe0g2rAUemP_ja1Huhs'; 

$sheetTab='LP - Leads';


$input = file_get_contents('php://input');
$lead = $_REQUEST;

//print_r($lead);

if(count($lead)>0) {
    
  
    $leadV = [[mysqli_real_escape_string($conn, $lead['name']),$lead['email'],$lead['phone'],mysqli_real_escape_string($conn, $lead['message']), mysqli_real_escape_string($conn, $lead['source']),$lead['project'], date('d-m-Y, h:i a')]];
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
	//$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code'=>200, 'response'=>"success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}