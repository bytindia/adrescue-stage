<?php

$zoom_secret_token = "s1s7f_RySvuepqAOsvYj0Q";

// ---------------------------------------------------
// Utility: Verify Zoom Signature (for normal events)
// ---------------------------------------------------
function verify_zoom_signature($secretToken, $requestBody, $providedSignature)
{
    if (strpos($providedSignature, "v0=") !== 0) {
        return false;
    }

    $providedHash = substr($providedSignature, 3);
    $expectedHash = hash_hmac("sha256", $requestBody, $secretToken);

    return hash_equals($expectedHash, $providedHash);
}

// ---------------------------------------------------
// Step 1: Read body + headers
// ---------------------------------------------------
$rawData = file_get_contents("php://input");
$data    = json_decode($rawData, true);

$zoomSignature = $_SERVER["HTTP_X_ZOOM_SIGNATURE"] ?? "";

// Log all incoming requests
file_put_contents("zoom_log.txt", date("Y-m-d H:i:s") . " => " . $rawData . "\n", FILE_APPEND);

// ---------------------------------------------------
// Step 2: Handle URL Validation FIRST!!!
// ---------------------------------------------------
if (($data['event'] ?? '') === "endpoint.url_validation") {

    $plainToken = $data['payload']['plainToken'];
    $encryptedToken = hash_hmac("sha256", $plainToken, $zoom_secret_token);

    echo json_encode([
        "plainToken"     => $plainToken,
        "encryptedToken" => $encryptedToken
    ]);
    exit;
}

// ---------------------------------------------------
// Step 3: For ALL OTHER EVENTS verify signature
// ---------------------------------------------------
if (!verify_zoom_signature($zoom_secret_token, $rawData, $zoomSignature)) {
    http_response_code(401);
    echo "Invalid Zoom signature";
    exit;
}

// ---------------------------------------------------
// Step 4: Process webhook events
// ---------------------------------------------------

$event = $data["event"] ?? "";

switch ($event) {

    case "webinar.started":
        break;

    case "webinar.ended":
        break;

    case "webinar.participant_joined":
        break;

    case "webinar.participant_left":
        break;

    default:
        break;
}

http_response_code(200);
echo "OK";
?>
