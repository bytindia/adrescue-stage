<?php include 'header.php'; 
date_default_timezone_set("Asia/Calcutta");

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

//Auth();
$pgHeadline = $_GET['name'].' - CPL Comparison ';
$pgID = 3;
$err =''; 

if(isset($_POST['submit'])){
	
	$cirSql = "UPDATE adAccounts SET status='0', updated=now() where uid='".$_SESSION['uid']."'";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	
	if(isset($_POST['ads_id']) && count($_POST['ads_id'])>0) {
		//echo (count($_POST['ads_id'])); 
		$ids = implode(",", $_POST['ads_id']);
		$cirSql2 = "UPDATE adAccounts SET status='1', updated=now() where tbl_id in (".$ids.") && uid='".$_SESSION['uid']."'";
		mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
	} 
	//echo count($_POST['ads_id']); exit;
	//print_r($_POST['ads_id']);
	//exit;
	
	
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'ad-accounts.php';</script>";
	exit();
}

require __DIR__ . '/vendor/autoload.php';

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Exception\RequestException;

include 'config.php';

$api = Api::init($app_id, $app_secret, $access_token);
$api->setLogger(new CurlLogger());

$fields = array(
'spend', 
'reach', 'impressions'
);
$params = array(
'level' => 'account', 
'filtering' => array(), 
'breakdowns' => array(), 
'time_range' => array('since' => date("Y-m-d", strtotime($_SESSION['stDt'])),'until' => date("Y-m-d", strtotime($_SESSION['enDt'])))
);
include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts WHERE uid='".$_SESSION['uid']."'";

//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}
$ac_status = array(
1 => 'ACTIVE',
2 => 'DISABLED',
3 => 'UNSETTLED',
7 => 'PENDING_RISK_REVIEW',
8 => 'PENDING_SETTLEMENT',
9 => 'IN_GRACE_PERIOD',
100 => 'PENDING_CLOSURE',
101 => 'CLOSED',
201 => 'ANY_ACTIVE',
202 => 'ANY_CLOSED'
);
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.blue {
    color: #0987f7;
}
table td a {
    color: #0a8ae6;
}
.dataTables_wrapper input, .dataTables_wrapper select {
    border: 1px solid;
}
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <?php if(isset($_SESSION['fb_id']) || $_SESSION['fb_id']!='') 	{ ?>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> Ad Account Missing? </li>
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=adAccounts.php"  class="btn btn-success btn-sm">Sync Account</a>                
                      	</div>  
                      </li>
                      
                    </ul>
                     <?php } ?>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
						if(!isset($_SESSION['fb_id']) || $_SESSION['fb_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="loading.php?pg=fb-login.php">
                                      <img src="images/fb-login.png">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} 
						else {
							

date_default_timezone_set('Asia/Kolkata');

		$st_30 = date('Y-m-d', strtotime('-30 days'));
		$st_15 = date('Y-m-d', strtotime('-15 days'));
		$st_7 = date('Y-m-d', strtotime('-7 days'));
		$st_3 = date('Y-m-d', strtotime('-3 days'));
		
		$en_30 = date('Y-m-d', strtotime('-30 days'));
		$en_15 = date('Y-m-d', strtotime('-15 days'));
		$en_7 = date('Y-m-d', strtotime('-7 days'));
		$en_3 = date('Y-m-d', strtotime('-3 days'));
		
		$st_4 = date('Y-m-d', strtotime('-4 days'));
		$en_6 = date('Y-m-d', strtotime('-6 days'));
		
		$st_8 = date('Y-m-d', strtotime('-8 days'));
		$en_14 = date('Y-m-d', strtotime('-14 days'));
		
		$st_16 = date('Y-m-d', strtotime('-16 days'));
		$en_30 = date('Y-m-d', strtotime('-30 days'));
		
		$st_31 = date('Y-m-d', strtotime('-31 days'));
		$en_60 = date('Y-m-d', strtotime('-60 days'));
		
		$en_30 = date('Y-m-d', strtotime('-30 days'));
		
		$st_1 = date('Y-m-d', strtotime('-1 days'));
		$en_1 = date('Y-m-d', strtotime('-1 days'));
		
		$accId = $_GET['act'];
		//$accId = 3060331874017269;
		/*
		echo  date('d-m-Y H:i:s');
		echo '<br>';
		echo "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by adset_id";
		echo '<br>';
		echo "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='LEAD_GENERATION' AND sDate>='".$en_3."' AND eDate<='".$st_1."' GROUP by adset_id"; 
		echo '<br>';
		*/
		
		$obj_wise_res_arr = $lgCPL30_id = $convCPL30_id = $lg_camp_ids_30 = $conv_camp_ids_30 = array();
		
		$q1 = mysqli_query($conn, "SELECT obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl as a WHERE accId='".$accId." 'AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by obj_type HAVING b!=0 ORDER BY obj_type ASC");
		
		while($r1 = mysqli_fetch_array($q1)) {  
			$obj_wise_res_arr[] = '<tr><td>'.ucfirst(strtolower(str_replace('_',' ',$r1['obj_type']))).'</td><td>'.round($r1['a']).'</td><td>'.round($r1['b']).'</td><td>'.round($r1['c'],2).'</td></tr>';
			if($r1['obj_type']=='') {
			}
		}
		
		$lg_tbl = $conv_tbl = $obj_wise_res = 'No Records Found!';
		
		if(count($obj_wise_res_arr)>0) {
					$obj_wise_res = '<table id="TABLE_9" class="table table-bordered dt-responsive compact"><thead><tr><th>Objective</th><th>Spend &#8377;</th><th>Results</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$obj_wise_res_arr).'</tbody></table>';
		}
		echo '<h3>Objective wise Results</h3> <small>Last 30d</small>';
		echo $obj_wise_res;
		echo '<br><br>'; 
		
		echo '<h3>LG - CPL Comparison</h3>';
		$lg_cpl_3 = $lg_cpl_7 = $lg_cpl_15 = $lg_cpl_30 = $lg_adset_cpl_3 = $lg_adset_cpl_7 = $lg_adset_cpl_15 = $lg_adset_cpl_30 = $lg_adset_name_30 = array();
		$lg_cpl_3_2 = $lg_cpl_7_2 = $lg_cpl_15_2 = $lg_cpl_30_2 = $lg_adset_cpl_3_2 = $lg_adset_cpl_7_2 = $lg_adset_cpl_15_2 = $lg_adset_cpl_30_2 = $lg_adset_name_30_2 = array();

		$lg_spend_3 = $lg_lead_3 = $lg_spend_7 = $lg_lead_7 = $lg_spend_15 = $lg_lead_15 = $lg_spend_30 = $lg_lead_30 = $lg_spend_3_2 = $lg_lead_3_2 = $lg_spend_15_2 = $lg_lead_15_2 = $lg_spend_30_2 = $lg_lead_30_2 = 0;
		
		$sql_1 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by adset_id");
		while($row1 = mysqli_fetch_array($sql_1)) {
			$lg_cpl_3[$row1['adset_id']] = round($row1['c']);
			$lg_spend_3 = $lg_spend_3 + round($row1['a']);
			$lg_lead_3 = $lg_lead_3 + round($row1['b']);
		}
		
		$sql_2 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_14."' AND eDate<='".$st_8."' GROUP by adset_id");
		while($row2 = mysqli_fetch_array($sql_2)) {
			$lg_cpl_7[$row2['adset_id']] = round($row2['c']);
			$lg_spend_7 = $lg_spend_7 + round($row2['a']);
			$lg_lead_7 = $lg_lead_7 + round($row2['b']);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_30."' AND eDate<='".$st_16."' GROUP by adset_id");
		while($row3 = mysqli_fetch_array($sql_3)) {
			$lg_cpl_15[$row3['adset_id']] = round($row3['c']);
			$lg_spend_15 = $lg_spend_15 + round($row3['a']);
			$lg_lead_15 = $lg_lead_15 + round($row3['b']);
		}
		
		$sql_4 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_60."' AND eDate<='".$st_31."' GROUP by adset_id");
		while($row4 = mysqli_fetch_array($sql_4)) {
			$lg_cpl_30[$row4['adset_id']] = round($row4['c']);
			$lg_spend_30 = $lg_spend_30 + round($row4['a']);
			$lg_lead_30 = $lg_lead_30 + round($row4['b']);
		}
		
		$sql_11 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_3."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row11 = mysqli_fetch_array($sql_11)) {
			$lg_cpl_3_2[$row11['adset_id']] = round($row11['c']);
			$lg_spend_3_2 = $lg_spend_3_2 + round($row11['a']);
			$lg_lead_3_2 = $lg_lead_3_2 + round($row11['b']);
		}
		//d($lg_cpl_3); d($lg_adset_cpl_3);
		$sql_12 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_7."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row12 = mysqli_fetch_array($sql_12)) {
			$lg_cpl_7_2[$row12['adset_id']] = round($row12['c']);
			$lg_spend_7_2 = $lg_spend_7_2 + round($row12['a']);
			$lg_lead_7_2 = $lg_lead_7_2 + round($row12['b']);
		}
		
		$sql_13 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_15."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row13 = mysqli_fetch_array($sql_13)) {
			$lg_cpl_15_2[$row13['adset_id']] = round($row13['c']);
			$lg_spend_15_2 = $lg_spend_15_2 + round($row13['a']);
			$lg_lead_15_2 = $lg_lead_15_2 + round($row13['b']);
		}
		
		$sql_14 = mysqli_query($conn, "SELECT adset_id, adset_name, campaign_id, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row14 = mysqli_fetch_array($sql_14)) { 
			$lgCPL30_id[]= $row14['adset_id'];
			$lg_camp_ids_30[$row14['adset_id']] = $row14['campaign_id'];
			$lg_cpl_30_2[$row14['adset_id']] = round($row14['c']);
			$lg_spend_30_2 = $lg_spend_30_2 + round($row14['a']);
			$lg_lead_30_2 = $lg_lead_30_2 + round($row14['b']);
			$lg_adset_name_30[$row14['adset_id']] = '<a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_adset_ids='.$row14['adset_id'].'" target="_blank">'.$row14['adset_name'].'</a>';
			$lg_camp_name_30[$row14['adset_id']] = '<a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/adsets?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_adset_ids='.$row14['adset_id'].'" target="_blank">'.$row14['campaign_name'].'</a>';
		}
		
		if(count($lgCPL30_id)>0) {
			
			$lg_tbl = '<table id="TABLE_8" class="table table-bordered dt-responsive compact"><thead><tr><th>Campaign Name</th><th>AdSet Name</th><th>Last 3d / Prev. 3d </th><th>Last 7d / Prev. 7d</th><th>Last 15d / Prev. 15d</th><th>Last 30d / Prev. 30d</th></tr></thead><tbody>';
			
			foreach ($lgCPL30_id as $key => $v) {
				
				$val_lg_3 = $val_lg_3_2 = $val_lg_7 = $val_lg_7_2 = $val_lg_15 = $val_lg_15_2 = $val_lg_30 =$val_lg_30_2 = '-';
				//$val_lg_3_tot = $val_lg_3_2_tot = $val_lg_7_tot = $val_lg_7_2_tot = $val_lg_15_tot = $val_lg_15_2_tot = $val_lg_30_tot =$val_lg_30_2_tot = 0;
				
				if(isset($lg_cpl_3[$v])) { $val_lg_3 = $lg_cpl_3[$v]; } 
				if(isset($lg_cpl_7[$v])) { $val_lg_7 = $lg_cpl_7[$v]; } 
				if(isset($lg_cpl_15[$v])) { $val_lg_15 = $lg_cpl_15[$v]; }
				if(isset($lg_cpl_30[$v])) { $val_lg_30 = $lg_cpl_30[$v]; }
				
				if(isset($lg_cpl_3_2[$v])) { $val_lg_3_2 = $lg_cpl_3_2[$v]; } 
				if(isset($lg_cpl_7_2[$v])) { $val_lg_7_2 = $lg_cpl_7_2[$v]; } 
				if(isset($lg_cpl_15_2[$v])) { $val_lg_15_2 = $lg_cpl_15_2[$v]; } 
				if(isset($lg_cpl_30_2[$v])) { $val_lg_30_2 = $lg_cpl_30_2[$v]; } 
				
				if(isset($lg_cpl_3[$v]) && $lg_cpl_3[$v]<round($val_lg_3_2) && $lg_cpl_3[$v]!=0) { $cls_1='red';  } else { $cls_1='green';  }
				if(isset($lg_cpl_7[$v]) && $lg_cpl_7[$v]<round($val_lg_7_2) && $lg_cpl_7[$v]!=0) { $cls_2='red';  } else { $cls_2='green';  }
				if(isset($lg_cpl_15[$v]) && $lg_cpl_15[$v]<round($val_lg_15_2) && $lg_cpl_15[$v]!=0) { $cls_3='red';  } else { $cls_3='green';  }
				if(isset($lg_cpl_30[$v]) && $lg_cpl_30[$v]<round($val_lg_30_2) && $lg_cpl_30[$v]!=0) { $cls_4='red';  } else { $cls_4='green';  }
				
				$Link_url = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$lg_camp_ids_30[$v].'&selected_adset_ids='.$v;
				
				$lg_tbl .= '<tr><td>'.$lg_camp_name_30[$v].'</td>'; 
				$lg_tbl .= '<td>'.$lg_adset_name_30[$v].'</td>'; 
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$val_lg_3_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$val_lg_3.'</a> ('.$lg_cpl_30_2[$v].')</td>'; 
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$val_lg_7_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$val_lg_7.'</a> ('.$lg_cpl_30_2[$v].')</td>';
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$val_lg_15_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$val_lg_15.'</a> ('.$lg_cpl_30_2[$v].')</td>';
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$val_lg_30_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_60.'_'.$st_31.'" target="_blank">'.$val_lg_30.'</a></td>'; 
				$lg_tbl .= '</tr>'; 
				
			}
			$Link_url2 = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId;

			$totCpl_3 =  @(round($lg_spend_3/$lg_lead_3)); 
			$totCpl_7 =  @(round($lg_spend_7/$lg_lead_7));
			$totCpl_15 =  @(round($lg_spend_15/$lg_lead_15));
			$totCpl_30 =  @(round($lg_spend_30/$lg_lead_30));

			$totCpl_3_2 =  @(round($lg_spend_3_2/$lg_lead_3_2)); 
			$totCpl_7_2 =  @(round($lg_spend_7_2/$lg_lead_7_2));
			$totCpl_15_2 =  @(round($lg_spend_15_2/$lg_lead_15_2));
			$totCpl_30_2 =  @(round($lg_spend_30_2/$lg_lead_30_2));

			if(isset($totCpl_3) && $totCpl_3<round($totCpl_3_2) && $totCpl_3!=0) { $cls_1='red';  } else { $cls_1='green';  }
			if(isset($totCpl_7) && $totCpl_7<round($totCpl_7_2) && $totCpl_7!=0) { $cls_2='red';  } else { $cls_2='green';  }
			if(isset($totCpl_15) && $totCpl_15<round($totCpl_15_2) && $totCpl_15!=0) { $cls_3='red';  } else { $cls_3='green';  }
			if(isset($totCpl_30) && $totCpl_30<round($totCpl_30_2) && $totCpl_30!=0) { $cls_4='red';  } else { $cls_4='green';  }

			$lg_tbl .= '<tr><td colspan="2">Total</td>'; 
				//$lg_tbl .= '<td>'.$lg_adset_name_30[$v].'</td>'; 
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$totCpl_3_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$totCpl_3.'</a> ('.$totCpl_30_2.')</td>'; 
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$totCpl_7_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$totCpl_7.'</a> ('.$totCpl_30_2.')</td>';
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$totCpl_15_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$totCpl_15.'</a> ('.$totCpl_30_2.')</td>';
				$lg_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$totCpl_30_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_60.'_'.$st_31.'" target="_blank">'.$totCpl_30.'</a></td>'; 
				$lg_tbl .= '</tr>'; 

			$lg_tbl .= '</tbody></table>';
		}
		
		echo $lg_tbl; 
		echo '<br><br>'; 
		//exit;
		
		
		echo '<h3>Conversion - CPL Comparison</h3>';
		$conv_cpl_3 = $conv_cpl_7 = $conv_cpl_15 = $conv_cpl_30 = $conv_adset_cpl_3 = $conv_adset_cpl_7 = $conv_adset_cpl_15 = $conv_adset_cpl_30 = $conv_adset_name_30 = array();
		$conv_cpl_3_2 = $conv_cpl_7_2 = $conv_cpl_15_2 = $conv_cpl_30_2 = $conv_adset_cpl_3_2 = $conv_adset_cpl_7_2 = $conv_adset_cpl_15_2 = $conv_adset_cpl_30_2 = $conv_adset_name_30_2 = array();
		$conv_spend_3 = $conv_lead_3 = $conv_spend_7 = $conv_lead_7 = $conv_spend_15 = $conv_lead_15 = $conv_spend_30 = $conv_lead_30 = $conv_spend_3_2 = $conv_lead_3_2 = $conv_spend_15_2 = $conv_lead_15_2 = $conv_spend_30_2 = $conv_lead_30_2 = 0;
		
		$sql_1 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by adset_id");
		while($row1 = mysqli_fetch_array($sql_1)) {
			$conv_cpl_3[$row1['adset_id']] = round($row1['c']);
			$conv_spend_3 = $conv_spend_3 + round($row1['a']);
			$conv_lead_3 = $conv_lead_3 + round($row1['b']);
		}
		
		$sql_2 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_14."' AND eDate<='".$st_8."' GROUP by adset_id");
		while($row2 = mysqli_fetch_array($sql_2)) {
			$conv_cpl_7[$row2['adset_id']] = round($row2['c']);
			$conv_spend_7 = $conv_spend_7 + round($row2['a']);
			$conv_lead_7 = $conv_lead_7 + round($row2['b']);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_30."' AND eDate<='".$st_16."' GROUP by adset_id");
		while($row3 = mysqli_fetch_array($sql_3)) {
			$conv_cpl_15[$row3['adset_id']] = round($row3['c']);
			$conv_spend_15 = $conv_spend_15 + round($row3['a']);
			$conv_lead_15 = $conv_lead_15 + round($row3['b']);
		}
		
		$sql_4 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_60."' AND eDate<='".$st_31."' GROUP by adset_id");
		while($row4 = mysqli_fetch_array($sql_4)) {
			$conv_cpl_30[$row4['adset_id']] = round($row4['c']);
			$conv_spend_30 = $conv_spend_30 + round($row4['a']);
			$conv_lead_30 = $conv_lead_30 + round($row4['b']);
		}
		
		$sql_11 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_3."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row11 = mysqli_fetch_array($sql_11)) {
			$conv_cpl_3_2[$row11['adset_id']] = round($row11['c']);
			$conv_spend_3_2 = $conv_spend_3_2 + round($row11['a']);
			$conv_lead_3_2 = $conv_lead_3_2 + round($row11['b']);
		}
		//d($conv_cpl_3); d($conv_adset_cpl_3);
		$sql_12 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_7."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row12 = mysqli_fetch_array($sql_12)) {
			$conv_cpl_7_2[$row12['adset_id']] = round($row12['c']);
			$conv_spend_7_2 = $conv_spend_7_2 + round($row11['a']);
			$conv_lead_7_2 = $conv_lead_7_2 + round($row11['b']);
		}
		
		$sql_13 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_15."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row13 = mysqli_fetch_array($sql_13)) {
			$conv_cpl_15_2[$row13['adset_id']] = round($row13['c']);
			$conv_spend_15_2 = $conv_spend_15_2 + round($row11['a']);
			$conv_lead_15_2 = $conv_lead_15_2 + round($row11['b']);
		}
		
		$sql_14 = mysqli_query($conn, "SELECT adset_id, adset_name, campaign_id, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row14 = mysqli_fetch_array($sql_14)) {
			$convCPL30_id[]= $row14['adset_id'];
			$conv_camp_ids_30[$row14['adset_id']] = $row14['campaign_id'];
			$conv_cpl_30_2[$row14['adset_id']] = round($row14['c']);
			$conv_spend_30_2 = $conv_spend_30_2 + round($row11['a']);
			$conv_lead_30_2 = $conv_lead_30_2 + round($row11['b']);
			$conv_adset_name_30[$row14['adset_id']] = '<a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_adset_ids='.$row14['adset_id'].'" target="_blank">'.$row14['adset_name'].'</a>';
			$conv_camp_name_30[$row14['adset_id']] = '<a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/adsets?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_adset_ids='.$row14['adset_id'].'" target="_blank">'.$row14['campaign_name'].'</a>';
		}
		
		if(count($convCPL30_id)>0) {
			
			$conv_tbl = '<table id="TABLE_8" class="table table-bordered dt-responsive compact"><thead><tr><th>Campaign Name</th><th>AdSet Name</th><th>Last 3d / Prev. 3d </th><th>Last 7d / Prev. 7d</th><th>Last 15d / Prev. 15d</th><th>Last 30d / Prev. 30d</th></tr></thead><tbody>';
			
			foreach ($convCPL30_id as $key => $v) {
				
				$val_conv_3 = $val_conv_3_2 = $val_conv_7 = $val_conv_7_2 = $val_conv_15 = $val_conv_15_2 = $val_conv_30 =$val_conv_30_2 = '-';
				
				if(isset($conv_cpl_3[$v])) { $val_conv_3 = $conv_cpl_3[$v]; } 
				if(isset($conv_cpl_7[$v])) { $val_conv_7 = $conv_cpl_7[$v]; } 
				if(isset($conv_cpl_15[$v])) { $val_conv_15 = $conv_cpl_15[$v]; }
				if(isset($conv_cpl_30[$v])) { $val_conv_30 = $conv_cpl_30[$v]; }
				
				if(isset($conv_cpl_3_2[$v])) { $val_conv_3_2 = $conv_cpl_3_2[$v]; } 
				if(isset($conv_cpl_7_2[$v])) { $val_conv_7_2 = $conv_cpl_7_2[$v]; } 
				if(isset($conv_cpl_15_2[$v])) { $val_conv_15_2 = $conv_cpl_15_2[$v]; } 
				if(isset($conv_cpl_30_2[$v])) { $val_conv_30_2 = $conv_cpl_30_2[$v]; } 
				
				if(isset($conv_cpl_3[$v]) && $conv_cpl_3[$v]<round($val_conv_3_2) && $conv_cpl_3[$v]!=0) { $cls_1='red';  } else { $cls_1='green';  }
				if(isset($conv_cpl_7[$v]) && $conv_cpl_7[$v]<round($val_conv_7_2) && $conv_cpl_7[$v]!=0) { $cls_2='red';  } else { $cls_2='green';  }
				if(isset($conv_cpl_15[$v]) && $conv_cpl_15[$v]<round($val_conv_15_2) && $conv_cpl_15[$v]!=0) { $cls_3='red';  } else { $cls_3='green';  }
				if(isset($conv_cpl_30[$v]) && $conv_cpl_30[$v]<round($val_conv_30_2) && $conv_cpl_30[$v]!=0) { $cls_4='red';  } else { $cls_4='green';  }
				
				$Link_url = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$conv_camp_ids_30[$v].'&selected_adset_ids='.$v;
				
				$conv_tbl .= '<tr><td>'.$conv_camp_name_30[$v].'</td>'; 
				$conv_tbl .= '<td>'.$conv_adset_name_30[$v].'</td>'; 
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$val_conv_3_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$val_conv_3.'</a> ('.$conv_cpl_30_2[$v].')</td>'; 
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$val_conv_7_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$val_conv_7.'</a> ('.$conv_cpl_30_2[$v].')</td>';
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$val_conv_15_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$val_conv_15.'</a> ('.$conv_cpl_30_2[$v].')</td>';
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$val_conv_30_2.'</span></a> / <a href="loading.php?pg='.$Link_url.'&date='.$en_60.'_'.$st_31.'" target="_blank">'.$val_conv_30.'</a></td>'; 
				$conv_tbl .= '</tr>'; 
			}

			$Link_url2 = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId;

			$totCplc_3 =  @(round($conv_spend_3/$conv_lead_3)); 
			$totCplc_7 =  @(round($conv_spend_7/$conv_lead_7));
			$totCplc_15 =  @(round($conv_spend_15/$conv_lead_15));
			$totCplc_30 =  @(round($conv_spend_30/$conv_lead_30));

			$totCplc_3_2 =  @(round($conv_spend_3_2/$conv_lead_3_2)); 
			$totCplc_7_2 =  @(round($conv_spend_7_2/$conv_lead_7_2));
			$totCplc_15_2 =  @(round($conv_spend_15_2/$conv_lead_15_2));
			$totCplc_30_2 =  @(round($conv_spend_30_2/$conv_lead_30_2));

			if(isset($totCplc_3) && $totCplc_3<round($totCplc_3_2) && $totCplc_3!=0) { $cls_1='red';  } else { $cls_1='green';  }
			if(isset($totCplc_7) && $totCplc_7<round($totCplc_7_2) && $totCplc_7!=0) { $cls_2='red';  } else { $cls_2='green';  }
			if(isset($totCplc_15) && $totCplc_15<round($totCplc_15_2) && $totCplc_15!=0) { $cls_3='red';  } else { $cls_3='green';  }
			if(isset($totCplc_30) && $totCplc_30<round($totCplc_30_2) && $totCplc_30!=0) { $cls_4='red';  } else { $cls_4='green';  }

			$conv_tbl .= '<tr><td colspan="2">Total</td>'; 
				//$conv_tbl .= '<td>'.$conv_adset_name_30[$v].'</td>'; 
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$totCplc_3_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$totCplc_3.'</a> ('.$totCplc_30_2.')</td>'; 
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$totCplc_7_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$totCplc_7.'</a> ('.$totCplc_30_2.')</td>';
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$totCplc_15_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$totCplc_15.'</a> ('.$totCplc_30_2.')</td>';
				$conv_tbl .='<td><a href="loading.php?pg='.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$totCplc_30_2.'</span></a> / <a href="loading.php?pg='.$Link_url2.'&date='.$en_60.'_'.$st_31.'" target="_blank">'.$totCplc_30.'</a></td>'; 
				$conv_tbl .= '</tr>'; 

			$conv_tbl .= '</tbody></table>';
		}
		
		echo $conv_tbl; 
		
						} ?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<script>
$(document).ready(function() {
    //$('table.table').DataTable();
	$('table.table').dataTable( {
    "iDisplayLength": 50
  } );
} );
</script>