<?php 
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

require('PHPExcel-1.8/Classes/PHPExcel.php');
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);




//include 'config.php';




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

$userId = 2;

									$start_lm = strtotime("first day of last month"); //date('m/01/Y');
									$end_lm = strtotime("last day of last month");
									
									$start_cm = strtotime("first day of this month"); //date('m/01/Y');
									$end_cm = strtotime("now");
									
									$start_lw = strtotime('monday last week'); //date('m/01/Y');
									$end_lw = strtotime("sunday last week");
									
									$start_cw = strtotime('monday this week'); //date('m/01/Y');
									$end_cw = strtotime("now"); //sunday last week
								
								$q0 = mysqli_query($conn, "SELECT pg_id,pg_name FROM `pages` WHERE uid='".$userId."' AND active=1 AND admin_delete=0");
								$r0 = $pgIds = array();
								while($rw0=mysqli_fetch_assoc($q0)) { $r0[$rw0['pg_id']] = $rw0['pg_name']; $pgIds[] = $rw0['pg_id']; }
								if(count($pgIds)>0) { $impIds = "AND pg_id IN (".implode(',',$pgIds).") "; } else { $impIds = "AND pg_id IN (0) "; }
								
								//echo "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_lm." AND end_time_unix<=".$end_lm." $impIds  GROUP BY pg_id"; exit;
								
								$q1 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_lm." AND end_time_unix<=".$end_lm." $impIds  GROUP BY pg_id");
								
								$q2 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_lm." AND created_time_unix<=".$end_lm." $impIds GROUP BY pg_id");
								
								
								$q3 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_cm." AND end_time_unix<=".$end_cm." $impIds GROUP BY pg_id");
								
								$q4 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_cm." AND created_time_unix<=".$end_cm." $impIds GROUP BY pg_id");
								
								$q11 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_lw." AND end_time_unix<=".$end_lw." $impIds GROUP BY pg_id");
								
								$q22 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_lw." AND created_time_unix<=".$end_lw." $impIds GROUP BY pg_id");
								
								
								$q33 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$userId."' AND end_time_unix>=".$start_cw." AND end_time_unix<=".$end_cw." $impIds GROUP BY pg_id");
								
								$q44 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$userId."' AND created_time_unix>=".$start_cw." AND created_time_unix<=".$end_cw." $impIds GROUP BY pg_id");
								
								 $r1 = $r2 = $r3 = $r4 = $r11 = $r22 = $r33 = $r44 =array();
								//while($rw0=mysqli_fetch_assoc($q0)) { $r0[$rw0['pg_id']] = $rw0['pg_name']; }
								while($rw1=mysqli_fetch_assoc($q1)) { $r1[$rw1['pg_id']] = $rw1; }
								while($rw2=mysqli_fetch_assoc($q2)) { $r2[$rw2['pg_id']] = $rw2; }
								while($rw3=mysqli_fetch_assoc($q3)) { $r3[$rw3['pg_id']] = $rw3; }
								while($rw4=mysqli_fetch_assoc($q4)) { $r4[$rw4['pg_id']] = $rw4; }
								
								while($rw11=mysqli_fetch_assoc($q11)) { $r11[$rw11['pg_id']] = $rw11; }
								while($rw22=mysqli_fetch_assoc($q22)) { $r22[$rw22['pg_id']] = $rw22; }
								while($rw33=mysqli_fetch_assoc($q33)) { $r33[$rw33['pg_id']] = $rw33; }
								while($rw44=mysqli_fetch_assoc($q44)) { $r44[$rw44['pg_id']] = $rw44; }
								
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
								$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Page')->getColumnDimension('B')->setWidth(30);
								
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, "Last Month (".date('M d', $start_lm).' - '.date('d, Y', $end_lm).")")->mergeCells('C1:J1');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, 1, "This Month (".date('M d', $start_cm).' - '.date('d, Y', $end_cm).")")->mergeCells('K1:R1');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(18, 1, "Last Week (".date('M d', $start_lw).' - '.date('d, Y', $end_lw).")")->mergeCells('S1:Z1');
								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(26, 1, "This Week (".date('M d', $start_cw).' - '.date('d, Y', $end_cw).")")->mergeCells('AA1:AH1')->getStyle("A1:AH1")->applyFromArray($styleArray);
								
								//$objPHPExcel->setStyle("A1:Q1")->applyFromArray($style);
								
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
								$objPHPExcel->getActiveSheet()->SetCellValue('AH'.$rowCount, 'No. of Posts');
									
								$rowCount = 3;
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
								
								$objPHPExcel->getActiveSheet()->freezePane('C2');
								$objPHPExcel->getActiveSheet()->getStyle("A2:AH2")->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("A2:A".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("B2:B".($rowCount-1))->applyFromArray($BStyle);
								
								$objPHPExcel->getActiveSheet()->getStyle("C2:J".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("K2:R".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("S2:Z".($rowCount-1))->applyFromArray($BStyle);
								$objPHPExcel->getActiveSheet()->getStyle("AA2:AH".($rowCount-1))->applyFromArray($BStyle);
								
								$objPHPExcel->getActiveSheet()->getStyle("C1:J2")->applyFromArray($fill_1);
								$objPHPExcel->getActiveSheet()->getStyle("K1:R2")->applyFromArray($fill_2);
								$objPHPExcel->getActiveSheet()->getStyle("S1:Z2")->applyFromArray($fill_1);
								$objPHPExcel->getActiveSheet()->getStyle("AA1:AH2")->applyFromArray($fill_2);
								
								//$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
								
								$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
								$fileN = 'Page-Performance-Report_'.date('M-Y').'.xlsx';
								$objWriter->save(''.$server_path.'ads-perform/'.$fileN);
								//$objWriter->save('php://output');  exit;
								include 'email/mail-page.php';	
								
									