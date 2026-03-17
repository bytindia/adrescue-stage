<?php
/**
 * Gmail helper functions - attachment extraction, folder routing, CSV parsing.
 */

define('GMAIL_ATTACHMENTS_BASE', __DIR__ . '/attachments');

function gmail_ensure_attachment_dirs() {
    $dirs = [
        GMAIL_ATTACHMENTS_BASE . '/images',
        GMAIL_ATTACHMENTS_BASE . '/csv',
        GMAIL_ATTACHMENTS_BASE . '/excel',
        GMAIL_ATTACHMENTS_BASE . '/videos',
        GMAIL_ATTACHMENTS_BASE . '/other',
    ];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            mkdir($d, 0755, true);
        }
    }
}

/**
 * Get folder for attachment based on mime type / extension.
 */
function gmail_attachment_folder($filename, $mimeType) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = strtolower($mimeType ?: '');

    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];
    $videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
    $csvExts  = ['csv'];
    $excelExts = ['xlsx', 'xls'];

    if (in_array($ext, $imageExts) || strpos($mime, 'image/') === 0) {
        return 'images';
    }
    if (in_array($ext, $videoExts) || strpos($mime, 'video/') === 0) {
        return 'videos';
    }
    if (in_array($ext, $csvExts) || $mime === 'text/csv') {
        return 'csv';
    }
    if (in_array($ext, $excelExts) || $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $mime === 'application/vnd.ms-excel') {
        return 'excel';
    }
    return 'other';
}

/**
 * Save attachment to appropriate folder. Returns relative path for web access, or null on failure.
 */
function gmail_save_attachment($msgId, $partId, $filename, $mimeType, $data) {
    gmail_ensure_attachment_dirs();
    $folder = gmail_attachment_folder($filename, $mimeType);
    $dir = GMAIL_ATTACHMENTS_BASE . '/' . $folder;

    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext  = pathinfo($filename, PATHINFO_EXTENSION) ?: '';
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);
    $name = $safe . '_' . substr(md5($msgId . $partId), 0, 8);
    if ($ext) $name .= '.' . $ext;

    $path = $dir . '/' . $name;
    if (file_put_contents($path, $data) !== false) {
        return 'attachments/' . $folder . '/' . $name;
    }
    return null;
}

/**
 * Decode base64url (Gmail uses - and _ instead of + and /).
 */
function gmail_decode_base64url($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}
