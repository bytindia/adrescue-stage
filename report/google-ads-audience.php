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

require '../google-ads-php/vendor/autoload.php';


use GetOpt\GetOpt;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentNames;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentParser;
use Google\Ads\GoogleAds\Lib\V13\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V13\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V13\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V13\GoogleAdsServerStreamDecorator;
use Google\Ads\GoogleAds\V13\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V13\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;

/** This example gets all campaigns. To add campaigns, run AddCampaigns.php. */

class GetAd
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
			$googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
			// Creates a query that retrieves all campaigns.
			$query = "SELECT
			campaign.name,
			campaign.id,
			ad_group.name,
			ad_group_audience_view.resource_name,
			ad_group.targeting_setting.target_restrictions,
			ad_group_criterion.user_list.user_list,
			ad_group_criterion.type,
			user_list.type,
			user_list.resource_name,
			user_list.name,
			user_list.description,
			user_list.id,
			user_list.eligible_for_display,
			user_list.eligible_for_search,
			user_list.integration_code,
			user_list.membership_status
		   
		FROM
			ad_group_audience_view"; 
			// Issues a search stream request.
			/** @var GoogleAdsServerStreamDecorator $stream */
			$stream = $googleAdsServiceClient->searchStream($customerId, $query);
	
			// Iterates over all rows in all messages and prints the requested field values for
			// the campaign in each row.
			$adId = $adName = $adChannel = $adStatus = $impressions = $clicks = $ctr = $avgCpc = $cost = $conv = $adData = array() ;
            $adId_arr = $adName_arr = $adChannel_arr = $adStatus_arr = $impressions_arr = $clicks_arr = $ctr_arr = $avgCpc_arr = $cost_arr = $conv_arr = $adData_arr = array() ;

			$channelN = array('UNSPECIFIED', 'UNKNOWN', 'SEARCH', 'DISPLAY', 'SHOPPING', 'HOTEL', 'VIDEO', 'MULTI_CHANNEL', 'LOCAL', 'SMART', 'PERFORMANCE_MAX', 'LOCAL_SERVICES', 'DISCOVERY');
			
			$statusN = array ('UNSPECIFIED', 'UNKNOWN', 'ENABLED', 'PAUSED', 'REMOVED');

			$z = 0; 
			foreach ($stream->iterateAllElements() as $googleAdsRow) {
				/** @var GoogleAdsRow $googleAdsRow */
				
				$ads = json_decode($googleAdsRow->serializeToJsonString(), true);
				//echo $ads['audience']['id'].' - '.$ads['audience']['name'].' - '.$ads['audience']['status'].'<br>';
				echo '<pre>'; print_r($ads); echo '</pre>'; 
				$z++;
			}
			
            if(count($adData)>0){
               // $adData[] = array('Total', '--', '--', '--', array_sum($avgCpc_arr), array_sum($impressions_arr), array_sum($clicks_arr), array_sum($cost_arr), array_sum($avgCpc_arr), array_sum($avgCpm_arr), @(array_sum($cost_arr)/array_sum($conv_arr)), array_sum($ctr_arr), array_sum($conv_arr));
            }
           // return $adData; 
		}
}
	

//$getAccRep = GetAd::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],'2470684564', '2022-05-01', '2022-05-16');

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