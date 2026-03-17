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
		$course = $_POST['course'];
		$city = '';
		$state = '';
		$source = $_POST['src'].'/'.$_POST['sub_src2'];
		$campaign = $_POST['sub_src'];
		$project = $_POST['project'];
        $page = $_POST['page'];
		$project_id = 1;

        if(isset($_POST['city'])) { $city = $_POST['city']; }
        if(isset($_POST['state'])) { $state = $_POST['state']; }
		
		$sqlQuery = "INSERT INTO kveg_lp (name, email, phone, course, city, state, source, campaign, project, project_id, page, created) VALUES ('".mysqli_real_escape_string($conn, $name)."', '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, $phone)."', '".mysqli_real_escape_string($conn, $course)."', '".mysqli_real_escape_string($conn, $city)."', '".mysqli_real_escape_string($conn, $state)."', '".mysqli_real_escape_string($conn, $source)."', '".mysqli_real_escape_string($conn, $campaign)."', '".mysqli_real_escape_string($conn, $project)."', '".mysqli_real_escape_string($conn, $project_id)."', '".mysqli_real_escape_string($conn, $page)."', now());"; 
		mysqli_query($conn, $sqlQuery) or die(mysqli_error()); 

        $dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='146SwyX4yOo2x4FEAtE1xyMpR0ZCIN5ozAiOU4UOjnk0'; 

$sheetTab='KVCET-PG-LP';

$leadV = [[mysqli_real_escape_string($conn, $name),$email,$phone, $course, mysqli_real_escape_string($conn, $city), mysqli_real_escape_string($conn, $source),mysqli_real_escape_string($conn, $campaign), date('d-m-Y, h:i a')]];
append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);

  //if ( $pass == $data["google_key"]) {
  //    loopAllData($data['user_column_data']);
$input = file_get_contents('php://input');
$body = json_decode($input);
$message = '';
foreach ($_POST as $key => $value) {
    if($key!='cId' && $key!='src_F_G' && $key!='project'&& $key!='sub_src2') {
        if($key=='src') { $key='source'; }
        if($key=='sub_src') { $key='campaign'; }
        $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
    }
}
//echo $message;
//print_r($_POST); exit;

$duration = '';
$subject = 'PG Admissions - LP Enquiry';
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
    $mail->setFrom($mail_from_info, 'KVEG - Enquiry');
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	//$to_address = "admission@kveg.in, bytramesh@gmail.com";
    $to_address = "surya@bytindia.com";
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