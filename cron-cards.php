<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
//error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'functions-report.php'; 

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';
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
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

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

//echo $start; exit;
$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 
if(isset($_GET['st'])) {
	$start = date('Y-m-d', strtotime(str_replace("/","-",$_GET['st'])));
	$today =  date('Y-m-d', strtotime(str_replace("/","-",$_GET['en'])));
}

//error_reporting(0);


$fbSpent = $gSpent = $inSpent = $taSpent = array();

$sqlRev=mysqli_query($conn, "SELECT tbl_id,card_name,fb_id,fb_stDt,fb_enDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM cards WHERE $extQ uid='".$uId."' AND delete_status=0");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;								
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	//echo $sqlROW['card_name'];
	$gAccIds[$sqlROW['tbl_id']] = $sqlROW['g_id'];
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = array();
	
	//Facebook
	if($sqlROW['fb_id']!='') 
	{
		$fbIds = explode(',',$sqlROW['fb_id']);	
		$fb_stDt = explode(',',$sqlROW['fb_stDt']);	
		$fbReceived = explode(',',$sqlROW['fb_received']);	
		$fb_enDt = explode(',',$sqlROW['fb_enDt']);
		
		foreach($fbIds as $key => $fbId) {						
			//echo $i . "<br />";
			$fbStDt='';
			if(isset($fb_enDt[$key]) && $fb_enDt[$key]!='') { $fbEnDt=$fb_enDt[$key]; } else { $fbEnDt=$today; }
			if(isset($fb_stDt[$key]) && $fb_stDt[$key]!='') { $fbStDt=$fb_stDt[$key]; } 
			if(isset($_GET['ajax'])) { $fbStDt= $start; $fbEnDt=$today; }

			if($fbStDt!='') { $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($fbStDt)).'&time_range[until]='.date("Y-m-d", strtotime($fbEnDt)).''; } else { $fbStDt='date_preset=maximum'; }
			
			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=spend&access_token='.$access_token.'&'.$dtRange.'';
			$requests = curl_get_file_contents($request_url); 
			$fb_response = json_decode($requests,true);
            //d($fb_response); exit;
			if(isset($fb_response['data'][0]['spend'])) {
				$fbSpent[] = round($fb_response['data'][0]['spend'] + $fbReceived[$key]); 
			}
		}		
		//echo "UPDATE cards SET fb_spent='".implode(',',$fbSpent)."' WHERE tbl_id=".$sqlROW['tbl_id'].""; 
		if(!isset($_GET['ajax'])) { 
			mysqli_query($conn, "UPDATE cards SET fb_spent='".implode(',',$fbSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); //exit;
		}
	}
	//exit;
	//Google
	if($sqlROW['g_id']!='') {
		$g_stDt = explode(',',$sqlROW['g_stDt']);
		$g_enDt = explode(',',$sqlROW['g_enDt']);
		$gaIds = explode(',',$sqlROW['g_id']); //$g_stDt = explode(',',$SatrtDate);		
		foreach($gaIds as $key => $gaId) {
			$gStats[] = $gaId; 
			//$gDates[$gaId] = $SatrtDate; 
			if(isset($g_enDt[$key]) && $g_enDt[$key]!='') { $gEnDt=$g_enDt[$key]; } else { $gEnDt = $today; }
			if(isset($g_stDt[$key]) && $g_stDt[$key]!='') { $gStDt=$g_stDt[$key]; }
			if(isset($_GET['ajax'])) { $gStDt= $start; $gEnDt=$today; }

			$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId,date("Y-m-d", strtotime($gStDt)), date("Y-m-d", strtotime($gEnDt)));
			//d($getAccRep ); exit;
			if(isset($getAccRep['cost'])) {
				$gSpent[] = round($getAccRep['cost']); 
			}
		}
		if(!isset($_GET['ajax'])) {
			mysqli_query($conn, "UPDATE cards SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
		}
	}
	
	
	//LinkedIn
	if($sqlROW['in_id']!='') { 
		$inIds = explode(',',$sqlROW['in_id']); 
		$in_stDt = explode(',',$sqlROW['in_stDt']);	
		$in_enDt = explode(',',$sqlROW['in_enDt']);	
		$inSpent = array();
		foreach($inIds as $key => $inId) {				
			if(isset($in_enDt[$key]) && $in_enDt[$key]!='') { $inEnDt=$in_enDt[$key]; } else { $inEnDt=$today; }				
			$accQry ='accounts[0]=urn:li:sponsoredAccount:'.$inId.'&';
			if(isset($in_stDt[$key]) && $in_stDt[$key]=='') { $inStDt = date("Y/m/d",strtotime("-10 year")); } else { $inStDt =$in_stDt[$key]; }
			if(isset($_GET['ajax'])) { $inStDt= $start; $inEnDt=$today; }

			$stDt = "dateRange.start.day=".date('d',strtotime($inStDt))."&dateRange.start.month=".date('m',strtotime($inStDt))."&dateRange.start.year=".date('Y',strtotime($inStDt))."&";
			$enDt = "dateRange.end.day=".date('d',strtotime($inEnDt))."&dateRange.end.month=".date('m',strtotime($inEnDt))."&dateRange.end.year=".date('Y',strtotime($inEnDt));
				
			$val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=costInLocalCurrency&'.$stDt.''.$enDt);
			//print_r($val); exit;
			if(isset($val['elements'][0]['costInLocalCurrency'])) {
				$inSpent[] = round($val['elements'][0]['costInLocalCurrency']);
			}
		}
		if(!isset($_GET['ajax'])) {
			mysqli_query($conn, "UPDATE cards SET in_spent='".implode(',',$inSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());		
		}
	}
	//exit;
	//Taboola
	if($sqlROW['ta_id']!='' && $taboola_tok!='') {
		$taIds = explode(',',$sqlROW['ta_id']);	
		$ta_stDt = explode(',',$sqlROW['ta_stDt']);
		$ta_enDt = explode(',',$sqlROW['ta_enDt']);	
			
		foreach($taIds as $key => $taId) {	
			if(isset($ta_enDt[$key]) && $ta_enDt[$key]!='') { $taEnDt=$ta_enDt[$key]; } else { $taEnDt=$today; }	
			if($ta_stDt[$key]=='') { $taStDt='2019/01/01'; } else { $taStDt= $ta_stDt[$key]; } 	
			if(isset($_GET['ajax'])) { $taStDt= $start; $taEnDt=$today; }

			$ta_url = 'https://backstage.taboola.com/backstage/api/1.0/'.$taId.'/reports/campaign-summary/dimensions/month?access_token='.$taboola_tok.'&start_date='.date('Y-m-d',strtotime($taStDt)).'&end_date='.date('Y-m-d',strtotime($taEnDt)).'';
			$ta_req = curl_get_file_contents($ta_url);
			$ta_res = json_decode($ta_req,true);
			if(isset($ta_res['results'])) {
				$ta_spent = array_sum(array_column($ta_res['results'],'spent'));
				$taSpent[] = round($ta_spent);
			}
		}
		if(!isset($_GET['ajax'])) {
			mysqli_query($conn, "UPDATE cards SET ta_spent='".implode(',',$taSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
		}


	}
	//print_r($errGoogle);
	if(!isset($_GET['ajax'])) {
		echo ' - Done<br>'; //exit;
	}
	
}
//d($fbSpent); d($gSpent); d($inSpent); d($taSpent);
if(!isset($_GET['ajax'])) {

$sqlRev=mysqli_query($conn, "SELECT c.*, cp.amount, cp.date FROM cards as c, cards_payments as cp WHERE c.tbl_id=cp.cashflow_id AND c.uid='2' AND c.delete_status=0 order by c.tbl_id desc");

						
$tbl = '	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
            <th>SNo</th>
            <th>Card</th>        	
            <th>Spent</th>
            <th>Paid</th> 
            <th>Current Balance</th>
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
		//echo $sqlROW["card_name"].' '.$last_paid_per;

		$grandRecived = $grandRecived + $sqlROW["tot_paid"];
		$grandSpent = $grandSpent + $tot_spend;
		$grandBalance = $grandBalance + $tot_bal;

		if($tot_bal < 0) {
			$em_type = 1;
			$client = $sqlROW["card_name"];
			$ad_bal = moneyFormatIndia($tot_bal);
			$to_address = "accounts@bytindia.com,faheem@bytindia.com,".$sqlROW["email"];
			//include 'email/mail-cashflow-client.php';
		} 
		if($tot_bal > 0 && $last_paid_per<25 && $sqlROW["daily_budget"]!='') {
			$em_type = 2;
			$client = $sqlROW["card_name"];
			$days_left = round(@($tot_bal / $sqlROW["daily_budget"]));
			//$ad_bal = moneyFormatIndia($tot_bal);
			$to_address = "accounts@bytindia.com,faheem@bytindia.com,".$sqlROW["email"];
			//include 'email/mail-cashflow-client.php';
		}
	}
	
	$tbl .='
		<tr>
			<td>'.$sno.'</td>
			<td>'.$sqlROW["card_name"].'</td>
			<td style="text-align: right;">'.money_format('%.0n',$sqlROW["tot_paid"]).'</td>
			<td style="text-align: right;">'.money_format('%.0n',$tot_spend).'</td>
			<td style="background-color:'.$bgcolor.';text-align: right;"><b>'.money_format('%.0n',$tot_bal).'</b></td>
		</tr>
	';
	if($sno==($totRows)){ 
		$tbl .='
			<tr>
				<td>-</td>
				<td><b>Total</b></td>
				<td style="text-align: right;"><b>'.money_format('%.0n',$grandRecived).'</b></td>
				<td style="text-align: right;"><b>'.money_format('%.0n',$grandSpent).'</b></td>
				<td style="background-color:'.$bgcolor.';text-align: right;"><b>'.money_format('%.0n',$grandBalance).'</b></td>
			</tr>
		';
	}
	//$spreadsheet[] = array($sno,$sqlROW["card_name"], $sqlROW["tot_paid"], $tot_spend, $tot_bal);
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

//http_build_query_for_curl($spreadsheet, $post['sheet']);

	/*	
$ch = curl_init('https://stage.adrescue.in/google-sheet/adsninja-cashflow.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post['sheet']);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
//curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
$response = curl_exec($ch);
curl_close($ch);
*/
//exit; 
if(isset($_GET['refresh'])) {
	echo 'success';
	echo "<script>window.location = 'cards.php';</script>"; 
	exit();
} else {
	$to_address = "ads@bytindia.com, faheem@bytindia.com, accounts@bytindia.com, prabhu@bytindia.com";
	//$to_address = "prabhu@bytindia.com";
	include 'email/mail-cards.php';
}

echo 'success';
exit;
//act_107704242648697/insights?level=account&fields=spend&time_range[since]=2018-02-01&&time_range[until]=2019-11-07
} else {

	echo '<table  class="table table-hover table-striped table-bordered">';
	echo '<thead><th>SM</td><th>Amount </th></thead>';
	
	$fbTot = array_sum(array_filter($fbSpent));
	$gTot = array_sum(array_filter($gSpent));
	$inTot = array_sum(array_filter($inSpent));
	$taTot = array_sum(array_filter($taSpent));

	$allTot = $fbTot + $gTot + $inTot + $taTot;

	if($fbTot>0) { echo '<tr><td>Facebook</td><th>'.moneyFormatIndia($fbTot).' </td></tr>'; }
	if($gTot>0) { echo '<tr><td>Google</td><th>'.moneyFormatIndia($gTot).' </td></tr>'; }
	if($inTot>0) { echo '<tr><td>LinkedIn</td><th>'.moneyFormatIndia($inTot).' </td></tr>'; }
	if($taTot>0) { echo '<tr><td>Taboola</td><th>'.moneyFormatIndia($taTot).' </td></tr>'; }
	if($allTot>0) { echo '<tr><td class="blue_txt"><b>Total</b></td><th>'.moneyFormatIndia($allTot).' </td></tr>'; }

	echo '</table>';


}