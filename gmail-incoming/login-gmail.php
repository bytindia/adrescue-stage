<?php
$autoloadPaths = [
    __DIR__ . '/../auth-vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/home/digitalb2k/stage.adrescue.in/auth-vendor/autoload.php',
];
foreach ($autoloadPaths as $p) { if (file_exists($p)) { require_once $p; break; } }

session_start();

$client = new Google_Client();

$client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
    $client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));

$client->setRedirectUri(
    'https://stage.adrescue.in/gmail-incoming/callback-g.php'
);

$client->setApplicationName('Gmail Integration');

$client->addScope('https://www.googleapis.com/auth/gmail.readonly');
$client->addScope('https://www.googleapis.com/auth/gmail.modify');

$client->setAccessType('offline');
$client->setPrompt('consent');

$auth_url = $client->createAuthUrl();

header('Location: ' . $auth_url);
exit;
