<?php session_start();
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
	$r = 0;
	for($q=0;$q<count($arr);$q++) {
		foreach($arr[$q] as $v) {
			if($v['action_type']==$filt) $r += $v['value'];	
		}
	}
	return $r;
}

$userId = 2;

									$start_lm = strtotime("first day of last month"); //date('m/01/Y');
									$end_lm = strtotime("last day of last month");
									
									$start_cm = strtotime("first day of this month"); //date('m/01/Y');
									$end_cm = strtotime("now");
									
									$start_lw = strtotime('monday last week'); //date('m/01/Y');
									$end_lw = strtotime("sunday last week");
									
									$start_cw = strtotime('monday this week'); //date('m/01/Y');
									$end_cw = strtotime("now"); //sunday last week
									
									$s_dt = array($start_lw, $start_cw, $start_lm, $start_cm);
									$e_dt = array($end_lw, $end_cw, $end_lm, $end_cm);
									
									$arr_lg = $arr_con = $arr_goo = $getData = $fbStats = $gStats = array();
									$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc FROM ads_perform where uid=2 AND delete_status=0 order by tbl_id desc");

									while($row=mysqli_fetch_assoc($sqlRev)) { 
										$getData[] = $row;
										if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc'];  }
										if($row['g_acc']!='') { $gStats[] = $row['g_acc']; }
									}
									
									
									
									//d($getData); exit;
									foreach($fbStats as $fd) {
										
										foreach ($s_dt as $key => $value) {
											 $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?access_token='.$access_token.'&level=campaign&fields=objective,spend,actions,ctr,cpc,cpm,impressions,clicks&time_range[since]='.date("Y-m-d", $s_dt[$key]).'&time_range[until]='.date("Y-m-d", $e_dt[$key]).'&filtering=[{"field":"objective","operator":"IN","value":["CONVERSIONS","LEAD_GENERATION"]},{"field":"action_type","operator":"IN","value":["leadgen.other","offsite_conversion.fb_pixel_lead"]}]&limit=100';
											$fbData[$fd][$key] = get_data($request_url);
											
											//d($fbData);
											$filterBy1 = 'LEAD_GENERATION'; 
											$filterBy2 = 'CONVERSIONS'; // or Finance etc.

											$arr_lg[$fd][$key] = array_filter($fbData[$fd][$key]['data'], function ($var) use ($filterBy1) { return ($var['objective'] == $filterBy1); });
											$arr_con[$fd][$key] = array_filter($fbData[$fd][$key]['data'], function ($var) use ($filterBy2) { return ($var['objective'] == $filterBy2); });
											//d($arr_lg[$fd]); 	d($arr_con[$fd]); exit;
										}
										
										//$fbData[$fd] = get_data('https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($start)).'&time_range[until]='.date("Y-m-d", strtotime($end)).'');
										//$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=campaign&fields=campaign_name,spend,impressions,reach,cpc&time_increment=1&limit='.$reqLimit.'&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($start)).'&time_range[until]='.date("Y-m-d", strtotime($end)).'';
										//$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?access_token='.$access_token.'&level=campaign&fields=objective,spend,actions,ctr,cpc,cpm&since='.date("Y-m-d", strtotime($start)).'&until='.date("Y-m-d", strtotime($start)).'&filtering=[{"field":"objective","operator":"IN","value":["CONVERSIONS","LEAD_GENERATION"]},{"field":"action_type","operator":"IN","value":["leadgen.other","offsite_conversion.fb_pixel_lead"]}]';
									}
									
									if(count($gStats)>0) {
										$accIds = $gStats;
										$accNames = 1;
										//$fromDt = date('Ymd', strtotime($start));
										//$enDt = date('Ymd', strtotime($end));
										include 'download-ads-performance.php';	
										
										foreach($gStats as $gR)
										{
											for($i=0;$i<4;$i++) {
													//echo 'ads-perform/account_'.$gR.'_'.$i.'.csv'; exit;
													$rows[$gR][$i] = file(''.$server_path.'ads-perform/account_'.$gR.'_'.$i.'.csv');
													$last_row[$gR][$i] = array_pop($rows[$gR][$i]);
													$arr_goo[$gR][$i] = str_getcsv($last_row[$gR][$i]);													
											}
										}
									}							
								$_SESSION['arr_lg'] = $arr_lg; $_SESSION['arr_con'] = $arr_con;  $_SESSION['arr_goo'] = $arr_goo;
								$arr_lg = $_SESSION['arr_lg'] ; $arr_con = $_SESSION['arr_con'] ; $arr_goo = $_SESSION['arr_goo'] ;
								//d($arr_lg); d($arr_con); d($arr_goo); exit;
								
								
								/*$q0 = mysqli_query($conn, "SELECT pg_id,pg_name FROM `pages` WHERE uid='".$userId."' AND active=1 AND admin_delete=0");
								
								$q1 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_lm." AND end_time_unix<=".$end_lm." GROUP BY pg_id");
								
								$q2 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_lm." AND created_time_unix<=".$end_lm." GROUP BY pg_id");
								
								
								$q3 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_cm." AND end_time_unix<=".$end_cm." GROUP BY pg_id");
								
								$q4 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_cm." AND created_time_unix<=".$end_cm." GROUP BY pg_id");
								
								$q11 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_lw." AND end_time_unix<=".$end_lw." GROUP BY pg_id");
								
								$q22 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_lw." AND created_time_unix<=".$end_lw." GROUP BY pg_id");
								
								
								$q33 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_cw." AND end_time_unix<=".$end_cw." GROUP BY pg_id");
								
								$q44 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_cw." AND created_time_unix<=".$end_cw." GROUP BY pg_id");
								
								$r0 = $r1 = $r2 = $r3 = $r4 = $r11 = $r22 = $r33 = $r44 =array();
								while($rw0=mysqli_fetch_assoc($q0)) { $r0[$rw0['pg_id']] = $rw0['pg_name']; }
								while($rw1=mysqli_fetch_assoc($q1)) { $r1[$rw1['pg_id']] = $rw1; }
								while($rw2=mysqli_fetch_assoc($q2)) { $r2[$rw2['pg_id']] = $rw2; }
								while($rw3=mysqli_fetch_assoc($q3)) { $r3[$rw3['pg_id']] = $rw3; }
								while($rw4=mysqli_fetch_assoc($q4)) { $r4[$rw4['pg_id']] = $rw4; }
								
								while($rw11=mysqli_fetch_assoc($q11)) { $r11[$rw11['pg_id']] = $rw11; }
								while($rw22=mysqli_fetch_assoc($q22)) { $r22[$rw22['pg_id']] = $rw22; }
								while($rw33=mysqli_fetch_assoc($q33)) { $r33[$rw33['pg_id']] = $rw33; }
								while($rw44=mysqli_fetch_assoc($q44)) { $r44[$rw44['pg_id']] = $rw44; }
								*/
								
								//d($r11); d($r22); exit;
								//$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
								//$i = (($page-1) * $per_page ) + 1;
								
								$BStyle = array('borders' => array('outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
								$style = array('font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,));								
								$style2 = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,));
								$styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),'font' => array('bold' => true,),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,));
								$fill_1 = array(	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'ffffe6')));
								$fill_2 = array(	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'e6f5ff')));
									
								$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'SNo');
								$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Client Name')->getColumnDimension('B')->setWidth(20);
								
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, "Last Week (".date('M d', $start_lw).' - '.date('d, Y', $end_lw).")")->mergeCells('C1:N1');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, 1, "This Week (".date('M d', $start_cw).' - '.date('d, Y', $end_cw).")")->mergeCells('O1:Z1');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(26, 1, "Last Month(".date('M d', $start_lm).' - '.date('d, Y', $end_lm).")")->mergeCells('AA1:AL1');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(38, 1, "This Month (".date('M d', $start_cm).' - '.date('d, Y', $end_cm).")")->mergeCells('AM1:AX1')->getStyle("A1:AX1")->applyFromArray($style);
								
								
								
								$rowCount = 2;
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, 2, "Lead Gen.")->mergeCells('C2:F2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, 2, "Website")->mergeCells('G2:J2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, 2, "Google")->mergeCells('K2:N2');
								
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, 2, "Lead Gen.")->mergeCells('O2:R2');								
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(18, 2, "Website")->mergeCells('S2:V2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(22, 2, "Google")->mergeCells('W2:Z2');
								
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(26, 2, "Lead Gen.")->mergeCells('AA2:AD2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(30, 2, "Website")->mergeCells('AE2:AH2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(34, 2, "Google")->mergeCells('AI2:AL2');
								
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(38, 2, "Lead Gen.")->mergeCells('AM2:AP2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(42, 2, "Website")->mergeCells('AQ2:AT2');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(46, 2, "Google")->mergeCells('AU2:AX2')->getStyle("A2:AX2")->applyFromArray($style);
								
								
								//->getStyle("A2:AX2")->applyFromArray($style);
								$rowCount = 3;
								
								for ($i = 1; $i < 46; $i += 4) {										
										
										$objPHPExcel->getActiveSheet()->SetCellValue($group[$i+1].''.$rowCount, 'CPL');
										$objPHPExcel->getActiveSheet()->SetCellValue($group[$i+2].''.$rowCount, 'CTR');
										$objPHPExcel->getActiveSheet()->SetCellValue($group[$i+3].''.$rowCount, 'CPC');
										$objPHPExcel->getActiveSheet()->SetCellValue($group[$i+4].''.$rowCount, 'CPM');
								}
								
								$rowCount = 4; $sNo =1;
								foreach($getData as $gd) {												
									$k = 0;				
									/*
									echo $spnd_lg = array_sum(array_column($arr_lg[479529832536428][0], 'spend')); 
									echo '<br>';
									echo $actions_lg = array_column($arr_lg[479529832536428][0], 'actions');
									d($actions_lg);
									$input = array_map("unserialize", array_unique(array_map("serialize", $actions_lg)));
									d($input);
									$like = 'leadgen.other';

									$x = array_filter($arr_lg[479529832536428][0], function ($item) use ($like) {
										
										d($item['actions']); exit;
										return false;
									});
									d($x);
									d(array_column(array_map('current', $actions_lg), 'value'));
									echo '<br>';
												echo $act_tot_lg = array_sum(array_column(array_map('current', $actions_lg), 'value'));
												echo '<br>';
												echo $cpl_lg = ($spnd_lg / $act_tot_lg);
												exit;
									*/			
									
									$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $sNo);
									$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $gd['client_name']);
									for ($j = 1; $j < 46; $j += 12) {	
											//echo  $gd['fb_acc'].'-->'.$k.'-->'.array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'spend')); 
											//echo '<br>';
											/*$x = $arr_lg[$gd['fb_acc']][$k];
											$y = array_column($x, 'actions');
											d($x); 
											d($y); 
											$z = array_map('current', $y);
											echo array_sum(array_column($z, 'value'));
											exit;*/
											//$x = array_filter($fbData[$fd][$key]['data'], function ($var) use ($filterBy1) { return ($var['objective'] == $filterBy1); });
											
											
											if(isset($arr_lg[$gd['fb_acc']][$k])) { 
												$spnd_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'spend')); 
												$imp_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'impressions'));
												$clik_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'clicks'));
												
												$actions_lg = array_column($arr_lg[$gd['fb_acc']][$k], 'actions');
												$act_tot_lg = LeadGenTot($actions_lg, 'leadgen.other');
												
												$cpl_lg = @($spnd_lg / $act_tot_lg);
												$ctr_lg = @($clik_lg / $imp_lg ) * 100;
												$cpc_lg = @($spnd_lg / $clik_lg) ;
												$cpm_lg = @($spnd_lg / $imp_lg) * 1000; 
												
											} else { $spnd_lg = $ctr_lg = $cpc_lg = $cpm_lg = ''; }
											
											/*if(isset($arr_lg[$gd['fb_acc']][$k])) { $cpl_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'spend')); } else { $cpl_lg =0; }
											if(isset($arr_lg[$gd['fb_acc']][$k])) { $imp_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'ctr')); $ctr_lg = $spnd_lg / } else { $ctr_lg =0; }
											if(isset($arr_lg[$gd['fb_acc']][$k])) { $cpc_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'cpc')); } else { $cpc_lg =0; }
											if(isset($arr_lg[$gd['fb_acc']][$k])) { $cpm_lg = array_sum(array_column($arr_lg[$gd['fb_acc']][$k], 'cpm')); } else { $cpm_lg =0; }*/
											
											if(isset($arr_con[$gd['fb_acc']][$k])) { 
												$spnd_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'spend')); 
												$imp_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'impressions'));
												$clik_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'clicks'));
												
												$actions_con = array_column($arr_con[$gd['fb_acc']][$k], 'actions');
												$act_tot_con = LeadGenTot($actions_con, 'offsite_conversion.fb_pixel_lead');
												
												$cpl_con = @($spnd_con / $act_tot_con);
												$ctr_con = @($clik_con / $imp_con ) * 100;
												$cpc_con = @($spnd_con / $clik_con) ;
												$cpm_con = @($spnd_con / $imp_con) * 1000; 
												
											} else { $spnd_con = $ctr_con = $cpc_con = $cpm_con = ''; }
											
											/*if(isset($arr_con[$gd['fb_acc']][$k])) { $cpl_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'spend')); } else { $cpl_con =0; }
											if(isset($arr_con[$gd['fb_acc']][$k])) { $ctr_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'ctr')); } else { $ctr_con =0; }
											if(isset($arr_con[$gd['fb_acc']][$k])) { $cpc_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'cpc')); } else { $cpc_con =0; }
											if(isset($arr_con[$gd['fb_acc']][$k])) { $cpm_con = array_sum(array_column($arr_con[$gd['fb_acc']][$k], 'cpm')); } else { $cpm_con =0; }*/
											
											if(isset($arr_goo[$gd['g_acc']][$k])) {
											$cpl_goo = round($arr_goo[$gd['g_acc']][$k][8]/1000000, 2);
											$ctr_goo = $arr_goo[$gd['g_acc']][$k][9];
											$cpc_goo = round($arr_goo[$gd['g_acc']][$k][1]/1000000, 2);
											$cpm_goo = round($arr_goo[$gd['g_acc']][$k][7]/1000000, 2);
											} else { $cpl_goo = $ctr_goo = $cpc_goo = $cpm_goo = ''; }
											
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+1].''.$rowCount, round($cpl_lg,2));
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+2].''.$rowCount, round($ctr_lg,2));
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+3].''.$rowCount, round($cpc_lg,2));
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+4].''.$rowCount, round($cpm_lg,2));
											
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+5].''.$rowCount, round($cpl_con,2));
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+6].''.$rowCount, round($ctr_con,2));
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+7].''.$rowCount, round($cpc_con,2));
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+8].''.$rowCount, round($cpm_con,2)); 											
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+9].''.$rowCount, $cpl_goo);
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+10].''.$rowCount, $ctr_goo);
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+11].''.$rowCount, $cpc_goo);
											$objPHPExcel->getActiveSheet()->SetCellValue($group[$j+12].''.$rowCount, $cpm_goo);
											
											$k++;
									}
									$rowCount++;
									$sNo++;
								}
								
								$objPHPExcel->getActiveSheet()->freezePane('C2');
								$objPHPExcel->getActiveSheet()->getStyle("A1:AX1")->applyFromArray($styleArray);
								$objPHPExcel->getActiveSheet()->getStyle("A2:B".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("A3:AX3")->applyFromArray($BStyle);
								
								//$objPHPExcel->getActiveSheet()->getStyle("B2:B".($rowCount-1))->applyFromArray($BStyle);
								
								$objPHPExcel->getActiveSheet()->getStyle("C2:F".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("G2:J".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("K2:N".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("O2:R".($rowCount-1))->applyFromArray($BStyle);
								
								$objPHPExcel->getActiveSheet()->getStyle("S2:V".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("W2:Z".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("AA2:AD".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("AE2:AH".($rowCount-1))->applyFromArray($BStyle);
								
								$objPHPExcel->getActiveSheet()->getStyle("AI2:AL".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("AM2:AP".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("AQ2:AT".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("AU2:AX".($rowCount-1))->applyFromArray($BStyle);
								
								$objPHPExcel->getActiveSheet()->getStyle("C1:N3")->applyFromArray($fill_1);
								$objPHPExcel->getActiveSheet()->getStyle("O1:Z3")->applyFromArray($fill_2);
								$objPHPExcel->getActiveSheet()->getStyle("AA1:AL3")->applyFromArray($fill_1);
								$objPHPExcel->getActiveSheet()->getStyle("AM1:AX3")->applyFromArray($fill_2);
								
								
								//$objPHPExcel->setStyle("A1:Q1")->applyFromArray($style);
								/*	
								$rowCount = 2;
								$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, ' ');
								$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, ' ');
								
								$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, 'Organic Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, 'Paid Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, 'Engagement');
								$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, 'Post Clicks');
								$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, 'Post Likes');
								$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, 'Post Shares');
								$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, 'Post Comnt.');
								$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, 'No. of Posts');
															
								
								$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, 'Organic Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, 'Paid Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowCount, 'Engagement');
								$objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowCount, 'Post Clicks');
								$objPHPExcel->getActiveSheet()->SetCellValue('O'.$rowCount, 'Post Likes');
								$objPHPExcel->getActiveSheet()->SetCellValue('P'.$rowCount, 'Post Shares');
								$objPHPExcel->getActiveSheet()->SetCellValue('Q'.$rowCount, 'Post Comnt.');
								$objPHPExcel->getActiveSheet()->SetCellValue('R'.$rowCount, 'No. of Posts');
								
								$objPHPExcel->getActiveSheet()->SetCellValue('S'.$rowCount, 'Organic Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('T'.$rowCount, 'Paid Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('U'.$rowCount, 'Engagement');
								$objPHPExcel->getActiveSheet()->SetCellValue('V'.$rowCount, 'Post Clicks');
								$objPHPExcel->getActiveSheet()->SetCellValue('W'.$rowCount, 'Post Likes');
								$objPHPExcel->getActiveSheet()->SetCellValue('X'.$rowCount, 'Post Shares');
								$objPHPExcel->getActiveSheet()->SetCellValue('Y'.$rowCount, 'Post Comnt.');
								$objPHPExcel->getActiveSheet()->SetCellValue('Z'.$rowCount, 'No. of Posts');
								
								$objPHPExcel->getActiveSheet()->SetCellValue('AA'.$rowCount, 'Organic Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('AB'.$rowCount, 'Paid Reach');
								$objPHPExcel->getActiveSheet()->SetCellValue('AC'.$rowCount, 'Engagement');
								$objPHPExcel->getActiveSheet()->SetCellValue('AD'.$rowCount, 'Post Clicks');
								$objPHPExcel->getActiveSheet()->SetCellValue('AE'.$rowCount, 'Post Likes');
								$objPHPExcel->getActiveSheet()->SetCellValue('AF'.$rowCount, 'Post Shares');
								$objPHPExcel->getActiveSheet()->SetCellValue('AG'.$rowCount, 'Post Comnt.');
								$objPHPExcel->getActiveSheet()->SetCellValue('AH'.$rowCount, 'No. of Posts');*/
									
								/*$rowCount = 3;
								$i =1;
								foreach($r1 as $k => $v)
								{ 
									if(isset($r1[$k]['r1']) && $r1[$k]['r1']!='') { $org_lm = moneyFormatIndia($r1[$k]['r1']); } else { $org_lm = 0; }
									if(isset($r1[$k]['r2']) && $r1[$k]['r2']!='') { $paid_lm = moneyFormatIndia($r1[$k]['r2']); } else { $paid_lm = 0; }
									if(isset($r1[$k]['r3']) && $r1[$k]['r3']!='') { $eng_lm = moneyFormatIndia($r1[$k]['r3']); } else { $eng_lm = 0; }
									if(isset($r2[$k]['c2']) && $r2[$k]['c2']!='') { $click_lm = moneyFormatIndia($r2[$k]['c2']); } else { $click_lm = 0; }
									if(isset($r2[$k]['c4']) && $r2[$k]['c4']!='') { $like_lm = moneyFormatIndia($r2[$k]['c4']); } else { $like_lm = 0; }
									if(isset($r2[$k]['c5']) && $r2[$k]['c5']!='') { $shar_lm = moneyFormatIndia($r2[$k]['c5']); } else { $shar_lm = 0; }
									if(isset($r2[$k]['c6']) && $r2[$k]['c6']!='') { $com_lm = moneyFormatIndia($r2[$k]['c6']); } else { $com_lm = 0; }
									if(isset($r2[$k]['c1']) && $r2[$k]['c1']!='') { $post_lm = moneyFormatIndia($r2[$k]['c1']); } else { $post_lm = 0; }
									
									if(isset($r3[$k]['r1']) && $r3[$k]['r1']!='') { $org_cm = moneyFormatIndia($r3[$k]['r1']); } else { $org_cm = 0; }
									if(isset($r3[$k]['r2']) && $r3[$k]['r2']!='') { $paid_cm = moneyFormatIndia($r3[$k]['r2']); } else { $paid_cm = 0; }
									if(isset($r3[$k]['r3']) && $r3[$k]['r3']!='') { $eng_cm = moneyFormatIndia($r3[$k]['r3']); } else { $eng_cm = 0; }
									if(isset($r4[$k]['c2']) && $r4[$k]['c2']!='') { $click_cm = moneyFormatIndia($r4[$k]['c2']); } else { $click_cm = 0; }
									if(isset($r4[$k]['c4']) && $r4[$k]['c4']!='') { $like_cm = moneyFormatIndia($r4[$k]['c4']); } else { $like_cm = 0; }
									if(isset($r4[$k]['c5']) && $r4[$k]['c5']!='') { $shar_cm = moneyFormatIndia($r4[$k]['c5']); } else { $shar_cm = 0; }
									if(isset($r4[$k]['c6']) && $r4[$k]['c6']!='') { $com_cm = moneyFormatIndia($r4[$k]['c6']); } else { $com_cm = 0; }
									if(isset($r4[$k]['c1']) && $r4[$k]['c1']!='') { $post_cm = moneyFormatIndia($r4[$k]['c1']); } else { $post_cm = 0; }
									
									if(isset($r11[$k]['r1']) && $r11[$k]['r1']!='') { $org_lw = moneyFormatIndia($r11[$k]['r1']); } else { $org_lw = 0; }
									if(isset($r11[$k]['r2']) && $r11[$k]['r2']!='') { $paid_lw = moneyFormatIndia($r11[$k]['r2']); } else { $paid_lw = 0; }
									if(isset($r11[$k]['r3']) && $r11[$k]['r3']!='') { $eng_lw = moneyFormatIndia($r11[$k]['r3']); } else { $eng_lw = 0; }
									if(isset($r22[$k]['c2']) && $r22[$k]['c2']!='') { $click_lw = moneyFormatIndia($r22[$k]['c2']); } else { $click_lw = 0; }
									if(isset($r22[$k]['c4']) && $r22[$k]['c4']!='') { $like_lw = moneyFormatIndia($r22[$k]['c4']); } else { $like_lw = 0; }
									if(isset($r22[$k]['c5']) && $r22[$k]['c5']!='') { $shar_lw = moneyFormatIndia($r22[$k]['c5']); } else { $shar_lw = 0; }
									if(isset($r22[$k]['c6']) && $r22[$k]['c6']!='') { $com_lw = moneyFormatIndia($r22[$k]['c6']); } else { $com_lw = 0; }
									if(isset($r22[$k]['c1']) && $r22[$k]['c1']!='') { $post_lw = moneyFormatIndia($r22[$k]['c1']); } else { $post_lw = 0; }
									
									if(isset($r33[$k]['r1']) && $r33[$k]['r1']!='') { $org_cw = moneyFormatIndia($r33[$k]['r1']); } else { $org_cw = 0; }
									if(isset($r33[$k]['r2']) && $r33[$k]['r2']!='') { $paid_cw = moneyFormatIndia($r33[$k]['r2']); } else { $paid_cw = 0; }
									if(isset($r33[$k]['r3']) && $r33[$k]['r3']!='') { $eng_cw = moneyFormatIndia($r33[$k]['r3']); } else { $eng_cw = 0; }
									if(isset($r44[$k]['c2']) && $r44[$k]['c2']!='') { $click_cw = moneyFormatIndia($r44[$k]['c2']); } else { $click_cw = 0; }
									if(isset($r44[$k]['c4']) && $r44[$k]['c4']!='') { $like_cw = moneyFormatIndia($r44[$k]['c4']); } else { $like_cw = 0; }
									if(isset($r44[$k]['c5']) && $r44[$k]['c5']!='') { $shar_cw = moneyFormatIndia($r44[$k]['c5']); } else { $shar_cw = 0; }
									if(isset($r44[$k]['c6']) && $r44[$k]['c6']!='') { $com_cw = moneyFormatIndia($r44[$k]['c6']); } else { $com_cw = 0; }
									if(isset($r44[$k]['c1']) && $r44[$k]['c1']!='') { $post_cw = moneyFormatIndia($r44[$k]['c1']); } else { $post_cw = 0; }
									
									
									$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $i);
									$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $r0[$k]);
									
									$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $org_lm);
									$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $paid_lm );
									$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $eng_lm );
									$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $click_lm);
									$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $like_lm);
									$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $shar_lm );
									$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $com_lm);
									$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, $post_lm);
									
									$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, $org_cm);
									$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, $paid_cm );
									$objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowCount, $eng_cm );
									$objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowCount, $click_cm);
									$objPHPExcel->getActiveSheet()->SetCellValue('O'.$rowCount, $like_cm);
									$objPHPExcel->getActiveSheet()->SetCellValue('P'.$rowCount, $shar_cm );
									$objPHPExcel->getActiveSheet()->SetCellValue('Q'.$rowCount, $com_cm);
									$objPHPExcel->getActiveSheet()->SetCellValue('R'.$rowCount, $post_cm);
									
									$objPHPExcel->getActiveSheet()->SetCellValue('S'.$rowCount, $org_lw);
									$objPHPExcel->getActiveSheet()->SetCellValue('T'.$rowCount, $paid_lw );
									$objPHPExcel->getActiveSheet()->SetCellValue('U'.$rowCount, $eng_lw );
									$objPHPExcel->getActiveSheet()->SetCellValue('V'.$rowCount, $click_lw);
									$objPHPExcel->getActiveSheet()->SetCellValue('W'.$rowCount, $like_lw);
									$objPHPExcel->getActiveSheet()->SetCellValue('X'.$rowCount, $shar_lw );
									$objPHPExcel->getActiveSheet()->SetCellValue('Y'.$rowCount, $com_lw);
									$objPHPExcel->getActiveSheet()->SetCellValue('Z'.$rowCount, $post_lw);
									
									$objPHPExcel->getActiveSheet()->SetCellValue('AA'.$rowCount, $org_cw);
									$objPHPExcel->getActiveSheet()->SetCellValue('AB'.$rowCount, $paid_cw );
									$objPHPExcel->getActiveSheet()->SetCellValue('AC'.$rowCount, $eng_cw );
									$objPHPExcel->getActiveSheet()->SetCellValue('AD'.$rowCount, $click_cw);
									$objPHPExcel->getActiveSheet()->SetCellValue('AE'.$rowCount, $like_cw);
									$objPHPExcel->getActiveSheet()->SetCellValue('AF'.$rowCount, $shar_cw );
									$objPHPExcel->getActiveSheet()->SetCellValue('AG'.$rowCount, $com_cw);
									$objPHPExcel->getActiveSheet()->SetCellValue('AH'.$rowCount, $post_cw)->getStyle("C".$rowCount.":AH".$rowCount)->applyFromArray($style2);
									
									$rowCount++;
									$i++;
								}
								*/
								
								$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
								$fileN = 'Ads-Performance-Report_'.date('M-Y').'.xlsx';
								$objWriter->save(''.$server_path.'ads-perform/'.$fileN);
								include 'email/mail-ads.php';	
								
									