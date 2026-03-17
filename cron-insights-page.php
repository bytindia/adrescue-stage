<?php 
date_default_timezone_set('Asia/Kolkata');

include 'db.php';

setlocale(LC_MONETARY, 'en_IN');

$query = "SELECT access_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
	
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

$start = date('m/d/Y', strtotime("first day of this month")); //date('m/01/Y');
$end = date('m/d/Y'); 


$getData = $fbStats = $gStats = array();
$fbStats = $fb_name = array();
$gStats = $g_name = array();

if(!isset($_GET['id'])) { $_GET['id']=1; }

$sqlRev2 = mysqli_query($conn, "SELECT tbl_id,uid,pg_id,pg_token,ins_update FROM pages WHERE uid='2' AND active='1'");

while($row=mysqli_fetch_assoc($sqlRev2)) { 
	$getData[] = $row;
}

foreach($getData as $d) {
	if($d['ins_update']=='') { $start= date('m/d/Y', strtotime('-1 days',strtotime("first day of last month"))); } else { $start=$d['ins_update']; }
	//echo 'https://graph.facebook.com/'.$api_ver.'/'.$d['pg_id'].'/insights?access_token='.$d['pg_token'].'&fields=values&period=day&since='.$start.'&until='.$end.'&metric=page_impressions_organic_unique,page_impressions_paid_unique,page_post_engagements'; exit;
	
	$val_lm = get_data('https://graph.facebook.com/'.$api_ver.'/'.$d['pg_id'].'/insights?access_token='.$d['pg_token'].'&fields=values&period=day&since='.$start.'&until='.$end.'&metric=page_impressions_organic_unique,page_impressions_paid_unique,page_post_engagements');	
	//d($val_lm['data'][0]['values']); exit;
	//echo sizeof($val_lm['data'][0]['values']); 
	for($k=0; $k < sizeof($val_lm['data'][0]['values']); $k++)
	{	
		/*echo $val_lm['data'][0]['values'][$k]['value'].' - '.$val_lm['data'][1]['values'][$k]['value'].' - '.$val_lm['data'][2]['values'][$k]['value'].' - '.$val_lm['data'][0]['values'][$k]['end_time']; exit;
		echo '<br>';*/
		 $chkRes = mysqli_query($conn, "select * from page_insights WHERE uid='".$d['uid']."' AND pg_id='".$d['pg_id']."' AND end_time='".$val_lm['data'][0]['values'][$k]['end_time']."'");						
		 if(mysqli_num_rows($chkRes)==0) { 
			 $cirSql = "INSERT INTO page_insights (uid, pg_id, org_reach, paid_reach, engagement, end_time, end_time_unix, created) VALUES ('".$d['uid']."', '".$d['pg_id']."', '".$val_lm['data'][0]['values'][$k]['value']."', '".$val_lm['data'][1]['values'][$k]['value']."', '".$val_lm['data'][2]['values'][$k]['value']."', '".$val_lm['data'][0]['values'][$k]['end_time']."', '".strtotime($val_lm['data'][0]['values'][$k]['end_time'])."',now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
		 }
	}
	$cirSql_x = "UPDATE pages SET ins_update='".$end."' WHERE tbl_id=".$d['tbl_id']."";
	mysqli_query($conn, $cirSql_x) or die(mysqli_error()); 
}



echo 'cron-insights-page.php => success';
	