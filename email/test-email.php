<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//exit;
//Load Composer's autoloader

//include 'config.php';
/*$mail_from_leads = "sales@edenpark.net";
$mail_password_leads = "WOXRxClrCktS";
$mailer_name_leads = "Edenpark";
$mail_host = 'sg2plcpnl0131.prod.sin2.secureserver.net';
$mail_port = 465;*/


//$_POST['name'] = 'test';

  //if ( $pass == $data["google_key"]) {
  //    loopAllData($data['user_column_data']);
  $input = file_get_contents('php://input');
  $body = json_decode($input);
//$body1 = json_decode(json_encode($input), true); 
//$body = json_decode($body1, true);

$message = 'Post: <pre>'.print_r($body,true).'</pre>';

foreach ($body as $key => $value) {
   $message .= "".htmlspecialchars(ucfirst($key))." : ".htmlspecialchars($value)."<br>";
    
}

$dbName = "digitalb2k_azadi_vlookup"; 
$conn = mysqli_connect('localhost', 'digitalb2k_azadi', ')*o[UT;DR1P}') or die('Error connecting to mysql');
mysqli_select_db($conn, $dbName) or die('Error connecting to database');

$cirSql = "INSERT INTO azadi_leads_new (form_id, name_1, email, phone, source, medium, campaign, lead_created, created_at) VALUES ('".mysqli_real_escape_string($conn, $body->form)."', '".mysqli_real_escape_string($conn, $body->name)."',  '".mysqli_real_escape_string($conn, $body->email)."', '".mysqli_real_escape_string($conn, $body->phone)."', '".mysqli_real_escape_string($conn, $body->src)."', '".mysqli_real_escape_string($conn, $body->medium)."', '".mysqli_real_escape_string($conn, $body->campaign)."', '".mysqli_real_escape_string($conn, $body->created)."', now())"; 

mysqli_query($conn, $cirSql) or die(mysqli_error());


header( "Content-type: application/json" );
$jsonAnswer = array('code' => 200, 'data'=>'success');
echo json_encode($jsonAnswer); exit;