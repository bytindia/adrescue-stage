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



$input = file_get_contents('php://input');
$lead = json_decode($input,true);

//print_r($_POST); print_r($lead); exit;
if(count($_POST)>0 && $_POST['project']=='Ishana') { //Vibrant - Ishana - https://vohllp.com/lp
    
    $spreadsheetId='1_S2XqcuqqnNBgZdB2nUFMYqMhe0g2rAUemP_ja1Huhs'; 
    $sheetTab='LP - Leads'; 

    $src= $med= $camp= $term=  $content='';
    $created_at = date('Y-m-d H:i:s');
    $ph = str_replace(' ', '', $_POST['phone']);
    $ph = str_replace('+91', 'p:+91', $ph);
    if(isset($_POST['source'])) { $src= $_POST['source']; }
    if(isset($_POST['medium'])) { $med= $_POST['medium']; }
    if(isset($_POST['campaign'])) { $camp= $_POST['campaign']; }
    
    
    $leadV = [[mysqli_real_escape_string($conn, $_POST['name']),$_POST['email'],$ph, $_POST['project'], $src,  $med, $camp, date('d-m-Y, h:i a'),'','','','']];
    
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
	//$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code'=>200, 'response'=>"success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}