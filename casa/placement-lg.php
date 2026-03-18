<!-- Include Required Prerequisites -->
<?php ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta"); 

if(!isset($_SESSION['client'])) {
	$pg = 'login.php';
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	exit();
}
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
  if(isset($_SESSION['client']) && $_SESSION['client']=='casa') {
    
    $ad_acc = array(
        427933624679014=>'Cloud9',
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
  } else {
    $ad_acc = array(
        1782824538572369=>'Altis',
        376583790733205=>'Jain Housing',
        1246709765756836=>'Voora'
      );
  }
  
  
  if(!isset($_GET['act_id']) && $_SESSION['client']=='casa'){
    $acc_id = 427933624679014;
  } else if(!isset($_GET['act_id']) && $_SESSION['client']=='other'){
    $acc_id = 1782824538572369;
  } else {
    $acc_id = $_GET['act_id'];
  }

  $pg = 'loading.php?pg=placement-lg.php';
  ?>
  <body>
  <?php include 'menu-top.php'; ?>

  <?php
  //echo $access_token; exit;
  $val = $acc_id;
  $url = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=adset&breakdowns=publisher_platform,device_platform,platform_position&fields=adset_id,adset_name,reach,impressions,spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=750"; 
  $req = file_get_contents_curl($url);
  $res = json_decode($req, true);  
  $fb_data = $res['data'];
  //$fb_data =array();
  //exit;

  ?>
<img src="images/adrescue-logo.png" class="adrescue_logo" />
<h3><?php echo $ad_acc[$acc_id]; ?> - Placements</h3>
<center><span class="font-italic percent">Reports on: <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.<br> Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b></span></center><br>
<table id="datatable" class="table table-hover table-striped table-bordered" data-ordering="true">
    <thead>
        <tr>
            <th>AdSet</th>
            <th>Source</th>
            <th>Position</th>
            <th>Platform</th>
            <th>Reach</th>
            <th>Impr.</th>
            <th>Spend</th>
            <th>Leads</th>
            <th>CPL</th>
        </tr>
    <thead>
    <tbody>
    <?php 
    $reach_tot = $impr_tot = $lead_tot = $spend_tot = $cpl_tot = 0;
    $tbl_data2 = array();
    foreach($fb_data as $k => $val) 
    { 
        


        $lead = $cpl = 0;

        if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

        if($lead !='-' || $lead !=0) $cpl = @($val['spend']/$lead);
        ?>
            <tr>
            <td><?php echo $val['adset_name']; ?></td> 
            <td><?php echo ucwords(str_replace('_',' ',$val['publisher_platform'])); ?></td>
            <td><?php echo ucwords(str_replace('_',' ',$val['platform_position'])); ?></td>
            <td><?php echo ucwords(str_replace('_',' ',$val['device_platform'])); ?></td>
            <td><?php echo moneyFormatIndia($val['reach']); ?></td>
            <td><?php echo moneyFormatIndia($val['impressions']); ?></td>
            <td><?php echo moneyFormatIndia($val['spend']); ?></td>
            <td><?php echo moneyFormatIndia($lead); ?></td>
            <td><?php echo moneyFormatIndia($cpl); ?></td>
            </tr>
    <?php
        $reach_tot += $val['reach'];
        $impr_tot += $val['impressions'];
        $lead_tot += $lead;
        $spend_tot += $val['spend'];

        $tbl_key2 = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
        $tbl_data2[$tbl_key2][] = array('reach'=>$val['reach'],'impressions'=>$val['impressions'],'lead'=>$lead,'cpl'=>$cpl,'spend'=>$val['spend']); 
    }
    ?>
    </tbody>
    <tr>
        <th>Total</th>
        <th>-</th>
        <th>-</th>
        <th>-</th>
        <th><?php echo moneyFormatIndia($reach_tot); ?></th>
        <th><?php echo moneyFormatIndia($impr_tot); ?></th>
        <th><?php echo moneyFormatIndia($spend_tot); ?></th>
        <th><?php echo moneyFormatIndia($lead_tot); ?></th>
        <th><?php echo moneyFormatIndia(@($spend_tot/$lead_tot)); ?></th>
    </tr>
</table>

<br><br>
<h4>Breakdown - Summary Report</h4>
<table id="datatable2" class="table table-hover table-striped table-bordered datatable" data-ordering="true">
    <thead>
        <tr>
            <th>Source</th>
            <th>Position</th>
            <th>Platform</th>
            <th>Reach</th>
            <th>Impr.</th>
            <th>Spend</th>
            <th>Leads</th>
            <th>CPL</th>
        </tr>
    <thead>
    <tbody>
    <?php 
    $reach_tot = $impr_tot = $lead_tot = $spend_tot = $cpl_tot = 0;
    //$tbl_data2 = array();
    foreach($tbl_data2 as $k2 => $val2) 
    { 
        


        $lead = $cpl = 0;

        //if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

        //if($lead !='-' || $lead !=0) $cpl = @($val['spend']/$lead);
        $reach = array_sum(array_column($val2, 'reach'));
        $impressions = array_sum(array_column($val2, 'impressions'));
        $lead = array_sum(array_column($val2, 'lead'));
        //$cpl = array_sum(array_column($val2, 'cpl'));
        $spend = array_sum(array_column($val2, 'spend'));
        $src_val = explode('_#_',$k2);
        if($lead !='-' || $lead !=0) $cpl = @($spend/$lead);
        ?>
            <tr>
           
            <td><?php echo ucwords(str_replace('_',' ',$src_val[0])); ?></td>
            <td><?php echo ucwords(str_replace('_',' ',$src_val[1])); ?></td>
            <td><?php echo ucwords(str_replace('_',' ',$src_val[2])); ?></td>
            <td><?php echo moneyFormatIndia($reach); ?></td>
            <td><?php echo moneyFormatIndia($impressions); ?></td>
            <td><?php echo moneyFormatIndia($spend); ?></td>
            <td><?php echo moneyFormatIndia($lead); ?></td>
            <td><?php echo moneyFormatIndia($cpl); ?></td>
            </tr>
    <?php
        $reach_tot += $reach;
        $impr_tot += $impressions;
        $lead_tot += $lead;
        $spend_tot += $spend;

       // $tbl_key2 = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
        //$tbl_data2[$tbl_key2][] = array($val['reach'],$val['impressions'],$lead,$cpl,$val['spend']); 
    }
    ?>
    </tbody>
    <tr>
        <th>Total</th>
        
        <th>-</th>
        <th>-</th>
        <th><?php echo moneyFormatIndia($reach_tot); ?></th>
        <th><?php echo moneyFormatIndia($impr_tot); ?></th>
        <th><?php echo moneyFormatIndia($spend_tot); ?></th>
        <th><?php echo moneyFormatIndia($lead_tot); ?></th>
        <th><?php echo moneyFormatIndia(@($spend_tot/$lead_tot)); ?></th>
    </tr>
</table>

<?php //d($tbl_data2); 
//include 'footer.php'; ?>
<style>
        th:first-child, td:first-child, stickyCls
        {
            position:sticky;
            left:0px;
            background-color: #fff;
        }
    </style>
<script>
   $(function() {
        
        $('.table').dataTable({
            "ordering": true,
            "lengthMenu": [25, 50, 100, 150, 200, 500],
            "pageLength": 50
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
                window.location = 'loading.php?pg=placement-lg.php&act_id=<?php echo $acc_id; ?>';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=placement-lg.php&act_id=<?php echo $acc_id; ?>&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );

});
					
	</script>