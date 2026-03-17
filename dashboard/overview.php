<?php session_start();
date_default_timezone_set('Asia/Kolkata'); 
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include '../db.php';
if(!isset($_SESSION['logged'])) {
	$pg = 'login.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
}
$pgName = $_SESSION['client_name'].' : Ads Overview';
?>
<title><?php echo $pgName; ?></title>
<?php
$overview = 1;
$pg='all';
include 'config.php';
include 'header.php';

//$in_acc_ids = array_filter($in_acc_ids);
// d($in_acc_ids); exit;
//FB Ads
$i = 1; 
$spend_t_tot_fb = $spend_y_tot_fb = $spend_30_tot_fb = $spend_30_cpl_tot = 0;
$lead_t_tot_fb = $lead_y_tot_fb = $lead_30_tot_fb = 0;
$cpl_t_tot_fb = $cpl_y_tot_fb = $cpl_30_tot_fb = 0;
$tot_bal = $tot_spent = 0; 

foreach($fb_acc_ids as $k => $val) {
    //$url_3 = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=campaign&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','CONVERSIONS','POST_ENGAGEMENT']}]&limit=500"; //exit;
    $url_3 = "https://graph.facebook.com/".$api_ver."/act_".$val."/insights?level=campaign&fields=objective,spend,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=500";
    
    $req_3 = file_get_contents_curl($url_3);
	$res_3 = json_decode($req_3, true);  
    $resData_fb[$val][3] = $res_3;

    $url_3_bal = "https://graph.facebook.com/".$api_ver."/act_".$val."?fields=amount_spent,balance&access_token=".$access_token."";
   // $req_3_bal = file_get_contents_curl($url_3_bal);
	//$res_3_bal = json_decode($req_3_bal, true);  
   // if(isset($res_3_bal['balance'])) { $tot_bal = $tot_bal + $res_3_bal['balance']; }
   // if(isset($res_3_bal['amount_spent'])) { $tot_spent = $tot_spent + $res_3_bal['amount_spent']; }
} 

foreach($fb_acc_ids as $k => $val) {

    $spend_30 =  $spend_30_cpl = $lead_30 =  $cpl_30 = 0;
    
    if(isset($resData_fb[$val][3]['data'])) {
        foreach($resData_fb[$val][3]['data'] as $k3 => $val3) {
            $data_30 = $val3;
            if(isset($data_30['spend'])) { $spend_30 += $data_30['spend']; }
            if(isset($data_30['objective']) && ($data_30['objective']=='LEAD_GENERATION' || $data_30['objective']=='OUTCOME_LEADS' || $data_30['objective']=='CONVERSIONS')) {
                if(isset($data_30['spend'])) { $spend_30_cpl += $data_30['spend']; }
                if(isset($data_30['actions'])) { if(array_key_exists($data_30['objective'], $obj_arr)) { $lead_30 += LeadGen($data_30['actions'], $obj_arr[$data_30['objective']]); } }
            }
        }
        $cpl_30 = @(round($spend_30_cpl / $lead_30, 2));
    }
    
    
    
    $spend_30_tot_fb += $spend_30; 
    $spend_30_cpl_tot += $spend_30_cpl;
    $lead_30_tot_fb += $lead_30;
    $cpl_30_tot_fb += $cpl_30;
}

//GOOGLE Ads

$i = 1; 
$spend_30_tot_g = 0;
$lead_30_tot_g = 0;
$cpl_30_tot_g = 0;
if(count($g_acc_ids)>0) {
    include '../google-ads.php';
    foreach($g_acc_ids as $k => $val) {
        $spend_30 = 0;
        $lead_30 = 0;
        $cpl_30 = 0;

        if($val!='') {
            $gResq3 = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'],$val,date("Y-m-d", strtotime($dtRange1)), date("Y-m-d", strtotime($dtRange2)));
            if(isset($gResq3['cost'])) { $spend_30 = $gResq3['cost']; }
            if(isset($gResq3['conversions'])) { $lead_30 = $gResq3['conversions']; }
        }

       // d($gResq3); 
        $spend_30_tot_g += $spend_30;
        $lead_30_tot_g += $lead_30;
        //$cpl_30_tot_g += $cpl_30;
        $i++;
    }
} 
//exit;
//LinkedIn
$spend_30_tot_in = 0;
$lead_30_tot_in = 0;
$cpl_30_tot_in = 0;

if(count($in_acc_ids)>0) {
    $client_id = '819hf4iznbxt3r';
    $client_secret = getenv('LINKEDIN_CLIENT_SECRET');
    $userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='2'");	
    $getRw2 = mysqli_fetch_assoc($userRes2);
    $_SESSION['in_id'] = $getRw2['in_id'];
    $_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
                        
    require_once $server_path .'vendor-linkedin/autoload.php';
    $linkedURL ="https://www.linkedin.com/oauth/v2/authorization";				
    $linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
    if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
    $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
    }

    foreach($in_acc_ids as $k => $val) {
        $spend_30 = 0;
        $lead_30 = 0;
        $cpl_30 = 0;

        if($val!='') {
            $taReq3 = LinkedInAPI($val,$linkedIn,$dtRange1,$dtRange2);
            if(isset($taReq3['cost'])) { $spend_30 = $taReq3['cost']; }
            if(isset($taReq3['conversions'])) { $lead_30 = $taReq3['conversions']; }
        }

        
        $spend_30_tot_in += $spend_30;
        $lead_30_tot_in += $lead_30;
        //$cpl_30_tot_g += $cpl_30;
        $i++;
    }
}

$i = 1; 


//Taboola Ads

$spend_30_tot_ta = 0;
$lead_30_tot_ta = 0;
$cpl_30_tot_ta = 0;
if(count($ta_acc_ids)>0) {
    include '../taboola-config.php';

    foreach($ta_acc_ids as $k => $val) {
        $spend_30 = 0;
        $lead_30 = 0;
        $cpl_30 = 0;

        if($val!='') {
            $taReq3 = TaboolaAPI($val,$taboola_tok,$dtRange1,$dtRange2);
            if(isset($taReq3['cost'])) { $spend_30 = $taReq3['cost']; }
            if(isset($taReq3['conversions'])) { $lead_30 = $taReq3['conversions']; }
        }

        
        $spend_30_tot_ta += $spend_30;
        $lead_30_tot_ta += $lead_30;
        //$cpl_30_tot_ta += $cpl_30;
        $i++;
    }
}
$i = 1; 
?>

<table id="datatable" class="table table-hover table-striped table-bordered">
    <tr>
        <th>Source</th>
        <th colspan="3"><?php echo $dtRange; ?></th>
    </tr>
    <tr>
        <th></th>
        
        <th>Spend</th>
        <th>Leads</th>
        <th>CPL</th>
    </tr>
        <?php if(count($fb_acc_ids)>0) { ?>
        <tr>
            <td style="text-align:center"><b><a href="loading.php?pg=facebook.php<?php echo $urlParam; ?>">Facebook</a></b></td>
            <td><b><?php echo moneyFormatIndia($spend_30_tot_fb); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot_fb); ?></b></td>
            <td><b><?php if($lead_30_tot_fb>0) { echo moneyFormatIndia(@($spend_30_cpl_tot/$lead_30_tot_fb)); } else { echo '-'; } ?></b></td>
        </tr>
        <?php   }
         if(count($g_acc_ids)>0) { ?>
        <tr>
            <td style="text-align:center"><b><a href="loading.php?pg=google.php<?php echo $urlParam; ?>">Google</a></b></td>
            <td><b><?php echo moneyFormatIndia($spend_30_tot_g); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot_g); ?></b></td>
            <td><b><?php if($lead_30_tot_g>0) { echo moneyFormatIndia(@($spend_30_tot_g/$lead_30_tot_g)); } else { echo '-'; } ?></b></td>
        </tr>
        <?php   }
         if(count($in_acc_ids)>0) { ?>
        <tr>
            <td style="text-align:center"><b><a href="loading.php?pg=linkedin.php<?php echo $urlParam; ?>">LinkedIn</a></b></td>
            <td><b><?php echo moneyFormatIndia($spend_30_tot_in); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot_in); ?></b></td>
            <td><b><?php if($lead_30_tot_in>0) { echo moneyFormatIndia(@($spend_30_tot_in/$lead_30_tot_in)); } else { echo '-'; } ?></b></td>
        </tr>
        <?php   }
         if(count($ta_acc_ids)>0) { ?>
        <tr>
            <td style="text-align:center"><b><a href="loading.php?pg=taboola.php<?php echo $urlParam; ?>">Taboola</a></b></td>
            <td><b><?php echo moneyFormatIndia($spend_30_tot_ta); ?></b></td>
            <td><b><?php echo moneyFormatIndia($lead_30_tot_ta); ?></b></td>
            <td><b><?php if($lead_30_tot_ta>0) { echo moneyFormatIndia(@($spend_30_tot_ta/$lead_30_tot_ta)); } else { echo '-'; } ?></b></td>
        </tr>
        <?php  } ?>
        <?php
            $spent_tot_overview = $spend_30_tot_fb+$spend_30_tot_g+$spend_30_tot_in+$spend_30_tot_ta;
            $cpl_tot_overview = $lead_30_tot_fb+$lead_30_tot_g+$lead_30_tot_in+$lead_30_tot_ta;
        ?>
        <tr class="tot_row">
            <td style="text-align:center"><b>Total</b></td>
            <td><b><?php echo moneyFormatIndia($spent_tot_overview); ?></b></td>
            <td><b><?php echo moneyFormatIndia($cpl_tot_overview); ?></b></td>
            <td><b><?php if($cpl_tot_overview>0) { echo moneyFormatIndia(@($spent_tot_overview/$cpl_tot_overview)); } else { echo '-'; } ?></b></td>
        </tr>
</table>
<?php include 'footer.php'; ?>