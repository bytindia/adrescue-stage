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
        $spreadsheetId='1aVuq6C4mEzn0aaTDMa-ONxB4FomijpOfdkLgx5zoFH4'; 

        
        $sheetTab='LP Leads';

        $leadV = [[
           
            mysqli_real_escape_string($conn, $_POST['name']),
            mysqli_real_escape_string($conn, $_POST['mobile']),
            mysqli_real_escape_string($conn, $_POST['email']),
            date('m/d/Y'),
            mysqli_real_escape_string($conn, $_POST['message']), 
            mysqli_real_escape_string($conn, $_POST['source']),
            mysqli_real_escape_string($conn, $_POST['campaign']), 
         
            ]];
        append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);

//$leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['form_name'],$_POST['source'],'LP', date('d-m-Y, h:i a')]];
//append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);

echo 1; 


$name     = isset($_POST['name']) ? $_POST['name'] : '';
$email    = isset($_POST['email']) ? $_POST['email'] : '';
$mobile   = isset($_POST['mobile']) ? $_POST['mobile'] : '';
$messageText = isset($_POST['message']) ? $_POST['message'] : '';
$utm_source = isset($_POST['source']) ? $_POST['source'] : '';
$utm_medium = isset($_POST['medium']) ? $_POST['medium'] : '';
$utm_campaign = isset($_POST['campaign']) ? $_POST['campaign'] : '';





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
	//$to_address = "surya@bytindia.com";
	
	
	
	$to_address = explode(",",$to_address);
	foreach($to_address as $val) { $mail->addAddress(trim($val)); }
    

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Mazhalai - LP Enquiry';
    
    $mail->Body = "
<html>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <h2 style='color: #0D4DA1;'>New Enquiry from LP Website</h2>
    <table style='width: 100%; border-collapse: collapse;'>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Name:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($name) . "</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Email:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($email) . "</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Mobile:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($mobile) . "</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Message:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>" . nl2br(htmlspecialchars($messageText)) . "</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Source:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>"  . htmlspecialchars($utm_source) . "</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Medium:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($utm_medium) . "</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Campaign:</td>
            <td style='padding: 8px; border: 1px solid #ddd;'>"  . htmlspecialchars($utm_campaign) . "</td>
        </tr>
    </table>
</body>
</html>";
  
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