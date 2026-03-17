<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'; 

$sheetTab='FB Leads';

$sheetTab='Retreat Leads'; 


$input = file_get_contents('php://input');
$lead = json_decode($input,true);


if(count($lead)>0) {
    
   // $dt = $lead['date'].' '.$lead['time'];
    //$rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $src= $med= $camp= $term=  $content='';
    $created_at = date('Y-m-d H:i:s');
    $ph = str_replace(' ', '', $lead['phone_1']);
    $ph = str_replace('+91', 'p:+91', $ph);
    if(isset($lead['hidden_1'])) { $src= $lead['hidden_1']; }
    if(isset($lead['hidden_2'])) { $med= $lead['hidden_2']; }
    if(isset($lead['hidden_3'])) { $camp= $lead['hidden_3']; }
    if(isset($lead['hidden_4'])) { $term= $lead['hidden_4']; }
    if(isset($lead['hidden_5'])) { $content= $lead['hidden_5']; }

    $cirSql = "INSERT INTO ananta_leads (name, email, phone, src, med, camp, term, content, full_url, created_at, created) VALUES ('".mysqli_real_escape_string($conn, $lead['name_1'])."',  '".mysqli_real_escape_string($conn, $lead['email_1'])."', '".mysqli_real_escape_string($conn, $ph)."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $med)."', '".mysqli_real_escape_string($conn, $camp)."', '".mysqli_real_escape_string($conn, $term)."', '".mysqli_real_escape_string($conn, $content)."', '".mysqli_real_escape_string($conn, $lead['_wp_http_referer'])."',  '".$created_at."', now());"; 

    mysqli_query($conn, $cirSql) or die(mysqli_error());
    //$lastId = mysqli_insert_id($conn);
    $sheetTab='Retreat Leads'; 
    $leadV = [[mysqli_real_escape_string($conn, $lead['name_1']),$lead['email_1'],$ph," ", date('d-m-Y, h:i a'),$src,$med,$camp]];
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
	//$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code'=>200, 'response'=>"success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
/*
require $dirPath.'email/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include $dirPath.'email/config.php';

$input = file_get_contents('php://input');
$body = json_decode($input,true);
$message = 'Post: <pre>'.print_r($body).'</pre>';
foreach ($body as $key => $value) {
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
    
    $mail->setFrom($mail_from_leads, 'Adrescue webhook - test');
    $to_address = "bytprabhu@gmail.com";
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val));  }
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $mail_body_1;    
    $mail->send();
	
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}

$data = array('code'=>200, 'response'=>"success");
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data); */