<?php session_start(); 
date_default_timezone_set('Asia/Kolkata');

include 'db.php';
//include 'functions-report.php'; 

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

function LeadGenTot($arr, $filt) {
	//print_r($arr);
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		//echo count($arr); 
		//echo 3; 
		//d($arr[0]->action_type); exit;
		for($q=0; $q<count($arr); $q++) {
			//foreach($arr[$q] as $v) {
				if(isset($arr[$q]->action_type) && $arr[$q]->action_type==$filt) $r = $arr[$q]->value;	
			//}
		}
	}
	return $r;
	//exit;
}

function FB_Report($url, $fbId, $uId, $conn, $incNo) {
	
	$fb_obj =  array(0=>'APP_INSTALLS', 1=>'BRAND_AWARENESS', 2=>'CONVERSIONS', 3=>'EVENT_RESPONSES', 4=>'LEAD_GENERATION', 5=>'LINK_CLICKS', 6=>'LOCAL_AWARENESS', 7=>'MESSAGES', 8=>'OFFER_CLAIMS', 9=>'PAGE_LIKES', 10=>'POST_ENGAGEMENT', 11=>'PRODUCT_CATALOG_SALES', 12=>'REACH', 13=>'VIDEO_VIEWS');

	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	$_SESSION['fb_response']  = $fb_response ;
	$fb_response = $_SESSION['fb_response'];
	//d($fb_response);
	$cmpDetail = $cmpObjDetail = array();
	foreach ($fb_response->data as $key => $res) 
	{		
			//$cmpDetail[$res->campaign_id] = $res->campaign_name;
			//$cmpObjDetail[$res->campaign_id] = $res->objective;
			//$cmpTy = array_search($res->objective, $fb_obj);
			//$act = json_decode($res->actions);
			if($res->objective=='LEAD_GENERATION' || $res->objective=='OUTCOME_LEADS') { $leads_lg = LeadGenTot($res->actions, 'leadgen_grouped'); }
			//if($res->objective=='OUTCOME_LEADS') { $leads_lg = LeadGenTot($res->actions, 'lead'); }
			$leads_con = LeadGenTot($res->actions, 'offsite_conversion.fb_pixel_lead');	
			//d($res->actions);
			//inline_link_clicks - conv, clicks - LG
			//$conn->query("TRUNCATE TABLE troubleshoot_reports");
			$cirRes = mysqli_query($conn, "select * from troubleshoot_reports WHERE adset_id='".$res->adset_id."' AND camp_id='".$res->campaign_id."' AND acc_id='".$fbId."' AND uId='".$uId."' AND stDt='".strtotime($res->date_start)."' AND enDt='".strtotime($res->date_stop)."'");	
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO troubleshoot_reports (uId, acc_id, acc_name, camp_id, camp_name, adset_id, adset_name, objective, spend, leads_lg, leads_con, ctr, created_time, stDt, enDt, created) VALUES ('".$uId."', '".$fbId."', '".mysqli_real_escape_string($conn, $res->account_name)."', '".mysqli_real_escape_string($conn, $res->campaign_id)."', '".mysqli_real_escape_string($conn, $res->campaign_name)."',  '".mysqli_real_escape_string($conn, $res->adset_id)."', '".mysqli_real_escape_string($conn, $res->adset_name)."', '".mysqli_real_escape_string($conn, $res->objective)."', '".mysqli_real_escape_string($conn, $res->spend)."', '".$leads_lg."', '".$leads_con."', '".mysqli_real_escape_string($conn, $res->ctr)."', '".strtotime($res->created_time)."', '".strtotime($res->date_start)."', '".strtotime($res->date_stop)."', now())"; 
					//$cirSql = "INSERT INTO troubleshoot_reports (uId, acc_id, acc_name, camp_id, camp_name, adset_id, adset_name, objective, spend, leads_lg, leads_con, created_time, stDt, enDt, created) VALUES ('".$uId."', '".$fbId."', '".mysqli_real_escape_string($conn, 'Company - '.$incNo)."', '".mysqli_real_escape_string($conn, $res->campaign_id)."', '".mysqli_real_escape_string($conn, 'Campaign Name ')."',  '".mysqli_real_escape_string($conn, $res->adset_id)."', '".mysqli_real_escape_string($conn, 'AdSet Name')."', '".mysqli_real_escape_string($conn, $res->objective)."', '".mysqli_real_escape_string($conn, $res->spend)."', '".$leads_lg."', '".$leads_con."', '".strtotime($res->created_time)."', '".strtotime($res->date_start)."', '".strtotime($res->date_stop)."', now())"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					 $cirSql = "UPDATE troubleshoot_reports SET leads_lg='".$leads_lg."', leads_con='".$leads_con."', spend='".mysqli_real_escape_string($conn, $res->spend)."', ctr='".mysqli_real_escape_string($conn, $res->ctr)."', updated=now() WHERE adset_id='".$res->adset_id."' AND camp_id='".$res->campaign_id."' AND acc_id='".$fbId."' AND uId='".$uId."' AND stDt='".strtotime($res->date_start)."' AND enDt='".strtotime($res->date_stop)."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			}		
			
	}  
	
	/*foreach($cmpDetail as $cKey => $cVal)
	{
			$cirRes = mysqli_query($conn, "select * from fb_campaigns WHERE camp_id='".$cKey."' AND fb_acc='".$fbId."' AND uid='".$uId."'");	
			
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO fb_campaigns (uid, fb_acc, camp_id, camp_name, camp_obj, created) VALUES ('".$uId."', '".$fbId."', '".mysqli_real_escape_string($conn, $cKey)."', '".mysqli_real_escape_string($conn, $cVal)."', '".mysqli_real_escape_string($conn, $cmpObjDetail[$cKey])."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 				
	}*/
	if(isset($fb_response->paging->next)) {
		FB_Report($fb_response->paging->next, $fbId, $uId, $conn, $incNo);
	}
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

$fbIds = $accName = array();
$today =  date('Y-m-d');
$last_5 =  date('Y-m-d', strtotime("-5 days")); 

mysqli_query($conn, "TRUNCATE TABLE troubleshoot_reports");

$sqlRev=mysqli_query($conn, "SELECT acc_id,acc_name FROM troubleshoot WHERE uid='".$uId."' AND delete_status=0");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['acc_id']!='') { $fbIds[] = $sqlROW['acc_id']; $accName[$sqlROW['acc_id']] = $sqlROW['acc_name']; }
}
echo 'Please wait..<br><br>';
if(count($fbIds)>0) {
	$fbIds = array_unique($fbIds);	
	$xyz =1;
	foreach($fbIds as $k => $v) {
		
				
		  $request_url = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=adset&fields=actions,account_name,account_id,campaign_name,campaign_id,adset_name,adset_id,spend,reach,impressions,objective,ctr,created_time&filtering=[{"field":"adset.objective","operator":"IN","value":["LEAD_GENERATION","OUTCOME_LEADS","CONVERSIONS"]},{"field":"action_type","operator":"IN","value":["leadgen_grouped","offsite_conversion.fb_pixel_lead"]},{"field":"adset.effective_status","operator":"IN","value":["ACTIVE"]}]&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($last_5)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'&limit=750&time_increment=1'; //exit;
		
		//exit;//act_340860296308567/insights?level=account&field=spend&date_preset=lifetime&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION","CONVERSIONS"]}]
		
		//$request_url = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=campaign&fields=objective,campaign_id,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,relevance_score,outbound_clicks&time_increment=1&limit=500&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($last90)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'';
		$fbData = FB_Report($request_url, $v, $uId, $conn, $xyz);
		
		$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?fields=actions,spend&level=account&date_preset=maximum&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION","OUTCOME_LEADS"]},{"field":"action_type","operator":"IN","value":["lead"]}]&access_token='.$access_token);
		
		echo 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?fields=actions,spend&level=account&date_preset=maximum&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]},{"field":"action_type","operator":"IN","value":["offsite_conversion.fb_pixel_lead"]}]&access_token='.$access_token; 

		$val2 = get_data('https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?fields=actions,spend&level=account&date_preset=maximum&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]},{"field":"action_type","operator":"IN","value":["offsite_conversion.fb_pixel_lead"]}]&access_token='.$access_token);
		
		//$val2 = get_data('https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&field=spend&date_preset=lifetime&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]},{"field":"action_type","operator":"IN","value":["offsite_conversion.fb_pixel_lead"]}]&access_token='.$access_token);
		//print_r($val);
		//d($val2);
		//exit;
		if(isset($val['data'][0]['spend'])) {
			if(isset($val['data'][0]['actions'][0]['value'])) { $life_lead=$val['data'][0]['actions'][0]['value']; } else { $life_lead=0; }
			
			$cirSql = "UPDATE troubleshoot SET life_bud_lg='".$val['data'][0]['spend']."', life_leads='".$life_lead."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		
		if(isset($val2['data'][0]['spend'])) {
			if(isset($val2['data'][0]['actions'][0]['value'])) { $life_lead2=$val2['data'][0]['actions'][0]['value']; } else { $life_lead2=0; }
			
			$cirSql = "UPDATE troubleshoot SET life_bud_con='".$val2['data'][0]['spend']."', life_conv='".$life_lead2."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		echo $accName[$v].' - Done <br>'; 
		//echo 'Company '.$xyz.' - Done <br>'; 
		$xyz++;
	}
} // exit;
echo '<br><b>successfully completed</b>';
echo '<script type="text/javascript">
           window.location = "https://stage.adrescue.in/troubleshoot-view.php"
      </script>';
