<?php

function requestPartnerAccess($clientBusinessId) {
    $agencyBusinessId = "866116510072687";
    $systemUserToken = "REDACTED_FB_TOKEN";

    $url = "https://graph.facebook.com/v19.0/$agencyBusinessId/client_businesses";

    $params = [
        'business' => $clientBusinessId,
        'permitted_tasks' => json_encode(['ADVERTISE','ANALYZE']),
        'access_token' => $systemUserToken
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
