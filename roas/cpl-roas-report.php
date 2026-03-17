<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set("Asia/Calcutta");

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

//Auth();

$pgID = 3;
$err =''; 
//require '../vendor/autoload.php';


include '../db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include '../config.php';




$statement = " adAccounts WHERE account_id='".$_GET['act']."'";
echo "SELECT name FROM ".$statement." order by name asc LIMIT 1"; 
$sqlRev=mysqli_query($conn, "SELECT name FROM ".$statement." order by name asc LIMIT 1");
$sqlROW=mysqli_fetch_row($sqlRev);
$clName = $sqlROW[0];
$pgHeadline = $clName.' - CPL Comparison ';
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
?>
<!DOCTYPE html>
<html>
  
<head>
  
    <meta content="initial-scale=1, maximum-scale=1, user-scalable=0" name="viewport" />
  
    <meta name="viewport" content="width=device-width" />
  
    <!--Datatable plugin CSS file -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css" />
    <!--jQuery library file -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
  
    <!--Datatable plugin JS library file -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
</head>
  
<body>
    <h2>
        Multiple tables operations
        using jQuery Datatables
    </h2>
    <?php
    $obj_wise_res_arr = $lgCPL30_id = $convCPL30_id = $lg_camp_ids_30 = $conv_camp_ids_30 = array();
		
    $q1 = mysqli_query($conn, "SELECT obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl as a WHERE accId='".$accId." 'AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by obj_type HAVING b!=0 ORDER BY obj_type ASC");
    
    while($r1 = mysqli_fetch_array($q1)) {  
        $obj_wise_res_arr[] = '<tr><td>'.ucfirst(strtolower(str_replace('_',' ',$r1['obj_type']))).'</td><td>'.round($r1['a']).'</td><td>'.round($r1['b']).'</td><td>'.round($r1['c'],2).'</td></tr>';
        if($r1['obj_type']=='') {
        }
    }
    
    $lg_tbl = $conv_tbl = $obj_wise_res = 'No Records Found!';
    
    if(count($obj_wise_res_arr)>0) {
                $obj_wise_res = '<table  id="" class="display" style="width:100%"><thead><tr><th>Objective</th><th>Spend &#8377;</th><th>Results</th><th>CPL &#8377;</th></tr></thead><tbody>'.implode('',$obj_wise_res_arr).'</tbody></table>';
    }
    echo '<center><h3>Objective wise Results</h3> <small>Last 30d</small></center>';
    echo $obj_wise_res;
    echo '<br><br>'; 
    
    echo '<center><h3>CTR (Link Clicks) - Ad level</h3></center>';
    $lg_cpl_3 = $lg_cpl_7 = $lg_cpl_15 = $lg_cpl_30 = $lg_adset_cpl_3 = $lg_adset_cpl_7 = $lg_adset_cpl_15 = $lg_adset_cpl_30 = $lg_adset_name_30 = array();
    $lg_cpl_3_2 = $lg_cpl_7_2 = $lg_cpl_15_2 = $lg_cpl_30_2 = $lg_adset_cpl_3_2 = $lg_adset_cpl_7_2 = $lg_adset_cpl_15_2 = $lg_adset_cpl_30_2 = $lg_adset_name_30_2 = array();

    $lg_spend_3 = $lg_lead_3 = $lg_spend_7 = $lg_lead_7 = $lg_spend_15 = $lg_lead_15 = $lg_spend_30 = $lg_lead_30 = $lg_spend_3_2 = $lg_lead_3_2 = $lg_spend_15_2 = $lg_lead_15_2 = $lg_spend_30_2 = $lg_lead_30_2 = $lg_spend_7_2 = $lg_lead_7_2   = 0;
    //echo "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(spend)/SUM(leads)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by ad_id"; exit;
    $sql_1 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_6."' AND eDate<='".$st_4."' GROUP by ad_id");
    while($row1 = mysqli_fetch_array($sql_1)) {
        $lg_cpl_3[$row1['ad_id']] = round($row1['c'] * 100, 2);
        $lg_spend_3 = $lg_spend_3 + round($row1['a']);
        $lg_lead_3 = $lg_lead_3 + round($row1['b']);
    }
    
    $sql_2 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_14."' AND eDate<='".$st_8."' GROUP by ad_id");
    while($row2 = mysqli_fetch_array($sql_2)) {
        $lg_cpl_7[$row2['ad_id']] = round($row2['c'] * 100, 2);
        $lg_spend_7 = $lg_spend_7 + round($row2['a']);
        $lg_lead_7 = $lg_lead_7 + round($row2['b']);
    }
    
    $sql_3 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_30."' AND eDate<='".$st_16."' GROUP by ad_id");
    while($row3 = mysqli_fetch_array($sql_3)) {
        $lg_cpl_15[$row3['ad_id']] = round($row3['c'] * 100, 2);
        $lg_spend_15 = $lg_spend_15 + round($row3['a']);
        $lg_lead_15 = $lg_lead_15 + round($row3['b']);
    }
    
    $sql_4 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_60."' AND eDate<='".$st_31."' GROUP by ad_id");
    while($row4 = mysqli_fetch_array($sql_4)) {
        $lg_cpl_30[$row4['ad_id']] = round($row4['c'] * 100, 2);
        $lg_spend_30 = $lg_spend_30 + round($row4['a']);
        $lg_lead_30 = $lg_lead_30 + round($row4['b']);
    }
    
    $sql_11 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_3."' AND eDate<='".$st_1."' GROUP by ad_id");
    while($row11 = mysqli_fetch_array($sql_11)) {
        $lg_cpl_3_2[$row11['ad_id']] = round($row11['c'] * 100, 2);
        $lg_spend_3_2 = $lg_spend_3_2 + round($row11['a']);
        $lg_lead_3_2 = $lg_lead_3_2 + round($row11['b']);
    }
    //d($lg_cpl_3); d($lg_adset_cpl_3);
    $sql_12 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_7."' AND eDate<='".$st_1."' GROUP by ad_id");
    while($row12 = mysqli_fetch_array($sql_12)) {
        $lg_cpl_7_2[$row12['ad_id']] = round($row12['c'] * 100, 2);
        $lg_spend_7_2 = $lg_spend_7_2 + round($row12['a']);
        $lg_lead_7_2 = $lg_lead_7_2 + round($row12['b']);
    }
    
    $sql_13 = mysqli_query($conn, "SELECT ad_id, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_15."' AND eDate<='".$st_1."' GROUP by ad_id");
    while($row13 = mysqli_fetch_array($sql_13)) {
        $lg_cpl_15_2[$row13['ad_id']] = round($row13['c'] * 100, 2);
        $lg_spend_15_2 = $lg_spend_15_2 + round($row13['a']);
        $lg_lead_15_2 = $lg_lead_15_2 + round($row13['b']);
    }
    
    $sql_14 = mysqli_query($conn, "SELECT ad_id, adset_name, campaign_id, ad_name, campaign_name, obj_type, SUM(spend) as a, SUM(leads) as b, (SUM(leads)/SUM(spend)) as c FROM audit_adIns_30d_cpl_2 WHERE accId='".$accId."' AND (obj_type='LEAD_GENERATION' || obj_type='OUTCOME_LEADS') AND sDate>='".$en_30."' AND eDate<='".$st_1."' GROUP by ad_id");
    while($row14 = mysqli_fetch_array($sql_14)) { 
        $lgCPL30_id[]= $row14['ad_id'];
        $lg_camp_ids_30[$row14['ad_id']] = $row14['campaign_id'];
        $lg_cpl_30_2[$row14['ad_id']] = round($row14['c'] * 100, 2);
        $lg_spend_30_2 = $lg_spend_30_2 + round($row14['a']);
        $lg_lead_30_2 = $lg_lead_30_2 + round($row14['b']);
        $lg_adset_name_30[$row14['ad_id']] = '<a href="https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_ad_ids='.$row14['ad_id'].'" target="_blank">'.$row14['ad_name'].'</a>';
        $lg_camp_name_30[$row14['ad_id']] = '<a href="https://www.facebook.com/adsmanager/manage/adsets?act='.$accId.'&selected_campaign_ids='.$row14['campaign_id'].'&selected_ad_ids='.$row14['ad_id'].'" target="_blank">'.$row14['campaign_name'].'</a>';
    }
    
    if(count($lgCPL30_id)>0) {
        
        $lg_tbl = '<table  id="" class="display" style="width:100%">
        <thead>
            <tr>
            <th>Ad</th>
            <th>Campaign</th>
            <th>Last 3d / Prev. 3d </th>
            <th>Last 7d / Prev. 7d</th>
            <th>Last 15d / Prev. 15d</th>
            <th>Last 30d / Prev. 30d</th>
            </tr>
        </thead>
        <tbody>';
        
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
            
            $Link_url = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId.'&selected_campaign_ids='.$lg_camp_ids_30[$v].'&selected_ad_ids='.$v;
            
            $lg_tbl .= '<tr><td>'.$lg_adset_name_30[$v].'</td>'; 
            $lg_tbl .= '<td>'.$lg_camp_name_30[$v].'</td>'; 
            $lg_tbl .='<td><a href="'.$Link_url.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$val_lg_3_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$val_lg_3.'</a> ('.$lg_cpl_30_2[$v].')</td>'; 
            $lg_tbl .='<td><a href="'.$Link_url.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$val_lg_7_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$val_lg_7.'</a> ('.$lg_cpl_30_2[$v].')</td>';
            $lg_tbl .='<td><a href="'.$Link_url.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$val_lg_15_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$val_lg_15.'</a> ('.$lg_cpl_30_2[$v].')</td>';
            $lg_tbl .='<td><a href="'.$Link_url.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$val_lg_30_2.'</span></a> / <a href="'.$Link_url.'&date='.$en_60.'_'.$st_31.'" target="_blank">'.$val_lg_30.'</a></td>'; 
            $lg_tbl .= '</tr>'; 
            
        }
        $Link_url2 = 'https://www.facebook.com/adsmanager/manage/ads?act='.$accId;

        $totCpl_3 =  @(round(($lg_spend_3/$lg_lead_3) * 100,2)); 
        $totCpl_7 =  @(round(($lg_spend_7/$lg_lead_7) * 100,2));
        $totCpl_15 =  @(round(($lg_spend_15/$lg_lead_15) * 100,2));
        $totCpl_30 =  @(round(($lg_spend_30/$lg_lead_30) * 100,2));

        $totCpl_3_2 =  @(round(($lg_spend_3_2/$lg_lead_3_2) * 100,2)); 
        $totCpl_7_2 =  @(round(($lg_spend_7_2/$lg_lead_7_2) * 100,2));
        $totCpl_15_2 =  @(round(($lg_spend_15_2/$lg_lead_15_2) * 100,2));
        $totCpl_30_2 =  @(round(($lg_spend_30_2/$lg_lead_30_2) * 100,2));

        if(isset($totCpl_3) && $totCpl_3<round(($totCpl_3_2),2) && $totCpl_3!=0) { $cls_1='red';  } else { $cls_1='green';  }
        if(isset($totCpl_7) && $totCpl_7<round(($totCpl_7_2),2) && $totCpl_7!=0) { $cls_2='red';  } else { $cls_2='green';  }
        if(isset($totCpl_15) && $totCpl_15<round(($totCpl_15_2),2) && $totCpl_15!=0) { $cls_3='red';  } else { $cls_3='green';  }
        if(isset($totCpl_30) && $totCpl_30<round(($totCpl_30_2),2) && $totCpl_30!=0) { $cls_4='red';  } else { $cls_4='green';  }

        $lg_tbl .= '<tr><td colspan="2">Total</td>'; 
            //$lg_tbl .= '<td>'.$lg_adset_name_30[$v].'</td>'; 
            $lg_tbl .='<td><a href="'.$Link_url2.'&date='.$en_3.'_'.$st_1.'" target="_blank"><span class="'.$cls_1.'">'.$totCpl_3_2.'</span></a> / <a href="'.$Link_url2.'&date='.$en_6.'_'.$st_4.'" target="_blank">'.$totCpl_3.'</a> ('.$totCpl_30_2.')</td>'; 
            $lg_tbl .='<td><a href="'.$Link_url2.'&date='.$en_7.'_'.$st_1.'" target="_blank"><span class="'.$cls_2.'">'.$totCpl_7_2.'</span></a> / <a href="'.$Link_url2.'&date='.$en_14.'_'.$st_8.'" target="_blank">'.$totCpl_7.'</a> ('.$totCpl_30_2.')</td>';
            $lg_tbl .='<td><a href="'.$Link_url2.'&date='.$en_15.'_'.$st_1.'" target="_blank"><span class="'.$cls_3.'">'.$totCpl_15_2.'</span></a> / <a href="'.$Link_url2.'&date='.$en_30.'_'.$st_16.'" target="_blank">'.$totCpl_15.'</a> ('.$totCpl_30_2.')</td>';
            $lg_tbl .='<td><a href="'.$Link_url2.'&date='.$en_30.'_'.$st_1.'" target="_blank"><span class="'.$cls_4.'">'.$totCpl_30_2.'</span></a> / <a href="'.$Link_url2.'&date='.$en_60.'_'.$st_31.'" target="_blank">'.$totCpl_30.'</a></td>'; 
            $lg_tbl .= '</tr>'; 

        $lg_tbl .= '</tbody></table>';
    }
    
    echo $lg_tbl; 
    echo '<br><br>'; 
    //exit;
    
        ?>	
  <table id="" class="display" style="width:100%">
        <thead>
            <tr>
                <th>StudentID</th>
                <th>StudentName</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Marks Scored</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>ST15</td>
                <td>Varun</td>
                <td>41</td>
                <td>male</td>
                <td>262</td>
            </tr>
            <tr>
                <td>ST16</td>
                <td>Waheeda</td>
                <td>47</td>
                <td>Female</td>
                <td>373</td>
            </tr>
            <tr>
                <td>ST17</td>
                <td>Charu</td>
                <td>31</td>
                <td>female</td>
                <td>475</td>
            </tr>
            <tr>
                <td>ST18</td>
                <td>Dhriti</td>
                <td>45</td>
                <td>female</td>
                <td>227</td>
            </tr>
            <tr>
                <td>ST19</td>
                <td>Haritha</td>
                <td>39</td>
                <td>female</td>
                <td>295</td>
            </tr>
            <tr>
                <td>ST20</td>
                <td>Faran</td>
                <td>39</td>
                <td>male</td>
                <td>340</td>
            </tr>
            <tr>
                <td>ST21</td>
                <td>Gaurav</td>
                <td>31</td>
                <td>male</td>
                <td>562</td>
            </tr>
            <tr>
                <td>ST22</td>
                <td>Fenny</td>
                <td>41</td>
                <td>Female</td>
                <td>349</td>
            </tr>
            <tr>
                <td>ST23</td>
                <td>Mamta</td>
                <td>29</td>
                <td>Female</td>
                <td>471</td>
            </tr>
            <tr>
                <td>ST23</td>
                <td>Kamat</td>
                <td>44</td>
                <td>male</td>
                <td>319</td>
            </tr>
        </tbody>
    </table>
    <script>
        /* Initialization of datatables */
        $(document).ready(function () {
            $('table.display').DataTable();
        });
    </script>
</body>
  
</html>