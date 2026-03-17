<?php require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include 'config.php';





  //if ( $pass == $data["google_key"]) {
  //    loopAllData($data['user_column_data']);
$input = file_get_contents('php://input');
$body = json_decode($input);
$message = '';
foreach ($_POST as $key => $value) {
    $message .= "".htmlspecialchars($key)." : ".htmlspecialchars($value)."<br>";
}
$phone = $name = $email = $site_visit = '';
if(isset($_POST['phone'])) { $phone = $_POST['phone']; }
if(isset($_POST['name'])) { $name = $_POST['name']; }
if(isset($_POST['email'])) { $email = $_POST['email']; }
if(isset($_POST['site_visit'])) { $site_visit = $_POST['site_visit']; }

$cId = 12;
$projectKey = "Ashraya";

$post = ['name' => $name, 'email' => $email, 'phone' =>  str_replace(' ', '', $phone), 'src' => 'Collect.Chat', 'sub_src' => '', 'cId' => $cId, 'project' => trim($projectKey), 'src_F_G' => 'Chat'];
$ch = curl_init('http://adrescue.in/sn-api/addlead');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
$response = curl_exec($ch);
curl_close($ch);	

$duration = '';
$subject = 'BYT Chatbot - LP Enquiry';
$mail_body_1 = $message;

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					          // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_leads;                   // SMTP username
    $mail->Password = $mail_password_leads;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from_leads, 'Chatbot Lead');
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$to_address = "enquiry@altisville.com,bytramesh@gmail.com";
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val));  }
    

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