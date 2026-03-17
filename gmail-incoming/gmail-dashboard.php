<?php
/**
 * Gmail Dashboard - Lists emails with Excel/CSV attachments only.
 * Allows converting attachments to visual dashboards via ChatGPT API.
 */

/* Password protection */
$authUser = 'basics';
$authPass = '9fQTSyN8Xu$Ch';
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $authUser || $_SERVER['PHP_AUTH_PW'] !== $authPass) {
    header('WWW-Authenticate: Basic realm="Gmail Dashboard"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}

require_once __DIR__ . '/gmail-functions.php';

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

function extract_email_from_header($from) {
    if (preg_match('/<([^>]+)>/', $from, $m)) return strtolower(trim($m[1]));
    return strtolower(trim($from));
}

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
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $mime = strtolower($mimeType ?? '');
                    $isExcel = in_array($ext, ['xlsx', 'xls']) || $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $mime === 'application/vnd.ms-excel';
                    $isCsv = $ext === 'csv' || $mime === 'text/csv';
                    if ($isExcel || $isCsv) {
                        $attachments[] = [
                            'filename' => $filename,
                            'path'     => $relPath,
                            'mime'     => $mimeType,
                            'type'     => $isExcel ? 'excel' : 'csv',
                        ];
                    }
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
    'maxResults' => 50,
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

/* Only include emails that have Excel or CSV attachments */
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
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $isExcel = in_array($ext, ['xlsx', 'xls']);
            $isCsv = $ext === 'csv';
            if ($isExcel || $isCsv) {
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
                                'type'     => $isExcel ? 'excel' : 'csv',
                            ];
                        }
                    } catch (Exception $e) {}
                }
            }
        }
    }

    /* Skip if no Excel/CSV attachments */
    if (empty($attachments)) {
        continue;
    }

    /* Filter by from email if configured */
    if ($fromEmailFilter && extract_email_from_header($from) !== strtolower($fromEmailFilter)) {
        continue;
    }

    $messagesData[] = [
        'id'          => $msgId,
        'subject'     => $subject,
        'from'        => $from,
        'date'        => $date,
        'attachments' => $attachments,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail - Convert to Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .email-card { border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 1rem; overflow: hidden; }
        .email-card:hover { border-color: #0d6efd; box-shadow: 0 2px 8px rgba(13,110,253,0.15); }
        .email-header { background: #f8f9fa; padding: 0.75rem 1rem; }
        .att-badge { font-size: 0.8rem; }
        .dashboard-preview { min-height: 80vh; overflow: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; background: #fff; }
        .loader-spinner { display: inline-block; width: 1.5rem; height: 1.5rem; border: 2px solid #f3f3f3; border-top: 2px solid #0d6efd; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>Gmail → Dashboard</h2>
    <p class="text-muted">Emails with Excel or CSV attachments only. Convert any attachment to a visual dashboard.</p>

    <?php if (empty($messagesData)): ?>
        <div class="alert alert-info">No emails with Excel or CSV attachments found in the last 7 days.</div>
    <?php else: ?>
        <?php foreach ($messagesData as $msg): ?>
            <div class="email-card">
                <div class="email-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <strong><?= htmlspecialchars($msg['subject'] ?: '(no subject)') ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($msg['from']) ?> · <?= htmlspecialchars($msg['date']) ?></small>
                    </div>
                </div>
                <div class="p-3">
                    <strong class="d-block mb-2">Attachments (Excel/CSV):</strong>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($msg['attachments'] as $idx => $att): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary att-badge"><?= htmlspecialchars($att['filename']) ?></span>
                                <button type="button" class="btn btn-sm btn-primary convert-btn"
                                        data-path="<?= htmlspecialchars($att['path']) ?>"
                                        data-filename="<?= htmlspecialchars($att['filename']) ?>"
                                        data-type="<?= htmlspecialchars($att['type']) ?>">
                                    Convert to Dashboard
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Sheet selection (for Excel) -->
<div class="modal fade" id="sheetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Choose sheet to convert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" id="sheetFileName"></p>
                <div id="sheetList"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Dashboard result -->
<div class="modal fade" id="dashboardModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboardModalTitle">Dashboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="dashboardPlaceholder" class="dashboard-preview">
                    <div id="dashboardLoader" class="text-center py-5">
                        <div class="loader-spinner mx-auto mb-2"></div>
                        <p>Generating dashboard...</p>
                    </div>
                    <div id="dashboardContent" style="display:none;"></div>
                    <div id="dashboardError" class="alert alert-danger" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const apiUrl = 'gmail-dashboard-api.php';

    function getAuthHeaders() {
        return {};
    }

    function convertToDashboard(path, sheetIndex, filename) {
        $('#dashboardModalTitle').text('Dashboard: ' + filename);
        $('#dashboardLoader').show();
        $('#dashboardContent').hide().empty();
        $('#dashboardError').hide();

        const dashboardModal = new bootstrap.Modal(document.getElementById('dashboardModal'));
        dashboardModal.show();

        const formData = new FormData();
        formData.append('action', 'convert');
        formData.append('path', path);
        if (sheetIndex !== undefined && sheetIndex !== null) {
            formData.append('sheet', sheetIndex);
        }

        $.ajax({
            url: apiUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhrFields: { withCredentials: true },
            success: function(res) {
                $('#dashboardLoader').hide();
                if (res.success && res.html) {
                    // Use iframe so scripts (Chart.js etc) execute properly
                    const iframe = $('<iframe>').attr('srcdoc', res.html).css({ width: '100%', minHeight: '80vh', border: 'none', flex: 1 });
                    $('#dashboardContent').empty().append(iframe).show();
                } else {
                    $('#dashboardError').text(res.error || 'Failed to generate dashboard').show();
                }
            },
            error: function(xhr) {
                $('#dashboardLoader').hide();
                let msg = 'Request failed';
                try {
                    const j = JSON.parse(xhr.responseText);
                    if (j.error) msg = j.error;
                } catch (e) {}
                $('#dashboardError').text(msg).show();
            }
        });
    }

    function showSheetModal(path, filename, callback) {
        $('#sheetFileName').text(filename);
        $('#sheetList').html('<div class="spinner-border spinner-border-sm" role="status"></div> Loading sheets...');

        const sheetModal = new bootstrap.Modal(document.getElementById('sheetModal'));
        sheetModal.show();

        $.ajax({
            url: apiUrl,
            method: 'GET',
            data: { action: 'get_sheets', path: path },
            xhrFields: { withCredentials: true },
            success: function(res) {
                if (res.success && res.sheets && res.sheets.length) {
                    let html = '<div class="list-group">';
                    res.sheets.forEach(function(name, idx) {
                        html += '<a href="#" class="list-group-item list-group-item-action sheet-item" data-idx="' + idx + '">' + name + '</a>';
                    });
                    html += '</div>';
                    $('#sheetList').html(html);

                    $('#sheetList .sheet-item').on('click', function(e) {
                        e.preventDefault();
                        const idx = parseInt($(this).data('idx'), 10);
                        sheetModal.hide();
                        callback(path, idx, filename);
                    });
                } else {
                    $('#sheetList').html('<div class="alert alert-warning">' + (res.error || 'No sheets found') + '</div>');
                }
            },
            error: function() {
                $('#sheetList').html('<div class="alert alert-danger">Failed to load sheets</div>');
            }
        });
    }

    $(document).on('click', '.convert-btn', function() {
        const path = $(this).data('path');
        const filename = $(this).data('filename');
        const type = $(this).data('type');

        if (type === 'excel') {
            showSheetModal(path, filename, function(selectedPath, sheetIdx, fname) {
                convertToDashboard(selectedPath, sheetIdx, fname);
            });
        } else {
            convertToDashboard(path, null, filename);
        }
    });
})();
</script>
</body>
</html>
