<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'google-ads-audience.php';
include '../db.php';

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 

$acc_id = '9516894033';
$d = new DateTime('first day of this month');
$d2 = new DateTime('today');

$fromDt = $d->format('Y-m-d');
$enDt = $d2->format('Y-m-d');

$getAccRep = GetAd::main($conn, $g_refresh_token, $g_mcc, $acc_id, $fromDt, $enDt);

//d($getAccRep);