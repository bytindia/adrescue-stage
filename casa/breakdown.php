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
        488023135037290=>'Divinity',
    689707531525571=>'Keatsway'
    
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
  <?php $projURL = 'breakdown.php'; include 'menu-top.php'; ?>
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

  ?>
  <?php 
   
    /*$maxLead = array();
    foreach($lead_data as $key => $val) {
       // d($val); exit;
        foreach($val as $k => $v) {
            $maxLead[$key][] = array_sum($v);
        }
       // $maxVal[$k] = array_sum($lead_data[$k][$val]);
    }*/
    //d($maxVal); exit;
    //d($tbl_data2); exit;
    //$value = array_sum(array_column($tbl_data2[1782824538572369], 'spend'));
   // d($value); exit;
   $tbl_cols = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
    ?>
    <img src="images/adrescue-logo.png" class="adrescue_logo" />
    <h3><?php echo $ad_acc[$acc_id]; ?> - Hourly breakdowns </h3>
    <center><span class="font-italic percent">Reports on: <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.<br> Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b><br>* S= Spend, L = Leads, C/L = Cost per Lead</span></center><br>
    
    
<table id="datatable" class="table table-hover table-striped table-bordered datatable" data-ordering="true">
    <thead>
        <tr>
            <th rowspan="2">Hour</th>
            <?php foreach($tbl_cols as $k => $v)  { //if($k==0) { $k='This Hour'; } else { $k= 'Last '$k} ?>
            <th colspan="3"><?php echo $v; ?></th>
            <?php } ?>
        </tr>
        <tr>
                <?php foreach($tbl_cols as $k => $v)  { ?>
                <th>S</th>
                <th>L</th>
                <th>C/L</th>
                <?php } ?>
        </tr>
    <thead>
    <tbody>
            
            <?php 
            $tot_spend = $tot_lead = $tot_cpl = array();
            for($i=0;$i<24;$i++)  { 
                if($i<10) { $j='0'.$i; } else { $j=$i; }
                $timeStamp = "$j:00:00 - $j:59:59";
                $timeStamp2 = "$j:00 - $j:59";
                $timeStampArr = preg_replace('/[^A-Za-z0-9]/', '', $timeStamp);
                ?>
                <tr> 
                <td><?php echo $timeStamp2; ?></td>
                <?php foreach($tbl_cols as $k => $v)  { 
                    //if($k==0) { $k='This Hour'; } else { $k= 'Last '$k} 
                    $lead =  $spend = $cpl = 0;
                    if(isset($getRep[$v][$timeStampArr])) {
                        $spend = array_sum(array_column($getRep[$v][$timeStampArr], 's'));
                        $lead =  array_sum(array_column($getRep[$v][$timeStampArr], 'l'));
                        if(round($lead)!=0 && $lead!='-') { $cpl = round(@($spend/$lead)); } else { $cpl  ='-'; }
                    }
                    $tot_spend[$v][] += $spend;
                    $tot_lead[$v][] += $lead;
                    //$getRep[$v];
                    ?>
                    <td  data-order="<?php echo $spend; ?>"><?php if($spend!=0 && $spend!='-') { echo moneyFormatIndia($spend); } else { echo '-'; } ?> </td>
                    <td  data-order="<?php echo $lead; ?>"><?php if($spend!=0 && $spend!='-') { echo moneyFormatIndia($lead); } else { echo '-'; }  ?> </td>
                    <td  data-order="<?php echo $cpl; ?>"><?php echo moneyFormatIndia($cpl); ?> </td>
                <?php } ?>
            </tr>
            <?php } ?>
            <tfoot>    
                <tr> 
                    <td>Total</td>
                    
                    <?php foreach($tbl_cols as $k => $v)  { 
                        $totspend = array_sum($tot_spend[$v]);
                        $totleads = array_sum($tot_lead[$v]);
                        $cpltot = round(@($totspend/$totleads));
                        ?>
                    <td><?php echo moneyFormatIndia($totspend); ?></td>
                    <td><?php echo moneyFormatIndia($totleads); ?></td>
                    <td><?php if($totleads>0 && $totleads!='-') { echo moneyFormatIndia($cpltot); } else { echo '-'; } ?></td>
                    <?php } ?>
                    
                </tr>
            </tfoot>
            
    </tbody>  
            
    
</table>
 

    <br><br>
<?php //d($tbl_data2); 
//include 'footer.php'; ?>
<script>
   $(function() {
        
        $('.table').dataTable({
            "ordering": true,
            "lengthMenu": [25, 50, 100, 150, 200, 500],
            "pageLength": 50,
            scrollX: true,
            "autoWidth": false,
            "bLengthChange": false,
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
                window.location = 'loading.php?pg=breakdown.php&act_id=<?php echo $acc_id; ?>';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=breakdown.php&act_id=<?php echo $acc_id; ?>&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );


});
					
	</script>