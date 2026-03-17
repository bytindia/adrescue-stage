<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/google-ads-v15/vendor/autoload.php';  // For Google Ads API
include 'db.php';  // Include your database connection file

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\V17\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V17\Services\SearchGoogleAdsRequest;

$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$g_ref_tok = $row['g_refresh_token'];
$g_mcc = $row['g_mcc']; // login-customer-id
$developerToken = getenv('GOOGLE_DEVELOPER_TOKEN');
$clientId = '1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com';
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$customerId = '7881398222'; // NOT MCC! Must be the billed account

// Step 2: Build OAuth2 & Client for Google Ads
$oAuth2Credential = (new OAuth2TokenBuilder())
    ->withClientId($clientId)          // OAuth2 Client ID
    ->withClientSecret($clientSecret)  // OAuth2 Client Secret
    ->withRefreshToken($g_ref_tok)    // User's refresh token
    ->build();

$googleAdsClient = (new GoogleAdsClientBuilder())
    ->withDeveloperToken($developerToken) // Ensure developer token is set
    ->withOAuth2Credential($oAuth2Credential)
    ->withLoginCustomerId($g_mcc)
    ->build();

// Step 3: Fetch Billing Setup Details
$googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
$billingSetupQuery = 'SELECT billing_setup.id, billing_setup.status FROM billing_setup';

$searchRequest = new SearchGoogleAdsRequest();
$searchRequest->setCustomerId($customerId);
$searchRequest->setQuery($billingSetupQuery);

// Execute the search for billing setup
$response = $googleAdsServiceClient->search($searchRequest);

echo "Billing Setup Information:<br>";
foreach ($response->getIterator() as $row) {
    echo "Billing Setup ID: " . $row->getBillingSetup()->getId() . "<br>";
    echo "Billing Setup Status: " . $row->getBillingSetup()->getStatus() . "<br>";
}

// Step 4: Fetch Campaign Data
$campaignQuery = 'SELECT campaign.id, campaign.name, metrics.cost_micros FROM campaign WHERE segments.date DURING LAST_30_DAYS';

$searchRequest->setQuery($campaignQuery);

// Execute the search for campaign data
$response = $googleAdsServiceClient->search($searchRequest);

echo "Campaign Information:<br>";
foreach ($response->getIterator() as $row) {
    echo "Campaign ID: " . $row->getCampaign()->getId() . "<br>";
    echo "Campaign Name: " . $row->getCampaign()->getName() . "<br>";
    $costMicros = $row->getMetrics()->getCostMicros();
    $cost = $costMicros / 1000000;  // Convert micros to regular currency
    echo "Campaign Cost: ₹" . number_format($cost, 2) . "<br>";
}
?>
