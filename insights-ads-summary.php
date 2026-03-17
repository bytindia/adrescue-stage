<?php session_start(); 
//echo '<span id="msg">Preparing report... Please wait!!</span>';
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';
if(isset($_GET['t']) && ($_GET['t']==1 || $_GET['t']==2)) {
?>
<script src="https://cdn.jsdelivr.net/gh/gitbrent/pptxgenjs@3.1.1/dist/pptxgen.bundle.js"></script>
<script src="https://cdn.jsdelivr.net/gh/gitbrent/pptxgenjs@3.1.1/libs/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/gitbrent/pptxgenjs@3.1.1/dist/pptxgen.min.js"></script>
<script>
function exportTableToExcel(tableID, filename = '') {
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'excel_data.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}
</script>
<style>
.bold { font-weight:bold; }
.bg_fb { background:#4f81bd; color:#ffffff; }
.bg_g { background:#c0504d;  color:#ffffff; }
.bg_ta { background:#8064A4;   color:#ffffff; }
.bg_ov { background:#c2d69b; }
table, td, th {
  border: 1px solid black;
}

table {
  border-collapse: collapse;
  width: 100%;
  <?php if(isset($_GET['t']) && ($_GET['t']==2)) { ?>font-size:11px; <?php } ?>
}
</style>
<?
}
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

$taboola_tok = '';
include 'taboola-config.php';

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

$group = createColumnsArray('BZ');

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
	$r = 1;
	if(is_array($arr)) {
		for($q=0; $q<count($arr); $q++) {
			//foreach($arr[$q] as $v) {
				if($arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
			//}
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

$fb_objective =  array(0=>'APP_INSTALLS', 1=>'BRAND_AWARENESS', 2=>'CONVERSIONS', 3=>'EVENT_RESPONSES', 4=>'LEAD_GENERATION', 5=>'LINK_CLICKS', 6=>'LOCAL_AWARENESS', 7=>'MESSAGES', 8=>'OFFER_CLAIMS', 9=>'PAGE_LIKES', 10=>'POST_ENGAGEMENT', 11=>'PRODUCT_CATALOG_SALES', 12=>'REACH', 13=>'VIDEO_VIEWS');

$fb_obj_act =  array(0=>array('mobile_app_install','app_custom_event.fb_mobile_complete_registration'), 1=>array('link_click', 'outbound_clicks'), 2=> array('offsite_conversion.fb_pixel_lead'), 3 => array('rsvp'), 4 => array('leadgen.other'), 5=>  array('link_click','post_reaction','post_engagement'), 6=>array('link_click', 'outbound_clicks'), 7=>array('onsite_conversion.messaging_first_reply','onsite_conversion.messaging_reply'), 8=> array('receive_offer'), 9=>array('like'), 10=>array('post_engagement','post_reaction','post','comment'), 12=>array('link_click', 'outbound_clicks'), 13=>array('video_view','post_reaction','post','comment'));

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
	13=> array('Video Plays', 'Cost per Video Play', 'Video Watches at 50%', 'Video Watches at 75%', 'Video Watches at 95%', 'Video Watches at 100%', 'Video Completion Rate')
);

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
									
									$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc, ta_acc, email_ids FROM ads_report_summary where tbl_id=".$_GET['id']." AND uid=2 AND delete_status=0 order by tbl_id asc limit 0, 1");
									$fbStats = $gStats = $taStats = array();

									while($row=mysqli_fetch_assoc($sqlRev)) { 
										$getData[] = $row;
										if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc'];  }
										if($row['g_acc']!='') { $gStats[] = $row['g_acc']; }
										if($row['ta_acc']!='') { $taStats[] = $row['ta_acc']; }
									}
									
									$keysVal = array(1=>'corniche,lemongraz,grand corridor');
									if($_GET['id']==1) {
										$rwdKey = array('corniche','lemongraz','grand corridor'); 
										$fb_chat_leads = $g_chat_leads = $ta_chat_leads = array(5,6,3); $fb_chat_leads_br = $g_chat_leads_br = $ta_chat_leads_br = 14;
									}
									if($_GET['id']==15) {
										$rwdKey = array('pallavaram','kanchipuram','nri'); 
										$fb_chat_leads = $g_chat_leads = $ta_chat_leads = array(5,6,3); $fb_chat_leads_br = $g_chat_leads_br = $ta_chat_leads_br = 14;
									}
									if($_GET['id']==20) {
										$rwdKey = array('nri'); 
										$fb_chat_leads = $g_chat_leads = $ta_chat_leads = array(8); $fb_chat_leads_br = $g_chat_leads_br = $ta_chat_leads_br = 14;
									}
									$spend_Tot = $leads_Tot = $chat_Tot = array();
									//$rwdKeyTbl = $rwdKey; array_push($rwdKeyTbl, 'Brainding', 'Total');
									
									
									$tbl = '<table id="reportTable"><tr><th>Platforms</th><th>Projects</th>';
									$rwdKey = is_array($rwdKey) ? $rwdKey : [];
									foreach($rwdKey as $key => $value) {
										$tbl .= '<th>'.ucwords($value).'</th>';
									}									
									$tbl .= '<th>Branding</th><th>Total</th></tr>';
									
									//d($getData); exit;
									if(count($fbStats)) {
										foreach($fbStats as $fd) 
										{
											
											foreach ($weeks as $key => $value) 
											{
												$cirRes = mysqli_query($conn, "select report_1 from ads_summary WHERE uid='2' AND acc_type='FB' AND acc_id='".$fd."' AND st_dt='".$weeks[$key][0]."' AND en_dt='".$weeks[$key][1]."'");
												//if(mysqli_num_rows($cirRes)==0) 
												//{
													//echo implode(",",$fb_objective); exit;											
													 $request_url = 'https://graph.facebook.com/'.$api_ver.'/act'.$fd.'/insights?level=campaign&fields=campaign_id,objective,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,outbound_clicks,video_play_actions,video_p50_watched_actions,video_p75_watched_actions,video_p95_watched_actions,video_p100_watched_actions&time_range[since]='.date("Y-m-d", $weeks[$key][0]).'&time_range[until]='.date("Y-m-d", $weeks[$key][1]).'&access_token=500&access_token='.$access_token;
													
													$fbData[$fd][$key] = get_data($request_url);
													
													
													$cirSql_fb = "INSERT INTO ads_summary (uid, acc_type, acc_id, report_1, st_dt, en_dt, created) VALUES ('2', 'FB', '".$fd."', '".mysqli_real_escape_string($conn, serialize($fbData[$fd][$key]))."', '".$weeks[$key][0]."', '".$weeks[$key][1]."', now());"; 
													mysqli_query($conn, $cirSql_fb) or die(mysqli_error());
												//} else {
												//	$row = mysqli_fetch_assoc($cirRes);
												//	$fbData[$fd][$key] = unserialize($row['report_1']);
												//}
												//d($fbData); exit;
												foreach($fb_objective as $ky => $fb_o) {
													$filterBy1 = $fb_o; //or Finance etc.
													$arr_fb[$fd][$key][$ky] = array_filter(
														is_array($fbData[$fd][$key]['data']) ? $fbData[$fd][$key]['data'] : [],
														function ($var) use ($filterBy1) {
															return ($var['objective'] == $filterBy1);
														}
													);
													if(count($arr_fb[$fd][$key][$ky])<1) { unset($arr_fb[$fd][$key][$ky]); }
												}
												
											}
											$fbArr = $fbData[$fd]['0']['data'];
										}
										
										
										//d($fb_report_key);  d($fb_report_key2); exit;
																		
										
										//$fbArr = $fbData['107704242648697']['0']['data'];
										
										//echo count($egArr).'<br>';
										
										foreach($fbArr as $k => $v) 
										{
											//$egArr2[$k] = array_filter($egArr, function ($var) use ($v) { return (strpos($var['campaign_name'], $v) !== false); });
											if($v['objective']=='LEAD_GENERATION') { $leads_lg=LeadGenTot($v['actions'], 'leadgen.other'); $leads_con=0; } else if($v['objective']=='CONVERSIONS') { $leads_con=LeadGenTot($v['actions'], 'offsite_conversion.fb_pixel_lead'); } else { $leads_lg = $leads_con = 0; }
											
											//echo $k.': '.$v['campaign_id'].'-'.$v['objective'].'-'.$v['campaign_name'].'<br>';
											
											$fb_report[] = array($v['campaign_id'], strtolower($v['campaign_name']), $v['objective'], $v['spend'], $leads_lg, $leads_con);
											
											if(preg_match('('.implode('|',$rwdKey).')', strtolower($v['campaign_name'])) === 0) { 
												$fb_report2[] = array($v['campaign_id'], strtolower($v['campaign_name']), $v['objective'], $v['spend'], $leads_lg, $leads_con);
											} 
											
										}								
										
										//d($fb_report2);  exit;
										
										foreach($rwdKey as $k => $v) 
										{
											$rwd_tot[$k] = array_filter($fb_report, function ($var) use ($v) { return (strpos($var[1], $v) !== false); });
											//d($fb_report); exit;
											$tot_spend = $tot_leads_lg = $tot_leads_con = 0;
											foreach($rwd_tot[$k] as $num => $values) {
												$tot_spend += $values[3];
												$tot_leads_lg += $values[4];
												$tot_leads_con += $values[5];
											}
											$fb_report_key[$k] = array($tot_spend,$tot_leads_lg,$tot_leads_con);
										}
										
										if (is_array($fb_report2) && count($fb_report2) > 0) {
											//$rwd_tot2[$k] = array_filter($fb_report2, function ($var2) use ($v) { return (strpos($var2[1], $v) !== false); });
											//d($fb_report2); exit;
											$tot_spend2 = $tot_leads_lg2 = $tot_leads_con2 = 0;
											foreach($fb_report2 as $num2 => $values2) {
												$tot_spend2 += $values2[3];
												$tot_leads_lg2 += $values2[4];
												$tot_leads_con2 += $values2[5];
											}
											$fb_report_key2 = array($tot_spend2,$tot_leads_lg2,$tot_leads_con2);
										}
										
										if(is_array($fbArr) && count(value: $fbArr)>0) {
													$spend = $leads = $tot_leads = $cpc = $leads_c = $leads_c_tot = 0;
													
													$tbl .= '<tr><td rowspan="5" class="bg_fb">Facebook</td><td class="bg_fb">Spends</td>';
													foreach($fb_report_key as $key => $value) { $tbl .= '<td>'.round($value[0]).'</td>'; $spend +=round($value[0]); $spend_arr[$key]=round($value[0]);  }
													$tbl .= '<td>'.round($fb_report_key2[0]).'</td><td>'.round(($spend+$fb_report_key2[0])).'</td></tr>';
													$spendT = array_merge($spend_arr, array($fb_report_key2[0]));
													array_push($spend_Tot, $spendT); 
													
													$tbl .= '<tr><td class="bg_fb">Leads</td>';
													foreach($fb_report_key as $key => $value) { $tbl .= '<td>'.$value[1].'</td>'; $leads +=$value[1]; $leads_arr[$key]=$value[1]; }
													$tbl .= '<td>'.$fb_report_key2[1].'</td><td>'.($leads+$fb_report_key2[1]).'</td></tr>';
													$leadsT = array_merge($leads_arr, array($fb_report_key2[1]));
													array_push($leads_Tot, $leadsT); 
													
													
													
													$tbl .= '<tr><td  class="bg_fb">Chat Leads</td>';
													foreach($fb_chat_leads as $key => $value) { $tbl .= '<td>'.$value.'</td>'; $leads_c +=$value; $leads_c_arr[$key]=$value; }	
													$tbl .= '<td>'.$fb_chat_leads_br.'</td><td>'.($leads_c+$fb_chat_leads_br).'</td></tr>';
													$chatT = array_merge($leads_c_arr, array($fb_chat_leads_br));
													array_push($chat_Tot, $chatT);
													
													$tbl .= '<tr><td class="bg_fb">Total Leads</td>';
													foreach($leads_arr as $key => $value) { $tbl .= '<td class="bg_fb">'.($value+$leads_c_arr[$key]).'</td>'; $leads_c_tot +=$value+$leads_c_arr[$key]; }	
													$tbl .= '<td class="bg_fb">'.($fb_chat_leads_br+$fb_report_key2[1]).'</td><td class="bg_fb">'.($fb_chat_leads_br+$fb_report_key2[1]+$leads_c_tot).'</td></tr>';
													
													$tbl .= '<tr><td class="bg_fb">CPL</td>';
													foreach($spend_arr as $key => $value) { $tbl .= '<td>'.@(round($value/($leads_arr[$key]+$leads_c_arr[$key]))).'</td>';  }	
													$tbl .= '<td>'.@(round($fb_report_key2[0]/($fb_chat_leads_br+$fb_report_key2[1]))).'</td><td>'.@round(($spend+$fb_report_key2[0])/($fb_chat_leads_br+$fb_report_key2[1]+$leads_c_tot)).'</td></tr>';
										}
									
									//echo $tbl.'</table>';  exit;
									//d($fb_report_key);  d($fb_report_key2); exit;
									}
									
									if(count($gStats)>0) {
										$accIds = $gStats;
										$accNames = 1;
										include 'download-ads-clients.php';	
										
										foreach($gStats as $gR)
										{
											for($i=0;$i<1;$i++) {
													if(!isset($arr_goo[$gR][$i])) 
													{
														$arr_goo[$gR][$i] = getCSVdata(''.$server_path.'ads-perform/weekly_'.$gR.'_'.$i.'.csv');		
														$cirSql_g = "INSERT INTO ads_summary (uid, acc_type, acc_id, report_1, st_dt, en_dt, created) VALUES ('2', 'G', '".$gR."', '".mysqli_real_escape_string($conn, serialize($arr_goo[$gR][$i]))."', '".$weeks[$i][0]."', '".$weeks[$i][1]."', now());"; 
														mysqli_query($conn, $cirSql_g) or die(mysqli_error());										
														
													}
											}
										}
										$gArr = array_slice($arr_goo[$gR]['0'], 2);
									
									
									//$gArr = array_slice($arr_goo[$gR]['0'], 2);
									
									//echo count($gArr).'<br>';
									//d($gArr);
									$g_report = $g_report2 = $g_report_key = $g_report_key2 = array();
									foreach($gArr as $k => $v) 
									{
										
										if($k < (count($gArr)-2)) {
											$campN = str_replace("_", " ", strtolower($v[1]));
											$g_report[] = array($v[5], $v[10], $v[0], $campN);
											
											if(preg_match('('.implode('|',$rwdKey).')', $campN) === 0) { 
												$g_report2[] = array($v[5], $v[10], $v[0], $campN);
											} 
										}
										
									}
									foreach($rwdKey as $k => $v) 
									{
										$rwd_tot[$k] = array_filter($g_report, function ($var) use ($v) { return (strpos($var[3], $v) !== false); });
										//d($g_report); exit;
										$tot_spend = $tot_leads =  0;
										foreach($rwd_tot[$k] as $num => $values) {
											$tot_spend += @($values[0]/1000000);
											$tot_leads += $values[1];
										}
										$g_report_key[$k] = array($tot_spend,$tot_leads);
									}
									
									if(count($g_report2)>0) {
										$tot_spend2 = $tot_leads2 = 0;
										foreach($g_report2 as $num2 => $values2) {
											$tot_spend2 += @($values2[0]/1000000);
											$tot_leads2 += $values2[1];
										}
										$g_report_key2 = array($tot_spend2,$tot_leads2);
									}
									//d($g_report); d($g_report2); d($g_report_key); d($g_report_key2); exit; 
									
									if(count($gArr)>0) {
												$spend = $leads = $tot_leads = $cpc = $leads_c = $leads_c_tot = 0;
												
												$tbl .= '<tr><td rowspan="5" class="bg_g">Google</td><td class="bg_g">Spends</td>';
												foreach($g_report_key as $key => $value) { $tbl .= '<td>'.round($value[0]).'</td>'; $spend +=round($value[0]); $spend_arr[$key]=round($value[0]);  }
												$tbl .= '<td>'.round($g_report_key2[0]).'</td><td>'.round(($spend+$g_report_key2[0])).'</td></tr>';
												$spendT = array_merge($spend_arr, array($g_report_key2[0]));
												array_push($spend_Tot, $spendT);
												
												$tbl .= '<tr><td class="bg_g">Leads</td>';
												foreach($g_report_key as $key => $value) { $tbl .= '<td>'.ceil($value[1]).'</td>'; $leads +=ceil($value[1]); $leads_arr[$key]=ceil($value[1]); }
												$tbl .= '<td>'.ceil($g_report_key2[1]).'</td><td>'.($leads+ceil($g_report_key2[1])).'</td></tr>';
												$leadsT = array_merge($leads_arr, array($g_report_key2[1]));
												array_push($leads_Tot, $leadsT);
												
												
												
												$tbl .= '<tr><td class="bg_g">Chat Leads</td>';
												foreach($g_chat_leads as $key => $value) { $tbl .= '<td>'.$value.'</td>'; $leads_c +=$value; $leads_c_arr[$key]=$value; }	
												$tbl .= '<td>'.$g_chat_leads_br.'</td><td>'.($leads_c+$g_chat_leads_br).'</td></tr>';
												$chatT = array_merge($leads_c_arr, array($g_chat_leads_br));
												array_push($chat_Tot, $chatT);
												
												$tbl .= '<tr><td class="bg_g">Total Leads</td>';
												foreach($leads_arr as $key => $value) { $tbl .= '<td class="bg_g">'.($value+$leads_c_arr[$key]).'</td>'; $leads_c_tot +=$value+$leads_c_arr[$key]; }	
												$tbl .= '<td class="bg_g">'.($g_chat_leads_br+$g_report_key2[1]).'</td><td class="bg_g">'.($g_chat_leads_br+$g_report_key2[1]+$leads_c_tot).'</td></tr>';
												
												$tbl .= '<tr><td class="bg_g">CPL</td>';
												foreach($spend_arr as $key => $value) { $tbl .= '<td>'.@(round($value/($leads_arr[$key]+$leads_c_arr[$key]))).'</td>';  }	
												$tbl .= '<td>'.@(round($g_report_key2[0]/($g_chat_leads_br+$g_report_key2[1]))).'</td><td>'.@round(($spend+$g_report_key2[0])/($g_chat_leads_br+$g_report_key2[1]+$leads_c_tot)).'</td></tr>';
									}
									
									//echo $tbl.'</table>'; 
									
									//d($arr_fb); exit;
									//d($arr_lg); d($arr_con); d($arr_goo);  exit;
									
									//d($arr_fb); exit;
									}	
									
									
									if(count($taStats)>0) {	
										foreach ($weeks as $key => $value) 
										{
												//if($sqlROW['ta_id']!='' && $taboola_tok!='') {
												//$taIds = explode(',',$sqlROW['ta_id']);	$ta_stDt = explode(',',$sqlROW['ta_stDt']);		
												foreach($taStats as $ta)  {	
													//if($ta_stDt[$key]=='') { $ta_stDt[$key]='2019/01/01'; } 			
													$ta_url = 'https://backstage.taboola.com/backstage/api/1.0/'.$ta.'/reports/campaign-summary/dimensions/campaign_breakdown?access_token='.$taboola_tok.'&start_date='.date('Y-m-d',$weeks[$key][0]).'&end_date='.date('Y-m-d',$weeks[$key][1]).'';
													$ta_req = file_get_contents_curl($ta_url);
													$ta_res = json_decode($ta_req,true);
													if(isset($ta_res['results'])) {
														$ta_spent = array_sum(array_column($ta_res['results'],'spent'));
														$taSpent[] = round($ta_spent);
													}
												}
												//mysqli_query($conn, "UPDATE cashflow SET ta_spent='".implode(',',$taSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
											//}
										}
									//d($ta_res);
									$taArr = $ta_res['results'];	
									$ta_report = $ta_report2 = $ta_report_key = $ta_report_key2 = array();
									foreach($taArr as $k => $v) 
									{
										
										//if($k>0 && $k < count($taArr)) {
											$campN = str_replace("_", " ", strtolower($v['campaign_name']));
											$ta_report[] = array($v['cpa_actions_num'], $v['campaign'], $campN);
											
											if(preg_match('('.implode('|',$rwdKey).')', $campN) === 0) { 
												$ta_report2[] = array($v['spent'], $v['cpa_actions_num'], $v['campaign'], $campN);
											} 
										//}
										
									}
									foreach($rwdKey as $k => $v) 
									{
										$rwd_tot[$k] = array_filter($ta_report, function ($var) use ($v) { return (strpos($var[1], $v) !== false); });
										//d($ta_report); exit;
										$tot_spend = $tot_leads =  0;
										foreach($rwd_tot[$k] as $num => $values) {
											$tot_spend += $values[0];
											$tot_leads += $values[1];
										}
										$ta_report_key[$k] = array($tot_spend,$tot_leads);
									}
									
									if(count($ta_report2)>0) {
										$tot_spend2 = $tot_leads2 = 0;
										foreach($ta_report2 as $num2 => $values2) {
											$tot_spend2 += $values2[0];
											$tot_leads2 += $values2[1];
										}
										$ta_report_key2 = array($tot_spend2,$tot_leads2);
									}
									//d($ta_report); d($ta_report2); d($ta_report_key); d($ta_report_key2); 
									
									if(count($taArr)>0) {
												$spend = $leads = $tot_leads = $cpc = $leads_c = $leads_c_tot = 0;
												
												$tbl .= '<tr><td rowspan="5" class="bg_ta">Taboola</td><td class="bg_ta">Spends</td>';
												foreach($ta_report_key as $key => $value) { $tbl .= '<td>'.round($value[0]).'</td>'; $spend +=round($value[0]); $spend_arr[$key]=round($value[0]);  }
												$tbl .= '<td>'.round($ta_report_key2[0]).'</td><td>'.round(($spend+$ta_report_key2[0])).'</td></tr>';
												$spendT = array_merge($spend_arr, array(round($ta_report_key2[0])));
												array_push($spend_Tot, $spendT);
												
												$tbl .= '<tr><td class="bg_ta">Leads</td>';
												foreach($ta_report_key as $key => $value) { $tbl .= '<td>'.$value[1].'</td>'; $leads +=$value[1]; $leads_arr[$key]=$value[1]; }
												$tbl .= '<td>'.$ta_report_key2[1].'</td><td>'.($leads+$ta_report_key2[1]).'</td></tr>';
												$leadsT = array_merge($leads_arr, array($ta_report_key2[1]));
												array_push($leads_Tot, $leadsT);
												
												
												
												$tbl .= '<tr><td class="bg_ta">Chat Leads</td>';
												foreach($ta_chat_leads as $key => $value) { $tbl .= '<td>'.$value.'</td>'; $leads_c +=$value; $leads_c_arr[$key]=$value; }	
												$tbl .= '<td>'.$ta_chat_leads_br.'</td><td>'.($leads_c+$ta_chat_leads_br).'</td></tr>';
												$chatT = array_merge($leads_c_arr, array($ta_chat_leads_br));
												array_push($chat_Tot, $chatT);
												
												
												$tbl .= '<tr><td class="bg_ta">Total Leads</td>';
												foreach($leads_arr as $key => $value) { $tbl .= '<td class="bg_ta">'.($value+$leads_c_arr[$key]).'</td>'; $leads_c_tot +=$value+$leads_c_arr[$key]; }	
												$tbl .= '<td class="bg_ta">'.($ta_chat_leads_br+$ta_report_key2[1]).'</td><td class="bg_ta">'.($ta_chat_leads_br+$ta_report_key2[1]+$leads_c_tot).'</td></tr>';
												
												$tbl .= '<tr><td class="bg_ta">CPL</td>';
												foreach($spend_arr as $key => $value) { $tbl .= '<td>'.@(round($value/($leads_arr[$key]+$leads_c_arr[$key]))).'</td>';  }	
												$tbl .= '<td>'.@(round($ta_report_key2[0]/($ta_chat_leads_br+$ta_report_key2[1]))).'</td><td>'.@round(($spend+$ta_report_key2[0])/($ta_chat_leads_br+$ta_report_key2[1]+$leads_c_tot)).'</td></tr>';
									}
									
									}
									//OVERALL
									
									$spend = $leads = $tot_leads = $cpc = $leads_c = $leads_c_tot = 0;
												
												$tbl .= '<tr><td rowspan="5" class="bg_ov">Overall</td><td class="bg_ov">Spends</td>';
												$rwdKey = is_array($rwdKey) ? $rwdKey : [];
												for($i=0;$i<=count($rwdKey);$i++) {													
													$val = array_sum(array_column($spend_Tot, $i));
													$tbl .= '<td>'.round($val).'</td>'; $spend +=round($val); $spend_arr[$i]=round($val);
												}
												$tbl .= '<td>'.round($spend).'</td></tr>';
												
												$tbl .= '<tr><td class="bg_ov">Leads</td>';
												for($i=0;$i<=count($rwdKey);$i++) {													
													$val = array_sum(array_column($leads_Tot, $i));
													$tbl .= '<td>'.round($val).'</td>'; $leads +=round($val); $leads_arr[$i]=round($val);
												}												
												$tbl .= '<td>'.($leads).'</td></tr>';
												
												
												
												$tbl .= '<tr><td class="bg_ov">Chat Leads</td>';
												for($i=0;$i<=count($rwdKey);$i++) {													
													$val = array_sum(array_column($chat_Tot, $i));
													$tbl .= '<td>'.round($val).'</td>'; $leads_c +=round($val); $leads_c_arr[$i]=round($val);
												}	
												$tbl .= '<td>'.($leads_c).'</td></tr>';
												
												$tbl .= '<tr><td class="bg_ov">Total Leads</td>';
												foreach($leads_arr as $key => $value) { $tbl .= '<td class="bg_ov">'.($value+$leads_c_arr[$key]).'</td>'; $leads_c_tot +=$value+$leads_c_arr[$key]; }	
												$tbl .= '<td class="bg_ov">'.($leads_c_tot).'</td></tr>';
												
												$tbl .= '<tr><td class="bg_ov">CPL</td>';
												foreach ($spend_arr as $key => $value) {
    $leads_total = ($leads_arr[$key] ?? 0) + ($leads_c_arr[$key] ?? 0);
    $tbl .= '<td>' . (($leads_total > 0) ? round($value / $leads_total) : 0) . '</td>';
}
												$tbl .= '<td>' . (($leads_c_tot > 0) ? round($spend / $leads_c_tot) : 0) . '</td></tr>';

											
									
									//d($leads_arr); d($leads_c_arr);
											//d($ta_res);
											if(isset($_GET['t']) && ($_GET['t']==1)) {	
											echo $tbl.'</table>'; 
											}
if(isset($_GET['t']) && ($_GET['t']==2)) {		
echo $tbl.'</table>'; 
?>
<script>
var pptx=new PptxGenJS(); let textboxOpts = { x: 0, y: 0}; pptx.tableToSlides('reportTable', textboxOpts); pptx.writeFile('<?php echo $getData[0]['client_name']; ?>.pptx');
//window.close('','_parent','');
</script>
<?	exit;			
}
										
									if(isset($_GET['t']) && ($_GET['t']==3)) {				
									$filename = "".$getData[0]['client_name'].".xls";
									$table    = $tbl;
									
									// save $table inside temporary file that will be deleted later
									$tmpfile = tempnam(sys_get_temp_dir(), 'html');
									file_put_contents($tmpfile, $table);
									$tmpfile = tempnam(sys_get_temp_dir(), 'html');
									file_put_contents($tmpfile, $table);
									
									// insert $table into $objPHPExcel's Active Sheet through $excelHTMLReader
									$objPHPExcel     = new PHPExcel();
									$excelHTMLReader = PHPExcel_IOFactory::createReader('HTML');
									$excelHTMLReader->loadIntoExisting($tmpfile, $objPHPExcel);
									$objPHPExcel->getActiveSheet()->setTitle('any name you want'); // Change sheet's title if you want
									
									unlink($tmpfile); // delete temporary file because it isn't needed anymore
									
									header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // header for .xlxs file
									header('Content-Disposition: attachment;filename='.$filename); // specify the download file name
									header('Cache-Control: max-age=0');
									
									// Creates a writer to output the $objPHPExcel's content
									$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
									$writer->save('php://output');
									exit;
									
													
									exit; 
									}
									
									