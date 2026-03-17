<?php session_start(); 
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', '0');

$dirpath = '/home/digitalb2k/stage.adrescue.in/';
include $dirpath.'db.php';

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
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
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

$extQ = '';
if(isset($_GET['user_id']) && $_GET['user_id']!=0){
    $extQ = "user_id='".$_GET['user_id']."' AND";
    mysqli_query($conn, "Delete from camp_report_data where user_id='".$_GET['user_id']."'");
} else {
    mysqli_query($conn, "TRUNCATE TABLE camp_report_data");
}

$sqlRev=mysqli_query($conn, "SELECT id,acc_id,acc_name,id,obj,camp_ids,user_id,cust_conv FROM camp_report WHERE $extQ delete_status=0");
                                        
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    //$cirSql2 = ;
    mysqli_query($conn, "UPDATE camp_report SET updated=now() WHERE id=".$sqlROW['id']."") or die(mysqli_error());

    if($sqlROW['acc_id']!='') { $fbIds[] = $sqlROW['acc_id']; $accName[$sqlROW['acc_id']] = $sqlROW['acc_name']; }

    $fbId = $sqlROW['acc_id'];
    $campIds = unserialize($sqlROW['camp_ids']);
    $cust_conv_id='';
    if($sqlROW['cust_conv']!='' && $sqlROW['cust_conv']!=null) { $cust_conv_id='offsite_conversion.custom.'.$sqlROW['cust_conv']; }

    // skip account-level metrics per requirement

    // campaign-level metrics for selected campaigns
    $actCampIdsStr = implode(',', array_unique($campIds));
    if($actCampIdsStr=='') { continue; }
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=campaign&breakdowns=publisher_platform&fields=campaign_id,campaign_name,impressions,spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':[".$objectives2[$sqlROW['obj']]."]},{'field':'campaign.id','operator':'IN','value':[".$actCampIdsStr."]}]&access_token=".$access_token."&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    // group by campaign and split by platform
    $grouped = [];
    if(isset($fb_response['data'])){
        foreach($fb_response['data'] as $v) {
            $campId = $v['campaign_id'];
            $campName = $v['campaign_name'];
            $obj = $v['objective'];
            if($obj=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
            if($obj=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
            $platform = isset($v['publisher_platform']) ? $v['publisher_platform'] : 'facebook';
            if(!isset($grouped[$campId])) {
                $grouped[$campId] = ['name'=>$campName, 'obj'=>$obj, 'meta'=>['spend'=>0,'leads'=>0,'ecom'=>0], 'insta'=>['spend'=>0,'leads'=>0,'ecom'=>0]];
            }
            $leads = $ecom_lead = 0;
            $metricKey = $cust_conv_id=='' ? $objectives[$obj]['valueKey'] : $cust_conv_id;
            if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $metricKey); $ecom_lead = LeadGen($v['actions'], 'offsite_conversion.fb_pixel_lead'); }
            if($platform==='instagram') {
                $grouped[$campId]['insta']['spend'] += $v['spend'];
                $grouped[$campId]['insta']['leads'] += $leads;
                $grouped[$campId]['insta']['ecom'] += $ecom_lead;
            } else {
                $grouped[$campId]['meta']['spend'] += $v['spend'];
                $grouped[$campId]['meta']['leads'] += $leads;
                $grouped[$campId]['meta']['ecom'] += $ecom_lead;
            }
        }
    }
    // insert aggregated rows
    foreach($grouped as $cid => $data) {
        $meta_spend = $data['meta']['spend'];
        $meta_leads = $data['meta']['leads'];
        $meta_ecom = $data['meta']['ecom'];
        $insta_spend = $data['insta']['spend'];
        $insta_leads = $data['insta']['leads'];
        $insta_ecom = $data['insta']['ecom'];
        $objIns = $data['obj'];
        $cpl_meta = ($meta_spend>0 && $meta_leads>0) ? round($meta_spend/$meta_leads) : 0;
        $cpl_insta = ($insta_spend>0 && $insta_leads>0) ? round($insta_spend/$insta_leads) : 0;
        $cirSql = "INSERT INTO camp_report_data (client, acc_id, user_id, obj, camp_id, camp_name, spend, leads, cpl, ecom_lead, spend_i, leads_i, cpl_i, ecom_lead_i, created) VALUES ('".$sqlROW['acc_name']."', '".$fbId."', '".$sqlROW['user_id']."', '".$objIns."', '".mysqli_real_escape_string($conn, $cid)."', '".mysqli_real_escape_string($conn, $data['name'])."', '".mysqli_real_escape_string($conn, $meta_spend)."', '".mysqli_real_escape_string($conn, $meta_leads)."', '".mysqli_real_escape_string($conn, $cpl_meta)."', '".mysqli_real_escape_string($conn, $meta_ecom)."', '".mysqli_real_escape_string($conn, $insta_spend)."', '".mysqli_real_escape_string($conn, $insta_leads)."', '".mysqli_real_escape_string($conn, $cpl_insta)."', '".mysqli_real_escape_string($conn, $insta_ecom)."', now())";
        mysqli_query($conn, $cirSql) or die(mysqli_error($conn));
    }
}
//echo 'successfully completed...';
if(isset($_GET['refresh'])) {
    echo "<script>window.location = 'index.php';</script>";
    exit();
}
?>


