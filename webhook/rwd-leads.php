<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require $dirPath.'email/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include $dirPath.'email/config.php';

$input = file_get_contents('php://input');
$lead = json_decode($input,true);

//print_r($_POST);

$src= $med= $camp= $term=  $content= $srd= '';
$created_at = date('Y-m-d H:i:s');
$ph = str_replace(' ', '', $_POST['phone']);

  

    $lead_val = array(
        'Name' => mysqli_real_escape_string($conn, $_POST['name']),
        'Email' => mysqli_real_escape_string($conn, $_POST['email']),
        'Phone' => mysqli_real_escape_string($conn, $ph),
        'Project' => mysqli_real_escape_string($conn, $_POST['project']),
        'Source' => mysqli_real_escape_string($conn, $_POST['source']),
        'Date' => $created_at
    );

    $message = '';
    foreach ($lead_val as $key => $value) {
    $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
        
    }
    //echo $message;
    //print_r($_POST); exit;

    $duration = '';
    $subject = 'RWD - Enquiry ('.$_POST['project'].')';
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
        
        $mail->setFrom($mail_from_leads, 'RWD Leads');
        $to_address = "bytramesh@gmail.com, sales@rwd.in";
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


    echo 'success';