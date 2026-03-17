<?php session_start(); 
//echo '<span id="msg">Preparing report... Please wait!!</span>';
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
?>
<!-- Bootstrap -->
<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	
    <!-- bootstrap-progressbar -->
    <link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom.css" rel="stylesheet">
    <link href="web/pagination.css" rel="stylesheet">
    <link href="assets/css/pagination.css" rel="stylesheet">
    <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            width: 80%;
    margin: 20px auto;
    background: white !important;
        }
        td.text-center {
    font-size: 20px;
    color: blue;
}
    </style>
<?php
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

require('PHPExcel-1.8/Classes/PHPExcel.php');
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);

if(isset($_POST) && isset($_POST['ids']) && count($_POST['ids'])>0){
    $ids = implode(',',$_POST['ids']);
    $qry = 'tbl_id IN ('.$ids.')';
} 

if(isset($_GET['id']))
{
    $qry = 'tbl_id = '.$_GET['id'].'';
}
//print_r($_POST); exit;
$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];

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
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
function getCSVdata($csvFile){
    $line_of_text[]=array();
    if (file_exists($csvFile)) {  
        $file_handle = fopen($csvFile, 'r');
        while (!feof($file_handle) ) {
                $line_of_text[] = fgetcsv($file_handle, 1024);
        }
        fclose($file_handle);
    }
	return $line_of_text;
}
									
									
$userId = 2;

$pg_id=''; $feedback = array();
$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc, email_ids, pg_id FROM ads_report_weekly where $qry AND uid=2 AND delete_status=0 order by tbl_id asc ");

while($row=mysqli_fetch_assoc($sqlRev)) { 
	$pg_id=$row['pg_id'];
	$getData[] = $row;
	if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc'];  }
	if($row['g_acc']!='') { $gStats[] = $row['g_acc']; }
}


function getWeekMonSun($weekOffset) {
	
	
	$dates_array[0] = strtotime(date('Y-m-d 00:00:00', strtotime('-3 days')));
	$dates_array[1] = strtotime(date('Y-m-d 23:59:59', strtotime('-1 days')));

	return $dates_array;
}
function getWeekMonSun2($weekOffset) {
	
	
	$dates_array[0] = strtotime(date('Y-m-d 00:00:00', strtotime('-30 days')));
	$dates_array[1] = strtotime(date('Y-m-d 23:59:59', strtotime('-1 days')));

	return $dates_array;
}

$weeks = $weeks2 = array();
for ($i = -5; $i <= -5; $i++) {
	$weeks[] = getWeekMonSun($i);
    $weeks2[] = getWeekMonSun2($i);
}

$arr_lg = $arr_lg = $arr_con = $arr_goo = $arr_goo2 =  $getData = $fbStats = $gStats = array();
									
$style = array('font' => array('bold' => true,'size' => 16,'color' => array('rgb' => 'ffffff')),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => '6d9eeb')));
$style2 = array('font' => array('bold' => true,'size' => 13),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),	'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'ffffcc')));		
$BStyle = array('font' => array('bold' => true,'size' => 12), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));

$red_bg = array('font' => array('color' => array('rgb' => 'ffffff')), 'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'ed3833')));
$green_bg = array('font' => array('color' => array('rgb' => 'ffffff')), 'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => '6aa84f')));

$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc, email_ids FROM ads_report_weekly where $qry AND uid=2 AND delete_status=0 order by tbl_id asc ");

while($row=mysqli_fetch_assoc($sqlRev)) { 
	$getData[] = $row;
	if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc'];  }
	if($row['g_acc']!='') { $gStats[] = $row['g_acc']; }
}

//d($getData); exit;

 
//d($getData); exit;
foreach($fbStats as $fd) 
{
	
	foreach ($weeks as $key => $value) 
	{
		$cirRes = mysqli_query($conn, "select report_1,report_1_30d from ads_daily WHERE uid='2' AND acc_type='FB' AND acc_id='".$fd."' AND st_dt='".$weeks[$key][0]."' AND en_dt='".$weeks[$key][1]."'");
		if(mysqli_num_rows($cirRes)==0) 
		{
			//echo implode(",",$fb_objective); exit;		
			$request_url = "https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=campaign&fields=objective,campaign_name,campaign_id,spend,actions&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION']}]&time_range[since]=".date("Y-m-d", $weeks[$key][0])."&time_range[until]=".date("Y-m-d", $weeks[$key][1])."&access_token=".$access_token."&limit=250";
			//exit;
			$fbData[$fd] = get_data($request_url);
            
            $request_url2 = "https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=objective,spend,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION']}]&date_preset=last_30d&access_token=".$access_token;
            $fbData2[$fd] = get_data($request_url2);
            //act_424100032235202/insights?level=account&fields=objective,spend,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION']}]&date_preset=last_3d
			
			
			$cirSql_fb = "INSERT INTO ads_daily (uid, acc_type, acc_id, report_1, report_1_30d, st_dt, en_dt, created) VALUES ('2', 'FB', '".$fd."', '".mysqli_real_escape_string($conn, serialize($fbData[$fd]))."', '".mysqli_real_escape_string($conn, serialize($fbData2[$fd]))."', '".$weeks[$key][0]."', '".$weeks[$key][1]."', now());"; 
			mysqli_query($conn, $cirSql_fb) or die(mysqli_error());
		} else {
			$row = mysqli_fetch_assoc($cirRes);
			$fbData[$fd] = unserialize($row['report_1']);
            $fbData2[$fd] = unserialize($row['report_1_30d']);
		}
        //$fb[$fd] = $fbData[$fd]['data'];
		//d($fbData2); exit;
		
	}
}

if(count($gStats)>0) {
	$accIds = $gStats;
	$accNames = 1;
	include 'download-ads-daily.php';	
	include 'download-ads-daily2.php';

	foreach($gStats as $gR)
	{
		//for($i=0;$i<1;$i++) {
				if(!isset($arr_goo[$gR])) 
				{
                    
                        $arr_goo[$gR] = getCSVdata(''.$server_path.'ads-daily/daily_'.$gR.'.csv');	
                        $arr_goo2[$gR] = getCSVdata(''.$server_path.'ads-daily/daily2_'.$gR.'.csv');		
                        $cirSql_g = "INSERT INTO ads_daily (uid, acc_type, acc_id, report_1, report_1_30d, st_dt, en_dt, created) VALUES ('2', 'G', '".$gR."', '".mysqli_real_escape_string($conn, serialize($arr_goo[$gR]))."', '".mysqli_real_escape_string($conn, serialize($arr_goo2[$gR]))."', '".$weeks[0][0]."', '".$weeks[0][1]."', now());"; 
                        mysqli_query($conn, $cirSql_g) or die(mysqli_error());	
                    //}
				}
		//}
	}
}


//d($G_records2); exit;
	
$table = '<table  class="table table-hover  table-bordered"><tr><th>Source</th><th>Spend</th><th>Leads</th><th>CPL</th></tr>';
$table_wa = '';

$rowCount = 1;

foreach($getData as $key => $getD) 
{
	//$getD[$key]['fb_acc']
    $table .= '<tr><td colspan="4" class="text-center">'.$getData[$key]['client_name'].'</td></tr>';
        $table_wa .= '*----- '.$getData[$key]['client_name'].' -----*%0A';
        $rowCount = $rowCount+1;
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $getData[$key]['client_name'])->mergeCells('A'.$rowCount.':D'.$rowCount.'')->getStyle('A'.$rowCount.':D'.$rowCount.'')->applyFromArray($style);
        $rowCount = $rowCount+1;
        //d($fb[$getData[$key]['fb_acc']]);

        if(isset($fbData[$getData[$key]['fb_acc']]['data']) && count($fbData[$getData[$key]['fb_acc']]['data'])>0)
        {
            $fb_data1 = $fbData[$getData[$key]['fb_acc']]['data'];
            $table .= '<tr><td colspan="4"><img src="images/fb-2.png" height="30" /></td></tr>';
            $table_wa .= '*FACEBOOK*%0A';

            $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Facebook')->mergeCells('A'.$rowCount.':D'.$rowCount.'')->getStyle('A'.$rowCount.':D'.$rowCount.'')->applyFromArray($style2);
            $rowCount = $rowCount+1;
            
            $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Campaign');
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), 'Spent');
            $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), 'Leads');
            $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), 'CPL');
            $objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount).":D".($rowCount))->applyFromArray($BStyle);
            $rowCount = $rowCount+1;
            
            $z = $fbTot_spend = $fbTot_cpl = $fbTot_lead = 0;
            foreach($fb_data1 as $k => $v){
                $lead = LeadGenTot($v['actions'], 'lead');
                $cpl = @($v['spend']/$lead);

                $fbTot_spend = $fbTot_spend + $v['spend'];
                $fbTot_lead = $fbTot_lead + $lead;
                

                $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), $v['campaign_name']);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), $v['spend']);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), $lead);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), round($cpl,2));
                $rowCount = $rowCount+1;

                if(++$z === count($fb_data1)){
                    $fbTot_cpl = @($fbTot_spend/$fbTot_lead);

                    $table .= '<tr><td> Last 3 days</td><td>'.$fbTot_spend.'</td><td> '.$fbTot_lead.'</td><td> '.round($fbTot_cpl).'</td></tr>';
                    $table_wa .= '```Last 3 days```%0ASpend: '.$fbTot_spend.'%0ALeads: '.$fbTot_lead.'%0ACPL: '.round($fbTot_cpl).'%0A%0A';

                    //$rowCount = $rowCount+1;
                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Last 3 days');
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), $fbTot_spend);
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), $fbTot_lead);
                    $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), round($fbTot_cpl,2));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount).":D".($rowCount))->applyFromArray($BStyle);
                    $rowCount = $rowCount+1;

                    if(isset($fbData2[$getData[$key]['fb_acc']]['data'][0])){
                        $fb_data2 = $fbData2[$getData[$key]['fb_acc']]['data'][0];
                        
                        $lead_30 = LeadGenTot($fb_data2['actions'], 'lead');
                        $cpl_30 = @($fb_data2['spend']/$lead_30);

                        $table .= '<tr><td>Last 30 days</td><td>'. $fb_data2['spend'].'</td><td> '.$lead_30.'</td><td> '.round($cpl_30).'</td></tr>';
                        $table_wa .= '```Last 30 days```%0ASpend: '. $fb_data2['spend'].'%0ALeads: '.$lead_30.'%0ACPL: '.round($cpl_30).'%0A%0A';

                        $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Last 30 days');
                        $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), $fb_data2['spend']);
                        $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), $lead_30);
                        $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), round($cpl_30,2));
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount).":D".($rowCount))->applyFromArray($BStyle);

                        if($fbTot_cpl > $cpl_30) {
                            $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount-1))->applyFromArray($red_bg);
                            $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount))->applyFromArray($green_bg);
                        }
    
                        if($fbTot_cpl < $cpl_30) {
                            $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount-1))->applyFromArray($green_bg);
                            $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount))->applyFromArray($red_bg);
                        }

                        $rowCount = $rowCount+1;
                    }
                    
                    

                    
                }
            }
        }
		
		//GOOGLE ADS
		if(isset($arr_goo[$getD['g_acc']]) && count($arr_goo[$getD['g_acc']])>1) {	
            $rowCount = $rowCount+1;
            $table .= '<tr><td colspan="4"><img src="images/google-ads.png"  height="30" /></td></tr>';
            $table_wa .= '*GOOGLE*%0A';

			$objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Google')->mergeCells('A'.$rowCount.':D'.$rowCount.'')->getStyle('A'.$rowCount.':D'.$rowCount.'')->applyFromArray($style2);
            $rowCount = $rowCount+1;		
			
            $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Campaign');
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), 'Spent');
            $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), 'Leads');
            $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), 'CPL');
            $objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount).":D".($rowCount))->applyFromArray($BStyle);
            $rowCount = $rowCount+1;

            $output = array_slice($arr_goo[$getD['g_acc']], 2); 
			$G_records = array_values($output);
			for ($i = 1; $i < (count($G_records)-1); $i++) 
			{	

                $lead = $G_records[$i][10];
                $cpl = round($G_records[$i][8]/1000000,2);
                
				if(trim($G_records[$i][1])=='--') { 
                    //$rowCount = $rowCount+1;
                    $G_records[$i][1]='Last 3 days'; 
                    
                    $table .= '<tr><td>Last 3 days</td><td>'.round($G_records[$i][5]/1000000).'</td><td> '.$lead.'</td><td> '.round($cpl).'</td></tr>';
                    $table_wa .= '```Last 3 days```%0ASpend: '.round($G_records[$i][5]/1000000).'%0ALeads: '.$lead.'%0ACPL: '.round($cpl).'%0A%0A';

                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), $G_records[$i][1]);
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), round($G_records[$i][5]/1000000,2));
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), $lead);
                    $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), $cpl);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount).":D".($rowCount))->applyFromArray($BStyle);
                    $rowCount = $rowCount+1;

                    $output2 = array_slice($arr_goo2[$getD['g_acc']], 3); 
                    $G_records2 = array_values($output2);
                    $lead_30 = $G_records2[0][6];
                    $cpl_30 = round($G_records2[0][4]/1000000,2);

                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), 'Last 30 days');
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), round($G_records2[0][1]/1000000,2));
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), $lead_30);
                    $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), $cpl_30);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($rowCount).":D".($rowCount))->applyFromArray($BStyle);

                    $table .= '<tr><td>Last 30 days</td><td>'.round($G_records2[0][1]/1000000).'</td><td> '.$lead_30.'</td><td> '.$cpl_30.'</td></tr>';
                    $table_wa .= '```Last 30 days```%0ASpend: '.round($G_records2[0][1]/1000000).'%0ALeads: '.$lead_30.'%0ACPL: '.$cpl_30.'%0A%0A';

                    if($cpl > $cpl_30) {
                        $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount-1))->applyFromArray($red_bg);
                        $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount))->applyFromArray($green_bg);
                    }

                    if($cpl < $cpl_30) {
                        $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount-1))->applyFromArray($green_bg);
                        $objPHPExcel->getActiveSheet()->getStyle("D".($rowCount))->applyFromArray($red_bg);
                    }
                    
                    $rowCount = $rowCount+1;
                    
                } else {
                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.($rowCount), $G_records[$i][1]);
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.($rowCount), round($G_records[$i][5]/1000000,2));
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.($rowCount), $lead);
                    $objPHPExcel->getActiveSheet()->SetCellValue('D'.($rowCount), $cpl);
                    $rowCount = $rowCount+1;
                }
			}
			
			//$rowCount = $rowCount+count($g_fields);
		}
		$rowCount = $rowCount+1;
        $table_wa .= '%0A';
}

    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(70);
    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);

    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	$fileN = 'BYT-FB-Google-Troubleshoot-'.date('d-M-Y').'.xlsx';
	$objWriter->save(''.$server_path.'daily-troubleshoot/'.$fileN); 


    

    if(isset($_POST['download'])) {
        header('Content-type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"".$fileN."\"");
        $objWriter->save('php://output');
    } else if(isset($_POST['troubleshoot'])) {
        $clientN = 'BYT Internal';
        $emailIds = 'bytyasir@gmail.com, prabhu@bytindia.com';
        //$emailIds = 'ramesh@bytindia.com, rajkumar@bytindia.com, prabhu@bytindia.com, bytvishnu@gmail.com, faheem@bytindia.com';
        include 'email/mail-ads-daily.php';	
        echo 'Email Sent!';
    } else if(isset($_POST['whatsapp'])) {
        $table .= '</table>';
        echo $table; 
        $wa_text_len =  strlen($table_wa);
        if($wa_text_len<4096) {
            echo '<a class="btn btn-lg btn-success full-width" href="https://api.whatsapp.com/send?text='.$table_wa.'" target="_blank">Whatsapp</a>'; 
        } else {
            echo "<h3>Sorry, You unable to whatsapp the ".$wa_text_len." characters length!</h3>";
        }
        exit;
    }
exit;
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
                                  