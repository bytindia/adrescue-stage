<?php

$qry_str_ad = '&filter_set=SEARCH_BY_ADGROUP_IDS-STRING_SET%1EANY%1E[%22';
$qry_str_adset = '&filter_set=SEARCH_BY_CAMPAIGN_IDS-STRING_SET%1EANY%1E[%22';
$qry_str_camp = '&filter_set=SEARCH_BY_CAMPAIGN_GROUP_IDS-STRING_SET%1EANY%1E[%22';

$fb_url_ad = 'https://adsmanager.facebook.com/adsmanager/manage/ads?act='.$fbId;
$fb_url_adset = 'https://adsmanager.facebook.com/adsmanager/manage/adsets?act='.$fbId;
$fb_url_camp = 'https://adsmanager.facebook.com/adsmanager/manage/campaigns?act='.$fbId;

$dt_qry_30 = '%22]&date='.date("Y-m-d", strtotime('-30 days')).'_'.date('Y-m-d',strtotime('today'));
$dt_qry_3 = '%22]&date='.date("Y-m-d", strtotime('-3 days')).'_'.date('Y-m-d',strtotime('today'));

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
function getBestAds($ads) {
    $highestLeadAd = null;
    $lowestCplAd = null;

    foreach ($ads as $ad) {
        // Determine highest leads
        if (!$highestLeadAd || $ad['lead'] > $highestLeadAd['lead']) {
            $highestLeadAd = $ad;
        }
        // Determine lowest CPL
        if (!$lowestCplAd || ($ad['cpl'] < $lowestCplAd['cpl'] && $ad['cpl'] > 0)) {
            $lowestCplAd = $ad;
        }
    }

    return [
        'lead' => $highestLeadAd,
        'cpl' => $lowestCplAd
    ];
}
function GetAdPreview($ad_id, $access_token, $api_ver){
        $adFormat = 'MOBILE_FEED_STANDARD';
        $pre_req = "https://graph.facebook.com/{$api_ver}/{$ad_id}/previews?ad_format={$adFormat}";
        $ch = curl_init();

        // cURL options
        curl_setopt($ch, CURLOPT_URL, $pre_req);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
        ]);
        // Execute cURL request
        $bst_res = curl_exec($ch);
        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
            curl_close($ch);
            //exit;
            return '';
        }
        // Close cURL
        curl_close($ch);
        
        $adPreview = json_decode($bst_res, true);
        if(isset($adPreview['data'][0]['body'])){
            return $adPreview['data'][0]['body'];
        }
        return '';
}
function checkPixel($url) {
    
    $ch = curl_init($url);

    // Set options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode == 403) {
        $pixel['err'] = "Access Forbidden (403). Cannot fetch the content.\n";
    } elseif ($response === false) {
        $pixel['err'] = "cURL Error: " . curl_error($ch);
    } else {
        // Check for Facebook Pixel
        if (preg_match("/fbq\\('init',\\s*'([0-9]+)'\\)/", $response, $fbMatch)) {
            $pixel['fb'] = 'fas fa-check-circle';
        } else {
            $pixel['fb'] = 'fas fa-times-circle';
        }

        // Check for Google Pixel
        if (preg_match("/gtag\\('config',\\s*'([A-Z0-9\\-]+)'\\)/", $response, $googleMatch)) {
            $pixel['g'] = 'fas fa-check-circle';
        } else {
            $pixel['g'] = 'fas fa-times-circle';
        }
    }
    

    return $pixel;
}

$adAccountStatus = [
    1   => "Active",
    2   => "Disabled",
    3   => "Unsettled",
    7   => "Pending Review",
    8   => "In Grace Period",
    9   => "Pending Closure",
    100 => "Prepaid Pending",
    101 => "Pending Settlement"
];

$timeRanges = [
    "00:00:00 - 00:59:59" => "12 AM - 01 AM",
    "01:00:00 - 01:59:59" => "01 AM - 02 AM",
    "02:00:00 - 02:59:59" => "02 AM - 03 AM",
    "03:00:00 - 03:59:59" => "03 AM - 04 AM",
    "04:00:00 - 04:59:59" => "04 AM - 05 AM",
    "05:00:00 - 05:59:59" => "05 AM - 06 AM",
    "06:00:00 - 06:59:59" => "06 AM - 07 AM",
    "07:00:00 - 07:59:59" => "07 AM - 08 AM",
    "08:00:00 - 08:59:59" => "08 AM - 09 AM",
    "09:00:00 - 09:59:59" => "09 AM - 10 AM",
    "10:00:00 - 10:59:59" => "10 AM - 11 AM",
    "11:00:00 - 11:59:59" => "11 AM - 12 PM",
    "12:00:00 - 12:59:59" => "12 PM - 01 PM",
    "13:00:00 - 13:59:59" => "01 PM - 02 PM",
    "14:00:00 - 14:59:59" => "02 PM - 03 PM",
    "15:00:00 - 15:59:59" => "03 PM - 04 PM",
    "16:00:00 - 16:59:59" => "04 PM - 05 PM",
    "17:00:00 - 17:59:59" => "05 PM - 06 PM",
    "18:00:00 - 18:59:59" => "06 PM - 07 PM",
    "19:00:00 - 19:59:59" => "07 PM - 08 PM",
    "20:00:00 - 20:59:59" => "08 PM - 09 PM",
    "21:00:00 - 21:59:59" => "09 PM - 10 PM",
    "22:00:00 - 22:59:59" => "10 PM - 11 PM",
    "23:00:00 - 23:59:59" => "11 PM - 12 AM"
];

function nf($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}

//Account Information
$query1 = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/?level=account&fields=name,age,created_time,account_status&access_token='.$access_token.'';
$q_res1 = file_get_contents_curl($query1);
$acc_info = json_decode($q_res1,true);
if(isset($acc_info['error']['message'])){
    echo '<b>Error: '.$acc_info['error']['message'].'</b> - Kindly check with the developer!'; exit;
}
//Active Campaigns Summary
$url_ad = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750"; //exit;
    
$daily_bud = $act_ads_camp_ids = $act_ads_adset_ids = $daily_bud_ty = $daily_bud_adsets = $daily_bud_camp_ids =  $daily_bud_camp_ids_auto = $act_ads_id = array();  
$daily_bud_adset_auto = $daily_bud_adset_manual = $life_bud_adset_auto = $life_bud_adset_manual = $daily_bud_camp_auto = $daily_bud_camp_manual = $life_bud_camp_auto = $life_bud_camp_manual = array();
$test_tot1 = $test_tot2 = $test_tot3=  $test_tot33= 0;
$daily_bud_ids = array(); 

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

$act_ads_camp_ids = array_unique($act_ads_camp_ids);
//$act_ads_adset_ids = array_unique($act_ads_adset_ids);
//d($act_ads_adset_ids); //exit;


$url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=id,name,bid_strategy,effective_status,daily_budget,lifetime_budget,end_time,adsets.limit(50){id,daily_budget,lifetime_budget,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
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

                                    if(isset($as_v['daily_budget']) && $ads_act == 'y' && isset($as_v['bid_strategy']) && $as_v['bid_strategy']=='LOWEST_COST_WITHOUT_CAP'){
                                        $daily_bud_adset_auto[$as_v['id']] = $as_v['daily_budget'];
                                    }
                                    if(isset($as_v['daily_budget']) && $ads_act == 'y' && isset($as_v['bid_strategy']) && $as_v['bid_strategy']!='LOWEST_COST_WITHOUT_CAP'){
                                        $daily_bud_adset_manual[$as_v['id']] = $as_v['daily_budget'];
                                    }
                                    if(isset($as_v['lifetime_budget']) && $ads_act == 'y' && isset($as_v['bid_strategy']) && $as_v['bid_strategy']=='LOWEST_COST_WITHOUT_CAP'){
                                        $life_bud_adset_auto[$as_v['id']] = $as_v['lifetime_budget'];
                                    }
                                    if(isset($as_v['lifetime_budget']) && $ads_act == 'y' && isset($as_v['bid_strategy']) && $as_v['bid_strategy']!='LOWEST_COST_WITHOUT_CAP'){
                                        $life_bud_adset_manual[$as_v['id']] = $as_v['lifetime_budget'];
                                    }
                                    
                                    if($ads_act == 'y'){
                                        $act_campIds[$v1['id']] = $v1['id'];
                                        $act_ads_adset_ids[] = $as_v['id'];
                                        //echo $as_v['id'].'-'.$endCheck.'<br>';
                                        if(isset($v1['daily_budget']) && isset($v1['bid_strategy']) && $v1['bid_strategy']=='LOWEST_COST_WITHOUT_CAP') {
                                            $daily_bud_camp_auto[$v1['id']] = $v1['daily_budget'];
                                        }
                                        if(isset($v1['daily_budget']) && isset($v1['bid_strategy']) && $v1['bid_strategy']!='LOWEST_COST_WITHOUT_CAP') {
                                            $daily_bud_camp_manual[$v1['id']] = $v1['daily_budget'];
                                        }
                                        if(isset($v1['lifetime_budget']) && isset($v1['bid_strategy']) && $v1['bid_strategy']=='LOWEST_COST_WITHOUT_CAP') {
                                            $life_bud_camp_auto[$v1['id']] = $v1['lifetime_budget'];
                                        }
                                        if(isset($v1['lifetime_budget']) && isset($v1['bid_strategy']) && $v1['bid_strategy']!='LOWEST_COST_WITHOUT_CAP') {
                                            $life_bud_camp_manual[$v1['id']] = $v1['lifetime_budget'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                

                }
            }
    } // exit;
    //d($daily_bud); echo round($test_tot1/100);  echo '-'.round($test_tot2/100); echo '-'.$test_tot33; echo '-'.$test_tot3; exit;
}

//echo count(array_unique($act_campIds)).' - '.count(array_unique($act_ads_adset_ids)).' - '.count(array_unique($act_ads_id)); 
//d(array_unique($act_ads_adset_ids));
//d($daily_bud_camp_ids); d($daily_bud_camp_ids_auto);
//d($daily_bud_ids); echo (array_sum($daily_bud_ids)/100);
//exit;
//d($act_ads_adset_ids); exit;

$camp_daily_bud_m = $camp_daily_bud_a = $camp_life_bud_m = $camp_life_bud_a = 0;
$adset_daily_bud_m = $adset_daily_bud_a = $adset_life_bud_m = $adset_life_bud_a = 0;

if(count($daily_bud_camp_manual)>0) { $camp_daily_bud_m = round(array_sum($daily_bud_camp_manual)/100); }
if(count($life_bud_camp_manual)>0) { $camp_life_bud_m = round(array_sum($life_bud_camp_manual)/100); }
if(count($daily_bud_camp_auto)>0) { $camp_daily_bud_a = round(array_sum($daily_bud_camp_auto)/100); }
if(count($life_bud_camp_auto)>0) { $camp_life_bud_a = round(array_sum($life_bud_camp_auto)/100); }

if(count($daily_bud_adset_manual)>0) { $adset_daily_bud_m = round(array_sum($daily_bud_adset_manual)/100); }
if(count($life_bud_adset_manual)>0) { $adset_life_bud_m = round(array_sum($life_bud_adset_manual)/100); }
if(count($daily_bud_adset_auto)>0) { $adset_daily_bud_a = round(array_sum($daily_bud_adset_auto)/100); }
if(count($life_bud_adset_auto)>0) { $adset_life_bud_a = round(array_sum($life_bud_adset_auto)/100); }

//Cust Audience
$cust_aud_list = $lookalike_aud_list = $remark_aud_list = $cust_aud_ty = $age_target = $work_position = $work_emp = $interests_target = $placement = $dynamic = array();
include 'targeting.php';

//best performing ad
$lg_best_lead = $lg_best_cpl = $conv_best_lead = $conv_best_cpl = $sale_best_lead = $sale_best_cpl = ''; 
$top_campaigns = $zero_lead_ads = $avg_cpl=[];  
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'LG', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];
include 'best-creative.php';

//Ad quality Ranking
$ad_qty_ranking = array();
if(count($act_ads_id)>0){
    $actAd_ids_str = implode(',', array_unique($act_ads_id));
    $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?fields=quality_ranking&level=ad&filtering=[{'field':'ad.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&limit=750";
    $output = $res = array();
    loopAdRep($url);  
    $res = $output;
    if(isset($res['data']) && count($res['data'])>0){ 
        foreach($res['data'] as $k1 => $v1) {
            $ad_qty_ranking[] = $v1['quality_ranking'];
        }
    }
}
//d($ad_qty_ranking); exit;

//LP URL's
$lp_urls_all = $lp_urls_unique = $lp_urls = array();
if(count($act_ads_id)>0){
    $actAd_ids_str = implode(',', array_unique($act_ads_id));
    $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,adcreatives{object_story_spec}&filtering=[{'field':'campaign.objective','operator':'IN','value':['CONVERSIONS']}]&filtering=[{'field':'ad.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&limit=200"; 
    $output = $res = array();
    loopAdRep($url);  
    $res = $output;
    //d($res);
    if(isset($res) && count($res)>0){ 
        foreach ($res as $k => $v) {
            if(isset($v['adcreatives']['data'])) {
                foreach ($v['adcreatives']['data'] as $k1 => $v1) {
                    //d($v1);
                    if(isset($v1['object_story_spec']['link_data']['link']) && $v1['object_story_spec']['link_data']['link']!='') {
                        $lp_urls_all[] = $v1['object_story_spec']['link_data']['link'];
                    }
                    if(isset($v1['object_story_spec']['link_data']['child_attachments']) && count($v1['object_story_spec']['link_data']['child_attachments'])) {
                        foreach ($v1['object_story_spec']['link_data']['child_attachments'] as $k2 => $v2) {
                            $lp_urls_all[] = $v2['link'];
                        }

                    }
                    if(isset($v1['object_story_spec']['video_data']['call_to_action']['value']['link'])) {
                        $lp_urls_all[] = $v1['object_story_spec']['video_data']['call_to_action']['value']['link'];
                    }
                    /*$getURL =  $v['object_story_spec']['link_data']['link'];
                    $getURL = strtok($getURL, '?');
                    $lp_urls[] = rtrim($getURL,"/");*/

                }
            }
        }
    }

    if(count($lp_urls_all)>0) {
        foreach ($lp_urls_all as $k => $v) {
            $getURL = strtok($v, '?');
            //if(str_contains($getURL, 'fb.me')) { }
            $lp_urls[] = rtrim($getURL,"/");
        }
        $lp_urls_unique = array_unique($lp_urls);
    }
}
//d($lp_urls_unique); exit;


//Placements

$placements = $breakdowns = [];
include 'placements.php';

 
