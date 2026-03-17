<?php
session_start();
include 'db.php';
include 'config.php';

if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'r'));
if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'w'));
if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));


require_once  '/home/digitalb2k/stage.adrescue.in/vendor/autoload.php';

use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;



$fb = new Facebook([
  'app_id' => $app_id,
  'app_secret' => $app_secret,
]);

$helper = $fb->getRedirectLoginHelper();
if (isset($_GET['state'])) {
    $helper->getPersistentDataHandler()->set('state', $_GET['state']);
}
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
   //echo "You are logged in!<br>";
   $_SESSION['facebook_access_token'];
   
   $accessToken = $_SESSION['facebook_access_token'];
   $response = $fb->get('/me?locale=en_US&fields=name,email,first_name,picture,last_name,location', $accessToken); 
   $profile = $response->getGraphNode()->asArray();
  //d($profile); exit;
  //$_SESSION['userdata'] = $profile; 
   //print_r($profile);
  // $_SESSION['uid'] = $profile['id'];
   $_SESSION['fb_id'] = $profile['id'];
   $userRes = mysqli_query($conn, "select tbl_id from audit_users WHERE fb_id='".$profile['id']."'");				
   if(mysqli_num_rows($userRes)==0) {
					$userSql = "INSERT INTO audit_users (fb_id, name, email, fb_token, updated) VALUES ('".mysqli_real_escape_string($conn,$profile['id'])."', '".mysqli_real_escape_string($conn, $profile['name'])."', '".mysqli_real_escape_string($conn, $profile['email'])."', '".mysqli_real_escape_string($conn, $accessToken)."', now());"; 
					mysqli_query($conn, $userSql) or die(mysqli_error()); 					
					$redURL = 'adAccounts.php?pg=add-account.php';
				} else {					
					
					$userSql = "UPDATE audit_users SET name='".mysqli_real_escape_string($conn, $profile['name'])."', email='".mysqli_real_escape_string($conn, $profile['email'])."', fb_token='".mysqli_real_escape_string($conn, $accessToken)."', updated=now() WHERE fb_id='".$profile['id']."'";
					mysqli_query($conn, $userSql) or die(mysqli_error()); 
					
					$redURL = 'adAccounts.php?pg=index.php';
   }
   
   $userRes2 = mysqli_query($conn, "select name, tbl_id, fb_id from audit_users WHERE fb_id='".$profile['id']."'");	
   $getRw2 = mysqli_fetch_assoc($userRes2);
   //$lastId = $getRw['tbl_id']; 
   $_SESSION['name'] = $getRw2['name'];
   $_SESSION['uid'] = $getRw2['tbl_id'];
   $_SESSION['fb_id'] = $profile['id'];
   //$_SESSION['g_id'] = $getRw2['g_id'];
   
   echo "<script>window.location = '".$redURL."';</script>";
   exit;
 

} else {
  $permissions = ['email','ads_management','ads_read','read_insights','leads_retrieval','pages_manage_ads','pages_show_list','instagram_basic','instagram_manage_insights','pages_manage_ads','whatsapp_business_messaging'];
  $loginUrl = $helper->getLoginUrl($siteURL.'fb-login.php', $permissions);
   echo "<script>window.location = '".$loginUrl."';</script>"; exit;
  //echo '<a href="' . $loginUrl . '"><img src="assets/fbconnect.png" /></a>';
}