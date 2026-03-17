<?php
$autoloadPaths = [
    __DIR__ . '/../auth-vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/home/digitalb2k/stage.adrescue.in/auth-vendor/autoload.php',
];
foreach ($autoloadPaths as $p) { if (file_exists($p)) { require_once $p; break; } }

$client = new Google_Client();

$client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
    $client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));

$client->addScope('https://www.googleapis.com/auth/gmail.readonly');
$client->addScope('https://www.googleapis.com/auth/gmail.modify');

$tokenPath = __DIR__.'/gmail-token.json';

if(!file_exists($tokenPath)){
    die("Token file not found.");
}

$token = json_decode(file_get_contents($tokenPath), true);

$client->setAccessToken($token);

/* AUTO REFRESH */
if ($client->isAccessTokenExpired()) {

    if ($client->getRefreshToken()) {

        $client->fetchAccessTokenWithRefreshToken(
            $client->getRefreshToken()
        );

        file_put_contents(
            $tokenPath,
            json_encode($client->getAccessToken())
        );
    } else {
        die("Refresh token missing.");
    }
}

/* START WATCH */

$gmail = new Google_Service_Gmail($client);

$request = new Google_Service_Gmail_WatchRequest();

$request->setTopicName(
    'projects/bytauth/topics/gmail-webhook-topic'
);

$response = $gmail->users->watch('me', $request);

echo "<pre>";
print_r($response);
echo "</pre>";
