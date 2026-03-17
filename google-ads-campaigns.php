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

require __DIR__ . '/google-ads-v15/vendor/autoload.php';

use GetOpt\GetOpt;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentNames;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentParser;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsServerStreamDecorator;
use Google\Ads\GoogleAds\V20\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V20\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

/** This example gets all campaigns. To add campaigns, run AddCampaigns.php. */

class GetCampaigns
{
    private const CUSTOMER_ID = '2470684564';

	
    public static function main($conn,$g_ref_tok,$g_mcc,$adAccId, $st, $en)
    {
		//$adAccId = '2470684564';
		//CUSTOMER_ID = '2470684564';
		// const CUSTOMER_ID = $adAccId;
        // Either pass the required parameters for this example on the command line, or insert them
        // into the constants above.
       


		//echo $g_mcc = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $g_mcc); 
		
		//$oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();
		//$sessionBuilder = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential)->withClientCustomerId($g_mcc);


        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();

        // Construct a Google Ads client configured from a properties file and the
        // OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
		->withOAuth2Credential($oAuth2Credential)
		->withLoginCustomerId($g_mcc)
		->build();

			try {
				return self::runExample(
					$googleAdsClient,
					$adAccId, $st, $en
				);
			} catch (GoogleAdsException $googleAdsException) {
				printf(
					"Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
					$googleAdsException->getRequestId(),
					PHP_EOL,
					PHP_EOL
				);
				foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
					/** @var GoogleAdsError $error */
					printf(
						"\t%s: %s%s",
						$error->getErrorCode()->getErrorCode(),
						$error->getMessage(),
						PHP_EOL
					);
				}
				//exit(1);
			} catch (ApiException $apiException) {
				printf(
					"ApiException was thrown with message '%s'.%s",
					$apiException->getMessage(),
					PHP_EOL
				);
				//exit(1);
			}
		}
	
		/**
		 * Runs the example.
		 *
		 * @param GoogleAdsClient $googleAdsClient the Google Ads API client
		 * @param int $customerId the customer ID
		 */
		public static function runExample(GoogleAdsClient $googleAdsClient, int $customerId, $st, $en)
		{
			global $filtering_param_g;
			$googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
			// Creates a query that retrieves all campaigns.
			$query = "SELECT 
			campaign.id, 
			campaign.name, 
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
			metrics.all_conversions_from_order
			FROM campaign WHERE segments.date >= '".$st."' AND segments.date <= '".$en."' AND metrics.impressions>0 $filtering_param_g ORDER BY campaign.id"; 
			// Issues a search stream request.
			/** @var GoogleAdsServerStreamDecorator $stream */

			$request = new SearchGoogleAdsStreamRequest(['customer_id' => $customerId,'query' => $query]);
			$stream = $googleAdsServiceClient->searchStream($request);
	
			// Iterates over all rows in all messages and prints the requested field values for
			// the campaign in each row.
			$campaignId = $campaignName = $impressions = $clicks = $ctr = $avgCpc = $cost = $conv = $conv_v = $campaignData = $conv_v2 = array() ;
            $campaignId_arr = $campaignName_arr = $impressions_arr = $clicks_arr = $ctr_arr = $avgCpc_arr = $cost_arr = $conv_arr = $campaignData_arr = array() ;

			foreach ($stream->iterateAllElements() as $googleAdsRow) {
				/** @var GoogleAdsRow $googleAdsRow */
				$campaign = $googleAdsRow->getCampaign();
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
				$campaignName = $campaign->getName();
				$impressions = $metrics->getImpressions();
				$clicks = $metrics->getClicks();
				$ctr = round($metrics->getCtr()*100,2);
				$avgCpc = round($metrics->getAverageCpc() /1000000,2) ;
                $avgCost = round($metrics->getAverageCost() /1000000,2) ;
                $avgCpm = round($metrics->getAverageCpm() /1000000,2) ;
				
				
                

                $cost_arr[] = round($metrics->getCostMicros() /1000000,2) ;
				$conv_arr[] = round($metrics->getConversions(),2);
				$campaignId_arr[] = $campaign->getId();
				$campaignName_arr[] = $campaign->getName();
				$impressions_arr[] = $metrics->getImpressions();
				$clicks_arr[] = $metrics->getClicks();
				$ctr_arr[] = round($metrics->getCtr()*100,2);
				$avgCpc_arr[] = round($metrics->getAverageCpc() /1000000,2) ;
                $avgCost_arr[] = round($metrics->getAverageCost() /1000000,2) ;
                $avgCpm_arr[] = round($metrics->getAverageCpm() /1000000,2) ;
                
				if($conv!=0){
					$CostPerConv = @($cost/$conv);
					$CostPerConv_arr[] = @($cost/$conv);
				}
				//echo $campaign->getId().' --> '.$campaign->getName().' -->'.$segments->getDate().' -->';
				//echo $metrics->getCostMicros().' --> '.$metrics->getConversions().'<br>';
				//$gSpent[] = round($cost);
				//echo '<br>';
                $campaignData[] = array($campaignId, $campaignName, $avgCpc, $impressions, $clicks, $cost, $avgCpc, $avgCpm, $CostPerConv, $ctr, $conv, $conv_v, $conv_v2);
			}
			/*echo '<br>';
			print_r($cost);
			echo '<br>';
			echo array_sum($cost);
			echo '<br>';
			echo array_sum($conv);*/
			/*return array(
				'cost'=> $cost, 
				'conversions'=> $conv,
                'campaignId'=> $campaignId, 
                'campaignName'=> $campaignName, 
                'impressions'=> $impressions, 
                'clicks'=> $clicks, 
                'ctr'=> $ctr, 
                'avgCpc'=> $avgCpc
			);*/
			$tot_cpc =0;
			if(array_sum($conv_arr)>0) { $tot_cpc = @(array_sum($cost_arr)/array_sum($conv_arr)); }
            if(count($campaignData)>0){
                $campaignData[] = array('Total', '--', array_sum($avgCpc_arr), array_sum($impressions_arr), array_sum($clicks_arr), array_sum($cost_arr), array_sum($avgCpc_arr), array_sum($avgCpm_arr), $tot_cpc, array_sum($ctr_arr), array_sum($conv_arr));
            }
            return $campaignData; 
		}
}
	

//$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],'2470684564', '2022-05-01', '2022-05-16');

//print_r($getAccRep);




/*SELECT campaign.name, ad_group.name, customer.id, customer.descriptive_name,
campaign.advertising_channel_type,
segments.date,
ad_group_ad.ad.expanded_text_ad.headline_part1,
ad_group_ad.ad.expanded_text_ad.headline_part2, 
ad_group_ad.ad.expanded_text_ad.headline_part3,
ad_group_ad.ad.expanded_text_ad.description,
ad_group_ad.ad.expanded_text_ad.description2,
customer.currency_code,
metrics.average_cpc,
ad_group_ad.ad.shopping_product_ad,
metrics.cost_micros,
metrics.impressions,
metrics.clicks,
metrics.ctr,
metrics.conversions
FROM ad_group_ad
WHERE segments.date BETWEEN {date_range}
AND campaign.advertising_channel_type = 'SEARCH'
AND metrics.cost_micros > 0
ORDER BY segments.date*/