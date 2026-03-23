<?php
/**
 * AJAX Handler: Custom Audience Setup
 * Actions:
 *   - get_ad_accounts  : list Meta ad accounts for the user
 *   - get_audiences    : list custom audiences for a given ad_account_id
 *   - create_audience  : create a new Meta Custom Audience and return ID
 *
 * Reuses: db.php, $api_ver from db.php
 */
include 'db.php';
session_start();
Auth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uid    = (int)$_SESSION['uid'];

// Get user access token
$res  = mysqli_query($conn, "SELECT access_token FROM users WHERE tbl_id=$uid LIMIT 1");
$urow = mysqli_fetch_assoc($res);
$access_token = $urow['access_token'] ?? '';

header('Content-Type: application/json');

// ── Helper: CURL GET ─────────────────────────────────────────────────────────
function graph_get($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    return json_decode($out, true);
}

// ── Helper: CURL POST ────────────────────────────────────────────────────────
function graph_post($url, $fields) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    return json_decode($out, true);
}

// ── 1. Get Ad Accounts ────────────────────────────────────────────────────────
if ($action === 'get_ad_accounts') {
    // Pull from DB (adAccounts table already synced)
    $adAccRes = mysqli_query($conn, "SELECT account_id, name FROM adAccounts WHERE uid=$uid ORDER BY name ASC");
    $accounts = [];
    while ($r = mysqli_fetch_assoc($adAccRes)) {
        $accounts[] = $r;
    }
    echo json_encode(['success' => true, 'accounts' => $accounts]);
    exit;
}

// ── 2. Get Audiences for Ad Account ──────────────────────────────────────────
if ($action === 'get_audiences') {
    $ad_account = preg_replace('/[^0-9]/', '', $_POST['ad_account'] ?? '');
    if (empty($ad_account)) {
        echo json_encode(['success' => false, 'msg' => 'No ad account specified.']);
        exit;
    }
    $url = 'https://graph.facebook.com/' . $api_ver . '/act_' . $ad_account
         . '/customaudiences?fields=name,id,description,approximate_count_upper_bound&limit=200&access_token=' . $access_token;
    $res = graph_get($url);

    if (isset($res['error'])) {
        echo json_encode(['success' => false, 'msg' => $res['error']['message'] ?? 'API error']);
        exit;
    }
    $audiences = $res['data'] ?? [];
    echo json_encode(['success' => true, 'audiences' => $audiences]);
    exit;
}

// ── 3. Create New Audience ─────────────────────────────────────────────────────
if ($action === 'create_audience') {
    $ad_account  = preg_replace('/[^0-9]/', '', $_POST['ad_account'] ?? '');
    $aud_name    = trim($_POST['aud_name'] ?? '');
    $aud_desc    = trim($_POST['aud_desc'] ?? 'Created via AdRescue VLOOKUP Automation');

    if (empty($ad_account) || empty($aud_name)) {
        echo json_encode(['success' => false, 'msg' => 'Ad account and audience name are required.']);
        exit;
    }

    $url    = 'https://graph.facebook.com/' . $api_ver . '/act_' . $ad_account . '/customaudiences';
    $fields = [
        'name'            => $aud_name,
        'subtype'         => 'CUSTOM',
        'description'     => $aud_desc,
        'customer_file_source' => 'USER_PROVIDED_ONLY',
        'access_token'    => $access_token,
    ];
    $res = graph_post($url, $fields);

    if (isset($res['error'])) {
        echo json_encode(['success' => false, 'msg' => $res['error']['message'] ?? 'API error']);
        exit;
    }

    $audience_id   = $res['id'] ?? '';
    $audience_name = $aud_name;

    echo json_encode([
        'success'      => true,
        'audience_id'  => $audience_id,
        'audience_name'=> $audience_name,
        'msg'          => 'Audience created successfully!',
    ]);
    exit;
}

// ── 4. Save Audience Settings to leads_acc ────────────────────────────────────
if ($action === 'save_settings') {
    $leads_acc_id  = (int)($_POST['leads_acc_id'] ?? 0);
    $enabled       = (int)($_POST['cust_aud_enabled'] ?? 0);
    $ad_account    = preg_replace('/[^0-9]/', '', $_POST['cust_aud_ad_account'] ?? '');
    $audience_id   = preg_replace('/[^0-9]/', '', $_POST['cust_aud_id'] ?? '');
    $audience_name = mysqli_real_escape_string($conn, trim($_POST['cust_aud_name'] ?? ''));

    if ($leads_acc_id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Invalid leads_acc record.']);
        exit;
    }

    $sql = "UPDATE leads_acc SET
                cust_aud_enabled    = $enabled,
                cust_aud_ad_account = '$ad_account',
                cust_aud_id         = '$audience_id',
                cust_aud_name       = '$audience_name',
                updated             = NOW()
            WHERE tbl_id = $leads_acc_id AND uid = $uid";
    mysqli_query($conn, $sql);

    echo json_encode(['success' => true, 'msg' => 'Settings saved.']);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Unknown action.']);
