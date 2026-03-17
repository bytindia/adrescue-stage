<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'google-sheets-api/vendor/autoload.php';
require_once 'google-sheets-api/class-db.php';
require_once 'google-sheets-api/config.php';
include 'google-sheets-api/insert-row.php';

$spreadsheetId = '1SObX6G3_j6fyK5EZ1Z3jgeAgXF65KV4rZD0v0WkF-r4';
$sheetTab = 'FB_Leads';

if(isset($_POST)){

   // $leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'], '', date('d-m-Y, h:i a'),$_POST['project'], 'Google']];
    //append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);

    //$leadV = [[$_POST['name'],$_POST['email'],$_POST['phone'], '', date('d-m-Y, h:i a'),$_POST['form_name'],$_POST['source'],'Google',$_POST['project']]];
	//append_to_sheet(2,1, $leadV,$spreadsheetId,$sheetTab);
    $leadV = [[date('d/m/Y, H:i'), $_POST['name'], $_POST['email'], $_POST['phone'], '']];
    $sheet = append_to_sheet(2,1, $leadV,'1i8OgoRXem92XlwNv2IEiIr-zadfOo7QkEwQ4PcMzsVs','Studio FB');
}
