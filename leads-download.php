<?php //include 'header.php'; 

include 'db.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

require('PHPExcel-1.8/Classes/PHPExcel.php');
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);

//Auth();
$pgHeadline = 'Leads';
$pgID = 8;
$err =''; 

$query = "SELECT client_name, pg_name FROM leads_acc WHERE pg_id='".$_GET['id']."' limit 0,1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$client_name = $row['client_name']; 


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

$group = createColumnsArray('AZ');

$projQ = '';
if(isset($_GET['proj']) && $_GET['proj']!='All') { $projQ = "AND refName='".strtolower($_GET['proj'])."'"; }
//$dtQ = 'AND (created_time >'.strtotime($_GET['stDt']).' AND created_time <'.strtotime($_GET['enDt']).')'; 
//$dtQ = 'AND (created_time >='.strtotime($_GET['stDt']).' AND created_time <='.(strtotime($_GET['enDt']) + 60*60*11).')'; 
$dtQ = 'AND (created_time >='.(strtotime($_GET['stDt'])-34199).' AND created_time <='.((strtotime($_GET['enDt']) + 60*60*11)+12599).')';

$statement = " leads WHERE page_id='".$_GET['id']."' $dtQ $projQ";
//echo "SELECT * FROM ".$statement." order by tbl_id desc "; 

$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc ");
$i = (($page-1) * $per_page ) + 1;

while($row=mysqli_fetch_assoc($sqlRev)) { 
	$leads = $lead_rem = unserialize($row["lead"]);
	$leads['full_name'] = $leads["full_name"];
	$leads['form'] = $row["formN"];
	$leads['campaign'] = $row["campN"];
	$leads['adset'] = $row["adsetN"];
	$leads['ad'] = $row["adN"];
	$leads['created'] = date('d-m-Y h:i a',$row['created_time']+34199); //date('d-m-Y h:i a',$sqlROW['created_time']+34199)
	//array_push($leads,$leads['created']);
	//$getData[] = $leads;
	unset($lead_rem['full_name']); 
	unset($lead_rem['email']);
	unset($lead_rem['phone_number']);
	
	$qus = '';
	$ar_count = count($lead_rem);
	if($ar_count > 0) {
		$j = 1;
		foreach($lead_rem as $k => $v) {
			$qus .= '[Q'.$j.']: '.str_replace("_"," ",$k).' = '.str_replace("_"," ",$v);
			if($ar_count!=$j) { $qus .=', '; }
			$j++;
		}
	}
	
	$getData[] = array(
		'Name' => $leads['full_name'], 
		'Phone' => $leads['phone_number'], 
		'Email' => $leads['email'], 
		'Form' => $row["formN"], 
		'Campaign' => $row["campN"], 
		'Adset' => $row["adsetN"], 
		'Ad' => $row["adN"], 
		'Questions' => $qus,
		'Date' => $leads['created']
	);
}

//d($getData); exit;

if(mysqli_num_rows($sqlRev)>0) 
{
	$style = array('font' => array('bold' => true,'size' => 16),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => '9fdf9f')));
	
	$style2 = array('font' => array('bold' => true,'size' => 13),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),	'fill' => array(	'type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'ffffcc')));		
	
	$BStyle = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
	
	$sqlRev=mysqli_query($conn, "SELECT client_name, fb_acc, g_acc, email_ids FROM ads_report_weekly where tbl_id=".$_GET['id']." AND uid=2 AND delete_status=0 order by tbl_id asc limit 0, 1");
	
	
	$rowCount = 1;
	
	$i =0;
	foreach($getData[0] as $key => $val) {
		//echo $group[$i].''.$rowCount.' --> '.$key.'<br>';	
		$key = ucwords(str_replace("_"," ",$key));
		$objPHPExcel->getActiveSheet()->SetCellValue($group[$i].''.$rowCount, $key);
		$i++;
	}
	
	//echo '<br><br>';
	
	$rowCount = 2;
	foreach($getData as $key => $val) {
		$i =0;
		foreach($val as $k => $v) {
			//echo $group[$i].''.$rowCount.' --> '.$v.'<br>';	
			$objPHPExcel->getActiveSheet()->SetCellValue($group[$i].''.$rowCount, $v);
			$i++;	
		}
		//echo '---------------<br>';	
		$rowCount++;
	}
	
	//$getData[$key]['client_name'] = 'test';
	$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	$fileN = str_replace(" ","_", $client_name.'-'.$_GET['proj']).'_Leads_'.date('d-M-Y').'.xlsx';
	//$objWriter->save(''.$server_path.'ads-perform/'.$fileN); 
	
	// We'll be outputting an excel file
	header('Content-type: application/vnd.ms-excel');
	
	// It will be called file.xls
	header('Content-Disposition: attachment; filename="'.$fileN.'"');
	
	// Write file to the browser
	$objWriter->save('php://output');
} else {
	echo 'No leads!';
}
?>File Downloaded!! <input type="button" class="btn btn-success" style="font-weight: bold;display: inline;" value="Close Window" onclick="closeMe()">
<script>
function closeMe()
{
    window.opener = self;
    window.close();
}
</script>