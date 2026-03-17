<?php
include 'db.php';
include 'config.php';
$app_secret = getenv('FB_APP_SECRET');
$app_id = '594832897646145';

function curl_get_file_contents($URL)
            {
                    $c = curl_init();
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($c, CURLOPT_URL, $URL);
                    $contents = curl_exec($c);
                    curl_close($c);
            
                    if ($contents) return $contents;
                    else return FALSE;
             }
$access_token = trim($access_token);

echo $tok_url = "https://graph.facebook.com/debug_token?input_token={$access_token}&access_token={$app_id}|{$app_secret}";

if ($access_token != '') {

    $tok_req = curl_get_file_contents($tok_url);
    d($tok_req); exit;
    if ($tok_req === FALSE) {
        // cURL failed
        $pg = 'cron-fb-report';      
        include 'email/mail-error.php';
        exit;
    }

    $data = json_decode($tok_req, true);

    // If token invalid
    if (!isset($data['data']['is_valid']) || $data['data']['is_valid'] !== true) {
        $pg = 'cron-fb-report';      
        include 'email/mail-error.php';
        exit;
    }

    // token is valid — continue
}

