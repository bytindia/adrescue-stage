<?php 
include 'db.php';
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

$start = new DateTime('yesterday');
$startDt = $start->format('Y-m-d').' 16:10:00';

$end = new DateTime('today');
$endDt = $end->format('Y-m-d').' 16:05:00'; 



$pg_urls = array('https://stage.adrescue.in/adAccounts.php', 'https://stage.adrescue.in/adAccounts-g.php', 'https://stage.adrescue.in/adAccounts-ta.php', 'https://stage.adrescue.in/adAccounts-in.php');

function curlPage($url) {
   // echo $url;
    $ch = curl_init(); 
    curl_setopt($ch,CURLOPT_URL, $url.'?cron=1');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_HEADER, false); 
    $result=curl_exec($ch);
    curl_close($ch);
   // echo $result;
}

foreach($pg_urls as $k => $val) {
   curlPage($val); 
}

$accCount = 0;

$extQ =  "where uid='2' AND created >= '".$startDt."' AND  created <= '".$endDt."' ";

$tbl ='
<h3>New Ad Accounts - (in last 24 hours) </h3>	
<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;"><tr><th>Ad Account</th><th>Account ID</th><th>Social Media</th></tr>';

$q1 = mysqli_query($conn,"SELECT account_id, name FROM adAccounts $extQ");
while($r1 = mysqli_fetch_array($q1)) { 
    $tbl .= '<tr><td>'.$r1['name'].'</td><td>'.$r1['account_id'].'</td><td>Facebook</td></tr>';
    $accCount = 1;
}

$q2 = mysqli_query($conn,"SELECT account_id, name FROM gaccounts $extQ");
while($r2 = mysqli_fetch_array($q2)) { 
    $tbl .= '<tr><td>'.$r2['name'].'</td><td>'.$r2['account_id'].'</td><td>Google</td></tr>';
    $accCount = 1;
}

$q3 = mysqli_query($conn,"SELECT account_id, name FROM adAccounts_ta $extQ");
while($r3= mysqli_fetch_array($q3)) { 
    $tbl .= '<tr><td>'.$r3['name'].'</td><td>'.$r3['account_id'].'</td><td>Taboola</td></tr>';
    $accCount = 1;
}

$q4 = mysqli_query($conn,"SELECT account_id, name FROM adAccounts_in $extQ");
while($r4= mysqli_fetch_array($q4)) { 
    $tbl .= '<tr><td>'.$r4['name'].'</td><td>'.$r4['account_id'].'</td><td>LinkedIn</td></tr>';
    $accCount = 1;
}
$tbl .='</table>';

//echo $accCount; 
if($accCount>0) {
   //echo $tbl;

   $to_address = "ads@bytindia.com, faheem@bytindia.com, accounts@bytindia.com, prabhu@bytindia.com";
    include 'email/mail-new-account.php';
}


