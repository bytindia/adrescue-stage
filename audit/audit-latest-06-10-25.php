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
$no_data_txt = '<div class="no_data"> No data found.</div>';
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
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
    color: #007bff; font-size: 18px;
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
    color: #b3afaf; /*#b3afaf;*/
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
.no_data {
    padding: 10px 5px;
}
.hor-scroll {
    overflow-x: auto !important;
}

</style>
<body>
<!--
<a href="logout.php" class="logout-icon">
        <i class="fas fa-sign-out-alt" style="font-size: 15px; color:#fff; "></i>
</a>
-->


<div class="container mt-3">
    <center><h5><?php echo $acc_info['name']; ?> - Audit Report</h5></center>
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
                            <tr><td>Acc. Name</td><td><?php echo $acc_info['name']; ?></td></tr>
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
                    <h4 class="card-title">Active Campaigns <i class="fas fa-bullhorn"></i></h4>
                </div>
                <div class="card-body" style="overflow: hidden;">
                    <?php if(count(array_unique($act_campIds))>0){ ?>
                        <div id="chart_div" style="width: 300px; height: 130px;"></div>
                    <?php } else { echo $no_data_txt; } ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title">Objective wise Report <i class="fas fa-tasks"></i></h4>
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
                    </div>
                    <?php } else { echo $no_data_txt; } ?>
                    <div class="note">* In Last 30 days</div>
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
                    <?php if($camp_daily_bud_m>0 ||  $adset_daily_bud_m>0 || $camp_life_bud_m>0 ||  $adset_life_bud_m>0 || $camp_daily_bud_a>0 ||  $adset_daily_bud_a>0 || $camp_life_bud_a>0 ||  $adset_life_bud_a>0) { ?>
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
                        <?php } else { echo $no_data_txt; } ?>
                        <div class="note">* In Active Campaigns & Adsets</div>
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
    <div class="note mt-2">* In Active Adsets & Campaigns</div>
    <div class="row">

        <!-- High & Low CPL -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(isset($top_campaigns[$k]['low_cpl']) && count($top_campaigns[$k]['low_cpl'])>0) { 
                usort($top_campaigns[$k]['low_cpl'], function($a, $b) {
                    return $a['cpl'] <=> $b['cpl']; // Spaceship operator for comparison
                });
                
            ?>
            <?php if(${$v['key'] . '_best_lead'}!='') { ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Low <? echo $v['cpl']; ?> AdSets (<? echo $v['name']; ?>) <i class="fas fa-arrow-circle-down green"></i></h4>
                    </div>
                    <div class="card-body pb-3 pt-2 <?php if(count($top_campaigns[$k]['low_cpl'])>4) { ?>height250<?php } ?>">
                        <div class="table-responsive <?php if(count($top_campaigns[$k]['low_cpl'])>4) { ?>pre-scrollable<?php } ?>">
                            <table class="table sortable-table">
                                <thead><th>AdSet</th><th class="text-left">Campaign</th><th><? echo $v['cpl']; ?></th></thead>
                                <tbody>
                                    <?php 
                                    foreach ($top_campaigns[$k]['low_cpl'] as $ky => $val) { 
                                        $AS_Link = $fb_url_adset.''.$qry_str_adset.''.$val['asId'].''.$dt_qry_30;
                                        $CAMP_Link = $fb_url_camp.''.$qry_str_camp.''.$val['camp_id'].''.$dt_qry_30;    
                                    ?>
                                    <tr><td><a href="<?php echo $AS_Link; ?>" target="_blank"><?php echo substr($val['asN'], 0, 100) . ''; ?></a></td><td class="text-left"><a href="<?php echo $CAMP_Link; ?>" target="_blank"><?php echo substr($val['campaign_name'], 0, 100) . ''; ?></a></td><td><?php echo nf(round($val['cpl'])); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="note mt-2">* In Last 30 days</div>
                    </div>
                </div>
            </div>  
            <?php  } ?>
            <?php if(isset($top_campaigns[$k]['high_cpl']) && count($top_campaigns[$k]['high_cpl'])>0) { 
                usort($top_campaigns[$k]['high_cpl'], function($a, $b) {
                    return $b['cpl'] <=> $a['cpl']; // Spaceship operator for comparison
                });?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title"> High <? echo $v['cpl']; ?> AdSets (<? echo $v['name']; ?>) <i class="fas fa-arrow-circle-up red"></i></h4>
                    </div>
                    <div class="card-body pb-3 pt-2 <?php if(count($top_campaigns[$k]['high_cpl'])>4) { ?>height250<?php } ?>">
                        <div class="table-responsive <?php if(count($top_campaigns[$k]['high_cpl'])>4) { ?>pre-scrollable<?php } ?>">
                            <table class="table sortable-table">
                                <thead><th>AdSet</th><th class="text-left">Campaign</th><th><? echo $v['cpl']; ?></th></thead>
                                <tbody>
                                    <?php foreach ($top_campaigns[$k]['high_cpl'] as $ky => $val) { 
                                        $AS_Link = $fb_url_adset.''.$qry_str_adset.''.$val['asId'].''.$dt_qry_30;
                                        $CAMP_Link = $fb_url_camp.''.$qry_str_camp.''.$val['camp_id'].''.$dt_qry_30;   
                                        ?>
                                    <tr><td><a href="<?php echo $AS_Link; ?>" target="_blank"><?php echo substr($val['asN'], 0, 100) . ''; ?></a></td><td class="text-left"><a href="<?php echo $CAMP_Link; ?>" target="_blank"><?php echo substr($val['campaign_name'], 0, 100) . ''; ?></a></td><td><?php echo nf(round($val['cpl'])); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="note mt-2">* In Last 30 days</div>
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
                                    <?php foreach ($zero_lead_ads[$k] as $ky => $val) { 
                                        $AD_Link = $fb_url_ad.''.$qry_str_ad.''.$val['id'].''.$dt_qry_3;
                                        $CAMP_Link = $fb_url_camp.''.$qry_str_camp.''.$val['camp_id'].''.$dt_qry_3;
                                        ?>
                                    <tr><td><a href="<?php echo $AD_Link; ?>" target="_blank"><?php echo substr($val['ad_name'], 0, 100) . ''; ?></a></td><td style="text-align: left;"><a href="<?php echo $CAMP_Link; ?>" target="_blank"><?php echo substr($val['camp_name'], 0, 100) . ''; ?></a></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="note mt-2">* In Last 3 days</div>
                    </div>
                </div>
            </div>
        <?php  } } ?>
    </div>

    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title">Audience & Targeting <i class="fas fa-bullseye"></i></h4>
    </div>
    </div>
    <div class="note mt-2">* In Active Ads</div>
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
                            <tr><td>Custom Audience <span class="note2">(<?echo count(array_unique($cust_aud_list)); ?>)</span></td><td><?php if(count($cust_aud_list)==0) { echo '<i class="fas fa-times-circle"></i>'; } else { echo '<i class="fas fa-check-circle"></i>'; } ?></td></tr>
                            <tr><td>Lookalike Audience <span class="note2">(<?echo count(array_unique($lookalike_aud_list)); ?>)</span></td><td><?php if(count($lookalike_aud_list)==0) { echo '<i class="fas fa-times-circle"></i>'; } else { echo '<i class="fas fa-check-circle"></i>'; } ?></td></tr>
                            <tr><td>Remarketing Audience <span class="note2">(<?echo count(array_unique($remark_aud_list)); ?>)</span></td><td><?php if(count($remark_aud_list)==0) { echo '<i class="fas fa-times-circle"></i>'; } else { echo '<i class="fas fa-check-circle"></i>'; } ?></td></tr>
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
                    <div id="chart_dynamic" style="width: 250px; height: 120px;"></div>
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
                    <div id="chart_place" style="width: 250px; height: 120px;"></div>
                </div>
            </div>
        </div>

        <?php 
        $targeting_opt = array('age_target', 'work_position', 'work_emp', 'interests_target'); 
        $targeting_name = array('Age', 'Work Position', 'Work Employer', 'Interests');
        $targeting_icon = array('fa-baby', 'fa-briefcase', 'fa-building', 'fa-heart');
        foreach ($targeting_opt  as $k1 => $targ) {
            //$targeting_v = array_unique(${$targ}); 
            $targeting_v = array_count_values(${$targ});
        ?>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><?php echo $targeting_name[$k1]; ?> <i class="fas <?php echo $targeting_icon[$k1]; ?>"></i></h4>
                </div>
                <div class="card-body pb-3 pt-2 <?php if(count($targeting_v)>4) { ?>height250<?php } ?>">
                    <div class="<?php if(count($targeting_v)>4) { ?>pre-scrollable<?php } ?>">
                    <ul class="list-group">
                        <?php 
                        if(count($targeting_v)>0) {
                            //$targeting_v = array_unique(${$targ});
                            foreach ($targeting_v as $k => $v) {
                                echo '<li class="list-group-item">'.$k.' <span class="note2">('.$v.')</span></li>';
                            }
                        } else {
                            echo $no_data_txt;
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
                    <h4 class="card-title">Ad Quality Ranking <i class="fas fa-star"></i></h4>
                </div>
                <?php $vals = array(); if(isset($ad_qty_ranking) && count($ad_qty_ranking)>0) { $vals = array_count_values($ad_qty_ranking); } ?>
                <div class="card-body pb-1 <?php if(count($vals)>4) { ?>height250<?php } ?>">
                    <div>
                        <?php 
                        if(count($vals)>0) { 
                            echo '<div id="chart_quality" style="width: 250px; height: 200px;"></div>';
                        } else {
                            echo $no_data_txt;
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
                <?php $vals = array(); if(isset($lp_urls) && count($lp_urls)>0) { $vals = array_count_values($lp_urls); unset($vals['http://fb.me']); } ?>
                <div class="card-body pb-3 pt-2 <?php if(count($vals)>4) { ?>height250<?php } ?>">
                    <div class="hor-scroll <?php if(count($vals)>4) { ?>pre-scrollable<?php } ?>">
                        <?php 
                        if(count($vals)>0) {
                            echo '<table class="table"><tr><th>Link</th><th class="text-center">FB Pixel</th><th class="text-center">G. Pixel</th><th class="text-center">Ads</th></tr>';

                            foreach ($vals as $k => $v) { 
                                $pixel_check = checkPixel($k);
                                $fb_pix = $g_pix = 'fas fa-exclamation-circle';
                                if(!isset($pixel_check['err'])) { $fb_pix = $pixel_check['fb']; $g_pix = $pixel_check['g']; ; }
                                echo '<tr><td><a href="'.$k.'" target="_blank">'.$k.'</a></td><td class="text-center"><i class="'.$fb_pix.'"></i></td><td class="text-center"><i class="'.$g_pix.'"></i></td><td class="text-center">'.$v.''.$ads_txt.'</td></tr>';
                            }
                            echo '</table>';
                        } else {
                            echo $no_data_txt;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title"> Placements & Lead Tracker <i class="fas fa-th-large"></i></h4>
    </div>
    </div>
    <div class="note mt-2">* In Last 30 days</div>
    <div class="row">  

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Placements Breakdown Summary <i class="fas fa-sitemap"></i></h4>
                    </div>
                    <div class="card-body <?php if(count($breakdowns)>8) { ?>height400<?php } ?>">
                    <div class="table-responsive <?php if(count($breakdowns)>8) { ?>pre-scrollable2 hor-scroll<?php } ?>">
                    <?php if(count($breakdowns)>0) { ?>
                        <table class="table sortable-table-breakdown" data-ordering="true"  data-toggle="table" >
                            <thead>
                                <tr>
                                <th>Source</th>
                                <th>Position</th>
                                <th>Platform</th>
                                <th>Spend</th>
                                <th data-order='desc' data-field="leads" data-sortable="true">Leads</th>
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
                        <?php  } else { echo $no_data_txt; } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">Lead Tracker <i class="fas fa-chart-line"></i></h4>
                    </div>
                    <div class="card-body <?php if(count($tracker_lead)>0) { ?>height400<?php } ?>">
                    <div class="table-responsive <?php if(count($tracker_lead)>0) { ?>pre-scrollable2 hor-scroll<?php } ?>"  style="overflow-y: hidden;">
                    <?php if(count($tracker_lead)>0) { ?>
                        <div id="chart_lt" style="width: 570px; height:380px;"></div>
                    <?php  } else { echo $no_data_txt; } ?>
                        </div>
                    </div>
                </div>
            </div>
            
    </div>

    <div class="card">
    <div class="card-header card-header text-center title-card">
            <h4 class="card-title"> Top-Performing Ads <i class="fas fa-star"></i></h4>
    </div>
    </div>
    <div class="note mt-2">* In Last 30 days</div>
    <div class="row">
        <!-- Best Perform Ads -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(${$v['key'] . '_best_lead'}!='' || ${$v['key'] . '_best_cpl'}!='') { ?>
            
            <?php if(${$v['key'] . '_best_lead'}!='') { ?>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title">More <?php echo $v['name2']; ?> <i class="fas fa-star"></i></h4>
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
                        <h4 class="card-title">Low <?php echo $v['cpl']; ?> <i class="fas fa-star"></i></h4>
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
<script type="text/javascript">
    <?php $vals = array(); if(isset($ad_qty_ranking) && count($ad_qty_ranking)>0) { $vals = array_count_values($ad_qty_ranking); } ?>
    
    <?php if(count(array_unique($act_campIds))>0){ ?>
        google.charts.load('current', { 'packages': ['corechart', 'bar'] });
        google.charts.setOnLoadCallback(drawAllCharts);

        // Draw all charts function
        function drawAllCharts() {
            drawChart1(); //Active Campaigns
            drawChart2(); //Dynamic creative ads
            drawChart3(); //Placements Type
            <?php if(count($vals)>0) { ?>drawChart4();<?php } ?>
            drawStuff();
        }
      // Callback function to draw the chart
      function drawChart1() {
        var data = google.visualization.arrayToDataTable([
          ['Category', 'Value', { role: 'style' }, { role: 'annotation' }],
          ['Campaign', <?php echo count(array_unique($act_campIds)); ?>, 'color: #76A7FA', '<?php echo count(array_unique($act_campIds)); ?>'],  
          ['AdSet', <?php echo count(array_unique($act_ads_adset_ids)); ?>, 'color: #F9A825', '<?php echo count(array_unique($act_ads_adset_ids)); ?>'],
          ['Ad', <?php echo count(array_unique($act_ads_id)); ?>, 'color: #33B679', '<?php echo count(array_unique($act_ads_id)); ?>']
        ]);

        // Set chart options
        var options = {
          legend: 'none',
          annotations: {
            alwaysOutside: true,
            textStyle: {
              fontSize: 12,
              bold: true,
              color: '#000'
            }
          },
          bar: { groupWidth: '50%' }, 
          hAxis: {  gridlines: { color: 'none' },},
          vAxis: {  gridlines: { color: 'none' }, },
        };
        // Instantiate and draw the chart
        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
      function drawChart2() { 
        var data = google.visualization.arrayToDataTable([
          ['Category', 'Value', { role: 'style' }, { role: 'annotation' }],
          ['Yes', <?php if(isset($dynamic['y'])) { echo count($dynamic['y']); } else { echo 0; } ?>, 'color:rgb(43, 196, 94)', '<?php if(isset($dynamic['y'])) { echo count($dynamic['y']); } else { echo 0; } ?>'],  
          ['No', <?php if(isset($dynamic['n'])) { echo count($dynamic['n']); } else { echo 0; } ?>, 'color:rgb(248, 104, 104)', '<?php if(isset($dynamic['n'])) { echo count($dynamic['n']); } else { echo 0; } ?>']
        ]);

        // Set chart options
        var options = {
          legend: 'none',
          annotations: {
            alwaysOutside: true,
            textStyle: {
              fontSize: 12,
              bold: true,
              color: '#000'
            }
          },
          bar: { groupWidth: '50%' }, 
          hAxis: {  gridlines: { color: 'none' },},
          vAxis: {  title: 'AdSet', gridlines: { color: 'none' }, },
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_dynamic'));
        chart.draw(data, options);
      }
      function drawChart3() { 
        var data = google.visualization.arrayToDataTable([
          ['Category', 'Value', { role: 'style' }, { role: 'annotation' }],
          ['Auto', <?php if(isset($placement_ty['auto'])) { echo count($placement_ty['auto']); } else { echo 0; } ?>, 'color: #FF9800', '<?php if(isset($placement_ty['auto'])) { echo count($placement_ty['auto']); } else { echo 0; } ?>'],  
          ['Manual', <?php if(isset($placement_ty['manual'])) { echo count($placement_ty['manual']); } else { echo 0; } ?>, 'color: #9C27B0', '<?php if(isset($placement_ty['manual'])) { echo count($placement_ty['manual']); } else { echo 0; } ?> ']
        ]);

        // Set chart options
        // Set chart options
        var options = {
          legend: 'none',
          annotations: {
            alwaysOutside: true,
            textStyle: {
              fontSize: 12,
              bold: true,
              color: '#000'
            }
          },
          bar: { groupWidth: '50%' }, 
          hAxis: {  gridlines: { color: 'none' },},
          vAxis: {  title: 'AdSet', gridlines: { color: 'none' }, },
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_place'));
        chart.draw(data, options);
      }
      
      function drawChart4() {
            var data = google.visualization.arrayToDataTable([
                ['Ad Quality', 'Ads'],
                <?php if(count($vals)>0) { 
                    foreach ($vals as $k => $v) { ?>
                    ['<?php echo ucwords(str_replace('_', ' ', strtolower($k))); ?>', <?php echo $v; ?>],
                <?php } } ?>
            ]);

            var options = {
                //colors: ['#4CAF50', '#FF9800', '#03A9F4', '#E91E63', '#9C27B0','#F44336', '#8BC34A', '#FFEB3B', '#673AB7', '#2196F3'],
                is3D: true,
                legend: {
                    position: 'bottom', // Legend at the bottom
                    alignment: 'center'
                }
            };

            var chart = new google.visualization.PieChart(document.getElementById('chart_quality'));
            chart.draw(data, options);
        }

        function drawStuff() {
            var data = new google.visualization.arrayToDataTable([
            ['Time', 'Lead', 'CPL'],
            <?php
            foreach($timeRanges as $k => $val) 
            { 
                $spend = $lead = $cpl = 0;
                if(isset($tracker_lead[$k])) {
                    $spend = $tracker_lead[$k]['spend'];
                    $lead = $tracker_lead[$k]['lead'];
                    $cpl = $tracker_lead[$k]['cpl'];
                }
            ?>
            ['<?php echo $val; ?>', <?php if($lead!=0) { echo round($lead); } else { echo 0; } ?>, <?php if($cpl!=0) { echo round($cpl); } else { echo 0; } ?>],
            <?php } ?>
            ]);
           
            var options = {
                title: 'Leads and CPL Over Time',
                chartArea: {width: '80%', height: '75%'},  // Adjust chart area for horizontal bar layout
                hAxis: {
                    title: 'Time Range',
                    minValue: 0
                },
                vAxis: {
                    title: 'Lead',
                    viewWindow: {min: 0} // Set view window for the left axis (Lead)
                },
                vAxes: {
                    // Left axis (Lead) configuration
                    0: {
                    title: 'Lead',
                    },
                    // Right axis (CPL) configuration
                    1: {
                    title: 'CPL',
                    minValue: 0
                    }
                },
                seriesType: 'bars',
                series: {
                    0: {targetAxisIndex: 0, color: '#3498db'},  // Highlight the "Leads" column with a custom color (e.g., red)
                    1: {type: 'line', targetAxisIndex: 1} // CPL as line (right axis)
                },
                legend: {position: 'top'},
                bars: 'horizontal'  // Horizontal bars
            };

            var chart = new google.visualization.ComboChart(document.getElementById('chart_lt'));
            chart.draw(data, options);
        }
      <?php } ?>
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('.sortable-table').DataTable({
            searching: false,  // Disable the search filter
            paging: false,      // Disable pagination
            info: false,
            order: [] 
        });
        $('.sortable-table-breakdown').DataTable({
            searching: false,  // Disable the search filter
            paging: false,      // Disable pagination
            info: false,
            order: [[4, 'desc']]
        });
        $('iframe').css('overflow', 'hidden');
        $('iframe').css('scrolling', 'no'); 
        $('iframe').attr('scrolling', 'no');
    });
</script>
</body>
</html>
