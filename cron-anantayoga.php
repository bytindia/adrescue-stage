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
//echo $sheetTab = rtrim('FB Leads!', "!"); //str_replace("!","",$Rdata['tabN']);
//exit;
require_once 'google-sheets-api/vendor/autoload.php';
require_once 'google-sheets-api/class-db.php';
require_once 'google-sheets-api/config.php';
include 'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'; 
$sheetTab='FB Leads';
//echo "select response from leads_spreadsheet_response WHERE sheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'"; 
$cirRes = mysqli_query($conn, "select tbl_id,response,tabN from leads_spreadsheet_response WHERE sheetId='".$spreadsheetId."' AND leadgen_id LIKE '%error%' AND cron!='yes'");	
//$leadV = [['Name','Email','Phone','Questions','Created','Source','Form','Campaign']];
//print_r($leadV);
while($Rdata=mysqli_fetch_array($cirRes)) { 
	
	 $v = str_replace('[["','',$Rdata['response']);
	 $v = str_replace('"]]','',$v);
	 $v = str_replace('","','<>',$v);
	 $leadV[0] = explode("<>",$v);  
	 $sheetTab = $Rdata['tabN']; //str_replace("!","",$Rdata['tabN']);
	//print_r($leadV);  exit;
	//echo '<br>';
	//$leadV = [[$lName, $lEmail, $lPhone, $lCustom, $timeNow, 'Facebook', $formName, $campN, '']];
	$sheetTab = str_replace("!","",trim($sheetTab));
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);// exit;
	$cirSql = "UPDATE leads_spreadsheet_response SET cron='yes' WHERE tbl_id=".$Rdata['tbl_id']."";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
}
/*
//echo "select tbl_id, name, email, phone, src, med, camp, created from ananta_leads WHERE WHERE ref is null OR ref=''"; 
$cirRes2 = mysqli_query($conn, "select tbl_id, name, email, phone, src, med, camp, created from ananta_leads WHERE ref is null OR ref=''");	
//$leadV = [['Name','Email','Phone','Questions','Created','Source','Form','Campaign']];
//print_r($leadV);
while($Rdata=mysqli_fetch_array($cirRes2)) { 
	
	 //$v = str_replace('[["','',$Rdata['response']);
	 //$v = str_replace('"]]','',$v);
	 //$v = str_replace('","','<>',$v);
	 //$leadV[0] = explode("<>",$v);  
	//print_r($leadV);  exit;
	//echo '<br>';
	//[["Shivaraj Savalagi","fly.shivaraj@gmail.com","p:+918861249886","[Q1]: are you interested in online yoga class ? = yes ","28-12-2023, 09:29 am","Facebook","LG - Ananta yoga - CF - Nov 23 - india","LG - BYT - Ananta yoga - Nov 23 - INDIA"]]
	$leadV = [[$Rdata['name'],$Rdata['email'],$Rdata['phone']," ", date('d-m-Y, h:i a',strtotime($Rdata['created'])),$Rdata['src'],$Rdata['med'],$Rdata['camp']]];
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);// exit;
	$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$Rdata['tbl_id']."";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	//exit;
}
*/

$spreadsheetId='1qkxJvF8U5nBCJDQpztxKkOJLU6rOQ2tBgFx6nLay9tU'; 
$sheetTab='Meta Leads';
//echo "select response from leads_spreadsheet_response WHERE sheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'"; 
$cirRes = mysqli_query($conn, "select tbl_id,response,tabN from leads_spreadsheet_response WHERE sheetId='".$spreadsheetId."' AND response LIKE '%tripti%' AND leadgen_id LIKE '%error%' AND cron!='yes'");	 
//$leadV = [['Name','Email','Phone','Questions','Created','Source','Form','Campaign']];
//print_r($leadV);
while($Rdata=mysqli_fetch_array($cirRes)) { 
	
	 $v = str_replace('[["','',$Rdata['response']);
	 $v = str_replace('"]]','',$v);
	 $v = str_replace('","','<>',$v);
	 $leadV[0] = explode("<>",$v);  
	 $sheetTab = $Rdata['tabN']; //str_replace("!","",$Rdata['tabN']);
	//print_r($leadV);  exit;
	//echo '<br>';
	//$leadV = [[$lName, $lEmail, $lPhone, $lCustom, $timeNow, 'Facebook', $formName, $campN, '']];
	$sheetTab = str_replace("!","",trim($sheetTab));
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);// exit;
	$cirSql = "UPDATE leads_spreadsheet_response SET cron='yes' WHERE tbl_id=".$Rdata['tbl_id']."";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
}

$spreadsheetId='1Je57NEHjFS0Ln75DRCnqCbhOO60UZWvmikPTbjwY-4s'; 
$sheetTab='Meta';
//echo "select response from leads_spreadsheet_response WHERE sheetId='1ejielk8NM2_ukHX7Yd-ghiZVHsdT2PcU8m5Fax0ZXi4'"; 
//echo "select tbl_id,response,tabN from leads_spreadsheet_response WHERE sheetId='".$spreadsheetId."' AND (response LIKE '%byt%' OR  response LIKE '%breezehi%') AND leadgen_id LIKE '%error%' AND cron!='yes'";
$cirRes = mysqli_query($conn, "select tbl_id,response,tabN from leads_spreadsheet_response WHERE sheetId='".$spreadsheetId."' AND (response LIKE '%byt%' OR  response LIKE '%breezehi%') AND leadgen_id LIKE '%error%' AND cron!='yes'");	 
//$leadV = [['Name','Email','Phone','Questions','Created','Source','Form','Campaign']];
//print_r($leadV);
while($Rdata=mysqli_fetch_array($cirRes)) { 
	
	 $v = str_replace('[["','',$Rdata['response']);
	 $v = str_replace('"]]','',$v);
	 $v = str_replace('","','<>',$v);
	 $leadV[0] = explode("<>",$v);  
	 $sheetTab = $Rdata['tabN']; //str_replace("!","",$Rdata['tabN']);
	//print_r($leadV);  exit;
	//echo '<br>';
	//$leadV = [[$lName, $lEmail, $lPhone, $lCustom, $timeNow, 'Facebook', $formName, $campN, '']];
	$sheetTab = str_replace("!","",trim($sheetTab));
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);// exit;
	$cirSql = "UPDATE leads_spreadsheet_response SET cron='yes' WHERE tbl_id=".$Rdata['tbl_id']."";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
}

//Crescent
$spreadsheetId='1wfWbr9464dB_Pkaak4oCMZa7PuJ6dZUIDcE2AQ0v9BQ'; 

$cirRes = mysqli_query($conn, "select tbl_id,response,tabN from leads_spreadsheet_response WHERE sheetId='".$spreadsheetId."' AND leadgen_id LIKE '%error%' AND cron!='yes'");	 

while($Rdata=mysqli_fetch_array($cirRes)) { 
	
	 $v = str_replace('[["','',$Rdata['response']);
	 $v = str_replace('"]]','',$v);
	 $v = str_replace('","','<>',$v);
	 $leadV[0] = explode("<>",$v);  
	 $sheetTab = $Rdata['tabN']; //str_replace("!","",$Rdata['tabN']);
	//print_r($leadV);  exit;
	//echo '<br>';
	//$leadV = [[$lName, $lEmail, $lPhone, $lCustom, $timeNow, 'Facebook', $formName, $campN, '']];
	$sheetTab = str_replace("!","",trim($sheetTab));
	append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);// exit;
	$cirSql = "UPDATE leads_spreadsheet_response SET cron='yes' WHERE tbl_id=".$Rdata['tbl_id']."";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
}