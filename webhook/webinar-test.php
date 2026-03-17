<?php
function d($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}   
/******************************************************
 *  ZOOM: GET WEBINARS (LAST 3 DAYS) + PARTICIPANTS
 *  Single-file working script
 ******************************************************/

// 👉 Replace with your Zoom App Credentials
$clientId     = "t3A3FJ93R8WXM6r0bpPN1Q";
$clientSecret = "KakpYI6gnNNsLaEYcU9CGeqe28zLSj9p";
$accountId    = "zC4gFNdSTMSa9QRB37i9-Q";

// -----------------------------------------------------
// 1️⃣ FUNCTION: GET ZOOM ACCESS TOKEN (Server-to-Server)
// -----------------------------------------------------
function getZoomAccessToken($clientId, $clientSecret, $accountId) {

    $auth = base64_encode("$clientId:$clientSecret");

    $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id=$accountId";

    $headers = [
        "Authorization: Basic $auth",
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['access_token'])) {
        echo "❌ Failed to get access token:\n";
        print_r($data);
        exit;
    }

    return $data['access_token'];
}

// -----------------------------------------------------
// 2️⃣ FUNCTION: GENERIC ZOOM API REQUEST
// -----------------------------------------------------
function zoomAPI($url, $token) {

    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$httpCode, json_decode($response, true)];
}

// -----------------------------------------------------
// 3️⃣ GET VALID ACCESS TOKEN
// -----------------------------------------------------
$token = getZoomAccessToken($clientId, $clientSecret, $accountId);


// -----------------------------------------------------
// 4️⃣ GET WEBINARS FROM LAST 3 DAYS
// -----------------------------------------------------
//$from = date("Y-m-d", strtotime("-2 days"));
$from = date("Y-m-d");
$to   = date("Y-m-d");

$userId = "me"; // or your Zoom user email

$webinarUrl = "https://api.zoom.us/v2/users/$userId/webinars?from=$from&to=$to";

list($status, $webinars) = zoomAPI($webinarUrl, $token);

echo "<pre>";

if ($status != 200) {
    echo "❌ Error fetching webinars:\n";
    print_r($webinars);
    exit;
}

// If no webinars
if (empty($webinars['webinars'])) {
    echo "No webinars found in the last 3 days.\n";
    exit;
}

echo "✅ Webinars from last 3 days:\n\n";

foreach ($webinars['webinars'] as $w) {

    $webinarId = $w['id'];
    echo "-------------------------------------------\n";
    echo "Webinar ID:  " . $webinarId . "\n";
    echo "Topic:       " . $w['topic'] . "\n";
    echo "Start Time:  " . $w['start_time'] . "\n";

    // -------------------------------------------------
    // 5️⃣ GET PARTICIPANTS FOR THIS WEBINAR
    // -------------------------------------------------
    $pUrl = "https://api.zoom.us/v2/report/webinars/$webinarId/participants?page_size=300";

    list($pStatus, $participants) = zoomAPI($pUrl, $token);

    // -------------------------------------------------
    // 6️⃣ GET REGISTRANTS FOR THIS WEBINAR (for phone numbers)
    // -------------------------------------------------
    $regUrl = "https://api.zoom.us/v2/webinars/$webinarId/registrants?page_size=300";
    list($regStatus, $registrants) = zoomAPI($regUrl, $token);
    
    // Create a lookup array for registrants by email
    $registrantLookup = [];
    if ($regStatus == 200 && !empty($registrants['registrants'])) {
        foreach ($registrants['registrants'] as $reg) {
            $email = strtolower(trim($reg['email'] ?? ''));
            if ($email) {
                $registrantLookup[$email] = $reg;
            }
        }
    }

    //d($participants);
    if ($pStatus == 200) {
        echo "Participants:\n";

        if (!empty($participants['participants'])) {
            foreach ($participants['participants'] as $p) {
                $name = $p['name'] ?? 'N/A';
                $email = $p['user_email'] ?? $p['email'] ?? 'N/A';
                $phone = 'N/A';
                
                // Try to get phone from participant data first
                if (isset($p['phone'])) {
                    $phone = $p['phone'];
                } elseif (isset($p['phone_number'])) {
                    $phone = $p['phone_number'];
                } elseif (isset($p['phoneNumber'])) {
                    $phone = $p['phoneNumber'];
                } else {
                    // Try to get phone from registrant data
                    $emailKey = strtolower(trim($email));
                    if (isset($registrantLookup[$emailKey])) {
                        $reg = $registrantLookup[$emailKey];
                        if (isset($reg['phone'])) {
                            $phone = $reg['phone'];
                        } elseif (isset($reg['phone_number'])) {
                            $phone = $reg['phone_number'];
                        } elseif (isset($reg['phoneNumber'])) {
                            $phone = $reg['phoneNumber'];
                        }
                    }
                }
                
                echo " - " . $name . " | " . $email . " | " . $phone . "\n";
            }
        } else {
            echo " - No participants found.\n";
        }

    } else {
        echo "❌ Error fetching participants:\n";
        print_r($participants);
    }
   // exit;
    echo "\n";
}

echo "</pre>";
