<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

//session_start();
require __DIR__ . '/google-ads-v15/vendor/autoload.php';
//include 'db.php';

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

class GetAccountDailyReport
{
    public static function main($g_ref_tok, $g_mcc, $adAccId, $st, $en, $daily, $filter_q = '')
    {
        // Build OAuth2 credentials
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();

        // Construct a Google Ads client configured from a properties file and the
        // OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
          ->withOAuth2Credential($oAuth2Credential)
          ->withLoginCustomerId($g_mcc)
          ->build();

        // The query to fetch daily metrics at the account level
        $ext_q = '';

        $query = "
    SELECT 
        campaign.id, 
        campaign.name, 
        segments.date, 
        metrics.impressions,
        metrics.clicks,
        metrics.ctr,
        metrics.average_cpc,
        metrics.cost_micros,
        metrics.conversions
    FROM 
        campaign
    WHERE 
        segments.date BETWEEN '".$st."' AND '".$en."'
        $filter_q
    ORDER BY 
        segments.date
";

        try {
            // Issue a search request by streaming.
            $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
            $request = new SearchGoogleAdsStreamRequest(['customer_id' => $adAccId,'query' => $query]);

			      $response = $googleAdsServiceClient->searchStream($request);
            $rep_data = array();
            // Process the response
            //echo "<h3>Account Daily Report:</h3>";
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                /** @var \Google\Ads\GoogleAds\V17\Services\GoogleAdsRow $googleAdsRow */
                /*printf(
                    "Date: %s | Account: %s (%s) | Impressions: %d | Clicks: %d | Cost: %.2f USD%s",
                    $googleAdsRow->getSegments()->getDate(),
                    $googleAdsRow->getCustomer()->getDescriptiveName(),
                    $googleAdsRow->getCustomer()->getId(),
                    $googleAdsRow->getMetrics()->getImpressions(),
                    $googleAdsRow->getMetrics()->getClicks(),
                    $googleAdsRow->getMetrics()->getCostMicros() / 1_000_000,
                    PHP_EOL
                );*/
                $rep_data[] = array(
                  'date' => $googleAdsRow->getSegments()->getDate(),
                  'cost' => ($googleAdsRow->getMetrics()->getCostMicros() / 1_000_000),
                  'conv' => $googleAdsRow->getMetrics()->getConversions(),
                  'clicks' => $googleAdsRow->getMetrics()->getClicks(),
                  'impr' => $googleAdsRow->getMetrics()->getImpressions()
                );
            }
            return $rep_data;
        } catch (GoogleAdsException $googleAdsException) {
            printf(
                "Request with ID '%s' failed due to Google Ads API errors:%s",
                $googleAdsException->getRequestId(),
                PHP_EOL
            );
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                /** @var \Google\Ads\GoogleAds\V17\Errors\GoogleAdsError $error */
                printf(
                    "\t%s: %s%s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage(),
                    PHP_EOL
                );
            }
        } catch (ApiException $apiException) {
            printf(
                "ApiException was thrown with message '%s'.%s",
                $apiException->getMessage(),
                PHP_EOL
            );
        }
    }
}
/*
$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 
$uId = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['g_id'] = $row['g_id'];
$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
$_SESSION['g_token'] = $row['g_token'];
$_SESSION['g_mcc'] = $row['g_mcc'];

// Parameters for the request
$g_ref_tok = $_SESSION['g_refresh_token'];
$g_mcc = $_SESSION['g_mcc'];
$adAccId = '3358840745';
$st = date("Y-m-d", strtotime('-10 days')); // Start date
$en = date("Y-m-d", strtotime('-1 days')); // End date

// Run the report fetcher
$rep = GetAccountDailyReport::main($g_ref_tok, $g_mcc, $adAccId, $st, $en);
d($rep);
*/