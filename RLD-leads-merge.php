<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Existing dependencies and configuration
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';
require($root_path.'PHPExcel-1.8/Classes/PHPExcel.php');

$uId = 2;

// Helper to normalize phone: strip non-digits, keep last 10 digits
function normalize_phone_last10($value) {
    $digits = preg_replace('/[^0-9]/', '', (string)$value);
    if ($digits === null) { $digits = ''; }
    if (strlen($digits) < 10) { return ''; }
    return substr($digits, -10);
}

// Helper to process rows: clean column C (index 2), dedupe by normalized phone
function clean_and_dedupe_by_phone($rows) {
    $seen = [];
    $result = [];
    foreach ($rows as $row) {
        // Ensure row has at least 3 columns
        if (!is_array($row)) { continue; }
        for ($i = 0; $i < 3; $i++) { if (!isset($row[$i])) { $row[$i] = ''; } }
        $normalized = normalize_phone_last10($row[2]);
        if ($normalized === '') { continue; }
        $row[2] = $normalized;
        if (isset($seen[$normalized])) { continue; }
        $seen[$normalized] = true;
        $result[] = $row;
    }
    return $result;
}

// Sheet IDs and tabs
$metaSheetId = '1UPhJC4CI0WEFQ6LTJDEGp0xKZFiROXw5nNWRnKpeyyM';
$metaTab = 'Sheet1';
$googleSheetId = '18ih98q3EMgJuO8DMg_jQVMKYnjeGIeHRlMwmDAQkm54';
$googleTab = 'Rld-LP-Leads';

// Read Meta
$metaRaw = read_sheet($uId, $metaSheetId, $metaTab);
$metaData = isset($metaRaw['data']) && is_array($metaRaw['data']) ? $metaRaw['data'] : [];
$metaHeader = !empty($metaData) ? $metaData[0] : [];
$metaBody = !empty($metaData) ? array_slice($metaData, 1) : [];
$metaClean = clean_and_dedupe_by_phone($metaBody);

// Read Google
$googleRaw = read_sheet($uId, $googleSheetId, $googleTab);
$googleData = isset($googleRaw['data']) && is_array($googleRaw['data']) ? $googleRaw['data'] : [];
$googleHeader = !empty($googleData) ? $googleData[0] : [];
$googleBody = !empty($googleData) ? array_slice($googleData, 1) : [];
$googleClean = clean_and_dedupe_by_phone($googleBody);

// Build Excel with two tabs: Meta and Google
$objPHPExcel = new PHPExcel();

// Meta sheet (first)
$sheetMeta = $objPHPExcel->getActiveSheet();
$sheetMeta->setTitle('Meta');
$r = 1;
// Write header
if (!empty($metaHeader)) {
    $c = 0;
    foreach ($metaHeader as $cell) {
        $sheetMeta->setCellValueByColumnAndRow($c, $r, $cell);
        $c++;
    }
    $r++;
}
// Write cleaned rows
foreach ($metaClean as $row) {
    $c = 0;
    foreach ($row as $cell) {
        $sheetMeta->setCellValueByColumnAndRow($c, $r, $cell);
        $c++;
    }
    $r++;
}

// Google sheet
$sheetGoogle = new PHPExcel_Worksheet($objPHPExcel, 'Google');
$objPHPExcel->addSheet($sheetGoogle, 1);
$r = 1;
// Write header
if (!empty($googleHeader)) {
    $c = 0;
    foreach ($googleHeader as $cell) {
        $sheetGoogle->setCellValueByColumnAndRow($c, $r, $cell);
        $c++;
    }
    $r++;
}
// Write cleaned rows
foreach ($googleClean as $row) {
    $c = 0;
    foreach ($row as $cell) {
        $sheetGoogle->setCellValueByColumnAndRow($c, $r, $cell);
        $c++;
    }
    $r++;
}

// Set active to Meta sheet on open
$objPHPExcel->setActiveSheetIndex(0);

// Filename with today's date in d-n-Y (no leading zeros)
$downloadName = 'RLD-Leads ' . date('j-n-Y') . '.xlsx';

// Stream download
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Cache-Control: max-age=0');
if (ob_get_length()) { ob_end_clean(); }
$objWriter->save('php://output');
exit;