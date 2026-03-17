<?php

//$spreadsheetId = '1bhXob-nRCXiBu2l9bDBh4Zt8zVFolCbYCSCtK2thmyQ';
//append_to_sheet($uId,$tbl_id, $leadV,$spreadsheetId); //
  
function append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab) {
  
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
  
    try {
        
        $alphabet = range('A', 'Z');
        $endRange = $alphabet[(count($leadV[0])-1)];
        if($sheetTab!='') { $sheetTab=$sheetTab.'!'; }
        $range = $sheetTab.'A1:'.$endRange.'1';
        $values = $leadV;
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ];
        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        //printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());
        
        $db->updateRes(json_encode($leadV),$result->getUpdates()->getUpdatedCells(),$spreadsheetId,$sheetTab);
        //return $result; 
        
    } catch(Exception $e) {
        if( 401 == $e->getCode() ) {
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
  
            $db->update_access_token($uId,json_encode($data));
  
            append_to_sheet($uId, $tbl_id, $leadV,$spreadsheetId,$sheetTab);
        } else {
            $db->updateRes(json_encode($leadV),$e->getMessage(),$spreadsheetId,$sheetTab);
            echo $e->getMessage(); //print the error just in case your data is not appended.
        }
    }
}