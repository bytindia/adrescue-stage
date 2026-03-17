<?php session_start();    
date_default_timezone_set('Asia/Kolkata'); 

include 'db.php';
//include 'functions-report.php'; 
include 'google-ads.php';

if(isset($_SESSION['uid'])){
	$uId = $_SESSION['uid'];
} else {
	$uId = 2;
}

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=$uId";
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
			foreach($arr[$q] as $v) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
			}
		}
	}
	return $r;
	//exit;
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
//$stDt =  date('Y-m-d', strtotime("-5 days")); 

 $last_6d = date("Y-m-d", strtotime("-6 days"));
 $last_4d = date("Y-m-d", strtotime("-4 days"));
 $last_3d = date("Y-m-d", strtotime("-3 days"));
 $last_1d = date("Y-m-d", strtotime("-1 days"));
//exit;
$stDt = date("Y-m-d", strtotime("first day of this month"));
if($stDt > $last_6d) {
	$stDt = $last_6d;
}
$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 
mysqli_query($conn, "TRUNCATE TABLE checklist_reports");

$sqlRev=mysqli_query($conn, "SELECT acc_id,g_acc,acc_name FROM checklist WHERE $extQ uid='".$uId."' AND delete_status=0 order by tbl_id desc");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['acc_id']!='') { if($sqlROW['acc_id']!='') { $fbIds[] = $sqlROW['acc_id']; } $accName[$sqlROW['acc_id']] = $sqlROW['acc_name']; if($sqlROW['g_acc']!='') { $gaIds[] = $sqlROW['g_acc']; } }
}
echo 'Please wait..<br><br>';

//GOOGLE ADS
if(count($gaIds)>0) {
	//d($gaIds); exit;
	//$gaIds = explode(',',$sqlROW['g_id']); //$g_stDt = explode(',',$SatrtDate);		
	foreach($gaIds as $key => $gaId) {
		
		$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId, $stDt, $today);
		if(isset($getAccRep['cost'])) {
			$spend = round($getAccRep['cost']); 
			$leads = round($getAccRep['conversions']); 
			$clicks = round($getAccRep['clicks']); 
			$cpl = @($spend / $leads);
			$ctr = @(($getAccRep['clicks'] / $getAccRep['impressions']) * 100);
			 $cirSql = "UPDATE checklist SET spend_g='".round($spend,2)."', mon_cpl_g='".round($cpl,2)."' WHERE g_acc='".$gaId."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($getAccRep); exit;
		//Last 3d, Google 
		$getAccRep2 = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId, date("Y-m-d", strtotime($last_3d)), date("Y-m-d", strtotime($last_1d)));
		if(isset($getAccRep2['cost'])) {
			$spend = round($getAccRep2['cost']); 
			$leads = round($getAccRep2['conversions']); 
			$clicks = round($getAccRep2['clicks']); 
			$cpl = @($spend / $leads);
			$ctr = @(($getAccRep2['clicks'] / $getAccRep2['impressions']) * 100);
			$cirSql = "UPDATE checklist SET last3_cpl_g='".round($cpl,2)."', last3_ctr_g='".round($ctr,2)."' WHERE g_acc='".$gaId."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//Rev 3d, Google 
		$getAccRep3 = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId, date("Y-m-d", strtotime($last_6d)), date("Y-m-d", strtotime($last_4d)));
		if(isset($getAccRep3['cost'])) {
			$spend = round($getAccRep3['cost']); 
			$leads = round($getAccRep3['conversions']); 
			$clicks = round($getAccRep3['clicks']); 
			$cpl = @($spend / $leads);
			$ctr = @(($getAccRep3['clicks'] / $getAccRep3['impressions']) * 100);
			$cirSql = "UPDATE checklist SET prev3_cpl_g='".round($cpl,2)."', prev3_ctr_g='".round($ctr,2)."' WHERE g_acc='".$gaId."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//echo 1; 
		//d($getAccRep3); exit;
	}
	
	//echo "UPDATE budget_reminder SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id'].""; exit;
	//mysqli_query($conn, "UPDATE budget_reminder SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
}
//exit;

//FB ADS
if(count($fbIds)>0) {
	$fbIds = array_unique($fbIds);	
	$xyz =1;
	foreach($fbIds as $k => $v) {
		
		
		//--------------- Current month spent  ----------------//
		$url_spend = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,actions&access_token='.$access_token.'&date_preset=this_month';
		$res_spend = get_data($url_spend);
		//$mon_spend = $res_spend['data'][0]['spend'];
		if(isset($res_spend['data'][0]['spend'])) {
			$cirSql = "UPDATE checklist SET spent_fb='".round($res_spend['data'][0]['spend'],2)."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($res_spend);exit;
		
		
		//--------------- Last 3d, LG ----------------//
		$url_last_3d_lg = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,ctr,actions&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION"]},{"field":"campaign.name","operator":"NOT_CONTAIN","value":"call"}]&date_preset=last_3d';
		$res_last_3d_lg = get_data($url_last_3d_lg);

		if(isset($res_last_3d_lg['data'][0]['spend'])) {
			$spend = $res_last_3d_lg['data'][0]['spend'];
			$leads = LeadGenTot($res_last_3d_lg['data'][0]['actions'], 'leadgen_grouped');
			$cpl = @($spend / $leads);
			$cirSql = "UPDATE checklist SET last3_cpl_lg='".round($cpl,2)."', last3_ctr_lg='".round($res_last_3d_lg['data'][0]['ctr'],2)."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($res_last_3d_lg);exit;

		//--------------- Prev 3d, LG ----------------//
		$url_prev_3d_lg = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,ctr,actions&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION"]},{"field":"campaign.name","operator":"NOT_CONTAIN","value":"call"}]&time_range[since]='.date("Y-m-d", strtotime($last_6d)).'&time_range[until]='.date("Y-m-d", strtotime($last_4d)).'';
		$res_prev_3d_lg = get_data($url_prev_3d_lg);

		if(isset($res_prev_3d_lg['data'][0]['spend'])) {
			$spend = $res_prev_3d_lg['data'][0]['spend'];
			$leads = LeadGenTot($res_prev_3d_lg['data'][0]['actions'], 'leadgen_grouped');
			$cpl = @($spend / $leads);
			$cirSql = "UPDATE checklist SET prev3_cpl_lg='".round($cpl,2)."', prev3_ctr_lg='".round($res_prev_3d_lg['data'][0]['ctr'],2)."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($res_prev_3d_lg);exit;

		//--------------- thins Month, LG ----------------//
		$url_mon_lg = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,ctr,actions&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION"]},{"field":"campaign.name","operator":"NOT_CONTAIN","value":"call"}]&date_preset=this_month';
		$res_mon_lg = get_data($url_mon_lg);

		if(isset($res_mon_lg['data'][0]['spend'])) {
			$spend = $res_mon_lg['data'][0]['spend'];
			$leads = LeadGenTot($res_mon_lg['data'][0]['actions'], 'leadgen_grouped');
			$cpl = @($spend / $leads);
			$cirSql = "UPDATE checklist SET mon_cpl_lg='".round($cpl,2)."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($res_mon_lg);exit;
		
		//--------------- Last 3d, Conv  ----------------//
		$url_last_3d_con = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,actions,inline_link_clicks&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]}]&date_preset=last_3d';
		$res_last_3d_con = get_data($url_last_3d_con);

		if(isset($res_last_3d_con['data'][0]['spend'])) {
			$spend = $res_last_3d_con['data'][0]['spend'];
			
			$leads = LeadGenTot($res_last_3d_con['data'][0]['actions'], 'offsite_conversion.fb_pixel_lead');
			$cpl = @($spend / $leads);
			
			$purchase = LeadGenTot($res_last_3d_con['data'][0]['actions'], 'purchase');
			$cpp =  @($spend / $purchase);

			$atc = LeadGenTot($res_last_3d_con['data'][0]['actions'], 'add_to_cart');
			$catc =  @($spend / $atc);

			$clicks = LeadGenTot($res_last_3d_con['data'][0]['actions'], 'link_click');
			$cpc =  @($spend / $clicks);

			$ctr =  @(($res_last_3d_con['data'][0]['inline_link_clicks'] / $res_last_3d_con['data'][0]['impressions'])*100);

			//echo $ctr = @($res_last_3d_con['data'][0]['inline_link_clicks'] / $res_last_3d_con['data'][0]['impressions']) * 100;
			$cirSql = "UPDATE checklist SET last3_cpl_conv='".round($cpl,2)."', last3_ctr_conv='".round($ctr,2)."', last3_cpp_conv='".round($cpp,2)."', last3_atc_conv='".round($catc,2)."', last3_cpc_conv='".round($cpc,2)."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($res_last_3d_con); exit;

		//--------------- Prev 3d, Conv  ----------------//
		$url_prev_3d_con = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,actions,inline_link_clicks&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]}]&time_range[since]='.date("Y-m-d", strtotime($last_6d)).'&time_range[until]='.date("Y-m-d", strtotime($last_4d)).'';
		$res_prev_3d_con = get_data($url_prev_3d_con);

		if(isset($res_prev_3d_con['data'][0]['spend'])) {
			$spend = $res_prev_3d_con['data'][0]['spend'];
			
			$leads = LeadGenTot($res_prev_3d_con['data'][0]['actions'], 'offsite_conversion.fb_pixel_lead');
			$cpl = @($spend / $leads);
			
			$purchase = LeadGenTot($res_prev_3d_con['data'][0]['actions'], 'purchase');
			$cpp =  @($spend / $purchase);

			$atc = LeadGenTot($res_prev_3d_con['data'][0]['actions'], 'add_to_cart');
			$catc =  @($spend / $atc);

			$clicks = LeadGenTot($res_prev_3d_con['data'][0]['actions'], 'link_click');
			$cpc =  @($spend / $clicks);

			$ctr =  @(($res_prev_3d_con['data'][0]['inline_link_clicks'] / $res_prev_3d_con['data'][0]['impressions'])*100);

			//echo $ctr = @($res_prev_3d_con['data'][0]['inline_link_clicks'] / $res_prev_3d_con['data'][0]['impressions']) * 100;
			$cirSql = "UPDATE checklist SET prev3_cpl_conv='".round($cpl,2)."', prev3_ctr_conv='".round($ctr,2)."', prev3_cpp_conv='".round($cpp,2)."', prev3_atc_conv='".round($catc,2)."', prev3_cpc_conv='".round($cpc,2)."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}

		//--------------- this month, Conv  ----------------//
		$url_mon_con = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,actions,inline_link_clicks&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]}]&date_preset=this_month';
		$res_mon_con = get_data($url_mon_con);

		if(isset($res_mon_con['data'][0]['spend'])) {
			$spend = $res_mon_con['data'][0]['spend'];
			
			$leads = LeadGenTot($res_mon_con['data'][0]['actions'], 'offsite_conversion.fb_pixel_lead');
			$cpl = @($spend / $leads);
			
			$purchase = LeadGenTot($res_mon_con['data'][0]['actions'], 'purchase');
			$cpp =  @($spend / $purchase);

			$atc = LeadGenTot($res_mon_con['data'][0]['actions'], 'add_to_cart');
			$catc =  @($spend / $atc);

			$link_click = LeadGenTot($res_mon_con['data'][0]['actions'], 'link_click');
			$ctr_link_click =  @($spend / $link_click);

			//echo $ctr = @($res_mon_con['data'][0]['inline_link_clicks'] / $res_mon_con['data'][0]['impressions']) * 100;
			$cirSql = "UPDATE checklist SET mon_cpl_conv='".round($cpl,2)."', mon_cpc_conv='".round($ctr_link_click,2)."', mon_cpp_conv='".round($cpp,2)."', mon_atc_conv='".round($catc,2)."'  WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		//d($res_mon_con); exit;
		//d($res_prev_3d_con); exit;

		/*
		//Prev 3d, LG
		$url_prev_3d_lg = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,ctr,actions&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION"]}]&date_preset=last_3d';
		$res_prev_3d_lg = get_data($url_prev_3d_lg);

		d($res_prev_3d_lg);

		//Prev 3d, Conv
		$url_prev_3d_con = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend,impressions,objective,actions,inline_link_clicks&access_token='.$access_token.'&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]}]&date_preset=last_3d';
		$res_prev_3d_con = get_data($url_prev_3d_con);

		d($res_prev_3d_con);
		


		exit;
		  echo $request_url = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&fields=spend&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($stDt)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'&limit=500&time_increment=1'; //exit;
		
		//exit;//act_340860296308567/insights?level=account&field=spend&date_preset=lifetime&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION","CONVERSIONS"]}]
		
		//$request_url = 'https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=campaign&fields=objective,campaign_id,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,relevance_score,outbound_clicks&time_increment=1&limit=500&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($last90)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'';
		$fbData = FB_Report($request_url, $v, $uId, $conn, $xyz);
		exit;
		$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?fields=actions,spend&level=account&date_preset=lifetime&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION"]},{"field":"action_type","operator":"IN","value":["leadgen_grouped"]}]&access_token='.$access_token);
		
		$val2 = get_data('https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?fields=actions,spend&level=account&date_preset=lifetime&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]},{"field":"action_type","operator":"IN","value":["offsite_conversion.fb_pixel_lead"]}]&access_token='.$access_token);
		
		//$val2 = get_data('https://graph.facebook.com/'.$api_ver.'/'.$v.'/insights?level=account&field=spend&date_preset=lifetime&filtering=[{"field":"campaign.objective","operator":"IN","value":["CONVERSIONS"]},{"field":"action_type","operator":"IN","value":["offsite_conversion.fb_pixel_lead"]}]&access_token='.$access_token);
		//print_r($val);
		//print_r($val2);
		 exit;
		if(isset($val['data'][0]['spend'])) {
			if(isset($val['data'][0]['actions'][0]['value'])) { $life_lead=$val['data'][0]['actions'][0]['value']; } else { $life_lead=0; }
			
			$cirSql = "UPDATE checklist SET life_bud_lg='".$val['data'][0]['spend']."', life_leads='".$life_lead."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		
		if(isset($val2['data'][0]['spend'])) {
			if(isset($val2['data'][0]['actions'][0]['value'])) { $life_lead2=$val2['data'][0]['actions'][0]['value']; } else { $life_lead2=0; }
			
			$cirSql = "UPDATE checklist SET life_bud_con='".$val2['data'][0]['spend']."', life_conv='".$life_lead2."' WHERE acc_id='".$v."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
		*/
		echo $accName[$v].' - Done <br>'; 
		//echo 'Company '.$xyz.' - Done <br>'; 
		$xyz++;
	}
}  

$upq = "UPDATE checklist SET updated=now()";
mysqli_query($conn, $upq) or die(mysqli_error()); 

//exit;
echo '<br><b>successfully completed</b>';
echo '<script type="text/javascript">
           window.location = "https://stage.adrescue.in/checklist.php"
      </script>';
