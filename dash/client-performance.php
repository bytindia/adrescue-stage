<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();
date_default_timezone_set("Asia/Calcutta");

include __DIR__ . '/../db.php';
if (!isset($no_auth) || $no_auth != 1) { Auth(); }
if (!isset($_SESSION['proj_list'])) $_SESSION['proj_list'] = [];
$header_no = 1; // Hide client dropdown (page shows all clients)

$pgHeadline = 'Client Performance';
ini_set('display_errors', 0);
error_reporting(0);

$media_by = array(1=>'Ramesh', 14=>'Charan', 15=>'Mughil', 16=>'Karthik');

// Fetch all clients with non-empty client_ty (include med_by)
$allClients = [];
$stmt = @mysqli_query($conn, "SELECT tbl_id, client_name, client_ty, med_by FROM dashboard_accounts 
    WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' 
    AND delete_status=0 
    AND client_ty IS NOT NULL 
    AND TRIM(client_ty) != ''
    ORDER BY client_name ASC");
if ($stmt) {
    while ($row = mysqli_fetch_assoc($stmt)) {
        $ty = isset($row['client_ty']) ? trim($row['client_ty']) : '';
        $row['client_ty_norm'] = $ty;
        $row['med_by'] = isset($row['med_by']) ? (int)$row['med_by'] : 0;
        $allClients[] = $row;
    }
}

// Fetch last updated per account from clients_performance
$lastUpdated = [];
$luRes = @mysqli_query($conn, "SELECT account_tbl_id, MAX(updated) AS last_up FROM clients_performance 
    WHERE account_tbl_id IN (SELECT tbl_id FROM dashboard_accounts WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' AND delete_status=0)
    GROUP BY account_tbl_id");
if ($luRes) {
    while ($lu = mysqli_fetch_assoc($luRes)) {
        $lastUpdated[$lu['account_tbl_id']] = $lu['last_up'];
    }
}

// Fetch saved performance data for all accounts (run dash/clients_performance.sql first if table missing)
$perfData = [];
$perfRes = @mysqli_query($conn, "SELECT account_tbl_id, custom_label, `value`, cost_per, sort_order, id FROM clients_performance 
    WHERE account_tbl_id IN (SELECT tbl_id FROM dashboard_accounts WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' AND delete_status=0)
    ORDER BY account_tbl_id, sort_order, id");
if ($perfRes) {
    while ($pr = mysqli_fetch_assoc($perfRes)) {
        $aid = $pr['account_tbl_id'];
        if (!isset($perfData[$aid])) $perfData[$aid] = [];
        $perfData[$aid][] = $pr;
    }
}

// Fetch latest snapshot metrics (Spend, Lead, Purchase, CPL, cPP) from client_performance_snapshot
$snapshotData = [];
$snapRes = @mysqli_query($conn, "SELECT s.* FROM client_performance_snapshot s
    INNER JOIN (SELECT account_tbl_id, MAX(period_end) AS max_end FROM client_performance_snapshot
        WHERE account_tbl_id IN (SELECT tbl_id FROM dashboard_accounts WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' AND delete_status=0)
        GROUP BY account_tbl_id) latest ON s.account_tbl_id = latest.account_tbl_id AND s.period_end = latest.max_end
    WHERE s.account_tbl_id IN (SELECT tbl_id FROM dashboard_accounts WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' AND delete_status=0)
    ORDER BY s.account_tbl_id, s.created_at DESC");
if ($snapRes) {
    while ($snap = mysqli_fetch_assoc($snapRes)) {
        $aid = $snap['account_tbl_id'];
        if (!isset($snapshotData[$aid])) {
            $spend = (float)($snap['total_spend'] ?? 0);
            $lead = (int)($snap['total_lead'] ?? 0);
            $purchase = (float)($snap['total_purchase'] ?? 0);
            $cpl = ($lead > 0 && $spend >= 0) ? round($spend / $lead, 2) : 0;
            $cpp = ($purchase > 0 && $spend >= 0) ? round($spend / $purchase, 2) : 0;
            $snapshotData[$aid] = [
                'spend' => $spend,
                'lead' => $lead,
                'purchase' => $purchase,
                'cpl' => $cpl,
                'cpp' => $cpp
            ];
        }
    }
}

$headerColors = [
    '#1E3A8A', // Deep Blue
    '#1F2937', // Dark Slate
    '#374151', // Charcoal
    '#475569', // Cool Gray
    '#0F766E', // Teal
    '#155E75', // Deep Cyan
    '#065F46', // Forest Green
    '#3F6212', // Olive
    '#7C2D12', // Burnt Brown
    '#7F1D1D', // Deep Red
    '#5B21B6', // Royal Purple
    '#6D28D9', // Elegant Violet
    '#9D174D', // Wine
    '#B45309', // Amber Brown
    '#92400E', // Warm Brown
    '#334155', // Slate Blue Gray
    '#0C4A6E', // Steel Blue
    '#166534', // Deep Green
    '#4C1D95', // Indigo
    '#831843'  // Dark Rose
];


// Indian number format: 12,34,567 (lakhs/crores)
function formatIndian($n) {
    if ($n === '' || $n === '-' || $n === null) return (string)$n;
    $n = str_replace(',', '', (string)$n);
    if (!preg_match('/^-?\d+\.?\d*$/', $n)) return $n;
    $neg = (substr($n, 0, 1) === '-');
    $n = ltrim($n, '-');
    $parts = explode('.', $n);
    $intPart = $parts[0];
    $len = strlen($intPart);
    if ($len <= 3) return ($neg ? '-' : '') . $intPart . (isset($parts[1]) ? '.' . $parts[1] : '');
    $last3 = substr($intPart, -3);
    $rest = substr($intPart, 0, -3);
    $formatted = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
    return ($neg ? '-' : '') . $formatted . (isset($parts[1]) ? '.' . $parts[1] : '');
}

// Check if label indicates "site visit" for cost-per-site-visit calculation
$isSiteVisitLabel = function($lbl) {
    $l = strtolower(trim($lbl));
    return (stripos($l, 'site') !== false && stripos($l, 'visit') !== false) || $l === 'sv' || preg_match('/^sv\b/i', $l);
};

// Sort: 1) Not updated today first, 2) Bad results (cost per site visit > 5000 or ROAS < 1) second, 3) Rest
$todayStart = strtotime('today');
usort($allClients, function ($a, $b) use ($perfData, $lastUpdated, $snapshotData, $todayStart, $isSiteVisitLabel) {
    $getNotUpdated = function($c) use ($lastUpdated, $todayStart) {
        $lu = isset($lastUpdated[$c['tbl_id']]) ? $lastUpdated[$c['tbl_id']] : '';
        return !$lu || strtotime($lu) < $todayStart;
    };
    $getCritical = function($c) use ($perfData, $snapshotData, $isSiteVisitLabel) {
        $snap = $snapshotData[$c['tbl_id']] ?? null;
        $spend = $snap ? (float)($snap['spend'] ?? 0) : 0;
        if ($spend <= 0) return false;
        $purchase = $snap ? (float)($snap['purchase'] ?? 0) : 0;
        $roas = $purchase / $spend;
        if ($roas < 1) return true;
        $items = $perfData[$c['tbl_id']] ?? [];
        foreach ($items as $it) {
            $lbl = trim($it['custom_label'] ?? '');
            $val = trim($it['value'] ?? '');
            if (!empty($it['cost_per']) && $val !== '' && is_numeric($val) && (float)$val > 0 && $isSiteVisitLabel($lbl)) {
                $costPer = round($spend / (float)$val, 0);
                if ($costPer > 5000) return true;
            }
        }
        return false;
    };
    $aNotUp = $getNotUpdated($a);
    $bNotUp = $getNotUpdated($b);
    $aCrit = $getCritical($a);
    $bCrit = $getCritical($b);
    $score = function($notUp, $crit) {
        if ($notUp) return 0;
        if ($crit) return 1;
        return 2;
    };
    $aScore = $score($aNotUp, $aCrit);
    $bScore = $score($bNotUp, $bCrit);
    if ($aScore !== $bScore) return $aScore - $bScore;
    return strcasecmp($a['client_name'], $b['client_name']);
});

// Media buyer stats: total clients with spend, and critical count per buyer
$mediaBuyerStats = [];
foreach ($media_by as $mbId => $mbName) {
    $mediaBuyerStats[$mbId] = ['total' => 0, 'critical' => 0];
}
foreach ($allClients as $c) {
    $snap = $snapshotData[$c['tbl_id']] ?? null;
    $spend = $snap ? (float)($snap['spend'] ?? 0) : 0;
    if ($spend <= 0) continue;
    $mbId = (int)($c['med_by'] ?? 0);
    if (!isset($mediaBuyerStats[$mbId])) continue;
    $mediaBuyerStats[$mbId]['total']++;
    $isCritical = false;
    if ($snap && $spend > 0) {
        $purchase = (float)($snap['purchase'] ?? 0);
        if ($purchase / $spend < 1) $isCritical = true;
        $items = $perfData[$c['tbl_id']] ?? [];
        foreach ($items as $it) {
            $lbl = trim($it['custom_label'] ?? '');
            $val = trim($it['value'] ?? '');
            if (!empty($it['cost_per']) && $val !== '' && is_numeric($val) && (float)$val > 0 && $isSiteVisitLabel($lbl)) {
                if (round($spend / (float)$val, 0) > 5000) { $isCritical = true; break; }
            }
        }
    }
    if ($isCritical) $mediaBuyerStats[$mbId]['critical']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pgHeadline; ?> - AdRescue</title>
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
     <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Nunito", sans-serif; }
        .filter-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: nowrap; }
        .filter-row .filter-row-left { display: flex; align-items: center; gap: 12px; flex-wrap: nowrap; flex: 0 1 auto; min-width: 0; }
        .filter-row .filter-row-right { display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: nowrap; width: 50%; flex-shrink: 0; }
        .filter-row .filter-row-right .form-control { min-width: 0; flex: 1; max-width: 180px; }
        @media (max-width: 992px) { .filter-row { flex-wrap: wrap; } .filter-row .filter-row-right { width: 100%; } }
        .filter-row label { margin-bottom: 0; font-weight: 500; }
        .mb-report-item { display: flex; align-items: center; gap: 8px; font-size: 14px; padding: 6px 12px; background: #f8f9fa; border-radius: 8px; }
        .mb-report-item .mb-name { font-weight: 500; }
        .mb-report-item .mb-count { color: #333; }
        .mb-report-item .mb-count .mb-critical { color: #ff6363; font-weight: 600; }
        .client-card-wrap.filter-hidden { display: none !important; }
        /* Pinterest-style masonry grid */
        .pinterest-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .pinterest-grid .client-card-wrap {
            width: calc(25% - 15px);
            min-width: 280px;
            margin-bottom: 0;
        }
        @media (max-width: 1200px) { .pinterest-grid .client-card-wrap { width: calc(33.333% - 14px); } }
        @media (max-width: 768px) { .pinterest-grid .client-card-wrap { width: calc(50% - 10px); } }
        @media (max-width: 480px) { .pinterest-grid .client-card-wrap { width: 100%; } }
        .client-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            background: #fff;
        }
        .client-card .card-header-custom {
            padding: 7px 12px;
            color: #fff;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .client-card .card-header-custom .header-left { display: flex; align-items: center; gap: 8px; }
        .client-card .card-header-custom .header-left i { font-size: 18px; }
        .client-card .card-header-custom .edit-toggle { color: #fff; opacity: 0.9; cursor: pointer; padding: 4px 8px; }
        .client-card .card-header-custom .edit-toggle:hover { opacity: 1; }
        .client-card .card-body { padding: 8px 10px; }
        .client-card .cp-default-metrics { margin-bottom: 8px; border-bottom: 1px solid #eee; }
        .client-card .cp-default-metrics-horizontal { background: #e9f8ff; border-radius: 8px; overflow: hidden; }
        .client-card .cp-default-metrics-horizontal .cp-metrics-label-row { display: flex; border-bottom: 1px solid rgba(0,0,0,0.08); }
        .client-card .cp-default-metrics-horizontal .cp-metrics-label-row .cp-metric-cell { flex: 1; min-width: 0; text-align: center; font-weight: 600; font-size: 11px; color: #555; padding: 6px 4px; border-right: 1px solid rgba(0,0,0,0.08); }
        .client-card .cp-default-metrics-horizontal .cp-metrics-label-row .cp-metric-cell:last-child { border-right: none; }
        .client-card .cp-default-metrics-horizontal .cp-metrics-value-row { display: flex; }
        .client-card .cp-default-metrics-horizontal .cp-metrics-value-row .cp-metric-cell { flex: 1; min-width: 0; text-align: center; font-weight: bold; font-size: 14px; color: #333; padding: 6px 4px; border-right: 1px solid rgba(0,0,0,0.08); }
        .client-card .cp-default-metrics-horizontal .cp-metrics-value-row .cp-metric-cell:last-child { border-right: none; }
        .client-card .cp-view-mode .cp-view-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 14px; border-bottom: 1px solid rgba(0, 0, 0, 0.08); }
        .client-card .cp-view-mode .cp-view-item:last-child { border-bottom: none; }
        .client-card .cp-row {
            display: flex;
            align-items: stretch;
            margin-bottom: 10px;
        }
        .cp-view-mode {padding: 7px; background: #f5f5f5; border-radius: 8px; }
        .client-card .cp-row:last-child { margin-bottom: 0; }
        .client-card .cp-input-group { display: flex; flex: 1; min-width: 0; border-collapse: separate; }
        .client-card .cp-input-group .form-control { border-radius: 0; }
        .client-card .cp-input-group .form-control:first-child { border-top-left-radius: 4px; border-bottom-left-radius: 4px; }
        .client-card .cp-input-group .cp-addon-check { 
            padding: 6px 10px; background: #f5f5f5; border: 1px solid #ccc; border-left: none;
            display: flex; align-items: center; border-top-right-radius: 4px; border-bottom-right-radius: 4px;
        }
        .client-card .cp-input-group .cp-value-input { width: 90px; min-width: 90px; flex-shrink: 0; border-left: none; }
        .client-card .cp-actions { flex-shrink: 0; display: flex; gap: 4px; margin-left: 6px; }
        .client-card .card-footer-custom {
            padding: 12px 16px;
            background: #e8f5e9;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }
        .client-card .card-footer-custom .save-status { font-size: 13px; color: #2e7d32; }
        .client-card .edit-form-wrap { display: none; }
        .client-card.editing .cp-view-mode { display: none; }
        .client-card.editing .edit-form-wrap { display: block; }
        .client-card.editing .card-footer-custom { display: block; }
        .client-card:not(.editing) .card-footer-custom { display: none; }
        .dynamic-fields-cp .form-inline { margin-bottom: 8px; }
        .dynamic-fields-cp .form-inline:last-child [data-role="add"] { display: inline-block; }
        .dynamic-fields-cp .form-inline:last-child [data-role="remove"] { display: inline-block; }
        .dynamic-fields-cp .form-inline:not(:last-child) [data-role="add"] { display: none; }
        .cp-card-meta .fa {
            color: #24a2eb;
        }
        /* Critical card pulse animation (from card-animation.html) */
        .client-card.critical-blink {
            animation: critical-pulse 1.5s infinite;
        }
        @keyframes critical-pulse {
            0% { box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0 0 0 rgba(176,42,55,0.4); }
            70% { box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0 0 12px rgba(176,42,55,0); }
            100% { box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0 0 0 rgba(176,42,55,0); }
        }
        /* Blinking dot near clock when date is old */
        .dot-blink {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
            animation: dot-blink 1s ease-in-out infinite alternate;
        }
        .dot-blink--old-date {
            background: #c62828;
            box-shadow: 0 0 6px rgba(198,40,40,0.5);
        }
        @keyframes dot-blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(0.85); }
        }
    </style>
    
</head>
<body>
<?php include __DIR__ . '/menu-top.php'; 
$cpStats = ['total_clients' => 0, 'missing_custom_label' => 0, 'has_custom_label' => 0];
$uidEsc = mysqli_real_escape_string($conn, $_SESSION['uid']);
$statsSql = "SELECT 
  COUNT(*) AS total_clients,
  SUM(CASE WHEN cp.account_tbl_id IS NULL THEN 1 ELSE 0 END) AS missing_custom_label,
  SUM(CASE WHEN cp.account_tbl_id IS NOT NULL THEN 1 ELSE 0 END) AS has_custom_label
FROM dashboard_accounts da
INNER JOIN (
  SELECT s.account_tbl_id
  FROM client_performance_snapshot s
  INNER JOIN (
    SELECT account_tbl_id, MAX(period_end) AS max_end
    FROM client_performance_snapshot
    GROUP BY account_tbl_id
  ) latest ON s.account_tbl_id = latest.account_tbl_id AND s.period_end = latest.max_end
  WHERE s.total_spend > 0
) snap ON snap.account_tbl_id = da.tbl_id
LEFT JOIN (
  SELECT DISTINCT account_tbl_id
  FROM clients_performance
  WHERE TRIM(IFNULL(custom_label, '')) != '' OR TRIM(IFNULL(`value`, '')) != ''
) cp ON cp.account_tbl_id = da.tbl_id
WHERE da.uid='" . $uidEsc . "' 
  AND da.delete_status=0 
  AND da.client_ty IS NOT NULL 
  AND TRIM(da.client_ty) != ''";
$statsRes = @mysqli_query($conn, $statsSql);
if ($statsRes && $row = mysqli_fetch_assoc($statsRes)) {
    $cpStats['total_clients'] = (int)($row['total_clients'] ?? 0);
    $cpStats['missing_custom_label'] = (int)($row['missing_custom_label'] ?? 0);
    $cpStats['has_custom_label'] = (int)($row['has_custom_label'] ?? 0);
}
//d($cpStats);
if($cpStats['total_clients']>0 && $cpStats['total_clients']!=$cpStats['has_custom_label']) {
?>
<div class="container-fluid" style="padding: 0 20px;">
 <div class="alert alert-warning" role="alert">
  <center><strong style="color:#ff6363;">You must update all client performance data to access adRescue.</strong></center>
</div>
<?php } ?>
    <div class="filter-row">
        <div class="filter-row-left">
            <?php foreach ($media_by as $mbId => $mbName): 
                $stats = $mediaBuyerStats[$mbId] ?? ['total' => 0, 'critical' => 0];
                if ($stats['total'] <= 0) continue;
                $crit = $stats['critical'];
                $tot = $stats['total'];
            ?>
            <div class="mb-report-item">
                <i class="fa fa-user" style="color:#24a2eb; font-size:16px;"></i>
                <span class="mb-name"><?php echo htmlspecialchars($mbName); ?>:</span>
                <span class="mb-count"><?php if ($crit > 0): ?><span class="mb-critical"><?php echo formatIndian($crit); ?></span> / <?php endif; ?><?php echo formatIndian($tot); ?>.</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="filter-row-right">
            <input type="text" id="clientNameSearch" class="form-control" placeholder="Search client...">
            <select id="mediaBuyerFilter" class="form-control">
                <option value="">Media Buyer (All)</option>
                <?php foreach ($media_by as $mbId => $mbName): ?>
                <option value="<?php echo $mbId; ?>"><?php echo htmlspecialchars($mbName); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="clientTyFilter" class="form-control">
                <option value="">Client Industry (All)</option>
                <option value="RE">Real Estate</option>
                <option value="Coach">Coaching</option>
                <option value="Sch">School / College</option>
                <option value="Ecom">E-commerce</option>
                <option value="Oth">Others</option>
            </select>
        </div>
    </div>

    <div class="pinterest-grid" id="clientGrid">
        <?php
                $hasDisplayedCard = false;
                foreach ($allClients as $c):
                    $snap = $snapshotData[$c['tbl_id']] ?? null;
                    $spend = $snap ? (float)($snap['spend'] ?? 0) : 0;
                    if ($spend <= 0) continue;
                    $hasDisplayedCard = true;
                    $ty = $c['client_ty_norm'];
                    $isCoach = (strtoupper($ty) === 'COACH');
                    $color = $headerColors[((int)$c['tbl_id']) % count($headerColors)];
                    $items = isset($perfData[$c['tbl_id']]) ? $perfData[$c['tbl_id']] : [];
                    if (empty($items)) $items = [['id' => '', 'custom_label' => '', 'value' => '', 'cost_per' => 0]];
                    $icon = $isCoach ? 'fa-graduation-cap' : 'fa-building-o';
                ?>
                <?php 
                    $medByName = isset($media_by[$c['med_by']]) ? $media_by[$c['med_by']] : '';
                    $lastUp = isset($lastUpdated[$c['tbl_id']]) ? $lastUpdated[$c['tbl_id']] : '';
                    $lastUpFormatted = $lastUp ? date('d-m-Y h.i a', strtotime($lastUp)) : '';
                    $isNotUpdatedToday = !$lastUp || strtotime($lastUp) < $todayStart;
                    $isCritical = false;
                    if ($snap && $spend > 0) {
                        $purchase = (float)($snap['purchase'] ?? 0);
                        if ($purchase / $spend < 1) $isCritical = true;
                        foreach ($items as $it) {
                            $lbl = trim($it['custom_label'] ?? '');
                            $val = trim($it['value'] ?? '');
                            if (!empty($it['cost_per']) && $val !== '' && is_numeric($val) && (float)$val > 0 && $isSiteVisitLabel($lbl)) {
                                if (round($spend / (float)$val, 0) > 5000) { $isCritical = true; break; }
                            }
                        }
                    }
                ?>
                <div class="client-card-wrap" data-client-ty="<?php echo htmlspecialchars($ty); ?>" data-client-name="<?php echo htmlspecialchars(strtolower($c['client_name'])); ?>" data-med-by="<?php echo (int)$c['med_by']; ?>">
                    <div class="client-card<?php echo $isCritical ? ' critical-blink' : ''; ?>" data-tbl-id="<?php echo (int)$c['tbl_id']; ?>" data-spend="<?php echo $snap ? (float)$snap['spend'] : 0; ?>">
                        <div class="card-header-custom" style="background: <?php echo htmlspecialchars($color); ?>;">
                            <div class="header-left">
                                <i class="fa <?php echo $icon; ?>"></i>
                                <span><?php echo htmlspecialchars($c['client_name']); ?></span>
                            </div>
                            <a class="edit-toggle" title="Edit" data-action="edit"><i class="fa fa-pencil"></i></a>
                        </div>
                        <div class="card-body">
                            <div class="cp-default-metrics cp-default-metrics-horizontal">
                                <div class="cp-metrics-label-row">
                                    <span class="cp-metric-cell">S</span>
                                    <span class="cp-metric-cell">L</span>
                                    <?php if ($isCoach): ?><span class="cp-metric-cell">P</span><?php endif; ?>
                                    <span class="cp-metric-cell">CPL</span>
                                    <?php if ($isCoach): ?><span class="cp-metric-cell">CPP</span><?php endif; ?>
                                </div>
                                <div class="cp-metrics-value-row">
                                    <span class="cp-metric-cell"><?php echo $snap ? formatIndian(round($snap['spend'], 0)) : '-'; ?></span>
                                    <span class="cp-metric-cell"><?php echo $snap ? formatIndian($snap['lead']) : '-'; ?></span>
                                    <?php if ($isCoach): ?><span class="cp-metric-cell"><?php echo $snap ? formatIndian(round($snap['purchase'], 0)) : '-'; ?></span><?php endif; ?>
                                    <span class="cp-metric-cell"><?php echo $snap && $snap['cpl'] > 0 ? formatIndian(round($snap['cpl'], 0)) : '-'; ?></span>
                                    <?php if ($isCoach): ?><span class="cp-metric-cell"><?php echo $snap && $snap['cpp'] > 0 ? formatIndian(round($snap['cpp'], 0)) : '-'; ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div class="cp-view-mode">
                                <?php foreach ($items as $it): 
                                    $lbl = trim($it['custom_label'] ?? '');
                                    $val = trim($it['value'] ?? '');
                                    $isCpa = !empty($it['cost_per']);
                                    if ($lbl === '' && $val === '') continue;
                                    $spend = $snap ? (float)$snap['spend'] : 0;
                                    $costPerVal = ($isCpa && $val !== '' && $spend >= 0 && is_numeric($val) && (float)$val > 0) ? round($spend / (float)$val, 0) : 0;
                                ?>
                                <div class="cp-view-item">
                                    <span><?php echo htmlspecialchars($lbl); ?></span>
                                    <span><?php echo htmlspecialchars(is_numeric(str_replace(',','',$val)) ? formatIndian($val) : $val); ?></span>
                                </div>
                                <?php if ($isCpa && $costPerVal > 0): ?>
                                <div class="cp-view-item">
                                    <span>Cost per <?php echo htmlspecialchars($lbl ?: 'Item'); ?></span>
                                    <span><?php echo formatIndian($costPerVal); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                <?php 
                                $hasLabels = !empty(array_filter(array_column($items, 'custom_label')));
                                $hasValues = !empty(array_filter(array_map(function($x){return $x['value']??'';}, $items)));
                                if (!$hasLabels && !$hasValues): ?>
                                <div class="cp-view-item text-muted" style="font-size:13px;">Metrics not updated yet.</div>
                                <?php endif; ?>
                            </div>
                            <?php if ($medByName || $lastUpFormatted || !$lastUp): ?>
                            <div class="cp-card-meta<?php echo $isNotUpdatedToday ? ' meta-not-updated' : ''; ?>" style="padding-top:8px; font-size:12px; color:#666; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                                <?php if ($medByName): ?>
                                <span><i class="fa fa-user"></i> <?php echo htmlspecialchars($medByName); ?></span>
                                <?php endif; ?>
                                <?php $showDot = $isNotUpdatedToday; $dateMsg = $lastUpFormatted ? htmlspecialchars($lastUpFormatted) : 'Not updated yet'; ?>
                                <span><?php if ($showDot): ?><span class="dot-blink dot-blink--old-date" title="<?php echo $lastUpFormatted ? 'Date not updated today' : 'Last update date missing'; ?>"></span><?php else: ?><i class="fa fa-clock-o"></i> <?php endif; ?><?php echo $dateMsg; ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="edit-form-wrap">
                                <div class="dynamic-fields-cp" data-role="dynamic-fields">
                                    <?php foreach ($items as $it): ?>
                                    <div class="form-inline cp-row">
                                        <div class="input-group input-group-sm cp-input-group">
                                            <input type="text" class="form-control cp-custom-label" placeholder="Custom label" 
                                                value="<?php echo htmlspecialchars($it['custom_label'] ?? ''); ?>" 
                                                data-id="<?php echo htmlspecialchars($it['id'] ?? ''); ?>" />
                                            <input type="text" class="form-control cp-value cp-value-input" placeholder="Value" 
                                                value="<?php $v = $it['value'] ?? ''; echo htmlspecialchars((is_numeric(str_replace(',','',$v))) ? formatIndian($v) : $v); ?>" />
                                            <span class="input-group-addon cp-addon-check">
                                                <input type="checkbox" class="cp-cost-per" <?php echo (!empty($it['cost_per'])) ? 'checked' : ''; ?> />
                                            </span>
                                        </div>
                                        <div class="cp-actions">
                                            <button type="button" class="btn btn-success btn-xs" data-role="add" title="Add"><i class="fa fa-plus"></i></button>
                                            <button type="button" class="btn btn-danger btn-xs" data-role="remove" title="Remove"><i class="fa fa-minus"></i></button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer-custom">
                            <button type="button" class="btn btn-primary btn-sm btn-save-cp">Save</button> 
                            <span class="save-status" style="margin-left:8px;"></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$hasDisplayedCard): ?>
                <div class="client-card-wrap" style="break-inside: auto;"><p class="text-muted"><?php echo empty($allClients) ? 'No clients with client type found. Set <code>client_ty</code> in dashboard_accounts (RE, Coach, Sch, Ecom, Oth).' : 'No clients with spend data.'; ?></p></div>
                <?php endif; ?>
    </div>
</div>

<script src="/vendors/jquery/dist/jquery.min.js"></script>
<script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
<script>
function formatIndianNum(n) {
    var s = String(n).replace(/,/g, '');
    if (!/^\d+\.?\d*$/.test(s)) return s;
    var parts = s.split('.');
    var intPart = parts[0];
    if (intPart.length <= 3) return intPart + (parts[1] ? '.' + parts[1] : '');
    var last3 = intPart.slice(-3);
    var rest = intPart.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
    return rest + ',' + last3 + (parts[1] ? '.' + parts[1] : '');
}
function stripCommas(s) { return String(s || '').replace(/,/g, ''); }
$(function () {
    // Indian format on cp-value input - auto-format as user types
    $(document).on('input', '.cp-value', function () {
        var $t = $(this);
        var v = $t.val().replace(/[^\d.]/g, '');
        var parts = v.split('.');
        if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
        else v = parts.join('.');
        if (v && /^\d+\.?\d*$/.test(v)) {
            $t.val(formatIndianNum(v));
        } else if (v === '') {
            $t.val('');
        }
    });
    function applyFilters() {
        var tySel = $('#clientTyFilter').val();
        var mbSel = $('#mediaBuyerFilter').val();
        var searchTxt = ($('#clientNameSearch').val() || '').toLowerCase().trim();
        $('#clientGrid .client-card-wrap[data-client-ty]').each(function () {
            var ty = $(this).data('client-ty') || '';
            var medBy = $(this).data('med-by');
            var clientName = ($(this).data('client-name') || '').toString();
            var tyMatch = !tySel || (ty && tySel && ty.toString().toLowerCase() === tySel.toLowerCase());
            var mbMatch = !mbSel || (medBy !== undefined && medBy !== null && String(medBy) === String(mbSel));
            var searchMatch = !searchTxt || (clientName && clientName.indexOf(searchTxt) >= 0);
            $(this).toggleClass('filter-hidden', !(tyMatch && mbMatch && searchMatch));
        });
    }
    // Client name search - filter as you type
    $('#clientNameSearch').on('input', function () { applyFilters(); });
    // Media buyer dropdown
    $('#mediaBuyerFilter').on('change', function () { applyFilters(); });
    // Client type dropdown
    $('#clientTyFilter').on('change', function () { applyFilters(); });

    // Edit toggle - click pencil to show form, click X to cancel
    $(document).on('click', '.edit-toggle', function (e) {
        e.preventDefault();
        var card = $(this).closest('.client-card');
        var $btn = $(this);
        if (card.hasClass('editing')) {
            card.removeClass('editing');
            $btn.find('i').removeClass('fa-times').addClass('fa-pencil');
            $btn.attr('title', 'Edit');
        } else {
            card.addClass('editing');
            $btn.find('i').removeClass('fa-pencil').addClass('fa-times');
            $btn.attr('title', 'Cancel');
        }
    });

    // Add row
    $(document).on('click', '.dynamic-fields-cp [data-role="add"]', function (e) {
        e.preventDefault();
        var container = $(this).closest('.dynamic-fields-cp');
        var tpl = container.find('.form-inline:first').clone();
        tpl.find('.cp-custom-label').val('').attr('data-id', '');
        tpl.find('.cp-value').val('');
        tpl.find('.cp-cost-per').prop('checked', false);
        container.append(tpl);
    });

    // Remove row
    $(document).on('click', '.dynamic-fields-cp [data-role="remove"]', function (e) {
        e.preventDefault();
        var container = $(this).closest('.dynamic-fields-cp');
        if (container.find('.form-inline').length > 1) {
            $(this).closest('.form-inline').remove();
        }
    });

    // Save
    $(document).on('click', '.btn-save-cp', function () {
        var card = $(this).closest('.client-card');
        var tblId = card.data('tbl-id');
        var $status = card.find('.save-status');

        var items = [];
        card.find('.cp-row').each(function () {
            var $row = $(this);
            var $input = $row.find('.cp-custom-label');
            var lid = $input.attr('data-id') || $input.data('id') || '';
            items.push({
                id: lid,
                custom_label: $input.val().trim(),
                value: stripCommas($row.find('.cp-value').val()).trim(),
                cost_per: $row.find('.cp-cost-per').is(':checked') ? 1 : 0
            });
        });

        $status.text('Saving...').css('color', '#666');

        var ajaxUrl = window.location.pathname.replace(/\/[^/]*$/, '/') + 'ajax-client-performance.php';
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'save',
                account_tbl_id: tblId,
                items: JSON.stringify(items)
            },
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    $status.text('Saved').css('color', '#2e7d32');
                    if (r.ids && r.ids.length) {
                        card.find('.cp-row').each(function (i) {
                            if (r.ids[i] !== undefined) {
                                $(this).find('.cp-custom-label').data('id', r.ids[i]).attr('data-id', r.ids[i]);
                            }
                        });
                    }
                    // Update view mode and exit edit
                    var viewBody = card.find('.cp-view-mode');
                    viewBody.empty();
                    var spend = parseFloat(card.data('spend')) || 0;
                    card.find('.cp-row').each(function () {
                        var lbl = $(this).find('.cp-custom-label').val().trim();
                        var val = stripCommas($(this).find('.cp-value').val()).trim();
                        var isCpa = $(this).find('.cp-cost-per').is(':checked');
                        if (lbl || val) {
                            var lblEsc = $('<div>').text(lbl).html();
                            var valDisp = (val && !isNaN(parseFloat(val))) ? formatIndianNum(val) : val;
                            var valEsc = $('<div>').text(valDisp).html();
                            viewBody.append('<div class="cp-view-item"><span>' + lblEsc + '</span><span>' + valEsc + '</span></div>');
                            if (isCpa && val && spend >= 0 && !isNaN(parseFloat(val)) && parseFloat(val) > 0) {
                                var costPer = Math.round(spend / parseFloat(val));
                                viewBody.append('<div class="cp-view-item"><span>Cost per ' + lblEsc + '</span><span>' + formatIndianNum(costPer) + '</span></div>');
                            }
                        }
                    });
                    if (viewBody.children().length === 0) {
                        viewBody.html('<div class="cp-view-item text-muted" style="font-size:13px;">Metrics not updated yet.</div>');
                    }
                    card.removeClass('editing');
                    card.find('.edit-toggle i').removeClass('fa-times').addClass('fa-pencil');
                    card.find('.edit-toggle').attr('title', 'Edit');
                    setTimeout(function () { $status.text(''); }, 2000);
                } else {
                    $status.text('Error: ' + (r.msg || 'Unknown')).css('color', '#c62828');
                }
            },
            error: function (xhr) {
                $status.text('Error saving').css('color', '#c62828');
            }
        });
    });
});
</script>
<br>
</body>
</html>
