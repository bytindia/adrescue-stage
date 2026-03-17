<?php

function LeadGenTot($arr, $filt) {
	//print_r($arr);
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		//echo count($arr); 
		//echo 3; 
		//d($arr[0]->action_type); exit;
		for($q=0; $q<count($arr); $q++) {
			//foreach($arr[$q] as $v) {
				if(isset($arr[$q]->action_type) && $arr[$q]->action_type==$filt) $r = $arr[$q]->value;	
			//}
		}
	}
	return $r;
	//exit;
}

function FB_Report($url, $fbId, $uId, $conn) {
	
	$fb_obj =  array(0=>'APP_INSTALLS', 1=>'BRAND_AWARENESS', 2=>'CONVERSIONS', 3=>'EVENT_RESPONSES', 4=>'LEAD_GENERATION', 5=>'LINK_CLICKS', 6=>'LOCAL_AWARENESS', 7=>'MESSAGES', 8=>'OFFER_CLAIMS', 9=>'PAGE_LIKES', 10=>'POST_ENGAGEMENT', 11=>'PRODUCT_CATALOG_SALES', 12=>'REACH', 13=>'VIDEO_VIEWS');

	$requests = @file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	$_SESSION['fb_response']  = $fb_response ;
	$fb_response = $_SESSION['fb_response'];
	//d($fb_response);
	$cmpDetail = $cmpObjDetail = array();
	if(isset($fb_response->data) && count($fb_response->data)>0) {
		foreach ($fb_response->data as $key => $res) 
		{		
				$cmpDetail[$res->campaign_id] = $res->campaign_name;
				$cmpObjDetail[$res->campaign_id] = $res->objective;
				$cmpTy = array_search($res->objective, $fb_obj);
				//$act = json_decode($res->actions);
				$leads_lg = LeadGenTot($res->actions, 'leadgen_grouped');		
				$leads_con = LeadGenTot($res->actions, 'offsite_conversion.fb_pixel_lead');	
				//d($res->actions);
				
				$res->relevance_score = 0;
				
				$cirRes = mysqli_query($conn, "select * from fb_reports WHERE camp_id='".$res->campaign_id."' AND fb_acc='".$fbId."' AND uid='".$uId."' AND (st_date='".$res->date_start."' AND en_date='".$res->date_stop."')");	
				if(mysqli_num_rows($cirRes)==0) {
						$cirSql = "INSERT INTO fb_reports (uid, fb_acc, camp_id, camp_ty, spend, reach, imp, clicks, rel_score, actions, outbound_clicks, leads_lg, leads_con, stDt, enDt, st_date, en_date, created) VALUES ('".$uId."', '".$fbId."', '".mysqli_real_escape_string($conn, $res->campaign_id)."', '".$cmpTy."', '".mysqli_real_escape_string($conn, $res->spend)."', '".mysqli_real_escape_string($conn, $res->reach)."', '".mysqli_real_escape_string($conn, $res->impressions)."', '".mysqli_real_escape_string($conn, $res->clicks)."', '".mysqli_real_escape_string($conn, $res->relevance_score)."',  '".mysqli_real_escape_string($conn, serialize($res->actions))."', '".mysqli_real_escape_string($conn, serialize($res->outbound_clicks))."', '".$leads_lg."', '".$leads_con."', '".strtotime($res->date_start)."', '".strtotime($res->date_stop)."', '".$res->date_start."', '".$res->date_stop."', now());"; 
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					} else {
						 $cirSql = "UPDATE fb_reports SET camp_id='".mysqli_real_escape_string($conn, $res->campaign_id)."', leads_lg='".$leads_lg."', leads_con='".$leads_con."', camp_ty='".$cmpTy."', spend='".mysqli_real_escape_string($conn, $res->spend)."', reach='".mysqli_real_escape_string($conn, $res->reach)."', imp='".mysqli_real_escape_string($conn, $res->impressions)."', clicks='".mysqli_real_escape_string($conn, $res->clicks)."', rel_score='".mysqli_real_escape_string($conn, $res->relevance_score)."', actions='".mysqli_real_escape_string($conn, serialize($res->actions))."', outbound_clicks='".mysqli_real_escape_string($conn, serialize($res->outbound_clicks))."', stDt='".strtotime($res->date_start)."', enDt='".strtotime($res->date_stop)."', updated=now() WHERE camp_id='".$res->campaign_id."' AND fb_acc='".$fbId."' AND uid='".$uId."' AND  (st_date='".$res->date_start."' AND en_date='".$res->date_stop."')";
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				}		
				
		}  
		foreach($cmpDetail as $cKey => $cVal)
		{
				$cirRes = mysqli_query($conn, "select * from fb_campaigns WHERE camp_id='".$cKey."' AND fb_acc='".$fbId."' AND uid='".$uId."'");	
				
				if(mysqli_num_rows($cirRes)==0) {
						$cirSql = "INSERT INTO fb_campaigns (uid, fb_acc, camp_id, camp_name, camp_obj, created) VALUES ('".$uId."', '".$fbId."', '".mysqli_real_escape_string($conn, $cKey)."', '".mysqli_real_escape_string($conn, $cVal)."', '".mysqli_real_escape_string($conn, $cmpObjDetail[$cKey])."', now());"; 
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} 				
		}
		if(isset($fb_response->paging->next)) {
			FB_Report($fb_response->paging->next, $fbId, $uId, $conn);
		}
	}
}




function G_Report($csvFile, $gId, $uId, $conn){
	$cmpDetail = $cmpObjDetail = $output =array();
	$file_handle = fopen($csvFile, 'r');
	while (!feof($file_handle) ) {
			$line_of_text[] = fgetcsv($file_handle, 1024);
	}
	fclose($file_handle);
	
	$output = array_slice($line_of_text, 2); 
	$output = array_slice($output, 0, -2);//array_slice($output, -2); 
	
	
	
	foreach ($output as $k => $v) 
	{		
			$cmpDetail[$v[0]] = $v[1];
			$cmpObjDetail[$v[0]] = $v[13];
			
			$cirRes = mysqli_query($conn, "select * from g_reports WHERE camp_id='".$v[0]."' AND g_acc='".$gId."' AND uid='".$uId."' AND stDt='".strtotime($v[14])."' AND enDt='".strtotime($v[14])."' ");	
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO g_reports (uid, g_acc, camp_id, spend, imp, clicks, ctr, conv, cost_conv, avg_cost, avg_cpc, avg_cpm, bounce_rate, time_on_site, stDt, enDt, created) VALUES ('".$uId."', '".$gId."', '".mysqli_real_escape_string($conn, $v[0])."', '".mysqli_real_escape_string($conn, $v[5])."', '".mysqli_real_escape_string($conn, $v[3])."', '".mysqli_real_escape_string($conn, $v[4])."', '".mysqli_real_escape_string($conn, $v[9])."', '".mysqli_real_escape_string($conn, $v[10])."', '".mysqli_real_escape_string($conn, $v[8])."', '".mysqli_real_escape_string($conn, $v[6])."', '".mysqli_real_escape_string($conn, $v[2])."', '".mysqli_real_escape_string($conn, $v[7])."', '".mysqli_real_escape_string($conn, $v[11])."', '".mysqli_real_escape_string($conn, $v[12])."', '".strtotime($v[14])."', '".strtotime($v[14])."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} else {
					$cirSql = "UPDATE g_reports SET camp_id='".mysqli_real_escape_string($conn, $v[0])."', spend='".mysqli_real_escape_string($conn, $v[5])."', imp='".mysqli_real_escape_string($conn, $v[3])."', clicks='".mysqli_real_escape_string($conn, $v[4])."', ctr='".mysqli_real_escape_string($conn, $v[9])."', conv='".mysqli_real_escape_string($conn, $v[10])."', cost_conv='".mysqli_real_escape_string($conn, $v[8])."', avg_cost='".mysqli_real_escape_string($conn, $v[6])."', avg_cpc='".mysqli_real_escape_string($conn, $v[2])."', avg_cpm='".mysqli_real_escape_string($conn, $v[7])."', bounce_rate='".mysqli_real_escape_string($conn, $v[11])."', time_on_site='".mysqli_real_escape_string($conn, $v[12])."', updated=now() WHERE camp_id='".$v[0]."' AND g_acc='".$gId."' AND uid='".$uId."' AND stDt='".strtotime($v[14])."' AND enDt='".strtotime($v[14])."'";
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			}
			
			
	}  
	foreach($cmpDetail as $cKey => $cVal)
	{
			$cirRes = mysqli_query($conn, "select * from g_campaigns WHERE camp_id='".$cKey."' AND g_acc='".$gId."' AND uid='".$uId."'");	
			
			if(mysqli_num_rows($cirRes)==0) {
					$cirSql = "INSERT INTO g_campaigns (uid, g_acc, camp_id, camp_name, status, created) VALUES ('".$uId."', '".$gId."', '".mysqli_real_escape_string($conn, $cKey)."', '".mysqli_real_escape_string($conn, $cVal)."', '".mysqli_real_escape_string($conn, $cmpObjDetail[$cKey])."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 				
	}
	return $line_of_text;
}
