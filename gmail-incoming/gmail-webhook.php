<?php

require_once '/home/digitalb2k/stage.adrescue.in/auth-vendor/autoload.php';

/* ===== SAVE RAW PAYLOAD FOR DEBUG ===== */

$raw = file_get_contents("php://input");

file_put_contents(
    __DIR__.'/gmail_payload_debug.txt',
    $raw."\n\n",
    FILE_APPEND
);

/* ===== LOG TRIGGER ===== */

file_put_contents(
    __DIR__.'/gmail_webhook_log.txt',
    date('Y-m-d H:i:s')." webhook triggered\n",
    FILE_APPEND
);

/* ===== DECODE PUBSUB DATA ===== */

$data = json_decode($raw,true);

if(!isset($data['message']['data'])){
    http_response_code(200);
    exit;
}

$decoded = json_decode(
    base64_decode($data['message']['data']),
    true
);

$historyId = $decoded['historyId'] ?? '';

if(!$historyId){
    http_response_code(200);
    exit;
}

/* ===== STORE LAST HISTORY ID ===== */

file_put_contents(
    __DIR__.'/last_history_id.txt',
    $historyId
);

http_response_code(200);
