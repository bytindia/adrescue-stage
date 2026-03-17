<?php session_start();
/*$baseURL = 'https://adsninja.bytsocial.com/linkedin-login/';
$callbackURL = 'https://adsninja.bytsocial.com/linkedin-login/process.php';
$linkedinApiKey = '819hf4iznbxt3r';
$linkedinApiSecret = getenv('LINKEDIN_CLIENT_SECRET');
$linkedinScope = 'r_basicprofile r_emailaddress';*/
//$linkedinScope = 'r_basicprofile r_emailaddress rw_ads r_ads_reporting r_ads_leadgen_automation';

/*
 * Basic Site Settings and API Configuration
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'fb_ads');
define('DB_PASSWORD', getenv('DB_PASS'));
define('DB_NAME', 'fb_ads');
define('DB_USER_TBL', 'users_linkedin2');

// LinkedIn API configuration
define('LIN_CLIENT_ID', '819hf4iznbxt3r');
define('LIN_CLIENT_SECRET', getenv('LINKEDIN_CLIENT_SECRET'));
define('LIN_REDIRECT_URL', 'https://adsninja.bytsocial.com/linkedin-login/process.php');
define('LIN_SCOPE', 'r_liteprofile r_emailaddress'); //API permissions

// Start session
if(!session_id()){
   // session_start();
}

// Include the oauth client library
require_once 'LinkedIn/http.php';
require_once 'LinkedIn/oauth_client.php';