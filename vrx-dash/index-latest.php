<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// ─── Auth Check ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['vrx_logged_in']) || $_SESSION['vrx_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$server_path = '/home/digitalb2k/public_html/adsninja/';

// ─── DB + Helpers ─────────────────────────────────────────────────────────────
include $server_path . 'db.php';

// Fetch tokens from users table (same user row used by main app)
$userRes = mysqli_query($conn, "SELECT access_token, g_refresh_token, g_mcc FROM users WHERE tbl_id = 2 LIMIT 1");
$userRow = mysqli_fetch_assoc($userRes);
$access_token    = $userRow['access_token']    ?? '';
$g_refresh_token = $userRow['g_refresh_token'] ?? '';
$g_mcc           = (int)($userRow['g_mcc']     ?? 0);

// Google Ads API class
include $server_path . 'dash/google-campaigns.php';

// ─── VRX Account Details (tbl_id = 56) ───────────────────────────────────────
$accRes  = mysqli_query($conn, "SELECT fb_id, g_id, client_name FROM dashboard_accounts WHERE tbl_id = 56 LIMIT 1");
$accRow  = mysqli_fetch_assoc($accRes);
$fb_ids  = !empty($accRow['fb_id']) ? array_filter(array_map('trim', explode(',', $accRow['fb_id']))) : [];
$g_ids   = !empty($accRow['g_id'])  ? array_filter(array_map('trim', explode(',', $accRow['g_id'])))  : [];

// ─── Date Range ───────────────────────────────────────────────────────────────
$dtRange1 = (isset($_GET['start_date']) && preg_match('/\d{4}-\d{2}-\d{2}/', $_GET['start_date']))
            ? $_GET['start_date'] : date('Y-m-01');
$dtRange2 = (isset($_GET['end_date'])   && preg_match('/\d{4}-\d{2}-\d{2}/', $_GET['end_date']))
            ? $_GET['end_date']   : date('Y-m-d');

// ─── Meta API: Overall + Daily breakdown ─────────────────────────────────────
function metaLeads(array $actions): int {
    $types = ['lead','offsite_conversion.fb_pixel_lead',
              'onsite_conversion.messaging_first_reply','onsite_conversion.messaging_block'];
    $total = 0;
    foreach ($actions as $a) {
        if (in_array($a['action_type'] ?? '', $types)) $total += (int)($a['value'] ?? 0);
    }
    return $total;
}

$meta_spend = 0.0;
$meta_leads = 0;
$api_daily  = []; // ['Y-m-d' => ['spend'=>float,'leads'=>int]] from Meta API

foreach ($fb_ids as $fb_id) {
    // Overall
    $url = "https://graph.facebook.com/{$api_ver}/act_{$fb_id}/insights"
         . "?level=account&fields=spend,actions"
         . "&time_range[since]={$dtRange1}&time_range[until]={$dtRange2}"
         . "&access_token={$access_token}&limit=500";
    $res = json_decode(file_get_contents_curl($url), true);
    if (!empty($res['data'][0])) {
        $meta_spend += (float)($res['data'][0]['spend'] ?? 0);
        $meta_leads += metaLeads($res['data'][0]['actions'] ?? []);
    }
    // Daily (for Low/High CPL)
    $url_d = "https://graph.facebook.com/{$api_ver}/act_{$fb_id}/insights"
           . "?level=account&fields=date_start,spend,actions"
           . "&time_range[since]={$dtRange1}&time_range[until]={$dtRange2}"
           . "&time_increment=1&access_token={$access_token}&limit=500";
    $res_d = json_decode(file_get_contents_curl($url_d), true);
    if (!empty($res_d['data'])) {
        foreach ($res_d['data'] as $day) {
            $dt = $day['date_start'];
            if (!isset($api_daily[$dt])) $api_daily[$dt] = ['spend' => 0.0, 'leads' => 0];
            $api_daily[$dt]['spend'] += (float)($day['spend'] ?? 0);
            $api_daily[$dt]['leads'] += metaLeads($day['actions'] ?? []);
        }
    }
}

// ─── Google Ads API ───────────────────────────────────────────────────────────
$google_spend = 0.0;
$google_leads = 0;
if (!empty($g_ids) && !empty($g_refresh_token) && $g_mcc) {
    try {
        $g_data = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $g_ids, $dtRange1, $dtRange2, '');
        if (is_array($g_data)) {
            foreach ($g_data as $camp) {
                $google_spend += (float)($camp['cost'] ?? 0);
                $google_leads += (int)($camp['conv'] ?? 0);
            }
        }
    } catch (Throwable $e) { /* silently continue */ }
}

$total_spend     = $meta_spend + $google_spend;
$total_leads_api = $meta_leads + $google_leads;

// ─── Low / High CPL from Meta daily API ──────────────────────────────────────
$low_cpl_val = $low_cpl_date = $high_cpl_val = $high_cpl_date = null;
foreach ($api_daily as $dt => $d) {
    if ($d['leads'] > 0) {
        $day_cpl = round($d['spend'] / $d['leads']);
        if ($low_cpl_val  === null || $day_cpl < $low_cpl_val)  { $low_cpl_val  = $day_cpl; $low_cpl_date  = $dt; }
        if ($high_cpl_val === null || $day_cpl > $high_cpl_val) { $high_cpl_val = $day_cpl; $high_cpl_date = $dt; }
    }
}

// ─── DB Lead Counts ───────────────────────────────────────────────────────────
function dbLeads(mysqli $db, string $start, string $end): int {
    $cnt = 0;
    foreach (['vrx_leads_lp' => 'created_at', 'vrx_leads_meta' => 'created'] as $tbl => $col) {
        $r = mysqli_query($db, "SELECT COUNT(*) AS c FROM `{$tbl}` WHERE DATE(`{$col}`) BETWEEN '{$start}' AND '{$end}'");
        if ($r) { $row = mysqli_fetch_assoc($r); $cnt += (int)$row['c']; }
    }
    return $cnt;
}

// ─── Workable Leads (WL) from CRM ────────────────────────────────────────────
// Statuses considered workable
$wl_statuses_raw = ['working', 'workable'];

// Build TWO CRM status maps (latest new_status per lead from vrx_crm_lead_status_history)
// Map 1: lead_id  → latest status  — used for LP leads matched via vrx_leads_lp.api_res
// Map 2: phone last-10 → latest status — used for Meta leads matched via phone
$crm_map_by_id    = []; // ['00Qe200000...' => 'interested']
$crm_map_by_phone = []; // ['9876543210'    => 'non qualified']

$crmQ = mysqli_query($conn,
    "SELECT c.lead_id, c.phone,
            (SELECT h.new_status FROM vrx_crm_lead_status_history h
             WHERE h.lead_id = c.lead_id ORDER BY h.id DESC LIMIT 1) AS latest_status
     FROM vrx_crm_leads c
     WHERE c.lead_id IS NOT NULL AND c.lead_id != ''"
);
while ($r = mysqli_fetch_assoc($crmQ)) {
    if (empty($r['latest_status'])) continue;
    $status = strtolower(trim($r['latest_status']));
    // Map 1 — by CRM lead_id (for LP via api_res)
    $crm_map_by_id[trim($r['lead_id'])] = $status;
    // Map 2 — by phone last 10 digits (for Meta via phone)
    if (!empty($r['phone'])) {
        $ph = substr(preg_replace('/\D/', '', $r['phone']), -10);
        if (strlen($ph) === 10) $crm_map_by_phone[$ph] = $status;
    }
}

// Count WL:
//   LP   → match by api_res (CRM lead_id)
//   Meta → match by phone last 10 digits
function wlLeads(mysqli $db, string $start, string $end, array $wl_statuses,
                 array &$crm_map_by_id, array &$crm_map_by_phone): int {
    $count = 0;
    // LP leads via api_res
    $r = mysqli_query($db,
        "SELECT api_res FROM vrx_leads_lp
         WHERE DATE(created_at) BETWEEN '{$start}' AND '{$end}'
           AND api_res IS NOT NULL AND api_res != ''"
    );
    while ($row = mysqli_fetch_assoc($r)) {
        $lid = trim($row['api_res']);
        if (!empty($lid) && isset($crm_map_by_id[$lid]) && in_array($crm_map_by_id[$lid], $wl_statuses)) $count++;
    }
    // Meta leads via phone last 10 digits
    $r = mysqli_query($db,
        "SELECT phone FROM vrx_leads_meta WHERE DATE(created) BETWEEN '{$start}' AND '{$end}'"
    );
    while ($row = mysqli_fetch_assoc($r)) {
        $ph = substr(preg_replace('/\D/', '', $row['phone'] ?? ''), -10);
        if (strlen($ph) === 10 && isset($crm_map_by_phone[$ph]) && in_array($crm_map_by_phone[$ph], $wl_statuses)) $count++;
    }
    return $count;
}

$today          = date('Y-m-d');
$yesterday      = date('Y-m-d', strtotime('-1 day'));
$thisWeekStart  = date('Y-m-d', strtotime('monday this week'));
$lastWeekStart  = date('Y-m-d', strtotime('monday last week'));
$lastWeekEnd    = date('Y-m-d', strtotime('sunday last week'));
$thisMonthStart = date('Y-m-01');

// Total leads (DB)
$db_leads_period    = dbLeads($conn, $dtRange1, $dtRange2);
$db_leads_today     = dbLeads($conn, $today, $today);
$db_leads_yesterday = dbLeads($conn, $yesterday, $yesterday);
$db_leads_thisweek  = dbLeads($conn, $thisWeekStart, $today);
$db_leads_lastweek  = dbLeads($conn, $lastWeekStart, $lastWeekEnd);
$db_leads_thismonth = dbLeads($conn, $thisMonthStart, $today);

// Workable leads (LP → api_res, Meta → phone last 10 digits)
$wl_period    = wlLeads($conn, $dtRange1,       $dtRange2,    $wl_statuses_raw, $crm_map_by_id, $crm_map_by_phone);
$wl_today     = wlLeads($conn, $today,          $today,       $wl_statuses_raw, $crm_map_by_id, $crm_map_by_phone);
$wl_yesterday = wlLeads($conn, $yesterday,      $yesterday,   $wl_statuses_raw, $crm_map_by_id, $crm_map_by_phone);
$wl_thisweek  = wlLeads($conn, $thisWeekStart,  $today,       $wl_statuses_raw, $crm_map_by_id, $crm_map_by_phone);
$wl_lastweek  = wlLeads($conn, $lastWeekStart,  $lastWeekEnd, $wl_statuses_raw, $crm_map_by_id, $crm_map_by_phone);
$wl_thismonth = wlLeads($conn, $thisMonthStart, $today,       $wl_statuses_raw, $crm_map_by_id, $crm_map_by_phone);

$total_leads = $db_leads_period;
$cpl_avg     = ($total_leads > 0) ? round($total_spend / $total_leads) : 0;

// ─── Daily Table: Leads + WL + Spend (DB grouped by date) ────────────────────
$daily_table = []; // ['Y-m-d' => ['leads'=>int,'wl'=>int,'spend'=>float]]

// LP leads by date
$r = mysqli_query($conn, "SELECT DATE(created_at) AS dt, COUNT(*) AS cnt FROM vrx_leads_lp WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}' GROUP BY DATE(created_at)");
while ($row = mysqli_fetch_assoc($r)) {
    $daily_table[$row['dt']]['leads'] = ($daily_table[$row['dt']]['leads'] ?? 0) + (int)$row['cnt'];
}

// Meta leads by date
$r = mysqli_query($conn, "SELECT DATE(created) AS dt, COUNT(*) AS cnt FROM vrx_leads_meta WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}' GROUP BY DATE(created)");
while ($row = mysqli_fetch_assoc($r)) {
    $daily_table[$row['dt']]['leads'] = ($daily_table[$row['dt']]['leads'] ?? 0) + (int)$row['cnt'];
}

// WL by date — LP via api_res, Meta via phone last 10 digits
$r = mysqli_query($conn,
    "SELECT DATE(created_at) AS dt, api_res FROM vrx_leads_lp
     WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}'
       AND api_res IS NOT NULL AND api_res != ''"
);
while ($row = mysqli_fetch_assoc($r)) {
    $lid = trim($row['api_res']);
    if (!empty($lid) && isset($crm_map_by_id[$lid]) && in_array($crm_map_by_id[$lid], $wl_statuses_raw)) {
        $daily_table[$row['dt']]['wl'] = ($daily_table[$row['dt']]['wl'] ?? 0) + 1;
    }
}
$r = mysqli_query($conn,
    "SELECT DATE(created) AS dt, phone FROM vrx_leads_meta
     WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}'"
);
while ($row = mysqli_fetch_assoc($r)) {
    $ph = substr(preg_replace('/\D/', '', $row['phone'] ?? ''), -10);
    if (strlen($ph) === 10 && isset($crm_map_by_phone[$ph]) && in_array($crm_map_by_phone[$ph], $wl_statuses_raw)) {
        $daily_table[$row['dt']]['wl'] = ($daily_table[$row['dt']]['wl'] ?? 0) + 1;
    }
}

// Merge API daily spend
foreach ($api_daily as $dt => $d) {
    $daily_table[$dt]['spend'] = ($daily_table[$dt]['spend'] ?? 0.0) + $d['spend'];
}

// Ensure defaults and sort newest first
foreach ($daily_table as $dt => &$d) {
    $d['leads'] = $d['leads'] ?? 0;
    $d['wl']    = $d['wl']    ?? 0;
    $d['spend'] = $d['spend'] ?? 0.0;
}
unset($d);
krsort($daily_table);

// ─── CRM Feedback Summary (latest status, LP via api_res, Meta via phone) ────
$crm_data = []; $crm_total = 0; $crm_no_feedback = 0;
$status_counts = [];
// LP leads — match by api_res
$r = mysqli_query($conn,
    "SELECT api_res FROM vrx_leads_lp
     WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}'
       AND api_res IS NOT NULL AND api_res != ''"
);
while ($row = mysqli_fetch_assoc($r)) {
    $lid = trim($row['api_res']);
    if (!empty($lid) && isset($crm_map_by_id[$lid])) {
        $st = $crm_map_by_id[$lid];
        $status_counts[$st] = ($status_counts[$st] ?? 0) + 1;
    } else {
        $crm_no_feedback++;
    }
}
// LP leads with no api_res (also no feedback)
$rn = mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM vrx_leads_lp
     WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}'
       AND (api_res IS NULL OR api_res = '')"
);
if ($rn) { $rowN = mysqli_fetch_assoc($rn); $crm_no_feedback += (int)$rowN['c']; }

// Meta leads — match by phone last 10 digits
$r = mysqli_query($conn,
    "SELECT phone FROM vrx_leads_meta WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}'"
);
while ($row = mysqli_fetch_assoc($r)) {
    $ph = substr(preg_replace('/\D/', '', $row['phone'] ?? ''), -10);
    if (strlen($ph) === 10 && isset($crm_map_by_phone[$ph])) {
        $st = $crm_map_by_phone[$ph];
        $status_counts[$st] = ($status_counts[$st] ?? 0) + 1;
    } else {
        $crm_no_feedback++;
    }
}
arsort($status_counts);
foreach ($status_counts as $fb => $cnt) {
    $crm_data[] = ['lead_feedback' => $fb, 'cnt' => $cnt];
    $crm_total += $cnt;
}

// ─── Meta API: Campaign / Adset / Ad wise summary ────────────────────────────
$filtering_lead = json_encode([
    ['field' => 'campaign.objective', 'operator' => 'IN', 'value' => ['LEAD_GENERATION', 'OUTCOME_LEADS']]
]);
$meta_camp_data  = []; // campaign_id  => ['name','spend','leads']
$meta_adset_data = []; // adset_id     => ['name','campaign','spend','leads']
$meta_ad_data    = []; // ad_id        => ['name','adset','campaign','spend','leads']

foreach ($fb_ids as $fb_id) {
    // ── Campaign level ──
    $url = "https://graph.facebook.com/{$api_ver}/act_{$fb_id}/insights"
         . "?level=campaign&fields=campaign_id,campaign_name,spend,actions"
         . "&filtering=" . urlencode($filtering_lead)
         . "&time_range[since]={$dtRange1}&time_range[until]={$dtRange2}"
         . "&access_token={$access_token}&limit=500";
    $res = json_decode(file_get_contents_curl($url), true);
    if (!empty($res['data'])) {
        foreach ($res['data'] as $row) {
            $cid = $row['campaign_id'];
            if (!isset($meta_camp_data[$cid]))
                $meta_camp_data[$cid] = ['name' => $row['campaign_name'], 'spend' => 0.0, 'leads' => 0];
            $meta_camp_data[$cid]['spend'] += (float)($row['spend'] ?? 0);
            $meta_camp_data[$cid]['leads'] += metaLeads($row['actions'] ?? []);
        }
    }
    // ── Adset level ──
    $url = "https://graph.facebook.com/{$api_ver}/act_{$fb_id}/insights"
         . "?level=adset&fields=adset_id,adset_name,campaign_name,spend,actions"
         . "&filtering=" . urlencode($filtering_lead)
         . "&time_range[since]={$dtRange1}&time_range[until]={$dtRange2}"
         . "&access_token={$access_token}&limit=500";
    $res = json_decode(file_get_contents_curl($url), true);
    if (!empty($res['data'])) {
        foreach ($res['data'] as $row) {
            $asid = $row['adset_id'];
            if (!isset($meta_adset_data[$asid]))
                $meta_adset_data[$asid] = ['name' => $row['adset_name'], 'campaign' => $row['campaign_name'], 'spend' => 0.0, 'leads' => 0];
            $meta_adset_data[$asid]['spend'] += (float)($row['spend'] ?? 0);
            $meta_adset_data[$asid]['leads'] += metaLeads($row['actions'] ?? []);
        }
    }
    // ── Ad level ──
    $url = "https://graph.facebook.com/{$api_ver}/act_{$fb_id}/insights"
         . "?level=ad&fields=ad_id,ad_name,adset_name,campaign_name,spend,actions"
         . "&filtering=" . urlencode($filtering_lead)
         . "&time_range[since]={$dtRange1}&time_range[until]={$dtRange2}"
         . "&access_token={$access_token}&limit=500";
    $res = json_decode(file_get_contents_curl($url), true);
    if (!empty($res['data'])) {
        foreach ($res['data'] as $row) {
            $adid = $row['ad_id'];
            if (!isset($meta_ad_data[$adid]))
                $meta_ad_data[$adid] = ['name' => $row['ad_name'], 'adset' => $row['adset_name'], 'campaign' => $row['campaign_name'], 'spend' => 0.0, 'leads' => 0];
            $meta_ad_data[$adid]['spend'] += (float)($row['spend'] ?? 0);
            $meta_ad_data[$adid]['leads'] += metaLeads($row['actions'] ?? []);
        }
    }
}
// Sort by spend desc
uasort($meta_camp_data,  fn($a, $b) => $b['spend'] <=> $a['spend']);
uasort($meta_adset_data, fn($a, $b) => $b['spend'] <=> $a['spend']);
uasort($meta_ad_data,    fn($a, $b) => $b['spend'] <=> $a['spend']);

// ─── Active Campaign / Adset / Ad IDs (current delivery status) ──────────────
$active_camp_ids  = [];
$active_adset_ids = [];
$active_ad_ids    = [];
foreach ($fb_ids as $fb_id) {
    $act_filter = urlencode(json_encode([['field'=>'ad.effective_status','operator'=>'IN','value'=>['ACTIVE']]]));
    $url = "https://graph.facebook.com/{$api_ver}/act_{$fb_id}/ads"
         . "?fields=id,campaign_id,adset_id,effective_status"
         . "&filtering={$act_filter}"
         . "&access_token={$access_token}&limit=750";
    $res = json_decode(file_get_contents_curl($url), true);
    if (!empty($res['data'])) {
        foreach ($res['data'] as $ad) {
            if (($ad['effective_status'] ?? '') === 'ACTIVE') {
                $active_ad_ids[$ad['id']]             = true;
                $active_adset_ids[$ad['adset_id']]    = true;
                $active_camp_ids[$ad['campaign_id']]  = true;
            }
        }
    }
}

// ─── WL grouped by campaign name and ad name (Meta DB leads) ─────────────────
$meta_camp_wl = []; // ['campaign name' => wl_count]
$meta_ad_wl   = []; // ['ad name'       => wl_count]
$r = mysqli_query($conn,
    "SELECT campN, adN, phone FROM vrx_leads_meta
     WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}'"
);
while ($row = mysqli_fetch_assoc($r)) {
    $ph = substr(preg_replace('/\D/', '', $row['phone'] ?? ''), -10);
    if (strlen($ph) === 10 && isset($crm_map_by_phone[$ph]) && in_array($crm_map_by_phone[$ph], $wl_statuses_raw)) {
        $cn = $row['campN'] ?? '';
        $an = $row['adN']   ?? '';
        $meta_camp_wl[$cn] = ($meta_camp_wl[$cn] ?? 0) + 1;
        $meta_ad_wl[$an]   = ($meta_ad_wl[$an]   ?? 0) + 1;
    }
}

// ─── LG Form: group vrx_leads_meta by formN ──────────────────────────────────
$meta_form_data = []; // formN => ['leads'=>int, 'wl'=>int]
$rfm = mysqli_query($conn,
    "SELECT formN, phone FROM vrx_leads_meta
     WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}'"
);
while ($row = mysqli_fetch_assoc($rfm)) {
    $fn = trim($row['formN'] ?? '');
    if ($fn === '') $fn = '(Unknown Form)';
    if (!isset($meta_form_data[$fn])) $meta_form_data[$fn] = ['leads' => 0, 'wl' => 0];
    $meta_form_data[$fn]['leads']++;
    $ph = substr(preg_replace('/\D/', '', $row['phone'] ?? ''), -10);
    if (strlen($ph) === 10 && isset($crm_map_by_phone[$ph])
            && in_array($crm_map_by_phone[$ph], $wl_statuses_raw))
        $meta_form_data[$fn]['wl']++;
}
uasort($meta_form_data, fn($a, $b) => $b['leads'] <=> $a['leads']);

// ─── CRM Status Breakdown + Genuine (Relevancy) per Campaign / AdSet / Ad ────
function categorizeCrmStatus(string $status): string {
    $s = strtolower(trim($status));
    if ($s === '') return 'no_feedback';
    if (str_contains($s, 'working') || str_contains($s, 'workable')) return 'working';
    if (str_contains($s, 'call back') || str_contains($s, 'callback')) return 'callback';
    if (str_contains($s, 'rnr')) return 'rnr';
    if (str_contains($s, 'non qualified') || str_contains($s, 'not interested')) return 'non_qualified';
    if (str_contains($s, 'unqualified') || str_contains($s, 'duplicate') || str_contains($s, 'invalid')) return 'unqualified';
    return 'no_feedback';
}
function initCrmRow(): array {
    return ['working'=>0,'callback'=>0,'rnr'=>0,'non_qualified'=>0,'unqualified'=>0,'no_feedback'=>0,'total'=>0];
}

// Load all genuine cache_keys
$genuine_cache_keys = [];
$_ai_tbl = @mysqli_query($conn, "SELECT 1 FROM vrx_lead_ai_analysis LIMIT 1");
if ($_ai_tbl !== false) {
    $gRes = mysqli_query($conn, "SELECT cache_key FROM vrx_lead_ai_analysis WHERE is_genuine = 1");
    if ($gRes) {
        while ($gRow = mysqli_fetch_assoc($gRes))
            $genuine_cache_keys[$gRow['cache_key']] = true;
    }
}

$meta_camp_crm    = [];
$meta_adset_crm   = [];
$meta_ad_crm      = [];
$meta_camp_genuine  = [];
$meta_adset_genuine = [];
$meta_ad_genuine    = [];

$rMtLeads = mysqli_query($conn,
    "SELECT campN, adsetN, adN, phone, leadgen_id FROM vrx_leads_meta
     WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}'"
);
while ($mRow = mysqli_fetch_assoc($rMtLeads)) {
    $cn  = trim($mRow['campN']  ?? '');
    $asn = trim($mRow['adsetN'] ?? '');
    $an  = trim($mRow['adN']    ?? '');
    $ph  = substr(preg_replace('/\D/', '', $mRow['phone'] ?? ''), -10);
    $lgid = trim($mRow['leadgen_id'] ?? '');

    // CRM status → category
    $crmSt = (strlen($ph) === 10 && isset($crm_map_by_phone[$ph])) ? $crm_map_by_phone[$ph] : '';
    $cat   = categorizeCrmStatus($crmSt);

    // Tally CRM per level
    if ($cn !== '') {
        if (!isset($meta_camp_crm[$cn]))  $meta_camp_crm[$cn]  = initCrmRow();
        $meta_camp_crm[$cn][$cat]++; $meta_camp_crm[$cn]['total']++;
    }
    if ($asn !== '') {
        if (!isset($meta_adset_crm[$asn])) $meta_adset_crm[$asn] = initCrmRow();
        $meta_adset_crm[$asn][$cat]++; $meta_adset_crm[$asn]['total']++;
    }
    if ($an !== '') {
        if (!isset($meta_ad_crm[$an]))   $meta_ad_crm[$an]   = initCrmRow();
        $meta_ad_crm[$an][$cat]++; $meta_ad_crm[$an]['total']++;
    }

    // Genuine (Relevancy)
    $ck = ($lgid !== '') ? $lgid : (strlen($ph) === 10 ? 'ph_'.$ph : '');
    if ($ck !== '' && isset($genuine_cache_keys[$ck])) {
        if ($cn  !== '') $meta_camp_genuine[$cn]   = ($meta_camp_genuine[$cn]   ?? 0) + 1;
        if ($asn !== '') $meta_adset_genuine[$asn] = ($meta_adset_genuine[$asn] ?? 0) + 1;
        if ($an  !== '') $meta_ad_genuine[$an]     = ($meta_ad_genuine[$an]     ?? 0) + 1;
    }
}

// ─── Formatting ───────────────────────────────────────────────────────────────
function fmtMoney(float $n): string { return '₹' . number_format((int)$n); }
function fmtDate(?string $d): string { return $d ? date('d M', strtotime($d)) : '–'; }
function badgePlatform(string $p): string {
    $map = ['Meta' => 'bg-primary','LP' => 'bg-success','Google' => 'bg-warning text-dark'];
    return "<span class='badge ".($map[$p] ?? 'bg-secondary')."'>{$p}</span>";
}
function feedbackBadge(string $f): string {
    $fl = strtolower(trim($f));
    $map = ['working'=>'bg-success','workable'=>'bg-success',
            'interested'=>'bg-success','not interested'=>'bg-danger','non qualified'=>'bg-danger',
            'call back'=>'bg-warning text-dark','callback'=>'bg-warning text-dark',
            'text'=>'bg-info text-dark','whatsapp'=>'bg-success','email'=>'bg-primary',
            'site visit'=>'bg-purple','sv'=>'bg-purple','visited'=>'bg-info text-dark',
            'booked'=>'bg-primary','duplicate'=>'bg-secondary','invalid'=>'bg-dark','fresh leads'=>'bg-info text-dark'];
    foreach ($map as $key => $cls) {
        if (str_contains($fl, $key)) return "<span class='badge {$cls}'>".htmlspecialchars(ucwords($f))."</span>";
    }
    return "<span class='badge bg-secondary'>".htmlspecialchars(ucwords($f))."</span>";
}

$dt_label = date('d M', strtotime($dtRange1)) . ' – ' . date('d M Y', strtotime($dtRange2));

// Source split this month
$lp_month = $meta_month_cnt = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM vrx_leads_lp WHERE DATE(created_at) BETWEEN '{$thisMonthStart}' AND '{$today}'");
if ($r) { $row = mysqli_fetch_assoc($r); $lp_month = (int)$row['c']; }
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM vrx_leads_meta WHERE DATE(created) BETWEEN '{$thisMonthStart}' AND '{$today}'");
if ($r) { $row = mysqli_fetch_assoc($r); $meta_month_cnt = (int)$row['c']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VRX – Ads &amp; Leads Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: none; color: #1a2942; }

        .navbar-brand { font-weight: 900; font-size: 1.5rem; letter-spacing: 4px; color: #fff !important; }
        .navbar { background: linear-gradient(135deg, #0f2044, #1a3a6e); box-shadow: 0 2px 12px rgba(0,0,0,0.2); }

        /* ── Stat cards ── */
        .stat-card {
            border: none; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .stat-card .card-body { padding: 1.25rem 1.4rem 1rem; }
        .stat-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 2px; }
        .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.1; }
        .stat-sub   { font-size: 0.75rem; margin-top: 3px; opacity: 0.75; }
        .stat-icon  { display: none; }

        /* Period cards – dual value */
        .period-card .card-body { padding: 0.75rem 1.1rem 1rem; }
        .period-card .period-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .period-card .period-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .period-card .period-date  { font-size: 0.63rem; color: #888; font-weight: 500; text-align: right; }
        .period-card .val-wrap     { display: flex; align-items: flex-end; gap: 12px; }
        .period-card .val-block    { flex: 1; }
        .period-card .val-num      { font-size: 1.65rem; font-weight: 800; line-height: 1; }
        .period-card .val-sub      { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px; }
        .period-card .val-divider  { width: 1px; height: 36px; background: rgba(0,0,0,0.12); margin-bottom: 4px; }
        .wl-num   { color: #1a7a46 !important; }
        .wl-sub   { color: #1a7a46 !important; }

        /* Colour themes */
        .card-blue   { background:#e8f0fe; } .card-blue   .stat-label,.card-blue   .stat-value,.card-blue   .period-label { color:#1a56db; }
        .card-orange { background:#fff3e0; } .card-orange .stat-label,.card-orange .stat-value,.card-orange .period-label { color:#e65100; }
        .card-purple { background:#f3e8ff; } .card-purple .stat-label,.card-purple .stat-value,.card-purple .period-label { color:#7c3aed; }
        .card-green  { background:#e6f9f0; } .card-green  .stat-label,.card-green  .stat-value,.card-green  .period-label { color:#1a7a46; }
        .card-red    { background:#fee2e2; } .card-red    .stat-label,.card-red    .stat-value,.card-red    .period-label { color:#b91c1c; }
        .card-teal   { background:#e0f7fa; } .card-teal   .stat-label,.card-teal   .stat-value,.card-teal   .period-label { color:#00796b; }
        .card-indigo { background:#e8eaf6; } .card-indigo .stat-label,.card-indigo .stat-value,.card-indigo .period-label { color:#3949ab; }
        .card-pink   { background:#fce4ec; } .card-pink   .stat-label,.card-pink   .stat-value,.card-pink   .period-label { color:#ad1457; }
        .card-amber  { background:#fff8e1; } .card-amber  .stat-label,.card-amber  .stat-value,.card-amber  .period-label { color:#f57f17; }
        .card-cyan   { background:#e0f7fa; } .card-cyan   .stat-label,.card-cyan   .stat-value,.card-cyan   .period-label { color:#0277bd; }
        .card-slate  { background:#f1f5f9; } .card-slate  .stat-label,.card-slate  .stat-value,.card-slate  .period-label { color:#475569; }

        .filter-bar { background:#f5f5f5; border-radius:14px; padding:10px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .filter-bar .btn { border-radius:8px; }

        .section-title { font-size:0.73rem; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#6b7280; margin-bottom:10px; margin-top:6px; }

        .dash-table { font-size:0.84rem; }
        .dash-table thead th { background:#f8fafc; font-weight:700; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; border-bottom:2px solid #e5e7eb; }
        .dash-table tbody tr:hover { background:#f8fafc; }
        .dash-table td { vertical-align:middle; }

        .feedback-bar  { height:5px; border-radius:3px; background:#e5e7eb; overflow:hidden; margin-top:4px; }
        .feedback-fill { height:100%; border-radius:3px; background:#1a3a6e; }

        /* WL badge in daily table */
        .wl-badge { background:#d1fae5; color:#065f46; font-weight:700; border-radius:6px; padding:2px 8px; font-size:0.8rem; }
        .leads-badge { background:#e0e7ff; color:#3730a3; font-weight:700; border-radius:6px; padding:2px 8px; font-size:0.8rem; }

        /* Active delivery green dot */
        .active-dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:#22c55e; margin-right:5px; vertical-align:middle; flex-shrink:0; box-shadow:0 0 0 2px #dcfce7; }
        /* Active / Paused row tints */
        .row-active { background: #dcfce7 !important; }
        .row-paused { background: #fee2e2 !important; }

        @media (max-width:576px) { .stat-value { font-size:1.4rem; } .period-card .val-num { font-size:1.3rem; } }

        /* Nav tabs for Meta summary — active uses navbar gradient */
        .nav-tabs { border-bottom: 2px solid #1a3a6e; gap:3px; }
        .nav-tabs .nav-link { font-size:.82rem; font-weight:600; color:#6b7280; padding:7px 16px; border-radius:6px 6px 0 0; border:1px solid transparent; }
        .nav-tabs .nav-link:hover:not(.active) { color:#0f2044; background:#e8eef8; border-color:#dde4f0 #dde4f0 transparent; }
        .nav-tabs .nav-link.active { background:linear-gradient(135deg,#0f2044,#1a3a6e) !important; color:#fff !important; border-color:#0f2044; font-weight:700; }
        .tab-content { border:1px solid #1a3a6e; border-top:none; border-radius:0 0 8px 8px; padding:4px; background:#fff; }
        .note { font-size:11px; color:#aaa; font-style:italic; text-align:center; }

        /* DataTables overrides */
        table.dataTable thead th { background:#f8fafc !important; font-size:.72rem; font-weight:700; text-transform:uppercase; color:#6b7280 !important; }
        table.dataTable { font-size:13px; }
    </style>
</head>
<body>

<?php $vrx_active_page = 'index'; include '_nav.php'; ?>

<div class="container-fluid px-3 px-md-4 pb-5">

    <!-- ── DATE FILTER ── -->
    <div class="filter-bar">
        <div class="btn-group btn-group-sm">
            <?php
            $shortcuts = [
                'Today'      => [date('Y-m-d'), date('Y-m-d')],
                'This Week'  => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
                'This Month' => [date('Y-m-01'), date('Y-m-d')],
                'Last Month' => [date('Y-m-01', strtotime('first day of last month')),
                                 date('Y-m-t',  strtotime('first day of last month'))],
            ];
            foreach ($shortcuts as $lbl => [$s, $e]):
                $act = ($dtRange1 === $s && $dtRange2 === $e) ? 'btn-primary' : 'btn-outline-secondary';
            ?>
            <a href="loading.php?pg=index.php&start_date=<?= $s ?>&end_date=<?= $e ?>" class="btn btn-sm <?= $act ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
        <small class="text-muted">
            Meta: <strong class="text-primary"><?= fmtMoney($meta_spend) ?></strong> &nbsp;
            Google: <strong class="text-success"><?= fmtMoney($google_spend) ?></strong>
        </small>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECTION 1 – LEADS BY PERIOD  (moved to top)
    ═══════════════════════════════════════════════════════════ -->
    <div class="section-title">📥 Leads by Period
        <span class="ms-2 text-muted fw-normal" style="font-size:0.68rem;">
            Leads = DB total &nbsp;|&nbsp; <span style="color:#1a7a46;">WL</span> = Workable (CRM)
        </span>
    </div>
    <div class="row g-3 mb-4">

        <!-- Today -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card period-card stat-card card-teal">
                <div class="card-body">
                    <div class="period-header">
                        <span class="period-label">Today</span>
                        <span class="period-date"><?= date('d M Y') ?></span>
                    </div>
                    <div class="val-wrap">
                        <div class="val-block">
                            <div class="val-num"><?= $db_leads_today ?></div>
                            <div class="val-sub">Leads</div>
                        </div>
                        <div class="val-divider"></div>
                        <div class="val-block">
                            <div class="val-num wl-num"><?= $wl_today ?></div>
                            <div class="val-sub wl-sub">WL</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yesterday -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card period-card stat-card card-amber">
                <div class="card-body">
                    <div class="period-header">
                        <span class="period-label">Yesterday</span>
                        <span class="period-date"><?= date('d M Y', strtotime('-1 day')) ?></span>
                    </div>
                    <div class="val-wrap">
                        <div class="val-block">
                            <div class="val-num"><?= $db_leads_yesterday ?></div>
                            <div class="val-sub">Leads</div>
                        </div>
                        <div class="val-divider"></div>
                        <div class="val-block">
                            <div class="val-num wl-num"><?= $wl_yesterday ?></div>
                            <div class="val-sub wl-sub">WL</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Week -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card period-card stat-card card-cyan">
                <div class="card-body">
                    <div class="period-header">
                        <span class="period-label">This Week</span>
                        <span class="period-date"><?= date('d M', strtotime('monday this week')) ?>–<?= date('d M') ?></span>
                    </div>
                    <div class="val-wrap">
                        <div class="val-block">
                            <div class="val-num"><?= $db_leads_thisweek ?></div>
                            <div class="val-sub">Leads</div>
                        </div>
                        <div class="val-divider"></div>
                        <div class="val-block">
                            <div class="val-num wl-num"><?= $wl_thisweek ?></div>
                            <div class="val-sub wl-sub">WL</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Week -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card period-card stat-card card-pink">
                <div class="card-body">
                    <div class="period-header">
                        <span class="period-label">Last Week</span>
                        <span class="period-date"><?= date('d M', strtotime('monday last week')) ?>–<?= date('d M', strtotime('sunday last week')) ?></span>
                    </div>
                    <div class="val-wrap">
                        <div class="val-block">
                            <div class="val-num"><?= $db_leads_lastweek ?></div>
                            <div class="val-sub">Leads</div>
                        </div>
                        <div class="val-divider"></div>
                        <div class="val-block">
                            <div class="val-num wl-num"><?= $wl_lastweek ?></div>
                            <div class="val-sub wl-sub">WL</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card period-card stat-card card-blue">
                <div class="card-body">
                    <div class="period-header">
                        <span class="period-label">This Month</span>
                        <span class="period-date"><?= date('M Y') ?></span>
                    </div>
                    <div class="val-wrap">
                        <div class="val-block">
                            <div class="val-num"><?= $db_leads_thismonth ?></div>
                            <div class="val-sub">Leads</div>
                        </div>
                        <div class="val-divider"></div>
                        <div class="val-block">
                            <div class="val-num wl-num"><?= $wl_thismonth ?></div>
                            <div class="val-sub wl-sub">WL</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Period -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card period-card stat-card card-purple">
                <div class="card-body">
                    <div class="period-header">
                        <span class="period-label">Selected</span>
                        <span class="period-date"><?= htmlspecialchars($dt_label) ?></span>
                    </div>
                    <div class="val-wrap">
                        <div class="val-block">
                            <div class="val-num"><?= $db_leads_period ?></div>
                            <div class="val-sub">Leads</div>
                        </div>
                        <div class="val-divider"></div>
                        <div class="val-block">
                            <div class="val-num wl-num"><?= $wl_period ?></div>
                            <div class="val-sub wl-sub">WL</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECTION 2 – ADS OVERVIEW
    ═══════════════════════════════════════════════════════════ -->
    <div class="section-title">📊 Ads Overview — <?= htmlspecialchars($dt_label) ?></div>
    <div class="row g-3 mb-4">

        <?php
            $meta_cpl_val   = $meta_leads   > 0 ? round($meta_spend   / $meta_leads)   : 0;
            $google_cpl_val = $google_leads > 0 ? round($google_spend / $google_leads) : 0;
        ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-blue">
                <div class="card-body">
                    <div class="stat-label">Total Spend</div>
                    <div class="stat-value"><?= fmtMoney($total_spend) ?></div>
                    <div class="stat-sub">
                        Meta: <?= fmtMoney($meta_spend) ?> &nbsp;|&nbsp; Google: <?= fmtMoney($google_spend) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-orange">
                <div class="card-body">
                    <div class="stat-label">Total Leads</div>
                    <div class="stat-value"><?= $total_leads_api ?></div>
                    <div class="stat-sub">
                        Meta: <?= $meta_leads ?> &nbsp;|&nbsp; Google: <?= $google_leads ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-purple">
                <div class="card-body">
                    <div class="stat-label">CPL</div>
                    <div class="stat-value"><?= fmtMoney($cpl_avg) ?></div>
                    <div class="stat-sub">
                        Meta: <?= $meta_cpl_val > 0 ? fmtMoney($meta_cpl_val) : '–' ?> &nbsp;|&nbsp; Google: <?= $google_cpl_val > 0 ? fmtMoney($google_cpl_val) : '–' ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-green">
                <div class="card-body">
                    <div class="stat-label">Low CPL</div>
                    <div class="stat-value"><?= $low_cpl_val !== null ? fmtMoney($low_cpl_val) : '–' ?></div>
                    <div class="stat-sub"><?= $low_cpl_date ? fmtDate($low_cpl_date) : 'No data' ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-red">
                <div class="card-body">
                    <div class="stat-label">High CPL</div>
                    <div class="stat-value"><?= $high_cpl_val !== null ? fmtMoney($high_cpl_val) : '–' ?></div>
                    <div class="stat-sub"><?= $high_cpl_date ? fmtDate($high_cpl_date) : 'No data' ?></div>
                </div>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECTION 3 – DAILY LEADS TABLE (Leads + WL + Spend + CPL)
    ═══════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">

        <!-- Daily Leads + WL table -->
        <div class="col-12 col-lg-<?= empty($crm_data) ? '12' : '8' ?>">
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">📋 Daily Leads Report</h6>
                    <?php if (empty($daily_table)): ?>
                        <p class="text-muted small">No data for selected period.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm dash-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-center">Leads</th>
                                    <th class="text-center">Workable (WL)</th>
                                    <th class="text-center">WL %</th>
                                    <th class="text-end">Meta Spend</th>
                                    <th class="text-end">CPL</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($daily_table as $dt => $d):
                                $day_cpl  = ($d['leads'] > 0) ? round($d['spend'] / $d['leads']) : 0;
                                $wl_pct   = ($d['leads'] > 0) ? round(($d['wl'] / $d['leads']) * 100) : 0;
                                $rowClass = '';
                                if ($dt === $low_cpl_date)  $rowClass = 'table-success';
                                if ($dt === $high_cpl_date) $rowClass = 'table-danger';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="fw-semibold"><?= date('D, d M', strtotime($dt)) ?></td>
                                <td class="text-center"><span class="leads-badge"><?= $d['leads'] ?></span></td>
                                <td class="text-center"><span class="wl-badge"><?= $d['wl'] ?></span></td>
                                <td class="text-center">
                                    <small class="text-muted"><?= $wl_pct ?>%</small>
                                </td>
                                <td class="text-end"><?= $d['spend'] > 0 ? fmtMoney($d['spend']) : '–' ?></td>
                                <td class="text-end fw-bold"><?= $day_cpl > 0 ? fmtMoney($day_cpl) : '–' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center"><span class="leads-badge"><?= $total_leads ?></span></td>
                                    <td class="text-center"><span class="wl-badge"><?= $wl_period ?></span></td>
                                    <td class="text-center">
                                        <small><?= $total_leads > 0 ? round(($wl_period / $total_leads) * 100) : 0 ?>%</small>
                                    </td>
                                    <td class="text-end"><?= fmtMoney($meta_spend) ?></td>
                                    <td class="text-end"><?= fmtMoney($cpl_avg) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CRM Feedback Summary -->
        <?php if (!empty($crm_data) || $crm_no_feedback > 0): ?>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <?php $grand_total = $crm_total + $crm_no_feedback; ?>
                    <h6 class="fw-bold mb-3">🗂️ CRM Feedback
                        <span class="badge bg-secondary ms-1"><?= $grand_total ?> total</span>
                    </h6>
                    <?php foreach ($crm_data as $fb):
                        $pct = $grand_total > 0 ? round(($fb['cnt'] / $grand_total) * 100) : 0; ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small" style="min-width:150px;"><?= feedbackBadge($fb['lead_feedback']) ?></div>
                        <div class="small fw-bold text-end" style="min-width:28px;"><?= $fb['cnt'] ?></div>
                        <div class="flex-grow-1 ms-2">
                            <div class="feedback-bar"><div class="feedback-fill" style="width:<?= $pct ?>%;"></div></div>
                        </div>
                        <div class="small text-muted ms-1" style="min-width:32px;"><?= $pct ?>%</div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($crm_no_feedback > 0):
                        $pct_nf = $grand_total > 0 ? round(($crm_no_feedback / $grand_total) * 100) : 0; ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pt-1" style="border-top:1px dashed #e5e7eb;">
                        <div class="small" style="min-width:150px;"><span class="badge bg-secondary">No Feedback</span></div>
                        <div class="small fw-bold text-end" style="min-width:28px;"><?= $crm_no_feedback ?></div>
                        <div class="flex-grow-1 ms-2">
                            <div class="feedback-bar"><div class="feedback-fill" style="width:<?= $pct_nf ?>%; background:#9ca3af;"></div></div>
                        </div>
                        <div class="small text-muted ms-1" style="min-width:32px;"><?= $pct_nf ?>%</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECTION 4 – META PERFORMANCE SUMMARY (Form / Campaign / AdSet / Ad)
    ═══════════════════════════════════════════════════════════ -->
    <div class="section-title">📣 Meta Performance Summary — <?= htmlspecialchars($dt_label) ?></div>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
        <div class="card-body">

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-0" id="metaTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-form" type="button">
                        LG Form <span class="badge ms-1" style="background:rgba(255,255,255,.25);color:#fff;"><?= count($meta_form_data) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-camp" type="button">
                        Campaign <span class="badge bg-secondary ms-1"><?= count($meta_camp_data) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-adset" type="button">
                        AdSet <span class="badge bg-secondary ms-1"><?= count($meta_adset_data) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ad" type="button">
                        Ad <span class="badge bg-secondary ms-1"><?= count($meta_ad_data) ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-3">

                <!-- ── LG Form Tab ── -->
                <div class="tab-pane fade show active" id="tab-form">
                    <?php if (empty($meta_form_data)): ?>
                        <p class="text-muted small text-center py-3">No lead form data for selected period.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="tbl_form" class="table table-sm table-hover dash-table w-100">
                            <thead>
                                <tr>
                                    <th>LG Form</th>
                                    <th class="text-end">Leads</th>
                                    <th class="text-end">WL</th>
                                    <th class="text-end">WL %</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($meta_form_data as $fn => $row):
                                $wl_pct = $row['leads'] > 0 ? round(($row['wl'] / $row['leads']) * 100) : 0;
                            ?>
                            <tr>
                                <td style="max-width:400px;white-space:normal;word-break:break-word;"><?= htmlspecialchars($fn) ?></td>
                                <td class="text-end"><span class="leads-badge"><?= $row['leads'] ?></span></td>
                                <td class="text-end"><span class="wl-badge"><?= $row['wl'] ?></span></td>
                                <td class="text-end text-muted"><?= $wl_pct ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end"><span class="leads-badge"><?= array_sum(array_column($meta_form_data, 'leads')) ?></span></td>
                                    <td class="text-end"><span class="wl-badge"><?= array_sum(array_column($meta_form_data, 'wl')) ?></span></td>
                                    <td class="text-end text-muted">
                                        <?php $tf_l = array_sum(array_column($meta_form_data,'leads')); $tf_w = array_sum(array_column($meta_form_data,'wl')); ?>
                                        <?= $tf_l > 0 ? round(($tf_w/$tf_l)*100) : 0 ?>%
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── Campaign Tab ── -->
                <div class="tab-pane fade" id="tab-camp">
                    <?php if (empty($meta_camp_data)): ?>
                        <p class="text-muted small text-center py-3">No campaign data for selected period.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="tbl_camp" class="table table-sm table-hover dash-table w-100">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th class="text-end">Working</th>
                                    <th class="text-end">Call Back</th>
                                    <th class="text-end">RNR</th>
                                    <th class="text-end">Non Qualified</th>
                                    <th class="text-end">Unqualified</th>
                                    <th class="text-end">No Feedback</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Spends</th>
                                    <th class="text-end">CPL</th>
                                    <th class="text-end">Relevancy</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $camp_totals = ['working'=>0,'callback'=>0,'rnr'=>0,'non_qualified'=>0,'unqualified'=>0,'no_feedback'=>0,'total'=>0,'spend'=>0,'relevancy'=>0];
                            foreach ($meta_camp_data as $cid => $row):
                                $cr  = $meta_camp_crm[$row['name']] ?? initCrmRow();
                                $tot = $cr['total'] ?: $row['leads'];
                                $cpl = $tot > 0 ? round($row['spend'] / $tot) : 0;
                                $rel = $meta_camp_genuine[$row['name']] ?? 0;
                                $is_active = isset($active_camp_ids[$cid]);
                                $rowClass  = $is_active ? 'row-active' : 'row-paused';
                                $camp_totals['working']      += $cr['working'];
                                $camp_totals['callback']     += $cr['callback'];
                                $camp_totals['rnr']          += $cr['rnr'];
                                $camp_totals['non_qualified']+= $cr['non_qualified'];
                                $camp_totals['unqualified']  += $cr['unqualified'];
                                $camp_totals['no_feedback']  += $cr['no_feedback'];
                                $camp_totals['total']        += $tot;
                                $camp_totals['spend']        += $row['spend'];
                                $camp_totals['relevancy']    += $rel;
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td style="max-width:320px;white-space:normal;word-break:break-word;">
                                    <?php if ($is_active): ?><span class="active-dot" title="Active"></span><?php endif; ?>
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>
                                <td class="text-end" data-order="<?= $cr['working'] ?>"><?= $cr['working'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['callback'] ?>"><?= $cr['callback'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['rnr'] ?>"><?= $cr['rnr'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['non_qualified'] ?>"><?= $cr['non_qualified'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['unqualified'] ?>"><?= $cr['unqualified'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['no_feedback'] ?>"><?= $cr['no_feedback'] ?: '' ?></td>
                                <td class="text-end fw-bold" data-order="<?= $tot ?>"><?= $tot ?: '' ?></td>
                                <td class="text-end" data-order="<?= (int)$row['spend'] ?>"><?= fmtMoney($row['spend']) ?></td>
                                <td class="text-end"><?= $cpl > 0 ? fmtMoney($cpl) : '–' ?></td>
                                <td class="text-end"><?= $rel > 0 ? '<span class="badge bg-success">'.$rel.'</span>' : '' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end"><?= $camp_totals['working'] ?: '' ?></td>
                                    <td class="text-end"><?= $camp_totals['callback'] ?: '' ?></td>
                                    <td class="text-end"><?= $camp_totals['rnr'] ?: '' ?></td>
                                    <td class="text-end"><?= $camp_totals['non_qualified'] ?: '' ?></td>
                                    <td class="text-end"><?= $camp_totals['unqualified'] ?: '' ?></td>
                                    <td class="text-end"><?= $camp_totals['no_feedback'] ?: '' ?></td>
                                    <td class="text-end"><?= $camp_totals['total'] ?></td>
                                    <td class="text-end"><?= fmtMoney($camp_totals['spend']) ?></td>
                                    <td class="text-end"><?= $camp_totals['total'] > 0 ? fmtMoney(round($camp_totals['spend'] / $camp_totals['total'])) : '–' ?></td>
                                    <td class="text-end"><?= $camp_totals['relevancy'] > 0 ? '<span class="badge bg-success">'.$camp_totals['relevancy'].'</span>' : '' ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── AdSet Tab ── -->
                <div class="tab-pane fade" id="tab-adset">
                    <?php if (empty($meta_adset_data)): ?>
                        <p class="text-muted small text-center py-3">No adset data for selected period.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="tbl_adset" class="table table-sm table-hover dash-table w-100">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Ad Set</th>
                                    <th class="text-end">Working</th>
                                    <th class="text-end">Call Back</th>
                                    <th class="text-end">RNR</th>
                                    <th class="text-end">Non Qualified</th>
                                    <th class="text-end">Unqualified</th>
                                    <th class="text-end">No Feedback</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Spends</th>
                                    <th class="text-end">CPL</th>
                                    <th class="text-end">Relevancy</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $adset_totals = ['working'=>0,'callback'=>0,'rnr'=>0,'non_qualified'=>0,'unqualified'=>0,'no_feedback'=>0,'total'=>0,'spend'=>0,'relevancy'=>0];
                            foreach ($meta_adset_data as $asid => $row):
                                $cr  = $meta_adset_crm[$row['name']] ?? initCrmRow();
                                $tot = $cr['total'] ?: $row['leads'];
                                $cpl = $tot > 0 ? round($row['spend'] / $tot) : 0;
                                $rel = $meta_adset_genuine[$row['name']] ?? 0;
                                $is_active = isset($active_adset_ids[$asid]);
                                $rowClass  = $is_active ? 'row-active' : 'row-paused';
                                $adset_totals['working']      += $cr['working'];
                                $adset_totals['callback']     += $cr['callback'];
                                $adset_totals['rnr']          += $cr['rnr'];
                                $adset_totals['non_qualified']+= $cr['non_qualified'];
                                $adset_totals['unqualified']  += $cr['unqualified'];
                                $adset_totals['no_feedback']  += $cr['no_feedback'];
                                $adset_totals['total']        += $tot;
                                $adset_totals['spend']        += $row['spend'];
                                $adset_totals['relevancy']    += $rel;
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td style="max-width:180px;white-space:normal;word-break:break-word;"><?= htmlspecialchars($row['campaign']) ?></td>
                                <td style="max-width:260px;white-space:normal;word-break:break-word;">
                                    <?php if ($is_active): ?><span class="active-dot" title="Active"></span><?php endif; ?>
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>
                                <td class="text-end" data-order="<?= $cr['working'] ?>"><?= $cr['working'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['callback'] ?>"><?= $cr['callback'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['rnr'] ?>"><?= $cr['rnr'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['non_qualified'] ?>"><?= $cr['non_qualified'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['unqualified'] ?>"><?= $cr['unqualified'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['no_feedback'] ?>"><?= $cr['no_feedback'] ?: '' ?></td>
                                <td class="text-end fw-bold" data-order="<?= $tot ?>"><?= $tot ?: '' ?></td>
                                <td class="text-end" data-order="<?= (int)$row['spend'] ?>"><?= fmtMoney($row['spend']) ?></td>
                                <td class="text-end"><?= $cpl > 0 ? fmtMoney($cpl) : '–' ?></td>
                                <td class="text-end"><?= $rel > 0 ? '<span class="badge bg-success">'.$rel.'</span>' : '' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td class="text-end"><?= $adset_totals['working'] ?: '' ?></td>
                                    <td class="text-end"><?= $adset_totals['callback'] ?: '' ?></td>
                                    <td class="text-end"><?= $adset_totals['rnr'] ?: '' ?></td>
                                    <td class="text-end"><?= $adset_totals['non_qualified'] ?: '' ?></td>
                                    <td class="text-end"><?= $adset_totals['unqualified'] ?: '' ?></td>
                                    <td class="text-end"><?= $adset_totals['no_feedback'] ?: '' ?></td>
                                    <td class="text-end"><?= $adset_totals['total'] ?></td>
                                    <td class="text-end"><?= fmtMoney($adset_totals['spend']) ?></td>
                                    <td class="text-end"><?= $adset_totals['total'] > 0 ? fmtMoney(round($adset_totals['spend'] / $adset_totals['total'])) : '–' ?></td>
                                    <td class="text-end"><?= $adset_totals['relevancy'] > 0 ? '<span class="badge bg-success">'.$adset_totals['relevancy'].'</span>' : '' ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── Ad Tab ── -->
                <div class="tab-pane fade" id="tab-ad">
                    <?php if (empty($meta_ad_data)): ?>
                        <p class="text-muted small text-center py-3">No ad data for selected period.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="tbl_ad" class="table table-sm table-hover dash-table w-100">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Ad Set</th>
                                    <th>Ad</th>
                                    <th class="text-end">Working</th>
                                    <th class="text-end">Call Back</th>
                                    <th class="text-end">RNR</th>
                                    <th class="text-end">Non Qualified</th>
                                    <th class="text-end">Unqualified</th>
                                    <th class="text-end">No Feedback</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Spends</th>
                                    <th class="text-end">CPL</th>
                                    <th class="text-end">Relevancy</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ad_totals = ['working'=>0,'callback'=>0,'rnr'=>0,'non_qualified'=>0,'unqualified'=>0,'no_feedback'=>0,'total'=>0,'spend'=>0,'relevancy'=>0];
                            foreach ($meta_ad_data as $adid => $row):
                                $cr  = $meta_ad_crm[$row['name']] ?? initCrmRow();
                                $tot = $cr['total'] ?: $row['leads'];
                                $cpl = $tot > 0 ? round($row['spend'] / $tot) : 0;
                                $rel = $meta_ad_genuine[$row['name']] ?? 0;
                                $is_active = isset($active_ad_ids[$adid]);
                                $rowClass  = $is_active ? 'row-active' : 'row-paused';
                                $ad_totals['working']      += $cr['working'];
                                $ad_totals['callback']     += $cr['callback'];
                                $ad_totals['rnr']          += $cr['rnr'];
                                $ad_totals['non_qualified']+= $cr['non_qualified'];
                                $ad_totals['unqualified']  += $cr['unqualified'];
                                $ad_totals['no_feedback']  += $cr['no_feedback'];
                                $ad_totals['total']        += $tot;
                                $ad_totals['spend']        += $row['spend'];
                                $ad_totals['relevancy']    += $rel;
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td style="max-width:140px;white-space:normal;word-break:break-word;"><?= htmlspecialchars($row['campaign']) ?></td>
                                <td style="max-width:140px;white-space:normal;word-break:break-word;"><?= htmlspecialchars($row['adset']) ?></td>
                                <td style="max-width:200px;white-space:normal;word-break:break-word;">
                                    <?php if ($is_active): ?><span class="active-dot" title="Active"></span><?php endif; ?>
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>
                                <td class="text-end" data-order="<?= $cr['working'] ?>"><?= $cr['working'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['callback'] ?>"><?= $cr['callback'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['rnr'] ?>"><?= $cr['rnr'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['non_qualified'] ?>"><?= $cr['non_qualified'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['unqualified'] ?>"><?= $cr['unqualified'] ?: '' ?></td>
                                <td class="text-end" data-order="<?= $cr['no_feedback'] ?>"><?= $cr['no_feedback'] ?: '' ?></td>
                                <td class="text-end fw-bold" data-order="<?= $tot ?>"><?= $tot ?: '' ?></td>
                                <td class="text-end" data-order="<?= (int)$row['spend'] ?>"><?= fmtMoney($row['spend']) ?></td>
                                <td class="text-end"><?= $cpl > 0 ? fmtMoney($cpl) : '–' ?></td>
                                <td class="text-end"><?= $rel > 0 ? '<span class="badge bg-success">'.$rel.'</span>' : '' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3">Total</td>
                                    <td class="text-end"><?= $ad_totals['working'] ?: '' ?></td>
                                    <td class="text-end"><?= $ad_totals['callback'] ?: '' ?></td>
                                    <td class="text-end"><?= $ad_totals['rnr'] ?: '' ?></td>
                                    <td class="text-end"><?= $ad_totals['non_qualified'] ?: '' ?></td>
                                    <td class="text-end"><?= $ad_totals['unqualified'] ?: '' ?></td>
                                    <td class="text-end"><?= $ad_totals['no_feedback'] ?: '' ?></td>
                                    <td class="text-end"><?= $ad_totals['total'] ?></td>
                                    <td class="text-end"><?= fmtMoney($ad_totals['spend']) ?></td>
                                    <td class="text-end"><?= $ad_totals['total'] > 0 ? fmtMoney(round($ad_totals['spend'] / $ad_totals['total'])) : '–' ?></td>
                                    <td class="text-end"><?= $ad_totals['relevancy'] > 0 ? '<span class="badge bg-success">'.$ad_totals['relevancy'].'</span>' : '' ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /tab-content -->
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-muted small py-2">
        VRX Ads &amp; Leads Dashboard &nbsp;|&nbsp; Powered by <strong>BYT</strong>
        &nbsp;|&nbsp; Last updated: <?= date('d M Y, h:i A') ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
    $('#tbl_form').DataTable({ order:[[1,'desc']], pageLength:25, responsive:true });
    // Camp: 0=name,1=working,2=callback,3=rnr,4=nq,5=unq,6=nofb,7=total,8=spend,9=cpl,10=relevancy
    $('#tbl_camp').DataTable({ order:[[8,'desc']], pageLength:25, responsive:true });
    // Adset: 0=camp,1=adset,2=working..8=total,9=spend,10=cpl,11=relevancy
    $('#tbl_adset').DataTable({ order:[[9,'desc']], pageLength:25, responsive:true });
    // Ad: 0=camp,1=adset,2=ad,3=working..9=total,10=spend,11=cpl,12=relevancy
    $('#tbl_ad').DataTable({ order:[[10,'desc']], pageLength:25, responsive:true });
});
</script>
</body>
</html>
