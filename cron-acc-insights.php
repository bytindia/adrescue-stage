<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
//error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
ini_set('display_errors', 0);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include 'db.php';
include 'functions-report.php'; 

function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
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

 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

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

$d = new DateTime('first day of this month');
$d2 = new DateTime('today');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';

$sqlRev=mysqli_query($conn, "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE uid='".$uId."' AND delete_status=0 AND hide_temp='0'");

while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $fbIds = explode(',',$sqlROW['fb_id'] ?? '');
    foreach($fbIds as $key => $fbId) {	
            $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

			 $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=reach,spend,ctr,cpc,cpm,impressions,clicks,actions,action_values&access_token='.$access_token.'&'.$dtRange.''; //exit;
			$requests = curl_get_file_contents($request_url);
			$fb_response = json_decode($requests,true); 
			//d($fb_response);

            $add_to_cart = $purchase = $purchase_val = $lead = 0;
            if(isset( $fb_response['data'][0])) {
                $spend = $fb_response['data'][0]['spend'];
                if(isset($fb_response['data'][0]['actions'])) { $add_to_cart = LeadGen($fb_response['data'][0]['actions'], 'offsite_conversion.fb_pixel_add_to_cart');  }
    
                if(isset($fb_response['data'][0]['actions'])) { $purchase = LeadGen($fb_response['data'][0]['actions'], 'offsite_conversion.fb_pixel_purchase');  }
    
                if(isset($fb_response['data'][0]['action_values'])) {  $purchase_val = LeadGen($fb_response['data'][0]['action_values'], 'offsite_conversion.fb_pixel_purchase');  }
    
                if(isset($fb_response['data'][0]['actions'])){ $lead = LeadGen($fb_response['data'][0]['actions'], 'lead'); }
    
                $query = "SELECT * FROM acc_insights where bud_rem_id=".$sqlROW['tbl_id']." AND mon_yr='".date('m-Y')."' AND fb_acc='".$fbId."'";
                $result = mysqli_query($conn, $query);
                
                if(mysqli_num_rows($result)==0){
                    $query = "INSERT INTO acc_insights (bud_rem_id, mon_yr, fb_acc, spend, lead, pur, pur_val, created, updated)  VALUES ('".$sqlROW['tbl_id']."', '".date('m-Y')."', '".$fbId."', '".$spend."', '".$lead."', '".$purchase."', '".$purchase_val."', now(), now())";
                    $result = mysqli_query($conn, $query);
                } else {
                    $query = "UPDATE acc_insights SET spend = '".$spend."', lead = '".$lead."', pur = '".$purchase."', pur_val = '".$purchase_val."', updated = NOW() WHERE bud_rem_id = ".$sqlROW['tbl_id']." AND mon_yr = '".date('m-Y')."' AND fb_acc = '".$fbId."'"; 
                    $result = mysqli_query($conn, $query);
                }
            }
            
    }
    //exit;
}
