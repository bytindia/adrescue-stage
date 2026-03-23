<?php
/**
 * Invoice Custom Attachments Handler
 * Actions: upload, delete, download
 * Stores files in: download/invoice-attachments/
 * DB Table: invoice_custom_attachments
 *
 * Reuses: db.php, Auth
 */
session_start();
include 'db.php';
Auth();

$action   = $_GET['action'] ?? $_POST['action'] ?? '';
$inv_id   = isset($_GET['inv_id'])  ? (int)$_GET['inv_id']  : (isset($_POST['inv_id'])  ? (int)$_POST['inv_id']  : 0);
$attach_id= isset($_GET['id'])      ? (int)$_GET['id']       : 0;

$upload_dir = '/home/digitalb2k/stage.adrescue.in/download/invoice-attachments/';
// ── ⚑ REPLACE above path with your actual server root if different

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ── UPLOAD ────────────────────────────────────────────────────────────────────
if ($action === 'upload' && $inv_id > 0) {
    header('Content-Type: application/json');

    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'File upload error.']);
        exit;
    }

    $file     = $_FILES['attachment'];
    $label    = trim($_POST['label'] ?? '');
    $orig_name = basename($file['name']);
    $ext      = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    // Allowed types
    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'zip'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'msg' => 'File type not allowed.']);
        exit;
    }

    $safe_name = 'inv_' . $inv_id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
    $dest      = $upload_dir . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'msg' => 'Failed to save file.']);
        exit;
    }

    $label_esc = mysqli_real_escape_string($conn, $label);
    $safe_esc  = mysqli_real_escape_string($conn, $safe_name);
    $orig_esc  = mysqli_real_escape_string($conn, $orig_name);
    $uid       = (int)$_SESSION['uid'];

    mysqli_query($conn,
        "INSERT INTO invoice_custom_attachments
         (inv_id, uid, orig_name, stored_name, label, sent, created)
         VALUES ($inv_id, $uid, '$orig_esc', '$safe_esc', '$label_esc', 0, NOW())"
    ) or die(mysqli_error($conn));

    $insert_id = mysqli_insert_id($conn);

    echo json_encode([
        'success'    => true,
        'id'         => $insert_id,
        'orig_name'  => $orig_name,
        'stored_name'=> $safe_name,
        'label'      => $label,
        'ext'        => $ext,
    ]);
    exit;
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($action === 'delete' && $attach_id > 0) {
    header('Content-Type: application/json');

    $res = mysqli_query($conn, "SELECT stored_name, sent FROM invoice_custom_attachments WHERE id=$attach_id AND uid='".(int)$_SESSION['uid']."' LIMIT 1");
    $row = mysqli_fetch_assoc($res);

    if (!$row) {
        echo json_encode(['success' => false, 'msg' => 'Not found.']);
        exit;
    }
    if ($row['sent']) {
        echo json_encode(['success' => false, 'msg' => 'Cannot delete — already sent.']);
        exit;
    }

    $file_path = $upload_dir . $row['stored_name'];
    if (file_exists($file_path)) { unlink($file_path); }

    mysqli_query($conn, "DELETE FROM invoice_custom_attachments WHERE id=$attach_id");
    echo json_encode(['success' => true]);
    exit;
}

// ── DOWNLOAD ─────────────────────────────────────────────────────────────────
if ($action === 'download' && $attach_id > 0) {
    $res = mysqli_query($conn, "SELECT * FROM invoice_custom_attachments WHERE id=$attach_id AND uid='".(int)$_SESSION['uid']."' LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    if (!$row) { http_response_code(404); exit('Not found'); }

    $file_path = $upload_dir . $row['stored_name'];
    if (!file_exists($file_path)) { http_response_code(404); exit('File not found'); }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $row['orig_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}

// ── GET LIST (AJAX) ──────────────────────────────────────────────────────────
if ($action === 'list' && $inv_id > 0) {
    header('Content-Type: application/json');
    $sent_filter = isset($_GET['sent']) ? "AND sent=" . (int)$_GET['sent'] : '';
    $res = mysqli_query($conn, "SELECT * FROM invoice_custom_attachments WHERE inv_id=$inv_id $sent_filter ORDER BY id ASC");
    $items = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

http_response_code(400);
echo 'Invalid request';
