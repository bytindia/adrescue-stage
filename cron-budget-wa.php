<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

include 'db.php';
include 'functions-report.php'; 

//require __DIR__ . '/email/vendor/autoload.php';
//include 'email/config.php';

function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
 }

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

function sendWhatsapp($tok, $acc_name, $phone, $per_msg, $spend, $budget_V, $percentage) {
     $phone = '91'.$phone; // exit;
     $wa_msg = 'The *'.$acc_name.'* ad account has reached *'.$percentage.'%* of its total spend, which is *₹'.$spend.'* out of *₹'.$budget_V.'*';
    $attachment =  array(
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type'=> 'template',
        'template' => 
          json_encode(
            array(
              'name' => 'budget_alert', 
              'language' => array('code'=>'en_GB'), 
              'components'=> 
                array(
                    array(
                        "type" => "header",
                        "parameters" => array(array("type"=> "text", "text"=>$acc_name." (".$percentage."%)"))
                    ),
                    array(
                        "type" => "body",
                        "parameters" => array(array("type"=> "text", "text"=> $wa_msg ))
                    )
                )
          ))
        );
        
        //print_r($attachment); exit;

        $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
        $post = json_encode($attachment); // Encode the data array into a JSON string
        $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); //Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection
        return json_decode($result); // Return the received data
        print_r($result); // exit;
}

$today = date('m/d/Y');



$editData = array();
$sqlD=mysqli_query($conn, "SELECT budget, client_name, date, leads, acc_ids, send_to, proj_name from budget_wa_notify where tbl_id=1");
while($Rdata=mysqli_fetch_array($sqlD)) {
      $editData = $Rdata;
}
$date = unserialize($editData['date']);
$budget = unserialize($editData['budget']);
$acc_ids = unserialize($editData['acc_ids']);
$send_to = unserialize($editData['send_to']);
$proj_name = unserialize($editData['proj_name']);
//d($date);


$lookfor = array($today);
$scalars = array_filter($date,function ($item) { return !is_array($item); });
$today_data = array_intersect ($scalars, $lookfor);

//d($found);

foreach($today_data as $key => $val){ 

  // echo $acc_ids[$key].' - '.$budget[$key].' - '.$date[$key]; echo '<br>';
   $fbId = $acc_ids[$key];
   $projN = $proj_name[$key];
   $cirRes = mysqli_query($conn, "select * from budget_wa_extra WHERE acc_id='".$fbId."' AND date='".$today."'");						
   if(mysqli_num_rows($cirRes)==0) {
        $cirSql = "INSERT INTO budget_wa_extra (acc_id, date,messages) VALUES ('".$fbId."', '".mysqli_real_escape_string($conn, $today)."', '0');"; 
        mysqli_query($conn, $cirSql) or die(mysqli_error());
        $lastId = mysqli_insert_id($conn);
   } 

   $cirRes1 = mysqli_query($conn, "select tbl_id,messages from budget_wa_extra WHERE acc_id='".$fbId."' AND date='".$today."'");
   $rs = mysqli_fetch_assoc($cirRes1);
   $lastId = $rs['tbl_id'];
   $messages = $rs['messages'];

   if($projN==''){
        $sqlRev=mysqli_query($conn, "SELECT name FROM adAccounts WHERE uid='2' AND account_id='".$fbId."' order by name asc");
        $rs2 = mysqli_fetch_assoc($sqlRev);
        $acc_name = $rs2['name'];
   } else {
        $acc_name =$projN;
   }
   
   
   $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($today)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'';
   $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend&access_token='.$access_token.'&'.$dtRange.''; //exit;
   $requests = curl_get_file_contents($request_url);
   $fb_response = json_decode($requests,true); 

   if(isset($fb_response['data'][0]['spend']))
   {
        $spend = round($fb_response['data'][0]['spend']);
        $budget_V = round($budget[$key]);
        $percentage = round(($spend / $budget_V) * 100);
        $per_msg = 0; 
        if($percentage>20 && $percentage<50 && $messages!='1') {
            $per_msg = 20;
            $cirSql = "UPDATE  budget_wa_extra SET messages='1'  WHERE tbl_id=".$lastId.""; 
            mysqli_query($conn, $cirSql) or die(mysqli_error());
        }
        if($percentage>50 && $percentage<80 && $messages!='2') {
            $per_msg = 50;
            $cirSql = "UPDATE  budget_wa_extra SET messages='2'  WHERE tbl_id=".$lastId.""; 
            mysqli_query($conn, $cirSql) or die(mysqli_error());
        }
        if($percentage>80 && $percentage<100 && $messages!='3') {
            $per_msg = 80;
            $cirSql = "UPDATE  budget_wa_extra SET messages='3'  WHERE tbl_id=".$lastId.""; 
            mysqli_query($conn, $cirSql) or die(mysqli_error());
        }
        if($percentage>100 && $messages!='4') {
            $per_msg = 100;
            $cirSql = "UPDATE  budget_wa_extra SET messages='4'  WHERE tbl_id=".$lastId.""; 
            mysqli_query($conn, $cirSql) or die(mysqli_error());
        }
        if($per_msg!=0){
            //echo 'The budget has reached '.$per_msg.'%, the total spend is : '.$spend.' / '.$budget_V.' ('.$percentage.'%)';
            //$wa_msg = 'The BYT Digital ad account has reached '.$per_msg.'% of its total spend, which is '.$spend.' out of '.$budget_V.' ('.$percentage.'%).';

            //$phone = array('9176299010','7904065676', '9840031390');
            
            //$phone = array('9176299010','7904065676');
            //$phone = array('9176299010');
            $send_V = $send_to[$key];
            if($send_V!=''){
                $phone = explode(",",$send_V);
            } else {
                $phone = array('9176299010');
            }
            //$phone = array('9176299010');
            //$phone = explode(",",$send_V);
	        //foreach($to_address as $val) { $mail->addAddress(trim($val)); }
            foreach($phone as $key => $v) {	
               $phNo= trim($v);
               sendWhatsapp($access_token, $acc_name, $phNo, $per_msg, $fmt->format($spend), $fmt->format($budget_V), $percentage);
            }
        }
   }
   //d($new_width);

}
echo 'success';
exit;
