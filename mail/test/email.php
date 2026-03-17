<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);
$from = 'info@bytindia.com';
$pw = '!nf0@12';
try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'secure.emailsrvr.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = $from;                     //SMTP username
    $mail->Password   = $pw;                               //SMTP password
    $mail->SMTPSecure = 'ssl';         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = 465;                                    //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
	$mail->Priority    = 1;
	$mail->Sendmail = '/usr/sbin/sendmail';
    //Recipients
    $mail->setFrom($from, 'Info');
    $mail->addAddress('prabhu@bytindia.com', 'Joe User');     //Add a recipient
    $mail->addAddress('bytprabhu@gmail.com');               //Name is optional

    //Attachments
    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

function SendMail( $ToEmail, $MessageHTML, $MessageTEXT ) {
  //require_once ( 'class.phpmailer.php' ); // Add the path as appropriate
  require 'vendor/autoload.php';
  $Mail = new PHPMailer();
  $Mail->IsSMTP(); // Use SMTP
  $Mail->Host        = "smtp.gmail.com"; // Sets SMTP server
  $Mail->SMTPDebug   = 2; // 2 to enable SMTP debug information
  $Mail->SMTPAuth    = TRUE; // enable SMTP authentication
  $Mail->SMTPSecure  = "tls"; //Secure conection
  $Mail->Port        = 587; // set the SMTP port
  $Mail->Username    = 'newprabhu@gmail.com'; // SMTP account username
  $Mail->Password    = 'Yeshwin30#'; // SMTP account password
  $Mail->Priority    = 1; // Highest priority - Email priority (1 = High, 3 = Normal, 5 = low)
  $Mail->CharSet     = 'UTF-8';
  $Mail->Encoding    = '8bit';
  $Mail->Subject     = 'Test Email Using Gmail';
  $Mail->ContentType = 'text/html; charset=utf-8\r\n';
  $Mail->From        = 'newprabhu@gmail.com';
  $Mail->FromName    = 'GMail Test';
  $Mail->WordWrap    = 900; // RFC 2822 Compliant for Max 998 characters per line

  $Mail->AddAddress( $ToEmail ); // To:
  $Mail->isHTML( TRUE );
  $Mail->Body    = $MessageHTML;
  $Mail->AltBody = $MessageTEXT;
  $Mail->Send();
  $Mail->SmtpClose();

  if ( $Mail->IsError() ) { // ADDED - This error checking was missing
    return FALSE;
  }
  else {
    return TRUE;
  }
}

$ToEmail = 'prabhu@bytindia.com';
$ToName  = 'Name';

$Send = SendMail( $ToEmail, 'hi', 'hi' );
if ( $Send ) {
  echo "<h2> Sent OK</h2>";
}
else {
  echo "<h2> ERROR</h2>";
}
die;