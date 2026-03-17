<?php
function d($d) {
	echo '<pre>';
	print_r($d);
	echo '</pre>';
}
require_once __DIR__ . '/vendor/autoload.php';

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;



use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Object\User;


// Init PHP Sessions
session_start();


$ad_account_id = 'act_479529832536428';
$app_secret = getenv('FB_APP_SECRET');
$app_id = '594832897646145';

$fb = new Facebook([
  'app_id' => $app_id,
  'app_secret' => $app_secret,
]);

$helper = $fb->getRedirectLoginHelper();
//REDACTED_FB_TOKEN

//REDACTED_FB_TOKEN

if (!isset($_SESSION['facebook_access_token'])) {
  $_SESSION['facebook_access_token'] = null;
}

if (!$_SESSION['facebook_access_token']) {
  $helper = $fb->getRedirectLoginHelper();
  try {
    $_SESSION['facebook_access_token'] = (string) $helper->getAccessToken();
  } catch(FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
  } catch(FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
  }
}

if ($_SESSION['facebook_access_token']) {
	//echo $_SESSION['facebook_access_token'];
 // echo "You are logged in!";
	$api = Api::init($app_id, $app_secret, $_SESSION['facebook_access_token']);
	//$api->setLogger(new CurlLogger());
	
	$account = new AdAccount($ad_account_id);
	$cursor = $account->getCampaigns();
	
	// Loop over objects
	foreach ($cursor as $campaign) {
	  echo $campaign->{CampaignFields::NAME}.PHP_EOL;
	}


} else {
  $permissions = ['ads_management'];
  $loginUrl = $helper->getLoginUrl('https://stage.adrescue.in/fb-ads/test.php', $permissions);
  echo '<a href="' . $loginUrl . '">Log in with Facebook</a>';
}