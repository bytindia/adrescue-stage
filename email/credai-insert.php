<?php
function d($d) {
echo '<pre>';
print_r($d);
echo '<pre>';
}
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

if(isset($_POST['user_first_name']) && isset($_POST['user_email'])) {

require_once 'vendor/autoload.php';

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/client_secret.json');
$client = new Google_Client;
$client->useApplicationDefaultCredentials();
 
$client->setApplicationName("Something to do with my representatives");
$client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
 
if ($client->isAccessTokenExpired()) {
    $client->refreshTokenWithAssertion();
}
 
$accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
ServiceRequestFactory::setInstance(
    new DefaultServiceRequest($accessToken)
);

$spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
   ->getSpreadsheetFeed()
   ->getByTitle('SalesNinja-Adroit');
 
// Get the first worksheet (tab)
$worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
$worksheet = $worksheets[0];

$listFeed = $worksheet->getListFeed();

	

if(isset($_POST['message']) && $_POST['message']!='') { $msg=$_POST['message']; } else { $msg=''; }
if(isset($_POST['created']) && $_POST['created']!='') { $create=$_POST['created']; } else { $create=date("Y/m/d G:i:s"); }	
//if(isset($_POST['phone']) && $_POST['phone']!='') { $create=$_POST['phone']; } else { $create=''; }

$listFeed->insert([
   'name' => $_POST['user_first_name'],
   'email' => $_POST['user_email'],
   'phone' => $_POST['phone'],
   'comments' => $msg,
   'created' => $create,
   'source' => $_POST['source'],
   'type' => $_POST['type']
]);

/** @var ListEntry 
foreach ($listFeed->getEntries() as $entry) {
  $representative[] = $entry->getValues();
}
*/
//d($representative);

}