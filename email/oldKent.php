<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception; 

//$leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['form_name'],$_POST['source'],'LP', date('d-m-Y, h:i a')]];
//append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);

echo 1; 
//Load Composer's autoloader

$mailer_name_leads = "LP Enquiry";

$message = '';
foreach ($_POST as $key => $value) {
    //if($key!='cId' && $key!='src_F_G' && $key!='project'&& $key!='sub_src2') {
        if($key=='src') { $key='source'; }
        if($key=='sub_src') { $key='campaign'; }
        if($key!='api_res'){
            $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
        }
    //}
}
echo 2; 



$name = $_POST['name'];
		$email = $_POST['email'];
		$phone = $_POST['phone'];
        $interested = $looking = $course = $camp_type = '';
		$city = '';
		$state = '';
		$source = $_POST['source'];
		$campaign =  '';
		$project =  '';
		$project_id = 1;

        if(isset($_POST['city'])) { $city = $_POST['city']; }
        if(isset($_POST['state'])) { $state = $_POST['state']; }
        if(isset($_POST['interested'])) { $interested = $_POST['interested']; $camp_type = 'pmax'; }
        if(isset($_POST['looking'])) { $looking = $_POST['looking']; $camp_type = 'pmax'; }
		

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
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
	$emailIds = "sadat.sathak@gmail.com,bytramesh@gmail.com";
	if($emailIds!='') { $to_address = $emailIds; } else { $emailIds = "sadat.sathak@gmail.com,bytramesh@gmail.com"; }
	//$to_address = "prabhu@bytindia.com";
	
	
	
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val)); }
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'OldKent - LP Enquiry';
    $mail->Body    = $message;    
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