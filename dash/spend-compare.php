<!-- Include Required Prerequisites -->
<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  

if(!isset($_GET['tbl_id']) && $_GET['tbl_id']=='') {
	$pg = 'login.php';
	$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	exit();
} else {
    $tbl_id = $_GET['tbl_id'];
}
//print_r($_SESSION);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<Title>AdRescue - Ads </Title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">

<!-- jQuery -->
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
   <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js" charset="utf-8"></script>
    <!-- Custom Theme Scripts -->
    
    <link href="//cdn.datatables.net/buttons/1.5.6/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="/web/pagination.css" rel="stylesheet">
    <link href="/assets/css/pagination.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/casa/css/style.css" />
    <link rel="stylesheet" type="text/css" href="/casa/style.css" />
    <style>
        th:first-child, td:first-child, stickyCls
        {
            position:sticky;
            left:0px;
            background-color: #fff;
        }
        h2 { text-align: center; }
        td {
            background-color: #fff;
        }
    </style>
<?php 
// Handle two date ranges for comparison
if(isset($_GET['st1']) && $_GET['st1']!='' && isset($_GET['en1']) && $_GET['en1']!='') {
    $_SESSION['st1'] = $_GET['st1'];
    $_SESSION['en1'] = $_GET['en1'];
} else {
    $start1 = date('d/m/Y',strtotime('first day of this month'));
    $end1 = date('d/m/Y',strtotime('last day of this month'));
    $_SESSION['st1'] = $start1;
    $_SESSION['en1'] = $end1;
}

if(isset($_GET['st2']) && $_GET['st2']!='' && isset($_GET['en2']) && $_GET['en2']!='') {
    $_SESSION['st2'] = $_GET['st2'];
    $_SESSION['en2'] = $_GET['en2'];
} else {
    $start2 = date('d/m/Y',strtotime('first day of last month'));
    $end2 = date('d/m/Y',strtotime('last day of last month'));
    $_SESSION['st2'] = $start2;
    $_SESSION['en2'] = $end2;
}


include '../db.php';
$pg='facebook';
include 'config.php';
include 'google-campaigns.php';
$oauthCredentials = [
    'client_id' => '1085049385463-74om7sd3sfm2aad216q7a6ejtodetgfl.apps.googleusercontent.com',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
    'refresh_token' => $_SESSION['g_refresh_token'],
    'developer_token' => getenv('GOOGLE_DEVELOPER_TOKEN')
];
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

function getFriendlyDateRange($startDate, $endDate) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $thisMonthStart = date('Y-m-01');
    $thisMonthEnd = date('Y-m-t');
    $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
    
    // Convert dates to Y-m-d format for comparison
    $start = date('Y-m-d', strtotime(str_replace('/', '-', $startDate)));
    $end = date('Y-m-d', strtotime(str_replace('/', '-', $endDate)));
    
    if ($start === $today && $end === $today) {
        return 'Today';
    } elseif ($start === $yesterday && $end === $yesterday) {
        return 'Yesterday';
    } elseif ($start === $thisMonthStart && $end === $thisMonthEnd) {
        return 'This month';
    } elseif ($start === $lastMonthStart && $end === $lastMonthEnd) {
        return 'Last month';
    } else {
        // Return original formatted dates for custom ranges
        return date("d-m-Y", strtotime(str_replace('/', '-', $startDate))).' to '.date("d-m-Y", strtotime(str_replace('/', '-', $endDate)));
    }
}

// Process Date Range 1
if(isset($_SESSION['st1'])) { 
    $stDt1 = $_SESSION['st1']; 
    $enDt1 = $_SESSION['en1']; 
    $dtRange1_start = str_replace('/', '-', $_SESSION['st1']); 
    $dtRange1_end = str_replace('/', '-', $_SESSION['en1']);
    } else { 
    $stDt1 = date('d/m/Y',strtotime('first day of this month'));
    $enDt1 = date('d/m/Y',strtotime('last day of this month'));
    $dtRange1_start = date('Y-m-d',strtotime('first day of this month')); 
    $dtRange1_end = date('Y-m-d',strtotime('last day of this month'));
}

// Process Date Range 2
if(isset($_SESSION['st2'])) { 
    $stDt2 = $_SESSION['st2']; 
    $enDt2 = $_SESSION['en2']; 
    $dtRange2_start = str_replace('/', '-', $_SESSION['st2']); 
    $dtRange2_end = str_replace('/', '-', $_SESSION['en2']);
} else { 
    $stDt2 = date('d/m/Y',strtotime('first day of last month'));
    $enDt2 = date('d/m/Y',strtotime('last day of last month'));
    $dtRange2_start = date('Y-m-d',strtotime('first day of last month')); 
    $dtRange2_end = date('Y-m-d',strtotime('last day of last month'));
  }
  $obj_arr = array(
                    //'POST_ENGAGEMENT' => 'post_engagement', 
                    //'LINK_CLICKS' => 'link_click',
                    //'VIDEO_VIEWS' => 'video_view',
                    'LEAD_GENERATION' => 'lead', 
                    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
                    'MESSAGES' => 'onsite_conversion.messaging_block',
                    'OUTCOME_LEADS' => 'lead',
                    'OUTCOME_SALES' => 'purchase',
                    'PRODUCT_CATALOG_SALES' => 'purchase'
                );
  //echo "time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2)); exit;
  
 //$ad_acc = array( 807092046796106=>'Flagship',1744658879060018=>'Boulevard');
  
  if(!isset($_GET['act_id'])){
    $acc_id = 1782824538572369;
  } else {
    $acc_id = $_GET['act_id'];
  }
  
  $pg = 'spend-compare.php';

// Convert to Y-m-d for Date Range 1
$startDate1 = DateTime::createFromFormat('d-m-Y', $dtRange1_start);
$endDate1 = DateTime::createFromFormat('d-m-Y', $dtRange1_end);

// Convert to Y-m-d for Date Range 2
$startDate2 = DateTime::createFromFormat('d-m-Y', $dtRange2_start);
$endDate2 = DateTime::createFromFormat('d-m-Y', $dtRange2_end);

if (!$startDate1 || !$endDate1 || !$startDate2 || !$endDate2) {
    die('Invalid date format. Expected format: DD/MM/YYYY');
}

// Use exact date ranges instead of month periods
$dateRange1_start = $startDate1->format('Y-m-d');
$dateRange1_end = $endDate1->format('Y-m-d');
$dateRange2_start = $startDate2->format('Y-m-d');
$dateRange2_end = $endDate2->format('Y-m-d');
//d($mon_period); exit;
//exit;
  ?>
  <body>
  <?php include 'menu-top2.php'; ?>
  
  <br>
    <div class="report-header">
    <h4><?php echo $_SESSION['client_name']; ?> - Ads Report Comparison </h4>

    <span class="font-italic percent">
        Reports on: 
        <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.
        Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b>
    </span>
    </div>
  <?php
 
  /* -------------------------------------- META - DATE RANGE 1 ---------------------------------*/

// Fetch data for exact Date Range 1
    $fb_data = array();
    foreach($fb_acc_ids as $k2 => $val2) {
  $url = "https://graph.facebook.com/".$api_ver."/act_".$val2."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$dateRange1_start."&time_range[until]=".$dateRange1_end."&access_token=".$access_token."&limit=1750";
      $req = file_get_contents_curl($url);
      $res = json_decode($req, true);  
      if(isset($res['data'])){
          $fb_data[$k2] = $res['data'];
      }
    }

    $fb_proj_data = array();
    foreach($fb_data as $k_fb => $v_fb) 
    {
        foreach($fb_data[$k_fb] as $k1 => $v1) 
        {
            if(count($name_contain)>0){
                $projKey = contains_proj($v1['campaign_name']);
            } else {
                $projKey = contains_proj2($v1['campaign_name']);
            }
            
            if($projKey!=''){
                $fb_proj_data[$projKey][] = $v1;
            }
        }
    }
    
  ?>
  
  <?php 
    $reach_tot = $impr_tot =  $cpl_tot = 0;
    $tbl_data2 = $spend_tot = $lead_tot = $lead_data = array();
    foreach($fb_proj_data as $k_fb => $v_fb) 
    { 
        foreach($v_fb as $k => $val) {
             $lead = $cpl = $lead_con = 0;

            if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }
            if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead_con = LeadGen($val['actions'], 'offsite_conversion.fb_pixel_lead'); } }
            if($lead!=0) { $lead = $lead - $lead_con; }
            if(isset($lead) && $lead>0 && ($lead !='-' || $lead !=0)) $cpl = @($val['spend']/$lead);
            $lead_tot[$k_fb][] = $lead;
            $spend_tot[$k_fb][] = $val['spend'];
        }
        
    } 
    //d($spend_tot);
    $tot_spend = $tot_lead = $tot_cpl = 0;
    if(isset($fb_proj_data['no']) && count($fb_proj_data['no'])>0) { $name_contain2=array('no'=>'no'); }
    foreach($name_contain2 as $k => $val)  { 
        //echo  $k .'--'. $val;
        $spend = $lead = 0;
        //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
        if(isset($spend_tot[$k])) { $spend = round(array_sum($spend_tot[$k])); }
        if(isset($lead_tot[$k])) { $lead = round(array_sum($lead_tot[$k])); }
        $spend_tot_meta1[0][$k][]=  $spend;
        $lead_tot_meta1[0][$k][]=  $lead;
    }
    
    //d($spend_tot_meta); exit;

    //echo 'F-'.$mon_k.'<br>';
    //d($spend_tot_meta); d($lead_tot_meta); //exit;

  /* -------------------------------------- GOOGLE - DATE RANGE 1 ---------------------------------*/

  //$val = $acc_id;
  $g_data = $g_proj_data = array();
  foreach($g_acc_ids as $k2 => $val2) {
    $adAccounts = [$val2];
    $g_data[$k2] = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $dateRange1_start, $dateRange1_end, $extQry=''); 
  }
  //d($g_data);
  foreach($g_data as $k_fb => $v_fb) 
  {
      foreach($g_data[$k_fb] as $k1 => $v1) 
      {
        if(count($name_contain)>0){
          $projKey = contains_proj($v1['camp_name']);
        } else {
            $projKey = contains_proj2($v1['camp_name']);
        }
          if($projKey!=''){
              $g_proj_data[$projKey][] = $v1;
          }
          //d($v1); exit;
      }
  }

  $reach_tot = $impr_tot =  $cpl_tot = 0;
  $tbl_data2 = $spend_tot = $lead_tot = $lead_data = array();
  foreach($g_proj_data as $k_fb => $v_fb) 
  { 
      foreach($v_fb as $k => $val) {
           $lead = $cpl = 0;

          $lead = $val['conv'];

          if(isset($lead) && $lead>0 && ($lead !='-' || $lead !=0)) $cpl = @($val['cost']/$lead);
          $lead_tot[$k_fb][] = $lead;
          $spend_tot[$k_fb][] = $val['cost'];
      }
      
  } 

  $tot_spend = $tot_lead = $tot_cpl = 0;
  if(isset($g_proj_data['no']) && count($g_proj_data['no'])>0) { $name_contain2=array('no'=>'no');  }
  foreach($name_contain2 as $k => $val)  { 
      $spend = $lead = 0;
      //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
      if(isset($spend_tot[$k])) { $spend = round(array_sum($spend_tot[$k])); }
      if(isset($lead_tot[$k])) { $lead = round(array_sum($lead_tot[$k])); }
      $spend_tot_google1[0][$k][] =  $spend;
      $lead_tot_google1[0][$k][]  =  $lead;
  }
  //echo 'G-'.$mon_k.'<br>';
  //d($spend_tot_google); d($lead_tot_google); //

/* -------------------------------------- META - DATE RANGE 2 ---------------------------------*/

// Fetch data for exact Date Range 2
$fb_data = array();
foreach($fb_acc_ids as $k2 => $val2) {
  $url = "https://graph.facebook.com/".$api_ver."/act_".$val2."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$dateRange2_start."&time_range[until]=".$dateRange2_end."&access_token=".$access_token."&limit=1750";
  $req = file_get_contents_curl($url);
  $res = json_decode($req, true);  
  if(isset($res['data'])){
      $fb_data[$k2] = $res['data'];
  }
}

    $fb_proj_data = array();
    foreach($fb_data as $k_fb => $v_fb) 
    {
        foreach($fb_data[$k_fb] as $k1 => $v1) 
        {
            if(count($name_contain)>0){
                $projKey = contains_proj($v1['campaign_name']);
            } else {
                $projKey = contains_proj2($v1['campaign_name']);
            }
            
            if($projKey!=''){
                $fb_proj_data[$projKey][] = $v1;
            }
        }
    }
    
  ?> 
  <?php 
    $reach_tot = $impr_tot =  $cpl_tot = 0;
    $tbl_data2 = $spend_tot = $lead_tot = $lead_data = array();
    foreach($fb_proj_data as $k_fb => $v_fb) 
    { 
        foreach($v_fb as $k => $val) {
             $lead = $cpl = $lead_con = 0;

            if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }
            if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead_con = LeadGen($val['actions'], 'offsite_conversion.fb_pixel_lead'); } }
            if($lead!=0) { $lead = $lead - $lead_con; }
            if(isset($lead) && $lead>0 && ($lead !='-' || $lead !=0)) $cpl = @($val['spend']/$lead);
            $lead_tot[$k_fb][] = $lead;
            $spend_tot[$k_fb][] = $val['spend'];
        }
        
    } 
    //d($spend_tot);
    $tot_spend = $tot_lead = $tot_cpl = 0;
    if(isset($fb_proj_data['no']) && count($fb_proj_data['no'])>0) { $name_contain2=array('no'=>'no'); }
    foreach($name_contain2 as $k => $val)  { 
        //echo  $k .'--'. $val;
        $spend = $lead = 0;
        //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
        if(isset($spend_tot[$k])) { $spend = round(array_sum($spend_tot[$k])); }
        if(isset($lead_tot[$k])) { $lead = round(array_sum($lead_tot[$k])); }
        $spend_tot_meta2[0][$k][]=  $spend;
        $lead_tot_meta2[0][$k][]=  $lead;
    }

  /* -------------------------------------- GOOGLE - DATE RANGE 2 ---------------------------------*/

  //$val = $acc_id;
  $g_data = $g_proj_data = array();
  foreach($g_acc_ids as $k2 => $val2) {
    $adAccounts = [$val2];
    $g_data[$k2] = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $dateRange2_start, $dateRange2_end, $extQry=''); 
  }
  //d($g_data);
  foreach($g_data as $k_fb => $v_fb) 
  {
      foreach($g_data[$k_fb] as $k1 => $v1) 
      {
        if(count($name_contain)>0){
          $projKey = contains_proj($v1['camp_name']);
        } else {
            $projKey = contains_proj2($v1['camp_name']);
        }
          if($projKey!=''){
              $g_proj_data[$projKey][] = $v1;
          }
          //d($v1); exit;
      }
  }

  $reach_tot = $impr_tot =  $cpl_tot = 0;
  $tbl_data2 = $spend_tot = $lead_tot = $lead_data = array();
  foreach($g_proj_data as $k_fb => $v_fb) 
  { 
      foreach($v_fb as $k => $val) {
           $lead = $cpl = 0;

          $lead = $val['conv'];

          if(isset($lead) && $lead>0 && ($lead !='-' || $lead !=0)) $cpl = @($val['cost']/$lead);
          $lead_tot[$k_fb][] = $lead;
          $spend_tot[$k_fb][] = $val['cost'];
      }
      
  } 

  $tot_spend = $tot_lead = $tot_cpl = 0;
  if(isset($g_proj_data['no']) && count($g_proj_data['no'])>0) { $name_contain2=array('no'=>'no');  }
  foreach($name_contain2 as $k => $val)  { 
      $spend = $lead = 0;
      //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
      if(isset($spend_tot[$k])) { $spend = round(array_sum($spend_tot[$k])); }
      if(isset($lead_tot[$k])) { $lead = round(array_sum($lead_tot[$k])); }
      $spend_tot_google2[0][$k][] =  $spend;
      $lead_tot_google2[0][$k][]  =  $lead;
  }

// Calculate totals for each date range
$comparison_data = [];

foreach ($name_contain2 as $proj_key => $proj_name) {
    $comparison_data[$proj_name] = [
        'Date Range 1' => [
            'Spend' => ['meta' => 0, 'google' => 0, 'total' => 0],
            'Leads' => ['meta' => 0, 'google' => 0, 'total' => 0],
            'CPL'   => ['meta' => 0, 'google' => 0, 'total' => 0],
        ],
        'Date Range 2' => [
            'Spend' => ['meta' => 0, 'google' => 0, 'total' => 0],
            'Leads' => ['meta' => 0, 'google' => 0, 'total' => 0],
            'CPL'   => ['meta' => 0, 'google' => 0, 'total' => 0],
        ],
        'Difference' => [
            'Spend' => ['meta' => 0, 'google' => 0, 'total' => 0],
            'Leads' => ['meta' => 0, 'google' => 0, 'total' => 0],
            'CPL'   => ['meta' => 0, 'google' => 0, 'total' => 0],
        ],
        'Percentage Change' => [
        'Spend' => ['meta' => 0, 'google' => 0, 'total' => 0],
        'Leads' => ['meta' => 0, 'google' => 0, 'total' => 0],
        'CPL'   => ['meta' => 0, 'google' => 0, 'total' => 0],
        ]
    ];

    // Calculate totals for Date Range 1
    // Meta
    $meta_spend1 = isset($spend_tot_meta1[0][$proj_key]) ? array_sum($spend_tot_meta1[0][$proj_key]) : 0;
    $meta_leads1 = isset($lead_tot_meta1[0][$proj_key]) ? array_sum($lead_tot_meta1[0][$proj_key]) : 0;
    
    // Google
    $google_spend1 = isset($spend_tot_google1[0][$proj_key]) ? array_sum($spend_tot_google1[0][$proj_key]) : 0;
    $google_leads1 = isset($lead_tot_google1[0][$proj_key]) ? array_sum($lead_tot_google1[0][$proj_key]) : 0;

    $comparison_data[$proj_name]['Date Range 1']['Spend']['meta'] = $meta_spend1;
    $comparison_data[$proj_name]['Date Range 1']['Spend']['google'] = $google_spend1;
    $comparison_data[$proj_name]['Date Range 1']['Leads']['meta'] = $meta_leads1;
    $comparison_data[$proj_name]['Date Range 1']['Leads']['google'] = $google_leads1;

    // Calculate totals for Date Range 2
    // Meta
    $meta_spend2 = isset($spend_tot_meta2[0][$proj_key]) ? array_sum($spend_tot_meta2[0][$proj_key]) : 0;
    $meta_leads2 = isset($lead_tot_meta2[0][$proj_key]) ? array_sum($lead_tot_meta2[0][$proj_key]) : 0;
    
    // Google
    $google_spend2 = isset($spend_tot_google2[0][$proj_key]) ? array_sum($spend_tot_google2[0][$proj_key]) : 0;
    $google_leads2 = isset($lead_tot_google2[0][$proj_key]) ? array_sum($lead_tot_google2[0][$proj_key]) : 0;

    $comparison_data[$proj_name]['Date Range 2']['Spend']['meta'] = $meta_spend2;
    $comparison_data[$proj_name]['Date Range 2']['Spend']['google'] = $google_spend2;
    $comparison_data[$proj_name]['Date Range 2']['Leads']['meta'] = $meta_leads2;
    $comparison_data[$proj_name]['Date Range 2']['Leads']['google'] = $google_leads2;

    // Calculate totals
    $comparison_data[$proj_name]['Date Range 1']['Spend']['total'] = $comparison_data[$proj_name]['Date Range 1']['Spend']['meta'] + $comparison_data[$proj_name]['Date Range 1']['Spend']['google'];
    $comparison_data[$proj_name]['Date Range 1']['Leads']['total'] = $comparison_data[$proj_name]['Date Range 1']['Leads']['meta'] + $comparison_data[$proj_name]['Date Range 1']['Leads']['google'];
    
    $comparison_data[$proj_name]['Date Range 2']['Spend']['total'] = $comparison_data[$proj_name]['Date Range 2']['Spend']['meta'] + $comparison_data[$proj_name]['Date Range 2']['Spend']['google'];
    $comparison_data[$proj_name]['Date Range 2']['Leads']['total'] = $comparison_data[$proj_name]['Date Range 2']['Leads']['meta'] + $comparison_data[$proj_name]['Date Range 2']['Leads']['google'];

    // Calculate CPL
    $comparison_data[$proj_name]['Date Range 1']['CPL']['meta'] = ($comparison_data[$proj_name]['Date Range 1']['Leads']['meta'] > 0) ? round($comparison_data[$proj_name]['Date Range 1']['Spend']['meta'] / $comparison_data[$proj_name]['Date Range 1']['Leads']['meta']) : 0;
    $comparison_data[$proj_name]['Date Range 1']['CPL']['google'] = ($comparison_data[$proj_name]['Date Range 1']['Leads']['google'] > 0) ? round($comparison_data[$proj_name]['Date Range 1']['Spend']['google'] / $comparison_data[$proj_name]['Date Range 1']['Leads']['google']) : 0;
    $comparison_data[$proj_name]['Date Range 1']['CPL']['total'] = ($comparison_data[$proj_name]['Date Range 1']['Leads']['total'] > 0) ? round($comparison_data[$proj_name]['Date Range 1']['Spend']['total'] / $comparison_data[$proj_name]['Date Range 1']['Leads']['total']) : 0;

    $comparison_data[$proj_name]['Date Range 2']['CPL']['meta'] = ($comparison_data[$proj_name]['Date Range 2']['Leads']['meta'] > 0) ? round($comparison_data[$proj_name]['Date Range 2']['Spend']['meta'] / $comparison_data[$proj_name]['Date Range 2']['Leads']['meta']) : 0;
    $comparison_data[$proj_name]['Date Range 2']['CPL']['google'] = ($comparison_data[$proj_name]['Date Range 2']['Leads']['google'] > 0) ? round($comparison_data[$proj_name]['Date Range 2']['Spend']['google'] / $comparison_data[$proj_name]['Date Range 2']['Leads']['google']) : 0;
    $comparison_data[$proj_name]['Date Range 2']['CPL']['total'] = ($comparison_data[$proj_name]['Date Range 2']['Leads']['total'] > 0) ? round($comparison_data[$proj_name]['Date Range 2']['Spend']['total'] / $comparison_data[$proj_name]['Date Range 2']['Leads']['total']) : 0;

    // Calculate differences and percentage changes
    foreach (['meta', 'google', 'total'] as $platform) {
        foreach (['Spend', 'Leads', 'CPL'] as $metric) {
            $val1 = $comparison_data[$proj_name]['Date Range 1'][$metric][$platform];
            $val2 = $comparison_data[$proj_name]['Date Range 2'][$metric][$platform];
            
            $comparison_data[$proj_name]['Difference'][$metric][$platform] = $val1 - $val2;
            
            if ($val2 != 0) {
                $comparison_data[$proj_name]['Percentage Change'][$metric][$platform] = round((($val1 - $val2) / $val2) * 100, 2);
            } else {
                $comparison_data[$proj_name]['Percentage Change'][$metric][$platform] = $val1 > 0 ? 100 : 0;
            }
        }
    }
}




?>
  <?php foreach ($comparison_data as $proj_name => $data): ?>
    <?php if($proj_name!='no') { ?><h4><?php $prjK = array_search($proj_name, $name_contain2); echo $proj_names[$prjK]; ?></h4><?php } ?>
    <span id="keywordsTable-<?php echo $proj_name; ?>">
    <table class="table table-bordered text-center">
        <thead>
            <tr>
                <th rowspan="2"><?php echo $_SESSION['client_name']; ?></th>
                <th colspan="3"><i class="fa fa-calendar" style="color:white; margin-right:8px;"></i> <?php echo getFriendlyDateRange($stDt1, $enDt1); ?></th>
                <th colspan="3"><i class="fa fa-calendar" style="color:white; margin-right:8px;"></i> <?php echo getFriendlyDateRange($stDt2, $enDt2); ?></th>
            </tr>
            <tr>
                    <th>Meta</th><th>Google</th><th>Total</th>
                <th>Meta</th><th>Google</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (['Spend', 'Leads', 'CPL'] as $metric): ?>
                <tr>
                    <td style="text-align: center;"><b><?= $metric ?></b></td>
                    
                    <!-- Date Range 1 -->
                        <?php if ($metric === 'CPL'): ?>
                        <td><?= number_format($data['Date Range 1'][$metric]['meta']) ?></td>
                        <td><?= number_format($data['Date Range 1'][$metric]['google']) ?></td>
                        <td><?= number_format($data['Date Range 1'][$metric]['total']) ?></td>
                        <?php else: ?>
                        <td><?= moneyFormatIndia($data['Date Range 1'][$metric]['meta']) ?></td>
                        <td><?= moneyFormatIndia($data['Date Range 1'][$metric]['google']) ?></td>
                        <td><?= moneyFormatIndia($data['Date Range 1'][$metric]['total']) ?></td>
                        <?php endif; ?>

                    <!-- Date Range 2 -->
                    <?php if ($metric === 'CPL'): ?>
                        <td><?= number_format($data['Date Range 2'][$metric]['meta']) ?></td>
                        <td><?= number_format($data['Date Range 2'][$metric]['google']) ?></td>
                        <td><?= number_format($data['Date Range 2'][$metric]['total']) ?></td>
                    <?php else: ?>
                        <td><?= moneyFormatIndia($data['Date Range 2'][$metric]['meta']) ?></td>
                        <td><?= moneyFormatIndia($data['Date Range 2'][$metric]['google']) ?></td>
                        <td><?= moneyFormatIndia($data['Date Range 2'][$metric]['total']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </span>
    <div style="text-align: right; margin-top: 10px;">
        <button id="copyTableImage-<?php echo $proj_name; ?>" class="btn btn-info" style="margin-left: 8px;">Copy Table as Image</button>
    </div>
    <hr>
<?php endforeach; ?>

<br>



<?php 
 
//include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
function changeModalTitle(newTitle) {
  if (window.parent && window.parent.document) {
    let modalTitle = window.parent.document.querySelector('#iframeModalLabel');
    if (modalTitle) {
      modalTitle.textContent = newTitle;
    }
  }
}
changeModalTitle("Report Comparison: <?php echo $_SESSION['client_name']; ?>");
// Copy functionality for all tables
<?php foreach ($comparison_data as $proj_name => $data): ?>
document.getElementById('copyTableImage-<?php echo $proj_name; ?>').addEventListener('click', function() {
    const table = document.getElementById('keywordsTable-<?php echo $proj_name; ?>');
    html2canvas(table, {backgroundColor: null, scale: 8}).then(function(canvas) {
        canvas.toBlob(function(blob) {
            if (navigator.clipboard && window.ClipboardItem) {
                // Copy to clipboard as image
                const item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function() {
                    alert('Table image copied to clipboard!');
                }, function(err) {
                    alert('Failed to copy image: ' + err);
                });
            } else {
                // Fallback: open image in new tab
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            }
        });
    });
});
<?php endforeach; ?>
</script>
<script>
   $(function() {
        
        $('.table').dataTable({
            "ordering": true,
            "lengthMenu": [25, 50, 100, 150, 200, 500],
            "pageLength": 50,
            scrollX: true,
            "autoWidth": false,
            "bLengthChange": false,
            "bSearch": false,
            "searching": false,
            "bInfo": false,
            "info": false,
            "bPaginate": false,
            "paging": false,
            "order": [], // disables initial sort
            /*initComplete: function () {
                let api = this.api();
                api.columns(':gt(3)').every(function () {
                    let col = this.index();
                    let data = this.data()
                        .unique()
                        .map(function (value) {
                            return parseInt(value);
                        })
                        .toArray()
                        .sort(function (a, b) {
                            return b - a;
                        });
                    let length = data.length;
                    api.cells(null, col).every(function () {
                        let cell = parseInt(this.data());
                        $(this.node()).html(cell+'_'+data[0]);
                        if (cell === data[0]) {
                            $(this.node()).css("background-color", "rgb(172, 240, 172)");
                        } else if (cell === data[length - 1]) {
                            $(this.node()).css("background-color", "#fff");
                        }
                    });
                });
            }*/
        });
});
</script>
<script>
$(document).ready(function() {
$('[data-toggle="tooltip"]').tooltip();
	
var defSt = '01/01/2018';
var defEnd = '01/01/2024';
var d1 = '<?php echo $_SESSION['st']; ?>';
var d2 = '<?php echo $_SESSION['en']; ?>';
var start = moment(d1.split(' ')[0].split("/").reverse().join("-"));
var end = moment(d2.split(' ')[0].split("/").reverse().join("-"));
$('#reportrange span').html(d1 + ' - ' + d2);
        $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                'Last 7 Days': [moment().subtract('days', 6), moment()],
                'Last 30 Days': [moment().subtract('days', 29), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
            },
            opens: 'left',
            buttonClasses: ['btn btn-default'],
            applyClass: 'btn-small btn-primary',
            cancelClass: 'btn-small',
            format: 'DD/MM/YYYY',
            separator: ' to ',
            locale: {
                applyLabel: 'Submit',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom Range',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: 1
            }
        },
        function(start, end) {
            if(start.format('DD/MM/YYYY')==defSt) {
                console.log("Callback has been called!");
                $('#reportrange span').html(''); 
                $('#stDt_upd').val('');
                $('#enDt_upd').val('');
                window.location = 'loading.php?pg=spend-month.php&tbl_id=<?php echo $tbl_id; ?>';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=spend-month.php&tbl_id=<?php echo $tbl_id; ?>&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );


});
					
	</script>