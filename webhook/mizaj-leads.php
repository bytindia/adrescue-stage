<?php
date_default_timezone_set('Asia/Kolkata');

// Define path
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

// Include required files
include $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
include $dirPath . 'google-sheets-api/insert-row.php';

// Setup variables
$uId = 2;
$tbl_id = 2;
$spreadsheetId = '1RGmysPAOxsqonTge8BGY33Vmec2_KapjS0LEbEzzmWA';
$sheetTab = 'LP Leads';

// Receive form data
$lead = $_POST;

if (count($lead) > 0) {
    $created_at = date('Y-m-d H:i:s');

    // Format phone number
    $ph = str_replace(' ', '', $lead['Mobile']);
    $ph = str_replace('+91', 'p:+91', $ph);

    // UTM data
    $src = isset($lead['UTM_Source']) ? $lead['UTM_Source'] : '';
    $med = isset($lead['UTM_Medium']) ? $lead['UTM_Medium'] : '';
    $camp = isset($lead['UTM_Campaign']) ? $lead['UTM_Campaign'] : '';

    // Insert into MySQL
    $cirSql = "INSERT INTO mizaj_leads (name, email, phone, message, src, med, camp) 
               VALUES (
                   '" . mysqli_real_escape_string($conn, $lead['Name']) . "',  
                   '" . mysqli_real_escape_string($conn, $lead['Email']) . "', 
                   '" . mysqli_real_escape_string($conn, $ph) . "',  
                   '" . mysqli_real_escape_string($conn, $lead['Message']) . "', 
                   '" . mysqli_real_escape_string($conn, $src) . "', 
                   '" . mysqli_real_escape_string($conn, $med) . "', 
                   '" . mysqli_real_escape_string($conn, $camp) . "'
               );";

    mysqli_query($conn, $cirSql) or die(mysqli_error($conn));

    // Insert into Google Sheet
    $leadV = [[
        mysqli_real_escape_string($conn, $lead['Name']),
        $lead['Email'],
        $ph,
        $lead['Message'],
        $src,
        $med,
        $camp,
        date('d-m-Y, h:i a'),
    ]];

    append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);

    // Return success response
    $data = array('code' => 200, 'response' => "success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
exit;
