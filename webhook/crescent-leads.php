<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 0); //ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  

//$spreadsheetId='1wfWbr9464dB_Pkaak4oCMZa7PuJ6dZUIDcE2AQ0v9BQ'; //2024
$spreadsheetId='1zGz75wvrNlHpmtnlb0WTR2t-UYYTEHybo30-tvAuNL4'; //2025
$tab_list = array(
    1=>'ARCHITECTURE',
    2=>'ASC',
    3=>'BRAND',
    4=>'ENGG',
    5=>'LAW',
    6=>'MBA',
    7=>'PG',
    8=>'PHARMACY',
    9=>'PUBLIC_POLICY',
    10=>'Online MBA',
    11=>'Online MCA'
);

$sheetTab='FB Leads';

$sheetTab='Retreat Leads'; 


$input = file_get_contents('php://input');
$lead = json_decode($input,true);
//d($lead); exit;

if(is_array($lead) && count($lead)>0) {
    
    // $dt = $lead['date'].' '.$lead['time'];
    //$rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $src= $med= $camp= $term=  $content = $level = $stream = '';

    $created_at = date('Y-m-d H:i:s');

    $page_id = $lead['page_id'];

    $name = mysqli_real_escape_string($conn, $lead['name']);
    $email = mysqli_real_escape_string($conn, $lead['email']);
    $state = mysqli_real_escape_string($conn, $lead['state']);
    $city = mysqli_real_escape_string($conn, $lead['city']);
    $programme = mysqli_real_escape_string($conn, $lead['programme']);
    $utm_source = mysqli_real_escape_string($conn, $lead['utm_source']);
    $utm_campaign = mysqli_real_escape_string($conn, $lead['utm_campaign']);
    $utm_medium = mysqli_real_escape_string($conn, $lead['utm_medium']);
    $created = $lead['created'];

    $ph1 = str_replace(' ', '', $lead['phone']);
    $ph = str_replace('+91', 'p:+91', $ph1);

    if(isset($lead['level'])) { $level = $lead['level']; }
    if(isset($lead['stream'])) { $stream = $lead['stream']; }

    $sqlQuery = "INSERT INTO crescent_leads_2025 (name, email, phone,  course, city, state, source, campaign, medium, project, project_id, created) VALUES ('".mysqli_real_escape_string($conn, $name)."', '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, $ph1)."', '".mysqli_real_escape_string($conn, $programme)."', '".mysqli_real_escape_string($conn, $city)."', '".mysqli_real_escape_string($conn, $state)."', '".mysqli_real_escape_string($conn, $utm_source)."', '".mysqli_real_escape_string($conn, $utm_campaign)."', '".mysqli_real_escape_string($conn, $utm_medium)."', '".mysqli_real_escape_string($conn, $tab_list[$page_id])."', '".mysqli_real_escape_string($conn, $page_id)."', now());"; 
	mysqli_query($conn, $sqlQuery) or die(mysqli_error()); 

    
    if($page_id==3 || $page_id==7) {
        $leadV = [[$name, $email, $ph, $state, $city, $programme, $level, $stream, $utm_source, $utm_campaign, $utm_medium, $created]];
    } else {
        $leadV = [[$name, $email, $ph, $state, $city, $programme, $utm_source, $utm_campaign, $utm_medium, $created]];
    }
    if($ph!='' || $email!=''){
        $sheetTab = $tab_list[$page_id];
        append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); 

        $data = array('code'=>200, 'response'=>"success");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);

    }
    
}