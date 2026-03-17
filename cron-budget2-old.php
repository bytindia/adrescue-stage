<?php 
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';
//echo date('m/d/Y', strtotime("first day of last month"));
//echo date('m/d/Y', strtotime("last day of last month"));
//echo date('Y-m-d', strtotime(date('Y-m')." -1 month")); 
//echo date('M Y'); exit;


//$f_pointer=fopen("download/AltisVille_Google_December-2018.csv","r"); // file pointer

/*
function gCurrency($g_csv_path) {
	$csv = array();
	if(($handle = fopen("download/".$g_csv_path.".csv", "r")) !== FALSE)
	{
		while(($data = fgetcsv($handle, 1000, ",")) !== FALSE)
		{
			if(isset($data[5]) && is_numeric($data[5])) {
				$data[5] = round($data[5]/1000000);
			} 
			$csv[] = $data;
		}
	}
	
	fclose($handle);
	$fp = fopen("download/".$g_csv_path."~.csv", 'w');
	foreach ($csv as $fields) {
		fputcsv($fp, $fields);
	}
	fclose($fp);
}

*/
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
	$fileN = preg_replace('/[^a-zA-Z0-9-_\.]/','-', $row['client_name']);
	if($row['fb_acc']!='') { $fbStats[] = $row['fb_acc']; $fb_name[$row['fb_acc']]=str_replace(" ","_", $fileN.'_Facebook_'.$duration); }
	if($row['g_acc']!='') { $gStats[] = $row['g_acc']; $g_name[$row['g_acc']]=str_replace(" ","_", $fileN.'_Google_'.$duration); }
}

//d($getData); exit;
foreach($fbStats as $fd) {
	
	$fbData[$fd] = get_data('https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($start)).'&time_range[until]='.date("Y-m-d", strtotime($end)).'');
	
	//LG - Leads
	$fb_lg[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_14))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'objective','operator':'IN','value':['LEAD_GENERATION']}]&time_increment=1");
	
	$fb_con[$fd] = get_data("https://graph.facebook.com/".$api_ver."/act_".$fd."/insights?level=account&fields=spend,actions&access_token=".$access_token."&time_range[since]=".date('Y-m-d', strtotime($st_14))."&time_range[until]=".date('Y-m-d', strtotime($st_1))."&filtering=[{'field':'objective','operator':'IN','value':['CONVERSIONS']}]&time_increment=1");
	
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
	
	$fromDt = date('Ymd', strtotime($start));
	$enDt = date('Ymd', strtotime($end));
	include 'download-report.php';
}

$DT_range = '( '.date('d-m-Y',strtotime($start)).' to '. date('d-m-Y',strtotime($end)).' )';

$tbl ='	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th rowspan="3">SNo</th>
			<th rowspan="3">Client Name</th>
			<th colspan="8">FB Leads / CPL</th>
			<th colspan="7" rowspan="2">Spent / Budget '.$DT_range.'</th>
		  </tr>	
		  <tr>
		  	<th colspan="4">Lead Generation</th>
			<th colspan="4">Conversions</th>
		  </tr>
		  <tr>
		  	<th>Last Day / Prev day Leads</th>
			<th>Last Day / Prev day CPL</th>
			<th>Last 7d/ Prev 7d Leads</th>
			<th>Last 7d/ Prev 7d CPL</th>
			<th>Last Day / Prev day Leads</th>
			<th>Last Day / Prev day CPL</th>
			<th>Last 7d/ Prev 7d Leads</th>
			<th>Last 7d/ Prev 7d CPL</th>
			
			<th>FB Spent</th>
			<th>G Spent</th>
			<th>Tot. Spent</th>
			<th>Tot. Budget</th>
			<th>Tot. Balance</th>
			<th>Balance in %</th>
			<th>Days Rem.</th>
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
	if(is_nan($cpr)) { $cpr='-'; }
	return array($cpr, $lead); 
}



foreach($getData as $d) {
	
	$duration = date('F-Y',strtotime($start));
	
	$fileN = preg_replace('/[^a-zA-Z0-9-_\.]/','-', $d['client_name']);
	$fname = str_replace(" ","_", $fileN.'_'.$duration);
	//print_r( $fbData[$d['fb_acc']]); exit;
	$fb_spend = 0;
	$lg_s1 = $lg_s2 = $lg_s7 = $lg_s14 = $lg_l1 = $lg_l2 = $lg_l7 = $lg_l14 = 0;
	$con_s1 = $con_s2 = $con_s7 = $con_s14 = $con_l1 = $con_l2 = $con_l7 = $con_l14 = 0;
	
	if($d['fb_acc']!='' && (isset($fbData[$d['fb_acc']]) && $fbData[$d['fb_acc']]!='' && $fbData[$d['fb_acc']]!=0)) 
	{ 
		$fb_spend= $fbData[$d['fb_acc']]['data'][0]['spend']; 
		$fbLg_1 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_1, $st_1, 'leadgen_grouped'); $lg_s1 = $fbLg_1[0]; $lg_l1 =$fbLg_1[1];
		$fbLg_2 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_2, $st_2, 'leadgen_grouped'); $lg_s2 = $fbLg_2[0]; $lg_l2 =$fbLg_2[1];
		$fbLg_7 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_7, $en_7, 'leadgen_grouped'); $lg_s7 = $fbLg_7[0]; $lg_l7 =$fbLg_7[1];
		$fbLg_14 = filterBy($fb_lg[$d['fb_acc']]['data'], $st_14, $en_14, 'leadgen_grouped'); $lg_s14 = $fbLg_14[0]; $lg_l14 =$fbLg_14[1];
		
		$fbcon_1 = filterBy($fb_con[$d['fb_acc']]['data'], $st_1, $st_1, 'offsite_conversion.fb_pixel_lead'); $con_s1 = $fbcon_1[0]; $con_l1 =$fbcon_1[1];
		$fbcon_2 = filterBy($fb_con[$d['fb_acc']]['data'], $st_2, $st_2, 'offsite_conversion.fb_pixel_lead'); $con_s2 = $fbcon_2[0]; $con_l2 =$fbcon_2[1];
		$fbcon_7 = filterBy($fb_con[$d['fb_acc']]['data'], $st_7, $en_7, 'offsite_conversion.fb_pixel_lead'); $con_s7 = $fbcon_7[0]; $con_l7 =$fbcon_7[1];
		$fbcon_14 = filterBy($fb_con[$d['fb_acc']]['data'], $st_14, $en_14, 'offsite_conversion.fb_pixel_lead'); $con_s14 = $fbcon_14[0]; $con_l14 =$fbcon_14[1];
		
	} 
	
	if($d['g_acc']!='') {
		//$rows = file('download/'.$accNames[$d['g_acc']].'.csv');
		$rows = file(''.$server_path.'download/account_'.$d['g_acc'].'.csv');
		$last_row = array_pop($rows);
		$data = str_getcsv($last_row);
		$g_spend = round($data[5]/1000000);										
	} else {
		$g_spend=0; 
	}
	
	$tot_spend = (int)$fb_spend + (int)$g_spend;
	$tot_bud = (int)$d['fb_bud'] + (int)$d['g_bud'];
	$tot_rem = $tot_bud - $tot_spend;
	
	$rem_percent = ($tot_rem / $tot_bud) * 100 ;
	if($rem_percent>20 || $rem_percent<0) { $bgcolor='#FA5858'; } else { $bgcolor='#89F99F'; }
	$tbl .='
		<tr>
			<td>'.$sno.'</td>
			<td>'.$d['client_name'].'</td>
			
			<td>'.$lg_l1.' / '.$lg_l2.'</td>
			<td>'.$lg_s1.' / '.$lg_s2.'</td>
			<td>'.$lg_l7.' / '.$lg_l14.'</td>
			<td>'.$lg_s7.' / '.$lg_s14.'</td>
			
			<td>'.$con_l1.' / '.$con_l2.'</td>
			<td>'.$con_s1.' / '.$con_s2.'</td>
			<td>'.$con_l7.' / '.$con_l14.'</td>
			<td>'.$con_s7.' / '.$con_s14.'</td>
			
			<td>'.moneyFormatIndia(round($fb_spend)).'</td>
			<td>'.moneyFormatIndia(round($g_spend)).'</td>
			<td>'.moneyFormatIndia(round($tot_spend)).'</td>
			<td>'.moneyFormatIndia(round($tot_bud)).'</td>
			<td>'.moneyFormatIndia(round($tot_rem)).'</td>
			<td style="background-color:'.$bgcolor.'">'.round($rem_percent).'%</td>
			<td>'.$numberDays.'</td>
		</tr>
	';
	$sno++;
	
	/*
	//echo $d['byt_fee']; exit;
	$invN = $invN +1;
	createPDF($d['client_name'], $d['addr1'], $d['addr2'], $d['byt_fee'], $d['byt_fee_c'], $d['byt_fee_i'], $d['gst'], $d['igst_gst'], $d['seo'], $d['seo_c'], $d['seo_i'], $d['seo_m'], $d['add_project'], $d['add_project_c'], $d['add_project_i'], $d['add_project_m'], $d['gif_ban'], $d['gif_ban_c'], $d['gif_ban_i'], $d['gif_ban_m'], $d['linkedin'], $d['linkedin_c'], $d['linkedin_i'], $d['linkedin_m'], $d['web_maint'], $d['web_maint_c'], $d['web_maint_i'], $d['web_maint_m'], $d['shopify'], $d['shopify_c'], $d['shopify_i'], $d['shopify_m'], $d['tax_note'], $d['g_acc'], $d['fb_acc'], $duration, $d['fb_fee'], $d['fb_fee_i'], $d['g_fee'], $d['g_fee_i'], $d['ads_mgnt'], $d['ads_mgnt_c'], $d['ads_mgnt_i'],  $d['ads_spend'], $d['ads_spend_c'], $d['ads_spend_i'], $d['cgst'], $d['sgst'], $igst=$d['igst'], $inv_date=date('d-m-Y'), $inv_no=$invN, $fb_spend, $g_spend, $fname, $invTy='');
	
	if($d['byt_fee_i']=='on' || $d['seo_i']=='on' || $d['add_project_i']=='on' || $d['gif_ban_i']=='on' || $d['linkedin_i']=='on' || $d['web_maint_i']=='on' || $d['shopify_i']=='on' || $d['ads_mgnt_i']=='on' || $d['ads_spend_i']=='on')
	{
		$invN = $invN +1;
		createPDF($d['client_name'], $d['addr1'], $d['addr2'], $d['byt_fee'], $d['byt_fee_c'], $d['byt_fee_i'], $d['gst'], $d['igst_gst'], $d['seo'], $d['seo_c'], $d['seo_i'], $d['seo_m'], $d['add_project'], $d['add_project_c'], $d['add_project_i'], $d['add_project_m'], $d['gif_ban'], $d['gif_ban_c'], $d['gif_ban_i'], $d['gif_ban_m'], $d['linkedin'], $d['linkedin_c'], $d['linkedin_i'], $d['linkedin_m'], $d['web_maint'], $d['web_maint_c'], $d['web_maint_i'], $d['web_maint_m'], $d['shopify'], $d['shopify_c'], $d['shopify_i'], $d['shopify_m'], $d['tax_note2'], $d['g_acc'], $d['fb_acc'], $duration, $d['fb_fee'], $d['fb_fee_i'], $d['g_fee'], $d['g_fee_i'], $d['ads_mgnt'], $d['ads_mgnt_c'], $d['ads_mgnt_i'],  $d['ads_spend'], $d['ads_spend_c'], $d['ads_spend_i'], $d['cgst'], $d['sgst'], $igst=$d['igst'], $inv_date=date('d-m-Y'), $inv_no=$invN, $fb_spend, $g_spend, $fname.'_2', $invTy='on');
	}
	
	if($d['email_ids']!='') { $to_address = ','.$d['email_ids']; } else { $to_address=''; }
	include 'email/mail.php';	
	//createPDF($client_name, $addr1, $addr2, $byt_fee, $gst, $igst_gst, $seo, $seo_m , $add_project, $add_project_m, $gif_ban,  $gif_ban_m, $linkedin, $linkedin_m , $web_maint, $web_maint_m, $shopify, $shopify_m, $tax_note, $g_acc, $fb_acc, $duration, $fb_fee, $g_fee, $cgst, $sgst, $igst, $inv_date, $inv_no, $fb_spend=0, $g_spend=0, $fileName)
	*/
	
}
$tbl .='</table>';
echo $tbl; 
$duration = '( '.date('d-m-Y',strtotime($start)).' to '. date('d-m-Y',strtotime($end)).' )';
//exit;
//echo $tbl;
$to_address = "prabhu@bytindia.com";
include 'email/mail-budget.php';
 //mysqli_query($conn, "UPDATE inv_no SET inv_no='".$invN."' where tbl_id=1");
//include 'class.pdf.php';
//createPDF($client_name=1, $addr1=1, $addr2=1, $byt_fee=1, $gst=1, $igst_gst=1, $seo=1, $seo_m =1, $add_project=1, $add_project_m=1, $gif_ban=1,  $gif_ban_m=1, $linkedin=1, $linkedin_m =1, $web_maint=1, $web_maint_m=1, $shopify=1, $shopify_m=1, $tax_note=1, $g_acc=1, $fb_acc=1, $duration=1, $byt_fee=1, $fb_fee=1, $g_fee=1, $cgst=1, $sgst=1, $igst=1, $inv_date=1, $inv_no=1, $fb_spend=0, $g_spend=0, $fileName=1);
//d($fbStats);
//exit;
/*
$_SESSION['suc'] = 'Successfully Sent!';	
echo "<script>window.location = 'invoice.php';</script>";
exit();*/

echo 'cron3.php => success';
	