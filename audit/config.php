<? 
if(isset($_SESSION['uid'])) {
	$query = "SELECT tbl_id, name, fb_id, fb_token FROM audit_users WHERE tbl_id='".$_SESSION['uid']."'"; 
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$access_token = $row['fb_token']; 
	$_SESSION['uid'] = $row['tbl_id'];
	$_SESSION['name'] = $row['name'];
    $_SESSION['fb_id'] = $row['fb_id'];
	
	//$_SESSION['f_tok_verify'] = $row['g_mcc'];
	
} else {
	$access_token = getenv('FB_FALLBACK_TOKEN');
}
$ad_account_id = 'act_479529832536428'; // Altis (INR)
//$ad_account_id = 'act_340860296308567'; // haritharang
$app_secret = getenv('FB_APP_SECRET');
$app_id = '594832897646145';

$siteURL = 'https://stage.adrescue.in/audit/';

$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

if(!isset($_SESSION['f_tok_verify']) && $access_token!='') {  
	//if (!$tok_req = file_get_contents($tok_url)) {     
	if (false === ($tok_req = @file_get_contents($tok_url)) && !isset($_GET['update'])) {     
		  echo "<script>window.location = 'fb-login.php';</script>"; exit;
	} else {		  
		  $tok_res = json_decode($tok_req);
		  $_SESSION['f_tok_verify'] = 1;
	}
}

//LINKEDIN
$client_id = '819hf4iznbxt3r';
$client_secret = getenv('LINKEDIN_CLIENT_SECRET');

//Taboola
$client_id_ta = '76e108578198485c8d0d3f2771f9f7c7';
$client_secret_ta = getenv('TABOOLA_CLIENT_SECRET');





