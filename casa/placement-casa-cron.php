<?php ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta"); 
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$_SESSION['client'] = 'casa';
ini_set('memory_limit','2048M');
if(!isset($_SESSION['client'])) {
	$pg = 'login.php';
	$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	//echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	//exit();
}
//print_r($_SESSION);
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
?>

<?php 
/*if(!isset($_SESSION['st'])) {
	$start = date('m/d/Y',strtotime('first day of this month'));
	$end = date('m/d/Y');
	$_SESSION['st'] = $start;
	$_SESSION['en'] = $end;
} else {

}*/
$dt_q ='';

if ($argc > 1 && $argv[1] ==='today'){
    $_GET['today'] = 1;
} else {
    $_GET['last_3d'] = 1;
}

if(isset($_GET['today'])) {
    $_GET['st']  = $_GET['en'] = date('d/m/Y',strtotime('today'));
} else {
    $_GET['st']  = date('d/m/Y',strtotime('-3 days'));
    $_GET['en'] = date('d/m/Y',strtotime('yesterday'));
}
//echo $_GET['st'].' to '.$_GET['en'] ; exit;

if(isset($_GET['st']) && $_GET['st']!='') {
    $_SESSION['st'] = $_GET['st'];
	$_SESSION['en'] = $_GET['en'];
} else {
    $start = date('d/m/Y',strtotime('first day of this month'));
	$end = date('d/m/Y');
    $_SESSION['st'] = $start;
    $_SESSION['en'] = $end;
}

$dirPath = '/home/digitalb2k/public_html/adsninja/';

include $dirPath.'db.php';
$pg='facebook';
include $dirPath.'casa/config.php';

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

function contains($str, array $arr)
{
    foreach($arr as $a) {
      //print_r($a);
        if (stripos($str,$a) !== false) { return $a; }
    }
    return false;
}

if(isset($_GET['st'])) { 
        $stDt =  $_SESSION['st']; 
        $enDt =  $_SESSION['en']; 
        $filter = 'yes';
        $dtRange = $_SESSION['st'].' - '.$_SESSION['en']; 
        $dtRange1 = str_replace('/', '-', $_SESSION['st']); $dtRange2 = str_replace('/', '-', $_SESSION['en']);
        $urlParam = '?st='.$stDt.'&en='.$enDt;
    } else { 
        $stDt = date('d/m/Y');
        $enDt = date('d/m/Y'); 
        $filter = 'no';

        $d = new DateTime('first day of this month');
        //$d = new DateTime('first day of this month');
        //echo $d->format('d/m/Y');

        $stDt = $d->format('d/m/Y');
        $enDt = date('d/m/Y');
        
        $dtRange = 'this month';
        //$dtRange1 = '-29 days'; $dtRange2 = '0 days';
        $dtRange1 = $d->format('Y-m-d'); 
        $dtRange2 = '0 days';
        $urlParam = '';
} 

  // $urlParam = '';
  if(isset($overview) && !isset($_SESSION['st']))
  {
      $d = new DateTime('first day of this month');
      //$d = new DateTime('first day of this month');
      //echo $d->format('d/m/Y');
  
      $stDt = $d->format('d/m/Y');
      $enDt = date('d/m/Y');
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
  //echo "time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2)); exit;
  $ad_acc = array();
  $extQ .= " AND hide_temp='0' AND fb_spent!='' ";
  $statement = " budget_reminder WHERE uid='2' AND delete_status=0";
  $sqlRev=mysqli_query($conn, "SELECT client_name, fb_id FROM ".$statement." $extQ order by cc_card");
  while($sqlROW=mysqli_fetch_array($sqlRev))
  {
    
    $ids = explode(',',trim($sqlROW['fb_id'])); 
    foreach($ids as $k => $id) 
    {
        if(trim($id)!=''){
            $ad_acc[trim($id)] = $sqlROW['client_name'].' - ('.trim($id).')';
        }
    }
  }
  d($ad_acc);
  //exit;
  /*$ad_acc = array(
        427933624679014=>'Athens',
        1744658879060018=>'Boulevard',
        1196015513904250=>'Southbrooke',
        569001103516290=>'Aria',
        462105354650207=>'Jubliant',
        370327086813811=>'Firstcity',
        807092046796106=>'Flagship',
        735957617015640=>'PlatinumJoy',
        834297526950773=>'Majestica',
        1317725875075405=>'Zenith'
    
  );*/
  $src_text = array(
    'Facebook' => 'FB',
    'Reels' => 'RL',
    'Desktop' => 'DT',
    'Search' => 'SR',
    'Feed' => 'FD',
    'Mobile App' => 'Mb A',
    'Explore' => 'Ex',
    'Marketplace' => 'Mkpl',
    'story' => 'Sty',
    'Instant Article' => 'IN Art',
    'Stories' => 'Sty',
    'Overlay' => 'Ov',
    'Video' => 'V',
    'Instagram' => 'IG',
    'Unknown' => 'UKN');
 //$ad_acc = array( 569001103516290=>'Aria',1317725875075405=>'Zenith');
  
  if(!isset($_GET['act_id'])){
    $acc_id = 1782824538572369;
  } else {
    $acc_id = $_GET['act_id'];
  }
  
  $pg = 'placement-casa.php';
 
  foreach($ad_acc as $k => $val) 
  { 
    //$val = $acc_id;
    $url = "https://graph.facebook.com/".$api_ver."/act_".$k."/insights?level=campaign&breakdowns=publisher_platform,device_platform,platform_position&fields=adset_id,adset_name,reach,impressions,spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true);  
    $fb_data[$k] = $res['data'];
    echo $k .'<br>';
  }
  //d($fb_data);
  //exit;

  ?>
  <?php 
    $reach_tot = $impr_tot =  $cpl_tot = 0;
    $tbl_data2 = $spend_tot = $lead_tot = $lead_data = array();
    foreach($fb_data as $k_fb => $v_fb) 
    { 
        

        foreach($v_fb as $k => $val) {
            $lead[$k_fb] = $cpl[$k_fb] = 0;

            if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead[$k_fb] = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

            if($lead[$k_fb] !='-' || $lead[$k_fb] !=0) $cpl[$k_fb] = @($val['spend']/$lead[$k_fb]);
        
            //$reach_tot[$k_fb] += $val['reach'];
           // $impr_tot[$k_fb] += $val['impressions'];
            $lead_tot[$k_fb][] = $lead[$k_fb];
            $spend_tot[$k_fb][] = $val['spend'];
            //d($spend_tot); 
            $tbl_key2[$k_fb] = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
            $tbl_data2[$k_fb][$tbl_key2[$k_fb]][] = array('reach'=>$val['reach'],'impressions'=>$val['impressions'],'lead'=>$lead[$k_fb],'cpl'=>$cpl[$k_fb],'spend'=>$val['spend']);

            $lead_data[$k_fb][$tbl_key2[$k_fb]][] = $lead[$k_fb];
           // $lead_data[$k_fb][$tbl_key2[$k_fb]][] = $lead[$k_fb];

            $tbl_cols[] = $tbl_key2[$k_fb]; 
        }
        
    } 
    $maxLead = array();
    foreach($lead_data as $key => $val) {
       // d($val); exit;
        foreach($val as $k => $v) {
            $maxLead[$key][] = array_sum($v);
        }
       // $maxVal[$k] = array_sum($lead_data[$k][$val]);
    }
    //d($maxVal); exit;
    //d($tbl_data2); exit;
    //$value = array_sum(array_column($tbl_data2[1782824538572369], 'spend'));
   // d($value); exit;
   $tbl_cols = array_unique($tbl_cols);
   
   $sendEmail ='No';

   if(isset($_GET['today'])) {
        $tbl = '	<h3>CasaGrand - Instream Video Leads (Today)</h3>';
   } else {
        $tbl = '	<h3>CasaGrand - Instream Video Leads (Last 3 days)</h3>';
   }
   $tbl .= '<span class="font-italic percent">Reports on: <b>'.date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)).'</b>.<br> Reporting time: <b>'.date("d-m-Y, h:i a").'</b></span><br><br>';

   $tbl .= '	
		<table border="1" cellpadding="10" style="border-collapse: collapse; padding:10px;">
		  <tr>
          <th>Projects</th>
';

            foreach($tbl_cols as $k => $val) { 
                
                if($val=='facebook_#_instream_video_#_mobile_app') {
                    $v = explode('_#_',$val); 
               
                    $str = ucwords(str_replace('_',' ',$v[0])).' - '.ucwords(str_replace('_',' ',$v[1])).' - '.ucwords(str_replace('_',' ',$v[2]));
                    $str = str_replace(array_keys($src_text), $src_text, $str);
                    $tbl .= '	<th> Leads</th>';
                    $tbl .= '	<th>  % </th>';
                } 
             } 
             $tbl .= '</tr>';
            
            
            foreach($ad_acc as $k => $val)  { 
                //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
                $tbl .= '<tr>';
                $tbl .= '<td>'.$val.'</td>';
                foreach($tbl_cols as $k1 => $val2) { 
                    if($val2=='facebook_#_instream_video_#_mobile_app') {
                    $lead = 0; $max_lead = 0; $max_css ='';
                    if(isset($lead_data[$k][$val2])) { $lead = round(array_sum($lead_data[$k][$val2])); $max_lead = max($maxLead[$k]); }
                    if($lead==0) { $lead_per = $lead = '-';  } else { $lead_per = ' <span class="font-italic percent">'.round((($lead/array_sum($lead_tot[$k]))*100),1).' %</span>';}
                    if($max_lead!=0 && $max_lead==$lead) { $max_css = 'bg_green"'; $sendEmail ='Yes'; }
                    if( $max_lead!=0 && $max_lead==$lead ) { $bgcolor='#f97878'; } else { $bgcolor='#fff'; }
                    $tbl .= '<td style="background-color:'.$bgcolor.';text-align: right;">'.$lead.'</td>';
                    $tbl .= '<td style="background-color:'.$bgcolor.';text-align: right;">'.$lead_per.'</td>';
                 } 
                }
            $tbl .= '</tr>';
             }  
             $tbl .= '</table>'; 
echo  $tbl;

if($sendEmail == 'Yes') {
    require $dirPath.'email/vendor/autoload.php';
    include $dirPath.'email/config.php';
    $to_address = "prabhu@bytindia.com";
    if(isset($_GET['today'])) {
	    $subjLine = 'Instream Video Leads - Alert! (Today: '.date("d-m-Y", strtotime('today')).')'; 	
    } else {
        $subjLine = 'Instream Video Leads - Alert! (Last 3days)'; 
    }
	include $dirPath.'email/mail-casa.php';
}
?>
