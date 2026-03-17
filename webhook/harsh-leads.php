<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
/*
require $dirPath.'email/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include $dirPath.'email/config.php';*/
/*$mail_from_leads = "sales@edenpark.net";
$mail_password_leads = "WOXRxClrCktS";
$mailer_name_leads = "Edenpark";
$mail_host = 'sg2plcpnl0131.prod.sin2.secureserver.net';
$mail_port = 465;*/


//$_POST['name'] = 'test';

//if ( $pass == $data["google_key"]) {
//    loopAllData($data['user_column_data']);


$input = file_get_contents('php://input');
$lead = json_decode($input,true);
if(isset($lead) && count($lead)>0) {

    $lead = $lead[0];
    $src = $med = $camp ='';
    if(parse_url($lead['url'], PHP_URL_QUERY)){
        $parts = parse_url($lead['url']);
        parse_str($parts['query'], $query);
        $src = $query['utm_source'];
        $med = $query['utm_medium'];
        $camp = $query['utm_campaign'];
    }
    
    $dt = $lead['date'].' '.$lead['time'];
    $rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $created_at = $rnage1->format('Y-m-d H:i:s');

    $cirSql = "INSERT INTO harshdave_leads (name, email, phone, src, med, camp, full_url, created_at, created) VALUES ('".mysqli_real_escape_string($conn, $lead['name'])."',  '".mysqli_real_escape_string($conn, $lead['email'])."', '".mysqli_real_escape_string($conn, $lead['phone'])."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $med)."', '".mysqli_real_escape_string($conn, $camp)."', '".mysqli_real_escape_string($conn, $lead['url'])."',  '".$created_at."', now());"; 
    mysqli_query($conn, $cirSql) or die(mysqli_error());

    $data = array('code'=>200, 'response'=>"success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}

