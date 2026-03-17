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

$start_lm = strtotime("first day of last month"); //date('m/01/Y');
$end_lm = strtotime("last day of last month");
									
$start_cm = strtotime("first day of this month"); //date('m/01/Y');
$end_cm = strtotime("now");
									
$start_lw = strtotime('monday last week'); //date('m/01/Y');
$end_lw = strtotime("sunday last week");
									
$start_cw = strtotime('monday this week'); //date('m/01/Y');
$end_cw = strtotime("now"); //sunday last week
 
$stDates = array(strtotime('monday this week'), strtotime('monday last week'), strtotime("first day of this month"), strtotime("first day of last month"));
$enDates = array(strtotime("now"), strtotime("sunday last week"), strtotime("now"), strtotime("last day of last month"));

foreach($stDates as $k => $v) {
	$where = "l.created_time >= ".$stDates[$k]." AND l.created_time <= ".$enDates[$k]." AND";
	$sqlRev2 = mysqli_query($conn, "SELECT la.client_name, l.page_id, count(*) as tot FROM `leads` as l, leads_acc as la where $where l.page_id=la.pg_id AND   la.delete_status=0 group by page_id");
	
	while($row=mysqli_fetch_assoc($sqlRev2)) { 
		$getData[$k][$row['page_id']] = $row;	
	}
}


$sqlRev3 = mysqli_query($conn, "SELECT client_name, pg_id FROM `leads_acc`where delete_status=0");
	
while($row3=mysqli_fetch_assoc($sqlRev3)) { 
		$getData3[] = $row3;	
}
	
d($getData3);

exit;

$tbl ='	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>SNo</th>
			<th>Client Name</th>
			<th>This Week</th>
			<th>Last Week</th>
			<th>This Month</th>
			<th>Last Month</th>
		  </tr>		
';

$sno = 1; 

foreach($getData3 as $d) {
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
	
}
$tbl .='</table>';

$duration = '( '.date('d-m-Y',strtotime($start)).' to '. date('d-m-Y',strtotime($end)).' )';

//echo $tbl;
include 'email/mail-budget.php';

echo 'cron3.php => success';
	