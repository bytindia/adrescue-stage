<?php
$post = array(
    "client_id"           => "76e108578198485c8d0d3f2771f9f7c7",
    "client_secret"       => getenv('TABOOLA_CLIENT_SECRET'),
    "grant_type"          => "client_credentials",
);

$ch = curl_init();

curl_setopt($ch, CURLOPT_COOKIESESSION, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "App Client" );
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60 );
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/x-www-form-urlencoded',
      'Accept: application/json',
));

curl_setopt($ch, CURLOPT_URL,"https://backstage.taboola.com/backstage/oauth/token");
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_AUTOREFERER, 0);

$result=curl_exec ($ch);

$info = curl_getinfo($ch);
$response = json_decode($result, true);

if ($info['http_code'] == 200) {
    $taboola_tok = $response['access_token'];
}