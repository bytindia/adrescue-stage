<?php

//$spreadsheetId = '1bhXob-nRCXiBu2l9bDBh4Zt8zVFolCbYCSCtK2thmyQ';
//merge_rows($uId,$tbl_id, $leadV,$spreadsheetId); //
  
function clear_format($uId, $tbl_id, $spreadsheetId, $sheetId) {
  
    global $rangel,  $request2, $body2,  $service, $batchUpdateRequest; 
    
    /*
    $request = new \Google_Service_Sheets_UpdateCellsRequest([
        'updateCells' => [ 
            'range' => [
                'sheetId' => $sheetId
            ],
            'fields' => "*" //clears everything
        ]
      ]);
    $requests[] = $request; */

    $rangel->setSheetId($sheetId);
    $request2->setRange($rangel);
    $request2->setFields("*");
    $body2->setUpdateCells($request2);

    //$req3 = $request1->setRange($rangel);
    //$body1->setUnmergeCells($req3);
    $batchUpdateRequest->setRequests([$body2]);
    $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest); 

    /*$request2 = new \Google_Service_Sheets_UpdateCellsRequest([
        'unmergeCells' => [ 
            'range' => [
                'sheetId' => $sheetId
            ]
        ]
    ]);
    
    $requests2[] = $request2;
    $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
    $requestBody->setRequests($requests2);
    $response = $service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);*/


 /*
    $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
    $requestBody->setRequests($requests);
    $response = $service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);

    $request2 = new \Google_Service_Sheets_UpdateCellsRequest([
        'unmergeCells' => [ 
            'range' => [
                'sheetId' => $sheetId
            ]
        ]
      ]);
    $requests2[] = $request2;
    
   // $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
    $requestBody->setRequests($requests2);
    $response = $service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);*/
    
    return $response;

    try {
        
        /*$alphabet = range('A', 'Z');
        $endRange = $alphabet[(count($leadV[0])-1)];
        if($sheetTab!='') { $sheetTab=$sheetTab.'!'; }
        $range = $sheetTab.'A'.$stRow.':'.$endRange.''.$stRow;
        $values = $leadV;
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ]; */
        //$result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        //$result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        

        //$result = json_decode($result,true);

        //$result->updatedCells;

       // printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());
        
       // $db->updateRes(json_encode($leadV),$result->getUpdates()->getUpdatedCells(),$spreadsheetId,$sheetTab);
        //return $result; 
        
        
    } catch(Exception $e) {
       /* if( 401 == $e->getCode() ) {
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
  
            //$db->update_access_token($uId,json_encode($data));
  
            merge_rows($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, $stRow,$startCol,$endCol);
        } else {
            //$db->updateRes(json_encode($leadV),$e->getMessage(), $spreadsheetId, $sheetTab);
            echo $e->getMessage(); //print the error just in case your data is not appended.
        } */
    } 
}
