<?php
function read_tabs($uId, $tbl_id, $spreadsheetId) {
    $client = new Google_Client();
    $db = new DB();

    // Get access token
    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = [
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
    ];

    $client->setAccessToken($accessToken);
    $service = new Google_Service_Sheets($client);

    try {
        // Get all sheets
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        $sheetData = [];

        foreach ($sheets as $sheet) {
            $props = $sheet->getProperties();
            $sheetData[] = [
                'tbl_id' => $tbl_id,
                'sheet_id' => $props->getSheetId(),
                'sheet_name' => $props->getTitle(),
                'created' => date('Y-m-d H:i:s')
            ];
        }

        return $sheetData;

    } catch (Exception $e) {
        // Handle expired token
        if ($e->getCode() == 401) {
            $refresh_token = $db->get_refersh_token($uId);

            $guzzle = new GuzzleHttp\Client(['base_uri' => 'https://accounts.google.com']);
            $response = $guzzle->request('POST', '/o/oauth2/token', [
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

            // Retry
            return read_tabs($uId, $tbl_id, $spreadsheetId);
        } else {
            return ['error' => $e->getMessage()];
        }
    }
}
