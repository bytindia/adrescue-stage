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
 }else {
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
        table, td, strong { font-size: 17px; }
        table.table td {
            text-align: center; 
        }
        td {
            background-color: #fff;
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

//Show Budget
$showBud = false;
// Parse end date
$enDateRaw = strtotime($dtRange2);
$endDate = $enDateRaw ? new DateTime(date('Y-m-d', $enDateRaw)) : null;

$today = new DateTime();
$yesterday = (clone $today)->modify('-1 day');

// Check if endDate is valid, in current month, and not before today
$showBud = false;

if ($endDate) {
    $sameMonth = $endDate->format('m-Y') === $today->format('m-Y');
    $notBeforeToday = !($endDate < $today && $endDate <= $yesterday);

    if ($sameMonth && $notBeforeToday) {
        $showBud = true;
    }
}

// Final output
if ($showBud) {
    echo $bud; // ✅ Show value
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
  
  $pg = 'spend.php';
  ?>
  <body>
  <?php include 'menu-top.php'; ?>

<br>
<div class="report-header">
  <h4><?php echo $_SESSION['client_name']; ?> - Ads Report </h4>

  <span class="font-italic percent">
    Reports on: 
    <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.
    Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b>
  </span>
</div>
  

  
  <?php
 
  /* -------------------------------------- META ---------------------------------*/

  //$proj_list = array('HANFORD','PLATINUM'); 
  
  
    //$val = $acc_id;
    foreach($fb_acc_ids as $k2 => $val2) {
      $url = "https://graph.facebook.com/".$api_ver."/act_".$val2."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=1750";
      $req = file_get_contents_curl($url);
      $res = json_decode($req, true);  
      if(isset($res['data'])){
          $fb_data[$k2] = $res['data'];
      }
     // d($fb_data); exit;
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
    //d($name_contain); exit;
  ?>
  
  <?php 
    $reach_tot = $impr_tot =  $cpl_tot = 0;
    $tbl_data2 = $spend_tot = $lead_tot = $lead_data = array();
    foreach($fb_proj_data as $k_fb => $v_fb) 
    { 
        foreach($v_fb as $k => $val) {
             $lead = $cpl = $lead_con = 0;
           
            if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) {  $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }
            //if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { echo $lead_con = LeadGen($val['actions'], 'offsite_conversion.fb_pixel_lead'); } }
            if($lead!=0) { $lead = $lead - $lead_con; }
            if(isset($lead) && $lead>0 && ($lead !='-' || $lead !=0)) $cpl = @($val['spend']/$lead);
            $lead_tot[$k_fb][] = $lead;
            $spend_tot[$k_fb][] = $val['spend'];
           // d($lead_tot); exit;
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
        $spend_tot_meta[$k][]=  $spend;
        $lead_tot_meta[$k][]=  $lead;
    }
    
    //d($spend_tot_meta); d($lead_tot_meta); exit;
    
  
  /* -------------------------------------- GOOGLE ---------------------------------*/



  //$val = $acc_id;
  $g_data = $g_proj_data = array();
  foreach($g_acc_ids as $k2 => $val2) {
    $adAccounts = [$val2];
    $g_data[$k2] = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, date("Y-m-d", strtotime($dtRange1)), date("Y-m-d", strtotime($dtRange2)), $extQry=''); 
  }
  d($g_data);
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
      $spend_tot_google[$k][] =  $spend;
      $lead_tot_google[$k][]  =  $lead;
  }

  $today = date('d');
  $daysInMonth = date('t');
  
  $remainingDays = $daysInMonth - $today;
?>
  
<span   id="keywordsTable">
<table class="table table-bordered text-center">
    <thead>
        <tr>
            <th style="vertical-align: middle;">
                <div style="font-weight: normal;">
                <small><i class="fa fa-calendar" style="color:white; margin-right:8px;"></i>
                <?php echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?></small>
                </div>
            </th>
              
            </th>
            <?php foreach ($name_contain2 as $key => $proj): ?>
                <th colspan="3"><?php echo ($proj_names[$key] ?? $proj) === 'no' ? $_SESSION['client_name'] : ($proj_names[$key] ?? $proj); ?></th>
            <?php endforeach; ?>
            <?php if(count($name_contain2) > 1): ?>
                <th colspan="3"><?php echo $_SESSION['client_name']; ?></th>
            <?php endif; ?>
        </tr>
        <tr>
            <th style="vertical-align: middle;">Metrics<br>
            <?php foreach ($name_contain2 as $key => $proj): ?>
                <th>Meta</th><th>Google</th><th>Total</th>
            <?php endforeach; ?>
            <?php if(count($name_contain2) > 1): ?>
                <th>Meta</th><th>Google</th><th>Total</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php
        $rows = ['Spends' => [], 'Leads' => [], 'CPL' => []];
        $grand_meta_spend = $grand_google_spend = $grand_meta_leads = $grand_google_leads = 0;

        foreach ($name_contain2 as $key => $proj) {
            $metaSpend = isset($spend_tot_meta[$key]) ? array_sum($spend_tot_meta[$key]) : 0;
            $googleSpend = isset($spend_tot_google[$key]) ? array_sum($spend_tot_google[$key]) : 0;
            $totalSpend = $metaSpend + $googleSpend;

            $metaLeads = isset($lead_tot_meta[$key]) ? array_sum($lead_tot_meta[$key]) : 0;
            $googleLeads = isset($lead_tot_google[$key]) ? array_sum($lead_tot_google[$key]) : 0;
            $totalLeads = $metaLeads + $googleLeads;

            $metaCPL = ($metaLeads > 0) ? round($metaSpend / $metaLeads) : 0;
            $googleCPL = ($googleLeads > 0) ? round($googleSpend / $googleLeads) : 0;
            $totalCPL = ($totalLeads > 0) ? round($totalSpend / $totalLeads) : 0;

            // store rows
            $rows['Spends'][] = [$metaSpend, $googleSpend, $totalSpend];
            $rows['Leads'][]  = [$metaLeads, $googleLeads, $totalLeads];
            $rows['CPL'][]    = [$metaCPL, $googleCPL, $totalCPL];

            $numProjects = count($name_contain2);

            // accumulate totals
            $grand_meta_spend += $metaSpend;
            $grand_google_spend += $googleSpend;
            $grand_meta_leads += $metaLeads;
            $grand_google_leads += $googleLeads;
        }

        // now render rows
        foreach (['Spends', 'Leads', 'CPL'] as $label) {
            echo "<tr><td><b>$label</b></td>";
            foreach ($rows[$label] as $vals) {
                echo "<td>" . moneyFormatIndia($vals[0]) . "</td>";
                echo "<td>" . moneyFormatIndia($vals[1]) . "</td>";
                echo "<td>" . moneyFormatIndia($vals[2]) . "</td>";
            }

            // total column - only show if multiple projects
            if (count($name_contain2) > 1) {
                if ($label == 'Spends') {
                    $totalSpendSum = $grand_meta_spend + $grand_google_spend;
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($grand_meta_spend) . "</td>";
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($grand_google_spend) . "</td>";
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($totalSpendSum) . "</td>";
                }
                elseif ($label == 'Leads') {
                    $totalLeadsSum = $grand_meta_leads + $grand_google_leads;
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($grand_meta_leads) . "</td>";
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($grand_google_leads) . "</td>";
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($totalLeadsSum) . "</td>";
                }
                elseif ($label == 'CPL') {
                    $metaCPLTotal = ($grand_meta_leads > 0) ? round($grand_meta_spend / $grand_meta_leads) : 0;
                    $googleCPLTotal = ($grand_google_leads > 0) ? round($grand_google_spend / $grand_google_leads) : 0;
                    $totalCPLTotal = (($grand_meta_leads + $grand_google_leads) > 0) ? round(($grand_meta_spend + $grand_google_spend) / ($grand_meta_leads + $grand_google_leads)) : 0;

                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($metaCPLTotal) . "</td>";
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($googleCPLTotal) . "</td>";
                    echo "<td style='background:#e6ffe6'>" . moneyFormatIndia($totalCPLTotal) . "</td>";
                }
            }
            echo "</tr>";
        }
        ?>
    </tbody>
    <?php if (!empty($total_budget) && $total_budget != 0 && $showBud): ?>
        <?php
$projectCount = count($name_contain2); // number of project blocks
$totalColumns = 1 + ($projectCount * 3); // 1 for Project + 3 per project
// Add 3 more columns if we have multiple projects (for the total column)
if (count($name_contain2) > 1) {
    $totalColumns += 3;
}
?>
<tfoot>
    <tr>
        <td colspan="<?= $totalColumns - 1 ?>" style="background:#e6f7ff;">
            <strong style="color:green;">Total Budget:</strong>
        </td>
        <td>
            <strong style="color:green;"><?= moneyFormatIndia($total_budget) ?></strong>
        </td>
    </tr>
    <tr>
        <td colspan="<?= $totalColumns - 1 ?>">
            <strong style="color:green;">Budget Balance:</strong>
        </td>
        <td>
            <strong style="color:green;"><?= moneyFormatIndia($total_budget - ($grand_meta_spend + $grand_google_spend)) ?></strong>
        </td>
    </tr>
    <tr>
        <td colspan="<?= $totalColumns - 1 ?>">
            <strong style="color:green;">Est. Budget/day:</strong>
        </td>
        <td>
            <strong style="color:green;"><?php if($remainingDays>0 && $total_budget>0) { echo moneyFormatIndia((($total_budget - ($grand_meta_spend + $grand_google_spend))/$remainingDays)); } else { echo '-'; } ?></strong>
        </td>
    </tr>
</tfoot>
    <?php endif; ?>
</table>
</span>
<div style="text-align: right; margin-top: 10px;">
    <button id="copyTableImage" class="btn btn-info" style="margin-left: 8px;">Copy Table as Image</button>
</div>
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
changeModalTitle("Ads Report: <?php echo $_SESSION['client_name']; ?>");

    document.getElementById('copyTableImage').addEventListener('click', function() {
    const table = document.getElementById('keywordsTable');
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
                window.location = 'loading.php?pg=spend.php&tbl_id=<?php echo $tbl_id ?? '' ; ?>';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=spend.php&tbl_id=<?php echo $tbl_id; ?>&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );

        

});
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