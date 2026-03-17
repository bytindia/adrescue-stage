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

class GetCampaignsFromMultipleAccounts
{
    public static function main($g_ref_tok,$g_mcc,$adAccId, $st, $en, $extQry) //$g_ref_tok,$g_mcc,$adAccId, $st, $en
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();

        // Construct a Google Ads client configured from a properties file and the
        // OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
		->withOAuth2Credential($oAuth2Credential)
		->withLoginCustomerId($g_mcc)
		->build();

        foreach ($adAccId as $accountId) {
           // echo "Fetching campaigns for account ID: $accountId\n";
            try {
                // Call the method to get the campaigns for this account
                return self::getCampaigns($googleAdsClient, $accountId, $st, $en, $extQry);
            } catch (GoogleAdsException $googleAdsException) {
                printf("Request failed for account ID '%s' with error: %s\n", $accountId, $googleAdsException->getMessage());
            } catch (ApiException $apiException) {
                printf("API Exception for account ID '%s': %s\n", $accountId, $apiException->getMessage());
            }
        }
    }

    public static function getCampaigns(GoogleAdsClient $googleAdsClient, $clientCustomerId, $st, $en, $extQry)
    {
        // Get the GoogleAdsServiceClient
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        // Query to fetch campaigns for the client account
        if($extQry=='') {
            $extQry = "segments.date >= '".$st."' AND segments.date <= '".$en."' ";
        } 
        $query = "SELECT 
        campaign.id, 
        campaign.name, 
        campaign.status, 
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
        metrics.all_conversions_from_order,
        campaign_budget.amount_micros
        FROM campaign WHERE $extQry ORDER BY campaign.id"; 

        // Issue a search stream request for the client account
        $request = new SearchGoogleAdsStreamRequest(['customer_id' => $clientCustomerId,'query' => $query]);
		$stream = $googleAdsServiceClient->searchStream($request);
        //$stream = $googleAdsServiceClient->searchStream($clientCustomerId, $query);
        

        // Process and print the campaign results
            $campaignId = $campaignName = $impressions = $clicks = $ctr = $avgCpc = $cost = $conv = $conv_v = $campaignData = $conv_v2 = array() ;
            $campaignId_arr = $campaignName_arr = $impressions_arr = $clicks_arr = $ctr_arr = $avgCpc_arr = $cost_arr = $conv_arr = $campaignData_arr = array() ;

			foreach ($stream->iterateAllElements() as $googleAdsRow) {
				/** @var GoogleAdsRow $googleAdsRow */
				$campaign = $googleAdsRow->getCampaign();
                $campaign_bud = $googleAdsRow->getCampaignBudget();
				$adGroup = $googleAdsRow->getAdGroup();
				$adGroupCriterion = $googleAdsRow->getAdGroupCriterion();
				$metrics = $googleAdsRow->getMetrics();
				//$shopping_performance_view = $googleAdsRow->getShopingPerformanceView();

				//print_r($campaign);
				$cost = round($metrics->getCostMicros() /1000000,2) ;
				$conv = round($metrics->getConversions(),2);
				$conv_v = round($metrics->getConversionsValue(),2);
				$conv_v2 = $metrics->getAllConversionsFromOrder();
				$campaignId = $campaign->getId();
                $campaignStatus = $campaign->getStatus();
				$campaignName = $campaign->getName();
				$impressions = $metrics->getImpressions();
				$clicks = $metrics->getClicks();
				$ctr = round($metrics->getCtr()*100,2);
				$avgCpc = round($metrics->getAverageCpc() /1000000,2) ;
                $avgCost = round($metrics->getAverageCost() /1000000,2) ;
                $avgCpm = round($metrics->getAverageCpm() /1000000,2) ;
				$daily_budget = round($campaign_bud->getAmountMicros()/1000000,2) ;
				
                

               
                $CostPerConv = 0;
				if($conv!=0){
					$CostPerConv = @($cost/$conv);
				}
				$camp_status = array(0=>'UNSPECIFIED',1=>'UNKNOWN',2=>'ENABLED',3=>'PAUSED',4=>'REMOVED');
                $campaignData[] = array('camp_id'=>$campaignId, 'camp_name'=>$campaignName, 'camp_status'=>$camp_status[$campaignStatus], 'cost'=>$cost, 'avg_cpc'=>$avgCpc, 'cpc'=>$CostPerConv, 'conv'=>$conv, 'conv_val'=>$conv_v, 'conv_val2'=>$conv_v2, 'budget'=>$daily_budget, 'impr'=>$impressions, 'clicks'=>$clicks);
                
			}
            
            return $campaignData;

        
    }
}
/*
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

// Run the main function to fetch campaigns for all accounts
$gooRep = GetCampaignsFromMultipleAccounts::main($oauthCredentials, $adAccounts, date("Y-m-d", strtotime('-10 days')), date("Y-m-d", strtotime('-1 days')));
d($gooRep);
exit;
// Example of how to call the main method for a standalone account
$refreshToken = $_SESSION['g_refresh_token'];   // Replace with your actual refresh token
$clientCustomerId = '2166797111';  // Client Customer Account ID (replace with actual)

$managerCustomerId = '2166797111';  // Manager Account (MCC) ID
// Client Customer Account ID (replace with actual)

GetClientCampaigns::main($refreshToken, $clientCustomerId);

*/