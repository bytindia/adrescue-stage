<?php
/**
 * Iyra Leads Meta Cron - Fetches daily leads from Meta API and stores in iyra_leads_meta
 * 
 * Usage:
 *   CLI: php iyra-leads-meta-cron.php [start_date] [end_date]
 *   URL: iyra-leads-meta-cron.php?st_dt=2026-03-01&end_dt=2026-03-04
 *   Default: today's date if no params
 * 
 * Date format: Y-m-d (e.g. 2026-03-04)
 */

date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include DB (has file_get_contents_curl, $api_ver)
require_once __DIR__ . '/../db.php';

// Copy of fetch helpers from leads-download.php (avoid session/HTML)
function _cron_file_get_contents_curl_post($url, $post_data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function _cron_fetchBulkCampaignData($lead_ids, $user_access_token) {
    global $api_ver;
    $campaign_data = [];
    $batch_size = 50;
    $batches = array_chunk($lead_ids, $batch_size);
    foreach ($batches as $batch) {
        $batch_requests = [];
        foreach ($batch as $index => $lead_id) {
            $batch_requests[] = [
                'method' => 'GET',
                'relative_url' => $lead_id . '?fields=campaign_name,adset_name,ad_name',
                'name' => 'lead_' . $index
            ];
        }
        $batch_data = [
            'batch' => json_encode($batch_requests),
            'access_token' => $user_access_token
        ];
        $batch_url = "https://graph.facebook.com/" . $api_ver . "/";
        $batch_output = _cron_file_get_contents_curl_post($batch_url, $batch_data);
        $batch_response = json_decode($batch_output, true);
        if (isset($batch_response) && is_array($batch_response)) {
            foreach ($batch_response as $response) {
                if (isset($response['body'])) {
                    $lead_data = json_decode($response['body'], true);
                    if (isset($lead_data['id'])) {
                        $campaign_data[$lead_data['id']] = [
                            'campaign_name' => $lead_data['campaign_name'] ?? '',
                            'adset_name' => $lead_data['adset_name'] ?? '',
                            'ad_name' => $lead_data['ad_name'] ?? ''
                        ];
                    }
                }
            }
        }
    }
    return $campaign_data;
}

function _cron_fetchLeadsFromForm($form_id, $page_token, $user_access_token, $start_date, $end_date) {
    global $api_ver;
    $leads = [];
    $since_datetime = new DateTime($start_date . " 00:00:00", new DateTimeZone('Asia/Calcutta'));
    $until_datetime = new DateTime($end_date . " 23:59:59", new DateTimeZone('Asia/Calcutta'));
    $since_datetime->setTimezone(new DateTimeZone('UTC'));
    $until_datetime->setTimezone(new DateTimeZone('UTC'));
    $since_timestamp = $since_datetime->getTimestamp();
    $until_timestamp = $until_datetime->getTimestamp();
    $url = "https://graph.facebook.com/" . $api_ver . "/" . $form_id . "/leads?fields=created_time,id,field_data,platform&since=" . $since_timestamp . "&until=" . $until_timestamp . "&access_token=" . $page_token . "&limit=1000";

    // Paginate through all lead pages
    do {
        $output = file_get_contents_curl($url);
        $response = json_decode($output, true);
        $url = null; // reset for next iteration

        if (isset($response['data']) && !empty($response['data'])) {
            $lead_ids = [];
            $temp_leads = [];
            foreach ($response['data'] as $leadData) {
                $lead = [];
                if (isset($leadData['field_data'])) {
                    foreach ($leadData['field_data'] as $field) {
                        $lead[$field['name']] = isset($field['values'][0]) ? $field['values'][0] : '';
                    }
                }
                $lead['created_time'] = $leadData['created_time'];
                $lead['lead_id'] = $leadData['id'];
                $lead['platform'] = isset($leadData['platform']) ? $leadData['platform'] : '';
                $lead_created_date = date('Y-m-d', strtotime($leadData['created_time']));
                if ($lead_created_date >= $start_date && $lead_created_date <= $end_date) {
                    $lead_ids[] = $leadData['id'];
                    $temp_leads[$leadData['id']] = $lead;
                }
            }
            if (!empty($lead_ids)) {
                $campaign_data = _cron_fetchBulkCampaignData($lead_ids, $user_access_token);
                foreach ($temp_leads as $lid => $lead) {
                    if (isset($campaign_data[$lid])) {
                        $lead['campaign_name'] = $campaign_data[$lid]['campaign_name'] ?? '';
                        $lead['adset_name'] = $campaign_data[$lid]['adset_name'] ?? '';
                        $lead['ad_name'] = $campaign_data[$lid]['ad_name'] ?? '';
                    } else {
                        $lead['campaign_name'] = $lead['adset_name'] = $lead['ad_name'] = '';
                    }
                    $leads[] = $lead;
                }
            }
        }

        // Follow pagination
        if (isset($response['paging']['next'])) {
            $url = $response['paging']['next'];
        }
    } while ($url);

    return $leads;
}

// Get date range: $st_dt and $end_dt
$st_dt = $end_dt = date('Y-m-d');
if (php_sapi_name() === 'cli') {
    if (isset($argv[1])) $st_dt = $argv[1];
    if (isset($argv[2])) $end_dt = $argv[2];
} else {
    if (!empty($_GET['st_dt'])) $st_dt = $_GET['st_dt'];
    if (!empty($_GET['end_dt'])) $end_dt = $_GET['end_dt'];
}

// Normalize dates
$st_dt = date('Y-m-d', strtotime($st_dt));
$end_dt = date('Y-m-d', strtotime($end_dt));

// UID for cron (matches leads-download)
$cron_uid = 2;

// Page IDs and project config
$PAGE_IYRA_PROPERTIES = '104623015828534';  // Iyra Properties
$PAGE_IYRA_CITY       = '957263474126805';  // Iyra City - Madurai

$projects = [
    'iyra city' => [
        'page_id'   => $PAGE_IYRA_CITY,
        'refName'   => 'iyra city',
        'form_filter' => ['byt'],  // form name must contain 'byt' (e.g. BYT_IYRA_SV...)
        'lead_filter' => null,     // no lead-level filter - include all from matching forms
    ],
    'iyra amara' => [
        'page_id'   => $PAGE_IYRA_PROPERTIES,
        'refName'   => 'iyra amara',
        'form_filter' => ['byt', 'amara'],  // form name must contain both
        'lead_filter' => null,
    ],
    'iyra spire' => [
        'page_id'   => $PAGE_IYRA_PROPERTIES,
        'refName'   => 'iyra spire',
        'form_filter' => ['byt', 'spire'],  // form name must contain both
        'lead_filter' => null,
    ],
];

// Get page tokens and user access token
$page_tokens = [];
$pageQuery = mysqli_query($conn, "SELECT pg_id, pg_token FROM pages WHERE uid='".$cron_uid."' AND pg_id IN ('".$PAGE_IYRA_PROPERTIES."','".$PAGE_IYRA_CITY."')");
while ($row = mysqli_fetch_assoc($pageQuery)) {
    $page_tokens[$row['pg_id']] = $row['pg_token'];
}

$userQuery = mysqli_query($conn, "SELECT access_token FROM users WHERE tbl_id=" . $cron_uid);
$userRow = mysqli_fetch_assoc($userQuery);
$user_access_token = $userRow ? $userRow['access_token'] : '';

if (!$user_access_token) {
    die("Error: User access token not found." . (php_sapi_name() === 'cli' ? "\n" : ""));
}

// Fetch forms for a page (with pagination)
function getFormsForPage($page_id, $page_token) {
    global $api_ver;
    $all_forms = [];
    $url = "https://graph.facebook.com/" . $api_ver . "/" . $page_id . "/leadgen_forms?access_token=" . $page_token . "&limit=500";
    do {
        $data = file_get_contents_curl($url);
        $val = json_decode($data, true);
        $url = null;
        if (isset($val['data']) && !empty($val['data'])) {
            $all_forms = array_merge($all_forms, $val['data']);
        }
        if (isset($val['paging']['next'])) {
            $url = $val['paging']['next'];
        }
    } while ($url);
    return $all_forms;
}

// Check if form name matches filter (contains all strings, case-insensitive)
function formMatchesFilter($form_name, $filter) {
    if (empty($filter)) return true;
    $fn = strtolower($form_name);
    foreach ($filter as $word) {
        if (stripos($fn, strtolower($word)) === false) return false;
    }
    return true;
}

// Get lead name from field_data (any name-like field)
function getLeadName($lead) {
    $name_keys = ['full_name', 'full name', 'first_name', 'first name', 'name'];
    foreach ($name_keys as $k) {
        if (isset($lead[$k]) && $lead[$k] !== '') return $lead[$k];
    }
    return '';
}

// Check if lead name contains 'byt'
function leadNameContainsByt($lead) {
    $name = getLeadName($lead);
    return stripos($name, 'byt') !== false;
}

// Insert lead into iyra_leads_meta (skip if leadgen_id already exists)
function insertLead($conn, $lead, $page_id, $form_id, $form_name, $refName) {
    $lead_id = mysqli_real_escape_string($conn, $lead['lead_id']);
    $check = mysqli_query($conn, "SELECT tbl_id FROM iyra_leads_meta WHERE leadgen_id='".$lead_id."'");
    if ($check && mysqli_num_rows($check) > 0) return 0;

    $name = '';
    foreach (['full_name','full name','first_name','first name','name'] as $k) {
        if (isset($lead[$k]) && $lead[$k] !== '') { $name = $lead[$k]; break; }
    }
    $email = isset($lead['email']) ? $lead['email'] : '';
    $phone = '';
    foreach (['phone_number','phone number','phone'] as $k) {
        if (isset($lead[$k]) && $lead[$k] !== '') { $phone = $lead[$k]; break; }
    }

    $page_id   = mysqli_real_escape_string($conn, $page_id);
    $form_id   = mysqli_real_escape_string($conn, $form_id);
    $formN     = mysqli_real_escape_string($conn, $form_name);
    $refName   = mysqli_real_escape_string($conn, $refName);
    $name      = mysqli_real_escape_string($conn, $name);
    $email     = mysqli_real_escape_string($conn, $email);
    $phone     = mysqli_real_escape_string($conn, $phone);
    $lead_json = mysqli_real_escape_string($conn, json_encode($lead));
    $ct        = mysqli_real_escape_string($conn, $lead['created_time']);
    $created   = date('Y-m-d H:i:s', strtotime($lead['created_time']));
    $adN       = mysqli_real_escape_string($conn, $lead['ad_name'] ?? '');
    $adsetN    = mysqli_real_escape_string($conn, $lead['adset_name'] ?? '');
    $campN     = mysqli_real_escape_string($conn, $lead['campaign_name'] ?? '');

    $sql = "INSERT INTO iyra_leads_meta (page_id,form_id,formN,refName,name,email,phone,lead,created_time,created,leadgen_id,adN,adsetN,campN) 
            VALUES ('$page_id','$form_id','$formN','$refName','$name','$email','$phone','$lead_json','$ct','$created','$lead_id','$adN','$adsetN','$campN')";
    return mysqli_query($conn, $sql) ? 1 : 0;
}

// --- Main execution ---
$total_inserted = 0;
$form_results = []; // refName => [ form_name => ['fetched'=>N, 'inserted'=>N] ]
$is_cli = (php_sapi_name() === 'cli');
$nl = $is_cli ? "\n" : "<br>\n";
$tab = $is_cli ? "  " : "&nbsp;&nbsp;";

foreach ($projects as $proj_key => $config) {
    $page_id = $config['page_id'];
    $refName = $config['refName'];
    $form_filter = $config['form_filter'];
    $lead_filter = $config['lead_filter'];

    if (!isset($page_tokens[$page_id])) {
        if ($is_cli) echo "Skip $refName: no token for page $page_id$nl";
        continue;
    }

    $page_token = $page_tokens[$page_id];
    $forms = getFormsForPage($page_id, $page_token);
    $form_results[$refName] = [];

    foreach ($forms as $form) {
        $form_id = $form['id'];
        $form_name = isset($form['name']) ? $form['name'] : '';

        if (!formMatchesFilter($form_name, $form_filter)) continue;

        $leads = _cron_fetchLeadsFromForm($form_id, $page_token, $user_access_token, $st_dt, $end_dt);
        $form_inserted = 0;

        foreach ($leads as $lead) {
            if ($lead_filter === 'name_contains_byt' && !leadNameContainsByt($lead)) continue;

            $n = insertLead($conn, $lead, $page_id, $form_id, $form_name, $refName);
            $form_inserted += $n;
            $total_inserted += $n;
        }

        $form_results[$refName][$form_name] = ['fetched' => count($leads), 'inserted' => $form_inserted];
    }
}

// --- Output with form-wise results ---
if ($is_cli) {
    echo "Iyra Leads Meta Cron - Date range: $st_dt to $end_dt$nl";
    echo "Total inserted: $total_inserted leads$nl$nl";
} else {
    echo "<h3>Iyra Leads Meta Cron</h3>";
    echo "<p><strong>Date range:</strong> $st_dt to $end_dt</p>";
    echo "<p><strong>Total inserted:</strong> $total_inserted leads</p>";
}

echo $is_cli ? "Form-wise results:$nl" : "<h4>Form-wise results:</h4>";
foreach ($form_results as $refName => $forms) {
    $proj_total_fetched = 0;
    $proj_total_inserted = 0;
    foreach ($forms as $fn => $counts) {
        $proj_total_fetched += $counts['fetched'];
        $proj_total_inserted += $counts['inserted'];
    }
    echo $nl . ($is_cli ? "--- $refName (fetched: $proj_total_fetched, inserted: $proj_total_inserted) ---$nl" : "<p><strong>$refName</strong> (fetched: $proj_total_fetched, inserted: $proj_total_inserted)</p>");
    if (!$is_cli) echo "<ul>";
    foreach ($forms as $form_name => $counts) {
        $display_name = strlen($form_name) > 80 ? substr($form_name, 0, 77) . '...' : $form_name;
        $line = "$display_name: fetched={$counts['fetched']}, inserted={$counts['inserted']}";
        echo $is_cli ? ($tab . "- " . $line . $nl) : "<li>" . htmlspecialchars($line) . "</li>";
    }
    if (!$is_cli) echo "</ul>";
}
