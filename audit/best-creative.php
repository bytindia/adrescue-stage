<?php
/*$api_ver = 'v19.0'; $fbId=735957617015640; $access_token = 'REDACTED_FB_TOKEN';
include '../db.php';
include '../media-buyer/include.php';
include '/home/digitalb2k/stage.adrescue.in/functions-report.php';
function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}*/
$sinceDate = date('Y-m-d', strtotime('-30 days'));
$untilDate = date('Y-m-d', strtotime('-1 day'));
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
//Avg CPL
$avg_cpl= [];
foreach($objectives2 as $k => $v){

    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=account&fields=spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':[".$v."]}]&access_token=".$access_token."&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    if(isset($fb_response['data'])){
        foreach($fb_response['data'] as $k1 => $v1) {
            $leads = $cpl = 0;
            $obj = $k;
            if(isset($v1['actions'])) { $leads = LeadGen($v1['actions'], $objectives[$obj]['valueKey']); }
            if($v1['spend']>0 && $leads>0) { $cpl= round($v1['spend']/$leads); }
            $avg_cpl[$obj] = array('spend'=>$v1['spend'], 'lead'=>$leads, 'cpl'=>round($cpl));
        }
    }
}
//d($avg_cpl); //exit;
$best_ads_results = [];
$lg_best_lead = $lg_best_cpl = $conv_best_lead = $conv_best_cpl = $sale_best_lead = $sale_best_cpl = '';
$top_campaigns = $zero_lead_ads = [];
$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=ad&fields=ad_id,campaign_id,campaign_name,adset_id,adset_name,ad_name,impressions,spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','CONVERSIONS','OUTCOME_SALES','OUTCOME_LEADS','PRODUCT_CATALOG_SALES']},{'field':'impressions','operator':'GREATER_THAN','value':1000}]&access_token=".$access_token."&limit=500&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."";

$requests = file_get_contents_curl($request_url);
$fb_response = json_decode($requests,true);




$best_ads_all = array();

$camp_data_all= array();
$campaign_data = [];

if(isset($fb_response['data'])){

    foreach($fb_response['data'] as $k => $v) {
        $leads = $cpl = 0;
        $obj = $v['objective'];
        if($v['objective']=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
        if($v['objective']=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
        if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $objectives[$obj]['valueKey']); }
        if($v['spend']>0 && $leads>0) { $cpl= round($v['spend']/$leads); }
        $best_ads_all[$obj][] = array('id'=>$v['ad_id'], 'lead'=>$leads, 'cpl'=>$cpl);
        $camp_data_all[$obj][$v['adset_id']][] = array('spend'=>$v['spend'], 'lead'=>$leads, 'name'=>$v['campaign_name'],'asN'=>$v['adset_name'],'asId'=>$v['adset_id'],'camp_id'=>$v['campaign_id']);
    }
    //d($best_ads_all);
    
    
    foreach ($objectives as $k => $v) {
        if(isset($best_ads_all[$k])){
            $best_ads_results[$k] = getBestAds($best_ads_all[$k]);
            
            if($k=='LEAD_GENERATION' && isset($best_ads_results[$k]['lead'])) { $lg_best_lead = GetAdPreview($best_ads_results[$k]['lead']['id'], $access_token, $api_ver); }
            if($k=='LEAD_GENERATION' && isset($best_ads_results[$k]['cpl'])) { $lg_best_cpl = GetAdPreview($best_ads_results[$k]['lead']['id'], $access_token, $api_ver); }
    
            if($k=='CONVERSIONS' && isset($best_ads_results[$k]['lead'])) { $conv_best_lead = GetAdPreview($best_ads_results[$k]['lead']['id'], $access_token, $api_ver); }
            if($k=='CONVERSIONS' && isset($best_ads_results[$k]['cpl'])) { $conv_best_cpl = GetAdPreview($best_ads_results[$k]['lead']['id'], $access_token, $api_ver); }
    
            if($k=='OUTCOME_SALES' && isset($best_ads_results[$k]['lead'])) { $sale_best_lead = GetAdPreview($best_ads_results[$k]['lead']['id'], $access_token, $api_ver); }
            if($k=='OUTCOME_SALES' && isset($best_ads_results[$k]['cpl'])) { $sale_best_cpl = GetAdPreview($best_ads_results[$k]['lead']['id'], $access_token, $api_ver); }
    
        }
    }

    foreach ($objectives as $k => $v) {
        if(isset($camp_data_all[$k]) && count($camp_data_all[$k])>0){
            foreach ($camp_data_all[$k] as $ck => $cv) {
                $cpl= 0;
                $spend = array_sum(array_column($cv, 'spend')); 
                $lead = array_sum(array_column($cv, 'lead')); 
                if($spend>0 && $lead>0) { $cpl= round($spend/$lead); }
                if (in_array($ck, $act_ads_adset_ids)) {
                    $campaign_data[$k][$ck] = array('spend'=>$spend, 'leads'=>$lead, 'cpl'=>$cpl,'campaign_name'=> htmlspecialchars($cv[0]['name']),'asN'=> htmlspecialchars($cv[0]['asN']),'asId'=>$cv[0]['asId'],'camp_id'=>$cv[0]['camp_id']); //substr($cv[0]['name'], 0, 30) . '...'
                }
            }
        }
    }
}
//d($campaign_data); exit;
//Zero Leads in the Last 3days
if(count($act_ads_id)>0){
    $actAd_ids_str = implode(',', array_unique($act_ads_id));
    $last_3d = date('Y-m-d', strtotime('-3 days'));
    $untilDate = date('Y-m-d', strtotime('-1 days'));

    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=ad&fields=ad_id,campaign_id,campaign_name,ad_name,impressions,spend,actions,objective&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','CONVERSIONS','OUTCOME_SALES','OUTCOME_LEADS','PRODUCT_CATALOG_SALES']},{'field':'impressions','operator':'GREATER_THAN','value':1000},{'field':'ad.id','operator':'IN','value':[".$actAd_ids_str."]}]&access_token=".$access_token."&limit=500&time_range[since]=".$last_3d."&time_range[until]=".$untilDate."";

    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    if(isset($fb_response['data'])){

        foreach($fb_response['data'] as $k => $v) {
            $leads = $cpl = 0;
            $obj = $v['objective'];
            if($v['objective']=='OUTCOME_LEADS'){ $obj='LEAD_GENERATION'; }
            if($v['objective']=='PRODUCT_CATALOG_SALES'){ $obj='OUTCOME_SALES'; }
            if(isset($v['actions'])) { $leads = LeadGen($v['actions'], $objectives[$obj]['valueKey']); }
            if($v['spend']>0 && $leads>0) { $cpl= round($v['spend']/$leads); }
            if($leads==0){
                $zero_lead_ads[$obj][$v['ad_id']] = array('id'=>$v['ad_id'], 'camp_id'=>$v['campaign_id'], 'camp_name'=>$v['campaign_name'], 'ad_name'=>$v['ad_name']);
            }
            //$camp_data_all[$obj][$v['campaign_id']][] = array('spend'=>$v['spend'], 'lead'=>$leads, 'name'=>$v['campaign_name']);
        }
    }
}
//d($lg_best_lead);
//echo $lg_best_lead; 
//d($best_ads_results); exit;

/*
$campaign_data = [];

if (isset($fb_response['data'])) {
    // Process ads and group them by campaign
    foreach ($fb_response['data'] as $ad) {
        $campaign_id = $ad['campaign_id'];
        $campaign_name = $ad['campaign_name'];
        $objective = $ad['objective'];
        $spend = isset($ad['spend']) ? (float)$ad['spend'] : 0;
        $actions = isset($ad['actions']) ? $ad['actions'] : [];
        $leads = 0;

        // Map objectives to ensure consistency
        if ($objective == 'OUTCOME_LEADS') {
            $objective = 'LEAD_GENERATION';
        }
        if ($objective == 'PRODUCT_CATALOG_SALES') {
            $objective = 'OUTCOME_SALES';
        }

        // Get leads or conversions based on the objective
        if (isset($objectives[$objective])) {
            foreach ($actions as $action) {
                if ($action['action_type'] === $objectives[$objective]['valueKey']) {
                    $leads = (int)$action['value'];
                    break;
                }
            }
        }

        // Calculate CPL
        $cpl = ($spend > 0 && $leads > 0) ? round($spend / $leads, 2) : PHP_INT_MAX;

        // Group by campaign
        if (!isset($campaign_data[$objective][$campaign_id])) {
            $campaign_data[$objective][$campaign_id] = [
                'campaign_name' => $campaign_name,
                'spend' => 0,
                'leads' => 0,
                'cpl' => PHP_INT_MAX
            ];
        }

        // Aggregate data for the campaign
        $campaign_data[$objective][$campaign_id]['spend'] += $spend;
        $campaign_data[$objective][$campaign_id]['leads'] += $leads;
        $campaign_data[$objective][$campaign_id]['cpl'] = ($campaign_data[$objective][$campaign_id]['leads'] > 0) 
            ? round($campaign_data[$objective][$campaign_id]['spend'] / $campaign_data[$objective][$campaign_id]['leads'], 2)
            : PHP_INT_MAX;
    }
}
*/
// Sort and get the top 5 campaigns for each objective

foreach ($campaign_data as $objective => $campaigns) {
     $fixed_cpl = $avg_cpl[$objective]['cpl'];
     // Filter campaigns by non-zero CPL and matching the fixed CPL value
    $filtered_campaigns = array_filter($campaigns, function ($campaign) use ($fixed_cpl) {
        return $campaign['cpl'] != 0 && $campaign['cpl'] <= $fixed_cpl;
    });

    $filtered_campaigns2 = array_filter($campaigns, function ($campaign) use ($fixed_cpl) {
        return $campaign['cpl'] != 0 && $campaign['cpl'] >= $fixed_cpl;
    });

    // Sort campaigns by CPL (ascending for lowest CPL campaigns)
    usort($filtered_campaigns, function ($a, $b) {
        return $a['cpl'] <=> $b['cpl'];
    });

    // Top 5 lowest CPL campaigns
    $top_campaigns[$objective]['low_cpl'] = $filtered_campaigns;

    // Sort campaigns by CPL (descending for highest CPL campaigns)
    usort($filtered_campaigns2, function ($a, $b) {
        return $b['cpl'] <=> $a['cpl'];
    });

    // Top 5 highest CPL campaigns
    $top_campaigns[$objective]['high_cpl'] = $filtered_campaigns2;
}
//d($top_campaigns);
/*
// Output the results
foreach ($top_campaigns as $objective => $data) {
    echo "Objective: ".$objective. "<br>";

    echo "Top 5 Lowest CPL Campaigns:<br>";
    foreach ($data['lowest_cpl'] as $campaign) {
        echo "Campaign Name: " . $campaign['campaign_name'] . "<br>";
        echo "Spend: $" . $campaign['spend'] . "<br>";
        echo "Leads: " . $campaign['leads'] . "<br>";
        echo "CPL: $" . ($campaign['cpl'] === PHP_INT_MAX ? 'N/A' : $campaign['cpl']) . "<br>";
        echo "----------------------------------------<br>";
    }

    echo "Top 5 Highest CPL Campaigns:<br>";
    foreach ($data['highest_cpl'] as $campaign) {
        echo "Campaign Name: " . $campaign['campaign_name'] . "<br>";
        echo "Spend: $" . $campaign['spend'] . "<br>";
        echo "Leads: " . $campaign['leads'] . "<br>";
        echo "CPL: $" . ($campaign['cpl'] === PHP_INT_MAX ? 'N/A' : $campaign['cpl']) . "<br>";
        echo "----------------------------------------<br>";
    }
    echo "<br>";
}
//d($best_ads_all);
*/