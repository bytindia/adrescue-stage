<?php
// Hourly zero-spend detector for Meta and Google ad accounts
// - Reads accounts from budget_reminder (fb_id, g_id)
// - Snapshots today's cumulative spend per account/platform
// - Compares against previous snapshot to find no-spend-in-period accounts

session_start();
date_default_timezone_set('Asia/Kolkata');

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Debug logging for cron environment
error_log("=== CRON DEBUG START ===");
error_log("Current working directory: " . getcwd());
error_log("Script directory (__DIR__): " . __DIR__);
error_log("Script file: " . __FILE__);
error_log("PHP version: " . PHP_VERSION);
error_log("ActiveAccGoogle file exists: " . (file_exists(__DIR__ . '/active-acc-google.php') ? 'YES' : 'NO'));
error_log("Google autoloader exists: " . (file_exists(__DIR__ . '/google-ads-v15/vendor/autoload.php') ? 'YES' : 'NO'));

include __DIR__ .'/db.php';
include __DIR__ .'/config.php';
require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';
include __DIR__ . '/active-acc-google.php';

// Ensure snapshot table exists
$createSql = "CREATE TABLE IF NOT EXISTS active_acc_snapshots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NULL,
  client_name VARCHAR(255) NULL,
  platform ENUM('Meta','Google') NOT NULL,
  account_id VARCHAR(64) NOT NULL,
  spend DECIMAL(18,4) NOT NULL DEFAULT 0,
  act_camp INT NOT NULL DEFAULT 0,
  snapshot_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_acc_platform (account_id, platform),
  KEY idx_acc_platform_time (account_id, platform, snapshot_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $createSql);

// Build account-id to name maps (for Meta and Google)
$fbAccN = $gAccN = array();
$resFbNames = mysqli_query($conn, "SELECT account_id, name FROM adAccounts WHERE uid='".$_SESSION['uid']."'");
while ($rfb = mysqli_fetch_assoc($resFbNames)) { $fbAccN[$rfb['account_id']] = $rfb['name']; }
$resGNames = mysqli_query($conn, "SELECT account_id, name FROM gaccounts WHERE uid='".$_SESSION['uid']."'");
while ($rg = mysqli_fetch_assoc($resGNames)) { $gAccN[$rg['account_id']] = $rg['name']; }

// Utility: parse comma-separated account ids, trim, dedupe, filter empties
function parseIds($csv)
{
    $out = array();
    foreach (explode(',', (string)$csv) as $id) {
        $id = trim($id);
        if ($id !== '') { $out[$id] = true; }
    }
    return array_keys($out);
}

// Helper: safe curl
function curl_get_file_contents_local($URL)
{
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    curl_close($c);
    if ($contents) return $contents; else return FALSE;
}

// Helper function to loop through Facebook API pagination
function loopAdRep($url) {
    global $output;
    $requests = file_get_contents_curl($url);
    $fb_response = json_decode($requests, true);
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

// Function to get active campaigns count for Facebook account
function getActiveCampaignsCountFB($fbId, $access_token, $api_ver) {
    // Build filtering for active ads (no campaign name filters)
    $filtering = [
        [
            "field" => "ad.effective_status",
            "operator" => "IN",
            "value" => ["ACTIVE"]
        ]
    ];
    $filtering_param = '&filtering=' . urlencode(json_encode($filtering));

    // Get active ad campaigns
    $url_ad = "https://graph.facebook.com/$api_ver/act_$fbId/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id$filtering_param&access_token=$access_token&limit=750";
    $output = [];
    loopAdRep($url_ad);
    $res_ad = $output;
    $act_ads_camp_ids = [];
    if(isset($res_ad) && count($res_ad)>0) {
        foreach($res_ad as $vv) {
            if(isset($vv['effective_status']) && $vv['effective_status']=='ACTIVE'){
                $act_ads_camp_ids[] = $vv['campaign_id'];
            }
        }
    }
    $act_ads_camp_ids = array_unique($act_ads_camp_ids);
    
    // Get campaign details to count active campaigns (no campaign name filters)
    $filtering_camp = [
        [
            "field" => "campaign.effective_status",
            "operator" => "IN",
            "value" => ["ACTIVE"]
        ]
    ];
    $filtering_param_camp = '&filtering=' . urlencode(json_encode($filtering_camp));

    $url = "https://graph.facebook.com/$api_ver/act_$fbId/campaigns?fields=id,name,effective_status,end_time$filtering_param_camp&access_token=$access_token&limit=750";
    $output = [];
    loopAdRep($url);
    $res = $output;
    
    $activeCampaigns = 0;
    if(isset($res) && count($res)>0){ 
        foreach($res as $v1) {
            $camp_act = 'y';
            if(isset($v1['end_time'])) { 
                $end_time = new DateTime($v1['end_time']);
                $currentDateTime = new DateTime('now', $end_time->getTimezone());
                if($end_time < $currentDateTime) { $camp_act = 'n'; }
            }
            if(isset($v1['effective_status']) && $v1['effective_status']=='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act=='y') {  
                $activeCampaigns++;
            }
        }
    }
    
    return $activeCampaigns;
}

// Function to get active campaigns count for Google account
function getActiveCampaignsCountGoogle($gId, $g_refresh_token, $g_mcc) {
    try {
        error_log("getActiveCampaignsCountGoogle called for account: " . $gId);
        if (!class_exists('ActiveAccGoogle')) {
            error_log("ERROR: ActiveAccGoogle class not found!");
            return 0;
        }
        
        $activeCampaigns = ActiveAccGoogle::getActiveCampaignsCount($gId, $g_refresh_token, $g_mcc);
        error_log("Active campaigns count: " . $activeCampaigns);
        return $activeCampaigns;
    } catch (Throwable $e) {
        error_log("Error in getActiveCampaignsCountGoogle: " . $e->getMessage());
        return 0;
    }
}

// Load user context (default to uid=2 for cron if not present)
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 2;
}

// Date range (today cumulative)
$todayYmd = date('Y-m-d');
$dtRange = 'time_range[since]='.$todayYmd.'&time_range[until]='.$todayYmd;
$nowTs = date('Y-m-d H:i:s');
$currentHour = date('H');
$can_send = ($currentHour !== '00');

// Collect zero-spend list rows (grouped by account to avoid duplicates)
$zeroRows = array();
$accountClientMap = array(); // Track which clients are associated with each account

// Fetch accounts from budget_reminder
$q = mysqli_query($conn, "SELECT tbl_id, client_name, fb_id, g_id FROM budget_reminder WHERE uid='".$_SESSION['uid']."' AND delete_status=0 AND hide_temp='0'");

// Google helper may be needed
$hasGoogle = false;
if ($q && mysqli_num_rows($q) > 0) {
    // Check availability of Google credentials
    $gRefresh = isset($_SESSION['g_refresh_token']) ? $_SESSION['g_refresh_token'] : '';
    $gMcc = isset($_SESSION['g_mcc']) ? $_SESSION['g_mcc'] : '';
    if ($gRefresh !== '' && $gMcc !== '') {
        if (file_exists(__DIR__ . '/google-ads.php')) {
            include_once 'google-ads.php';
            $hasGoogle = true;
        }
    }
}

// Collect all account IDs for batch processing
$fb_ids_all = array();
$g_ids_all = array();
$client_account_map = array(); // Map account_id to array of client info (multiple clients per account)

// First pass: collect all account IDs
while ($row = mysqli_fetch_assoc($q)) {
    $clientId = (int)$row['tbl_id'];
    $clientName = $row['client_name'];
    
    // Collect Facebook account IDs
    if (!empty($row['fb_id'])) {
        $fbIds = parseIds($row['fb_id']);
        foreach ($fbIds as $fid) {
            // Add to unique Facebook IDs array (avoid duplicates)
            if (!in_array($fid, $fb_ids_all)) {
                $fb_ids_all[] = $fid;
            }
            
            // Track multiple clients per account
            if (!isset($client_account_map[$fid])) {
                $client_account_map[$fid] = array();
            }
            $client_account_map[$fid][] = array(
                'client_id' => $clientId,
                'client_name' => $clientName,
                'platform' => 'Meta'
            );
        }
    }
    
    // Collect Google account IDs
    if (!empty($row['g_id']) && $hasGoogle) {
        $gIds = parseIds($row['g_id']);
        foreach ($gIds as $gId) {
            // Add to unique Google IDs array (avoid duplicates)
            if (!in_array($gId, $g_ids_all)) {
                $g_ids_all[] = $gId;
            }
            
            // Track multiple clients per account
            if (!isset($client_account_map[$gId])) {
                $client_account_map[$gId] = array();
            }
            $client_account_map[$gId][] = array(
                'client_id' => $clientId,
                'client_name' => $clientName,
                'platform' => 'Google'
            );
        }
    }
}

// Reset query result for potential reuse
mysqli_data_seek($q, 0);

// --- Process Facebook accounts in batch ---
if (count($fb_ids_all) > 0) {
    // Build batch request for all Facebook accounts
    $batch = array();
    foreach ($fb_ids_all as $fid) {
        $batch[] = array(
            'method' => 'GET',
            'relative_url' => 'act_'.$fid.'/insights?fields=spend&'.$dtRange
        );
    }
    
    // Send single batch request for all Facebook accounts
    $graphUrl = 'https://graph.facebook.com/'.$api_ver.'/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graphUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'access_token' => $access_token,
        'batch' => json_encode($batch)
    )));
    $response = curl_exec($ch);
    curl_close($ch);
    $results = json_decode($response, true);

    // Parse results and update database
    foreach ($fb_ids_all as $i => $fid) {
        $spend = 0.0;
        if (is_array($results) && isset($results[$i]['body'])) {
            $body = json_decode($results[$i]['body'], true);
            if (isset($body['data'][0]['spend'])) {
                $spend = (float)$body['data'][0]['spend'];
            }
        }

        // Handle multiple clients per account
        $clientInfos = $client_account_map[$fid];
        
        // Read previous stored spend (one row per account+platform)
        $sel = $conn->prepare("SELECT spend, act_camp FROM active_acc_snapshots WHERE account_id=? AND platform='Meta' LIMIT 1");
        $sel->bind_param('s', $fid);
        $sel->execute();
        $selRes = $sel->get_result();
        
        if ($selRes && $selRes->num_rows > 0) {
            $prev = $selRes->fetch_assoc();
            $isZeroSpend = (float)$spend <= (float)$prev['spend'];
            
            // Only check active campaigns if account has zero spending
            $activeCampaigns = 0;
            if ($isZeroSpend) {
                $activeCampaigns = getActiveCampaignsCountFB($fid, $access_token, $api_ver);
                
                // Only flag as issue if account has active campaigns but no spending
                if ($activeCampaigns > 0) {
                    $accName = isset($fbAccN[$fid]) ? $fbAccN[$fid] : '';
                    $accountKey = $fid . '_Meta';
                    
                    // Only add if we haven't already added this account
                    if (!isset($accountClientMap[$accountKey])) {
                        $clientNames = array();
                        foreach ($clientInfos as $clientInfo) {
                            $clientNames[] = $clientInfo['client_name'];
                        }
                        
                        $zeroRows[] = array(
                            'client' => implode(', ', $clientNames), // Show all clients for this account
                            'acc_name' => $accName, 
                            'acc' => $fid, 
                            'platform' => 'Meta',
                            'active_campaigns' => $activeCampaigns
                        );
                        
                        $accountClientMap[$accountKey] = true;
                    }
                }
            } else {
                // If account is spending, keep the previous active campaigns count
                $activeCampaigns = $prev['act_camp'];
            }
            
            // Update existing row (use first client's info for the snapshot record)
            $firstClient = $clientInfos[0];
            $upd = $conn->prepare("UPDATE active_acc_snapshots SET client_id=?, client_name=?, spend=?, act_camp=?, snapshot_time=? WHERE account_id=? AND platform='Meta'");
            $upd->bind_param('isdisi', $firstClient['client_id'], $firstClient['client_name'], $spend, $activeCampaigns, $nowTs, $fid);
            $upd->execute();
        } else {
            // First snapshot for this account (use first client's info)
            $firstClient = $clientInfos[0];
            $ins = $conn->prepare("INSERT INTO active_acc_snapshots (client_id, client_name, platform, account_id, spend, act_camp, snapshot_time) VALUES (?, ?, 'Meta', ?, ?, ?, ?)");
            $activeCampaigns = 0; // Default for first snapshot
            $ins->bind_param('issdis', $firstClient['client_id'], $firstClient['client_name'], $fid, $spend, $activeCampaigns, $nowTs);
            $ins->execute();
        }
    }
}

// --- Process Google accounts ---
error_log("Google accounts to process: " . count($g_ids_all));
error_log("Has Google credentials: " . ($hasGoogle ? 'YES' : 'NO'));
error_log("ActiveAccGoogle class exists: " . (class_exists('ActiveAccGoogle') ? 'YES' : 'NO'));

if (count($g_ids_all) > 0 && $hasGoogle) {
    foreach ($g_ids_all as $gId) {
        error_log("Processing Google account: " . $gId);
        // Use ActiveAccGoogle to get cost for today
        $gSpend = 0.0;
        try {
            if (class_exists('ActiveAccGoogle')) {
                $rep = ActiveAccGoogle::getSpendData($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gId, $todayYmd, $todayYmd);
                if (is_array($rep) && isset($rep['cost'])) {
                    $gSpend = (float)$rep['cost'];
                }
            }
        } catch (Throwable $e) {
            error_log("Error getting Google spend data: " . $e->getMessage());
        }

        // Handle multiple clients per account
        $clientInfos = $client_account_map[$gId];

        // Read previous stored spend
        $sel2 = $conn->prepare("SELECT spend, act_camp FROM active_acc_snapshots WHERE account_id=? AND platform='Google' LIMIT 1");
        $sel2->bind_param('s', $gId);
        $sel2->execute();
        $selRes2 = $sel2->get_result();
        
        if ($selRes2 && $selRes2->num_rows > 0) {
            $prev2 = $selRes2->fetch_assoc();
            $isZeroSpend = (float)$gSpend <= (float)$prev2['spend'];
            
            // Only check active campaigns if account has zero spending
            $activeCampaigns = 0;
            if ($isZeroSpend) {
                error_log("Getting active campaigns for Google account: " . $gId);
                $activeCampaigns = getActiveCampaignsCountGoogle($gId, $_SESSION['g_refresh_token'], $_SESSION['g_mcc']);
                error_log("Active campaigns count: " . $activeCampaigns);
                
                // Only flag as issue if account has active campaigns but no spending
                if ($activeCampaigns > 0) {
                    $accName2 = isset($gAccN[$gId]) ? $gAccN[$gId] : '';
                    $accountKey = $gId . '_Google';
                    
                    // Only add if we haven't already added this account
                    if (!isset($accountClientMap[$accountKey])) {
                        $clientNames = array();
                        foreach ($clientInfos as $clientInfo) {
                            $clientNames[] = $clientInfo['client_name'];
                        }
                        
                        $zeroRows[] = array(
                            'client' => implode(', ', $clientNames), // Show all clients for this account
                            'acc_name' => $accName2, 
                            'acc' => $gId, 
                            'platform' => 'Google',
                            'active_campaigns' => $activeCampaigns
                        );
                        
                        $accountClientMap[$accountKey] = true;
                    }
                }
            } else {
                // If account is spending, keep the previous active campaigns count
                $activeCampaigns = $prev2['act_camp'];
            }
            
            // Update existing row (use first client's info for the snapshot record)
            $firstClient = $clientInfos[0];
            $upd2 = $conn->prepare("UPDATE active_acc_snapshots SET client_id=?, client_name=?, spend=?, act_camp=?, snapshot_time=? WHERE account_id=? AND platform='Google'");
            $upd2->bind_param('isdisi', $firstClient['client_id'], $firstClient['client_name'], $gSpend, $activeCampaigns, $nowTs, $gId);
            $upd2->execute();
        } else {
            // First snapshot for this account (use first client's info)
            $firstClient = $clientInfos[0];
            $ins2 = $conn->prepare("INSERT INTO active_acc_snapshots (client_id, client_name, platform, account_id, spend, act_camp, snapshot_time) VALUES (?, ?, 'Google', ?, ?, ?, ?)");
            $activeCampaigns = 0; // Default for first snapshot
            $ins2->bind_param('issdis', $firstClient['client_id'], $firstClient['client_name'], $gId, $gSpend, $activeCampaigns, $nowTs);
            $ins2->execute();
        }
    }
}

// Build HTML table for email
$total_acc = count($zeroRows);
$sub = "⚠️ ".$total_acc." Ad Accounts Not Spending - Action Needed";
$upd = date('d-m-Y, h:i a');
$table = 'Hello,<br>
We have identified '.$total_acc.' ad accounts with active campaigns but no spending activity in the last 3 hours. 
 Please review these accounts at the earliest and take appropriate action.<br><br>
Last Updated: '.$upd.'<br/>';
$table .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:14px;">';
$table .= '<thead><tr style="background:#f2f2f2;">';
$table .= '<th align="left">Client name</th>';
$table .= '<th align="left">Account name</th>';
$table .= '<th align="left">Ad account</th>';
$table .= '<th align="left">Platform</th>';
$table .= '<th align="left">Active Campaigns</th>';
$table .= '</tr></thead><tbody>';
if ($total_acc > 0) {
    foreach ($zeroRows as $r) {
        $table .= '<tr>';
        $table .= '<td>'.htmlspecialchars($r['client']).'</td>';
        $table .= '<td>'.htmlspecialchars(isset($r['acc_name']) ? $r['acc_name'] : '').'</td>';
        // Build platform-specific dashboard link with today date
        $accIdEsc = htmlspecialchars($r['acc']);
        if ($r['platform'] === 'Meta') {
            $accLink = 'https://adsmanager.facebook.com/adsmanager/manage/campaigns?act='.$r['acc'].'&date=preset_today';
        } else if ($r['platform'] === 'Google') {
            $accLink = 'https://ads.google.com/aw/overview?__c='.$r['acc'].'&dur=today';
        } else {
            $accLink = '';
        }
        if ($accLink !== '') {
            $table .= '<td><a href="'.htmlspecialchars($accLink).'" target="_blank" rel="noopener noreferrer">'.$accIdEsc.'</a></td>';
        } else {
            $table .= '<td>'.$accIdEsc.'</td>';
        }
        $table .= '<td>'.htmlspecialchars($r['platform']).'</td>';
        $table .= '<td>'.htmlspecialchars(isset($r['active_campaigns']) ? $r['active_campaigns'] : '0').'</td>';
        $table .= '</tr>';
    }
} else {
    $table .= '<tr><td colspan="5" align="center">No zero-spend accounts with active campaigns in the last interval</td></tr>';
}
$table .= '</tbody></table>';

// Optional: echo for testing in browser/CLI
//echo $table;

if($can_send && $total_acc > 0){
    $subject = $sub;
    $message = $table;
    $to_address = 'prabhu@bytindia.com,ramesh@bytindia.com,mughil@bytindia.com,charan@bytindia.com,ads@bytindia.com,faheem@bytindia.com'; 
    $to_address = 'prabhu@bytindia.com';
    include 'email/mail-common.php';
}
?>


