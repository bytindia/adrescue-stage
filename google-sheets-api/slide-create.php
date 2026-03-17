<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once 'vendor/autoload.php';
require_once 'class-db.php';
require_once 'config.php';

function create_spreadsheet($uId, $tbl_id, $sheetName) {
  
    $client = new Google_Client();
  
    $db = new DB();
  
    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = array(
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
    );
  
    $client->setAccessToken($accessToken);
  
  // $service = new Google_Service_Sheets($client);
  
    $service = new Google_Service_Drive($client);

    
    // Create a new file
    $file = new Google_Service_Drive_DriveFile(array(
        'name' => 'PPTX Test Presentation',
        'mimeType' => 'application/vnd.google-apps.presentation'
    ));

    // Read Powerpoint pptx file
    $pptx = file_get_contents("samplepptx.pptx");

    // Declare opts params
    $optParams = array(
        'uploadType' => 'multipart',
        'data' => $pptx,
        'mimeType' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    );

    // Import pptx file as a Google Slide presentation
    $createdFile = $service->files->create($file, $optParams);

    // Print google slides id
    print "File id: " . $createdFile->id; exit; 

    try {
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => $sheetName
            ]
        ]);
        $spreadsheet = $service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
        printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);
        //$db->addSheetId($uId,$spreadsheet->spreadsheetId,$tbl_id);
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
  
            create_spreadsheet($uId, $tbl_id, $sheetName);
        } else {
            echo $e->getMessage(); //print the error just in case your sheet is not created.
        }
    }
}


//include 'create-sheet.php';
$uId= 2;
echo $sheetName = 'Prabhu - Test - '.rand(0,99);
$tbl_id= 'test';
create_spreadsheet($uId, $tbl_id, $sheetName);
$new = 1;