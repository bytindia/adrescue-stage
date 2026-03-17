<?php
session_start(); 
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
error_log( "Hello, errors!" );

function sendWhatsapp($tok, $phone, $res_msg) {
    $attachment =  array(
        
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type'=> 'template',
        'template' => 
          json_encode(
            array(
              'name' => 'one_line_notification', 
              'language' => array('code'=>'en_US'), 
              'components'=> 
                array(array(
                "type" => "body",
                "parameters" => array(
                    array("type"=> "text","text"=> $res_msg)
                )))
          ))
        );
        $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
        $post = json_encode($attachment); // Encode the data array into a JSON string
        $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection
        return json_decode($result); // Return the received data
        //print_r($result); 
  
}

$challenge = $_REQUEST['hub_challenge'];
$verify_token = $_REQUEST['hub_verify_token'];

if ($verify_token === 'bytads2023') {
echo $challenge;
}

date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';

$challenge = $_REQUEST['hub_challenge'];
$verify_token = $_REQUEST['hub_verify_token'];
$input = json_decode(file_get_contents('php://input'), true);

$fromNo = array('919176299010','918825787703', '919840619930');
$res_msg = '';
if(isset($input['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'])) {
    $msg_txt = $input['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];
    $msg_from = $input['entry'][0]['changes'][0]['value']['messages'][0]['from'];

    if(substr(trim(strtolower($msg_txt)), 0, 15) === "budget received" && in_array($msg_from, $fromNo)){
        
        $msg_txt2 = substr(trim(strtolower($msg_txt)), 15);
        $msgTxt_ar = explode("_", $msg_txt2); 

        $client = trim($msgTxt_ar[0]);
        $budget = str_replace(" ","",trim($msgTxt_ar[1]));
        $budget = str_replace(",","",trim($budget));
        //include 'email/mail-adrule.php'; 

        $sqlQ= "SELECT * FROM budget_reminder WHERE tags like '%".$client."%'";
        $sqlD = mysqli_query($conn, $sqlQ);
        $totRow = mysqli_num_rows($sqlD);

        if($totRow==1){
            $row = mysqli_fetch_assoc($sqlD);
	        $updated = $row['updated']; 
            $budget_received = $row['budget_received']; 
            $updated_dt = date ('Y-m-d', strtotime($updated));
            if(date("m", strtotime($updated_dt)) == date("m"))
            { 
                $budget = $budget + $budget_received;
                $cirSql = "UPDATE budget_reminder SET budget_received = '". $budget."', updated=now() WHERE  tags like '%".$client."%'";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
                $res_msg = 'Budget has been updated successfully!';
            }
            else
            {
                $cirSql = "UPDATE budget_reminder SET budget_received = '". $budget."', updated=now() WHERE  tags like '%".$client."%'";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
                $res_msg = 'Budget has been updated successfully!';
            }
        } else {
            $res_msg = 'Budget not updated! incorrect message template or client name!';
        }
    }

} else {
    //exit;
}

if($res_msg!=''){
    $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $access_token = $row['access_token']; 
    $wa_res = sendWhatsapp($access_token, $msg_from,  $res_msg);
    $wa_res = json_decode(json_encode($wa_res), true);
}



if ($verify_token === 'bytads2019') {
    echo $challenge;
}


include 'email/mail-adrule.php';  
echo 'Email Sent!'; 