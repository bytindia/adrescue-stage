<?php
date_default_timezone_set("Asia/Kolkata");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'email/vendor/autoload.php';
include 'db.php';

if(isset($_POST) && (isset($_POST['pageId']) || isset($_POST['src']) || isset($_POST['account_id'])))
{
	if(isset($_POST['pageId']) && $_POST['pageId']!='')
	{
		$lead = $_POST['lead'];
		$src = 'Facebook - LG';
		$source = $_POST['formName']; //.'/'.$_POST['medium'];
		$medium = $_POST['ad'].' / '.$_POST['adset'];
		$campaign = $_POST['campaign'];
		
		$user = unserialize($lead);
		$name = $user['full_name'];
		$phone = $user['phone_number'];
		$email = $user['email'];
	}
	else if(isset($_POST['src']) && $_POST['src']!='') {
		$lead = $_POST['lead'];
		$src = $_POST['src'];
		$source = $_POST['source']; //.'/'.$_POST['medium'];
		$medium = $_POST['medium'];
		$campaign = $_POST['campaign'];
		
		$user = unserialize($lead);
		$name = $user['name'];
		$phone = $user['phone'];
		$email = $user['email'];
	}
	else if(isset($_POST['account_id']) && $_POST['account_id']!='') {
		$lead1 = array();
		for($i=1;$i<=6;$i++) {
			if(isset($_POST["questions__".$i])) {
				$lead1[] =  $_POST["questions__".$i];
			}
		}
		
		$lead2 = serialize($lead1);
		$src = 'LinkedIn';
		$source = $_POST['ad__form__name']; //.'/'.$_POST['medium'];
		$medium = $_POST['ad__form__name'];
		$campaign = $_POST['campaign__name'];
		
		$user = unserialize($lead2);
		$name = $user[0].' '.$user[1];
		$phone = $user[3];
		$email = $user[2];
		$lead = serialize($_POST);
	}
	$q3 = "SELECT tbl_id FROM ep_leads WHERE (email='".$email."' || phone='".$phone."')";
	$r3 = mysqli_query($conn, $q3);
	if(mysqli_num_rows($r3)==0) { 
		$sqlQuery = "INSERT INTO ep_leads (name, email, phone, lead, source, src, med, camp, created) VALUES ('".mysqli_real_escape_string($conn, $name)."', '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, $phone)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $source)."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $medium)."', '".mysqli_real_escape_string($conn, $campaign)."', now());";
		mysqli_query($conn, $sqlQuery) or die(mysqli_error()); 
		echo 'success';
	}
}
exit;

//Load Composer's autoloader

include 'email/config.php';
/* OUTGOING MAIL CONFIGURATION */ 
$mail_host = "secure.emailsrvr.com"; // Specify main and backup SMTP servers. eg. smtp.gmail.com
$mail_from = "accounts@bytindia.com"; //from email - SMTP username   
$mail_password = "Acc0unt5@123"; // SMTP password  
$mailer_name = "BYT Accounts";
$contact_phone = "7204004567"; //contact phone no 
$tls_ssl = "ssl"; //Enable TLS encryption, `ssl` also accepted
$mail_port = "465";  // TCP port to connect to
$to_address = "prabhu@bytindia.com,newprabhu@gmail.com"; //use comma for multiple email ids         

$mail_from_info = "info@bytindia.com";
$mail_password_info = "!nf0@12";
$mailer_name_info = "AdsNinja Report";

$mail_from_leads = "leads@bytindia.com";
$mail_password_leads = "!e@dsANinja";
$mailer_name_leads = "FB Leads";

$duration = '';
$subject = 'Hi!'.$_POST['name'];
$mail_body_1 = "Hi,\n\nHow are you? ".$_GET['smsNo'].', '.$_GET['smsBody'];

$data = '<pre>'.print_r( $_POST, true ).'</pre>';
foreach ($_POST as $key=>$value){
    $data .= $key.'-------'.$value;
    $data.= "\n";
}

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try { 
    //Server settings
    $mail->SMTPDebug = 1;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_leads;             // SMTP username
    $mail->Password = $mail_password_leads;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from_leads, $mailer_name_leads);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$to_address = "prabhu@bytindia.com";
	$mail->addAddress(trim($to_address));
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $data;    
    $mail->send();
	/*
	$mail->ClearAllRecipients( );
	$mail->addAddress($email);
	$mail->Subject = $subj_2;
    $mail->Body    = $mail_body_2;
	$mail->send();*/
    //echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}