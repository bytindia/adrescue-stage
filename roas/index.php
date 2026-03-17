<?php
session_start(); //exit;   
date_default_timezone_set('Asia/Kolkata');
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

if(isset($_POST['submit'])) {
    
    //print_r($_FILES); exit;
    $password = $_POST['password'];
    $user = $_POST['user'];
   
    if($user =='techno' && $password=='BYTechno@23') {
        
        $_SESSION['logged'] = 1;
        echo "<script>window.location = 'index.php';</script>";
        exit();
    } else {
        $_SESSION['err'] = 'Invalid username / password';	
        echo "<script>window.location = 'index.php';</script>";
        exit();
    }
}

include '../db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include '../config.php';
$title = 'Technofunda - ROAS';
?>
<title><?php echo $title; ?></title>
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Bootstrap -->
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body, html {
    width: 80%;
    margin: 10 auto;
    font-family: Trebuchet MS, sans-serif;
}
.table { font-size: 14px;}
ul.nav.navbar-right.panel_toolbox {
    float: right;
    margin-bottom: 20px;
}
footer {
    margin-top: 25px;
    float: right;
    font-size: 14px;
}
@media (min-width: 768px) {
.form-horizontal .control-label {
  
    text-align: left !important; 
}}
tfoot, thead {
    background: #3e99e8;
    font-weight: bold;
    color: white;
}
tfoot td {
    text-align: right !important;
}
thead td {
    text-align: center !important;
}
.percent {
    color: #2d89d7;
    font-size: 12px;
    font-style: italic;
}
.font-italic b, strong {
    font-weight: 700;
    color: #13a911;
    font-size: 13px;
}
</style>

<?php

if(!isset($_SESSION['logged'])){
?>
 <form id="demo-form2" data-parsley-validate class="form-horizontal form-label-left" enctype="multipart/form-data" method="post" action="">
 <div class="form-group">
<label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Username<span class="required">*</span>
</label>                        
                                               
    <input type="text" class="form-control has-feedback-left" name="user" required="required"  />
    
</div>
<div class="form-group">
<label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Password<span class="required">*</span>
</label>                        
                                               
    <input type="password" class="form-control has-feedback-left" name="password" required="required"  />
    
</div>

<br>
<?php if(isset($_SESSION['err'])) { echo  $_SESSION['err']; } unset( $_SESSION['err']); ?>
<div class="ln_solid"></div>
<div class="form-group">
    <button type="submit" name="submit" class="btn btn-success btn-lg">Submit</button> 
</div>

</form>
<?php
} else {

$_GET['st']  = $_GET['en'] = date('d/m/Y',strtotime('yesterday'));
$dtRange1_y = str_replace('/', '-', $_GET['st']); 
$dtRange2_y = str_replace('/', '-', $_GET['en']);

if(isset($_GET['today'])) {
    $_GET['st']  = $_GET['en'] = date('d/m/Y',strtotime('yesterday'));
    $dtRange1_y = str_replace('/', '-', $_GET['st']); 
    $dtRange2_y = str_replace('/', '-', $_GET['en']);
} else {
    $_GET['st']  = date('d/m/Y',strtotime('first day of this month'));
    $_GET['en'] = date('d/m/Y',strtotime('today'));
    $dtRange1 = str_replace('/', '-', $_GET['st']); 
    $dtRange2 = str_replace('/', '-', $_GET['en']);
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

$fb_id = 165466229093582;
$spreadsheetId = '1w5w_2nJvtRiH7A-unHGpk0pOcnPjvegLHUE8BqHOGwI';
$sheetTab = 'Sales Tracking - all sources (for BYT Team)';
$uid_sheet = 2;

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function find_parent($array, $needle, $parent = null) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $pass = $parent;
            if (is_string($key)) {
                $pass = $key;
            }
            $found = find_parent($value, $needle, $pass);
            if ($found !== false) {
                return $found;
            }
        } else if ($key === 'id' && $value === $needle) {
            return $parent;
        }
    }

    return false;
}
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
function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

// Google sheet API
require_once '../google-sheets-api/vendor/autoload.php';
require_once '../google-sheets-api/class-db.php';
require_once '../google-sheets-api/config.php';
include '../google-sheets-api/read-sheet.php';

//Yesterday

$fetch_sale = read_sheet($uid_sheet, $spreadsheetId, $sheetTab);
//echo '<pre>';   print_r($fetch_sale); echo '</pre>'; 
//exit;
$rangeStart = strtotime($dtRange1);  
$rangeEnd = strtotime($dtRange2);  
  
$filter_sale = array_filter($fetch_sale['data'], function($var) use ($rangeStart, $rangeEnd) {  
    $evtime = strtotime($var[0]);  
    return $evtime <= $rangeEnd && $evtime >= $rangeStart;  
});
//d($filter_sale); exit;

$url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=account&fields=spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=750";
$req = file_get_contents_curl($url);
$res = json_decode($req, true);  
//d($res['data'][0]['spend']);
$spend_ads = $res['data'][0]['spend'];
$leads = LeadGen($res['data'][0]['actions'], $obj_arr['CONVERSIONS']);
$cpl = @(($spend_ads/$leads));


$raw_Val = $csvData = $csvData2 = $csvData3 = $raw_Keys = $sale_Keys = $sale_Val = array();
//RAW Data
//SELECT * FROM `clickfunnel_technofunda` WHERE page_id=59016691 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
$raw_data = array();
$sqlRev=mysqli_query($conn, "SELECT email, source, campaign FROM `clickfunnel_technofunda` WHERE page_id=59016691 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
			
while($sqlROW=mysqli_fetch_assoc($sqlRev))
{
    $raw_data[] = $sqlROW;
    $src_val = trim($sqlROW['source']);
    $cmp_val = trim($sqlROW['campaign']);
    $csvData[$src_val.'<>'.$cmp_val][] = trim($sqlROW['email']);
    $csvData3[$sqlROW['email']] = $src_val.'<>'.$cmp_val;
}
//d($csvData3); exit;
foreach($filter_sale as $k => $v){
    if(isset($csvData3[$v[3]])) {
        $find_key = $csvData3[$v[3]];
        $csvData2[$find_key][] = trim($v[3]);
    }
}

$raw_Keys = array_keys($csvData);
$sale_Keys = array_keys($csvData2);

$all_Keys = array_unique(array_merge($raw_Keys,$sale_Keys), SORT_REGULAR);
$all_Keys = array_diff($all_Keys, array('<>'));

//d($all_Keys); exit;
foreach($csvData as $k => $v){
    $raw_Val[$k] = count($v);
} 
foreach($csvData2 as $k => $v){
    $sale_Val[$k] = count($v);
} 
if(isset($sale_Val['<>'])) { unset($sale_Val['<>']); }
$tot_sal_lead = array_sum($sale_Val);
?>
<a href="logout.php" style="float:right;">Logout</a>

<center>
    <h3><?php echo $title; ?> </h3>
    <span class="font-italic percent">
        Reports on: <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.<br> 
        Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b>
    </span>
</center>
<br>
<table id="datatable2" class="table  table-striped table-bordered dataTable no-footer">
                <thead>
                    <tr>
                        <td>Total Spend</td>
                        <td>Total Leads</td>
                        <td>CPL</td>
                        <td>Total Sale</td>
                        <td>ROAS</td>
                    </tr>
                </thead>
                <tr>
                        <td class="text-right"><?php echo moneyFormatIndia($spend_ads); ?></td>
                        <td class="text-right"><?php echo moneyFormatIndia($leads); ?></td>
                        <td class="text-right"><?php echo moneyFormatIndia(round($cpl)); ?></td>
                        <td class="text-right"><?php echo moneyFormatIndia($tot_sal_lead); ?></td>
                        <td class="text-right"><?php if($tot_sal_lead>0) { echo @round(((6999 * $tot_sal_lead)/$spend_ads),2); } else { echo '-'; } ?></td>
                </tr>
                
</table>
<br><br>
<table id="datatable" class="table  table-striped table-bordered dataTable no-footer">
                <thead>
                    <tr>
                        <td>SNo</td>
                        <td>Campaign</td>
                        <td>Source</td>
                        <td>Sale</td>
                        <td>Leads</td>
                        <td>Sale (in %)</td>
                    </tr>
                </thead>
                <?php $s=1; 
                $tot_sale = $tot_lead = 0;
                foreach($all_Keys as $k => $v){ 
                        $src_camp = $str_arr = explode ("<>", $v);
                        ?>
                        <tr>
                        <td><?php echo $s; ?></td>
                        <td><?php echo $src_camp[1]; ?></td>
                        <td><?php echo ucfirst($src_camp[0]); ?></td>
                        <td class="text-right"><?php if(isset($sale_Val[$v])) { echo moneyFormatIndia($sale_Val[$v]); $tot_sale = $tot_sale + $sale_Val[$v]; } else { echo '-'; }?></td>
                        <td class="text-right"><?php if(isset($raw_Val[$v])) { echo moneyFormatIndia($raw_Val[$v]); $tot_lead = $tot_lead + $raw_Val[$v]; } else { echo '-'; }?></td>
                        <td class="text-right"><?php if(isset($sale_Val[$v]) && isset($raw_Val[$v]) && $sale_Val[$v]>0 && $raw_Val[$v]>0) { echo round((($sale_Val[$v]/$raw_Val[$v])*100),2); } else { echo '-'; } ?></td>
                </tr>
                
                <?php $s++; 
                    
                } 
                if(count($all_Keys)>0) {
                    ?> <tfoot>
                        <tr>
                        <td><b>Total</b>
                    </td>
                        <td>-</td>
                        <td>-</td>
                        <td class="text-right"><b><?php if($tot_sale>0) { echo moneyFormatIndia($tot_sale); } else { echo '-'; }?></b></td>
                        <td class="text-right"><b><?php if($tot_lead>0) { echo moneyFormatIndia($tot_lead); } else { echo '-'; }?></b></td>
                        <td class="text-right"><b><?php if($tot_sale>0 && $tot_lead>0) { echo round((($tot_sale/$tot_lead)*100),2); } else { echo '-'; }?></b></td>
                </tr> </tfoot>
                <?php 
                }
                ?>
</table>
<?php 
                }
                ?>
<!-- Custom Theme Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

	<script type="text/javascript">
    var isMob = false;
    $(document).ready(function() {
    $('.table').DataTable( {
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copyHtml5', footer: true },
                { extend: 'excelHtml5', footer: true },
                { extend: 'csvHtml5', footer: true },
                { extend: 'pdfHtml5', footer: true },
                { extend: 'print', footer: true }
            ],
            "pageLength": 50,
            "lengthMenu": [ [20, 50, 100, -1], [20, 50, 100, "All"] ]
        } );
    } );
	</script>