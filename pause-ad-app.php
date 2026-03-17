<?php
include 'db.php'; 

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

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 

//$_POST['act'] = 665661671485180; $_POST['camp_name'] = 'water'; $_GET['type'] = 'fetch';

$campContain = $campContain2 = "";
if(isset($_POST['act']) && $_POST['act']!=''){ $act = $_POST['act']; }
if(isset($_POST['camp_name']) && $_POST['camp_name']!=''){
    $campN = $_POST['camp_name'];
    $campContain = "&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']},{'field':'campaign.name','operator':'CONTAIN','value':'".$campN."'}]";
    $campContain2 =  ",{'field':'campaign.name','operator':'CONTAIN','value':'".$campN."'}";
}

if(isset($_GET['type']) && $_GET['type'] == 'pause'){

    
    if(isset($_GET['action']) && $_GET['action'] == 'fetch'){
        $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$act."/ads?fields=id,status,effective_status,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}$campContain2]&access_token=".$access_token."&limit=750"; //exit;
        
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
    
        $url = "https://graph.facebook.com/".$api_ver."/act_".$act."/campaigns?fields=id,name,bid_strategy,effective_status,end_time,adsets.limit(50){id,name,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
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
                                                $act_ads_adset_ids[] = array('asId'=>$as_v['id'], 'asName'=>$as_v['name'], 'cId'=>$v1['id'], 'cName'=>$v1['name']); //$as_v['id'];
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
    
        //d($act_ads_adset_ids);
        header('Content-Type: application/json; charset=utf-8');
        if(count($act_ads_adset_ids)>0){
            echo json_encode($act_ads_adset_ids);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No data found',
                'error_code' => 500
            ];
            echo json_encode($response);
        }
    }
    

    if(isset($_GET['action']) && $_GET['action'] == 'stop'){
        $adset_ids = explode(',', $_GET['adset_ids']);
        header('Content-Type: application/json; charset=utf-8');
        if(count($adset_ids)>0){
            echo json_encode(array('status'=>200, 'message'=>$adset_ids));
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No data found',
                'error_code' => 500
            ];
            echo json_encode($response);
        }
    }

}

if(isset($_GET['type']) && $_GET['type'] == 'resume'){

    
    if(isset($_GET['action']) && $_GET['action'] == 'fetch'){
        $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$act."/ads?fields=id,status,effective_status,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['PAUSED']}$campContain2]&access_token=".$access_token."&limit=750"; //exit;
        
        $daily_bud = $act_ads_camp_ids = $act_ads_adset_ids = $daily_bud_ty = $daily_bud_adsets = $daily_bud_camp_ids =  $daily_bud_camp_ids_auto = $act_ads_id = array();  
        $daily_bud_adset_auto = $daily_bud_adset_manual = $life_bud_adset_auto = $life_bud_adset_manual = $daily_bud_camp_auto = $daily_bud_camp_manual = $life_bud_camp_auto = $life_bud_camp_manual = array();
        $test_tot1 = $test_tot2 = $test_tot3=  $test_tot33= 0;
        $daily_bud_ids = array(); 
    
        loopAdRep($url_ad);  
        $res_ad = $output;
    
    
    
        if(isset($res_ad['data']) && count($res_ad['data'])>0) {
            foreach($res_ad['data'] as $kk => $vv) {
                if($vv['effective_status']=='PAUSED'){
                    $act_ads_camp_ids[] = $vv['campaign_id'];
                    //$act_ads_adset_ids[] = $vv['adset_id'];
                }
            }
        }
    
        $act_ads_camp_ids = array_unique($act_ads_camp_ids);
    
        $url = "https://graph.facebook.com/".$api_ver."/act_".$act."/campaigns?fields=id,name,bid_strategy,effective_status,end_time,adsets.limit(50){id,name,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['PAUSED']}]&access_token=".$access_token."&limit=750";
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
    
                    if($v1['effective_status']=='PAUSED' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act=='y') {  
                        $camp_act = 'n';
                        if(isset($v1['adsets']['data'])){
                            
                            foreach($v1['adsets']['data'] as $as_k => $as_v) {
                                $ads_act = 'n';
                                if($as_v['effective_status']=='PAUSED'){
                                    if(isset($as_v['ads']['data'])){
                                        $endCheck = 'y';
                                        if(isset($as_v['end_time'])) { 
                                            $end_time = new DateTime($as_v['end_time']);
                                            $currentDateTime = new DateTime('now', $end_time->getTimezone());
                                            if($end_time < $currentDateTime) { $ads_act = 'n'; $endCheck='n'; }
                                        }
                                        if($endCheck=='y'){
                                            foreach($as_v['ads']['data'] as $ad_k => $ad_v) {
                                                if($ad_v['effective_status']=='PAUSED'){
                                                    $ads_act = $camp_act = 'y';
                                                    $act_ads_id[] = $ad_v['id'];
                                                }
                                            }
                                            
                                            if($ads_act == 'y'){
                                                $act_campIds[$v1['id']] = $v1['id'];
                                                $act_ads_adset_ids[] = array('asId'=>$as_v['id'], 'asName'=>$as_v['name'], 'cId'=>$v1['id'], 'cName'=>$v1['name']); //$as_v['id'];
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
    
        //d($act_ads_adset_ids);
        header('Content-Type: application/json; charset=utf-8');
        if(count($act_ads_adset_ids)>0){
            echo json_encode($act_ads_adset_ids);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No data found',
                'error_code' => 500
            ];
            echo json_encode($response);
        }
    }
    

    if(isset($_GET['action']) && $_GET['action'] == 'start'){
        $adset_ids = explode(',', $_GET['adset_ids']);
        header('Content-Type: application/json; charset=utf-8');
        if(count($adset_ids)>0){
            echo json_encode(array('status'=>200, 'message'=>$adset_ids));
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No data found',
                'error_code' => 500
            ];
            echo json_encode($response);
        }
    }

}



