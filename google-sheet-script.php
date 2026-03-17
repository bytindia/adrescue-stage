<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
/*
$editData['gs_no'] = 0;
 
if(isset($_GET['id']) && $_GET['id']!='' && isset($_GET['val'])) 
{
	$cirRes = mysqli_query($conn, "select gs_no from gs_no WHERE ref_id='".$_GET['id']."'");	
	
	
	if(mysqli_num_rows($cirRes)==0) {
		$cirSql = "INSERT INTO gs_no (gs_no, ref_id, updated) VALUES ('".mysqli_real_escape_string($conn, trim($_GET['val']))."', '".mysqli_real_escape_string($conn, trim($_GET['id']))."', now());"; 
		mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	} else {
		while($Rdata=mysqli_fetch_array($cirRes)) { $editData = $Rdata; }
		$cirSql = "UPDATE gs_no SET gs_no='".mysqli_real_escape_string($conn, trim($_GET['val']))."', updated=now() WHERE ref_id='".mysqli_real_escape_string($conn, trim($_GET['id']))."'";
		mysqli_query($conn, $cirSql) or die(mysqli_error()); 	
	}
} 

echo $editData['gs_no'];
*/
require_once 'google-sheets-api/vendor/autoload.php';
require_once 'google-sheets-api/class-db.php';
require_once 'google-sheets-api/config.php';
include 'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1heDj5qa8MgW8vC0XwxEObbqDT-x6BKNqlASpk3N3Spg'; 
$sheetTab='FB Leads';
//echo "select response from leads_spreadsheet_response WHERE sheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'"; 
$cirRes = mysqli_query($conn, "select tbl_id,response,tabN from leads_spreadsheet_response WHERE sheetId='1heDj5qa8MgW8vC0XwxEObbqDT-x6BKNqlASpk3N3Spg' AND leadgen_id LIKE '%error%' AND cron!='yes'");	
//$leadV = [['Name','Email','Phone','Questions','Created','Source','Form','Campaign']];
//print_r($leadV);
while($Rdata=mysqli_fetch_array($cirRes)) { 
	
	 $v = str_replace('[["','',$Rdata['response']);
	 $v = str_replace('"]]','',$v);
	 $v = str_replace('","','<>',$v);
	 $leadV[0] = explode("<>",$v);  
	 $sheetTab = rtrim($Rdata['tabN'], "!");
	//print_r($leadV);  exit;
	//echo '<br>';
	//$leadV = [[$lName, $lEmail, $lPhone, $lCustom, $timeNow, 'Facebook', $formName, $campN, '']];
	$sheetTab = str_replace("!","",trim($sheetTab));
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);// exit;
	$cirSql = "UPDATE leads_spreadsheet_response SET cron='yes' WHERE tbl_id=".$Rdata['tbl_id']."";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
}
