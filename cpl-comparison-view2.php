<?php include 'header.php'; 
date_default_timezone_set("Asia/Calcutta");

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
//error_reporting(E_ALL); ini_set('display_errors', '1');
//Auth();

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
$sepIcon = ' <img src="images/sepr4.png" style="height:22px;" /> ';
$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts WHERE account_id='".$_GET['act']."'";
$sqlRev=mysqli_query($conn, "SELECT name FROM ".$statement." order by name asc LIMIT 1");
$sqlROW=mysqli_fetch_row($sqlRev);
$clName = $sqlROW[0];
$pgHeadline = $clName.' - ROAS / CPL Comparison ';
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

function formatRep($v1, $v2, $url1, $url2, $high, $sep)
{

	global $sepIcon;
	global $fmt;

	if($sep=='n') { $sepIcon2 = ''; } else { $sepIcon2 = $sepIcon; }
	
	if($v1=='' || $v1==0) { $v1='-'; }
	if($v2=='' || $v2==0) { $v2='-'; }
	if($v1=='-' && $v2=='-') { return ''; }
	if($high=='h'){
		if($v2<$v1) {
			$cls1 = 'green'; $cls2 = 'red'; 
		} else {
			$cls1 = 'red'; $cls2 = 'green'; 
		}
	}
	if($high=='l'){
		if($v2>$v1) {
			$cls1 = 'red'; $cls2 = 'green'; 
		} else {
			$cls1 = 'green'; $cls2 = 'red'; 
		}
	}
	if($high=='') { $cls1 =  $cls2 = ''; }
	$v1_1 = $v1; $v2_1 = $v2;
	if(preg_match('/^\d+\.\d+$/',$v1)==false) { $v1_1 = $fmt->format($v1); }
	if(preg_match('/^\d+\.\d+$/',$v2)==false) { $v2_1 = $fmt->format($v2); }
	//if(preg_match('/^\d+\.\d+$/',$v1)==false) { $x='x'; } else { $x='y'; }
	$l1 = '<a href="'.$url1.'" target="_blank" class="'.$cls1.'">'.$v1_1.'<a>';
	$l2 = '<a href="'.$url2.'" target="_blank" class="'.$cls2.'">'.$v2_1.'<a>';
	if($v1!='-' && $v2!='-') { return $l1.' '.$sepIcon2.' '.$l2; }
	else if($v1=='-' && $v2!='-') { return '- '.$sepIcon2.' '.$l2; }
	else if($v1!='-' && $v2=='-' && $sep=='n') { return $l1.' '.$sepIcon2.''; }
	else if($v1!='-' && $v2=='-' && $sep=='y') { return $l1.' '.$sepIcon2.' -'; }
	else { return '-'; }
}

?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.toggle1 a, .blue {
    color: #0987f7; cursor: pointer;
}
table td a {
    color: #0a8ae6;
}
.dataTables_wrapper input, .dataTables_wrapper select {
    border: 1px solid;
}
thead tr th { text-align: center; } 
h3 { font-size: 20px !important;}
.txt-right, tfoot td { text-align: right; white-space: nowrap; }
.two_line_txt { 
	overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; 
}
.green-bg { background: #e7fbfc; }
.yellow-bg { background: #fcfadd; }
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
                        	<a href="adAccounts.php"  class="btn btn-success btn-sm">Sync Account</a>                
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
                                 <a href="fb-login.php">
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
		$CPL_VALUE = 0;
		$obj_wise_res_arr = $lgCPL30_id = $convCPL30_id = $lg_camp_ids_30 = $conv_camp_ids_30 = array();
		
		$q1 = mysqli_query($conn, "SELECT obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl as a WHERE accId='".$accId." 'AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by obj_type HAVING b!=0 ORDER BY obj_type ASC");
		
		while($r1 = mysqli_fetch_array($q1)) {  

			$roas_obj = round($r1['d'],2);
            //$roas_con_3 = $roas_con_3 + round($row1['e']);
			$CPL_VALUE = round($r1['c'],2);
			$obj_wise_res_arr[] = '<tr><td>'.ucfirst(strtolower(str_replace('_',' ',$r1['obj_type']))).'</td><td>'.$fmt->format(round($r1['a'])).'</td><td>'.$fmt->format(round($r1['b'])).'</td><td class="yellow-bg">'.round($r1['c'],2).'</td><td class="green-bg">'.round($roas_obj,2).'</td></tr>';
			if($r1['obj_type']=='') {
			}
		}
		
		$lg_tbl = $conv_tbl = $obj_wise_res = 'No Records Found!';
		
		if(count($obj_wise_res_arr)>0) {
					$obj_wise_res = '<table id="TABLE_9" class="table table-bordered dt-responsive compact"><thead><tr><th>Objective</th><th>Spend &#8377;</th><th>Results</th><th class="yellow-bg">CPL &#8377;</th><th class="green-bg">ROAS</th></tr></thead><tbody>'.implode('',$obj_wise_res_arr).'</tbody></table>';
		}
		echo '<center><h3>Objective wise Results</h3> <small>Last 30d</small></center>';
		echo $obj_wise_res;
		echo '<br><br>'; 
		?>
		<?
		$extQ .= "";                
		if(isset($_GET['high']) && $_GET['high']==2){
			$extQ .= "HAVING c>'".$CPL_VALUE."'"; $titV = "High CPL";    
		} else {
			$titV = "AdSet";    
		}
        
        $convCPL_HIGH_id = $conv_camp_ids_HIGH = $conv_cpl_HIGH_2 = $spend_High = $roas_High = $leads_High = array();
        $sql_15 = mysqli_query($conn, "SELECT adset_id, adset_name, campaign_id, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by adset_id $extQ");

        while($row15 = mysqli_fetch_array($sql_15)) {
			$convCPL_HIGH_id[]= $row15['adset_id'];
			$conv_camp_ids_HIGH[$row15['adset_id']] = $row15['campaign_id'];
			$conv_cpl_HIGH_2[$row15['adset_id']] = round($row15['c']);
			$spend_High[$row15['adset_id']] = round($row15['a']);
			$leads_High[$row15['adset_id']] = round($row15['b']);
			$roas_High[$row15['adset_id']] = round($row15['d'],2);

			$conv_adset_name_HIGH[$row15['adset_id']] = '<a href="https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row15['campaign_id'].'&selected_adset_ids='.$row15['adset_id'].'" target="_blank">'.$row15['adset_name'].'</a>';
			$conv_camp_name_HIGH[$row15['adset_id']] = '<a href="https://www.facebook.com/adsmanager/manage/adsets?act='.$accId.'&selected_campaign_ids='.$row15['campaign_id'].'&selected_adset_ids='.$row15['adset_id'].'" target="_blank">'.$row15['campaign_name'].'</a>';
		}
//d($convCPL_HIGH_id); d($conv_cpl_HIGH_2);
        if(count($convCPL_HIGH_id)>0) {
			echo '<center><h3 id="high_tit">'.$titV.'</h3><small>Adset level. CPL: '.$CPL_VALUE.' (Last 30d)</small></center>';
			?>
			
			<span class="toggle1">
				<center> Filter : <a href="?act=<?php echo $_GET['act']; ?>&high=1" class="toggle-vis3" data-column="0">All Adset</a> | <a href="?act=<?php echo $_GET['act']; ?>&high=2" class="toggle-vis3" data-column="1">High CPL</a></center>
			</span>
			
			<?
			$high_cpl_tbl = '<table id="TABLE_10" class="table high_cpl table-bordered dt-responsive"><thead><tr><th>AdSet</th><th>Campaign</th><th class="yellow-bg">CPL</th><th>Leads</th><th class="green-bg">ROAS</th><th>Spend</th></tr></thead><tbody>';
			
			foreach ($convCPL_HIGH_id as $key => $v) {
				
				//$conv_cpl_HIGH_2 = '-';

                //if(isset($conv_cpl_HIGH_2[$v])) { $val_conv_HIGH_2 = $conv_cpl_HIGH_2[$v]; } 

				$linkU = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$conv_camp_ids_HIGH[$v].'&selected_adset_ids='.$v.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1))));

                $high_cpl_tbl .= '<tr><td>'.$conv_adset_name_HIGH[$v].'</td>'; 
				$high_cpl_tbl .= '<td>'.$conv_camp_name_HIGH[$v].'</td>'; 
                $high_cpl_tbl .= '<td class="txt-right yellow-bg"><a href="'.$linkU.'" target="_blank">'.$conv_cpl_HIGH_2[$v].'</a></td>';
				$high_cpl_tbl .= '<td class="txt-right"><a href="'.$linkU.'" target="_blank">'.$leads_High[$v].'</a></td>';
				$high_cpl_tbl .= '<td class="txt-right green-bg"><a href="'.$linkU.'" target="_blank">'.$roas_High[$v].'</a></td>';
				$high_cpl_tbl .= '<td class="txt-right"><a href="'.$linkU.'" target="_blank">'.$fmt->format($spend_High[$v]).'</a></td></tr>';
                
            }
            $high_cpl_tbl .= '</tbody></table>';
			
            echo $high_cpl_tbl; 
        }
		
		echo '<br><center><h3>CPL / ROAS</h3><small>Adset level</small></center>';
		$conv_cpl_3 = $conv_cpl_7 = $conv_cpl_15 = $conv_cpl_30 = $conv_adset_cpl_3 = $conv_adset_cpl_7 = $conv_adset_cpl_15 = $conv_adset_cpl_30 = $conv_adset_name_30 = array();
		$conv_cpl_3_2 = $conv_cpl_7_2 = $conv_cpl_15_2 = $conv_cpl_30_2 = $conv_adset_cpl_3_2 = $conv_adset_cpl_7_2 = $conv_adset_cpl_15_2 = $conv_adset_cpl_30_2 = $conv_adset_name_30_2 = $pur_roas_3 = $pur_roas_7 = $pur_roas_15 = $pur_roas_30 = $pur_roas_3_2 = $pur_roas_7_2 = $pur_roas_15_2 = $pur_roas_30_2 = array();

		$conv_spend_3 = $conv_lead_3 = $conv_spend_7 = $conv_lead_7 = $conv_spend_15 = $conv_lead_15 = $conv_spend_30 = $conv_lead_30 = $conv_spend_3_2 = $conv_lead_3_2 = $conv_spend_15_2 = $conv_lead_15_2 = $conv_spend_30_2 = $conv_lead_30_2 = array();

        $tot_roas_con_3 = $tot_roas_con_7 = $tot_roas_con_15 = $tot_roas_con_30 = $tot_roas_con_3_2 = $tot_roas_con_7_2 = $tot_roas_con_15_2 = $tot_roas_con_30_2 = 0;

		$conv_spend_t_3 = $conv_lead_t_3 = $conv_spend_t_7 = $conv_lead_t_7 = $conv_spend_t_15 = $conv_lead_t_15 = $conv_spend_t_30 = $conv_lead_t_30 = $conv_spend_t_3_2 = $conv_lead_t_3_2 = $conv_spend_t_15_2 = $conv_lead_t_15_2 = $conv_spend_t_30_2 = $conv_lead_t_30_2 = 0;
		//echo "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by adset_id"; 
		$sql_1 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by adset_id");
		while($row1 = mysqli_fetch_array($sql_1)) {
			$conv_cpl_3[$row1['adset_id']] = round($row1['c']);
			$conv_spend_t_3 = $conv_spend_t_3 + round($row1['a']);
			$conv_lead_t_3 = $conv_lead_t_3 + round($row1['b']);
			$conv_spend_3[$row1['adset_id']] =  round($row1['a']);
			$conv_lead_3[$row1['adset_id']] =  round($row1['b']);
            $pur_roas_3[$row1['adset_id']] = round($row1['d'],2);
            $tot_roas_con_3 = $tot_roas_con_3 + round($row1['e']);
		}
		//d($conv_cpl_3); d($conv_lead_3);
		$sql_2 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_14."' AND eDate<='".$st_8."' GROUP by adset_id");
		while($row2 = mysqli_fetch_array($sql_2)) {
			$conv_cpl_7[$row2['adset_id']] = round($row2['c']);
			$conv_spend_t_7 = $conv_spend_t_7 + round($row2['a']);
			$conv_lead_t_7 = $conv_lead_t_7 + round($row2['b']);
			$conv_spend_7[$row2['adset_id']] =  round($row2['a']);
			$conv_lead_7[$row2['adset_id']] =  round($row2['b']);
            $pur_roas_7[$row2['adset_id']] = round($row2['d'],2);
            $tot_roas_con_7 = $tot_roas_con_7 + round($row2['e']);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_30."' AND eDate<='".$st_16."' GROUP by adset_id");
		while($row3 = mysqli_fetch_array($sql_3)) {
			$conv_cpl_15[$row3['adset_id']] = round($row3['c']);
			$conv_spend_t_15 = $conv_spend_t_15 + round($row3['a']);
			$conv_lead_t_15 = $conv_lead_t_15 + round($row3['b']);
			$conv_spend_15[$row3['adset_id']] =  round($row3['a']);
			$conv_lead_15[$row3['adset_id']] =  round($row3['b']);
            $pur_roas_15[$row3['adset_id']] = round($row3['d'],2);
            $tot_roas_con_15 = $tot_roas_con_15 + round($row3['e']);
		}
		
		$sql_4 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_60."' AND eDate<='".$st_31."' GROUP by adset_id");
		while($row4 = mysqli_fetch_array($sql_4)) {
			$conv_cpl_30[$row4['adset_id']] = round($row4['c']);
			$conv_spend_t_30 = $conv_spend_t_30 + round($row4['a']);
			$conv_lead_t_30 = $conv_lead_t_30 + round($row4['b']);
			$conv_spend_3[$row4['adset_id']] =  round($row4['a']);
			$conv_lead_3[$row4['adset_id']] =  round($row4['b']);
            $pur_roas_30[$row4['adset_id']] = round($row4['d'],2);
            $tot_roas_con_30 = $tot_roas_con_30 + round($row4['e']);
		}
		
		$sql_11 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_3."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row11 = mysqli_fetch_array($sql_11)) {
			$conv_cpl_3_2[$row11['adset_id']] = round($row11['c']);
			$conv_spend_t_3_2 = $conv_spend_t_3_2 + round($row11['a']);
			$conv_lead_t_3_2 = $conv_lead_t_3_2 + round($row11['b']);
			$conv_spend_3_2[$row11['adset_id']] =  round($row11['a']);
			$conv_lead_3_2[$row11['adset_id']] =  round($row11['b']);
            $pur_roas_3_2[$row11['adset_id']] = round($row11['d'],2);
            $tot_roas_con_3_2 = $tot_roas_con_3_2 + round($row11['e']);
		}
		
		$sql_12 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_7."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row12 = mysqli_fetch_array($sql_12)) {
			$conv_cpl_7_2[$row12['adset_id']] = round($row12['c']);
			$conv_spend_t_7_2 = $conv_spend_t_7_2 + round($row12['a']);
			$conv_lead_t_7_2 = $conv_lead_t_7_2 + round($row12['b']);
			$conv_spend_7_2[$row12['adset_id']] =  round($row12['a']);
			$conv_lead_7_2[$row12['adset_id']] =  round($row12['b']);
            $pur_roas_7_2[$row12['adset_id']] = round($row12['d'],2);
            $tot_roas_con_7_2 = $tot_roas_con_7_2 + round($row12['e']);
		}
		
		$sql_13 = mysqli_query($conn, "SELECT adset_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_15."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row13 = mysqli_fetch_array($sql_13)) {
			$conv_cpl_15_2[$row13['adset_id']] = round($row13['c']);
			$conv_spend_t_15_2 = $conv_spend_t_15_2 + round($row13['a']);
			$conv_lead_t_15_2 = $conv_lead_t_15_2 + round($row13['b']);
			$conv_spend_15_2[$row13['adset_id']] =  round($row13['a']);
			$conv_lead_15_2[$row13['adset_id']] =  round($row13['b']);
            $pur_roas_15_2[$row13['adset_id']] = round($row13['d'],2);
            $tot_roas_con_15_2 = $tot_roas_con_15_2 + round($row13['e']);
		}
		//echo "SELECT adset_id, adset_name, campaign_id, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by adset_id"; 

		$sql_14 = mysqli_query($conn, "SELECT adset_id, adset_name, campaign_id, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl WHERE accId='".$accId."' AND obj_type='CONVERSIONS' AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by adset_id");
		while($row14 = mysqli_fetch_array($sql_14)) {
			$convCPL30_id[]= $row14['adset_id'];
			$conv_camp_ids_30[$row14['adset_id']] = $row14['campaign_id'];
			$conv_cpl_30_2[$row14['adset_id']] = round($row14['c']);
			$conv_spend_t_30_2 = $conv_spend_t_30_2 + round($row14['a']);
			$conv_lead_t_30_2 = $conv_lead_t_30_2 + round($row14['b']);
			$conv_spend_30_2[$row14['adset_id']] =  round($row14['a']);
			$conv_lead_30_2[$row14['adset_id']] =  round($row14['b']);
            $pur_roas_30_2[$row14['adset_id']] = round($row14['d'],2);
            $tot_roas_con_30_2 = $tot_roas_con_30_2 + round($row14['e']);

			$conv_adset_name_30[$row14['adset_id']] = '<a href="https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_adset_ids='.$row14['adset_id'].'" target="_blank">'.$row14['adset_name'].'</a>';
			$conv_camp_name_30[$row14['adset_id']] = '<a href="https://www.facebook.com/adsmanager/manage/adsets?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_adset_ids='.$row14['adset_id'].'" target="_blank">'.$row14['campaign_name'].'</a>';
		}
		
		if(count($convCPL30_id)>0) {
			?>
			<span class="toggle1">
				<center> Toggle Column: <a class="toggle-vis2" data-column="0">AdSet</a> | <a class="toggle-vis2" data-column="1">Campaign</a></center>
			</span>
			<?
			$conv_tbl = '<table id="TABLE_9" class="table table-bordered dt-responsive compact table_roas"><thead><tr><th rowspan="2">AdSet</th><th rowspan="2">Campaign</th><th colspan="4">L 3d | P 3d </th><th colspan="4">L 7d | P 7d</th><th colspan="4">L 15d | P 15d</th><th colspan="4">L 30d </th></tr><tr><th class="yellow-bg">CPL</th><th>Conv.</th><th class="green-bg">ROAS</th><th>Spend</th><th class="yellow-bg">CPL</th><th>Conv.</th><th class="green-bg">ROAS</th><th>Spend</th><th class="yellow-bg">CPL</th><th>Conv.</th><th class="green-bg">ROAS</th><th>Spend</th><th class="yellow-bg">CPL</th><th>Conv.</th><th class="green-bg">ROAS</th><th>Spend</th></tr></thead><tbody>';
			
			foreach ($convCPL30_id as $key => $v) {
				
				$val_conv_3 = $val_conv_3_2 = $val_conv_7 = $val_conv_7_2 = $val_conv_15 = $val_conv_15_2 = $val_conv_30 =$val_conv_30_2 = '-';
                $roas_conv_3 = $roas_conv_3_2 = $roas_conv_7 = $roas_conv_7_2 = $roas_conv_15 = $roas_conv_15_2 = $roas_conv_30 =$roas_conv_30_2 = '-';
				
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

                if(isset($pur_roas_3[$v])) { $roas_conv_3 = $pur_roas_3[$v]; } 
				if(isset($pur_roas_7[$v])) { $roas_conv_7 = $pur_roas_7[$v]; } 
				if(isset($pur_roas_15[$v])) { $roas_conv_15 = $pur_roas_15[$v]; }
				if(isset($pur_roas_30[$v])) { $roas_conv_30 = $pur_roas_30[$v]; }
				
				if(isset($pur_roas_3_2[$v])) { $roas_conv_3_2 = $pur_roas_3_2[$v]; } 
				if(isset($pur_roas_7_2[$v])) { $roas_conv_7_2 = $pur_roas_7_2[$v]; } 
				if(isset($pur_roas_15_2[$v])) { $roas_conv_15_2 = $pur_roas_15_2[$v]; } 
				if(isset($pur_roas_30_2[$v])) { $roas_conv_30_2 = $pur_roas_30_2[$v]; } 
				
				if(isset($pur_roas_3[$v]) && $pur_roas_3[$v]<round($roas_conv_3_2) && $pur_roas_3[$v]!=0) { $cls2_1='red';  } else { $cls2_1='green';  }
				if(isset($pur_roas_7[$v]) && $pur_roas_7[$v]<round($roas_conv_7_2) && $pur_roas_7[$v]!=0) { $cls2_2='red';  } else { $cls2_2='green';  }
				if(isset($pur_roas_15[$v]) && $pur_roas_15[$v]<round($roas_conv_15_2) && $pur_roas_15[$v]!=0) { $cls2_3='red';  } else { $cls2_3='green';  }
				if(isset($pur_roas_30[$v]) && $pur_roas_30[$v]<round($roas_conv_30_2) && $pur_roas_30[$v]!=0) { $cls2_4='red';  } else { $cls2_4='green';  }
				
				$Link_url = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&business_id=866116510072687&selected_campaign_ids='.$conv_camp_ids_30[$v].'&selected_adset_ids='.$v;
				
				$conv_tbl .= '<tr><td>'.$conv_adset_name_30[$v].'</td>'; 
				$conv_tbl .= '<td>'.$conv_camp_name_30[$v].'</td>'; 
				
				$url1 = $Link_url.'&date='.$en_3.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1)))); 
				$url2 = $Link_url.'&date='.$en_6.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_4))));
				$conv_tbl .='<td class="txt-right yellow-bg" data-order="'.$val_conv_3_2.'">'.formatRep($val_conv_3_2, $val_conv_3, $url1, $url2, 'l', 'y').'</td>';  
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_lead_3_2[$v].'">'.formatRep($conv_lead_3_2[$v], $conv_lead_3[$v], $url1, $url2, 'h', 'y').'</td>'; 
				$conv_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_conv_3_2.'">'.formatRep($roas_conv_3_2, $roas_conv_3, $url1, $url2, 'l', 'y').'</td>'; 
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_spend_3_2[$v].'">'.formatRep($conv_spend_3_2[$v], $conv_spend_3[$v], $url1, $url2, '', 'y').'</td>'; 

				$url1 = $Link_url.'&date='.$en_7.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1)))); 
				$url2 = $Link_url.'&date='.$en_14.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_8))));
				$conv_tbl .='<td class="txt-right yellow-bg" data-order="'.$val_conv_7_2.'">'.formatRep($val_conv_7_2, $val_conv_7, $url1, $url2, 'h', 'y').'</td>';  
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_lead_7_2[$v].'">'.formatRep($conv_lead_7_2[$v], $conv_lead_7[$v], $url1, $url2, 'h', 'y').'</td>'; 
				$conv_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_conv_7_2.'">'.formatRep($roas_conv_7_2, $roas_conv_7, $url1, $url2, 'l', 'y').'</td>'; 
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_spend_7_2[$v].'">'.formatRep($conv_spend_7_2[$v], $conv_spend_7[$v], $url1, $url2, '', 'y').'</td>'; 

				$url1 = $Link_url.'&date='.$en_15.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1)))); //.$st_1; 
				$url2 = $Link_url.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_16)))); //.$st_16;
				$conv_tbl .='<td class="txt-right yellow-bg" data-order="'.$val_conv_15_2.'">'.formatRep($val_conv_15_2, $val_conv_15, $url1, $url2, 'h', 'y').'</td>';  
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_lead_15_2[$v].'">'.formatRep($conv_lead_15_2[$v], $conv_lead_15[$v], $url1, $url2, 'h', 'y').'</td>'; 
				$conv_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_conv_15_2.'">'.formatRep($roas_conv_15_2, $roas_conv_15, $url1, $url2, 'l', 'y').'</td>'; 
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_spend_15_2[$v].'">'.formatRep($conv_spend_15_2[$v], $conv_spend_15[$v], $url1, $url2, '', 'y').'</td>'; 

				$url1 = $Link_url.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1))));
				$url2 = $Link_url.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1))));
				$conv_tbl .='<td class="txt-right yellow-bg" data-order="'.$val_conv_30_2.'">'.formatRep($val_conv_30_2, $val_conv_30, $url1, $url2, '', 'n').'</td>';  
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_lead_30_2[$v].'">'.formatRep($conv_lead_30_2[$v], $conv_lead_30[$v], $url1, $url2, '', 'n').'</td>'; 
				$conv_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_conv_30_2.'">'.formatRep($roas_conv_30_2, $roas_conv_30, $url1, $url2, '', 'n').'</td>'; 
				$conv_tbl .= '<td class="txt-right" data-order="'.$conv_spend_30_2[$v].'">'.formatRep($conv_spend_30_2[$v], $conv_spend_30[$v], $url1, $url2, '', 'n').'</td>'; 


				/*
				$conv_tbl .='<td data-order="'.$val_conv_3_2.'"><a href="'.$Link_url.'&date='.$en_3.'_'.$st_1.'&insights_date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$val_conv_3_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_6.'_'.$st_4.'&insights_date='.$en_6.'_'.$st_4.'" target="_blank">'.$val_conv_3.'</a> ('.$conv_cpl_30_2[$v].')</td>'; 
                $conv_tbl .='<td data-order="'.$roas_conv_3_2.'"><a href="'.$Link_url.'&date='.$en_3.'_'.$st_1.'&insights_date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls2_1.'">'.$roas_conv_3_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_6.'_'.$st_4.'&insights_date='.$en_6.'_'.$st_4.'" target="_blank">'.$roas_conv_3.'</a> ('.$pur_roas_30_2[$v].')</td>'; 
				$conv_tbl .='<td data-order="'.$val_conv_7_2.'"><a href="'.$Link_url.'&date='.$en_7.'_'.$st_1.'&insights_date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$val_conv_7_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_14.'_'.$st_8.'&insights_date='.$en_14.'_'.$st_8.'" target="_blank">'.$val_conv_7.'</a> ('.$conv_cpl_30_2[$v].')</td>';
                $conv_tbl .='<td data-order="'.$roas_conv_7_2.'"><a href="'.$Link_url.'&date='.$en_7.'_'.$st_1.'&insights_date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls2_2.'">'.$roas_conv_7_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_14.'_'.$st_8.'&insights_date='.$en_14.'_'.$st_8.'" target="_blank">'.$roas_conv_7.'</a> ('.$pur_roas_30_2[$v].')</td>';
				$conv_tbl .='<td data-order="'.$val_conv_15_2.'"><a href="'.$Link_url.'&date='.$en_15.'_'.$st_1.'&insights_date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$val_conv_15_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_30.'_'.$st_16.'&insights_date='.$en_30.'_'.$st_16.'" target="_blank">'.$val_conv_15.'</a> ('.$conv_cpl_30_2[$v].')</td>';
                $conv_tbl .='<td data-order="'.$roas_conv_15_2.'"><a href="'.$Link_url.'&date='.$en_15.'_'.$st_1.'&insights_date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls2_3.'">'.$roas_conv_15_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_30.'_'.$st_16.'&insights_date='.$en_30.'_'.$st_16.'" target="_blank">'.$roas_conv_15.'</a> ('.$pur_roas_30_2[$v].')</td>';
				$conv_tbl .='<td data-order="'.$val_conv_30_2.'"><a href="'.$Link_url.'&date='.$en_30.'_'.$st_1.'&insights_date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$val_conv_30_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_60.'_'.$st_31.'&insights_date='.$en_60.'_'.$st_31.'" target="_blank">'.$val_conv_30.'</a></td>'; 
                $conv_tbl .='<td data-order="'.$roas_conv_30_2.'"><a href="'.$Link_url.'&date='.$en_30.'_'.$st_1.'&insights_date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls2_4.'">'.$roas_conv_30_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_60.'_'.$st_31.'&insights_date='.$en_60.'_'.$st_31.'" target="_blank">'.$roas_conv_30.'</a></td>'; */
				$conv_tbl .= '</tr>'; 

			}

			$Link_url2 = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId;

			$totCplc_3 =  @(round($conv_spend_t_3/$conv_lead_t_3)); 
			$totCplc_7 =  @(round($conv_spend_t_7/$conv_lead_t_7));
			$totCplc_15 =  @(round($conv_spend_t_15/$conv_lead_t_15));
			$totCplc_30 =  @(round($conv_spend_t_30/$conv_lead_t_30));

			$totCplc_3_2 =  @(round($conv_spend_t_3_2/$conv_lead_t_3_2)); 
			$totCplc_7_2 =  @(round($conv_spend_t_7_2/$conv_lead_t_7_2));
			$totCplc_15_2 =  @(round($conv_spend_t_15_2/$conv_lead_t_15_2));
			$totCplc_30_2 =  $CPL_VALUE = @(round($conv_spend_t_30_2/$conv_lead_t_30_2));

			if(isset($totCplc_3) && $totCplc_3<round($totCplc_3_2) && $totCplc_3!=0) { $cls_1='red';  } else { $cls_1='green';  }
			if(isset($totCplc_7) && $totCplc_7<round($totCplc_7_2) && $totCplc_7!=0) { $cls_2='red';  } else { $cls_2='green';  }
			if(isset($totCplc_15) && $totCplc_15<round($totCplc_15_2) && $totCplc_15!=0) { $cls_3='red';  } else { $cls_3='green';  }
			if(isset($totCplc_30) && $totCplc_30<round($totCplc_30_2) && $totCplc_30!=0) { $cls_4='red';  } else { $cls_4='green';  }

            $totRoas_3 =  @(round($tot_roas_con_3/$conv_spend_t_3,2)); 
			$totRoas_7 =  @(round($tot_roas_con_7/$conv_spend_t_7,2));
			$totRoas_15 =  @(round($tot_roas_con_15/$conv_spend_t_15,2));
			$totRoas_30 =  @(round($tot_roas_con_30/$conv_spend_t_30,2));

			$totRoas_3_2 =  @(round($tot_roas_con_3_2/$conv_spend_t_3_2,2));
			$totRoas_7_2 =  @(round($tot_roas_con_7_2/$conv_spend_t_7_2,2));
			$totRoas_15_2 =  @(round($tot_roas_con_15_2/$conv_spend_t_15_2,2));
			$totRoas_30_2 =  @(round($tot_roas_con_30_2/$conv_spend_t_30_2,2));

			if(isset($totRoas_3) && $totRoas_3<round($totRoas_3_2) && $totRoas_3!=0) { $cls2_1='red';  } else { $cls2_1='green';  }
			if(isset($totRoas_7) && $totRoas_7<round($totRoas_7_2) && $totRoas_7!=0) { $cls2_2='red';  } else { $cls2_2='green';  }
			if(isset($totRoas_15) && $totRoas_15<round($totRoas_15_2) && $totRoas_15!=0) { $cls2_3='red';  } else { $cls2_3='green';  }
			if(isset($totRoas_30) && $totRoas_30<round($totRoas_30_2) && $totRoas_30!=0) { $cls2_4='red';  } else { $cls2_4='green';  }

			$conv_tbl .= '</tbody><tfoot><tr><td colspan="2">Total</td>'; 
				//$conv_tbl .= '<td>'.$conv_adset_name_30[$v].'</td>'; 
				

				
				$conv_tbl .='<td class="yellow-bg"><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$totCplc_3_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$totCplc_3.'</a></td>'; 
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank">'.$conv_lead_t_3_2.'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$conv_lead_t_3.'</a></td>';
                $conv_tbl .='<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls2_1.'">'.$totRoas_3_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$totRoas_3.'</a></td>'; 
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank">'.$fmt->format($conv_spend_t_3_2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$fmt->format($conv_spend_t_3).'</a></td>';

				$conv_tbl .='<td class="yellow-bg"><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$totCplc_7_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$totCplc_7.'</a></td>';
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank">'.$conv_lead_t_7_2.'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$conv_lead_t_7.'</a></td>';
                $conv_tbl .='<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls2_2.'">'.$totRoas_7_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$totRoas_7.'</a></td>';
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank">'.$fmt->format($conv_spend_t_7_2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$fmt->format($conv_spend_t_7).'</a></td>';

				$conv_tbl .='<td class="yellow-bg"><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$totCplc_15_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$totCplc_15.'</a></td>';
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank">'.$conv_lead_t_15_2.'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$conv_lead_t_15.'</a></td>';
                $conv_tbl .='<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls2_3.'">'.$totRoas_15_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$totRoas_15.'</a></td>';
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank">'.$fmt->format($conv_spend_t_7_2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$fmt->format($conv_spend_t_7).'</a></td>';

				$conv_tbl .='<td class="yellow-bg"><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$totCplc_30_2.'</span></a></td>'; 
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank">'.$conv_lead_t_30_2.'</a></td>';
                $conv_tbl .='<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls2_4.'">'.$totRoas_30_2.'</span></a></td>'; 
				$conv_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank">'.$fmt->format($conv_spend_t_30_2).'</a></a></td>';
				$conv_tbl .= '</tr>'; 

			$conv_tbl .= '</tfoot></table>';
			echo $conv_tbl; 
			echo '<br><br>'; 
		}
		} 
        

		echo '<center><h3>CTR (link clicks) </h3><small>Ad level</small></center>';
		$lg_cpl_3 = $lg_cpl_7 = $lg_cpl_15 = $lg_cpl_30 = $lg_adset_cpl_3 = $lg_adset_cpl_7 = $lg_adset_cpl_15 = $lg_adset_cpl_30 = $lg_adset_name_30 = $lg_ad_name_30 = array();
		$lg_cpl_3_2 = $lg_cpl_7_2 = $lg_cpl_15_2 = $lg_cpl_30_2 = $lg_adset_cpl_3_2 = $lg_adset_cpl_7_2 = $lg_adset_cpl_15_2 = $lg_adset_cpl_30_2 = $lg_adset_name_30_2 = array();
		$roas_3 = $roas_7 = $roas_15 = $roas_30 = $roas_3_2 = $roas_7_2 = $roas_15_2 = $roas_30_2 =array();
		$lg_ctr_3 = $lg_ctr_7 = $lg_ctr_15 = $lg_ctr_30 = $lg_ctr_3_2 = $lg_ctr_7_2 = $lg_ctr_15_2 = $lg_ctr_30_2 =array();

		$lg_spend_t_3 = $lg_lead_t_3 = $lg_spend_t_7 = $lg_lead_t_7 = $lg_spend_t_15 = $lg_lead_t_15 = $lg_spend_t_30 = $lg_lead_t_30 = $lg_spend_t_3_2 = $lg_lead_t_3_2 = $lg_spend_t_15_2 = $lg_lead_t_15_2 = $lg_spend_t_30_2 = $lg_lead_t_30_2 = 0;
		$lg_spend_3 = $lg_lead_3 = $lg_spend_7 = $lg_lead_7 = $lg_spend_15 = $lg_lead_15 = $lg_spend_30 = $lg_lead_30 = $lg_spend_3_2 = $lg_lead_3_2 = $lg_spend_15_2 = $lg_lead_15_2 = $lg_spend_30_2 = $lg_lead_30_2 = array();
		$roas_con_3 = $roas_con_7 = $roas_con_15 = $roas_con_30 = $roas_con_3_2 = $roas_con_7_2 = $roas_con_15_2 = $roas_con_30_2 = 0; 

		$link_cl_t_3 = $link_cl_t_3_2 = $link_cl_t_7 = $link_cl_t_7_2 = $link_cl_t_15 = $link_cl_t_15_2 = $link_cl_t_30 = $link_cl_t_30_2 = 0;
		$impr_t_3 = $impr_t_3_2 = $impr_t_7 = $impr_t_7_2 = $impr_t_15 = $impr_t_15_2 = $impr_t_30 = $impr_t_30_2 = 0;

		//$sqlQ = "accId='".$accId."'  AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS'  || obj_type='CONVERSIONS') AND";
		$sqlQ = "accId='".$accId."'  AND (obj_type='CONVERSIONS') AND";
		$selectQ = "accId='".$accId."ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f";

		//echo "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by ad_id"; exit;
		$sql_1 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by ad_id");
		while($row1 = mysqli_fetch_array($sql_1)) {
			$lg_cpl_3[$row1['ad_id']] = round($row1['c'] * 100, 2);
			$lg_spend_t_3 = $lg_spend_t_3 + round($row1['a']);
			$lg_lead_t_3 = $lg_lead_t_3 + round($row1['b']);
			$lg_spend_3[$row1['ad_id']] =  round($row1['a']);
			$lg_lead_3[$row1['ad_id']] =  round($row1['b']);
			$roas_3[$row1['ad_id']] = round($row1['d'],2);
            $roas_con_3 = $roas_con_3 + round($row1['e']);
			$lg_ctr_3[$row1['ad_id']] = round($row1['f'] * 100, 2);
			$link_cl_t_3 = $link_cl_t_3 + round($row1['link_clicks']);
			$impr_t_3 = $impr_t_3 + round($row1['impressions']);
		}
		//d($lg_cpl_3); //exit;
		
		$sql_2 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_14."' AND eDate<='".$st_8."' GROUP by ad_id");
		while($row2 = mysqli_fetch_array($sql_2)) {
			$lg_cpl_7[$row2['ad_id']] = round($row2['c'] * 100, 2);
			$lg_spend_t_7 = $lg_spend_t_7 + round($row2['a']);
			$lg_lead_t_7 = $lg_lead_t_7 + round($row2['b']);
			$lg_spend_7[$row2['ad_id']] =  round($row2['a']);
			$lg_lead_7[$row2['ad_id']] =  round($row2['b']);
			$roas_7[$row2['ad_id']] = round($row2['d'],2);
            $roas_con_7 = $roas_con_7 + round($row2['e']);
			$lg_ctr_7[$row2['ad_id']] = round($row2['f'] * 100, 2);
			$link_cl_t_7 = $link_cl_t_7 + round($row2['link_clicks']);
			$impr_t_7 = $impr_t_7 + round($row2['impressions']);
		}
		
		$sql_3 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_30."' AND eDate<='".$st_16."' GROUP by ad_id");
		while($row3 = mysqli_fetch_array($sql_3)) {
			$lg_cpl_15[$row3['ad_id']] = round($row3['c'] * 100, 2);
			$lg_spend_t_15 = $lg_spend_t_15 + round($row3['a']);
			$lg_lead_t_15 = $lg_lead_t_15 + round($row3['b']);
			$lg_spend_15[$row3['ad_id']] =  round($row3['a']);
			$lg_lead_15[$row3['ad_id']] =  round($row3['b']);
			$roas_15[$row3['ad_id']] = round($row3['d'],2);
            $roas_con_15 = $roas_con_15 + round($row3['e']);
			$lg_ctr_15[$row3['ad_id']] = round($row3['f'] * 100, 2);
			$link_cl_t_15 = $link_cl_t_15 + round($row3['link_clicks']);
			$impr_t_15 = $impr_t_15 + round($row3['impressions']);
		}
		
		$sql_4 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_60."' AND eDate<='".$st_31."' GROUP by ad_id");
		while($row4 = mysqli_fetch_array($sql_4)) {
			$lg_cpl_30[$row4['ad_id']] = round($row4['c'] * 100, 2);
			$lg_spend_t_30 = $lg_spend_t_30 + round($row4['a']);
			$lg_lead_t_30 = $lg_lead_t_30 + round($row4['b']);
			$lg_spend_30[$row4['ad_id']] = round($row4['a']);
			$lg_lead_30[$row4['ad_id']] = round($row4['b']);
			$roas_30[$row4['ad_id']] = round($row4['d'],2);
            $roas_con_30 = $roas_con_30 + round($row4['e']);
			$lg_ctr_30[$row4['ad_id']] = round($row4['f'] * 100, 2);
			$link_cl_t_30 = $link_cl_t_30 + round($row4['link_clicks']);
			$impr_t_30 = $impr_t_30 + round($row4['impressions']);
		}
		
		$sql_11 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_3."' AND eDate<='".$st_1."' GROUP by ad_id");
		while($row11 = mysqli_fetch_array($sql_11)) {
			$lg_cpl_3_2[$row11['ad_id']] = round($row11['c'] * 100, 2);
			$lg_spend_t_3_2 = $lg_spend_t_3_2 + round($row11['a']);
			$lg_lead_t_3_2 = $lg_lead_t_3_2 + round($row11['b']);
			$lg_spend_3_2[$row11['ad_id']] =  round($row11['a']);
			$lg_lead_3_2[$row11['ad_id']] = round($row11['b']);
			$roas_3_2[$row11['ad_id']] = round($row11['d'],2);
            $roas_con_3_2 = $roas_con_3_2 + round($row11['e']);
			$lg_ctr_3_2[$row11['ad_id']] = round($row11['f'] * 100, 2);
			$link_cl_t_3_2 = $link_cl_t_3_2 + round($row11['link_clicks']);
			$impr_t_3_2 = $impr_t_3_2 + round($row11['impressions']);
		}
		//d($lg_cpl_3); d($lg_adset_cpl_3);
		$sql_12 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_7."' AND eDate<='".$st_1."' GROUP by ad_id");
		while($row12 = mysqli_fetch_array($sql_12)) {
			$lg_cpl_7_2[$row12['ad_id']] = round($row12['c'] * 100, 2);
			$lg_spend_t_7_2 = $lg_spend_t_7_2 + round($row12['a']);
			$lg_lead_t_7_2 = $lg_lead_t_7_2 + round($row12['b']);
			$lg_spend_7_2[$row12['ad_id']] = round($row12['a']);
			$lg_lead_7_2[$row12['ad_id']] =  round($row12['b']);
			$roas_7_2[$row12['ad_id']] = round($row12['d'],2);
            $roas_con_7_2 = $roas_con_7_2 + round($row12['e']);
			$lg_ctr_7_2[$row12['ad_id']] = round($row12['f'] * 100, 2);
			$link_cl_t_7_2 = $link_cl_t_7_2 + round($row12['link_clicks']);
			$impr_t_7_2 = $impr_t_7_2 + round($row12['impressions']);
		}
		
		$sql_13 = mysqli_query($conn, "SELECT ad_id, obj_type, link_clicks, impressions, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_15."' AND eDate<='".$st_1."' GROUP by ad_id");
		while($row13 = mysqli_fetch_array($sql_13)) {
			$lg_cpl_15_2[$row13['ad_id']] = round($row13['c'] * 100, 2);
			$lg_spend_t_15_2 = $lg_spend_t_15_2 + round($row13['a']);
			$lg_lead_t_15_2 = $lg_lead_t_15_2 + round($row13['b']);
			$lg_spend_15_2[$row13['ad_id']] =  round($row13['a']);
			$lg_lead_15_2[$row13['ad_id']] =  round($row13['b']);
			$roas_15_2[$row13['ad_id']] = round($row13['d'],2);
            $roas_con_15_2 = $roas_con_15_2 + round($row13['e']);
			$lg_ctr_15_2[$row13['ad_id']] = round($row13['f'] * 100, 2);
			$link_cl_t_15_2 = $link_cl_t_15_2 + round($row13['link_clicks']);
			$impr_t_15_2 = $impr_t_15_2 + round($row13['impressions']);
		}
		
		//$sql_14 = mysqli_query($conn, "SELECT ad_id, adset_name, campaign_id, ad_name, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by ad_id");
		
		$sql_14 = mysqli_query($conn, "SELECT ad_id, link_clicks, impressions, adset_name, campaign_id, ad_name, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c, (SUM(web_con_val)/SUM(spend)) as d, SUM(web_con_val) as e, (SUM(link_clicks)/SUM(impressions)) as f FROM audit_adIns_30d_cpl_2 WHERE ".$sqlQ." sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by ad_id");
		while($row14 = mysqli_fetch_array($sql_14)) { 
			$lgCPL30_id[]= $row14['ad_id'];
			$lg_camp_ids_30[$row14['ad_id']] = $row14['campaign_id'];
			$lg_cpl_30_2[$row14['ad_id']] = round($row14['c'] * 100, 2);
			$lg_spend_t_30_2 = $lg_spend_t_30_2 + round($row14['a']);
			$lg_lead_t_30_2 = $lg_lead_t_30_2 + round($row14['b']);
			$lg_spend_30_2[$row14['ad_id']] = round($row14['a']);
			$lg_lead_30_2[$row14['ad_id']] =  round($row14['b']);
			$roas_30_2[$row14['ad_id']] = round($row14['d'],2);
            $roas_con_30_2 = $roas_con_30_2 + round($row14['e']);
			$lg_ctr_30_2[$row14['ad_id']] = round($row14['f'] * 100, 2);
			$link_cl_t_30_2 = $link_cl_t_30_2 + round($row14['link_clicks']);
			$impr_t_30_2 = $impr_t_30_2 + round($row14['impressions']);
			$lg_ad_name_30[$row14['ad_id']] = '<a href="https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_ad_ids='.$row14['ad_id'].'" target="_blank">'.$row14['ad_name'].'</a>';
			$lg_adset_name_30[$row14['ad_id']] = '<a href="https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_ad_ids='.$row14['ad_id'].'" target="_blank">'.$row14['adset_name'].'</a>';
			$lg_camp_name_30[$row14['ad_id']] = '<a href="https://www.facebook.com/adsmanager/manage/adsets?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_ad_ids='.$row14['ad_id'].'" target="_blank">'.$row14['campaign_name'].'</a>';
		}
		?>
		
	<?
		if(count($lgCPL30_id)>0) {
			?>
			<span class="toggle1">
				<center> Toggle Column: <a class="toggle-vis" data-column="0">Ad</a> | <a class="toggle-vis" data-column="1">Adset</a> | <a class="toggle-vis" data-column="2">Campaign</a></center>
			</span>
			<?
			$lg_tbl = '<table id="TABLE_8" class="table table-bordered dt-responsive compact table_ctr"><thead><tr><th rowspan="2">Ad</th><th rowspan="2">Adset</th><th rowspan="2">Campaign</th><th colspan="4">L 3d | P 3d </th><th colspan="4">L 7d | P 7d</th><th colspan="4">L 15d | P 15d</th><th colspan="4">L 30d</th></tr>
			<tr>
				<th>CTR</th><th>Lead</th><th class="green-bg">ROAS</th><th>Spend</th>
				<th>CTR</th><th>Lead</th><th class="green-bg">ROAS</th><th>Spend</th>
				<th>CTR</th><th>Lead</th><th class="green-bg">ROAS</th><th>Spend</th>
				<th>CTR</th><th>Lead</th><th class="green-bg">ROAS</th><th>Spend</th>
			</tr></thead>
			<tbody>';
			
			foreach ($lgCPL30_id as $key => $v) {
				
				$val_lg_3 = $val_lg_3_2 = $val_lg_7 = $val_lg_7_2 = $val_lg_15 = $val_lg_15_2 = $val_lg_30 =$val_lg_30_2 = '-';
				//$val_lg_3_tot = $val_lg_3_2_tot = $val_lg_7_tot = $val_lg_7_2_tot = $val_lg_15_tot = $val_lg_15_2_tot = $val_lg_30_tot =$val_lg_30_2_tot = 0;
				
				if(isset($lg_ctr_3[$v])) { $val_lg_3 = $lg_ctr_3[$v]; } 
				if(isset($lg_ctr_7[$v])) { $val_lg_7 = $lg_ctr_7[$v]; } 
				if(isset($lg_ctr_15[$v])) { $val_lg_15 = $lg_ctr_15[$v]; }
				if(isset($lg_ctr_30[$v])) { $val_lg_30 = $lg_ctr_30[$v]; }
				
				if(isset($lg_ctr_3_2[$v])) { $val_lg_3_2 = $lg_ctr_3_2[$v]; } 
				if(isset($lg_ctr_7_2[$v])) { $val_lg_7_2 = $lg_ctr_7_2[$v]; } 
				if(isset($lg_ctr_15_2[$v])) { $val_lg_15_2 = $lg_ctr_15_2[$v]; } 
				if(isset($lg_ctr_30_2[$v])) { $val_lg_30_2 = $lg_ctr_30_2[$v]; } 
				
				$Link_url = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$lg_camp_ids_30[$v].'&selected_ad_ids='.$v;
				
				
				$lg_tbl .= '<tr><td>'.$lg_ad_name_30[$v].'</td>'; 
				$lg_tbl .= '<td>'.$lg_adset_name_30[$v].'</td>'; 
				$lg_tbl .= '<td>'.$lg_camp_name_30[$v].'</td>'; 

			/*
				$lg_tbl .= '<tr><td></td>'; 
				$lg_tbl .= '<td></td>'; 
				$lg_tbl .= '<td></td>'; */
				
				$url1 = $Link_url.'&date='.$en_3.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1)))); 
				$url2 = $Link_url.'&date='.$en_6.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_4))));
				$lg_tbl .='<td class="txt-right" data-order="'.$val_lg_3_2.'">'.formatRep($val_lg_3_2, $val_lg_3, $url1, $url2, 'h', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_lead_3_2[$v].'">'.formatRep($lg_lead_3_2[$v], $lg_lead_3[$v], $url1, $url2, 'h', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_3_2[$v].'">'.formatRep($roas_3_2[$v], $roas_3[$v], $url1, $url2, 'l', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_spend_3_2[$v].'">'.formatRep($lg_spend_3_2[$v], $lg_spend_3[$v], $url1, $url2, '', 'y').'</td>'; 

				$url1 = $Link_url.'&date='.$en_7.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1)))); 
				$url2 = $Link_url.'&date='.$en_14.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_8))));
				$lg_tbl .='<td class="txt-right" data-order="'.$val_lg_7_2.'">'.formatRep($val_lg_7_2, $val_lg_7, $url1, $url2, 'h', 'y').'</td>';  
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_lead_7_2[$v].'">'.formatRep($lg_lead_7_2[$v], $lg_lead_7[$v], $url1, $url2, 'h', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_7_2[$v].'">'.formatRep($roas_7_2[$v], $roas_7[$v], $url1, $url2, 'l', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_spend_7_2[$v].'">'.formatRep($lg_spend_7_2[$v], $lg_spend_7[$v], $url1, $url2, '', 'y').'</td>'; 

				$url1 = $Link_url.'&date='.$en_15.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1)))); //.$st_1; 
				$url2 = $Link_url.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_16)))); //.$st_16;
				$lg_tbl .='<td class="txt-right" data-order="'.$val_lg_15_2.'">'.formatRep($val_lg_15_2, $val_lg_15, $url1, $url2, 'h', 'y').'</td>';  
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_lead_15_2[$v].'">'.formatRep($lg_lead_15_2[$v], $lg_lead_15[$v], $url1, $url2, 'h', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_15_2[$v].'">'.formatRep($roas_15_2[$v], $roas_15[$v], $url1, $url2, 'l', 'y').'</td>'; 
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_spend_15_2[$v].'">'.formatRep($lg_spend_15_2[$v], $lg_spend_15[$v], $url1, $url2, '', 'y').'</td>'; 

				$url1 = $Link_url.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1))));
				$url2 = $Link_url.'&date='.$en_30.'_'.date('Y-m-d',date(strtotime("+1 day", strtotime($st_1))));
				$lg_tbl .='<td class="txt-right" data-order="'.$val_lg_30_2.'">'.formatRep($val_lg_30_2, $val_lg_30, $url1, $url2, '', 'n').'</td>';  
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_lead_30_2[$v].'">'.formatRep($lg_lead_30_2[$v], $lg_lead_30[$v], $url1, $url2, '', 'n').'</td>'; 
				$lg_tbl .= '<td class="txt-right green-bg" data-order="'.$roas_30_2[$v].'">'.formatRep($roas_30_2[$v], $roas_30[$v], $url1, $url2, '', 'n').'</td>'; 
				$lg_tbl .= '<td class="txt-right" data-order="'.$lg_spend_30_2[$v].'">'.formatRep($lg_spend_30_2[$v], $lg_spend_30[$v], $url1, $url2, '', 'n').'</td>'; 
				
				$lg_tbl .= '</tr>'; 
				
			}
			$Link_url2 = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId;

			$totCTR_3 =  @(round(($link_cl_t_3/$impr_t_3) * 100,2)); 
			$totCTR_7 =  @(round(($link_cl_t_7/$impr_t_7) * 100,2));
			$totCTR_15 =  @(round(($link_cl_t_15/$impr_t_15) * 100,2));
			$totCTR_30 =  @(round(($link_cl_t_30/$impr_t_30) * 100,2));

			$totCTR_3_2 =  @(round(($link_cl_t_3_2/$impr_t_3_2) * 100,2)); 
			$totCTR_7_2 =  @(round(($link_cl_t_7_2/$impr_t_7_2) * 100,2));
			$totCTR_15_2 =  @(round(($link_cl_t_15_2/$impr_t_15_2) * 100,2));
			$totCTR_30_2 =  @(round(($link_cl_t_30_2/$impr_t_30_2) * 100,2));

			if(isset($totCTR_3) && $totCTR_3>round(($totCTR_3_2),2) && $totCTR_3!=0) { $cls_1='red';  } else { $cls_1='green';  }
			if(isset($totCTR_7) && $totCTR_7>round(($totCTR_7_2),2) && $totCTR_7!=0) { $cls_2='red';  } else { $cls_2='green';  }
			if(isset($totCTR_15) && $totCTR_15>round(($totCTR_15_2),2) && $totCTR_15!=0) { $cls_3='red';  } else { $cls_3='green';  }
			if(isset($totCTR_30) && $totCTR_30>round(($totCTR_30_2),2) && $totCTR_30!=0) { $cls_4='red';  } else { $cls_4='green';  }

			$lg_tbl .= '</tbody><tfoot><tr><td colspan="3" class="txt-right">Total</td>'; 
				//$lg_tbl .= '<td>'.$lg_adset_name_30[$v].'</td>'; 
				
				$lg_tbl .='<td class="txt-right"><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$totCTR_3_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$totCTR_3.'</a></td>'; 
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank">'.$lg_lead_t_3_2.'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$lg_lead_t_3.'</a></td>';
				$lg_tbl .= '<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank">'.round(($roas_con_3_2/$lg_spend_t_3_2),2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.round(($roas_con_3/$lg_spend_t_3),2).'</a></td>';
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank">'.$fmt->format($lg_spend_t_3_2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$fmt->format($lg_spend_t_3).'</a></td>';

				$lg_tbl .='<td class="txt-right"><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$totCTR_7_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$totCTR_7.'</a></td>';
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank">'.$lg_lead_t_7_2.'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$lg_lead_t_7.'</td></td>';
				$lg_tbl .= '<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank">'.round(($roas_con_7_2/$lg_spend_t_7_2),2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.round(($roas_con_7/$lg_spend_t_7),2).'</td></td>';
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank">'.$fmt->format($lg_spend_t_7_2).'</a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$fmt->format($lg_spend_t_7).'</td></td>';

				$lg_tbl .='<td class="txt-right"><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$totCTR_15_2.'</span></a> '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$totCTR_15.'</a></td>';
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank">'.$lg_lead_t_15_2.' '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$lg_lead_t_15.'</td></td>';
				$lg_tbl .= '<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank">'.round(($roas_con_15_2/$lg_spend_t_15_2),2).' '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.round(($roas_con_15/$lg_spend_t_15),2).'</td></td>';
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank">'.$fmt->format($lg_spend_t_15_2).' '.$sepIcon.' <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$fmt->format($lg_spend_t_15).'</td></td>';

				$lg_tbl .='<td class="txt-right"><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank">'.$totCTR_30_2.'</span></a></td>'; 
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank">'.$lg_lead_t_30_2.'</a></td>';
				$lg_tbl .= '<td class="green-bg"><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank">'.round(($roas_con_30_2/$lg_spend_t_30_2),2).'</a></td>';
				$lg_tbl .= '<td><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank">'.$fmt->format($lg_spend_t_30_2).'</a></td>';
				$lg_tbl .= '</tr></tfoot>';

			$lg_tbl .= '</table>';
		}
		
		echo $lg_tbl; 
		echo '<br><br>'; 
		//exit;
		
		
		
            ?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<script>
<?php if(isset($_GET['high'])) { ?> $('html, body').scrollTop($("#high_tit").offset().top);  <?php } ?>
$(document).ready(function() {
    //$('table.table').DataTable();
	/*$('table.table_roas').dataTable( {
		"iDisplayLength": 100,
		scrollX: true,
		"bAutoWidth": false,
		fixedColumns:   {
            leftColumns: 1,
            rightColumns: 1
        }
    });*/
	
	$('table.high_cpl').dataTable( {
		"iDisplayLength": 100,
		scrollX: true,
		"bAutoWidth": false,
		order: [[4, 'desc']]
    });

	var table_ctr = $('#TABLE_8').DataTable( {
        "iDisplayLength": 100,
		scrollX: true,
		"bAutoWidth": false,
    } );
 
    $('a.toggle-vis').on( 'click', function (e) {
        e.preventDefault();
 
        // Get the column API object
        var column = table_ctr.column( $(this).attr('data-column') );
 
        // Toggle the visibility
        column.visible( ! column.visible() );
    } );

	var table_roas = $('.table_roas').DataTable( {
        "iDisplayLength": 100,
		scrollX: true,
		"bAutoWidth": false,
    } );
 
    $('a.toggle-vis2').on( 'click', function (e) {
        e.preventDefault();
 
        // Get the column API object
        var column = table_roas.column( $(this).attr('data-column') );
 
        // Toggle the visibility
        column.visible( ! column.visible() );
    } );
	
} );
</script>