<?php 
 session_start(); //exit;   
 date_default_timezone_set('Asia/Kolkata');
 ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
 include 'db.php';
 include 'functions-report.php'; 
 
 require __DIR__ . '/email/vendor/autoload.php';
 include 'email/config.php';
 
 include 'gsquare-taboola.php';
 
 function curl_get_file_contents($URL)
 {
         $c = curl_init();
         curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($c, CURLOPT_URL, $URL);
         $contents = curl_exec($c);
         curl_close($c);
 
         if ($contents) return $contents;
         else return FALSE;
  }
 
 
 $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

require __DIR__ . '/google-ads-php/vendor/autoload.php';

use GetOpt\GetOpt;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentNames;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentParser;
use Google\Ads\GoogleAds\Lib\V10\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V10\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V10\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V10\GoogleAdsServerStreamDecorator;
use Google\Ads\GoogleAds\V10\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V10\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;

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
				exit(1);
			} catch (ApiException $apiException) {
				printf(
					"ApiException was thrown with message '%s'.%s",
					$apiException->getMessage(),
					PHP_EOL
				);
				exit(1);
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
			campaign.id, 
			campaign.name, 
			metrics.impressions,
			metrics.clicks,
			metrics.ctr,
			metrics.cost_micros,
			metrics.average_cpc,
			metrics.cost_micros,
			metrics.conversions,
            metrics.quality_score,
			segments.date
			FROM campaign WHERE segments.date >= '".$st."' AND segments.date <= '".$en."' ORDER BY campaign.id"; 
			// Issues a search stream request.
			/** @var GoogleAdsServerStreamDecorator $stream */
			$stream =
				$googleAdsServiceClient->searchStream($customerId, $query);
	
			// Iterates over all rows in all messages and prints the requested field values for
			// the campaign in each row.
			$cost = $conv = array() ;
			foreach ($stream->iterateAllElements() as $googleAdsRow) {
				/** @var GoogleAdsRow $googleAdsRow */
				$campaign = $googleAdsRow->getCampaign();
				$adGroup = $googleAdsRow->getAdGroup();
				$adGroupCriterion = $googleAdsRow->getAdGroupCriterion();
				$metrics = $googleAdsRow->getMetrics();
				$segments = $googleAdsRow->getSegments();
				//print_r($campaign);
				$cost[] = round($metrics->getCostMicros() /1000000) ;
				$conv[] = round($metrics->getConversions()) ;
				$clicks[] = round($metrics->getClicks());
				$ctr[] = round($metrics->getCtr());
				$impressions[] = round($metrics->getImpressions());
                $QualityScore = $metrics->getSegments();
				//echo $campaign->getId().' --> '.$campaign->getName().' -->'.$segments->getDate().' -->';
				//echo $metrics->getCostMicros().' --> '.$metrics->getConversions().'<br>';
				//$gSpent[] = round($cost);
				//echo '<br>';
			}
			/*echo '<br>';
			print_r($cost);
			echo '<br>';
			echo array_sum($cost);
			echo '<br>';
			echo array_sum($conv);*/
			return array('cost'=>array_sum($cost), 'conversions'=>array_sum($conv), 'clicks'=>array_sum($clicks), 'ctr'=>array_sum($ctr), 'impressions'=>array_sum($impressions),'QualityScore'=>$QualityScore);
		}
}
	
$conn =1;
$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],'2470684564', '2022-05-01', '2022-05-16');

print_r($getAccRep);




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