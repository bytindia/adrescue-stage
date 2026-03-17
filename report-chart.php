<!-- Include Required Prerequisites -->
<?php session_start(); date_default_timezone_set("Asia/Calcutta");  
if(!isset($_SESSION['client'])) {
	$pg = 'login-placement.php';
	$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	exit();
}
include 'db.php';
$pg='facebook';
include 'placements/config.php';
$d = new DateTime('first day of this month');
$stDt = $d->format('d/m/Y');
$enDt = date('d/m/Y');

$obj_arr = array(
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'leadgen_grouped'
    );
  //echo "time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2)); exit;
  $ad_acc = array(
    427933624679014=>'Athens',
    1744658879060018=>'Boulevard',
    1196015513904250=>'Southbrooke',
    569001103516290=>'Aria',
    462105354650207=>'Jubliant',
    370327086813811=>'Firstcity',
    807092046796106=>'Flagship',
    735957617015640=>'PlatinumJoy',
    834297526950773=>'Tudor',
    1317725875075405=>'Zenith'

);
 //$ad_acc = array( 807092046796106=>'Flagship',1744658879060018=>'Boulevard');
  
  if(!isset($_GET['act_id'])){
    $acc_id = 1782824538572369;
  } else {
    $acc_id = $_GET['act_id'];
  }


$url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/insights?level=campaign&fields=spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($stDt))."&time_range[until]=".date("Y-m-d", strtotime($enDt))."&access_token=".$access_token."&limit=750";
$req = file_get_contents_curl($url);
$res = json_decode($req, true);  

d($res);

?>