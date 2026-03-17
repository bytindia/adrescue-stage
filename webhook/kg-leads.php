<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 0); //ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  

require $dirPath.'email/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include $dirPath.'email/config.php';

$spreadsheetId='1Ow2D2COhhBamiv84FsuM-FJqJjTCNpx4qtnmf63I4gA'; 
$tab_list = array(
    1=>'Alcazaba',
    2=>'SPV'
);

$sheetTab='FB Leads';

$sheetTab='Retreat Leads'; 


$input = file_get_contents('php://input');
$lead = json_decode($input,true);
//d($lead); exit;

if(isset($_POST) && count($_POST)>0) {
    
    // $dt = $_POST['date'].' '.$_POST['time'];
    //$rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $src= $med= $camp= $term=  $content = $level = $stream = '';

    $created_at = date('Y-m-d H:i:s');

    $page_id = $_POST['page_id'];

    $name = mysqli_real_escape_string($conn, urldecode($_POST['name']));
    $email = mysqli_real_escape_string($conn, urldecode($_POST['email']));
    $message = mysqli_real_escape_string($conn, urldecode($_POST['message']));
    $project = mysqli_real_escape_string($conn, $_POST['project']);
    $page_id = mysqli_real_escape_string($conn, $_POST['page_id']);
    $full_url = mysqli_real_escape_string($conn, urldecode($_POST['full_url']));
    $created = date('d-m-Y h:i:s');

    $ph = str_replace(' ', '', urldecode($_POST['phone']));
    $ph = str_replace('+91', 'p:+91', $ph);

   
    
    $leadV = [[$name, $email, $ph, $message, $project, $full_url, $created]];

    if($ph!='' || $email!=''){
        $sheetTab = $tab_list[$page_id];
        append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); 

        

        $lead_val = array(
            'Name' => mysqli_real_escape_string($conn, $name),
            'Email' => mysqli_real_escape_string($conn, $email),
            'Phone' => mysqli_real_escape_string($conn, str_replace(' ', '', urldecode($_POST['phone']))),
            'Project' => mysqli_real_escape_string($conn, $sheetTab),
            'Page_URL' => mysqli_real_escape_string($conn, $full_url),
            'Date' => $created_at
        );
    
        $message = '';
        foreach ($lead_val as $key => $value) {
        $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
            
        }
        //echo $message;
        //print_r($_POST); exit;
    
        $duration = '';
        $subject = 'KG - Enquiry ('.$sheetTab.')';
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
            
            $mail->setFrom($mail_from_leads, 'KG Enquiry');
            $to_address = "abarnabyt@gmail.com";
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
        echo json_encode($data);

    }
    
}