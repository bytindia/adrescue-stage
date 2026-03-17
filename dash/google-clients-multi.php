<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
/**
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//namespace Google\Ads\GoogleAds\Examples\AccountManagement;
//session_start();

//include 'db.php';

require  '/home/digitalb2k/stage.adrescue.in/google-ads-v15/vendor/autoload.php';

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

class GetGoogleAdsResultsForMultiClient
{
    public static function main($g_ref_tok, $g_mcc, $adAccId, $st, $en, $extQry)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();

        // Construct a Google Ads client configured from a properties file and the
        // OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
		->withOAuth2Credential($oAuth2Credential)
		->withLoginCustomerId($g_mcc)
		->build();

        foreach ($adAccId as $accountId) {
            try {
                // Call the method to get the results data for this account
                return self::getAccountResults($googleAdsClient, $accountId, $st, $en, $extQry);
            } catch (GoogleAdsException $googleAdsException) {
                printf("Request failed for account ID '%s' with error: %s\n", $accountId, $googleAdsException->getMessage());
            } catch (ApiException $apiException) {
                printf("API Exception for account ID '%s': %s\n", $accountId, $apiException->getMessage());
            }
        }
    }

    public static function getAccountResults(GoogleAdsClient $googleAdsClient, $clientCustomerId, $st, $en, $extQry)
    {
        // Get the GoogleAdsServiceClient
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        // Query to fetch account-level results data for lead form submissions
        if($extQry=='') {
            $extQry = "segments.date >= '".$st."' AND segments.date <= '".$en."' ";
        } 
        
        // Query to get account-level metrics (without segments that cause conflicts)
        $query = "SELECT 
        customer.id,
        customer.descriptive_name,
        metrics.impressions,
        metrics.clicks,
        metrics.ctr,
        metrics.average_cpc,
        metrics.cost_micros,
        metrics.average_cost,
        metrics.average_cpm,
        metrics.cost_per_all_conversions,
        metrics.conversions, 
        metrics.conversions_value,
        metrics.all_conversions,
        metrics.all_conversions_value
        FROM customer WHERE $extQry ORDER BY customer.id"; 

        // Issue a search stream request for the client account
        $request = new SearchGoogleAdsStreamRequest(['customer_id' => $clientCustomerId,'query' => $query]);
		$stream = $googleAdsServiceClient->searchStream($request);

        // Process and return the account results
        $accountData = array();
        $totalCost = 0;
        $totalLeads = 0;
        $totalImpressions = 0;
        $totalClicks = 0;

        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $customer = $googleAdsRow->getCustomer();
            $metrics = $googleAdsRow->getMetrics();

            $cost = round($metrics->getCostMicros() / 1000000, 2);
            $conversions = round($metrics->getConversions(), 2);
            $allConversions = round($metrics->getAllConversions(), 2);
            
            $customerId = $customer->getId();
            $customerName = $customer->getDescriptiveName();
            $impressions = $metrics->getImpressions();
            $clicks = $metrics->getClicks();
            $ctr = round($metrics->getCtr() * 100, 2);
            $avgCpc = round($metrics->getAverageCpc() / 1000000, 2);
            $avgCost = round($metrics->getAverageCost() / 1000000, 2);
            $avgCpm = round($metrics->getAverageCpm() / 1000000, 2);

            $totalCost += $cost;
            $totalLeads += $allConversions; // Use all_conversions for total leads
            $totalImpressions += $impressions;
            $totalClicks += $clicks;
        }

        // Get lead form specific conversions from campaign level
        $leadFormConversions = self::getLeadFormConversions($googleAdsServiceClient, $clientCustomerId, $st, $en);

        // At account level, we use all_conversions as the leads value
        // This represents the total number of conversions across all conversion actions
        $accountData = array(
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'cost' => $totalCost,
            'leads' => $leadFormConversions > 0 ? $leadFormConversions : $totalLeads, // Use lead form conversions if available, otherwise all_conversions
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'ctr' => ($totalImpressions > 0) ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
            'avg_cpc' => ($totalClicks > 0) ? round($totalCost / $totalClicks, 2) : 0,
            'avg_cost' => ($totalImpressions > 0) ? round($totalCost / $totalImpressions, 2) : 0,
            'avg_cpm' => ($totalImpressions > 0) ? round(($totalCost / $totalImpressions) * 1000, 2) : 0,
            'cost_per_lead' => ($totalLeads > 0) ? round($totalCost / $totalLeads, 2) : 0
        );
            
        return $accountData;
    }

    private static function getLeadFormConversions($googleAdsServiceClient, $clientCustomerId, $st, $en)
    {
        // Query to get lead form conversions at campaign level
        $leadFormQuery = "SELECT 
        segments.conversion_action_name,
        segments.conversion_action_category,
        metrics.all_conversions
        FROM campaign 
        WHERE segments.date >= '".$st."' AND segments.date <= '".$en."' 
        AND (segments.conversion_action_name LIKE '%lead form%' 
             OR segments.conversion_action_name LIKE '%submit lead%'
             OR segments.conversion_action_category = 'LEAD')
        ORDER BY segments.conversion_action_name";

        try {
            $request = new SearchGoogleAdsStreamRequest(['customer_id' => $clientCustomerId,'query' => $leadFormQuery]);
            $stream = $googleAdsServiceClient->searchStream($request);

            $totalLeadFormConversions = 0;
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $segments = $googleAdsRow->getSegments();
                $metrics = $googleAdsRow->getMetrics();
                
                $conversionActionName = $segments->getConversionActionName();
                $conversionActionCategory = $segments->getConversionActionCategory();
                $allConversions = round($metrics->getAllConversions(), 2);

                // Check if this is a lead form conversion
                if ($conversionActionName && 
                    (stripos($conversionActionName, 'lead form') !== false || 
                     stripos($conversionActionName, 'submit lead') !== false ||
                     stripos($conversionActionCategory, 'LEAD') !== false)) {
                    $totalLeadFormConversions += $allConversions;
                }
            }
            
            return $totalLeadFormConversions;
        } catch (Exception $e) {
            // If lead form query fails, return 0 to fall back to all conversions
            return 0;
        }
    }
}

/*
// Example usage:
// OAuth2 credentials (you can load these from a config file or environment variables)
$oauthCredentials = [
    'client_id' => '1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
    'refresh_token' => $_SESSION['g_refresh_token'],
    'developer_token' => getenv('GOOGLE_DEVELOPER_TOKEN')
];

// List of client customer IDs (ad account IDs) that you want to access
$adAccounts = [
    '2166797111',
];

// Run the main function to fetch results for all accounts
$gooRep = GetGoogleAdsResultsForMultiClient::main($oauthCredentials, $adAccounts, date("Y-m-d", strtotime('-10 days')), date("Y-m-d", strtotime('-1 days')));
d($gooRep);
exit;
*/
?>
