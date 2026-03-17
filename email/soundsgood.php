<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception; 

        $dirPath = '/home/digitalb2k/stage.adrescue.in/';
        include $dirPath.'db.php';
        require_once $dirPath.'google-sheets-api/vendor/autoload.php';
        require_once $dirPath.'google-sheets-api/class-db.php';
        require_once $dirPath.'google-sheets-api/config.php';
        include $dirPath.'google-sheets-api/insert-row.php';
        $uId=2; 
        $tbl_id=2;  
        $spreadsheetId='1urKxZu7aaDHF-7SLX1mMZLDyRC9Vg-QeGgRqHTYGNJU'; 

        
        $sheetTab='Leads';

        $leadV = [[
            date('F'),
            date('m/d/Y'),
            '',
            '',
            mysqli_real_escape_string($conn, $_POST['name']),
            mysqli_real_escape_string($conn, $_POST['mobile']),
            mysqli_real_escape_string($conn, $_POST['city']), 
            '',
            '',
            mysqli_real_escape_string($conn, $_POST['source']),
            mysqli_real_escape_string($conn, $_POST['campaign']), 
            '',
            '',
            '',
            mysqli_real_escape_string($conn, '['.date('d-m-Y').']: Mode Of Consultation: '.$_POST['mode']), 
            '',
            '',
            '',
            '',
            '',
            '',
            mysqli_real_escape_string($conn, $_POST['email'])
            ]];
        append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);

//$leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['form_name'],$_POST['source'],'LP', date('d-m-Y, h:i a')]];
//append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);

echo 1; 
//Load Composer's autoloader

$mailer_name_leads = "LP Enquiry";

$message = '';
foreach ($_POST as $key => $value) {
    //if($key!='cId' && $key!='src_F_G' && $key!='project'&& $key!='sub_src2') {
        if($key=='src') { $key='source'; }
        if($key=='sub_src') { $key='campaign'; }
        if($key!='api_res'){
            $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
        }
    //}
}
echo 2; 



		//$sqlQuery = "INSERT INTO jain_antareeksh  (name, email, phone, course, city, state, source, campaign, project, project_id, interested, looking, camp_type, api_res, created) VALUES ('".mysqli_real_escape_string($conn, $name)."', '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, $phone)."', '".mysqli_real_escape_string($conn, $course)."', '".mysqli_real_escape_string($conn, $city)."', '".mysqli_real_escape_string($conn, $state)."', '".mysqli_real_escape_string($conn, $source)."', '".mysqli_real_escape_string($conn, $campaign)."', '".mysqli_real_escape_string($conn, $project)."', '".mysqli_real_escape_string($conn, $project_id)."', '".mysqli_real_escape_string($conn, $looking)."', '".mysqli_real_escape_string($conn, $interested)."', '".mysqli_real_escape_string($conn, $camp_type)."', '".mysqli_real_escape_string($conn, $api_res)."', now());"; 
		//mysqli_query($conn, $sqlQuery) or die(mysqli_error()); 

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_leads;             // SMTP username
    $mail->Password = $mail_password_leads;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from_leads, $mailer_name_leads);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$emailIds = "sylviasoundsgood2022@gmail.com";
	if($emailIds!='') { $to_address = $emailIds; } else { $emailIds =  "sylviasoundsgood2022@gmail.com"; }
	//$to_address = "prabhu@bytindia.com";
	
	
	
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val)); }
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Soundsgood - LP Enquiry';
    
    $mail->Body    = $message;    
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