<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
//error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'functions-report.php'; 

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

function sendWhatsapp($tok, $phone) {
    $phone = '91'.$phone;
    $attachment =  array(
        
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type'=> 'template',
        'template' => 
          json_encode(
            array(
              'name' => 'salary_update_notify', 
              'language' => array('code'=>'en_US'), 
              'components'=> 
                array(array(
                "type" => "body",
                "parameters" => array(
                    array("type"=> "text","text"=> 'Kindly update your salary sheet before EOD.'),
                )))
          ))
        );
        $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
        $post = json_encode($attachment); // Encode the data array into a JSON string
        $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection
        return json_decode($result); // Return the received data
        //print_r($result); 
  
  }

$sqlRev=mysqli_query($conn, "SELECT * FROM budget_reminder WHERE delete_status='0'");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;								
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	
	echo $sqlROW['media_by']; echo '<br>'; 
   // exit;
    //$wa_res = sendWhatsapp($access_token, $sqlROW['phone']);
    //$wa_res = json_decode(json_encode($wa_res), true);
}
exit;
