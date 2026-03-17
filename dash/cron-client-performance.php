<?php
/**
 * Cron: Client Ads Performance Snapshot
 * Run nightly to store Meta and Google ads report for RE/Coach clients in client_performance_snapshot.
 * 
 * Setup: Run dash/client_performance_snapshot.sql first to create the table.
 * Cron: 0 2 * * * php /path/to/adrescue/dash/cron-client-performance.php
 */
@session_start();
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('CRON_UID', '2');
define('CRON_RUN', true);

include __DIR__ . '/../db.php';
include __DIR__ . '/overview-config.php';

// Google Ads - load from project root (google-campaigns.php may use server path; adjust if needed)
if (file_exists(__DIR__ . '/../google-ads-v15/vendor/autoload.php')) {
    require_once __DIR__ . '/../google-ads-v15/vendor/autoload.php';
}
require_once __DIR__ . '/google-campaigns.php';

// Create table if not exists (run client_performance_snapshot.sql for full schema with FK)
$createTable = "CREATE TABLE IF NOT EXISTS `client_performance_snapshot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_tbl_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `meta_spend` decimal(12,2) NOT NULL DEFAULT 0,
  `meta_lead` int(11) NOT NULL DEFAULT 0,
  `meta_purchase` int(11) NOT NULL DEFAULT 0,
  `google_spend` decimal(12,2) NOT NULL DEFAULT 0,
  `google_lead` int(11) NOT NULL DEFAULT 0,
  `google_purchase` decimal(12,2) NOT NULL DEFAULT 0,
  `total_spend` decimal(12,2) NOT NULL DEFAULT 0,
  `total_lead` int(11) NOT NULL DEFAULT 0,
  `total_purchase` decimal(12,2) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_account_period` (`account_tbl_id`, `period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $createTable);

// Fixed date range: this month
$d = new DateTime('first day of this month');
$d2 = new DateTime('today');
$periodStart = $d->format('Y-m-d');
$periodEnd   = $d2->format('Y-m-d');
$dtRange1 = $periodStart;
$dtRange2 = $periodEnd;

// Fetch clients: uid=2, delete_status=0, client_ty = RE or Coach
$query = "SELECT tbl_id, client_name, fb_id, g_id, proj_name, name_contain 
          FROM dashboard_accounts 
          WHERE  uid='" . mysqli_real_escape_string($conn, CRON_UID) . "' 
          AND delete_status=0 
          ORDER BY client_name ASC";
$clientsRes = mysqli_query($conn, $query);

if (!$clientsRes) {
    error_log('cron-client-performance: Failed to fetch clients - ' . mysqli_error($conn));
    echo "error: " . mysqli_error($conn);
    exit(1);
}

$obj_arr = array(
    'LEAD_GENERATION' => 'lead',
    'CONVERSIONS'     => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES'        => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS'   => 'lead',
    'OUTCOME_SALES'   => 'purchase',
    'PRODUCT_CATALOG_SALES' => 'purchase'
);

$processed = 0;
$errors = array();

while ($row = mysqli_fetch_assoc($clientsRes)) {
    $account_tbl_id = (int) $row['tbl_id'];
    
    // Per-client filter (proj_name, name_contain) - clients can have multiple ad accounts
    $proj_names = $name_contain = array();
    if (!empty($row['proj_name'])) {
        $proj_names = array_filter(unserialize($row['proj_name']));
    }
    if (!empty($row['name_contain'])) {
        $name_contain = array_filter(unserialize($row['name_contain']));
    }
    
    $fb_acc_ids = array_filter(array_map('trim', explode(',', $row['fb_id'] ?? '')));
    $g_acc_ids  = array_filter(array_map('trim', explode(',', $row['g_id'] ?? '')));
    
    $meta_spend = $meta_lead = $meta_purchase = 0;
    $google_spend = $google_lead = $google_purchase = 0;
    
    // ---- Meta (Facebook + Instagram combined) ----
    if (!empty($fb_acc_ids)) {
        $fb_data = array();
        foreach ($fb_acc_ids as $fb_id) {
            $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $fb_id . "/insights?level=campaign&breakdowns=publisher_platform&fields=campaign_id,campaign_name,spend,objective,actions&time_range[since]=" . $dtRange1 . "&time_range[until]=" . $dtRange2 . "&access_token=" . $access_token . "&limit=1750";
            $req = file_get_contents_curl($url);
            $res = json_decode($req, true);
            if (isset($res['data']) && is_array($res['data'])) {
                $fb_data[] = $res['data'];
            }
        }
        if (empty($fb_data) || (count($fb_data) == 1 && empty($fb_data[0]))) {
            $fb_data = array();
            foreach ($fb_acc_ids as $fb_id) {
                $url2 = "https://graph.facebook.com/" . $api_ver . "/act_" . $fb_id . "/insights?level=campaign&fields=campaign_id,campaign_name,spend,objective,actions&time_range[since]=" . $dtRange1 . "&time_range[until]=" . $dtRange2 . "&access_token=" . $access_token . "&limit=1750";
                $req2 = file_get_contents_curl($url2);
                $res2 = json_decode($req2, true);
                if (isset($res2['data']) && is_array($res2['data'])) {
                    $fb_data[] = $res2['data'];
                }
            }
        }
        
        $grouped_campaigns = array();
        foreach ($fb_data as $data) {
            foreach ($data as $campaign) {
                $campaign_id = isset($campaign['campaign_id']) ? $campaign['campaign_id'] : '';
                $campaign_name = isset($campaign['campaign_name']) ? $campaign['campaign_name'] : '';
                $should_include = true;
                
                if (!empty($proj_names) || !empty($name_contain)) {
                    $should_include = false;
                    foreach ($proj_names as $proj_name) {
                        if (stripos($campaign_name, $proj_name) !== false) {
                            $should_include = true;
                            break;
                        }
                    }
                    if (!$should_include && !empty($name_contain)) {
                        $str = strtolower(trim($campaign_name));
                        foreach ($name_contain as $a) {
                            if (stripos($str, strtolower(trim($a))) !== false) {
                                $should_include = true;
                                break;
                            }
                        }
                    }
                }
                
                if ($should_include && $campaign_id) {
                    if (!isset($grouped_campaigns[$campaign_id])) {
                        $grouped_campaigns[$campaign_id] = array(
                            'campaign_name' => $campaign_name,
                            'objective' => isset($campaign['objective']) ? $campaign['objective'] : '',
                            'platforms' => array()
                        );
                    }
                    $publisher = isset($campaign['publisher_platform']) ? $campaign['publisher_platform'] : 'facebook';
                    $grouped_campaigns[$campaign_id]['platforms'][$publisher] = array(
                        'spend' => floatval(isset($campaign['spend']) ? $campaign['spend'] : 0),
                        'actions' => isset($campaign['actions']) ? $campaign['actions'] : array()
                    );
                }
            }
        }
        
        if (!empty($grouped_campaigns)) {
            foreach ($grouped_campaigns as $campaign_data) {
                foreach ($campaign_data['platforms'] as $platform_data) {
                    $meta_spend += floatval($platform_data['spend']);
                    $leads = $wa_leads = 0;
                    $purchases = 0;
                    if (!empty($platform_data['actions']) && array_key_exists($campaign_data['objective'], $obj_arr)) {
                        $ft = $obj_arr[$campaign_data['objective']];
                         //d($platform_data['actions']);
                        $wa_leads   += LeadGen($platform_data['actions'], 'onsite_conversion.messaging_conversation_started_7d');
                        foreach ($platform_data['actions'] as $a) {
                           
                            if (isset($a['action_type']) && $a['action_type'] === $ft && isset($a['value'])) {
                                if ($ft === 'purchase') {
                                    $purchases += floatval($a['value']);
                                } else {
                                    
                                    $leads += floatval($a['value']);
                                }
                            }
                        }
                    }
                    $meta_lead += $leads + $wa_leads;
                    $meta_purchase += $purchases;
                }
            }
           // echo $meta_lead;
        } else {
            foreach ($fb_data as $data) {
                foreach ($data as $campaign) {
                    $campaign_name = isset($campaign['campaign_name']) ? $campaign['campaign_name'] : '';
                    $should_include = true;
                    if (!empty($proj_names) || !empty($name_contain)) {
                        $should_include = false;
                        foreach ($proj_names as $proj_name) {
                            if (stripos($campaign_name, $proj_name) !== false) { $should_include = true; break; }
                        }
                        if (!$should_include && !empty($name_contain)) {
                            $str = strtolower(trim($campaign_name));
                            foreach ($name_contain as $a) {
                                if (stripos($str, strtolower(trim($a))) !== false) { $should_include = true; break; }
                            }
                        }
                    }
                    if ($should_include) {
                        $meta_spend += floatval(isset($campaign['spend']) ? $campaign['spend'] : 0);
                        if (!empty($campaign['actions']) && array_key_exists(isset($campaign['objective']) ? $campaign['objective'] : '', $obj_arr)) {
                            $obj = isset($campaign['objective']) ? $campaign['objective'] : '';
                            $leads = LeadGen($campaign['actions'], $obj_arr[$obj]);
                            if ($obj_arr[$obj] === 'purchase') {
                                $meta_purchase += $leads;
                            } else {
                                $wa_leads   = LeadGen($campaign['actions'], 'onsite_conversion.messaging_conversation_started_7d');
                                $meta_lead += ($leads + $wa_leads);
                            }
                        }
                    }
                }
            }
        }
    }
   // exit;
    // ---- Google Ads (each account fetched separately per multi-client.php) ----
    if (!empty($g_acc_ids)) {
        foreach ($g_acc_ids as $g_id) {
            try {
                $adAccounts = array($g_id);
                $g_data = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $dtRange1, $dtRange2, '');
                if (is_array($g_data)) {
                    foreach ($g_data as $campaign) {
                        $campaign_name = isset($campaign['camp_name']) ? $campaign['camp_name'] : '';
                        $should_include = true;
                        if (!empty($proj_names) || !empty($name_contain)) {
                            $should_include = false;
                            foreach ($proj_names as $proj_name) {
                                if (stripos($campaign_name, $proj_name) !== false) { $should_include = true; break; }
                            }
                            if (!$should_include && !empty($name_contain)) {
                                $str = strtolower(trim($campaign_name));
                                foreach ($name_contain as $a) {
                                    if (stripos($str, strtolower(trim($a))) !== false) { $should_include = true; break; }
                                }
                            }
                        }
                        if ($should_include) {
                            $google_spend += floatval(isset($campaign['cost']) ? $campaign['cost'] : 0);
                            $google_lead += floatval(isset($campaign['conv']) ? $campaign['conv'] : 0);
                            $google_purchase += floatval(isset($campaign['conv_val']) ? $campaign['conv_val'] : 0);
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = $row['client_name'] . ' (Google ' . $g_id . '): ' . $e->getMessage();
            }
        }
    }
    
    $total_spend   = $meta_spend + $google_spend;
    $total_lead    = $meta_lead + $google_lead;
    $total_purchase = $meta_purchase + $google_purchase;
    
    $meta_spend     = round($meta_spend, 2);
    $google_spend   = round($google_spend, 2);
    $total_spend    = round($total_spend, 2);
    $google_purchase = round($google_purchase, 2);
    $total_purchase = round($total_purchase, 2);
    $meta_lead      = (int) $meta_lead;
    $meta_purchase  = (int) $meta_purchase;
    $google_lead    = (int) $google_lead;
    $total_lead     = (int) $total_lead;
    
    $account_tbl_id_safe = (int) $account_tbl_id;
    $periodStart_safe    = mysqli_real_escape_string($conn, $periodStart);
    $periodEnd_safe      = mysqli_real_escape_string($conn, $periodEnd);
    $meta_spend_safe     = (float) $meta_spend;
    $meta_lead_safe      = (int) $meta_lead;
    $meta_purchase_safe  = (int) $meta_purchase;
    $google_spend_safe   = (float) $google_spend;
    $google_lead_safe   = (int) $google_lead;
    $google_purchase_safe = (float) $google_purchase;
    $total_spend_safe    = (float) $total_spend;
    $total_lead_safe     = (int) $total_lead;
    $total_purchase_safe = (float) $total_purchase;

    $ins = "INSERT INTO client_performance_snapshot 
            (account_tbl_id, period_start, period_end, meta_spend, meta_lead, meta_purchase, google_spend, google_lead, google_purchase, total_spend, total_lead, total_purchase) 
            VALUES ($account_tbl_id_safe, '$periodStart_safe', '$periodEnd_safe', $meta_spend_safe, $meta_lead_safe, $meta_purchase_safe, $google_spend_safe, $google_lead_safe, $google_purchase_safe, $total_spend_safe, $total_lead_safe, $total_purchase_safe)
            ON DUPLICATE KEY UPDATE 
            meta_spend=VALUES(meta_spend), meta_lead=VALUES(meta_lead), meta_purchase=VALUES(meta_purchase),
            google_spend=VALUES(google_spend), google_lead=VALUES(google_lead), google_purchase=VALUES(google_purchase),
            total_spend=VALUES(total_spend), total_lead=VALUES(total_lead), total_purchase=VALUES(total_purchase)";
    
    if (mysqli_query($conn, $ins)) {
        $processed++;
    } else {
        $errors[] = $row['client_name'] . ': ' . mysqli_error($conn);
    }
}

echo "success";
if ($processed > 0) {
    echo " - Processed $processed clients for $periodStart to $periodEnd";
}
if (!empty($errors)) {
    echo "\nErrors: " . implode('; ', $errors);
    error_log('cron-client-performance: ' . implode('; ', $errors));
}
echo "\n";
exit(0);
