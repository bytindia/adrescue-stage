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
$spreadsheetId='1qkxJvF8U5nBCJDQpztxKkOJLU6rOQ2tBgFx6nLay9tU'; 

$sheetTab='LP Leads';

$sheetTab='LP Leads'; 


$input = file_get_contents('php://input');
$lead = json_decode($input,true);


if(isset($_POST)) {
    //$data = json_decode($your_json_string, TRUE);
   // $dt = $_POST['date'].' '.$_POST['time'];
    //$rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $src= $med= $camp= $term= $srd= $content='';
    $created_at = date('Y-m-d H:i:s');
    $ph = str_replace(' ', '', $lead['uphone']);
    $ph = str_replace('+91', 'p:+91', $ph);
    if(isset($lead['utm_source'])) { $src= $lead['utm_source']; }
    if(isset($lead['utm_medium'])) { $med= $lead['utm_medium']; }
    if(isset($lead['utm_campaign'])) { $camp= $lead['utm_campaign']; }
    if(isset($lead['srd']) && trim($lead['srd']) != '') { $srd= trim($lead['srd']); }

    if($srd =='')
    {
        $srd = '65d706410d18510cb9c52cd6';
    }

    $apiKey = 'd50154787e5d175357686612ac641d04';
	
    $ch = curl_init();
    $link = "https://app.sell.do/api/leads/create?api_key=".$apiKey."&sell_do[form][lead][name]=".mysqli_real_escape_string($conn, $lead['uname'])."&sell_do[form][lead][email]=".mysqli_real_escape_string($conn, $lead['uemail'])."&sell_do[form][lead][phone]=".mysqli_real_escape_string($conn, $ph)."&sell_do[campaign][srd]=".$srd."&sell_do[form][lead][note]=";

    $link = str_replace ( ' ', '%20', $link);
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $data = curl_exec($ch);
    curl_close($ch);


    $cirSql = "INSERT INTO rld_leads (name, email, phone, src, med, camp, budget, created_at, created, api_res) VALUES ('".mysqli_real_escape_string($conn, $lead['uname'])."',  '".mysqli_real_escape_string($conn, $lead['uemail'])."', '".mysqli_real_escape_string($conn, $ph)."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $med)."', '".mysqli_real_escape_string($conn, $camp)."', '".mysqli_real_escape_string($conn, $lead['budget'])."', '".$created_at."', now(), '".mysqli_real_escape_string($conn, $data)."');"; 
    error_log($cirSql);
    mysqli_query($conn, $cirSql) or die(mysqli_error());
    /* $myfile = fopen("rld-api.txt", "w") or die("Unable to open file!");
         $txt = $phone.' - '.$data."\n";
        fwrite($myfile, $txt);
         fclose($myfile);*/

    //$lastId = mysqli_insert_id($conn);
    //$sheetTab='Retreat _POSTs'; 
    $leadV = [[mysqli_real_escape_string($conn, $lead['uname']),$lead['uemail'],$ph,$lead['budget'], date('d-m-Y, h:i a'),$src,$med,$camp]];
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