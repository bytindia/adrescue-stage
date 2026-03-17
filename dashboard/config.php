<?php


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
//echo "SELECT tbl_id, client_name,fb_id,g_id,in_id,ta_id  FROM dashboard_accounts WHERE tbl_id='".$_SESSION['tbl_id']."'";
$cirRes = mysqli_query($conn, "SELECT tbl_id, client_name,fb_id,g_id,in_id,ta_id  FROM dashboard_accounts WHERE tbl_id='".$_SESSION['tbl_id']."'");
$row = mysqli_fetch_assoc($cirRes);
$_SESSION['tbl_id'] = $row['tbl_id'];
$fb_acc_ids = array_filter(explode(',',$row['fb_id']));
$g_acc_ids = array_filter(explode(',',$row['g_id']));
$ta_acc_ids = array_filter(explode(',',$row['ta_id']));
$in_acc_ids = array_filter(explode(',',$row['in_id']));
$_SESSION['client_name'] = $row['client_name'];

//$in_acc_ids = array_filter($in_acc_ids);

//$fb_acc_ids = array(732236261026706, 229650465874267, 880541165925308, 624107499015180,665661671485180);
$fb_acc_name = array('RWD Account 1', 'RWD Account 2 ( New )', 'RWD Grand Corridor', 'RWD Spotlight', 'RWD Ibis County');


//
//$g_acc_ids = array(9516894033);
$g_acc_name = array('RWD');


//$ta_acc_ids = array();
$ta_acc_name = array();

function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
$obj_arr = array(
    'POST_ENGAGEMENT' => 'page_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'leadgen_grouped'
    );

    $post = array(
        "client_id"           => "d0ed8eb186d3422c83defbb56a0178cc",
        "client_secret"       => "3f4b80205a9c45f3bcfd8ea3dd164c00",
        "grant_type"          => "client_credentials",
    );
    
    $post2 = array(
        "client_id"           => "ab4c07a63fdf4a89bff0daed8a9be426",
        "client_secret"       => "8a57d7def8a543d0944c86f1f729c012",
        "grant_type"          => "client_credentials",
    );
    function retTok($t) {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_COOKIESESSION, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "App Client" );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ));
    
        curl_setopt($ch, CURLOPT_URL,"https://backstage.taboola.com/backstage/oauth/token");
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($t));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 0);
    
        $result=curl_exec ($ch);
    
        $info = curl_getinfo($ch);
        $response = json_decode($result, true);
    
    
    
        if ($info['http_code'] == 200) {
            return $taboola_tok = $response['access_token'];
        } else {
            return '';
        }
    }
    
 /*
if(is_array( $g_acc_ids ) && count($g_acc_ids)>0) {
        $accIds = $g_acc_ids;
        $accNames = 1;
        include '../download-gsquare.php';
        foreach($g_acc_ids as $k => $gR)			
        {
            if($gR!='') {
                $gSpent = array();
                $gaIds = explode(',',$gR); 
                for($z=1;$z<4;$z++) {
                    foreach($gaIds as $key => $gaId) {
                        $cost = $conv = $cpl = 0;
                        $rows = array();
                        
                        $rows[$gaId] = file(''.$server_path.'ads-gsquare/account_'.$gaId.'_'.$z.'.csv');
                        $last_row[$gaId] = array_pop($rows[$gaId]);
                        $arr_goo[$gaId] = str_getcsv($last_row[$gaId]);	
    
                        if($arr_goo[$gaId][5]!=0) { $cost = $arr_goo[$gaId][5]/1000000; }	
                        if($arr_goo[$gaId][9]!=0) { $conv = $arr_goo[$gaId][9]; }	
                        if($arr_goo[$gaId][8]!=0) { $cpl = $arr_goo[$gaId][8]/1000000; }	
    
                       // $gSpent[] = round($cost);		
                        $resData_g[$gaId][$z] = array($cost, $conv, $cpl);
                        //d($arr_goo);
                    }
                }
                //echo  "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'"; 
                //mysqli_query($conn, "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'") or die(mysqli_error());		
            }
        }
    } */


    $fbAccN = $gAccN = $inAccN = $taAccN = array();
	
    if($pg=='all' || $pg=='facebook'){
        $sqlRev1=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='2' order by name asc");
        while($sqlROW1=mysqli_fetch_array($sqlRev1)) { $fbAccN[$sqlROW1["account_id"]] = $sqlROW1["name"]; }
    }
    if($pg=='all' || $pg=='google'){
        $sqlRev2=mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='2' order by name asc");
        while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2["account_id"]] = $sqlROW2["name"]; }
    }
    if($pg=='all' || $pg=='linkedin'){
        $sqlRev3=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_in WHERE uid='2' order by name asc");
        while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $inAccN[$sqlROW3["account_id"]] = $sqlROW3["name"]; }
    }
    if($pg=='all' || $pg=='taboola'){
        $sqlRev4=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_ta WHERE uid='2' order by name asc");
        while($sqlROW4=mysqli_fetch_array($sqlRev4)) { $taAccN[$sqlROW4["account_id"]] = $sqlROW4["name"]; }
    }
    
    
    
    
    function LinkedInAPI($in_id,$linkedIn,$dt1,$dt2) {
        $spend = $conv = 0;
        $accQry ='accounts[0]=urn:li:sponsoredAccount:'.$in_id.'&';
        //if(isset($in_stDt[$key]) && $in_stDt[$key]=='') { $in_stDt[$key] = date("Y/m/d",strtotime("-10 year")); }
        $stDt = "dateRange.start.day=".date('d',strtotime($dt1))."&dateRange.start.month=".date('m',strtotime($dt1))."&dateRange.start.year=".date('Y',strtotime($dt1))."&";
        $enDt = "dateRange.end.day=".date('d',strtotime($dt2))."&dateRange.end.month=".date('m',strtotime($dt2))."&dateRange.end.year=".date('Y',strtotime($dt2));
                    
        $val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=costInLocalCurrency&'.$stDt.''.$enDt);
        if(isset($val['elements'][0]['costInLocalCurrency'])) {
            $spend = round($val['elements'][0]['costInLocalCurrency']);
        }
        return array('cost'=>round($spend),'conversions'=>round($conv));
    }
    
    function TaboolaAPI($ta_id,$taboola_tok,$dt1,$dt2) {
        $spend = $conv = 0;
        $ta_url = 'https://backstage.taboola.com/backstage/api/1.0/'.$ta_id.'/reports/campaign-summary/dimensions/month?access_token='.$taboola_tok.'&start_date='.date('Y-m-d',strtotime($dt1)).'&end_date='.date('Y-m-d',strtotime($dt2)).'';
        $ta_req = file_get_contents_curl($ta_url);
        $ta_res = json_decode($ta_req,true);
        //d($ta_res); 
        if(isset($ta_res['results'])) {
            $spend = array_sum(array_column($ta_res['results'],'spent'));
            $conv = array_sum(array_column($ta_res['results'],'conversions_value'));
           // $taSpent[] = round($spend);
        }
        return array('cost'=>round($spend),'conversions'=>round($conv));
    }