<?php

//$spreadsheetId = '1bhXob-nRCXiBu2l9bDBh4Zt8zVFolCbYCSCtK2thmyQ';
//update_color($uId,$tbl_id, $leadV,$spreadsheetId); //
  
function update_color($uId, $tbl_id, $spreadsheetId, $sheetTab, $sheetId, $stRow, $enRow, $startCol, $endCol, $bg) {
  
    global  $service, $batchUpdateRequest, $bgColor; 
    
    
    // Range
        /*$rangel = new Google_Service_Sheets_GridRange();
        $rangel->setStartRowIndex(0);
        $rangel->setEndRowIndex(1);
        $rangel->setStartColumnIndex(7);
        $rangel->setEndColumnIndex(11);
        $rangel->setSheetId(0); */
        
        $bgColor->setRed(20);     $bgColor->setGreen(50);     $bgColor->setBlue(50);  $bgColor->setAlpha(50);
        //$bgColor->setRed(200);     $bgColor->setGreen(10);     $bgColor->setBlue(.5);  $bgColor->setAlpha(0.8);
        
        
// set colour to a medium gray
$r = 20; $g = $b = 50;
$a = 50;

// define range
$myRange = [
    'sheetId' => 0, // IMPORTANT: sheetId IS NOT the sheets index but its actual ID
    'startRowIndex' => $stRow,
    'endRowIndex' => $stRow+1,
    //'startColumnIndex' => 0, // can be omitted because default is 0
    'endColumnIndex' => $endCol,
];

// define the formatting, change background colour and bold text
$format = [
    'backgroundColor' => [
        'red' => $r,
        'green' => $g,
        'blue' => $b,
        'alpha' => $a,
    ],
    'textFormat' => [
      'bold' => true
    ]
];

// build request
$requests = [
    new Google_Service_Sheets_Request([
        'repeatCell' => [
            'fields' => 'userEnteredFormat.backgroundColor, userEnteredFormat.textFormat.bold',
            'range' => $myRange,
            'cell' => [
                'userEnteredFormat' => $format,
            ],
        ],
    ])
];

// add request to batchUpdate    
$batchUpdateRequest->setRequests([$requests]);

// run batchUpdate
$result = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

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
  
            update_color($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, $stRow,$startCol,$endCol);
        } else {
            //$db->updateRes(json_encode($leadV),$e->getMessage(), $spreadsheetId, $sheetTab);
            echo $e->getMessage(); //print the error just in case your data is not appended.
        } */
    } 
}
