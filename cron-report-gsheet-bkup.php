<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include 'db.php';
include 'functions-report.php'; 

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

//include 'gsquare-taboola.php';

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

$client_id = '819hf4iznbxt3r';
$client_secret = getenv('LINKEDIN_CLIENT_SECRET');
$userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='2'");	
$getRw2 = mysqli_fetch_assoc($userRes2);
$_SESSION['in_id'] = $getRw2['in_id'];
$_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
					
require_once $server_path .'vendor-linkedin/autoload.php';
$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";				
$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
  $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
}
$d = new DateTime('first day of this month');
$d2 = new DateTime('today');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';
//exit;

$taboola_tok = '';
//include 'taboola-config.php';
include 'google-ads-gsheet.php';

$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

if($access_token!='') {  
	if (!$tok_req = curl_get_file_contents($tok_url)) { 
		  $pg = 'cron-fb-report';      
		  include 'email/mail-error.php';
		  exit;
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
//exit;

function daterange_rep($rep_data, $rangeStart, $rangeEnd, $key){
	$filtered_events = array_filter($rep_data, function($var) use ($rangeStart, $rangeEnd, $key) {  
		$evtime = strtotime($var[$key]);  
		return $evtime <= $rangeEnd && $evtime >= $rangeStart;  
	});
	return $filtered_events; 
}

$week_dates = array(
	1 => array('01','07'),
	2 => array('08','14'),
	3 => array('15','21'),
	4 => array('22','31')
);

$gAccIds = $gStats =array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 
$cur_mon = date('m');
$cur_yr = date('Y');
$cur_mon_yr = $cur_mon.', '.$cur_yr;
$sqlRev=mysqli_query($conn, "SELECT tbl_id, client, projects, budget, fb_acc, g_acc, month, del FROM report_gsheet as c WHERE tbl_id='1' AND del=0");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;		
$repId = $repMon = 	'';
$total_spent = 0; 				
$projects_list = array();
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	$repId = $sqlROW['tbl_id'];
	$clientName = $sqlROW['client'];
	
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = $acc_bal = $fblead = $fbcpl = $fblead2 = $fbcpl2 = array();
	
	$projects = unserialize($sqlROW['projects']);
	$budget = unserialize($sqlROW['budget']);
	$month = unserialize($sqlROW['month']);
	//d($projects); exit;
	
	
	//Facebook
	if($sqlROW['fb_acc']!='') 
	{
		//echo $sqlROW['fb_id']; exit;
		
		$fbIds = explode(',',$sqlROW['fb_acc'] ?? '');		//$fbReceived = explode(',',$sqlROW['fb_received']);	
		
		foreach($fbIds as $key => $fbId) {						
			//echo $i . "<br />";
            foreach($projects as $pk => $pv) {		
				//d($month);
				$mon_spl = explode(',',$month[$pk] ?? '');
				$repMon = $month[$pk];
				$mon = $mon_spl[0];
				$yr = trim($mon_spl[1]);
				$SatrtDate = $yr.'-'.$mon.'-01';
				$EndDate = date($yr.'-'.$mon.'-t');
                $proj_names =  $projects_list = explode(',',$pv ?? '');
                //d($proj_names); d($mon); //exit;
				foreach($proj_names as $pn_k => $pn_v) {		
					$pn_v = trim($pn_v);
					$dtRange = 'time_range[since]='.$SatrtDate.'&time_range[until]='.$EndDate.''; //exit;
					//act_6473800839396603/insights?level=campaign&fields=campaign_id,campaign_name,spend,impressions,reach,cpc,clicks,actions,cpm,ctr&filtering=[{field:"campaign.name",operator:"CONTAIN",value:'Ameya'},{field: "action_type",operator:"IN", value: ['lead']}]&time_range[since]=2024-05-01&time_range[until]=2024-05-14&time_increment=1

					$req_url_acc = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=campaign&fields=campaign_id,campaign_name,spend,impressions,reach,cpc,clicks,cpm,ctr,actions&filtering=[{field:'campaign.name',operator:'CONTAIN',value:'".$pn_v."'}]&access_token=".$access_token."&".$dtRange."&time_increment=1&limit=750"; //exit;
					$res_acc = curl_get_file_contents($req_url_acc);
					$acc_rep[$pn_k] = json_decode($res_acc,true); 

					if(isset($acc_rep[$pn_k]['data']) && count($acc_rep[$pn_k]['data'])>0){
						foreach($acc_rep[$pn_k]['data'] as $rep_k => $rep_v){
							$acc_rep[$pn_k]['data'][$rep_k]['lead'] = LeadGen($rep_v['actions'], 'lead');
							unset($acc_rep[$pn_k]['data'][$rep_k]['actions']);
						}
					}
					//d($acc_rep); exit;
					$acc_rep_dt[$pn_k] = array($SatrtDate, $EndDate); 

					$tot_spend_acc[$pn_k] = array_sum(array_column($acc_rep[$pn_k]['data'], 'spend')); 
					$tot_leads_acc[$pn_k] = array_sum(array_column($acc_rep[$pn_k]['data'], 'lead')); 

					foreach($week_dates as $w_k => $w_v) {
						$wk_rep[$pn_k][$w_k] = daterange_rep($acc_rep[$pn_k]['data'],  strtotime($yr.'-'.$mon.'-'.$w_v[0]), strtotime($yr.'-'.$mon.'-'.$w_v[1]), 'date_start');
						foreach($wk_rep[$pn_k][$w_k] as $w_k1 => $w_v2){
							$wk_rep_cmp[$pn_k][$w_k][$w_v2['campaign_id']][] = $w_v2;
						}
					}
				}
				
            }
		}		
	}
	//d($wk_rep); exit; //d($tot_leads_acc); d($acc_rep_dt); exit;

	
	
	//Google
	if($sqlROW['g_acc']!='') 
	{
		//echo $sqlROW['fb_id']; exit;
		
		$gIds = explode(',',$sqlROW['g_acc'] ?? '');		//$fbReceived = explode(',',$sqlROW['fb_received']);	
		
		foreach($gIds as $key => $gId) {						
			//echo $i . "<br />";
            foreach($projects as $pk => $pv) {		
				$mon_spl = explode(',',$month[$pk] ?? '');
				$mon = $mon_spl[0];
				$yr = trim($mon_spl[1]);
				$SatrtDate = $yr.'-'.$mon.'-01';
				$EndDate = date($yr.'-'.$mon.'-t');
                $proj_names =  $projects_list  = explode(',',$pv ?? '');
                
				foreach($proj_names as $pn_k => $pn_v) {		
					$pn_v = trim($pn_v);
					$acc_rep_g[$pn_k] = GetCampaigns::main($conn, $g_refresh_token, $g_mcc,$gId,$SatrtDate, $EndDate,$pn_v);
					//d($acc_rep_g); exit;

					//d($acc_rep_g); exit;
					$acc_rep_dt_g[$pn_k] = array($SatrtDate, $EndDate); 

					$tot_spend_acc_g[$pn_k] = array_sum(array_column($acc_rep_g[$pn_k], 'cost')); 
					$tot_leads_acc_g[$pn_k] = array_sum(array_column($acc_rep_g[$pn_k], 'conversions')); 

					foreach($week_dates as $w_k => $w_v) {
						$wk_rep_g[$pn_k][$w_k] = daterange_rep($acc_rep_g[$pn_k],  strtotime($yr.'-'.$mon.'-'.$w_v[0]), strtotime($yr.'-'.$mon.'-'.$w_v[1]), 'date');
						foreach($wk_rep_g[$pn_k][$w_k] as $w_k1 => $w_v2){
							$wk_rep_cmp_g[$pn_k][$w_k][$w_v2['id']][] = $w_v2;
						}
					}
					//d($wk_rep_cmp_g); exit;
				}
            }
			//d($wk_rep_cmp_g); exit;
			
		}		
	} 
	
	
	
} 

//exit;


// Google sheet API
require_once 'google-sheets-api/vendor/autoload.php';
require_once 'google-sheets-api/class-db.php';
require_once 'google-sheets-api/config.php';
require_once 'google-sheets-api/service-inc.php';

include 'google-sheets-api/update-range.php';
include 'google-sheets-api/update-row.php';
include 'google-sheets-api/insert-row.php';
include 'google-sheets-api/merge-row.php';
include 'google-sheets-api/create-sheet-tab.php';


$uId=2; 
$tbl_id=2;  
$spreadsheetId = '1bV3PYwo5p18jdHKUe6Z42t_PMDDMbipLrnjt5ia8RYk';
$leadV = [['Summary ( Meta )']];
$tab = 0;

$tabIds = array(
	0 => 0,
	1 => 444588437,
	2 => 1120228611,
	3 => 149971141,
	4 => 1208382820,
	5 => 1207846138,
);
$tblIds_proj_fb =array(
	1 => 1208382820,
	2 => 1207846138
);
$tblIds_proj_g =array(
	1 => 404775442,
	2 => 756640488
);

/*
//Overview
$sheetTab = 'Overview';
$sheetId = $tabIds[0];
merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow=0, $enRow=0, $startCol=0, $endCol=4, $service); //($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, $stRow, $enRow, $startCol, $endCol) 

$leadV = [['Summary ( Meta )']];
update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=1, $service);
$leadV = [['Project', 'Spends', 'Leads', 'CPL']];
update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=2, $service);
$grand_spend_f = $grand_leads_f = 0;
$row_inc = 3; $j =1;
foreach($projects_list as $pk => $pv) {
	$acc_cpl = 0; 
	if($tot_spend_acc[$pk]>0 && $tot_leads_acc[$pk]) { $acc_cpl = @round($tot_spend_acc[$pk]/$tot_leads_acc[$pk], 2); }
	$leadV = [[trim($pv), round($tot_spend_acc[$pk]), round($tot_leads_acc[$pk]), $acc_cpl]];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $row_inc, $service);
	
	$grand_spend_f += $tot_spend_acc[$pk]; 
	$grand_leads_f += $tot_leads_acc[$pk]; 
	
	if(count($projects_list)==$j) {
		$grand_cpl = 0;
		if($grand_spend_f>0 && $grand_leads_f) { $grand_cpl = @round($grand_spend_f/$grand_leads_f, 2); }
		$leadV = [['Total', round($grand_spend_f), round($grand_leads_f), $grand_cpl]];
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $row_inc+1, $service);
	}

	$row_inc++; $j++;
}

merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow=0, $enRow=0, $startCol=5, $endCol=9, $service);
$leadV = [['Summary ( Google )']];
update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'F', $rowNo=1, $service);
$leadV = [['Project', 'Spends', 'Leads', 'CPL']];
update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'F',$rowNo=2, $service);
$grand_spend_g = $grand_leads_g = 0;
$row_inc = 3; $j =1;
foreach($projects_list as $pk => $pv) {
	$acc_cpl = 0; 
	if($tot_spend_acc_g[$pk]>0 && $tot_leads_acc_g[$pk]) { $acc_cpl = @round($tot_spend_acc_g[$pk]/$tot_leads_acc_g[$pk], 2); }
	$leadV = [[trim($pv), round($tot_spend_acc_g[$pk]), round($tot_leads_acc_g[$pk]), $acc_cpl]];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'F', $row_inc, $service);

	$grand_spend_g += $tot_spend_acc_g[$pk]; 
	$grand_leads_g += $tot_leads_acc_g[$pk]; 
	
	if(count($projects_list)==$j) {
		$grand_cpl = 0;
		if($grand_spend_g>0 && $grand_leads_g) { $grand_cpl = @round($grand_spend_g/$grand_leads_g, 2); }
		$leadV = [['Total', round($grand_spend_g), round($grand_leads_g), $grand_cpl]];
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'F', $row_inc+1, $service);
	}

	$row_inc++; $j++;
}


//Weekly Project wise

$grand_spend_f = $grand_leads_f = 0;

foreach($projects_list as $pk => $pv) {
	$pv = trim($pv);
	$row_inc = 0; $j =1; $last_rowNo = 0;
	echo $sheetTab = trim($pv).' - FB';
	echo $sheetId = $tblIds_proj_fb[$pk+1];

	foreach($week_dates as $w_k => $w_v) {

		merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo, $enRow=0, $startCol=0, $endCol=6, $service);
		$leadV = [[$pv.' Lead Generation - ('.$week_dates[$w_k][0].' - '.$week_dates[$w_k][1].')']];
		echo ($rowNo=(($row_inc+$last_rowNo)+1)); 
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+1), $service);
		$leadV = [['Campaign', 'Reach', 'Impr.', 'Leads', 'CPL', 'Spend']];
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2), $service);
		
		$k = 1;
		$grand_spend = $grand_cpl = $grand_impr = $grand_lead = $grand_reach = 0;
		foreach($wk_rep_cmp[$pk][$w_k] as $camp_id => $camp_val){
			$camp_cpl =0;
			$camp_spend = array_sum(array_column($camp_val, 'spend')); $grand_spend +=$camp_spend;
			$camp_impr = array_sum(array_column($camp_val, 'impressions')); $grand_impr +=$camp_impr;
			$camp_lead = array_sum(array_column($camp_val, 'lead')); $grand_lead +=$camp_lead;
			$camp_reach = array_sum(array_column($camp_val, 'reach')); $grand_reach +=$camp_reach;
			$camp_name = $camp_val[0]['campaign_name'];
			if($camp_spend>0 && $camp_lead) { $camp_cpl = @round($camp_spend/$camp_lead, 2); }

			$leadV = [[$camp_name, $camp_reach, $camp_impr, $camp_lead, $camp_cpl, $camp_spend]];
			update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2+$k), $service);
			if(count($wk_rep_cmp[$pk][$w_k])==$k) { 
				$grand_cpl = 0;
				if($grand_spend>0 && $grand_lead) { $grand_cpl = @round($grand_spend/$grand_lead, 2); }

				$leadV = [['Total', round($grand_reach), round($grand_impr), round($grand_lead), $grand_cpl, round($grand_spend)]];
				update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2+$k+1), $service);
			}
			$k++;
		}
		$last_rowNo = $last_rowNo + $k + 3;
		$row_inc++;
	}
	 $j++; //exit;
}

//Google
$grand_spend_g = $grand_leads_g = 0;

foreach($projects_list as $pk => $pv) {
	$pv = trim($pv);
	$row_inc = 0; $j =1; $last_rowNo = 0;
	echo $sheetTab = trim($pv).' - Google';
	echo $sheetId = $tblIds_proj_g[$pk+1];

	foreach($week_dates as $w_k => $w_v) {

		merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo, $enRow=0, $startCol=0, $endCol=6, $service);
		$leadV = [[$pv.' Conversion - ('.$week_dates[$w_k][0].' - '.$week_dates[$w_k][1].')']];
		echo ($rowNo=(($row_inc+$last_rowNo)+1)); 
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+1), $service);
		$leadV = [['Campaign', 'Clicks', 'Impr.', 'Leads', 'CPL', 'Spend']];
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2), $service);
		
		$k = 1;
		$grand_spend = $grand_cpl = $grand_impr = $grand_lead = $grand_reach = 0;
		foreach($wk_rep_cmp_g[$pk][$w_k] as $camp_id => $camp_val){
			$camp_cpl =0;
			$camp_spend = array_sum(array_column($camp_val, 'cost')); $grand_spend +=$camp_spend;
			$camp_impr = array_sum(array_column($camp_val, 'impressions')); $grand_impr +=$camp_impr;
			$camp_lead = array_sum(array_column($camp_val, 'conversions')); $grand_lead +=$camp_lead;
			$camp_reach = array_sum(array_column($camp_val, 'clicks')); $grand_reach +=$camp_reach;
			$camp_name = $camp_val[0]['campaign'];
			if($camp_spend>0 && $camp_lead) { $camp_cpl = @round($camp_spend/$camp_lead, 2); }

			$leadV = [[$camp_name, $camp_reach, $camp_impr, $camp_lead, $camp_cpl, $camp_spend]];
			update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2+$k), $service);
			if(count($wk_rep_cmp_g[$pk][$w_k])==$k) { 
				$grand_cpl = 0;
				if($grand_spend>0 && $grand_lead) { $grand_cpl = @round($grand_spend/$grand_lead, 2); }

				$leadV = [['Total', round($grand_reach), round($grand_impr), round($grand_lead), $grand_cpl, round($grand_spend)]];
				update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2+$k+1), $service);
			}
			$k++;
		}
		$last_rowNo = $last_rowNo + $k + 3;
		$row_inc++;
	}
	 $j++; //exit;
}
*/

//Monthly - Report

$grand_spend_f = $grand_leads_f = 0;
$row_inc = 0; $j =1; $last_rowNo = 0;
foreach($projects_list as $pk => $pv) {
	$pv = trim($pv);
	
	$sheetTab = 'Monthly Report';
	$sheetId = 444588437;

	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo, $enRow=0, $startCol=0, $endCol=12, $service);
	$leadV = [[$pv.' - Summary for the month ']];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+1), $service);
	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo+1, $enRow=0, $startCol=1, $endCol=3, $service);
	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo+1, $enRow=0, $startCol=3, $endCol=5, $service);
	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo+1, $enRow=0, $startCol=5, $endCol=7, $service);
	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo+1, $enRow=0, $startCol=7, $endCol=9, $service);
	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo+1, $enRow=0, $startCol=9, $endCol=12, $service); 
	$leadV = [['','Week 1','','Week 2','','Week 3','','Week 4','','Total']];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2), $service);
	$leadV = [['','Spend','Leads','Spend','Leads','Spend','Leads','Spend','Leads','Total Spend','Total Leads','CPL']];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+3), $service);
	//exit;
	$monthlyRep_f = array();
	$grand_spend = $grand_cpl = $grand_impr = $grand_lead = $grand_reach = $acc_cpl_f = 0;
	foreach($wk_rep[$pk] as $wk => $wv){
		//d($camp_val);
		$camp_spend = array_sum(array_column($wv, 'spend')); $grand_spend +=$camp_spend;
		$camp_lead = array_sum(array_column($wv, 'lead')); $grand_lead +=$camp_lead;
		$monthlyRep_f[$wk] = array(round($camp_spend), $camp_lead);
	} 
	$all_val_F = call_user_func_array('array_merge', $monthlyRep_f);
	if($grand_spend>0 && $grand_lead) { $acc_cpl_f = @round($grand_spend/$grand_lead, 2); }
	array_unshift($all_val_F , 'FB'); array_push($all_val_F,round($grand_spend),round($grand_lead),$acc_cpl_f);
	$leadV = [$all_val_F];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+4), $service);
	

	$monthlyRep_g = array();
	$grand_spend_g = $grand_cpl_g = $grand_impr_g = $grand_lead_g = $grand_reach_g = $acc_cpl_g = 0;
	foreach($wk_rep_g[$pk] as $wk => $wv){
		//d($camp_val);
		$camp_spend_g = array_sum(array_column($wv, 'cost')); $grand_spend_g +=$camp_spend_g;
		$camp_lead_g = array_sum(array_column($wv, 'conversions')); $grand_lead_g +=$camp_lead_g;
		$monthlyRep_g[$wk] = array(round($camp_spend_g), $camp_lead_g);
	} 
	$all_val_G = call_user_func_array('array_merge', $monthlyRep_g);
	if($grand_spend_g>0 && $grand_lead_g) { $acc_cpl_g = @round($grand_spend_g/$grand_lead_g, 2); }
	array_unshift($all_val_G , 'Google'); array_push($all_val_G,round($grand_spend_g),round($grand_lead_g),$acc_cpl_g);
	$leadV = [$all_val_G];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+5), $service);
	$acc_cpl_all = 0;
	merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo+5, $enRow=0, $startCol=0, $endCol=9, $service);
	if(round($grand_spend+$grand_spend_g)>0 && round($grand_lead+$grand_lead_g)>0) { $acc_cpl_all = @round(round($grand_spend+$grand_spend_g)/round($grand_lead+$grand_lead_g), 2); }
	$leadV = [['Total','','','','','','','','',round($grand_spend+$grand_spend_g),round($grand_lead+$grand_lead_g),$acc_cpl_all]];
	update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+6), $service);

	$row_inc =+8;
	/*foreach($week_dates as $w_k => $w_v) {

		merge_rows($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow = $row_inc+$last_rowNo, $enRow=0, $startCol=0, $endCol=6, $service);
		$leadV = [[$pv.' Lead Generation - ('.$week_dates[$w_k][0].' - '.$week_dates[$w_k][1].')']];
		echo ($rowNo=(($row_inc+$last_rowNo)+1)); 
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+1), $service);
		$leadV = [['Campaign', 'Reach', 'Impr.', 'Leads', 'CPL', 'Spend']];
		update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2), $service);
		
		$k = 1;
		$grand_spend = $grand_cpl = $grand_impr = $grand_lead = $grand_reach = 0;
		foreach($wk_rep_cmp[$pk][$w_k] as $camp_id => $camp_val){
			$camp_cpl =0;
			$camp_spend = array_sum(array_column($camp_val, 'spend')); $grand_spend +=$camp_spend;
			$camp_impr = array_sum(array_column($camp_val, 'impressions')); $grand_impr +=$camp_impr;
			$camp_lead = array_sum(array_column($camp_val, 'lead')); $grand_lead +=$camp_lead;
			$camp_reach = array_sum(array_column($camp_val, 'reach')); $grand_reach +=$camp_reach;
			$camp_name = $camp_val[0]['campaign_name'];
			if($camp_spend>0 && $camp_lead) { $camp_cpl = @round($camp_spend/$camp_lead, 2); }

			$leadV = [[$camp_name, $camp_reach, $camp_impr, $camp_lead, $camp_cpl, $camp_spend]];
			update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2+$k), $service);
			if(count($wk_rep_cmp[$pk][$w_k])==$k) { 
				$grand_cpl = 0;
				if($grand_spend>0 && $grand_lead) { $grand_cpl = @round($grand_spend/$grand_lead, 2); }

				$leadV = [['Total', round($grand_reach), round($grand_impr), round($grand_lead), $grand_cpl, round($grand_spend)]];
				update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=(($row_inc+$last_rowNo)+2+$k+1), $service);
			}
			$k++;
		}
		$last_rowNo = $last_rowNo + $k + 3;
		$row_inc++;
	}
	 $j++; //exit; */
}

//merge_rows($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 1, 0, 4,1); //merge_rows($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, $stRow, $enRow, $startCol, $endCol)
//create_spreadsheet_tab($uId, $tbl_id,'Adrescue-Test');
//$repMon = '05, 2024';
//create_spreadsheet_tab($uId, $tbl_id, $sheetId='', $clientName.' - Ads Report', $projects_list, $repId, $repMon);

exit;