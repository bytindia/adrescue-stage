<? 
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

//LINKEDIN-2
$client_id2     = getenv('LINKEDIN_CLIENT_ID_2');
$client_secret2 = getenv('LINKEDIN_CLIENT_SECRET_2');

//Taboola
$client_id_ta     = getenv('TABOOLA_CLIENT_ID');
$client_secret_ta = getenv('TABOOLA_CLIENT_SECRET');





