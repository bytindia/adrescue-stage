<?php

$client = new Google_Client();
  
$db = new DB();
  
$arr_token = (array) $db->get_access_token($uId);
    //echo $spreadsheetId = $db->getSheetId($uId,$tbl_id); exit;
$accessToken = array(
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
);
  
$client->setAccessToken($accessToken);
$service = new Google_Service_Sheets($client);


$body1 = new Google_Service_Sheets_Request();

$rangel = new Google_Service_Sheets_GridRange();

$request1 = new Google_Service_Sheets_MergeCellsRequest();

$cellFormat = new Google_Service_Sheets_CellFormat();

$cellData = new Google_Service_Sheets_CellData();

$rowData = new Google_Service_Sheets_RowData();

$request2 = new Google_Service_Sheets_UpdateCellsRequest();

$body2 = new Google_Service_Sheets_Request();

$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();

$body3 = new Google_Service_Sheets_ValueRange();
//$rangel, $request1, $request2, $body2, $cellFormat, $cellData, $rowData

$bgColor = new Google_Service_Sheets_Color();