<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$subj_1 =  $fname.' - Invoice';
$today = date("d-m-Y h:i a"); 

$mail_body_1 = "Dear Sir/Madam, <br/><br/> \n
PFA the Invoice & Ads Reports detail
<br/><br/>
Regards,<br/>
Komal Jain<br/>
+91-44-4353 6664
";		

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from;             // SMTP username
    $mail->Password = $mail_password;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from, $mailer_name);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com";
	$to_address = "accounts@bytindia.com".$to_address;
	$path_inv = $server_path.'invoices/';
	$path_rep = $server_path.'download/';
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val));  }
    //$mail->addBCC($email);               // Name is optional
	if($fname!='' && file_exists($path_inv.''.$fname.'.pdf')) {
		 $mail->addAttachment($path_inv.''.$fname.'.pdf');  		   // Add attachments
	}
	if($fname!='' && file_exists($path_inv.''.$fname.'_2'.'.pdf')) {
		 $mail->addAttachment($path_inv.''.$fname.'_2'.'.pdf');  		   // Add attachments
	}
	if($fb_csv!='' && $fb_spend!=0 && file_exists($path_rep.''.$fb_csv.'.csv')) {
    	$mail->addAttachment($path_rep.''.$fb_csv.'.csv');         // Add attachments
	}
	if($g_csv!='' && $g_spend!=0 && file_exists($path_rep.''.$g_csv.'.csv')) {
		gCurrency($g_csv, $path_rep);
    	$mail->addAttachment($path_rep.''.$g_csv.'~.csv');          // Add attachments
	}
	//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subj_1;
    $mail->Body    = $mail_body_1;    
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