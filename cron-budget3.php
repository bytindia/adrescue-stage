<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include 'db.php';
include 'functions-report.php'; 
ini_set('memory_limit','2048M');

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

include 'gsquare-taboola.php';


//	exit;
	
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

 function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

 function sendWhatsapp($tok, $ph, $head_param, $body_param, $rep_on) {
	//echo $tok.'__'.$ph.'__'.$head_param.'__'.$body_param.'__'.$rep_on; exit;
    $phone = '91'.$ph; // exit;
    //$wa_msg = 'The *'.$acc_name.'* ad account has reached *'.$percentage.'%* of its total spend, which is *₹'.$spend.'* out of *₹'.$budget_V.'*';
    $attachment =  array(
       'messaging_product' => 'whatsapp',
       'to' => $phone,
       'type'=> 'template',
       'template' => 
         json_encode(
           array(
             'name' => 'bud_alert_internal', 
             'language' => array('code'=>'en'), 
             'components'=> 
               array(
                   array(
                       "type" => "header",
                       "parameters" => array(array("type"=> "text", "text"=>$head_param))
                   ),
                   array(
                       "type" => "body",
                       "parameters" => array(
                            array("type"=> "text","text"=> $rep_on),
                            array("type"=> "text","text"=> $body_param)
                       )
                   )
               )
         ))
       );
       
       //print_r($attachment); exit;

       $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
       $post = json_encode($attachment); // Encode the data array into a JSON string
       $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
       curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); //Inject the token into the header
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
       curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
       curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
       $result = curl_exec($ch); // Execute the cURL statement
       curl_close($ch); // Close the cURL connection
       return json_decode($result); // Return the received data
       print_r($result); // exit;
}
$media_by = array(1=>'Ramesh', 2=>'RajKumar', 3=>'Simin', 4=>'Bargavi', 5=>'Shaheena', 6=>'Samadh', 7=>'Vedika', 8=>'Radhika', 9=>'Dhanush', 10=>'Nida', 11=>'Maha', 12=>'Bala', 13=>'Pavithra', 14=>'Charan', 15=>'Mughil');
$media_by_ph = array(1=>'9840619930', 3=>'8667864319', 5=>'6383714329', 14=>'9360262876', 15=>'9080174712');

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
$d = new DateTime('first day of this month');
$d2 = new DateTime('today');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';
//exit;

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
if (date('j') === '1') {
	$conn->query("UPDATE budget_reminder SET budget_received = NULL");
}
$gAccIds = $gStats =array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 
$cc_card = array(1=>'BYT', 2=>'Client'); 
//echo "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0"; exit;
$sqlRev=mysqli_query($conn, "SELECT tbl_id,camp_name,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0 AND hide_temp='0'");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;			
$total_spent = 0; 	

while($sqlROW=mysqli_fetch_array($sqlRev))
{
	echo $sqlROW['tbl_id'].'<br>';
	$camp_contains = $sqlROW['camp_name'] ?? ''; // fallback if key not set
	$matches = [];
	$filter_keywords = [];

	if (!empty($camp_contains) && is_string($camp_contains)) {
		preg_match('/\[(.*?)\]/', $camp_contains, $matches);
		if (!empty($matches[1])) {
			$filter_keywords = array_map('trim', explode(',', $matches[1]));
		}
	}

	$filtering_param = $filtering_param_g = '';
	$filtering = [];
	$like_clauses = [];

	if (!empty($filter_keywords)) {
		foreach ($filter_keywords as $word) {
			$word = trim($word);
			$filtering[] = [
				"field" => "campaign.name",
				"operator" => "CONTAIN",
				"value" => $word
			];
	
			$like_clauses[] = "campaign.name LIKE '%" . $word . "%'";
		}
	
		$filtering_param = '&filtering=' . urlencode(json_encode($filtering));
	
		if (count($like_clauses) > 1) {
			$filtering_param_g = ' AND (' . implode(' OR ', $like_clauses) . ')';
		} elseif (count($like_clauses) === 1) {
			$filtering_param_g = ' AND ' . $like_clauses[0];
		}
	}
	
	
	//echo $sqlROW['tbl_id'].'<br>';
	$gAccIds[$sqlROW['tbl_id']] = $sqlROW['g_id'];
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = $acc_bal = $fbClicks = $fbImpr = $gClicks = $gImpr = $fblead = $fbcpl = $gLead = array();
	
	mysqli_query($conn, "UPDATE budget_reminder SET fb_spent='0', fb_balance='', g_spent='0', in_spent='0', ta_spent='0' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	
	//Facebook
	if($sqlROW['fb_id']!='') 
	{
		$fbIds = explode(',',$sqlROW['fb_id']);	$fb_stDt = explode(',',$sqlROW['fb_stDt']);	//$fbReceived = explode(',',$sqlROW['fb_received']);	
		
		foreach($fbIds as $key => $fbId) {						
			//echo $i . "<br />";
		 $dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).''; //exit;

			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=clicks,impressions,spend,actions,cost_per_action_type' . $filtering_param . '&access_token='.$access_token.'&'.$dtRange.''; //exit;
			$requests = curl_get_file_contents($request_url);
			$fb_response = json_decode($requests,true);
			if(isset($fb_response['data'][0]['spend'])){
				$fbSpent[] = round($fb_response['data'][0]['spend']); 
				$fbClicks[] = round($fb_response['data'][0]['clicks']); 
				$fbImpr[] = round($fb_response['data'][0]['impressions']); 
			} else {
				$fbSpent[] = $fbClicks[] = $fbImpr[] = 0;
			}
			if(isset($fb_response['data'][0]['actions'])){
                $fblead[] = LeadGen($fb_response['data'][0]['actions'], 'lead');
                $fbcpl[] = LeadGen($fb_response['data'][0]['cost_per_action_type'], 'lead');
            } else {
                $fblead[] = $fbcpl[] = 0;
            }
		}		
		//echo "UPDATE budget_reminder SET fb_spent='".implode(',',$fbSpent)."', fb_balance='".implode(',',$acc_bal)."' WHERE tbl_id=".$sqlROW['tbl_id'].""; exit;
		mysqli_query($conn, "UPDATE budget_reminder SET fb_spent='".implode(',',$fbSpent)."', fb_leads='".implode(',',$fblead)."', fb_cpl='".implode(',',$fbcpl)."', clicks_fb='".implode(',',$fbClicks)."', impr_fb='".implode(',',$fbImpr)."', fb_balance='".implode(',',$acc_bal)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	}
	//exit;
	//Google
	
	if($sqlROW['g_id']!='') {
		
		$gaIds = explode(',',$sqlROW['g_id']); //$g_stDt = explode(',',$SatrtDate);		
		foreach($gaIds as $key => $gaId) {
			$gStats[] = $gaId; 
			$gDates[$gaId] = $SatrtDate; 
			$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId,date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)));
			if(isset($getAccRep['cost'])) {
				$gSpent[] = round($getAccRep['cost']); 
				$gClicks[] = round($getAccRep['clicks']); 
				$gImpr[] = round($getAccRep['impressions']); 
				$gLead[] = round($getAccRep['conversions']) ?? 0;
			}
		}
		//echo "UPDATE budget_reminder SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id'].""; exit;
		mysqli_query($conn, "UPDATE budget_reminder SET g_spent='".implode(',',$gSpent)."', g_leads='".implode(',',$gLead)."', clicks_g='".implode(',',$gClicks)."', impr_g='".implode(',',$gImpr)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	}
	//exit;
	//LinkedIn
	if($sqlROW['in_id']!='') { 
		$inIds = explode(',',$sqlROW['in_id']); $in_stDt = explode(',',$sqlROW['in_stDt']);	
		
		foreach($inIds as $key => $inId) {								
			$accQry ='accounts[0]=urn:li:sponsoredAccount:'.$inId.'&';
			//if(isset($in_stDt[$key]) && $in_stDt[$key]=='') { $in_stDt[$key] = date("Y/m/d",strtotime("-10 year")); }
			$stDt = "dateRange.start.day=".date('d',strtotime($SatrtDate))."&dateRange.start.month=".date('m',strtotime($SatrtDate))."&dateRange.start.year=".date('Y',strtotime($SatrtDate))."&";
			$enDt = "dateRange.end.day=".date('d',strtotime($EndDate))."&dateRange.end.month=".date('m',strtotime($EndDate))."&dateRange.end.year=".date('Y',strtotime($EndDate));
				
			$val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=costInLocalCurrency&'.$stDt.''.$enDt);
			$inSpent[] = round($val['elements'][0]['costInLocalCurrency']);
		}
		mysqli_query($conn, "UPDATE budget_reminder SET in_spent='".implode(',',$inSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());		
	}
	//exit;
	//Taboola
	/*
	if($sqlROW['ta_id']!='' && $taboola_tok!='') {
		$taIds = explode(',',$sqlROW['ta_id']);	$ta_stDt = explode(',',$sqlROW['ta_stDt']);	
			
		foreach($taIds as $key => $taId) {	
			//if($ta_stDt[$key]=='') { $ta_stDt[$key]='2019/01/01'; } 			
			$ta_url = 'https://backstage.taboola.com/backstage/api/1.0/'.$taId.'/reports/campaign-summary/dimensions/month?access_token='.$taboola_tok.'&start_date='.date('Y-m-d',strtotime($SatrtDate)).'&end_date='.date('Y-m-d',strtotime($EndDate)).'';
			$ta_req = curl_get_file_contents($ta_url);
			$ta_res = json_decode($ta_req,true);
			if(isset($ta_res['results'])) {
				$ta_spent = array_sum(array_column($ta_res['results'],'spent'));
				$taSpent[] = round($ta_spent);
			}
		}
		mysqli_query($conn, "UPDATE budget_reminder SET ta_spent='".implode(',',$taSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
		//d($ta_res); 
			//https://backstage.taboola.com/backstage/api/1.0/bytindia-altis-inr-sc/reports/campaign-summary/dimensions/month?access_token=CWQPAAAAAAAAEVdhAgAAAAAAGAEgACl4w05KbgREDACTED_FB_TOKEN::b66473::51ec1f&start_date=2019-01-29&end_date=2019-11-07
	}
	*/
	
}

// YESTERDAY REPORT

$d = new DateTime('yesterday');
$d2 = new DateTime('yesterday');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';

$sqlRev=mysqli_query($conn, "SELECT tbl_id,camp_name,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;			
$total_spent = 0; 					
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	echo $sqlROW['tbl_id'].'<br>';
	$camp_contains = $sqlROW['camp_name'] ?? ''; // fallback if key not set

	$matches = [];
	$filter_keywords = [];

	if (!empty($camp_contains) && is_string($camp_contains)) {
		preg_match('/\[(.*?)\]/', $camp_contains, $matches);
		if (!empty($matches[1])) {
			$filter_keywords = array_map('trim', explode(',', $matches[1]));
		}
	}

	$filtering_param = $filtering_param_g = '';
	$filtering = [];
	$like_clauses = [];

	if (!empty($filter_keywords)) {
		foreach ($filter_keywords as $word) {
			$word = trim($word);
			$filtering[] = [
				"field" => "campaign.name",
				"operator" => "CONTAIN",
				"value" => $word
			];
	
			$like_clauses[] = "campaign.name LIKE '%" . $word . "%'";
		}
	
		$filtering_param = '&filtering=' . urlencode(json_encode($filtering));
	
		if (count($like_clauses) > 1) {
			$filtering_param_g = ' AND (' . implode(' OR ', $like_clauses) . ')';
		} elseif (count($like_clauses) === 1) {
			$filtering_param_g = ' AND ' . $like_clauses[0];
		}
	}

	$gAccIds[$sqlROW['tbl_id']] = $sqlROW['g_id'];
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = $fbClicks = $fbImpr = $gClicks = $gImpr = $fblead = $fbcpl = $gLead=array();
	
	
	//Facebook
	if($sqlROW['fb_id']!='') 
	{
		$fbIds = explode(',',$sqlROW['fb_id'] ?? '');	$fb_stDt = explode(',',$sqlROW['fb_stDt'] ?? '');	//$fbReceived = explode(',',$sqlROW['fb_received']);	
		
		foreach($fbIds as $key => $fbId) {						
			//echo $i . "<br />";
			$dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).'';

			$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/insights?level=account&fields=clicks,impressions,spend,actions,cost_per_action_type' . $filtering_param . '&access_token='.$access_token.'&'.$dtRange.'';
			$requests = curl_get_file_contents($request_url);
			$fb_response = json_decode($requests,true);
			if(isset($fb_response['data'][0]['spend'])){
				$fbSpent[] = round($fb_response['data'][0]['spend']); 
				$fbClicks[] = round($fb_response['data'][0]['clicks']); 
				$fbImpr[] = round($fb_response['data'][0]['impressions']); 
			} else {
				$fbSpent[] = 0;
			}
			if(isset($fb_response['data'][0]['actions'])){
                $fblead[] = LeadGen($fb_response['data'][0]['actions'], 'lead');
                $fbcpl[] = LeadGen($fb_response['data'][0]['cost_per_action_type'], 'lead');
            } else {
                $fblead[] = $fbcpl[] = 0;
            }
		}	
		mysqli_query($conn, "UPDATE budget_reminder SET fb_spent_y='".implode(',',$fbSpent)."', fb_leads2='".implode(',',$fblead)."', fb_cpl2='".implode(',',$fbcpl)."', clicks_fb_y='".implode(',',$fbClicks)."', impr_fb_y='".implode(',',$fbImpr)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	}
	
	if($sqlROW['g_id']!='') {
		
		$gaIds = explode(',',$sqlROW['g_id'] ?? ''); //$g_stDt = explode(',',$SatrtDate);		
		foreach($gaIds as $key => $gaId) {
			$gStats[] = $gaId; 
			$gDates[$gaId] = $SatrtDate; 
			$getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$gaId,date("Y-m-d", strtotime($SatrtDate)), date("Y-m-d", strtotime($EndDate)));
			//d($getAccRep);
			if(isset($getAccRep['cost'])) {
				$gSpent[] = round($getAccRep['cost']); 
				$gClicks[] = round($getAccRep['clicks']); 
				$gImpr[] = round($getAccRep['impressions']); 
				$gLead[] = round($getAccRep['conversions']) ?? 0;
			}
		}
		//echo "UPDATE budget_reminder SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id=".$sqlROW['tbl_id'].""; exit;
		mysqli_query($conn, "UPDATE budget_reminder SET g_spent_y='".implode(',',$gSpent)."', g_leads_y ='".implode(',',$gLead)."',  clicks_g_y='".implode(',',$gClicks)."', impr_g_y='".implode(',',$fbImpr)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	}
	
	//LinkedIn
	if($sqlROW['in_id']!='') { 
		$inIds = explode(',',$sqlROW['in_id']); $in_stDt = explode(',',$sqlROW['in_stDt']);	
		
		foreach($inIds as $key => $inId) {								
			$accQry ='accounts[0]=urn:li:sponsoredAccount:'.$inId.'&';
			$stDt = "dateRange.start.day=".date('d',strtotime($SatrtDate))."&dateRange.start.month=".date('m',strtotime($SatrtDate))."&dateRange.start.year=".date('Y',strtotime($SatrtDate))."&";
			$enDt = "dateRange.end.day=".date('d',strtotime($EndDate))."&dateRange.end.month=".date('m',strtotime($EndDate))."&dateRange.end.year=".date('Y',strtotime($EndDate));
				
			$val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=costInLocalCurrency&'.$stDt.''.$enDt);
			$inSpent[] = round($val['elements'][0]['costInLocalCurrency']);
		}
		mysqli_query($conn, "UPDATE budget_reminder SET in_spent_y='".implode(',',$inSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());		
	}
	//exit;
	//Taboola
	/*
	if($sqlROW['ta_id']!='' && $taboola_tok!='') {
		$taIds = explode(',',$sqlROW['ta_id'] ?? '');	$ta_stDt = explode(',',$sqlROW['ta_stDt'] ?? '');	
			
		foreach($taIds as $key => $taId) {	
			//if($ta_stDt[$key]=='') { $ta_stDt[$key]='2019/01/01'; } 			
			$ta_url = 'https://backstage.taboola.com/backstage/api/1.0/'.$taId.'/reports/campaign-summary/dimensions/month?access_token='.$taboola_tok.'&start_date='.date('Y-m-d',strtotime($SatrtDate)).'&end_date='.date('Y-m-d',strtotime($EndDate)).'';
			$ta_req = curl_get_file_contents($ta_url);
			$ta_res = json_decode($ta_req,true);
			if(isset($ta_res['results'])) {
				$ta_spent = array_sum(array_column($ta_res['results'],'spent'));
				$taSpent[] = round($ta_spent);
			}
		}
		mysqli_query($conn, "UPDATE budget_reminder SET ta_spent_y='".implode(',',$taSpent)."' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error());	
		
	}
	*/
	
}

$sqlRev=mysqli_query($conn, "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received,fb_leads,fb_leads2,fb_cpl,fb_cpl2,fb_spent_y, g_spent_y, in_spent_y, ta_spent_y FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;			
$total_spent = 0; 	
				


// Google sheet API
require_once 'google-sheets-api/vendor/autoload.php';
require_once 'google-sheets-api/class-db.php';
require_once 'google-sheets-api/config.php';

include 'google-sheets-api/clear-sheet.php';
include 'google-sheets-api/insert-row.php';

$spreadsheetId = '1XIXylbT51fjkriLVh-e3_boMfJ2k-BN4Q2bD6mry3A0';
$sheetTab = 'Autolink sheet - Adrescue';
$uId_SHEET = 1;
clear_sheet($uId_SHEET, $spreadsheetId, '1441276057');

//$leadV = [['Account Name', 'Monthly Budget', 'FB Spent Oct 2022', 'Google spent Oct 2022', 'Total Spent', 'Remaining Bal', 'Underspent', 'Overspent']];
//append_to_sheet($uId_SHEET, $uId_SHEET, $leadV, $spreadsheetId, $sheetTab);

//
//exit;
//EMAIL REPORT
//$sqlRev=mysqli_query($conn, "SELECT * FROM budget_reminder WHERE uid='2' AND delete_status=0 order by tbl_id asc");
//echo "SELECT c.*, cp.amount, cp.date FROM budget_reminder as c, cashflow_payments as cp WHERE c.tbl_id=cp.cashflow_id AND c.uid='2' AND c.delete_status=0 order by c.tbl_id asc"; 
$sqlRev=mysqli_query($conn, "SELECT * FROM budget_reminder WHERE uid='2' AND delete_status=0 AND hide_temp='0' order by cc_card asc");
$body_wa = array();
$tbl = '<br><h3>Budget detailed report:</h3>';
$tbl .= '	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>SNo</th>
			<th>Client Name</th>
			<th>Acc</th>
			<th>Spend</th>
			<th>Spend %</th>
            <th>Budget</th> 
            <th>Balance</th>
			<th>Budget Received</th> 
			<th>Outstanding</th>
			<th>Est. month end</th>
		  </tr>		
';
$maxDays = date('t');
$nDay = date("d");

$sno = 1; 
setlocale(LC_MONETARY, 'en_IN');
$spreadsheet = array();
$totRows = mysqli_num_rows($sqlRev);
$grandRecived = $grandSpent = $grandBudget = $grandMonEnd = $grandSpent_FB =$grandSpent_G = $outstanding = $grabd_budget_received = 0;
$grandRecived_byt = $grandSpent_byt = $grandBudget_byt = $grandMonEnd_byt = $grandSpent_FB_byt =$grandSpent_G_byt = $outstanding_byt =$grabd_budget_received_byt = 0;
$grandRecived_cl = $grandSpent_cl = $grandBudget_cl = $grandMonEnd_cl = $grandSpent_FB_cl =$grandSpent_G_cl = $outstanding_cl =$grabd_budget_received_cl = 0;
$med_bud = $med_est = array();

while($sqlROW=mysqli_fetch_array($sqlRev))
{ 
	//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
	//$tot_bal = $sqlROW["tot_paid"] - $tot_spend;
	$tot_bal = $fb_leads_spend=  $fb_leads_spend2 = 0;
    $fb_spent_y = $g_spent_y = $in_spent_y = $ta_spent_y = $fb_leads = $fb_cpl = $fb_leads2 = $fb_cpl2 =  0;

	$tot_spend = $fb_spent = $g_spent = $in_spent = $ta_spent = $last_paid = $budget_received = 0;
	$spend_per = '-';
											
	if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
	if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
	if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
	if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }
	if($sqlROW["budget_received"]!='') { $budget_received = explode(',',$sqlROW["budget_received"]); $budget_received = array_sum(array_filter($budget_received)); }
	
	if($sqlROW["fb_spent_y"]!='') { $fb_spent_y = explode(',',$sqlROW["fb_spent_y"]); $fb_spent_y = array_sum(array_filter($fb_spent_y)); }
	if($sqlROW["g_spent_y"]!='') { $g_spent_y = explode(',',$sqlROW["g_spent_y"]); $g_spent_y = array_sum(array_filter($g_spent_y)); }
	if($sqlROW["in_spent_y"]!='') { $in_spent_y = explode(',',$sqlROW["in_spent_y"]); $in_spent_y = array_sum(array_filter($in_spent_y)); }
	if($sqlROW["ta_spent_y"]!='') { $ta_spent_y = explode(',',$sqlROW["ta_spent_y"]); $ta_spent_y = array_sum(array_filter($ta_spent_y)); }

	$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
	$tot_spend_y = $fb_spent_y + $g_spent_y + $in_spent_y + $ta_spent_y;

	if($sqlROW["total_budget"]=='' || $sqlROW["total_budget"]==null) { $sqlROW["total_budget"]=0; }
	$tot_bal = $sqlROW["total_budget"] - $tot_spend;
	
	if( $tot_bal < 0 ) { $bgcolor='#f97878'; } else { $bgcolor='#99f3aa'; }

	$grandSpent = $grandSpent + $tot_spend;
	$grandBudget = $grandBudget + $sqlROW["total_budget"];
	$grandMonEnd = $grandMonEnd + (($tot_spend/$nDay)*$maxDays);
	$grandSpent_FB = $grandSpent_FB + $fb_spent;
	$grandSpent_G = $grandSpent_G + $g_spent;
	$grabd_budget_received = $grabd_budget_received + $budget_received;
	$outstanding = $tot_spend - $budget_received;
	$acc_type = '';
	if($sqlROW["cc_card"]!='') { $acc_type = $cc_card[$sqlROW["cc_card"]]; }

	if($sqlROW["cc_card"]==1) {
		$grandSpent_byt = $grandSpent_byt + $tot_spend;
		$grandBudget_byt = $grandBudget_byt + $sqlROW["total_budget"];
		$grandMonEnd_byt = $grandMonEnd_byt + (($tot_spend/$nDay)*$maxDays);
		$grandSpent_FB_byt = $grandSpent_FB_byt + $fb_spent;
		$grandSpent_G_byt = $grandSpent_G_byt + $g_spent;
		$grabd_budget_received_byt = $grabd_budget_received_byt + $budget_received;
		//$outstanding_byt = $tot_spend_byt - $budget_received;
	}
	if($sqlROW["cc_card"]==2) {
		$grandSpent_cl = $grandSpent_cl + $tot_spend;
		$grandBudget_cl = $grandBudget_cl + $sqlROW["total_budget"];
		$grandMonEnd_cl = $grandMonEnd_cl + (($tot_spend/$nDay)*$maxDays);
		$grandSpent_FB_cl = $grandSpent_FB_cl + $fb_spent;
		$grandSpent_G_cl = $grandSpent_G_cl + $g_spent;
		$grabd_budget_received_cl = $grabd_budget_received_cl + $budget_received;
		//$outstanding_cl = $tot_spend_cl - $budget_received;
	}

	$med_bud[$sqlROW["media_by"]][] = $sqlROW["total_budget"];
    $med_est[$sqlROW["media_by"]][] = (($tot_spend/$nDay)*$maxDays);

	//$body_wa[$sqlROW["media_by"]] = '\\n*'.$sqlROW["client_name"].' ('.$acc_type.' card)*\\n';
	//$body_wa[$sqlROW["media_by"]] .= '\\nBudget / Received / Client cash: '.$sqlROW["client_name"].' ('.$acc_type.' card)*\\n';

	if($sqlROW["total_budget"]!='' && $sqlROW["total_budget"]>0 && $tot_spend>0) { $spend_per = @round(($tot_spend/$sqlROW["total_budget"])*100); }

	if($sqlROW["fb_leads"]!='') { 
		$fb_leads_exp = $fb_leads_spend = array();
		$fb_leads_exp = explode(',',$sqlROW["fb_leads"]); 
		$fb_leads = array_sum(array_filter($fb_leads_exp)); 
		$fb_cpl_exp = explode(',',$sqlROW["fb_cpl"]);
		if($fb_leads>0) {
		  foreach($fb_leads_exp as $lKey => $lval){
			$fb_leads_spend[] = $lval * $fb_cpl_exp[$lKey];
		  }
		  $fb_leads_spend = array_sum(array_filter($fb_leads_spend));
		  $fb_cpl = round(($fb_leads_spend / $fb_leads));
		} else {
		  $fb_leads = $fb_leads_spend = 0;
		}
	  }

	  if($sqlROW["fb_leads2"]!='') { 
		$fb_leads_exp2 = $fb_leads_spend2 = array();
		$fb_leads_exp2 = explode(',',$sqlROW["fb_leads2"]); 
		$fb_leads2 = array_sum(array_filter($fb_leads_exp2)); 
		$fb_cpl_exp2 = explode(',',$sqlROW["fb_cpl2"] ?? '');
		if($fb_leads2>0) {
		  foreach($fb_leads_exp2 as $lKey => $lval){
			$fb_leads_spend2[] = $lval * $fb_cpl_exp2[$lKey];
		  }
		  $fb_leads_spend2 = array_sum(array_filter($fb_leads_spend2));
		  $fb_cpl2 = round(($fb_leads_spend2 / $fb_leads2));
		} else {
		  $fb_leads2 = $fb_leads_spend2 = 0;
		}
	  }
	  $actual_spend = 0;
	if($sqlROW["total_budget"]!=0) { $actual_spend = $fmt->format(($sqlROW["total_budget"]/$maxDays)); }
	$est_mon_end = $fmt->format((($tot_spend/$nDay)*$maxDays));
	$body_wa[$sqlROW["media_by"]][] = array(
		'client'=> $sqlROW["client_name"],
		'acc_ty'=> $acc_type,
		'budget'=> $fmt->format($sqlROW["total_budget"]),
		'bud_rec'=> $fmt->format($budget_received),
		'due' => $fmt->format($outstanding),
		'spend'=> $fmt->format($tot_spend),
		'est_mon_spend'=> $est_mon_end,
		'cpl'=> $fmt->format($fb_cpl),
		'cpl2'=> $fmt->format($fb_cpl2),
		'yest_spend'=> $fmt->format($tot_spend_y),
		'act_spend' => $actual_spend
	);
	if($est_mon_end<$sqlROW["total_budget"]) { $arrow_icon = 'down-arr.png'; } else { $arrow_icon = 'up-arr.png'; }
	$tbl .='
		<tr>
			<td>'.$sno.'</td>
			<td>'.$sqlROW["client_name"].'</td>
			<td>'.$acc_type.'</td>
			<td style="text-align: right;">'.$fmt->format($tot_spend).'</td>
			<td style="text-align: right;">'.$spend_per.' %  <img src="https://stage.adrescue.in/images/'.$arrow_icon.'" /></td>
			<td style="text-align: right;">'.$fmt->format($sqlROW["total_budget"]).'</td>
			<td style="background-color:'.$bgcolor.';text-align: right;"><b>'.$fmt->format($tot_bal).'</b></td>
			<td style="text-align: right;">'.$fmt->format($budget_received).'</td>
			<td style="text-align: right;">'.$fmt->format($outstanding).'</td>
			<td style="text-align: right;"><b>'.$est_mon_end.'</b></td>
		</tr>
	';

	$leadV = [[$sqlROW["client_name"], $fmt->format($sqlROW["total_budget"]), $fmt->format($fb_spent), $fmt->format($g_spent), $fmt->format($tot_spend), $fmt->format($tot_bal)]];
	//append_to_sheet($uId_SHEET, $uId_SHEET, $leadV, $spreadsheetId, $sheetTab);

	if($sno==($totRows)){ 
		$spend_per_grand = '-';
		if($grandBudget!='' && $grandBudget>0 && $grandSpent>0) { $spend_per_grand = @round(($grandSpent/$grandBudget)*100); }
		if($grandMonEnd<$grandBudget) { $arrow_icon = 'down-arr.png'; } else { $arrow_icon = 'up-arr.png'; }
		$tbl .='
			<tr>
				<td>-</td>
				<td><b>Total</b></td>
				<td>-</td>
				<td style="text-align: right;"><b>'.$fmt->format($grandSpent).'</b></td>
				<td style="text-align: right;"><b>'.$spend_per_grand.' %</b>  <img src="https://stage.adrescue.in/images/'.$arrow_icon.'" /></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandBudget).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format(($grandBudget-$grandSpent)).'</b></td>
				<td style="text-align: right;">'.$fmt->format($grabd_budget_received).'</td>
				<td style="text-align: right;">'.$fmt->format($grandSpent - $grabd_budget_received).'</td>
				<td style="text-align: right;"><b>'.$fmt->format($grandMonEnd).'</b></td>
			</tr>
		';
		$leadV = [['Total', $fmt->format($grandBudget), $fmt->format($grandSpent_FB), $fmt->format($grandSpent_G), $fmt->format($grandSpent), $fmt->format(($grandBudget-$grandSpent))]];
		//append_to_sheet($uId_SHEET, $uId_SHEET, $leadV, $spreadsheetId, $sheetTab);
	}
	//$spreadsheet[] = array($sno,$sqlROW["client_name"], $sqlROW["tot_paid"], $tot_spend, $tot_bal);
	$sno++;
	
}
$tbl .='</table>';

if($grandMonEnd_byt<$grandBudget_byt) { $arrow_icon1 = 'down-arr.png'; } else { $arrow_icon1 = 'up-arr.png'; }
if($grandMonEnd_cl<$grandBudget_cl) { $arrow_icon2 = 'down-arr.png'; } else { $arrow_icon2 = 'up-arr.png'; }
if($grandMonEnd<$grandBudget) { $arrow_icon3 = 'down-arr.png'; } else { $arrow_icon3 = 'up-arr.png'; }

$tbl2 = '<br>
<img src="https://stage.adrescue.in/images/up-arr.png" /> - Overspend<br>
<img src="https://stage.adrescue.in/images/down-arr.png" /> - Underspend<br>
<h3>Budget Overview:</h3>
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>SNo</th>
			<th>Acc</th>
			<th>Spend</th>
			<th>Spend %</th>
            <th>Budget</th> 
            <th>Balance</th>
			<th>Budget Recived</th> 
			<th>Outstanding</th>
			<th>Est. month end</th>
		  </tr>		
		  <tr>
				<td>1</td>
				<td><b>BYT</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandSpent_byt).'</b></td>
				<td style="text-align: right;"><b>'.round(($grandSpent_byt/$grandBudget_byt)*100).' %</b> <img src="https://stage.adrescue.in/images/'.$arrow_icon1.'" /></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandBudget_byt).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format(($grandBudget_byt-$grandSpent_byt)).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($grabd_budget_received_byt).'</b></td>
				<td style="text-align: right;">'.$fmt->format($grandSpent_byt - $grabd_budget_received_byt).'</td>
				<td style="text-align: right;"><b>'.$fmt->format($grandMonEnd_byt).'</b></td>
		  </tr>
		  <tr>
				<td>2</td>
				<td><b>Client</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandSpent_cl).'</b></td>
				<td style="text-align: right;"><b>'.round(($grandSpent_cl/$grandBudget_cl)*100).' %</b> <img src="https://stage.adrescue.in/images/'.$arrow_icon2.'" /></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandBudget_cl).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format(($grandBudget_cl-$grandSpent_cl)).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($grabd_budget_received_cl).'</b></td>
				<td style="text-align: right;">'.$fmt->format($grandSpent_cl - $grabd_budget_received_cl).'</td>
				<td style="text-align: right;"><b>'.$fmt->format($grandMonEnd_cl).'</b></td>
		  </tr>
		  <tr>
				<td>-</td>
				<td><b>Total</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandSpent).'</b></td>
				<td style="text-align: right;"><b>'.round(($grandSpent/$grandBudget)*100).' %</b> <img src="https://stage.adrescue.in/images/'.$arrow_icon3.'" /></td>
				<td style="text-align: right;"><b>'.$fmt->format($grandBudget).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format(($grandBudget-$grandSpent)).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($grabd_budget_received).'</b></td>
				<td style="text-align: right;">'.$fmt->format($grandSpent - $grabd_budget_received).'</td>
				<td style="text-align: right;"><b>'.$fmt->format($grandMonEnd).'</b></td>
		  </tr>
		</table>
';

$tbl3 = '<br><h3>Media buyer vs Budget:</h3>
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>Media Buyer</th>
			<th>Budget</th>
			<th>Est. Mon. End</th>
			<th>Est. Diff</th>
			<th>Est. Diff in %</th>
			<th>Spend</th>
			<th>Penalty</th>
		  </tr>';

		  foreach($med_bud as $k => $v){ 
			$tot_bud = array_sum($med_bud[$k]);
			$tot_est = array_sum($med_est[$k]);
			$tot_dif = $tot_bud - $tot_est;
			$bud_2per = $tot_bud * 0.05;
			if($tot_dif>0) { $spendTy= 'Underspend'; $penalty = $tot_dif - $bud_2per; } else { $spendTy= 'Overspend'; $penalty = ($tot_dif + $bud_2per) * -1; }
			if( $penalty > 0 ) { $bgcolor='#f97878'; } else { $bgcolor='#99f3aa'; $penalty=0; }
		  	
			$percentage = ($tot_bud > 0) ? round(($tot_dif / $tot_bud) * 100, 2) . ' %' : '0 %';
			$tbl3 .='<tr>
				<td style="text-align: right;"><b>'.$media_by[$k].'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format($tot_bud).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format(round($tot_est)).'</b></td>
				<td style="text-align: right;"><b>'.$fmt->format(round($tot_dif)).'</b></td>
				<td style="text-align: right;">'.$percentage.'</td>
				<td style="text-align: right;"><b>'.$spendTy.'</b></td>
				<td style="background-color:'.$bgcolor.';text-align: right;"><b>'.$fmt->format(round($penalty)).'</b></td>
			</tr>';
		  }
$tbl3 .='</table>';

//d($body_wa); exit;
$rep_time = date("d-m-Y h:i a");
foreach($body_wa  as $k => $v){
	$media_buyer = $media_by[$k];
	$med_by_ph = $media_by_ph[$k];
	$txt_msg = '';
	foreach($v as $k1 => $v1){
		
		$txt_msg .= '\\n*'. $v1['client'] . ' ('.$v1['acc_ty'].')*\\n';
		$txt_msg .= 'Bud. / Rcd. / Due: '.$v1['budget'].' / '.$v1['bud_rec'].' / '.$v1['due'].'\\n';
		$txt_msg .= 'Spend: '.$v1['spend'].'\\n';
		$txt_msg .= 'Est. Spend: '.$v1['est_mon_spend'].'\\n';
		$txt_msg .= 'CPL / pre mon: '.$v1['cpl'].' / '.$v1['cpl2'].'\\n';
		//$txt_msg .= ': '.$v1['cpl2'].'\\n';
		$txt_msg .= 'Yest. Spend: '.$v1['yest_spend'].'\\n';
		$txt_msg .= 'Actual Spend: '.$v1['act_spend'].'\\n';
		
	}
	$tit_wa = $media_buyer." : Ads & Budget Report";
		$phone = array('9176299010', '9840031390', $med_by_ph);
		//echo $txt_msg; 
		foreach($phone as $k3 => $v3) 
		{	
					$phNo= trim($v3);
					if($phNo!=''){
						sendWhatsapp($access_token, $phNo, $tit_wa, $txt_msg, $rep_time); //exit;
					}
		}

}

if (date('Y-m-d') === date('Y-m-t')) {
    $sqlDrop = "DROP TABLE IF EXISTS budget_reminder_last_mon";
    $conn->query($sqlDrop);

    $sqlCreate = "CREATE TABLE budget_reminder_last_mon LIKE budget_reminder";
    $conn->query($sqlCreate);

    $sqlInsert = "INSERT INTO budget_reminder_last_mon SELECT * FROM budget_reminder";
    $conn->query($sqlInsert);
}

//echo $tbl.'<br>'.$tbl2;  exit;
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
if(!isset($_GET['tbl_id'])) {
	mysqli_query($conn, "UPDATE last_updated SET last_updated=now() WHERE type='budget'") or die(mysqli_error()); 
}
//exit;
if(isset($_GET['refresh'])) {
	echo "<script>window.location = 'budget2.php';</script>"; 
	exit();
} else {
	$to_address = "bytacnts@gmail.com, faheem@bytindia.com, accounts@bytindia.com, prabhu@bytindia.com, shaheena@bytindia.com, charan@bytindia.com, mughil@bytindia.com, simin@bytindia.com, ramesh@bytindia.com";
	$subjLine = 'Ads Budget'; 
	//$to_address = "prabhu@bytindia.com";
	include 'email/mail-budget.php';
	//$to_address = "bytacnts@gmail.com, faheem@bytindia.com, accounts@bytindia.com, prabhu@bytindia.com, shaheenabyt@gmail.com, samadhbyt@gmail.com, balamurugan.vbyt@gmail.com, pavithrabyt@gmail.com, bytacnts@gmail.com";
	$subjLine = 'Media buyer vs Budget'; 
	//include 'email/mail-media-buyer.php';
}

echo 'success';
exit;
//act_107704242648697/insights?level=account&fields=spend&time_range[since]=2018-02-01&&time_range[until]=2019-11-07