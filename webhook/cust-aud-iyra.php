<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Load DB
include '../db.php';

require '../google-ads-v15/vendor/autoload.php';

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

// Fetch user tokens
$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id = 2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$accessToken = $row['access_token'];
$g_ref_tok = $row['g_refresh_token'];

/*
$_POST['email'] = 'test@gmail.com';
$_POST['phone'] = '912345678901';
$_POST['project'] = 'iyra-city';
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead'])) {
    $lead_data = @unserialize($_POST['lead']);
    $phone = preg_replace('/\D/', '', $lead_data['phone_number'] ?? '');

    $user_phone = substr($phone, -10);
    $user_email = $lead_data['email'] ?? '';
    if($_POST['pageId'] == '104623015828534') { $proj = 'iyra-amara'; } else{ $proj = 'iyra-city'; }
    
} elseif (isset($_POST['phone']) && isset($_POST['email']) && isset($_POST['project'])) {
    $user_phone = preg_replace('/\D/', '', $_POST['phone']);
    if (strlen($user_phone) === 10) {
        $user_phone = '91' . $user_phone;
    }
    $user_email = $_POST['email'];
    $proj = $_POST['project'];
} else {
    die('❌ Invalid request method or missing lead data.');
}

/*
// Input lead
$user_phone = $_POST['phone'] ?? '';
$user_email = $_POST['email'] ?? '';
$proj = $_POST['project'] ?? '';

$user_email = 'test@gmail.com';
$user_phone = '912345678901';
$proj = 'rwd-vista';
*/
$user_phone = preg_replace('/\D/', '', $user_phone);
if (strlen($user_phone) === 10) {
    $user_phone = '91' . $user_phone;
}

// --- META ---
$metaAudienceMap = [
    'iyra-city' => '120236883305680611',
    'iyra-amara' => '120237515690310611'
];

$hashedLead = [[
    hash('sha256', strtolower(trim($user_email))),
    hash('sha256', strtolower(trim($user_phone)))
]];
$payload = [
    'payload' => [
        'schema' => ['EMAIL', 'PHONE'],
        'data' => $hashedLead
    ],
    'access_token' => $accessToken
];

//$api_ver = 'v19.0';
if (!isset($metaAudienceMap[$proj])) {
    die("❌ Invalid project code for Meta");
}
$audienceId = $metaAudienceMap[$proj];

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
    echo "[❌] Meta upload error for Audience $audienceId: " . curl_error($ch) . "\n";
} else {
    echo "[✅] Meta Audience $audienceId | HTTP $httpCode\n$response\n\n";
}
curl_close($ch);



// --- GOOGLE ---
$googleAudienceMap = [
    'iyra-city' => ['customerId' => '3950071509', 'userListId' => '9277219746']
];

if (!isset($googleAudienceMap[$proj])) {
    die("❌ Invalid project code for Google");
}

$aud = $googleAudienceMap[$proj];
$customerId = $aud['customerId'];
$userListId = $aud['userListId'];

$oAuth2Credential = (new OAuth2TokenBuilder())
    ->fromFile()
    ->withRefreshToken($g_ref_tok)
    ->build();

$googleAdsClient = (new GoogleAdsClientBuilder())
    ->fromFile()
    ->withOAuth2Credential($oAuth2Credential)
    ->withLoginCustomerId($row['g_mcc']) // ✅ MCC account from DB
    ->build();

$hashedEmail = hash('sha256', strtolower(trim($user_email)));
$hashedPhone = hash('sha256', trim($user_phone));

// Step 1: Create Job
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

// Step 2: Add User
$userData = new UserData([
    'user_identifiers' => [
        new UserIdentifier(['hashed_email' => $hashedEmail]),
        new UserIdentifier(['hashed_phone_number' => $hashedPhone]),
    ]
]);
$operation = new OfflineUserDataJobOperation(['create' => $userData]);
$operations = [$operation];

$request = new AddOfflineUserDataJobOperationsRequest([
    'resource_name' => $jobResourceName,
    'operations' => $operations,
]);
$response = $offlineUserDataJobServiceClient->addOfflineUserDataJobOperations($request);

// Step 3: Run Job
$request = new RunOfflineUserDataJobRequest([
    'resource_name' => $jobResourceName,
]);
$response = $offlineUserDataJobServiceClient->runOfflineUserDataJob($request);

echo "[✅] Google Audience $userListId (Customer $customerId) - Job submitted\n";
?>
