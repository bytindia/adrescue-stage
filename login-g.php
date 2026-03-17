<?php
require_once __DIR__.'/auth-vendor/autoload.php';
include 'db.php'; 

session_start();
if (isset($_GET['unset'])) {
	unset($_SESSION['access_token']);
}
$client = new Google_Client();
//$client->setAuthConfig('client_secret_1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com.json');

    $client->setClientId('1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com');
    $client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri('https://stage.adrescue.in/callback-g.php');
    $client->setApplicationName('App Google Integration');
    $client->addScope("profile");
    $client->addScope("email");
    $client->addScope("https://www.googleapis.com/auth/adwords");
    $client->addScope("https://www.googleapis.com/auth/spreadsheets");
    $client->addScope("https://www.googleapis.com/auth/drive.metadata");
    $client->addScope("https://www.googleapis.com/auth/cloud-billing");
    $client->addScope("https://www.googleapis.com/auth/cloud-platform");
    $client->addScope("https://www.googleapis.com/auth/analytics.readonly");
    

    //make sure there is a refresh token in the request for offline access.
    $client->setApprovalPrompt('force');
    $client->setAccessType('offline');
	
//$client->addScope('https://www.googleapis.com/auth/adwords');

// [old hardcoded access token removed]

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	


  $client->setAccessToken($_SESSION['access_token']);
  //$drive = new Google_Service_AdWords($client);
  //$files = $drive->files->listFiles(array())->getItems();
  //echo json_encode($files);
   //print_r($_SESSION['access_token']);
  
  	$oauth2 = new Google_Service_Oauth2($client);
	$userInfo = $oauth2->userinfo->get();
	//print_r($userInfo);
    //print_r($userInfo->id);
    
  	if(isset($_SESSION['uid']) && $_SESSION['uid']!='') {
		$cirSql = "UPDATE users SET g_id='".mysqli_real_escape_string($conn, $userInfo->id)."', g_name='".mysqli_real_escape_string($conn, $userInfo->name)."', g_email='".mysqli_real_escape_string($conn, $userInfo->email)."', g_token='".mysqli_real_escape_string($conn, $_SESSION['access_token']['access_token'])."', g_refresh_token='".mysqli_real_escape_string($conn, $_SESSION['access_token']['refresh_token'])."', updated=now() WHERE tbl_id='".$_SESSION['uid']."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
		echo "<script>window.location = 'index.php';</script>";
    	exit;
	}  
  
	$cirRes = mysqli_query($conn, "SELECT * FROM users WHERE g_id='".$userInfo->id."'");
	if(mysqli_num_rows($cirRes)==0) {
		//$_SESSION['uid']
		$userSql = "INSERT INTO users (g_id, g_name, g_email, g_token, g_refresh_token, created) VALUES ('".mysqli_real_escape_string($conn,$userInfo->id)."', '".mysqli_real_escape_string($conn, $userInfo->name)."', '".mysqli_real_escape_string($conn, $userInfo->email)."', '".mysqli_real_escape_string($conn, $_SESSION['access_token']['access_token'])."',  '".mysqli_real_escape_string($conn, $_SESSION['access_token']['refresh_token'])."', now());"; 
		mysqli_query($conn, $userSql) or die(mysqli_error()); 			  
	} else {
		$cirSql = "UPDATE users SET g_id='".mysqli_real_escape_string($conn, $userInfo->id)."', g_name='".mysqli_real_escape_string($conn, $userInfo->name)."', g_email='".mysqli_real_escape_string($conn, $userInfo->email)."', g_token='".mysqli_real_escape_string($conn, $_SESSION['access_token']['access_token'])."', g_refresh_token='".mysqli_real_escape_string($conn, $_SESSION['access_token']['refresh_token'])."', updated=now() WHERE g_id='".$userInfo->id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
	}
	
   $userRes2 = mysqli_query($conn, "select tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc from users WHERE g_id='".$userInfo->id."'");	
   $getRw2 = mysqli_fetch_assoc($userRes2);
   //$lastId = $getRw['tbl_id']; 
   
   $_SESSION['uid'] = $getRw2['tbl_id'];
   $_SESSION['name'] = $getRw2['name'];
   $_SESSION['fb_id'] = $getRw2['fb_id'];
   $_SESSION['g_id'] = $getRw2['g_id'];
   $_SESSION['g_refresh_token'] = $row['g_refresh_token'];
   $_SESSION['g_token'] = $row['g_token'];
   $_SESSION['g_mcc'] = $row['g_mcc'];
   
	echo "<script>window.location = 'index.php';</script>";
    exit;
  
} else {
  $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/callback-g.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}