<?php session_start();
date_default_timezone_set('Asia/Kolkata'); 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
?>
<title>Loading...</title>
Loading... Please wait...
<?php
$fbId = $_GET['act'];
function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

function AdInsights_30d($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); exit;
	
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$cSpend = $adset_tot = $lead = $cpr = $web_con_val = 0;
				$objType = $res['objective']; 
				if(isset($res['spend'])) { $cSpend=$res['spend']; } 
				if(isset($res['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$cpr = $lead = 0;
						$lead = LeadGen($res['actions'], $obj_arr[$objType]);
						if($cSpend!=0 && $lead!=0) { $cpr = @(round($cSpend / $lead, 2)); }
					}
				}
				if(isset($res['action_values'])) {
					//if(array_key_exists($objType, $obj_arr)) {
						$web_con_val = LeadGen($res['action_values'], 'purchase');
						//$cpr = @(round($cSpend / $lead, 2));
					//}
				}
				$stDt = strtotime(date("".$res['date_start']." 00:00:00"));
				$enDt = strtotime(date("".$res['date_stop']." 23:59:59"));
				//echo $res['campaign_name'].'_'. $cSpend.'_'.$lead .'_'.$cpr.'<br>' ; exit;
				
			//$campQ = mysqli_query($conn, "select * from audit_adIns_30d_cpl WHERE accId='".$accId."' AND adset_id='".$res['adset_id']."' AND st_dt=".$stDt." AND en_dt=".$enDt."");						
			
			//if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_adIns_30d_cpl (accId,  adset_id, campaign_id,  adset_name, campaign_name, spend, leads, obj_type, web_con_val, st_dt, en_dt, sDate, eDate, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['adset_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $res['adset_name'])."', '".mysqli_real_escape_string($conn, $res['campaign_name'])."', '".mysqli_real_escape_string($conn, $cSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $objType)."', '".mysqli_real_escape_string($conn, $web_con_val)."', ".$stDt.", ".$enDt.", '".$res['date_start']."', '".$res['date_stop']."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			//} 
	}  //exit;*/
	if(isset($fb_response['paging']['next'])) {
		AdInsights_30d($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		
		$cirSql = "INSERT INTO audit_adIns_cpl (accId, uId, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn,$_SESSION['uid'])."', now());"; 
		//mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		
	} 
} 

function AdInsights_30d_2($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); exit;
	
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$cSpend = $adset_tot = $lead = $cpr = $web_con_val = $link_clicks = $impressions = 0;
				$objType = $res['objective']; 
				if(isset($res['spend'])) { $cSpend=$res['spend']; } 
				if(isset($res['impressions'])) { $impressions=$res['impressions']; } 
				if(isset($res['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['actions'], $obj_arr[$objType]);
						$link_clicks = LeadGen($res['actions'], 'link_click');
					}
				}
				if(isset($res['action_values'])) {
					//if(array_key_exists($objType, $obj_arr)) {
						$web_con_val = LeadGen($res['action_values'], 'purchase');
						//$cpr = @(round($cSpend / $lead, 2));
					//}
				}
				$stDt = strtotime(date("".$res['date_start']." 00:00:00"));
				$enDt = strtotime(date("".$res['date_stop']." 23:59:59"));
				//echo $res['campaign_name'].'_'. $cSpend.'_'.$lead .'_'.$cpr.'<br>' ; exit;
				
			//$campQ = mysqli_query($conn, "select * from audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND ad_id='".$res['ad_id']."' AND st_dt=".$stDt." AND en_dt=".$enDt."");						
			
			//if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_adIns_30d_cpl_2 (accId,  ad_id, ad_name, adset_id, campaign_id,  adset_name, campaign_name, spend, leads, link_clicks, obj_type, web_con_val, impressions, st_dt, en_dt, sDate, eDate, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['ad_id'])."', '".mysqli_real_escape_string($conn, $res['ad_name'])."', '".mysqli_real_escape_string($conn, $res['adset_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $res['adset_name'])."', '".mysqli_real_escape_string($conn, $res['campaign_name'])."', '".mysqli_real_escape_string($conn, $cSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $link_clicks)."', '".mysqli_real_escape_string($conn, $objType)."', '".mysqli_real_escape_string($conn, $web_con_val)."', '".mysqli_real_escape_string($conn, $impressions)."', ".$stDt.", ".$enDt.", '".$res['date_start']."', '".$res['date_stop']."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			//} 
	}  //exit;*/
	if(isset($fb_response['paging']['next'])) {
		AdInsights_30d_2($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		
		$cirSql = "INSERT INTO audit_adIns_cpl (accId, uId, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn,$_SESSION['uid'])."', now());"; 
		//mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		
	} 
} 

include 'db.php';
include 'functions-report.php'; 

require 'email/vendor/autoload.php';
include 'email/config.php';

$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 



$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

$loadMsg = "Don't Refresh or Close the Page.... This will take 2 minutes";
$loadId = 1; 
if($access_token!='') {  
	if (!$tok_req = @file_get_contents_curl($tok_url)) { 
		  $loadMsg = "Sorry, Your Facebook Access token has been Expired or Invalid! <a href='fb-login.php?update=1'>Click Here</a> to renew access token";
		  $loadId = 2; 
		  $pg = 'cron-fb-report';      
		  //include 'email/mail-error.php';
		 // exit;
	} 
}

$obj_arr = array(
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'lead', 
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'lead'
);

$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$marks = 0;
/*
$q3 = "SELECT tbl_id FROM audit_adIns_cpl WHERE uid='".$_SESSION['uid']."' && accId='".$_GET['act']."' && date(updated) = CURDATE()";
$r3 = mysqli_query($conn, $q3);
if(mysqli_num_rows($r3)>0) {
	 echo "<script>window.location = 'cpl-comparison-view.php?act=".$_GET['act']."&name=".$_GET['name']."';</script>";
	 exit();
}*/
mysqli_query($conn, "DELETE FROM audit_adIns_30d_cpl WHERE accId='".$fbId."'") or die(mysqli_error());  
$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&fields=objective,adset_id,campaign_id,spend,adset_name,campaign_name,actions,action_values&time_range[since]=".date('Y-m-d', strtotime('-30 days'))."&time_range[until]=".date('Y-m-d', strtotime('-1 days'))."&time_increment=1&access_token=".$access_token."&limit=250"; //exit;
//&filtering=[{field: 'action_type', operator:'IN', value: ['offsite_conversion.fb_pixel_lead', 'purchase','link_click']}]
AdInsights_30d($request_url, $conn, $fbId, $obj_arr, $fbId, $marks); 

mysqli_query($conn, "DELETE FROM audit_adIns_30d_cpl_2 WHERE accId='".$fbId."'") or die(mysqli_error());  
$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=ad&fields=objective,ad_id,adset_id,campaign_id,spend,ad_name,adset_name,campaign_name,impressions,actions,action_values&time_range[since]=".date('Y-m-d', strtotime('-30 days'))."&time_range[until]=".date('Y-m-d', strtotime('-1 days'))."&time_increment=1&access_token=".$access_token."&limit=250";
AdInsights_30d_2($request_url, $conn, $fbId, $obj_arr, $fbId, $marks); 

//exit;
$name = $_GET['name'];
echo "<script>window.location = 'cpl-comparison-view2.php?act=".$_GET['act']."&name=".$name."';</script>";
exit();


?>