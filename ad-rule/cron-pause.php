<!-- Include Required Prerequisites -->
<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  

//print_r($_SESSION);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//echo date('H'); exit;
//if(date('H')<5) { exit; }

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
require $dirPath.'email/vendor/autoload.php';
include $dirPath.'email/config.php';

$today = date('Y-m-d');
$stDate =  date('Y-m-d',strtotime('-1 days'));

include '/home/digitalb2k/stage.adrescue.in/db.php';
$pg='facebook';
include 'config.php';
include 'functions.php';

$curTime_H = (int)date('H');
$curTime_S = (int)date('i');

//echo $curTime; exit;
$time_trig = array(1);
if($curTime_S<5 || ($curTime_S>25 && $curTime_S<35)) { array_push($time_trig,2); }
if($curTime_S<5) { array_push($time_trig,3); }
if(($curTime_H == 0 || $curTime_H % 2 == 0) && $curTime_S<5) { array_push($time_trig,4); }
if(($curTime_H == 0 || $curTime_H % 3 == 0) && $curTime_S<5) { array_push($time_trig,5); }
if($curTime_H == 0 || $curTime_H == 6 || $curTime_H == 12 || $curTime_H == 18) { array_push($time_trig,6); }
if($curTime_H == 0 || $curTime_H == 12) { array_push($time_trig,7); }
if($curTime_H == 12) { array_push($time_trig,8); }

$qryTrig = 'AND check_rep IN ('.implode(',',$time_trig).')';

$post_arr = ['status' => 'PAUSED','access_token' => $access_token];
$graph_url  = "https://graph.facebook.com/".$api_ver."/";

require_once "/home/digitalb2k/public_html/byt.ink/DataSource.php";
$query = "INSERT INTO tbl_url (short_url_hash, original_url) VALUES (?, ?)";
$paramType = "ss";
$ds = new DataSource();

$rep_time = 'Rep. Time: '.date("d-m-Y h:i a");

$fb_qry_str[3] = '&filter_set=SEARCH_BY_ADGROUP_IDS-STRING_SET%1EANY%1E';
$fb_qry_str[2] = '&filter_set=SEARCH_BY_CAMPAIGN_IDS-STRING_SET%1EANY%1E';
$fb_qry_str[1] = '&filter_set=SEARCH_BY_CAMPAIGN_GROUP_IDS-STRING_SET%1EANY%1E';

$fb_url_ad = $fb_url_adset = $fb_url_camp = $dt_qry = $dt_range = array();
//echo "SELECT * FROM ad_rules WHERE activate='yes' AND del_admin='no' $qryTrig order by tbl_id desc limit 0,20"; exit;
//$sqlRev=mysqli_query($conn, "SELECT * FROM ad_rules WHERE activate='yes' AND del_admin='no' $qryTrig order by tbl_id desc limit 0,20");
$sqlRev=mysqli_query($conn, "SELECT * FROM ad_rules WHERE tbl_id=8");
while($row=mysqli_fetch_assoc($sqlRev)){
    $tbl = $row['tbl_id'];
    $cust_conv_check_db[$tbl] = $cust_conv_ids_db[$tbl] = array();
    $ad_acc[$tbl] = $row['ad_acc'];
    $rule_name[$tbl] = $row['rule_name'];
    $rep_dt[$tbl] = $check_rep_dt[$row['check_rep']];
    $ad_level_db[$tbl] = unserialize($row['ad_level']);
    $rule_match_db[$tbl] = unserialize($row['rule_match']);
    $name_cont1_db[$tbl] = unserialize($row['name_cont1']);
    $name_cont2_db[$tbl] = unserialize($row['name_cont2']);
    $act_metrics_db[$tbl] = unserialize($row['act_metrics']);
    $act_oper_db[$tbl] = unserialize($row['act_oper']);
    $act_val_db[$tbl] = unserialize($row['act_val']);
    $wa_alert_db[$tbl] = $row['wa_alert'];
    $email_alert_db[$tbl] = $row['email_alert'];
    $ad_acc_type[$tbl] = $row['acc_type'];
    if($row['cust_conv_check']!='') { $cust_conv_check_db[$tbl] = unserialize($row['cust_conv_check']); }
    if($row['cust_conv_ids']!='') { $cust_conv_ids_db[$tbl] = unserialize($row['cust_conv_ids']); }

    $dt_qry[$tbl] = '&date='.date("Y-m-d", strtotime($rep_dt[$tbl][1])).'_'.date('Y-m-d',strtotime($rep_dt[$tbl][0]));
    $dt_range[$tbl] = date("d-m-Y", strtotime($rep_dt[$tbl][1])).' to '.date('Y-m-d',strtotime($rep_dt[$tbl][0]));

    $fb_link[$tbl][3] = 'https://adsmanager.facebook.com/adsmanager/manage/ads?act='.$row['ad_acc'];
    $fb_link[$tbl][2] = 'https://adsmanager.facebook.com/adsmanager/manage/adsets?act='.$row['ad_acc'];
    $fb_link[$tbl][1] = 'https://adsmanager.facebook.com/adsmanager/manage/campaigns?act='.$row['ad_acc'];
}

d($cust_conv_ids_db); //exit; d($act_metrics_db); d($act_oper_db); //exit;


$fb_data = array();
foreach($ad_acc as $k => $val) 
{       
        $ad_active = $as_active = $camp_active = array();
        $act_ads_camp_ids = $act_ads_adset_ids = $pause_ids = array();  
        $qryparam ="";

        if($ad_acc_type[$k]=='') { $ad_acc_type[$k]=1; }
        if($ad_acc_type[$k]==4) { $qryparam .= ',purchase_roas'; }

        if($ad_acc_type[$k]==2) { //Coaching
            //$qryparam .= ",action_values&filtering=[{'field':'objective','operator':'IN','value':[".$cam_obj[$ad_acc_type[$k]]."]},{'field':'action_type','operator':'IN','value':
            $qryparam .= ",action_values&filtering=[{'field':'action_type','operator':'IN','value':['offsite_conversion.fb_pixel_purchase','purchase'"; 
            if(count($cust_conv_ids_db[$k])>0) {
                foreach($cust_conv_ids_db[$k] as $k_a => $v_a) {
                    $qryparam .= ",'offsite_conversion.custom.".$v_a."'";
                }
            }
            $qryparam .= "]}]";
        } else {
            //$qryparam .="&filtering=[{'field':'objective','operator':'IN','value':[".$cam_obj[$ad_acc_type[$k]]."]}]";
        }

        $url = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=ad&fields=ad_id,adset_id,campaign_id,campaign_name,spend,objective,actions".$qryparam."&time_range[since]=".$rep_dt[$k][1]."&time_range[until]=".$rep_dt[$k][0]."&access_token=".$access_token."&limit=750"; //exit;
        $req = file_get_contents_curl($url); 
        $res = json_decode($req, true);  
        if(isset($res['data'])){
            $fb_data[$k] = $res['data'];
        }
        //d($fb_data);
        if(isset($fb_data[$k]) && count($fb_data[$k])>0) 
        {
            
            $idsToFilter = $cam_obj[$ad_acc_type[$k]];
            
            $filteredData = array_filter($fb_data[$k], function($item) use ($idsToFilter) {
                return in_array($item['objective'], $idsToFilter);
            });
            
            //d($filteredData); exit;
            foreach($filteredData as $k_fb => $v_fb) {
                $lead = $cpl = $pur_roas = $cust_conv = $purchase =  0; 
                //$cust_conv = array();

                if($ad_acc_type[$k]==2) {
                    if(isset($v_fb['action_values'])) { $purchase = LeadGen($v_fb['action_values'], 'purchase'); }
                    if(count($cust_conv_ids_db[$k])>0 && isset($v_fb['action_values'])) { $cust_conv = LeadGen($v_fb['action_values'], $cust_conv_ids_db[$k][0]); }
                }
                if($ad_acc_type[$k]==4) {
                    if(isset($v_fb['purchase_roas'])) {  $pur_roas = LeadGen($v_fb['purchase_roas'], 'omni_purchase'); } 
                }
                if(isset($v_fb['actions'])) { if(array_key_exists($v_fb['objective'], $obj_arr)) { $lead = LeadGen($v_fb['actions'], $obj_arr[$v_fb['objective']]); } }
    
                if(isset($lead) && $lead>0 && ($lead !='-' || $lead !=0)) $cpl = @($v_fb['spend']/$lead);
                //$lead_tot[$k][] = $lead;
                //$spend_tot[$k][] = $v_fb['spend'];
                
                $camp_data_ri[$k][$v_fb['campaign_id']][] = array('spend'=> $v_fb['spend'], 'lead'=> $lead, 'camp_name'=>$v_fb['objective'], 'obj'=>$v_fb['objective'], 'cust_conv'=>$cust_conv, 'purchase'=>$purchase, 'pur_roas'=>$pur_roas);
                $adset_data_ri[$k][$v_fb['adset_id']][] = array('spend'=>$v_fb['spend'], 'lead'=> $lead, 'camp_name'=>$v_fb['campaign_name'], 'obj'=>$v_fb['objective'], 'cust_conv'=>$cust_conv, 'purchase'=>$purchase, 'pur_roas'=>$pur_roas);
                $ad_data_ri[$k][$v_fb['ad_id']][] = array('spend'=>$v_fb['spend'], 'lead'=> $lead, 'camp_name'=>$v_fb['campaign_name'], 'obj'=>$v_fb['objective'], 'cust_conv'=>$cust_conv, 'purchase'=>$purchase, 'pur_roas'=>$pur_roas);
            }
            
            //d($camp_data_ri); exit;
            $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$val."/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750"; //exit;
            $req_ad = file_get_contents_curl($url_ad);
            $res_ad = json_decode($req_ad, true); 
            if(isset($res_ad['data']) && count($res_ad['data'])>0){
                foreach($res_ad['data'] as $kk => $vv) {
                    if($vv['effective_status']=='ACTIVE'){
                        $act_ads_camp_ids[$k][] = $vv['campaign_id'];
                    }
                }
            }
            
            if(isset($act_ads_camp_ids[$k]) && count($act_ads_camp_ids[$k])>0){
                
                $act_ads_camp_ids_unique = array_unique($act_ads_camp_ids[$k]);
                $x = implode(',',array_unique($act_ads_camp_ids_unique, SORT_REGULAR)); 
                //d($act_ads_camp_ids); exit;
                $url2 = "https://graph.facebook.com/".$api_ver."/act_".$val."/campaigns?fields=id,name,effective_status,adsets.limit(50){id,effective_status,ads.limit(50){adset_id,effective_status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']},{'field':'campaign.id','operator':'IN','value':[".$x."]}]&access_token=".$access_token."&limit=750";
                $req2 = file_get_contents_curl($url2);
                $res2 = json_decode($req2, true); 
                //d($res2);

                if(isset($res2['data']) && count($res2['data'])>0){
                    foreach($res2['data'] as $k1 => $v1) {
            
                        if($v1['effective_status']=='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids_unique)) { 
                            $camp_act = 'n';
                            if(isset($v1['adsets']['data'])){
                                
                                foreach($v1['adsets']['data'] as $as_k => $as_v) {
                                    $ads_act = 'n';
                                    if($as_v['effective_status']=='ACTIVE'){
                                        if(isset($as_v['ads']['data'])){
                                            foreach($as_v['ads']['data'] as $ad_k => $ad_v) {
                                                if($ad_v['effective_status']=='ACTIVE'){
                                                    $ads_act = $camp_act = 'y';
                                                    $ad_active[$k][] = $ad_v['id'];
                                                    $as_active[$k][] = $as_v['id'];
                                                    $camp_active[$k][] = $v1['id'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } 
                }

            }


            if(isset($ad_level_db[$k]) && count($ad_level_db[$k])>0){
                foreach($ad_level_db[$k] as $k3 => $v3) {
                    if(isset($act_metrics_db[$k][$k3]) && count($act_metrics_db[$k][$k3])>0){
                            $match = $rule_match_db[$k][$k3];
                            $name_cont = $name_cont1_db[$k][$k3];
                            $name_cont_not = $name_cont2_db[$k][$k3];
                            
                            //Campaign Level
                            $rep_assign = $active_ids = array();
                            if($v3==1 && isset($camp_data_ri[$k]) && count($camp_data_ri[$k])>0) {
                                $rep_assign = $camp_data_ri[$k]; $active_ids = $camp_active[$k];
                            } else if($v3==2 && isset($adset_data_ri[$k]) && count($adset_data_ri[$k])>0) {
                                $rep_assign = $adset_data_ri[$k]; $active_ids = $as_active[$k];
                            } else if($v3==3 && isset($ad_data_ri[$k]) && count($ad_data_ri[$k])>0) {
                                $rep_assign = $ad_data_ri[$k]; $active_ids = $ad_active[$k];
                            }

                            d($rep_assign); d($active_ids); exit;

                            if(count($rep_assign)>0) {
                                foreach($rep_assign as $k5 => $v5){
                                    if(in_array($k5, $active_ids)){

                                        $cam_name = $v5[0]['camp_name']; $allow = 'yes';
                                        $cam_obj = $v5[0]['obj']; 

                                        if ($name_cont !== '' && !str_contains(strtolower($cam_name), strtolower($name_cont))) { $allow = 'no'; }
                                        if ($name_cont_not !== '' && str_contains(strtolower($cam_name), strtolower($name_cont_not))) { $allow = 'no'; }

                                        if($allow=='yes'){

                                            $spend = $lead = $cpl = 0;
                                            
                                            $spend = array_sum(array_column($v5,'spend'));
                                            $lead = array_sum(array_column($v5,'lead'));

                                            if($spend>0 && $lead>0) { $cpl = @($spend / $lead); } 
    
                                            $jj=0; $numItems = count($act_metrics_db[$k][$k3]);
                                            $matched = array();
                                            foreach($act_metrics_db[$k][$k3] as $k4 => $v4) {

                                                if($v4=='spend') { $val_1= $spend; }
                                                if($v4=='leadgen_grouped') { $val_1= $lead; }
                                                if($v4=='cpl') { $val_1= $cpl; }
                                                $val_2 = $act_val_db[$k][$k3][$k4];
                                                $opr = $act_oper_db[$k][$k3][$k4];
                                                if(criteriaMet($val_1, $opr, $val_2)){ $matched[] = 'yes'; }
                                                if(++$jj === $numItems && count($matched)>0) {
                                                    $matched_tot = array_count_values($matched);
                                                    $match_found = 'no';
                                                    if($match==1 && isset($matched_tot['yes']) && $matched_tot['yes']==$numItems){
                                                        $match_found = 'yes';
                                                    } else if($match==2 && isset($matched_tot['yes']) && $matched_tot['yes']>0){
                                                        $match_found = 'yes';
                                                    }
                                                    if($match_found == 'yes'){
                                                        $pause_ids[$k][$k3][] = array($k5, $spend, $lead, $cpl, $matched_tot, $cam_name);
                                                    }
                                                }
                                            }
                                        }
                                        
                                    }
                                }
                            }
                        }
                }
            }
            
            //d($pause_ids[$k]);
            
            if(isset($pause_ids[$k]) && count($pause_ids[$k])>0){
                $m = 0; $body_wa = ''; $rep_gen = 'no';
                foreach($pause_ids[$k] as $k6 => $v6) {
                    //d($ad_level_db); 
                    $adlevel = $ad_level_db[$k][$k6]; 
                    if($rule_match_db[$k][$k6]==1) { $match_sym =' & '; } else { $match_sym =' OR '; }
                    
                    $body_wa .= '\\n'.$ad_level[$adlevel].' : '; 
                    $n=0; $numItems = count($act_metrics_db[$k][$k6]);
                    foreach($act_metrics_db[$k][$k6] as $k7 => $v7) {
                        $body_wa .= $metrics[$v7].' '.$act_oper_db[$k][$k6][$k7].' '.$act_val_db[$k][$k6][$k7]; 
                        if(++$n != $numItems) { $body_wa .= $match_sym; }
                    }
                    $body_wa .= '\\n';
                    $j = 1;
                    foreach($v6 as $k8 => $v8) { 
                        //$body_wa .= $v8[0].'<br>';
                        $link_ad = $fb_link[$k][$adlevel].''.$fb_qry_str[$adlevel].'["'.$v8[0].'"]'.$dt_qry[$k];
                        $short_code = getName($num=5);
                        $paramValueArray = array($short_code, urlencode($link_ad));
                        $ds->insert($query, $paramType, $paramValueArray);

                        //$ad_ids_cpl[$k_p][] = $j.'. https://byt.ink/'.$short_code.' - '.$v8[5]; 
                        $body_wa .= $j.'. byt.ink/'.$short_code; 
                        $body_wa .= '\\n';
                        $j++;
                        $rep_gen = 'yes';
                    }
                }
               
                if($rep_gen == 'yes'){
                    $subj_1 = $head_str = $rule_name[$k].' : Pause Ads : Alert';
                    $body_text = $body_wa;
                    $body_text = str_replace('<br>', '\\n', $body_text);
                    //$to_address = "prabhu@bytindia.com";
                    //include $dirPath.'email/pause-mail.php';

                    $phone = array('9176299010'); 
                    foreach($phone as $key => $v) 
                    {	
                                $phNo= trim($v);
                                sendWhatsapp($access_token, $phNo, $head_str, $body_wa, $dt_range[$k].''.'\\n'.$rep_time); //exit;
                    }
                    //exit;
                }
                //echo $body_wa; exit;
            } 
        }
}
exit;
exit;