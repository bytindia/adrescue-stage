<!-- Include Required Prerequisites -->
<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  

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
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'lead'
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
        1317725875075405=>'Zenith',
        488023135037290=>'Divinity'
    
  );
 //$ad_acc = array( 807092046796106=>'Flagship',1744658879060018=>'Boulevard');
  
  if(!isset($_GET['act_id'])){
    $acc_id = 1782824538572369;
  } else {
    $acc_id = $_GET['act_id'];
  }
  
  $pg = 'spend-casa.php';
  ?>
  <body>
  <?php include 'menu-top.php'; ?>
  
  <?php
  foreach($ad_acc as $k => $val) 
  { 
    //$val = $acc_id;
     $url = "https://graph.facebook.com/".$api_ver."/act_".$k."/insights?level=campaign&fields=adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true);  
    $fb_data[$k] = $res['data'];
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
           // $tbl_key2[$k_fb] = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
           // $tbl_data2[$k_fb][$tbl_key2[$k_fb]][] = array('reach'=>$val['reach'],'impressions'=>$val['impressions'],'lead'=>$lead[$k_fb],'cpl'=>$cpl[$k_fb],'spend'=>$val['spend']);

            $lead_data[$k_fb][$tbl_key2[$k_fb]][] = $lead[$k_fb];
           // $lead_data[$k_fb][$tbl_key2[$k_fb]][] = $lead[$k_fb];

           // $tbl_cols[] = $tbl_key2[$k_fb]; 
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
   $tbl_cols = array_unique($tbl_cols)
    ?>
    <img src="images/adrescue-logo.png" class="adrescue_logo" />
    <h3>CasaGrand - Ads Spend </h3>
    <center><span class="font-italic percent">Reports on: <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.<br> Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b></span></center><br>
    <div id="table-scroll" class="table-scroll">
    
<table id="datatable" class="table table-hover table-striped table-bordered datatable" data-ordering="true">
    <thead>
        <tr>
            <th>Projects</th>
            <th>Spend</th>
            <th>Leads</th>
            <th>CPL</th>
            
        </tr>
    <thead>
    <tbody>
            
            <?php 
            $tot_spend = $tot_lead = $tot_cpl = 0;
            foreach($ad_acc as $k => $val)  { 
                //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
                $spend = round(array_sum($spend_tot[$k]));
                $lead = round(array_sum($lead_tot[$k]));
                $tot_spend +=  $spend;
                $tot_lead +=  $lead;
                ?>
                <tr> 
                <td><a href="loading.php?pg=placement-lg.php&act_id=<?php echo $k; ?>" target="_blank"><?php echo $val; ?></a></td>
                <td><?php echo moneyFormatIndia($spend); ?></td>
                <td><?php echo moneyFormatIndia($lead); ?></td>
                <td><?php if(round($lead)!=0) { echo moneyFormatIndia(round(@($spend/$lead))); } else { echo '-'; }?></td>
                
            </tr>
            <?php } ?>
            </tbody>  
            <tfoot>    
            <tr> 
                <td>Total</td>
                <td><?php echo moneyFormatIndia($tot_spend); ?></td>
                <td><?php echo moneyFormatIndia($tot_lead); ?></td>
                <td><?php if(round($tot_lead)!=0) { echo moneyFormatIndia(round(@($tot_spend/$tot_lead))); } else { echo '-'; }?></td>
                
            </tr>
            </tfoot>
    
</table>
 

    </div>
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
                window.location = 'loading.php?pg=spend-casa.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=spend-casa.php&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );


});
					
	</script>