<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
require_once $dirPath . 'db.php';

$uId = 2;

/* ==========================================================
   1) FETCH USER + TOKENS
========================================================== */
$query = "SELECT tbl_id, g_token, g_refresh_token 
          FROM users WHERE tbl_id = 2";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$acc_token_g     = $row['g_token'];          // stored access token
$g_refresh_token = $row['g_refresh_token'];  // stored refresh token

// ---- PUT YOUR GOOGLE CREDENTIALS HERE ----
$googleClientId     = "1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com";
$googleClientSecret = getenv('GOOGLE_CLIENT_SECRET');
// ------------------------------------------

/* ==========================================================
   2) DATE RANGE
========================================================== */
$startDateInput = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDateInput   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

if ($startDateInput === '' || $endDateInput === '') {
    $today        = new DateTime('today');
    $firstOfMonth = (clone $today)->modify('first day of this month');
    $startDate    = $firstOfMonth->format('Y-m-d');
    $endDate      = $today->format('Y-m-d');
} else {
    $startDate = $startDateInput;
    $endDate   = $endDateInput;
}

/* ==========================================================
   3) HELPER FUNCTIONS
========================================================== */

function refreshGoogleToken($clientId, $clientSecret, $refreshToken) {

    $url = "https://oauth2.googleapis.com/token";

    $postData = [
        "client_id"     => $clientId,
        "client_secret" => $clientSecret,
        "refresh_token" => $refreshToken,
        "grant_type"    => "refresh_token"
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($postData)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['access_token'] ?? null;
}

function callGA4($propertyId, $accessToken, $requestBody) {

    $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $accessToken
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($requestBody)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return [
            "error" => true,
            "status" => $httpCode,
            "response" => $response
        ];
    }

    return json_decode($response, true);
}

/* ==========================================================
   4) CHECK + REFRESH TOKEN IF NEEDED
========================================================== */

$propertyId = "524077779";

// Test call
$resultTest = callGA4($propertyId, $acc_token_g, [
    "dateRanges" => [["startDate" => $startDate, "endDate" => $endDate]],
    "metrics" => [["name" => "sessions"]]
]);

if (isset($resultTest['error']) && $resultTest['status'] == 401) {

    $newAccessToken = refreshGoogleToken(
        $googleClientId,
        $googleClientSecret,
        $g_refresh_token
    );

    if (!$newAccessToken) {
        die("❌ Failed to refresh Google token.");
    }

    // Save new token in DB
    $update = "UPDATE users 
               SET g_token = '" . mysqli_real_escape_string($conn, $newAccessToken) . "' 
               WHERE tbl_id = 2";

    mysqli_query($conn, $update);

    $acc_token_g = $newAccessToken;
}

$accessToken = $acc_token_g;

$requestTraffic = [
    "dateRanges" => [["startDate" => $startDate, "endDate" => $endDate]],
    "metrics" => [
        ["name" => "sessions"],               // index 0
        ["name" => "engagedSessions"],        // index 1
        ["name" => "engagementRate"],         // index 2
        ["name" => "userEngagementDuration"]  // index 3 (Seconds)
    ]
];

$resultTraffic = callGA4($propertyId, $accessToken, $requestTraffic);
$m = $resultTraffic['rows'][0]['metricValues'] ?? [];

$totalSessions   = (int) ($m[0]['value'] ?? 0);
$engagementRate  = round((($m[2]['value'] ?? 0) * 100), 2);

// FIX: userEngagementDuration is already in SECONDS
$userEngagementSeconds = (float) ($m[3]['value'] ?? 0); 

// --- AVERAGE ENGAGEMENT TIME PER SESSION ---
$avgEngagementTimePerSession = ($totalSessions > 0)
    ? round($userEngagementSeconds / $totalSessions, 2)
    : 0;

$avgEngagementFormatted = gmdate(
    ($avgEngagementTimePerSession >= 3600) ? "H:i:s" : "i:s",
    (int)$avgEngagementTimePerSession
);

$trafficData = [
    "totalSessions" => $totalSessions,
    "avgEngagementTimePerSession_sec" => $avgEngagementTimePerSession,
    "avgEngagementTimePerSession_fmt" => $avgEngagementFormatted,
    "engagementRate" => $engagementRate
];

/* ==========================================================
   6) TOP 5 CITIES
========================================================== */
$requestCities = [
    "dateRanges" => [["startDate" => $startDate, "endDate" => $endDate]],
    "dimensions" => [["name" => "city"]],
    "metrics" => [
        ["name" => "activeUsers"],
        ["name" => "userEngagementDuration"], // Seconds
        ["name" => "engagementRate"]
    ],
    "orderBys" => [["metric" => ["metricName" => "activeUsers"], "desc" => true]],
    "limit" => 5
];

$resultCities = callGA4($propertyId, $accessToken, $requestCities);
$cityData = [];

foreach (($resultCities['rows'] ?? []) as $row) {
    $city = $row['dimensionValues'][0]['value'] ?? "(not set)";
    $m = $row['metricValues'];

    $activeUsers = (int) ($m[0]['value'] ?? 0);
    
    // FIX: userEngagementDuration is already in SECONDS
    $userEngagementSec = (float) ($m[1]['value'] ?? 0); 

    // --- AVERAGE ENGAGEMENT TIME PER ACTIVE USER ---
    $avgEngagementPerUser = ($activeUsers > 0)
        ? round($userEngagementSec / $activeUsers, 2)
        : 0;

    $engagementRateCity = round((($m[2]['value'] ?? 0) * 100), 2);

    $cityData[$city] = [
        "activeUsers" => $activeUsers,
        "avgEngagementPerActiveUser_sec" => $avgEngagementPerUser,
        "engagementRate" => $engagementRateCity,
        "LP_Lead" => 0
    ];
}

/* ==========================================================
   7) LP_Lead BY CITY (SEPARATE CALL)
========================================================== */

$requestLeads = [
    "dateRanges" => [
        [
            "startDate" => $startDate,
            "endDate"   => $endDate
        ]
    ],
    "dimensions" => [
        ["name" => "city"],
        ["name" => "eventName"]
    ],
    "metrics" => [
        ["name" => "eventCount"]
    ],
    "dimensionFilter" => [
        "filter" => [
            "fieldName" => "eventName",
            "stringFilter" => [
                "value" => "Lead",
                "matchType" => "EXACT"
            ]
        ]
    ]
];

$resultEvents = callGA4($propertyId, $accessToken, $requestLeads);

foreach (($resultEvents['rows'] ?? []) as $row) {

    $city = $row['dimensionValues'][0]['value'] ?? "(not set)";
    $lpLeadCount = (int) ($row['metricValues'][0]['value'] ?? 0);

    if (isset($cityData[$city])) {
        $cityData[$city]['LP_Lead'] = $lpLeadCount;
    }
}

/* ==========================================================
   8) FINAL STRUCTURED OUTPUT
========================================================== */

$finalOutput = [
    "traffic" => $trafficData,
    "topCities" => $cityData
];

header('Content-Type: application/json');
echo json_encode($finalOutput, JSON_PRETTY_PRINT);
exit;
