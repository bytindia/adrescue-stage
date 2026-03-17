<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);


$dirPath = '/home/digitalb2k/stage.adrescue.in/';
require_once $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
require_once $dirPath . 'google-sheets-api/read-sheet.php';

$uId = $tbl_id2 = 2;
$spreadsheetId = '1F81uo6cwGcLbWH3Q-Qlxpb1t3kSg6Hr8DyZMwpEyKTc';
$sheetNameThisMonth = 'Lead List';

$sheettabinbound = 'Inbound';
$sheettabwebsite = 'Website';
$sheettwhatsapp = 'WhatsApp';
$sheettprintmedia = 'Print media';
$sheettapplication = 'Application Status';



// Fetch data from Google Sheet
$sheetRowsThis = read_sheet($uId, $spreadsheetId, $sheetNameThisMonth);
$sheetRowsWebsite = read_sheet($uId, $spreadsheetId, $sheettabwebsite);
$sheetRowsInbound = read_sheet($uId, $spreadsheetId, $sheettabinbound);
$sheetRowsWhatsApp = read_sheet($uId, $spreadsheetId, $sheettwhatsapp);
$sheetRowsPrintMedia = read_sheet($uId, $spreadsheetId, $sheettprintmedia);
$sheetRowsApplication = read_sheet($uId, $spreadsheetId, $sheettapplication);


$rows = isset($sheetRowsThis['data']) ? $sheetRowsThis['data'] : [];
$rowsWebsite = isset($sheetRowsWebsite['data']) ? $sheetRowsWebsite['data'] : [];
$rowsInbound = isset($sheetRowsInbound['data']) ? $sheetRowsInbound['data'] : [];
$rowsWhatsApp = isset($sheetRowsWhatsApp['data']) ? $sheetRowsWhatsApp['data'] : [];
$rowsPrintMedia = isset($sheetRowsPrintMedia['data']) ? $sheetRowsPrintMedia['data'] : [];

// Column indexes (0‑based) based on the sample you shared
$COL_DATE = 5;  // Created / Date column
$COL_FORM = 6;  // Form name
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
if (!empty($rowsWhatsApp)) {
    $whatsAppHeader = array_shift($rowsWhatsApp);
}
if (!empty($rowsPrintMedia)) {
    $printMediaHeader = array_shift($rowsPrintMedia);
}

// Read date range preset from GET
$dateRangePreset = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$startDateInput = '';
$endDateInput = '';

if ($dateRangePreset) {
    try {
        $today = new DateTime('today');
        switch ($dateRangePreset) {
            case 'today':
                $startDateInput = $today->format('Y-m-d');
                $endDateInput = $today->format('Y-m-d');
                break;
            case 'yesterday':
                $yesterday = (clone $today)->modify('-1 day');
                $startDateInput = $yesterday->format('Y-m-d');
                $endDateInput = $yesterday->format('Y-m-d');
                break;
            case 'last_7_days':
                $startDateInput = (clone $today)->modify('-6 days')->format('Y-m-d');
                $endDateInput = $today->format('Y-m-d');
                break;
            case 'last_30_days':
                $startDateInput = (clone $today)->modify('-29 days')->format('Y-m-d');
                $endDateInput = $today->format('Y-m-d');
                break;
            case 'this_month':
                $startDateInput = $today->format('Y-m-01');
                $endDateInput = $today->format('Y-m-d');
                break;
            case 'last_month':
                $start = (clone $today)->modify('first day of last month');
                $end = (clone $today)->modify('last day of last month');
                $startDateInput = $start->format('Y-m-d');
                $endDateInput = $end->format('Y-m-d');
                break;
            case 'all_time':
                $startDateInput = '2020-01-01';
                $endDateInput = $today->format('Y-m-d');
                break;
            default:
                // Fallback to last 30 days
                $dateRangePreset = 'last_30_days';
                $startDateInput = (clone $today)->modify('-29 days')->format('Y-m-d');
                $endDateInput = $today->format('Y-m-d');
                break;
        }
    } catch (Exception $e) {
        // Fallback on error
        $dateRangePreset = 'last_30_days';
        $startDateInput = date('Y-m-d', strtotime('-30 days'));
        $endDateInput = date('Y-m-d');
    }
} else {
    // If no explicit preset is provided, check for manual dates or default to "last 30 days"
    $startDateInput = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDateInput = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    if ($startDateInput === '' && $endDateInput === '') {
        $dateRangePreset = 'last_30_days';
        $today = new DateTime('today');
        $thirtyDaysAgo = (clone $today)->modify('-29 days'); // -29 to include today in 30 days count roughly

        $startDateInput = $thirtyDaysAgo->format('Y-m-d');
        $endDateInput = $today->format('Y-m-d');
    } else {
        // Custom dates were provided (manually via URL maybe), so preset is 'custom' or empty
        $dateRangePreset = 'custom';
    }
}

// Read dimension filters from GET
$filterForm = isset($_GET['form']) ? trim($_GET['form']) : '';
$filterCampaign = isset($_GET['campaign']) ? trim($_GET['campaign']) : '';
$filterAdset = isset($_GET['adset']) ? trim($_GET['adset']) : '';
$filterAdName = isset($_GET['ad_name']) ? trim($_GET['ad_name']) : '';

// Convert dates to d-m-Y format for API calls
$apiStartDate = '';
$apiEndDate = '';
if ($startDateInput && $endDateInput) {
    $startDt = DateTime::createFromFormat('Y-m-d', $startDateInput);
    $endDt = DateTime::createFromFormat('Y-m-d', $endDateInput);
    if ($startDt && $endDt) {
        $apiStartDate = $startDt->format('d-m-Y');
        $apiEndDate = $endDt->format('d-m-Y');
    }
}

// Fetch API data for sagehill-cc and sagehill-byt
$sagehillCCData = ['tot_fb_spend' => 0, 'tot_fb_leads' => 0, 'tot_g_spend' => 0, 'tot_g_leads' => 0, 'tot_spend' => 0, 'tot_leads' => 0];
$sagehillBYTData = ['tot_fb_spend' => 0, 'tot_fb_leads' => 0, 'tot_g_spend' => 0, 'tot_g_leads' => 0, 'tot_spend' => 0, 'tot_leads' => 0];

if ($apiStartDate && $apiEndDate) {
    // Fetch sagehill-cc data
    $ccUrl = "https://stage.adrescue.in/fetch-report-json.php?tag=sagehill-cc&start={$apiStartDate}&end={$apiEndDate}&ty=all";
    $ccResponse = @file_get_contents($ccUrl);
    if ($ccResponse !== false) {
        $ccJson = json_decode($ccResponse, true);
        if (is_array($ccJson)) {
            $sagehillCCData = array_merge($sagehillCCData, $ccJson);
        }
    }

    // Fetch sagehill-byt data
    $bytUrl = "https://stage.adrescue.in/fetch-report-json.php?tag=sagehill-byt&start={$apiStartDate}&end={$apiEndDate}&ty=all";
    $bytResponse = @file_get_contents($bytUrl);
    if ($bytResponse !== false) {
        $bytJson = json_decode($bytResponse, true);
        if (is_array($bytJson)) {
            $sagehillBYTData = array_merge($sagehillBYTData, $bytJson);
        }
    }
}

// Fetch Traffic Analytics data (pass filter params when they change)
$trafficAnalytics = ['traffic' => ['totalSessions' => 0, 'engagementRate' => 0, 'avgEngagementTimePerSession_fmt' => '00:00'], 'topCities' => []];
$analyticsParams = [
    'start_date' => $startDateInput,
    'end_date' => $endDateInput,
    'form' => $filterForm,
    'campaign' => $filterCampaign,
    'adset' => $filterAdset,
    'ad_name' => $filterAdName,
];
$analyticsUrl = 'https://stage.adrescue.in/dash/sagehill-analytics.php?' . http_build_query($analyticsParams);
$analyticsResponse = @file_get_contents($analyticsUrl);
if ($analyticsResponse !== false) {
    $analyticsJson = json_decode($analyticsResponse, true);
    if (is_array($analyticsJson) && isset($analyticsJson['traffic']) && isset($analyticsJson['topCities'])) {
        $trafficAnalytics = $analyticsJson;
    }
}

// Calculate totals
$grandTotSpend = $sagehillCCData['tot_spend'] + $sagehillBYTData['tot_spend'];
$grandTotLeads = $sagehillCCData['tot_leads'] + $sagehillBYTData['tot_leads'];
$grandTotFBSpend = $sagehillCCData['tot_fb_spend'] + $sagehillBYTData['tot_fb_spend'];
$grandTotFBLeads = $sagehillCCData['tot_fb_leads'] + $sagehillBYTData['tot_fb_leads'];
$grandTotGSpend = $sagehillCCData['tot_g_spend'] + $sagehillBYTData['tot_g_spend'];
$grandTotGLeads = $sagehillCCData['tot_g_leads'] + $sagehillBYTData['tot_g_leads'];

$grandTotValidPct = $grandTotLeads > 0 ? ($grandTotLeads / $grandTotSpend) * 100 : 0; // Just a placeholder formula if needed later
// Actual Lead % (Valid / Total) for sheets will be calc in table rows.


// Helpers
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
    $datePart = trim($firstParts[0]);

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

function classify_validity($raw)
{
    $val = strtolower(trim((string) $raw));

    if ($val === '') {
        return 'unknown';
    }

    if (strpos($val, 'valid') === 0) {
        return 'valid';
    }

    if (strpos($val, 'invalid') === 0 || strpos($val, 'unvalid') === 0 || strpos($val, 'not interested') !== false) {
        return 'invalid';
    }

    if (strpos($val, 'mislead') === 0) {
        return 'mislead';
    }

    return 'other';
}

function find_col_index($headerRow, $possibleNames, $defaultIdx)
{
    if (empty($headerRow) || !is_array($headerRow)) {
        return $defaultIdx;
    }

    $lowerHeader = array_map(function ($h) {
        return strtolower(trim((string) $h));
    }, $headerRow);

    foreach ($possibleNames as $name) {
        $idx = array_search(strtolower($name), $lowerHeader);
        if ($idx !== false) {
            return $idx;
        }
    }

    // Try partial matches if exact match failed
    foreach ($possibleNames as $name) {
        foreach ($lowerHeader as $idx => $hVal) {
            if (strpos($hVal, strtolower($name)) !== false) {
                return $idx;
            }
        }
    }

    return $defaultIdx;
}

function aggregate_sheet_by_feedback($rows, $dateCol, $feedbackCol, $startDate, $endDate)
{
    $total = 0;
    $valid = 0;
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
        'total' => $total,
        'valid' => $valid,
        'invalid' => $invalid,
        'mislead' => $mislead,
    ];
}

function traffic_format_sec($sec)
{
    $m = floor((float) $sec / 60);
    $s = floor((float) $sec % 60);
    return sprintf('%02d:%02d', $m, $s);
}

// Aggregate counters
$totalLeads = 0;
$validCount = 0;
$invalidCount = 0;
$misleadCount = 0;

$formsSummary = [];
$campaignsSummary = [];
$adsetSummary = [];
$adNameSummary = [];

$formOptions = [];
$campaignOptions = [];
$adsetOptions = [];
$adNameOptions = [];

// Dynamic Column Finding
$dateKeywords = ['date', 'timestamp', 'created', 'time'];
$statusKeywords = ['status', 'feedback', 'validity', 'quality', 'remark'];

// Website
$webDateIdx = find_col_index($websiteHeader ?? [], $dateKeywords, 1);
$webStatusIdx = find_col_index($websiteHeader ?? [], $statusKeywords, 16);
$websiteStats = aggregate_sheet_by_feedback($rowsWebsite, $webDateIdx, $webStatusIdx, $startDateInput, $endDateInput);

// Inbound
$inbDateIdx = find_col_index($inboundHeader ?? [], $dateKeywords, 1);
$inbStatusIdx = find_col_index($inboundHeader ?? [], $statusKeywords, 16);
$inboundStats = aggregate_sheet_by_feedback($rowsInbound, $inbDateIdx, $inbStatusIdx, $startDateInput, $endDateInput);

// WhatsApp
$waDateIdx = find_col_index($whatsAppHeader ?? [], $dateKeywords, 1);
$waStatusIdx = find_col_index($whatsAppHeader ?? [], $statusKeywords, 12);
$whatsAppStats = aggregate_sheet_by_feedback($rowsWhatsApp, $waDateIdx, $waStatusIdx, $startDateInput, $endDateInput);

// Print Media
$pmDateIdx = find_col_index($printMediaHeader ?? [], $dateKeywords, 1);
$pmStatusIdx = find_col_index($printMediaHeader ?? [], $statusKeywords, 15);
$printMediaStats = aggregate_sheet_by_feedback($rowsPrintMedia, $pmDateIdx, $pmStatusIdx, $startDateInput, $endDateInput);


// Process Main Lead List
// Try to find columns dynamically for main list too, falling back to originals
$mainDateIdx = find_col_index($header ?? [], $dateKeywords, $COL_DATE);
$mainFormIdx = find_col_index($header ?? [], ['form', 'source'], $COL_FORM);
$mainStatusIdx = find_col_index($header ?? [], $statusKeywords, $COL_VALIDITY);
// Campaign, Adset, Ad Name dynamic lookup
$mainCampIdx = find_col_index($header ?? [], ['campaign'], 7);
$mainAdsetIdx = find_col_index($header ?? [], ['adset'], 8);
$mainAdNameIdx = find_col_index($header ?? [], ['ad name', 'ad_name'], 9);

foreach ($rows as $row) {
    $cellDate = isset($row[$mainDateIdx]) ? $row[$mainDateIdx] : '';
    $rowDate = parse_sheet_date($cellDate);

    // Apply date filter
    if ($startDateInput && $endDateInput) {
        if (!$rowDate) {
            continue;
        }

        if ($rowDate < $startDateInput || $rowDate > $endDateInput) {
            continue;
        }
    }

    $formName = isset($row[$mainFormIdx]) && $row[$mainFormIdx] !== '' ? $row[$mainFormIdx] : 'Unknown';
    $campaignName = isset($row[$mainCampIdx]) && $row[$mainCampIdx] !== '' ? $row[$mainCampIdx] : 'Unknown';
    $adsetName = isset($row[$mainAdsetIdx]) && $row[$mainAdsetIdx] !== '' ? $row[$mainAdsetIdx] : 'Unknown';
    $adName = isset($row[$mainAdNameIdx]) && $row[$mainAdNameIdx] !== '' ? $row[$mainAdNameIdx] : 'Unknown';

    // Build unique option lists
    $formOptions[$formName] = true;
    $campaignOptions[$campaignName] = true;
    $adsetOptions[$adsetName] = true;
    $adNameOptions[$adName] = true;

    // Apply dimension filters
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

    // Form Summary
    if (!isset($formsSummary[$formName])) {
        $formsSummary[$formName] = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'mislead' => 0];
    }
    $formsSummary[$formName]['total']++;

    // Campaign Summary
    if (!isset($campaignsSummary[$campaignName])) {
        $campaignsSummary[$campaignName] = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'mislead' => 0];
    }
    $campaignsSummary[$campaignName]['total']++;

    // Adset Summary
    if (!isset($adsetSummary[$adsetName])) {
        $adsetSummary[$adsetName] = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'mislead' => 0];
    }
    $adsetSummary[$adsetName]['total']++;

    // Ad Name Summary
    if (!isset($adNameSummary[$adName])) {
        $adNameSummary[$adName] = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'mislead' => 0];
    }
    $adNameSummary[$adName]['total']++;

    $validityType = classify_validity(isset($row[$mainStatusIdx]) ? $row[$mainStatusIdx] : '');

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

// Combined stats (Lead List + Website + Inbound + WhatsApp + Print media)
$combinedTotalLeads = $totalLeads
    + $websiteStats['total']
    + $inboundStats['total']
    + $whatsAppStats['total']
    + $printMediaStats['total'];

$combinedValidCount = $validCount
    + $websiteStats['valid']
    + $inboundStats['valid']
    + $whatsAppStats['valid']
    + $printMediaStats['valid'];

$combinedInvalidCount = $invalidCount
    + $websiteStats['invalid']
    + $inboundStats['invalid']
    + $whatsAppStats['invalid']
    + $printMediaStats['invalid'];

$combinedMisleadCount = $misleadCount
    + $websiteStats['mislead']
    + $inboundStats['mislead']
    + $whatsAppStats['mislead']
    + $printMediaStats['mislead'];

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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
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

        .metric-valid {
            border-top: 3px solid #22c55e;
        }

        .metric-invalid {
            border-top: 3px solid #ef4444;
        }

        .metric-mislead {
            border-top: 3px solid #f97316;
        }

        .metric-total {
            border-top: 3px solid #3b82f6;
        }

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
            color: #ffffff !important;
            vertical-align: middle;
        }

        .dt-buttons .btn {
            margin-right: 4px;
        }

        .g-3,
        .gy-3 {
            --bs-gutter-y: 0rem;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(236, 254, 255, 0.95), rgba(238, 242, 255, 0.95));
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loader-container {
            text-align: center;
        }

        .loader-spinner {
            height: 15px;
            aspect-ratio: 4;
            --_g: no-repeat radial-gradient(farthest-side, #3b82f6 90%, transparent);
            background:
                var(--_g) left,
                var(--_g) right;
            background-size: 25% 100%;
            display: grid;
            margin: 0 auto 24px;
        }

        .loader-spinner::before,
        .loader-spinner::after {
            content: "";
            height: inherit;
            aspect-ratio: 1;
            grid-area: 1/1;
            margin: auto;
            border-radius: 50%;
            transform-origin: -100% 50%;
            background: #3b82f6;
            animation: l49 1s infinite linear;
        }

        .loader-spinner::after {
            transform-origin: 200% 50%;
            --s: -1;
            background: #0ea5e9;
            animation-delay: -.5s;
        }

        @keyframes l49 {

            58%,
            100% {
                transform: rotate(calc(var(--s, 1) * 1turn));
            }
        }

        .loader-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .loader-subtext {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 400;
        }

        .loader-dots {
            display: inline-block;
            width: 20px;
            text-align: left;
        }

        .loader-dots::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }

        @keyframes dots {

            0%,
            20% {
                content: '.';
            }

            40% {
                content: '..';
            }

            60%,
            100% {
                content: '...';
            }
        }

        /* Traffic Analytics */
        .traffic-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .traffic-summary-card {
            position: relative;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 20px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .traffic-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.1);
        }

        .traffic-summary-card .traffic-card-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .traffic-summary-card .traffic-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
        }

        .traffic-summary-card .traffic-card-caption {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .traffic-summary-card .traffic-card-icon {
            position: absolute;
            top: 14px;
            right: 16px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
        }

        .traffic-city-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .traffic-city-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.1);
        }

        .traffic-city-header {
            padding: 12px 16px;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .traffic-city-body {
            padding: 16px;
        }

        .traffic-city-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 0.9rem;
        }

        .traffic-city-metric .label {
            color: #6b7280;
        }

        .traffic-city-metric .value {
            font-weight: 700;
            color: #0f172a;
        }

        .traffic-city-leads-btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            border-radius: 10px;
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
            font-weight: 600;
            font-size: 0.875rem;
            text-align: center;
            border: none;
            cursor: default;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loader-container">
            <div class="loader-spinner"></div>
            <!-- <div class="loader-text">Loading Dashboard<span class="loader-dots"></span></div>
        <div class="loader-subtext">Fetching data and processing filters</div> -->
        </div>
    </div>

    <div class="container-fluid py-4">

        <!-- <div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.history.back();">
            <i class="bi bi-arrow-left"></i> Back
        </button></div> -->

        <div class="filter-card">
            <form id="filterForm" class="row g-3 align-items-end" method="get">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <input type="hidden" name="start_date" id="hidden_start_date"
                        value="<?php echo htmlspecialchars($startDateInput); ?>">
                    <input type="hidden" name="end_date" id="hidden_end_date"
                        value="<?php echo htmlspecialchars($endDateInput); ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius: 999px 0 0 999px;">
                            <i class="bi bi-calendar-event text-secondary"></i>
                        </span>
                        <input type="text" id="dateRangePicker" class="form-control border-start-0 ps-0"
                            style="border-radius: 0 999px 999px 0; cursor: pointer; background-color: #fff;" readonly
                            value="<?php echo ($startDateInput && $endDateInput) ? date('M d, Y', strtotime($startDateInput)) . ' - ' . date('M d, Y', strtotime($endDateInput)) : 'Select Date Range'; ?>">
                    </div>
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
                    <!-- <div class="metric-caption">
                        LI: <?php echo number_format($totalLeads); ?>
                        &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['total']); ?>
                        &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['total']); ?>
                        &nbsp;•&nbsp; WP: <?php echo number_format($whatsAppStats['total']); ?>
                        &nbsp;•&nbsp; PM: <?php echo number_format($printMediaStats['total']); ?>
                    </div> -->
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card metric-valid">
                    <div class="metric-icon bg-success-subtle">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="metric-label">Valid</div>
                    <div class="metric-value"><?php echo number_format($combinedValidCount); ?></div>
                    <!-- <div class="metric-caption">
                        LI: <?php echo number_format($validCount); ?>
                        &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['valid']); ?>
                        &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['valid']); ?>
                        &nbsp;•&nbsp; WP: <?php echo number_format($whatsAppStats['valid']); ?>
                        &nbsp;•&nbsp; PM: <?php echo number_format($printMediaStats['valid']); ?>
                    </div> -->
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card metric-invalid">
                    <div class="metric-icon bg-danger-subtle">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="metric-label">Invalid</div>
                    <div class="metric-value"><?php echo number_format($combinedInvalidCount); ?></div>
                    <!-- <div class="metric-caption">
                        LI: <?php echo number_format($invalidCount); ?>
                        &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['invalid']); ?>
                        &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['invalid']); ?>
                        &nbsp;•&nbsp; WP: <?php echo number_format($whatsAppStats['invalid']); ?>
                        &nbsp;•&nbsp; PM: <?php echo number_format($printMediaStats['invalid']); ?>
                    </div> -->
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card metric-mislead">
                    <div class="metric-icon bg-warning-subtle">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="metric-label">Mislead</div>
                    <div class="metric-value"><?php echo number_format($combinedMisleadCount); ?></div>
                    <!-- <div class="metric-caption">
                        LI: <?php echo number_format($misleadCount); ?>
                        &nbsp;•&nbsp; WL: <?php echo number_format($websiteStats['mislead']); ?>
                        &nbsp;•&nbsp; IL: <?php echo number_format($inboundStats['mislead']); ?>
                        &nbsp;•&nbsp; WP: <?php echo number_format($whatsAppStats['mislead']); ?>
                        &nbsp;•&nbsp; PM: <?php echo number_format($printMediaStats['mislead']); ?>
                    </div> -->
                </div>
            </div>
        </div>




        <!-- API Data Cards: Sagehill CC & BYT -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="metric-card" style="border-top: 3px solid #8b5cf6;">
                    <div class="metric-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div class="metric-label">Total Spend</div>
                    <div class="metric-value">₹<?php echo number_format($grandTotSpend); ?></div>
                    <div class="metric-caption">
                        CC: ₹<?php echo number_format($sagehillCCData['tot_spend']); ?>
                        &nbsp;•&nbsp; BYT: ₹<?php echo number_format($sagehillBYTData['tot_spend']); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card" style="border-top: 3px solid #06b6d4;">
                    <div class="metric-icon" style="background: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="metric-label">Total Leads</div>
                    <div class="metric-value"><?php echo number_format($grandTotLeads); ?></div>
                    <div class="metric-caption">
                        CC: <?php echo number_format($sagehillCCData['tot_leads']); ?>
                        &nbsp;•&nbsp; BYT: <?php echo number_format($sagehillBYTData['tot_leads']); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card" style="border-top: 3px solid #3b82f6;">
                    <div class="metric-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="bi bi-facebook"></i>
                    </div>
                    <div class="metric-label">Facebook Spend</div>
                    <div class="metric-value">₹<?php echo number_format($grandTotFBSpend); ?></div>
                    <div class="metric-caption">
                        CC: ₹<?php echo number_format($sagehillCCData['tot_fb_spend']); ?>
                        &nbsp;•&nbsp; BYT: ₹<?php echo number_format($sagehillBYTData['tot_fb_spend']); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card" style="border-top: 3px solid #10b981;">
                    <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-google"></i>
                    </div>
                    <div class="metric-label">Google Spend</div>
                    <div class="metric-value">₹<?php echo number_format($grandTotGSpend); ?></div>
                    <div class="metric-caption">
                        CC: ₹<?php echo number_format($sagehillCCData['tot_g_spend']); ?>
                        &nbsp;•&nbsp; BYT: ₹<?php echo number_format($sagehillBYTData['tot_g_spend']); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $traffic = $trafficAnalytics['traffic'] ?? [];
        $topCities = $trafficAnalytics['topCities'] ?? [];
        $cityColors = ['Chennai' => '#3b82f6', 'Coimbatore' => '#22c55e', 'Bengaluru' => '#8b5cf6', '' => '#ef4444', 'Other' => '#ef4444', 'Hyderabad' => '#f97316'];
        ?>

        <!-- Traffic Analytics -->
        <div class="mb-4">
            <h5 class="traffic-section-title">Traffic Analytics</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="traffic-summary-card" style="border-top: 3px solid #3b82f6;">
                        <div class="traffic-card-icon" style="background: #60a5fa; color: #fff;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="traffic-card-label">Total Sessions</div>
                        <div class="traffic-card-value">
                            <?php echo number_format((int) ($traffic['totalSessions'] ?? 0)); ?>
                        </div>
                        <div class="traffic-card-caption">Traffic Volume</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="traffic-summary-card" style="border-top: 3px solid #22c55e;">
                        <div class="traffic-card-icon" style="background: #4ade80; color: #fff;">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div class="traffic-card-label">Engagement Rate</div>
                        <div class="traffic-card-value">
                            <?php echo number_format((float) ($traffic['engagementRate'] ?? 0), 2); ?>%
                        </div>
                        <div class="traffic-card-caption">Interaction Quality</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="traffic-summary-card" style="border-top: 3px solid #8b5cf6;">
                        <div class="traffic-card-icon" style="background: #a78bfa; color: #fff;">
                            <i class="bi bi-stopwatch-fill"></i>
                        </div>
                        <div class="traffic-card-label">Avg. Duration</div>
                        <div class="traffic-card-value">
                            <?php echo htmlspecialchars($traffic['avgEngagementTimePerSession_fmt'] ?? '00:00'); ?>
                        </div>
                        <div class="traffic-card-caption">Time per Session</div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($topCities as $cityKey => $cityData): ?>
                    <?php
                    $cityName = $cityKey === '' ? 'Other' : $cityKey;
                    $color = $cityColors[$cityKey] ?? $cityColors[$cityName] ?? '#64748b';
                    $sessions = (int) ($cityData['activeUsers'] ?? 0);
                    $engRate = (float) ($cityData['engagementRate'] ?? 0);
                    $avgSec = (float) ($cityData['avgEngagementPerActiveUser_sec'] ?? 0);
                    $leads = (int) ($cityData['LP_Lead'] ?? 0);
                    ?>
                    <div class="col-md col-lg">
                        <div class="traffic-city-card">
                            <div class="traffic-city-header"
                                style="background: <?php echo $color; ?>; border-radius: 12px 12px 0 0;">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?php echo htmlspecialchars($cityName); ?>
                            </div>
                            <div class="traffic-city-body">
                                <div class="traffic-city-metric">
                                    <span class="label">Sessions</span>
                                    <span class="value"><?php echo number_format($sessions); ?></span>
                                </div>
                                <div class="traffic-city-metric">
                                    <span class="label">Eng. Rate</span>
                                    <span class="value"><?php echo number_format($engRate, 2); ?>%</span>
                                </div>
                                <div class="traffic-city-metric">
                                    <span class="label">Avg. Time</span>
                                    <span class="value"><?php echo traffic_format_sec($avgSec); ?></span>
                                </div>
                                <button type="button" class="traffic-city-leads-btn" disabled><?php echo $leads; ?>
                                    Lead<?php echo $leads !== 1 ? 's' : ''; ?></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Comparison Row: Lead Summary & Campaign Performance -->
        <div class="row g-4 pt-4">
            <div class="col-xl-5 col-lg-12">
                <!-- Lead Summary by Sheet Table -->
                <div class="table-card h-100 mt-0">
                    <div class="table-card-header">
                        <div class="table-title">
                            <span class="table-title-icon"><i class="bi bi-table"></i></span>
                            <span>Lead Summary by Sheet</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="table-lead-summary" class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary text-uppercase fw-semibold"
                                        style="font-size: 0.75rem; letter-spacing: 0.05em;">Sheet</th>
                                    <th class="text-end text-secondary text-uppercase fw-semibold"
                                        style="font-size: 0.75rem; letter-spacing: 0.05em;">Valid</th>
                                    <th class="text-end text-secondary text-uppercase fw-semibold"
                                        style="font-size: 0.75rem; letter-spacing: 0.05em;">Invalid</th>
                                    <th class="text-end text-secondary text-uppercase fw-semibold"
                                        style="font-size: 0.75rem; letter-spacing: 0.05em;">Mislead</th>
                                    <th class="text-end text-secondary text-uppercase fw-semibold"
                                        style="font-size: 0.75rem; letter-spacing: 0.05em;">Total</th>
                                    <th class="text-end text-secondary text-uppercase fw-semibold"
                                        style="font-size: 0.75rem; letter-spacing: 0.05em;">Lead % (Valid)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rowsList = [
                                    ['name' => 'Meta', 'valid' => $validCount, 'invalid' => $invalidCount, 'mislead' => $misleadCount, 'total' => $totalLeads],
                                    ['name' => 'Website', 'valid' => $websiteStats['valid'], 'invalid' => $websiteStats['invalid'], 'mislead' => $websiteStats['mislead'], 'total' => $websiteStats['total']],
                                    ['name' => 'Inbound', 'valid' => $inboundStats['valid'], 'invalid' => $inboundStats['invalid'], 'mislead' => $inboundStats['mislead'], 'total' => $inboundStats['total']],
                                    ['name' => 'WhatsApp', 'valid' => $whatsAppStats['valid'], 'invalid' => $whatsAppStats['invalid'], 'mislead' => $whatsAppStats['mislead'], 'total' => $whatsAppStats['total']],
                                    ['name' => 'Print media', 'valid' => $printMediaStats['valid'], 'invalid' => $printMediaStats['invalid'], 'mislead' => $printMediaStats['mislead'], 'total' => $printMediaStats['total']],
                                ];

                                foreach ($rowsList as $r) {
                                    $pct = $r['total'] > 0 ? ($r['valid'] / $r['total']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['name']); ?></td>
                                        <td class="text-end"><?php echo number_format($r['valid']); ?></td>
                                        <td class="text-end"><?php echo number_format($r['invalid']); ?></td>
                                        <td class="text-end"><?php echo number_format($r['mislead']); ?></td>
                                        <td class="text-end fw-semibold"><?php echo number_format($r['total']); ?></td>
                                        <td class="text-end"><?php echo number_format($pct, 2); ?>%</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end"><?php echo number_format($combinedValidCount); ?></td>
                                    <td class="text-end"><?php echo number_format($combinedInvalidCount); ?></td>
                                    <td class="text-end"><?php echo number_format($combinedMisleadCount); ?></td>
                                    <td class="text-end"><?php echo number_format($combinedTotalLeads); ?></td>
                                    <td class="text-end">
                                        <?php
                                        $combPct = $combinedTotalLeads > 0 ? ($combinedValidCount / $combinedTotalLeads) * 100 : 0;
                                        echo number_format($combPct, 2);
                                        ?>%
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-7 col-lg-12">
                <!-- API Data Comparison Table -->
                <div class="table-card h-100 mt-0">
                    <div class="table-card-header">
                        <div class="table-title">
                            <span class="table-title-icon"><i class="bi bi-bar-chart-line"></i></span>
                            <span>Campaign Performance Summary</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="table-api-summary" class="table table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th class="text-end">Total Spend</th>
                                    <th class="text-end">Total Leads</th>
                                    <th class="text-end">FB Spend</th>
                                    <th class="text-end">FB Leads</th>
                                    <th class="text-end">Google Spend</th>
                                    <th class="text-end">Google Leads</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Sagehill CC</strong></td>
                                    <td class="text-end">₹<?php echo number_format($sagehillCCData['tot_spend']); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($sagehillCCData['tot_leads']); ?></td>
                                    <td class="text-end">₹<?php echo number_format($sagehillCCData['tot_fb_spend']); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($sagehillCCData['tot_fb_leads']); ?>
                                    </td>
                                    <td class="text-end">₹<?php echo number_format($sagehillCCData['tot_g_spend']); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($sagehillCCData['tot_g_leads']); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Sagehill BYT</strong></td>
                                    <td class="text-end">₹<?php echo number_format($sagehillBYTData['tot_spend']); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($sagehillBYTData['tot_leads']); ?>
                                    </td>
                                    <td class="text-end">₹<?php echo number_format($sagehillBYTData['tot_fb_spend']); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($sagehillBYTData['tot_fb_leads']); ?>
                                    </td>
                                    <td class="text-end">₹<?php echo number_format($sagehillBYTData['tot_g_spend']); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($sagehillBYTData['tot_g_leads']); ?>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td>Total</td>
                                    <td class="text-end">₹<?php echo number_format($grandTotSpend); ?></td>
                                    <td class="text-end"><?php echo number_format($grandTotLeads); ?></td>
                                    <td class="text-end">₹<?php echo number_format($grandTotFBSpend); ?></td>
                                    <td class="text-end"><?php echo number_format($grandTotFBLeads); ?></td>
                                    <td class="text-end">₹<?php echo number_format($grandTotGSpend); ?></td>
                                    <td class="text-end"><?php echo number_format($grandTotGLeads); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
                        $grandTotal += $summary['total'];
                        $grandValid += $summary['valid'];
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
                    $cGrandTotal = 0;
                    $cGrandValid = 0;
                    $cGrandInvalid = 0;
                    $cGrandMislead = 0;

                    foreach ($campaignsSummary as $campaignName => $summary) {
                        $cGrandTotal += $summary['total'];
                        $cGrandValid += $summary['valid'];
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
                    $aGrandTotal = 0;
                    $aGrandValid = 0;
                    $aGrandInvalid = 0;
                    $aGrandMislead = 0;

                    foreach ($adsetSummary as $adsetNameRow => $summary) {
                        $aGrandTotal += $summary['total'];
                        $aGrandValid += $summary['valid'];
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
                    $adGrandTotal = 0;
                    $adGrandValid = 0;
                    $adGrandInvalid = 0;
                    $adGrandMislead = 0;

                    foreach ($adNameSummary as $adNameRow => $summary) {
                        $adGrandTotal += $summary['total'];
                        $adGrandValid += $summary['valid'];
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

    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        function getButtons(title) {
            return [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: 'Excel', title: title },
                { extend: 'pdfHtml5', className: 'btn btn-danger btn-sm', text: 'PDF', title: title },
                { extend: 'copyHtml5', className: 'btn btn-secondary btn-sm', text: 'Copy', title: title }
            ];
        }

        function initSummaryTable(tableId) {
            if (!$.fn.DataTable.isDataTable('#' + tableId)) {
                var title = 'Sagehill Leads Summary';
                $('#' + tableId).DataTable({
                    dom: 'Bfrtip',
                    buttons: getButtons(title),
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    ordering: true,
                    responsive: true
                });
            }
        }

        // Loading overlay functions
        function showLoader() {
            $('#loadingOverlay').addClass('active');
        }

        function hideLoader() {
            $('#loadingOverlay').removeClass('active');
        }

        // Show loader on form submit
        $('#filterForm').on('submit', function () {
            showLoader();
        });

        // Show loader on reset button click
        $('a[href="sagehil-lead-dash.php"]').on('click', function () {
            showLoader();
        });

        // Show loader on initial page load if date parameters exist (indicating a filter is active)
        $(document).ready(function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('start_date') || urlParams.has('end_date') ||
                urlParams.has('form') || urlParams.has('campaign') ||
                urlParams.has('adset') || urlParams.has('ad_name')) {
                showLoader();
            }
        });

        $(function () {
            // Initialize Date Range Picker
            var start = moment('<?php echo $startDateInput ?: date("Y-m-d", strtotime("-29 days")); ?>');
            var end = moment('<?php echo $endDateInput ?: date("Y-m-d"); ?>');

            function cb(start, end) {
                $('#dateRangePicker').val(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
                $('#hidden_start_date').val(start.format('YYYY-MM-DD'));
                $('#hidden_end_date').val(end.format('YYYY-MM-DD'));
            }

            $('#dateRangePicker').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This Week': [moment().startOf('isoWeek'), moment().endOf('isoWeek')],
                    'All Time': [moment('2020-01-01'), moment()]
                },
                opens: 'right',
                alwaysShowCalendars: true,
                autoUpdateInput: false,
                locale: {
                    format: 'MMM D, YYYY',
                    cancelLabel: 'Clear'
                }
            }, function (start, end) {
                cb(start, end);
                $('#filterForm').submit();
            });

            // If we have values, set them initially
            if ('<?php echo $startDateInput; ?>' && '<?php echo $endDateInput; ?>') {
                // cb(start, end); // Don't auto-fill on load if php value sets the input value attribute, but helps if needed
            } else {
                // default view
                // cb(start, end);
            }

            // Handle cancel/clear if needed
            $('#dateRangePicker').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
                $('#hidden_start_date').val('');
                $('#hidden_end_date').val('');
            });

            $('#dateRangePicker').on('apply.daterangepicker', function (ev, picker) {
                cb(picker.startDate, picker.endDate);
                $('#filterForm').submit();
            });


            // Initialize all DataTables
            initSummaryTable('table-lead-summary');
            initSummaryTable('table-form-summary');
            initSummaryTable('table-campaign-summary');
            initSummaryTable('table-adset-summary');
            initSummaryTable('table-adname-summary');
            initSummaryTable('table-api-summary');

            // Hide loader after everything is initialized
            // Add a small delay to ensure smooth transition
            setTimeout(function () {
                hideLoader();
            }, 300);
        });
    </script>

</body>

</html>