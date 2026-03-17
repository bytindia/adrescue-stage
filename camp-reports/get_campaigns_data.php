<?php
// get active campaigns options for a given ad account
session_start();
include '../db.php';

if (isset($_GET['category'])) {
    $category = $_GET['category'];
    $options = [];
    $fbId = $category;

    function loopAdRep($url) {
        global $output;
        $requests = file_get_contents_curl($url);
        $fb_response = json_decode($requests, true);
        if (isset($output) && count($output) > 0 && isset($fb_response['data']) && count($fb_response['data']) > 0) {
            $output = array_merge($output, $fb_response['data']);
        } else if (isset($fb_response['data']) && count($fb_response['data']) > 0) {
            $output = $fb_response['data'];
        }
        if (isset($fb_response['paging']['next'])) {
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

    // collect campaign ids that currently have at least one ACTIVE ad
    $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
    $act_ads_camp_ids = $output = array();
    loopAdRep($url_ad);
    $res_ad = $output;
    if (isset($res_ad['data']) && count($res_ad['data']) > 0) {
        foreach ($res_ad['data'] as $kk => $vv) {
            if ($vv['effective_status'] == 'ACTIVE') {
                $act_ads_camp_ids[] = $vv['campaign_id'];
            }
        }
    }

    $output = $res = $campaignOptions = array();
    // fetch active campaigns
    $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=id,name,effective_status,end_time&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750";
    loopAdRep($url);
    $res = $output;
    if (isset($res['data']) && count($res['data']) > 0) {
        foreach ($res['data'] as $v1) {
            $camp_act = 'y';
            if (isset($v1['end_time'])) {
                $end_time = new DateTime($v1['end_time']);
                $currentDateTime = new DateTime('now', $end_time->getTimezone());
                if ($end_time < $currentDateTime) { $camp_act = 'n'; }
            }
            if ($v1['effective_status'] == 'ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act == 'y') {
                $campaignOptions[] = array('value' => $v1['id'], 'text' => mysqli_real_escape_string($conn, $v1['name']));
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($campaignOptions);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>


