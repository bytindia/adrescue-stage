<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();
date_default_timezone_set("Asia/Calcutta");

include __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action !== 'save') {
    echo json_encode(['success' => false, 'msg' => 'Invalid action']);
    exit;
}

$account_tbl_id = isset($_POST['account_tbl_id']) ? (int)$_POST['account_tbl_id'] : 0;
$itemsJson = isset($_POST['items']) ? $_POST['items'] : '';

if ($account_tbl_id <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Invalid account']);
    exit;
}

// Verify account belongs to user
$chk = mysqli_query($conn, "SELECT tbl_id FROM dashboard_accounts WHERE tbl_id=" . $account_tbl_id . " AND uid='" . mysqli_real_escape_string($conn, $_SESSION['uid']) . "' AND delete_status=0");
if (!$chk || mysqli_num_rows($chk) === 0) {
    echo json_encode(['success' => false, 'msg' => 'Access denied']);
    exit;
}

$items = json_decode($itemsJson, true);
if (!is_array($items)) {
    echo json_encode(['success' => false, 'msg' => 'Invalid data']);
    exit;
}

$now = date('Y-m-d H:i:s');
$ids = [];

foreach ($items as $idx => $it) {
    $label = isset($it['custom_label']) ? trim($it['custom_label']) : '';
    $val = isset($it['value']) ? trim($it['value']) : '';
    $costPer = isset($it['cost_per']) && $it['cost_per'] ? 1 : 0;
    $existingId = (isset($it['id']) && $it['id'] !== '' && $it['id'] !== null) ? (int)$it['id'] : 0;

    if ($existingId > 0) {
        $uq = "UPDATE clients_performance SET custom_label='" . mysqli_real_escape_string($conn, $label) . "', `value`='" . mysqli_real_escape_string($conn, $val) . "', cost_per=" . (int)$costPer . ", sort_order=" . (int)$idx . ", updated='" . $now . "' WHERE id=" . $existingId . " AND account_tbl_id=" . $account_tbl_id;
        mysqli_query($conn, $uq);
        if (mysqli_error($conn)) {
            echo json_encode(['success' => false, 'msg' => 'Update failed: ' . mysqli_error($conn)]);
            exit;
        }
        $ids[] = $existingId;
    } else {
        $iq = "INSERT INTO clients_performance (account_tbl_id, custom_label, `value`, cost_per, sort_order, created, updated) VALUES (" . $account_tbl_id . ", '" . mysqli_real_escape_string($conn, $label) . "', '" . mysqli_real_escape_string($conn, $val) . "', " . (int)$costPer . ", " . (int)$idx . ", '" . $now . "', '" . $now . "')";
        mysqli_query($conn, $iq);
        if (mysqli_error($conn)) {
            echo json_encode(['success' => false, 'msg' => 'Insert failed: ' . mysqli_error($conn)]);
            exit;
        }
        $ids[] = mysqli_insert_id($conn);
    }
}

// Delete removed items - keep only ids we just updated/inserted ($ids), delete the rest
$delRes = mysqli_query($conn, "SELECT id FROM clients_performance WHERE account_tbl_id=" . $account_tbl_id);
if ($delRes) {
    while ($row = mysqli_fetch_assoc($delRes)) {
        if (!in_array($row['id'], $ids)) {
            mysqli_query($conn, "DELETE FROM clients_performance WHERE id=" . (int)$row['id'] . " AND account_tbl_id=" . $account_tbl_id);
        }
    }
}

echo json_encode(['success' => true, 'msg' => 'Saved', 'ids' => $ids]);
