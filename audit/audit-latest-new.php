<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db.php';
include '../media-buyer/include.php';
include '/home/digitalb2k/stage.adrescue.in/functions-report.php';

function Auth2()
{
	if(!isset($_SESSION['log'])) {
		$_SESSION['error'] = 'Please Login!';
        $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		echo "<script>window.location = 'login.php?redirect=".$fullUrl."';</script>";
		exit();
	}
} 
//Auth2();
$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token'];
$fbId = 5731659836926959;
if(isset($_GET['act'])){
    $fbId = $_GET['act'];
}

include 'include.php';
//d($acc_info);
$ads_txt = '<span class="note2"> Ad(s)</span>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/images/favicon.ico" type="image/ico" />
    <title>Media Buyer Dashboard - AdRescue</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="/media-buyer/style.css" rel="stylesheet">
</head>
<style>
    i.fas.fa-times-circle {
    color: #ed4e4e;
}
i.fas.fa-check-circle {
    color: #64ad57;
}
.fas {
    color: #007bff; font-size: 20px;
}
.pre-scrollable { max-height: 180px;
    overflow-x: hidden;
    overflow-y: scroll;
}
.pre-scrollable2 { max-height: 380px;
    overflow-x: hidden;
    overflow-y: scroll;
}
.height250 {  min-height:200px; }
.height400 {  min-height:400px; }
.title-card { background: #769edb; color: white; }
.title-card .fas { color: white; }

.note {
    text-align: center;
    font-size: 11px;
    padding-bottom: 5px;
    color: #b3afaf;
    font-style: italic;
}
.table td:not(:first-child), .table th:not(:first-child) {
    text-align: right;
}
.note2 {
    font-size: 11px;
    padding-bottom: 5px;
    color: #b3afaf;
}
.text-left { text-align: left !important; }
.text-right { text-align: right !important; }
.card-title {
    font-size: 16px;
    text-align: center;
}
.card { margin: 10px 5px !important; width: 100%; }
.red { color: red; }
.green { color: green; }
table.dataTable { border-spacing: 0; }
table.dataTable.no-footer {
    border-bottom: 0px solid #ebe9e9;
}
</style>
<body>
<a href="logout.php" class="logout-icon">
        <i class="fas fa-sign-out-alt" style="font-size: 15px; color:#fff; "></i>
</a>


<div class="container mt-3">
    <center><h4><?php echo $acc_info['name']; ?> - Audit Report</h4></center>
    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title">Account summary & Budget <i class="fas fa-user-circle"></i></h4>
    </div>
    </div>
    <div class="row">
        <!-- First Column: Card 1 -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Account Info <i class="fas fa-user-circle"></i></h4>
                </div>
                <div class="card-body">
                <div class="table-responsive">
                        <table class="table">
                            <tr><td>Account Name</td><td><?php echo $acc_info['name']; ?></td></tr>
                            <tr><td>Status</td><td><?php echo $adAccountStatus[$acc_info['account_status']]; if($acc_info['account_status']==1) { echo '  <i class="fas fa-check-circle"></i>'; } else { echo '  <i class="fas fa-times-circle"></i>'; }  ?></td></tr>
                            <tr><td>Age</td><td><?php echo round($acc_info['age']).' days'; ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Active Campaigns Summary <i class="fas fa-chart-line"></i></h4>
                </div>
                <div class="card-body">
                    <?php if(count(array_unique($act_campIds))>0){ ?>
                    <div class="table-responsive">
                    <table class="table">
                            <tr><td>Campaign</td><td><?php echo count(array_unique($act_campIds)); ?></td></tr>
                            <tr><td>Adset</td><td><?php echo count(array_unique($act_ads_adset_ids)); ?></td></tr>
                            <tr><td>Ad</td><td><?php echo count(array_unique($act_ads_id)); ?></td></tr>
                        </table>
                    </div>
                    <?php } else { echo 'No active campaigns / ads.'; } ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Objective wise Report <i class="fas fa-chart-line"></i></h4>
                </div>
                <div class="card-body">
                    <?php if(count($avg_cpl)>0){ ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><th>Objective</th><th>Result</th><th>CPL</th></thead>
                            <tbody>
                                <?php foreach ($avg_cpl as $ky => $val) { ?>
                                    <tr><td><?php echo ucwords(str_replace('_', ' ', strtolower($ky))); ?></td><td><?php echo nf($val['lead']); ?></td><td><?php echo nf($val['cpl']); ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <div class="note">* Last 30 days</div>
                    </div>
                    <?php } else { echo 'No active campaigns / ads.'; } ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Bid Strategy and Budget <i class="fas fa-money-bill-wave"></i></h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                    <th>Bud. (Bid)</th>
                                    <th>Campaign</th>
                                    <th>AdSet</th>
                            </thead>
                            <tbody>
                                <?php if($camp_daily_bud_m>0 ||  $adset_daily_bud_m>0) { ?>
                                <tr>
                                    <td>Daily (Manual)</td>
                                    <td><?php  if($camp_daily_bud_m>0) { echo nf($camp_daily_bud_m); } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_daily_bud_m>0) { echo nf($adset_daily_bud_m); } else { echo '-'; } ?></td>
                                </tr>
                                <?php }
                                if($camp_life_bud_m>0 ||  $adset_life_bud_m>0) { ?>
                                <tr>
                                    <td>Lifetime (Manual)</td>
                                    <td><?php  if($camp_life_bud_m>0) { echo nf($camp_life_bud_m); } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_life_bud_m>0) { echo nf($adset_life_bud_m); } else { echo '-'; } ?></td>
                                </tr>
                                <?php }
                                if($camp_daily_bud_a>0 ||  $adset_daily_bud_a>0) { ?>
                                <tr>
                                    <td>Daily (Auto)</td>
                                    <td><?php  if($camp_daily_bud_a>0) { echo nf($camp_daily_bud_a); } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_daily_bud_a>0) { echo nf($adset_daily_bud_a); } else { echo '-'; } ?></td>
                                </tr>
                                <?php }
                                if($camp_life_bud_a>0 ||  $adset_life_bud_a>0) { ?>
                                <tr>
                                    <td>Lifetime (Auto)</td>
                                    <td><?php  if($camp_life_bud_a>0) { echo nf($camp_life_bud_a); } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_life_bud_a>0) { echo nf($adset_life_bud_a); } else { echo '-'; } ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
        
    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title"> Campaign Performance <i class="fas fa-chart-line"></i></h4>
    </div>
    </div>
    <div class="row">

        <!-- High & Low CPL -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(isset($top_campaigns[$k]['low_cpl']) && count($top_campaigns[$k]['low_cpl'])>0) { ?>
            <?php if(${$v['key'] . '_best_lead'}!='') { ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Low <? echo $v['cpl']; ?> AdSets (<? echo $v['name']; ?>) <i class="fas fa-arrow-circle-down green"></i></h4>
                    </div>
                    <div class="card-body pb-3 pt-2 <?php if(count($top_campaigns[$k]['low_cpl'])>4) { ?>height250<?php } ?>">
                        <div class="table-responsive <?php if(count($top_campaigns[$k]['low_cpl'])>4) { ?>pre-scrollable<?php } ?>">
                            <table class="table sortable-table">
                                <thead><th>AdSet</th><th class="text-left">Campaign</th><th>CPL</th></thead>
                                <tbody>
                                    <?php foreach ($top_campaigns[$k]['low_cpl'] as $ky => $val) { ?>
                                    <tr><td><?php echo substr($val['asN'], 0, 100) . ''; ?></td><td class="text-left"><?php echo substr($val['campaign_name'], 0, 100) . ''; ?></td><td><?php echo nf(round($val['cpl'])); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="note">* Last 30 days</div>
                    </div>
                </div>
            </div>  
            <?php  } ?>
            <?php if(isset($top_campaigns[$k]['high_cpl']) && count($top_campaigns[$k]['high_cpl'])>0) { ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title"> High <? echo $v['cpl']; ?> AdSets (<? echo $v['name']; ?>) <i class="fas fa-arrow-circle-up red"></i></h4>
                    </div>
                    <div class="card-body pb-3 pt-2 <?php if(count($top_campaigns[$k]['high_cpl'])>4) { ?>height250<?php } ?>">
                        <div class="table-responsive <?php if(count($top_campaigns[$k]['high_cpl'])>4) { ?>pre-scrollable<?php } ?>">
                            <table class="table sortable-table">
                                <thead><th>AdSet</th><th class="text-left">Campaign</th><th>CPL</th></thead>
                                <tbody>
                                    <?php foreach ($top_campaigns[$k]['high_cpl'] as $ky => $val) { ?>
                                    <tr><td><?php echo substr($val['asN'], 0, 100) . ''; ?></td><td class="text-left"><?php echo substr($val['campaign_name'], 0, 100) . ''; ?></td><td><?php echo nf(round($val['cpl'])); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="note">* Last 30 days</div>
                    </div>
                </div>
            </div>  
            <?php  } ?>
        <?php  } } ?>

        <!-- Zero Lead Ads -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(isset($zero_lead_ads[$k]) && count($zero_lead_ads[$k])>0) { ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">0 <? echo $v['name2']; ?> Ads <i class="fas fa-arrow-circle-down red"></i></h4>
                    </div>
                    <div class="card-body pb-3 pt-2 <?php if(count($zero_lead_ads[$k])>4) { ?>height250<?php } ?>">
                        <div class="table-responsive <?php if(count($zero_lead_ads[$k])>4) { ?>pre-scrollable<?php } ?>">
                            <table class="table sortable-table">
                                <thead><th>Ad</th><th style="text-align: left;">Campaign</th></thead>
                                <tbody>
                                    <?php foreach ($zero_lead_ads[$k] as $ky => $val) { ?>
                                    <tr><td><?php echo substr($val['ad_name'], 0, 100) . ''; ?></td><td style="text-align: left;"><?php echo substr($val['camp_name'], 0, 100) . ''; ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="note">* Last 3 days</div>
                    </div>
                </div>
            </div>
        <?php  } } ?>
    </div>

    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title">Targeting <i class="fas fa-bullseye"></i></h4>
    </div>
    </div>
    <div class="row">
        <!-- Targeting -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Audience <i class="fas fa-users"></i></h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tr><td>Custom Audience</td><td><?php if(count($cust_aud_list)==0) { echo '<i class="fas fa-times-circle"></i>'; } else { echo '<i class="fas fa-check-circle"></i>'; } ?></td></tr>
                            <tr><td>Lookalike Audience</td><td><?php if(count($lookalike_aud_list)==0) { echo '<i class="fas fa-times-circle"></i>'; } else { echo '<i class="fas fa-check-circle"></i>'; } ?></td></tr>
                            <tr><td>Remarketing Audience</td><td><?php if(count($remark_aud_list)==0) { echo '<i class="fas fa-times-circle"></i>'; } else { echo '<i class="fas fa-check-circle"></i>'; } ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Creative Ads -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Dynamic Creative Ads <i class="fas fa-bolt"></i></h4>
                </div>
                <div class="card-body">
                    <div class="pt-2">
                    <ul class="list-group">
                        <table class="table">
                            <tr><td>Yes</td><td><?php if(isset($dynamic['y'])) { echo count($dynamic['y']).''.$ads_txt; } else { echo '-'; } ?></td></tr>
                            <tr><td>No</td><td><?php if(isset($dynamic['n'])) { echo count($dynamic['n']).''.$ads_txt; } else { echo '-'; } ?></td></tr>
                        </table>
                    </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Placement Type -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Placement Type <i class="fas fa-th-large"></i></h4>
                </div>
                <div class="card-body">
                    <div class="pt-2">
                    <ul class="list-group">
                        <table class="table">
                            <tr><td>Auto</td><td><?php if(isset($placement_ty['auto'])) { echo count($placement_ty['auto']).''.$ads_txt; } else { echo '-'; } ?></td></tr>
                            <tr><td>Manual</td><td><?php if(isset($placement_ty['manual'])) { echo count($placement_ty['manual']).''.$ads_txt; } else { echo '-'; } ?></td></tr>
                        </table>
                    </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php 
        $targeting_opt = array('age_target', 'work_position', 'work_emp', 'interests_target'); 
        $targeting_name = array('Age', 'Work Position', 'Work Employer', 'Interests');
        foreach ($targeting_opt  as $k1 => $targ) {
            $targeting_v = array_unique(${$targ}); 
        ?>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><?php echo $targeting_name[$k1]; ?> <i class="fas fa-chart-line"></i></h4>
                </div>
                <div class="card-body pb-3 pt-2 <?php if(count($targeting_v)>4) { ?>height250<?php } ?>">
                    <div class="<?php if(count($targeting_v)>4) { ?>pre-scrollable<?php } ?>">
                    <ul class="list-group">
                        <?php 
                        if(count($targeting_v)>0) {
                            //$targeting_v = array_unique(${$targ});
                            foreach ($targeting_v as $v) {
                                echo '<li class="list-group-item">'.$v.'</li>';
                            }
                        } else {
                            echo '<li class="list-group">No data found!</li>';
                        }
                        ?>
                    </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php }  ?>

        

        <!-- Ad Quality Ranking -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Ad Quality Ranking <i class="fas fa-chart-line"></i></h4>
                </div>
                <?php $vals = array(); if(isset($ad_qty_ranking) && count($ad_qty_ranking)>0) { $vals = array_count_values($ad_qty_ranking); } ?>
                <div class="card-body pb-3 pt-2 <?php if(count($vals)>4) { ?>height250<?php } ?>">
                    <div class="<?php if(count($vals)>4) { ?>pre-scrollable<?php } ?>">
                        <?php 
                        if(count($vals)>0) { 
                            //$vals = array_count_values($ad_qty_ranking);
                            echo '<table class="table">';
                            foreach ($vals as $k => $v) { 
                                echo '<tr><td>'.ucwords(str_replace('_', ' ', strtolower($k))).'</td><td>'.$v.''.$ads_txt.'</td></tr>';
                            }
                            echo '</table>';
                        } else {
                            echo 'No data found!';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- LP Links -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Ad / LP URL <i class="fas fa-link"></i></h4>
                </div>
                <?php $vals = array(); if(isset($lp_urls) && count($lp_urls)>0) { $vals = array_count_values($lp_urls); } ?>
                <div class="card-body pb-3 pt-2 <?php if(count($vals)>4) { ?>height250<?php } ?>">
                    <div class="<?php if(count($vals)>4) { ?>pre-scrollable<?php } ?>">
                        <?php 
                        if(count($vals)>0) {
                            echo '<table class="table">';
                            foreach ($vals as $k => $v) { 
                                echo '<tr><td><a href="'.$k.'" target="_blank">'.$k.'</a></td><td>'.$v.''.$ads_txt.'</td></tr>';
                            }
                            echo '</table>';
                        } else {
                            echo 'No data found!';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title"> Placements & Breakdown <i class="fas fa-th-large"></i></h4>
    </div>
    </div>
    <div class="row">
        
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Placements <i class="fas fa-star"></i></h4>
                    </div>
                    <div class="card-body <?php if(count($placements)>8) { ?>height400<?php } ?>">
                    <div class="table-responsive <?php if(count($placements)>8) { ?>pre-scrollable2<?php } ?>">
                    <?php if(count($placements)>0) { ?>
                        <table class="table sortable-table" data-ordering="true">
                            <thead>
                                <tr>
                                    <th>AdSet</th>
                                    <th>Source</th>
                                    <th>Position</th>
                                    <th>Platform</th>
                                    <th>Spend</th>
                                    <th>Leads</th>
                                    <th>CPL</th>
                                </tr>
                            <thead>
                            <tbody>
                            <?php 
                                $reach_tot = $impr_tot = $lead_tot = $spend_tot = $cpl_tot = 0;
                                $breakdowns = array();
                                foreach($placements as $k => $val) 
                                { 
                                    $lead = $cpl = 0;
                                    if(isset($val['actions'])) { $lead = LeadGen($val['actions'], 'lead'); }
                                    if($val['spend'] !=0 && $lead !=0) { $cpl = @($val['spend']/$lead); } 
                                    ?>
                                    <tr>
                                        <td><?php echo $val['adset_name']; ?></td> 
                                        <td><?php echo ucwords(str_replace('_',' ',$val['publisher_platform'])); ?></td>
                                        <td><?php echo ucwords(str_replace('_',' ',$val['platform_position'])); ?></td>
                                        <td><?php echo ucwords(str_replace('_',' ',$val['device_platform'])); ?></td>
                                        <td><?php echo nf(round($val['spend'])); ?></td>
                                        <td><?php echo nf(round($lead)); ?></td>
                                        <td><?php echo nf(round($cpl)); ?></td>
                                    </tr>
                                <?php
                                    $reach_tot += $val['reach'];
                                    $impr_tot += $val['impressions'];
                                    $lead_tot += $lead;
                                    $spend_tot += $val['spend'];
                            
                                    $tbl_key2 = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
                                    $breakdowns[$tbl_key2][] = array('reach'=>$val['reach'],'impressions'=>$val['impressions'],'lead'=>$lead,'cpl'=>$cpl,'spend'=>$val['spend']); 
                                }
                            ?>
                            </tbody>
                            <tr>
                                <th>Total</th>
                                <th>-</th>
                                <th>-</th>
                                <th>-</th>
                                <th><?php echo nf(round($spend_tot)); ?></th>
                                <th><?php echo nf(round($lead_tot)); ?></th>
                                <th><?php echo nf(round(@($spend_tot/$lead_tot))); ?></th>
                            </tr>
                        </table>
                    <?php  } else { echo 'No data found!'; } ?>
                        </div>
                    </div>
                </div>
            </div>  

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Breakdown Summary <i class="fas fa-star"></i></h4>
                    </div>
                    <div class="card-body <?php if(count($breakdowns)>8) { ?>height400<?php } ?>">
                    <div class="table-responsive <?php if(count($breakdowns)>8) { ?>pre-scrollable2<?php } ?>">
                    <?php if(count($breakdowns)>0) { ?>
                        <table class="table sortable-table" data-ordering="true">
                            <thead>
                                <tr>
                                <th>Source</th>
                                <th>Position</th>
                                <th>Platform</th>
                                <th>Spend</th>
                                <th>Leads</th>
                                <th>CPL</th>
                                </tr>
                            <thead>
                            <tbody>
                            <?php 
                                $reach_tot = $impr_tot = $lead_tot = $spend_tot = $cpl_tot = 0;
                                
                                foreach($breakdowns as $k => $val) 
                                { 
                                    $lead = $cpl = 0;
                                     

                                    $lead = array_sum(array_column($val, 'lead'));
                                    //$cpl = array_sum(array_column($val2, 'cpl'));
                                    $spend = array_sum(array_column($val, 'spend'));
                                    $src_val = explode('_#_',$k);
                                    if($spend !=0 && $lead !=0) { $cpl = @($spend/$lead); }
                                    ?>
                                    <tr>
                                        <td><?php echo ucwords(str_replace('_',' ',$src_val[0])); ?></td>
                                        <td><?php echo ucwords(str_replace('_',' ',$src_val[1])); ?></td>
                                        <td><?php echo ucwords(str_replace('_',' ',$src_val[2])); ?></td>
                                        <td><?php echo nf(round($spend)); ?></td>
                                        <td><?php echo nf(round($lead)); ?></td>
                                        <td><?php echo nf(round($cpl)); ?></td>
                                    </tr>
                                <?php
                                   
                                    $lead_tot += $lead;
                                    $spend_tot += $spend;
                                }
                            ?>
                            </tbody>
                            <tr>
                                <th>Total</th>
                                <th>-</th>
                                <th>-</th>
                               
                                <th><?php echo nf(round($spend_tot)); ?></th>
                                <th><?php echo nf(round($lead_tot)); ?></th>
                                <th><?php echo nf(round(@($spend_tot/$lead_tot))); ?></th>
                            </tr>
                        </table>
                        <?php  } else { echo 'No data found!'; } ?>
                        </div>
                    </div>
                </div>
            </div>
            
    </div>

    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title"> Best Performing Ads <i class="fas fa-star"></i></h4>
    </div>
    </div>
    <div class="row">
        <!-- Best Perform Ads -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(${$v['key'] . '_best_lead'}!='' || ${$v['key'] . '_best_cpl'}!='') { ?>
            
            <?php if(${$v['key'] . '_best_lead'}!='') { ?>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Best Ad (<? echo $v['name']; ?> - Leads) <i class="fas fa-star"></i></h4>
                    </div>
                    <div class="card-body" style="overflow: hidden; padding: 0px;">
                        <div style="font-size: 16px; font-weight: bold; color: #333; margin: 10px 0;"><?php echo ${$v['key'] . '_best_cpl'}; ?></div>
                    </div>
                </div>
            </div>  
            <?php  } ?>
            <?php if(${$v['key'] . '_best_cpl'}!='') { ?>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Best Ad (<? echo $v['name']; ?> - <? echo $v['cpl']; ?>) <i class="fas fa-star"></i></h4>
                    </div>
                    <div class="card-body" style="overflow: hidden; padding: 0px;">
                        <div style="font-size: 16px; font-weight: bold; color: #333; margin: 10px 0;"><?php echo ${$v['key'] . '_best_cpl'}; ?></div>
                    </div>
                </div>
            </div>  
            <?php  } ?>

        <?php  } } ?>

        
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('.sortable-table').DataTable({
            searching: false,  // Disable the search filter
            paging: false,      // Disable pagination
            info: false 
        });
    });
</script>
</body>
</html>
