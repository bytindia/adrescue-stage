<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader

$subj_1 = "Ads Budget ".$duration;

$mail_body_1 = "
\n
".$tbl."

<br/><br/> \n

Regards, <br/> \n
BYT Ads Report.

<br/><br/> \n
<span style='font-size:12px; color:#6F6E6E;'><b>Note:</b> This Email is automate system generated reports. For any queries / issues, please email to prabhu@bytindia.com.<span>";	

//$subj_1 = "BYT - Leads Analysis Report";
$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_info;             // SMTP username
    $mail->Password = $mail_password_info;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from_info, $mailer_name_info);
    //$mail->addAddress($receive_email);     // Add a recipient
	if($to_address=='') {
		$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, accounts@bytindia.com, ramesh@bytindia.com";
	}
	//$to_address = "prabhu@bytindia.com";
	//$to_address = "prabhu@bytindia.com";
	$path_inv = $server_path.'invoices/';
	$path_rep = $server_path.'download/';
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val)); }
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subjLine;
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