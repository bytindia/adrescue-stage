<? session_start();
include 'db.php';
include 'config.php';

function audit_adAccounts($url, $conn) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
	$requests = file_get_contents($url);
	$fb_response = json_decode($requests);
	foreach ($fb_response->data as $key => $response) {			
			//echo 'ID: ' . $response->id . '<br />';
			//echo "select * from audit_adAccounts WHERE account_id='".$response->account_id."' AND fb_id='".$_SESSION['fb_id']."' AND uid='".$_SESSION['uid']."'"; exit;
			$cirRes = mysqli_query($conn, "select * from audit_adAccounts WHERE account_id='".$response->account_id."' AND fb_id='".$_SESSION['fb_id']."' AND uid='".$_SESSION['uid']."'");						
			
			//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO audit_adAccounts (uid, fb_id, id, name, account_id, account_status, currency, created) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->account_id)."', '".mysqli_real_escape_string($conn, $response->account_status)."', '".mysqli_real_escape_string($conn, $response->currency)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					$cirSql = "UPDATE audit_adAccounts SET name='".mysqli_real_escape_string($conn, $response->name)."', account_status='".mysqli_real_escape_string($conn, $response->account_status)."', updated=now() WHERE account_id='".$response->account_id."' AND fb_id='".$_SESSION['uid']."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			}
	}  
	if(isset($fb_response->paging->next)) {
		//echo $fb_response->paging->next;
		audit_adAccounts($fb_response->paging->next, $conn);
	} else {
		//exit;
	}
}

$request_url = "https://graph.facebook.com/".$api_ver."/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";
audit_adAccounts($request_url, $conn);

function FbPages($url, $conn) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
	$requests = file_get_contents($url);
	$fb_response = json_decode($requests);
	foreach ($fb_response->data as $key => $response) {			
			//echo 'ID: ' . $response->id . '<br />';
			$cirRes = mysqli_query($conn, "select * from audit_pages WHERE pg_id='".$response->id."' AND uid='".$_SESSION['uid']."'");						
			
			//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO audit_pages (uid, pg_id, pg_name, pg_cat, pg_token, created) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->category)."', '".mysqli_real_escape_string($conn, $response->access_token)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					$cirSql = "UPDATE audit_pages SET pg_name='".mysqli_real_escape_string($conn, $response->name)."', pg_cat='".mysqli_real_escape_string($conn, $response->category)."', pg_token='".mysqli_real_escape_string($conn, $response->access_token)."', updated=now() WHERE pg_id='".$response->id."' AND uid='".$_SESSION['uid']."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  
	if(isset($fb_response->paging->next)) {
		//echo $fb_response->paging->next;
		FbPages($fb_response->paging->next, $conn);
	} else {
		//exit;
	}
}

//$access_token = 'REDACTED_FB_TOKEN';
 
$request_url = "https://graph.facebook.com/".$api_ver."/me/accounts?access_token=".$access_token."&fields=name,id,access_token,category&limit=50";
FbPages($request_url, $conn);

if(isset($_GET['pg'])) { $pg=$_GET['pg']; } else { $pg='add-account.php'; }
$_SESSION['suc'] = 'Successfully Updated!';	
echo "<script>window.location = 'add-account.php';</script>";
exit();
//d($fb_response->paging->next);