<?php session_start();
date_default_timezone_set('Asia/Kolkata'); 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include '../db.php';
if(!isset($_SESSION['logged'])) {
	$pg = 'login.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
}
$pg='taboola';
include 'config.php';
$pgName = $_SESSION['client_name'].' : Taboola Ads';
?>
<title><?php echo $pgName; ?></title>
<?php
include 'header.php';


$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 
$uId = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['g_id'] = $row['g_id'];
$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
$_SESSION['g_token'] = $row['g_token'];
$_SESSION['g_mcc'] = $row['g_mcc'];
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];

$g_acc_name = array('RWD');
$resData = array();

$taboola_tok = '';
include '../taboola-config.php';


foreach($ta_acc_ids as $k => $val) {
    if($filter=='no') {
      
        $gResq = TaboolaAPI($val,$taboola_tok,'0 days','0 days');
        $resData[$val][1] = array($gResq['cost'], $gResq['conversions'], @($gResq['conversions']/$gResq['cost']));
        
        $gResq2 = TaboolaAPI($val,$taboola_tok,'-1 days','-1 days');
        $resData[$val][2] = array($gResq2['cost'], $gResq2['conversions'], @($gResq2['conversions']/$gResq2['cost']));
    }
    
    $taReq3 = TaboolaAPI($val,$taboola_tok,$dtRange1,$dtRange2);
    $resData[$val][3] = array($taReq3['cost'], $taReq3['conversions'], @($taReq3['conversions']/$taReq3['cost']));
} 

?>

<table id="datatable" class="table table-hover table-striped table-bordered">
    <tr>
        <th rowspan="2">SNo</th>
        <th rowspan="2">Ad Account</th>
        <?php if($filter=='no') { ?>
        <th colspan="3">Today</th>
        <th colspan="3">Yesterday</th>
        <?php } ?>
        <th colspan="3"><?php echo $dtRange; ?></th>
    </tr>
    <tr>
        <?php if($filter=='no') { ?>
        <th>Spend</th>
        <th>Conv.</th>
        <th>Cost / Conv.</th>
        
        <th>Spend</th>
        <th>Conv.</th>
        <th>Cost / Conv.</th>
        <?php } ?>
        <th>Spend</th>
        <th>Conv.</th>
        <th>Cost / Conv.</th>
    </tr>
<?php
$i = 1; 
$spend_t_tot = $spend_y_tot = $spend_30_tot = 0;
$lead_t_tot = $lead_y_tot = $lead_30_tot = 0;
$cpl_t_tot = $cpl_y_tot = $cpl_30_tot = 0;

foreach($ta_acc_ids as $k => $val) {
    $spend_t = $spend_y = $spend_30 = 0;
    $lead_t = $lead_y = $lead_30 = 0;
    $cpl_t = $cpl_y = $cpl_30 = 0;

    if($filter=='no') {
        if(isset($resData[$val][1])) {
            $data_t = $resData[$val][1];
            if(isset($data_t[0])) { $spend_t = $data_t[0]; }
            if(isset($data_t[1])) { $lead_t = $data_t[1]; }
            if(isset($data_t[2])) { $cpl_t = $data_t[2]; }
        }

        if(isset($resData[$val][2])) {
            $data_y = $resData[$val][2];
            if(isset($data_y[0])) { $spend_y = $data_y[0]; }
            if(isset($data_y[1])) { $lead_y = $data_y[1]; }
            if(isset($data_y[2])) { $cpl_y = $data_y[2]; }
        }
    }

    if(isset($resData[$val][3])) {
        $data_30 = $resData[$val][3];
        if(isset($data_30[0])) { $spend_30 = $data_30[0]; }
        if(isset($data_30[1])) { $lead_30 = $data_30[1]; }
        if(isset($data_30[2])) { $cpl_30 = $data_30[2]; }
    }
    
    $spend_t_tot += $spend_t; $spend_y_tot += $spend_y; $spend_30_tot += $spend_30;
    $lead_t_tot += $lead_t; $lead_y_tot += $lead_y; $lead_30_tot += $lead_30;
    $cpl_t_tot += $cpl_t; $cpl_y_tot += $cpl_y; $cpl_30_tot += $cpl_30;

    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td class="text-left"><?php echo $taAccN[$val]; ?></td>
            <?php if($filter=='no') { ?>
            <td><?php echo moneyFormatIndia($spend_t); ?></td>
            <td><?php echo moneyFormatIndia($lead_t); ?></td>
            <td><?php echo moneyFormatIndia($cpl_t); ?></td>
            
            <td><?php echo moneyFormatIndia($spend_y); ?></td>
            <td><?php echo moneyFormatIndia($lead_y); ?></td>
            <td><?php echo moneyFormatIndia($cpl_y); ?></td>
            <?php } ?>
            <td><?php echo moneyFormatIndia($spend_30); ?></td>
            <td><?php echo moneyFormatIndia($lead_30); ?></td>
            <td><?php echo moneyFormatIndia($cpl_30); ?></td>
        </tr>
    <?php
    $i++;
}
?>
        <tr class="tot_row">
            <td colspan="2" style="text-align:center"><b>Total</b></td>
            <?php if($filter=='no') { ?>
            <td><b><?php echo moneyFormatIndia($spend_t_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_t_tot); ?></b></td>
            <td><b><?php if($lead_t_tot>0) { echo moneyFormatIndia(@($spend_t_tot/$lead_t_tot)); } else { echo '-'; } ?></b></td>
            
            <td><b><?php echo moneyFormatIndia($spend_y_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_y_tot); ?></b></td>
            <td><b><?php if($lead_y_tot>0) { echo moneyFormatIndia(@($spend_y_tot/$lead_y_tot)); } else { echo '-'; } ?></b></td>
            <?php } ?>
            <td><b><?php echo moneyFormatIndia($spend_30_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot); ?></b></td>
            <td><b><?php if($lead_30_tot>0) { echo moneyFormatIndia(@($spend_30_tot/$lead_30_tot)); } else { echo '-'; } ?></b></td>
        </tr>
</table>
<?php include 'footer.php'; ?>