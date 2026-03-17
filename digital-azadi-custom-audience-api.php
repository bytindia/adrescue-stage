<?php

if(isset($_GET['phone'])) {

include 'db.php';
$pg='facebook';
include 'config.php';

function curlPost($url, $post){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 



$audience_id = 23861946761880022;

$phone = $_GET['phone'];
$phone = str_replace(' ', '', $phone);
$phone = substr($phone, -10);

$phone_hash = hash('sha256','91'.$phone);

$post = [
        'payload' => '{
            "schema": [
              "EMAIL"
            ],
            "data": [
              [
                "'.$phone_hash.'"
              ]
            ]
          }',
        'access_token' => $access_token
];

$url = "https://graph.facebook.com/".$api_ver."/".$audience_id."/users";
$req = curlPost($url,$post);

d($req);
$content = "some text here";
$fp = fopen("digit-api","wb");
fwrite($fp,$req);
fclose($fp);
}

