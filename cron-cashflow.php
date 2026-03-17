<?php session_start(); //exit; 
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include 'db.php';
include 'functions-report.php'; 

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
 }


$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 
$uId = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['g_id'] = $row['g_id'];
$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
$_SESSION['g_token'] = $row['g_token'];
$_SESSION['g_mcc'] = $row['g_mcc'];

$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];

$client_id = '819hf4iznbxt3r';
$client_secret = getenv('LINKEDIN_CLIENT_SECRET');
$userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='2'");	
$getRw2 = mysqli_fetch_assoc($userRes2);
$_SESSION['in_id'] = $getRw2['in_id'];
$_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
					
require_once $server_path .'vendor-linkedin/autoload.php';
$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";				
$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
  $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
}

$taboola_tok = '';
include 'taboola-config.php';
include 'google-ads.php';

$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

if($access_token!='') {  
	if (!$tok_req = curl_get_file_contents($tok_url)) { 
		  $pg = 'cron-fb-report';      
		  include 'email/mail-error.php';
		  exit;
	} 
}

//exit;
$gAccIds = $gStats =array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 
	
$sqlRev=mysqli_query($conn, "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM cashflow WHERE $extQ uid='".$uId."' AND delete_status=0");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;								
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	$gAccIds[$sqlROW['tbl_id']] = $sqlROW['g_id'];
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = array();
	
	//Facebook
	if($sqlROW['fb_id']!='') 
	{
		$fbIds = explode(',',$sqlROW['fb_id']);	$fb_stDt = explode(',',$sqlROW['fb_stDt']);	$fbReceived = explode(',',$sqlROW['fb_received']);	
		
		foreach($fbIds as $key => $fbId) {						
			//echo $i . "<br />";
			if(isset($fb_stDt[$key]) && $fb_stDt[$key]!='') { $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($fb_stDt[$key])).'&time_range[until]='.date("Y-m-d", strtotime($today)).''; } else { $dtRange = 'date_preset=maximum'; }
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend&access_token='.$access_token.'&'.$dtRange.'';
			$requests = curl_get_file_contents($request_url);
			$fb_response = json_decode($requests,true);
			$fbSpent[] = round($fb_response['data'][0]['spend'] + $fbReceived[$key]); 
		}		
		mysqli_query($conn, "UPDATE cashflow SET fb_spent='".implode(',',$fbSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	}
	
	//Google
	if($sqlROW['g_id']!='') {
		$g_stDt = explode(',',$sqlROW['fb_stDt']);
		$gaIds = explode(',',$sqlROW['g_id']); //$g_stDt = explode(',',$SatrtDate);		
		foreach($gaIds as $key => $gaId) {
			$gStats[] = $gaId; 
			$gDates[$gaId] = $SatrtDate; 
			$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId,date("Y-m-d", strtotime($g_stDt[$key])), date("Y-m-d", strtotime($today)));
			if(isset($getAccRep['cost'])) {
				$gSpent[] = round($getAccRep['cost']); 
			}
		}
		//echo "UPDATE budget_reminder SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id'].""; exit;
		mysqli_query($conn, "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
	}
	
	
	//LinkedIn
	if($sqlROW['in_id']!='') { 
		$inIds = explode(',',$sqlROW['in_id']); $in_stDt = explode(',',$sqlROW['in_stDt']);	
		
		foreach($inIds as $key => $inId) {								
			$accQry ='accounts[0]=urn:li:sponsoredAccount:'.$inId.'&';
			if(isset($in_stDt[$key]) && $in_stDt[$key]=='') { $in_stDt[$key] = date("Y/m/d",strtotime("-10 year")); }
			$stDt = "dateRange.start.day=".date('d',strtotime($in_stDt[$key]))."&dateRange.start.month=".date('m',strtotime($in_stDt[$key]))."&dateRange.start.year=".date('Y',strtotime($in_stDt[$key]))."&";
			$enDt = "dateRange.end.day=".date('d',strtotime($today))."&dateRange.end.month=".date('m',strtotime($today))."&dateRange.end.year=".date('Y',strtotime($today));
				
			$val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=costInLocalCurrency&'.$stDt.''.$enDt);
			$inSpent[] = round($val['elements'][0]['costInLocalCurrency']);
		}
		mysqli_query($conn, "UPDATE cashflow SET in_spent='".implode(',',$inSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());		
	}
	//exit;
	//Taboola
	if($sqlROW['ta_id']!='' && $taboola_tok!='') {
		$taIds = explode(',',$sqlROW['ta_id']);	$ta_stDt = explode(',',$sqlROW['ta_stDt']);	
			
		foreach($taIds as $key => $taId) {	
			if($ta_stDt[$key]=='') { $ta_stDt[$key]='2019/01/01'; } 			
			$ta_url = 'https://backstage.taboola.com/backstage/api/1.0/'.$taId.'/reports/campaign-summary/dimensions/month?access_token='.$taboola_tok.'&start_date='.date('Y-m-d',strtotime($ta_stDt[$key])).'&end_date='.date('Y-m-d',strtotime($today)).'';
			$ta_req = curl_get_file_contents($ta_url);
			$ta_res = json_decode($ta_req,true);
			if(isset($ta_res['results'])) {
				$ta_spent = array_sum(array_column($ta_res['results'],'spent'));
				$taSpent[] = round($ta_spent);
			}
		}
		mysqli_query($conn, "UPDATE cashflow SET ta_spent='".implode(',',$taSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
		//d($ta_res); 
			//https://backstage.taboola.com/backstage/api/1.0/bytindia-altis-inr-sc/reports/campaign-summary/dimensions/month?access_token=CWQPAAAAAAAAEVdhAgAAAAAAGAEgACl4w05KbgREDACTED_FB_TOKEN::b66473::51ec1f&start_date=2019-01-29&end_date=2019-11-07
	}
	
}
/*
//d($gAccIds); exit;
if(is_array( $gStats ) && count($gStats)>0) {
			$accIds = $gStats;
			$accNames = 1;
			//$fromDt = date('Ymd', strtotime($start));
			//$enDt = date('Ymd', strtotime($end));
			include 'download-cashflow.php';	
			
			
			foreach($gAccIds as $k => $gR)			
			{
				
				if($gR!='') {
					$gSpent = array();
					$gaIds = explode(',',$gR); 
					
					foreach($gaIds as $key => $gaId) {
						$cost = 0;
						$rows[$gaId] = file(''.$server_path.'ads-cashflow/account_'.$gaId.'.csv');
						$last_row[$gaId] = array_pop($rows[$gaId]);
						$arr_goo[$gaId] = str_getcsv($last_row[$gaId]);							 
						if($arr_goo[$gaId][5]!=0) { $cost = $arr_goo[$gaId][5]/1000000; }			
						$gSpent[] = round($cost);		
					}
					//echo  "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'"; 
					mysqli_query($conn, "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'") or die(mysqli_error());		
				}
			}
}	
*/
//exit;
//EMAIL REPORT
//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow WHERE uid='2' AND delete_status=0 order by tbl_id asc");
//echo "SELECT c.*, cp.amount, cp.date FROM cashflow as c, cashflow_payments as cp WHERE c.tbl_id=cp.cashflow_id AND c.uid='2' AND c.delete_status=0 order by c.tbl_id asc"; 
$sqlRev=mysqli_query($conn, "SELECT c.*, cp.amount, cp.date FROM cashflow as c, cashflow_payments as cp WHERE c.tbl_id=cp.cashflow_id AND c.uid='2' AND c.delete_status=0 order by c.tbl_id asc");

						
$tbl = '	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>SNo</th>
			<th>Client Name</th>
			<th>Payment Received (A)</th>
            <th>Total Spent (B)</th> 
            <th>Balance (A-B)</th>
		  </tr>		
';

$sno = 1; 
setlocale(LC_MONETARY, 'en_IN');
$spreadsheet = array();
$totRows = mysqli_num_rows($sqlRev);
$grandRecived = $grandSpent = $grandBalance = 0;
while($sqlROW=mysqli_fetch_array($sqlRev))
{ 
	//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
	//$tot_bal = $sqlROW["tot_paid"] - $tot_spend;
	
	$tot_spend = $fb_spent = $g_spent = $in_spent = $ta_spent = $last_paid = 0;
											
	if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
	if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
	if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
	if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }
	
	$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
	
	$tot_bal = $sqlROW["tot_paid"] - $tot_spend + $sqlROW["tot_penalty"];
	
	if( $tot_bal < 0 ) { $bgcolor='#f97878'; } else { $bgcolor='#99f3aa'; }
	
	if($sqlROW["date"]!='' && !isset($_GET['refresh'])) 
	{
		$last_paid = unserialize($sqlROW['amount']);
		$last_paid_dt = unserialize($sqlROW['date']);
		$mostRecent= 0;
		foreach($last_paid_dt as $k => $date){
		  $curDate = strtotime($date);
		  if ($curDate > $mostRecent) {
			 $mostRecent = $curDate;
			 $key = $k;
		  }
		}
		//echo $last_paid[$k].' - '.$mostRecent;  exit;
		$last_paid_per = round(@($tot_bal / $last_paid[$k]) * 100);
		$last_paid = $last_paid[$k];
		//echo $sqlROW["client_name"].' '.$last_paid_per;

		$grandRecived = $grandRecived + $sqlROW["tot_paid"];
		$grandSpent = $grandSpent + $tot_spend;
		$grandBalance = $grandBalance + $tot_bal;

		if($tot_bal < 0) {
			$em_type = 1;
			$client = $sqlROW["client_name"];
			$ad_bal = money_format('%!i', $tot_bal);
			$to_address = "accounts@bytindia.com,faheem@bytindia.com,".$sqlROW["email"];
			include 'email/mail-cashflow-client.php';
		} 
		if($tot_bal > 0 && $last_paid_per<25 && $sqlROW["daily_budget"]!='') {
			$em_type = 2;
			$client = $sqlROW["client_name"];
			$days_left = round(@($tot_bal / $sqlROW["daily_budget"]));
			//$ad_bal = money_format('%!i', $tot_bal);
			$to_address = "accounts@bytindia.com,faheem@bytindia.com,".$sqlROW["email"];
			include 'email/mail-cashflow-client.php';
		}
	}
	
	$tbl .='
		<tr>
			<td>'.$sno.'</td>
			<td>'.$sqlROW["client_name"].'</td>
			<td style="text-align: right;">'.money_format('%!i', $sqlROW["tot_paid"]).'</td>
			<td style="text-align: right;">'.money_format('%!i', $tot_spend).'</td>
			<td style="background-color:'.$bgcolor.';text-align: right;"><b>'.money_format('%!i', $tot_bal).'</b></td>
		</tr>
	';
	if($sno==($totRows)){ 
		$tbl .='
			<tr>
				<td>-</td>
				<td><b>Total</b></td>
				<td style="text-align: right;"><b>'.money_format('%!i', $grandRecived).'</b></td>
				<td style="text-align: right;"><b>'.money_format('%!i', $grandSpent).'</b></td>
				<td style="background-color:'.$bgcolor.';text-align: right;"><b>'.money_format('%!i', $grandBalance).'</b></td>
			</tr>
		';
	}
	$spreadsheet[] = array($sno,$sqlROW["client_name"], $sqlROW["tot_paid"], $tot_spend, $tot_bal);
	$sno++;
}
$tbl .='</table>';

//echo $tbl;  exit;
//$post['sheet'] = $spreadsheet;

//GOOGLESHEET
function http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {

    if ( is_object( $arrays ) ) {
        $arrays = get_object_vars( $arrays );
    }

    foreach ( $arrays AS $key => $value ) {
        $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
        if ( is_array( $value ) OR is_object( $value )  ) {
            http_build_query_for_curl( $value, $new, $k );
        } else {
            $new[$k] = $value;
        }
    }
}

http_build_query_for_curl($spreadsheet, $post['sheet']);

		
$ch = curl_init('https://stage.adrescue.in/google-sheet/adsninja-cashflow.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post['sheet']);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
//curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
$response = curl_exec($ch);
curl_close($ch);

//exit; 

if(isset($_GET['refresh'])) {
	echo "<script>window.location = 'cashflow.php';</script>"; 
	exit();
} else {
	$to_address = "ads@bytindia.com, faheem@bytindia.com, accounts@bytindia.com, prabhu@bytindia.com, bytacnts@gmail.com";
	//$to_address = "prabhu@bytindia.com";
	//include 'email/mail-cashflow.php';
}

echo 'success';
exit;
//act_107704242648697/insights?level=account&fields=spend&time_range[since]=2018-02-01&&time_range[until]=2019-11-07