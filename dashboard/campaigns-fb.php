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
<title>Facebook Ads dashboard</title>
<?php
$header_no = 1;
$pg='facebook';
//include 'config.php';
include 'header.php';

$query = "SELECT access_token,g_mcc,g_refresh_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token'];  

$fb_acc_ids = array(732236261026706, 229650465874267, 880541165925308, 624107499015180,665661671485180);
$fb_acc_name = array('RWD Account 1', 'RWD Account 2 ( New )', 'RWD Grand Corridor', 'RWD Spotlight', 'RWD Ibis County');

function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
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
$obj_arr = array(
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
	'OUTCOME_LEADS' => 'lead'
    );


//d($resData); exit;
?>
<style>
    table#datatable td {
    text-align: left;
}
</style>
<h3>Facebook ads dashboard</h3>
<table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th>Campaign Name</th>
                                        <th>Objective</th>
                                    	<th>Spend</th>
                                        <th>Leads</th>
                                        <th>CPL</th>
                                    	<th>Reach</th>
                                        <th>Impressions</th>
                                    </thead>
                                    <tbody>
                                    <?php
										//$val['data'] = array();
										if(isset($_GET['pgId']) && isset($_GET['pgType'])) { $after='&'.$_GET['pgType'].'='.$_GET['pgId']; } else { $after=''; }
										//echo 'https://graph.facebook.com/'.$api_ver.'/act_'.$_GET['id'].'/insights?level=campaign&fields=account_name,campaign_name,campaign_id,objective,spend,reach,impressions,account_currency,actions&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION","CONVERSIONS","POST_ENGAGEMENT"]}]&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($dtRange1)).'&time_range[until]='.date("Y-m-d", strtotime($dtRange2)).'&limit=500'; 
										$val = get_data('https://graph.facebook.com/'.$api_ver.'/act_'.$_GET['id'].'/insights?level=campaign&fields=account_name,campaign_name,campaign_id,objective,spend,reach,impressions,account_currency,actions&filtering=[{"field":"campaign.objective","operator":"IN","value":["LEAD_GENERATION","CONVERSIONS","POST_ENGAGEMENT"]}]&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($dtRange1)).'&time_range[until]='.date("Y-m-d", strtotime($dtRange2)).'&limit=500');
										//d($val);	
										//echo count($val['data']); 
										if(count($val['data'])==0) 
										{ 
											echo '<tr><td colspan="7">No records found!</td> </tr> '; 
										} else {
                                            $i=1;
											foreach ($val['data'] as $cS) 
											{ 
                                                $lead_t = 0;
                                                if(isset($cS['actions'])) { if(array_key_exists($cS['objective'], $obj_arr)) { $lead_t = LeadGen($cS['actions'], $obj_arr[$cS['objective']]); } }
                                                $cpl_t = @(round($cS['spend'] / $lead_t, 2));
												?>
											<tr>
												<td><?php echo $i; ?></td>
												<td><?php echo $cS['campaign_name']; ?></td>
                                                <td><?php echo $cS['objective']; ?></td>
												<td><?php if(isset($cS['spend'])) { echo $cS['spend']; } else { echo 0; } ?></td>
                                                <td><?php echo $lead_t ; ?></td>
                                                <td><?php echo $cpl_t; ?></td>
												<td><?php if(isset($cS['reach'])) { echo $cS['reach']; } else { echo 0; }  ?></td>
												<td><?php if(isset($cS['impressions'])) { echo $cS['impressions']; } else { echo 0; }  ?></td>
											</tr>  
											<?php $i++;
											} 
											if(isset($val['paging']['previous']) || isset($val['paging']['next'])) {
											?>  
											<tr>
												<td colspan="7">
												 <div id="pagDiv">
														<ul class="pagination">                                                                              
															<?php if(isset($val['paging']['previous'])) { ?><li><a href="?id=<?php echo $_GET['id']; ?>&pgType=before&pgId=<?php echo $val['paging']['cursors']['before']; ?>&page=<? echo $page-1; ?>"><< Prev</a></li><?php } ?>
															<?php if(isset($val['paging']['next'])) { ?><li><a href="?id=<?php echo $_GET['id']; ?>&pgType=after&pgId=<?php echo $val['paging']['cursors']['after']; ?>&page=<? echo $page+1; ?>">Next >></a></li><?php } ?>
														</ul>
													</div>
												 </td>
											</tr> 
											<?php 
											}   
									}	?>                              
                                    </tbody>
                                </table>
<?php include 'footer.php'; ?>