<?php
/**
 * Launch Meta campaigns from email content.
 * Creates campaigns, ad sets, and ads per territory.
 * Supports: carousel, static image, video.
 */

session_start();

header('Content-Type: application/json');

$configPath = __DIR__ . '/campaign-config.php';
if (!file_exists($configPath)) {
    echo json_encode(['success' => false, 'error' => 'Config not found']);
    exit;
}

$config = require $configPath;

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/email-parser.php';

$pg = 'facebook';
include __DIR__ . '/../casa3/config.php';

if (!isset($api_ver)) $api_ver = 'v21.0';

if (!isset($access_token) || !isset($api_ver)) {
    echo json_encode(['success' => false, 'error' => 'Meta API config missing']);
    exit;
}

function curlPost($url, $post) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function curlPostFile($url, $post) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$msgId = $_POST['msg_id'] ?? $_GET['msg_id'] ?? '';
if (!$msgId) {
    echo json_encode(['success' => false, 'error' => 'Missing msg_id']);
    exit;
}

/* Fetch full email via Gmail API */
$autoloadPaths = [
    __DIR__ . '/../auth-vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/home/digitalb2k/stage.adrescue.in/auth-vendor/autoload.php',
];
$autoload = null;
foreach ($autoloadPaths as $p) {
    if (file_exists($p)) { $autoload = $p; break; }
}
if (!$autoload) {
    echo json_encode(['success' => false, 'error' => 'Autoload not found']);
    exit;
}
require_once $autoload;

$client = new Google_Client();
$client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->addScope('https://www.googleapis.com/auth/gmail.readonly');
$tokenPath = __DIR__ . '/gmail-token.json';
if (!file_exists($tokenPath)) {
    echo json_encode(['success' => false, 'error' => 'Gmail token not found']);
    exit;
}
$token = json_decode(file_get_contents($tokenPath), true);
$client->setAccessToken($token);
if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
}
$gmail = new Google_Service_Gmail($client);

try {
    $message = $gmail->users_messages->get('me', $msgId, ['format' => 'full']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch email: ' . $e->getMessage()]);
    exit;
}

/**
 * Recursively extract email body from payload (handles nested multipart/alternative).
 */
function getEmailBodyFromPayload($payload) {
    $body = $payload->getBody();
    if ($body && $body->getData()) {
        return base64_decode(strtr($body->getData(), '-_', '+/'));
    }
    $parts = $payload->getParts();
    if (!$parts) return '';

    foreach ($parts as $p) {
        $mime = strtolower($p->getMimeType() ?? '');
        if ($mime === 'text/plain') {
            $b = $p->getBody();
            if ($b && $b->getData()) {
                return base64_decode(strtr($b->getData(), '-_', '+/'));
            }
        }
    }
    foreach ($parts as $p) {
        $mime = strtolower($p->getMimeType() ?? '');
        if ($mime === 'text/html') {
            $b = $p->getBody();
            if ($b && $b->getData()) {
                $html = base64_decode(strtr($b->getData(), '-_', '+/'));
                $html = str_replace(['<br>','<br/>','<br />','<BR>'], "\n", $html);
                $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return trim(strip_tags($html));
            }
        }
    }
    foreach ($parts as $p) {
        $mime = strtolower($p->getMimeType() ?? '');
        if (strpos($mime, 'multipart/') === 0) {
            $sub = getEmailBodyFromPayload($p);
            if ($sub) return $sub;
        }
    }
    return '';
}

$payload = $message->getPayload();
$headers = $payload->getHeaders() ?: [];
$subject = '';
foreach ($headers as $h) {
    if (strtolower($h->getName()) === 'subject') { $subject = $h->getValue(); break; }
}
$bodyText = getEmailBodyFromPayload($payload);
$parsed = parse_email_ad_content($bodyText);

if (!$parsed['has_content']) {
    echo json_encode(['success' => false, 'error' => 'No Headline/Primary/Description found in email']);
    exit;
}

/* Get image attachments for creative */
require_once __DIR__ . '/gmail-functions.php';
$accId = $config['ad_account_id'];
$pageId = $config['page_id'];
$dailyBudget = $config['daily_budget'];
$objective = $config['objective'];
$adStatus = $config['ad_status'];
$territories = $config['territories'];
$territoryTargeting = $config['territory_targeting'];
$ageMin = $config['age_min'];
$ageMax = $config['age_max'];
$optGoal = $config['optimization_goal'] ?? null;

$parts = $payload->getParts();
$imageAttachments = [];
$videoAttachment = null;

if ($parts) {
    foreach ($parts as $i => $part) {
        $fn = $part->getFilename();
        if (!$fn) continue;
        $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
        $mime = strtolower($part->getMimeType() ?? '');
        $body = $part->getBody();
        $data = null;
        if ($body) {
            if ($body->getAttachmentId()) {
                try {
                    $att = $gmail->users_messages_attachments->get('me', $msgId, $body->getAttachmentId());
                    $data = base64_decode(strtr($att->getData(), '-_', '+/'));
                } catch (Exception $e) {}
            } elseif ($body->getData()) {
                $data = base64_decode(strtr($body->getData(), '-_', '+/'));
            }
        }
        if ($data && (in_array($ext, ['jpg','jpeg','png','gif']) || strpos($mime, 'image/') === 0)) {
            $imageAttachments[] = ['filename' => $fn, 'data' => $data, 'mime' => $mime ?: 'image/jpeg'];
        }
        if ($data && (in_array($ext, ['mp4','mov','webm']) || strpos($mime, 'video/') === 0)) {
            $videoAttachment = ['filename' => $fn, 'data' => $data, 'mime' => $mime ?: 'video/mp4'];
        }
    }
}

/* Ensure correct type based on attachments */
if ($parsed['type'] === 'video' && !$videoAttachment) $parsed['type'] = count($imageAttachments) > 1 ? 'carousel' : 'static';
if ($parsed['type'] === 'carousel' && count($imageAttachments) < 2) $parsed['type'] = 'static';
if ($parsed['type'] === 'static' && count($imageAttachments) < 1) {
    echo json_encode(['success' => false, 'error' => 'No image attachments found']);
    exit;
}

$baseDir = __DIR__ . '/attachments/images';
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

$imageHashes = [];
$videoId = null;

/* Upload images to Meta */
foreach ($imageAttachments as $idx => $img) {
    $tmpFile = $baseDir . '/tmp_' . $msgId . '_' . $idx . '_' . preg_replace('/[^a-z0-9\.]/i', '_', $img['filename']);
    file_put_contents($tmpFile, $img['data']);
    $cfile = curl_file_create($tmpFile, $img['mime'], $img['filename']);
    $post = ['filename' => $cfile, 'access_token' => $access_token];
    $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $accId . "/adimages";
    $req = curlPostFile($url, $post);
    $res = json_decode($req, true);
    @unlink($tmpFile);
    if (isset($res['images'][$img['filename']]['hash'])) {
        $imageHashes[] = $res['images'][$img['filename']]['hash'];
    }
}

if (empty($imageHashes) && !$videoId && $videoAttachment) {
    $tmpFile = $baseDir . '/tmp_' . $msgId . '_vid_' . preg_replace('/[^a-z0-9\.]/i', '_', $videoAttachment['filename']);
    file_put_contents($tmpFile, $videoAttachment['data']);
    $cfile = curl_file_create($tmpFile, $videoAttachment['mime'], $videoAttachment['filename']);
    $post = ['source' => $cfile, 'access_token' => $access_token];
    $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $accId . "/advideos";
    $req = curlPostFile($url, $post);
    $res = json_decode($req, true);
    @unlink($tmpFile);
    if (isset($res['id'])) $videoId = $res['id'];
}

/* Sanitize text for Meta API - replace straight apostrophes to avoid parsing issues */
function meta_sanitize_text($str) {
    if (!is_string($str) || $str === '') return $str;
    return str_replace("'", "\u{2019}", $str);  // ' → ' (curly apostrophe, visually same)
}

/* Build campaign name: subject + territory */
$campBase = $subject ?: 'Email Campaign';
$campBase = substr(preg_replace('/[^\w\s\-\.\']/', '', $campBase), 0, 60);
$headline = meta_sanitize_text($parsed['headline'] ?? '');
$primary = meta_sanitize_text($parsed['primary'] ?? '');
$description = meta_sanitize_text($parsed['description'] ?? '');

$created = [];
$errors = [];

$advantageAudience = $config['advantage_audience'] ?? 0;  /* 0 = opt out, 1 = opt in */

foreach ($territories as $terr) {
    $targeting = $territoryTargeting[$terr] ?? ['geo_locations' => ['countries' => ['IN']]];
    $targeting['age_min'] = $ageMin;
    $targeting['age_max'] = $ageMax;
    $targeting['targeting_automation'] = ['advantage_audience' => (int)$advantageAudience];
    $targetJson = json_encode($targeting);

    $campName = $campBase . ', ' . $terr;
    $post = [
        'name' => $campName,
        'objective' => $objective,
        'special_ad_categories' => '[]',
        'is_adset_budget_sharing_enabled' => '0',
        'status' => $adStatus,
        'access_token' => $access_token,
    ];
    $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $accId . "/campaigns";
    $req = curlPost($url, $post);
    $res = json_decode($req, true);

    if (!isset($res['id'])) {
        $errors[] = "Campaign $terr: " . ($res['error']['message'] ?? json_encode($res));
        continue;
    }
    $campId = $res['id'];

    $adSetName = $terr . ($objective === 'OUTCOME_AWARENESS' ? ' - Awareness - ' : ' - Reach - ') . date('d M');
    /* OUTCOME_AWARENESS: REACH or AD_RECALL_LIFT; OUTCOME_TRAFFIC: LINK_CLICKS */
    $optimizationGoal = ($objective === 'OUTCOME_TRAFFIC')
        ? 'LINK_CLICKS'
        : (in_array($optGoal ?? '', ['REACH', 'AD_RECALL_LIFT']) ? $optGoal : 'REACH');
    $post = [
        'name' => $adSetName,
        'optimization_goal' => $optimizationGoal,
        'billing_event' => 'IMPRESSIONS',
        'campaign_id' => $campId,
        'targeting' => $targetJson,
        'status' => $adStatus,
        'promoted_object' => json_encode(['page_id' => (string)$pageId]),
        'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
        'daily_budget' => (string)($dailyBudget * 100),
        'access_token' => $access_token,
    ];
    $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $accId . "/adsets";
    $req = curlPost($url, $post);
    $res = json_decode($req, true);

    if (!isset($res['id'])) {
        $err = $res['error'] ?? [];
        $msg = $err['message'] ?? 'Unknown error';
        if (!empty($err['error_user_msg'])) $msg .= ' — ' . $err['error_user_msg'];
        if (!empty($err['error_subcode'])) $msg .= ' (code ' . $err['error_subcode'] . ')';
        $errors[] = "AdSet $terr: " . $msg;
        continue;
    }
    $adSetId = $res['id'];

    /* Create creative based on type */
    $creativeSpec = null;

    if ($parsed['type'] === 'carousel' && count($imageHashes) >= 2) {
        $childAttachments = [];
        foreach ($imageHashes as $h) {
            $childAttachments[] = [
                'link' => 'https://www.facebook.com/' . $pageId,
                'name' => $headline,
                'description' => $description ?: $primary,
                'image_hash' => $h,
                'call_to_action' => ['type' => 'LEARN_MORE', 'value' => ['link' => 'https://www.facebook.com/' . $pageId]],
            ];
        }
        $creativeSpec = [
            'page_id' => $pageId,
            'link_data' => [
                'link' => 'https://www.facebook.com/' . $pageId,
                'message' => $primary,
                'name' => $headline,
                'child_attachments' => $childAttachments,
                'call_to_action' => ['type' => 'LEARN_MORE', 'value' => ['link' => 'https://www.facebook.com/' . $pageId]],
            ],
        ];
    } elseif ($parsed['type'] === 'video' && $videoId) {
        $creativeSpec = [
            'page_id' => $pageId,
            'video_data' => [
                'video_id' => $videoId,
                'message' => $primary,
                'title' => $headline,
                'call_to_action' => ['type' => 'LEARN_MORE', 'value' => ['link' => 'https://www.facebook.com/' . $pageId]],
            ],
        ];
    } else {
        $creativeSpec = [
            'page_id' => $pageId,
            'link_data' => [
                'image_hash' => $imageHashes[0],
                'link' => 'https://www.facebook.com/' . $pageId,
                'message' => $primary,
                'name' => $headline,
                'description' => $description,
                'call_to_action' => ['type' => 'LEARN_MORE', 'value' => ['link' => 'https://www.facebook.com/' . $pageId]],
            ],
        ];
    }

    $post = [
        'name' => 'Creative ' . $terr . ' ' . date('d M'),
        'object_story_spec' => json_encode($creativeSpec),
        'access_token' => $access_token,
    ];
    $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $accId . "/adcreatives";
    $req = curlPost($url, $post);
    $res = json_decode($req, true);

    if (!isset($res['id'])) {
        $errors[] = "Creative $terr: " . ($res['error']['message'] ?? json_encode($res));
        continue;
    }
    $creativeId = $res['id'];

    $post = [
        'name' => 'Ad ' . $terr . ' ' . date('d M'),
        'adset_id' => $adSetId,
        'creative' => json_encode(['creative_id' => $creativeId]),
        'status' => $adStatus,
        'access_token' => $access_token,
    ];
    $url = "https://graph.facebook.com/" . $api_ver . "/act_" . $accId . "/ads";
    $req = curlPost($url, $post);
    $res = json_decode($req, true);

    if (isset($res['id'])) {
        $created[] = [
            'territory' => $terr,
            'campaign_id' => $campId,
            'adset_id' => $adSetId,
            'ad_id' => $res['id'],
        ];
    } else {
        $errors[] = "Ad $terr: " . ($res['error']['message'] ?? json_encode($res));
    }
}

$campURL = 'https://business.facebook.com/adsmanager/manage/campaigns/edit?act=' . $accId . '&selected_campaign_ids=';

echo json_encode([
    'success' => count($created) > 0,
    'created' => $created,
    'errors' => $errors,
    'manager_url' => $campURL . implode(',', array_column($created, 'campaign_id')),
]);
