<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Load DB
include '../db.php';


// Fetch user tokens
$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id = 2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$accessToken = $row['access_token'];
$g_ref_tok = $row['g_refresh_token'];

// Input lead
$user_phone = $_POST['phone'] ?? '';
$user_email = $_POST['email'] ?? '';
$proj = $_POST['project'] ?? '';
/*
$user_email = 'test@gmail.com';
$user_phone = '912345678901';
$proj = 'rwd-waterfront';
*/
$user_phone = preg_replace('/\D/', '', $user_phone);

// If number is only 10 digits, add '91' prefix
if (strlen($user_phone) === 10) {
    $user_phone = '91' . $user_phone;
}

$hashedLead = [[
    hash('sha256', strtolower(trim($user_email))),
    hash('sha256', strtolower(trim($user_phone)))
]];
$payload = [
    'payload' => [
        'schema' => ['EMAIL', 'PHONE'],
        'data' => $hashedLead
    ],
    'access_token' => $accessToken
];

if($proj === 'rwd-vista') {
    $audienceId = '120232470656450076';
} else if($proj === 'rwd-waterfront') {
    $audienceId = '120233720489650204';
}

$url = "https://graph.facebook.com/{$api_ver}/{$audienceId}/users";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "[❌] Meta upload error for Audience $audienceId: " . curl_error($ch) . "\n";
} else {
    echo "[✅] Meta Audience $audienceId | HTTP $httpCode\n$response\n\n";
}