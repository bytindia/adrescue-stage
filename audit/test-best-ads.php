<?php

include '../db.php';
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
$accessToken = 'REDACTED_FB_TOKEN';
$adAccountId = 'act_5731659836926959';

$sinceDate = date('Y-m-d', strtotime('-30 days'));
$untilDate = date('Y-m-d');

 $request_url = "https://graph.facebook.com/v20.0/".$adAccountId."/insights?fields=ad_id,ad_name,impressions,spend,actions,objective&level=ad&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','CONVERSIONS','OUTCOME_SALES','OUTCOME_LEADS','PRODUCT_CATALOG_SALES']},{'field':'impressions','operator':'GREATER_THAN','value':1000}]&access_token=".$accessToken."&limit=500&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."";

$requests = file_get_contents_curl($request_url);
$fb_response = json_decode($requests,true);
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'LG', 'name'=>'Ecom.', 'key'=>'sale']
];
$best_ads_all = array();
foreach($fb_response['data'] as $k => $v){
    $leads = $cpl = 0;
    $obj = $v['objective'];
    if($v['objective']=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
    if($v['objective']=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
    if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $objectives[$obj]['valueKey']); }
    if($v['spend']>0 && $leads>0) { $cpl= round($v['spend']/$leads); }
    $best_ads_all[$obj][] = array('id'=>$v['ad_id'], 'lead'=>$leads, 'cpl'=>$cpl);
}
//d($best_ads_all);

$best_ads_results = [];
$lg_best_lead = $lg_best_cpl = $conv_best_lead = $conv_best_cpl = $sale_best_lead = $sale_best_cpl = '';
foreach ($objectives as $k => $v) {
    if(isset($best_ads_all[$k])){
        $best_ads_results[$k] = getBestAds($best_ads_all[$k]);
        
        if($k=='LEAD_GENERATION' && isset($best_ads_results[$k]['lead'])) { $lg_best_lead = GetAdPreview($best_ads_results[$k]['lead']['id'], $accessToken, $api_ver); }
        if($k=='LEAD_GENERATION' && isset($best_ads_results[$k]['cpl'])) { $lg_best_cpl = GetAdPreview($best_ads_results[$k]['lead']['id'], $accessToken, $api_ver); }

        if($k=='CONVERSIONS' && isset($best_ads_results[$k]['lead'])) { $conv_best_lead = GetAdPreview($best_ads_results[$k]['lead']['id'], $accessToken, $api_ver); }
        if($k=='CONVERSIONS' && isset($best_ads_results[$k]['cpl'])) { $conv_best_cpl = GetAdPreview($best_ads_results[$k]['lead']['id'], $accessToken, $api_ver); }

        if($k=='OUTCOME_SALES' && isset($best_ads_results[$k]['lead'])) { $sale_best_lead = GetAdPreview($best_ads_results[$k]['lead']['id'], $accessToken, $api_ver); }
        if($k=='OUTCOME_SALES' && isset($best_ads_results[$k]['cpl'])) { $sale_best_cpl = GetAdPreview($best_ads_results[$k]['lead']['id'], $accessToken, $api_ver); }

    }
}
//echo $lg_best_lead; 
//d($best_ads_results); exit;
