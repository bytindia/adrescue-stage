<?php session_start(); 
//error_reporting(E_ALL); ini_set('display_errors', '1');

// Reuse the same DB and Google Sheets setup as other SOA scripts
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-tabs.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';

// Config
$uId = 2; // user id used in other scripts
$spreadsheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk';
$outstanding = read_sheet($uId, $spreadsheetId, $sheetTab='Outstanding');
//d($outstanding);exit;
// Tabs to ignore

$ignoreTabs = array(
    'Outstanding',
    'Meta Invoices 2025',
    'Receipts-All'
);

// Optional: allow overriding via GET when testing
if(isset($_GET['sheet_id']) && $_GET['sheet_id']!='') {
    $spreadsheetId = $_GET['sheet_id'];
}

// Fetch tabs
$tabs = read_tabs($uId, $tbl_id, $spreadsheetId);
if(isset($tabs['error'])) {
    echo 'Error fetching tabs: '.$tabs['error'];
    exit;
}

// Prepare table (schema provided separately). We truncate for full refresh.
// If you need incremental, replace TRUNCATE with INSERT ... ON DUPLICATE KEY UPDATE keyed by invoice_no+sheet_tab.
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS soa_data (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sheet_tab VARCHAR(255) NOT NULL,
    invoice_no VARCHAR(128) DEFAULT NULL,
    invoice_date VARCHAR(64) DEFAULT NULL,
    client VARCHAR(255) DEFAULT NULL,
    invoice_description TEXT DEFAULT NULL,
    taxable_value DECIMAL(18,2) DEFAULT NULL,
    cgst DECIMAL(18,2) DEFAULT NULL,
    sgst DECIMAL(18,2) DEFAULT NULL,
    igst DECIMAL(18,2) DEFAULT NULL,
    invoice_value DECIMAL(18,2) DEFAULT NULL,
    tds_pct DECIMAL(7,4) DEFAULT NULL,
    tds_amount DECIMAL(18,2) DEFAULT NULL,
    receivable DECIMAL(18,2) DEFAULT NULL,
    receipt DECIMAL(18,2) DEFAULT NULL,
    balance DECIMAL(18,2) DEFAULT NULL,
    balance_ex_tds DECIMAL(18,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sheet_tab (sheet_tab),
    KEY idx_invoice_no (invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "TRUNCATE TABLE soa_data");

// Helper to coerce numeric values safely
function toNum($v) {
    if($v === null) return null;
    if(is_numeric(str_replace(array(',', ' '), '', $v))) {
        $v2 = str_replace(array(',', ' '), '', $v);
        return $v2 === '' ? null : (float)$v2;
    }
    return null;
}

// Iterate tabs
foreach($tabs as $tab) {
    if(!is_array($tab)) continue;
    $sheetName = isset($tab['sheet_name']) ? $tab['sheet_name'] : '';
    
    if ($sheetName === '' || strpos($sheetName, '- PI') !== false) {
        continue;
    }

    // Skip ignored tabs
    if(in_array($sheetName, $ignoreTabs, true)) continue;

    // Read sheet rows
    $raw = read_sheet($uId, $spreadsheetId, $sheetName);
    if(!isset($raw['data']) || !is_array($raw['data'])) continue;
    $rows = $raw['data'];

    // Expect header with the provided columns; find header row
    // Columns: Invoice No, Invoice Date, Client, Invoice Description, Taxable Value, CGST, SGST, IGST, Invoice Value, TDS %, TDS Amount, Receivable, Receipt, Balance, Balance (Ex TDS)
    $headerIndex = -1;
    foreach($rows as $i => $r) {
        if(!is_array($r)) continue;
        $first = isset($r[0]) ? trim($r[0]) : '';
        if(stripos($first, 'Invoice No') !== false) { $headerIndex = $i; break; }
    }
    if($headerIndex === -1) {
        // If header not found, assume first row is header
        $headerIndex = 0;
    }

    for($i = $headerIndex + 1; $i < count($rows); $i++) {
        $r = $rows[$i];
        if(!is_array($r)) continue;

        // Normalize to at least 15 columns (some scripts used 14; here we include Balance (Ex TDS) as 15th)
        $r = array_slice(array_pad($r, 15, ''), 0, 15);

        $invoice_no = $conn->real_escape_string(trim($r[0]));
        $invoice_date = $conn->real_escape_string(trim($r[1]));
        $client = $conn->real_escape_string(trim($r[2]));
        $invoice_description = $conn->real_escape_string(trim($r[3]));

        $taxable_value = toNum($r[4]);
        $cgst = toNum($r[5]);
        $sgst = toNum($r[6]);
        $igst = toNum($r[7]);
        $invoice_value = toNum($r[8]);
        // TDS % may include % sign
        $tds_pct_raw = trim((string)$r[9]);
        $tds_pct_num = str_replace('%', '', $tds_pct_raw);
        $tds_pct = toNum($tds_pct_num);
        $tds_amount = toNum($r[10]);
        $receivable = toNum($r[11]);
        $receipt = toNum($r[12]);
        $balance = toNum($r[13]);
        $balance_ex_tds = toNum($r[14]);

        // Skip entirely empty rows
        $allEmpty = ($invoice_no==='' && $invoice_date==='' && $client==='' && $invoice_description==='');
        $allEmpty = $allEmpty && ($taxable_value===null && $cgst===null && $sgst===null && $igst===null && $invoice_value===null && $tds_pct===null && $tds_amount===null && $receivable===null && $receipt===null && $balance===null && $balance_ex_tds===null);
        if($allEmpty) continue;

        $sql = "INSERT INTO soa_data
            (sheet_tab, invoice_no, invoice_date, client, invoice_description, taxable_value, cgst, sgst, igst, invoice_value, tds_pct, tds_amount, receivable, receipt, balance, balance_ex_tds)
            VALUES
            ('".$conn->real_escape_string($sheetName)."',
             '".$invoice_no."',
             '".$invoice_date."',
             '".$client."',
             '".$invoice_description."',
             ".($taxable_value===null?'NULL':$taxable_value).",
             ".($cgst===null?'NULL':$cgst).",
             ".($sgst===null?'NULL':$sgst).",
             ".($igst===null?'NULL':$igst).",
             ".($invoice_value===null?'NULL':$invoice_value).",
             ".($tds_pct===null?'NULL':$tds_pct).",
             ".($tds_amount===null?'NULL':$tds_amount).",
             ".($receivable===null?'NULL':$receivable).",
             ".($receipt===null?'NULL':$receipt).",
             ".($balance===null?'NULL':$balance).",
             ".($balance_ex_tds===null?'NULL':$balance_ex_tds)."
            )";

        mysqli_query($conn, $sql);
    }
} 
//exit;
$_SESSION['suc'] = 'SOA data synced successfully';	
echo '✅ SOA data synced successfully';
echo "<script>window.location = 'invoice-outstanding.php';</script>";

