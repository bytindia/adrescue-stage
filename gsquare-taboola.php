<?php 
$post_gsquare = array(
    "client_id"           => "d0ed8eb186d3422c83defbb56a0178cc",
    "client_secret"       => "3f4b80205a9c45f3bcfd8ea3dd164c00",
    "grant_type"          => "client_credentials",
);

$post_gsquare2 = array(
    "client_id"           => "ab4c07a63fdf4a89bff0daed8a9be426",
    "client_secret"       => "8a57d7def8a543d0944c86f1f729c012",
    "grant_type"          => "client_credentials",
);

function retTok($t) {
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
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($t));
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
        return $taboola_tok = $response['access_token'];
    } else {
        return '';
    }
}
