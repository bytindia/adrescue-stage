<?php
date_default_timezone_set('Asia/Kolkata');
function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

$lp_urls = $objSpend = $objValue = array();
function ObjResults($url, $conn, $accId, $pgId, $obj_arr, $rep_id, $marks, $objSpend, $objValue) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); exit;
	foreach($fb_response['data'] as $k => $v) {
					if(array_key_exists($v['objective'], $obj_arr)) {
						$objSpend[$v['objective']][] = $v['spend'];
						$objValue[$v['objective']][] = LeadGen($v['actions'], $obj_arr[$v['objective']]);
					}
	}
	
	if(isset($fb_response['paging']['next'])) {
		ObjResults($fb_response['paging']['next'], $conn, $accId, $pgId, $obj_arr, $rep_id, $marks, $objSpend, $objValue);
	} else {
		$res = '';
		if(isset($objSpend) && count($objSpend)>0) {
			foreach($objSpend as $k => $v) {
						$cpr = @(round(array_sum($v) / array_sum($objValue[$k]), 2));
						$res .= $k.': '.array_sum($objValue[$k]).' | &#8377; '.$cpr.'<>';
			}
		}
		//mysqli_query($conn, "UPDATE audit_data SET obj_cost_result='".$res."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
		//echo $res;
	}
}
 
function AdSet($url, $conn, $accId, $rep_id, $marks) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests);
	if(isset($fb_response->data)) {
		foreach ($fb_response->data as $key => $res) {
					$intr_name = $loc_name = $w_pos_name = $w_emp_name = $pacing_type = $expansion = $bid = '';
					
					if(isset($res->bid_strategy) && $res->bid_strategy!='') { $bid='manual'; } else { $bid='auto'; } 
					
					if(isset($res->is_dynamic_creative) && $res->is_dynamic_creative!='') { $dynamic='Yes'; } else { $dynamic='No'; } 
					if(isset($res->targeting->flexible_spec[0]->interests)) { //d($res->targeting->flexible_spec[0]->interests); 
						 $intr_comma = array_column($res->targeting->flexible_spec[0]->interests, 'name');
						 sort($intr_comma);
						 $intr_name = implode(', ', $intr_comma);
					}
					if(isset($res->targeting->geo_locations->location_types)) {
						 $loc_name = implode(', ', $res->targeting->geo_locations->location_types);
					}
					if(isset($res->targeting->flexible_spec[0]->work_positions)) { //d($res->targeting->flexible_spec[0]->interests); 
						$w_pos_comma = array_column($res->targeting->flexible_spec[0]->work_positions, 'name');
						sort($w_pos_comma);
						$w_pos_name = implode(', ', $w_pos_comma);
					}
					if(isset($res->targeting->flexible_spec[0]->work_employers)) { //d($res->targeting->flexible_spec[0]->interests); 
						$w_emp_comma = array_column($res->targeting->flexible_spec[0]->work_employers, 'name');
						sort($w_emp_comma);
						$w_emp_name = implode(', ', $w_emp_comma);
					}
					/*if(isset($res->targeting->publisher_platforms) && isset($res->targeting->device_platforms) && count($res->targeting->publisher_platforms)==4 && count($res->targeting->device_platforms)==2) { 
						$placement = 'auto';
					} else { 
						$placement = 'manual';
					}*/
					if(isset($res->targeting->targeting_optimization)) {
						 $expansion = $res->targeting->targeting_optimization;
					}	
					if(isset($res->pacing_type[0])) { $pacing_type = $res->pacing_type[0]; } 
					$pubPlat = $devicePlat = 0;
					if(!isset($res->targeting->publisher_platforms) || (isset($res->targeting->publisher_platforms) && count($res->targeting->publisher_platforms)==4)) {
						$pubPlat = 1;
					} 
					if(!isset($res->targeting->device_platforms) || (isset($res->targeting->device_platforms) && count($res->targeting->device_platforms)==2)) {
						$devicePlat = 1;
					} 
					if($pubPlat==1 && $devicePlat == 1) { $placement = 'auto'; } else { $placement = 'manual'; }
		
				$AdsetQ = mysqli_query($conn, "select * from audit_adset WHERE adset_id='".$res->id."' AND accId='".$accId."'");						
				
				if(mysqli_num_rows($AdsetQ)==0) {
						$cirSql = "INSERT INTO audit_adset (accId, adset_id, adset_name, bid_strategy, is_dynamic_creative, adset_type, obj_type, age_group, interests, geo_locations, campaign_id, daily_budget, lifetime_budget, work_employers, work_positions, pacing_type, expansion, placement, status, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res->id)."', '".mysqli_real_escape_string($conn, $res->name)."', '".mysqli_real_escape_string($conn, $bid)."', '".mysqli_real_escape_string($conn, $dynamic)."', '".mysqli_real_escape_string($conn, $res->adcreatives->data[0]->object_type)."', '".mysqli_real_escape_string($conn, $res->insights->data[0]->objective)."', '".mysqli_real_escape_string($conn, $res->targeting->age_min.'-'.$res->targeting->age_max)."', '".mysqli_real_escape_string($conn, $intr_name)."', '".mysqli_real_escape_string($conn, $loc_name)."', '".mysqli_real_escape_string($conn, $res->campaign_id)."', '".mysqli_real_escape_string($conn, $res->daily_budget)."', '".mysqli_real_escape_string($conn, $res->lifetime_budget)."', '".mysqli_real_escape_string($conn, $w_emp_name)."', '".mysqli_real_escape_string($conn, $w_pos_name)."', '".mysqli_real_escape_string($conn, $pacing_type)."', '".mysqli_real_escape_string($conn, $expansion)."',  '".mysqli_real_escape_string($conn, $placement)."', '".mysqli_real_escape_string($conn, $res->status)."', now());"; 
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				} 
		}  
	}
	if(isset($fb_response->paging->next)) {
		AdSet($fb_response->paging->next, $conn, $accId, $rep_id, $marks);
	} else {
		$bid_strategy = $is_dynamic_creative = $adset_type = $interests = $geo_locations = $interests = $work_positions = $placement = $work_employers = array();
		$daily_budget = $lifetime_budget = $day_part = '';
		$placement = $age_group = array();
		$sql_1 = mysqli_query($conn, "SELECT age_group, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND age_group!='' group by age_group");
		while($row1 = mysqli_fetch_array($sql_1)) { $age_group[]  = $row1['age_group'].': '.$row1['tot']; }
		
		//$sql_2 = mysqli_query($conn, "SELECT bid_strategy, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND bid_strategy!='' group by bid_strategy");
		//while($row2 = mysqli_fetch_array($sql_2)) { $bid_strategy[]  = $row2['bid_strategy'].': '.$row2['tot']; }
		
		$sql_3 = mysqli_query($conn, "SELECT is_dynamic_creative, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' group by is_dynamic_creative");
		while($row3 = mysqli_fetch_array($sql_3)) { $is_dynamic_creative[]  = $row3['is_dynamic_creative'].': '.$row3['tot']; 
			if(strpos(strtolower($row3['is_dynamic_creative']), 'yes') == false) { $marks += 5; }
		}
		
		$sql_4 = mysqli_query($conn, "SELECT adset_type, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' group by adset_type");
		while($row4 = mysqli_fetch_array($sql_4)) { if($row4['adset_type']=='PHOTO' || $row4['adset_type']=='VIDEO') { $adset_type[]  = $row4['adset_type'].': '.$row4['tot'];  }
			if(strpos(strtolower($row4['adset_type']), 'video') == false) { $marks += 2; }
			if(strpos(strtolower($row4['adset_type']), 'photo') == false) { $marks += 2; }
		}
		
		$sql_5 = mysqli_query($conn, "SELECT geo_locations, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND geo_locations!='' group by geo_locations");
		while($row5 = mysqli_fetch_array($sql_5)) { $geo_locations[]  = $row5['geo_locations'].': '.$row5['tot']; }
		
		$sql_6 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND daily_budget!=0 AND daily_budget!=''");
		while($row6 = mysqli_fetch_array($sql_6)) { $daily_budget  = 'DAILY: '.$row6['tot']; }
		
		$sql_7 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND pacing_type=0 AND lifetime_budget!=''");
		while($row7 = mysqli_fetch_array($sql_7)) { $lifetime_budget  = 'LIFETIME: '.$row7['tot']; }
		
		$sql_8 = mysqli_query($conn, "SELECT count(tbl_id) as tot, interests FROM `audit_adset` WHERE accId='".$accId."' AND interests!='' group by interests ORDER BY `tot`  DESC");
		while($row8 = mysqli_fetch_array($sql_8)) { $interests[]  = $row8['interests']; }
		if(count($interests)>0) {
			$marks += 5;
			$interests = array_unique($interests);
		}
		
		$sql_9 = mysqli_query($conn, "SELECT count(tbl_id) as tot, work_employers FROM `audit_adset` WHERE accId='".$accId."' AND work_employers!='' group by interests ORDER BY `tot`  DESC");
		while($row9 = mysqli_fetch_array($sql_9)) { $work_employers[]  = $row9['work_employers']; }
		if(count($work_employers)>0) {
			$work_employers = array_unique($work_employers);
		}
		
		$sql_10 = mysqli_query($conn, "SELECT count(tbl_id) as tot, work_positions FROM `audit_adset` WHERE accId='".$accId."' AND work_positions!='' group by work_positions  ORDER BY `tot`  DESC");
		while($row10 = mysqli_fetch_array($sql_10)) { $work_positions[]  = $row10['work_positions']; }
		if(count($work_positions)>0) {
			$work_positions = array_unique($work_positions);
		}
		
		$sql_11 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND pacing_type='day_parting'");
		while($row11 = mysqli_fetch_array($sql_11)) { 
			if($row11['tot']>0) { $day_part = 'Yes'; } else { $day_part = 'No'; }
		}
		
		$sql_12 = mysqli_query($conn, "SELECT count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND expansion='expansion_all'");
		while($row12 = mysqli_fetch_array($sql_12)) { 
			if($row12['tot']>0) { $exp_on = 'No'; $marks += 5; } else { $exp_on = 'Yes'; }
		}
		
		$sql_13 = mysqli_query($conn, "SELECT placement, count(tbl_id) as tot FROM audit_adset WHERE accId='".$accId."' AND placement!='' group by placement");
		while($row13 = mysqli_fetch_array($sql_13)) { $placement[]  = $row13['placement'].': '.$row13['tot']; }
		
		$cirSql = "UPDATE audit_data SET age_group='".mysqli_real_escape_string($conn, implode('<>', $age_group))."', dynamic='".mysqli_real_escape_string($conn, implode('<>', $is_dynamic_creative))."', adset_type='".mysqli_real_escape_string($conn, implode('<>', $adset_type))."', loc_type='".mysqli_real_escape_string($conn, implode('<>', $geo_locations))."', daily_life='".mysqli_real_escape_string($conn, $daily_budget.'<>'.$lifetime_budget)."', interests='".mysqli_real_escape_string($conn, implode('</li><li>', $interests))."', work_pos='".mysqli_real_escape_string($conn, implode('</li><li>', $work_positions))."', work_emp='".mysqli_real_escape_string($conn, implode('</li><li>', $work_employers))."', day_part='".mysqli_real_escape_string($conn, $day_part)."', exp_on='".mysqli_real_escape_string($conn, $exp_on)."', placement='".mysqli_real_escape_string($conn, implode('<>', $placement))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
	}
}

function LeadQus($url, $conn, $accId, $pgId, $rep_id, $marks) { 
	//echo $url; exit;
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				if(isset($res['questions'])) 
				{	
					$fName = $qusAns = $qusAnsKey = array();
					foreach ($res['questions'] as $k => $v) {
						if(isset($v['type']) && $v['type']=='CUSTOM' && $v['key']!='inbox_url'){
							//echo $v['name']; 
							$l_opt ='';
							if(isset($v['options'])) {
									$l_opt_v = array_column($v['options'], 'value');
									$l_opt = implode(', ', $l_opt_v);
							}
							
							$fName[] = $v['name'].' : '.$l_opt;
							$qusAns[] = $v['label'].' : '.$l_opt;
							$qusAnsKey[] = $v['key'];
							
						 	/*
							//$qus_ans = $v['label']..
							$LqQ = mysqli_query($conn, "select * from audit_lead_qus WHERE pg_id='".$pgId."' AND l_id='".$v['id']."'");						
			
							if(mysqli_num_rows($LqQ)==0) {
									$cirSql = "INSERT INTO audit_lead_qus (pg_id, l_id, l_key, l_lab, l_opt, created) VALUES ('".mysqli_real_escape_string($conn, $pgId)."', '".mysqli_real_escape_string($conn, $v['id'])."', '".mysqli_real_escape_string($conn, $v['key'])."', '".mysqli_real_escape_string($conn, $v['label'])."', '".mysqli_real_escape_string($conn, $l_opt)."',  now());"; 
									mysqli_query($conn, $cirSql) or die(mysqli_error()); 
							} */
						}
					}
					if(count($qusAns)>0) {
						//d($qusAns); exit;
						$qusAnsImp = '<b>'.$res['name'].'</b> <>'.implode('<>',$qusAns).'<>';
						$qusAnsKeyImp = implode('<>',$qusAnsKey);
						$LqQ = mysqli_query($conn, "select * from audit_lead_qus WHERE pg_id='".$pgId."' AND l_id='".$res['id'] ."'");						
			
						if(mysqli_num_rows($LqQ)==0) {
									$cirSql = "INSERT INTO audit_lead_qus (pg_id, l_id, l_key, l_lab, created) VALUES ('".mysqli_real_escape_string($conn, $pgId)."', '".mysqli_real_escape_string($conn, $res['id'])."', '".mysqli_real_escape_string($conn, $qusAnsKeyImp)."', '".mysqli_real_escape_string($conn, $qusAnsImp)."',  now());"; 
									mysqli_query($conn, $cirSql) or die(mysqli_error()); 
						}
					}
				}
	 }  
	if(isset($fb_response['paging']['next'])) {
		LeadQus($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks); 
	} else {
		$lQus = $leadQus = array();
		
		$sql_1 = mysqli_query($conn, "SELECT l_lab,l_opt FROM `audit_lead_qus` WHERE pg_id='".$pgId."' GROUP BY l_key");
		while($row1 = mysqli_fetch_array($sql_1)) { $lQus[]  = $row1['l_lab']; }
		
		
		if(count($lQus)>0) {
			$leadQus = implode('<>',$lQus);
			$cirSql = "UPDATE audit_data SET lead_qus='".mysqli_real_escape_string($conn, $leadQus)."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
		}
	}
}

$lp_urls = array();
function Lp_Url($url, $conn, $accId, $pgId, $rep_id, $marks, $lp_urls) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); 
	foreach ($fb_response['data'] as $key => $res) {
				if(isset($res['adcreatives']['data'])) {
					foreach ($res['adcreatives']['data'] as $k => $v) {
						$getURL =  $v['object_story_spec']['link_data']['link'];
						$getURL = strtok($getURL, '?');
						$trimURL = rtrim($getURL,"/");
						if(trim($trimURL)!='') {
							$lp_urls[] = '<a href="loading.php?pg='.$trimURL.'" target="_blank">'.$trimURL.'<a>';
						}
						//echo rtrim($getURL,"/").'<br>';

					}
				}
	 }  //exit;
	
	if(isset($fb_response['paging']['next'])) {
		Lp_Url($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks, $lp_urls);
	} else {
		//d($lp_urls);  exit;
		if(isset($lp_urls) && count($lp_urls)>0) {
			$lp_urls = array_unique($lp_urls);
			$lp_urls = array_filter($lp_urls);  
			$lp_urls = array_values($lp_urls); 
			$cirSql = "UPDATE audit_data SET lp_url='".mysqli_real_escape_string($conn, implode('<>',$lp_urls))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
			mysqli_query($conn, $cirSql) or die(mysqli_error());
		}
		// d($lp_urls);
	}
}

function AdSetInsights($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests, true);
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$adSpend = $lead = $cpr =0;
				
				if(isset($res['adcreatives']['data'][0]['object_type'])) { $adType=$res['adcreatives']['data'][0]['object_type']; } 
				if(isset($res['insights']['data'][0]['objective'])) { $objType=$res['insights']['data'][0]['objective']; } 
				if(isset($res['insights']['data'][0]['spend'])) { $adSpend=$res['insights']['data'][0]['spend']; } 
				if(isset($res['insights']['data'][0]['quality_ranking'])) { $adQty= ucfirst(strtolower(str_replace('_',' ',$res['insights']['data'][0]['quality_ranking']))); } 
				if(isset($res['insights']['data'][0]['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['insights']['data'][0]['actions'], $obj_arr[$objType]);
						$cpr = @(round($adSpend / $lead, 2));
					}
				}
				
			$AdInQ = mysqli_query($conn, "select * from audit_ad WHERE ad_id='".$res['id']."' AND accId='".$accId."' ");						
			
			if(mysqli_num_rows($AdInQ)==0) {
					$cirSql = "INSERT INTO audit_ad (accId, ad_id, adset_id, campaign_id, ad_type, ad_qty, obj_type, spend, lead, cpl, status, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['id'])."', '".mysqli_real_escape_string($conn, $res['adset_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $adType)."', '".mysqli_real_escape_string($conn, $adQty)."', '".mysqli_real_escape_string($conn, $objType)."', '".mysqli_real_escape_string($conn, $adSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $cpr)."', '".mysqli_real_escape_string($conn, $res['status'])."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); //exit;
			} 
	}  
	if(isset($fb_response['paging']['next'])) {
		AdSetInsights($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		$ad_type_res = $ad_type_best = $ad_qty = $lg_0 = $lg_cpl_less25 = $lg_cpl_high25 = $lg_0 = $conv_0 = $conv_cpl_less25 = $conv_cpl_high25 = array();
		
		
		$sql_1 = mysqli_query($conn, "SELECT ad_type, SUM(spend) AS spend, SUM(lead) as lead, (SUM(spend)/SUM(lead)) as per FROM `audit_ad` where accId='".$accId."' AND (obj_type='LEAD_GENERATION' OR obj_type='CONVERSIONS') GROUP BY ad_type");
		while($row1 = mysqli_fetch_array($sql_1)) { if($row1['ad_type']=='PHOTO' || $row1['ad_type']=='VIDEO') { $cpl=@($row1['spend']/$row1['lead']); if($cpl>0) { $ad_type_res[]  = $row1['ad_type'].': '.round($cpl,2); } } }
		
		if(count($ad_type_res)>0) {
			$adty_imp = implode('<>',$ad_type_res);
			$adty_exp = explode('<>',$adty_imp);
			$ad_type_res = array_unique($adty_exp);
		}
		
		$sql_2 = mysqli_query($conn, "SELECT ad_type, min(cpl) AS cpl FROM `audit_ad` WHERE accId='".$accId."' AND cpl!='0' GROUP BY ad_type");
		while($row2 = mysqli_fetch_array($sql_2)) { if($row2['cpl']!='') { $ad_type_best[]  = $row2['ad_type'].': '.round($row2['cpl'],2); } }
		
		if(count($ad_type_best)>0) {
			$adty_imp2 = implode('<>',$ad_type_best);
			$adty_exp2 = explode('<>',$adty_imp2);
			$ad_type_best = array_unique($adty_exp2);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT if(ad_qty IS NULL or ad_qty='','Not Set',ad_qty) AS ad_qty, count(ad_qty) AS tot FROM `audit_ad` WHERE accId='".$accId."'  GROUP BY ad_qty");
		while($row3 = mysqli_fetch_array($sql_3)) {  $ad_qty[]  = $row3['ad_qty'].': '.$row3['tot'];  }
		
		if(count($ad_type_best)>0) {
			$adty_imp3 = implode('<>',$ad_qty);
			$adty_exp3 = explode('<>',$adty_imp3);
			$ad_qty = array_unique($adty_exp3);
		}
		
		
		
		$cirSql = "UPDATE audit_data SET ad_type_res='".mysqli_real_escape_string($conn, implode('<>', $ad_type_res))."', ad_type_best='".mysqli_real_escape_string($conn, implode('<>', $ad_type_best))."', ad_qty='".mysqli_real_escape_string($conn, implode('<>', $ad_qty))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error()); //exit;
	} 
}

		
function Campaign($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = $adSetStatus = '';
				//$cSpend = $adset_tot = $lead = $cpr =0;
				$objType = $res['objective']; 
				if(isset($res['adsets']['data'])) { 
					$adset_tot = count($res['adsets']['data']); 
					if($adset_tot>1) {
						$like = 'ACTIVE';
						$adset_res = array_filter($res['adsets']['data'], function ($item) use ($like) {
							if (stripos($item['status'], $like) !== false) {
								return true;
							}
							return false;
						});
						if(count($adset_res)>1) { $adSetStatus = 'ACTIVE'; }
					}
				} 
				if(isset($res['daily_budget']) || isset($res['lifetime_budget'])) { $budget = 'Yes'; } else { $budget = 'No';  } 
				
				//echo $res['name'].'_'. $cSpend.'_'.$lead .'_'.$cpr.'<br>' ;
				
			$campQ = mysqli_query($conn, "select * from audit_campaign WHERE id='".$res['id']."'");						
			
			if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_campaign (accId, id, name, objective, budget_type, adset_tot, status, adset_status, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['id'])."', '".mysqli_real_escape_string($conn, $res['name'])."', '".mysqli_real_escape_string($conn, $objType)."', '".mysqli_real_escape_string($conn, $budget)."', '".mysqli_real_escape_string($conn, $adset_tot)."', '".mysqli_real_escape_string($conn, $res['status'])."', '".mysqli_real_escape_string($conn, $adSetStatus)."', now());"; //exit;
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  //exit;
	if(isset($fb_response['paging']['next'])) {
		Campaign($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		$ad_type_res = $ad_type_best = $bid_strategy = array();
		$objQ = $objQ2 = '';
		$sql_1 = mysqli_query($conn, "SELECT tbl_id FROM `audit_campaign` WHERE accId='".$accId."' AND adset_tot>1 AND budget_type='Yes'");
		if(mysqli_num_rows($sql_1)>0) { $cbo_camp ='Yes'; $marks += 5; } else { $cbo_camp ='No'; }
		
		$cirSql = "UPDATE audit_data SET cbo_camp='".mysqli_real_escape_string($conn, $cbo_camp)."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
		
		$result1=mysqli_query($conn, "SELECT count(*) as total FROM `audit_campaign` WHERE accId='".$accId."'");
		$data1=mysqli_fetch_assoc($result1);
		
		$result2=mysqli_query($conn, "SELECT count(*) as total FROM `audit_campaign` WHERE accId='".$accId."' and status='ACTIVE' and adset_status='ACTIVE'");
		$data2=mysqli_fetch_assoc($result2);
		if($data1['total']=='') { $data1['total']=0; }
		if($data2['total']=='') { $data2['total']=0; }
		
		$sql_22 = mysqli_query($conn, "SELECT a.bid_strategy, count(a.tbl_id) as tot FROM `audit_adset` as a, audit_campaign as b WHERE a.campaign_id=b.id AND a.accId='".$accId."' AND (b.objective='LEAD_GENERATION' OR b.objective='CONVERSIONS')  group by a.bid_strategy");
		while($row22 = mysqli_fetch_array($sql_22)) { $bid_strategy[]  = $row22['bid_strategy'].': '.$row22['tot']; }
		
		//mysqli_query($conn, "UPDATE audit_data SET camp_tot='".$data2['total']." / ".$data1['total']."', bid_type='".mysqli_real_escape_string($conn, implode('<>', $bid_strategy))."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
		mysqli_query($conn, "UPDATE audit_data SET camp_tot='".$data1['total']."', bid_type='".mysqli_real_escape_string($conn, implode('<>', $bid_strategy))."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
		
	}
}


$cust_aud_web_25 = array(); $top25_web = 'No';
function CustAud_25($url, $conn, $accId, $pgId, $rep_id, $marks, $cust_aud_web_25) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				if(isset($res['rule_aggregation'])) { $rule_aggregation = json_decode($res['rule_aggregation'],true); } else { $rule_aggregation = array(); }
				if(isset($rule_aggregation['type']) && $rule_aggregation['type']=='time_spent' && $rule_aggregation['lower_bound']>=75) {
						$cust_aud_web_25[] = 1;
				}
	 }  
	if(isset($fb_response['paging']['next'])) {
		CustAud_25($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks, $cust_aud_web_25);
	} else {
		if(isset($cust_aud_web_25) && count($cust_aud_web_25)>0) { 
			$top25_web = 'Yes'; $marks += 8;
			mysqli_query($conn, "UPDATE audit_data SET top_web_25='".$top25_web."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
		}
	}
}

$adRule = array(); 
function adRule($url, $conn, $accId, $pgId, $rep_id, $marks, $adRule) { 
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				if(isset($res['status']) && $res['status']=='ENABLED') {
						$adRule[] = 1;
				}
	 }  
	if(isset($fb_response['paging']['next'])) {
		adRule($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks, $adRule);
	} else {
		$adRule_v = 'No';
		if(isset($adRule) && count($adRule)>0) { 
			$adRule_v = 'Yes'; $marks += 5;
		}
		mysqli_query($conn, "UPDATE audit_data SET rules='".$adRule_v."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
	}
}

$custAud = array(); $laAud = array();
function CustAud($url, $conn, $accId, $pgId, $rep_id, $marks, $custAud, $laAud) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests,true);
	//d($fb_response); exit;
	foreach ($fb_response['data'] as $key => $res) {
				if((isset($res['subtype']) && isset($res['ads']) && $res['subtype']=='CUSTOM')) { $custAud[] = 1;  } 
				if(isset($res['subtype']) && isset($res['ads']) && $res['subtype']=='LOOKALIKE') { $laAud[] = 1; } 
	 }  
	if(isset($fb_response['paging']['next'])) {
		CustAud($fb_response['paging']['next'], $conn, $accId, $pgId, $rep_id, $marks, $custAud, $laAud);
	} else {
		 $custAud_v = $laAud_v = 'No';
		if(isset($custAud) && count($custAud)>0) { 
			$custAud_v = 'Yes'; $marks += 5;
		}
		if(isset($laAud) && count($laAud)>0) { 
			$laAud_v = 'Yes'; $marks += 5;
		}
		mysqli_query($conn, "UPDATE audit_data SET cust_aud='".$custAud_v."', la_aud='".$laAud_v."' WHERE acc_id=".$accId." AND rep_id='".$rep_id."'") or die(mysqli_error());
	}
}
function CampaignInsights($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$cSpend = $adset_tot = $lead = $cpr =0;
				$objType = $res['objective']; 
				if(isset($res['spend'])) { $cSpend=$res['spend']; } 
				if(isset($res['daily_budget']) || isset($res['lifetime_budget'])) { $budget = 'Yes'; } else { $budget = 'No';  } 
				if(isset($res['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['actions'], $obj_arr[$objType]);
						$cpr = @(round($cSpend / $lead, 2));
					}
				}
				
				//echo $res['campaign_name'].'_'. $cSpend.'_'.$lead .'_'.$cpr.'<br>' ; exit;
				
			$campQ = mysqli_query($conn, "select * from audit_campaign WHERE id='".$res['campaign_id']."'");						
			
			if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_campaignIns (accId, id, name, objective, spend, leads, cpl, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_name'])."', '".mysqli_real_escape_string($conn, $res['objective'])."', '".mysqli_real_escape_string($conn, $cSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $cpr)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  //exit;
	if(isset($fb_response['paging']['next'])) {
		CampaignInsights($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		
		$ad_type_res = $ad_type_best = $cmp_high = $cmp_high_conv = array();
		$objQ = $objQ2 = '';
		
		$sql_2 = mysqli_query($conn, "SELECT id, name, objective, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM `audit_campaignIns` WHERE accId='".$accId."' AND objective='LEAD_GENERATION' GROUP by objective");
		
		while($row2 = mysqli_fetch_array($sql_2)) {  if($row2['c']>0) { $objQ .= " (objective ='".$row2['objective']."' AND cpl>".round($row2['c'],2).") OR "; } }
		if($objQ!='') {
			$objQ = ' WHERE accId='.$accId.' AND cpl!=0 AND '.substr($objQ, 0, -4) ;
			//echo "SELECT name,cpl FROM `audit_campaignIns` {$objQ}"; 
			$sql_3 = mysqli_query($conn, "SELECT name,cpl FROM `audit_campaignIns` {$objQ}");
			while($row3 = mysqli_fetch_array($sql_3)) {  $cmp_high[]  = $row3['name'].': '.round($row3['cpl'],2);  }
			
			if(count($cmp_high)>0) {
				$cmp_imp3 = implode('<>',$cmp_high);
				$cmp_exp3 = explode('<>',$cmp_imp3);
				$cmp_high = array_unique($cmp_exp3);
			}
		}
		
		$sql_3 = mysqli_query($conn, "SELECT id, name, objective, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM `audit_campaignIns` WHERE accId='".$accId."' AND objective='CONVERSIONS' GROUP by objective");
		
		while($row3 = mysqli_fetch_array($sql_3)) {  if($row3['c']>0) { $objQ2 .= " (objective ='".$row3['objective']."' AND cpl>".round($row3['c'],2).") OR "; } }
		if($objQ2!='') {
			$objQ2 = ' WHERE accId='.$accId.' AND cpl!=0 AND '.substr($objQ2, 0, -4) ;
			//echo "SELECT name,cpl FROM `audit_campaignIns` {$objQ2}"; 
			$sql_3 = mysqli_query($conn, "SELECT name,cpl FROM `audit_campaignIns` {$objQ2}");
			while($row3 = mysqli_fetch_array($sql_3)) {  $cmp_high_conv[]  = $row3['name'].': '.round($row3['cpl'],2);  }
			
			if(count($cmp_high_conv)>0) {
				$cmp_imp4 = implode('<>',$cmp_high_conv);
				$cmp_exp4 = explode('<>',$cmp_imp4);
				$cmp_high_conv = array_unique($cmp_exp4);
			}
		}
		
		
		$cirSql = "UPDATE audit_data SET high_cpl='".mysqli_real_escape_string($conn, implode('<>', $cmp_high))."', high_cpl_conv='".mysqli_real_escape_string($conn, implode('<>', $cmp_high_conv))."', updated=now() WHERE acc_id='".$accId."' AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
		
	}
}



function AdInsights($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); //exit;
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$cSpend = $adset_tot = $lead = $cpr =0;
				$objType = $res['objective']; 
				if(isset($res['spend'])) { $cSpend=$res['spend']; } 
				if(isset($res['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['actions'], $obj_arr[$objType]);
						$cpr = @(round($cSpend / $lead, 2));
					}
				}
				
				//echo $res['campaign_name'].'_'. $cSpend.'_'.$lead .'_'.$cpr.'<br>' ; exit;
				
			$campQ = mysqli_query($conn, "select * from audit_adIns WHERE ad_id='".$res['ad_id']."'");						
			
			if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_adIns (accId, ad_id, adset_id, campaign_id, ad_name, adset_name, campaign_name, spend, leads, obj_type, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['ad_id'])."', '".mysqli_real_escape_string($conn, $res['adset_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $res['ad_name'])."', '".mysqli_real_escape_string($conn, $res['adset_name'])."', '".mysqli_real_escape_string($conn, $res['campaign_name'])."', '".mysqli_real_escape_string($conn, $cSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $objType)."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  //exit;
	if(isset($fb_response['paging']['next'])) {
		AdInsights($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		$adsetURL = 'https://www.facebook.com/adsmanager/manage/ads';
		$ad_type_res = $ad_type_best = $obj_wise_res_arr = $cmp_high_arr = $cmp_high_conv_arr = $lg_0_arr = $lg_cpl_less25_arr = $lg_cpl_high25_arr = $lg_0_arr = $conv_0_arr = $conv_cpl_less25_arr = $conv_cpl_high25_arr = $ad_type_res = array(); 
		$obj_wise_res = $cmp_high = $cmp_high_conv = $lg_0 = $lg_cpl_less25 = $lg_cpl_high25 = $lg_0 = $conv_0 = $conv_cpl_less25 = $conv_cpl_high25 = $objQ = $objQ2 = '';
		
		
		
		$sql_2 = mysqli_query($conn, "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' GROUP by obj_type");
		
		while($row2 = mysqli_fetch_array($sql_2)) {  
			//if($row2['c']>0) { $objQ .= " (obj_type ='".$row2['obj_type']."' AND cpl>".round($row2['c'],2).") OR "; } 
			if($row2['c']>0) {
				$sql_3 = mysqli_query($conn, "SELECT campaign_name, campaign_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row2['obj_type']."' GROUP BY campaign_id HAVING c>".round($row2['c'],2)." ORDER BY c DESC");
				
				while($row3 = mysqli_fetch_array($sql_3)) {  $cmp_high_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row3['campaign_id'].'" target="_blank">'.$row3['campaign_name'].'</td><td>'.round($row3['a']).'</a></td><td>'.round($row3['b']).'</td><td>'.round($row3['c']).'</td></tr>';  }
				
				if(count($cmp_high_arr)>0) {
					$cmp_high = '<table id="TABLE_1" class="table table-bordered dt-responsive compact"><thead><tr><th>Campaign</th><th>Spend &#8377;</th><th>Leads</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$cmp_high_arr).'</tbody></table>';
				}
				
				$per1=round(($row2['a']/$row2['b'])*1.25);
				$per2=round(($row2['a']/$row2['b'])*0.75);
					
				$sql_61 = mysqli_query($conn, "SELECT adset_name, campaign_id, adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row2['obj_type']."' GROUP BY adset_id HAVING b>0 AND c>{$per1} ORDER BY c DESC");
				while($row_61 = mysqli_fetch_array($sql_61)) {  $lg_cpl_less25_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row_61['campaign_id'].'&selected_adset_ids='.['adset_id'].'" target="_blank">'.$row_61['adset_name'].'</a></td><td>'.round($row_61['a']).'</td><td>'.$row_61['b'].'</td><td>'.round($row_61['c']).'</td></tr>';  }
				if(count($lg_cpl_less25_arr)>0) {
					$lg_cpl_less25 = '<table id="TABLE_2" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Spend &#8377;</th><th>Leads</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$lg_cpl_less25_arr).'</tbody></table>';
				}
				
				$sql_62 = mysqli_query($conn, "SELECT adset_name, campaign_id, adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row2['obj_type']."' GROUP BY adset_id HAVING b>0 AND c<{$per2} ORDER BY c DESC");
				while($row_62 = mysqli_fetch_array($sql_62)) {  $lg_cpl_high25_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row_62['campaign_id'].'&selected_adset_ids='.['adset_id'].'" target="_blank">'.$row_62['adset_name'].'</a></td><td>'.round($row_62['a']).'</td><td>'.$row_62['b'].'</td><td>'.round($row_62['c']).'</td></tr>';  }
				if(count($lg_cpl_high25_arr)>0) {
					$lg_cpl_high25 = '<table id="TABLE_3" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Spend &#8377;</th><th>Leads</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$lg_cpl_high25_arr).'</tbody></table>';
				}
			}
		}
		
		
		$sql_4 = mysqli_query($conn, "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type='CONVERSIONS' GROUP by obj_type");
		
		while($row4 = mysqli_fetch_array($sql_4)) {  //if($row4['c']>0) { $objQ2 .= " (objective ='".$row4['objective']."' AND cpl>".round($row4['c'],2).") OR "; } 
			if($row4['c']>0) {
				
				$sql_5 = mysqli_query($conn, "SELECT campaign_name, campaign_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row4['obj_type']."' GROUP BY campaign_id HAVING c>".round($row4['c'],2)." ORDER BY c DESC");
				while($row5 = mysqli_fetch_array($sql_5)) {  $cmp_high_conv_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row5['campaign_id'].'" target="_blank">'.$row5['campaign_name'].'</a></td><td>'.round($row5['a']).'</td><td>'.round($row5['b']).'</td><td>'.round($row5['c']).'</td></tr>';  }
				
				if(count($cmp_high_conv_arr)>0) {
					$cmp_high_conv = '<table id="TABLE_4" class="table table-bordered dt-responsive compact"><thead><tr><th>Campaign</th><th>Spend &#8377;</th><th>Leads</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$cmp_high_conv_arr).'</tbody></table>';  
				}
				
				$per1=round(($row4['a']/$row4['b'])*1.25);
				$per2=round(($row4['a']/$row4['b'])*0.75);
					//echo "SELECT adset_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row4['obj_type']."' GROUP BY adset_id HAVING b>0 AND c>{$per1}";
				$sql_61 = mysqli_query($conn, "SELECT adset_name, campaign_id, adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row4['obj_type']."' GROUP BY adset_id HAVING b>0 AND c>{$per1} ORDER BY c DESC");
				while($row_61 = mysqli_fetch_array($sql_61)) {  $conv_cpl_less25_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row_61['campaign_id'].'&selected_adset_ids='.$row_61['adset_id'].'">'.$row_61['adset_name'].'</a></td><td>'.round($row_61['a']).'</td><td>'.$row_61['b'].'</td><td>'.round($row_61['c']).'</td></tr>';  }
				if(count($conv_cpl_less25_arr)>0) {
					$conv_cpl_less25 = '<table id="TABLE_5" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Spend &#8377;</th><th>Leads</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$conv_cpl_less25_arr).'</tbody></table>';
				}
				
				$sql_62 = mysqli_query($conn, "SELECT adset_name, campaign_id, adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type ='".$row4['obj_type']."' GROUP BY adset_id HAVING b>0 AND c<{$per2} ORDER BY c DESC");
				while($row_62 = mysqli_fetch_array($sql_62)) {  $conv_cpl_high25_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row_62['campaign_id'].'&selected_adset_ids='.['adset_id'].'" target="_blank">'.$row_62['adset_name'].'</a></td><td>'.round($row_62['a']).'</td><td>'.$row_62['b'].'</td><td>'.round($row_62['c']).'</td></tr>';  }
				if(count($conv_cpl_high25_arr)>0) {
					$conv_cpl_high25 = '<table id="TABLE_6" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Spend &#8377;</th><th>Leads</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$conv_cpl_high25_arr).'</tbody></table>';
				}
			}
		}
		
		mysqli_query($conn, "DELETE FROM audit_adtracker WHERE accId='".$fb_adAcc."' AND rep_id='".$rep_id."' ") or die(mysqli_error()); 
		
		$sql_4 = mysqli_query($conn, "SELECT adset_name, campaign_id, adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND spend!=0 GROUP BY adset_id HAVING b=0 AND a>=1 ORDER BY a DESC");
		while($row4 = mysqli_fetch_array($sql_4)) {  $lg_0_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row4['campaign_id'].'&selected_adset_ids='.$row4['adset_id'].'" target="_blank">'.$row4['adset_name'].'</a></td><td>'.round($row4['a']).'</td></tr>';  }
		if(count($lg_0_arr)>0) {
					$lg_0 = '<table id="TABLE_7" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Spend &#8377;</th></tr></thead><tbody>'.implode('',$lg_0_arr).'</tbody></table>';
		}
		
		$sql_5 = mysqli_query($conn, "SELECT adset_name, campaign_id, adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns WHERE accId='".$accId."' AND obj_type='CONVERSIONS' GROUP BY adset_id HAVING b=0 AND a>=1 ORDER BY a DESC");
		while($row5 = mysqli_fetch_array($sql_5)) {  $conv_0_arr[]  = '<tr><td><a href="loading.php?pg='.$adsetURL.'?act='.$accId.'&selected_campaign_ids='.$row5['campaign_id'].'&selected_adset_ids='.$row5['adset_id'].'"  target="_blank">'.$row5['adset_name'].'</a></td><td>'.round($row5['a']).'</td></tr>';  }
		if(count($conv_cpl_high25_arr)>0) {
					$conv_0 = '<table id="TABLE_8" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Spend &#8377;</th></tr></thead><tbody>'.implode('',$conv_0_arr).'</tbody></table>';
		}
		
		
		$sql_6 =  mysqli_query($conn, "SELECT b.ad_type, SUM(a.spend) as s, SUM(a.leads) as l FROM audit_adIns as a, audit_ad as b where a.accId='".$accId."' AND a.accId=b.accId AND (a.obj_type='LEAD_GENERATION' OR a.obj_type='CONVERSIONS') GROUP BY b.ad_type");
		
		while($row6 = mysqli_fetch_array($sql_6)) { if($row6['ad_type']=='PHOTO' || $row6['ad_type']=='VIDEO') { $cpl=@($row6['s']/$row6['l']); if($cpl>0) { $ad_type_res[]  = $row6['ad_type'].': '.round($cpl,2); } } }
		
		if(count($ad_type_res)>0) {
			$adty_imp = implode('<>',$ad_type_res);
			$adty_exp = explode('<>',$adty_imp);
			$ad_type_res = array_unique($adty_exp);
		}
		$sql_7 = mysqli_query($conn, "SELECT  b.objective, SUM(a.spend) as a, SUM(a.leads) as b, (SUM(a.spend)/SUM(a.leads)) as c FROM audit_adIns as a, audit_campaign as b WHERE a.campaign_id=b.id AND a.accId='".$accId."'  GROUP by b.objective HAVING b!=0 ORDER BY b.objective ASC");
		
		while($row7 = mysqli_fetch_array($sql_7)) {  
			$obj_wise_res_arr[] = '<tr><td>'.ucfirst(strtolower(str_replace('_',' ',$row7['objective']))).'</td><td>'.round($row7['a']).'</td><td>'.round($row7['b']).'</td><td>'.round($row7['c'],2).'</td></tr>';
		}
		if(count($obj_wise_res_arr)>0) {
					$obj_wise_res = '<table id="TABLE_9" class="table table-bordered dt-responsive compact"><thead><tr><th>Objective</th><th>Spend &#8377;</th><th>Results</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$obj_wise_res_arr).'</tbody></table>';
		}
		
		 $cirSql = "UPDATE audit_data SET ad_type_res='".mysqli_real_escape_string($conn, implode('<>', $ad_type_res))."', high_cpl='".mysqli_real_escape_string($conn, $cmp_high)."', high_cpl_conv='".mysqli_real_escape_string($conn, $cmp_high_conv)."', obj_cost_result='".mysqli_real_escape_string($conn, $obj_wise_res)."', updated=now() WHERE acc_id=".$accId." AND rep_id='".$rep_id."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error());
		
		 $cirSql2 = "INSERT INTO audit_adtracker (accId, rep_id, lg_lead_0, conv_lead_0, lg_cpl_less25, lg_cpl_high25, conv_cpl_less25, conv_cpl_high25, updated) VALUES ('".$fb_adAcc."', '".$rep_id."', '".mysqli_real_escape_string($conn, $lg_0)."', '".mysqli_real_escape_string($conn,  $conv_0)."', '".mysqli_real_escape_string($conn, $lg_cpl_less25)."', '".mysqli_real_escape_string($conn,  $lg_cpl_high25)."', '".mysqli_real_escape_string($conn, $conv_cpl_less25)."', '".mysqli_real_escape_string($conn,  $conv_cpl_high25)."', now());"; 
		mysqli_query($conn, $cirSql2) or die(mysqli_error());
		
	}
}

 
function AdInsights_30d($url, $conn, $accId, $obj_arr, $rep_id, $marks) { 
	
	$requests = curl_get_file_contents($url);
	$fb_response = json_decode($requests, true);  
	//d($fb_response); exit;
	
	foreach ($fb_response['data'] as $key => $res) {
				$adType = $objType = $status = $adQty = '';
				$cSpend = $adset_tot = $lead = $cpr =0;
				$objType = $res['objective']; 
				if(isset($res['spend'])) { $cSpend=$res['spend']; } 
				if(isset($res['actions'])) {
					if(array_key_exists($objType, $obj_arr)) {
						$lead = LeadGen($res['actions'], $obj_arr[$objType]);
						$cpr = @(round($cSpend / $lead, 2));
					}
				}
				$stDt = strtotime(date("".$res['date_start']." 00:00:00"));
				$enDt = strtotime(date("".$res['date_stop']." 23:59:59"));
				//echo $res['campaign_name'].'_'. $cSpend.'_'.$lead .'_'.$cpr.'<br>' ; exit;
				
			$campQ = mysqli_query($conn, "select * from audit_adIns_30d WHERE accId='".$accId."' AND ad_id='".$res['ad_id']."' AND st_dt=".$stDt." AND en_dt=".$enDt."");						
			
			if(mysqli_num_rows($campQ)==0) {
					$cirSql = "INSERT INTO audit_adIns_30d (accId, ad_id, adset_id, campaign_id, ad_name, adset_name, campaign_name, spend, leads, obj_type, st_dt, en_dt, sDate, eDate, updated) VALUES ('".mysqli_real_escape_string($conn, $accId)."', '".mysqli_real_escape_string($conn, $res['ad_id'])."', '".mysqli_real_escape_string($conn, $res['adset_id'])."', '".mysqli_real_escape_string($conn, $res['campaign_id'])."', '".mysqli_real_escape_string($conn, $res['ad_name'])."', '".mysqli_real_escape_string($conn, $res['adset_name'])."', '".mysqli_real_escape_string($conn, $res['campaign_name'])."', '".mysqli_real_escape_string($conn, $cSpend)."', '".mysqli_real_escape_string($conn, $lead)."', '".mysqli_real_escape_string($conn, $objType)."', ".$stDt.", ".$enDt.", '".$res['date_start']."', '".$res['date_stop']."', now());"; 
					mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			} 
	}  //exit;*/
	if(isset($fb_response['paging']['next'])) {
		AdInsights_30d($fb_response['paging']['next'], $conn, $accId, $obj_arr, $rep_id, $marks);
	} else {
		
		$st_30 = strtotime(date('Y-m-d 00:00:00', strtotime('-30 days')));
		$st_15 = strtotime(date('Y-m-d 00:00:00', strtotime('-15 days')));
		$st_7 = strtotime(date('Y-m-d 00:00:00', strtotime('-7 days')));
		$st_3 = strtotime(date('Y-m-d 00:00:00', strtotime('-3 days')));
		
		$en_30 = strtotime(date('Y-m-d 23:59:59', strtotime('-30 days')));
		$en_15 = strtotime(date('Y-m-d 23:59:59', strtotime('-15 days')));
		$en_7 = strtotime(date('Y-m-d 23:59:59', strtotime('-7 days')));
		$en_3 = strtotime(date('Y-m-d 23:59:59', strtotime('-3 days')));
		
		$st_4 = strtotime(date('Y-m-d 00:00:00', strtotime('-4 days')));
		$en_6 = strtotime(date('Y-m-d 23:59:59', strtotime('-6 days')));
		
		$st_8 = strtotime(date('Y-m-d 00:00:00', strtotime('-8 days')));
		$en_14 = strtotime(date('Y-m-d 23:59:59', strtotime('-14 days')));
		
		$st_15 = strtotime(date('Y-m-d 00:00:00', strtotime('-15 days')));
		$en_30 = strtotime(date('Y-m-d 23:59:59', strtotime('-30 days')));
		
		$st_31 = strtotime(date('Y-m-d 00:00:00', strtotime('-31 days')));
		$en_60 = strtotime(date('Y-m-d 23:59:59', strtotime('-60 days')));
		
		$en_30 = strtotime(date('Y-m-d 23:59:59', strtotime('-30 days')));
		
		$st_1 = strtotime(date('Y-m-d 00:00:00', strtotime('-1 days')));
		$en_1 = strtotime(date('Y-m-d 23:59:59', strtotime('-1 days')));
		
		
		//echo "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' st_dt>=".$en_6." AND en_dt<=".$st_6." GROUP by obj_type"; exit;
		$sql_1 = mysqli_query($conn, "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_6." AND en_dt<=".$st_3." GROUP by adset_id");
		while($row1 = mysqli_fetch_array($sql_1)) {
			$lg_cpl_3 = round($row1['c']);
		}
		
		$sql_2 = mysqli_query($conn, "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_14." AND en_dt<=".$st_8." GROUP by adset_id");
		while($row2 = mysqli_fetch_array($sql_2)) {
			$lg_cpl_7 = round($row2['c']);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_30." AND en_dt<=".$st_15." GROUP by adset_id");
		while($row3 = mysqli_fetch_array($sql_3)) {
			$lg_cpl_15 = round($row3['c']);
		}
		
		$sql_4 = mysqli_query($conn, "SELECT campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_60." AND en_dt<=".$st_31." GROUP by adset_id");
		while($row4 = mysqli_fetch_array($sql_4)) {
			$lg_cpl_30 = round($row4['c']);
		}
		echo "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_3." AND en_dt<=".$st_1." GROUP by adset_id"; 
		$sql_11 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_3." AND en_dt<=".$st_1." GROUP by adset_id");
		while($row11 = mysqli_fetch_array($sql_11)) {
			if($lg_cpl_3<round($row11['c'])) { $cls='red'; } else { $cls='green'; }
			$adset_cpl_3[$row11['adset_id']] = '<span class="'.$cls.'">'.round($row11['c']).'</span>';
		}
		d($adset_cpl_3);
		echo  "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_7." AND en_dt<=".$st_1." GROUP by adset_id"; 
		$sql_12 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_7." AND en_dt<=".$st_1." GROUP by adset_id");
		while($row12 = mysqli_fetch_array($sql_12)) {
			if($lg_cpl_7<round($row12['c'])) { $cls='red'; } else { $cls='green'; }
			$adset_cpl_7[$row12['adset_id']] = '<span class="'.$cls.'">'.round($row12['c']).'</span>';
		}
		d($adset_cpl_7);
		$sql_13 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_14." AND en_dt<=".$st_1." GROUP by adset_id");
		while($row13 = mysqli_fetch_array($sql_13)) {
			if($lg_cpl_15<round($row13['c'])) { $cls='red'; } else { $cls='green'; }
			$adset_cpl_15[$row13['adset_id']] = '<span class="'.$cls.'">'.round($row13['c']).'</span>';
		}
		d($adset_cpl_15);
		//echo "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_30." AND en_dt<=".$st_1." GROUP by adset_id"; 
		$sql_14 = mysqli_query($conn, "SELECT adset_id, adset_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND st_dt>=".$en_30." AND en_dt<=".$st_1." GROUP by adset_id");
		while($row14 = mysqli_fetch_array($sql_14)) {
			if($lg_cpl_30<round($row14['c'])) { $cls='red'; } else { $cls='green'; }
			$adset_cpl_30[$row14['adset_id']] = '<span class="'.$cls.'">'.round($row14['c']).'</span>';
			$adset_name_30[$row14['adset_id']] = $row14['adset_name'];
		}
		d($adset_cpl_30);
		if(count($adset_cpl_30)>0) {
			$lg_tbl = '<table id="TABLE_8" class="table table-bordered dt-responsive compact"><thead><tr><th>AdSet Name</th><th>Last 3d CPL (Prev. 3d CPL: '.$lg_cpl_3.')</th><th>Last 7d CPL (Prev. 7d CPL: '.$lg_cpl_7.')</th><th>Last 15d CPL (Prev. 3d CPL: '.$lg_cpl_15.')</th><th>Last 30d CPL (Prev. 3d CPL: '.$lg_cpl_30.')</th></tr></thead><tbody>';
			foreach ($adset_cpl_30 as $key => $value) {
				$lg_tbl .= '<tr><td>'.$adset_name_30[$key].'</td>'; 
				if(isset($adset_cpl_3[$key])) { $lg_tbl .='<td>'.$adset_cpl_3[$key].'</td>'; } else {  $lg_tbl .='<td>-</td>'; }
				if(isset($adset_cpl_7[$key])) { $lg_tbl .='<td>'.$adset_cpl_7[$key].'</td>'; } else {  $lg_tbl .='<td>-</td>'; }
				if(isset($adset_cpl_15[$key])) { $lg_tbl .='<td>'.$adset_cpl_15[$key].'</td>'; } else {  $lg_tbl .='<td>-</td>'; }
				if(isset($adset_cpl_30[$key])) { $lg_tbl .='<td>'.$adset_cpl_30[$key].'</td>'; } else {  $lg_tbl .='<td>-</td>'; }
				$lg_tbl .= '</tr>'; 
				
			}
			$lg_tbl .= '</tbody></table>';
		}
		
		echo $lg_tbl; 
		
	} 
} 