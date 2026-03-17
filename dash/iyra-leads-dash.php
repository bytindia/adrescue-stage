<?php
session_start();
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION['uid'] = 2;
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

include '../db.php';

// If tables are in different database (e.g. digitalb2k_adsninja), uncomment:
// mysqli_select_db($conn, 'digitalb2k_adsninja');

$projects_config = [
    1 => ['key' => 'iyra city', 'label' => 'Iyra City'],
    2 => ['key' => 'iyra amara', 'label' => 'Iyra Amara'],
    3 => ['key' => 'iyra spire', 'label' => 'Iyra Spire'],
];
$project_id = isset($_GET['project']) ? (int)$_GET['project'] : 1;
if (!isset($projects_config[$project_id])) $project_id = 1;
$project_filter = $projects_config[$project_id]['key'];

$st_dt = isset($_GET['st_dt']) ? $_GET['st_dt'] : date('Y-m-d', strtotime('first day of this month'));
$en_dt = isset($_GET['en_dt']) ? $_GET['en_dt'] : date('Y-m-d');
$st_dt = date('Y-m-d', strtotime($st_dt));
$en_dt = date('Y-m-d', strtotime($en_dt));
$st_full = $st_dt . ' 00:00:00';
$en_full = $en_dt . ' 23:59:59';

function normPhone($p) {
    if (empty($p)) return '';
    $p = preg_replace('/\D/', '', trim($p));
    if (strlen($p) >= 10) {
        if (substr($p, 0, 2) === '91' && strlen($p) > 10) $p = substr($p, 2);
        elseif (substr($p, 0, 1) === '0') $p = substr($p, 1);
    }
    return $p;
}

function normProject($p) {
    if (empty($p)) return '';
    $p = strtolower(trim($p));
    if (stripos($p, 'city') !== false) return 'iyra city';
    if (stripos($p, 'amara') !== false) return 'iyra amara';
    if (stripos($p, 'spire') !== false) return 'iyra spire';
    return $p;
}

function normSource($s) {
    if (empty($s)) return 'Other';
    $s = trim($s);
    $lower = strtolower($s);
    if ($lower === 'facebook' || $lower === 'meta') return 'Facebook';
    if ($lower === 'google') return 'Google';
    return ucfirst($lower);
}

function normStage($s) {
    if (empty($s)) return 'Unknown';
    $s = trim($s);
    $k = strtolower($s);
    if (stripos($k, 'opportunity') !== false) return (stripos($k, 'rnr') !== false) ? 'Opportunity - RNR' : 'Opportunity';
    if (stripos($k, 'site') !== false && stripos($k, 'visit') !== false) return 'Site visit';
    $map = ['drop'=>'Drop','assigned'=>'Assigned','opportunity'=>'Opportunity','site visit'=>'Site visit','sitevisit'=>'Site visit','qualified'=>'Qualified','leads'=>'Leads'];
    if (isset($map[$k])) return $map[$k];
    return $s;
}

function normLeadType($s) {
    if (empty($s)) return 'Other';
    $k = strtolower(trim($s));
    if ($k === 'call' || $k === 'calls') return 'Calls';
    if ($k === 'form fill' || $k === 'form fills') return 'Form Fills';
    return ucfirst($k);
}

// Build lead_stage lookup - use LATEST per phone+project (ORDER BY created DESC, first wins)
// Store multiple phone variants (with/without 91) for matching
$upload_lookup = [];
$upload_q = mysqli_query($conn, "SELECT phone, project, lead_stage FROM iyra_leads_upload ORDER BY created DESC");
if ($upload_q) {
    while ($r = mysqli_fetch_assoc($upload_q)) {
        $ph = normPhone($r['phone']);
        $proj = normProject($r['project']);
        if (!$ph || strlen($ph) < 10) continue;
        $stage = normStage($r['lead_stage'] ?: 'Unknown');
        $keys = [$ph . '|' . $proj];
        if (substr($ph, 0, 2) === '91' && strlen($ph) > 10) $keys[] = substr($ph, 2) . '|' . $proj;
        elseif (strlen($ph) === 10) $keys[] = '91' . $ph . '|' . $proj;
        foreach ($keys as $key) {
            if (!isset($upload_lookup[$key])) $upload_lookup[$key] = $stage;
        }
    }
}

function getStage($phone, $project, $upload_lookup) {
    $ph = normPhone($phone);
    $proj = normProject($project);
    if (!$ph || strlen($ph) < 10) return 'Unknown';
    $keys = [$ph . '|' . $proj];
    if (substr($ph, 0, 2) === '91' && strlen($ph) > 10) $keys[] = substr($ph, 2) . '|' . $proj;
    elseif (strlen($ph) === 10) $keys[] = '91' . $ph . '|' . $proj;
    foreach ($keys as $key) {
        if (isset($upload_lookup[$key])) return $upload_lookup[$key];
    }
    return 'Unknown';
}

$project_summary = [];
$source_leadtype_summary = [];  // "Source - LeadType" => [ stage => count ]
$meta_form_summary = [];  // formN => [ stage => count ]
$meta_summary = [];
$all_stages = [];

$proj_esc = mysqli_real_escape_string($conn, $project_filter);
$proj_like = '%city%';
if (strpos($project_filter, 'amara') !== false) $proj_like = '%amara%';
elseif (strpos($project_filter, 'spire') !== false) $proj_like = '%spire%';
$proj_like_esc = mysqli_real_escape_string($conn, $proj_like);

// 1. iyra_leads (Google Ads) - used only for stage lookup in Meta tables, NOT for Source-wise Summary
//    (Source-wise uses iyra_leads_upload only to avoid double-counting same leads)

// 2. iyra_leads_meta (Meta) - filter by refName - build form-wise and campaign-wise (NOT project_summary)
$q_meta = mysqli_query($conn, "SELECT refName, campN, formN, phone, created FROM iyra_leads_meta WHERE created BETWEEN '$st_full' AND '$en_full' AND LOWER(COALESCE(refName,'')) LIKE '%$proj_like_esc%'");
if ($q_meta) {
    while ($r = mysqli_fetch_assoc($q_meta)) {
        if (normProject($r['refName']) !== $project_filter) continue;
        $stage = getStage($r['phone'], $r['refName'], $upload_lookup);
        $camp = !empty($r['campN']) ? $r['campN'] : '(No campaign)';
        $formN = !empty($r['formN']) ? $r['formN'] : '(No form)';
        $all_stages[$stage] = true;
        // Meta form/campaign tables only - do NOT add to project_summary (avoids double-count with upload)
        if (!isset($meta_form_summary[$formN])) $meta_form_summary[$formN] = [];
        if (!isset($meta_form_summary[$formN][$stage])) $meta_form_summary[$formN][$stage] = 0;
        $meta_form_summary[$formN][$stage]++;
        if (!isset($meta_summary[$camp])) $meta_summary[$camp] = [];
        if (!isset($meta_summary[$camp][$stage])) $meta_summary[$camp][$stage] = 0;
        $meta_summary[$camp][$stage]++;
    }
}

// 3. iyra_leads_upload - SOLE source for Source-wise Summary & Source & Lead Type (CRM source of truth)
//    Avoids double-counting: same leads exist in iyra_leads_meta + iyra_leads + upload
$q_upload = mysqli_query($conn, "SELECT source, lead_stage, lead_type FROM iyra_leads_upload WHERE created BETWEEN '$st_full' AND '$en_full' AND LOWER(COALESCE(project,'')) LIKE '%$proj_like_esc%'");
if ($q_upload) {
    while ($r = mysqli_fetch_assoc($q_upload)) {
        $src = normSource($r['source'] ?? 'Upload');
        $stage = normStage($r['lead_stage'] ?? 'Unknown');
        $leadType = normLeadType($r['lead_type'] ?? 'Other');
        $all_stages[$stage] = true;
        // Source-wise Summary - from upload only (no overlap with Meta/Google tables)
        if (!isset($project_summary[$src])) $project_summary[$src] = [];
        if (!isset($project_summary[$src][$stage])) $project_summary[$src][$stage] = 0;
        $project_summary[$src][$stage]++;
        // Source & Lead Type - from upload only
        $key = $src . ' - ' . $leadType;
        if (!isset($source_leadtype_summary[$key])) $source_leadtype_summary[$key] = [];
        if (!isset($source_leadtype_summary[$key][$stage])) $source_leadtype_summary[$key][$stage] = 0;
        $source_leadtype_summary[$key][$stage]++;
    }
}

$stage_order = ['Drop', 'Assigned', 'Opportunity', 'Opportunity - RNR', 'Site visit', 'Qualified', 'Leads', 'Unknown'];
$stages = $stage_order;
foreach (array_keys($all_stages) as $s) {
    if (!in_array($s, $stages)) $stages[] = $s;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Iyra Leads Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/casa/css/style.css" rel="stylesheet">
    <link href="/casa/style.css" rel="stylesheet">
    <link href="/css/table.css" rel="stylesheet">
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.bootstrap.min.css">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <style>
        .card-header h5 { color: #000 !important; text-align: center;
    
    font-size: 18px; }
        .table th, .table td { text-align: center; vertical-align: middle; }
        .table th:first-child, .table td:first-child { text-align: left; }
        .table th { background-color: #f8f9fa; }
        .total-row { background-color: #bedbff !important; font-weight: bold; }
        #reportrange { background: #fff; cursor: pointer; padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; display: inline-block; }
        .nav-tabs .nav-link.active { background-color: #007bff; color: #fff !important; border-color: #007bff; }
        .nav-tabs .nav-link { color: #333; }
        .nav-tabs .nav-link:hover { border-color: #e9ecef #e9ecef #dee2e6; }
        .nav-tabs-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .nav-tabs-row .nav-tabs { flex: 1; border-bottom: 1px solid #dee2e6; }
        .nav-tabs-row .nav-tabs .nav-item { margin-bottom: -1px; }
        .nav-tabs-row .dash-controls { display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <div class="container" style="margin-top: 20px;">
        <div class="row">
            <div class="col-12">
                <!-- Tab navigation row: nav left, date range + upload right -->
                <div class="nav-tabs-row">
                    <ul class="nav nav-tabs" role="tablist">
                        <?php foreach ($projects_config as $pid => $p): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $project_id == $pid ? 'active' : ''; ?>" href="iyra-leads-dash.php?project=<?php echo $pid; ?>&st_dt=<?php echo $st_dt; ?>&en_dt=<?php echo $en_dt; ?>"><?php echo htmlspecialchars($p['label']); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="dash-controls">
                        <form method="GET" class="form-inline align-items-center mb-0">
                            <input type="hidden" name="project" value="<?php echo $project_id; ?>">
                            <div class="form-group mb-0 mr-2">
                                <div id="reportrange" style="background: #fff; cursor: pointer; padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px;">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span id="dateRangeText"><?php echo date('m/d/Y', strtotime($st_dt)) . ' - ' . date('m/d/Y', strtotime($en_dt)); ?></span> <i class="fa fa-caret-down"></i>
                                </div>
                                <input type="hidden" name="st_dt" id="st_dt" value="<?php echo $st_dt; ?>">
                                <input type="hidden" name="en_dt" id="en_dt" value="<?php echo $en_dt; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        </form>
                        <a href="iyra-leads-upload.php" class="btn btn-secondary btn-sm"><i class="fa fa-upload"></i> Upload CSV</a>
                    </div>
                </div>

                <!-- Table 1: Project-wise summary (Source | Lead stages) -->
                <div class="card mt-4">
                    <div class="card-header"><h5>Source-wise Summary</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="projectSummary">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Source</th>
                                    <?php foreach ($stages as $s): ?>
                                        <th><?php echo htmlspecialchars($s); ?></th>
                                    <?php endforeach; ?>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project_summary as $src => $counts): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($src); ?></td>
                                        <?php 
                                        $row_total = 0;
                                        foreach ($stages as $s): 
                                            $c = isset($counts[$s]) ? $counts[$s] : 0;
                                            $row_total += $c;
                                        ?>
                                            <td><?php echo $c; ?></td>
                                        <?php endforeach; ?>
                                        <td><strong><?php echo $row_total; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td>Total</td>
                                    <?php 
                                    $col_totals = [];
                                    foreach ($stages as $s) $col_totals[$s] = 0;
                                    foreach ($project_summary as $counts) {
                                        foreach ($counts as $s => $c) {
                                            if (isset($col_totals[$s])) $col_totals[$s] += $c;
                                        }
                                    }
                                    $grand = 0;
                                    foreach ($stages as $s): 
                                        $grand += $col_totals[$s];
                                    ?>
                                        <td><?php echo $col_totals[$s]; ?></td>
                                    <?php endforeach; ?>
                                    <td><?php echo $grand; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Table 2: Source & Lead Type wise (e.g. Facebook - Calls, Google - Form Fills) -->
                <div class="card mt-4">
                    <div class="card-header"><h5>Source & Lead Type Summary (by Lead Stage)</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="sourceLeadTypeSummary">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Source - Lead Type</th>
                                    <?php foreach ($stages as $s): ?>
                                        <th><?php echo htmlspecialchars($s); ?></th>
                                    <?php endforeach; ?>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($source_leadtype_summary as $key => $counts): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($key); ?></td>
                                        <?php 
                                        $row_total = 0;
                                        foreach ($stages as $s): 
                                            $c = isset($counts[$s]) ? $counts[$s] : 0;
                                            $row_total += $c;
                                        ?>
                                            <td><?php echo $c; ?></td>
                                        <?php endforeach; ?>
                                        <td><strong><?php echo $row_total; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td>Total</td>
                                    <?php 
                                    $col_totals = [];
                                    foreach ($stages as $s) $col_totals[$s] = 0;
                                    foreach ($source_leadtype_summary as $counts) {
                                        foreach ($counts as $s => $c) {
                                            if (isset($col_totals[$s])) $col_totals[$s] += $c;
                                        }
                                    }
                                    $grand = 0;
                                    foreach ($stages as $s): 
                                        $grand += $col_totals[$s];
                                    ?>
                                        <td><?php echo $col_totals[$s]; ?></td>
                                    <?php endforeach; ?>
                                    <td><?php echo $grand; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Table 3: Meta form-wise summary (Form | Lead stages) -->
                <div class="card mt-4">
                    <div class="card-header"><h5>Meta Leads Summary (by Form & Lead Stage)</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="metaFormSummary">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Form Name</th>
                                    <?php foreach ($stages as $s): ?>
                                        <th><?php echo htmlspecialchars($s); ?></th>
                                    <?php endforeach; ?>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meta_form_summary as $formN => $counts): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($formN); ?></td>
                                        <?php 
                                        $row_total = 0;
                                        foreach ($stages as $s): 
                                            $c = isset($counts[$s]) ? $counts[$s] : 0;
                                            $row_total += $c;
                                        ?>
                                            <td><?php echo $c; ?></td>
                                        <?php endforeach; ?>
                                        <td><strong><?php echo $row_total; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td>Total</td>
                                    <?php 
                                    $col_totals = [];
                                    foreach ($stages as $s) $col_totals[$s] = 0;
                                    foreach ($meta_form_summary as $counts) {
                                        foreach ($counts as $s => $c) {
                                            if (isset($col_totals[$s])) $col_totals[$s] += $c;
                                        }
                                    }
                                    $grand = 0;
                                    foreach ($stages as $s): 
                                        $grand += $col_totals[$s];
                                    ?>
                                        <td><?php echo $col_totals[$s]; ?></td>
                                    <?php endforeach; ?>
                                    <td><?php echo $grand; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Table 4: Meta leads summary (Campaign | Lead stages) -->
                <div class="card mt-4">
                    <div class="card-header"><h5>Meta Leads Summary (by Campaign & Lead Stage)</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="metaSummary">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Campaign Name</th>
                                    <?php foreach ($stages as $s): ?>
                                        <th><?php echo htmlspecialchars($s); ?></th>
                                    <?php endforeach; ?>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meta_summary as $camp => $counts): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($camp); ?></td>
                                        <?php 
                                        $row_total = 0;
                                        foreach ($stages as $s): 
                                            $c = isset($counts[$s]) ? $counts[$s] : 0;
                                            $row_total += $c;
                                        ?>
                                            <td><?php echo $c; ?></td>
                                        <?php endforeach; ?>
                                        <td><strong><?php echo $row_total; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td>Total</td>
                                    <?php 
                                    $col_totals = [];
                                    foreach ($stages as $s) $col_totals[$s] = 0;
                                    foreach ($meta_summary as $counts) {
                                        foreach ($counts as $s => $c) {
                                            if (isset($col_totals[$s])) $col_totals[$s] += $c;
                                        }
                                    }
                                    $grand = 0;
                                    foreach ($stages as $s): 
                                        $grand += $col_totals[$s];
                                    ?>
                                        <td><?php echo $col_totals[$s]; ?></td>
                                    <?php endforeach; ?>
                                    <td><?php echo $grand; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    $(function() {
        var st = moment('<?php echo $st_dt; ?>', 'YYYY-MM-DD');
        var en = moment('<?php echo $en_dt; ?>', 'YYYY-MM-DD');
        $('#reportrange').daterangepicker({
            startDate: st,
            endDate: en,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            opens: 'left',
            locale: { format: 'MM/DD/YYYY' }
        }, function(start, end) {
            $('#dateRangeText').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            $('#st_dt').val(start.format('YYYY-MM-DD'));
            $('#en_dt').val(end.format('YYYY-MM-DD'));
        });

        var dtConfig = {
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row mb-2"<"col-sm-12"B>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                { extend: 'copy', className: 'btn btn-primary btn-sm' },
                { extend: 'csv', className: 'btn btn-primary btn-sm' },
                { extend: 'excel', className: 'btn btn-primary btn-sm' },
                { extend: 'pdf', className: 'btn btn-primary btn-sm' },
                { extend: 'print', className: 'btn btn-primary btn-sm' }
            ],
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
            scrollX: true,
            autoWidth: false,
            bLengthChange: true,
            searching: true,
            info: true,
            paging: true,
            order: []
        };
        $('#projectSummary').DataTable(dtConfig);
        $('#sourceLeadTypeSummary').DataTable(dtConfig);
        $('#metaFormSummary').DataTable(dtConfig);
        $('#metaSummary').DataTable(dtConfig);
    });
    </script>
</body>
</html>
