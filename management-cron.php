<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include 'db.php';
include 'functions-report.php'; 

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

include 'gsquare-taboola.php';
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
$d = new DateTime('yesterday');
$d2 = new DateTime('today');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';
//exit;

$taboola_tok = '';
include 'taboola-config.php';
include 'management-google.php';

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
$gAccIds = $gStats =array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 

$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'LG',  'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];
$objectives2 = [
    'LEAD_GENERATION' => "'LEAD_GENERATION','OUTCOME_LEADS'",
    'CONVERSIONS' => "'CONVERSIONS'",
    'OUTCOME_SALES' => "'OUTCOME_SALES','PRODUCT_CATALOG_SALES'",
];
$objectives3 = "'LEAD_GENERATION','OUTCOME_LEADS'";
$objectives3 = "'LEAD_GENERATION','OUTCOME_LEADS','CONVERSIONS','OUTCOME_SALES','PRODUCT_CATALOG_SALES'";

$sqlRev=mysqli_query($conn, "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0 AND hide_temp='0' limit 10,10");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;			
$total_spent = 0; 			
//mysqli_query($conn, "TRUNCATE TABLE management_dash") or die(mysqli_error());		
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	echo $bud_tbl.'<br>';
    $bud_tbl = $sqlROW['tbl_id'];
	$gAccIds[$sqlROW['tbl_id']] = $sqlROW['g_id'];
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = $acc_bal = $fblead = $fbcpl = $fblead2 = $fbcpl2 = array();
	
	//mysqli_query($conn, "UPDATE budget_reminder SET fb_spent='0', fb_balance='', g_spent='0', in_spent='0', ta_spent='0' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
    mysqli_query($conn, "INSERT INTO management_dash (bud_tbl) VALUES ('".$bud_tbl."')") or die(mysqli_error());

	
	//Facebook
	$act_adset_data=[];
	if($sqlROW['fb_id']!='') 
	{
		$fbIdsRaw = $sqlROW['fb_id'] ?? ''; // Use empty string if null
		$fbIds = array_filter(array_unique(array_map('trim', explode(',', $fbIdsRaw))));	//$fbReceived = explode(',',$sqlROW['fb_received']);	
		//d($fbIds); exit;
		$fbdata = [];
		foreach($fbIds as $key => $fbId) {						
			//echo $i . "<br />";
			$url_ad = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,effective_status,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
			//$requests = curl_get_file_contents($url_ad);
			$res_ad = $output = array();

			loopAdRep($url_ad);  
			$res_ad = $output;
			//d($res_ad); //exit;
			$act_ads_camp_ids = $act_ads_adset_ids = $act_ads_id = $act_adset_data = array();  
			if(isset($res_ad['data']) && count($res_ad['data'])>0) {
				foreach($res_ad['data'] as $kk => $vv) {
					if($vv['effective_status']=='ACTIVE'){
						$act_ads_camp_ids[] = $vv['campaign_id'];
					}
				}
			}
			$act_ads_camp_ids = array_unique($act_ads_camp_ids);

			$url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=id,name,bid_strategy,effective_status,daily_budget,lifetime_budget,end_time,adsets.limit(50){id,daily_budget,lifetime_budget,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
			$output = $res = $act_campIds = array();
			loopAdRep($url);  
			$res = $output; $k=1;
			if(isset($res['data']) && count($res['data'])>0){ 
					foreach($res['data'] as $k1 => $v1) {
						$camp_act = 'y';
						if(isset($v1['end_time'])) { 
							$end_time = new DateTime($v1['end_time']);
							$currentDateTime = new DateTime('now', $end_time->getTimezone());
							if($end_time < $currentDateTime) { $camp_act = 'n'; }
						}

						if($v1['effective_status']=='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act=='y') {  
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
			$act_ads_adset_ids = array_unique($act_ads_adset_ids);
			$actAd_ids_str = implode(',', array_unique($act_ads_adset_ids));
			//d($actAd_ids_str); exit;


		    $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

			$url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&breakdowns=publisher_platform,device_platform,platform_position&fields=adset_id&filtering=[{'field':'objective','operator':'IN','value':[".$objectives3."]},{'field':'adset.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&".$dtRange."&limit=750";
			$req = file_get_contents_curl($url);
			$res = json_decode($req, true);  
			$placement = '';
			if(isset($res['data']) && count($res['data'])>0) {
				 foreach ($res['data'] as $v) {
					$act_ads_id[] = $v['adset_id'];
					$placement = (stripos($v['platform_position'], 'reels') !== false || stripos($v['platform_position'], 'stories') !== false) ? 'Y' : 'N';
				}
			}

			$levels = ['campaigns', 'adsets', 'ads'];
			$updatedTimes = [];

			foreach ($levels as $level) {
				$fields = "id,name,updated_time";
				$url = "https://graph.facebook.com/{$api_ver}/act_{$fbId}/{$level}?fields={$fields}&effective_status=[\"ACTIVE\",\"PAUSED\"]&access_token={$access_token}&limit=100";
				
				do {
					$response = json_decode(file_get_contents_curl($url), true);

					if (!empty($response['data'])) {
						foreach ($response['data'] as $item) {
							if (!empty($item['updated_time'])) {
								$updatedTimes[] = strtotime($item['updated_time']);
							}
						}
					}

					// Handle pagination
					$url = $response['paging']['next'] ?? null;
				} while ($url);
			}

			$mostRecentDate = '-';
			if (!empty($updatedTimes)) {
				$mostRecentTimestamp = max($updatedTimes);
				$mostRecentDate = date('Y-m-d H:i:s', $mostRecentTimestamp);
			}
			//d($updatedTimes);
			//echo $mostRecentDate; exit;

			 $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&fields=adset_id,campaign_id,campaign_name,adset_name,impressions,spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':[".$objectives3."]},{'field':'adset.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&".$dtRange."";
			$requests = file_get_contents_curl($request_url);
			$fb_response = json_decode($requests,true);
			//d($fb_response);// exit;

			$zero_lead_adset = [];
			if(isset($fb_response['data'])){
					foreach($fb_response['data'] as $k1 => $v) {
						$status = 'no';
						$leads = $spend = $cpl = $ecom_lead = 0;
						$obj = $v['objective'];
						if(isset($adset_status[$v['adset_id']])) { $status = $adset_status[$v['adset_id']]; }
						if($v['objective']=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
						if($v['objective']=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
						if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $objectives[$obj]['valueKey']);   }
						
						if($v['spend']>0 && $leads>0) { $cpl= round($v['spend']/$leads); }
						if($leads==0){
							$zero_lead_adset[] = $v['adset_id'];
						}
						$act_adset_data[] = array(
							'adset_id' => $v['adset_id'],
							'spend' => round($v['spend']),
							'leads' => round($leads),
							'cpl' => round($cpl),
							'obj' => $obj
						);
					}
			}
			//d($act_adset_data); exit;

            $dtRange = 'time_range[since]='.date("Y-m-d", strtotime("-4 days")).'&time_range[until]='.date("Y-m-d", strtotime("-2 days")).'';
            $request_url2 = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend,actions,cost_per_action_type&access_token='.$access_token.'&'.$dtRange.''; //exit;
			$requests2 = curl_get_file_contents($request_url2);
			$fb_response2 = json_decode($requests2,true);
			$fblead2 = $fbcpl2 = $fbspend2 = $fbcpp2 = $fb_pur =0;
            if(isset($fb_response2['data'][0]['actions'])){
				$fbspend2 = round($fb_response2['data'][0]['spend']);
                $fblead2 = LeadGen($fb_response2['data'][0]['actions'], 'lead');
                $fbcpl2 = LeadGen($fb_response2['data'][0]['cost_per_action_type'], 'lead');
				$fb_pur = LeadGen($fb_response2['data'][0]['actions'], 'purchase');
				$fbcpp2 = LeadGen($fb_response2['data'][0]['cost_per_action_type'], 'purchase');
            } 
			//d($act_adset_data);
			//if(count($act_adset_data)>0) {
				$fbdata[] = array(
					'adset_data' => $act_adset_data,
					'last3_spend' => $fbspend2,
					'last3_leads' => $fblead2,
					'last3_cpl' => $fbcpl2,
					'last3_pur' => $fb_pur,
					'last3_cpp' => $fbcpp2,
					'placement' => $placement,
					'last_updated' => $mostRecentDate,
				);
			//}
			
		}		
		//echo "UPDATE management_dash SET fb_spent='".implode(',',$fbSpent)."', fb_leads='".implode(',',$fblead)."', fb_cpl='".implode(',',$fbcpl)."', fb_leads2='".implode(',',$fblead2)."', fb_cpl2='".implode(',',$fbcpl2)."', fb_balance='".implode(',',$acc_bal)."' WHERE bud_tbl=".$bud_tbl.""; 
		//mysqli_query($conn, query: "UPDATE management_dash SET fb_spent='".implode(',',$fbSpent)."', fb_leads='".implode(',',$fblead)."', fb_cpl='".implode(',',$fbcpl)."', fb_leads2='".implode(',',$fblead2)."', fb_cpl2='".implode(',',$fbcpl2)."', fb_balance='".implode(',',$acc_bal)."' WHERE bud_tbl=".$bud_tbl."") or die(mysqli_error()); 
	}
	
	mysqli_query($conn, query: "UPDATE management_dash SET fb_data='".serialize($fbdata)."' WHERE bud_tbl=".$bud_tbl."") or die(mysqli_error()); 
	//d($fbdata); exit;

	//Google
	$g_data = array();
	if($sqlROW['g_id']!='') {
		$gSpent = $gLeads = $gSpent2 = $gLeads2 =  $gCPL = $gCPL2 = array();
		$gaIds = explode(',',$sqlROW['g_id']); //$g_stDt = explode(',',$SatrtDate);
		//d($gaIds);
		$g_data = GoogleAdsDataFetcher::fetchAllData($g_mcc, $g_refresh_token, $gaIds);
	}
	mysqli_query($conn, query: "UPDATE management_dash SET g_data='".serialize($g_data)."' WHERE bud_tbl=".$bud_tbl."") or die(mysqli_error()); 
	//exit;
}

exit;

// YESTERDAY REPORT
