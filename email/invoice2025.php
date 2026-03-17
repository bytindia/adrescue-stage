<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$subj_1 =  $fname.' - Invoice';
$today = date("d-m-Y h:i a"); 

$mail_body_1 = $_POST['body'];
$mail_body_1 = mb_convert_encoding($mail_body_1, 'UTF-8', 'auto');

$root_path = '/home/digitalb2k/stage.adrescue.in/';
$server_path = '/home/digitalb2k/stage.adrescue.in/fb-ads/demo/';

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $acc_em;             // SMTP username
    $mail->Password = $acc_pw;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom($acc_em, $acc_name);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com";
	$to_address = $_POST['to']. ',faheem@bytindia.com,accounts@bytindia.com'; // Add multiple recipients
	//$to_address = "accounts@bytindia.com";
	$path_inv = $root_path.'fb-ads/demo/invoices/';
	$path_rep = $root_path.'download/';
	//$to_address = "prabhu@bytindia.com";
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { if(trim($val)!='') { $mail->addAddress(trim($val)); } }
    //$mail->addBCC($email);               // Name is optional
	foreach ($inv_files as $fname) {
        $fname = trim($fname); // Remove spaces
        if (!empty($fname)) {
            $filePath = $path_inv . $fname.'.pdf';
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath);
            } 
        }
    }
    $fileN = $data['report_files'];
    //echo $path_rep.''.$fileN; 
	if(!empty($_POST['report_files']) && $fileN!='' && file_exists($path_rep.''.$fileN)) {
		$mail->addAttachment($path_rep.''.$fileN);  		   // Add attachments
	}
	if(!empty($_POST['soa_file']) && isset($fileN_SOA) && $fileN_SOA!='' && file_exists($path_rep.''.$fileN_SOA)) {
		$mail->addAttachment($path_rep.''.$fileN_SOA);  		   // Add attachments
	}
	if(!empty($_POST['soa_pi_file']) && isset($fileN_SOA_PI) && $fileN_SOA_PI!='' && file_exists($path_rep.''.$fileN_SOA_PI)) {
		$mail->addAttachment($path_rep.''.$fileN_SOA_PI);  		   // Add attachments
	}
	//foreach($mergeFiles as $val) { 
	//	$mail->addAttachment($path_rep.''.$val.'.csv');  
	//}
	/*
	if($fb_csv!='' && $fb_spend!=0 && file_exists($path_rep.''.$fb_csv.'.csv')) {
    	$mail->addAttachment($path_rep.''.$fb_csv.'.csv');         // Add attachments
		$repMsg = '& Ads Reports detail';
	}
	if($g_csv!='' && $g_spend!=0 && file_exists($path_rep.''.$g_csv.'.csv')) {
		//gCurrency($g_csv, $path_rep);
    	$mail->addAttachment($path_rep.''.$g_csv.'.csv');          // Add attachments
		$repMsg = '& Ads Reports detail';
	}*/
	//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');


    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $_POST['subject'];
    $mail->Body = nl2br($mail_body_1); 
    $mail->send();
		
	/*
	$mail->ClearAllRecipients( );
	$mail->addAddress($email);
	$mail->Subject = $subj_2;
    $mail->Body    = $mail_body_2;
	$mail->send();*/
	if(!isset($_GET['app'])) {
    	echo 'Message has been sent';
	}
} catch (Exception $e) {
    //echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}