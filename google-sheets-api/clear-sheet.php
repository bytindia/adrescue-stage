<?php

//require_once 'config.php';

function clear_sheet($uId, $spreadsheetId, $sheetTab) {
  
   
            $client = new Google_Client();
            
            $db = new DB();

            $arr_token = (array) $db->get_access_token($uId);
           // print_r($arr_token); // exit;
           // echo $spreadsheetId = $db->getSheetId($uId,$tbl_id); exit;
            $accessToken = array(
                'access_token' => $arr_token['access_token'],
                'expires_in' => $arr_token['expires_in'],
            );

            $client->setAccessToken($accessToken);

            $service = new Google_Service_Sheets($client);

            try {
        
                $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                    'requests' => array(
                        'deleteDimension' => array(
                            'range' => array(
                                'sheetId' => $sheetTab, // the ID of the sheet/tab shown after 'gid=' in the URL
                                'dimension' => "ROWS",
                                'startIndex' => 1, // row number to delete
                                'endIndex' => 50
                            )
                        )    
                    )
                    ));
        
                    $result = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
                
                
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
          
                    //append_to_sheet($uId, $tbl_id, $leadV,$spreadsheetId,$sheetTab);
                    clear_sheet($uId, $spreadsheetId, $sheetTab);
                } else {
                    //$db->updateRes(json_encode($leadV),$e->getMessage(),$spreadsheetId,$sheetTab);
                    echo $e->getMessage(); //print the error just in case your data is not appended.
                }
            }



            

}