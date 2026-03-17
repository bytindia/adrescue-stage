<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '../google-sheets-api/vendor/autoload.php';
require_once '../google-sheets-api/class-db.php';
require_once '../google-sheets-api/config.php';
include '../google-sheets-api/insert-row.php';

$spreadsheetId = '1bvh-dEsuPnZzW0NFAeVH8Xxe_tNdLLVVOBnKSsMbOI4';
$sheetTab = 'Leads';

if(isset($_POST)){

    $leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'], '', date('d-m-Y, h:i a'),$_POST['project'], 'Google']];
    append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);


    $spreadsheetId ='1E4g6R0NTx_9AKO3oJn3Uqisx9c8d8vNpBRkdrxxQqog';

    $formName_s = strtolower($_POST['project']);
	
    if (strpos($formName_s, strtolower('corniche')) !== FALSE) { $sheetTab = 'Corniche'; }
	else if (strpos($formName_s, strtolower('ibis')) !== FALSE) { $sheetTab = 'Ibis'; }
	else if (strpos($formName_s, strtolower('spotlight')) !== FALSE) { $sheetTab = 'Spotlight'; }
	else if (strpos($formName_s, strtolower('gc')) !== FALSE) { $sheetTab = 'GC'; }
    else if (strpos($formName_s, strtolower('corridor')) !== FALSE) { $sheetTab = 'GC'; }
	else  { $sheetTab = 'RWD'; }

    $leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'], '', date('d-m-Y, h:i a'),$_POST['form_name'],$_POST['source'],'Google']];
	append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);
}
