<?php

$sinceDate = date('Y-m-d', strtotime('-30 days'));
$untilDate = date('Y-m-d', strtotime('-1 day'));

$placements = $breakdowns = $tracker_lead = [];

$url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&breakdowns=publisher_platform,device_platform,platform_position&fields=adset_id,adset_name,reach,impressions,spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&time_range[since]=".$sinceDate."&time_range[until]=".$untilDate."&access_token=".$access_token."&limit=750"; 
$requests = file_get_contents_curl($url);
$fb_response = json_decode($requests,true);

if(isset($fb_response['data'])){
    $placements = $fb_response['data'];
    foreach($placements as $k => $val) 
    {
        $lead = $cpl = $spend = 0;
        if(isset($val['actions'])) { $lead = LeadGen($val['actions'], 'lead'); }
        if($val['spend'] !=0 && $lead !=0) { $cpl = @($val['spend']/$lead); } 
        $tbl_key2 = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
        $breakdowns[$tbl_key2][] = array('lead'=>$lead,'cpl'=>$cpl,'spend'=>$val['spend']); 
    }
}

$today = date('Y-m-d');
$url2 = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=account&breakdowns=hourly_stats_aggregated_by_advertiser_time_zone&fields=spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&date_preset=last_30d&access_token=".$access_token."&limit=750";
$requests2 = file_get_contents_curl($url2);
$fb_response2 = json_decode($requests2,true);

if(isset($fb_response2['data'])){
    //$tracker_lead = $fb_response2['data'];

    foreach($fb_response2['data'] as $k => $val) 
    {
        $lead = $cpl = $spend = 0;
        if(isset($val['actions'])) { $lead = LeadGen($val['actions'], 'lead'); }
        if($val['spend'] !=0 && $lead !=0) { $cpl = @($val['spend']/$lead); } 

        $tracker_lead[$val['hourly_stats_aggregated_by_advertiser_time_zone']] = array('lead'=>$lead,'cpl'=>$cpl,'spend'=>$val['spend']); 
    }
}

//d($tracker_lead); //exit;