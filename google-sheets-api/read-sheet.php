<?php

function read_sheet($uId, $spreadsheetId, $sheetTab) {
  
    $client = new Google_Client();
    $db = new DB();

    // Fetch the access token from the database
    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = array(
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
    );

    $client->setAccessToken($accessToken);
    $service = new Google_Service_Sheets($client);

    try {
        // Fetch spreadsheet metadata
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();
        $sheetExists = false;

        // Check if the sheetTab exists in the list of sheets
        foreach ($sheets as $sheet) {
            if ($sheet['properties']['title'] === $sheetTab) {
                $sheetExists = true;
                break;
            }
        }

        // If the sheet exists, fetch the data
        if ($sheetExists) {
            $range = $sheetTab;
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            return array('data' => $response->getValues());
        } else {
            // Handle the case when the sheet does not exist
            return array('error' => 'Sheet "' . $sheetTab . '" does not exist.');
        }

    } catch(Exception $e) {
        if (401 == $e->getCode()) {
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
  
            $db->update_access_token($uId, json_encode($data));
  
            // Retry fetching the sheet after refreshing the token
            return read_sheet($uId, $spreadsheetId, $sheetTab);
        } else {
            echo $e->getMessage(); // Print the error if the sheet can't be accessed
        }
    }
}
?>
