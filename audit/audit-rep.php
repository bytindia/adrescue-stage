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
Auth2();
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
 /* Masonry Container */
 .masonry-container {
        column-count: 3; /* 4 Columns */
        column-gap: 1.5rem; /* Gap between cards */
        width: 80%;
        margin: 0 auto;
    }

    .masonry-item {
        display: inline-block;
        width: 100%;
       
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Breakpoints */
    @media (max-width: 992px) {
        .masonry-container {
            column-count: 2; /* 2 columns for medium screens */
        }
    }

    @media (max-width: 576px) {
        .masonry-container {
            column-count: 1; /* 1 column for small screens */
        }
    }
</style>
<body>
<a href="logout.php" class="logout-icon">
        <i class="fas fa-sign-out-alt" style="font-size: 15px; color:#fff; "></i>
</a>

<center><h4><?php echo $acc_info['name']; ?> - Audit Report</h4></center>
    <br>
    <div class="clearfix" />
<div class="masonry-container mt-3">
    
        <!-- First Column: Card 1 -->
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-user-circle"></i> Account Info</h4>
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
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-chart-line"></i> Active Campaigns Summary</h4>
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
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-money-bill-wave"></i> Bid Strategy and Budget</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                    <th>Budget (Bid)</th>
                                    <th>Campaign</th>
                                    <th>AdSet</th>
                            </thead>
                            <tbody>
                                <?php if($camp_daily_bud_m>0 ||  $adset_daily_bud_m>0) { ?>
                                <tr>
                                    <td>Daily (Manual)</td>
                                    <td><?php  if($camp_daily_bud_m>0) { echo '₹ '.nf($camp_daily_bud_m).' ('.count($daily_bud_camp_manual).')'; } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_daily_bud_m>0) { echo '₹ '.nf($adset_daily_bud_m).' ('.count($adset_bud_camp_manual).')'; } else { echo '-'; } ?></td>
                                </tr>
                                <?php }
                                if($camp_life_bud_m>0 ||  $adset_life_bud_m>0) { ?>
                                <tr>
                                    <td>Lifetime (Manual)</td>
                                    <td><?php  if($camp_life_bud_m>0) { echo '₹ '.nf($camp_life_bud_m).' ('.count($life_bud_camp_manual).')'; } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_life_bud_m>0) { echo '₹ '.nf($adset_life_bud_m).' ('.count($life_bud_adset_manual).')'; } else { echo '-'; } ?></td>
                                </tr>
                                <?php }
                                if($camp_daily_bud_a>0 ||  $adset_daily_bud_a>0) { ?>
                                <tr>
                                    <td>Daily (Auto)</td>
                                    <td><?php  if($camp_daily_bud_a>0) { echo '₹ '.nf($camp_daily_bud_a).' ('.count($daily_bud_camp_auto).')'; } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_daily_bud_a>0) { echo '₹ '.nf($adset_daily_bud_a).' ('.count($daily_bud_adset_auto).')'; } else { echo '-'; } ?></td>
                                </tr>
                                <?php }
                                if($camp_life_bud_a>0 ||  $adset_life_bud_a>0) { ?>
                                <tr>
                                    <td>Lifetime (Auto)</td>
                                    <td><?php  if($camp_life_bud_a>0) { echo '₹ '.nf($camp_life_bud_a).' ('.count($life_bud_camp_auto).')'; } else { echo '-'; } ?></td>
                                    <td><?php  if($adset_life_bud_a>0) { echo '₹ '.nf($adset_life_bud_a).' ('.count($life_bud_adset_auto).')'; } else { echo '-'; } ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-users"></i> Custom Audience - List</h4>
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

        <!-- High & Low CPL -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(isset($top_campaigns[$k]['low_cpl']) && count($top_campaigns[$k]['low_cpl'])>0) { ?>
            
            <?php if(${$v['key'] . '_best_lead'}!='') { ?>
            <div class="masonry-item">
                <div class="card" style="width: 100%;">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title"><i class="fas fa-arrow-circle-down"></i> Low <? echo $v['cpl']; ?> Campaigns (<? echo $v['name']; ?>)</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><th>Campaign</th><th>CPL</th></thead>
                                <tbody>
                                    <?php foreach ($top_campaigns[$k]['low_cpl'] as $ky => $val) { ?>
                                    <tr><td><?php echo substr($val['campaign_name'], 0, 30) . '...'; ?></td><td><?php echo round($val['cpl']); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>  
            <?php  } ?>
            <?php if(isset($top_campaigns[$k]['high_cpl']) && count($top_campaigns[$k]['high_cpl'])>0) { ?>
            <div class="masonry-item">
                <div class="card" style="width: 100%;">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title"><i class="fas fa-arrow-circle-up"></i> High <? echo $v['cpl']; ?> Campaigns (<? echo $v['name']; ?>)</h4>
                    </div>
                    <div class="card-body">
                            <table class="table">
                                <thead><th>Campaign</th><th>CPL</th></thead>
                                <tbody>
                                    <?php foreach ($top_campaigns[$k]['high_cpl'] as $ky => $val) { ?>
                                    <tr><td><?php echo substr($val['campaign_name'], 0, 30) . '...'; ?></td><td><?php echo round($val['cpl']); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>  
            <?php  } ?>

        <?php  } } ?>

        <!-- Targeting -->
        <?php 
        $targeting_opt = array('age_target', 'work_position', 'work_emp', 'interests_target'); 
        $targeting_name = array('Age', 'Work Position', 'Work Employer', 'Interests');
        foreach ($targeting_opt  as $k1 => $targ) {
            $targeting_v = array_unique(${$targ}); 
        ?>
        <div class="masonry-item">
            <div class="card" style="width: 100%; <?php if(count($targeting_v)>4) { ?>min-height:250px;<?php } ?>">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-chart-line"></i> <?php echo $targeting_name[$k1]; ?> - Targeting</h4>
                </div>
                <div class="card-body">
                    <div class="<?php if(count($targeting_v)>4) { ?>pre-scrollable<?php } ?> pt-2 pb-2">
                    <ul class="list-group">
                        <?php 
                        if(count($targeting_v)>0) {
                            //$targeting_v = array_unique(${$targ});
                            foreach ($targeting_v as $v) {
                                echo '<li class="list-group-item">'.$v.'</li>';
                            }
                        } else {
                            echo '<li class="list-group-item">No data found!</li>';
                        }
                        ?>
                    </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php }  ?>

        <!-- Dynamic Creative Ads -->
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-bolt"></i> Dynamic Creative Ads</h4>
                </div>
                <div class="card-body">
                    <div class="pt-2">
                    <ul class="list-group">
                        <table class="table">
                            <tr><td>Yes</td><td><?php if(isset($dynamic['y'])) { echo count($dynamic['y']).' Ad(s)'; } else { echo '-'; } ?></td></tr>
                            <tr><td>No</td><td><?php if(isset($dynamic['n'])) { echo count($dynamic['n']).' Ad(s)'; } else { echo '-'; } ?></td></tr>
                        </table>
                    </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Placement Type -->
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-th-large"></i> Placement Type</h4>
                </div>
                <div class="card-body">
                    <div class="pt-2">
                    <ul class="list-group">
                        <table class="table">
                            <tr><td>Auto</td><td><?php if(isset($placement['auto'])) { echo count($placement['auto']).' Ad(s)'; } else { echo '-'; } ?></td></tr>
                            <tr><td>Manual</td><td><?php if(isset($placement['manual'])) { echo count($placement['manual']).' Ad(s)'; } else { echo '-'; } ?></td></tr>
                        </table>
                    </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Quality Ranking -->
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-chart-line"></i> Ad Quality Ranking</h4>
                </div>
                <div class="card-body">
                    <div class="pt-2">
                        <?php 
                        if(isset($ad_qty_ranking) && count($ad_qty_ranking)>0) { 
                            $vals = array_count_values($ad_qty_ranking);
                            echo '<table class="table">';
                            foreach ($vals as $k => $v) { 
                                echo '<tr><td>'.str_replace('_', ' ', ucfirst(strtolower($k))).'</td><td>'.$v.' Ad(s)</td></tr>';
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
        <div class="masonry-item">
            <div class="card" style="width: 100%;">
                <div class="card-header card-header-warning">
                    <h4 class="card-title"><i class="fas fa-link"></i> Landing Page - URLs</h4>
                </div>
                <div class="card-body">
                    <div class="pt-2">
                        <?php 
                        if(isset($lp_urls) && count($lp_urls)>0) { 
                            $vals = array_count_values($lp_urls);
                            echo '<table class="table">';
                            foreach ($vals as $k => $v) { 
                                echo '<tr><td>'.$k.'</td><td>'.$v.' Ad(s)</td></tr>';
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

        <!-- Best Perform Ads -->
        <?php 
        foreach ($objectives as $k => $v) { 
            if(${$v['key'] . '_best_lead'}!='' || ${$v['key'] . '_best_cpl'}!='') { ?>
            
            <?php if(${$v['key'] . '_best_lead'}!='') { ?>
            <div class="masonry-item">
                <div class="card" style="width: 100%;">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title"><i class="fas fa-star"></i> Best Ad (<? echo $v['name']; ?> - Leads)</h4>
                    </div>
                    <div class="card-body" style="overflow: hidden; padding: 0px;">
                        <div style="font-size: 16px; font-weight: bold; color: #333; margin: 10px 0;"><?php echo ${$v['key'] . '_best_cpl'}; ?></div>
                    </div>
                </div>
            </div>  
            <?php  } ?>
            <?php if(${$v['key'] . '_best_cpl'}!='') { ?>
            <div class="masonry-item">
                <div class="card" style="width: 100%;">
                    <div class="card-header card-header-warning">
                        <h4 class="card-title"><i class="fas fa-star"></i> Best Ad (<? echo $v['name']; ?> - <? echo $v['cpl']; ?>)</h4>
                    </div>
                    <div class="card-body" style="overflow: hidden; padding: 0px;">
                        <div style="font-size: 16px; font-weight: bold; color: #333; margin: 10px 0;"><?php echo ${$v['key'] . '_best_cpl'}; ?></div>
                    </div>
                </div>
            </div>  
            <?php  } ?>

        <?php  } } ?>

        
    
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

