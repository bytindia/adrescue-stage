<?php
// Standalone Accounts & Budget cards page for Accounts module (no navigation/includes)
session_start();
require_once __DIR__ . '/../db.php';
Auth();
// Helper: fetch remote URL (same style as index-acc.php)
if (!function_exists('curl_get_contents1')) {
    function curl_get_contents1($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}

// Helper: Indian money format (copied from index-acc.php)
if (!function_exists('moneyFormatIndia')) {
    function moneyFormatIndia($num)
    {
        if ($num < 0) {
            return '-' . moneyFormatIndia(abs($num));
        }
        $explrestunits = "";
        if (strlen($num) > 3) {
            $lastthree = substr($num, strlen($num) - 3, strlen($num));
            $restunits = substr($num, 0, strlen($num) - 3);
            $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
            $expunit = str_split($restunits, 2);
            for ($i = 0; $i < sizeof($expunit); $i++) {
                if ($i == 0) {
                    $explrestunits .= (int)$expunit[$i] . ",";
                } else {
                    $explrestunits .= $expunit[$i] . ",";
                }
            }
            $thecash = $explrestunits . $lastthree;
        } else {
            $thecash = $num;
        }
        return $thecash;
    }
}

// Helper: Parse serialized line_items and compute totals (same as invoice-list-filter.php)
if (!function_exists('computeTotalsFromLineItems')) {
    function computeTotalsFromLineItems($serialized)
    {
        $totalSubtotal = 0; // excl GST
        $totalGrand    = 0; // incl GST
        if (!empty($serialized)) {
            $data = @unserialize($serialized);
            if (is_array($data)) {
                foreach ($data as $invoice) {
                    if (isset($invoice['totals'])) {
                        $totalSubtotal += floatval($invoice['totals']['subtotal'] ?? 0);
                        $totalGrand    += floatval($invoice['totals']['grand_total'] ?? 0);
                    }
                }
            }
        }
        return [$totalSubtotal, $totalGrand];
    }
}

// ---- Fetch API data (same APIs as index-acc.php) ----
$budgetJson = curl_get_contents1("https://stage.adrescue.in/budget-api-json.php");
$budgetData = json_decode($budgetJson, true);
$byt = isset($budgetData['byt']) ? $budgetData['byt'] : [
    'bud'       => 0,
    'spend'     => 0,
    'bud_rcd'   => 0,
    'bal'       => 0,
];

// Accounts – Outstanding & Receivable
$soaJson  = curl_get_contents1("https://stage.adrescue.in/soa-api.php");
$soaData  = json_decode($soaJson, true);
$outstandingRows = isset($soaData['outstanding']) && is_array($soaData['outstanding']) ? $soaData['outstanding'] : [];
$receiptsRows = isset($soaData['receipts']) && is_array($soaData['receipts']) ? $soaData['receipts'] : [];

$totalOutstanding = 0;
$totalReceivable  = 0;
foreach ($outstandingRows as $row) {
    $out = isset($row['outstanding']) ? (int)str_replace([',', ' '], '', $row['outstanding']) : 0;
    $rec = isset($row['receivable']) ? (int)str_replace([',', ' '], '', $row['receivable']) : 0;
    if ($out > 0) {
        $totalOutstanding += $out;
    }
    if ($rec > 0) {
        $totalReceivable += $rec;
    }
}

// Process receipts to find last payment date per client (for modal)
// Create a mapping that handles client name variations
$lastPaymentByClient = [];
foreach ($receiptsRows as $receipt) {
    if (empty($receipt['date']) || empty($receipt['client'])) continue;
    
    $client = trim($receipt['client']);
    $dateStr = $receipt['date'];
    
    // Parse date (d-m-Y format)
    $dt = DateTime::createFromFormat('d-m-Y', $dateStr);
    if (!$dt) continue;
    
    // Normalize client name (remove common suffixes like " - PI", extra spaces)
    $clientNormalized = strtoupper(trim(preg_replace('/\s*-\s*PI\s*$/i', '', $client)));
    $clientNormalized = preg_replace('/\s+/', ' ', $clientNormalized);
    
    // Store both full name and normalized key
    if (!isset($lastPaymentByClient[$clientNormalized]) || $dt > $lastPaymentByClient[$clientNormalized]) {
        $lastPaymentByClient[$clientNormalized] = $dt;
    }
    
    // Also store by first word for matching (e.g., "RWD" matches "RWD Waterfront")
    $firstWord = explode(' ', $clientNormalized)[0];
    if (strlen($firstWord) > 2 && (!isset($lastPaymentByClient[$firstWord]) || $dt > $lastPaymentByClient[$firstWord])) {
        $lastPaymentByClient[$firstWord] = $dt;
    }
}

// Calculate days ago for each client
$lastPaymentDaysAgo = [];
$today = new DateTime();
foreach ($lastPaymentByClient as $clientKey => $lastDate) {
    $diff = $today->diff($lastDate);
    $lastPaymentDaysAgo[$clientKey] = $diff->days;
}

// Calculate Payment Received from SOA receipts (this month)
$receiptsRows = isset($soaData['receipts']) && is_array($soaData['receipts']) ? $soaData['receipts'] : [];
$totalPaymentReceivedSOA = 0;
$currentYear = date('Y');
$currentMonth = date('m');

foreach ($receiptsRows as $receipt) {
    if (empty($receipt['date'])) continue;
    
    // Parse date in d-m-Y format (e.g., "25-04-2025")
    $dt = DateTime::createFromFormat('d-m-Y', $receipt['date']);
    if ($dt && $dt->format('Y') == $currentYear && $dt->format('m') == $currentMonth) {
        $amount = isset($receipt['amount']) ? (float)str_replace([',', ' '], '', $receipt['amount']) : 0;
        $totalPaymentReceivedSOA += $amount;
    }
}

// Spend totals (from budget summary)
$totalBudget        = isset($byt['bud']) ? (float)$byt['bud'] : 0;
$totalSpend         = isset($byt['spend']) ? (float)$byt['spend'] : 0;
$totalSpendReceived = isset($byt['bud_rcd']) ? (float)$byt['bud_rcd'] : 0;
$budgetRemaining    = isset($byt['bal']) ? (float)$byt['bal'] : max(0, $totalBudget - $totalSpend);

// Accounts view: total spend not received = budget - spend received (never negative)
$totalSpendNotReceived = max(0, $totalBudget - $totalSpendReceived);

// ---- Invoice & Proforma totals (This Month, Tot(+GST)) ----
// Current month boundaries (Y-m-d format for comparison)
$currentMonthStartYmd = date('Y-m-01');
$currentMonthEndYmd   = date('Y-m-t');

function parseInvDate($dateStr) {
    if (empty($dateStr)) return null;
    
    // Try d-m-Y format first (e.g., "18-12-2025")
    $dt = DateTime::createFromFormat('d-m-Y', $dateStr);
    if ($dt) return $dt;
    
    // Try Y-m-d format
    $dt = DateTime::createFromFormat('Y-m-d', $dateStr);
    if ($dt) return $dt;
    
    // Try MySQL datetime format (Y-m-d H:i:s)
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
    if ($dt) return $dt;
    
    // Try MySQL date format with time (Y-m-d H:i:s)
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', substr($dateStr, 0, 19));
    if ($dt) return $dt;
    
    // Try other common formats
    $dt = DateTime::createFromFormat('d/m/Y', $dateStr);
    if ($dt) return $dt;
    
    return null;
}

function isInCurrentMonth($dateStr) {
    global $currentYear, $currentMonth;
    
    $dt = parseInvDate($dateStr);
    if (!$dt) return false;
    
    return ($dt->format('Y') == $currentYear && $dt->format('m') == $currentMonth);
}

function sumInvoiceGrandTotalByType($conn, $type)
{
    global $currentMonthStartYmd, $currentMonthEndYmd;
    
    $where = ["approved='yes'"];
    
    // Filter by type
    if ($type === 'pi') {
        $where[] = "inv_ty='pi'";
    } elseif ($type === 'invoice') {
        $where[] = "(inv_ty!='pi' OR inv_ty IS NULL OR inv_ty='')";
    }
    
    $whereSql = implode(' AND ', $where);
    
    $totalGrand = 0;
    // Fetch both inv_dt and created date - use inv_dt if available, else fallback to created
    $sql = mysqli_query($conn, "SELECT inv_dt, created, line_items FROM invoice2 WHERE {$whereSql}");
    
    if ($sql) {
        while ($row = mysqli_fetch_assoc($sql)) {
            // Use inv_dt if available, otherwise use created date
            $dateToCheck = !empty($row['inv_dt']) ? $row['inv_dt'] : $row['created'];
            
            // Filter by invoice sent date in PHP (handles various date formats)
            if (isInCurrentMonth($dateToCheck)) {
                if (!empty($row['line_items'])) {
                    list(, $grand) = computeTotalsFromLineItems($row['line_items']);
                    $totalGrand += $grand;
                }
            }
        }
    } else {
        // Log error if query fails (for debugging)
        error_log("Invoice query failed: " . mysqli_error($conn));
    }
    return $totalGrand;
}

$totalInvoiceThisMonth         = sumInvoiceGrandTotalByType($conn, 'invoice');
$totalProformaInvoiceThisMonth = sumInvoiceGrandTotalByType($conn, 'pi');

// ---- Fetch FinSheet data (Payable & Receivable Summary) ----
$finesheetData = [
    'payable_end_month' => 0,
    'cash_on_hand' => 0,
    'meta_payable' => 0,
    'g_cc_payables' => 0,
    'old_receivable' => 0,
    'current_receivable' => 0,
    'pi_receivables' => 0,
];

$finesheetQuery = mysqli_query($conn, "SELECT finesheet_data FROM finesheet WHERE id = 1 ORDER BY id DESC LIMIT 1");
if ($finesheetQuery && mysqli_num_rows($finesheetQuery) > 0) {
    $finesheetRow = mysqli_fetch_assoc($finesheetQuery);
    if (!empty($finesheetRow['finesheet_data'])) {
        $finesheetJson = json_decode($finesheetRow['finesheet_data'], true);
        if (is_array($finesheetJson)) {
            // Extract values, handling spaces and converting to float
            $finesheetData['payable_end_month'] = isset($finesheetJson['payable_end_month']) ? (float)str_replace([' ', ','], '', $finesheetJson['payable_end_month']) : 0;
            $finesheetData['cash_on_hand'] = isset($finesheetJson['cash_on_hand']) ? (float)str_replace([' ', ','], '', $finesheetJson['cash_on_hand']) : 0;
            $finesheetData['meta_payable'] = isset($finesheetJson['meta_payable']) ? (float)str_replace([' ', ','], '', $finesheetJson['meta_payable']) : 0;
            $finesheetData['g_cc_payables'] = isset($finesheetJson['g_cc_payables']) ? (float)str_replace([' ', ','], '', $finesheetJson['g_cc_payables']) : 0;
            $finesheetData['old_receivable'] = isset($finesheetJson['old_receivable']) ? (float)str_replace([' ', ','], '', $finesheetJson['old_receivable']) : 0;
            $finesheetData['current_receivable'] = isset($finesheetJson['current_receivable']) ? (float)str_replace([' ', ','], '', $finesheetJson['current_receivable']) : 0;
            $finesheetData['pi_receivables'] = isset($finesheetJson['pi_receivables']) ? (float)str_replace([' ', ','], '', $finesheetJson['pi_receivables']) : 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/images/favicon.ico" type="image/ico" />
    <title>Accounts &amp; Budget Overview</title>

    <!-- Bootstrap & jQuery from CDN -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <!-- DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            <?php if(!isset($_GET['menu']) && $_GET['menu']!='hide') { ?> background-color: #213d5a; <?php } ?>
            font-family: "Helvetica Neue", Arial, sans-serif;
            padding: 0 50px;
            margin: 0;
        }
        .container-fluid {
            padding: 0;
            max-width: 100%;
        }
        .section-wrapper {
            margin-bottom: 30px;
        }
        .section-title {
            text-align: center;
            font-weight: 600;
            font-size: 18px;
            <?php if(!isset($_GET['menu']) && $_GET['menu']!='hide') { ?> color: #ffffff; <?php } ?>
            margin: 15px 0;
            padding: 0;
        }
        hr {
            <?php if(!isset($_GET['menu']) && $_GET['menu']!='hide') { ?>
            border: 0;
            height: 1px;
            background-image: -webkit-linear-gradient(left, rgba(255,255,255,0.1), rgba(255,255,255,0.5), rgba(255,255,255,0.1));
            <?php } ?>
            margin: 15px 0;
        }
        /* grid: 6 cards per row on large screens */
        .card-grid {
            display: flex;
            flex-wrap: wrap;
            margin: -8px;
        }
        .card-grid-item {
            padding: 8px;
            width: 16.6667%; /* 6 per row */
        }
        @media (max-width: 1199px) {
            .card-grid-item { width: 25%; } /* 4 per row on medium */
        }
        @media (max-width: 991px) {
            .card-grid-item { width: 33.3333%; } /* 3 per row on tablet */
        }
        @media (max-width: 767px) {
            body { padding: 5px 24px; }
            .section-title { font-size: 16px; margin: 12px 0; }
            .card-grid { margin: -6px; }
            .card-grid-item { width: 50%; padding: 6px; } /* 2 per row on small mobile */
            .stat-card { padding: 12px; min-height: 90px; }
            .stat-title { font-size: 12px; margin-bottom: 6px; }
            .stat-value { font-size: 18px; }
            .stat-caption { font-size: 11px; margin-top: 4px; }
            .header-icons {
                position: relative;
                top: 0;
                right: 0;
                justify-content: center;
                margin-top: 10px;
            }
            .page-header {
                text-align: center;
            }
        }
        @media (max-width: 480px) {
            body { padding: 5px 24px; }
            .section-wrapper { margin-bottom: 20px; }
            .section-title { font-size: 25px; margin: 10px 0; }
            hr { margin: 10px 0; }
            .card-grid { margin: -4px; }
            .card-grid-item { width: 100%; padding: 10px; } /* 1 per row on mobile */
            .stat-card { padding: 14px; min-height: 85px; }
            .stat-title { font-size: 13px; margin-bottom: 8px; }
            .stat-value { font-size: 20px; }
            .stat-caption { font-size: 11px; margin-top: 5px; }
            .header-icons {
                position: relative;
                top: 0;
                right: 0;
                justify-content: center;
                margin-top: 10px;
            }
            .page-header {
                text-align: center;
            }
            .page-header img {
                width: 120px;
            }
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            padding: 12px 14px;
            border-left: 4px solid #0984e3;
            min-height: 80px;
            width: 100%;
        }
        .stat-title {
            font-size: 14px;
            font-weight: 600;
            color: #636e72;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .stat-title .view-details-icon {
            color: #0984e3;
            margin-left: 6px;
            font-size: 12px;
            vertical-align: middle;
        }
        .stat-value {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            line-height: 2;
            word-break: break-word;
        }
        .stat-caption {
            margin-top: 2px;
            font-size: 11px;
            color: #95a5a6;
            line-height: 1.2;
        }
        .stat-primary   { color: #0984e3; }
        .stat-success   { color: #00b894; }
        .stat-warning   { color: #fbc531; }
        .stat-danger    { color: #e17055; }
        .stat-purple    { color: #6c5ce7; }

        /* All account cards should show values in #0984e3 */
        .accounts-section .stat-value {
            color: #0984e3 !important;
        }

        /* Budget section also uses #0984e3 */
        .budget-section .stat-value {
            color: #0984e3 !important;
        }

        /* individual border colors */
        .border-outstanding   { border-left-color: #0984e3; }
        .border-receivable    { border-left-color: #00b894; }
        .border-spend-rec     { border-left-color: #6c5ce7; }
        .border-spend-not-rec { border-left-color: #e17055; }
        .border-payment-soa   { border-left-color: #00b894; }
        .border-payable-eom   { border-left-color: #e74c3c; }
        .border-cash-bank     { border-left-color: #27ae60; }
        .border-meta-payable  { border-left-color: #3498db; }
        .border-google-cc     { border-left-color: #f39c12; }
        .border-old-receivable { border-left-color: #9b59b6; }
        .border-curr-receivable { border-left-color: #16a085; }
        .border-pi-receivables { border-left-color: #e67e22; }
        .border-budget        { border-left-color: #00c6ff; }
        .border-spend         { border-left-color: #00b894; }
        .border-bud-rec       { border-left-color: #fd9644; }
        .border-bud-rem       { border-left-color: #fbc531; }
        
        /* Header with icons */
        .page-header {
           position: relative;
    padding: 15px 0;
    margin-bottom: 20px;
    border-bottom: none;
    margin: 0 !important;
        }
        .header-icons {
            position: absolute;
            top: 15px;
            right: 0;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .header-icon {
            color: #ffffff;
            font-size: 20px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.3s;
        }
        .header-icon:hover {
            opacity: 0.7;
            color: #ffffff;
            text-decoration: none;
        }
        .header-icon i {
            font-size: 20px;
        }
        
        /* List view styles */
        .card-grid.list-view {
            flex-direction: column;
        }
        .card-grid.list-view .card-grid-item {
            width: 100% !important;
            padding: 8px;
        }
        .card-grid.list-view .stat-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            min-height: 60px;
            padding: 15px 20px;
        }
        .card-grid.list-view .stat-title {
            flex: 0 0 200px;
            margin-bottom: 0;
            font-size: 14px;
        }
        .card-grid.list-view .stat-value {
            flex: 1;
            text-align: right;
            margin: 0;
            line-height: 1.2;
        }
        .card-grid.list-view .stat-caption {
            flex: 0 0 250px;
            text-align: right;
            margin-top: 0;
            margin-left: 15px;
        }
        @media (max-width: 767px) {
            .card-grid.list-view .stat-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-grid.list-view .stat-title {
                flex: 1 1 100%;
                margin-bottom: 8px;
            }
            .card-grid.list-view .stat-value {
                flex: 1 1 100%;
                text-align: left;
                margin-bottom: 4px;
            }
            .card-grid.list-view .stat-caption {
                flex: 1 1 100%;
                text-align: left;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header with Icons -->
         <?php if(!isset($_GET['menu']) && $_GET['menu']!='hide') { ?>
        <div class="page-header">
            <center><img src="https://stage.adrescue.in/images/adRes-w.png" width="160px" alt="AdRescue Logo"></center>
            <div class="header-icons">
                <a href="/loading.php?pg=index-acc.php" class="header-icon" title="Home">
                    <i class="fa fa-home"></i>
                </a>
                <a href="/loading.php?pg=https://stage.adrescue.in/accounts/" class="header-icon" title="Refresh">
                    <i class="fa fa-refresh"></i>
                </a>
                <a href="#" class="header-icon" id="viewToggle" title="Toggle List/Grid View" onclick="toggleView(); return false;">
                    <i class="fa fa-list" id="viewToggleIcon"></i>
                </a>
            </div>
        </div>
        <?php } ?>
        
        <!-- Accounts Section -->
        <div class="section-wrapper">
            <hr>
            <h3 class="section-title">Accounts Overview</h3>
            <hr>
            <div class="card-grid accounts-section" id="accountsGrid">
            <!-- 1. Cash in Bank -->
            <div class="card-grid-item">
                <div class="stat-card border-cash-bank">
                    <div class="stat-title">Cash (in Bank)</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['cash_on_hand']); ?>
                    </p>
                    <div class="stat-caption">Current balance</div>
                </div>
            </div>
            <!-- 2. Meta Payable -->
            <div class="card-grid-item">
                <div class="stat-card border-meta-payable">
                    <div class="stat-title">Meta (Payable)</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['meta_payable']); ?>
                    </p>
                    <div class="stat-caption">Outstanding</div>
                </div>
            </div>
            <!-- 3. Google CC Payable -->
            <div class="card-grid-item">
                <div class="stat-card border-google-cc">
                    <div class="stat-title">Google CC (Payable)</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['g_cc_payables']); ?>
                    </p>
                    <div class="stat-caption">Outstanding</div>
                </div>
            </div>
            <!-- 4. Payable EOM -->
            <div class="card-grid-item">
                <div class="stat-card border-payable-eom">
                    <div class="stat-title">Payable (EOM)</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['payable_end_month']); ?>
                    </p>
                    <div class="stat-caption">End of month</div>
                </div>
            </div>
            <!-- 5. Total Outstanding -->
            <div class="card-grid-item">
                <div class="stat-card border-outstanding" style="cursor: pointer;" onclick="showOutstandingModal()">
                    <div class="stat-title">Total Outstanding <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value stat-primary">
                        ₹<?php echo moneyFormatIndia((int)$totalOutstanding); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <!-- 6. Total Receivable -->
            <div class="card-grid-item">
                <div class="stat-card border-receivable" style="cursor: pointer;" onclick="showReceivableModal()">
                    <div class="stat-title">Total Receivable <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalReceivable); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <!-- 7. Current Receivable -->
            <div class="card-grid-item">
                <div class="stat-card border-curr-receivable">
                    <div class="stat-title">Curr. Receivable</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['current_receivable']); ?>
                    </p>
                    <div class="stat-caption">Current</div>
                </div>
            </div>
            <!-- 8. PI Receivables -->
            <div class="card-grid-item">
                <div class="stat-card border-pi-receivables">
                    <div class="stat-title">PI Receivables</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['pi_receivables']); ?>
                    </p>
                    <div class="stat-caption">Proforma invoices</div>
                </div>
            </div>
            <!-- 9. Old Receivable -->
            <div class="card-grid-item">
                <div class="stat-card border-old-receivable">
                    <div class="stat-title">Old Receivable</div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$finesheetData['old_receivable']); ?>
                    </p>
                    <div class="stat-caption">Pending</div>
                </div>
            </div>
            <!-- 10. Payment Received : SOA -->
            <div class="card-grid-item">
                <div class="stat-card border-payment-soa" style="cursor: pointer;" onclick="showPaymentReceivedModal()">
                    <div class="stat-title">Payment Received : SOA <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalPaymentReceivedSOA); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <!-- 11. Total Spend Received -->
            <div class="card-grid-item">
                <div class="stat-card border-spend-rec" style="cursor: pointer;" onclick="showSpendReceivedModal()">
                    <div class="stat-title">Total Spend Received <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalSpendReceived); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <!-- 12. Total Spend Not Received -->
            <div class="card-grid-item">
                <div class="stat-card border-spend-not-rec" style="cursor: pointer;" onclick="showSpendNotReceivedModal()">
                    <div class="stat-title">Total Spend Not Received <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalSpendNotReceived); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <!-- 13. Total Invoice Sent -->
            <div class="card-grid-item">
                <div class="stat-card border-outstanding" style="cursor: pointer;" onclick="showInvoiceModal()">
                    <div class="stat-title">Total Invoice Sent <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalInvoiceThisMonth); ?>
                    </p>
                    <div class="stat-caption">Invoices sent this month (with GST)</div>
                </div>
            </div>
            <!-- 14. Proforma Invoice Sent -->
            <div class="card-grid-item">
                <div class="stat-card border-outstanding" style="cursor: pointer;" onclick="showProformaModal()">
                    <div class="stat-title">Proforma Invoice Sent <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalProformaInvoiceThisMonth); ?>
                    </p>
                    <div class="stat-caption">Proforma invoices this month (with GST)</div>
                </div>
            </div>
        </div>
        </div>

        <!-- Budget Section -->
        <div class="section-wrapper">
            
            <hr>
            <h3 class="section-title">Budget Overview</h3>
            <hr>
            <div class="card-grid budget-section" id="budgetGrid">
            <div class="card-grid-item">
                <div class="stat-card border-budget" style="cursor: pointer;" onclick="showBudgetModal()">
                    <div class="stat-title">Total Budget <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalBudget); ?>
                    </p>
                    <div class="stat-caption">This month (BYT CC)</div>
                </div>
            </div>
            <div class="card-grid-item">
                <div class="stat-card border-spend" style="cursor: pointer;" onclick="showSpendModal()">
                    <div class="stat-title">Total Spend <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalSpend); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <div class="card-grid-item">
                <div class="stat-card border-bud-rec" style="cursor: pointer;" onclick="showSpendReceivedModal()">
                    <div class="stat-title">Spend Received <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$totalSpendReceived); ?>
                    </p>
                    <div class="stat-caption">This month</div>
                </div>
            </div>
            <div class="card-grid-item">
                <div class="stat-card border-bud-rem" style="cursor: pointer;" onclick="showBudgetRemainingModal()">
                    <div class="stat-title">Budget Remaining <i class="fa fa-external-link view-details-icon"></i></div>
                    <p class="stat-value">
                        ₹<?php echo moneyFormatIndia((int)$budgetRemaining); ?>
                    </p>
                    <div class="stat-caption">Current balance</div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Outstanding Details Modal -->
    <div class="modal fade" id="outstandingModal" tabindex="-1" role="dialog" aria-labelledby="outstandingModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="outstandingModalLabel">Outstanding Details</h4>
                </div>
                <div class="modal-body" id="outstandingModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Sent Details Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog" aria-labelledby="invoiceModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="invoiceModalLabel">Invoice Sent Details</h4>
                </div>
                <div class="modal-body" id="invoiceModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Proforma Invoice Sent Details Modal -->
    <div class="modal fade" id="proformaModal" tabindex="-1" role="dialog" aria-labelledby="proformaModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="proformaModalLabel">Proforma Invoice Sent Details</h4>
                </div>
                <div class="modal-body" id="proformaModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Received Details Modal -->
    <div class="modal fade" id="paymentReceivedModal" tabindex="-1" role="dialog" aria-labelledby="paymentReceivedModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="paymentReceivedModalLabel">Payment Received Details</h4>
                </div>
                <div class="modal-body" id="paymentReceivedModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receivable Details Modal -->
    <div class="modal fade" id="receivableModal" tabindex="-1" role="dialog" aria-labelledby="receivableModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="receivableModalLabel">Receivable Details</h4>
                </div>
                <div class="modal-body" id="receivableModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Spend Received Details Modal -->
    <div class="modal fade" id="spendReceivedModal" tabindex="-1" role="dialog" aria-labelledby="spendReceivedModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="spendReceivedModalLabel">Spend Received Details</h4>
                </div>
                <div class="modal-body" id="spendReceivedModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Spend Not Received Details Modal -->
    <div class="modal fade" id="spendNotReceivedModal" tabindex="-1" role="dialog" aria-labelledby="spendNotReceivedModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="spendNotReceivedModalLabel">Spend Not Received Details</h4>
                </div>
                <div class="modal-body" id="spendNotReceivedModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Details Modal -->
    <div class="modal fade" id="budgetModal" tabindex="-1" role="dialog" aria-labelledby="budgetModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="budgetModalLabel">Budget Details</h4>
                </div>
                <div class="modal-body" id="budgetModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Spend Details Modal -->
    <div class="modal fade" id="spendModal" tabindex="-1" role="dialog" aria-labelledby="spendModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="spendModalLabel">Total Spend Details</h4>
                </div>
                <div class="modal-body" id="spendModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Remaining Details Modal -->
    <div class="modal fade" id="budgetRemainingModal" tabindex="-1" role="dialog" aria-labelledby="budgetRemainingModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="budgetRemainingModalLabel">Budget Remaining Details</h4>
                </div>
                <div class="modal-body" id="budgetRemainingModalBody">
                    <div class="text-center" style="padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to show Outstanding Modal and load data via AJAX
        function showOutstandingModal() {
            var modal = $('#outstandingModal');
            var modalBody = $('#outstandingModalBody');
            
            // Reset modal body
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            
            // Show modal
            modal.modal('show');
            
            // Load data via AJAX
            $.ajax({
                url: 'outstanding-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    // Execute any scripts in the loaded content
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Invoice Modal and load data via AJAX
        function showInvoiceModal() {
            var modal = $('#invoiceModal');
            var modalBody = $('#invoiceModalBody');
            
            // Reset modal body
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            
            // Show modal
            modal.modal('show');
            
            // Load data via AJAX
            $.ajax({
                url: 'invoice-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    // Execute any scripts in the loaded content
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Proforma Modal and load data via AJAX
        function showProformaModal() {
            var modal = $('#proformaModal');
            var modalBody = $('#proformaModalBody');
            
            // Reset modal body
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            
            // Show modal
            modal.modal('show');
            
            // Load data via AJAX
            $.ajax({
                url: 'proforma-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    // Execute any scripts in the loaded content
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Payment Received Modal and load data via AJAX
        function showPaymentReceivedModal() {
            var modal = $('#paymentReceivedModal');
            var modalBody = $('#paymentReceivedModalBody');
            
            // Reset modal body
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            
            // Show modal
            modal.modal('show');
            
            // Load data via AJAX
            $.ajax({
                url: 'payment-received-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    // Execute any scripts in the loaded content
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Receivable Modal and load data via AJAX
        function showReceivableModal() {
            var modal = $('#receivableModal');
            var modalBody = $('#receivableModalBody');
            
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            modal.modal('show');
            
            $.ajax({
                url: 'receivable-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Spend Received Modal and load data via AJAX
        function showSpendReceivedModal() {
            var modal = $('#spendReceivedModal');
            var modalBody = $('#spendReceivedModalBody');
            
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            modal.modal('show');
            
            $.ajax({
                url: 'spend-received-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Spend Not Received Modal and load data via AJAX
        function showSpendNotReceivedModal() {
            var modal = $('#spendNotReceivedModal');
            var modalBody = $('#spendNotReceivedModalBody');
            
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            modal.modal('show');
            
            $.ajax({
                url: 'spend-not-received-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Budget Modal and load data via AJAX
        function showBudgetModal() {
            var modal = $('#budgetModal');
            var modalBody = $('#budgetModalBody');
            
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            modal.modal('show');
            
            $.ajax({
                url: 'budget-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Spend Modal and load data via AJAX
        function showSpendModal() {
            var modal = $('#spendModal');
            var modalBody = $('#spendModalBody');
            
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            modal.modal('show');
            
            $.ajax({
                url: 'spend-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Function to show Budget Remaining Modal and load data via AJAX
        function showBudgetRemainingModal() {
            var modal = $('#budgetRemainingModal');
            var modalBody = $('#budgetRemainingModalBody');
            
            modalBody.html('<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>');
            modal.modal('show');
            
            $.ajax({
                url: 'budget-remaining-popup.php',
                type: 'GET',
                success: function(data) {
                    modalBody.html(data);
                    modalBody.find('script').each(function() {
                        var script = document.createElement('script');
                        script.text = $(this).html();
                        document.body.appendChild(script).parentNode.removeChild(script);
                    });
                },
                error: function() {
                    modalBody.html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                }
            });
        }

        // Destroy DataTables when modals are closed to prevent conflicts
        $('#outstandingModal, #invoiceModal, #proformaModal, #paymentReceivedModal, #receivableModal, #spendReceivedModal, #spendNotReceivedModal, #budgetModal, #spendModal, #budgetRemainingModal').on('hidden.bs.modal', function() {
            var table = $(this).find('table').DataTable();
            if (table) {
                table.destroy();
            }
        });

        // Toggle between list and grid view
        function toggleView() {
            var accountsGrid = $('#accountsGrid');
            var budgetGrid = $('#budgetGrid');
            var toggleIcon = $('#viewToggleIcon');
            
            if (accountsGrid.hasClass('list-view')) {
                // Switch to grid view
                accountsGrid.removeClass('list-view');
                budgetGrid.removeClass('list-view');
                toggleIcon.removeClass('fa-th').addClass('fa-list');
                localStorage.setItem('viewMode', 'grid');
            } else {
                // Switch to list view
                accountsGrid.addClass('list-view');
                budgetGrid.addClass('list-view');
                toggleIcon.removeClass('fa-list').addClass('fa-th');
                localStorage.setItem('viewMode', 'list');
            }
        }

        // Load saved view mode on page load
        $(document).ready(function() {
            var savedViewMode = localStorage.getItem('viewMode');
            if (savedViewMode === 'list') {
                $('#accountsGrid').addClass('list-view');
                $('#budgetGrid').addClass('list-view');
                $('#viewToggleIcon').removeClass('fa-list').addClass('fa-th');
            }
        });
    </script>

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        .whatsapp-link {
            text-decoration: none;
            display: inline-block;
        }
        .whatsapp-link:hover {
            opacity: 0.8;
        }
        #outstandingTable {
            font-size: 14px;
        }
        #outstandingTable thead th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        #outstandingTable tbody tr:hover {
            background-color: #f5f5f5;
        }
        .stat-card[style*="cursor: pointer"]:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }
    </style>
</body>
</html>


