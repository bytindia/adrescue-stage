<?php session_start(); 
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', '1');

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
global $output;
function loopAdRep($url) {
    global $output;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
    if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = array_merge($output, $fb_response['data']);
    } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = $fb_response['data']; 
    }
    
	if(isset($fb_response['paging']['next'])) {
		loopAdRep($fb_response['paging']['next']);
	} else { 
        return $output['data'] = $output; 
	}
}
function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'LG', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];
$objectives2 = [
    'LEAD_GENERATION' => "'LEAD_GENERATION','OUTCOME_LEADS'",
    'CONVERSIONS' => "'CONVERSIONS'",
    'OUTCOME_SALES' => "'OUTCOME_SALES','PRODUCT_CATALOG_SALES'",
];

$sinceDate = date('Y-m-d', strtotime('-30 days'));
$untilDate = date('Y-m-d', strtotime('-1 day'));

mysqli_query($conn, "TRUNCATE TABLE troubleshoot_cpl");
mysqli_query($conn, "TRUNCATE TABLE troubleshoot_data");

mysqli_query($conn, "TRUNCATE TABLE troubleshoot_reports");

$sqlRev=mysqli_query($conn, "SELECT acc_id,acc_name FROM troubleshoot WHERE uid='2' AND delete_status=0");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['acc_id']!='') { $fbIds[] = $sqlROW['acc_id']; $accName[$sqlROW['acc_id']] = $sqlROW['acc_name']; }

    $fbId = str_replace('act_', '', $sqlROW['acc_id']);

    $avg_cpl= [];
    foreach($objectives2 as $k => $v){

        $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=account&fields=spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':[".$v."]}]&access_token=".$access_token."&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."";
        $requests = file_get_contents_curl($request_url);
        $fb_response = json_decode($requests,true);
        //d($fb_response);
        if(isset($fb_response['data'])){
            foreach($fb_response['data'] as $k1 => $v1) {
                $leads = $cpl = 0;
                $obj = $k;
                if(isset($v1['actions'])) { $leads = LeadGen($v1['actions'], $objectives[$obj]['valueKey']); }
                if($v1['spend']>0 && $leads>0) { $cpl= round($v1['spend']/$leads); }
                //$avg_cpl[$obj] = array('spend'=>$v1['spend'], 'lead'=>$leads, 'cpl'=>round($cpl));

                $cirSql = "INSERT INTO troubleshoot_cpl (acc_id, acc_name, obj, spend, leads, cpl, created) VALUES ('".$fbId."', '".$sqlROW['acc_name']."', '".$obj."', '".mysqli_real_escape_string($conn, $v1['spend'])."', '".mysqli_real_escape_string($conn, $leads)."', '".mysqli_real_escape_string($conn, round($cpl))."', now())"; 
                mysqli_query($conn, $cirSql) or die(mysqli_error()); 
            }
        }
    }

    //Active Campaigns Summary
    $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750"; //exit;
        
    $act_ads_camp_ids  = $output = array(); 
    loopAdRep($url_ad);  
    $res_ad = $output;
    if(isset($res_ad['data']) && count($res_ad['data'])>0) {
        foreach($res_ad['data'] as $kk => $vv) {
            if($vv['effective_status']=='ACTIVE'){
                $act_ads_camp_ids[] = $vv['campaign_id'];
                //$act_ads_adset_ids[] = $vv['adset_id'];
            }
        }
    }
    
    //d($act_ads_adset_ids);

    $act_campIds = $act_ads_id = $act_ads_adset_ids = $zero_lead_adset = array();

    $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=id,name,bid_strategy,effective_status,end_time,adsets.limit(50){id,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
        $output = $res = $act_campIds = array();
        loopAdRep($url);  
        $res = $output; $k=1;
        //d($res);
        if(isset($res['data']) && count($res['data'])>0){ 
            foreach($res['data'] as $k1 => $v1) {
                $camp_act = 'y';
                if(isset($v1['end_time'])) { 
                    $end_time = new DateTime($v1['end_time']);
                    $currentDateTime = new DateTime('now', $end_time->getTimezone());
                    if($end_time < $currentDateTime) { $camp_act = 'n'; }
                }

                if($v1['effective_status']=='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act=='y') {  
                    // && isset($v1['bid_strategy']) && ($v1['bid_strategy']=='LOWEST_COST_WITH_BID_CAP' || $v1['bid_strategy']=='COST_CAP')){
                    $camp_act = 'n';
                    if(isset($v1['adsets']['data'])){
                        
                        foreach($v1['adsets']['data'] as $as_k => $as_v) {
                            $ads_act = 'n';
                            if($as_v['effective_status']=='ACTIVE'){
                                if(isset($as_v['ads']['data'])){
                                    $endCheck = 'y';
                                    if(isset($as_v['end_time'])) { 
                                        $end_time = new DateTime($as_v['end_time']);
                                        $currentDateTime = new DateTime('now', $end_time->getTimezone());
                                        if($end_time < $currentDateTime) { $ads_act = 'n'; $endCheck='n'; }
                                    }
                                    if($endCheck=='y'){
                                        foreach($as_v['ads']['data'] as $ad_k => $ad_v) {
                                            if($ad_v['effective_status']=='ACTIVE'){
                                                $ads_act = $camp_act = 'y';
                                                $act_ads_id[] = $ad_v['id'];
                                            }
                                        }
                                        if($ads_act == 'y'){
                                            $act_campIds[$v1['id']] = $v1['id'];
                                            $act_ads_adset_ids[] = $as_v['id'];
                                        }
                                    }
                                }
                            }
                        }
                    

                    }
                }
        } 
    }

    $cirRes = mysqli_query($conn, "select * from troubleshoot_cpl WHERE acc_id='".$fbId."'");	
	if(mysqli_num_rows($cirRes)==1) {
        $cirSql2 = "UPDATE troubleshoot_cpl SET act_camp='".count(array_unique($act_campIds)).",".count(array_unique($act_ads_adset_ids)).",".count(array_unique($act_ads_id))."' WHERE acc_id='".$fbId."'";
        //mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
    }
   

    //Zero Leads in the Last 3days
    if(count($act_ads_adset_ids)>0){
        
        $actAd_ids_str = implode(',', array_unique($act_ads_adset_ids));
        $last_3d = date('Y-m-d', strtotime('-3 days'));
        $untilDate = date('Y-m-d', strtotime('-1 days'));

        $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&fields=adset_id,campaign_id,campaign_name,adset_name,impressions,spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','CONVERSIONS','OUTCOME_SALES','OUTCOME_LEADS','PRODUCT_CATALOG_SALES']},{'field':'impressions','operator':'GREATER_THAN','value':1000},{'field':'adset.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&limit=750&time_range[since]=".$last_3d."&time_range[until]=".$untilDate."";

        $requests = file_get_contents_curl($request_url);
        $fb_response = json_decode($requests,true);
        if(isset($fb_response['data'])){
            $act_camp_tot = $act_adset_tot = array();
            foreach($fb_response['data'] as $k => $v) {
                
                $leads = $cpl = 0;
                $obj = $v['objective'];
                if($v['objective']=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
                if($v['objective']=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
                if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $objectives[$obj]['valueKey']); }
                if($v['spend']>0 && $leads>0) { $cpl= round($v['spend']/$leads); }
                if($leads==0){
                   // $zero_lead_adset[$obj][$v['adset_id']] = array('id'=>$v['adset_id'], 'camp_id'=>$v['campaign_id'], 'camp_name'=>$v['campaign_name'], 'adset_name'=>$v['ad_name']);
                }
                //$camp_data_all[$obj][$v['campaign_id']][] = array('spend'=>$v['spend'], 'lead'=>$leads, 'name'=>$v['campaign_name']);
                $cirSql = "INSERT INTO troubleshoot_data (acc_id, obj, adset_id, camp_id, adset_name, camp_name, spend, leads, cpl, created) VALUES ('".$fbId."', '".$obj."', '".mysqli_real_escape_string($conn, $v['adset_id'])."', '".mysqli_real_escape_string($conn, $v['campaign_id'])."', '".mysqli_real_escape_string($conn, $v['adset_name'])."', '".mysqli_real_escape_string($conn, $v['campaign_name'])."', '".mysqli_real_escape_string($conn, $v['spend'])."', '".mysqli_real_escape_string($conn, $leads)."', '".mysqli_real_escape_string($conn, round($cpl))."', now())"; 
                mysqli_query($conn, $cirSql) or die(mysqli_error()); 

                $act_camp_tot[$obj][] = $v['campaign_id'];
                $act_adset_tot[$obj][] = $v['adset_id'];
                
                
            }
            //d($act_camp_tot); d($act_adset_tot);
            if(count($act_camp_tot)>0){
                foreach($act_camp_tot as $k => $v) {
                    $cirRes = mysqli_query($conn, "select * from troubleshoot_cpl WHERE acc_id='".$fbId."' AND obj='".$k."'");	
                    if(mysqli_num_rows($cirRes)==1) {
                        $cirSql2 = "UPDATE troubleshoot_cpl SET act_camp='".count(array_unique($act_camp_tot[$k]))."', act_adset='".count(array_unique($act_adset_tot[$k]))."' WHERE acc_id='".$fbId."' AND obj='".$k."'";
                        mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
                    }
                }
            }
            
        }
    }

}