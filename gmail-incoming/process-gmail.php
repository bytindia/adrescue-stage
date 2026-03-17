<?php
/**
 * Process Gmail inbox - fetches and processes new messages.
 * Saves attachments to attachments/csv, attachments/images, attachments/videos, attachments/excel.
 * Uses users.messages.list (not history API) to avoid PHP 8 implode bug in older Google API client.
 */

require_once __DIR__ . '/gmail-functions.php';

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
    die("Autoload not found. Please run composer install or ensure auth-vendor exists.");
}

require_once $autoload;

$client = new Google_Client();
$client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->addScope('https://www.googleapis.com/auth/gmail.readonly');
$client->addScope('https://www.googleapis.com/auth/gmail.modify');

$tokenPath = __DIR__ . '/gmail-token.json';
if (!file_exists($tokenPath)) {
    die("Token file not found. Please run login-gmail.php first.");
}

$token = json_decode(file_get_contents($tokenPath), true);
$client->setAccessToken($token);

/* Auto refresh token */
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    } else {
        die("Refresh token missing. Re-authenticate via login-gmail.php");
    }
}

$gmail = new Google_Service_Gmail($client);

/*
 * Use messages.list instead of history.list to avoid PHP 8 implode bug
 * in older google/apiclient when passing startHistoryId.
 */
$optParams = [
    'maxResults' => 20,
    'labelIds'   => ['INBOX'],
    'q'          => 'newer_than:7d',  // last 7 days - adjust as needed
];

try {
    $messagesResponse = $gmail->users_messages->listUsersMessages('me', $optParams);
} catch (Exception $e) {
    die("Gmail API error: " . $e->getMessage());
}

$messageIds = $messagesResponse->getMessages();
if ($messageIds === null) {
    $messageIds = [];
}

if (empty($messageIds)) {
    /* Fallback: try without query in case newer_than fails on some setups */
    $optParams = ['maxResults' => 10, 'labelIds' => ['INBOX']];
    try {
        $messagesResponse = $gmail->users_messages->listUsersMessages('me', $optParams);
        $messageIds = $messagesResponse->getMessages() ?: [];
    } catch (Exception $e) {
        $messageIds = [];
    }

    if (empty($messageIds)) {
        echo "No new messages.\n";
        exit(0);
    }
}

/**
 * Recursively extract and save attachments from message parts.
 */
function processAttachments($gmail, $userId, $msgId, $parts, $prefix = '') {
    $count = 0;
    if (!$parts) return 0;
    foreach ($parts as $i => $part) {
        $partId = $prefix . ($prefix ? '.' : '') . $i;
        $filename = $part->getFilename();
        if ($filename) {
            $body = $part->getBody();
            $data = null;
            if ($body) {
                if ($body->getAttachmentId()) {
                    try {
                        $att = $gmail->users_messages_attachments->get($userId, $msgId, $body->getAttachmentId());
                        $data = gmail_decode_base64url($att->getData());
                    } catch (Exception $e) {}
                } elseif ($body->getData()) {
                    $data = gmail_decode_base64url($body->getData());
                }
            }
            if ($data !== null) {
                $relPath = gmail_save_attachment($msgId, $partId, $filename, $part->getMimeType(), $data);
                if ($relPath) {
                    $count++;
                    echo "    Saved: $filename -> $relPath\n";
                }
            }
        }
        $subParts = $part->getParts();
        if ($subParts) {
            $count += processAttachments($gmail, $userId, $msgId, $subParts, $partId);
        }
    }
    return $count;
}

gmail_ensure_attachment_dirs();

$processed = 0;
foreach ($messageIds as $msgRef) {
    $msgId = $msgRef->getId();
    try {
        $message = $gmail->users_messages->get('me', $msgId, ['format' => 'full']);
    } catch (Exception $e) {
        error_log("Failed to get message $msgId: " . $e->getMessage());
        continue;
    }

    $payload = $message->getPayload();
    $headers = $payload->getHeaders();
    $subject = '';
    $from = '';
    foreach ($headers as $h) {
        if (strtolower($h->getName()) === 'subject') $subject = $h->getValue();
        if (strtolower($h->getName()) === 'from')    $from    = $h->getValue();
    }

    $snippet = $message->getSnippet();

    $processed++;
    echo "Message #$processed: [{$msgId}] $subject\n";
    echo "  From: $from\n";
    echo "  Snippet: " . substr($snippet, 0, 80) . "...\n";

    $parts = $payload->getParts();
    $attCount = 0;
    if ($parts) {
        $attCount = processAttachments($gmail, 'me', $msgId, $parts);
    } else {
        $filename = $payload->getFilename();
        if ($filename && $payload->getBody() && $payload->getBody()->getAttachmentId()) {
            try {
                $att = $gmail->users_messages_attachments->get('me', $msgId, $payload->getBody()->getAttachmentId());
                $data = gmail_decode_base64url($att->getData());
                $relPath = gmail_save_attachment($msgId, '0', $filename, $payload->getMimeType(), $data);
                if ($relPath) {
                    $attCount = 1;
                    echo "    Saved: $filename -> $relPath\n";
                }
            } catch (Exception $e) {}
        }
    }
    if ($attCount > 0) {
        echo "  Attachments saved: $attCount\n";
    }
    echo "\n";
}

echo "Processed $processed new message(s).\n";
