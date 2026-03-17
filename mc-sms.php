<?php
	// Account details
	$apiKey = urlencode('bTSRN1eaAjY-CfsMOwq7pVR2vhvglJbMV07Qd6Qldn');
	
	// Contact details
	$group_id = '1068488';
    $numbers = 9176299010  .",". 9840619930 ;
 
	// Prepare data for POST request
	$data = array('apikey' => $apiKey, 'group_id' => $group_id, 'numbers' => $numbers);
 
	// Send the POST request with cURL
	$ch = curl_init('https://api.textlocal.in/create_contacts/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	
	// Process your response here
	echo $response;
?>