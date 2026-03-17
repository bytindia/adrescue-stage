<?php require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include 'config.php';
$conn =  mysqli_connect('localhost', 'digitalb2k_adsninja', getenv('DB_PASS'), 'digitalb2k_adsninja'); 
mysqli_select_db($conn,'digitalb2k_adsninja');


$name = $_POST['name'];
		$email = $_POST['email'];
		$phone = $_POST['phone'];

        $source = $_POST['source'];
		$form_name = $_POST['form_name'];
		$creat = $_POST['creat'];
        
		$message="<h2>DLF Riverside Landing Page Enquiry:</h2><table style=border:1px solid;><tr><td>Name: </td><td>".$name."</td></tr><tr><td>Email: </td><td>".$email."</td></tr><tr><td>Phone: </td><td>".$phone."</td></tr><tr><td>Source: </td><td>".$source."</td></tr><tr><td>Form: </td><td>".$form_name."</td></tr><tr><td>Created: </td><td>".$creat."</td></tr></table>";
//echo $message;
//print_r($_POST); exit;

$duration = '';
$subject = 'DLF Riverside - LP Enquiry';
$mail_body_1 = $message;

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
    $mail->setFrom($mail_from_info, 'DLF Riverside - Enquiry');
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$to_address = "zaffer@dlf.in, kumar-lalith@dlf.in, bytramesh@gmail.com";
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