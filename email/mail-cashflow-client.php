<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader

if($em_type==1) {
	$subj_1 = "Overdue Rs. ".str_replace(".00","",$ad_bal).". Will affect leads! ".$client;
	$mail_body_1 = "
	Hi, <br/><br/> \n
	Your ads spend is <b>Rs. ".str_replace(".00","",$ad_bal)."</b> due. This needs to be cleared immediately to ensure spends & leads are not affected.<br/> \n 
	Can we expect the payments by today?<br/><br/> \n
	
	Regards, <br/> \n
	BYT Account.";		
} else {
	$subj_1 = "Leads: Only ".$days_left.". days remaining! ".$client;
	$mail_body_1 = "
	Hi, <br/><br/> \n
	Only ads spend is available & it will exhaust in <b>".$days_left." days</b>. Can you please release the funds immediately to ensure spends & leads are not affected. <br/> \n 
	Can we expect the payments by today?<br/><br/> \n
	
	Regards, <br/> \n
	BYT Account.";	
}


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
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, accounts@bytindia.com, ramesh@bytindia.com";
	//$to_address = "prabhu@bytindia.com";
	//$to_address = "prabhu@bytindia.com";
	$path_inv = $server_path.'invoices/';
	$path_rep = $server_path.'download/';
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { 
		if(trim($val)!='') {
			$mail->addAddress(trim($val)); 
		}
	}

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