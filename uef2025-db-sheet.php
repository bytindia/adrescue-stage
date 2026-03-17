<?php
date_default_timezone_set('Asia/Kolkata');
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
include $dirPath . 'google-sheets-api/insert-row.php';
include $dirPath.'google-sheets-api/update-row-bulk.php';


$uId =2;
$tbl_id = 2;

// Log file setup
$logFile = __DIR__ . '/uef2025-DB-log.txt';

// Logging function
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Start logging
writeLog("===========================================", $logFile);
writeLog("UEF2025 Cron Job Started", $logFile);
writeLog("===========================================", $logFile);

// Fetch user tokens for Meta and Google APIs
$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id = 2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$accessToken = $row['access_token'];
$g_ref_tok = $row['g_refresh_token'];
$g_mcc = $row['g_mcc']; // Manager Customer ID (MCC) for Google Ads

// Audience IDs
// Meta Audiences
$metaAbandonedCartAud = '120237190935700071';
$metaPurchasedAud = '120237191020510071';

// Google Audiences
// userListId = The audience list ID (these are your audience IDs)
$googleAbandonedCartAud = '9251226957'; // Audience List ID for Abandoned Cart
$googlePurchasedAud = '9251227851';     // Audience List ID for Purchased
// Note: These audience lists must exist in the Customer ID account specified below



// Google Sheet config (used in production mode append)
$spreadsheetId = '1FgMShgsBN1FziCmJBi1mVSP7WwPNP5M_av9x1WwlmTg';
$sheetTab = 'Leads';


// ============================================
// PRODUCTION MODE - Fetch from MySQL table
// ============================================
/*
$query = "
SELECT *
FROM uef2025
WHERE COALESCE(upd,'') != 'Y'
  AND created_at BETWEEN NOW() - INTERVAL 40 MINUTE AND NOW() - INTERVAL 20 MINUTE
";*/
$query = "SELECT * FROM uef2025";
writeLog("Executing database query...", $logFile);
$result = $conn->query($query);

if($result->num_rows > 0){
   // writeLog("Found " . $result->num_rows . " records in database", $logFile);
    $allRows = []; // collect all rows for Google Sheets
    $paidUsers = []; // users with trans_status = 'paid'
    $pendingUsers = []; // users with trans_status = 'Pending'
    
    
    while ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $email = $row['email'];
        $phone = str_replace('+91', 'p:+91', str_replace(' ', '', $row['phone']));
        $iam = $row['iam'];
        $pan = $row['pan'];
        $company = $row['company'];
        $designation = $row['designation'];
        $num_tickets = $row['num_tickets'];
        $amount = $row['amount'];
        $trans_status = $row['trans_status'];
        $source = $row['source'];
        $medium = $row['medium'];
        $campaign = $row['campaign'];
        $lead_id = $row['lead_id'];
        $address = $row['address'];
        $verify_code = $row['verify_code'];
        $formatted_date = date('d-m-Y, h:i a', strtotime($row['created_at']));
        if(strtolower(trim($trans_status)) === 'captured'){ $trans_status = 'paid'; }
        $term = $row['term'];

        if ($term != '') {
            $ad_preview = "https://stage.adrescue.in/loading.php?pg=ad-preview.php?ad_id={$term}"; 
            $campaign = '=HYPERLINK("'.$ad_preview.'","'.$campaign.'")';
        }

        $allRows[] = [
            $name, $email, $phone, $iam, $pan, $company,
            $designation, $address, $verify_code, $num_tickets, $amount, $trans_status,
            $source, $medium, $campaign,
            $lead_id, $formatted_date, ''
        ];
        
        // Prepare user data for audience upload (clean phone number)
        $cleanPhone = preg_replace('/\D+/', '', $row['phone']);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }
        
        $userData = [
            'email' => $email,
            'phone' => $cleanPhone
        ];
        
        // Split users based on trans_status
        if (strtolower(trim($trans_status)) === 'paid') {
            $paidUsers[] = $userData;
        } elseif (strtolower(trim($trans_status)) === 'pending') {
            $pendingUsers[] = $userData;
        }
        $tbl_id = intval($row['id']); // safety
        $conn->query("UPDATE uef2025 SET upd = 'Y' WHERE id = $tbl_id");
    }

    // ✅ Append to Google Sheets only if there are rows
    if (count($allRows) > 0) {
        //writeLog("Appending " . count($allRows) . " rows to Google Sheets...", $logFile);
        //append_to_sheet($uId, $tbl_id, $allRows, $spreadsheetId, $sheetTab);
        update_row_bulk($uId, $tbl_id, $allRows, $spreadsheetId, $sheetTab, 'A', $rowNo=2);
       // writeLog("Google Sheets: Data appended successfully", $logFile);
    }
    

    
} else {
    writeLog("No new records found in the specified time range", $logFile);
}