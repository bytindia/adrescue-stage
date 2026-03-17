<?php session_start(); 
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$dirpath = '/home/digitalb2k/stage.adrescue.in/';
include $dirpath.'db.php';
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
		  include $dirpath.'email/mail-error.php';
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

if(isset($_SESSION['st'])) { 
    $stDt =  $_SESSION['st']; 
    $enDt =  $_SESSION['en']; 
    $filter = 'yes';
    $dtRange = $_SESSION['st'].' - '.$_SESSION['en']; 
    $sinceDate = date("Y-m-d", strtotime(str_replace('/', '-', $_SESSION['st']))); 
    $untilDate = date("Y-m-d", strtotime(str_replace('/', '-', $_SESSION['en']))); 
    $urlParam = '?st='.$stDt.'&en='.$enDt;
    
} else {
    $sinceDate = date('Y-m-d', strtotime('first day of this month'));
    $untilDate = date('Y-m-d', strtotime('today'));
}
$_SESSION['st'] = date('d/m/Y',strtotime($sinceDate));
$_SESSION['en'] = date('d/m/Y',strtotime($untilDate));

//print_r($_GET); exit;

//mysqli_query($conn, "TRUNCATE TABLE adset_report_data");

$extQ = '';
if(isset($_GET['user_id']) && $_GET['user_id']!=0){
    $extQ = "user_id='".$_GET['user_id']."' AND";
    mysqli_query($conn, "Delete from adset_report_data where user_id='".$_GET['user_id']."'");
} else {
    mysqli_query($conn, "TRUNCATE TABLE adset_report_data");
}

$sqlRev=mysqli_query($conn, "SELECT acc_id,acc_name,id,obj,adset_ids,user_id,cust_conv FROM adset_report WHERE $extQ delete_status=0");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['acc_id']!='') { $fbIds[] = $sqlROW['acc_id']; $accName[$sqlROW['acc_id']] = $sqlROW['acc_name']; }

    $fbId = $sqlROW['acc_id'];
    $adsetIds = unserialize($sqlROW['adset_ids']);
    $cust_conv_id='';
    if($sqlROW['cust_conv']!='' && $sqlROW['cust_conv']!=null) { $cust_conv_id='offsite_conversion.custom.'.$sqlROW['cust_conv']; }

    $avg_cpl= [];
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=account&fields=spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':[".$objectives2[$sqlROW['obj']]."]}]&access_token=".$access_token."&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate.""; 
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    //d($fb_response); exit;
    if(isset($fb_response['data'])){
            foreach($fb_response['data'] as $k1 => $v1) {
                $leads = $cpl = $ecom_lead = 0;
                $obj = $sqlROW['obj'];
                if($cust_conv_id=='') { $cust_conv_id = $objectives[$obj]['valueKey']; }
                if(isset($v1['actions'])) { $leads = LeadGen($v1['actions'], $cust_conv_id); $ecom_lead = LeadGen($v1['actions'], 'offsite_conversion.fb_pixel_lead');  }
                if($v1['spend']>0 && $leads>0) { $cpl= round($v1['spend']/$leads); }
                //$avg_cpl[$obj] = array('spend'=>$v1['spend'], 'lead'=>$leads, 'cpl'=>round($cpl));

                $cirSql2 = "UPDATE adset_report SET spend='".round($v1['spend'])."', leads='".round($leads)."', ecom_lead='".round($ecom_lead)."', cpl='".round($cpl)."', updated=now() WHERE id=".$sqlROW['id']."";
                mysqli_query($conn, $cirSql2) or die(mysqli_error());
            }
    }

    $actAd_ids_str = implode(',', array_unique($adsetIds));
    $adset_status = [];
    $request_url = "https://graph.facebook.com/".$api_ver."/?ids=".$actAd_ids_str."&fields=effective_status&access_token=".$access_token."";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    //d($fb_response); exit;
    if(isset($fb_response) && count($fb_response)>0){
        foreach($fb_response as $k1 => $v) {
            $adset_status[$k1] = 'no';
            if($v['effective_status']=='ACTIVE'){ $adset_status[$k1] = 'yes'; }
        }
    }
    //d($adset_status); exit;
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&fields=adset_id,campaign_id,campaign_name,adset_name,impressions,spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':[".$objectives2[$sqlROW['obj']]."]},{'field':'adset.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    //d($fb_response); exit;
    if(isset($fb_response['data'])){
            foreach($fb_response['data'] as $k1 => $v) {
                $status = 'no';
                $leads = $cpl = $ecom_lead = 0;
                $obj = $v['objective'];
                if(isset($adset_status[$v['adset_id']])) { $status = $adset_status[$v['adset_id']]; }
                if($v['objective']=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
                if($v['objective']=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
                if($cust_conv_id=='') { $cust_conv_id = $objectives[$obj]['valueKey']; }
                if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $cust_conv_id); $ecom_lead = LeadGen($v['actions'], 'offsite_conversion.fb_pixel_lead');  }
                if($v['spend']>0 && $leads>0) { $cpl= round($v['spend']/$leads); }
                if($leads==0){
                   // $zero_lead_adset[$obj][$v['adset_id']] = array('id'=>$v['adset_id'], 'camp_id'=>$v['campaign_id'], 'camp_name'=>$v['campaign_name'], 'adset_name'=>$v['ad_name']);
                }
                //$camp_data_all[$obj][$v['campaign_id']][] = array('spend'=>$v['spend'], 'lead'=>$leads, 'name'=>$v['campaign_name']);
                $cirSql = "INSERT INTO adset_report_data (client, acc_id, user_id, obj, adset_id, camp_id, adset_name, camp_name, spend, leads, cpl, ecom_lead, adset_status, created) VALUES ('".$sqlROW['acc_name']."', '".$fbId."', '".$sqlROW['user_id']."', '".$obj."', '".mysqli_real_escape_string($conn, $v['adset_id'])."', '".mysqli_real_escape_string($conn, $v['campaign_id'])."', '".mysqli_real_escape_string($conn, $v['adset_name'])."', '".mysqli_real_escape_string($conn, $v['campaign_name'])."', '".mysqli_real_escape_string($conn, $v['spend'])."', '".mysqli_real_escape_string($conn, $leads)."', '".mysqli_real_escape_string($conn, round($cpl))."', '".mysqli_real_escape_string($conn, $ecom_lead)."', '".$status."', now())"; 
                mysqli_query($conn, $cirSql) or die(mysqli_error()); 
            }
    }


}
echo 'successfully completed...';
if(isset($_GET['refresh'])) {
    echo "<script>window.location = 'index.php';</script>";
	exit();
}