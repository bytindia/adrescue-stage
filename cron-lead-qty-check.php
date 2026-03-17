<?php session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include 'db.php';
include 'functions-report.php'; 

require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';

include 'gsquare-taboola.php';

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
global $output;
function loopAdRep($url) {
    global $output;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
    if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = array_merge($output, $fb_response['data']);
    } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = $fb_response['data']; 
    }
    
	if(isset($fb_response['paging']['next'])) {
		loopAdRep($fb_response['paging']['next']);
	} else { 
        return $output['data'] = $output; 
	}
}
function loopAdRep2($url) {
    global $output;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
    if(isset($output) && count($output)>0 && !isset($fb_response['error']) && count($fb_response)>0) {
        $output = array_merge($output, $fb_response);
    } else if(isset($fb_response) && count($fb_response)>0) {
        $output = $fb_response; 
    }
    
	if(isset($fb_response['paging']['next'])) {
		loopAdRep($fb_response['paging']['next']);
	} else { 
        return $output = $output; 
	}
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
//include 'taboola-config.php';
//include 'google-ads.php';

$app_id = '594832897646145';
$tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";

if($access_token!='') {  
	if (!$tok_req = curl_get_file_contents($tok_url)) { 
		  $pg = 'cron-fb-report';      
		  include 'email/mail-error.php';
		  exit;
	} 
}

$gAccIds = $gStats =array();
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-1 days")); 

$extQ ="";
if(isset($_GET['tbl_id'])) {
	$extQ = "tbl_id=".$_GET['tbl_id']." AND ";
} 
$cc_card = array(1=>'BYT', 2=>'Client'); 

$sqlROWs = $ad_acc_name = array();
										
$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid=2 order by name asc");
while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs[] = $sqlROW; }
foreach($sqlROWs as $sqlROW){ 
    $ad_acc_name[$sqlROW["account_id"]] = $sqlROW["name"];
}
$qry_str_adset = '&filter_set=SEARCH_BY_CAMPAIGN_IDS-STRING_SET%1EANY%1E[%22';
$sendEmail = 'no';
$tbl = '<br><h3>Performance Goal : Maximize number of conversion leads</h3>';
$tbl .= '	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
			<th>Ad Acc.</th>
			<th>AdSet</th>
			<th>Campaign</th>
			<th>View</th>
		  </tr>		
';
error_reporting(E_ALL);

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$ad_accounts = [
    494189059371075 => "Asha Academia (BMI) (AV)",
    5731659836926959 => "Big Cup (BMI) (AV)",
    543552406049077 => "Eden Park Main Ad Account (BMI)",
    1199132407348703 => "Ecovista Life (BMI) (AV)",
    5648409881891407 => "KMC Aluminium (BMI) (AV)",
    624107499015180 => "KVCET - BYT CC",
    4724898947735863 => "MLM Vishal Nagar",
    514080233995392 => "OKEAD (BMI) (CC)",
    312900368198106 => "Physio1st (BMI)",
    1782824538572369 => "Rockstar (BMI)",
    271047031562298 => "RLD Merlion - BMI",
    732236261026706 => "RWD Account 1 - BYT CC",
    665661671485180 => "RWD Waterfront (BMI)",
    615262527033827 => "Vibrant Homes",
    706484654176551 => "Sounds Good (BMI - CC)",
    1174724716367570 => "BYT Digital (BMI)",
    990352134809831 => "Urbando (BMI - IL)",
    735957617015640 => "NRI_BYT_CL"
];
//echo "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0"; exit;
$sqlRev=mysqli_query($conn, "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0 AND hide_temp='0'");
//$sqlROW=mysqli_fetch_assoc($sqlRev);
//d($sqlROW); exit;			
$total_spent = 0; 	
$act_ads_adset_ids_all = $acc_info = array();
foreach($ad_accounts as $acc_key => $acc_val) 
{
	
	//echo $sqlROW['tbl_id'].'<br>';
	//$gAccIds[$sqlROW['tbl_id']] = $sqlROW['g_id'];
	$fbIds = $taIds = $inIds = $fbSpent = $inSpent = $taSpent = $gSpent = $acc_bal = array();
	
	//mysqli_query($conn, "UPDATE budget_reminder SET fb_spent='0', fb_balance='', g_spent='0', in_spent='0', ta_spent='0' WHERE tbl_id=".$sqlROW['tbl_id']."") or die(mysqli_error()); 
	
	//Facebook
	if($acc_key!='') 
	{
		$fbIds = explode(',',$acc_key);	
		
		foreach($fbIds as $key => $fbId) {						
			
            $url_ad = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token=".$access_token."&limit=750"; 
            $act_ads_camp_ids = $output =  array();
            loopAdRep($url_ad);  
            $res_ad = $output;
            if(isset($res_ad['data']) && count($res_ad['data'])>0) {
                foreach($res_ad['data'] as $kk => $vv) {
                    if($vv['effective_status']=='ACTIVE'){
                        $act_ads_camp_ids[] = $vv['campaign_id'];
                        //$act_ads_adset_ids[] = $vv['adset_id'];
                    }
                }
            }
            $act_ads_camp_ids = array_unique($act_ads_camp_ids);


            $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/campaigns?fields=account_id,id,name,effective_status,end_time,adsets.limit(50){name,id,effective_status,end_time,optimization_goal,ads.limit(50){id,adset_id,effective_status,configured_status,status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']},{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&access_token=".$access_token."&limit=750";
            $output = $res = $act_campIds = $act_ads_adset_ids = $act_ads_id = array();
			
            loopAdRep($url);  
            $res = $output; $k=1;

            if(isset($res['data']) && count($res['data'])>0){ 
                foreach($res['data'] as $k1 => $v1) {
                    $camp_act = 'y';
                    if(isset($v1['end_time'])) { 
                        $end_time = new DateTime($v1['end_time']);
                        $currentDateTime = new DateTime('now', $end_time->getTimezone());
                        if($end_time < $currentDateTime) { $camp_act = 'n'; }
                    }
        
                    if($v1['effective_status']=='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act=='y') {  
                        // && isset($v1['bid_strategy']) && ($v1['bid_strategy']=='LOWEST_COST_WITH_BID_CAP' || $v1['bid_strategy']=='COST_CAP')){
                        $camp_act = 'n';
                        if(isset($v1['adsets']['data'])){
                            
                            foreach($v1['adsets']['data'] as $as_k => $as_v) {
                                $ads_act = 'n';
                                if($as_v['effective_status']=='ACTIVE'){
                                    if(isset($as_v['ads']['data'])){
                                        $endCheck = 'y';
                                        if(isset($as_v['end_time'])) { 
                                            $end_time = new DateTime($as_v['end_time']);
                                            $currentDateTime = new DateTime('now', $end_time->getTimezone());
                                            if($end_time < $currentDateTime) { $ads_act = 'n'; $endCheck='n'; }
                                        }
                                        if($endCheck=='y'){
                                            foreach($as_v['ads']['data'] as $ad_k => $ad_v) {
                                                if($ad_v['effective_status']=='ACTIVE'){
                                                    $ads_act = $camp_act = 'y';
                                                    $act_ads_id[] = $ad_v['id'];
                                                }
                                            }
                                            
                                            if($ads_act == 'y'){
                                                $act_campIds[$v1['id']] = $v1['id'];
                                                $act_ads_adset_ids[] = $as_v['id'];
                                                $act_ads_adset_ids_all[] = $as_v['id'];
                                                $acc_info[$as_v['id']] = array('acc_id'=>$v1['account_id'],'camp_name'=>$v1['name'],'adset_name'=>$as_v['name'],'goal'=>$as_v['optimization_goal']);

                                                if(isset($as_v['optimization_goal']) && $as_v['optimization_goal']=='QUALITY_LEAD'){
                                                    $fb_url_adset = 'https://adsmanager.facebook.com/adsmanager/manage/adsets?act='.$v1['account_id'];
                                                    $AS_Link = $fb_url_adset.''.$qry_str_adset.''.$as_v['id'].'%22]';
                                                    $tbl .= '<tr><td>'.$acc_val.'</td><td>'.$as_v['name'].'</td><td>'.$v1['name'].'</td><td><a href="'.$AS_Link.'" target="_blank">View</a></td></tr>';
                                                    $sendEmail = 'yes';
                                                }
                                                
                                            }
                                        }
                                    }
                                }
                            }
                        
        
                        }
                    }
                }
		    }
            //d($acc_info);
            //exit;
            /*
            $act_ads_adset_ids_all_2 = array();
            if(count($act_ads_adset_ids)>0){
                $act_ads_adset_ids_all_2 = implode(',',$act_ads_adset_ids);
                $url_adset = "https://graph.facebook.com/".$api_ver."/?ids=".$act_ads_adset_ids_all_2."&fields=id,name,optimization_goal&access_token=".$access_token."&limit=750"; 
                $output =  array();
                loopAdRep2($url_adset);  
                $res_adset = $output;
                if(isset($res_adset) && count($res_adset)>0){ 
                    foreach($res_adset as $k => $v) {
                        if(isset($v['optimization_goal']) && $v['optimization_goal']=='QUALITY_LEAD'){
                            $fb_url_adset = 'https://adsmanager.facebook.com/adsmanager/manage/adsets?act='.$acc_info[$v['id']]['acc_id'];
                            $AS_Link = $fb_url_adset.''.$qry_str_adset.''.$v['id'].'%22]';
                            $tbl .= '<tr><td>'.$ad_acc_name[$acc_info[$v['id']]['acc_id']].'</td><td>'.$acc_info[$v['id']]['adset_name'].'</td><td>'.$acc_info[$v['id']]['camp_name'].'</td><td><a href="'.$AS_Link.'" target="_blank">View</a></td></tr>';
                        }
                    }
                }
            }
                */
           // d($acc_info);
           // exit;
            
	    }
    }
}



$tbl .= '</table><br>';
//echo $tbl; 
//exit;
$tbl2 ='';
if($sendEmail == 'yes'){
    $to_address = "faheem@bytindia.com, prabhu@bytindia.com, shaheena@bytindia.com, charan@bytindia.com, mughil@bytindia.com, simin@bytindia.com, ramesh@bytindia.com";
	$subjLine = 'Performance Goal : Maximize number of conversion leads'; 
	//$to_address = "prabhu@bytindia.com";
	include 'email/mail-budget.php';
}   

echo 'success';
exit;
