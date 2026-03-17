<?php
//error_reporting(E_ALL); ini_set('display_errors', '1');
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

header('Content-Type: application/json');



/*
|--------------------------------------------------------------------------
| STEP 1: AUTH (replace with JWT validation)
|--------------------------------------------------------------------------
*/
$uid = 2; // 🔐 replace with getUserIdFromBearerToken()

/*
|--------------------------------------------------------------------------
| STEP 2: FETCH USER DETAILS
|--------------------------------------------------------------------------
*/
$userQuery = "
    SELECT 
        tbl_id,
        name,
        fb_id,
        g_id,
        access_token,
        g_token,
        g_refresh_token,
        g_mcc
    FROM users
    WHERE tbl_id = ?
";

$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $uid);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid user"
    ]);
    exit;
}

$user = $userResult->fetch_assoc();

/*
|--------------------------------------------------------------------------
| STEP 3: FETCH DASHBOARD ACCOUNTS
|--------------------------------------------------------------------------
*/
$accQuery = "
    SELECT 
        client_name,
        nic_name,
        fb_id,
        g_id,
        proj_name
    FROM dashboard_accounts
    WHERE uid = ?
      AND delete_status = 0
    ORDER BY tbl_id
";

$accStmt = $conn->prepare($accQuery);
$accStmt->bind_param("i", $uid);
$accStmt->execute();
$accResult = $accStmt->get_result();

$accounts = [];

while ($row = $accResult->fetch_assoc()) {
    $accounts[] = [
        "client_name"  => $row['client_name'],
        "nic_name"     => $row['nic_name'],
        "project_name" => $row['proj_name'],
        "facebook_ids" => !empty($row['fb_id'])
            ? array_map('trim', explode(',', $row['fb_id']))
            : [],
        "google_ids" => !empty($row['g_id'])
            ? array_map('trim', explode(',', $row['g_id']))
            : []
    ];
}

/*
|--------------------------------------------------------------------------
| STEP 4: FINAL JSON RESPONSE
|--------------------------------------------------------------------------
*/
$response = [
    "status" => true,
    "data" => [
        "user" => [
            "user_id" => $user['tbl_id'],
            "name"    => $user['name'],
            "facebook" => [
                "fb_id" => $user['fb_id'],
                "access_token" => $user['access_token']
            ],
            "google" => [
                "g_id" => $user['g_id'],
                "g_token" => $user['g_token'],
                "g_refresh_token" => $user['g_refresh_token'],
                "g_mcc" => $user['g_mcc']
            ]
        ],
        "accounts" => [
            "count" => count($accounts),
            "list"  => $accounts
        ]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
