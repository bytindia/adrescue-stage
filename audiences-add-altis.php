<?php
include 'db.php';

$app_secret = getenv('FB_APP_SECRET');
$app_id = '594832897646145';
$siteURL = 'https://stage.adrescue.in/';


//include 'config.php';

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
 $access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];
/*
require __DIR__ . '/vendor/autoload.php';

		use FacebookAds\Object\AdAccount;
		use FacebookAds\Object\CustomAudience;
		use FacebookAds\Api;
		use FacebookAds\Logger\CurlLogger;
		
		use FacebookAds\Http\Exception\AuthorizationException;
		use FacebookAds\Http\Exception\RequestException;
		use FacebookAds\Object\Values\CustomAudienceTypes;

$api = Api::init($app_id, $app_secret, $access_token);
$api->setLogger(new CurlLogger());*/

function get_data($url, $accTok, $ph) {	
	$post = array(
		'payload'=> $postVal,
	    'access_token'=> $accTok		
	);
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'access_token='.$accTok.'&payload={"schema": "PHONE_SHA256","data": ["'.$ph.'"]}');
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}

/*
$id = 'act_479529832536428'; //Altisville (INR)
//$id = 'act_522360268293904'; //Ahraya
 //account id
		//$page_id = $_POST['fb_pg'];
		//$form_id = $_POST['form_id'];
	

$fields = array(
);
$params = array(
  'name' => 'SN Altis Ashraya Cold',
  'subtype' => 'CUSTOM',
  'description' => 'People who has interested (Cold)',
  'customer_file_source' => 'USER_PROVIDED_ONLY',
);



// Hot - 23843637523430343
// Warm - 23843637555670343
// Cold - 23843637566990343


echo json_encode((new AdAccount($id))->createCustomAudience($fields, $params)->exportAllData(), JSON_PRETTY_PRINT);

exit;
*/

$cId = $_GET['cId'];
$pId = $_GET['pId'];
$int_id = $_GET['int_id'];
$ph = $_GET['phone'];

//$aud_ids = array(8=>'23843637523430343',9=>'23843637555670343',10=>'23843637566990343');

if($cId==4 && $ph!='' && $pId!='' && ($int_id==8 || $int_id==9 || $int_id==10)) {
	if($pId==17)  //ASHRYA
	{ 
		$id = 'act_522360268293904'; 
		$aud_ids = array(8=>'23843534583280589',9=>'23843534599630589',10=>'23843534603160589');
	} else { 
		$id = 'act_479529832536428'; 
		$aud_ids = array(8=>'23843637523430343',9=>'23843637555670343',10=>'23843637566990343');
	}	
	
	//$postVal = 'payload={"schema": "PHONE_SHA256","data": ["e86180b6a591aa12a9dfad31f09fa1f6de5c835d3a3a6cf8c37fa602bac29e18"]}'
	
	$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$aud_ids[$int_id].'/users', $access_token, hash('sha256',$ph));
	//print_r($val);
	//$audience = new CustomAudience($aud_ids[$int_id]);	
	//$audience->addUsers($phone_nos, CustomAudienceTypes::PHONE);
	echo 'success';
}