<?php include 'header.php'; 


Auth();
$pgHeadline = 'Campaign Reports';
$pgID = 2;
$err =''; 

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  'ad.php?id=".$_GET['id']."&camp=".$_GET['camp']."';</script>";
	exit();
}	

require __DIR__ . '/vendor/autoload.php';

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Values\InsightsPresets;


use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdFields;

use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdSetFields;

use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Object\AdsInsights;

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

$per_page = 25; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts where fb_id='".$_SESSION['uid']."' && status=1";

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
	$getID = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM adAccounts WHERE account_id = '".$_GET['id']."'"));
	$cName = $getID['name'];
	$pgHeadline = $cName.' - Reports';
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
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
                    <h2>Ad - <?php echo $_GET['camp']; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      
                    </ul>
                    <form method="post" action="ad.php?id=<?php echo $_GET['id']; ?>&camp=<?php echo $_GET['camp']; ?>">
                    <ul class="nav navbar-right panel_toolbox">
                      <li>Filter : &nbsp;
                      </li>
                      <li>
                      		     <div id="reportrange_right" class="pull-right1" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                                      <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                                      <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                                 </div>
                                 <input type="hidden" id="stDt" name="start" value="<?php echo $_SESSION['stDt']; ?>">
								<input type="hidden" id="enDt" name="end" value="<?php echo $_SESSION['enDt']; ?>">
                      </li>
                      <li><input type="submit" name="dt_submit" value="Submit" class="btn btn-primary"></li>
                    </ul>
                    </form>
                    
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th>Ad Name</th>
                                    	<th>Ad ID</th>
                                    	<th>Currency</th>
                                    	<th>Spend</th>
                                    	<th>Reach</th>
                                        <th>Impressions</th>
                                    </thead>
                                    <tbody>
                                    	<?php
										$val['data'] = array();
										//echo 'https://graph.facebook.com/'.$api_ver.'/'.$_GET['id'].'/insights?level=campaign&fields=account_id,spend,reach,impressions,account_currency&ids=['.$campIds.']&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'';
										//$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$_GET['id'].'/insights?level=campaign&ids=['.$campIds.']&fields=account_id,spend,reach,impressions,account_currency&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
										if(isset($_GET['pgId']) && isset($_GET['pgType'])) { $after='&'.$_GET['pgType'].'='.$_GET['pgId']; } else { $after=''; }
										//echo 'https://graph.facebook.com/'.$api_ver.'/act_'.$_GET['id'].'/insights?level=campaign&fields=campaign_name,campaign_id,spend,reach,impressions,account_currency&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).''.$after;
										$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$_GET['id'].'/insights?level=ad&fields=account_name,ad_name,ad_id,spend,reach,impressions,account_currency&&filtering=[{"field":"ad.impressions","operator":"GREATER_THAN","value":0}]&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).''.$after);
										//d($val);	
										//echo count($val['data']); 
										if(count($val['data'])==0) 
										{ 
											echo '<tr><td colspan="7">No records found!</td> </tr> '; 
										} else {
										
											foreach ($val['data'] as $cS) 
											{ 
												//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();
												//$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$cS->id.'/insights?level=campaign&fields=spend,reach,impressions,account_currency&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).''); exit;
												//$val = (new AdAccount("act_50358248"));
												//$val = $val->getInsights($fields, $params)->getResponse()->getContent();
												//print_r($val);
												
											?>
											<tr>
												<td><?php echo $i; ?></td>
												<td><?php echo $cS['ad_name']; ?></td>
												<td><?php echo $cS['ad_id']; ?></td>
												<td><?php if(isset($cS['account_currency'])) { echo $cS['account_currency']; } else { echo "-"; } ?></td>
												<td><?php if(isset($cS['spend'])) { echo $cS['spend']; } else { echo 0; } ?></td>
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
															<?php if(isset($val['paging']['previous'])) { ?><li><a href="loading.php?pg=?id=<?php echo $_GET['id']; ?>&pgType=before&pgId=<?php echo $val['paging']['cursors']['before']; ?>&page=<? echo $page-1; ?>"><< Prev</a></li><?php } ?>
															<?php if(isset($val['paging']['next'])) { ?><li><a href="loading.php?pg=?id=<?php echo $_GET['id']; ?>&pgType=after&pgId=<?php echo $val['paging']['cursors']['after']; ?>&page=<? echo $page+1; ?>">Next >></a></li><?php } ?>
														</ul>
													</div>
												 </td>
											</tr> 
											<?php 
											}   
									}	?>                              
                                    </tbody>
                                </table>
                               
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>		
        		  </div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>