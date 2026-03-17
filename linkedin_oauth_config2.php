<?php
//session_start();

 
//Linkedin API PHP Library includes
require_once 'vendor-linkedin/autoload.php';
 
// Fill CLIENT ID, CLIENT SECRET ID, REDIRECT URI from linkedin
 $client_id = '86qvqy176u7bqs';
 $client_secret = getenv('LINKEDIN_CLIENT_SECRET_2');
 
 $redirect_uri = $siteURL.'linkedin-login2.php';
 $linkedURL ="https://www.linkedin.com/oauth/v2/authorization";
 $linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
 
//Logout
if (isset($_REQUEST['logout'])) {
  unset($_SESSION['access_token']);
  $linkedIn->clearStorage();
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL)); //redirect user back to page
}
 
//Set Access Token to make Request
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {	
  $linkedIn->setAccessToken($_SESSION['access_token']); 
}
 echo $linkedIn->hasError();
//play with user data
if ($linkedIn->isAuthenticated()) 
{
	$_SESSION['access_token'] = (string) $linkedIn->getAccessToken();
  /*$userData = $linkedIn->get('v1/people/~:(firstName,lastName,headline,id,emailAddress)');
  d($userData);
  
  //$adData = $linkedIn->get('v2/adAccountsV2?q=search&search.type.values[0]=BUSINESS&search.type.values[1]=ENTERPRISE&search.status.values[0]=ACTIVE&search.status.values[1]=CANCELED&sort.field=ID&sort.order=DESCENDING');
  //d($adData['elements']);
  
   $adReport = $linkedIn->get('v2/adAnalyticsV2?accounts[0]=urn:li:sponsoredAccount:504525377&q=analytics&pivot=ACCOUNT&timeGranularity=ALL&dateRange.start.month=2&dateRange.start.day=1&dateRange.start.year=2019&dateRange.end.month=2&dateRange.end.day=20&dateRange.end.year=2019&fields=oneClickLeads,externalWebsiteConversions,likes,clicks,shares,totalEngagements,actionClicks,impressions,comments,dateRange,costInLocalCurrency,costInUsd');
   
 // $adReport = $linkedIn->get('v2/adAnalyticsV2?accounts[0]=urn:li:sponsoredAccount:504389587&q=analytics&pivot=ACCOUNT&timeGranularity=ALL&dateRange.start=1/1/2019&dateRange.end=1/15/2019');
  //$adReport = $linkedIn->get('v2/adAccounts/504525377');
  
  d($adReport);
  
  $adCamp = $linkedIn->get('v2/adAnalyticsV2?accounts[0]=urn:li:sponsoredAccount:504525377&q=statistics&pivots[0]=CAMPAIGN&timeGranularity=ALL&dateRange.start.month=2&dateRange.start.day=1&dateRange.start.year=2019&dateRange.end.month=2&dateRange.end.day=20&dateRange.end.year=2019');
  //$adReport = $linkedIn->get('v2/adAccounts/504525377');
  
  d($adCamp);
  
  $_SESSION['access_token'] = (string) $linkedIn->getAccessToken();
  
  //https://api.linkedin.com/v2/adAccountUsersV2?q=authenticatedUser */
} else {
  $scope = 'r_ads_reporting,r_ads,rw_ads,r_ads_leadgen_automation,r_events,r_organization_admin,r_liteprofile,r_organization_social,r_marketing_leadgen_automation'; //r_ads_leadgen_automation
	//or 
  $linkedInAuthUrl  =  $linkedIn->getLoginUrl(array('scope'=>$scope));
}
?>