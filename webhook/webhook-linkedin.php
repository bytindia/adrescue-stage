<?php
//echo 'success';

$client_id = '86qvqy176u7bqs';
$client_secret = getenv('LINKEDIN_CLIENT_SECRET_2');

if (isset($_REQUEST['challengeCode'])) {
    header('Content-Type: application/json');
    $res = json_encode([
        'challengeCode' => $_REQUEST['challengeCode'],
        'challengeResponse' => hash_hmac('sha256', $_REQUEST['challengeCode'], $client_secret),
    ]);
    echo $res;
   // exit;
}
exit;
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';

require $dirPath.'email/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include $dirPath.'email/config.php';
/*$mail_from_leads = "sales@edenpark.net";
$mail_password_leads = "WOXRxClrCktS";
$mailer_name_leads = "Edenpark";
$mail_host = 'sg2plcpnl0131.prod.sin2.secureserver.net';
$mail_port = 465;*/


//$_POST['name'] = 'test';

//if ( $pass == $data["google_key"]) {
//    loopAllData($data['user_column_data']);
$input = file_get_contents('php://input');
$body = json_decode($input);
$message = 'Post: <pre>'.print_r($res,true).'</pre>';
$message .= 'Post: <pre>'.print_r($_REQUEST,true).'</pre>';
foreach ($_POST as $key => $value) {
   $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
    
}
//echo $message;
//print_r($_POST); exit;

$duration = '';
$subject = 'test';
$mail_body_1 = $message;

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					          // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_leads;                   // SMTP username
    $mail->Password = $mail_password_leads;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    
    $mail->setFrom($mail_from_leads, 'Linkedin webhook - test');
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	//$to_address = "bytprabhu@gmail.com,prabhu@bytindia.com,mahesha.reddy@lucep.com";
    $to_address = "bytprabhu@gmail.com";
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
