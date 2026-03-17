<?php

function verify_otp($url) {

ob_start();

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);

curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');

curl_setopt($ch, CURLOPT_HTTPHEADER, array(

'Content-Type: application/json',

'Accept: application/json'

));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);

print_r($result);

$output = ob_get_contents();

    ob_end_clean();

    $result = json_decode($result,true);

    echo ' res: '.$result['response_type'].' :> '.$result['response'];

    echo "\n";

}

 

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $user_mobile = '9176299010';

$otp = '946712';

$query_string = "mobile=".$user_mobile;

$query_string .= "&otp=".$otp; // OTP to be verified

$url = "https://teleduce.corefactors.in/validate-otp/10eedb7e-2131-4458-8c9c-1ce416f49beb/?".$query_string;

$response = verify_otp($url);

}

?>

 

<html>

<head><title> SMS OTP Verification </title></head>

<body>

<form method="post" name="frmadd" id="frmadd" action="" enctype="multipart/form-data">

<input name="btn_submit" id="btn_type" type="submit" class="button" value="Verify OTP" />

</form>

</body>

</html>