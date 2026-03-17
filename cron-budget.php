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
function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        _close($c);

        if ($contents) return $contents;
        else return FALSE;
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
	if (!$tok_req = curl_get_file_contents($tok_url)) { 
		  $pg = 'cron-budget';      
		  include 'email/mail-error.php';
		  exit;
	} 
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
	$requests = curl_get_file_contents($url);
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

$tbl ='	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>SNo</th>
			<th>Client Name</th>
			<th>FB Spent</th>
			<th>Google Spent</th>
			<th>Total Spent</th>
			<th>Total Budget</th>
			<th>Total Balance</th>
			<th>Balance in %</th>
			<th>Days Rem.</th>
		  </tr>		
';

$sno = 1; 
foreach($getData as $d) {
	
	$duration = date('F-Y',strtotime($start));
	
	$fileN = preg_replace('/[^a-zA-Z0-9-_\.]/','-', $d['client_name']);
	$fname = str_replace(" ","_", $fileN.'_'.$duration);
	//print_r( $fbData[$d['fb_acc']]); exit;
	if($d['fb_acc']!='' && (isset($fbData[$d['fb_acc']]) && $fbData[$d['fb_acc']]!='' && $fbData[$d['fb_acc']]!=0)) 
	{ 
		$fb_spend= $fbData[$d['fb_acc']]['data'][0]['spend'];  
	} else { 
		$fb_spend=0; 
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

$duration = '( '.date('d-m-Y',strtotime($start)).' to '. date('d-m-Y',strtotime($end)).' )';

//echo $tbl;
$to_address = "prabhu@bytindia.com, ads@bytindia.com, faheem@bytindia.com, accounts@bytindia.com, ramesh@bytindia.com";
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
	