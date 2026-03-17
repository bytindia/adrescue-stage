<?php
include 'db.php';

$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 

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

$request_url = 'https://graph.facebook.com/'.$api_ver.'/100284149552425/messages?access_token='.$access_token;
//FB_DailyReport($request_url, $conn, $fb_name[$fd], $server_path);

$requests = curl_get_file_contents($request_url);
$fb_response = json_decode($requests);
d($fb_response); exit;