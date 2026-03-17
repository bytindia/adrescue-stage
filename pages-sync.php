<? session_start();
include 'db.php';
include 'config.php';

function FbPages($url, $conn) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	foreach ($fb_response->data as $key => $response) {			
			//echo 'ID: ' . $response->id . '<br />';
			$cirRes = mysqli_query($conn, "select * from pages WHERE pg_id='".$response->id."' AND uid='".$_SESSION['uid']."'");						
			
			//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO pages (uid, pg_id, pg_name, pg_cat, pg_token, admin_delete, created) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->category)."', '".mysqli_real_escape_string($conn, $response->access_token)."', '0', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					$cirSql = "UPDATE pages SET pg_name='".mysqli_real_escape_string($conn, $response->name)."', pg_cat='".mysqli_real_escape_string($conn, $response->category)."', pg_token='".mysqli_real_escape_string($conn, $response->access_token)."', admin_delete='0', updated=now() WHERE pg_id='".$response->id."' AND uid='".$_SESSION['uid']."'";
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

mysqli_query($conn, "UPDATE pages SET admin_delete='1' WHERE uid='".$_SESSION['uid']."'") or die(mysqli_error()); 

$request_url = "https://graph.facebook.com/".$api_ver."/me/accounts?access_token=".$access_token."&fields=name,id,access_token,category&limit=700";
FbPages($request_url, $conn);

$_SESSION['suc'] = 'Successfully Updated!';	
echo "<script>window.location = 'pages.php';</script>";
exit();
//d($fb_response->paging->next);