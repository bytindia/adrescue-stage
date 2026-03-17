<?php

function get_spreadsheet_modified_time($uId, $spreadsheetId) {
    $client = new Google_Client();
    $db = new DB();

    // Get access token
    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = [
        'access_token' => $arr_token['access_token'],
        'expires_in'   => $arr_token['expires_in'],
    ];

    $client->setAccessToken($accessToken);
    $service = new Google_Service_Drive($client);

    try {
        // ✅ Fetch full file metadata (no restricted fields)
        $file = $service->files->get($spreadsheetId);

        // Extract values safely
        $fileName = method_exists($file, 'getName') ? $file->getName() : $file->title;
        $utcTime  = method_exists($file, 'getModifiedTime') ? $file->getModifiedTime() : $file->modifiedDate;

        // Convert UTC → IST
        $datetime = new DateTime($utcTime);
        $datetime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $istTime = $datetime->format('d-m-y h:i a');

        // Last modifying user (if available)
        $lastModifiedBy = 'Unknown';
        if (method_exists($file, 'getLastModifyingUser') && $file->getLastModifyingUser()) {
            $lastModifiedBy = $file->getLastModifyingUser()->getDisplayName();
        } elseif (property_exists($file, 'lastModifyingUserName')) {
            $lastModifiedBy = $file->lastModifyingUserName;
        }

        return [
            'file_id'           => $file->getId(),
            'file_name'         => $fileName,
            'modified_time_utc' => $utcTime,
            'modified_time_ist' => $istTime,
            'last_modified_by'  => $lastModifiedBy
        ];

    } catch (Exception $e) {
        if ($e->getCode() == 401) {
            // Refresh token logic
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

            // Retry once with refreshed token
            return get_spreadsheet_modified_time($uId, $spreadsheetId);
        }

        return ['error' => $e->getMessage()];
    }
}
