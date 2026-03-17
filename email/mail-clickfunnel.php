<?php /*
session_start(); 
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
error_log( "Hello, errors!" );

date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/vendor/autoload.php';

include 'db.php';
include 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader

$input = json_decode(file_get_contents('php://input'), true);
$subj_1 = "Clickfunnel - webhook";
$message = "";
foreach ($input as $key => $value) {
    $message .= "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
}
$duration = '';
//$subject = 'Hi!'.$_POST['lead_id'];
$mail_body_1 = "<pre>".print_r($input,true).'</pre>, '.$message;	

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

    //Recipients
    $mail->setFrom($mail_from, $mailer_name);
    //$mail->addAddress($receive_email);     // Add a recipient
	$to_address = "prabhu@bytindia.com";
	//$to_address = "prabhu@bytindia.com";
	//$to_address = "prabhu@bytindia.com";
	//$path_inv = $server_path.'invoices/';
	//$path_rep = $server_path.'download/';
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val)); }
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subj_1;
    $mail->Body    = $mail_body_1;    
   // $mail->send();
	
    //echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}
*/
header('Content-Type: application/json; charset=utf-8');
$given = new DateTime();
$given->setTimezone(new DateTimeZone("UTC"));
$date = array('time'=>$given->format("Y-m-d H:i:s e"));
echo json_encode($date,true);