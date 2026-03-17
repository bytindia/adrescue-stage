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
<title>GSquare - Taboola Ads dashboard</title>
<?php
include 'header.php';

$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE email='bytramesh@gmail.com'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 

$post = array(
    "client_id"           => "d0ed8eb186d3422c83defbb56a0178cc",
    "client_secret"       => "3f4b80205a9c45f3bcfd8ea3dd164c00",
    "grant_type"          => "client_credentials",
);

$post2 = array(
    "client_id"           => "ab4c07a63fdf4a89bff0daed8a9be426",
    "client_secret"       => "8a57d7def8a543d0944c86f1f729c012",
    "grant_type"          => "client_credentials",
);
function retTok($t) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_COOKIESESSION, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, "App Client" );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60 );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ));

    curl_setopt($ch, CURLOPT_URL,"https://backstage.taboola.com/backstage/oauth/token");
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($t));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 0);

    $result=curl_exec ($ch);

    $info = curl_getinfo($ch);
    $response = json_decode($result, true);



    if ($info['http_code'] == 200) {
        return $taboola_tok = $response['access_token'];
    } else {
        return '';
    }
}
$taboola_tok = retTok($post);
$taboola_tok2 = retTok($post2);


//$ta_acc_ids = array('bytdigital-inr-gsquare-sc','gsquarechennai-inr-pp-sc');
$ta_acc_ids = array('bytdigital-inr-gsquare-sc','gsquarechennai-inr-pp-sc');
$ta_acc_name = array('G Square Chennai - INR - PP - SC', 'BYT Digital - INR - G Square - SC');
$ta_tok = array($taboola_tok,$taboola_tok2);


foreach($ta_acc_ids as $k => $val) {
    if($filter=='no') {
        
        $url_1 = 'https://backstage.taboola.com/backstage/api/1.0/'.$val.'/reports/campaign-summary/dimensions/day?access_token='.$ta_tok[$k].'&start_date='.date('Y-m-d',strtotime('0 days')).'&end_date='.date('Y-m-d',strtotime('0 days')).'';
        $req_1 = file_get_contents_curl($url_1);
        $res_1 = json_decode($req_1, true);  
        $resData[$val][1] = $res_1;

        
        $url_2 = 'https://backstage.taboola.com/backstage/api/1.0/'.$val.'/reports/campaign-summary/dimensions/day?access_token='.$ta_tok[$k].'&start_date='.date('Y-m-d',strtotime('-1 days')).'&end_date='.date('Y-m-d',strtotime('-1 days')).'';
        $req_2 = file_get_contents_curl($url_2);
        $res_2 = json_decode($req_2, true);  
        $resData[$val][2] = $res_2;
    }
    
     $url_3 = 'https://backstage.taboola.com/backstage/api/1.0/'.$val.'/reports/campaign-summary/dimensions/day?access_token='.$ta_tok[$k].'&start_date='.date('Y-m-d',strtotime($dtRange1)).'&end_date='.date('Y-m-d',strtotime($dtRange2)).'';
    $req_3 = file_get_contents_curl($url_3);
	$res_3 = json_decode($req_3, true);  
    $resData[$val][3] = $res_3;


    //d($resData); exit;
	//d($res_1); exit;
} 
?>

<h3>G Square - Taboola ads dashboard</h3>
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
$lead_t_tot = $lead_y_tot = $lead_30_tot = 0;
$cpl_t_tot = $cpl_y_tot = $cpl_30_tot = 0;

foreach($ta_acc_ids as $k => $val) {
    $spend_t = $spend_y = $spend_30 =  0;
    $lead_t = $lead_y = $lead_30 =  0;
    $cpl_t = $cpl_y = $cpl_30 =  0;
    


    if(isset($resData[$val][1]['results'][0])) {
        $data_t = $resData[$val][1]['results'][0];
        if(isset($data_t['spent'])) { $spend_t = $data_t['spent']; }
        if(isset($data_t['cpa_actions_num'])) { $lead_t = $data_t['cpa_actions_num']; }
        if(isset($data_t['cpa'])) { $cpl_t = $data_t['cpa']; }
    }

    if(isset($resData[$val][2]['results'][0])) {
        $data_y = $resData[$val][2]['results'][0];
        if(isset($data_y['spent'])) { $spend_y = $data_y['spent']; }
        if(isset($data_y['cpa_actions_num'])) { $lead_y = $data_y['cpa_actions_num']; }
        if(isset($data_y['cpa'])) { $cpl_y = $data_y['cpa']; }
    }

    if(isset($resData[$val][3]['results'][0])) {
        $data_30 = $resData[$val][3]['results'][0];
        if(isset($data_30['spent'])) { $spend_30 = $data_30['spent']; }
        if(isset($data_30['cpa_actions_num'])) { $lead_30 = $data_30['cpa_actions_num']; }
        if(isset($data_30['cpa'])) { $cpl_30 = $data_30['cpa']; }
    }
    
    $spend_t_tot += $spend_t; $spend_y_tot += $spend_y; $spend_30_tot += $spend_30;
    $lead_t_tot += $lead_t; $lead_y_tot += $lead_y; $lead_30_tot += $lead_30;
    $cpl_t_tot += $cpl_t; $cpl_y_tot += $cpl_y; $cpl_30_tot += $cpl_30;
    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td class="text-left"><?php echo $ta_acc_name[$k]; ?></td>
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