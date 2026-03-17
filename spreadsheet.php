<?php //session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//namespace Google\AdsApi\Examples\AdWords\v201809\Reporting;
session_start();
require __DIR__ . 'spreadsheet/vendor/autoload.php';

$redirect = 'yes'; if(isset($_GET['cron'])) { $_SESSION['uid'] = 2; $redirect = 'no'; }

include 'db.php';

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201809\cm\ApiException;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\mcm\ManagedCustomerService;
use Google\AdsApi\Common\OAuth2TokenBuilder;

/**
 * This example gets and downloads an Ad Hoc report from an XML report
 * definition for all accounts directly under a manager account.
 * This example should be run against an AdWords manager account.
 *
 * Although the example's name is `ParallelReportDownload`, it doesn't download
 * reports in parallel as this client library doesn't support multithreading.
 * It is named so to be consistent with other client libraries.
 */
class ParallelReportDownload
{

    // Timeout between retries in seconds.
    const BACKOFF_FACTOR = 5;

    // Maximum number of retries for 500 errors.
    const MAX_RETRIES = 5;

    // The number of entries per page of the results.
    const PAGE_LIMIT = 500;

    public static function runExample(
        AdWordsServices $adWordsServices,
        AdWordsSessionBuilder $sessionBuilder,
        $reportDir,
		$conn
    ) {
        // Construct an API session for the client customer ID specified in the
        // configuration file.
        $session = $sessionBuilder->build();

        $customerIds = self::getAllManagedCustomerIds($adWordsServices, $session, $conn);
    }

    /**
     * Retrieves all the customer IDs under a manager account.
     *
     * To set clientCustomerId to any manager account you want to get
     * reports for its client accounts, use `AdWordsSessionBuilder` to
     * create new session.
     */
    private static function getAllManagedCustomerIds(AdWordsServices $adWordsServices, AdWordsSession $session, $conn) 
	{
        $managedCustomerService = $adWordsServices->get($session, ManagedCustomerService::class);

        $selector = new Selector();
        $selector->setFields(['CustomerId','Name','CurrencyCode']);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $selector->setPredicates(
            [
                new Predicate(
                    'CanManageClients',
                    PredicateOperator::EQUALS,
                    ['false']
                )
            ]
        );

        $customerIds = [];
        $totalNumEntries = 0;
        do {
            $page = $managedCustomerService->get($selector);
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $customer) {
                    $customerIds[] = $customer->getCustomerId();
					 //echo $customer->getCustomerId().','.$customer->getName();
					 //echo $customer->getCurrencyCode(); exit;
					$cirRes = mysqli_query($conn, "select * from gaccounts WHERE account_id='".$customer->getCustomerId()."' AND uid='".$_SESSION['uid']."'");						
			
					//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
					if(mysqli_num_rows($cirRes)==0) {
							$cirSql = "INSERT INTO gaccounts (uid, name, account_id, currency, created) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $customer->getName())."', '".mysqli_real_escape_string($conn, $customer->getCustomerId())."', '".mysqli_real_escape_string($conn, $customer->getCurrencyCode())."', now());"; 
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
						} else {
							 $cirSql = "UPDATE gaccounts SET name='".mysqli_real_escape_string($conn, $customer->getName())."', updated=now() WHERE account_id='".$customer->getCustomerId()."' AND g_id='".$_SESSION['g_id']."' AND uid='".$_SESSION['uid']."'";
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					}
					
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return $customerIds;
    }

    public static function main($conn,$g_ref_tok,$g_mcc)
    {
        // Generate a refreshable OAuth2 credential for authentication.
        //$oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->build();

        // See: AdWordsSessionBuilder for setting a client customer ID that is
        // different from that specified in your adsapi_php.ini file.
        //$sessionBuilder = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential);

		//working below 2 linkes
		
		//$oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken('1/31jPeOvZa2QGpic6WjQMg1V5l8FPtB3tFZcP0rSJcCE')->build();
		//$sessionBuilder = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential)->withClientCustomerId('779-436-9179');		
		$g_mcc = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $g_mcc); 
		
		$oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();
		$sessionBuilder = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential)->withClientCustomerId($g_mcc);
		
        self::runExample(
            new AdWordsServices(),
            $sessionBuilder,
           // sys_get_temp_dir()
		   'download/',
		   $conn
        );
    }
}

ParallelReportDownload::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc']);

if($redirect == 'yes') {
    $_SESSION['suc'] = 'Successfully Updated!';	
    echo "<script>window.location = 'ad-accounts-g.php';</script>";
    exit();
}
