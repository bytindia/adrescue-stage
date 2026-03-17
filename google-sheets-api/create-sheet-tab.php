<?php

//require_once 'config.php';


  
function create_spreadsheet_tab($uId, $tbl_id, $sheetId, $sheetName, $proj, $repId, $repMon) {
  
    $client = new Google_Client();
  
    $db = new DB();
  
    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = array(
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
    );
  
    $client->setAccessToken($accessToken);
  
    $service = new Google_Service_Sheets($client);
  
    try {
        
        if($sheetId=='') {
            $spreadsheet = new Google_Service_Sheets_Spreadsheet([
                'properties' => [
                    'title' => $sheetName
                ]
            ]);
            $spreadsheet = $service->spreadsheets->create($spreadsheet, [
                'fields' => 'spreadsheetId'
            ]);
            $sheetId = $spreadsheet->spreadsheetId;
        }
        
        //$db->addSheetId($uId,$spreadsheet->spreadsheetId,$tbl_id); */

        //$test = array(1,2);
        
        if($sheetName!=''){
                $body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                'requests' => array(
                    'addSheet' => array(
                        'properties' => array(
                            'title' => $sheetName               )
                        )
                    )
                ));
                $result1 = $service->spreadsheets->batchUpdate($sheetId, $body);
               // $tab_ids[$k] = (array) $result1->replies[0]->addSheet->properties->sheetId;
        }

        foreach($proj as $k => $v){
             $body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
            'requests' => array(
                'addSheet' => array(
                    'properties' => array(
                        'title' => $v.' - FB'                )
                    )
                )
            ));
            $result1 = $service->spreadsheets->batchUpdate($sheetId, $body);
            $tab_ids[$k] = (array) $result1->replies[0]->addSheet->properties->sheetId;
        }
        
        //exit;

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
  
            return create_spreadsheet_tab($uId, $tbl_id, $sheetId, $sheetName, $proj, $repId, $repMon);
        } else {
            echo $e->getMessage(); //print the error just in case your sheet is not created.
        }
    }
}
