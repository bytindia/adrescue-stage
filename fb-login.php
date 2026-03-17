<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
include 'config.php';

$app_id     = $app_id;
$app_secret = $app_secret;
$redirect   = $siteURL . "fb-login.php";   // Must match FB App settings

// -------------------------------------------------------------------
// STEP 1 — If Facebook redirected back WITH CODE → Exchange for token
// -------------------------------------------------------------------
if (isset($_GET['code'])) {

    $code = $_GET['code'];

    // Exchange code → access token
    $token_url = "https://graph.facebook.com/v20.0/oauth/access_token?"
        . "client_id={$app_id}"
        . "&redirect_uri=" . urlencode($redirect)
        . "&client_secret={$app_secret}"
        . "&code={$code}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  // SSL OK now
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        die("Token cURL Error: " . curl_error($ch));
    }
    curl_close($ch);

    $tokenData = json_decode($result, true);

    if (!isset($tokenData['access_token'])) {
        die("FB Error: Cannot get access token<br><pre>$result</pre>");
    }

    $accessToken = $tokenData['access_token'];
    $_SESSION['facebook_access_token'] = $accessToken;

    // -------------------------------------------------------------------
    // STEP 2 — Fetch user profile with access token
    // -------------------------------------------------------------------
    $profile_url = "https://graph.facebook.com/me?"
        . "fields=id,name,email,picture"
        . "&access_token={$accessToken}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $profile_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $user_response = curl_exec($ch);

    if (curl_errno($ch)) {
        die("Profile cURL Error: " . curl_error($ch));
    }
    curl_close($ch);

    $profile = json_decode($user_response, true);

    if (!isset($profile['id'])) {
        die("FB Error: Could not fetch profile<br><pre>$user_response</pre>");
    }

    // ---------------------- DATABASE LOGIC ---------------------------
    $fbid = mysqli_real_escape_string($conn, $profile['id']);
    $name = mysqli_real_escape_string($conn, $profile['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $profile['email'] ?? '');

    $q = mysqli_query($conn, "SELECT tbl_id FROM users WHERE fb_id='$fbid'");

    if (mysqli_num_rows($q) == 0) {
        // No record → Insert
        $sql = "INSERT INTO users (fb_id, name, email, access_token, created)
                VALUES ('$fbid', '$name', '$email', '$accessToken', NOW())";
        mysqli_query($conn, $sql);
        $redirectPage = "adAccounts.php";
    } else {
        // Update
        $sql = "UPDATE users SET 
                    name='$name',
                    email='$email',
                    access_token='$accessToken',
                    updated=NOW()
                WHERE fb_id='$fbid'";
        mysqli_query($conn, $sql);
        $redirectPage = "ad-accounts.php";
    }

    $u = mysqli_query($conn, "SELECT tbl_id,g_id FROM users WHERE fb_id='$fbid'");
    $uRow = mysqli_fetch_assoc($u);

    $_SESSION['uid']   = $uRow['tbl_id'];
    $_SESSION['fb_id'] = $fbid;
    $_SESSION['g_id']  = $uRow['g_id'];

    // Redirect user
    echo "<script>window.location='$redirectPage';</script>";
    exit;
}



// -------------------------------------------------------------------
// STEP 3 — FIRST TIME LOGIN → Redirect to Facebook Login URL
// -------------------------------------------------------------------

$permissions = [
    'email',
    'ads_management',
    'ads_read',
    'read_insights',
    'leads_retrieval',
    'pages_manage_ads',
    'pages_show_list',
    'instagram_basic',
    'instagram_manage_insights',
    'whatsapp_business_messaging',
    'whatsapp_business_management',
    'business_management'
];

$loginUrl = "https://www.facebook.com/v20.0/dialog/oauth?"
    . "client_id={$app_id}"
    . "&redirect_uri=" . urlencode($redirect)
    . "&scope=" . urlencode(implode(',', $permissions))
    . "&response_type=code"
    . "&state=" . md5(time());

header("Location: $loginUrl");
exit;

?>
