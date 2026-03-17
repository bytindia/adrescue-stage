<?php
session_start();
$redirect = 'yes'; if(isset($_GET['cron'])) { $_SESSION['uid'] = 2; $redirect = 'no'; }
include 'db.php';
include 'config.php';

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

require_once 'vendor-linkedin/autoload.php';
$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";
$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id2, $client_secret2);

$tok = 'AQUID9ix_10D9ZHzNWq410-4KNqiJO5BlogKoyFEMZ78N_RgPiBIXbMdmbxhwwb-f1VMmrRvx_7LArrTom2dvZIPlDpVGh985fbXFqt1LpB7b0wYuj0yJAcTOeal_t-yWDd2UVWCvzQTWCRzsnX-mneDkKOtWRwZHM33Flz_humtfaaFyQiAu1_GMV_ydfQ5-8xKJ0gGJ4KZrgwGy5P3z-goFhrRYjoCU-VZL_dVVG6jePau7lj_ua5q0jYofUzLHPElz73_66B_EsikR6WQp5pLpNj4swuly5AixYTZnIrXZ8TeqsBwP4G5W1gf4xN3L78s7Z0qvIAqouT4s7dwpUzO8PTtLw';

if (isset($tok) && $tok) {
  $linkedIn->setAccessToken($tok); 
}


if ($linkedIn->isAuthenticated()) 
{
    if(isset($_GET['acc'])){ 
      $adData = $linkedIn->get('v2/adAccountsV2?q=search&search.status.values[0]=ACTIVE&search.status.values[1]=CANCELED&sort.field=ID&sort.order=DESCENDING');
      d($adData['elements']); exit;
    }
  

	  //$adData = $linkedIn->get('rest/adForms?q=account&account=urn:li:sponsoredAccount:511996384&totals=true&count=1&start=0');
    $options['headers']['LinkedIn-Version'] = '202307';
    $options['headers']['Content-Type'] = 'application/json';
    $options['headers']['X-Restli-Protocol-Version'] = '2.0.0';
    $options['body'] = '{
      "webhook": "https://stage.adrescue.in/webhook/webhook-linkedin3.php",
      "owner": {
          "sponsoredAccount": "urn:li:sponsoredAccount:504610084"
      },
      "leadType": "SPONSORED"
   }';
   $adData = $linkedIn->post('rest/leadNotifications', $options); d($adData);  exit;
  // $adData = $linkedIn->post('rest/leadNotifications', $options); d($adData);  exit;
    /* '{
      "webhook": "https://stage.adrescue.in/webhook/webhook-linkedin2.php",
      "owner": {
          "organization": "urn:li:organization:40664853"
      },
      "leadType": "SPONSORED"
   }';*/
    //$adData = $linkedIn->get('rest/leadNotificationUrls/sponsoredEntity=urn:li:sponsoredAccount:511996384&developerApplication=urn:li:developerApplication:217180573', $options);
     $adData = $linkedIn->post('rest/leadNotifications', $options);
    //$adData = $linkedIn->get('rest/leadNotifications?q=criteria&owner=(value:(organization:urn%3Ali%3Aorganization%3A520866471))&leadType=(leadType:SPONSORED)', $options);
    d($adData);  exit;
    /*
    $url = "https://api.linkedin.com/rest/leadNotifications";

    $headers = array('Content-Type: application/json', 'LinkedIn-Version: 202401','Authorization: Bearer '.$tok);

    $fields = '{
        "webhook": "https://stage.adrescue.in/webhook/webhook-linkedin.php",
        "data ": { "owner ": { "sponsoredAccount": "urn:li:sponsoredAccount:1462019" } },
        "leadType": "SPONSORED"
        
    }';*/

  

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.linkedin.com/rest/leadNotificationUrls',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "url": "https://stage.adrescue.in/webhook/webhook-linkedin.php",
    "key": {
        "developerApplication": "urn:li:developerApplication:217180573", 
        "sponsoredEntity": "urn:li:sponsoredAccount:40664853"
    },
    "status": "ACTIVE"
}',
  CURLOPT_HTTPHEADER => array(
    'LinkedIn-Version: 202307',
    'X-Restli-Protocol-Version: 2.0.0',
    'Authorization: Bearer AQUID9ix_10D9ZHzNWq410-4KNqiJO5BlogKoyFEMZ78N_RgPiBIXbMdmbxhwwb-f1VMmrRvx_7LArrTom2dvZIPlDpVGh985fbXFqt1LpB7b0wYuj0yJAcTOeal_t-yWDd2UVWCvzQTWCRzsnX-mneDkKOtWRwZHM33Flz_humtfaaFyQiAu1_GMV_ydfQ5-8xKJ0gGJ4KZrgwGy5P3z-goFhrRYjoCU-VZL_dVVG6jePau7lj_ua5q0jYofUzLHPElz73_66B_EsikR6WQp5pLpNj4swuly5AixYTZnIrXZ8TeqsBwP4G5W1gf4xN3L78s7Z0qvIAqouT4s7dwpUzO8PTtLw',
    'Content-Type: application/json',
    'Cookie: lidc="b=OB17:s=O:r=O:a=O:p=O:g=9457:u=406:x=1:i=1710936634:t=1711022430:v=2:sig=AQF2zQ8iqbqvy01m63RrOQxGkbnhpGn2"; bcookie="v=2&41e1f0fa-8ecf-485b-88d1-376605c2d6be"'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

    exit;


    $url = "https://api.linkedin.com/rest/leadNotificationUrls";

    $headers = array('Content-Type: application/json', 'LinkedIn-Version: 202307','Authorization: Bearer '.$tok);

    $fields = '{
        "url": "https://stage.adrescue.in/webhook/webhook-linkedin.php",
        "data ": { "key ": { "developerApplication": "urn:li:developerApplication:217180573", "sponsoredEntity": "urn:li:sponsoredAccount:504610084" } },
        "status": "ACTIVE"
        
    }';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
    $httpCode = curl_getinfo($curl , CURLINFO_HTTP_CODE); // this results 0 every time
    $response = curl_exec($curl);
    d($response);
}