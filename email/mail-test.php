<?php require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include 'config.php';
$_ENV['STRIPE_SECRET_KEY'] = getenv('STRIPE_SECRET_KEY');
$_ENV['STRIPE_PUBLISHABLE_KEY'] = getenv('STRIPE_PUBLISHABLE_KEY');
$_ENV['STRIPE_WEBHOOK_SECRET'] = 'dg_grill_guru';
$_ENV['DOMAIN'] = 'https://adrescue.in/stripe-payment/2/';
$_ENV['PRICE'] = 'price_12345...';

$input = file_get_contents('php://input');
$body = json_decode($input);
$message = '123';
foreach ($_POST as $key => $value) {
    $message .= "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
}
$duration = '';
$subject = 'Hi!'.$_POST['lead_id'];
$mail_body_1 = "Hi,\n\nHow are you? <pre>".print_r($_POST,true).'</pre>, '.$message;

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					          // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_info;                   // SMTP username
    $mail->Password = $mail_password_info;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from_info, $mailer_name_leads);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$to_address = "prabhu@bytindia.com";
	$mail->addAddress(trim($to_address));
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
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