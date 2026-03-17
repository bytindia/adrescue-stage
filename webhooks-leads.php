<?php
session_start(); 
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
error_log( "Hello, errors!" );

date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';
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
function postCurl($postURL, $postVal) {	
	$ch = curl_init();	
	curl_setopt($ch, CURLOPT_URL, $postURL);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postVal);
	curl_setopt($ch, CURLOPT_POSTREDIR, 3);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec($ch); 
	curl_close($ch);
	return $output;
}

function utf8ize_recursive($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize_recursive($value);
        }
    } elseif (is_string($mixed)) {
        if (!mb_detect_encoding($mixed, 'UTF-8', true)) {
            $mixed = utf8_encode($mixed);
        }
    }
    return $mixed;
}

$input = json_decode(file_get_contents('php://input'), true);

if(isset($input["entry"][0]["changes"][0]["field"]) && $input["entry"][0]["changes"][0]["field"]=='feed') 
{
	//Page feed
	//include 'email/mail-feed.php';
	if(isset($input["entry"][0]["changes"][0]["value"]["item"]) 
	&& isset($input["entry"][0]["changes"][0]["value"]["verb"]) 
	&& ($input["entry"][0]["changes"][0]["value"]["item"]=='photo' || $input["entry"][0]["changes"][0]["value"]["item"]=='video') 
	&& $input["entry"][0]["changes"][0]["value"]["verb"]=='add') {

		$page_post_id = $input["entry"][0]["changes"][0]["value"]["post_id"];
		$postVal = array(
			'user_id' => $input["entry"][0]["changes"][0]["value"]["from"]["id"],
			'page_id' => $input["entry"][0]["id"],
			'post_id'=> $page_post_id
		);
		$postURL = 'https://stage.adrescue.in/post-promotion-post.php';
		$webHooks = postCurl($postURL, $postVal);
		//include 'casa/test-post-eng-ad.php';
		
	}
	exit;
} 
else if(isset($input["entry"][0]["changes"][0]["field"]) && $input["entry"][0]["changes"][0]["field"]=='leadgen') 
{
	// LeadGen
	$challenge = $_REQUEST['hub_challenge'];
	$verify_token = $_REQUEST['hub_verify_token'];

	if ($verify_token === 'bytads2019') {
	echo $challenge;
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

	$app_id = '594832897646145';
	$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

	error_log(print_r($input, true));
	if($access_token!='') {  
		if (!$tok_req = curl_get_file_contents($tok_url)) { 
			$pg = 'webhooks-leads.php';      
			//include 'email/mail-error.php';
			//exit;
		} 
	}


	

	function getLead($leadgen_id, $form_id, $user_access_token, $pg_token,$api_ver ) {
		//fetch lead info from FB API
		$graph_url= 'https://graph.facebook.com/'.$api_ver.'/'.$leadgen_id.'?fields=created_time,id,campaign_name,ad_name,adset_name,field_data,platform,campaign_id&access_token='.$user_access_token;
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
		
		return array('lead' => $lead, 'form' => $formdata, 'campaign' => $leaddata->campaign_name, 'adName' => $leaddata->ad_name, 'adsetName' => $leaddata->adset_name, 'platform' => $leaddata->platform, 'campaign_id' => $leaddata->campaign_id,); 
	}

	//$input = json_decode(file_get_contents('php://input'), true);
	
	$leadgen_id = $input["entry"][0]["changes"][0]["value"]["leadgen_id"];
	$form_id = $input["entry"][0]["changes"][0]["value"]["form_id"];
	$page_id = $input["entry"][0]["changes"][0]["value"]["page_id"];
	$created_time = $input["entry"][0]["changes"][0]["value"]["created_time"];
	/*$leadgen_id =1863494407090221;
	$form_id =1862151637224498;
	$page_id = 303145299754605;*/

	$q3 = "SELECT tbl_id, client_name, email_ids, words_like, emails_to, webhook_url, googlesheet, googlesheet_id, googlesheet_tab, uid, sheet_like, sheet_tabs FROM leads_acc WHERE uid='".$uid."' && pg_id='".$page_id."' && delete_status='0' limit 0,1";
	$r3 = mysqli_query($conn, $q3);

	if(mysqli_num_rows($r3)>0) 
	{
		$sheet_like = $sheet_tabs = $words_like = $emails_to = array();
		$row3 = mysqli_fetch_assoc($r3);
		
		if($row3['words_like']!='') { $words_like = unserialize($row3['words_like']); }
		if($row3['emails_to']!='') { $emails_to = unserialize($row3['emails_to']); } 

		if($row3['sheet_like']!='') { $sheet_like = unserialize($row3['sheet_like']); }
		if($row3['sheet_tabs']!='') { $sheet_tabs = unserialize($row3['sheet_tabs']); } 

		$query2 = "SELECT pg_id, pg_name, pg_token FROM pages WHERE uid='".$uid."' && pg_id='".$page_id."'";
		$result2 = mysqli_query($conn, $query2);
		$row2 = mysqli_fetch_assoc($result2);
		$pg_token = $row2['pg_token']; 
		$pg_name = $row2['pg_name']; 


		/*$leads = print_r($_POST, true);
		$emailIds = '';
		include 'email/mail-leads.php';	exit;*/
		//Token - you must generate this in the FB API Explorer - tip: exchange it to a long-lived (valid 60 days) token
		$user_access_token = $access_token;
		
		//Get the lead info
		$lead = getLead($leadgen_id, $form_id, $user_access_token, $pg_token,$api_ver); //get lead info
		//d($lead['form']->name); exit;
		$adN = $lead['adName'];
		$adsetN = $lead['adsetName'];
		$campN = $lead['campaign'];
		$platform = $lead['platform'];
		$campId = $lead['campaign_id'];
		
		$formName = trim($lead['form']->name);
		$formName_s = strtolower(trim($lead['form']->name));
		foreach ($words_like as $key => $val) {
			//if (strstr($string, $url)) { // mine version
			if ($val!='' && strpos($formName_s, strtolower($val)) !== FALSE) { // Yoshi version
				$ClientEmail= $emails_to[$key]; 
				$refProject = strtolower($val); 
				break;
			}
		}
		
		if($ClientEmail=='') { $ClientEmail = $row3['email_ids']; }
		
		$mail_body="<html><body><h2>Form : $formName </h2>";
		$mail_body.="<br>
		<style>
		table{
			border-collapse: separate;
			border-spacing: 10px; /* Apply cell spacing */
		}
		table, th, td{
			border: 1px solid #666;
		}
		table th, table td{
			padding: 5px; /* Apply cell padding */
		}
	</style>
		<table border='1' style='border-collapse: collapse; border-spacing: 10px;'  cellpadding='10' cellspacing='10'>
		";
		$subject ='';
		if($page_id==105867278406249 || $page_id==107598684916081 || $page_id==101134905839359 || $page_id==105392468732698 || $page_id==107823568484938) { //Trust Realty
				$formName_s = strtolower($formName);

				if ($page_id==105392468732698 || $page_id==107823568484938) { // Yoshi version
					$obwProj = 'Park Estate'; $obwProjN = '';
					$subject = 'DLF Plots - FB Lead Enquiry - '.html_entity_decode($formName);
					$projCode = 0021;
				} else if (strpos($formName_s, strtolower('obw')) !== FALSE) { // Yoshi version
					$obwProj = 'OBW'; $obwProjN = 'OBW or Kessaku';
					$subject = 'OBW - FB Lead Enquiry - '.html_entity_decode($formName);
				} else if (strpos($formName_s, strtolower('kochi')) !== FALSE) { 
					$obwProj = $obwProjN = 'DLF';
					$subject = 'DLF - NTH - FB Lead Enquiry - '.html_entity_decode($formName);
					$projCode = 156;
				} else if (strpos($formName_s, strtolower('asv')) !== FALSE) { 
					$obwProj = $obwProjN = 'ASV';
					$subject = 'ASV - FB Lead Enquiry - '.html_entity_decode($formName);
					$projCode = 221;
					$ClientEmail = 'asvalexandria@anarock.com, Mohammed.Shariff@anarock.com, thetrustrealty@gmail.com';
				}
				else {
					$obwProj = $obwProjN = 'DLF';
					$subject = 'DLF Commanders Court - FB Lead Enquiry - '.html_entity_decode($formName);
					$projCode = 221;
				}

				foreach($lead['lead'] as $attr=>$val) 
				{
					$fields = ucwords(str_replace("_"," ",$attr));
					if($attr=='full_name') { $fields='Name of the client'; }
					if($attr=='email') { $fields='Email ID of the client'; }
					if($attr=='phone_number') { $fields='Number of the client'; }
					$mail_body.= "<tr><td>".$fields."</td><td> ".$val."</td></tr>";
				}
				$mail_body.= "<tr><td>Project: ".$obwProjN." </td><td> ".$obwProj."</td></tr>";
				$mail_body.= "<tr><td>Source: Agent</td><td> Syed Faheem Ahmed</td></tr>";
				$mail_body.= "<tr><td>Sub Source</td><td> Trust Realty</td></tr>";
				$mail_body.= "<tr><td>STM assigned</td><td> </td></tr>";
		} else {
				$formName_s = strtolower($formName);
				if($page_id==253509791497350) {
					if (strpos($formName_s, 'corniche') !== false) {
						//echo 'true'; 
					} else {
						//exit;
					}
					
				}
				foreach($lead['lead'] as $attr=>$val) 
				{
					//$fields = ucwords(str_replace("_"," ",$attr));
					if($attr!='Project') { //RWD conditions - tracking prameter
						$mail_body.= "<tr><td>".$attr."</td><td> ".$val."</td></tr>";
					}
				}
				$mail_body.= "<tr><td>Platform</td><td> ".mysqli_real_escape_string($conn, $platform)."</td></tr>";
				$mail_body.= "<tr><td>Campaign</td><td> ".mysqli_real_escape_string($conn, $campN)."</td></tr>";
				$mail_body.= "<tr><td>Adset</td><td> ".mysqli_real_escape_string($conn, $adsetN)."</td></tr>";
				$mail_body.= "<tr><td>Ad</td><td> ".mysqli_real_escape_string($conn, $adN)."</td></tr>";
		}
		
		$mail_body.="
		</table><br>
		</body></html>";

		if(count($lead['lead'])==0){
			exit;
		}

		$q_lg = "SELECT tbl_id FROM leads WHERE leadgen_id='".$leadgen_id."'";
		$r_lg = mysqli_query($conn, $q_lg);
		
		if(mysqli_num_rows($r_lg)==0) 
		{	
			$leads = print_r($_POST, true);
			
			$postURL = trim($row3['webhook_url']);	
			if($postURL!='') {	
				$cleanLead = utf8ize_recursive($lead['lead']);

				$postVal = array(
					'pageId'=>$page_id,
					'formId'=>$form_id,
					'formName'=>mysqli_real_escape_string($conn, $formName),
					'leadId'=>$leadgen_id,
					'refName'=>$refProject,
					'ad'=>mysqli_real_escape_string($conn, $adN),
					'adset'=>mysqli_real_escape_string($conn, $adsetN),
					'campaign'=>mysqli_real_escape_string($conn, $campN),
					'created_unix'=>$created_time,
					'lead'=> serialize($cleanLead),
					'projCode'=> $projCode,
					'platform'=> $platform
				);
				
				//$webHooks = postCurl($postURL, $postVal);
				//FOR DLF
				if($page_id==105867278406249 || $page_id==105392468732698 || $page_id==107823568484938) { //Trust Realty
					$formName_s = strtolower($formName);
					if (strpos($formName_s, strtolower('obw')) !== FALSE) { // Yoshi version
						
					} else if (strpos($formName_s, strtolower('asv')) !== FALSE) { // Yoshi version
						
					} else {
						$webHooks = postCurl($postURL, $postVal); 
					}
				} else {
					$webHooks = postCurl($postURL, $postVal); 
				}

				/*
				$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");		
				fwrite($myfile, $subject.','.$mail_body.','.$ClientEmail.', '.$webHooks );
				fclose($myfile);*/
			}
			//exit;
			//INSERT DATA
			$q4 = "SELECT tbl_id FROM leads_form WHERE uid='".$uid."' && page_id='".$page_id."'";
			$r4 = mysqli_query($conn, $q4);
			
			if (!in_array($page_id, [111946245299330, 164008927291094, 103547999353061])) { // Mahendra Homes start
				
			if(mysqli_num_rows($r4)==0) 
				{
					$InsSql_f = "INSERT INTO leads_form (uid, page_id, form_id, form_name, camp_name, created) VALUES ('".$uid."', '".$page_id."', '".$form_id."', '".mysqli_real_escape_string($conn, $formName)."', '".mysqli_real_escape_string($conn, $campN)."', now());"; 
					mysqli_query($conn, $InsSql_f) or die(mysqli_error());
				}
				
				

				$InsSql = "INSERT INTO leads (page_id, form_id, leadgen_id, lead, refName, created_time, formN, adN, adsetN, campN, camp_id, created) VALUES ('".$page_id."', '".$form_id."', '".$leadgen_id."', '".mysqli_real_escape_string($conn, serialize($lead['lead']))."', '".$refProject."', '".$created_time."', '".mysqli_real_escape_string($conn, $formName)."', '".mysqli_real_escape_string($conn, $adN)."', '".mysqli_real_escape_string($conn, $adsetN)."', '".mysqli_real_escape_string($conn, $campN)."', '".mysqli_real_escape_string($conn, $campId)."', now());"; 
				error_log(print_r($InsSql, true));
				//error_log(print_r($input, true));
				mysqli_query($conn, $InsSql) or die(mysqli_error());
				
				mysqli_query($conn,"INSERT INTO notification_alert (uId,client_name,notify_type,notify_msg,created) VALUES ('".$row3['uid']."', '".$row3['client_name']."', 'Lead','New Lead Captured', NOW())");

				if($page_id==304390736080504) { //VRX
					
					$lName = $lEmail = $lPhone = $lCustom = '';
					$j = 1;
					foreach($lead['lead'] as $attr=>$val) {
						$attr_clean=strtolower(str_replace("_"," ",trim($attr)));

						if($attr_clean=='full name' || $attr_clean=='first name'){ $lName=$val; }
						else if($attr_clean=='email'){ $lEmail=$val; }
						else if($attr_clean=='phone number' || $attr_clean=='phone'){ $lPhone='p:'.$val; }
						else{ $lCustom.='[Q'.$j.']: '.str_replace("_"," ",$attr).' = '.str_replace("_"," ",$val).' ';$j++; }
					}
					$sql_vrx = "INSERT INTO vrx_leads_meta (page_id,form_id,formN,refName,name,email,phone,lead,created_time,created,leadgen_id,adN,adsetN,campN) VALUES ('".$page_id."', '".$form_id."', '" . mysqli_real_escape_string($conn, $formName) . "','','" . mysqli_real_escape_string($conn, $lName) . "','" . mysqli_real_escape_string($conn, $lEmail) . "','" . mysqli_real_escape_string($conn, $lPhone) . "','" . mysqli_real_escape_string($conn, json_encode($lead['lead'])) . "','" . mysqli_real_escape_string($conn, $created_time) . "',NOW(),'" . mysqli_real_escape_string($conn, $leadgen_id) . "','" . mysqli_real_escape_string($conn, $adN) . "','" . mysqli_real_escape_string($conn, $adsetN) . "','" . mysqli_real_escape_string($conn, $campN) . "')";
					mysqli_query($conn, $sql_vrx) or die(mysqli_error($conn));
				}
				

				if($subject=='') {
					$subject = $row3['client_name'].' - FB Lead Enquiry - '.html_entity_decode($formName);
				}

				$emailIds = $ClientEmail;
				$postVal2 = array(
						'subject'=> $subject,
						'body'=> $mail_body,
						'email_ids'=> $ClientEmail
				);
				//postCurl('https://bytindia.com/adsninja-email/mail-leads.php', $postVal2); 
				include 'email/mail-leads.php';  
				echo 'Email Sent!'; 
				error_log('Email Sent!');
			} // Mahendra Homes - End
			
			if(trim($row3['googlesheet'])=='Yes') {
				$timeNow = date('d-m-Y, h:i a');
				$uId = $row3['uid'];
				$sheetName = $row3['client_name'].' - Leads';
				$tbl_id = $row3['tbl_id'];
				$leadData = 'Sheet Name';
				require_once 'google-sheets-api/vendor/autoload.php';
				require_once 'google-sheets-api/class-db.php';
				require_once 'google-sheets-api/config.php';
				$new = 0;
				if($row3['googlesheet_id']==''){
					include 'google-sheets-api/create-sheet.php';
					create_spreadsheet($uId, $tbl_id, $sheetName);
					$new = 1;
				}
				$q3_gs = "SELECT googlesheet_id,googlesheet_tab FROM leads_acc WHERE uid='".$uid."' && pg_id='".$page_id."' && delete_status='0' limit 0,1";
				$r3_gs = mysqli_query($conn, $q3_gs);
				$row3_gs = mysqli_fetch_assoc($r3_gs);
				if($row3_gs['googlesheet_id']!=''){
					include 'google-sheets-api/insert-row.php';
					$spreadsheetId = $row3_gs['googlesheet_id'];
					$sheetTab = $row3_gs['googlesheet_tab'];

					//AnataYogo - Start 
					/*
					$retreat_camp_ids = array(120206085472730706, 120206083038150706);
					if ($page_id=='133892533138413' && in_array($campId, $retreat_camp_ids)) {
						$sheetTab = 'Retreat Leads';
					}*/
					//AnantaYoga - End

					if($new == 1){
						$leadV = [['Name','Email','Phone','Questions','Created','Source','Form','Campaign']];
						append_to_sheet($uId,$tbl_id, $leadV,$spreadsheetId,$sheetTab);
					}
					$lName = $lEmail = $lPhone = $lCustom = '';
					$j = 1;
					foreach($lead['lead'] as $attr=>$val){
						$attr_clean=strtolower(str_replace("_"," ",trim($attr)));

						if($attr_clean=='full name' || $attr_clean=='first name'){ $lName=$val; }
						else if($attr_clean=='email'){ $lEmail=$val; }
						else if($attr_clean=='phone number' || $attr_clean=='phone'){ $lPhone='p:'.$val; }
						else{ $lCustom.='[Q'.$j.']: '.str_replace("_"," ",$attr).' = '.str_replace("_"," ",$val).' ';$j++; }
					}
					
					/*
					//Jain Housing
					$spreadsheetId2='1SObX6G3_j6fyK5EZ1Z3jgeAgXF65KV4rZD0v0WkF-r4';
					if($spreadsheetId==$spreadsheetId2){
						$formName_s = strtolower($formName);
						if (strpos($formName_s, strtolower('byt')) !== FALSE) { } else { exit; }
					}

					

					//Zimson - Bengalore
					$spreadsheetId2='1HnZJabiZsxYVnQX5XlTro6VCqgjyeOc5rUCmKdwk8b0';
					if($spreadsheetId==$spreadsheetId2){
						$formName_s = strtolower($formName);
						if (strpos($formName_s, strtolower('indira')) !== FALSE) { 

							$leadV = [[$lName, $lEmail, $lPhone, $lCustom, $timeNow, $pg_name, 'Facebook',$formName,$campN]];
							append_to_sheet($uId,$tbl_id, $leadV,'1KYL1t3Ah725MzLX_vhOt6o1V_74-04hB9KY4RdA78JQ','Sheet1');

						} //else { exit; }
					}
					*/
					$gsheet_add = 'yes';
					$formName_s = strtolower($formName);
					if ($page_id == '784265891937676') { //RLD - based on Form name
						$gsheet_add = 'no';
						if(strpos($formName_s, 'byt') !== FALSE) { $gsheet_add = 'yes'; }
					} else if ($page_id == '231116700384017') {  //KG - based on Form name
						$gsheet_add = 'no';
						if(strpos($formName_s, 'byt') !== FALSE) { $gsheet_add = 'yes'; }
					} else if ($page_id == '195066580526877') {  //Marutham - based on Form name
						$gsheet_add = 'no';
						if(strpos($formName_s, 'byt') !== FALSE || strpos($formName_s, 'breezehi') !== FALSE) { $gsheet_add = 'yes'; }
					} else if ($page_id == '1099575313463313') {  //Urbando - based on Form name
						$gsheet_add = 'no';
						if(strpos($formName_s, 'byt') !== FALSE) { $gsheet_add = 'yes'; }
					} else if ($page_id == '252779871512710') {  //DRA HOMES - based on Form name
						$gsheet_add = 'no';
						if(strpos($formName_s, 'byt') !== FALSE) { $gsheet_add = 'yes'; }
					} else if ($page_id == '508688052598180') {  //Raunaq - Solitaire
						$gsheet_add = 'no';
						if(strpos($formName_s, 'byt') !== FALSE || strpos($formName_s, 'solitaire') !== FALSE) { $gsheet_add = 'yes'; }
					} else {
						$gsheet_add = 'yes';
					}
					
					if($gsheet_add == 'yes'){

						//Googlesheet tab - project wise
						if(count($sheet_like)>0 && count($sheet_tabs)>0){
							$sheet_like_unique = array_filter($sheet_like);
							if(count($sheet_like_unique)>0){
								foreach ($sheet_like_unique as $key => $val) {
									//if (strstr($string, $url)) { // mine version
									if ($val!='' && strpos($formName_s, strtolower($val)) !== FALSE) { // Yoshi version
										$sheetTab= $sheet_tabs[$key]; 
										//$refProject = strtolower($val); 
										break;
									}
								}
								if($sheetTab == '') { $gsheet_add = 'no'; }
							}
							
						}
						//END
						
						if (in_array($page_id, ['863532840185334'])) { //Sagehill
							$leadV = [[mysqli_real_escape_string($conn, $lName), $lEmail, $lPhone, mysqli_real_escape_string($conn, $lCustom), $timeNow,'Facebook',mysqli_real_escape_string($conn, $formName),mysqli_real_escape_string($conn, $campN), mysqli_real_escape_string($conn, $adsetN), mysqli_real_escape_string($conn, $adN)]];
						} else if (in_array($page_id, ['545242895332414', '837243512796677', '784265891937676', '250018794871097'])) { //MLM, DRA, RLD
							$leadV = [[mysqli_real_escape_string($conn, $lName), $lEmail, $lPhone, mysqli_real_escape_string($conn, $lCustom), $timeNow,'Facebook',mysqli_real_escape_string($conn, $formName),mysqli_real_escape_string($conn, $campN),mysqli_real_escape_string($conn, $adsetN)]];
						} else if($page_id=='356610894388649'){ //KVCET GOOGLESHEET 
							$cusQ = ''; 
							foreach($lead['lead'] as $attr=>$val){
								$attr_clean=strtolower(str_replace("_"," ",trim($attr)));
								if(!in_array($attr_clean,['full name','first name','email','phone number','phone']))
								{
									if($cusQ!=''){ $cusQ.=', '; }
									$cusQ.=str_replace("_"," ",$val);
								}
							}
							$leadV = [[mysqli_real_escape_string($conn, $lName), $lEmail, $lPhone, mysqli_real_escape_string($conn, $cusQ), $timeNow,'Facebook',mysqli_real_escape_string($conn, $formName),mysqli_real_escape_string($conn, $campN),mysqli_real_escape_string($conn, $adsetN)]];
						} else if($gsheet_add == 'yes'){
							$leadV = [[mysqli_real_escape_string($conn, $lName), $lEmail, $lPhone, mysqli_real_escape_string($conn, $lCustom), $timeNow,'Facebook',mysqli_real_escape_string($conn, $formName),mysqli_real_escape_string($conn, $campN)]];
						}
						
						if($gsheet_add == 'yes'){
							append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);
						}
					}
					

					//$InsSqlG = "INSERT INTO leads_spreadsheet_response ( leadgen_id, response, created) VALUES ('".$leadgen_id."', '".json_encode($resG)."', now())"; 
					//mysqli_query($conn, $InsSqlG) or die(mysqli_error());

				}
			} 

		}
		
		
	} 
}