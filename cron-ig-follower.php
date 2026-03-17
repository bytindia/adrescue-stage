<?php 
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

$conn2 = mysqli_connect('localhost', 'salesninja', 'SalesNinja#2000', 'salesninja');

function d1($d) {
	echo '<pre>';
	print_r($d);
	echo '</pre>';
}

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}

//$path='pdf/';
//require($server_path.'pdf/fpdf.php');
include 'db.php';
//include 'class.pdf.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 

$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";
  
if($access_token!='') {  
	if (!$tok_req = file_get_contents_curl($tok_url)) { 
		  $pg = 'cron-budget';      
		  include 'email/mail-error.php';
		  exit;
	} 
}

$today =  date('Y-m-d'); 

$st_1 =  date('Y-m-d', strtotime("-1 days")); 
$st_2 =  date('Y-m-d', strtotime("-2 days")); 

$st_4 =  date('Y-m-d', strtotime("-4 days")); 
$st_5 =  date('Y-m-d', strtotime("-5 days")); 

$st_7 =  date('Y-m-d', strtotime("-8 days"));
$en_7 =  date('Y-m-d', strtotime("-1 days"));

$st_14 =  date('Y-m-d', strtotime("-15 days"));
$en_14 =  date('Y-m-d', strtotime("-8 days"));  


$sqlRev2 = mysqli_query($conn, "SELECT i.tbl_id, i.pg_id, p.pg_token FROM insta_followers as i, pages as p WHERE i.pg_id=p.pg_id");

while($row=mysqli_fetch_assoc($sqlRev2)) { 
	$data = get_data('https://graph.facebook.com/'.$api_ver.'/'.$row['pg_id'].'?fields=instagram_accounts{followed_by_count,username}&access_token='.$row['pg_token'].'');
	if(isset($data['instagram_accounts']['data'][0]['followed_by_count'])) {
		
		$sqlRev3 = mysqli_query($conn, "SELECT tbl_id,tot FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$today."'");
		if(mysqli_num_rows($sqlRev3)==0) {
			 $q = "SELECT tot FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$st_1."''";
			 $r = mysqli_query($conn, $q);
			 $rw = mysqli_fetch_assoc($r);
			 $yest_tot = $rw['tot']; 
			 $diff = $data['instagram_accounts']['data'][0]['followed_by_count'] - $yest_tot;
			 
			 $cirSql = "INSERT INTO insta_follower_count (ref_id, tot,  diff, date, created) VALUES ('".$row['tbl_id']."','".$data['instagram_accounts']['data'][0]['followed_by_count']."',  '".$diff."', '".$today."',  now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
		}
	}
}