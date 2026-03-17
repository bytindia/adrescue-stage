<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include 'db.php';
if (!isset($_SESSION['uid'])) {
    header('Content-Type: application/json');
    echo json_encode(['html' => '', 'has_more' => false]);
    exit;
}

$uid = (int)$_SESSION['uid'];
$limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

$result = @mysqli_query($conn, "SELECT client_name, notify_type, notify_msg, created FROM notification_alert WHERE uId='" . $uid . "' ORDER BY created DESC LIMIT " . $limit . " OFFSET " . $offset);
if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['html' => '<div class="notif-empty">No notifications</div>', 'has_more' => false, 'count' => 0, 'total' => 0]);
    exit;
}

$html = '';
$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    $time_ago = 'Just now';
    $ts = !empty($row['created']) ? strtotime($row['created']) : time();
    if ($ts) {
        $diff = time() - $ts;
        $mins = floor($diff / 60);
        $hrs = floor($diff / 3600);
        $days = floor($diff / 86400);
        $weeks = floor($diff / 604800);
        if ($diff >= 60 && $diff < 3600) $time_ago = $mins . ($mins == 1 ? ' min' : ' mins') . ' ago';
        elseif ($diff >= 3600 && $diff < 86400) $time_ago = $hrs . ($hrs == 1 ? ' hr' : ' hrs') . ' ago';
        elseif ($diff >= 86400 && $diff < 604800) $time_ago = $days . 'd ago';
        elseif ($diff >= 604800 && $diff < 2592000) $time_ago = $weeks . 'w ago';
        elseif ($diff >= 2592000) $time_ago = date('M j', $ts) . ' ago';
    }

    $client = htmlspecialchars($row['client_name']);
    $msg = htmlspecialchars($row['notify_msg']);
    $avatarLetter = !empty($client) ? strtoupper(substr($client, 0, 1)) : '?';
    $avatarClass = 'notif-avatar';
    if (isset($row['notify_type'])) {
        if (trim($row['notify_type']) === 'Lead') $avatarClass .= ' notif-avatar-lead';
        elseif (trim($row['notify_type']) === '0 Lead') $avatarClass .= ' notif-avatar-0lead';
    }

    $html .= '<a href="#" class="notif-item">';
    $html .= '<div class="' . $avatarClass . '">' . $avatarLetter . '</div>';
    $html .= '<div class="notif-body">';
    $html .= '<div class="notif-text"><strong>' . $client . '</strong> – ' . $msg . '</div>';
    $html .= '<div class="notif-time">' . $time_ago . '</div>';
    $html .= '</div></a>';
}

$has_more = ($count >= $limit);

$total = 0;
if ($offset == 0) {
    $cntRes = @mysqli_query($conn, "SELECT COUNT(*) as c FROM notification_alert WHERE uId='" . $uid . "'");
    if ($cntRes && $r = mysqli_fetch_assoc($cntRes)) $total = (int)$r['c'];
}

header('Content-Type: application/json');
echo json_encode(['html' => $html, 'has_more' => $has_more, 'count' => $count, 'total' => $total]);
