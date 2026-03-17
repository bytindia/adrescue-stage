<?php
date_default_timezone_set('Asia/Kolkata');
function postCurl($postURL, $postVal) {	
	 $ch = curl_init($postURL);
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVal));
	 curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	 $response = curl_exec($ch);
	 curl_close($ch);
     return $response;
}

//page_id --> 1=>'ARCHITECTURE', 2=>'ASC', 3=>'BRAND', 4=>'ENGG', 5=>'LAW', 6=>'MBA', 7=>'PG', 8=>'PHARMACY', 9=>'PUBLIC_POLICY'
$postVal = array(
    'page_id' => 1,
    'name' => '',
    'email'=> '',
    'phone' => '4','',
    'state'=> '',
    'city' => '',
    'programme'=> '',
    'utm_source' => '',
    'utm_campaign'=> '',
    'utm_medium' => '',
    'created'=> date('d-m-Y h:i a')
);

$postURL = 'https://stage.adrescue.in/webhook/crescent-leads.php';
$webHooks = postCurl($postURL, $postVal);

print_r($webHooks);