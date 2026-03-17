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

$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];


$gIds = array();

$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-60 days")); 

$sqlRev=mysqli_query($conn, "SELECT tbl_id,client_name,fb_acc,g_acc,in_acc FROM client_dashboard WHERE uid='".$uId."' AND delete_status=0");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['g_acc']!='') { $gIds[] = $sqlROW['g_acc']; }
}

if(count($gIds)>0) {
	$accIds = array_unique($gIds);
	include 'download-campaign-dashboard.php';		
	foreach($gIds as $k => $v) {
		$gData = G_Report(''.$server_path.'ads-perform/dash_'.$v.'.csv', trim($v), $uId, $conn);
	}
}
echo 'success';


