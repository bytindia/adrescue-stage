<?php
//error_reporting(E_ALL); ini_set('display_errors', '1');
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-tabs.php';
$uId = $tbl_id2= 2; 
$spreadsheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk';
$sheetRows = read_tabs($uId, $tbl_id, $spreadsheetId);
d($sheetRows);

// Step 2: Save to DB
if (isset($sheetRows['error'])) {
    echo "Error: " . $sheetRows['error'];
} else {
    mysqli_query($conn, "TRUNCATE TABLE soa_sheet");
    foreach ($sheetRows as $row) {
        $tbl_id = intval($row['tbl_id']);
        $sheet_id = $conn->real_escape_string($row['sheet_id']);
        $sheet_name = $conn->real_escape_string($row['sheet_name']);
        $created = $conn->real_escape_string($row['created']);

        $insertQuery = "
            INSERT INTO soa_sheet (tbl_id, sheet_id, sheet_name, created)
            VALUES ('$tbl_id', '$sheet_id', '$sheet_name', '$created')
        ";

        mysqli_query($conn, $insertQuery);
    }

    echo "✅ All sheet rows inserted into `soa_sheet`.";
}
