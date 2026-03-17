<?php exit;
date_default_timezone_set('Asia/Kolkata');
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// ---------------------------
// Load Configs & Sheet Data
// ---------------------------
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';

$uId = $tbl_id2 = 2;
$spreadsheetId = '1q-Pyr4Pqepv9wPo6T_5RqkM8iQjwRnlZY1b7Ad1bzX8';
$sheetTab = 'Feedback -FB';
$lead_data = read_sheet($uId, $spreadsheetId, $sheetTab);

// ---------------------------
// Meta Config
// ---------------------------
$dataset_id = "1933882573552940";
$access_token = "REDACTED_FB_TOKEN";
$api_ver = "v24.0";
$url = "https://graph.facebook.com/{$api_ver}/{$dataset_id}/events?access_token={$access_token}";

// ---------------------------
// Date Range Filter (edit as needed)
// ---------------------------
//$fromDate = strtotime("2025-10-01");
//$fromDate = strtotime("-3 days");

$fromDate = strtotime("-5 days");
$toDate   = strtotime("-5 days");


// ---------------------------
// Helper Functions
// ---------------------------
function cleanPhone($raw) {
    $ph = trim(strtolower($raw));
    $ph = preg_replace("/^p:/", "", $ph);
    $ph = preg_replace("/[\s\-\(\)]/", "", $ph);
    if (!str_starts_with($ph, "+91") && preg_match("/^[6-9][0-9]{9}$/", $ph)) {
        $ph = "+91" . $ph;
    }
    return $ph;
}

function categorizeFeedback($feedback) {
    $fb = strtolower(trim($feedback));
    $high = ["sitevisit","site visit","site visited","qualified","interested","follow up","details shared","detail given","ready to occupy","detail send on whatsapp","details given"];
    $med  = ["rnr","not connected","call back","low budget","duplicate","location issue","call not connected","call not connect","call back evening","call back later"];
    $low  = ["drop","unqualified","un qualified","not interested","not enquired","not inquired","switch off","out of service","not exist","disconnected the call","number does not exist","swo","temporarily out of service","budget not interested"];

    if (array_filter($low, fn($k)=>str_contains($fb, $k))) return "LOW";
    if (array_filter($med, fn($k)=>str_contains($fb, $k))) return "MEDIUM";
    if (array_filter($high, fn($k)=>str_contains($fb, $k))) return "HIGH";
    return "UNCATEGORIZED";
}

function calculateLeadScore($feedback, $category) {
    $fb = strtolower($feedback);
    if (preg_match("/site ?visit(ed)?/", $fb)) {
        return 100; // special rule
    }
    return match($category) {
        "HIGH" => 75,
        "MEDIUM" => 50,
        "LOW" => 0,
        default => 0
    };
}

// ---------------------------
// 1️⃣ Filter Leads by Date
// ---------------------------
$rows = $lead_data["data"] ?? [];
if (empty($rows) || count($rows) <= 1) {
    echo "⚠️ No leads found or invalid sheet data.";
    exit;
}

$rows = array_slice($rows, 1); // skip header
$filtered = [];

foreach ($rows as $lead) {
    // Basic validation
    if (empty($lead[1]) || empty($lead[2]) || empty($lead[4]) || empty($lead[8])) continue;

    $created_raw = trim($lead[4]);

    // Try parsing both formats — with and without comma
    $timestamp = DateTime::createFromFormat('d-m-Y, h:i a', $created_raw);
    if (!$timestamp) {
        $timestamp = DateTime::createFromFormat('d-m-Y h:i a', $created_raw);
    }

    // Skip if invalid or empty date
    if (!$timestamp) continue;

    $ts = $timestamp->getTimestamp();

    // Filter within date range
    if ($ts >= $fromDate && $ts <= $toDate + 86400) {
        $filtered[] = $lead;
    }
}
//d($lead_data );
echo "✅ Total filtered leads: " . count($filtered) . "<br>";
//exit;
// ---------------------------
// 2️⃣ Prepare All Events
// ---------------------------
$events = [];

foreach ($filtered as $lead) {
    $email = trim($lead[1]);
    $phone = trim($lead[2]);
    $feedback = trim($lead[8]);
    $created = trim($lead[4]);

    $cleanPh = cleanPhone($phone);
    $emHash = hash("sha256", strtolower($email));
    $phHash = hash("sha256", strtolower($cleanPh));
    $category = categorizeFeedback($feedback);
    $score = calculateLeadScore($feedback, $category);

    $events[] = [
        "event_name" => "Lead",
        "event_time" => time(),
        "action_source" => "system_generated",
        "user_data" => [
            "em" => [$emHash],
            "ph" => [$phHash],
        ],
        "custom_data" => [
            "event_source" => "crm",
            "lead_event_source" => "AdRescue - BYT",
            "feedback" => $feedback,
            "category" => $category,
            "lead_score" => $score,
            "created" => $created
        ]
    ];
}

// ---------------------------
// 3️⃣ Batch Send (≤100 leads/request)
// ---------------------------
$chunks = array_chunk($events, 100);
$batchCount = 0;

foreach ($chunks as $batch) {
    $batchCount++;
    $payload = json_encode(["data" => $batch]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ Batch {$batchCount}: CURL Error - {$error}<br>";
    } else {
        if (str_contains($response, '"events_received"')) {
            echo "✅ Batch {$batchCount}: Sent successfully<br>";
        } else {
            echo "⚠️ Batch {$batchCount}: Meta response: {$response}<br>";
        }
    }
}

echo "<br>🎯 Meta Bulk Sync Completed at " . date("d-m-Y h:i:s A") . "<br>";
?>
