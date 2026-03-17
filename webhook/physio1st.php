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
$spreadsheetId='1gEK0HAZLl8DfmWyFMK9P8vjJVxSa-ZEVqvCWUZOtGJI'; 

$sheetTab='Google';



$input = file_get_contents('php://input');
$lead = json_decode($input,true);

d($lead);
if(count($lead)>0) {
    
   // $dt = $lead['date'].' '.$lead['time'];
    //$rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $src= $med= $camp= $term=  $content='';
    $created_at = date('Y-m-d H:i:s');
    $ph = str_replace(' ', '', $lead['phone']);
    $ph = str_replace('+91', 'p:+91', $ph);
    if(isset($lead['source'])) { $src= $lead['source']; }
    if(isset($lead['message'])) { $src= $lead['message']; }
    if(isset($lead['medium'])) { $med= $lead['medium']; }
    if(isset($lead['campaign'])) { $camp= $lead['campaign']; }

    //$cirSql = "INSERT INTO ananta_leads (name, email, phone, src, med, camp, term, content, full_url, created_at, created) VALUES ('".mysqli_real_escape_string($conn, $lead['name_1'])."',  '".mysqli_real_escape_string($conn, $lead['email_1'])."', '".mysqli_real_escape_string($conn, $ph)."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $med)."', '".mysqli_real_escape_string($conn, $camp)."', '".mysqli_real_escape_string($conn, $term)."', '".mysqli_real_escape_string($conn, $content)."', '".mysqli_real_escape_string($conn, $lead['_wp_http_referer'])."',  '".$created_at."', now());"; 

   // mysqli_query($conn, $cirSql) or die(mysqli_error());
    //$lastId = mysqli_insert_id($conn);
    $sheetTab='Retreat Leads'; 
    $leadV = [[mysqli_real_escape_string($conn, $lead['name']),$lead['email'], $ph, mysqli_real_escape_string($conn, $lead['message']), $src, $med, $camp, date('d-m-Y, h:i a')]];
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
	//$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code'=>200, 'response'=>"success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}