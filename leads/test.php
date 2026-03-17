<?php /*
$apiKey = '6de981f88f09369d30f2a93c6c906fc9';
	$name = 'Prabhu';		
	$phone = '+919176299011';	
	$leads['email'] = 'abcd@gm.in';	
	if(isset($leads['email'])) { $email = $leads['email']; } else { $email =''; }
	$src = 'Facebook';	
	$sub_src = 'Test';
	$created_unix = 'fb';
	
	$sub_src_sm = 'fb';		

			
	//$post = ['name' => $name, 'email' => $email, 'phone' => $phone, 'src' => $src, 'sub_src' => $sub_src, 'cId' => $cId, 'project' => trim($projectKey)];

	$leadData['leadName'] = $name;
	$leadData['email'] = $email;
	$leadData['mobile'] = $phone;
	$leadData['remarks'] = '';
	$headers = ['Content-Type' => 'application/json', 'charset' => 'utf-8'];
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://app.sell.do/api/leads/create?sell_do[form][lead][name]=".$name."&sell_do[form][lead][email]=".$email."&sell_do[form][lead][phone]=".$phone."&api_key=".$apiKey."&sell_do[form][note][content]=&sell_do[campaign][srd]=".$sub_src."");
	curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($leadData));
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	$responseData = json_decode($response);
	curl_close($ch);
	print_r($response);
exit; */
$conn =  mysqli_connect('localhost', 'fb_ads', getenv('DB_PASS'), 'fb_ads'); 
mysqli_select_db($conn,'fb_ads');

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id='2'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 


function getLead($leadgen_id, $form_id, $user_access_token, $pg_token) {
    
	//fetch lead info from FB API
    $graph_url= 'https://graph.facebook.com/v6.0/'.$leadgen_id."?fields=created_time,id,campaign_name,campaign_id&access_token=".$user_access_token;
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
	
	//print_r($leaddata); exit;
    
	
	 $graph_url2= 'https://graph.facebook.com/v6.0/'.$form_id."?fields=name&access_token=".$pg_token;// exit;
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $graph_url2);
    curl_setopt($ch2, CURLOPT_HEADER, 0);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
    $output2 = curl_exec($ch2); 
    curl_close($ch2);

    //work with the lead data
    $formdata = json_decode($output2);
	
    return array('form' => $formdata, 'campaign' => $leaddata->campaign_name, 'campaign_id' => $leaddata->campaign_id); 
}

$page_id = 466133053541973; //FPL
$query2 = "SELECT pg_id, pg_name, pg_token FROM pages WHERE uid='2' && pg_id='".$page_id."'";
$result2 = mysqli_query($conn, $query2);
$row2 = mysqli_fetch_assoc($result2);
$pg_token = $row2['pg_token']; 
	
echo $q3 = "SELECT page_id, form_id, leadgen_id, tbl_id FROM leads WHERE page_id='".$page_id."' AND feedback IS NOT NULL";
$r3 = mysqli_query($conn, $q3);

while($r=mysqli_fetch_array($r3))
{ 
	$lead = getLead($r['leadgen_id'], $r['form_id'], $access_token, $pg_token);// print_r($lead);
	//echo $lead['campaign']; exit;
	 $q3 = "Update leads set formN='".$lead['form']->name."', campN='".$lead['campaign']."', camp_id='".$lead['campaign_id']."' WHERE tbl_id='".$r['tbl_id']."'";
	 mysqli_query($conn, $q3);
	//print_r($lead);
	// exit;
	//echo $r['tbl_id'].'<br>';
}

echo "<meta http-equiv='refresh' content='10;url=https://adsninja.bytsocial.com/leads/test.php'>";
exit();
	
	