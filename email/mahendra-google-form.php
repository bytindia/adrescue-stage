<?php require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//exit;
//Load Composer's autoloader

include 'config.php';



$Google_data = file_get_contents("php://input");
$data = json_decode($Google_data, true);
$name = $email = $phone = '';
  function loopAllData($data) {
    foreach ($data as $key => $value) {
      if (!is_array($value)) {
        if($key=='column_name') { $results .= $value . ": ";  }
        if($key=='string_value') { 
            $results .= $value .  "<br>\r\n"; 
            
        }
       // $results .= $key . ": " . $value . "<br>\r\n";
      }
      if (is_array($value)) {
        $results .= loopAllData($value);
      }
    }
    return $results;
  }
function searchForId($id, $array) {
    foreach ($array as $key => $val) {
        if ($val['column_name'] === $id) {
          return $val['string_value'];
        }
    }
    return null;
 }
  // You set this in Google Ads, just under where you put the webhook URL
  $pass="12345";

  $projectKey = "Ashraya";
			
			//$post = ['name' => $name, 'email' => $email, 'phone' => $phone, 'src' => $src, 'campaign' => $campaign, 'sub_src' => $sub_src, 'cId' => $cId, 'project' => trim($projectKey)];
      $phone = searchForId('User Phone', $data['user_column_data']);
      $name = searchForId('Full Name', $data['user_column_data']);
      $email = searchForId('User Email', $data['user_column_data']);

			$cId = 12;
			$post = ['name' => $name, 'email' => $email, 'phone' => $phone, 'src' => 'Google', 'sub_src' => 'Google lead form', 'cId' => 12, 'project' => trim($projectKey), 'src_F_G' => 'Google'];
			$ch = curl_init('http://adrescue.in/sn-api/addlead');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			$response = curl_exec($ch);
			curl_close($ch);	
      $myfile = fopen("ashraya.txt", "w") or die("Unable to open file!");
        $txt = $phone.' - '.$name."\n".$email;
        fwrite($myfile, $txt.' --> '.$response);
        fclose($myfile);

  //if ( $pass == $data["google_key"]) {
  //    loopAllData($data['user_column_data']);
$input = file_get_contents('php://input');
$body = json_decode($input);
$message = '123';
foreach ($_REQUEST as $key => $value) {
    $message .= "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
}
$duration = '';
$subject = 'Mahendra Google Form Leads '.$_POST['lead_id'];
$mail_body_1 = loopAllData($data['user_column_data']);

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $mail_host;  					          // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $mail_from_leads;                   // SMTP username
    $mail->Password = $mail_password_leads;                           // SMTP password
    $mail->SMTPSecure = $tls_ssl;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mail_port;                                    // TCP port to connect to
	$mail->CharSet = 'UTF-8';
    //Recipients
    $mail->setFrom($mail_from_leads, $mailer_name_leads);
    //$mail->addAddress($receive_email);     // Add a recipient
	//$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, ramesh@bytindia.com";
	$to_address = "bytprabhu@gmail.com, bytramesh@gmail.com";
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