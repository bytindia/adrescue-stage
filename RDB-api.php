<?php
$leads = unserialize($_POST['lead']); 

$name=$leads['full_name'];
$phone=$leads['phone_number'];
$email=$leads['email'];
$campaign_id='facebook-RDB-chennai';
$country_code='IN';
$source=$_POST['campaign'];
$sub_source=$_POST['formName'];

hit_api($name,$phone,$email,$campaign_id,$country_code,$source,$sub_source);

function hit_api($name,$phone,$email,$campaign_id,$country_code,$source,$sub_source){
    $key = "385594453e595929";
    $channel = "facebook";
    $domain = "anarock.com";
    $referer = $_SERVER["HTTP_REFERER"];
    //$key = "385594453e595929";
    //$channel = "facebook-RDB-chennai";
    
    $placement = "";
    $extra_details = "{}";
    try{
     // $source = preg_split("/&/",(preg_split("/utm_source=/",$referer)[1]))[0];
     // $sub_source = preg_split("/&/",(preg_split("/utm_medium=/",$referer)[1]))[0];
     // $placement = preg_split("/&/",(preg_split("/utm_campaign=/",$referer)[1]))[0];
     // $gclid = preg_split("/&/",(preg_split("/gclid=/",$referer)[1]))[0];
     // $extra_details = json_encode(array("gclid" => $gclid, "referer" => $referer));
    } catch(Exception $e) {
      // echo 'Message: ' .$e->getMessage();
    }
    $api_url = 'https://lead.'.$domain.'/api/v0/'.$channel.'/sync-lead';
    $current_time = (string)time();
    $hash = hash_hmac('sha256',$current_time,$key);
    $postFields  = "";
    $postFields .= "&name=".$name;
    $postFields .= "&phone=".$phone;
    $postFields .= "&email=".$email;
    $postFields .= "&purpose=buy";
    $postFields .= "&current_time=".$current_time;
    $postFields .= "&country_code=".$country_code;
    $postFields .= "&source=".$source; 
    $postFields .= "&sub_source=".$sub_source;
   // $postFields .= "&placement=".$placement;
    $postFields .= "&hash=".$hash;
    $postFields .= "&campaign_id=".$campaign_id;
    $postFields .= "&extra_details=".$extra_details;
    echo $postFields;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$postFields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    echo $server_output = curl_exec ($ch);

    

    $myfile = fopen("rdb-output.txt", "w") or die("Unable to open file!");
        $txt = $server_output."\n";
        fwrite($myfile, $txt);
        fclose($myfile);

    return $server_output;
  }