<?php
 //ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); 
/**
 * Autoload files of https://github.com/google/google-api-php-client
 *
 */ 
require_once 'google-api-php-client/src/Google/autoload.php';
 
 
/**
 * If you install https://github.com/asimlqt/php-google-spreadsheet-client through composer
 * Then you can just do:
 * require 'vendor/autoload.php';
 *
 * If you just download the zip file of https://github.com/asimlqt/php-google-spreadsheet-client
 * Then, you need to load the following files:
 *
 */
require_once 'src/Google/Spreadsheet/ServiceRequestInterface.php';
require_once 'src/Google/Spreadsheet/DefaultServiceRequest.php';
require_once 'src/Google/Spreadsheet/Exception.php';
require_once 'src/Google/Spreadsheet/UnauthorizedException.php';
require_once 'src/Google/Spreadsheet/ServiceRequestFactory.php';
require_once 'src/Google/Spreadsheet/SpreadsheetService.php';
require_once 'src/Google/Spreadsheet/SpreadsheetFeed.php';
require_once 'src/Google/Spreadsheet/Spreadsheet.php';
require_once 'src/Google/Spreadsheet/WorksheetFeed.php';
require_once 'src/Google/Spreadsheet/Worksheet.php';
require_once 'src/Google/Spreadsheet/ListFeed.php';
require_once 'src/Google/Spreadsheet/ListEntry.php';
require_once 'src/Google/Spreadsheet/CellFeed.php';
require_once 'src/Google/Spreadsheet/CellEntry.php';
require_once 'src/Google/Spreadsheet/Util.php';
 
 
/**
 * AUTHENTICATE
 *
 */
// These settings are found on google developer console
const CLIENT_APP_NAME = 'Whatever name you want';
const CLIENT_ID       = '109683751008232558460';
const CLIENT_EMAIL    = 'spreedsheet@testing2-181303.iam.gserviceaccount.com';
const CLIENT_KEY_PATH = 'testing2-181303-75a69f2a181c.p12'; // PATH_TO_KEY = where you keep your key file
const CLIENT_KEY_PW   = 'notasecret';
 
$objClientAuth  = new Google_Client ();
$objClientAuth -> setApplicationName (CLIENT_APP_NAME);
$objClientAuth -> setClientId (CLIENT_ID);
$objClientAuth -> setAssertionCredentials (new Google_Auth_AssertionCredentials (
    CLIENT_EMAIL, 
    array('https://spreadsheets.google.com/feeds','https://docs.google.com/feeds'), 
    file_get_contents (CLIENT_KEY_PATH), 
    CLIENT_KEY_PW
));
$objClientAuth->getAuth()->refreshTokenWithAssertion();
$objToken  = json_decode($objClientAuth->getAccessToken());
$accessToken = $objToken->access_token;
 
 
/**
 * Initialize the service request factory
 */ 
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
 
$serviceRequest = new DefaultServiceRequest($accessToken);
ServiceRequestFactory::setInstance($serviceRequest);
 
 
/**
 * Get spreadsheet by title
 */
$spreadsheetTitle = 'Ads spend cash flow working sheet nov 2019';
$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
$spreadsheet = $spreadsheetFeed->getByTitle($spreadsheetTitle);

//print_r($spreadsheet);

$worksheetTitle = 'Sheet1'; // it's generally named 'Sheet1' 
$worksheetFeed = $spreadsheet->getWorksheets();
$worksheet = $worksheetFeed->getByTitle($worksheetTitle);
$listFeed = $worksheet->getListFeed();

//foreach ($listFeed->getEntries() as $entries) {
   // print_r($entries->getValues()); 
//}

$cellFeed = $worksheet->getCellFeed();
$cellFeed->editCell(1,1, "SNo");
$cellFeed->editCell(1,2, "Client Name");
$cellFeed->editCell(1,3, "Payment Received");
$cellFeed->editCell(1,4, "Total Spent");
$cellFeed->editCell(1,5, "Balance");

//$row = array('name'=>'John', 'age'=>25);
//$listFeed->insert($row);
//echo '<pre>';
//print_r($_POST);
//echo '</pre>';


$entries = $listFeed->getEntries();

$i=0	;
foreach ($_POST  as $k => $v) {
	if(isset($entries[$i])) {
		$listEntry = $entries[$i]; // 0 = 1st row (editing value of first row)
		$values = $listEntry->getValues(); 
		
		$values['sno'] = $v[0];
		$values['clientname'] = $v[1];
		$values['paymentreceived'] = $v[2];
		$values['totalspent'] = $v[3];
		$values['balance'] = $v[4];
		$listEntry->update($values);
		$i++;
	} else {
		$row = array('sno'=>$v[0], 'clientname'=>$v[1], 'paymentreceived'=>$v[2], 'totalspent'=>$v[3], 'balance'=>$v[4]);
		$listFeed->insert($row);
	}
}

?>