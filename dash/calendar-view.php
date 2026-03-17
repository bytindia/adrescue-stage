<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  
error_reporting(E_ALL);
ini_set('display_errors', '1');

$_SESSION['uid'] = 2;
if (!isset($_SESSION['uid'])) {
    $pg = '../login.php';
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
    exit();
}

include '../db.php';
include 'overview-config.php';
include 'google-campaigns-day.php';

if (!isset($_SESSION['stDt'])) {
    $start = date('m/d/Y', strtotime('first day of this month'));
    $end = date('m/d/Y');
    $_SESSION['stDt'] = $start;
    $_SESSION['enDt'] = $end;
}

$showReport = false;
$selectedClientId = null;
$dayData = [];      // [ 'Y-m-d' => [ 'spend' => x, 'leads' => y, 'cpl' => z ], ... ]
$monthList = [];     // [ [ 'year' => 2025, 'month' => 2 ], ... ]
$festivalsByDate = []; // [ 'Y-m-d' => 'Festival Name', ... ]
$cplMin = null;
$cplMax = null;

// Load festivals from festivals.json (same folder as this script)
$festivalsPath = __DIR__ . DIRECTORY_SEPARATOR . 'festivals.json';
if (file_exists($festivalsPath)) {
    $festivalsRaw = @file_get_contents($festivalsPath);
    if ($festivalsRaw !== false) {
        $festivalsRaw = trim($festivalsRaw);
        if (substr($festivalsRaw, 0, 3) === "\xEF\xBB\xBF") $festivalsRaw = substr($festivalsRaw, 3);
        $festivalsJson = json_decode($festivalsRaw, true);
        if (is_array($festivalsJson) && !empty($festivalsJson['india_holidays'])) {
            foreach ($festivalsJson['india_holidays'] as $yearBlock) {
                if (!is_array($yearBlock)) continue;
                $list = isset($yearBlock['holidays']) && is_array($yearBlock['holidays']) ? $yearBlock['holidays'] : [];
                foreach ($list as $h) {
                    $d = isset($h['date']) ? trim($h['date']) : '';
                    $n = isset($h['name']) ? trim($h['name']) : '';
                    if ($d !== '' && $n !== '') $festivalsByDate[$d] = $n;
                }
            }
        }
    }
}

if (isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedClientId = isset($_POST['client']) ? trim($_POST['client']) : '';
    $stDt = $_POST['start_date'];
    $enDt = $_POST['end_date'];
    $_SESSION['stDt'] = $stDt;
    $_SESSION['enDt'] = $enDt;
    $_SESSION['calendar_client'] = $selectedClientId;

    $dtRange1 = date("Y-m-d", strtotime($stDt));
    $dtRange2 = date("Y-m-d", strtotime($enDt));

    if ($selectedClientId) {
        $cirRes = mysqli_query($conn, "SELECT da.tbl_id, da.client_name, da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain FROM dashboard_accounts da WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $selectedClientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0");
        $row = mysqli_fetch_assoc($cirRes);

        if ($row) {
            $fb_acc_ids = array_filter(explode(',', $row['fb_id']));
            $g_acc_ids = array_filter(explode(',', $row['g_id']));
            $proj_names = $row['proj_name'] != '' ? array_filter(unserialize($row['proj_name'])) : [];
            $name_contain = $row['name_contain'] != '' ? array_filter(unserialize($row['name_contain'])) : [];

            $obj_arr = [
                'LEAD_GENERATION' => 'lead',
                'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
                'MESSAGES' => 'onsite_conversion.messaging_block',
                'OUTCOME_LEADS' => 'lead',
                'OUTCOME_SALES' => 'purchase',
                'PRODUCT_CATALOG_SALES' => 'purchase'
            ];

            // Meta: day-wise with time_increment=1 (one row per day per campaign)
            $metaByDay = [];
            if (!empty($fb_acc_ids)) {
                foreach ($fb_acc_ids as $fb_id) {
                    $fb_id = trim($fb_id);
                    $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&breakdowns=publisher_platform&fields=campaign_id,campaign_name,adset_id,adset_name,spend,objective,actions,date_start,date_stop&time_range[since]=".$dtRange1."&time_range[until]=".$dtRange2."&time_increment=1&access_token=".$access_token."&limit=1750";
                    $req = file_get_contents_curl($url);
                    $res = json_decode($req, true);
                    $accountAdded = false;
                    if (isset($res['data']) && is_array($res['data'])) {
                        foreach ($res['data'] as $campaign) {
                            $campaign_name = isset($campaign['campaign_name']) ? $campaign['campaign_name'] : '';
                            $should_include = true;
                            if (!empty($proj_names) || !empty($name_contain)) {
                                $should_include = false;
                                if (!empty($proj_names)) {
                                    foreach ($proj_names as $proj_name) {
                                        if (stripos($campaign_name, $proj_name) !== false) { $should_include = true; break; }
                                    }
                                }
                                if (!$should_include && !empty($name_contain)) {
                                    if (contains_proj($campaign_name) !== '') $should_include = true;
                                }
                            }
                            if (!$should_include) continue;

                            $dt = isset($campaign['date_start']) ? $campaign['date_start'] : (isset($campaign['date_stop']) ? $campaign['date_stop'] : null);
                            if (!$dt) continue;
                            if (!isset($metaByDay[$dt])) $metaByDay[$dt] = ['spend' => 0, 'leads' => 0];
                            $spend = isset($campaign['spend']) ? floatval($campaign['spend']) : 0;
                            $metaByDay[$dt]['spend'] += $spend;
                            $actions = isset($campaign['actions']) ? $campaign['actions'] : [];
                            $obj = isset($campaign['objective']) ? $campaign['objective'] : '';
                            if (!empty($actions) && isset($obj_arr[$obj])) {
                                $metaByDay[$dt]['leads'] += LeadGen($actions, $obj_arr[$obj]);
                            }
                            $accountAdded = true;
                        }
                    }
                    // Fallback without breakdown for this account if no rows
                    if (!$accountAdded) {
                        $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&fields=campaign_id,campaign_name,spend,objective,actions,date_start,date_stop&time_range[since]=".$dtRange1."&time_range[until]=".$dtRange2."&time_increment=1&access_token=".$access_token."&limit=1750";
                        $req = file_get_contents_curl($url);
                        $res = json_decode($req, true);
                        if (isset($res['data']) && is_array($res['data'])) {
                            foreach ($res['data'] as $campaign) {
                                $campaign_name = isset($campaign['campaign_name']) ? $campaign['campaign_name'] : '';
                                $should_include = true;
                                if (!empty($proj_names) || !empty($name_contain)) {
                                    $should_include = false;
                                    if (!empty($proj_names)) {
                                        foreach ($proj_names as $proj_name) {
                                            if (stripos($campaign_name, $proj_name) !== false) { $should_include = true; break; }
                                        }
                                    }
                                    if (!$should_include && !empty($name_contain)) {
                                        if (contains_proj($campaign_name) !== '') $should_include = true;
                                    }
                                }
                                if (!$should_include) continue;
                                $dt = isset($campaign['date_start']) ? $campaign['date_start'] : (isset($campaign['date_stop']) ? $campaign['date_stop'] : null);
                                if (!$dt) continue;
                                if (!isset($metaByDay[$dt])) $metaByDay[$dt] = ['spend' => 0, 'leads' => 0];
                                $metaByDay[$dt]['spend'] += isset($campaign['spend']) ? floatval($campaign['spend']) : 0;
                                $actions = isset($campaign['actions']) ? $campaign['actions'] : [];
                                $obj = isset($campaign['objective']) ? $campaign['objective'] : '';
                                if (!empty($actions) && isset($obj_arr[$obj])) {
                                    $metaByDay[$dt]['leads'] += LeadGen($actions, $obj_arr[$obj]);
                                }
                            }
                        }
                    }
                }
            }

            // Google: day-wise via google-campaigns-day.php (segments.date BETWEEN)
            $googleByDay = [];
            if (!empty($g_acc_ids)) {
                $googleByDay = GetCampaignsFromMultipleAccountsDay::main($g_refresh_token, $g_mcc, $g_acc_ids, $dtRange1, $dtRange2);
            }

            // Merge and build $dayData for every day in range
            $startTs = strtotime($dtRange1);
            $endTs = strtotime($dtRange2);
            for ($t = $startTs; $t <= $endTs; $t += 86400) {
                $d = date('Y-m-d', $t);
                $meta = isset($metaByDay[$d]) ? $metaByDay[$d] : ['spend' => 0, 'leads' => 0];
                $goo = isset($googleByDay[$d]) ? $googleByDay[$d] : ['cost' => 0, 'conv' => 0];
                $spend = $meta['spend'] + $goo['cost'];
                $leads = $meta['leads'] + $goo['conv'];
                $cpl = ($leads > 0) ? round($spend / $leads) : 0;
                $dayData[$d] = ['spend' => $spend, 'leads' => $leads, 'cpl' => $cpl];
            }

            // CPL range for coloring (only days with CPL > 0)
            $cplValues = array_filter(array_column($dayData, 'cpl'));
            if (!empty($cplValues)) {
                $cplMin = min($cplValues);
                $cplMax = max($cplValues);
            }

            // Build list of months in range for calendar navigation
            $y1 = (int)date('Y', $startTs);
            $m1 = (int)date('n', $startTs);
            $y2 = (int)date('Y', $endTs);
            $m2 = (int)date('n', $endTs);
            for ($y = $y1; $y <= $y2; $y++) {
                $ms = ($y == $y1) ? $m1 : 1;
                $me = ($y == $y2) ? $m2 : 12;
                for ($m = $ms; $m <= $me; $m++) {
                    $monthList[] = ['year' => $y, 'month' => $m];
                }
            }
        }
    }
}

function moneyFormatIndia($num) {
    $num = round($num);
    if ($num == 0) return '-';
    $explrestunits = "";
    if (strlen($num) > 3) {
        $lastthree = substr($num, strlen($num) - 3, strlen($num));
        $restunits = substr($num, 0, strlen($num) - 3);
        // Indian format: no leading zero (e.g. 1,171 not 01,171)
        $restunits = (strlen($restunits) % 2 == 1 && strlen($restunits) > 1) ? "0" . $restunits : $restunits;
        $expunit = str_split($restunits, 2);
        for ($i = 0; $i < sizeof($expunit); $i++) {
            $explrestunits .= $expunit[$i] . ",";
        }
        $thecash = $explrestunits . $lastthree;
        $thecash = preg_replace('/^0+(?=,|[0-9])/', '', $thecash);
    } else {
        $thecash = $num;
    }
    return $thecash;
}

function getCplBgColor($cpl, $cplMin, $cplMax) {
    if ($cpl <= 0 || $cplMin === null || $cplMax === null || $cplMin >= $cplMax) {
        return '';
    }
    $p = ($cpl - $cplMin) / ($cplMax - $cplMin); // 0 = low CPL (best), 1 = high CPL (worst)
    // Low CPL #59d34b -> Very high CPL #e73429
    $r = (int)(89 + $p * (231 - 89));
    $g = (int)(211 + $p * (52 - 211));
    $b = (int)(75 + $p * (41 - 75));
    return sprintf('#%02x%02x%02x', min(255,max(0,$r)), min(255,max(0,$g)), min(255,max(0,$b)));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>AdRescue - Calendar View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/casa/css/style.css" rel="stylesheet">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <style>
        /* Scope all calendar-view styles so they never affect bootstrap daterangepicker popup */
        #calendarReportSection .calendar-wrap { max-width: 100%; overflow-x: auto; }
        #calendarReportSection .calendar-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        #calendarReportSection .calendar-table th,
        #calendarReportSection .calendar-table td { border: 1px solid #e1dfdf; padding: 4px; font-size: 12px; vertical-align: top; }
        #calendarReportSection .calendar-table th { background: #0f5d9b; color: #fff; font-weight: bold; text-align: center; }
        #calendarReportSection .cal-header-row { background: #2196f3; color: #fff; }
        #calendarReportSection .cal-month-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        #calendarReportSection .cal-cell-inrange {  }
        #calendarReportSection .cal-cell-out { background: #e9ecef; color: #999; }
        #calendarReportSection .cal-cell-empty {  color: #6c757d; }
        #calendarReportSection .calendar-table td { width: 14.28%; height: 86px; min-height: 86px; vertical-align: top; box-sizing: border-box; }
        #calendarReportSection .cal-day-line { margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; gap: 6px; flex-wrap: nowrap; }
        #calendarReportSection .cal-day-num { font-weight: bold; font-size: 20px; color: #333; flex-shrink: 0; margin-left: auto; }
        #calendarReportSection .cal-cell-out .cal-day-num { color: #999; }
        #calendarReportSection .cal-cell-has-metrics .cal-day-num { font-size: 22px; }
        #calendarReportSection .cal-day-num-colored { display: inline-block; min-width: 32px; padding: 2px 8px; border-radius: 50px; color: #fff !important; text-shadow: 0 1px 2px rgba(0,0,0,0.3); text-align: center; }
        #calendarReportSection .cal-cell-cpl-bg .cal-metrics {
    color: #5c5858;
   
    border-top-color: rgba(255, 255, 255, 0.2);
    background: rgb(186 225 249 / 43%);
    border-radius: 8px;
    padding: 5px;
    margin-bottom: 3px;
}
        #calendarReportSection .cal-cell-has-metrics:not(.cal-cell-cpl-bg) .cal-metrics { color: #333; }
        #calendarReportSection .cal-festival-badge { display: inline-block !important; visibility: visible !important; background: #fbf072; color: #832705; border: 1px solid #fbc915; border-radius: 4px; padding: 2px 6px; font-size: 10px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; min-width: 0; text-align: left; }
        #calendarReportSection .cal-metrics { font-size: 11px; line-height: 1.2; margin-top: 4px; border-top: 1px solid rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 0; min-height: 28px; }
        #calendarReportSection .cal-metrics-label-row { display: flex; align-items: stretch; border-bottom: 1px solid rgba(0,0,0,0.25); }
        #calendarReportSection .cal-cell-cpl-bg .cal-metrics-label-row { border-bottom-color: rgb(153 149 149 / 20%); }
        #calendarReportSection .cal-cell-cpl-bg .cal-metrics-label-row .cal-metric-label { border-bottom-color: rgba(255,255,255,0.2); }
        #calendarReportSection .cal-metrics-label-row .cal-metric-label { flex: 1; min-width: 0; text-align: center; font-weight: 600; font-size: 10px; padding: 2px 0; border-right: 1px solid rgba(0,0,0,0.1); }
        #calendarReportSection .cal-metrics-label-row .cal-metric-label:last-child { border-right: none; }
        #calendarReportSection .cal-metrics-value-row { display: flex; align-items: center; flex: 1; }
        #calendarReportSection .cal-metrics-value-row .cal-metric-value { flex: 1; min-width: 0; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 2px 0; border-right: 1px solid rgba(0,0,0,0.1); }
        #calendarReportSection .cal-metrics-value-row .cal-metric-value:last-child { border-right: none; }
        #calendarReportSection .cal-cell-cpl-bg .cal-metrics-value-row .cal-metric-value { border-right-color:  rgb(153 149 149 / 20%); }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .loading-overlay.active { display: flex; }
        .loading-content { background: white; padding: 30px; border-radius: 10px; text-align: center; }
        .daterangepicker { z-index: 1050 !important; }
        span.cal-metric-value {
    font-weight: bold;
    font-size: 14px;
}
        /* Summary cards above calendar */
        .cal-summary-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .cal-summary-card { border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); padding: 16px 20px; flex: 1; min-width: 140px; display: flex; align-items: flex-start; justify-content: space-between; }
        .cal-summary-card:nth-child(1) { background: linear-gradient(270deg, #d0f1f9 0%, #e6edff 100%); }
        .cal-summary-card:nth-child(2) { background: linear-gradient(135deg, #fff8f0 0%, #ffefd9 100%); }
        .cal-summary-card:nth-child(3) { background: linear-gradient(135deg, #f8f0fd 0%, #eedcfc 100%); }
        .cal-summary-card:nth-child(4) { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
        .cal-summary-card:nth-child(5) { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); }
        .cal-summary-card-inner { flex: 1; min-width: 0; }
        .cal-summary-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6c757d; letter-spacing: 0.3px; margin-bottom: 6px; }
        .cal-summary-value { font-size: 22px; font-weight: 700; color: #2c3e50; line-height: 1.2; }
        .cal-summary-icon { width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .cal-summary-icon.spend { background: #fde8e0; color: #e67e50; }
        .cal-summary-icon.leads { background: #fff3e0; color: #f57c00; }
        .cal-summary-icon.cpl { background: #e8f5e9; color: #43a047; }
        .cal-summary-icon.low-cpl { background: #e8f5e9; color: #2e7d32; }
        .cal-summary-icon.high-cpl { background: #ffebee; color: #c62828; }
        .cal-summary-value .cal-cpl-num { font-weight: 600; font-size: 0.9em; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="loading-overlay"><div class="loading-content"><div class="spinner-border text-primary"></div><h4>Loading...</h4><p>Fetching day-wise data from Meta & Google...</p></div></div>
    <div class="container-fluid px-3">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <select class="form-control" name="client" required title="Select one client">
                                        <option value="">Select one client...</option>
                                        <?php
                                        $clientsQuery = mysqli_query($conn, "SELECT tbl_id, client_name FROM dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ORDER BY client_name");
                                        while ($client = mysqli_fetch_assoc($clientsQuery)) {
                                            $sel = (isset($_SESSION['calendar_client']) && $_SESSION['calendar_client'] == $client['tbl_id']) || (isset($_POST['client']) && $_POST['client'] == $client['tbl_id']) ? 'selected' : '';
                                            echo '<option value="'.htmlspecialchars($client['tbl_id']).'" '.$sel.'>'.htmlspecialchars($client['client_name']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <div id="reportrange" style="background: #fff; cursor: pointer; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span id="dateRangeText"><?php echo $_SESSION['stDt'].' - '.$_SESSION['enDt']; ?></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                        <input type="hidden" name="start_date" id="start_date" value="<?php echo $_SESSION['stDt']; ?>">
                                        <input type="hidden" name="end_date" id="end_date" value="<?php echo $_SESSION['enDt']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="submit_report" class="btn btn-primary btn-block">
                                        <i class="fa fa-search"></i> Fetch Report
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($showReport && !empty($monthList) && !empty($dayData)):
                    $totalSpend = array_sum(array_column($dayData, 'spend'));
                    $totalLeads = array_sum(array_column($dayData, 'leads'));
                    $overallCpl = ($totalLeads > 0) ? round($totalSpend / $totalLeads) : 0;
                    $lowCplDay = null; $lowCplVal = null; $highCplDay = null; $highCplVal = null;
                    foreach ($dayData as $d => $row) {
                        if ($row['cpl'] > 0) {
                            if ($lowCplVal === null || $row['cpl'] < $lowCplVal) { $lowCplVal = $row['cpl']; $lowCplDay = $d; }
                            if ($highCplVal === null || $row['cpl'] > $highCplVal) { $highCplVal = $row['cpl']; $highCplDay = $d; }
                        }
                    }
                ?>
                <div id="calendarReportSection" class="report-container mt-4">
                    <div class="cal-summary-row">
                        <div class="cal-summary-card">
                            <div class="cal-summary-card-inner">
                                <div class="cal-summary-label">Total Spend</div>
                                <div class="cal-summary-value"><?php echo moneyFormatIndia($totalSpend); ?></div>
                            </div>
                            <div class="cal-summary-icon spend"><i class="fa fa-money"></i></div>
                        </div>
                        <div class="cal-summary-card">
                            <div class="cal-summary-card-inner">
                                <div class="cal-summary-label">Total Leads</div>
                                <div class="cal-summary-value"><?php echo moneyFormatIndia($totalLeads); ?></div>
                            </div>
                            <div class="cal-summary-icon leads"><i class="fa fa-users"></i></div>
                        </div>
                        <div class="cal-summary-card">
                            <div class="cal-summary-card-inner">
                                <div class="cal-summary-label">CPL</div>
                                <div class="cal-summary-value"><?php echo $overallCpl > 0 ? moneyFormatIndia($overallCpl) : '-'; ?></div>
                            </div>
                            <div class="cal-summary-icon cpl"><i class="fa fa-calculator"></i></div>
                        </div>
                        <div class="cal-summary-card">
                            <div class="cal-summary-card-inner">
                                <div class="cal-summary-label">Low CPL</div>
                                <div class="cal-summary-value"><?php echo $lowCplDay ? date('j M', strtotime($lowCplDay)).' <span class="cal-cpl-num">('.moneyFormatIndia($lowCplVal).')</span>' : '-'; ?></div>
                            </div>
                            <div class="cal-summary-icon low-cpl"><i class="fa fa-arrow-down"></i></div>
                        </div>
                        <div class="cal-summary-card">
                            <div class="cal-summary-card-inner">
                                <div class="cal-summary-label">High CPL</div>
                                <div class="cal-summary-value"><?php echo $highCplDay ? date('j M', strtotime($highCplDay)).' <span class="cal-cpl-num">('.moneyFormatIndia($highCplVal).')</span>' : '-'; ?></div>
                            </div>
                            <div class="cal-summary-icon high-cpl"><i class="fa fa-arrow-up"></i></div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div id="calendarContainer">
                                <?php foreach ($monthList as $idx => $mo):
                                    $y = $mo['year'];
                                    $m = $mo['month'];
                                    $monthStart = strtotime("$y-$m-01");
                                    $monthEnd = strtotime(date("Y-m-t", $monthStart));
                                    $rangeStart = strtotime($dtRange1);
                                    $rangeEnd = strtotime($dtRange2);
                                    $calStart = strtotime('last sunday', $monthStart);
                                    if (date('w', $calStart) != 0) $calStart = strtotime('last sunday', $monthStart);
                                    $weeks = [];
                                    $cur = $calStart;
                                    $lastDay = strtotime('last day of this month', $monthStart);
                                    $endCell = strtotime('next saturday', $lastDay);
                                    while ($cur <= $endCell) {
                                        $weeks[] = $cur;
                                        $cur += 86400;
                                    }
                                    $weeksChunked = array_chunk($weeks, 7);
                                ?>
                                <div class="calendar-month-block" data-month-index="<?php echo $idx; ?>">
                                    <div class="cal-month-nav">
                                        <?php if ($idx > 0): ?><a href="#" class="cal-nav-prev btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> Prev</a><?php else: ?><span></span><?php endif; ?>
                                        <span></span>
                                        <?php if ($idx < count($monthList) - 1): ?><a href="#" class="cal-nav-next btn btn-sm btn-outline-secondary">Next <i class="fa fa-chevron-right"></i></a><?php else: ?><span></span><?php endif; ?>
                                    </div>
                                    <div class="calendar-wrap">
                                        <table class="calendar-table">
                                            <thead>
                                                <tr>
                                                    <th colspan="7" style="text-align:center;"><?php echo date('M Y', $monthStart); ?></th>
                                                </tr>
                                                <tr>
                                                    <th>SUN</th><th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($weeksChunked as $week): ?>
                                                <tr>
                                                    <?php foreach ($week as $cellTs): 
                                                        $cellDate = date('Y-m-d', $cellTs);
                                                        $inRange = ($cellTs >= $rangeStart && $cellTs <= $rangeEnd);
                                                        $inMonth = (date('n', $cellTs) == $m && date('Y', $cellTs) == $y);
                                                        $data = isset($dayData[$cellDate]) ? $dayData[$cellDate] : ['spend' => 0, 'leads' => 0, 'cpl' => 0];
                                                        $showMetrics = $inRange && $inMonth && $data['spend'] > 0;
                                                        $bg = '';
                                                        if ($showMetrics && $data['leads'] == 0) {
                                                            $bg = 'linear-gradient(180deg, #e57373 0%, #b22222 100%)';
                                                        } elseif ($showMetrics && $data['cpl'] > 0 && $cplMin !== null && $cplMax !== null && $cplMin < $cplMax) {
                                                            $bg = getCplBgColor($data['cpl'], $cplMin, $cplMax);
                                                        }
                                                        $festival = isset($festivalsByDate[$cellDate]) ? $festivalsByDate[$cellDate] : '';
                                                        $festivalDisplay = $festival;
                                                        $festivalTitle = $festival;
                                                        if ($festival !== '' && strlen($festival) > 22) {
                                                            $festivalDisplay = substr($festival, 0, 20) . '...';
                                                        }
                                                        if (!$inMonth) {
                                                            $cellClass = 'cal-cell-out';
                                                        } elseif ($inMonth && !$showMetrics) {
                                                            $cellClass = 'cal-cell-empty';
                                                        } else {
                                                            $cellClass = 'cal-cell-inrange';
                                                        }
                                                        if ($showMetrics) $cellClass .= ' cal-cell-has-metrics';
                                                        if ($bg) $cellClass .= ' cal-cell-cpl-bg';
                                                    ?>
                                                    <td class="<?php echo $cellClass; ?>">
                                                        <div class="cal-day-line">
                                                            <?php if ($festival !== ''): ?><span class="cal-festival-badge" title="<?php echo htmlspecialchars($festivalTitle); ?>"><?php echo htmlspecialchars($festivalDisplay); ?></span><?php endif; ?>
                                                            <span class="cal-day-num<?php if ($bg): ?> cal-day-num-colored<?php endif; ?>"<?php if ($bg): ?> style="background:<?php echo htmlspecialchars($bg); ?>"<?php endif; ?>><?php echo date('d', $cellTs); ?></span>
                                                        </div>
                                                        <?php if ($showMetrics): ?>
                                                        <div class="cal-metrics">
                                                            <div class="cal-metrics-label-row">
                                                                <span class="cal-metric-label">S</span>
                                                                <span class="cal-metric-label">L</span>
                                                                <span class="cal-metric-label">CPL</span>
                                                            </div>
                                                            <div class="cal-metrics-value-row">
                                                                <span class="cal-metric-value"><?php echo moneyFormatIndia($data['spend']); ?></span>
                                                                <span class="cal-metric-value"><?php echo moneyFormatIndia($data['leads']); ?></span>
                                                                <span class="cal-metric-value"><?php echo moneyFormatIndia($data['cpl']); ?></span>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($monthList) > 1): ?>
                            <p class="text-muted small mt-2">Use Prev / Next above each month to navigate.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php elseif ($showReport && $selectedClientId && empty($dayData)): ?>
                <div class="alert alert-info mt-4">No data for the selected client and date range.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    $(function () {
        <?php if (isset($_POST['submit_report'])): ?> $('.loading-overlay').removeClass('active'); <?php endif; ?>
        $('form').on('submit', function () {
            var c = $('select[name="client"]').val();
            if (!c) { alert('Please select a client.'); return false; }
            $('.loading-overlay').addClass('active');
        });
        var start = moment('<?php echo $_SESSION['stDt']; ?>', 'MM/DD/YYYY');
        var end = moment('<?php echo $_SESSION['enDt']; ?>', 'MM/DD/YYYY');
        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            parentEl: 'body',
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
        }, function (s, e) {
            $('#dateRangeText').html(s.format('MM/DD/YYYY') + ' - ' + e.format('MM/DD/YYYY'));
            $('#start_date').val(s.format('MM/DD/YYYY'));
            $('#end_date').val(e.format('MM/DD/YYYY'));
        });
        $('#reportrange').on('show.daterangepicker', function () {
            var $dp = $('.daterangepicker:visible').last();
            if ($dp.length && $dp.parent()[0] !== document.body) {
                $dp.appendTo('body');
            }
        });
        // Show only one month at a time when multiple months
        var $blocks = $('.calendar-month-block');
        if ($blocks.length > 1) {
            $blocks.hide().eq(0).show();
            $('.cal-nav-next').on('click', function (e) { e.preventDefault(); var $b = $(this).closest('.calendar-month-block'); var $n = $b.next('.calendar-month-block'); if ($n.length) { $b.hide(); $n.show(); } });
            $('.cal-nav-prev').on('click', function (e) { e.preventDefault(); var $b = $(this).closest('.calendar-month-block'); var $p = $b.prev('.calendar-month-block'); if ($p.length) { $b.hide(); $p.show(); } });
        }
    });
    </script>
</body>
</html>
