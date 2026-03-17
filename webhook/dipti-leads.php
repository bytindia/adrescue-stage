<?php

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
if(count($lead)>0) {
    $cirSql = "INSERT INTO dipti_leads (lead_id, page_id, name, name2, email, phone, city, src, med, camp, full_url, created_at, created) VALUES ('".mysqli_real_escape_string($conn, $lead['lead_id'])."', '".mysqli_real_escape_string($conn, $lead['page_id'])."', '".mysqli_real_escape_string($conn, $lead['firstname'])."', '".mysqli_real_escape_string($conn, $lead['lastname'])."', '".mysqli_real_escape_string($conn, $lead['youremail'])."', '".mysqli_real_escape_string($conn, $lead['yourwhatsappnumber'])."', '".mysqli_real_escape_string($conn, $lead['city'])."', '".mysqli_real_escape_string($conn, $lead['page_parameters']['utm_source'])."', '".mysqli_real_escape_string($conn, $lead['page_parameters']['utm_medium'])."', '".mysqli_real_escape_string($conn, $lead['page_parameters']['utm_campaign'])."','".mysqli_real_escape_string($conn, $lead['page_url'])."', '".date('Y-m-d h:i:s', strtotime($lead['created_at']))."', now());"; 
    mysqli_query($conn, $cirSql) or die(mysqli_error());
}

