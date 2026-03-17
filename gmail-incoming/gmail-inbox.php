<?php
/**
 * Gmail Inbox Viewer - fetches messages, saves attachments to folders,
 * displays CSV as table, images inline, videos embedded.
 * Filters by from email, parses ad content, offers Launch Campaign.
 */

/* Password protection */
$authUser = 'basics';
$authPass = '9fQTSyN8Xu$Ch';
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $authUser || $_SERVER['PHP_AUTH_PW'] !== $authPass) {
    header('WWW-Authenticate: Basic realm="Gmail Inbox"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}

require_once __DIR__ . '/gmail-functions.php';
require_once __DIR__ . '/email-parser.php';

$campaignConfig = file_exists(__DIR__ . '/campaign-config.php') ? require __DIR__ . '/campaign-config.php' : null;
$fromEmailFilter = $campaignConfig['from_email'] ?? '';

$autoloadPaths = [
    __DIR__ . '/../auth-vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/home/digitalb2k/stage.adrescue.in/auth-vendor/autoload.php',
];

$autoload = null;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        $autoload = $path;
        break;
    }
}

if (!$autoload) {
    die("Autoload not found.");
}

require_once $autoload;

$client = new Google_Client();
$client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->addScope('https://www.googleapis.com/auth/gmail.readonly');
$client->addScope('https://www.googleapis.com/auth/gmail.modify');

$tokenPath = __DIR__ . '/gmail-token.json';
if (!file_exists($tokenPath)) {
    die("Token file not found. <a href='login-gmail.php'>Login with Gmail</a> first.");
}

$token = json_decode(file_get_contents($tokenPath), true);
$client->setAccessToken($token);

if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    } else {
        die("Refresh token missing. <a href='login-gmail.php'>Re-login</a>");
    }
}

$gmail = new Google_Service_Gmail($client);

/**
 * Extract email address from From header (e.g. "Name <email@domain.com>").
 */
function extract_email_from_header($from) {
    if (preg_match('/<([^>]+)>/', $from, $m)) return strtolower(trim($m[1]));
    return strtolower(trim($from));
}

/**
 * Get full plain-text body from Gmail message payload.
 * Recurses into nested parts (multipart/alternative, multipart/mixed, etc.).
 */
function getEmailBodyFromPayload($payload, $gmail, $userId, $msgId) {
    $body = $payload->getBody();
    if ($body && $body->getData()) {
        return base64_decode(strtr($body->getData(), '-_', '+/'));
    }
    $parts = $payload->getParts();
    if (!$parts) return '';

    $plainText = $htmlText = '';
    foreach ($parts as $p) {
        $mime = strtolower($p->getMimeType() ?? '');
        if ($mime === 'text/plain') {
            $b = $p->getBody();
            if ($b && $b->getData()) {
                $plainText = base64_decode(strtr($b->getData(), '-_', '+/'));
                break;
            }
        }
    }
    if ($plainText) return $plainText;

    foreach ($parts as $p) {
        $mime = strtolower($p->getMimeType() ?? '');
        if ($mime === 'text/html') {
            $b = $p->getBody();
            if ($b && $b->getData()) {
                $htmlText = base64_decode(strtr($b->getData(), '-_', '+/'));
                break;
            }
        }
    }
    if ($htmlText) {
        $htmlText = str_replace(['<br>','<br/>','<br />','<BR>'], "\n", $htmlText);
        $htmlText = html_entity_decode($htmlText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(strip_tags($htmlText));
    }

    /* Recurse into nested multipart parts (e.g. multipart/alternative inside multipart/mixed) */
    foreach ($parts as $p) {
        $mime = strtolower($p->getMimeType() ?? '');
        if (strpos($mime, 'multipart/') === 0) {
            $sub = getEmailBodyFromPayload($p, $gmail, $userId, $msgId);
            if ($sub) return $sub;
        }
    }
    return '';
}

/**
 * Recursively extract attachments from message parts.
 */
function extractAttachments($gmail, $userId, $msgId, $parts, $prefix = '') {
    $attachments = [];
    if (!$parts) return $attachments;

    foreach ($parts as $i => $part) {
        $partId = $prefix . ($prefix ? '.' : '') . $i;
        $mimeType = $part->getMimeType();
        $filename = $part->getFilename();

        if ($filename) {
            $body = $part->getBody();
            $data = null;
            if ($body) {
                if ($body->getAttachmentId()) {
                    try {
                        $att = $gmail->users_messages_attachments->get($userId, $msgId, $body->getAttachmentId());
                        $data = gmail_decode_base64url($att->getData());
                    } catch (Exception $e) {
                        $data = null;
                    }
                } elseif ($body->getData()) {
                    $data = gmail_decode_base64url($body->getData());
                }
            }
            if ($data !== null) {
                $relPath = gmail_save_attachment($msgId, $partId, $filename, $mimeType, $data);
                if ($relPath) {
                    $attachments[] = [
                        'filename' => $filename,
                        'path'     => $relPath,
                        'mime'     => $mimeType,
                    ];
                }
            }
        }

        $subParts = $part->getParts();
        if ($subParts) {
            $attachments = array_merge($attachments, extractAttachments($gmail, $userId, $msgId, $subParts, $partId));
        }
    }
    return $attachments;
}

/* Fetch messages */
$optParams = [
    'maxResults' => 25,
    'labelIds'   => ['INBOX'],
    'q'          => 'newer_than:7d',
];

try {
    $messagesResponse = $gmail->users_messages->listUsersMessages('me', $optParams);
} catch (Exception $e) {
    die("Gmail API error: " . htmlspecialchars($e->getMessage()));
}

$messageIds = $messagesResponse->getMessages() ?: [];

gmail_ensure_attachment_dirs();

$messagesData = [];
foreach ($messageIds as $msgRef) {
    $msgId = $msgRef->getId();
    try {
        $message = $gmail->users_messages->get('me', $msgId, ['format' => 'full']);
    } catch (Exception $e) {
        continue;
    }

    $payload = $message->getPayload();
    $headers = $payload->getHeaders();
    $subject = $from = $date = '';
    foreach ($headers as $h) {
        $n = strtolower($h->getName());
        if ($n === 'subject') $subject = $h->getValue();
        if ($n === 'from')    $from    = $h->getValue();
        if ($n === 'date')    $date    = $h->getValue();
    }

    $parts = $payload->getParts();
    $attachments = [];
    if ($parts) {
        $attachments = extractAttachments($gmail, 'me', $msgId, $parts);
    } else {
        $filename = $payload->getFilename();
        if ($filename) {
            $body = $payload->getBody();
            if ($body && $body->getAttachmentId()) {
                try {
                    $att = $gmail->users_messages_attachments->get('me', $msgId, $body->getAttachmentId());
                    $data = gmail_decode_base64url($att->getData());
                    $relPath = gmail_save_attachment($msgId, '0', $filename, $payload->getMimeType(), $data);
                    if ($relPath) {
                        $attachments[] = [
                            'filename' => $filename,
                            'path'     => $relPath,
                            'mime'     => $payload->getMimeType(),
                        ];
                    }
                } catch (Exception $e) {}
            }
        }
    }

    /* Filter by from email if configured */
    if ($fromEmailFilter && extract_email_from_header($from) !== strtolower($fromEmailFilter)) {
        continue;
    }

    $bodyText = getEmailBodyFromPayload($payload, $gmail, 'me', $msgId);
    $parsedAd = parse_email_ad_content($bodyText);

    $messagesData[] = [
        'id'          => $msgId,
        'subject'     => $subject,
        'from'        => $from,
        'date'        => $date,
        'snippet'     => $message->getSnippet(),
        'body'        => $bodyText,
        'attachments' => $attachments,
        'parsed_ad'   => $parsedAd,
    ];
}

/* Use relative URLs - works regardless of install path */
$serveUrl = 'serve-attachment.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-card { border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 1rem; overflow: hidden; }
        .message-header { background: #f8f9fa; padding: 0.75rem 1rem; }
        .attachment-item { padding: 0.5rem; border-bottom: 1px solid #eee; }
        .csv-table { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
        .csv-table th, .csv-table td { border: 1px solid #ddd; padding: 6px 8px; }
        .csv-table th { background: #f1f1f1; }
        .preview-csv { max-height: 300px; overflow: auto; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>Gmail Inbox</h2>
    <?php if ($fromEmailFilter): ?>
        <p class="text-muted small">Filtered by: <strong><?= htmlspecialchars($fromEmailFilter) ?></strong></p>
    <?php endif; ?>
    <?php if (empty($messagesData)): ?>
        <p class="text-muted">No messages found.</p>
    <?php else: ?>
        <?php foreach ($messagesData as $msg): ?>
            <div class="message-card">
                <div class="message-header">
                    <strong><?= htmlspecialchars($msg['subject'] ?: '(no subject)') ?></strong><br>
                    <small>From: <?= htmlspecialchars($msg['from']) ?> | <?= htmlspecialchars($msg['date']) ?></small>
                </div>
                <div class="p-3">
                    <div class="email-body mb-2" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($msg['body'] ?: $msg['snippet'])) ?></div>

                    <?php 
                    $hasAdContent = !empty($msg['parsed_ad']['has_content']);
                    $hasImageAttachments = !empty($msg['attachments']) && count(array_filter($msg['attachments'], function($a) {
                        $ext = strtolower(pathinfo($a['filename'], PATHINFO_EXTENSION));
                        return in_array($ext, ['jpg','jpeg','png','gif','webp']);
                    })) > 0;
                    $bodyLower = strtolower($msg['body'] ?? '');
                    $mentionsAdFields = (strpos($bodyLower, 'headline') !== false || strpos($bodyLower, 'primary') !== false || strpos($bodyLower, 'description') !== false || strpos($bodyLower, 'carousel') !== false);
                    $showLaunchBtn = $hasAdContent || ($hasImageAttachments && $mentionsAdFields);
                    ?>
                    <?php if ($showLaunchBtn): ?>
                        <div class="alert alert-info py-2 px-3 mb-2">
                            <strong>Ad detected (<?= htmlspecialchars($msg['parsed_ad']['type']) ?>):</strong>
                            <?php if (!empty($msg['parsed_ad']['headline'])): ?>
                                <br>Headline: <?= htmlspecialchars($msg['parsed_ad']['headline']) ?>
                            <?php endif; ?>
                            <?php if (!empty($msg['parsed_ad']['primary'])): ?>
                                <br>Primary: <?= htmlspecialchars(mb_substr($msg['parsed_ad']['primary'], 0, 100)) ?><?= mb_strlen($msg['parsed_ad']['primary']) > 100 ? '...' : '' ?>
                            <?php endif; ?>
                            <?php if (!empty($msg['parsed_ad']['description'])): ?>
                                <br>Description: <?= htmlspecialchars(mb_substr($msg['parsed_ad']['description'], 0, 100)) ?><?= mb_strlen($msg['parsed_ad']['description']) > 100 ? '...' : '' ?>
                            <?php endif; ?>
                            <br class="mt-2">
                            <button type="button" class="btn btn-success btn-sm mt-2 launch-campaign-btn" data-msg-id="<?= htmlspecialchars($msg['id']) ?>">
                                Launch Campaign
                            </button>
                            <span class="launch-status ms-2"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($msg['attachments'])): ?>
                        <hr>
                        <strong>Attachments</strong>
                        <?php foreach ($msg['attachments'] as $att): ?>
                            <div class="attachment-item">
                                <?php
                                $ext = strtolower(pathinfo($att['filename'], PATHINFO_EXTENSION));
                                $mime = strtolower($att['mime'] ?? '');
                                /* All URLs via serve-attachment for reliable display and download */
                                $displayUrl = $serveUrl . '?f=' . rawurlencode($att['path']);
                                $downloadUrl = $serveUrl . '?f=' . rawurlencode($att['path']) . '&dl=1';
                                $isCsv = in_array($ext, ['csv']) || $mime === 'text/csv';
                                $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']) || strpos($mime, 'image/') === 0;
                                $isVideo = in_array($ext, ['mp4','webm','mov']) || strpos($mime, 'video/') === 0;
                                ?>
                                <strong><?= htmlspecialchars($att['filename']) ?></strong>

                                <?php if ($isCsv): ?>
                                    <div class="preview-csv mt-2">
                                        <?php
                                        $fullPath = __DIR__ . '/' . $att['path'];
                                        if (file_exists($fullPath) && ($h = fopen($fullPath, 'r'))): ?>
                                            <table class="csv-table table table-sm">
                                                <?php
                                                $rowNum = 0;
                                                while (($row = fgetcsv($h, 0, ',')) !== false && $rowNum < 50):
                                                    $rowNum++;
                                                    $tag = ($rowNum === 1) ? 'th' : 'td';
                                                    echo '<tr>';
                                                    foreach ($row as $cell) {
                                                        echo "<$tag>" . htmlspecialchars($cell) . "</$tag>";
                                                    }
                                                    echo '</tr>';
                                                endwhile;
                                                fclose($h);
                                                ?>
                                            </table>
                                            <?php if ($rowNum >= 50): ?>
                                                <small class="text-muted">(showing first 50 rows)</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-sm btn-outline-primary mt-1">Download CSV</a>

                                <?php elseif ($isImage): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($displayUrl) ?>" alt="<?= htmlspecialchars($att['filename']) ?>" style="max-width:100%; max-height:300px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <a href="<?= htmlspecialchars($downloadUrl) ?>" style="display:none;" class="d-block text-muted">Image failed to load — <strong>click here to download</strong></a>
                                    </div>
                                    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-sm btn-outline-primary mt-1">Download</a>

                                <?php elseif ($isVideo): ?>
                                    <div class="mt-2">
                                        <video controls style="max-width:100%; max-height:300px;" src="<?= htmlspecialchars($displayUrl) ?>">
                                            <a href="<?= htmlspecialchars($downloadUrl) ?>">Download video</a>
                                        </video>
                                    </div>
                                    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-sm btn-outline-primary mt-1">Download</a>

                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-sm btn-outline-primary ms-2">Download</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.launch-campaign-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var msgId = this.getAttribute('data-msg-id');
        var statusEl = this.closest('.alert').querySelector('.launch-status');
        if (!msgId) return;
        this.disabled = true;
        statusEl.textContent = 'Launching...';
        var fd = new FormData();
        fd.append('msg_id', msgId);
        fetch('launch-campaign.php', {
            method: 'POST',
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                statusEl.innerHTML = '<span class="text-success">&#10004; Launched! <a href="' + (data.manager_url || '#') + '" target="_blank">View in Ads Manager</a></span>';
            } else {
                statusEl.innerHTML = '<span class="text-danger">Error: ' + (data.error || data.errors?.join('; ') || 'Unknown') + '</span>';
                btn.disabled = false;
            }
        })
        .catch(function(e) {
            statusEl.innerHTML = '<span class="text-danger">Request failed</span>';
            btn.disabled = false;
        });
    });
});
</script>
</body>
</html>
