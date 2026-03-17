<?php require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

/* OUTGOING MAIL CONFIGURATION */ 
$mail_host = "secure.emailsrvr.com"; // Specify main and backup SMTP servers. eg. smtp.gmail.com
$mail_from = "accounts@bytindia.com"; //from email - SMTP username   
$mail_password = "Acc0unt5@123"; // SMTP password  
$mailer_name = "BYT Accounts";
$contact_phone = "7204004567"; //contact phone no 
$tls_ssl = "ssl"; //Enable TLS encryption, `ssl` also accepted
$mail_port = "465";  // TCP port to connect to
//$to_address = "prabhu@bytindia.com,newprabhu@gmail.com"; //use comma for multiple email ids         

$mail_from_leads = "leads@bytindia.com";
$mail_password_leads = "!e@dsANinja";
$mailer_name_leads = "RWD Leads";



$Google_data = file_get_contents("php://input");
$data = json_decode($Google_data, true);

  function loopAllData($data) {
    foreach ($data as $key => $value) {
      if (!is_array($value)) {
        if($key=='column_name') { $results .= $value . ": "; }
        if($key=='string_value') { $results .= $value .  "<br>\r\n"; }
       // $results .= $key . ": " . $value . "<br>\r\n";
      }
      if (is_array($value)) {
        $results .= loopAllData($value);
      }
    }
    return $results;
  }

  // You set this in Google Ads, just under where you put the webhook URL
  $pass="12345";

  //if ( $pass == $data["google_key"]) {
  //    loopAllData($data['user_column_data']);
$input = file_get_contents('php://input');
$body = json_decode($input);
$message = '123';
foreach ($_REQUEST as $key => $value) {
    $message .= "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
}
$duration = '';
$subject = 'RWD GC Google Form Leads '.$_POST['lead_id'];
$mail_body_1 = loopAllData($data['user_column_data']);

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
    $mail->setFrom($mail_from_leads, $mailer_name_leads);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$to_address = "sales@rwd.in, bytramesh@gmail.com";
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