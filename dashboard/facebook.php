<?php session_start();
//echo basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
date_default_timezone_set('Asia/Kolkata'); 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include '../db.php';
if(!isset($_SESSION['logged'])) {
	$pg = 'login.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
}
$pgName = $_SESSION['client_name'].' : Facebook Ads';
?>
<title><?php echo $pgName; ?></title>
<?php
$pg='facebook';
include 'config.php';
include 'header.php';


$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 

//$fb_acc_ids = array(732236261026706, 229650465874267, 880541165925308, 624107499015180,665661671485180);
$fb_acc_name = array('RWD Account 1', 'RWD Account 2 ( New )', 'RWD Grand Corridor', 'RWD Spotlight', 'RWD Ibis County');

$obj_arr = array(
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'lead'
    );

foreach($fb_acc_ids as $k => $val) {
    if($filter=='no') {
        $url_1 = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=campaign&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime('0 days'))."&time_range[until]=".date("Y-m-d", strtotime('0 days'))."&access_token=".$access_token."&limit=500"; //exit;
        $req_1 = file_get_contents_curl($url_1); 
        $res_1 = json_decode($req_1, true);  
        $resData[$val][1] = $res_1; //exit;

        $url_2 = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=campaign&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime('-1 days'))."&time_range[until]=".date("Y-m-d", strtotime('-1 days'))."&access_token=".$access_token."&limit=500";
        $req_2 = file_get_contents_curl($url_2);
        $res_2 = json_decode($req_2, true);  
        $resData[$val][2] = $res_2;
       // d($resData); exit;
    }

    $url_3 = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=campaign&fields=objective,spend,actions,account_name&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=500";
    $req_3 = file_get_contents_curl($url_3);
	$res_3 = json_decode($req_3, true);  
    $resData[$val][3] = $res_3;
} 

//d($fbAccN); exit;
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
        <th>Leads</th>
        <th>CPL</th>

        <th>Spend</th>
        <th>Leads</th>
        <th>CPL</th>
        <?php } ?>
        <th>Spend</th>
        <th>Leads</th>
        <th>CPL</th>
    </tr>
<?php
$i = 1; 
$spend_t_tot = $spend_y_tot = $spend_30_tot = 0;
$spend_t_cpl_tot = $spend_y_cpl_tot = $spend_30_cpl_tot = 0;
$lead_t_tot = $lead_y_tot = $lead_30_tot = 0;
$cpl_t_tot = $cpl_y_tot = $cpl_30_tot = 0;

foreach($fb_acc_ids as $k => $val) {
    $spend_t = $spend_y = $spend_30 =  $spend_t_cpl = $spend_y_cpl = $spend_30_cpl =  0;
    $lead_t = $lead_y = $lead_30 =  0;
    $cpl_t = $cpl_y = $cpl_30 =  0;
    


    //d($resData[$val][2]['data']);
    if(isset($resData[$val][1]['data'])) {
        foreach($resData[$val][1]['data'] as $k1 => $val1) {
            $data_t = $val1;
            if(isset($data_t['spend'])) { $spend_t += $data_t['spend']; }
            if(isset($data_t['objective']) && ($data_t['objective']=='LEAD_GENERATION' || $data_t['objective']=='OUTCOME_LEADS' || $data_t['objective']=='CONVERSIONS')) {
                if(isset($data_t['spend'])) { $spend_t_cpl += $data_t['spend']; }
                if(isset($data_t['actions'])) { if(array_key_exists($data_t['objective'], $obj_arr)) { $lead_t += LeadGen($data_t['actions'], $obj_arr[$data_t['objective']]); } }
            }
        }
        $cpl_t = @(round($spend_t_cpl / $lead_t, 2));
    }

    if(isset($resData[$val][2]['data'])) {
        foreach($resData[$val][2]['data'] as $k2 => $val2) {
            $data_y = $val2;
            if(isset($data_y['spend'])) { $spend_y += $data_y['spend']; }
            if(isset($data_y['objective']) && ($data_y['objective']=='LEAD_GENERATION' || $data_y['objective']=='OUTCOME_LEADS' || $data_y['objective']=='CONVERSIONS')) {
                if(isset($data_y['spend'])) { $spend_y_cpl += $data_y['spend']; }
                if(isset($data_y['actions'])) { if(array_key_exists($data_y['objective'], $obj_arr)) { $lead_y += LeadGen($data_y['actions'], $obj_arr[$data_y['objective']]); } }
            }
        }
        $cpl_y = @(round($spend_y_cpl / $lead_y, 2));
    }

    if(isset($resData[$val][3]['data'])) {
        foreach($resData[$val][3]['data'] as $k3 => $val3) {
            $data_30 = $val3;
            if(isset($data_30['spend'])) { $spend_30 += $data_30['spend']; }
            if(isset($data_30['objective']) && ($data_30['objective']=='LEAD_GENERATION' || $data_30['objective']=='OUTCOME_LEADS' || $data_30['objective']=='CONVERSIONS')) {
                if(isset($data_30['spend'])) { $spend_30_cpl += $data_30['spend']; }
                if(isset($data_30['actions'])) { if(array_key_exists($data_30['objective'], $obj_arr)) { $lead_30 += LeadGen($data_30['actions'], $obj_arr[$data_30['objective']]); } }
            }
            $fb_acc_name[$k] = $data_30['account_name'];
        }
        $cpl_30 = @(round($spend_30_cpl / $lead_30, 2));
    }
    

   
    
    $spend_t_tot += $spend_t; $spend_y_tot += $spend_y; $spend_30_tot += $spend_30;
    $spend_t_cpl_tot += $spend_t_cpl; $spend_y_cpl_tot += $spend_y_cpl; $spend_30_cpl_tot += $spend_30_cpl;
    $lead_t_tot += $lead_t; $lead_y_tot += $lead_y; $lead_30_tot += $lead_30;
    $cpl_t_tot += $cpl_t; $cpl_y_tot += $cpl_y; $cpl_30_tot += $cpl_30;
    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td class="text-left"><a href="loading.php?pg=campaigns-fb.php?id=<?php echo $val; ?>" target="_blank"><?php echo $fbAccN[$val]; ?></a></td>
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