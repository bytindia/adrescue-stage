<?php 
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

$conn2 = mysqli_connect('localhost', 'salesninja', 'SalesNinja#2000', 'salesninja');

function d1($d) {
	echo '<pre>';
	print_r($d);
	echo '</pre>';
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


//$path='pdf/';
//require($server_path.'pdf/fpdf.php');
include 'db.php';
//include 'class.pdf.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 

$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";
  
if($access_token!='') {  
	if (!$tok_req = file_get_contents_curl($tok_url)) { 
		  $pg = 'cron-budget';      
		  include 'email/mail-error.php';
		  exit;
	} 
}


function LeadGenTot($arr, $filt) {
	$r = 0;
	if(is_array($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

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


$reqLimit = 500;

function FB_DailyReport($url, $conn, $file, $server_path) 
{
	global $csv;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	//d($fb_response); exit;
	
	foreach ($fb_response->data as $key => $response) {						
			$csv.= $response->date_start.','.$response->date_stop.','.$response->campaign_name.','.$response->reach.','.$response->impressions.','.$response->spend."\n";
	} 
	if(isset($fb_response->paging->next)) {		
		FB_DailyReport($fb_response->paging->next, $conn, $server_path); 
	} else {
		$csv_handler = fopen ($server_path.'download/'.$file.'.csv','w');
		fwrite ($csv_handler,$csv);
		fclose ($csv_handler);
	}
}

$st_1 =  date('Y-m-d', strtotime("-1 days")); 
$st_2 =  date('Y-m-d', strtotime("-2 days")); 

$st_7 =  date('Y-m-d', strtotime("-8 days"));
$en_7 =  date('Y-m-d', strtotime("-1 days"));

$st_14 =  date('Y-m-d', strtotime("-15 days"));
$en_14 =  date('Y-m-d', strtotime("-8 days"));  

$start = date('m/d/Y', strtotime("first day of this month")); //date('m/01/Y');
$end = date('m/d/Y'); 

$duration = date('F-Y', strtotime($start));

$lastDay = date('m/d/Y', strtotime("last day of this month"));

$startTimeStamp = strtotime("last day of this month");
$endTimeStamp = strtotime($end);
$timeDiff = abs($endTimeStamp - $startTimeStamp);
$numberDays = $timeDiff/86400;  // 86400 seconds in one day
// and you might want to convert to integer
$numberDays = intval($numberDays);


$getData = $fbStats = $gStats = array();
$fbStats = $fb_name = array();
$gStats = $g_name = array();

if(!isset($_GET['id'])) { $_GET['id']=1; }

$sqlRev2 = mysqli_query($conn, "SELECT fb_id, client_name, fb_acc, fb_bud, fb_every_month, g_acc, g_bud, g_every_month, both_budget, email_ids, email_notif, created, updated FROM budget WHERE uid='2' AND delete_status='0'");

while($row=mysqli_fetch_assoc($sqlRev2)) { 
	$getData[] = $row;
	$fileN = preg_replace('/[^a-zA-Z0-9-_\.]/','-', 'Eden Park');
	if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc']; $fb_name[$row['fb_acc']]=str_replace(" ","_", $fileN.'_Facebook_'.$duration); }
	if($row['g_acc']!='') { $gStats[] = $row['g_acc']; $g_name[$row['g_acc']]=str_replace(" ","_", $fileN.'_Google_'.$duration); }
}
$getData = array (
	array('client_name'=>'Eden Park (BYT)', 'fb_acc'=>915436565524562, 'g_acc'=>5724798552),
	array('client_name'=>'Siruseri OMR Apartments', 'fb_acc'=>2773856296201294, 'g_acc'=>'')
);
$fileN = preg_replace('/[^a-zA-Z0-9-_\.]/','-', 'Eden Park');

$fbStats = array(915436565524562, 2773856296201294); 
$gStats = array(5724798552);

//$g_name[7527988464]=str_replace(" ","_", $fileN.'_Google_'.$duration);
$g_name[5724798552]=str_replace(" ","_", $fileN.'_Google_2_'.$duration);

//d($getData); exit;
foreach($fbStats as $fd) {
	
	$fbData[$fd] = get_data('https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($start)).'&time_range[until]='.date("Y-m-d", strtotime($end)).'');
	
	//LG - Leads
	$fb_lg[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_1))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION']}]&time_increment=1");
	
	$fb_con[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_1))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'campaign.objective','operator':'IN','value':['CONVERSIONS']}]&time_increment=1");
	
	$fb_wa[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_1))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'campaign.objective','operator':'IN','value':['LINK_CLICKS']}]&time_increment=1");
	
	/*
	$f1_lg[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_1))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION']}]");
	
	$f2_lg[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_2))."&time_range[until]=".date('Y-m-d', strtotime($st_2))."&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION']}]");
	
	$f7_lg[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_7))."&time_range[until]=".date('Y-m-d', strtotime($en_7))."&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION']}]");
	
	$f14_lg[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_14))."&time_range[until]=".date('Y-m-d', strtotime($st_14))."&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION']}]");
	//Conv. - Leads
	$f1_conv[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_1))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'objective','operator':'IN','value':['CONVERSIONS']}]");
	
	$f2_conv[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_2))."&time_range[until]=".date('Y-m-d', strtotime($st_2))."&filtering=[{'field':'objective','operator':'IN','value':['CONVERSIONS']}]");
	
	$f7_conv[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_7))."&time_range[until]=".date('Y-m-d', strtotime($en_7))."&filtering=[{'field':'objective','operator':'IN','value':['CONVERSIONS']}]");
	
	$f14_conv[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_14))."&time_range[until]=".date('Y-m-d', strtotime($st_14))."&filtering=[{'field':'objective','operator':'IN','value':['CONVERSIONS']}]");
	*/
	
	
	//&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION','POST_ENGAGEMENT','VIDEO_VIEWS','CONVERSIONS','MESSAGES','LINK_CLICKS']}]
	
	//$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=campaign&fields=campaign_name,spend,impressions,reach,cpc&time_increment=1&limit='.$reqLimit.'&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($start)).'&time_range[until]='.date("Y-m-d", strtotime($end)).'';
	
	$csv = "Reporting Starts, Reporting Ends, Campaign Name, Reach, Impressions, Amount Spent (INR) \n";
	//FB_DailyReport($request_url, $conn, $fb_name[$fd], $server_path);
}


if(count($gStats)>0) {
	$accIds = $gStats;
	$accNames = $g_name;
	
	$fromDt = date('Ymd', strtotime($st_1));
	$enDt = date('Ymd', strtotime($st_1));
	include 'download-report.php';
}

$DT_range = '( '.date('d-m-Y',strtotime($start)).' to '. date('d-m-Y',strtotime($end)).' )';

$tbl ='	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th rowspan="2">SNo</th>
			<th rowspan="2">Ad Account / Client</th>
			<th colspan="3">Facebook</th>
			<th rowspan="2">Google</th>
			<th rowspan="2">Chat</th>
		  </tr>	
		  <tr>
		  	<th>Lead Generation</th>
			<th>Conversions</th>
			<th>Whatsapp</th>
		  </tr>
';

$sno = 1; 
function filterBy($arr, $from, $to, $filt) {
	$spend = $lead = 0;
	foreach($arr as $k => $v) {
		if($v['date_start'] >= $from && $v['date_stop'] <= $to) {
			$spend +=  $v['spend'];
			$lead +=  LeadGenTot($v['actions'], $filt);
		}
	}
	$cpr = @(round($spend / $lead));
	if(is_nan($cpr)) { $cpr='-'; } else { $cpr='&#x20B9; '.$cpr; }
	return array($cpr, $lead); 
}



foreach($getData as $d) {
	
	$duration = date('F-Y',strtotime($start));
	
	$fileN = preg_replace('/[^a-zA-Z0-9-_\.]/','-', $d['client_name']);
	$fname = str_replace(" ","_", $fileN.'_'.$duration);
	//print_r( $fbData[$d['fb_acc']]); exit;
	$fb_spend = 0;
	$lg_s1 = $lg_s2 = $lg_s7 = $lg_s14 = $lg_l1 = $lg_l2 = $lg_l7 = $lg_l14 = 0;
	$wa_s1 = $wa_l1 = $con_s1 = $con_s2 = $con_s7 = $con_s14 = $con_l1 = $con_l2 = $con_l7 = $con_l14 = 0;
	
	if($d['fb_acc']!='' && (isset($fbData[$d['fb_acc']]) && $fbData[$d['fb_acc']]!='' && $fbData[$d['fb_acc']]!=0)) 
	{ 
		$fb_spend= $fbData[$d['fb_acc']]['data'][0]['spend']; 
		$fbLg_1 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_1, $st_1, 'leadgen_grouped'); $lg_s1 = $fbLg_1[0]; $lg_l1 =$fbLg_1[1];
		/*$fbLg_2 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_2, $st_2, 'leadgen_grouped'); $lg_s2 = $fbLg_2[0]; $lg_l2 =$fbLg_2[1];
		$fbLg_7 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_7, $en_7, 'leadgen_grouped'); $lg_s7 = $fbLg_7[0]; $lg_l7 =$fbLg_7[1];
		$fbLg_14 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_14, $en_14, 'leadgen_grouped'); $lg_s14 = $fbLg_14[0]; $lg_l14 =$fbLg_14[1];*/
		
		$fbcon_1 = filterBy($fb_con[$d['fb_acc']]['data'], $st_1, $st_1, 'offsite_conversion.fb_pixel_lead'); $con_s1 = $fbcon_1[0]; $con_l1 =$fbcon_1[1];
		/*$fbcon_2 = filterBy($fb_con[$d['fb_acc']]['data'], $st_2, $st_2, 'offsite_conversion.fb_pixel_lead'); $con_s2 = $fbcon_2[0]; $con_l2 =$fbcon_2[1];
		$fbcon_7 = filterBy($fb_con[$d['fb_acc']]['data'], $st_7, $en_7, 'offsite_conversion.fb_pixel_lead'); $con_s7 = $fbcon_7[0]; $con_l7 =$fbcon_7[1];
		$fbcon_14 = filterBy($fb_con[$d['fb_acc']]['data'], $st_14, $en_14, 'offsite_conversion.fb_pixel_lead'); $con_s14 = $fbcon_14[0]; $con_l14 =$fbcon_14[1];*/
		
		$fbwa_1 = filterBy($fb_wa[$d['fb_acc']]['data'], $st_1, $st_1, 'link_click'); $wa_s1 = $fbwa_1[0]; $wa_l1 =$fbwa_1[1];
		
	} 
	
	if($d['g_acc']!='') {
		//$rows = file('download/'.$accNames[$d['g_acc']].'.csv');
		$rows = file(''.$server_path.'download/account_'.$d['g_acc'].'.csv');
		$last_row = array_pop($rows);
		$data = str_getcsv($last_row);
		$g_conv = round($data[10]);										
	} else {
		$g_conv=0; 
	}
	
	//Chat Leads
	if($sno==1) {
		mysqli_select_db($conn2,'salesninja');
		$chkRes = mysqli_query($conn2, "select * from chat_leads WHERE project='Eden Park - Siruseri' AND created >= CURDATE() - INTERVAL 1 DAY AND created < CURDATE()");						
		$chatTot = mysqli_num_rows($chkRes);
	} else {
		$chatTot = '-';
	}
	// exit;
	
	$tbl .='
		<tr>
			<td>'.$sno.'</td>
			<td>'.$d['client_name'].'</td>
			
			<td>'.$lg_l1.'</td>
			
			<td>'.$con_l1.'</td>
			<td>'.$wa_l1.'</td>
			<td>'.$g_conv.'</td>
			<td>'.$chatTot.'</td>
		</tr>
	';
	$sno++;
	
	
}
$tbl .='</table>';
echo $tbl; 
echo '<br>Report Date - From: '.date('d-m-Y',strtotime($st_1)).' To '.date('d-m-Y',strtotime($st_1)).' ';
$duration = '( '.date('d-m-Y',strtotime($start)).' to '. date('d-m-Y',strtotime($end)).' )';
exit;


//echo $tbl;
$to_address = "prabhu@bytindia.com";
include 'email/mail-budget.php';


echo 'cron3.php => success';
	