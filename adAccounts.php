<? session_start();
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

$redirect = 'yes'; if(isset($_GET['cron'])) { $_SESSION['uid'] = 2; $redirect = 'no'; }

include 'db.php';
include 'config.php';

function adAccounts($url, $conn) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	//d($fb_response); exit;
	foreach ($fb_response->data as $key => $response) {			
			//echo 'ID: ' . $response->id . '<br />';
			$cirRes = mysqli_query($conn, "select * from adAccounts WHERE account_id='".$response->account_id."'  AND uid='".$_SESSION['uid']."'");						
			
			//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO adAccounts (uid, fb_id, id, name, account_id, account_status, currency, created) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->account_id)."', '".mysqli_real_escape_string($conn, $response->account_status)."', '".mysqli_real_escape_string($conn, $response->currency)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					 $cirSql = "UPDATE adAccounts SET name='".mysqli_real_escape_string($conn, $response->name)."', account_status='".mysqli_real_escape_string($conn, $response->account_status)."', updated=now() WHERE account_id='".$response->account_id."' AND uid='".$_SESSION['uid']."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			}
	}  
	if(isset($fb_response->paging->next)) {
		//echo $fb_response->paging->next;
		adAccounts($fb_response->paging->next, $conn);
	} else {
		//exit;
	}
}

$request_url = "https://graph.facebook.com/".$api_ver."/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=500";
adAccounts($request_url, $conn);
//exit;
if($redirect == 'yes') {
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'whatsapp.php';</script>";
	exit();
}
//d($fb_response->paging->next);