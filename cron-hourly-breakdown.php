<?php
/**
 * Cron: Hourly Breakdown - 0 Lead Alert
 * Run every 3 hours: 0 0,3,6,9,12,15,18,21 * * * (minute 0 of hours 0,3,6,9,12,15,18,21)
 * Logic: Check last 5h first. If 0 leads in 5h, skip. If has leads in 5h, check last 3h - alert if spend>0 and 0 leads in 3h.
 */
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'functions-report.php';
ini_set('memory_limit', '2048M');

function curl_get_file_contents($URL) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    curl_close($c);
    return $contents ? $contents : false;
}

function LeadGen($arr, $filt) {
    $r = 0;
    if ((is_array($arr) || is_object($arr)) && count($arr) > 0) {
        for ($q = 0; $q < count($arr); $q++) {
            if (isset($arr[$q]['action_type']) && $arr[$q]['action_type'] == $filt) {
                $r = $arr[$q]['value'];
            }
        }
    }
    return $r;
}

// Get user access token (uid=2, same as cron-budget3)
$query = "SELECT tbl_id, access_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
if (!$row || empty($row['access_token'])) {
    exit('Access token not found');
}
$access_token = $row['access_token'];
$uId = $row['tbl_id'];

// Get clients with Meta ad accounts only (no Google)
$filtering = [];
$filtering[] = [
    'field' => 'campaign.objective',
    'operator' => 'IN',
    'value' => ['LEAD_GENERATION', 'OUTCOME_LEADS']
];
$filtering_param = '&filtering=' . urlencode(json_encode($filtering));

$sqlRev = mysqli_query($conn, "SELECT tbl_id, client_name, fb_id FROM budget_reminder WHERE uid='" . $uId . "' AND delete_status=0 AND hide_temp='0' AND fb_id != '' AND fb_id IS NOT NULL");

while ($sqlROW = mysqli_fetch_assoc($sqlRev)) {
    $client_name = mysqli_real_escape_string($conn, $sqlROW['client_name']);
    $fbIds = array_map('trim', explode(',', $sqlROW['fb_id']));

    foreach ($fbIds as $fbId) {
        $fbId = trim($fbId);
        if (empty($fbId)) continue;

        $request_url = 'https://graph.facebook.com/' . $api_ver . '/act_' . $fbId . '/insights?level=account&breakdowns=hourly_stats_aggregated_by_advertiser_time_zone&fields=spend,objective,actions' . $filtering_param . '&date_preset=today&access_token=' . $access_token;

        $requests = curl_get_file_contents($request_url);
        $fb_response = json_decode($requests, true);

        if (!isset($fb_response['data']) || !is_array($fb_response['data'])) {
            continue;
        }

        $data = $fb_response['data'];
        $last5hours = array_slice($data, -5);
        $last3hours = array_slice($data, -3);

        $spend_5h = $leads_5h = 0;
        foreach ($last5hours as $val) {
            $spend_5h += isset($val['spend']) ? (float)$val['spend'] : 0;
            $leads_5h += isset($val['actions']) ? (int)LeadGen($val['actions'], 'lead') : 0;
        }

        // Case 1: Last 5h has spend but 0 leads -> insert with 5h message
        if ($spend_5h > 0 && $leads_5h == 0) {
            $spend_formatted = $fmt->format(round($spend_5h));
            $notify_msg = '0 Leads, ₹' . $spend_formatted . ' Spent in last 5 Hours';
            mysqli_query($conn, "INSERT INTO notification_alert (uId, client_name, notify_type, notify_msg, created) VALUES ('" . $uId . "', '" . $client_name . "', '0 Lead', '" . mysqli_real_escape_string($conn, $notify_msg) . "', NOW())") or die(mysqli_error($conn));
            continue;
        }

        // Case 2: Last 5h has leads, check last 3h -> if spend>0 and 0 leads in 3h, insert with 3h message
        $spend_3h = $leads_3h = 0;
        foreach ($last3hours as $val) {
            $spend_3h += isset($val['spend']) ? (float)$val['spend'] : 0;
            $leads_3h += isset($val['actions']) ? (int)LeadGen($val['actions'], 'lead') : 0;
        }
        if ($spend_3h > 0 && $leads_3h == 0) {
            $spend_formatted = $fmt->format(round($spend_3h));
            $notify_msg = '0 Leads, ₹' . $spend_formatted . ' Spent in last 3 Hours';
            mysqli_query($conn, "INSERT INTO notification_alert (uId, client_name, notify_type, notify_msg, created) VALUES ('" . $uId . "', '" . $client_name . "', '0 Lead', '" . mysqli_real_escape_string($conn, $notify_msg) . "', NOW())") or die(mysqli_error($conn));
        }
    }
}

echo 'success';
