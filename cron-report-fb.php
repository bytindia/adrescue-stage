<?php session_start(); 
date_default_timezone_set('Asia/Kolkata');

include 'db.php';
include 'functions-report.php'; 

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 
$uId = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['g_id'] = $row['g_id'];
$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
$_SESSION['g_token'] = $row['g_token'];
$_SESSION['g_mcc'] = $row['g_mcc'];

$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";
  
if($access_token!='') {  
	if (!$tok_req = file_get_contents_curl($tok_url)) { 
		  $pg = 'cron-fb-report';      
		  include 'email/mail-error.php';
		  exit;
	} 
}

$fbIds = array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-3 days")); 

$sqlRev=mysqli_query($conn, "SELECT tbl_id,client_name,fb_acc,g_acc,in_acc FROM client_dashboard WHERE uid='".$uId."' AND delete_status=0");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['fb_acc']!='') { $fbIds[] = $sqlROW['fb_acc']; }
}

if(count($fbIds)>0) {
	$fbIds = array_unique($fbIds);		
	foreach($fbIds as $k => $v) {
		//$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$v.'/insights?level=campaign&fields=objective,campaign_id,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,relevance_score,outbound_clicks&time_increment=1&limit=500&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($last90)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'';
		$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$v.'/insights?level=campaign&fields=objective,campaign_id,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,outbound_clicks&time_increment=1&limit=500&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($last90)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'';
		$fbData = FB_Report($request_url, $v, $uId, $conn);
	}
}
echo 'success';


