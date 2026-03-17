<?php

//$spreadsheetId = '1bhXob-nRCXiBu2l9bDBh4Zt8zVFolCbYCSCtK2thmyQ';
//update_to_sheet($uId,$tbl_id, $leadV,$spreadsheetId); //
  
function update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, $rangeSt, $rowNo) {
    global $service, $body3; 
    
    /*
    // Range
$rangel = new Google_Service_Sheets_GridRange();
$rangel->setStartRowIndex(0);
$rangel->setEndRowIndex(1);
$rangel->setStartColumnIndex(0);
$rangel->setEndColumnIndex(5);
$rangel->setSheetId(0);

// Merge rows of "A1:E1".
$request1 = new Google_Service_Sheets_MergeCellsRequest();
$request1->setMergeType('MERGE_ROWS');
$request1->setRange($rangel);
$body1 = new Google_Service_Sheets_Request();
$body1->setMergeCells($request1);

// Change horizontalAlignment to "CENTER".
$cellFormat = new Google_Service_Sheets_CellFormat();
$cellFormat->setHorizontalAlignment('CENTER');
$cellData = new Google_Service_Sheets_CellData();
$cellData->setUserEnteredFormat($cellFormat);
$rowData = new Google_Service_Sheets_RowData();
$rowData->setValues([$cellData]);
$rows[] = $rowData;
$request2 = new Google_Service_Sheets_UpdateCellsRequest();
$request2->setRows($rows);
$request2->setFields('userEnteredFormat.horizontalAlignment');
$request2->setRange($rangel);
$body2 = new Google_Service_Sheets_Request();
$body2->setUpdateCells($request2);

$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
$batchUpdateRequest->setRequests([$body1, $body2]);

$response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest); */
    try {
        
        $alphabet = range($rangeSt, 'Z');
        $endRange = $alphabet[(count($leadV[0])-1)];
        if($sheetTab!='') { $sheetTab=$sheetTab.'!'; }
        echo $range = $sheetTab.''.$rangeSt.''.$rowNo; //.':'.$endRange.''.$rowNo;
        $values = $leadV;
       /* $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);*/
        $body3->setValues($values);
        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ];
        
        $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body3, $params);
        //$result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        

        //$result = json_decode($result,true);

        //$result->updatedCells;

       // printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());
        
       // $db->updateRes(json_encode($leadV),$result->getUpdates()->getUpdatedCells(),$spreadsheetId,$sheetTab);
        //return $result; 
        
        
    } catch(Exception $e) {
      /*  if( 401 == $e->getCode() ) {
            $refresh_token = $db->get_refersh_token($uId);
  
            $client = new GuzzleHttp\Client(['base_uri' => 'https://accounts.google.com']);
  
            $response = $client->request('POST', '/o/oauth2/token', [
                'form_params' => [
                    "grant_type" => "refresh_token",
                    "refresh_token" => $refresh_token,
                    "client_id" => GOOGLE_CLIENT_ID,
                    "client_secret" => GOOGLE_CLIENT_SECRET,
                ],
            ]);
  
            $data = (array) json_decode($response->getBody());
            $data['refresh_token'] = $refresh_token;
  
           // $db->update_access_token($uId,json_encode($data));
  
            update_range($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, $rowNo);
        } else {
            //$db->updateRes(json_encode($leadV),$e->getMessage(), $spreadsheetId, $sheetTab);
            echo $e->getMessage(); //print the error just in case your data is not appended.
        } */
    } 
}