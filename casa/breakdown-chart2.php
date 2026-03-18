<!-- Include Required Prerequisites -->
<?php ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
//$dN = date('D', strtotime('2023-02-04')); exit;

$curTime =  date('H');
$curTime_1 =  date('H',strtotime('-1 hour'));
$curTime_2 =  date('H',strtotime('-2 hour'));
$curTime_3 =  date('H',strtotime('-3 hour'));
//00:00:00 - 00:59:59

$timeArr = array(
    0=>"$curTime:00:00 - $curTime:59:59",
    1=>"$curTime_1:00:00 - $curTime_1:59:59",
    2=>"$curTime_2:00:00 - $curTime_2:59:59",
    3=>"$curTime_3:00:00 - $curTime_3:59:59"
);
$timeArr2 = array(
    0=>"$curTime:00<br> to<br> $curTime:59",
    1=>"$curTime_1:00<br> to<br> $curTime_1:59",
    2=>"$curTime_2:00<br> to<br> $curTime_2:59",
    3=>"$curTime_3:00<br> to<br> $curTime_3:59"
);

if(!isset($_SESSION['client'])) {
	$pg = 'login.php';
	$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	exit();
}
//print_r($_SESSION);
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Highcharts Example</title>
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
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <style>
        th:first-child, td:first-child, stickyCls
        {
            position:sticky;
            left:0px;
            background-color: #fff;
        }
        body { width: 97% !important; }
        .table { font-size: 12px; }
    </style>
		<link rel="stylesheet" type="text/css" href="code/chart.css" />
	</head>
	<body>

<script src="./chart/code/highcharts.js"></script>
<script src="./chart/code/modules/series-label.js"></script>
<script src="./chart/code/modules/exporting.js"></script>
<script src="./chart/code/modules/export-data.js"></script>
<script src="./chart/code/modules/accessibility.js"></script>
<?php 
/*if(!isset($_SESSION['st'])) {
	$start = date('m/d/Y',strtotime('first day of this month'));
	$end = date('m/d/Y');
	$_SESSION['st'] = $start;
	$_SESSION['en'] = $end;
} else {

}*/
$dt_q ='';
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
  //global $obj_arr;
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
        834297526950773=>'Majestica',
        1317725875075405=>'Zenith',
        488023135037290=>'Divinity'
    
  );
 //$ad_acc = array(569001103516290=>'Aria');
  
  if(!isset($_GET['act_id'])){
    $acc_id = 427933624679014;
    $ad_acc[] = 'Athens';
  } else {
    $acc_id = $_GET['act_id'];
  }
  
  $pg = 'spend-casa.php';
  ?>
  <body>
    <div class="nav">
  <?php 
  $color_arr = array('primary','danger','success','primary','warning','info','danger','success','danger','info','primary','danger','success','danger','warning');$i=0;
 
    foreach($ad_acc as $k => $v) 
    { //shuffle($color_arr); ?>
    <a class="btn btn-sm btn-<?php echo $color_arr[$i]; ?>" href="loading.php?pg=breakdown-chart2.php&act_id=<?php echo $k; ?>"><?php echo $v; ?></a>
    <?php $i++; } ?>
    <?php if($_SESSION['client']=='casa') { ?>
        <a class="btn btn-sm btn-danger" href="loading.php?pg=placement-casa.php" style="background-color: #d33af9; border: 2px solid #e9a6f1;">Placements</a>
    <a class="btn btn-sm btn-danger" href="loading.php?pg=spend-casa.php" style="background-color: #1a98e1; border: 2px solid #0a77ff;">Spend</a>
    <a class="btn btn-sm btn-danger" href="loading.php?pg=tracker.php" style="background-color: #f0ad4e; border: 2px solid #e9d15a;">Tracker</a>
    <a class="btn btn-sm btn-danger" href="loading.php?pg=spend-casa2.php" style="background-color: #17a106; border: 2px solid #22bb0f">Budget</a>
    <?php } ?>
    <a class="btn btn-sm btn-default float-right" href="logout.php?placement">Logout</a>
    </div>

    <?php if(!isset($header_no)) { ?>
  <div class="right_col" role="main">
      <div class="row" style="margin-right: 0px; margin-bottom: 10px;">
          <div class="col-12">
          <span class="pull-right">
          
          </span>
          </div>   
      </div>
  
      <div class="row">
        <div class="col-4">
        <span style="float:left; margin-left: 15px; font-size:25px;">
        <?php echo $pgName; ?> 
        </span>
        </div>
        <div class="col-8">
        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                        </div>
        </div>       
      </div>
  </div>
  <?php } ?> 
  <?php
  global $camIds;
  function adAccounts($url) {
      //$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
      global $obj_arr;
      global $camIds;
      $requests = file_get_contents_curl($url);
      $fb_response = json_decode($requests, true);
      //d($fb_response); exit;
      foreach ($fb_response['data'] as $key => $response) {	
              
              $dN = date('D', strtotime($response['date_start']));		
              if(isset($response['actions'])) { if(array_key_exists($response['actions'], $obj_arr)) { $lead = LeadGen($response['actions'], $obj_arr['LEAD_GENERATION']); } }

              $lead = 0;
             if(is_array($response['actions']) || is_object($response['actions']) && count($response['actions'])>0) {
                for($q=0; $q<count($response['actions']); $q++) {
                        //$result = json_decode($response['actions'], true);
                        if(isset($response['actions'][$q]['action_type']) && $response['actions'][$q]['action_type']=='leadgen_grouped') $lead = $response['actions'][$q]['value'];	
                }
             }
             //return $r;
              $timeAr = preg_replace('/[^A-Za-z0-9]/', '', $response['hourly_stats_aggregated_by_advertiser_time_zone']);
              $camIds[$dN][$timeAr][] = array('s'=>$response['spend'], 'l'=>$lead, 'd'=>$dN, 't'=>$response['hourly_stats_aggregated_by_advertiser_time_zone']);
              //d($camIds); exit;
       // echo  $response->id; exit;
      }  
      if(isset($fb_response['paging']['next'])) {
     // d($camIds); exit;
          adAccounts($fb_response['paging']['next']);
      } else {
          //exit;
        return $camIds;
      }
    return $camIds;
  }
  $today = date('Y-m-d');
    $url2 = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/insights?level=account&breakdowns=hourly_stats_aggregated_by_advertiser_time_zone&fields=spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&time_increment=1&limit=500";
    $getRep = adAccounts($url2);

    $tbl_cols = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

  ?>
<div class="container-fluid">
  <div class="row">
        
<?php foreach($tbl_cols as $k => $v)  { ?>
    <div class="col-xs-1 col-sm-1 col-md-6 col-lg-6">
        <figure class="highcharts-figure">
            <div id="container_<?php echo $v; ?>"></div>
        </figure>
    </div>
<?php } ?>
</div>
<?php 
$lead =  $spend = $cpl = array();

for($i=0;$i<24;$i++)  {
    if($i<10) { $j='0'.$i; } else { $j=$i; }
    if($i<12) { $k='am'; } else { $k='pm'; }
    $timeStamp = "$j:00:00 - $j:59:59";
    $timeStamp2[] = "$i - ".($i+1)."";
    $timeStampArr = preg_replace('/[^A-Za-z0-9]/', '', $timeStamp);

    $spend_x =  $lead_x = $cpl_x = 0;
    foreach($tbl_cols as $k => $v)  {  
                    if(isset($getRep[$v][$timeStampArr])) {
                        $spend_x = round(array_sum(array_column($getRep[$v][$timeStampArr], 's'))); //exit;
                        $lead_x =  round(array_sum(array_column($getRep[$v][$timeStampArr], 'l')));
                        if(round($lead_x)!=0 && $lead_x!='-') { $cpl_x = round(@( $spend_x/$lead_x)); } else { $cpl_x  =0; }
                    } else {
                        $spend_x =  $lead_x = $cpl_x = 0;
                    }
                    $spend[$v][] = $spend_x;
                    $lead[$v][] = $lead_x;
                    $cpl[$v][] = $cpl_x;
    }
}
//d($spend); exit;

foreach($tbl_cols as $k => $v)  { 
    //if($k==0) { $k='This Hour'; } else { $k= 'Last '$k} 
    //$lead =  $spend = $cpl = 0;
   
}
//d($spend); exit;
$xAxis = "'".implode("','",$timeStamp2)."'"; //exit;
?>

		<script type="text/javascript">
            <?php foreach($tbl_cols as $k => $v)  { 
$spend_1 = implode(",",$spend[$v]);
$lead_1 = implode(",",$lead[$v]);
$cpl_1 = implode(",",$cpl[$v]);
    ?>
Highcharts.chart('container_<?php echo $v; ?>', {

title: {
    text: '<?php echo $v; ?> - Hourly breakdown',
    align: 'left'
},

subtitle: {
    text: 'Source: AdRescue',
    align: 'left'
},

yAxis: {
    title: {
        text: ' '
    }
},

xAxis: {categories: [<?php echo $xAxis; ?>],crosshair: true},
legend: {
    layout: 'vertical',
    align: 'right',
    verticalAlign: 'middle'
},

plotOptions: {
    series: {
        label: {
            connectorAllowed: false
        },
        pointStart: 0
    }
},

series: [{
    name: 'Spend',
    data: [<?php echo $spend_1; ?>]
}, {
    name: 'Leads',
    data: [<?php echo $lead_1; ?>]
}, {
    name: 'CPL',
    data: [<?php echo $cpl_1; ?>]
}],

responsive: {
    rules: [{
        condition: {
            maxWidth: 500
        },
        chartOptions: {
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom'
            }
        }
    }]
}

}); 
<?php } ?>
		</script>
        <script>
$(document).ready(function() {
	
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
                window.location = 'loading.php?pg=breakdown-chart2.php&act_id=<?php echo $acc_id; ?>';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=breakdown-chart2.php&act_id=<?php echo $acc_id; ?>&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );


});
					
	</script>
	</body>
   
</html>
