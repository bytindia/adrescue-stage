<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// ─── Auth ─────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['vrx_logged_in']) || $_SESSION['vrx_logged_in'] !== true) {
    header('Location: login.php'); exit();
}

$server_path = '/home/digitalb2k/public_html/adsninja/';
include $server_path . 'db.php';

// ─── AJAX: AI Lead Analysis endpoint ─────────────────────────────────────────
if (isset($_POST['ajax_ai']) || isset($_GET['ajax_ai'])) {
    set_time_limit(120);
    header('Content-Type: application/json');
    $out = ['results' => [], 'error' => ''];

    $api_key = '...';

    // Ensure cache table exists
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vrx_lead_ai_analysis (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        cache_key  VARCHAR(130) NOT NULL,
        is_genuine TINYINT NOT NULL DEFAULT 0 COMMENT '1=genuine,0=junk',
        reason     TEXT,
        analyzed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_ckey (cache_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Read POST body
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $leads_in = $input['leads'] ?? [];

    if (empty($leads_in)) { echo json_encode($out); exit(); }

    // Load already-cached results
    $keys_in  = array_map(fn($l) => $l['cache_key'], $leads_in);
    $esc_keys = implode(',', array_map(fn($k) => "'" . mysqli_real_escape_string($conn, $k) . "'", $keys_in));
    $cached   = [];
    $cRes = mysqli_query($conn, "SELECT cache_key, is_genuine, reason FROM vrx_lead_ai_analysis WHERE cache_key IN ($esc_keys)");
    if ($cRes) {
        while ($crow = mysqli_fetch_assoc($cRes)) {
            $cached[$crow['cache_key']] = ['is_genuine' => (int)$crow['is_genuine'], 'reason' => $crow['reason']];
        }
    }

    // Leads that still need analysis
    $todo = array_filter($leads_in, fn($l) => !isset($cached[$l['cache_key']]));

    if (!empty($todo)) {
        $content = "Evaluate each Meta LeadGen form submission. Decide: genuine (real prospect, meaningful answers) or junk (fake/spam/gibberish/test data).\n\n";
        $idx_map = [];
        $n = 1;
        foreach ($todo as $lead) {
            $content .= "Lead $n:\n";
            foreach ($lead['questions'] as $qk => $qv) {
                $content .= '  ' . ucwords(str_replace('_', ' ', $qk)) . ': ' . $qv . "\n";
            }
            $content .= "\n";
            $idx_map[$n] = $lead['cache_key'];
            $n++;
        }
        $content .= 'Return ONLY valid JSON: {"1":{"status":"genuine","reason":"short reason"},"2":{"status":"junk","reason":"..."}}';

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                "model"           => "gpt-4o-mini",
                "response_format" => ["type" => "json_object"],
                "messages"        => [
                    ["role" => "system", "content" => "You are a lead quality classifier for real-estate Meta LeadGen ads. Reply JSON only."],
                    ["role" => "user",   "content" => $content]
                ]
            ]),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "Authorization: Bearer $api_key"
            ]
        ]);
        $resp = curl_exec($ch);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($resp) {
            $api_res = @json_decode($resp, true);
            $raw     = preg_replace('/^```json\s*|\s*```$/s', '', trim($api_res['choices'][0]['message']['content'] ?? '{}'));
            $parsed  = @json_decode($raw, true);
            if (is_array($parsed)) {
                foreach ($parsed as $num => $analysis) {
                    $ck = $idx_map[(int)$num] ?? '';
                    if (!$ck) continue;
                    $is_genuine = strtolower(trim($analysis['status'] ?? '')) === 'genuine' ? 1 : 0;
                    $rsn_esc    = mysqli_real_escape_string($conn, $analysis['reason'] ?? '');
                    $ck_esc     = mysqli_real_escape_string($conn, $ck);
                    mysqli_query($conn,
                        "INSERT INTO vrx_lead_ai_analysis (cache_key, is_genuine, reason)
                         VALUES ('$ck_esc', $is_genuine, '$rsn_esc')
                         ON DUPLICATE KEY UPDATE is_genuine=$is_genuine, reason='$rsn_esc', analyzed_at=NOW()"
                    );
                    $cached[$ck] = ['is_genuine' => $is_genuine, 'reason' => $analysis['reason'] ?? ''];
                }
            } else {
                $out['error'] = 'JSON parse failed: ' . substr($raw, 0, 200);
            }
        } else {
            $out['error'] = 'curl failed: ' . $curl_err;
        }
    }

    $out['results'] = $cached;
    echo json_encode($out);
    exit();
}

// ─── WL Statuses ─────────────────────────────────────────────────────────────
$wl_statuses = ['working', 'workable'];

// ─── Date Range ───────────────────────────────────────────────────────────────
$dtRange1 = (isset($_GET['start_date']) && preg_match('/\d{4}-\d{2}-\d{2}/', $_GET['start_date']))
            ? $_GET['start_date'] : date('Y-m-01');
$dtRange2 = (isset($_GET['end_date'])   && preg_match('/\d{4}-\d{2}-\d{2}/', $_GET['end_date']))
            ? $_GET['end_date']   : date('Y-m-d');
$dt_label = date('d M', strtotime($dtRange1)) . ' – ' . date('d M Y', strtotime($dtRange2));

// ─── Meta Leads (includes adsetN + serialised Q&A) ───────────────────────────
$meta_leads = [];
$mRes = mysqli_query($conn,
    "SELECT name, phone, email, campN AS campaign, adsetN AS adset_name, adN AS ad_name,
            formN AS form_name, page_id, leadgen_id, lead AS lead_data, created AS lead_date
     FROM vrx_leads_meta
     WHERE DATE(created) BETWEEN '{$dtRange1}' AND '{$dtRange2}'
     ORDER BY created DESC"
);
$std_keys = ['full_name','email','phone_number','phone','email_address','first_name','last_name','name'];
function parseLeadData(?string $raw): array {
    if (empty($raw)) return [];
    // Try PHP unserialize first
    $ld = @unserialize($raw);
    // Fallback: try JSON decode
    if (!is_array($ld)) {
        $ld = @json_decode($raw, true);
    }
    return is_array($ld) ? $ld : [];
}
while ($r = mysqli_fetch_assoc($mRes)) {
    $r['questions'] = [];
    $ld = parseLeadData($r['lead_data'] ?? null);
    foreach ($ld as $k => $v) {
        if (!in_array(strtolower(trim($k)), $std_keys) && trim((string)$v) !== '')
            $r['questions'][$k] = $v;
    }
    unset($r['lead_data']);
    $meta_leads[] = $r;
}

// ─── LP / Google Leads ────────────────────────────────────────────────────────
$lp_leads = [];
$lRes = mysqli_query($conn,
    "SELECT name, phone, email, camp AS campaign, src AS source,
            api_res, created_at AS lead_date
     FROM vrx_leads_lp
     WHERE DATE(created_at) BETWEEN '{$dtRange1}' AND '{$dtRange2}'
     ORDER BY created_at DESC"
);
while ($r = mysqli_fetch_assoc($lRes)) $lp_leads[] = $r;

// ─── CRM Maps (latest status) ─────────────────────────────────────────────────
$crm_map_by_id    = [];
$crm_map_by_phone = [];
$phone_to_crm_lid = []; // phone last-10 => crm lead_id (for history lookup)
$crmRes = mysqli_query($conn,
    "SELECT c.lead_id, c.phone,
            (SELECT h.new_status FROM vrx_crm_lead_status_history h
             WHERE h.lead_id = c.lead_id ORDER BY h.id DESC LIMIT 1) AS latest_status
     FROM vrx_crm_leads c
     WHERE c.lead_id IS NOT NULL AND c.lead_id != ''"
);
while ($r = mysqli_fetch_assoc($crmRes)) {
    if (empty($r['latest_status'])) continue;
    $crm_map_by_id[trim($r['lead_id'])] = $r['latest_status'];
    if (!empty($r['phone'])) {
        $ph = substr(preg_replace('/\D/', '', $r['phone']), -10);
        if (strlen($ph) === 10) {
            $crm_map_by_phone[$ph]  = $r['latest_status'];
            $phone_to_crm_lid[$ph]  = trim($r['lead_id']);
        }
    }
}

// ─── AJAX: Lead History endpoint ──────────────────────────────────────────────
if (isset($_GET['ajax_history'])) {
    header('Content-Type: application/json');
    $lead_id = trim($_GET['lead_id'] ?? '');
    $phone   = trim($_GET['phone']   ?? '');
    $lid     = '';
    if ($lead_id !== '') {
        $lid = mysqli_real_escape_string($conn, $lead_id);
    } elseif ($phone !== '') {
        $ph10 = substr(preg_replace('/\D/', '', $phone), -10);
        if (strlen($ph10) === 10 && isset($phone_to_crm_lid[$ph10]))
            $lid = mysqli_real_escape_string($conn, $phone_to_crm_lid[$ph10]);
    }
    $history = [];
    if ($lid !== '') {
        $hRes = mysqli_query($conn,
            "SELECT * FROM vrx_crm_lead_status_history WHERE lead_id = '$lid' ORDER BY id ASC");
        if ($hRes) while ($hRow = mysqli_fetch_assoc($hRes)) $history[] = $hRow;
    }
    echo json_encode(['history' => $history, 'lead_id' => $lid]);
    exit();
}

// ─── "Ever Working/Workable" history map ─────────────────────────────────────
// green dot if the lead was EVER marked working/workable (any history entry)
$phone_ever_working  = []; // phone last-10 => true
$leadid_ever_working = []; // crm lead_id   => true
$ewQ = mysqli_query($conn,
    "SELECT DISTINCT c.phone, c.lead_id
     FROM vrx_crm_leads c
     JOIN vrx_crm_lead_status_history h ON h.lead_id = c.lead_id
     WHERE LOWER(h.new_status) IN ('working', 'workable')"
);
while ($r = mysqli_fetch_assoc($ewQ)) {
    if (!empty($r['phone'])) {
        $ph = substr(preg_replace('/\D/', '', $r['phone']), -10);
        if (strlen($ph) === 10) $phone_ever_working[$ph] = true;
    }
    if (!empty($r['lead_id'])) $leadid_ever_working[trim($r['lead_id'])] = true;
}

// ─── CRM Helpers ──────────────────────────────────────────────────────────────
function getCrmFeedbackById(string $lead_id, array &$map): string {
    $lid = trim($lead_id);
    return (!empty($lid) && isset($map[$lid])) ? $map[$lid] : '';
}
function getCrmFeedbackByPhone(string $phone, array &$map): string {
    $ph = substr(preg_replace('/\D/', '', $phone), -10);
    return (strlen($ph) === 10 && isset($map[$ph])) ? $map[$ph] : '';
}
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
function indianNumber(int $n): string {
    if ($n < 0) return '-' . indianNumber(-$n);
    $s = (string)$n;
    $len = strlen($s);
    if ($len <= 3) return $s;
    $last3 = substr($s, -3);
    $rest  = substr($s, 0, $len - 3);
    $parts = [];
    while (strlen($rest) > 2) { $parts[] = substr($rest, -2); $rest = substr($rest, 0, -2); }
    if ($rest !== '') $parts[] = $rest;
    return implode(',', array_reverse($parts)) . ',' . $last3;
}
function feedbackBadge(string $f): string {
    if ($f === '') return "<span class='badge bg-secondary'>No Feedback</span>";
    $fl = strtolower(trim($f));
    $map = [
        'working'        => 'bg-success',
        'workable'       => 'bg-success',
        'interested'     => 'bg-success',
        'not interested' => 'bg-danger',
        'non qualified'  => 'bg-danger',
        'call back'      => 'bg-warning text-dark',
        'callback'       => 'bg-warning text-dark',
        'text'           => 'bg-info text-dark',
        'whatsapp'       => 'bg-success',
        'email'          => 'bg-primary',
        'site visit'     => 'bg-purple',
        'sv'             => 'bg-purple',
        'visited'        => 'bg-info text-dark',
        'booked'         => 'bg-primary',
        'duplicate'      => 'bg-secondary',
        'invalid'        => 'bg-dark',
        'fresh leads'    => 'bg-info text-dark',
    ];
    foreach ($map as $key => $cls) {
        if (str_contains($fl, $key)) return "<span class='badge {$cls}'>".htmlspecialchars(ucwords($f))."</span>";
    }
    return "<span class='badge bg-secondary'>".htmlspecialchars(ucwords($f))."</span>";
}

// ─── Summary Stats ────────────────────────────────────────────────────────────
$today_dt      = date('Y-m-d');
$yesterday_dt  = date('Y-m-d', strtotime('-1 day'));
$week_start_dt = date('Y-m-d', strtotime('monday this week'));

// Meta
$m_total = count($meta_leads);
$m_today = $m_yest = $m_week = $m_wl = $m_nofb = 0;
foreach ($meta_leads as $lead) {
    $dt = substr($lead['lead_date'], 0, 10);
    if ($dt === $today_dt)     $m_today++;
    if ($dt === $yesterday_dt) $m_yest++;
    if ($dt >= $week_start_dt) $m_week++;
    $ph = substr(preg_replace('/\D/', '', $lead['phone'] ?? ''), -10);
    $fb = strlen($ph) === 10 ? strtolower(trim($crm_map_by_phone[$ph] ?? '')) : '';
    if ($fb === '')              $m_nofb++;
    if (in_array($fb, $wl_statuses)) $m_wl++;
}

// LP
$l_total = count($lp_leads);
$l_today = $l_yest = $l_week = $l_wl = $l_nofb = 0;
$l_rnr = $l_callback = $l_nq = $l_working = 0;
foreach ($lp_leads as $lead) {
    $dt = substr($lead['lead_date'], 0, 10);
    if ($dt === $today_dt)     $l_today++;
    if ($dt === $yesterday_dt) $l_yest++;
    if ($dt >= $week_start_dt) $l_week++;
    $lid = trim($lead['api_res'] ?? '');
    $fb  = !empty($lid) ? strtolower(trim($crm_map_by_id[$lid] ?? '')) : '';
    if ($fb === '')              $l_nofb++;
    if (in_array($fb, $wl_statuses)) $l_wl++;
    $cat = categorizeCrmStatus($fb);
    if ($cat === 'rnr')           $l_rnr++;
    if ($cat === 'callback')      $l_callback++;
    if ($cat === 'non_qualified') $l_nq++;
    if ($cat === 'working')       $l_working++;
}

// Meta feedback counts
$m_rnr = $m_callback = $m_nq = $m_working = 0;
foreach ($meta_leads as $lead) {
    $ph  = substr(preg_replace('/\D/', '', $lead['phone'] ?? ''), -10);
    $fb  = strlen($ph) === 10 ? strtolower(trim($crm_map_by_phone[$ph] ?? '')) : '';
    $cat = categorizeCrmStatus($fb);
    if ($cat === 'rnr')           $m_rnr++;
    if ($cat === 'callback')      $m_callback++;
    if ($cat === 'non_qualified') $m_nq++;
    if ($cat === 'working')       $m_working++;
}

// ─── AI cache: load already-analysed results from DB (fast, no API call) ─────
function aiCacheKey(array $lead): string {
    $lgid = trim($lead['leadgen_id'] ?? '');
    if ($lgid !== '') return $lgid;
    $ph = substr(preg_replace('/\D/', '', $lead['phone'] ?? ''), -10);
    return strlen($ph) === 10 ? 'ph_' . $ph : '';
}
$ai_cache = [];
// Silently skip if table doesn't exist yet (first run)
$_tbl_chk = @mysqli_query($conn, "SELECT 1 FROM vrx_lead_ai_analysis LIMIT 1");
if ($_tbl_chk !== false) {
    $all_keys = array_unique(array_filter(array_map('aiCacheKey', $meta_leads)));
    if (!empty($all_keys)) {
        $esc_keys = implode(',', array_map(fn($k) => "'" . mysqli_real_escape_string($conn, $k) . "'", $all_keys));
        $cRes = mysqli_query($conn, "SELECT cache_key, is_genuine, reason FROM vrx_lead_ai_analysis WHERE cache_key IN ($esc_keys)");
        if ($cRes) {
            while ($crow = mysqli_fetch_assoc($cRes)) {
                $ai_cache[$crow['cache_key']] = ['is_genuine' => (int)$crow['is_genuine'], 'reason' => $crow['reason']];
            }
        }
    }
}

$vrx_active_page = 'leads';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VRX – Leads</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: none; color: #1a2942; }
        .navbar { background: linear-gradient(135deg, #0f2044, #1a3a6e); box-shadow: 0 2px 12px rgba(0,0,0,0.2); }
        .navbar-brand { font-weight: 900; font-size: 1.5rem; letter-spacing: 4px; color: #fff !important; }
        .filter-bar { background:#f5f5f5; border-radius:14px; padding:10px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .filter-bar .btn { border-radius:8px; }

        /* Nav tabs — active uses navbar gradient */
        .nav-tabs { border-bottom: 2px solid #1a3a6e; gap:3px; }
        .nav-tabs .nav-link { font-size:0.82rem; font-weight:600; color:#6b7280; border:1px solid transparent; padding:7px 16px; border-radius:6px 6px 0 0; }
        .nav-tabs .nav-link:hover:not(.active) { color:#0f2044; background:#e8eef8; }
        .nav-tabs .nav-link.active { background:linear-gradient(135deg,#0f2044,#1a3a6e) !important; color:#fff !important; border-color:#0f2044; font-weight:700; }
        .tab-content { background:#fff; border:1px solid #1a3a6e; border-top:none; border-radius:0 0 14px 14px; overflow:hidden; }

        .leads-count { font-size:0.7rem; background:rgba(255,255,255,.22); color:#fff; font-weight:700; border-radius:10px; padding:1px 8px; }
        .leads-count-inactive { font-size:0.7rem; background:#e0e7ff; color:#3730a3; font-weight:700; border-radius:10px; padding:1px 8px; }

        /* Summary mini-cards */
        .summary-cards { display:flex; flex-wrap:wrap; gap:8px; padding:12px 16px 4px; }
        .s-card { background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:8px 14px; min-width:90px; text-align:center; flex:1; }
        .s-card .s-label { font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; }
        .s-card .s-val  { font-size:1.3rem; font-weight:800; line-height:1.1; color:#1a2942; }
        .s-card.c-wl   .s-val { color:#1a7a46; }
        .s-card.c-nofb .s-val { color:#b91c1c; }
        .s-card.c-today .s-val { color:#0277bd; }

        /* DataTable */
        table.dataTable thead th { background:#f8fafc !important; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280 !important; }
        table.dataTable tbody tr:hover { background:#f0f4ff; }
        table.dataTable { font-size:0.83rem; }
        .dataTables_wrapper .dataTables_filter input { border-radius:8px; border:1px solid #dee2e6; padding:4px 10px; font-size:0.82rem; }
        .dataTables_wrapper .dataTables_length select { border-radius:8px; border:1px solid #dee2e6; padding:3px 6px; }
        .dataTables_wrapper { padding:12px 16px; }

        /* Green dot — ever working in history */
        .wl-dot { display:inline-block; width:9px; height:9px; background:#22c55e; border-radius:50%; vertical-align:middle; margin-right:4px; flex-shrink:0; box-shadow:0 0 0 2px #fff, 0 0 0 3px #22c55e40; }

        /* AI quality dot — shown inside Custom Questions cell */
        .ai-dot { display:inline-block; width:10px; height:10px; border-radius:50%; vertical-align:middle; margin-right:5px; flex-shrink:0; cursor:help; }
        .ai-dot.genuine { background:#16a34a; box-shadow:0 0 0 2px #fff, 0 0 0 3px #16a34a55; }
        .ai-dot.junk    { background:#f97316; box-shadow:0 0 0 2px #fff, 0 0 0 3px #f9731655; }

        /* Q&A inline column */
        .qa-inline { display:flex; flex-direction:column; gap:2px; }
        .qa-item { font-size:0.75rem; line-height:1.3; }
        .qa-key { color:#6b7280; font-weight:600; margin-right:3px; }
        .qa-val { color:#1e293b; }

        .badge-lp { background:#e6f9f0; color:#1a7a46; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:10px; }
    </style>
</head>
<body>
<?php include '_nav.php'; ?>

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
            <a href="loading.php?pg=leads.php&start_date=<?= $s ?>&end_date=<?= $e ?>" class="btn btn-sm <?= $act ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
        <small class="text-muted">
            Meta: <strong class="text-primary"><?= $m_total ?></strong> &nbsp;
            LP/Google: <strong class="text-success"><?= $l_total ?></strong>
        </small>
    </div>

    <!-- ── TABS ── -->
    <ul class="nav nav-tabs" id="leadsTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMeta" type="button">
                Meta Leads <span class="leads-count ms-1"><?= $m_total ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabGoogle" type="button">
                Google / LP Leads <span class="leads-count-inactive ms-1"><?= $l_total ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ══ META LEADS TAB ══ -->
        <div class="tab-pane fade show active" id="tabMeta" role="tabpanel">

            <!-- Feedback count chips -->
            <div style="padding:10px 16px 4px;display:flex;flex-wrap:wrap;gap:6px;font-size:0.75rem;">
                <span class="badge bg-success">Working – <?= indianNumber($m_working) ?></span>
                <span class="badge bg-warning text-dark">Callback – <?= indianNumber($m_callback) ?></span>
                <span class="badge bg-secondary">RNR – <?= indianNumber($m_rnr) ?></span>
                <span class="badge bg-danger">Not Qualified – <?= indianNumber($m_nq) ?></span>
                <span class="badge bg-dark">No Feedback – <?= indianNumber($m_nofb) ?></span>
            </div>

            <!-- Summary cards -->
            <div class="summary-cards">
                <div class="s-card">
                    <div class="s-label">Total</div>
                    <div class="s-val"><?= indianNumber($m_total) ?></div>
                </div>
                <div class="s-card c-wl">
                    <div class="s-label">Workable</div>
                    <div class="s-val"><?= indianNumber($m_wl) ?></div>
                </div>
                <div class="s-card c-nofb">
                    <div class="s-label">No Feedback</div>
                    <div class="s-val"><?= indianNumber($m_nofb) ?></div>
                </div>
                <div class="s-card c-today">
                    <div class="s-label">Today</div>
                    <div class="s-val"><?= indianNumber($m_today) ?></div>
                </div>
                <div class="s-card">
                    <div class="s-label">Yesterday</div>
                    <div class="s-val"><?= indianNumber($m_yest) ?></div>
                </div>
                <div class="s-card">
                    <div class="s-label">This Week</div>
                    <div class="s-val"><?= indianNumber($m_week) ?></div>
                </div>
            </div>

            <!-- Table -->
            <div class="dataTables_wrapper" style="padding:12px 16px;">
                <table id="tblMeta" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Campaign</th>
                            <th>Adset</th>
                            <th>Ad</th>
                            <th>Date</th>
                            <th>Custom Questions</th>
                            <th>CRM Feedback</th>
                            <th class="text-center">History</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($meta_leads as $i => $lead):
                        $ph_raw  = $lead['phone'] ?? '';
                        $ph10    = substr(preg_replace('/\D/', '', $ph_raw), -10);
                        $fb      = getCrmFeedbackByPhone($ph_raw, $crm_map_by_phone);
                        $everWk  = strlen($ph10) === 10 && isset($phone_ever_working[$ph10]);
                        $hasQA   = !empty($lead['questions']);
                        // AI quality lookup — use same cache key logic
                        $aiCk    = aiCacheKey($lead);
                        $aiData  = ($aiCk !== '' && isset($ai_cache[$aiCk])) ? $ai_cache[$aiCk] : null;
                        $aiStatus = null; // null=not analysed, 1=genuine, 0=junk
                        if ($aiData !== null && $hasQA) {
                            $aiStatus = (int)$aiData['is_genuine'];
                        }
                        $aiReason = $aiData['reason'] ?? '';
                    ?>
                    <tr<?php if ($hasQA && $aiStatus === null && $aiCk !== ''): ?> data-ai-key="<?= htmlspecialchars($aiCk, ENT_QUOTES) ?>" data-questions="<?= htmlspecialchars(json_encode($lead['questions'], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>"<?php endif; ?>>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($lead['name'] ?? '–') ?></td>
                        <td><a href="tel:<?= htmlspecialchars($ph_raw) ?>"><?= htmlspecialchars($ph_raw ?: '–') ?></a></td>
                        <td class="small"><?= htmlspecialchars($lead['campaign'] ?? '–') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($lead['adset_name'] ?? '–') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($lead['ad_name'] ?? '–') ?></td>
                        <td class="text-nowrap">
                            <small><?= $lead['lead_date'] ? date('d M Y, H:i', strtotime($lead['lead_date'])) : '–' ?></small>
                        </td>
                        <td style="max-width:220px;" data-order="<?= $aiStatus === 1 ? 2 : ($aiStatus === 0 ? 1 : ($hasQA ? 0 : -1)) ?>">
                            <?php if ($hasQA): ?>
                            <div class="qa-inline">
                                <?php if ($aiStatus === 1): ?>
                                <div class="mb-1">
                                    <span class="ai-dot genuine" title="Genuine Lead — <?= htmlspecialchars($aiReason) ?>"></span>
                                    <small class="text-success fw-semibold" style="font-size:0.7rem;">Genuine</small>
                                </div>
                                <?php elseif ($aiStatus === 0): ?>
                                <div class="mb-1">
                                    <span class="ai-dot junk" title="Not Genuine — <?= htmlspecialchars($aiReason) ?>"></span>
                                    <small class="text-warning fw-semibold" style="font-size:0.7rem;color:#ea580c!important;">Not Genuine</small>
                                </div>
                                <?php endif; ?>
                                <?php foreach ($lead['questions'] as $qk => $qv): ?>
                                <div class="qa-item">
                                    <span class="qa-key"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $qk))) ?>:</span>
                                    <span class="qa-val"><?= htmlspecialchars((string)$qv) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:0.72rem;">–</span>
                            <?php endif; ?>
                        </td>
                        <td data-order="<?= htmlspecialchars(strtolower($fb ?: 'zzz'), ENT_QUOTES) ?>">
                            <?php if ($everWk): ?><span class="wl-dot" title="Was Working/Workable in history"></span><?php endif; ?>
                            <?= feedbackBadge($fb) ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-secondary py-0 px-2 btn-history"
                                    data-phone="<?= htmlspecialchars($ph_raw, ENT_QUOTES) ?>"
                                    data-name="<?= htmlspecialchars($lead['name'] ?? '', ENT_QUOTES) ?>"
                                    title="View status history" style="font-size:0.8rem;">
                                🕐
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($meta_leads)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-3">No Meta leads in this period.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ GOOGLE / LP LEADS TAB ══ -->
        <div class="tab-pane fade" id="tabGoogle" role="tabpanel">

            <!-- Feedback count chips -->
            <div style="padding:10px 16px 4px;display:flex;flex-wrap:wrap;gap:6px;font-size:0.75rem;">
                <span class="badge bg-success">Working – <?= indianNumber($l_working) ?></span>
                <span class="badge bg-warning text-dark">Callback – <?= indianNumber($l_callback) ?></span>
                <span class="badge bg-secondary">RNR – <?= indianNumber($l_rnr) ?></span>
                <span class="badge bg-danger">Not Qualified – <?= indianNumber($l_nq) ?></span>
                <span class="badge bg-dark">No Feedback – <?= indianNumber($l_nofb) ?></span>
            </div>

            <!-- Summary cards -->
            <div class="summary-cards">
                <div class="s-card">
                    <div class="s-label">Total</div>
                    <div class="s-val"><?= indianNumber($l_total) ?></div>
                </div>
                <div class="s-card c-wl">
                    <div class="s-label">Workable</div>
                    <div class="s-val"><?= indianNumber($l_wl) ?></div>
                </div>
                <div class="s-card c-nofb">
                    <div class="s-label">No Feedback</div>
                    <div class="s-val"><?= indianNumber($l_nofb) ?></div>
                </div>
                <div class="s-card c-today">
                    <div class="s-label">Today</div>
                    <div class="s-val"><?= indianNumber($l_today) ?></div>
                </div>
                <div class="s-card">
                    <div class="s-label">Yesterday</div>
                    <div class="s-val"><?= indianNumber($l_yest) ?></div>
                </div>
                <div class="s-card">
                    <div class="s-label">This Week</div>
                    <div class="s-val"><?= indianNumber($l_week) ?></div>
                </div>
            </div>

            <!-- Table -->
            <div class="dataTables_wrapper" style="padding:12px 16px;">
                <table id="tblLP" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Campaign</th>
                            <th>Source</th>
                            <th>Date</th>
                            <th>CRM Feedback</th>
                            <th class="text-center">History</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lp_leads as $i => $lead):
                        $lid    = trim($lead['api_res'] ?? '');
                        $fb     = getCrmFeedbackById($lid, $crm_map_by_id);
                        $everWk = !empty($lid) && isset($leadid_ever_working[$lid]);
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($lead['name'] ?? '–') ?></td>
                        <td><a href="tel:<?= htmlspecialchars($lead['phone'] ?? '') ?>"><?= htmlspecialchars($lead['phone'] ?? '–') ?></a></td>
                        <td class="small text-muted"><?= htmlspecialchars($lead['email'] ?? '–') ?></td>
                        <td class="small"><?= htmlspecialchars($lead['campaign'] ?? '–') ?></td>
                        <td><span class="badge-lp"><?= htmlspecialchars($lead['source'] ?? 'LP') ?></span></td>
                        <td class="text-nowrap">
                            <small><?= $lead['lead_date'] ? date('d M Y, H:i', strtotime($lead['lead_date'])) : '–' ?></small>
                        </td>
                        <td>
                            <?php if ($everWk): ?><span class="wl-dot" title="Was Working/Workable in history"></span><?php endif; ?>
                            <?= feedbackBadge($fb) ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-secondary py-0 px-2 btn-history"
                                    data-lead-id="<?= htmlspecialchars($lid, ENT_QUOTES) ?>"
                                    data-name="<?= htmlspecialchars($lead['name'] ?? '', ENT_QUOTES) ?>"
                                    title="View status history" style="font-size:0.8rem;">
                                🕐
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lp_leads)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">No LP/Google leads in this period.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /tab-content -->

</div>

<!-- ── Lead History Modal ── -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0f2044,#1a3a6e);padding:12px 16px;">
                <h6 class="modal-title text-white mb-0">🕐 Status History</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:14px 16px;" id="historyModalBody">
                <div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function() {
    var tblMeta = $('#tblMeta').DataTable({
        order: [[6, 'desc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: [9] }],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_' }
    });

    $('#tblLP').DataTable({
        order: [[6, 'desc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: [7, 8] }],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_' }
    });

    // Re-adjust when switching tabs
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // ── Lead History Modal ─────────────────────────────────────────────────────
    var $historyModal = new bootstrap.Modal(document.getElementById('historyModal'));

    $(document).on('click', '.btn-history', function() {
        var leadId = $(this).data('lead-id') || '';
        var phone  = $(this).data('phone')   || '';
        var name   = $(this).data('name')    || 'Lead';
        $('#historyModal .modal-title').text('🕐 ' + name);
        $('#historyModalBody').html('<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>');
        $historyModal.show();

        var params = leadId ? 'ajax_history=1&lead_id=' + encodeURIComponent(leadId)
                            : 'ajax_history=1&phone='   + encodeURIComponent(phone);
        $.getJSON('?' + params, function(data) {
            var hist = data.history || [];
            if (hist.length === 0) {
                $('#historyModalBody').html('<p class="text-muted text-center small py-2">No history found.</p>');
                return;
            }
            var html = '<div style="font-size:0.82rem;">';
            $.each(hist, function(i, h) {
                var arrow = h.old_status
                    ? '<span class="text-muted">' + h.old_status + '</span> → <strong>' + h.new_status + '</strong>'
                    : '<strong>' + h.new_status + '</strong>';
                var dt = h.changed_at || h.created_at || h.date || '';
                var dtStr = dt ? '<small class="text-muted ms-1" style="font-size:0.72rem;">' + dt + '</small>' : '';
                html += '<div class="d-flex align-items-start gap-2 mb-2">';
                html += '<span class="badge rounded-pill bg-light text-dark border" style="min-width:22px;font-size:0.7rem;">' + (i+1) + '</span>';
                html += '<div>' + arrow + dtStr + '</div>';
                html += '</div>';
            });
            html += '</div>';
            $('#historyModalBody').html(html);
        }).fail(function() {
            $('#historyModalBody').html('<p class="text-danger text-center small py-2">Failed to load history.</p>');
        });
    });

    // ── AI Lead Analysis (async, runs after page load) ──────────────────────
    (function() {
        var toAnalyze = [];
        // Collect rows that have questions but no cached AI result yet
        $('#tblMeta tbody tr[data-ai-key]').each(function() {
            var ck = $(this).data('ai-key');
            var qs = $(this).data('questions');
            // jQuery auto-parses data-* JSON strings into objects
            if (ck && qs && typeof qs === 'object' && Object.keys(qs).length > 0) {
                toAnalyze.push({ cache_key: String(ck), questions: qs });
            }
        });

        if (toAnalyze.length === 0) return;

        // Show a subtle loading indicator in the tab header
        var $tabBtn = $('button[data-bs-target="#tabMeta"]');
        var origHtml = $tabBtn.html();
        $tabBtn.append(' <span class="spinner-border spinner-border-sm ms-1" style="width:.65rem;height:.65rem;border-width:2px;" id="aiSpinner"></span>');

        $.ajax({
            url: '?ajax_ai=1',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ leads: toAnalyze }),
            timeout: 90000,
            success: function(resp) {
                if (!resp || !resp.results) return;
                var results = resp.results;

                // Update each row that was sent for analysis
                $('#tblMeta tbody tr[data-ai-key]').each(function() {
                    var ck  = String($(this).data('ai-key'));
                    var res = results[ck];
                    if (!res) return;

                    var isGenuine = parseInt(res.is_genuine, 10) === 1;
                    var reason    = res.reason || '';

                    // Custom Questions cell is column index 7 (0-based)
                    var $qaDiv = $(this).find('td').eq(7).find('.qa-inline');
                    if ($qaDiv.length === 0) return;

                    // Remove any existing indicator (safety)
                    $qaDiv.find('.ai-indicator').remove();

                    var dotClass = isGenuine ? 'genuine' : 'junk';
                    var labelHtml = isGenuine
                        ? '<small class="text-success fw-semibold" style="font-size:0.7rem;">Genuine</small>'
                        : '<small class="fw-semibold" style="font-size:0.7rem;color:#ea580c!important;">Not Genuine</small>';
                    var titleTxt  = (isGenuine ? 'Genuine Lead' : 'Not Genuine')
                                  + (reason ? ' \u2014 ' + reason : '');

                    var $indicator = $('<div class="mb-1 ai-indicator">')
                        .append($('<span class="ai-dot ' + dotClass + '">').attr('title', titleTxt))
                        .append($(labelHtml));

                    $qaDiv.prepend($indicator);
                });
            },
            error: function(xhr) {
                console.warn('AI analysis AJAX error:', xhr.status, xhr.statusText);
            },
            complete: function() {
                $('#aiSpinner').remove();
            }
        });
    })();
});
</script>
</body>
</html>
