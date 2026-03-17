<?php //session_start();
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

namespace Google\AdsApi\Examples\AdWords\v201809\Reporting;
session_start();
require __DIR__ . '/g-vendor/autoload.php';
//include 'db.php';

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
use Google\AdsApi\AdWords\v201809\mcm\CustomerService;
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
		$customerService = $adWordsServices->get($session, CustomerService::class);
		$customers = $customerService->getCustomers();
		//$customerId = $customers[0]->getCustomerId();  // Getting main customer client id 
		foreach($customers as $k=>$v) {
			// echo $v->getCustomerId().'-'.$v->getDescriptiveName();
			//echo '<br>';
			 
			 $cirRes = mysqli_query($conn, "select * from mcc_acc WHERE mcc_id='".$v->getCustomerId()."' AND uid='".$_SESSION['uid']."'");						
			
					//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
					if(mysqli_num_rows($cirRes)==0) {
							$cirSql = "INSERT INTO mcc_acc (uid, mcc_id, mcc_name, currency, created) VALUES ('".$_SESSION['uid']."', '".$v->getCustomerId()."', '".mysqli_real_escape_string($conn, $v->getDescriptiveName())."', '".mysqli_real_escape_string($conn, $v->getCurrencyCode())."', now());"; 
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
						} else {
							 $cirSql = "UPDATE mcc_acc SET mcc_name='".mysqli_real_escape_string($conn, $v->getDescriptiveName())."', updated=now() WHERE mcc_id='".$v->getCustomerId()."' AND uid='".$_SESSION['uid']."'";
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					}
		}

		return $customers;
    }

    public static function main($conn,$g_ref_tok)
    {
        // Generate a refreshable OAuth2 credential for authentication.
       // $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->build();

        // See: AdWordsSessionBuilder for setting a client customer ID that is
        // different from that specified in your adsapi_php.ini file.
        //$sessionBuilder = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential);

		//working below 2 linkes
		
		//echo $g_ref_tok = $_SESSION['g_refresh_token'];
		$oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();
		$sessionBuilder = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential);
		//exit;
		
        self::runExample(
            new AdWordsServices(),
            $sessionBuilder,
           // sys_get_temp_dir()
		   'download/',
		   $conn
        );
    }
}



include 'header.php'; 
$pgHeadline = 'Google Ads - Select account ';
$pgID = 22;
$err =''; 
ParallelReportDownload::main($conn, $_SESSION['g_refresh_token']);

//echo $request_url = "https://graph.facebook.com/v11.0/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";exit;
//echo $access_token; 
if(isset($_POST['submit'])){
	if($_POST['mcc_id']!='' || $_POST['mcc_id']!='') {
		
		$cirSql = "UPDATE users SET g_mcc='".mysqli_real_escape_string($conn, $_POST['mcc_id'])."' WHERE tbl_id=".$_SESSION['uid']."";
		mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		//$lastId = $_GET['id'];
		$_SESSION['g_mcc'] = $_POST['mcc_id'];
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'adAccounts-g.php';</script>";
		exit();
	} 
}
if(isset($_SESSION['g_mcc']) || $_SESSION['g_mcc']!='') 
{
	echo "<script>window.location = 'ad-accounts-g.php';</script>";
	exit();
}
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>                      
                    </ul>
                    
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  	<?php
                    $sqlRev=mysqli_query($conn, "SELECT * FROM mcc_acc WHERE uid='".$_SESSION['uid']."' order by mcc_name asc");
					?>
        					<form method="post" action="">
                            	 <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">   
                            		<label>Facebook Account: </label>
                                                    <select name="mcc_id" id="mcc_id" class="form-control">
                                                    	<option value="">Select Google Manager Account</option>
                                                        <?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["mcc_id"]; ?>"><?php echo $sqlROW["mcc_name"]; ?></option>
                                                        <?php } ?>
                                                    	
                                           </select>
                                           <br />
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-info">
                                                 
                                                 <br />
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
        		  </div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
