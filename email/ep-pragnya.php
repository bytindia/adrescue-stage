<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require 'vendor/autoload.php';

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1_IhZpagR02JRyzKW7HwHN1oXj0ml3lHtICV6Baz6ENw'; 

$sheetTab='LP Leads';

use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception; 

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

$name = $_POST['name'];
		$email = $_POST['email'];
		$phone = $_POST['phone'];
        $interested = $looking = $course = $camp_type = '';
		$city = '';
		$state = '';
		$source = $_POST['source'];
		$campaign =  $form_name = '';
		$project =  '';
		$project_id = 1;

$url = "https://www.thesalezrobot.com/public/api/WebformIntegration";

		// Parameters
		$params = [
			'webformid'    => '10',
			'moduletype'   => 'Basic',
			'company_name' => 'PRAGNYA',
			'name'         => $name,
			'mobileno'     => $phone,
			'email'        => $email,
			'medium'       => $source,
			'location'     => '',
			'description'  => ''
		];

		// Build full URL with query parameters
		$fullUrl = $url . '?' . http_build_query($params);

		// Initialize cURL
		$ch = curl_init();

		// cURL options
		curl_setopt_array($ch, [
			CURLOPT_URL            => $fullUrl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json'
			]
		]);

		// Execute and capture response
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$fp = fopen('rsoft.txt', 'w');
	/*fwrite($fp, $response);
	fclose($fp);
		curl_close($ch);*/

        
        if(isset($_POST['project'])) { $project =  $_POST['project']; }

        if(isset($_POST['city'])) { $city = $_POST['city']; }
        if(isset($_POST['state'])) { $state = $_POST['state']; }
        if(isset($_POST['interested'])) { $interested = $_POST['interested']; $camp_type = 'pmax'; }
        if(isset($_POST['looking'])) { $looking = $_POST['looking']; $camp_type = 'pmax'; }
		if(isset($_POST['form_name'])) { $form_name = $_POST['form_name']; }

        $api_res = '';
        if(isset($_POST['api_res'])) { $api_res = $_POST['api_res']; }

		//$sqlQuery = "INSERT INTO jain_antareeksh  (name, email, phone, course, city, state, source, campaign, project, project_id, interested, looking, camp_type, api_res, created) VALUES ('".mysqli_real_escape_string($conn, $name)."', '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, $phone)."', '".mysqli_real_escape_string($conn, $course)."', '".mysqli_real_escape_string($conn, $city)."', '".mysqli_real_escape_string($conn, $state)."', '".mysqli_real_escape_string($conn, $source)."', '".mysqli_real_escape_string($conn, $campaign)."', '".mysqli_real_escape_string($conn, $project)."', '".mysqli_real_escape_string($conn, $project_id)."', '".mysqli_real_escape_string($conn, $looking)."', '".mysqli_real_escape_string($conn, $interested)."', '".mysqli_real_escape_string($conn, $camp_type)."', '".mysqli_real_escape_string($conn, $api_res)."', now());"; 
		//mysqli_query($conn, $sqlQuery) or die(mysqli_error()); 

        $leadV = [[mysqli_real_escape_string($conn, $name),$email,'p:'.$phone,mysqli_real_escape_string($conn, $source), date('d-m-Y, h:i a')]];
        append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); 

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->IsHTML(true);
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
	$emailIds = "syed.m@pragnyaedenpark.com, benjose@pragnyaedenpark.com, bytramesh@gmail.com";
	if($emailIds!='') { $to_address = $emailIds; } else { $emailIds = "bytprabhu@gmail.com"; }
	//$to_address = "prabhu@bytindia.com";
	
	
	
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val)); }
    

    $mail->Subject = 'EdenPark Landing Page Enquiry';
    
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