<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
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
function contains_proj($str) {
        $str = strtolower(trim($str));
        global $name_contain;
        
        foreach($name_contain as $k => $a) {
            // Support either a single string or an array of keywords for a project key
            if (is_array($a)) {
                foreach($a as $needle) {
                    $needle = strtolower(trim($needle));
                    if ($needle !== '' && stripos($str, $needle) !== false) return $k;
                }
            } else {
                $a = strtolower(trim($a));
                if ($a !== '' && stripos($str, $a) !== false) return $k;
            }
        }
        return '';
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
if (date('d') == 1) {  $d = new DateTime('first day of last month'); }
$d2 = new DateTime('yesterday');
$SatrtDate = $d->format('Y-m-d').' 00:00:00';
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well

$dateRange = getDatesFromRange(date("Y-m-d", strtotime($SatrtDate)),date("Y-m-d", strtotime($EndDate)),'Y-m-d');

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/update-row-bulk.php';
$uId=2; 
$tbl_id=2;

//MH - Aarya

$name_contain = array(
	'aarya' => array('aarya'),
	'arto' => array('arto')
);

$fbIds = array(349193802406853,2324523194685701,1037282367255891);
$gIds = array(5600153376, 1449416941);
$rowData = array();
$fb_report = $fb_report2 = array();
// Aggregated results by project => date => metrics
$fb_agg = array();
$g_agg = array();
$fb = array();
$g = array();

// Initialize project arrays with all dates to ensure empty arrays for missing dates
foreach($name_contain as $projKey => $keywords) {
    $fb_agg[$projKey] = array();
    $g_agg[$projKey] = array();
    foreach($dateRange as $date) {
        $fb_agg[$projKey][$date] = array('spend'=>0.0,'leads'=>0.0);
        $g_agg[$projKey][$date] = array('spend'=>0.0,'leads'=>0.0);
    }
}

foreach($fbIds as $key => $fbId) {						
    //echo $i . "<br />";
    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=campaign&fields=campaign_name,spend,actions&time_increment=1&access_token='.$access_token.'&'.$dtRange.'&limit=1750';
    $requests = curl_get_file_contents($request_url);
    $fb_response = json_decode($requests,true);
    //d($fb_response); exit;
    if (isset($fb_response['data']) && is_array($fb_response['data'])) {
        foreach($fb_response['data'] as $fb_k => $fb_v){
            $fblead = 0;
            if(isset($fb_v['actions'])){
                $fblead = LeadGen($fb_v['actions'], 'lead');
            }
            $dateKey = isset($fb_v['date_start']) ? $fb_v['date_start'] : (isset($fb_v['date_stop']) ? $fb_v['date_stop'] : '');
            $campName = isset($fb_v['campaign_name']) ? $fb_v['campaign_name'] : '';
            $projKey = contains_proj($campName);
            if ($projKey === '' || $dateKey === '') continue; // skip non-target campaigns

            $spendVal = floatval($fb_v['spend']);
            // Sum across multiple ad accounts
            $fb_agg[$projKey][$dateKey]['spend'] += $spendVal;
            $fb_agg[$projKey][$dateKey]['leads'] += floatval($fblead);

            // Maintain existing date-only aggregation if needed elsewhere
            if (!isset($fb_report[$dateKey])) $fb_report[$dateKey] = array();
            $fb_report[$dateKey][] = array('spend'=>$spendVal, 'lead'=>floatval($fblead));
        }
    }
}


include $dirPath.'google-ads-campaigns-mh.php';
$g_report = array();
if(count($gIds)>0){
    foreach($gIds as $key => $gaId) {
        $gStats[] = $gaId; 
        $gDates[$gaId] = $SatrtDate; 
        $getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)));

        if (is_array($getAccRep)) {
            foreach($getAccRep as $g_k => $g_v){
                // Support both associative and numeric indexed rows
                $campName = '';
                $dateKey = '';
                $spendVal = 0.0;
                $leadsVal = 0.0;

                if (is_array($g_v)) {
                    if (isset($g_v['campaign_name']) || isset($g_v['date']) || isset($g_v['cost']) || isset($g_v['conv'])) {
                        $campName = isset($g_v['campaign_name']) ? $g_v['campaign_name'] : '';
                        $dateKey = isset($g_v['date']) ? $g_v['date'] : '';
                        $spendVal = isset($g_v['cost']) ? floatval($g_v['cost']) : 0.0;
                        $leadsVal = isset($g_v['conv']) ? floatval($g_v['conv']) : 0.0;
                    } else {
                        // Numeric indices based on provided sample
                        $campName = isset($g_v[1]) ? $g_v[1] : '';
                        $spendVal = isset($g_v[5]) ? floatval($g_v[5]) : 0.0;
                        $leadsVal = isset($g_v[10]) ? floatval($g_v[10]) : 0.0;
                        $dateKey = isset($g_v[13]) ? $g_v[13] : '';
                    }
                }

                $projKey = contains_proj($campName);
                if ($projKey === '' || $dateKey === '') continue;

                // Sum across multiple ad accounts
                $g_agg[$projKey][$dateKey]['spend'] += $spendVal;
                $g_agg[$projKey][$dateKey]['leads'] += $leadsVal;

                // Maintain existing date-only aggregation if needed elsewhere
                if (!isset($g_report[$dateKey])) $g_report[$dateKey] = array();
                $g_report[$dateKey][] = array('spend'=> round($spendVal), 'lead'=>round($leadsVal));
            }
        }
        
    }
}

// Build output arrays fb[project][date] = array(spend, leads, cpl)
foreach ($fb_agg as $projKey => $byDate) {
	$fb[$projKey] = array();
	foreach ($byDate as $dateKey => $vals) {
		$sp = floatval($vals['spend']);
		$ld = floatval($vals['leads']);
		$cpl = ($sp > 0 && $ld > 0) ? ($sp / $ld) : 0.0;
		$fb[$projKey][] = array(round($sp,2), round($ld,2), round($cpl,2));
	}
}

// Build output arrays g[project][date] = array(spend, leads, cpl)
foreach ($g_agg as $projKey => $byDate) {
	$g[$projKey] = array();
	foreach ($byDate as $dateKey => $vals) {
		$sp = floatval($vals['spend']);
		$ld = floatval($vals['leads']);
		$cpl = ($sp > 0 && $ld > 0) ? ($sp / $ld) : 0.0;
		$g[$projKey][] = array(round($sp,2), round($ld,2), round($cpl,2));
	}
}

$rowData[] = array('Date', 'FB Spend', 'FB Leads', 'FB CPL', 'G Spend', 'G Leads', 'G CPL' , 'Tot. Spend', 'Tot. Leads', 'Tot. CPL'); 
$rowNo = 1;
$tot_fb_spend = $tot_fb_lead = $tot_fb_cpl = $tot_g_spend = $tot_g_lead = $tot_g_cpl = $grand_spend = $grand_lead = $grand_cpl = 0;
$rowData_f = $rowData_g = array();
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
    $rowData_f[] = array(round($fb_spend), round($fb_lead), round($fb_cpl));
    
    $rowData_g[] = array(round($g_spend), round($g_lead), round($g_cpl));
    $rowNo++;

    $tot_fb_spend += $fb_spend;
    $tot_fb_lead += $fb_lead;    
    $tot_g_spend += $g_spend;
    $tot_g_lead += $g_lead;

    $grand_spend += $tot_spend;
    $grand_lead += $tot_lead;
}

$spreadsheetId='1DbfaJ13QjLf1_EYMjFpkOlcMgNDT9i3jJR6Frm-mlk8'; 
//$sheetTab= 'Meta - Aarya'; //'Dec-2024';
//d($rowData);
$mon = strtoupper(date("M"));
update_row_bulk($uId, $tbl_id, $fb['aarya'], $spreadsheetId, 'Meta-'.$mon, 'C', $rowNo=5);
update_row_bulk($uId, $tbl_id, $g['aarya'], $spreadsheetId, 'Google-'.$mon, 'C', $rowNo=5);

update_row_bulk($uId, $tbl_id, $fb['arto'], $spreadsheetId, 'Meta-'.$mon, 'R', $rowNo=5);
update_row_bulk($uId, $tbl_id, $g['arto'], $spreadsheetId, 'Google-'.$mon, 'R', $rowNo=5);

// Final requested output structures
//d($fb); d($g);