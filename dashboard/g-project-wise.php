<?php session_start();
date_default_timezone_set('Asia/Kolkata'); 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include '../db.php';
if(!isset($_SESSION['logged'])) {
	$pg = 'login.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
}
?>
<title>RWD - Google Ads dashboard</title>
<?php
include 'header.php';

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE email='bytramesh@gmail.com'";
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

//$g_acc_ids = array(3083685793, 2846916411, 33937645764, 4432945048, 7403611625, 9331044768);
//$g_acc_ids = array(3083685793,2846916411);
$g_acc_ids = array(9516894033);
//$g_acc_ids = array(3083685793);
$g_acc_name = array('RWD');
$resData = array();
$rwd_projects = array(1=>'Corniche', 2=>'Grand Corridor', 3=>'Spotlight',4=>'Ibis');
if(is_array( $g_acc_ids ) && count($g_acc_ids)>0) {
    $accIds = $g_acc_ids;
    $accNames = 1;
    include '../download-rwd-camp.php';
    foreach($g_acc_ids as $k => $gR)			
    {
        if($gR!='') {
            $gSpent = array();
            $gaIds = explode(',',$gR); 
            for($z=1;$z<4;$z++) {
                foreach($gaIds as $key => $gaId) {
                    $cost = $conv = $cpl = 0;
                    $rows = array();
                    
                    $rows[$gaId] = file(''.$server_path.'ads-gsquare/rwd_camp_'.$gaId.'_'.$z.'.csv');
                  
                    d($rows);  exit;
                    foreach ($rows[$gaId] as $key2 => $value)
					{
	                        $csv[$key2] = str_getcsv($value);
                            
	                        //echo $csv[$key2][0].'<br>';
	                        $campName = str_replace("-", " ", $csv[$key2][0]);
	                        $campName = str_replace("_", " ", $campName);
	                        $pVal = contains($campName, $rwd_projects);
	                        if($pVal!=FALSE) {
	                            $pKey = array_search ($pVal, $rwd_projects);
	                            
	                            if($csv[$key2][5]!=0) { $cost += $csv[$key2][5]/1000000; }	
	                            if($csv[$key2][9]!=0) { $conv += $csv[$key2][9]; }	
	                            if($csv[$key2][8]!=0) { $cpl += $csv[$key2][8]/1000000; }	
	                            echo $csv[$key2][0].' => '.$cost.' => '.$pKey.'<br>'; 
	                            // $gSpent[] = round($cost);		
	                            $resData[$z][$pKey][] = array($cost, $conv, $cpl,$z,$pVal);
	                        }
                    }	
                    
                    //$cpl = @(round($cost / $conv, 2));
                    //$resData[$key][$z] = array($cost, $conv, $cpl);
                
                    //
                }
            }
            //echo  "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'"; 
            //mysqli_query($conn, "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'") or die(mysqli_error());		
        }
    }
}
//d($resData); 
//d($arr_goo); exit;
//d($gData);
?>
<h3>RWD - Google ads dashboard</h3>
<table id="datatable" class="table table-hover table-striped table-bordered">
    <tr>
        <th rowspan="2">SNo</th>
        <th rowspan="2">Account Name</th>
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

foreach($rwd_projects as $k => $val) {
    $spend_t = $spend_y = $spend_30 = '-';
    $lead_t = $lead_y = $lead_30 = '-';
    $cpl_t = $cpl_y = $cpl_30 = '-';
    //d($resData[$k]); exit;
    //foreach($resData[$val][1]['data'] as $k1 => $val1) {
    if($filter=='no') {
        if(isset($resData[1][$k])) {
            foreach($resData[1][$k] as $k1 => $val1) {
                $data_t = $val1;
                $spend_t += $data_t[0];
                $lead_t += $data_t[1];
                $cpl_t += $data_t[2];
            }
        }

        if(isset($resData[2][$k])) {
            foreach($resData[2][$k] as $k1 => $val1) {
                $data_y = $val1;
                $spend_y += $data_y[0];
                $lead_y += $data_y[1];
                $cpl_y += $data_y[2];
            }
        }
    }
    if(isset($resData[3][$k])) {
        foreach($resData[3][$k] as $k1 => $val1) {
            $data_30 = $val1;
            $spend_30 += $data_30[0];
            $lead_30 += $data_30[1];
            $cpl_30 += $data_30[2];
        }
    }
    
    $spend_t_tot += $spend_t; $spend_y_tot += $spend_y; $spend_30_tot += $spend_30;
    $lead_t_tot += $lead_t; $lead_y_tot += $lead_y; $lead_30_tot += $lead_30;
    $cpl_t_tot += $cpl_t; $cpl_y_tot += $cpl_y; $cpl_30_tot += $cpl_30;

    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td class="text-left"><?php echo $rwd_projects[$k]; ?></td>
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
            <td><b><?php echo moneyFormatIndia(@($spend_t_tot/$lead_t_tot)); ?></b></td>
            
            <td><b><?php echo moneyFormatIndia($spend_y_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_y_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia(@($spend_y_tot/$lead_y_tot)); ?></b></td>
            <?php } ?>
            <td><b><?php echo moneyFormatIndia($spend_30_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia(@($spend_30_tot/$lead_30_tot)); ?></b></td>
        </tr>
</table>
<?php include 'footer.php'; ?>