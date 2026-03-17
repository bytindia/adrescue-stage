<?php




$dirPath = '/home/digitalb2k/stage.adrescue.in/';
require_once $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
require_once $dirPath . 'google-sheets-api/read-sheet.php';

$uId = $tbl_id2 = 2;
$spreadsheetId      = '1F81uo6cwGcLbWH3Q-Qlxpb1t3kSg6Hr8DyZMwpEyKTc';
$sheetNameThisMonth = 'Lead List';

$sheettabinbound = 'Inbound';
$sheettabwebsite = 'Website';



// Fetch data from Google Sheet
$sheetRowsThis    = read_sheet($uId, $spreadsheetId, $sheetNameThisMonth);
$sheetRowsWebsite = read_sheet($uId, $spreadsheetId, $sheettabwebsite);
$sheetRowsInbound = read_sheet($uId, $spreadsheetId, $sheettabinbound);

$rows        = isset($sheetRowsThis['data']) ? $sheetRowsThis['data'] : [];
$rowsWebsite = isset($sheetRowsWebsite['data']) ? $sheetRowsWebsite['data'] : [];
$rowsInbound = isset($sheetRowsInbound['data']) ? $sheetRowsInbound['data'] : [];

// Column indexes (0‑based) based on the sample you shared
$COL_DATE     = 4;  // Created / Date column
$COL_FORM     = 6;  // Form name
$COL_VALIDITY = 27; // Valid / Invalid / Mislead

// Remove header row if present
if (!empty($rows)) {
    $header = array_shift($rows);
}
if (!empty($rowsWebsite)) {
    $websiteHeader = array_shift($rowsWebsite);
}
if (!empty($rowsInbound)) {
    $inboundHeader = array_shift($rowsInbound);
}

// Read date filter (Y-m-d) from GET
$startDateInput = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDateInput   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// If no explicit dates are provided, default to "this month" (1st of month to today)
if ($startDateInput === '' && $endDateInput === '') {
    $today = new DateTime('today');
    $firstOfMonth = (clone $today)->modify('first day of this month');

    $startDateInput = $firstOfMonth->format('Y-m-d');
    $endDateInput   = $today->format('Y-m-d');
}

// Read dimension filters from GET
$filterForm     = isset($_GET['form']) ? trim($_GET['form']) : '';
$filterCampaign = isset($_GET['campaign']) ? trim($_GET['campaign']) : '';
$filterAdset    = isset($_GET['adset']) ? trim($_GET['adset']) : '';
$filterAdName   = isset($_GET['ad_name']) ? trim($_GET['ad_name']) : '';

// Helpers
function parse_sheet_date($value)
{
    if (!$value) {
        return null;
    }

    // Strip time / extra text after comma
    $parts = explode(',', $value);
    $first = trim($parts[0]);

    // Also strip any trailing time if it is in "dd-mm-yyyy hh:mm" style
    $firstParts = preg_split('/\s+/', $first);
    $datePart   = trim($firstParts[0]);

    // Try multiple common date formats used in sheets
    $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $datePart);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }

    // Fallback: let strtotime try to parse
    $ts = strtotime($datePart);
    if ($ts !== false) {
        $dt = new DateTime('@' . $ts);
        $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $dt->format('Y-m-d');
    }

    return null;
}

function aggregate_sheet_by_feedback($rows, $dateCol, $feedbackCol, $startDate, $endDate)
{
    $total   = 0;
    $valid   = 0;
    $invalid = 0;
    $mislead = 0;

    foreach ($rows as $row) {
        $rawDate = isset($row[$dateCol]) ? $row[$dateCol] : '';
        $rowDate = parse_sheet_date($rawDate);

        if ($startDate && $endDate) {
            if (!$rowDate) {
                continue;
            }
            if ($rowDate < $startDate || $rowDate > $endDate) {
                continue;
            }
        }

        $total++;

        $type = classify_validity(isset($row[$feedbackCol]) ? $row[$feedbackCol] : '');
        if ($type === 'valid') {
            $valid++;
        } elseif ($type === 'invalid') {
            $invalid++;
        } elseif ($type === 'mislead') {
            $mislead++;
        }
    }

    return [
        'total'   => $total,
        'valid'   => $valid,
        'invalid' => $invalid,
        'mislead' => $mislead,
    ];
}

function classify_validity($raw)
{
    $val = strtolower(trim((string)$raw));

    if ($val === '') {
        return 'unknown';
    }

    if (strpos($val, 'valid') === 0) {
        return 'valid';
    }

    if (strpos($val, 'invalid') === 0) {
        return 'invalid';
    }

    if (strpos($val, 'mislead') === 0) {
        return 'mislead';
    }

    return 'other';
}

// Aggregate counters
$totalLeads   = 0;
$validCount   = 0;
$invalidCount = 0;
$misleadCount = 0;

$formsSummary      = []; // formName => ['total' => .., 'valid' => .., 'invalid' => .., 'mislead' => ..]
$campaignsSummary  = []; // campaignName => ['total' => .., 'valid' => .., 'invalid' => .., 'mislead' => ..]
$adsetSummary      = []; // adsetName => ['total' => .., 'valid' => .., 'invalid' => .., 'mislead' => ..]
$adNameSummary     = []; // adName => ['total' => .., 'valid' => .., 'invalid' => .., 'mislead' => ..]

// Distinct options for dropdown filters
$formOptions     = [];
$campaignOptions = [];
$adsetOptions    = [];
$adNameOptions   = [];

// Website & Inbound stats
// User-provided indexes [1] (date) and [16] (feedback) are taken directly
// from the PHP array dump, so we use them as-is here.
$websiteStats = aggregate_sheet_by_feedback($rowsWebsite, 1, 16, $startDateInput, $endDateInput);
$inboundStats = aggregate_sheet_by_feedback($rowsInbound, 1, 16, $startDateInput, $endDateInput);

foreach ($rows as $row) {
    $cellDate = isset($row[$COL_DATE]) ? $row[$COL_DATE] : '';
    $rowDate  = parse_sheet_date($cellDate);

    // Apply date filter only if both dates are provided
    if ($startDateInput && $endDateInput) {
        if (!$rowDate) {
            continue;
        }

        if ($rowDate < $startDateInput || $rowDate > $endDateInput) {
            continue;
        }
    }

    $formName = isset($row[$COL_FORM]) && $row[$COL_FORM] !== '' ? $row[$COL_FORM] : 'Unknown';
    $campaignName = isset($row[7]) && $row[7] !== '' ? $row[7] : 'Unknown';
    $adsetName    = isset($row[8]) && $row[8] !== '' ? $row[8] : 'Unknown';
    $adName       = isset($row[9]) && $row[9] !== '' ? $row[9] : 'Unknown';

    // Build unique option lists (after date filter)
    $formOptions[$formName]         = true;
    $campaignOptions[$campaignName] = true;
    $adsetOptions[$adsetName]       = true;
    $adNameOptions[$adName]         = true;

    // Apply dimension filters if provided
    if ($filterForm && $formName !== $filterForm) {
        continue;
    }
    if ($filterCampaign && $campaignName !== $filterCampaign) {
        continue;
    }
    if ($filterAdset && $adsetName !== $filterAdset) {
        continue;
    }
    if ($filterAdName && $adName !== $filterAdName) {
        continue;
    }

    $totalLeads++;

    if (!isset($formsSummary[$formName])) {
        $formsSummary[$formName] = [
            'total'   => 0,
            'valid'   => 0,
            'invalid' => 0,
            'mislead' => 0,
        ];
    }

    $formsSummary[$formName]['total']++;

    // Campaign-wise aggregation (column [7] - Campaign)
    if (!isset($campaignsSummary[$campaignName])) {
        $campaignsSummary[$campaignName] = [
            'total'   => 0,
            'valid'   => 0,
            'invalid' => 0,
            'mislead' => 0,
        ];
    }
    $campaignsSummary[$campaignName]['total']++;

    // Adset-wise aggregation (column [8] - Adset Name)
    $adsetName = isset($row[8]) && $row[8] !== '' ? $row[8] : 'Unknown';
    if (!isset($adsetSummary[$adsetName])) {
        $adsetSummary[$adsetName] = [
            'total'   => 0,
            'valid'   => 0,
            'invalid' => 0,
            'mislead' => 0,
        ];
    }
    $adsetSummary[$adsetName]['total']++;

    // Ad Name-wise aggregation (column [9] - Ad Name)
    $adName = isset($row[9]) && $row[9] !== '' ? $row[9] : 'Unknown';
    if (!isset($adNameSummary[$adName])) {
        $adNameSummary[$adName] = [
            'total'   => 0,
            'valid'   => 0,
            'invalid' => 0,
            'mislead' => 0,
        ];
    }
    $adNameSummary[$adName]['total']++;

    $validityType = classify_validity(isset($row[$COL_VALIDITY]) ? $row[$COL_VALIDITY] : '');

    if ($validityType === 'valid') {
        $validCount++;
        $formsSummary[$formName]['valid']++;
        $campaignsSummary[$campaignName]['valid']++;
        $adsetSummary[$adsetName]['valid']++;
        $adNameSummary[$adName]['valid']++;
    } elseif ($validityType === 'invalid') {
        $invalidCount++;
        $formsSummary[$formName]['invalid']++;
        $campaignsSummary[$campaignName]['invalid']++;
        $adsetSummary[$adsetName]['invalid']++;
        $adNameSummary[$adName]['invalid']++;
    } elseif ($validityType === 'mislead') {
        $misleadCount++;
        $formsSummary[$formName]['mislead']++;
        $campaignsSummary[$campaignName]['mislead']++;
        $adsetSummary[$adsetName]['mislead']++;
        $adNameSummary[$adName]['mislead']++;
    }
}

// Combined stats (Lead List + Website + Inbound)
$combinedTotalLeads   = $totalLeads + $websiteStats['total'] + $inboundStats['total'];
$combinedValidCount   = $validCount + $websiteStats['valid'] + $inboundStats['valid'];
$combinedInvalidCount = $invalidCount + $websiteStats['invalid'] + $inboundStats['invalid'];
$combinedMisleadCount = $misleadCount + $websiteStats['mislead'] + $inboundStats['mislead'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sagehill Leads Summary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: radial-gradient(circle at top left, #ecfeff 0, #eef2ff 35%, #f9fafb 100%);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
        }
        .container-fluid {
            max-width: 1380px;
        }
        .filter-card {
            background: linear-gradient(135deg, #ffffff, #eef2ff);
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            padding: 22px 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }
        .filter-card .form-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            font-weight: 600;
            color: #6b7280;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 999px;
            border-color: #e5e7eb;
            font-size: 0.9rem;
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            border-color: #3b82f6;
        }
        .filter-card .btn-primary {
            border-radius: 999px;
            font-weight: 600;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            border: none;
        }
        .filter-card .btn-outline-secondary {
            border-radius: 999px;
        }

        .metric-card {
            position: relative;
            background: linear-gradient(135deg, #ffffff, #f9fafb);
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            padding: 20px 20px 18px 20px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 55px rgba(15, 23, 42, 0.12);
        }
        .metric-icon {
            position: absolute;
            top: 14px;
            right: 16px;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #0f172a;
        }
        .metric-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .metric-value {
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
        }
        .metric-caption {
            margin-top: 6px;
            font-size: 1rem;
            color: #6b7280;
        }
        .metric-valid   { border-top: 3px solid #22c55e; }
        .metric-invalid { border-top: 3px solid #ef4444; }
        .metric-mislead { border-top: 3px solid #f97316; }
        .metric-total   { border-top: 3px solid #3b82f6; }

        .table-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            padding: 20px 22px 18px 22px;
            margin-top: 28px;
            border: 1px solid rgba(148, 163, 184, 0.22);
        }
        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .table-title {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }
        .table-title-icon {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            background: rgba(59, 130, 246, 0.08);
            color: #2563eb;
        }
        .table thead th {
            background-color: #0ea5e9;
            color: #ffffff;
            vertical-align: middle;
        }
        .dt-buttons .btn {
            margin-right: 4px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.history.back();">
            <i class="bi bi-arrow-left"></i> Back
        </button>
    </div>
    <div class="filter-card">
        <form class="row g-3 align-items-end" method="get">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDateInput); ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDateInput); ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Form</label>
                <select name="form" class="form-select">
                    <option value="">All Forms</option>
                    <?php foreach (array_keys($formOptions) as $formOpt): ?>
                        <option value="<?php echo htmlspecialchars($formOpt); ?>" <?php echo $filterForm === $formOpt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($formOpt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Campaign</label>
                <select name="campaign" class="form-select">
                    <option value="">All Campaigns</option>
                    <?php foreach (array_keys($campaignOptions) as $campOpt): ?>
                        <option value="<?php echo htmlspecialchars($campOpt); ?>" <?php echo $filterCampaign === $campOpt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($campOpt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-100"></div>

            <div class="col-md-3">
                <label class="form-label">Adset Name</label>
                <select name="adset" class="form-select">
                    <option value="">All Adsets</option>
                    <?php foreach (array_keys($adsetOptions) as $adsetOpt): ?>
                        <option value="<?php echo htmlspecialchars($adsetOpt); ?>" <?php echo $filterAdset === $adsetOpt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($adsetOpt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ad Name</label>
                <select name="ad_name" class="form-select">
                    <option value="">All Ads</option>
                    <?php foreach (array_keys($adNameOptions) as $adNameOpt): ?>
                        <option value="<?php echo htmlspecialchars($adNameOpt); ?>" <?php echo $filterAdName === $adNameOpt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($adNameOpt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 ms-auto">
                <button type="submit" class="btn btn-primary w-100">
                    Filter
                </button>
            </div>
            <div class="col-md-3">
                <a href="sagehil-lead-dash.php" class="btn btn-outline-secondary w-100">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-3">
        <div class="col-md-3">
            <div class="metric-card metric-total">
                <div class="metric-icon bg-warning-subtle">
                    <i class="bi bi-currency-rupee"></i>
                </div>
                <div class="metric-label">Leads Total</div>
                <div class="metric-value"><?php echo number_format($combinedTotalLeads); ?></div>
                <div class="metric-caption">
                    LI: <?php echo number_format($totalLeads); ?>
                    &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['total']); ?>
                    &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['total']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card metric-valid">
                <div class="metric-icon bg-success-subtle">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div class="metric-label">Valid</div>
                <div class="metric-value"><?php echo number_format($combinedValidCount); ?></div>
                <div class="metric-caption">
                    LI: <?php echo number_format($validCount); ?>
                    &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['valid']); ?>
                    &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['valid']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card metric-invalid">
                <div class="metric-icon bg-danger-subtle">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="metric-label">Invalid</div>
                <div class="metric-value"><?php echo number_format($combinedInvalidCount); ?></div>
                <div class="metric-caption">
                    LI: <?php echo number_format($invalidCount); ?>
                    &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['invalid']); ?>
                    &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['invalid']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card metric-mislead">
                <div class="metric-icon bg-warning-subtle">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="metric-label">Mislead</div>
                <div class="metric-value"><?php echo number_format($combinedMisleadCount); ?></div>
                <div class="metric-caption">
                    LI: <?php echo number_format($misleadCount); ?>
                    &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['mislead']); ?>
                    &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['mislead']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="metric-card">
                <div class="metric-icon bg-info-subtle">
                    <i class="bi bi-globe2"></i>
                </div>
                <div class="metric-label">Website Leads</div>
                <div class="metric-value"><?php echo number_format($websiteStats['total']); ?></div>
                <div class="metric-caption">
                    Mislead: <strong><?php echo number_format($websiteStats['mislead']); ?></strong>
                    &nbsp;•&nbsp;
                    Invalid: <strong><?php echo number_format($websiteStats['invalid']); ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="metric-card">
                <div class="metric-icon bg-primary-subtle">
                    <i class="bi bi-telephone-inbound"></i>
                </div>
                <div class="metric-label">Inbound Leads</div>
                <div class="metric-value"><?php echo number_format($inboundStats['total']); ?></div>
                <div class="metric-caption">
                    Mislead: <strong><?php echo number_format($inboundStats['mislead']); ?></strong>
                    &nbsp;•&nbsp;
                    Invalid: <strong><?php echo number_format($inboundStats['invalid']); ?></strong>
                </div>
            </div>
        </div>
    </div> -->

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-title">
                <span class="table-title-icon"><i class="bi bi-ui-checks-grid"></i></span>
                <span>Form-wise Summary</span>
            </div>
        </div>
        <div class="table-responsive">
            <table id="table-form-summary" class="table table-striped table-bordered align-middle mb-0">
                <thead>
                <tr>
                    <th>Form</th>
                    <th class="text-end">Total Leads</th>
                    <th class="text-end">Valid</th>
                    <th class="text-end">Invalid</th>
                    <th class="text-end">Mislead</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $grandTotal = 0;
                $grandValid = 0;
                $grandInvalid = 0;
                $grandMislead = 0;

                foreach ($formsSummary as $formName => $summary) {
                    $grandTotal   += $summary['total'];
                    $grandValid   += $summary['valid'];
                    $grandInvalid += $summary['invalid'];
                    $grandMislead += $summary['mislead'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($formName); ?></td>
                        <td class="text-end"><?php echo number_format($summary['total']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['valid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['invalid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['mislead']); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="fw-semibold">
                    <td>Total</td>
                    <td class="text-end"><?php echo number_format($grandTotal); ?></td>
                    <td class="text-end"><?php echo number_format($grandValid); ?></td>
                    <td class="text-end"><?php echo number_format($grandInvalid); ?></td>
                    <td class="text-end"><?php echo number_format($grandMislead); ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-title">
                <span class="table-title-icon"><i class="bi bi-bullseye"></i></span>
                <span>Campaign-wise Summary</span>
            </div>
        </div>
        <div class="table-responsive">
            <table id="table-campaign-summary" class="table table-striped table-bordered align-middle mb-0">
                <thead>
                <tr>
                    <th>Campaign</th>
                    <th class="text-end">Total Leads</th>
                    <th class="text-end">Valid</th>
                    <th class="text-end">Invalid</th>
                    <th class="text-end">Mislead</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $cGrandTotal   = 0;
                $cGrandValid   = 0;
                $cGrandInvalid = 0;
                $cGrandMislead = 0;

                foreach ($campaignsSummary as $campaignName => $summary) {
                    $cGrandTotal   += $summary['total'];
                    $cGrandValid   += $summary['valid'];
                    $cGrandInvalid += $summary['invalid'];
                    $cGrandMislead += $summary['mislead'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($campaignName); ?></td>
                        <td class="text-end"><?php echo number_format($summary['total']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['valid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['invalid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['mislead']); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="fw-semibold">
                    <td>Total</td>
                    <td class="text-end"><?php echo number_format($cGrandTotal); ?></td>
                    <td class="text-end"><?php echo number_format($cGrandValid); ?></td>
                    <td class="text-end"><?php echo number_format($cGrandInvalid); ?></td>
                    <td class="text-end"><?php echo number_format($cGrandMislead); ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-title">
                <span class="table-title-icon"><i class="bi bi-diagram-3"></i></span>
                <span>Adset Name Summary</span>
            </div>
        </div>
        <div class="table-responsive">
            <table id="table-adset-summary" class="table table-striped table-bordered align-middle mb-0">
                <thead>
                <tr>
                    <th>Adset Name</th>
                    <th class="text-end">Total Leads</th>
                    <th class="text-end">Valid</th>
                    <th class="text-end">Invalid</th>
                    <th class="text-end">Mislead</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $aGrandTotal   = 0;
                $aGrandValid   = 0;
                $aGrandInvalid = 0;
                $aGrandMislead = 0;

                foreach ($adsetSummary as $adsetNameRow => $summary) {
                    $aGrandTotal   += $summary['total'];
                    $aGrandValid   += $summary['valid'];
                    $aGrandInvalid += $summary['invalid'];
                    $aGrandMislead += $summary['mislead'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($adsetNameRow); ?></td>
                        <td class="text-end"><?php echo number_format($summary['total']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['valid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['invalid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['mislead']); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="fw-semibold">
                    <td>Total</td>
                    <td class="text-end"><?php echo number_format($aGrandTotal); ?></td>
                    <td class="text-end"><?php echo number_format($aGrandValid); ?></td>
                    <td class="text-end"><?php echo number_format($aGrandInvalid); ?></td>
                    <td class="text-end"><?php echo number_format($aGrandMislead); ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-title">
                <span class="table-title-icon"><i class="bi bi-megaphone"></i></span>
                <span>Ad Name Summary</span>
            </div>
        </div>
        <div class="table-responsive">
            <table id="table-adname-summary" class="table table-striped table-bordered align-middle mb-0">
                <thead>
                <tr>
                    <th>Ad Name</th>
                    <th class="text-end">Total Leads</th>
                    <th class="text-end">Valid</th>
                    <th class="text-end">Invalid</th>
                    <th class="text-end">Mislead</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $adGrandTotal   = 0;
                $adGrandValid   = 0;
                $adGrandInvalid = 0;
                $adGrandMislead = 0;

                foreach ($adNameSummary as $adNameRow => $summary) {
                    $adGrandTotal   += $summary['total'];
                    $adGrandValid   += $summary['valid'];
                    $adGrandInvalid += $summary['invalid'];
                    $adGrandMislead += $summary['mislead'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($adNameRow); ?></td>
                        <td class="text-end"><?php echo number_format($summary['total']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['valid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['invalid']); ?></td>
                        <td class="text-end"><?php echo number_format($summary['mislead']); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="fw-semibold">
                    <td>Total</td>
                    <td class="text-end"><?php echo number_format($adGrandTotal); ?></td>
                    <td class="text-end"><?php echo number_format($adGrandValid); ?></td>
                    <td class="text-end"><?php echo number_format($adGrandInvalid); ?></td>
                    <td class="text-end"><?php echo number_format($adGrandMislead); ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
    function initSummaryTable(tableId) {
        const $table = $('#' + tableId);
        if ($table.length === 0) return;

        $table.DataTable({
            paging: false,
            searching: true,
            info: true, // show "Showing 1 to N of N entries" below the table
            ordering: true,
            dom: 'Bfrtip',
            buttons: [
                {extend: 'csvHtml5', className: 'btn btn-info btn-sm', text: 'CSV'},
                {extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: 'Excel'},
                {extend: 'pdfHtml5', className: 'btn btn-danger btn-sm', text: 'PDF'},
                {extend: 'copyHtml5', className: 'btn btn-success btn-sm', text: 'Copy'}
            ]
        });
    }

    $(function () {
        initSummaryTable('table-form-summary');
        initSummaryTable('table-campaign-summary');
        initSummaryTable('table-adset-summary');
        initSummaryTable('table-adname-summary');
    });
</script>

</body>
</html>






