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


$sqlRev2 = mysqli_query($conn, "SELECT i.client, i.tbl_id, i.pg_id, p.pg_token FROM insta_followers as i, pages as p WHERE i.pg_id=p.pg_id");

echo '<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;"><tr><th>Client</th><th>Today</th><th>Yesterday</th><th>Last 7 days</th></tr>';

while($row=mysqli_fetch_assoc($sqlRev2)) 
{ 
		$diff = $diff1 = $diff2 = '-';
		echo '</tr><td>'.$row['client'].'</td>';
		//echo "SELECT tbl_id,tot,SUM(diff) as diff FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$today."'";
		$q = mysqli_query($conn, "SELECT tbl_id,tot,SUM(diff) as diff FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$today."'");
		if(mysqli_num_rows($q)!=0) {
			 //$q = "SELECT tot FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$st_1."''";
			 //$r = mysqli_query($conn, $q);
			 $rw = mysqli_fetch_assoc($q);
			 $yest_tot = $rw['tot']; 
			 $diff = $rw['diff']; 
		}
		echo '<td>'.$diff.'</td>';
		
		$q1 = mysqli_query($conn, "SELECT tbl_id,tot,SUM(diff) as diff FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$st_1."'");
		if(mysqli_num_rows($q1)!=0) {
			 //$q = "SELECT tot FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$st_1."''";
			 //$r1 = mysqli_query($conn, $q1);
			 $rw1 = mysqli_fetch_assoc($q1);
			 $yest_tot1 = $rw1['tot']; 
			 $diff1 = $rw1['diff']; 
			 //echo '<td>'.$diff1.'</td>';
		}
		echo '<td>'.$diff1.'</td>';
		$q2 = mysqli_query($conn, "SELECT tbl_id,tot,SUM(diff) as diff FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date>='".$st_7."'AND date<='".$en_7."'");
		if(mysqli_num_rows($q2)!=0) {
			 //$q = "SELECT tot FROM insta_follower_count WHERE ref_id='".$row['tbl_id']."' AND date='".$st_1."''";
			 //$r2 = mysqli_query($conn, $q2);
			 $rw2 = mysqli_fetch_assoc($q2);
			 $yest_tot2 = $rw2['tot']; 
			 $diff2 = $rw2['diff']; 
			 //echo '<td>'.$diff2.'</td>';
		}
		echo '<td>'.$diff2.'</td>';
		echo '</tr>';
	
}
echo '</table>';
?>