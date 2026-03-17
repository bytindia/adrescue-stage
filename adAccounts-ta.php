<? session_start();
$redirect = 'yes'; if(isset($_GET['cron'])) { $_SESSION['uid'] = 2; $redirect = 'no'; }
include 'db.php';
include 'config.php';

$post = array(
    "client_id"           => $client_id_ta,
    "client_secret"       => $client_secret_ta,
    "grant_type"          => "client_credentials",
);

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
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_AUTOREFERER, 0);

$result=curl_exec ($ch);

$info = curl_getinfo($ch);
$response = json_decode($result, true);
//d($response);
if ($info['http_code'] == 200) 
{	
	function adAccounts_ta($url, $conn) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
		$requests = file_get_contents_curl($url);
		$fb_response = json_decode($requests);
		//print_r($fb_response);
		foreach ($fb_response->results as $key => $response) {			
				//echo 'ID: ' . $response->id . '<br />'; exit;
				$cirRes = mysqli_query($conn, "select * from adAccounts_ta WHERE account_id='".$response->account_id."' AND id='".$response->id."' AND uid='".$_SESSION['uid']."'");						
				
				//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
				if(mysqli_num_rows($cirRes)==0) {
						 $cirSql = "INSERT INTO adAccounts_ta (uid, id, name, account_id, currency, created) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->account_id)."', '".mysqli_real_escape_string($conn, $response->currency)."', now());"; 
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					} else {
						$cirSql = "UPDATE adAccounts_ta SET name='".mysqli_real_escape_string($conn, $response->name)."', updated=now() WHERE account_id='".$response->account_id."' AND id='".$response->id."' AND uid='".$_SESSION['uid']."'";
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				}
		} 
	}
	
     $access_token_ta = $response['access_token'];
	
	$request_url = "https://backstage.taboola.com/backstage/api/1.0/bytindia-inr-network/advertisers/?access_token=".$access_token_ta."";
	adAccounts_ta($request_url, $conn);

	if($redirect == 'yes') {
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'ad-accounts-ta.php';</script>";
		exit();
	}
}
