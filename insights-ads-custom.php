<?php session_start(); 
//echo '<span id="msg">Preparing report... Please wait!!</span>';
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
 
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

require('PHPExcel-1.8/Classes/PHPExcel.php');
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);


$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 
include 'google-ads-campaigns.php';
//include 'config.php';

function createColumnsArray($end_column, $first_letters = '')
{
  $columns = array();
  $length = strlen($end_column);
  $letters = range('A', 'Z');

  // Iterate over 26 letters.
  foreach ($letters as $letter) {
      // Paste the $first_letters before the next.
      $column = $first_letters . $letter;

      // Add the column to the final array.
      $columns[] = $column;

      // If it was the end column that was added, return the columns.
      if ($column == $end_column)
          return $columns;
  }

  // Add the column children.
  foreach ($columns as $column) {
      // Don't itterate if the $end_column was already set in a previous itteration.
      // Stop iterating if you've reached the maximum character length.
      if (!in_array($end_column, $columns) && strlen($column) < $length) {
          $new_columns = createColumnsArray($end_column, $column);
          // Merge the new columns which were created with the final columns array.
          $columns = array_merge($columns, $new_columns);
      }
  }

  return $columns;
}
//print_r( createColumnsArray('BZ'));

$group = createColumnsArray('ZZ');

//echo "<pre>";
//print_r( createColumnsArray('BZ'));
//exit;


function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}
function moneyFormatIndia($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}
function LeadGenTot($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
function getCSVdata($csvFile){
	$file_handle = fopen($csvFile, 'r');
	while (!feof($file_handle) ) {
			$line_of_text[] = fgetcsv($file_handle, 1024);
	}
	fclose($file_handle);
	return $line_of_text;
}
									
									
$userId = 2;

$fb_objective =  array(0=>'APP_INSTALLS', 1=>'BRAND_AWARENESS', 2=>'CONVERSIONS', 3=>'EVENT_RESPONSES', 4=>'LEAD_GENERATION', 5=>'LINK_CLICKS', 6=>'LOCAL_AWARENESS', 7=>'MESSAGES', 8=>'OFFER_CLAIMS', 9=>'PAGE_LIKES', 10=>'POST_ENGAGEMENT', 11=>'PRODUCT_CATALOG_SALES', 12=>'REACH', 13=>'VIDEO_VIEWS', 14=>'OUTCOME_LEADS');



$fb_obj_act =  array(0=>array('mobile_app_install','app_custom_event.fb_mobile_complete_registration'), 1=>array('link_click', 'outbound_clicks'), 2=> array('offsite_conversion.fb_pixel_lead'), 3 => array('rsvp'), 4 => array('lead'), 5=>  array('link_click','post_reaction','post_engagement'), 6=>array('link_click', 'outbound_clicks'), 7=>array('onsite_conversion.messaging_first_reply','onsite_conversion.messaging_reply'), 8=> array('receive_offer'), 9=>array('like'), 10=>array('post_engagement','post_reaction','post','comment'), 12=>array('link_click', 'outbound_clicks'), 13=>array('video_view','post_reaction','post','comment', 14 => array('lead')));

$fb_fields_comm = array('Campaign Name', 'Amount Spent (INR)', 'Reach', 'Impressions', 'Cost per 1,000 People Reached (INR)', 'CPM (Cost per 1,000 Impressions) (INR)', 'Clicks (All)', 'CPC (All) (INR)');

$fb_fields = array(
	0=> array('App Installs', 'Cost per App Install (INR)', 'App event', 'Cost per App event (INR)'),
	1=> array('Link Clicks', 'Cost per Link Click (INR)', 'Outbound Clicks', 'Cost per Outbound Clicks (INR)'),
	2=> array('Leads (Website)', 'Cost per Lead (Website) (INR)'),
	3=> array('Event Response', 'Cost per Event Response (INR)',),
	4=> array('Leads (Form)', 'Cost per Lead (Form) (INR)'),
	5=> array('Link Clicks', 'Cost per Link Click (INR)', 'Post Engagement', 'Cost per Post Engagement (INR)', 'Post Reaction', 'Cost per Post Reaction (INR)'),
	6=> array('Link Clicks', 'Cost per Link Click (INR)', 'Outbound Clicks', 'Cost per Outbound Clicks (INR)'),
	7=> array('New messaging connections', 'Cost per New messaging connection', 'Messaging Replies',  'Cost per Messaging Replies'),
	8=> array('Offer Saves'),
	9=> array('Page Likes', 'Cost per Page Like (INR)'),
	10=> array('Post Engagement', 'Cost per Post Engagement (INR)', 'Post Reactions', 'Cost per Post Reactions (INR)', 'Post Shares', 'Cost per Post Shares (INR)', 'Post Comments', 'Cost per Post Comments (INR)'),
	11=> array(),
	12=> array('Link Clicks', 'Cost per Link Click (INR)', 'Outbound Clicks', 'Cost per Outbound Clicks (INR)'),
	13=> array('Video Plays', 'Cost per Video Play', 'Video Watches at 50%', 'Video Watches at 75%', 'Video Watches at 95%', 'Video Watches at 100%', 'Video Completion Rate'),
	14=> array('Leads (Form)', 'Cost per Lead (Form) (INR)'),
);



$pg_id=''; $feedback = array();
$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc, email_ids, pg_id FROM ads_report_weekly where tbl_id=".$_GET['id']." AND uid=2 AND delete_status=0 order by tbl_id asc limit 0, 1");

while($row=mysqli_fetch_assoc($sqlRev)) { 
	$pg_id=$row['pg_id'];
	$getData[] = $row;
	if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc'];  }
	if($row['g_acc']!='') { $gStats[] = $row['g_acc']; }
}

$dtQ = 'AND (created_time >='.(strtotime($_SESSION['stDt'])-34200).' AND created_time <='.((strtotime($_SESSION['enDt']) + 60*60*11)+12600).')'; 
if($pg_id!='') {
	$sqlRev=mysqli_query($conn, "SELECT feedback, count(*) as tot FROM leads where page_id=".$pg_id." AND (feedback IS NOT NULL AND feedback!='Unqualified' AND  feedback!='NR' AND feedback!='') $dtQ group by feedback HAVING tot>0");
	
	while($row=mysqli_fetch_assoc($sqlRev)) { 
		if($row['feedback']!='') { 
				$feedback[] = $row['feedback'];  
				array_push($fb_fields[4], $row['feedback'], 'Cost per '.$row['feedback'].' Lead');
		}
	}
}
//$fb_fields = array('Campaign Name', 'Amount Spent (INR)', 'Reach', 'Impressions', 'Cost per 1,000 People Reached (INR)', 'CPM (Cost per 1,000 Impressions) (INR)', 'Clicks (All)', 'CPC (All) (INR)', 'Leads (Form)', 'Cost per Lead (Form) (INR)', 'Prospective Leads', 'Cost per Prospective Lead', 'Test', 'Test', 'Test', 'Test');

$fb_fields2 = array('Campaign Name', 'Amount Spent (INR)', 'Reach', 'Impressions', 'Cost per 1,000 People Reached (INR)', 'CPM (Cost per 1,000 Impressions) (INR)', 'Clicks (All)', 'CPC (All) (INR)', 'Leads (Website)', 'Cost per Lead (Website) (INR)', 'Prospective Leads', 'Cost per Prospective Lead');

$g_fields = array('Campaign', 'Cost', 'Impr.', 'Interactions', 'Interaction rate', 'Avg. cost', 'Conversions', 'Cost / conv.', 'Prospective Leads', 'Cost per Prospective Lead');

function getWeekMonSun($weekOffset) {
	//$dt = new DateTime();
	//$dt->setIsoDate($dt->format('o'), $dt->format('W') + $weekOffset);
	
	$dates_array[0] = strtotime($_SESSION['stDt']);
	$dates_array[1] = strtotime($_SESSION['enDt']);

	return $dates_array;
}

$weeks = array();
for ($i = -5; $i <= -5; $i++) {
	$weeks[] = getWeekMonSun($i);
}


//d($weeks); exit;

									
									
									$arr_lg = $arr_lg = $arr_con = $arr_goo = $getData = $fbStats = $gStats = array();
									
									$style = array('font' => array('bold' => true,'size' => 16),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => '9fdf9f')));
									$style2 = array('font' => array('bold' => true,'size' => 13),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'ffffcc')));		
									$BStyle = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
									
									$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc, email_ids FROM ads_report_weekly where tbl_id=".$_GET['id']." AND uid=2 AND delete_status=0 order by tbl_id asc limit 0, 1");

									while($row=mysqli_fetch_assoc($sqlRev)) { 
										$getData[] = $row;
										if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc'];  }
										if($row['g_acc']!='') { $gStats[] = $row['g_acc']; }
									}
									
									
									 
									//d($getData); exit;
									foreach($fbStats as $fd) 
									{
										
										foreach ($weeks as $key => $value) 
										{
											$cirRes = mysqli_query($conn, "select report_1 from ads_weekly WHERE uid='2' AND acc_type='FB' AND acc_id='".$fd."' AND st_dt='".$weeks[$key][0]."' AND en_dt='".$weeks[$key][1]."'");
											if(mysqli_num_rows($cirRes)==0) 
											{
												//echo implode(",",$fb_objective); exit;											
												  $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=campaign&fields=objective,campaign_name,campaign_id,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,outbound_clicks,video_play_actions,video_p50_watched_actions,video_p75_watched_actions,video_p95_watched_actions,video_p100_watched_actions&time_range[since]='.date("Y-m-d", $weeks[$key][0]).'&time_range[until]='.date("Y-m-d", $weeks[$key][1]).'&access_token='.$access_token.'&limit=250';
												//exit;
												$fbData[$fd][$key] = get_data($request_url);
												
												
												$cirSql_fb = "INSERT INTO ads_weekly (uid, acc_type, acc_id, report_1, st_dt, en_dt, created) VALUES ('2', 'FB', '".$fd."', '".mysqli_real_escape_string($conn, serialize($fbData[$fd][$key]))."', '".$weeks[$key][0]."', '".$weeks[$key][1]."', now());"; 
												//mysqli_query($conn, $cirSql_fb) or die(mysqli_error());
											} else {
												$row = mysqli_fetch_assoc($cirRes);
												$fbData[$fd][$key] = unserialize($row['report_1']);
											}
											//d($fbData); exit;
											foreach($fb_objective as $ky => $fb_o) {
												$filterBy1 = $fb_o; // or Finance etc.
												$arr_fb[$fd][$key][$ky] = array_filter($fbData[$fd][$key]['data'], function ($var) use ($filterBy1) { return ($var['objective'] == $filterBy1); });
												if(count($arr_fb[$fd][$key][$ky])<1) { unset($arr_fb[$fd][$key][$ky]); }
												//d($arr_lg); 
											}
											
										}
									}
									
									//d($weeks);
									if(count($gStats)>0) {
										//$accIds = $gStats;
										//$accNames = 1;
										//include 'download-ads-clients.php';	
										
										foreach($gStats as $gR)
										{
											for($i=0;$i<1;$i++) {
													if(!isset($arr_goo[$gR][$i])) 
													{
														$gStats[] = $gR; 
														//$gDates[$gaId] = $SatrtDate; 
														$getAccRep = GetCampaigns::main($conn, $g_refresh_token, $g_mcc,$gR,date("Y-m-d", $weeks[$i][0]), date("Y-m-d", $weeks[$i][1]));
														//d($getAccRep); exit;
														$arr_goo[$gR][$i] = $getAccRep;		
														$cirSql_g = "INSERT INTO ads_weekly (uid, acc_type, acc_id, report_1, st_dt, en_dt, created) VALUES ('2', 'G', '".$gR."', '".mysqli_real_escape_string($conn, serialize($arr_goo[$gR][$i]))."', '".$weeks[$i][0]."', '".$weeks[$i][1]."', now());"; 
														//mysqli_query($conn, $cirSql_g) or die(mysqli_error());										
														
													}
											}
										}
									}	
									//d($getAccRep);
									//exit;
									
									
									//d($arr_fb); exit;
									//d($arr_lg); d($arr_con); d($arr_goo);  exit;
									
									//d($arr_fb); exit;
									
									foreach($getData as $key => $getD) 
									{
										//$getD[$key]['fb_acc']
										$rowCount = 1;
										for($y=0;$y<1;$y++) 
										{
											
											$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, 'Date : '.date("d M", $weeks[$y][0]).' to '.date("d M", $weeks[$y][1]))->getStyle('A'.$rowCount)->applyFromArray($style);
											
											//d($arr_lg[$getD['fb_acc']][$y]); exit;
											
											//FB LEAD GENERATIONS
											//d($arr_fb[$getD['fb_acc']][$y]);
											//echo count($arr_fb[$getD['fb_acc']][$y]); exit;
											foreach($arr_fb[$getD['fb_acc']][$y]  as $z => $arrFB) 
											{
												//d($arrFB); exit;
												//$totLeads_lg[$z] = array();
												if(count($arrFB)>0) {	
													$objN = str_replace("_"," ",$fb_objective[$z]);
													$objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount+1), ucwords(strtolower($objN)).' (FB)')->getStyle('A'.($rowCount+1))->applyFromArray($style2);
													$rowCount = $rowCount+1;
												
													$inc =1;	
													$fb_fields_merge = array_merge($fb_fields_comm,$fb_fields[$z]);				
													foreach($fb_fields_merge as $fb_f) {
														//echo 'A'.($rowCount+$inc).'--'.$fb_f; exit;	
														$objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount+$inc), $fb_f);
														$inc++;
													}
													$objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount+1).":A".($rowCount+$inc-1))->applyFromArray($BStyle);
													
													$rowCount = $rowCount+1;
													//d($arrFB);
													$FB_records = array_values($arrFB);
													//$totLeads_lg = 0;
													$tot_v_play = $tot_v_50 =  $tot_v_75 =  $tot_v_95 =  $tot_v_100 = $v_play_c = $v_100_c = 0;
													$feedTotArr = $feedAmountArr = array();
													//d($arrFB[$z]); exit;
													//echo $fb_objective[$z].'__'.count($FB_records); echo '<br>';
													for ($i = 0; $i < count($FB_records); $i++) 
													{										
														$campaign_id = $FB_records[$i]['campaign_id'];
														$campaign_name = $FB_records[$i]['campaign_name'];
														$spend = $FB_records[$i]['spend'];
														$reach = $FB_records[$i]['reach'];
														$impr = $FB_records[$i]['impressions'];														
														$cpm = $FB_records[$i]['cpm'];
														$clicks = $FB_records[$i]['clicks'];
														$cpc = $FB_records[$i]['cpc'];
														
														$cpp_lg = @($spend/$reach)*1000; 
														
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.$rowCount, $campaign_name);
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+1), $spend);
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+2), $reach);
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+3), $impr);
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+4), round($cpp_lg,2));
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+5), round($cpm,2));
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+6), $clicks);
														$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+7), round($cpc,2));
														
														$defN = 7;
														if($fb_objective[$z]=='VIDEO_VIEWS') {
															//if(!isset($tot_)) { $totLeads_lg=array(); echo 1; }
															$v_play = $FB_records[$i]['video_play_actions'][0]['value'];
															$v_50 = $FB_records[$i]['video_p50_watched_actions'][0]['value'];
															$v_75 = $FB_records[$i]['video_p75_watched_actions'][0]['value'];
															$v_95 = $FB_records[$i]['video_p95_watched_actions'][0]['value'];
															$v_100 = $FB_records[$i]['video_p100_watched_actions'][0]['value'];
															
															$tot_v_play += $v_play;
															$tot_v_50 += $v_50;
															$tot_v_75 += $v_75;
															$tot_v_95 += $v_95;
															$tot_v_100 += $v_100;
															
															$v_play_c = @($spend/$v_play);
															$v_100_c = @($v_100/$v_play)*100;
															
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+1)), $v_play);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+2)), round($v_play_c,2));
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+3)), $v_50);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+4)), $v_75);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+5)), $v_95);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+6)), $v_100);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+7)), round($v_100_c,2));
															$defN = 14;
														} 
														
														if(isset($fb_obj_act[$z]) && count($fb_obj_act[$z])>0 && $fb_objective[$z]!='VIDEO_VIEWS') 
														{
															if(!isset($totLeads_lg)) { $totLeads_lg=array();  }															
															foreach($fb_obj_act[$z] as $fk => $fv) 
															{	
																if($fv=='outbound_clicks') { 
																	$leads_lg = $FB_records[$i]['outbound_clicks'][0]['value'];
																} else {
																	$leads_lg = LeadGenTot($FB_records[$i]['actions'], $fv);																	
																} 
																//echo $leads_lg[$z][$fk].'<br>';
																$totLeads_lg[$z][$fk] += @((int)$leads_lg); 
																$cpl_lg = @($spend/$leads_lg);
																//$tLeads[$z][$fk] = $totLeads_lg; 
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+1)), round($leads_lg,2));														
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+2)), round($cpl_lg,2));
																$defN += 2;
																
															}
															
															if($fb_objective[$z]=='LEAD_GENERATION' && count($feedback)>0) {
																
																foreach($feedback as $feed) { 
																	$sqlRev=mysqli_query($conn, "SELECT feedback, count(*) as tot FROM leads where feedback='".$feed."' AND page_id=".$pg_id." AND camp_id=".$campaign_id." $dtQ");
																	$feedTot = $feedTot_cpl = 0;
																	while($row=mysqli_fetch_assoc($sqlRev)) { 
																		if($row['tot']>0) {
																			$feedTot = $row['tot']; 
																			$feedTot_cpl = @($spend/$feedTot);
																			$feedTotArr[$feed][] = $row['tot'];
																			$feedAmountArr[$feed][] = $spend;
																		}
																	}
																	$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+1)),  $feedTot);
																	
																	$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+($defN+2)),  $feedTot_cpl);
																	
																	$defN += 2;
																}
																
															}
														}			
														$objPHPExcel->getActiveSheet()->getStyle(($group[($i+1)]).''.$rowCount.":".($group[($i+1)]).''.($rowCount+$defN))->applyFromArray($BStyle);		
														
														if($i==(count($FB_records)-1)) {
															
															$tot_spend = array_sum(array_column($FB_records, 'spend'));
															$tot_reach = array_sum(array_column($FB_records, 'reach'));
															$tot_impr = array_sum(array_column($FB_records, 'impressions'));
															$tot_clicks = array_sum(array_column($FB_records, 'clicks'));
															
															$cpp_lg = @($tot_spend/$tot_reach)* 1000; 
															$cpm_lg = @($tot_spend/$tot_impr) * 1000; 
															$cpc_lg = @($tot_spend/$tot_clicks);
															
															
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.$rowCount, 'Total');
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+1), $tot_spend);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+2), $tot_reach);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+3), $tot_impr);															
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+4), round($cpp_lg,2));
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+5), round($cpm_lg,2));															
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+6), $tot_clicks);
															$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+7), round($cpc_lg,2));
															
															$defN = 7;
															if($fb_objective[$z]=='VIDEO_VIEWS') {
																
																$tot_v_play_c = @($tot_spend/$tot_v_play);
																$tot_v_100_c = @($tot_v_100/$tot_v_play)*100;
																
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+1)), $tot_v_play);
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+2)), round($tot_v_play_c,2));																
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+3)), $tot_v_50);
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+4)), $tot_v_75);
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+5)), $tot_v_95);
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+6)), $tot_v_100);
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+7)), round($tot_v_100_c,2));																
																$defN = 14;
															} 
															
															if(isset($fb_obj_act[$z]) && count($fb_obj_act[$z])>0  && $fb_objective[$z]!='VIDEO_VIEWS') 
															{
																foreach($fb_obj_act[$z] as $fk => $fv) {		
																$cpl_lg = @($tot_spend/$totLeads_lg[$z][$fk]);
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+1)), round($totLeads_lg[$z][$fk],2));															
																$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+2)), round($cpl_lg,2));
																$defN += 2;
																}
															}	
															//print_r($feedTotArr); print_r($feedAmountArr); echo array_sum( $feedAmountArr['Qualified']); exit;
															if($fb_objective[$z]=='LEAD_GENERATION' && count($feedback)>0) {
																
																foreach($feedback as $feed) { 
																	/*$sqlRev=mysqli_query($conn, "SELECT feedback, count(*) as tot FROM leads where feedback='".$feed."' AND page_id=".$pg_id." AND camp_id=".$campaign_id." $dtQ");
																	$feedTot = $feedTot_cpl = 0;
																	while($row=mysqli_fetch_assoc($sqlRev)) { 
																		$feedTot = $row['tot']; 
																		$feedTot_cpl = @($spend/$feedTot);
																	}*/
																	$feedCount = $feedCPL = 0;
																	if(isset($feedTotArr[$feed]) && $feedTotArr[$feed]>0) {
																		 $feedCount = array_sum($feedTotArr[$feed]);
																		 $feedCPL = @(array_sum($feedAmountArr[$feed])/$feedCount);
																	}
																	$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+1)), $feedCount);
																	
																	$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+2)]).''.($rowCount+($defN+2)), round($feedCPL,2));
																	//$feedTot_sum[] = $feedTot;
																	//$feedTot_cpl[] = $feedTot_cpl;
																	$defN += 2;
																} //exit;
																
															}														
															$objPHPExcel->getActiveSheet()->getStyle(($group[($i+2)]).''.$rowCount.":".($group[($i+2)]).''.($rowCount+$defN))->applyFromArray($BStyle);
														}
														
														
													}
													//$objPHPExcel->getActiveSheet()->getStyle("AI2:AL".($rowCount-1))->applyFromArray($BStyle);
													$rowCount = $rowCount+count($fb_fields_merge);
												}
											}
											
											//GOOGLE ADS
											if(isset($arr_goo[$getD['g_acc']][$y]) && count($arr_goo[$getD['g_acc']][$y])>0) {	
												$objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount+1), 'Google Ads')->getStyle('A'.($rowCount+1))->applyFromArray($style2);
												$rowCount = $rowCount+1;												
												$inc =1;						
												foreach($g_fields as $g_f) {													
													$objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount+$inc), $g_f);
													$inc++;
												}
												$objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount+1).":A".($rowCount+$inc-1))->applyFromArray($BStyle);
												
												$rowCount = $rowCount+1;
												//$output = array_slice($arr_goo[$getD['g_acc']][$y], 2); 
												$G_records = array_values($arr_goo[$getD['g_acc']][$y]);
												for ($i = 0; $i < (count($G_records)); $i++) 
												{	
													if(trim($G_records[$i][1])=='--') { $G_records[$i][1]='Total'; }
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.$rowCount, $G_records[$i][1]);
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+1), round($G_records[$i][5],2));
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+2), $G_records[$i][3]);
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+3), $G_records[$i][4]);
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+4), $G_records[$i][9]);
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+5), round($G_records[$i][6],2));
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+6), $G_records[$i][10]);
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+7), round($G_records[$i][8],2));
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+8), '-');
													$objPHPExcel->getActiveSheet()->SetCellValue(($group[($i+1)]).''.($rowCount+9), '-')->getStyle(($group[($i+1)]).''.$rowCount.":".($group[($i+1)]).''.($rowCount+9))->applyFromArray($BStyle);
												}
												
												$rowCount = $rowCount+count($g_fields);
											}
											$rowCount++;
										}
										//d($$totLeads_lg); exit;	
										$objPHPExcel->getActiveSheet()->freezePane('B1');
										$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
										$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
										$fileN = str_replace(" ","_", $getData[$key]['client_name']).'_Ads-Report_'.date('d-M-Y').'.xlsx';
										$objWriter->save(''.$server_path.'ads-perform/'.$fileN); 
										$clientN = $getData[$key]['client_name'];
										$emailIds = $getData[$key]['email_ids'];
										$rep_type = '';
										if(isset($_GET['download'])) {
											header('Content-type: application/vnd.ms-excel');
											header("Content-Disposition: attachment; filename=\"".$fileN."\"");
											$objWriter->save('php://output');
										} else {
											include 'email/mail-ads-weekly.php';	
											echo 'Email Sent!'; 
										}
										?>
										<br><br><br>
										<input type="button" class="btn btn-success" style="font-weight: bold;display: inline;" value="Close Window" onclick="closeMe()">
										<script>
										window.onload = function () {  document.getElementById("msg").style.display = 'none'; }
										function closeMe()
										{
											window.opener = self;
											window.close();
										}
										</script>
                                        <?
										exit;
										
									}
									
									
									
									exit; ?>
