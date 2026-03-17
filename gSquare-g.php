 <?php session_start();
date_default_timezone_set('Asia/Kolkata'); 
ini_set('display_errors', 0); ini_set('display_startup_errors', 0); //error_reporting(E_ALL);
include 'db.php';
echo "<script>window.location = 'login.php';</script>";
	exit();
?>
<style>
body { width:80%; margin:0 auto; }
table {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

table td, table th {
  border: 1px solid #ddd;
  padding: 8px;
}

table tr:nth-child(even){background-color: #f2f2f2;}

table tr:hover {background-color: #ddd;}

table th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: center;
  background-color: #3b5998;
  color: white;
}
</style>
<?php
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

//$g_acc_ids = array(3083685793, 2846916411, 33937645764, 4432945048, 7403611625, 9331044768);
//$g_acc_ids = array(3083685793,2846916411);
$g_acc_ids = array(3083685793, 2846916411, 4432945048, 7403611625, 9331044768, 3937645764);
$g_acc_name = array('G-square account 1', 'G-square account 2','G-squareaccount4', 'G-squareaccount 5', 'G-square account 6', 'G Square Account - 3');
$resData = array();

if(is_array( $g_acc_ids ) && count($g_acc_ids)>0) {
    $accIds = $g_acc_ids;
    $accNames = 1;
    //include 'download-gsquare.php';
    foreach($g_acc_ids as $k => $gR)			
    {
        if($gR!='') {
            $gSpent = array();
            $gaIds = explode(',',$gR); 
            for($z=1;$z<4;$z++) {
                foreach($gaIds as $key => $gaId) {
                    $cost = $conv = $cpl = 0;
                    $rows = array();
                    $rows[$gaId] = file(''.$server_path.'ads-gsquare/account_'.$gaId.'_'.$z.'.csv');
                    $last_row[$gaId] = array_pop($rows[$gaId]);
                    $arr_goo[$gaId] = str_getcsv($last_row[$gaId]);	

                    if($arr_goo[$gaId][5]!=0) { $cost = $arr_goo[$gaId][5]/1000000; }	
                    if($arr_goo[$gaId][9]!=0) { $conv = $arr_goo[$gaId][9]; }	
                    if($arr_goo[$gaId][8]!=0) { $cpl = $arr_goo[$gaId][8]/1000000; }	

                   // $gSpent[] = round($cost);		
                    $resData[$gaId][$z] = array($cost, $conv, $cpl);
                    //d($arr_goo);
                }
            }
            //echo  "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'"; 
            //mysqli_query($conn, "UPDATE cashflow SET g_spent='".implode(',',$gSpent)."' WHERE tbl_id='".$k."'") or die(mysqli_error());		
        }
    }
}

//d($gData);
?>
<span style="float:right; margin-top:10px;">
    <a href="loading.php?pg=gSquare-fb.php">Facebook</a> | <a href="loading.php?pg=gSquare-g.php">Google</a>
</span>
<h2>G Square - Google ads dashboard</h2>
<table>
    <tr>
        <th rowspan="2">SNo</th>
        <th rowspan="2">Account Name</th>
        <th colspan="3">Today</th>
        <th colspan="3">Yesterday</th>
        <th colspan="3">Last 30days</th>
    </tr>
    <tr>
        <th>Spend</th>
        <th>Leads</th>
        <th>CPL</th>

        <th>Spend</th>
        <th>Leads</th>
        <th>CPL</th>

        <th>Spend</th>
        <th>Leads</th>
        <th>CPL</th>
    </tr>
<?php
$i = 1; 
$spend_t_tot = $spend_y_tot = $spend_30_tot = 0;
$lead_t_tot = $lead_y_tot = $lead_30_tot = 0;
$cpl_t_tot = $cpl_y_tot = $cpl_30_tot = 0;

foreach($g_acc_ids as $k => $val) {
    $spend_t = $spend_y = $spend_30 = '-';
    $lead_t = $lead_y = $lead_30 = '-';
    $cpl_t = $cpl_y = $cpl_30 = '-';


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
            <td><?php echo $g_acc_name[$k]; ?></td>

            <td><?php echo round($spend_t); ?></td>
            <td><?php echo round($lead_t); ?></td>
            <td><?php echo round($cpl_t); ?></td>
            
            <td><?php echo round($spend_y); ?></td>
            <td><?php echo round($lead_y); ?></td>
            <td><?php echo round($cpl_y); ?></td>
            
            <td><?php echo round($spend_30); ?></td>
            <td><?php echo round($lead_30); ?></td>
            <td><?php echo round($cpl_30); ?></td>
        </tr>
    <?php
    $i++;
}
?>
        <tr>
            <td colspan="2" style="text-align:center"><b>Total</b></td>

            <td><b><?php echo round($spend_t_tot); ?></b></td>
            <td><b><?php echo round($lead_t_tot); ?></b></td>
            <td><b><?php echo round($spend_t_tot/$lead_t_tot); ?></b></td>
            
            <td><b><?php echo round($spend_y_tot); ?></b></td>
            <td><b><?php echo round($lead_y_tot); ?></b></td>
            <td><b><?php echo round($spend_y_tot/$lead_t_tot); ?></b></td>
            
            <td><b><?php echo round($spend_30_tot); ?></b></td>
            <td><b><?php echo round($lead_30_tot); ?></b></td>
            <td><b><?php echo round($spend_30_tot/$lead_30_tot); ?></b></td>
        </tr>
</table>
