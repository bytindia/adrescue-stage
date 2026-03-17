<?php exit;
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

    $ph = str_replace('+91', 'p:+91', $ph);
    if(isset($_POST['source'])) { $src= $_POST['source']; }
    if(isset($_POST['medium'])) { $med= $_POST['medium']; }
    if(isset($_POST['campaign'])) { $camp= $_POST['campaign']; }
    if(isset($_POST['project'])) { $project= $_POST['project']; }
    if(isset($_POST['hidden_5'])) { $content= $_POST['hidden_5']; }

    if(isset($_POST['srd']) && trim($_POST['srd'])!='') { $srd=trim($_POST['srd']); }


    $apiKey = '46996f24a4ce88a72127a43311967190';
	
    $ch = curl_init();
    $link = "https://app.sell.do/api/leads/create?api_key=".$apiKey."&sell_do[form][lead][name]=".mysqli_real_escape_string($conn, $_POST['name'])."&sell_do[form][lead][email]=".mysqli_real_escape_string($conn, $_POST['email'])."&sell_do[form][lead][phone]=".mysqli_real_escape_string($conn, $_POST['phone'])."&sell_do[campaign][srd]=".$srd."&sell_do[form][lead][note]=";

    $link = str_replace ( ' ', '%20', $link);
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $api_res = curl_exec($ch);
    curl_close($ch);

    $myfile = fopen("lml-api.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $api_res);
        fclose($myfile);

    $cirSql = "INSERT INTO lml_leads (name, email, phone, src, med, camp, project,  created_at, created, api_res, srd) VALUES ('".mysqli_real_escape_string($conn, $_POST['name'])."',  '".mysqli_real_escape_string($conn, $_POST['email'])."', '".mysqli_real_escape_string($conn, $ph)."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $med)."', '".mysqli_real_escape_string($conn, $camp)."', '".mysqli_real_escape_string($conn, $project)."',  '".$created_at."', now(), '".mysqli_real_escape_string($conn, $api_res)."', '".mysqli_real_escape_string($conn, $srd)."');"; 

    mysqli_query($conn, $cirSql) or die(mysqli_error());

    $lead_val = array(
        'Name' => mysqli_real_escape_string($conn, $_POST['name']),
        'Email' => mysqli_real_escape_string($conn, $_POST['email']),
        'Phone' => mysqli_real_escape_string($conn, $ph),
        'Project' => mysqli_real_escape_string($conn, $project),
        'Source' => mysqli_real_escape_string($conn, $src),
        'Medium' => mysqli_real_escape_string($conn, $med),
        'Campaign' => mysqli_real_escape_string($conn, $camp),
        'Date' => $created_at
    );

    $message = '';
    foreach ($lead_val as $key => $value) {
    $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
        
    }
    //echo $message;
    //print_r($_POST); exit;

    $duration = '';
    $subject = 'LML - Enquiry ('.$project.')';
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
        
        $mail->setFrom($mail_from_leads, 'LML Leads');
        $to_address = "bytramesh@gmail.com, cs.lmlhomes@gmail.com";
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