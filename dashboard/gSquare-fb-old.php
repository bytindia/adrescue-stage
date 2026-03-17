 <?php session_start();
date_default_timezone_set('Asia/Kolkata'); 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include '../db.php';
if(!isset($_SESSION['logged'])) {
	$pg = 'login.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
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
    return $thecash; // writes the final format where $currency is the currency symbol.
}
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
$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE email='bytramesh@gmail.com'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 

$fb_acc_ids = array(383518006666264, 304377234584273, 808913746712244, 1443048976079797, 3085473235009346, 875335466450762, 365878318477021, 961482261110975, 590458382267515);
//$fb_acc_ids = array(383518006666264);
$fb_acc_name = array('G Square Byt New', 'G Square BYT- 6', 'G Square BYT-3','G Square BYT- 5', 'G Square Housing 3', 'G Square BYT- 7', 'G Square Housing', 'G Square Housing 1', 'G Square Housing 2');

//echo $url_3 = "https://graph.facebook.com/v11.0/3085473235009340/insights?level=account&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime('-30 days'))."&time_range[until]=".date("Y-m-d", strtotime('-1 days'))."&access_token=".$access_token."&limit=250"; exit;
function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
$obj_arr = array(
    'POST_ENGAGEMENT' => 'page_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block'
    );

foreach($fb_acc_ids as $k => $val) {
    $url_1 = "https://graph.facebook.com/v11.0/act_".$val."/insights?level=account&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime('0 days'))."&time_range[until]=".date("Y-m-d", strtotime('0 days'))."&access_token=".$access_token."&limit=250";
    $req_1 = file_get_contents_curl($url_1);
	$res_1 = json_decode($req_1, true);  
    $resData[$val][1] = $res_1;

    $url_2 = "https://graph.facebook.com/v11.0/act_".$val."/insights?level=account&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime('-1 days'))."&time_range[until]=".date("Y-m-d", strtotime('-1 days'))."&access_token=".$access_token."&limit=250";
    $req_2 = file_get_contents_curl($url_2);
	$res_2 = json_decode($req_2, true);  
    $resData[$val][2] = $res_2;

    $url_3 = "https://graph.facebook.com/v11.0/act_".$val."/insights?level=account&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime('-30 days'))."&time_range[until]=".date("Y-m-d", strtotime('-1 days'))."&access_token=".$access_token."&limit=250";
    $req_3 = file_get_contents_curl($url_3);
	$res_3 = json_decode($req_3, true);  
    $resData[$val][3] = $res_3;


    //d($resData); exit;
	//d($res_1); exit;
} 
?>
<span style="float:right; margin-top:10px;">
    <a href="gSquare-fb.php">Facebook</a> | <a href="gSquare-g.php">Google</a> | <a href="logout.php">Logout</a>
</span>
<h2>G Square - Facebook ads dashboard</h2>
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

foreach($fb_acc_ids as $k => $val) {
    $spend_t = $spend_y = $spend_30 =  0;
    $lead_t = $lead_y = $lead_30 =  0;
    $cpl_t = $cpl_y = $cpl_30 =  0;
    


    if(isset($resData[$val][1]['data'][0])) {
        $data_t = $resData[$val][1]['data'][0];
        if(isset($data_t['spend'])) { $spend_t = $data_t['spend']; }
        if(isset($data_t['actions'])) { if(array_key_exists($data_t['objective'], $obj_arr)) { $lead_t = LeadGen($data_t['actions'], $obj_arr[$data_t['objective']]);$cpl_t = @(round($spend_t / $lead_t, 2)); } }
    }

    if(isset($resData[$val][2]['data'][0])) {
        $data_y = $resData[$val][2]['data'][0];
        if(isset($data_y['spend'])) { $spend_y = $data_y['spend']; }
        if(isset($data_y['actions'])) { if(array_key_exists($data_y['objective'], $obj_arr)) { $lead_y = LeadGen($data_y['actions'], $obj_arr[$data_y['objective']]);$cpl_y = @(round($spend_y / $lead_y, 2)); } }
    }

    if(isset($resData[$val][3]['data'][0])) {
        $data_30 = $resData[$val][3]['data'][0];
        if(isset($data_30['spend'])) { $spend_30 = $data_30['spend']; }
        if(isset($data_30['actions'])) { if(array_key_exists($data_30['objective'], $obj_arr)) { $lead_30 = LeadGen($data_30['actions'], $obj_arr[$data_30['objective']]);$cpl_30 = @(round($spend_30 / $lead_30, 2)); } }
    }
    
    $spend_t_tot += $spend_t; $spend_y_tot += $spend_y; $spend_30_tot += $spend_30;
    $lead_t_tot += $lead_t; $lead_y_tot += $lead_y; $lead_30_tot += $lead_30;
    $cpl_t_tot += $cpl_t; $cpl_y_tot += $cpl_y; $cpl_30_tot += $cpl_30;
    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td><?php echo $fb_acc_name[$k]; ?></td>

            <td><?php echo moneyFormatIndia($spend_t); ?></td>
            <td><?php echo moneyFormatIndia($lead_t); ?></td>
            <td><?php echo moneyFormatIndia($cpl_t); ?></td>
            
            <td><?php echo moneyFormatIndia($spend_y); ?></td>
            <td><?php echo moneyFormatIndia($lead_y); ?></td>
            <td><?php echo moneyFormatIndia($cpl_y); ?></td>
            
            <td><?php echo moneyFormatIndia($spend_30); ?></td>
            <td><?php echo moneyFormatIndia($lead_30); ?></td>
            <td><?php echo moneyFormatIndia($cpl_30); ?></td>
        </tr>
    <?php
    $i++;
}
?>
        <tr>
            <td colspan="2" style="text-align:center"><b>Total</b></td>

            <td><b><?php echo moneyFormatIndia($spend_t_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_t_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($spend_t_tot/$lead_t_tot); ?></b></td>
            
            <td><b><?php echo moneyFormatIndia($spend_y_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_y_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($spend_y_tot/$lead_t_tot); ?></b></td>
            
            <td><b><?php echo moneyFormatIndia($spend_30_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot); ?></b></td>
            <td><b><?php echo moneyFormatIndia($spend_30_tot/$lead_30_tot); ?></b></td>
        </tr>
</table>