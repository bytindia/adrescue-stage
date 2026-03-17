<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

include $dirPath.'db.php';
include $dirPath.'functions-report.php'; 
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
function getDatesFromRange($start, $end, $format = 'Y-m-d') {
    $array = array();
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($end);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

    foreach($period as $date) { 
        $array[] = $date->format($format); 
    }

    return $array;
}
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

$d = new DateTime('first day of this month');
$d2 = new DateTime('today');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';
$filter_q = '';

//RLD MERLION
$fbIds = array(1622025181942867);
$fbIds2 = array(271047031562298);
$gIds = array(3358840745);

$fb_report = $fb_report2 = array();
/*foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}*/
foreach($fbIds2 as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $requests = curl_get_file_contents($request_url);
    $fb_response2 = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response2['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report2[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}

include $dirPath.'google-account-rep.php';
$g_report = array();
foreach($gIds as $key => $gaId) {
    $gStats[] = $gaId; 
    $gDates[$gaId] = $SatrtDate; 
    $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes');
    foreach($getAccRep as $g_k => $g_v){
       
        $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
    }
    
}
//d($fb_report); d($g_report);



$dateRange = getDatesFromRange(date("Y-m-d", strtotime($SatrtDate)),date("Y-m-d", strtotime($EndDate)),'Y-m-d');
//d($dateRange);
//$rowData[] = array('Date', 'BYT FB Spend', 'BYT FB Leads', 'BYT FB CPL', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowData[] = array('Date', 'BYT FB Spend', 'BYT FB Leads', 'BYT FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    /*if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }*/
    if(isset($fb_report2[$dv]) && count($fb_report2[$dv])>0){
        $fb_spend2 = array_sum(array_column($fb_report2[$dv],'spend'));
        $fb_lead2 = array_sum(array_column($fb_report2[$dv],'lead'));
        if($fb_spend2>0 && $fb_lead2>0) { $fb_cpl2 = @($fb_spend2/$fb_lead2); }
    }
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    //$rowData[] = array($dv, round($fb_spend2), round($fb_lead2), round($fb_cpl2), round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowData[] = array($dv, round($fb_spend2), round($fb_lead2), round($fb_cpl2), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;
}
//d($rowData); 

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/update-row-bulk.php';
$uId=2; 
$tbl_id=2;  

/*

//Vibrant

$spreadsheetId='1_-pg6fGYpV-ESUiEpbb9fq3uI-yWodw0d2kM0HnJ8KU'; 
$sheetTab= date('M-Y'); //'Dec-2024';

update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'C', $rowNo=2);



echo '<br/>Vibrant<br/>';
$fbIds = array(615262527033827);
$gIds = array(4248208994);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
foreach($gIds as $key => $gaId) {
    $gStats[] = $gaId; 
    $gDates[$gaId] = $SatrtDate; 
    $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes');
    foreach($getAccRep as $g_k => $g_v){
       
        $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
    }
    
}

$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;
}

$spreadsheetId='1ff3JNJfbb4_7wn34kuHQvWZgV0A7-dQsJfBJg2MyiHU'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);
*/

//Raunaq
/*
$fbIds = array(597312711090623,6473800839396603);
$gIds = array(3546904591);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
if(count($gIds)>0){
    foreach($gIds as $key => $gaId) {
        $gStats[] = $gaId; 
        $gDates[$gaId] = $SatrtDate; 
        $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes');
        foreach($getAccRep as $g_k => $g_v){
           
            $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
        }
        
    }
}


$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
$tot_fb_spend = $tot_fb_lead = $tot_fb_cpl = $tot_g_spend = $tot_g_lead = $tot_g_cpl = $grand_spend = $grand_lead = $grand_cpl = 0;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;

    $tot_fb_spend += $fb_spend;
    $tot_fb_lead += $fb_lead;    
    $tot_g_spend += $g_spend;
    $tot_g_lead += $g_lead;

    $grand_spend += $tot_spend;
    $grand_lead += $tot_lead;
}

if($tot_fb_spend>0 && $tot_fb_lead>0) { $tot_fb_cpl = round($tot_fb_spend/$tot_fb_lead); }
if($tot_g_spend>0 && $tot_g_lead>0) { $tot_g_cpl = round($tot_g_spend/$tot_g_lead); }
if($grand_spend>0 && $grand_lead>0) { $grand_cpl = round($grand_spend/$grand_lead); }

$rowData[] =array('','','','','','','','','','');
$rowData[] = array('Total', round($tot_fb_spend), round($tot_fb_lead), round($tot_fb_spend), round($tot_g_spend), round($tot_g_lead), round($tot_g_cpl), round($grand_spend), round($grand_lead), round($grand_cpl));

$spreadsheetId='1qQXlJkbBJFCDw2kQQmGknjdV4yStV7cqEUhkpLg7ix8'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);
*/

//RLD Altima
echo '<br/>RLD Altima<br/>';
$fbIds = array(1866059083840423);
$gIds = array(8727678691);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
foreach($gIds as $key => $gaId) {
    $gStats[] = $gaId; 
    $gDates[$gaId] = $SatrtDate; 
    $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes');
    foreach($getAccRep as $g_k => $g_v){
       
        $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
    }
    
}

$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;
}

$spreadsheetId='1v7xefqrZahbghxGK1i1BnaZfNfJiUVnN6f_PWsVEx9I'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);



echo '<br/>iYRA City<br/>';
$fbIds = array(6473800839396603);
$gIds = array(3950071509);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    //$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions,campaign_name&time_increment=1&limit=750&filtering='.urlencode('[{"field":"campaign.name","operator":"CONTAIN","value":"city"}]').'&access_token='.$access_token.'&'.$dtRange;

    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
foreach($gIds as $key => $gaId) {
    $gStats[] = $gaId; 
    $gDates[$gaId] = $SatrtDate; 
    $filter_q = " AND campaign.name LIKE '%city%'";
    $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes', $filter_q);
    foreach($getAccRep as $g_k => $g_v){
       
        $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
    }
    
}

$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;
}

$spreadsheetId='1UQLsav-s_YzsCr7DH8MKNN3OLt-ldk3mNyECQ3MZvpI'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);



echo '<br/>iYRA Amara<br/>';
$fbIds = array(6473800839396603);
$gIds = array(3950071509);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    //$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions,campaign_name&time_increment=1&limit=750&filtering='.urlencode('[{"field":"campaign.name","operator":"CONTAIN","value":"amara"}]').'&access_token='.$access_token.'&'.$dtRange;

    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
foreach($gIds as $key => $gaId) {
    $gStats[] = $gaId; 
    $gDates[$gaId] = $SatrtDate; 
    $filter_q = " AND campaign.name LIKE '%amara%'";
    $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes', $filter_q);
    foreach($getAccRep as $g_k => $g_v){
       
        $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
    }
    
}

$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;
}

$spreadsheetId='1cHCARB4cmwmQiEt5WbuRivDAwDovOlNsvslbY0o9BOA'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);


echo '<br/>iYRA Spire<br/>';
$fbIds = array(6473800839396603);
$gIds = array(3950071509);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    //$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions,campaign_name&time_increment=1&limit=750&filtering='.urlencode('[{"field":"campaign.name","operator":"CONTAIN","value":"spire"}]').'&access_token='.$access_token.'&'.$dtRange;

    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
foreach($gIds as $key => $gaId) {
    $gStats[] = $gaId; 
    $gDates[$gaId] = $SatrtDate; 
    $filter_q = " AND campaign.name LIKE '%spire%'";
    $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes', $filter_q);
    foreach($getAccRep as $g_k => $g_v){
       
        $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
    }
    
}

$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;
}

$spreadsheetId='1Gl0_ge40u9GUAEnQWKXkUmjLSCiiIR4fRnSZlB49YRw'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);

/*
//EP
echo '<br/>EP<br/>';
$fbIds = array(605103317327861);
$gIds = array(5031681195);
$rowData = array();
$fb_report = $fb_report2 = array();
foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions&time_increment=1&limit=750&access_token='.$access_token.'&'.$dtRange.'';
    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);

    //d($fb_response);

    foreach($fb_response['data'] as $fb_k => $fb_v){
        $fblead =  0;
        //d($fb_v); exit;
        if(isset($fb_v['actions'])){
            $fblead = LeadGen($fb_v['actions'], 'lead');
        }
        $fb_report[$fb_v['date_start']][] = array('spend'=>$fb_v['spend'], 'lead'=>$fblead);
    }
}


//include $dirPath.'google-account-rep.php';
$g_report = array();
if(count($gIds)>0){
    foreach($gIds as $key => $gaId) {
        $gStats[] = $gaId; 
        $gDates[$gaId] = $SatrtDate; 
        $getAccRep = GetAccountDailyReport::main($_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)), $daily='yes');
        foreach($getAccRep as $g_k => $g_v){
           
            $g_report[$g_v['date']][] = array('spend'=> round($g_v['cost']), 'lead'=>round($g_v['conv']));
        }
        
    }
}


$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
$tot_fb_spend = $tot_fb_lead = $tot_fb_cpl = $tot_g_spend = $tot_g_lead = $tot_g_cpl = $grand_spend = $grand_lead = $grand_cpl = 0;
foreach($dateRange as $dk => $dv){
    $fb_spend = $fb_lead = $fb_cpl = $g_spend = $g_lead = $g_cpl = $tot_cpl = 0;
    $fb_spend2 = $fb_lead2 = $fb_cpl2 = 0;
    if(isset($fb_report[$dv]) && count($fb_report[$dv])>0){
        $fb_spend = array_sum(array_column($fb_report[$dv],'spend'));
        $fb_lead = array_sum(array_column($fb_report[$dv],'lead'));
        if($fb_spend>0 && $fb_lead>0) { $fb_cpl = @($fb_spend/$fb_lead); }
    }
    
    if(isset($g_report[$dv]) && count($g_report[$dv])>0){
        $g_spend = array_sum(array_column($g_report[$dv],'spend'));
        $g_lead = array_sum(array_column($g_report[$dv],'lead'));
        if($g_spend>0 && $g_lead>0) { $g_cpl = @($g_spend/$g_lead); }
    }
    $tot_spend = $fb_spend + $fb_spend2 + $g_spend;
    $tot_lead = $fb_lead + $fb_lead2 + $g_lead;
    if($tot_spend>0 && $tot_lead>0) { $tot_cpl = @($tot_spend/$tot_lead); }
    $rowData[] = array($dv, round($fb_spend), round($fb_lead), round($fb_cpl), round($g_spend), round($g_lead), round($g_cpl), round($tot_spend), round($tot_lead), round($tot_cpl));
    $rowNo++;

    $tot_fb_spend += $fb_spend;
    $tot_fb_lead += $fb_lead;    
    $tot_g_spend += $g_spend;
    $tot_g_lead += $g_lead;

    $grand_spend += $tot_spend;
    $grand_lead += $tot_lead;
}

if($tot_fb_spend>0 && $tot_fb_lead>0) { $tot_fb_cpl = round($tot_fb_spend/$tot_fb_lead); }
if($tot_g_spend>0 && $tot_g_lead>0) { $tot_g_cpl = round($tot_g_spend/$tot_g_lead); }
if($grand_spend>0 && $grand_lead>0) { $grand_cpl = round($grand_spend/$grand_lead); }

$rowData[] =array('','','','','','','','','','');
$rowData[] = array('Total', round($tot_fb_spend), round($tot_fb_lead), round($tot_fb_spend), round($tot_g_spend), round($tot_g_lead), round($tot_g_cpl), round($grand_spend), round($grand_lead), round($grand_cpl));

$spreadsheetId='1uBy1KbUJk7Ins3rH6VnftTa02ERnyGuNYe2UQqUlb0M'; 
$sheetTab= date('M-Y'); //'Dec-2024';
//d($rowData);
update_row_bulk($uId, $tbl_id, $rowData, $spreadsheetId, $sheetTab, 'A', $rowNo=1);
*/
echo 'success';