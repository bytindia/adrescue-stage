<?php
require_once __DIR__.'/auth-vendor/autoload.php';

session_start();

$client = new Google_Client();
//$client->setAuthConfigFile('client_secret_1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com.json');
$client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
    $client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri('https://stage.adrescue.in/callback-g.php');
    $client->setApplicationName('App Google Integration');
    $client->addScope("profile");
    $client->addScope("email");
    $client->addScope("https://www.googleapis.com/auth/adwords");
    $client->addScope("https://www.googleapis.com/auth/spreadsheets");
    $client->addScope("https://www.googleapis.com/auth/drive.metadata");
    $client->addScope("https://www.googleapis.com/auth/cloud-billing");
    $client->addScope("https://www.googleapis.com/auth/cloud-platform");
    $client->addScope("https://www.googleapis.com/auth/analytics.readonly");


    //make sure there is a refresh token in the request for offline access.
    $client->setApprovalPrompt('force');
    $client->setAccessType('offline');
$client->setRedirectUri('https://' . $_SERVER['HTTP_HOST'] . '/callback-g.php');
//$client->addScope('https://www.googleapis.com/auth/adwords');

if (!isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  //$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
  //$client->fetchAccessTokenWithAssertion($_GET['code']);
  $client->authenticate($_GET['code']);
 // print_r( $client->authenticate($_GET['code']));
   $_SESSION['access_token'] = $client->getAccessToken();
  $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/login-g.php';
  //exit;
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
