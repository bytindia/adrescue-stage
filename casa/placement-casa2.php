<!-- Include Required Prerequisites -->
<?php session_start();
if(!isset($_SESSION['client'])) {
	$pg = 'login-placement.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
}
//print_r($_SESSION);
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
?>

<Title>AdRescue - Ads </Title>
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
<style>
    body {
    width: 97%;
    }
.table-scroll {
	position:relative;
	margin:auto;
	overflow:hidden;
	
}
.table-wrap {
	width:100%;
	overflow:auto;
}
.table-scroll table {
	width:100%;
	margin:auto;
	border-collapse:separate;
	border-spacing:0;
}
.table-scroll th, .table-scroll td {
	padding:5px 10px;
	border:1px solid #dddddd;
	white-space:nowrap;
	vertical-align:top;
}
.table-scroll thead, .table-scroll tfoot {
	background:#f9f9f9;
}
.clone {
	position:absolute;
	top:0;
	left:0;
	pointer-events:none;
}
.clone th, .clone td {
	visibility:hidden
}
.clone td, .clone th {
	border-color:transparent
}
.clone tbody th {
	visibility:visible;
	color:red;
}
.clone .fixed-side {
	border:1px solid #dddddd;
  background: #f9f9f9;
	visibility:visible;
  font-weight: bold;
  text-align:left;
}
tbody td {
    text-align: right;
}
.clone thead, .clone tfoot{background:transparent;}

.txt_red { color:red; font-weight:bold; }
.txt_green { color:#74d350; font-weight:bold; }
thead th {
    text-align: center !important;
}
table .fa { font-size: 15px; color: #b5b7be; cursor: pointer; }
table .fa-wa { color: #4db628; font-weight: bold; }
.wa_msg { font-weight: bold; }
h3 { text-align:center; }
</style>
<?php 
if(!isset($_SESSION['st'])) {
	$start = date('m/d/Y',strtotime('first day of this month'));
	$end = date('m/d/Y');
	$_SESSION['st'] = $start;
	$_SESSION['en'] = $end;
}
$dt_q ='';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
} else {
    $start = date('d/m/Y',strtotime('first day of this month'));
	$end = date('d/m/Y');
    $_GET['st'] = $start;
    $_GET['en'] = $end;
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

if(isset($_GET['st'])) { 
        $stDt =  $_GET['st']; 
        $enDt =  $_GET['en']; 
        $filter = 'yes';
        $dtRange = $_GET['st'].' - '.$_GET['en']; 
        $dtRange1 = str_replace('/', '-', $_GET['st']); $dtRange2 = str_replace('/', '-', $_GET['en']);
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
  if(isset($overview) && !isset($_GET['st']))
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
        569001103516290=>'Arial',
        462105354650207=>'Elysium',
        370327086813811=>'Firstcity',
        807092046796106=>'Flagship',
        735957617015640=>'PlatinumJoy',
        834297526950773=>'Majestica',
        1317725875075405=>'Zenith'
    
  );
  //$ad_acc = array( 807092046796106=>'Flagship');
  
  if(!isset($_GET['act_id'])){
    $acc_id = 1782824538572369;
  } else {
    $acc_id = $_GET['act_id'];
  }
  
  $pg = 'placement-casa.php';
  ?>
  <body>
    <div class="nav">
  <?php 
  $color_arr = array('primary','danger','success','primary','warning','primary','info','success','danger','info','primary','danger','success','danger','warning');
  $i=0;
  $src_text = array(
    'Facebook' => 'FB',
    'Reels' => 'RL',
    'Desktop' => 'DT',
    'Search' => 'SR',
    'Feed' => 'FD',
    'Mobile App' => 'Mb A',
    'Instagram' - 'IG',
    'Explore' => 'Ex',
    'Marketplace' => 'Mkpl',
    'story' => 'Sty',
    'Instant Article' => 'IN Art',
    'Stories' => 'Sty',
    'Overlay' => 'Ov',
    'Video' => 'V',
    'Instagram' => 'IG',
    'Unknown' => 'UKN');
    foreach($ad_acc as $k => $v) 
    { //shuffle($color_arr); ?>
    <a class="btn btn-<?php echo $color_arr[$i]; ?>" href="placement-lg.php?act_id=<?php echo $k; ?>"><?php echo $v; ?></a>
    <?php $i++; } ?>
    <?php if($_SESSION['client']=='casa') { ?>
    <a class="btn btn-danger" href="placement-casa.php" style="background-color: #d33af9; border: 2px solid #e9a6f1;">Overview</a>
    <?php } ?>
    <a class="btn btn-default" href="logout.php?placement">Logout</a>
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
  foreach($ad_acc as $k => $val) 
  { 
    //$val = $acc_id;
     $url = "https://graph.facebook.com/".$api_ver."/act_".$k."/insights?level=campaign&breakdowns=publisher_platform,device_platform,platform_position&fields=adset_id,adset_name,reach,impressions,spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=750";
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
            $tbl_key2[$k_fb] = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
            $tbl_data2[$k_fb][$tbl_key2[$k_fb]][] = array('reach'=>$val['reach'],'impressions'=>$val['impressions'],'lead'=>$lead[$k_fb],'cpl'=>$cpl[$k_fb],'spend'=>$val['spend']);

            $lead_data[$k_fb][$tbl_key2[$k_fb]][] = $lead[$k_fb];
           // $lead_data[$k_fb][$tbl_key2[$k_fb]][] = $lead[$k_fb];

            $tbl_cols[] = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform']; 
        }
        
    } 

   $tbl_cols = array_unique($tbl_cols)
    ?>
    <h3>CasaGrand - Placements - Overview</h3>
    <div id="table-scroll" class="table-scroll">
    <div class="table-wrap">
<table id="datatable2" class="table table-hover table-striped table-bordered datatable" data-ordering="true">
    <thead>
        <tr>
            <th>Projects</th>
            <th>Spend</th>
            <th>Lead</th>
            <th>CPL</th>
            <?php
            foreach($tbl_cols as $k => $val) { 
                $v = explode('_#_',$val); 
                $str = ucwords(str_replace('_',' ',$v[0])).' - '.ucwords(str_replace('_',' ',$v[1])).' - '.ucwords(str_replace('_',' ',$v[2]));
                $str = str_replace(array_keys($src_text), $src_text, $str);
                ?>
            <th><?php echo $str; ?></th>
            <?php } ?>
        </tr>
    <thead>
    <tbody>
            <tr> 
            <?php foreach($ad_acc as $k => $val)  { 
                //$value = array_sum(array_column($tbl_data2[$k], 'spend'));
                ?>
                <td><a href="placement-lg.php?act_id=<?php echo $k; ?>" target="_blank"><?php echo $val; ?></a></td>
                <td><?php echo moneyFormatIndia($spend_tot[$k]); ?></td>
                <td><?php echo moneyFormatIndia($lead_tot[$k]); ?></td>
                <td><?php if(round(array_sum($lead_tot[$k]))!=0) { echo moneyFormatIndia(round(@(array_sum($spend_tot[$k])/array_sum($lead_tot[$k])))); } else { echo '-'; }?></td>
                <?php
                foreach($tbl_cols as $k1 => $val2) { 
                    $lead = 0;
                    if(isset($lead_data[$k][$val2])) { $lead = round(array_sum($lead_data[$k][$val2])); }
                    if($lead==0) { $lead_per = '-';  } else { $lead_per = $lead.' ('.round((($lead/array_sum($lead_tot[$k]))*100)).'%)';}
                    //echo array_sum(array_column($tbl_data2[$k][$val2], 'lead')); exit;
                  //  if(isset($tbl_data2[$k][$val2])) { $lead = array_sum(array_column($tbl_data2[$k][$val2], 'lead')); }
                ?>
                <td><?php  echo $lead_per; ?></td>
                <?php } ?>
            </tr>
            <?php } ?>
    </tbody>
    
</table>

    </div>

    </div>
    <br><br>
<?php //d($tbl_data2); 
//include 'footer.php'; ?>
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
	
var defSt = '01/01/2018';
var defEnd = '01/01/2024';

        $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            ranges: {
                'All': [defSt, defEnd],
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
                window.location = 'placement-casa.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'placement-casa.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );

        <?php if(isset($_GET['st']) && $_GET['st']!='') { ?>
          var d1 = '<?php echo $_GET['st']; ?>';
          var d2 = '<?php echo $_GET['en']; ?>';
          $('#reportrange span').html(d1 + ' - ' + d2);
          $("#reportrange").data().daterangepicker.startDate = moment(d1, datepicker.data().daterangepicker.format );
          $("#reportrange").data().daterangepicker.endDate = moment(d2, datepicker.data().daterangepicker.format );
          $("#reportrange").data().daterangepicker.updateCalendars();
        <?php }  ?>
});
					
	</script>