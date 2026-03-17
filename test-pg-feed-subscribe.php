<?php 
include 'header.php'; 

$pg_access_token='REDACTED_FB_TOKEN';

$graph_url= 'https://graph.facebook.com/'.$api_ver.'/303145299754605/subscribed_apps?subscribed_fields=feed,leadgen';
    $acc_tok = array('access_token'=>$pg_access_token);
	//echo '<br>'.$pg_access_token;
	$ch = curl_init($graph_url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $acc_tok);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);	
	curl_close($ch);
	//print_r($result); //exit;
    return json_decode($result);