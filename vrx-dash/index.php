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

// ─── DB + Helpers ─────────────────────────────────────────────────────────────
include '../db.php';

// Fetch tokens from users table (same user row used by main app)
$userRes = mysqli_query($conn, "SELECT access_token, g_refresh_token, g_mcc FROM users WHERE tbl_id = 2 LIMIT 1");
$userRow = mysqli_fetch_assoc($userRes);
$access_token    = $userRow['access_token']    ?? '';
$g_refresh_token = $userRow['g_refresh_token'] ?? '';
$g_mcc           = (int)($userRow['g_mcc']     ?? 0);

// Google Ads API class
include '../dash/google-campaigns.php';

//$api_ver = 'v24.0';

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
$wl_statuses_raw = ['interested','call back','callback','text','whatsapp me',
                    'email detail','site visited','site visit','sv'];
$wl_in = "'" . implode("','", $wl_statuses_raw) . "'";

function wlLeads(mysqli $db, string $start, string $end, string $wl_in): int {
    $r = mysqli_query($db,
        "SELECT COUNT(*) AS c FROM vrx_crm_leads
         WHERE DATE(created_at) BETWEEN '{$start}' AND '{$end}'
           AND LOWER(TRIM(lead_feedback)) IN ({$wl_in})"
    );
    if ($r) { $row = mysqli_fetch_assoc($r); return (int)$row['c']; }
    return 0;
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

// Workable leads (CRM)
$wl_period    = wlLeads($conn, $dtRange1, $dtRange2, $wl_in);
$wl_today     = wlLeads($conn, $today, $today, $wl_in);
$wl_yesterday = wlLeads($conn, $yesterday, $yesterday, $wl_in);
$wl_thisweek  = wlLeads($conn, $thisWeekStart, $today, $wl_in);
$wl_lastweek  = wlLeads($conn, $lastWeekStart, $lastWeekEnd, $wl_in);
$wl_thismonth = wlLeads($conn, $thisMonthStart, $today, $wl_in);

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

// WL by date from CRM
$r = mysqli_query($conn, "SELECT DATE(created_at) AS dt, COUNT(*) AS cnt FROM vrx_crm_leads WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}' AND LOWER(TRIM(lead_feedback)) IN ({$wl_in}) GROUP BY DATE(created_at)");
while ($row = mysqli_fetch_assoc($r)) {
    $daily_table[$row['dt']]['wl'] = ($daily_table[$row['dt']]['wl'] ?? 0) + (int)$row['cnt'];
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

// ─── CRM Feedback Summary ─────────────────────────────────────────────────────
$crm_res = mysqli_query($conn,
    "SELECT lead_feedback, COUNT(*) AS cnt FROM vrx_crm_leads
     WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}'
       AND lead_feedback IS NOT NULL AND lead_feedback != ''
     GROUP BY lead_feedback ORDER BY cnt DESC"
);
$crm_data = []; $crm_total = 0;
while ($r = mysqli_fetch_assoc($crm_res)) { $crm_data[] = $r; $crm_total += (int)$r['cnt']; }

// ─── Recent Leads ─────────────────────────────────────────────────────────────
$recent_leads = [];
$r = mysqli_query($conn, "SELECT name, phone, camp AS campaign, src AS source, created_at AS lead_date, 'LP' AS platform FROM vrx_leads_lp ORDER BY created_at DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) $recent_leads[] = $row;
$r = mysqli_query($conn, "SELECT name, phone, campN AS campaign, 'Meta' AS source, created AS lead_date, 'Meta' AS platform FROM vrx_leads_meta ORDER BY created DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) $recent_leads[] = $row;
usort($recent_leads, fn($a, $b) => strtotime($b['lead_date']) - strtotime($a['lead_date']));
$recent_leads = array_slice($recent_leads, 0, 15);

// ─── Formatting ───────────────────────────────────────────────────────────────
function fmtMoney(float $n): string { return '₹' . number_format((int)$n); }
function fmtDate(?string $d): string { return $d ? date('d M', strtotime($d)) : '–'; }
function badgePlatform(string $p): string {
    $map = ['Meta' => 'bg-primary','LP' => 'bg-success','Google' => 'bg-warning text-dark'];
    return "<span class='badge ".($map[$p] ?? 'bg-secondary')."'>{$p}</span>";
}
function feedbackBadge(string $f): string {
    $fl = strtolower(trim($f));
    $map = ['interested'=>'bg-success','not interested'=>'bg-danger','call back'=>'bg-warning text-dark',
            'callback'=>'bg-warning text-dark','text'=>'bg-info text-dark','whatsapp'=>'bg-success',
            'email'=>'bg-primary','site visit'=>'bg-purple','sv'=>'bg-purple',
            'visited'=>'bg-info text-dark','booked'=>'bg-primary','duplicate'=>'bg-secondary','invalid'=>'bg-dark'];
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
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f4f8; color: #1a2942; }

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
        .stat-icon  { font-size: 1.8rem; opacity: 0.12; position: absolute; right: 14px; top: 12px; }

        /* Period cards – dual value */
        .period-card .card-body { padding: 1rem 1.25rem 1.6rem; }
        .period-card .period-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .period-card .period-date  { position: absolute; bottom: 9px; right: 12px; font-size: 0.65rem; color: #aaa; }
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

        .filter-bar { background:#fff; border-radius:14px; padding:10px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
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

        @media (max-width:576px) { .stat-value { font-size:1.4rem; } .period-card .val-num { font-size:1.3rem; } }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar navbar-expand-lg sticky-top mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#"><img src="vrx-logo-white.png" style="height: 25px;" /></a>
        <span class="text-white opacity-50 small d-none d-md-inline">Ads &amp; Leads Dashboard</span>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark py-2 px-3 rounded-pill small fw-semibold">
                📅 <?= htmlspecialchars($dt_label) ?>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 pb-5">

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
            <a href="?start_date=<?= $s ?>&end_date=<?= $e ?>" class="btn btn-sm <?= $act ?>"><?= $lbl ?></a>
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
            <div class="card period-card stat-card card-teal position-relative">
                <div class="card-body">
                    <div class="stat-icon">☀️</div>
                    <div class="period-label">Today</div>
                    <div class="period-date"><?= date('d M Y') ?></div>
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
            <div class="card period-card stat-card card-amber position-relative">
                <div class="card-body">
                    <div class="stat-icon">🌙</div>
                    <div class="period-label">Yesterday</div>
                    <div class="period-date"><?= date('d M Y', strtotime('-1 day')) ?></div>
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
            <div class="card period-card stat-card card-cyan position-relative">
                <div class="card-body">
                    <div class="stat-icon">📆</div>
                    <div class="period-label">This Week</div>
                    <div class="period-date"><?= date('d M', strtotime('monday this week')) ?> – <?= date('d M') ?></div>
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
            <div class="card period-card stat-card card-pink position-relative">
                <div class="card-body">
                    <div class="stat-icon">🗓️</div>
                    <div class="period-label">Last Week</div>
                    <div class="period-date">
                        <?= date('d M', strtotime('monday last week')) ?> –
                        <?= date('d M', strtotime('sunday last week')) ?>
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
            <div class="card period-card stat-card card-blue position-relative">
                <div class="card-body">
                    <div class="stat-icon">📅</div>
                    <div class="period-label">This Month</div>
                    <div class="period-date"><?= date('M Y') ?></div>
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
            <div class="card period-card stat-card card-purple position-relative">
                <div class="card-body">
                    <div class="stat-icon">📊</div>
                    <div class="period-label">Selected Period</div>
                    <div class="period-date"><?= htmlspecialchars($dt_label) ?></div>
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

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-blue position-relative">
                <div class="card-body">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Total Spend</div>
                    <div class="stat-value"><?= fmtMoney($total_spend) ?></div>
                    <div class="stat-sub">Meta + Google</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-orange position-relative">
                <div class="card-body">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Total Leads</div>
                    <div class="stat-value"><?= $total_leads ?></div>
                    <div class="stat-sub">
                        <span class="badge bg-success me-1" style="font-size:0.65rem;">LP</span><?= $lp_month ?>
                        &nbsp;<span class="badge bg-primary" style="font-size:0.65rem;">Meta</span> <?= $meta_month_cnt ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-purple position-relative">
                <div class="card-body">
                    <div class="stat-icon">🧮</div>
                    <div class="stat-label">CPL</div>
                    <div class="stat-value"><?= fmtMoney($cpl_avg) ?></div>
                    <div class="stat-sub">Avg Cost / Lead</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-green position-relative">
                <div class="card-body">
                    <div class="stat-icon">↓</div>
                    <div class="stat-label">Low CPL</div>
                    <div class="stat-value"><?= $low_cpl_val !== null ? fmtMoney($low_cpl_val) : '–' ?></div>
                    <div class="stat-sub"><?= $low_cpl_date ? fmtDate($low_cpl_date) : 'No data' ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-red position-relative">
                <div class="card-body">
                    <div class="stat-icon">↑</div>
                    <div class="stat-label">High CPL</div>
                    <div class="stat-value"><?= $high_cpl_val !== null ? fmtMoney($high_cpl_val) : '–' ?></div>
                    <div class="stat-sub"><?= $high_cpl_date ? fmtDate($high_cpl_date) : 'No data' ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card card-indigo position-relative">
                <div class="card-body">
                    <div class="stat-icon">📡</div>
                    <div class="stat-label">Platform Leads</div>
                    <div class="stat-value"><?= $total_leads_api ?></div>
                    <div class="stat-sub">API reported</div>
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
        <?php if (!empty($crm_data)): ?>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">🗂️ CRM Feedback
                        <span class="badge bg-secondary ms-1"><?= $crm_total ?> total</span>
                    </h6>
                    <?php foreach ($crm_data as $fb):
                        $pct = $crm_total > 0 ? round(($fb['cnt'] / $crm_total) * 100) : 0; ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small" style="min-width:150px;"><?= feedbackBadge($fb['lead_feedback']) ?></div>
                        <div class="small fw-bold text-end" style="min-width:28px;"><?= $fb['cnt'] ?></div>
                        <div class="flex-grow-1 ms-2">
                            <div class="feedback-bar"><div class="feedback-fill" style="width:<?= $pct ?>%;"></div></div>
                        </div>
                        <div class="small text-muted ms-1" style="min-width:32px;"><?= $pct ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECTION 4 – RECENT LEADS
    ═══════════════════════════════════════════════════════════ -->
    <div class="section-title">📋 Recent Leads</div>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover dash-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Campaign</th>
                            <th>Source</th>
                            <th>Platform</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recent_leads)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No leads found</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_leads as $i => $lead): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($lead['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($lead['phone'] ?? '') ?></td>
                            <td class="text-truncate" style="max-width:180px;"><?= htmlspecialchars($lead['campaign'] ?? '') ?></td>
                            <td><?= htmlspecialchars($lead['source'] ?? '') ?></td>
                            <td><?= badgePlatform($lead['platform']) ?></td>
                            <td class="text-muted"><?= date('d M H:i', strtotime($lead['lead_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-muted small py-2">
        VRX Ads &amp; Leads Dashboard &nbsp;|&nbsp; Powered by <strong>BYT</strong>
        &nbsp;|&nbsp; Last updated: <?= date('d M Y, h:i A') ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
