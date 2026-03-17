<?php
header('Content-type: application/json');

include 'db.php';

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

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
if(isset($_POST['cmp_dt'])) { 
	$_SESSION['stDt2'] = $_POST['start2'];
	$_SESSION['enDt2'] = $_POST['end2'];
}
$today =  date('Y-m-d',strtotime($_SESSION['enDt']));
$last90 =  date('Y-m-d',strtotime($_SESSION['stDt']));
	
	
//$_POST['tblId']=5; $_POST['uId']=2;;

if($_POST['tblId']!='' && $_POST['uId']!='') 
{
	echo $query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=".$_POST['uId'].""; exit;
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	$access_token = $row['access_token']; 
	$g_mcc = $row['g_mcc']; 
	$g_refresh_token = $row['g_refresh_token'];
	
	include 'functions-report.php';
	
	$queryD = "SELECT * FROM client_dashboard WHERE tbl_id=".$_POST['tblId']."";
	$resultD = mysqli_query($conn, $queryD);
	$rowD = mysqli_fetch_assoc($resultD);
	
	
		
	//FACEBOOK ADS Report
	if(isset($rowD['fb_acc']) && trim($rowD['fb_acc'])!='') 
	{			
			//$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$rowD['fb_acc'].'/insights?level=campaign&fields=objective,campaign_id,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,relevance_score,outbound_clicks&time_range[since]='.date("Y-m-d", strtotime($last90)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'&time_increment=1&limit=500&access_token='.$access_token;
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$rowD['fb_acc'].'/insights?level=campaign&fields=objective,campaign_id,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,outbound_clicks&time_range[since]='.date("Y-m-d", strtotime($last90)).'&time_range[until]='.date("Y-m-d", strtotime($today)).'&time_increment=1&limit=500&access_token='.$access_token;
			$fbData = FB_Report($request_url, $rowD['fb_acc'], $_POST['uId'], $conn);
			//d($fbData); exit;
	}
	if(isset($rowD['g_acc']) && trim($rowD['g_acc'])!='') 
	{
			$accIds = array($rowD['g_acc']);
			include 'download-campaign-dashboard.php';	
			$gData = G_Report(''.$server_path.'ads-perform/dash_'.trim($rowD['g_acc']).'.csv', trim($rowD['g_acc']), $_POST['uId'], $conn);	//getCSVdata($csvFile, $gId, $conn)
			//d($gData); exit;
	}
		
		//LinkedIn ADS Report
	if(isset($rowD['in_acc']) && trim($rowD['in_acc'])!='') 
	{
			$uId = $_POST['uId'];
			$in_acc = $rowD['in_acc'];
			//include 'linkedin-report.php';
			//include 'download-campaign-dashboard.php';	
			//$gData = getCSVdata(''.$server_path.'ads-perform/dash_'.$accIds.'.csv');	
	}
}
d($fbData); d($gData); exit;
$array = array('success' => 1);

$valDt = date('d/m/Y',strtotime($_SESSION['stDt']))."<br>".date('d/m/Y',strtotime($_SESSION['enDt']));
$timeQry = " AND stDt>=".strtotime($_SESSION['stDt'])." AND enDt<=".strtotime($_SESSION['enDt'])."";

if(isset($_SESSION['cmp_dt'])) {
	$valDt2 = date('d/m/Y',strtotime($_SESSION['stDt2']))."<br>".date('d/m/Y',strtotime($_SESSION['enDt2']));
	$timeQry2 = " AND stDt>=".strtotime($_SESSION['stDt2'])." AND enDt<=".strtotime($_SESSION['enDt2'])."";
}


$allData = $gData = $fbData = $fbData1 = $fbData2 = $inData = $inData1 = $inData2 = $gIds = $fbIds = $inIds = array();
$sqlRev=mysqli_query($conn, "SELECT tbl_id,client_name,fb_acc,g_acc,in_acc FROM client_dashboard WHERE tbl_id=".$_POST['tblId']."");
$i = (($page-1) * $per_page ) + 1;


while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['fb_acc']!='') { $fbIds[] = $sqlROW['fb_acc']; }
	if($sqlROW['g_acc']!='') { $gIds[] = $sqlROW['g_acc']; }
	if($sqlROW['in_acc']!='') { $inIds[] = $sqlROW['in_acc']; }
	$allData[] = $sqlROW;
}
//d($fbData); 
if(count($fbIds)>0) {			
//echo "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $timeQry group by fb_acc"; exit;								
	$rep_fb = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $timeQry group by fb_acc");
	while($rw_fb =mysqli_fetch_array($rep_fb))	{	$fbData[$rw_fb['fb_acc']] = $rw_fb;	}
	
	$rep_fb1 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_lg) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='4' $timeQry group by fb_acc");
	while($rw_fb1 =mysqli_fetch_array($rep_fb1))	{	$fbData1[$rw_fb1['fb_acc']] = $rw_fb1;	}
	
	$rep_fb2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_con) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='2' $timeQry group by fb_acc");
	while($rw_fb2 =mysqli_fetch_array($rep_fb2))	{	$fbData2[$rw_fb2['fb_acc']] = $rw_fb2;	}
}

if(count($fbIds)>0 && isset($_SESSION['cmp_dt'])) {			
//echo "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $timeQry group by fb_acc"; exit;								
	$rep_fb_2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $timeQry2 group by fb_acc");
	while($rw_fb_2 =mysqli_fetch_array($rep_fb_2))	{	$fbData_2[$rw_fb_2['fb_acc']] = $rw_fb_2;	}
	
	$rep_fb1_2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_lg) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='4' $timeQry2 group by fb_acc");
	while($rw_fb1_2 =mysqli_fetch_array($rep_fb1_2))	{	$fbData1_2[$rw_fb1_2['fb_acc']] = $rw_fb1_2;	}
	
	$rep_fb2_2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_con) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='2' $timeQry2 group by fb_acc");
	while($rw_fb2_2 =mysqli_fetch_array($rep_fb2_2))	{	$fbData2_2[$rw_fb2_2['fb_acc']] = $rw_fb2_2;	}
}

//d($fbData); d($fbData1); d($fbData2); //exit;
if(count($gIds)>0) {
	//echo "SELECT SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).")"; 
	//echo "SELECT g_acc, SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") group by g_acc"; 
	$rep_g = mysqli_query($conn, "SELECT g_acc, SUM(spend) as spend, SUM(conv) as leads FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") $timeQry group by g_acc");
	while($rw_g =mysqli_fetch_array($rep_g))		{	$gData[$rw_g['g_acc']] = $rw_g;	}
}
if(count($gIds)>0 && isset($_SESSION['cmp_dt'])) {
	//echo "SELECT SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).")"; 
	//echo "SELECT g_acc, SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") group by g_acc"; 
	$rep_g_2 = mysqli_query($conn, "SELECT g_acc, SUM(spend) as spend, SUM(conv) as leads FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") $timeQry2 group by g_acc");
	while($rw_g_2 =mysqli_fetch_array($rep_g_2))		{	$gData_2[$rw_g_2['g_acc']] = $rw_g_2;	}
}

if(count($inIds)>0) {
	$rep_in = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") $timeQry group by in_acc");
	while($rw_in =mysqli_fetch_array($rep_in))	{	$inData[$rw_in['in_acc']] = $rw_in;	}
	
	$rep_in1 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(one_click_leads) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='0' $timeQry group by in_acc");
	while($rw_in1 =mysqli_fetch_array($rep_in1))	{	$inData1[$rw_in1['in_acc']] = $rw_in1;	}
	
	$rep_in2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(web_conv) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='5' $timeQry group by in_acc");
	while($rw_in2 =mysqli_fetch_array($rep_in2))	{	$inData2[$rw_in2['in_acc']] = $rw_in2;	}
}
if(count($inIds)>0 && isset($_SESSION['cmp_dt'])) {
	$rep_in_2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") $timeQry2 group by in_acc");
	while($rw_in_2 =mysqli_fetch_array($rep_in_2))	{	$inData_2[$rw_in_2['in_acc']] = $rw_in_2;	}
	
	$rep_in1_2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(one_click_leads) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='0' $timeQry2 group by in_acc");
	while($rw_in1_2 =mysqli_fetch_array($rep_in1_2))	{	$inData1_2[$rw_in1_2['in_acc']] = $rw_in1_2;	}
	
	$rep_in2_2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(web_conv) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='5' $timeQry2 group by in_acc");
	while($rw_in2_2 =mysqli_fetch_array($rep_in2_2))	{	$inData2_2[$rw_in2_2['in_acc']] = $rw_in2_2;	}
}



$i=1;
function changeDiff($a,$b) {
	$a = str_replace(',','',$a);
	$b = str_replace(',','',$b);
	if(is_numeric($a) && is_numeric($b) && !is_nan($a) && !is_nan($b) && !is_infinite($a) && !is_infinite($b)) { $c = round($a - $b); } else { $c = '-';}
	return $c; 
}
function changePer($a,$b) {
	$a = str_replace(',','',$a);
	$b = str_replace(',','',$b);
	//if($a!='-' && $b!='-') {
	if(is_numeric($a) && is_numeric($b) && !is_nan($a) && !is_nan($b) && !is_infinite($a) && !is_infinite($b)) {
	$p_v = round(@((($a - $b)/$b)*100));
	if($p_v>0) { $neg='plus'; } else { $neg='minus'; }
	$c = "<span class='".$neg."'>".$p_v."%</span>"; //round((($a - $b)/$b)*100, 2).'%'; 
	} else { $c = '-';}
	return $c; 
}
foreach($allData as $k => $v) 
{
	
	//FACEBOOK
	$fb_spend = $fb_lead = $fb_cpc = $fb_spend_con = $fb_lead_con = $fb_cpc_con = '-';
	$fb_spend_c = $fb_lead_c = $fb_cpc_c = $fb_spend_con_c = $fb_lead_con_c = $fb_cpc_con_c = '-';
	
	if(isset($fbData[$v['fb_acc']])) { 
		$fb_spend = moneyFormatIndia(round($fbData[$v['fb_acc']]['spend']));
		$fb_lead = $fbData1[$v['fb_acc']]['leads'];
		$fb_cpc = round(@($fbData1[$v['fb_acc']]['spend']/$fbData1[$v['fb_acc']]['leads']));
	}
	if(isset($fbData2[$v['fb_acc']])) { 
		$fb_lead_con = $fbData2[$v['fb_acc']]['leads'];
		$fb_cpc_con = round(@($fbData2[$v['fb_acc']]['spend']/$fbData2[$v['fb_acc']]['leads']));
	}	
	if(isset($fbData_2[$v['fb_acc']])) { 
		$fb_spend_c = moneyFormatIndia(round($fbData_2[$v['fb_acc']]['spend']));
		$fb_lead_c = $fbData1_2[$v['fb_acc']]['leads'];
		$fb_cpc_c = round(@($fbData1_2[$v['fb_acc']]['spend']/$fbData1_2[$v['fb_acc']]['leads']));
	}
	if(isset($fbData2_2[$v['fb_acc']])) { 
		$fb_lead_con_c = $fbData2_2[$v['fb_acc']]['leads'];
		$fb_cpc_con_c = round(@($fbData2_2[$v['fb_acc']]['spend']/$fbData2_2[$v['fb_acc']]['leads']));
	}
	
	//GOOGLE
	$g_spend = $g_lead = $g_cpc = '-';
	$g_spend_c = $g_lead_c = $g_cpc_c = '-';
	
	if(isset($gData[$v['g_acc']])) { 
		$g_spend = moneyFormatIndia(round($gData[$v['g_acc']]['spend']/1000000));
		$g_lead = round(@($gData[$v['g_acc']]['leads']));
		$g_cpc = round(@(($gData[$v['g_acc']]['spend']/1000000)/$gData[$v['g_acc']]['leads']));
	}
	if(isset($gData_2[$v['g_acc']])) { 
		$g_spend_c = moneyFormatIndia(round($gData_2[$v['g_acc']]['spend']/1000000));
		$g_lead_c = round(@($gData_2[$v['g_acc']]['leads']));
		$g_cpc_c = round(@(($gData_2[$v['g_acc']]['spend']/1000000)/$gData_2[$v['g_acc']]['leads']));
	}
	
	//LINKEDIN
	$in_spend = $in_lead = $in_cpc = $in_spend_con = $in_lead_con = $in_cpc_con = '-';
	$in_spend_c = $in_lead_c = $in_cpc_c = $in_spend_con_c = $in_lead_con_c = $in_cpc_con_c = '-';
	
	if(isset($inData[$v['in_acc']])) { 
		$in_spend = moneyFormatIndia(round($inData[$v['in_acc']]['spend']));
		$in_lead = $inData1[$v['in_acc']]['leads'];
		$in_cpc = round(@($inData1[$v['in_acc']]['spend']/$inData1[$v['in_acc']]['leads']));
	}
	if(isset($inData2[$v['in_acc']])) { 
		$in_lead_con = $inData2[$v['in_acc']]['leads'];
		$in_cpc_con = round(@($inData2[$v['in_acc']]['spend']/$inData2[$v['in_acc']]['leads']));
	}
	if(isset($inData_2[$v['in_acc']])) { 
		$in_spend_c = moneyFormatIndia(round($inData_2[$v['in_acc']]['spend']));
		$in_lead_c = $inData1_2[$v['in_acc']]['leads'];
		$in_cpc_c = round(@($inData1_2[$v['in_acc']]['spend']/$inData1_2[$v['in_acc']]['leads']));
	}
	if(isset($inData2_2[$v['in_acc']])) { 
		$in_lead_con_c = $inData2_2[$v['in_acc']]['leads'];
		$in_cpc_con_c = round(@($inData2_2[$v['in_acc']]['spend']/$inData2_2[$v['in_acc']]['leads']));
	}
	
	$fb_reports = array(
		array($fb_spend,$fb_spend_c,changeDiff($fb_spend,$fb_spend_c,$fb_spend_c),changePer($fb_spend,$fb_spend_c)),
		array($fb_lead,$fb_lead_c,changeDiff($fb_lead,$fb_lead_c,$fb_lead_c),changePer($fb_lead,$fb_lead_c)),
		array($fb_cpc,$fb_cpc_c,changeDiff($fb_cpc,$fb_cpc_c,$fb_cpc_c),changePer($fb_cpc,$fb_cpc_c)),
		array($fb_lead_con,$fb_lead_con_c,changeDiff($fb_lead_con,$fb_lead_con_c,$fb_lead_con_c),changePer($fb_lead_con,$fb_lead_con_c)),
		array($fb_cpc_con,$fb_cpc_con_c,changeDiff($fb_cpc_con,$fb_cpc_con_c,$fb_cpc_con_c),changePer($fb_cpc_con,$fb_cpc_con_c))
	);
	$g_reports = array(
		array($g_spend,$g_spend_c,changeDiff($g_spend,$g_spend_c,$g_spend_c),changePer($g_spend,$fb_spend_c)),
		array($g_lead,$g_lead_c,changeDiff($g_lead,$g_lead_c,$g_lead_c),changePer($g_lead,$g_lead_c)),
		array($g_cpc,$g_cpc_c,changeDiff($g_cpc,$g_cpc_c,$g_cpc_c),changePer($g_cpc,$g_cpc_c))
	);
	$in_reports = array(
		array($in_spend,$in_spend_c,changeDiff($in_spend,$in_spend_c,$in_spend_c),changePer($in_spend,$in_spend_c)),
		array($in_lead,$in_lead_c,changeDiff($in_lead,$in_lead_c,$in_lead_c),changePer($in_lead,$in_lead_c)),
		array($in_cpc,$in_cpc_c,changeDiff($in_cpc,$in_cpc_c,$in_cpc_c),changePer($in_cpc,$in_cpc_c)),
		array($in_lead_con,$in_lead_con_c,changeDiff($in_lead_con,$in_lead_con_c,$in_lead_con_c),changePer($in_lead_con,$in_lead_con_c)),
		array($in_cpc_con,$in_cpc_con_c,changeDiff($in_cpc_con,$in_cpc_con_c,$in_cpc_con_c),changePer($in_cpc_con,$in_cpc_con_c))
	);
$i++;
}
$allReports=array($fb_reports,$g_reports,$in_reports);								
echo json_encode($allReports);