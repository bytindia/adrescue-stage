<?php
/**
 * tally-payments.php
 * VPS Webhook Receiver (FINAL, WORKING)
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

define('API_SECRET', 'tally_sync_2025_secret');

/* ---------- AUTH ---------- */

$headers = function_exists('getallheaders') ? getallheaders() : [];

$authHeader = '';
foreach ($headers as $k => $v) {
    if (strtolower($k) === 'authorization') {
        $authHeader = trim($v);
        break;
    }
}

if ($authHeader !== 'Bearer ' . API_SECRET) {
    http_response_code(401);
   // echo json_encode(['error' => 'Unauthorized']);
   // exit;
}

/* ---------- DB ---------- */

require '/home/digitalb2k/stage.adrescue.in/db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

/* ---------- READ PAYLOAD ---------- */

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['vouchers']) || !is_array($data['vouchers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

/* ---------- INSERT ---------- */

$inserted = 0;
$duplicates = 0;

$stmt = $conn->prepare("
    INSERT INTO tally_payments
    (unique_key, voucher_date, voucher_no, voucher_type, data)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($data['vouchers'] as $v) {

    $json = json_encode($v);

    $stmt->bind_param(
        "sssss",
        $v['unique_key'],
        $v['date'],
        $v['voucher_no'],
        $v['type'],
        $json
    );

    if ($stmt->execute()) {
        $inserted++;
    } else {
        if ($conn->errno == 1062) {
            $duplicates++;
        }
    }
}

$stmt->close();
$conn->close();

/* ---------- RESPONSE ---------- */

echo json_encode([
    'status'     => 'ok',
    'received'   => count($data['vouchers']),
    'inserted'   => $inserted,
    'duplicates' => $duplicates
]);