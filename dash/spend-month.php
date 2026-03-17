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
        /* Desktop view (default): full width */
        .dataTables_scrollBody { width: 100% !important; }
        table.dataTable { width: 100% !important; }
        /* Mobile view: width auto so table grows with content, no scroll needed to view text */
        body.view-mobile .dataTables_scrollBody {
            width: auto !important;
            overflow-x: visible !important;
        }
        body.view-mobile table.dataTable {
            width: auto !important;
            table-layout: auto !important;
        }
        body.view-mobile .dataTables_wrapper .dataTables_scroll {
            overflow-x: visible !important;
        }
        body.view-mobile .dataTables_wrapper .dataTables_scrollHead,
        body.view-mobile .dataTables_wrapper .dataTables_scrollBody {
            overflow-x: visible !important;
        }
        /* Center table in mobile view */
        body.view-mobile [id^="keywordsTable-"] {
            display: block !important;
        }
        body.view-mobile .dataTables_wrapper {
            margin-left: auto !important;
            margin-right: auto !important;
            display: table !important;
            width: auto !important;
        }
        body.view-mobile .dataTables_wrapper .dataTables_scroll {
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .view-toggle-wrap { margin-top: 10px; }
        .view-toggle-wrap .btn { margin-right: 6px; }
        .view-toggle-wrap .btn.active { background: #5bc0de; color: #fff; border-color: #5bc0de; }
        table.dataTable thead .sorting:after {
    opacity: 0.2;
    content: "\e150";
    font-size: 10px;
}
    </style>
<?php 
if(isset($_GET['st']) && $_GET['st']!='') {
    $_SESSION['st'] = $_GET['st'];
	$_SESSION['en'] = $_GET['en'];
} else {
    $start = date('d/m/Y',strtotime('first day of this month'));
	$end = date('d/m/Y');
    $_SESSION['st'] = $start;
    $_SESSION['en'] = $end;
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

if(isset($_SESSION['st'])) { 
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
  
  $pg = 'spend-month.php';

// Convert to Y-m-d
$startDate = DateTime::createFromFormat('d-m-Y', $dtRange1);
$endDate = DateTime::createFromFormat('d-m-Y', $dtRange2);

if (!$startDate || !$endDate) {
    die('Invalid date format. Expected format: DD/MM/YYYY');
}

// Normalize to the first day of the month
$startDate->modify('first day of this month');
$endDate->modify('first day of next month'); // Include the end month

// Generate month list
$interval = new DateInterval('P1M'); // every 1 month
$period = new DatePeriod($startDate, $interval, $endDate);

foreach ($period as $dt) {
   // echo $dt->format('m') . "<br>"; // 01, 02, 03...
   $mon_period[] = $dt->format('Y').'-'.$dt->format('m');
}
//d($mon_period); exit;
//exit;
  ?>
  <body>
  <?php include 'menu-top.php'; ?>
  
  <br>
    <div class="report-header">
    <h4><?php echo $_SESSION['client_name']; ?> - Month on Month - Report </h4>

    <span class="font-italic percent">
        Reports on: 
        <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.
        Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b>
    </span>
    <div class="view-toggle-wrap">
        <button type="button" id="desktop-view-btn" class="btn btn-sm btn-default">Desktop view</button>
        <button type="button" id="mobile-view-btn" class="btn btn-sm btn-default">Mobile view</button>
    </div>
    </div>
  
  <?php
 
  /* -------------------------------------- META ---------------------------------*/

  //$proj_list = array('HANFORD','PLATINUM'); 

foreach ($mon_period as $mon_k => $mon_v) {
    //$val = $acc_id;
    
    $fb_data = array();
    foreach($fb_acc_ids as $k2 => $val2) {
      $url = "https://graph.facebook.com/".$api_ver."/act_".$val2."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($mon_v . "-01"))."&time_range[until]=".date("Y-m-t", strtotime($mon_v . "-01"))."&access_token=".$access_token."&limit=1750";
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
        $spend_tot_meta[$mon_k][$k][]=  $spend;
        $lead_tot_meta[$mon_k][$k][]=  $lead;
    }
    
    //d($spend_tot_meta); exit;

    //echo 'F-'.$mon_k.'<br>';
    //d($spend_tot_meta); d($lead_tot_meta); //exit;

  /* -------------------------------------- GOOGLE ---------------------------------*/



  //$val = $acc_id;
  $g_data = $g_proj_data = array();
  foreach($g_acc_ids as $k2 => $val2) {
    $adAccounts = [$val2];
    $g_data[$k2] = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, date("Y-m-d", strtotime($mon_v . "-01")), date("Y-m-t", strtotime($mon_v . "-01")), $extQry=''); 
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
      $spend_tot_google[$mon_k][$k][] =  $spend;
      $lead_tot_google[$mon_k][$k][]  =  $lead;
  }
  //echo 'G-'.$mon_k.'<br>';
  //d($spend_tot_google); d($lead_tot_google); //

}



$final_data_projectwise = [];
$grand_totals_projectwise = [];

foreach ($name_contain2 as $proj_key => $proj_name) {
    $final_data_projectwise[$proj_name] = [];
    $grand_totals_projectwise[$proj_name] = [
        'Spend' => ['meta' => 0, 'google' => 0, 'total' => 0],
        'Leads' => ['meta' => 0, 'google' => 0, 'total' => 0],
        'CPL'   => ['meta' => 0, 'google' => 0, 'total' => 0],
    ];

    $reversed_mon_period = array_reverse($mon_period, true);
    foreach ($reversed_mon_period as $mon_k => $mon_v) {
        $col_key = date('M - Y', strtotime($mon_v . "-01"));

        // Meta
        $meta_spend = isset($spend_tot_meta[$mon_k][$proj_key]) ? array_sum($spend_tot_meta[$mon_k][$proj_key]) : 0;
        $meta_leads = isset($lead_tot_meta[$mon_k][$proj_key]) ? array_sum($lead_tot_meta[$mon_k][$proj_key]) : 0;
        $meta_cpl = ($meta_leads > 0) ? round($meta_spend / $meta_leads) : 0;

        // Google
        $google_spend = isset($spend_tot_google[$mon_k][$proj_key]) ? array_sum($spend_tot_google[$mon_k][$proj_key]) : 0;
        $google_leads = isset($lead_tot_google[$mon_k][$proj_key]) ? array_sum($lead_tot_google[$mon_k][$proj_key]) : 0;
        $google_cpl = ($google_leads > 0) ? round($google_spend / $google_leads) : 0;

        // Total
        $total_spend = $meta_spend + $google_spend;
        $total_leads = $meta_leads + $google_leads;
        $total_cpl = ($total_leads > 0) ? round($total_spend / $total_leads) : 0;

        // Store data
        $final_data_projectwise[$proj_name]['Spend'][$col_key] = ['meta' => $meta_spend, 'google' => $google_spend, 'total' => $total_spend];
        $final_data_projectwise[$proj_name]['Leads'][$col_key] = ['meta' => $meta_leads, 'google' => $google_leads, 'total' => $total_leads];
        $final_data_projectwise[$proj_name]['CPL'][$col_key] = ['meta' => $meta_cpl, 'google' => $google_cpl, 'total' => $total_cpl];

        // Grand Totals
        $grand_totals_projectwise[$proj_name]['Spend']['meta'] += $meta_spend;
        $grand_totals_projectwise[$proj_name]['Spend']['google'] += $google_spend;
        $grand_totals_projectwise[$proj_name]['Spend']['total'] += $total_spend;

        $grand_totals_projectwise[$proj_name]['Leads']['meta'] += $meta_leads;
        $grand_totals_projectwise[$proj_name]['Leads']['google'] += $google_leads;
        $grand_totals_projectwise[$proj_name]['Leads']['total'] += $total_leads;
    }

    // Grand CPL Calculation
    $meta_leads = $grand_totals_projectwise[$proj_name]['Leads']['meta'];
    $google_leads = $grand_totals_projectwise[$proj_name]['Leads']['google'];
    $total_leads = $grand_totals_projectwise[$proj_name]['Leads']['total'];

    $meta_spend = $grand_totals_projectwise[$proj_name]['Spend']['meta'];
    $google_spend = $grand_totals_projectwise[$proj_name]['Spend']['google'];
    $total_spend = $grand_totals_projectwise[$proj_name]['Spend']['total'];

    $grand_totals_projectwise[$proj_name]['CPL']['meta'] = ($meta_leads > 0) ? round($meta_spend / $meta_leads) : 0;
    $grand_totals_projectwise[$proj_name]['CPL']['google'] = ($google_leads > 0) ? round($google_spend / $google_leads) : 0;
    $grand_totals_projectwise[$proj_name]['CPL']['total'] = ($total_leads > 0) ? round($total_spend / $total_leads) : 0;
}




?>
  <?php foreach ($final_data_projectwise as $proj_name => $final_data): ?>
    <?php if($proj_name!='no') { ?><h4><?php $prjK = array_search($proj_name, $name_contain2); echo $proj_names[$prjK]; ?></h4><?php } ?>
    <span id="keywordsTable-<?php echo $proj_name; ?>">
    <table class="table table-bordered text-center">
        <thead>
            <tr>
                <th rowspan="2">Metric</th>
                <?php foreach (array_keys($final_data['Spend']) as $col): ?>
                    <th colspan="3"><?= $col ?></th>
                <?php endforeach; ?>
                <th colspan="3">Grand Total</th>
            </tr>
            <tr>
                <?php foreach ($final_data['Spend'] as $group): ?>
                    <th>Meta</th><th>Google</th><th>Total</th>
                <?php endforeach; ?>
                <th>Meta</th><th>Google</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($final_data as $metric => $groups): ?>
                <tr>
                    <td><b><?= $metric ?></b></td>
                    <?php foreach ($groups as $data): ?>
                        <?php if ($metric === 'CPL'): ?>
                            <td><?= number_format($data['meta']) ?></td>
                            <td><?= number_format($data['google']) ?></td>
                            <td><?= number_format($data['total']) ?></td>
                        <?php else: ?>
                            <td><?= moneyFormatIndia($data['meta']) ?></td>
                            <td><?= moneyFormatIndia($data['google']) ?></td>
                            <td><?= moneyFormatIndia($data['total']) ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($metric === 'CPL'): ?>
                        <td><b><?= number_format($grand_totals_projectwise[$proj_name][$metric]['meta']) ?></b></td>
                        <td><b><?= number_format($grand_totals_projectwise[$proj_name][$metric]['google']) ?></b></td>
                        <td><b><?= number_format($grand_totals_projectwise[$proj_name][$metric]['total']) ?></b></td>
                    <?php else: ?>
                        <td><b><?= moneyFormatIndia($grand_totals_projectwise[$proj_name][$metric]['meta']) ?></b></td>
                        <td><b><?= moneyFormatIndia($grand_totals_projectwise[$proj_name][$metric]['google']) ?></b></td>
                        <td><b><?= moneyFormatIndia($grand_totals_projectwise[$proj_name][$metric]['total']) ?></b></td>
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
changeModalTitle("MoM Report: <?php echo $_SESSION['client_name']; ?>");

// Crop canvas to content bounds (remove empty/black space around table)
function cropCanvasToContent(canvas) {
    var ctx = canvas.getContext('2d');
    var w = canvas.width, h = canvas.height;
    var imgData = ctx.getImageData(0, 0, w, h);
    var data = imgData.data;
    var minX = w, minY = h, maxX = 0, maxY = 0;
    for (var y = 0; y < h; y++) {
        for (var x = 0; x < w; x++) {
            var i = (y * w + x) * 4;
            var a = data[i + 3];
            var r = data[i], g = data[i + 1], b = data[i + 2];
            var empty = (a < 15) || (a > 200 && r < 15 && g < 15 && b < 15);
            if (!empty) {
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x > maxX) maxX = x;
                if (y > maxY) maxY = y;
            }
        }
    }
    var cw = Math.max(1, maxX - minX + 1);
    var ch = Math.max(1, maxY - minY + 1);
    var out = document.createElement('canvas');
    out.width = cw;
    out.height = ch;
    out.getContext('2d').drawImage(canvas, minX, minY, cw, ch, 0, 0, cw, ch);
    return out;
}

// Copy functionality: header + body, no empty space – crop to table content only
<?php foreach ($final_data_projectwise as $proj_name => $final_data): ?>
document.getElementById('copyTableImage-<?php echo $proj_name; ?>').addEventListener('click', function() {
    var wrapper = document.getElementById('keywordsTable-<?php echo $proj_name; ?>');
    if (!wrapper) return;
    var scrollWrap = wrapper.querySelector('.dataTables_scroll');
    var target = scrollWrap || wrapper;
    if (!scrollWrap) {
        var tableEl = wrapper.querySelector('table');
        if (tableEl && tableEl.getElementsByTagName('tbody').length) target = tableEl;
    }
    html2canvas(target, {
        backgroundColor: null,
        scale: 2,
        useCORS: true,
        logging: false,
        onclone: function(clonedDoc, node) {
            if (node.querySelector) {
                var tables = node.querySelectorAll('table');
                for (var t = 0; t < tables.length; t++) {
                    tables[t].style.margin = '0';
                    tables[t].style.padding = '0';
                }
            }
        }
    }).then(function(canvas) {
        var cropped = cropCanvasToContent(canvas);
        cropped.toBlob(function(blob) {
            if (navigator.clipboard && window.ClipboardItem) {
                var item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function() {
                    alert('Table image copied to clipboard!');
                }, function(err) {
                    alert('Failed to copy image: ' + err);
                });
            } else {
                var url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            }
        });
    });
});
<?php endforeach; ?>
</script>
<script>
$(function() {
    var isMobile = localStorage.getItem('spends-table-view') === 'mobile';
    if (isMobile) {
        $('body').addClass('view-mobile');
        $('#mobile-view-btn').addClass('active');
        $('#desktop-view-btn').removeClass('active');
    } else {
        $('body').removeClass('view-mobile');
        $('#desktop-view-btn').addClass('active');
        $('#mobile-view-btn').removeClass('active');
    }
    $('#desktop-view-btn').on('click', function() {
        $('body').removeClass('view-mobile');
        $('#desktop-view-btn').addClass('active');
        $('#mobile-view-btn').removeClass('active');
        localStorage.setItem('spends-table-view', 'desktop');
        $('.table').each(function() {
            var dt = $(this).DataTable();
            if (dt) dt.columns.adjust();
        });
    });
    $('#mobile-view-btn').on('click', function() {
        $('body').addClass('view-mobile');
        $('#mobile-view-btn').addClass('active');
        $('#desktop-view-btn').removeClass('active');
        localStorage.setItem('spends-table-view', 'mobile');
        $('.table').each(function() {
            var dt = $(this).DataTable();
            if (dt) dt.columns.adjust();
        });
    });
});
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