<?php
include 'header.php'; 


//print_r($_SESSION); exit;
Auth();
$pgHeadline = 'Facebook - Ad Account Reports';
$pgID = 2;
$err =''; 
if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE adAccounts SET status='0' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'report.php';</script>";
	exit();
}

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  'report.php';</script>";
	exit();
}
require __DIR__ . '/vendor/autoload.php';

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\CustomAudience;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Exception\RequestException;

$id = 'act_522360268293904'; //account id
$page_id = '505567312893486';
$form_id = '370961913774505';

include 'config.php';
$api = Api::init($app_id, $app_secret, $access_token);
$api->setLogger(new CurlLogger());

$fields = array(
);
$params = array(
  'name' => 'My Test Engagement Custom Audience',
  'rule' => array('inclusions' => array('operator' => 'or','rules' => array(array('event_sources' => array(array('id' => $form_id,'type' => 'lead')),'retention_seconds' => 7776000,'filter' => array('operator' => 'and', 'filters' => array(array('field' => 'event','operator' => 'eq','value' => 'lead_generation_submitted'))))))),
  'prefill' => '1',
);
//echo json_encode((new AdAccount($id))->createCustomAudience($fields, $params)->exportAllData(), JSON_PRETTY_PRINT);

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}
$userRes2 = mysqli_query($conn, "select pg_token from pages WHERE pg_id='".$page_id."' and uid=".$_SESSION['uid']."");	
$getRw2 = mysqli_fetch_assoc($userRes2);
$pg_access_token = $getRw2['pg_token'];						

$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$page_id.'/leadgen_forms?access_token='.$pg_access_token.'&limit=500');
d($val);
//?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');

exit;

