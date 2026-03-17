<?php //session_start();
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
session_start();
require __DIR__ . '/google-ads-v15/vendor/autoload.php';
include 'db.php';

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

/** This example gets all campaigns. To add campaigns, run AddCampaigns.php. */

class GetCampaigns
{
    private const CUSTOMER_ID = '2470684564';

	
    public static function main($conn,$g_ref_tok,$g_mcc)
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
					$googleAdsClient
				);
			} catch (GoogleAdsException $googleAdsException) {
				printf(
					"<br>Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
					$googleAdsException->getRequestId(),
					PHP_EOL,
					PHP_EOL
				);
				foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
					/** @var GoogleAdsError $error */
					printf(
						"<br>\t%s: %s%s",
						$error->getErrorCode()->getErrorCode(),
						$error->getMessage(),
						PHP_EOL
					);
				}
				$errGoogle[] = $adAccId;
				//exit(1);
			} catch (ApiException $apiException) {
				printf(
					"<br>ApiException was thrown with message '%s'.%s",
					$apiException->getMessage(),
					PHP_EOL
				);
				$errGoogle[] = $adAccId;
				//exit(1);
			}
		}
	
		/**
		 * Runs the example.
		 *
		 * @param GoogleAdsClient $googleAdsClient the Google Ads API client
		 * @param int $customerId the customer ID
		 */
		public static function runExample(GoogleAdsClient $googleAdsClient)
		{
			//$googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
			// Creates a query that retrieves all campaigns.
			$customerServiceClient = $googleAdsClient->getCustomerServiceClient();
			//print_r($customerServiceClient);
            // Issues a request for listing all accessible customers.
            $accessibleCustomers = $customerServiceClient->listAccessibleCustomers();
            print 'Total results: ' . count($accessibleCustomers->getResourceNames()) . PHP_EOL;
			echo '<pre>';
			print_r($accessibleCustomers->getResourceNames());
			echo '</pre>';
            // Iterates over all accessible customers' resource names and prints them.
            foreach ($accessibleCustomers->getResourceNames() as $resourceName) {
                /** @var string $resourceName */
                printf("Customer resource name: '%s' %s", $resourceName, PHP_EOL);
				echo '<br>';
            }
		}
}
	
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
d($row);
$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc']);

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