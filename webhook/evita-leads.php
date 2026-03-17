<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
// require_once $dirPath.'google-sheets-api/vendor/autoload.php';
// require_once $dirPath.'google-sheets-api/class-db.php';
// require_once $dirPath.'google-sheets-api/config.php';
// include $dirPath.'google-sheets-api/insert-row.php';
// $uId=2; 
// $tbl_id=2;  
// $spreadsheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'; 

// $sheetTab='FB Leads';

// $sheetTab='Retreat Leads'; 


$input = file_get_contents('php://input');
$lead = json_decode($input,true);

require $dirPath.'email/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include $dirPath.'email/config.php';

if(count($lead)>0) {
    
   // $dt = $lead['date'].' '.$lead['time'];
    //$rnage1 = DateTime::createFromFormat('F d, Y h:i a', $dt);
    $src= $med= $camp= $term=  $srd= $content='';
    $created_at = date('Y-m-d H:i:s');
    $ph = str_replace(' ', '', $lead['phone_1']);
    $ph = str_replace('+91', 'p:+91', $ph);
    if(isset($lead['hidden_1'])) { $src= $lead['hidden_1']; }
    if(isset($lead['hidden_2'])) { $med= $lead['hidden_2']; }
    if(isset($lead['hidden_3'])) { $camp= $lead['hidden_3']; }
    if(isset($lead['hidden_4']) && trim($lead['hidden_4']) != '') { $srd= trim($lead['hidden_4']); }
    // if(isset($lead['hidden_4'])) { $term= $lead['hidden_4']; }
    // if(isset($lead['hidden_5'])) { $content= $lead['hidden_5']; }

    if($srd !='')
    {
        $project_ID = $srd;
    } else {
        $srd = $project_ID = '65c31b002f31c6c0e7d8035d';
        
    }

    
    //$lastId = mysqli_insert_id($conn);
    // $sheetTab='Retreat Leads'; 
    // $leadV = [[mysqli_real_escape_string($conn, $lead['name_1']),$lead['email_1'],$ph," ", date('d-m-Y, h:i a'),$src,$med,$camp]];
	// append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
	//$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

   /* $data = array('code'=>200, 'response'=>"success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);*/

    $apiKey = '0ba147147898fb91baec052dd5dc05f0';
	
    $ch = curl_init();
    $link = "https://app.sell.do/api/leads/create?api_key=".$apiKey."&sell_do[form][lead][name]=".mysqli_real_escape_string($conn, $lead['name_1'])."&sell_do[form][lead][email]=".mysqli_real_escape_string($conn, $lead['email_1'])."&sell_do[form][lead][phone]=".mysqli_real_escape_string($conn, $ph)."&sell_do[campaign][srd]=".$project_ID."&sell_do[form][lead][note]=";

    $link = str_replace ( ' ', '%20', $link);
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $data = curl_exec($ch);
    curl_close($ch);

    $cirSql = "INSERT INTO evita_leads (name, email, phone, src, med, camp, srd, full_url, created_at, created, api_response) VALUES ('".mysqli_real_escape_string($conn, $lead['name_1'])."',  '".mysqli_real_escape_string($conn, $lead['email_1'])."', '".mysqli_real_escape_string($conn, $ph)."', '".mysqli_real_escape_string($conn, $src)."', '".mysqli_real_escape_string($conn, $med)."', '".mysqli_real_escape_string($conn, $camp)."','".mysqli_real_escape_string($conn, $srd)."', '".mysqli_real_escape_string($conn, $lead['_wp_http_referer'])."',  '".$created_at."', now(), '".mysqli_real_escape_string($conn, $data)."');"; 

    mysqli_query($conn, $cirSql) or die(mysqli_error());

    // $myfile = fopen("evita-api.txt", "w") or die("Unable to open file!");
    //     $txt = $phone.' - '.$data."\n";
    //     fwrite($myfile, $txt);
    //     fclose($myfile);


    $lead_val = array(
        'Name' => mysqli_real_escape_string($conn, $lead['name_1']),
        'Email' => mysqli_real_escape_string($conn, $lead['email_1']),
        'Phone' => mysqli_real_escape_string($conn, $ph),
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
    $subject = 'Evita Leads - Enquiry';
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
        
        $mail->setFrom($mail_from_leads, 'Evita Leads');
        $to_address = "contact@evitahomes.in";
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

}




