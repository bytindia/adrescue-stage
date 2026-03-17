<?php session_start();
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata'); ?>
<!DOCTYPE html> 
<html lang="en"> 
  
<head> 
    <meta charset="utf-8" /> 
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0" /> 
    <meta http-equiv="X-UA-Compatible" content="ie=edge" /> 
    <title>Loading...</title> 
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
</head> 
<style>
.container .jumbotron, .container-fluid .jumbotron { margin-top:100px; background:#fff; } 
.display-3 { font-size:28px; margin-top:50px; }
</style>
  
<body> 

    
<div class="container h-100 d-flex justify-content-center text-center">

    <div class="jumbotron my-auto">
      <div class="text-center">
                <a><img src="images/logo-350.png" alt="AdRescue" ></a>
      </div>
	  <H3>Loading...</H3>
      <h2 class="display-3">  Don't Refresh or Close the Page.... This will take 2 minutes</h2>

    </div>

 </div>  

<?php //exit;
include '/home/digitalb2k/stage.adrescue.in/db.php'; 
include '/home/digitalb2k/stage.adrescue.in/functions-report.php'; 

require '/home/digitalb2k/stage.adrescue.in/email/vendor/autoload.php';
include '/home/digitalb2k/stage.adrescue.in/email/config.php';



$query = "SELECT tbl_id, audit_name, fb_id, acc_id, page_id, acc_name, pg_name, acc_token, page_token, email_ids FROM audit_reports WHERE tbl_id='".$_GET['tbl_id']."'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['acc_token']; 
$uId = $rep_id = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['acc_id'] = $row['acc_id'];
$_SESSION['page_id'] = $row['page_id'];
$_SESSION['acc_name'] = $row['acc_name'];
$_SESSION['pg_name'] = $row['pg_name'];
$_SESSION['acc_token'] = $row['acc_token'];
$_SESSION['page_token'] = $row['page_token'];
$_SESSION['email_ids'] = $row['email_ids'];

$fb_adAcc = $_SESSION['acc_id'];
$page_id = $_SESSION['page_id'] ;
$pg_access_token = $_SESSION['page_token'];

$repQ = mysqli_query($conn, "select * from audit_data WHERE rep_id='".$rep_id."' AND  acc_id='".$fb_adAcc."'");						
if(mysqli_num_rows($repQ)==0) {
	$repSql = "INSERT INTO audit_data (rep_id, acc_id, updated) VALUES ('".mysqli_real_escape_string($conn, $rep_id)."', '".mysqli_real_escape_string($conn, $fb_adAcc)."', now());"; 
	mysqli_query($conn, $repSql) or die(mysqli_error()); 
}



$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";
  
if($access_token!='') {  
	if (!$tok_req = file_get_contents_curl($tok_url)) { 
		  $pg = 'cron-fb-report';      
		  include 'email/mail-error.php';
		  exit;
	} 
}

$obj_arr = array(
'POST_ENGAGEMENT' => 'page_engagement',
'LINK_CLICKS' => 'link_click',
'VIDEO_VIEWS' => 'video_view',
'LEAD_GENERATION' => 'leadgen_grouped',
'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
'MESSAGES' => 'onsite_conversion.messaging_block'
);

$gAccIds = array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$marks = 0;
 

	//$pg_access_token
//d($interests); exit;

function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

$lp_urls = array();
function ObjResults($url, $conn, $accId, $pgId, $obj_arr, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); exit;
	foreach($fb_response['data'] as $k => $v) {
					if(array_key_exists($v['objective'], $obj_arr)) {
						$objSpend[$v['objective']][] = $v['spend'];
						$objValue[$v['objective']][] = LeadGen($v['actions'], $obj_arr[$v['objective']]);
					}
	}
	
	if(isset($fb_response['paging']['next'])) {
		ObjResults($fb_response['paging']['next'], $conn, $accId, $pgId, $obj_arr, $rep_id, $marks);
	} else {
		$res = '';
		if(isset($objSpend) && count($objSpend)>0) {
			foreach($objSpend as $k => $v) {
						$cpr = @(round(array_sum($v) / array_sum($objValue[$k]), 2));
						$res .= $k.': '.array_sum($objValue[$k]).' | &#8377; '.$cpr.'<>';
			}
		}
		mysqli_query($conn, "UPDATE audit_data SET obj_cost_result='".$res."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
		//echo $res;
	}
}

function AdSet($url, $conn, $accId, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	foreach ($fb_response->data as $key => $res) {
				$intr_name = $loc_name = $w_pos_name = $w_emp_name = $pacing_type = $expansion = '';
				if(isset($res->bid_strategy) && $res->bid_strategy!='') { $bid='manual'; } else { $bid='auto'; } 
				if(isset($res->is_dynamic_creative) && $res->is_dynamic_creative!='') { $dynamic='Yes'; } else { $dynamic='No'; } 
				if(isset($res->targeting->flexible_spec[0]->interests)) { //d($res->targeting->flexible_spec[0]->interests); 
					 $intr_comma = array_column($res->targeting->flexible_spec[0]->interests, 'name');
					 sort($intr_comma);
					 $intr_name = implode(', ', $intr_comma);
				}
				if(isset($res->targeting->geo_locations->location_types)) {
					 $loc_name = implode(', ', $res->targeting->geo_locations->location_types);
				}
				if(isset($res->targeting->flexible_spec[0]->work_positions)) { //d($res->targeting->flexible_spec[0]->interests); 
					$w_pos_comma = array_column($res->targeting->flexible_spec[0]->work_positions, 'name');
					sort($w_pos_comma);
					$w_pos_name = implode(', ', $w_pos_comma);
				}
				if(isset($res->targeting->flexible_spec[0]->work_employers)) { //d($res->targeting->flexible_spec[0]->interests); 
					$w_emp_comma = array_column($res->targeting->flexible_spec[0]->work_employers, 'name');
					sort($w_emp_comma);
					$w_emp_name = implode(', ', $w_emp_comma);
				}
				/*if(isset($res->targeting->publisher_platforms) && isset($res->targeting->device_platforms) && count($res->targeting->publisher_platforms)==4 && count($res->targeting->device_platforms)==2) { 
					$placement = 'auto';
				} else { 
					$placement = 'manual';
				}*/
				if(isset($res->targeting->targeting_optimization)) {
					 $expansion = $res->targeting->targeting_optimization;
				}	
				if(isset($res->pacing_type[0])) { $pacing_type = $res->pacing_type[0]; } 
				$pubPlat = $devicePlat = 0;
				if(!isset($res->targeting->publisher_platforms) || (isset($res->targeting->publisher_platforms) && count($res->targeting->publisher_platforms)==4)) {
					$pubPlat = 1;
				} 
				if(!isset($res->targeting->device_platforms) || (isset($res->targeting->device_platforms) && count($res->targeting->device_platforms)==2)) {
					$devicePlat = 1;
				} 
				if($pubPlat==1 && $devicePlat == 1) { $placement = 'auto'; } else { $placement = 'manual'; }
	
			$AdsetQ = mysqli_query($conn, "select * from audit_adset WHERE adset_id='".$res->id."' AND accId='".$accId."'");						
			
			if(mysqli_num_rows($AdsetQ)==0) {
					$cirSql = "INSERT INTO audit_adset (accId, adset_id, adset_name, bid_strategy, is_dynamic_creative, adset_type, age_group, interests, geo_locations, campaign_id, daily_budget, lifetime_budget, work_employers, work_positions, pacing_type, expansion, placement, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res->id)."', '".mysqli_real_escape_string($conn, $res->name)."', '".mysqli_real_escape_string($conn, $bid)."', '".mysqli_real_escape_string($conn, $dynamic)."', '".mysqli_real_escape_string($conn, $res->adcreatives->data[0]->object_type)."', '".mysqli_real_escape_string($conn, $res->targeting->age_min.'-'.$res->targeting->age_max)."', '".mysqli_real_escape_string($conn, $intr_name)."', '".mysqli_real_escape_string($conn, $loc_name)."', '".mysqli_real_escape_string($conn, $res->campaign_id)."', '".mysqli_real_escape_string($conn, $res->daily_budget)."', '".mysqli_real_escape_string($conn, $res->lifetime_budget)."', '".mysqli_real_escape_string($conn, $w_emp_name)."', '".mysqli_real_escape_string($conn, $w_pos_name)."', '".mysqli_real_escape_string($conn, $pacing_type)."', '".mysqli_real_escape_string($conn, $expansion)."',  '".mysqli_real_escape_string($conn, $placement)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  
	if(isset($fb_response->paging->next)) {
		AdSet($fb_response->paging->next, $conn, $accId, $rep_id, $marks);
	} else {
		$bid_strategy = $is_dynamic_creative = $adset_type = $interests = $geo_locations = $interests = $work_positions = $placement = $work_employers = array();
		$daily_budget = $lifetime_budget = $day_part = '';
		$placement = $age_group = array();
		$sql_1 = mysqli_query($conn, "SELECT age_group, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' group by age_group");
		while($row1 = mysqli_fetch_array($sql_1)) { $age_group[]  = $row1['age_group'].': '.$row1['tot']; }
		
		$sql_2 = mysqli_query($conn, "SELECT bid_strategy, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' group by bid_strategy");
		while($row2 = mysqli_fetch_array($sql_2)) { $bid_strategy[]  = $row2['bid_strategy'].': '.$row2['tot']; }
		
		$sql_3 = mysqli_query($conn, "SELECT is_dynamic_creative, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' group by is_dynamic_creative");
		while($row3 = mysqli_fetch_array($sql_3)) { $is_dynamic_creative[]  = $row3['is_dynamic_creative'].': '.$row3['tot']; 
			if(strpos(strtolower($row3['is_dynamic_creative']), 'yes') == false) { $marks += 5; }
		}
		
		$sql_4 = mysqli_query($conn, "SELECT adset_type, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' group by adset_type");
		while($row4 = mysqli_fetch_array($sql_4)) { if($row4['adset_type']=='PHOTO' || $row4['adset_type']=='VIDEO') { $adset_type[]  = $row4['adset_type'].': '.$row4['tot'];  }
			if(strpos(strtolower($row4['adset_type']), 'video') == false) { $marks += 2; }
			if(strpos(strtolower($row4['adset_type']), 'photo') == false) { $marks += 2; }
		}
		
		$sql_5 = mysqli_query($conn, "SELECT geo_locations, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND geo_locations!='' group by geo_locations");
		while($row5 = mysqli_fetch_array($sql_5)) { $geo_locations[]  = $row5['geo_locations'].': '.$row5['tot']; }
		
		$sql_6 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND daily_budget!=0 AND daily_budget!=''");
		while($row6 = mysqli_fetch_array($sql_6)) { $daily_budget  = 'DAILY: '.$row6['tot']; }
		
		$sql_7 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND pacing_type=0 AND lifetime_budget!=''");
		while($row7 = mysqli_fetch_array($sql_7)) { $lifetime_budget  = 'LIFETIME: '.$row7['tot']; }
		
		$sql_8 = mysqli_query($conn, "SELECT count(tbl_id) as tot, interests FROM `audit_adset` WHERE accId='".$accId."' AND interests!='' group by interests ORDER BY `tot`  DESC");
		while($row8 = mysqli_fetch_array($sql_8)) { $interests[]  = $row8['interests']; }
		if(count($interests)>0) {
			$marks += 5;
			$intr_imp = implode('<>',$interests);
			$intr_exp = explode('<>',$intr_imp);
			$interests = array_unique($intr_exp);
		}
		
		$sql_9 = mysqli_query($conn, "SELECT count(tbl_id) as tot, work_employers FROM `audit_adset` WHERE accId='".$accId."' AND work_employers!='' group by interests ORDER BY `tot`  DESC");
		while($row9 = mysqli_fetch_array($sql_9)) { $work_employers[]  = $row9['work_employers']; }
		if(count($work_employers)>0) {
			$w_emp_imp = implode('<>',$work_employers);
			$w_emp_exp = explode('<>',$w_emp_imp);
			$work_employers = array_unique($w_emp_exp);
		}
		
		$sql_10 = mysqli_query($conn, "SELECT count(tbl_id) as tot, work_positions FROM `audit_adset` WHERE accId='".$accId."' AND work_positions!='' group by work_positions  ORDER BY `tot`  DESC");
		while($row10 = mysqli_fetch_array($sql_10)) { $work_positions[]  = $row10['work_positions']; }
		if(count($work_positions)>0) {
			$w_pos_imp = implode('<>',$work_positions);
			$w_pos_exp = explode('<>',$w_pos_imp);
			$work_positions = array_unique($w_pos_exp);
		}
		
		$sql_11 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND pacing_type='day_parting'");
		while($row11 = mysqli_fetch_array($sql_11)) { 
			if($row11['tot']>0) { $day_part = 'Yes'; } else { $day_part = 'No'; }
		}
		
		$sql_12 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND expansion='expansion_all'");
		while($row12 = mysqli_fetch_array($sql_12)) { 
			if($row12['tot']>0) { $exp_on = 'Yes'; $marks += 5; } else { $exp_on = 'No'; }
		}
		
		$sql_13 = mysqli_query($conn, "SELECT placement, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND placement!='' group by placement");
		while($row13 = mysqli_fetch_array($sql_13)) { $placement[]  = $row13['placement'].': '.$row13['tot']; }
		
		$cirSql = "UPDATE audit_data SET age_group='".mysqli_real_escape_string($conn, implode('<>', $age_group))."', bid_type='".mysqli_real_escape_string($conn, implode('<>', $bid_strategy))."', dynamic='".mysqli_real_escape_string($conn, implode('<>', $is_dynamic_creative))."', adset_type='".mysqli_real_escape_string($conn, implode('<>', $adset_type))."', loc_type='".mysqli_real_escape_string($conn, implode('<>', $geo_locations))."', daily_life='".mysqli_real_escape_string($conn, $daily_budget.'<>'.$lifetime_budget)."', interests='".mysqli_real_escape_string($conn, implode('<><>', $interests))."', work_pos='".mysqli_real_escape_string($conn, implode('<><>', $work_positions))."', work_emp='".mysqli_real_escape_string($conn, implode('<><>', $work_employers))."', day_part='".mysqli_real_escape_string($conn, $day_part)."', exp_on='".mysqli_real_escape_string($conn, $exp_on)."', placement='".mysqli_real_escape_string($conn, implode('<>', $placement))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
	}
}

function LeadQus($url, $conn, $accId, $pgId, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); exit;
	foreach ($fb_response['data'] as $key => $res) {
				if(isset($res['questions'])) {
					foreach ($res['questions'] as $k => $v) {
						if(isset($v['type']) && $v['type']=='CUSTOM'){
							$l_opt ='';
							if(isset($v['options'])) {
							$l_opt_v = array_column($v['options'], 'value');
							$l_opt = implode(', ', $l_opt_v);
							}
							$LqQ = mysqli_query($conn, "select * from audit_lead_qus WHERE pg_id='".$pgId."' AND l_id='".$v['id']."'");						
			
							if(mysqli_num_rows($LqQ)==0) {
									$cirSql = "INSERT INTO audit_lead_qus (pg_id, l_id, l_key, l_lab, l_opt, created) VALUES ('".mysqli_real_escape_string($conn, $pgId)."', '".mysqli_real_escape_string($conn, $v['id'])."', '".mysqli_real_escape_string($conn, $v['key'])."', '".mysqli_real_escape_string($conn, $v['label'])."', '".mysqli_real_escape_string($conn, $l_opt)."',  now());"; 
									mysqli_query($conn, $cirSql) or die(mysqli_error()); 
							} 
						}
					}
				}
	 }  
	if(isset($fb_response['paging']['next'])) {
		LeadQus($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks); 
	} else {
		$lQus = $leadQus = array();
		
		$sql_1 = mysqli_query($conn, "SELECT l_lab,l_opt FROM `audit_lead_qus` WHERE pg_id='".$pgId."' GROUP BY l_key");
		while($row1 = mysqli_fetch_array($sql_1)) { $lQus[]  = $row1['l_lab'].': '.$row1['l_opt']; }
		
		
		if(count($lQus)>0) {
			$leadQus = implode('<>',$lQus);
			$cirSql = "UPDATE audit_data SET lead_qus='".mysqli_real_escape_string($conn, $leadQus)."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
	}
}

$lp_urls = array();
function Lp_Url($url, $conn, $accId, $pgId, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); exit;
	foreach ($fb_response['data'] as $key => $res) {
				if(isset($res['adcreatives']['data'])) {
					foreach ($res['adcreatives']['data'] as $k => $v) {
						$getURL =  $v['object_story_spec']['link_data']['link'];
						$getURL = strtok($getURL, '?');
						$lp_urls[] = rtrim($getURL,"/");

					}
				}
	 }  
	if(isset($fb_response['paging']['next'])) {
		Lp_Url($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks);
	} else {
		if(isset($lp_urls) && count($lp_urls)>0) {
			$lp_urls = array_unique($lp_urls);
			$lp_urls = array_filter($lp_urls);  
			$lp_urls = array_values($lp_urls); 
			$cirSql = "UPDATE audit_data SET lp_url='".mysqli_real_escape_string($conn, implode('<>',$lp_urls))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error());
		}
		// d($lp_urls);
	}
}

function AdSetInsights($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests, true);
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$adSpend = $lead = $cpr =0;
				
				if(isset($res['adcreatives']['data'][0]['object_type'])) { $adType=$res['adcreatives']['data'][0]['object_type']; } 
				if(isset($res['insights']['data'][0]['objective'])) { $objType=$res['insights']['data'][0]['objective']; } 
				if(isset($res['insights']['data'][0]['spend'])) { $adSpend=$res['insights']['data'][0]['spend']; } 
				if(isset($res['insights']['data'][0]['quality_ranking'])) { $adQty=$res['insights']['data'][0]['quality_ranking']; } 
				if(isset($res['insights']['data'][0]['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['insights']['data'][0]['actions'], $obj_arr[$objType]);
						$cpr = @(round($adSpend / $lead, 2));
					}
				}
				
			$AdInQ = mysqli_query($conn, "select * from audit_ad WHERE ad_id='".$res['id']."' AND accId='".$accId."' ");						
			
			if(mysqli_num_rows($AdInQ)==0) {
					$cirSql = "INSERT INTO audit_ad (accId, ad_id, adset_id, campaign_id, ad_type, ad_qty, obj_type, spend, lead, cpl, status, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['id'])."', '".mysqli_real_escape_string($conn, $res['adset_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $adType)."', '".mysqli_real_escape_string($conn, $adQty)."', '".mysqli_real_escape_string($conn, $objType)."', '".mysqli_real_escape_string($conn, $adSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $cpr)."', '".mysqli_real_escape_string($conn, $res['status'])."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); //exit;
			} 
	}  
	if(isset($fb_response['paging']['next'])) {
		AdSetInsights($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		$ad_type_res = $ad_type_best = $ad_qty = $lg_0 = $lg_cpl_less25 = $lg_cpl_high25 = $lg_0 = $conv_0 = $conv_cpl_less25 = $conv_cpl_high25 = array();
		
		
		$sql_1 = mysqli_query($conn, "SELECT ad_type, SUM(spend) AS spend, SUM(lead) as lead, (SUM(spend)/SUM(lead)) as per FROM `audit_ad` where accId='".$accId."' AND obj_type='LEAD_GENERATION' GROUP BY ad_type");
		while($row1 = mysqli_fetch_array($sql_1)) { if($row1['ad_type']=='PHOTO' || $row1['ad_type']=='VIDEO') { $cpl=@($row1['spend']/$row1['lead']); if($cpl>0) { $ad_type_res[]  = $row1['ad_type'].': '.round($cpl,2); } } }
		
		if(count($ad_type_res)>0) {
			$adty_imp = implode('<>',$ad_type_res);
			$adty_exp = explode('<>',$adty_imp);
			$ad_type_res = array_unique($adty_exp);
		}
		
		$sql_2 = mysqli_query($conn, "SELECT ad_type, min(cpl) AS cpl FROM `audit_ad` WHERE accId='".$accId."' AND cpl!='0' GROUP BY ad_type");
		while($row2 = mysqli_fetch_array($sql_2)) { if($row2['cpl']!='') { $ad_type_best[]  = $row2['ad_type'].': '.round($row2['cpl'],2); } }
		
		if(count($ad_type_best)>0) {
			$adty_imp2 = implode('<>',$ad_type_best);
			$adty_exp2 = explode('<>',$adty_imp2);
			$ad_type_best = array_unique($adty_exp2);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT ad_qty, count(ad_qty) AS tot FROM `audit_ad` WHERE accId='".$accId."' AND ad_qty!='' GROUP BY ad_qty");
		while($row3 = mysqli_fetch_array($sql_3)) {  $ad_qty[]  = $row3['ad_qty'].': '.$row3['tot'];  }
		
		if(count($ad_type_best)>0) {
			$adty_imp3 = implode('<>',$ad_qty);
			$adty_exp3 = explode('<>',$adty_imp3);
			$ad_qty = array_unique($adty_exp3);
		}
		
		$sql_4 = mysqli_query($conn, "SELECT a.adset_id, SUM(a.spend) AS spend, SUM(a.lead) as lead, (SUM(a.spend)/SUM(a.lead)) as per, b.adset_name FROM `audit_ad` as a, audit_adset as b where a.adset_id=b.adset_id AND a.accId='".$accId."' AND a.obj_type='LEAD_GENERATION' GROUP BY a.adset_id HAVING lead=0");
		while($row4 = mysqli_fetch_array($sql_4)) {  $lg_0[]  = $row4['adset_name'];  }
		
		$sql_5 = mysqli_query($conn, "SELECT a.adset_id, SUM(a.spend) AS spend, SUM(a.lead) as lead, (SUM(a.spend)/SUM(a.lead)) as per, b.adset_name FROM `audit_ad` as a, audit_adset as b where a.adset_id=b.adset_id AND a.accId='".$accId."' AND a.obj_type='CONVERSIONS' GROUP BY a.adset_id HAVING lead=0");
		while($row5 = mysqli_fetch_array($sql_5)) {  $conv_0[] = $row5['adset_name'];  }
		
		$sql_6 = mysqli_query($conn, "SELECT obj_type, adset_id, SUM(spend) AS spend, SUM(lead) as lead, (SUM(spend)/SUM(lead)) as per FROM `audit_ad` where accId='".$accId."' AND (obj_type='LEAD_GENERATION' OR obj_type='CONVERSIONS') GROUP BY obj_type");
		while($row6 = mysqli_fetch_array($sql_6)) { 
			$per1=round(($row6['spend']/$row6['lead'])*1.25);
			$per2=round(($row6['spend']/$row6['lead'])*0.75);
			
			if($row6['obj_type']=='LEAD_GENERATION') {
				
				$sql_61 = mysqli_query($conn, "SELECT a.adset_id, SUM(a.spend) AS spend, SUM(a.lead) as lead, (SUM(a.spend)/SUM(a.lead)) as per, b.adset_name FROM `audit_ad` as a, audit_adset as b where a.adset_id=b.adset_id AND a.accId='".$accId."' AND a.obj_type='LEAD_GENERATION' GROUP BY a.adset_id HAVING lead>0 AND per>{$per1}");
				while($row_61 = mysqli_fetch_array($sql_61)) {  $lg_cpl_less25[]  = $row_61['adset_name'];  }
				$sql_62 = mysqli_query($conn, "SELECT a.adset_id, SUM(a.spend) AS spend, SUM(a.lead) as lead, (SUM(a.spend)/SUM(a.lead)) as per, b.adset_name FROM `audit_ad` as a, audit_adset as b where a.adset_id=b.adset_id AND a.accId='".$accId."' AND a.obj_type='LEAD_GENERATION' GROUP BY a.adset_id HAVING lead>0 AND per>{$per2}");
				while($row_62 = mysqli_fetch_array($sql_62)) {  $lg_cpl_high25[]  = $row_62['adset_name'];  }

			}
			if($row63['obj_type']=='CONVERSIONS') {
				$sql_63 = mysqli_query($conn, "SELECT a.adset_id, SUM(a.spend) AS spend, SUM(a.lead) as lead, (SUM(a.spend)/SUM(a.lead)) as per, b.adset_name FROM `audit_ad` as a, audit_adset as b where a.adset_id=b.adset_id AND a.accId='".$accId."' AND a.obj_type='CONVERSIONS' GROUP BY a.adset_id HAVING lead>0 AND per>{$per1}");
				while($row_63 = mysqli_fetch_array($sql_63)) {  $conv_cpl_less25[]  = $row_63['adset_name'];  }
				
				$sql_64 = mysqli_query($conn, "SELECT a.adset_id, SUM(a.spend) AS spend, SUM(a.lead) as lead, (SUM(a.spend)/SUM(a.lead)) as per, b.adset_name FROM `audit_ad` as a, audit_adset as b where a.adset_id=b.adset_id AND a.accId='".$accId."' AND a.obj_type='CONVERSIONS' GROUP BY a.adset_id HAVING lead>0 AND per>{$per2}");
				while($row_64 = mysqli_fetch_array($sql_64)) {  $conv_cpl_high25[]  = $row_64['adset_name'];  }

			}
				//$ad_qty[]  = $row4['ad_qty'].': '.$row3['tot'];  
		} 
		
		$cirSql = "UPDATE audit_data SET ad_type_res='".mysqli_real_escape_string($conn, implode('<>', $ad_type_res))."', ad_type_best='".mysqli_real_escape_string($conn, implode('<>', $ad_type_best))."', ad_qty='".mysqli_real_escape_string($conn, implode('<>', $ad_qty))."', lg_lead_0='".mysqli_real_escape_string($conn, implode('<>', $lg_0))."', conv_lead_0='".mysqli_real_escape_string($conn, implode('<>', $conv_0))."', lg_cpl_less25='".mysqli_real_escape_string($conn, implode('<>', $lg_cpl_less25))."', lg_cpl_high25='".mysqli_real_escape_string($conn, implode('<>', $lg_cpl_high25))."', conv_cpl_less25='".mysqli_real_escape_string($conn, implode('<>', $conv_cpl_less25))."', conv_cpl_high25='".mysqli_real_escape_string($conn, implode('<>', $conv_cpl_high25))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error()); //exit;
	} 
}

		
function Campaign($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$cSpend = $adset_tot = $lead = $cpr =0;
				$objType = $res['objective']; 
				if(isset($res['adsets']['data'])) { $adset_tot = count($res['adsets']['data']); } 
				if(isset($res['insights']['data'][0]['spend'])) { $cSpend=$res['insights']['data'][0]['spend']; } 
				if(isset($res['daily_budget']) || isset($res['lifetime_budget'])) { $budget = 'Yes'; } else { $budget = 'No';  } 
				if(isset($res['insights']['data'][0]['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['insights']['data'][0]['actions'], $obj_arr[$objType]);
						$cpr = @(round($cSpend / $lead, 2));
					}
				}
				
			$campQ = mysqli_query($conn, "select * from audit_campaign WHERE id='".$res['id']."'");						
			
			if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_campaign (accId, id, name, objective, budget_type, spend, leads, cpl, adset_tot, status, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['id'])."', '".mysqli_real_escape_string($conn, $res['name'])."', '".mysqli_real_escape_string($conn, $objType)."', '".mysqli_real_escape_string($conn, $budget)."', '".mysqli_real_escape_string($conn, $cSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $cpr)."', '".mysqli_real_escape_string($conn, $adset_tot)."', '".mysqli_real_escape_string($conn, $res['status'])."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  //exit;
	if(isset($fb_response['paging']['next'])) {
		Campaign($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		
		$ad_type_res = $ad_type_best = $cmp_high = array();
		$objQ ='';
		$sql_1 = mysqli_query($conn, "SELECT tbl_id FROM `audit_campaign` WHERE accId='".$accId."' AND adset_tot>1 AND budget_type='Yes'");
		if(mysqli_num_rows($sql_1)>0) { $cbo_camp ='Yes'; $marks += 5; } else { $cbo_camp ='No'; }
		//SELECT id,name,objective, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM `audit_campaign` GROUP by objective
		
		$sql_2 = mysqli_query($conn, "SELECT id, name, objective, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM `audit_campaign` WHERE accId='".$accId."' AND objective='LEAD_GENERATION' GROUP by objective");
		
		while($row2 = mysqli_fetch_array($sql_2)) {  if($row2['c']>0) { $objQ .= " (objective ='".$row2['objective']."' AND cpl>".round($row2['c'],2).") OR "; } }
		if($objQ!='') {
			$objQ = ' WHERE accId='.$accId.' AND '.substr($objQ, 0, -4) ;
			$sql_3 = mysqli_query($conn, "SELECT name,cpl FROM `audit_campaign` {$objQ}");
			while($row3 = mysqli_fetch_array($sql_3)) {  $cmp_high[]  = $row3['name'].': '.round($row3['cpl'],2);  }
			
			if(count($cmp_high)>0) {
				$cmp_imp3 = implode('<>',$cmp_high);
				$cmp_exp3 = explode('<>',$cmp_imp3);
				$cmp_high = array_unique($cmp_exp3);
			}
		}
		
		
		$cirSql = "UPDATE audit_data SET cbo_camp='".mysqli_real_escape_string($conn, $cbo_camp)."', high_cpl='".mysqli_real_escape_string($conn, implode('<>', $cmp_high))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
		
	}
}

	//Facebook
	if($fb_adAcc!='') 
	{
		$fbIds = explode(',',$fb_adAcc);	$fb_stDt = explode(',',$sqlROW['fb_stDt']);	
		foreach($fbIds as $key => $fbId) {						
			echo $i . "<br />";
			if(isset($fb_stDt[$key]) && $fb_stDt[$key]!='') { $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($fb_stDt[$key])).'&time_range[until]='.date("Y-m-d", strtotime($today)).''; } else { $dtRange = 'date_preset=lifetime'; }
			
			
			//Get Campaign wise CPR
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?fields=spend,objective,actions&level=campaign&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','POST_ENGAGEMENT','VIDEO_VIEWS','CONVERSIONS','MESSAGES','LINK_CLICKS']}]&access_token=".$access_token."&date_preset=lifetime&limit=500";
			ObjResults($request_url, $conn, $fb_adAcc, $page_id, $obj_arr, $rep_id, $marks);
				
			//}
			//mysqli_query($conn, "UPDATE audit_data SET obj_cost_result='".$obj_cost_result."' WHERE acc_id=".$fbId."") or die(mysqli_error()); 
			
			
			
			//Get Age & Status
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/?level=account&fields=name,age,created_time,account_status&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			$created_time = date('Y-m-d', strtotime($fb_response['created_time']));
			mysqli_query($conn, "UPDATE audit_data SET age='".$created_time."', active='".$fb_response['account_status']."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());  
			
			//Get Campaigns Total
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/campaigns?date_preset=lifetime&summary=total_count&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			mysqli_query($conn, "UPDATE audit_data SET camp_tot='".$fb_response['summary']['total_count']."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());  
			
			
			//Get Ads pixcel
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/adspixels?fields=is_unavailable&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if($fb_response['data'][0]['is_unavailable']=='' || $fb_response['data'][0]['is_unavailable']==false) { $pix_act = 'Yes'; $marks += 5; } else { $pix_act = 'No'; }
			mysqli_query($conn, "UPDATE audit_data SET pix_act='".$pix_act."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());  
			
			
			//Get Custom Conversion
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/customconversions?fields=id&limit=500&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $cust_conv='Yes'; $marks += 7; } else { $cust_conv='No'; }
			mysqli_query($conn, "UPDATE audit_data SET cust_conv='".$cust_conv."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			  
			//Get Ad rules
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/adrules_library?fields=id&limit=500&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $rules='Yes'; $marks += 8; } else { $rules='No'; }
			mysqli_query($conn, "UPDATE audit_data SET rules='".$rules."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			
			//Get Ad rules
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/adrules_library?fields=id&limit=500&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $rules='Yes'; } else { $rules='No'; }
			mysqli_query($conn, "UPDATE audit_data SET rules='".$rules."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			
			//act_107704242648697/customaudiences?fields=name,subtype,delivery_status&filtering=[{'field':'subtype','operator':'IN','value':['WEBSITE']}]
			//act_107704242648697/customaudiences?fields=name,subtype,delivery_status&filtering=[{'field':'delivery_status.code','operator':'IN','value':['200']}]
			
			//Get Cust Aud
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/customaudiences?fields=name,subtype,delivery_status&filtering=[{'field':'delivery_status.code','operator':'IN','value':['200']},{'field':'subtype','operator':'IN','value':['CUSTOM']}]&access_token=".$access_token."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $cust_aud='Yes'; $marks += 5; } else { $cust_aud='No'; }
			mysqli_query($conn, "UPDATE audit_data SET cust_aud='".$cust_aud."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			//Get LA Aud
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/customaudiences?fields=name,subtype,delivery_status&filtering=[{'field':'delivery_status.code','operator':'IN','value':['200']},{'field':'subtype','operator':'IN','value':['LOOKALIKE']}]&access_token=".$access_token."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $la_aud='Yes'; $marks += 5; } else { $la_aud='No'; }
			mysqli_query($conn, "UPDATE audit_data SET la_aud='".$la_aud."', nar_aud='Yes' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			//Get Remarketting
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/customaudiences?fields=name,subtype,delivery_status&filtering=[{'field':'delivery_status.code','operator':'IN','value':['200']},{'field':'subtype','operator':'IN','value':['WEBSITE']}]&access_token=".$access_token."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $remarket='Yes'; $marks += 5; } else { $remarket='No'; }
			mysqli_query($conn, "UPDATE audit_data SET remarket='".$remarket."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			//Get top_web_25
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/customaudiences?fields=name,subtype&filtering=[{'field':'subtype','operator':'IN','value':['WEBSITE']},{'field':'name','operator':'CONTAIN','value':'25%'}]&access_token=".$access_token."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $top_web_25='Yes'; $marks += 5; } else { $top_web_25='No'; }
			mysqli_query($conn, "UPDATE audit_data SET top_web_25='".$top_web_25."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			//Get top_la_25
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/customaudiences?fields=name,subtype&filtering=[{'field':'subtype','operator':'IN','value':['LOOKALIKE']},{'field':'name','operator':'CONTAIN','value':'25%'}]&access_token=".$access_token."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(count($fb_response['data'])>0) { $top_la_25='Yes'; $marks += 5; } else { $top_la_25='No'; }
			mysqli_query($conn, "UPDATE audit_data SET top_la_25='".$top_la_25."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			
			//Get INSTAGRAM Account
			$request_url = "https://graph.facebook.com/".$api_ver."/".$page_id."?fields=connected_instagram_account,instagram_business_account,instagram_accounts{id,username,followed_by_count}&access_token=".$pg_access_token."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(isset($fb_response['instagram_accounts']['data']) && count($fb_response['instagram_accounts']['data'])>0) { $insta_acc='Yes'; $ig_count=$fb_response['instagram_accounts']['data'][0]['followed_by_count']; $marks += 5; } else { $insta_acc='No'; $ig_count=0; }
			mysqli_query($conn, "UPDATE audit_data SET insta_acc='".$insta_acc."',ig_followers='".$ig_count."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			//exit;
			
			
			//Get Ad image updated
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/adimages?fields=updated_time&sort=updated_time&limit=1&access_token='.$access_token.'';
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			if(isset($fb_response['data'][0]['updated_time'])) { $img_updated = date('d-m-Y h:i a', strtotime($fb_response['data'][0]['updated_time'])); } else { $img_updated =''; }
			mysqli_query($conn, "UPDATE audit_data SET img_updated='".$img_updated."' WHERE acc_id=".$fbId." AND rep_id='".$rep_id."'") or die(mysqli_error());
			
			
			//$request_url = "https://graph.facebook.com/".$api_ver."/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/adsets?fields=bid_strategy,configured_status,is_dynamic_creative,adcreatives{adlabels,object_type},targeting,name,campaign,campaign_id,daily_budget,lifetime_budget,pacing_type&date_preset=lifetime&access_token=".$access_token."&limit=500";
			AdSet($request_url, $conn, $fb_adAcc, $rep_id, $marks); //exit;
			
			
			//Get LeadQus 
			//$request_url = "https://graph.facebook.com/".$api_ver."/".$page_id."/leadgen_forms?fields=questions&access_token=".$pg_access_token."&limit=100";
			//LeadQus($request_url, $conn, $fb_adAcc, $page_id);
			
			//Get LeadQus 
			$request_url = "https://graph.facebook.com/".$api_ver."/".$page_id."/leadgen_forms?fields=questions&access_token=".$pg_access_token."&limit=100";
			LeadQus($request_url, $conn, $fb_adAcc, $page_id, $rep_id, $marks);
			
			//Get LP URLs 
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fb_adAcc."/adsets?fields=adcreatives{object_story_spec{link_data{link}}}&filtering=[{'field':'campaign.objective','operator':'IN','value':['CONVERSIONS']}]&access_token=".$pg_access_token."&limit=100";
			Lp_Url($request_url, $conn, $fb_adAcc, $page_id, $rep_id, $marks);
			
			//ADSET data
			//$request_url = "https://graph.facebook.com/".$api_ver."/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=adset_id,campaign_id,adcreatives{object_type},insights{objective,quality_ranking,spend,actions},status&date_preset=lifetime&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','POST_ENGAGEMENT','VIDEO_VIEWS','CONVERSIONS','MESSAGES','LINK_CLICKS']}]&access_token=".$access_token."&limit=500";
			AdSetInsights($request_url, $conn, $fbId, $obj_arr, $rep_id, $marks);
			
			
			//CAMPAIGN data
			//$request_url = "https://graph.facebook.com/".$api_ver."/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=id,bid_strategy,daily_budget,lifetime_budget,status,objective,name,adsets{id},insights{spend,actions}&date_preset=lifetime&access_token=".$access_token."&limit=500";
			Campaign($request_url, $conn, $fbId, $obj_arr, $rep_id, $marks);
			
			//UPDATE SYNC
			mysqli_query($conn, "UPDATE audit_reports SET sync='1', marks='".$marks."' WHERE tbl_id=".$_GET['tbl_id']."") or die(mysqli_error());
			
		}		
		//mysqli_query($conn, "UPDATE cashflow SET fb_spent='".implode(',',$fbSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	}

//echo "Report Completed!"; 
//exit;
if(isset($_GET['refresh'])) {
	echo "<script>window.location = 'audit.php?tbl_id=".$_GET['tbl_id']."';</script>"; 
	exit;
}

exit;
?>
</body> 
  
</html> 
