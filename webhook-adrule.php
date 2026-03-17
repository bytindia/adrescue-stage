<?php //echo 1; exit;
session_start(); 
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
error_log( "Hello, errors!" );

date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';

$challenge = $_REQUEST['hub_challenge'];
$verify_token = $_REQUEST['hub_verify_token'];
$input = json_decode(file_get_contents('php://input'), true);

$fromNo = array('919176299010');

if(isset($input['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'])) {
    $msg_txt = $input['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];
    $msg_from = $input['entry'][0]['changes'][0]['value']['messages'][0]['from'];

    if(substr(trim(strtolower($msg_txt)), 0, 13) === "budget update" && in_array($msg_from, $fromNo)){
        
        $msg_txt2 = substr(trim(strtolower($msg_txt)), 13);
        $msgTxt_ar = explode("_", $msg_txt2); 

        $client = trim($msgTxt_ar[0]);
        $budget = $result = preg_replace('/[ ,]+/', '-', trim($msgTxt_ar[1]));
        include 'email/mail-adrule.php'; 
    }

}


if ($verify_token === 'bytads2019') {
    echo $challenge; exit;
}


include 'email/mail-adrule.php';  
echo 'Email Sent!'; 