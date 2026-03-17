<?php 

function adAccounts($url, $conn) {
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	foreach ($fb_response->data as $key => $response) {		
			$cirRes = mysqli_query($conn, "select * from adAccounts WHERE account_id='".$response->account_id."' AND fb_id='".$_SESSION['fb_id']."' AND uid='".$_SESSION['uid']."'");	
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO adAccounts (uid, fb_id, id, name, account_id, account_status, currency, created) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->account_id)."', '".mysqli_real_escape_string($conn, $response->account_status)."', '".mysqli_real_escape_string($conn, $response->currency)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					 $cirSql = "UPDATE adAccounts SET name='".mysqli_real_escape_string($conn, $response->name)."', account_status='".mysqli_real_escape_string($conn, $response->account_status)."', updated=now() WHERE account_id='".$response->account_id."' AND fb_id='".$_SESSION['fb_id']."' AND uid='".$_SESSION['uid']."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			}
	}  
	if(isset($fb_response->paging->next)) {
		adAccounts($fb_response->paging->next, $conn);
	} else {
		//exit;
	}
}

function retTok($t) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_COOKIESESSION, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, "App Client" );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60 );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ));

    curl_setopt($ch, CURLOPT_URL,"https://backstage.taboola.com/backstage/oauth/token");
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($t));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 0);

    $result=curl_exec ($ch);

    $info = curl_getinfo($ch);
    $response = json_decode($result, true);



    if ($info['http_code'] == 200) {
        return $taboola_tok = $response['access_token'];
    } else {
        return '';
    }
}

if(isset($_SESSION['uid'])) {
	$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id='".$_SESSION['uid']."'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$access_token = $row['access_token']; 
	$_SESSION['uid'] = $row['tbl_id'];
	$_SESSION['name'] = $row['name'];
    $_SESSION['fb_id'] = $row['fb_id'];
    $_SESSION['g_id'] = $row['g_id'];
	$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
    $_SESSION['g_token'] = $row['g_token'];
	$_SESSION['g_mcc'] = $row['g_mcc'];
	
	//$_SESSION['f_tok_verify'] = $row['g_mcc'];
	
} else {
	$access_token = getenv('FB_FALLBACK_TOKEN') ?: '';
}
$ad_account_id = getenv('FB_AD_ACCOUNT_ID') ?: 'act_479529832536428'; // Altis (INR)
//$ad_account_id = 'act_340860296308567'; // haritharang
$app_secret = getenv('FB_APP_SECRET');
$app_id     = getenv('FB_APP_ID');

$siteURL = getenv('SITE_URL') ?: 'https://stage.adrescue.in/';

$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

if(!isset($_SESSION['f_tok_verify']) && $access_token!='') {  
	//if (!$tok_req = file_get_contents_curl($tok_url)) {     
	if (false === ($tok_req = @file_get_contents_curl($tok_url)) && !isset($_GET['update'])) {     
		  echo "<script>window.location = 'fb-login.php';</script>"; exit;
	} else {		  
		  $tok_res = json_decode($tok_req);
		  $_SESSION['f_tok_verify'] = 1;
	}
}


//LINKEDIN
$client_id     = getenv('LINKEDIN_CLIENT_ID');
$client_secret = getenv('LINKEDIN_CLIENT_SECRET');

//Taboola
$client_id_ta     = getenv('TABOOLA_CLIENT_ID');
$client_secret_ta = getenv('TABOOLA_CLIENT_SECRET');





