<?php
/**
 * Serve attachment file - for display (img/video) and download.
 * Usage: serve-attachment.php?f=attachments/images/file.jpg
 *        serve-attachment.php?f=attachments/csv/file.csv&dl=1  (force download)
 */

$base = __DIR__;
$req = isset($_GET['f']) ? trim($_GET['f']) : '';
$forceDownload = !empty($_GET['dl']);

/* Security: must start with attachments/, no directory traversal */
if (strpos($req, 'attachments/') !== 0 || strpos($req, '..') !== false) {
    http_response_code(400);
    exit('Invalid path');
}

$fullPath = $base . '/' . $req;
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

$path = realpath($fullPath);
$baseReal = realpath($base);
if (!$path || !$baseReal || strpos($path, $baseReal) !== 0) {
    http_response_code(404);
    exit('File not found');
}

$mimes = [
    'csv' => 'text/csv',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls' => 'application/vnd.ms-excel',
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = $mimes[$ext] ?? 'application/octet-stream';

if (ob_get_level()) ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($forceDownload ? 'attachment' : 'inline') . '; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=3600');
readfile($path);
exit;
