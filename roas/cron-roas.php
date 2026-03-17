<?php
session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

if(isset($_POST['submit'])) {
    
    //print_r($_FILES); exit;
    $password = $_POST['password'];
    $user = $_POST['user'];
   
    if($user =='techno' && $password=='BYTechno@23') {
        
        $_SESSION['logged'] = 1;
        echo "<script>window.location = 'index.php';</script>";
        exit();
    } else {
        $_SESSION['err'] = 'Invalid username / password';	
        echo "<script>window.location = 'index.php';</script>";
        exit();
    }
}

include '../db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include '../config.php';

$_GET['st']  = $_GET['en'] = date('d/m/Y',strtotime('yesterday'));
$dtRange1_y = str_replace('/', '-', $_GET['st']); 
$dtRange2_y = str_replace('/', '-', $_GET['en']);

if(isset($_GET['today'])) {
    $_GET['st']  = $_GET['en'] = date('d/m/Y',strtotime('yesterday'));
    $dtRange1_y = str_replace('/', '-', $_GET['st']); 
    $dtRange2_y = str_replace('/', '-', $_GET['en']);
} else {
    $_GET['st']  = date('d/m/Y',strtotime('first day of this month'));
    $_GET['en'] = date('d/m/Y',strtotime('today'));
    $dtRange1 = str_replace('/', '-', $_GET['st']); 
    $dtRange2 = str_replace('/', '-', $_GET['en']);
}

$obj_arr = array(
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'lead'
);

$fb_id = 165466229093582;
$spreadsheetId = '1w5w_2nJvtRiH7A-unHGpk0pOcnPjvegLHUE8BqHOGwI';
$sheetTab = 'Sales Tracking - all sources (for BYT Team)';
$uid_sheet = 2;

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function find_parent($array, $needle, $parent = null) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $pass = $parent;
            if (is_string($key)) {
                $pass = $key;
            }
            $found = find_parent($value, $needle, $pass);
            if ($found !== false) {
                return $found;
            }
        } else if ($key === 'id' && $value === $needle) {
            return $parent;
        }
    }

    return false;
}
function moneyFormatIndia($num) {
    $num = round($num);
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
    if($thecash==0) { $thecash='-'; }
    return $thecash; // writes the final format where $currency is the currency symbol.
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
$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

// Google sheet API
require_once '../google-sheets-api/vendor/autoload.php';
require_once '../google-sheets-api/class-db.php';
require_once '../google-sheets-api/config.php';
include '../google-sheets-api/read-sheet.php';

//Yesterday

$fetch_sale = read_sheet($uid_sheet, $spreadsheetId, $sheetTab);
//echo '<pre>';   print_r($fetch_sale); echo '</pre>'; 
//exit;
$rangeStart = strtotime($dtRange1);  
$rangeEnd = strtotime($dtRange2);  
  
$filter_sale = array_filter($fetch_sale['data'], function($var) use ($rangeStart, $rangeEnd) {  
    $evtime = strtotime($var[0]);  
    return $evtime <= $rangeEnd && $evtime >= $rangeStart;  
});
//d($filter_sale); exit;

$url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=account&fields=spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=750";
$req = file_get_contents_curl($url);
$res = json_decode($req, true);  
//d($res['data'][0]['spend']);
$spend_ads = $res['data'][0]['spend'];
$leads = LeadGen($res['data'][0]['actions'], $obj_arr['CONVERSIONS']);
$cpl = @(($spend_ads/$leads));


$raw_Val = $csvData = $csvData2 = $csvData3 = $raw_Keys = $sale_Keys = $sale_Val = array();
//RAW Data
//SELECT * FROM `clickfunnel_technofunda` WHERE page_id=59016691 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
$raw_data = array();
$sqlRev=mysqli_query($conn, "SELECT email, source, campaign FROM `clickfunnel_technofunda` WHERE page_id=59016691 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
			
while($sqlROW=mysqli_fetch_assoc($sqlRev))
{
    $raw_data[] = $sqlROW;
    $src_val = trim($sqlROW['source']);
    $cmp_val = trim($sqlROW['campaign']);
    $csvData[$src_val.'<>'.$cmp_val][] = trim($sqlROW['email']);
    $csvData3[$sqlROW['email']] = $src_val.'<>'.$cmp_val;
}
//d($csvData3); exit;
foreach($filter_sale as $k => $v){
    if(isset($csvData3[$v[3]])) {
        $find_key = $csvData3[$v[3]];
        $csvData2[$find_key][] = trim($v[3]);
    }
}

$raw_Keys = array_keys($csvData);
$sale_Keys = array_keys($csvData2);

$all_Keys = array_unique(array_merge($raw_Keys,$sale_Keys), SORT_REGULAR);
$all_Keys = array_diff($all_Keys, array('<>'));

//d($all_Keys); exit;
foreach($csvData as $k => $v){
    $raw_Val[$k] = count($v);
} 
foreach($csvData2 as $k => $v){
    $sale_Val[$k] = count($v);
} 
if(isset($sale_Val['<>'])) { unset($sale_Val['<>']); }
$tot_sal_lead = array_sum($sale_Val);

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

function sendWhatsapp($tok, $acc_name, $phone, $spend, $leads, $cpl, $sale_lead, $roas) {
     $phone = '91'.$phone; // exit;
    // $wa_msg = 'The *'.$acc_name.'* ad account has reached *'.$percentage.'%* of its total spend, which is *₹'.$spend.'* out of *₹'.$budget_V.'*';
    $attachment =  array(
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type'=> 'template',
        'template' => 
          json_encode(
            array(
              'name' => 'roas_alert', 
              'language' => array('code'=>'en_US'), 
              'components'=> 
                array(
                    array(
                        "type" => "header",
                        "parameters" => array(array("type"=> "text", "text"=> 'ROAS -'.$acc_name))
                    ),
                    array(
                        "type" => "body",
                        "parameters" => array(
                            array("type"=> "text","text"=> '₹ '.$spend),
                            array("type"=> "text","text"=> $leads),
                            array("type"=> "text","text"=> $cpl),
                            array("type"=> "text","text"=> $sale_lead),
                            array("type"=> "text","text"=> $roas),
                            array("type"=> "text","text"=> 'This month')
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

            $phone = array('9176299010');
            //$phone = explode(",",$send_V);
	        //foreach($to_address as $val) { $mail->addAddress(trim($val)); }
            if($tot_sal_lead>0) { $roas_v = @round(((6999 * $tot_sal_lead)/$spend_ads),2); } else { $roas_v = '-'; } 
            $phone = array('9176299010');
            foreach($phone as $key => $v) {	
               $phNo= trim($v);
               sendWhatsapp($access_token, 'Technofunda', $phNo, moneyFormatIndia($spend_ads), moneyFormatIndia($leads), moneyFormatIndia(round($cpl)), moneyFormatIndia($tot_sal_lead), $roas_v);
            }
?>
success