<?php
session_start(); 
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
error_log( "Hello, errors!" );

date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'config.php';
include 'email/config.php';

function curlPage($url) {
    // echo $url;
     $ch = curl_init(); 
     curl_setopt($ch,CURLOPT_URL, $url.'?cron=1');
     curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
     curl_setopt($ch,CURLOPT_HEADER, false); 
     $result=curl_exec($ch);
     curl_close($ch);
    // echo $result;
 }

function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
 }
 function FbPages($url, $conn, $uid) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	foreach ($fb_response->data as $key => $response) {			
			//echo 'ID: ' . $response->id . '<br />';
			$cirRes = mysqli_query($conn, "select * from pages WHERE pg_id='".$response->id."' AND uid='".$uid."'");						
			
			//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO pages (uid, pg_id, pg_name, pg_cat, pg_token, created) VALUES ('".$uid."', '".mysqli_real_escape_string($conn, $response->id)."', '".mysqli_real_escape_string($conn, $response->name)."', '".mysqli_real_escape_string($conn, $response->category)."', '".mysqli_real_escape_string($conn, $response->access_token)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					 $cirSql = "UPDATE pages SET pg_name='".mysqli_real_escape_string($conn, $response->name)."', pg_cat='".mysqli_real_escape_string($conn, $response->category)."', pg_token='".mysqli_real_escape_string($conn, $response->access_token)."', updated=now() WHERE pg_id='".$response->id."' AND uid='".$uid."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  
	if(isset($fb_response->paging->next)) { //exit;
		//echo $fb_response->paging->next;
		FbPages($fb_response->paging->next, $conn);
	} else {
		//exit;
	}
}

$uid =2;
	$ClientEmail = $refProject = '';
	$projCode = 0;
	$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id='".$uid."'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	$access_token = $row['access_token']; 
	$g_mcc = $row['g_mcc']; 
	$g_refresh_token = $row['g_refresh_token']; 
	//print_r($row); exit;

	$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

	//error_log(print_r($input, true));
	if($access_token!='') {  
		if (!$tok_req = curl_get_file_contents($tok_url)) { 
			$pg = 'cron-token-update.php';      
			include 'email/mail-error.php';
			exit;
		} 
	}

	$tok_url2 = "https://graph.facebook.com/".$api_ver."/oauth/access_token?grant_type=fb_exchange_token&client_id=".$app_id."&client_secret=".$app_secret."&fb_exchange_token=".$access_token."";
    
    $tok_req2 = curl_get_file_contents($tok_url2);
    $acctok = json_decode($tok_req2,true);
    if(isset($acctok['access_token'])) {
       // print_r($acctok);
        $tok = $acctok['access_token']; 
        mysqli_query($conn, "UPDATE users SET access_token='".$tok."' WHERE tbl_id='".$uid."'") or die(mysqli_error()); 
        //curlPage('https://stage.adrescue.in/adAccounts.php'); 

        $request_url = "https://graph.facebook.com/".$api_ver."/me/accounts?access_token=".$tok."&fields=name,id,access_token,category&limit=500";
       FbPages($request_url, $conn, $uid);
    }
    
    