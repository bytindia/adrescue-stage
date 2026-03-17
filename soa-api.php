<?php
//error_reporting(E_ALL); ini_set('display_errors', '1');
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';
$uId = $tbl_id2= 2; 
$spreadsheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk';

$outstanding = read_sheet($uId, $spreadsheetId, $sheetTab='Outstanding');
//d($outstanding);exit;
$filteredData = array_slice($outstanding['data'], 2, count($outstanding['data']) - 4);

//$filteredData = array_slice($outstanding['data'], 2);
$final = [];

foreach ($filteredData as $row) {
    //if($row[0] != 'Eden park') {
    $final[] = [
        'client' => isset($row[0]) ? $row[0] : '',
        'outstanding' => isset($row[1]) ? $row[1] : '',
        'receivable' => isset($row[3]) ? $row[3] : '',
        'date' => isset($row[2]) ? $row[2] : '',
        'notes' => isset($row[4]) ? $row[4] : '',
    ];
   // }
}
$receipts = read_sheet($uId, $spreadsheetId, $sheetTab='Receipts-All');
//d($receipts);exit;
unset($receipts['data'][0]); 
$receipts_data = [];

foreach ($receipts['data'] as $row) {
    $rawDate = isset($row[1]) ? trim($row[1]) : '';
    $date = '';

    if (!empty($rawDate)) {
        // Try both dash and slash formats
        $timestamp = strtotime(str_replace('/', '-', $rawDate)); // normalize slashes
        if ($timestamp) {
            $date = date('d-m-Y', $timestamp); // format: dd-mm-yy
        }
    }

    $receipts_data[] = [
        'client' => isset($row[0]) ? $row[0] : '',
        'date' => $date,
        'amount' => isset($row[2]) ? $row[2] : '',
    ];
}

// Optional: remove header row (if not already done)
if (!empty($receipts_data) && $receipts_data[0]['client'] === 'Client Name') {
    array_shift($receipts_data);
}

// Reset numeric keys (optional if you're JSON encoding)
$receipts_data = array_values($receipts_data);


$output = [
    'outstanding' => $final,
    'receipts' => $receipts_data,
];
header('Content-Type: application/json');
$json = json_encode($output, JSON_PRETTY_PRINT);
echo $json;

//d($filteredData);