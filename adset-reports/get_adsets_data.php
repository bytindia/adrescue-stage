<?php
// get_options.php
session_start();
include '../db.php';
// Check if the category is set and fetch options based on the selected category
//$_GET['category'] = 665661671485180;

if (isset($_GET['category'])) {
    $category = $_GET['category'];
    $options = [];
    $fbId = $category;
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

    $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    $access_token = $row['access_token']; 
    $uId = $row['tbl_id'];
    
    $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750"; //exit;
        
    $act_ads_camp_ids  = $output = array(); 
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

    $act_campIds = $act_ads_id = $act_ads_adset_ids = $zero_lead_adset = array();

    $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=id,name,bid_strategy,effective_status,end_time,adsets.limit(50){id,name,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
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
                                        if($ads_act == 'y'){
                                            $act_campIds[$v1['id']] = $v1['id'];
                                            $act_ads_adset_ids[] = array('value' => $as_v['id'], 'text' => mysqli_real_escape_string($conn, $as_v['name'].' ('.$v1['name'].')'));
                                        }
                                    }
                                }
                            }
                        }
                    

                    }
                }
        } 
    }

    $options = $act_ads_adset_ids;

    

    // Send the options as a JSON response
    header('Content-Type: application/json');
    echo json_encode($options);
} else {
    // Return empty if no category is selected
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
