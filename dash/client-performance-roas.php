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
$header_no = 1;

$pgHeadline = 'Client Performance ROAS';
ini_set('display_errors', 0);
error_reporting(0);

$media_by = array(1=>'Ramesh', 14=>'Charan', 15=>'Mughil', 16=>'Karthik');

// Industry display mapping: RE, Coach, E-com., School, Others
$industryMap = [
    'RE' => 'RE',
    'Coach' => 'Coach',
    'Sch' => 'School',
    'Ecom' => 'E-com.',
    'Oth' => 'Others'
];

// Type based on industry: RE->SV, Ecom/Coach->Sale, Sch->Adm, Oth->try to detect
function getTypeByIndustry($clientTy) {
    $ty = strtoupper(trim($clientTy ?? ''));
    if ($ty === 'RE') return 'SV';
    if ($ty === 'ECOM' || $ty === 'COACH') return 'Sale';
    if ($ty === 'SCH') return 'Adm.';
    return 'Oth';
}

// Check if label matches Site Visit (SV)
function isSiteVisitLabel($lbl) {
    $l = strtolower(trim($lbl ?? ''));
    if ($l === 'sv') return true;
    if (preg_match('/^sv\b/i', $l)) return true;
    return (stripos($l, 'site') !== false && stripos($l, 'visit') !== false) || stripos($l, 'sitevisit') !== false || stripos($l, 'site visited') !== false;
}

// Check if label matches Sale
function isSaleLabel($lbl) {
    $l = strtolower(trim($lbl ?? ''));
    return stripos($l, 'sale') !== false || stripos($l, 'sales') !== false || stripos($l, 'sold') !== false;
}

// Check if label matches Admission
function isAdmissionLabel($lbl) {
    $l = strtolower(trim($lbl ?? ''));
    return stripos($l, 'admission') !== false || stripos($l, 'admissions') !== false || preg_match('/\badm\b/i', $l);
}

// Check if label contains ROAS
function isRoasLabel($lbl) {
    return stripos(trim($lbl ?? ''), 'roas') !== false;
}

// Fetch all clients with non-empty client_ty (include med_by for Advertiser)
$allClients = [];
$stmt = @mysqli_query($conn, "SELECT tbl_id, client_name, client_ty, med_by FROM dashboard_accounts 
    WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' 
    AND delete_status=0 
    AND client_ty IS NOT NULL 
    AND TRIM(client_ty) != ''
    ORDER BY client_name ASC");
if ($stmt) {
    while ($row = mysqli_fetch_assoc($stmt)) {
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

// Fetch performance data (custom_label, value) per account
$perfData = [];
$perfRes = @mysqli_query($conn, "SELECT account_tbl_id, custom_label, `value` FROM clients_performance 
    WHERE account_tbl_id IN (SELECT tbl_id FROM dashboard_accounts WHERE uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' AND delete_status=0)
    ORDER BY account_tbl_id, sort_order, id");
if ($perfRes) {
    while ($pr = mysqli_fetch_assoc($perfRes)) {
        $aid = $pr['account_tbl_id'];
        if (!isset($perfData[$aid])) $perfData[$aid] = [];
        $perfData[$aid][] = $pr;
    }
}

// Fetch latest snapshot (spend) per account
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
            $snapshotData[$aid] = [
                'spend' => (float)($snap['total_spend'] ?? 0),
                'purchase' => (float)($snap['total_purchase'] ?? 0)
            ];
        }
    }
}

// Indian number format
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

// Build table rows
$tableRows = [];
foreach ($allClients as $c) {
    $aid = $c['tbl_id'];
    $snap = $snapshotData[$aid] ?? null;
    $spend = $snap ? (float)($snap['spend'] ?? 0) : 0;
    if ($spend <= 0) continue;

    $ty = trim($c['client_ty'] ?? '');
    $industry = isset($industryMap[$ty]) ? $industryMap[$ty] : $ty;
    $type = getTypeByIndustry($ty);

    $items = $perfData[$aid] ?? [];
    $results = 0;
    $roasVal = '-';

    foreach ($items as $it) {
        $lbl = trim($it['custom_label'] ?? '');
        $val = trim($it['value'] ?? '');
        $numVal = is_numeric(str_replace(',', '', $val)) ? (float)str_replace(',', '', $val) : 0;
        if ($numVal <= 0) continue;

        if ($type === 'SV' && isSiteVisitLabel($lbl)) {
            $results = $numVal;
            break;
        }
        if ($type === 'Sale' && isSaleLabel($lbl)) {
            $results = $numVal;
            break;
        }
        if ($type === 'Adm.' && isAdmissionLabel($lbl)) {
            $results = $numVal;
            break;
        }
        if ($type === 'Oth') {
            if (isSiteVisitLabel($lbl)) { $results = $numVal; $type = 'SV'; break; }
            if (isSaleLabel($lbl)) { $results = $numVal; $type = 'Sale'; break; }
            if (isAdmissionLabel($lbl)) { $results = $numVal; $type = 'Adm.'; break; }
        }
    }

    foreach ($items as $it) {
        $lbl = trim($it['custom_label'] ?? '');
        $val = trim($it['value'] ?? '');
        if (isRoasLabel($lbl) && $val !== '') {
            $roasVal = is_numeric(str_replace(',', '', $val)) ? $val : $val;
            break;
        }
    }

    $cpa = ($results > 0 && $spend > 0) ? round($spend / $results, 0) : '-';

    $advertiser = isset($media_by[$c['med_by']]) ? $media_by[$c['med_by']] : '-';
    $lastUp = isset($lastUpdated[$aid]) ? $lastUpdated[$aid] : '';
    $updatedDisplay = '-';
    if ($lastUp) {
        $diff = time() - strtotime($lastUp);
        $days = floor($diff / 86400);
        if ($days === 0) $updatedDisplay = 'today';
        elseif ($days === 1) $updatedDisplay = '1d old';
        else $updatedDisplay = $days . 'd old';
    }

    $tableRows[] = [
        'industry' => $industry,
        'client' => $c['client_name'],
        'spend' => $spend,
        'type' => $type,
        'results' => $results > 0 ? $results : '-',
        'cpa' => $cpa,
        'cpa_order' => ($cpa !== '-' && is_numeric($cpa)) ? (float)$cpa : -1,
        'roas' => $roasVal,
        'advertiser' => $advertiser,
        'updated' => $updatedDisplay
    ];
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
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <style>
        .roas-table th, .roas-table td {
            text-align: center;
            vertical-align: middle;
            font-size: 15px;
            color: #1a1a1a;
            letter-spacing: 0.02em;
            line-height: 1.5;
        }
        .roas-table thead th {
            background-color: #3981bb !important;
            color: #fff !important;
            border-color: #1E3A8A;
            font-size: 15px;
        }
        .report-container { margin-top: 20px; }
        .report-container .dataTables_wrapper { display: inline-block; }
        .report-container table { margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
<?php include __DIR__ . '/menu-top.php'; ?>
<div class="container-fluid" style="padding: 20px;">
    <div class="card">
        <div class="card-body">
            <div class="report-container text-center">
                <div id="reportTable">
                <table id="roasTable" class="table table-bordered table-striped roas-table">
                    <thead>
                        <tr>
                            <th>Industry</th>
                            <th>Client</th>
                            <th>Spend</th>
                            <th>Type</th>
                            <th>Results</th>
                            <th>CPA</th>
                            <th>ROAS</th>
                            <th>Advertiser</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableRows as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['industry']); ?></td>
                            <td><?php echo htmlspecialchars($r['client']); ?></td>
                            <td><?php echo $r['spend'] > 0 ? formatIndian(round($r['spend'], 0)) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($r['type']); ?></td>
                            <td><?php echo $r['results'] === '-' ? '-' : formatIndian($r['results']); ?></td>
                            <td data-order="<?php echo $r['cpa_order']; ?>"><?php echo $r['cpa'] === '-' ? '-' : formatIndian($r['cpa']); ?></td>
                            <td><?php echo htmlspecialchars($r['roas']); ?></td>
                            <td><?php echo htmlspecialchars($r['advertiser']); ?></td>
                            <td><?php echo htmlspecialchars($r['updated']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php if (empty($tableRows)): ?>
                <p class="text-muted">No clients with spend data.</p>
                <?php endif; ?>
                <?php if (!empty($tableRows)): ?>
                <div class="text-center" style="margin-top: 10px;">
                    <button id="copyTableImage" class="btn btn-info">
                        <i class="fa fa-copy"></i> Copy Table as Image
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/vendors/jquery/dist/jquery.min.js"></script>
<script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
$(document).ready(function() {
    <?php if (!empty($tableRows)): ?>
    $('#roasTable').DataTable({
        ordering: true,
        lengthMenu: [25, 50, 100],
        pageLength: 25,
        scrollX: true,
        order: [[5, 'desc']]
    });

    $('#copyTableImage').click(function() {
        var table = document.getElementById('roasTable');
        var clone = document.createElement('table');
        clone.className = table.className;
        clone.style.borderCollapse = 'collapse';
        clone.style.width = table.offsetWidth + 'px';
        var headTable = document.querySelector('.dataTables_scrollHeadInner table');
        var theadSrc = (headTable && headTable.querySelector('thead')) ? headTable.querySelector('thead') : table.querySelector('thead');
        if (theadSrc) {
            var theadClone = theadSrc.cloneNode(true);
            theadClone.querySelectorAll('th').forEach(function(th) { th.style.backgroundColor = '#1E3A8A'; th.style.color = '#fff'; });
            clone.appendChild(theadClone);
        }
        var tbody = table.querySelector('tbody');
        if (tbody) clone.appendChild(tbody.cloneNode(true));
        var wrap = document.createElement('div');
        wrap.style.cssText = 'position:absolute;left:-9999px;top:0;background:#fff;padding:10px;';
        wrap.appendChild(clone);
        document.body.appendChild(wrap);
        html2canvas(clone, { backgroundColor: '#fff', scale: 2 }).then(function(canvas) {
            canvas.toBlob(function(blob) {
                if (navigator.clipboard && window.ClipboardItem) {
                    const item = new ClipboardItem({ 'image/png': blob });
                    navigator.clipboard.write([item]).then(function() {
                        alert('Table image copied to clipboard!');
                    }, function(err) {
                        alert('Failed to copy image: ' + err);
                    });
                } else {
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');
                }
                try { document.body.removeChild(wrap); } catch(e) {}
            });
        }).catch(function() { try { document.body.removeChild(wrap); } catch(e) {} });
    });
    <?php endif; ?>
});
</script>
<br>
</body>
</html>
