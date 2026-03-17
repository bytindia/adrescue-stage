<?php
date_default_timezone_set('Asia/Kolkata');
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
include $dirPath . 'google-sheets-api/insert-row.php';

// Load Composer for Google Ads SDK
require_once $dirPath . 'google-ads-v15/vendor/autoload.php';

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Util\V20\ResourceNames;
use Google\Ads\GoogleAds\V20\Common\UserIdentifier;
use Google\Ads\GoogleAds\V20\Common\UserData;
use Google\Ads\GoogleAds\V20\Enums\OfflineUserDataJobTypeEnum\OfflineUserDataJobType;
use Google\Ads\GoogleAds\V20\Services\OfflineUserDataJobOperation;
use Google\Ads\GoogleAds\V20\Services\CreateOfflineUserDataJobRequest;
use Google\Ads\GoogleAds\V20\Services\AddOfflineUserDataJobOperationsRequest;
use Google\Ads\GoogleAds\V20\Services\RunOfflineUserDataJobRequest;

$uId =2;
$tbl_id = 2;

// Log file setup
$logFile = __DIR__ . '/uef2025-cron-log.txt';

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

// Get last 10 mins entries (or use status flag later)
/*$query = "
    SELECT *
    FROM uef2025
    WHERE created_at >= (NOW() - INTERVAL 10 MINUTE)
";
$query = "
    SELECT *
    FROM uef2025
    WHERE created_at >= NOW() - INTERVAL 15 MINUTE
      AND created_at <= NOW() - INTERVAL 5 MINUTE
";*/
// Function to upload users to Meta Custom Audience (with batch chunking)
function uploadToMetaAudience($users, $audienceId, $accessToken, $api_ver, $logFile = null) {
    if (empty($users)) {
        if ($logFile) writeLog("Meta: No users to upload for audience $audienceId", $logFile);
        return;
    }
    
    // Meta API batch limit: 10,000 users per request
    $batchSize = 10000;
    $chunks = array_chunk($users, $batchSize);
    $totalUploaded = 0;
    
    foreach ($chunks as $chunkIndex => $chunk) {
        $hashedLead = [];
        
        foreach ($chunk as $user) {
            $email = trim($user['email']);
            $phone = preg_replace('/\D+/', '', $user['phone']); // only digits for phone
            
            if (empty($email) && empty($phone)) {
                continue;
            }
            
            $hashedEmail = hash('sha256', strtolower($email));
            $hashedPhone = hash('sha256', $phone);
            
            $hashedLead[] = [$hashedEmail, $hashedPhone];
        }
        
        if (empty($hashedLead)) {
            continue;
        }
        
        $payload = [
            'payload' => [
                'schema' => ['EMAIL', 'PHONE'],
                'data' => $hashedLead
            ],
            'access_token' => $accessToken
        ];
        
        $url = "https://graph.facebook.com/{$api_ver}/{$audienceId}/users";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            echo "   ❌ Meta upload error for Audience $audienceId (Batch " . ($chunkIndex + 1) . "): $errorMsg\n";
            if ($logFile) writeLog("Meta ERROR: Audience $audienceId (Batch " . ($chunkIndex + 1) . ") - $errorMsg", $logFile);
            error_log("[❌] Meta upload error for Audience $audienceId (Batch " . ($chunkIndex + 1) . "): " . $errorMsg);
        } else {
            $totalUploaded += count($hashedLead);
            $responseData = json_decode($response, true);
            if (isset($responseData['error'])) {
                $errorMsg = $responseData['error']['message'];
                echo "   ❌ Meta API Error: " . $errorMsg . "\n";
                if ($logFile) writeLog("Meta API ERROR: Audience $audienceId - $errorMsg", $logFile);
            } else {
                echo "   ✅ Meta Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " | HTTP $httpCode | Users: " . count($hashedLead) . "\n";
                if ($logFile) writeLog("Meta SUCCESS: Audience $audienceId | Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " | HTTP $httpCode | Users: " . count($hashedLead), $logFile);
            }
            error_log("[✅] Meta Audience $audienceId | Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " | HTTP $httpCode | Users: " . count($hashedLead));
        }
        
        curl_close($ch);
        
        // Small delay between batches to avoid rate limiting
        if ($chunkIndex < count($chunks) - 1) {
            usleep(500000); // 0.5 second delay
        }
    }
    
    echo "   ✅ Meta Audience $audienceId | Total uploaded: $totalUploaded users\n";
    if ($logFile) writeLog("Meta COMPLETE: Audience $audienceId | Total uploaded: $totalUploaded users", $logFile);
    error_log("[✅] Meta Audience $audienceId | Total uploaded: $totalUploaded users");
}

// Function to upload users to Google Custom Audience (with batch chunking)
function uploadToGoogleAudience($users, $userListId, $customerId, $googleAdsClient, $logFile = null) {
    if (empty($users)) {
        if ($logFile) writeLog("Google: No users to upload for audience $userListId", $logFile);
        return;
    }
    
    try {
        // Google API batch limit: 10,000 operations per batch
        $batchSize = 10000;
        
        // Step 1: Create job
        $offlineUserDataJobServiceClient = $googleAdsClient->getOfflineUserDataJobServiceClient();
        $job = new \Google\Ads\GoogleAds\V20\Resources\OfflineUserDataJob([
            'type' => OfflineUserDataJobType::CUSTOMER_MATCH_USER_LIST,
            'customer_match_user_list_metadata' => new \Google\Ads\GoogleAds\V20\Common\CustomerMatchUserListMetadata([
                'user_list' => ResourceNames::forUserList($customerId, $userListId),
            ])
        ]);
        
        $request = new CreateOfflineUserDataJobRequest([
            'customer_id' => $customerId,
            'job' => $job,
        ]);
        $response = $offlineUserDataJobServiceClient->createOfflineUserDataJob($request);
        $jobResourceName = $response->getResourceName();
        
        // Step 2: Prepare all operations
        $allOperations = [];
        foreach ($users as $user) {
            $email = trim($user['email']);
            $phone = preg_replace('/\D+/', '', $user['phone']); // only digits for phone
            
            if (empty($email) && empty($phone)) {
                continue;
            }
            
            $identifiers = [];
            if (!empty($email)) {
                $identifiers[] = new UserIdentifier(['hashed_email' => hash('sha256', strtolower($email))]);
            }
            if (!empty($phone)) {
                $identifiers[] = new UserIdentifier(['hashed_phone_number' => hash('sha256', $phone)]);
            }
            
            if (!empty($identifiers)) {
                $userData = new UserData(['user_identifiers' => $identifiers]);
                $allOperations[] = new OfflineUserDataJobOperation(['create' => $userData]);
            }
        }
        
        if (empty($allOperations)) {
            return;
        }
        
        // Step 3: Split operations into chunks and upload in batches
        $chunks = array_chunk($allOperations, $batchSize);
        $totalUploaded = 0;
        
        foreach ($chunks as $chunkIndex => $operations) {
            $request = new AddOfflineUserDataJobOperationsRequest([
                'resource_name' => $jobResourceName,
                'operations' => $operations,
            ]);
            $response = $offlineUserDataJobServiceClient->addOfflineUserDataJobOperations($request);
            
            $totalUploaded += count($operations);
            echo "   ✅ Google Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " | Operations: " . count($operations) . "\n";
            if ($logFile) writeLog("Google SUCCESS: Audience $userListId | Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " | Operations: " . count($operations), $logFile);
            error_log("[✅] Google Audience $userListId | Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " | Operations: " . count($operations));
            
            // Small delay between batches to avoid rate limiting
            if ($chunkIndex < count($chunks) - 1) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        // Step 4: Run job (only once after all batches are added)
        $request = new RunOfflineUserDataJobRequest([
            'resource_name' => $jobResourceName,
        ]);
        $response = $offlineUserDataJobServiceClient->runOfflineUserDataJob($request);
        
        echo "   ✅ Google Audience $userListId (Customer $customerId) - Job submitted and running | Total users: $totalUploaded\n";
        if ($logFile) writeLog("Google COMPLETE: Audience $userListId (Customer $customerId) - Job submitted | Total users: $totalUploaded", $logFile);
        error_log("[✅] Google Audience $userListId (Customer $customerId) - Job submitted and running | Total users: $totalUploaded");
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        echo "   ❌ Google upload error for Audience $userListId: " . $errorMsg . "\n";
        if ($logFile) writeLog("Google ERROR: Audience $userListId - $errorMsg", $logFile);
        error_log("[❌] Google upload error for Audience $userListId: " . $errorMsg);
    }
}

// ============================================
// PRODUCTION MODE - Fetch from MySQL table
// ============================================
$query = "
SELECT *
FROM uef2025
WHERE created_at >= NOW() - INTERVAL 20 MINUTE
  AND created_at <= NOW() - INTERVAL 10 MINUTE
";
writeLog("Executing database query...", $logFile);
$result = $conn->query($query);

if($result->num_rows > 0){
    writeLog("Found " . $result->num_rows . " records in database", $logFile);
    $allRows = []; // collect all rows for Google Sheets
    $paidUsers = []; // users with trans_status = 'paid'
    $pendingUsers = []; // users with trans_status = 'Pending'
    
    // Google Ads client setup
    $googleCustomerId = '6725026344'; // Your Google Ads ACCOUNT ID (ad account) where audience lists exist
    
    writeLog("Setting up Google Ads client...", $logFile);
    try {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile()
            ->withRefreshToken($g_ref_tok)
            ->build();

        $clientBuilder = (new GoogleAdsClientBuilder())
            ->fromFile()
            ->withOAuth2Credential($oAuth2Credential);

        // Use MCC login-customer-id if available
        if (!empty($g_mcc)) {
            $formattedMcc = preg_replace('/[^0-9]/', '', $g_mcc);
            $clientBuilder->withLoginCustomerId($formattedMcc);
            writeLog("Google Ads client: Using MCC $formattedMcc", $logFile);
        }

        $googleAdsClient = $clientBuilder->build();
        writeLog("Google Ads client initialized successfully", $logFile);
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        writeLog("Google Ads client ERROR: $errorMsg", $logFile);
        error_log("[❌] Google Ads client error: " . $errorMsg);
        $googleAdsClient = null;
    }

    while ($row = $result->fetch_assoc()) {
        $tbl_id = $row['id'];
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

        $tbl_id = intval($tbl_id); // safety
        $conn->query("UPDATE uef2025 SET upd = 'Y' WHERE id = $tbl_id");
    }

    // ✅ Append to Google Sheets only if there are rows
    if (count($allRows) > 0) {
        writeLog("Appending " . count($allRows) . " rows to Google Sheets...", $logFile);
        //append_to_sheet($uId, $tbl_id, $allRows, $spreadsheetId, $sheetTab);
        writeLog("Google Sheets: Data appended successfully", $logFile);
    }
    
    // Upload to Meta and Google Custom Audiences
    // Paid users -> Purchased Audience
    if (!empty($paidUsers)) {
        writeLog("Processing " . count($paidUsers) . " paid users -> Purchased Audience", $logFile);
        uploadToMetaAudience($paidUsers, $metaPurchasedAud, $accessToken, $api_ver, $logFile);
        if ($googleAdsClient) {
            uploadToGoogleAudience($paidUsers, $googlePurchasedAud, $googleCustomerId, $googleAdsClient, $logFile);
        } else {
            writeLog("Google: Skipping paid users upload (client not initialized)", $logFile);
        }
    } else {
        writeLog("No paid users found", $logFile);
    }
    
    // Pending users -> Abandoned Cart Audience
    if (!empty($pendingUsers)) {
        writeLog("Processing " . count($pendingUsers) . " pending users -> Abandoned Cart Audience", $logFile);
        uploadToMetaAudience($pendingUsers, $metaAbandonedCartAud, $accessToken, $api_ver, $logFile);
        if ($googleAdsClient) {
            uploadToGoogleAudience($pendingUsers, $googleAbandonedCartAud, $googleCustomerId, $googleAdsClient, $logFile);
        } else {
            writeLog("Google: Skipping pending users upload (client not initialized)", $logFile);
        }
    } else {
        writeLog("No pending users found", $logFile);
    }
    
    writeLog("===========================================", $logFile);
    writeLog("UEF2025 Cron Job Completed Successfully", $logFile);
    writeLog("===========================================", $logFile);
    
} else {
    writeLog("No new records found in the specified time range", $logFile);
}


//EXPO


// ============================================
// PRODUCTION MODE - Fetch from MySQL table
// ============================================
$query = "
SELECT *
FROM uef2025_expo
WHERE created_at >= NOW() - INTERVAL 20 MINUTE
  AND created_at <= NOW() - INTERVAL 10 MINUTE
";

$result = $conn->query($query);

if($result->num_rows > 0){

    $allRows = []; // collect all rows for Google Sheets
    $paidUsers = []; // users with trans_status = 'paid'
    $pendingUsers = []; // users with trans_status = 'Pending'
    
    // Google Ads client setup
    $googleCustomerId = '6725026344'; // Your Google Ads ACCOUNT ID (ad account) where audience lists exist
    

    try {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile()
            ->withRefreshToken($g_ref_tok)
            ->build();

        $clientBuilder = (new GoogleAdsClientBuilder())
            ->fromFile()
            ->withOAuth2Credential($oAuth2Credential);

        // Use MCC login-customer-id if available
        if (!empty($g_mcc)) {
            $formattedMcc = preg_replace('/[^0-9]/', '', $g_mcc);
            $clientBuilder->withLoginCustomerId($formattedMcc);
           
        }

        $googleAdsClient = $clientBuilder->build();
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        
        error_log("[❌] Google Ads client error: " . $errorMsg);
        $googleAdsClient = null;
    }

    while ($row = $result->fetch_assoc()) {
        $tbl_id = $row['id'];
        $name = $row['name'];
        $email = $row['email'];
        $phone = str_replace('+91', 'p:+91', str_replace(' ', '', $row['phone']));
        $iam = $row['iam'];
        $pan = $row['pan'];
        $company = $row['company'];
        $designation = $row['designation'];
        $num_tickets = 0;
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

        $tbl_id = intval($tbl_id); // safety
        $conn->query("UPDATE uef2025_expo SET upd = 'Y' WHERE id = $tbl_id");
    }

   
    
    
    
    // Pending users -> Abandoned Cart Audience
    if (!empty($pendingUsers)) {
        
        uploadToMetaAudience($pendingUsers, '120238886482420071', $accessToken, $api_ver, $logFile);
        if ($googleAdsClient) {
            uploadToGoogleAudience($pendingUsers, '9269155970', $googleCustomerId, $googleAdsClient, $logFile);
        }
    }
 
    
}