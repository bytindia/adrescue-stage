<?php

define('GOOGLE_CLIENT_ID', '1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
  
$config = [
    'callback' => 'https://stage.adrescue.in/google-sheets-api/callback.php',
    'keys'     => [
        'id' => GOOGLE_CLIENT_ID,
        'secret' => GOOGLE_CLIENT_SECRET
    ],
    'scope'    => 'https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/presentations https://www.googleapis.com/auth/drive.metadata.readonly',
    'authorize_url_parameters' => [
        'prompt' => 'consent', // only when you need a new refresh token
        'access_type' => 'offline'
    ]
];

  //'https://www.googleapis.com/auth/spreadsheets', 'https://www.googleapis.com/auth/presentations'
$adapter = new Hybridauth\Provider\Google( $config );
