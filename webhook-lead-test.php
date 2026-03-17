<?php

$leadgen_id='1606661053099053';
$form_id='855542702424561';
$user_access_token='REDACTED_FB_TOKEN';
$pg_token='REDACTED_FB_TOKEN';

function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

function getLead($leadgen_id, $form_id, $user_access_token, $pg_token,$api_ver ) {
    //fetch lead info from FB API
     $graph_url= 'https://graph.facebook.com/'.$api_ver.'/'.$leadgen_id.'?fields=created_time,id,campaign_name,ad_name,adset_name,field_data&access_token='.$user_access_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graph_url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch); 
    curl_close($ch);

    //work with the lead data
    $leaddata = json_decode($output);
    $lead = [];
	//print_r($leaddata);
    for($i=0;$i<count($leaddata->field_data);$i++) {
        $lead[$leaddata->field_data[$i]->name]=$leaddata->field_data[$i]->values[0];
    }
	
     $graph_url2= 'https://graph.facebook.com/'.$api_ver.'/'.$form_id.'?access_token='.$pg_token; 
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $graph_url2);
    curl_setopt($ch2, CURLOPT_HEADER, 0);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
    $output2 = curl_exec($ch2); 
    curl_close($ch2);

    //work with the lead data
    $formdata = json_decode($output2);
	
    return array('lead' => $lead, 'form' => $formdata, 'campaign' => $leaddata->campaign_name, 'adName' => $leaddata->ad_name, 'adsetName' => $leaddata->adset_name); 
}


$lead = getLead($leadgen_id, $form_id, $user_access_token, $pg_token,$api_ver); //get lead info
d($lead); exit;
