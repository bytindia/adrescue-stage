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
	echo "<script>window.location =  'creative.php?id=".$_GET['id']."';</script>";
	exit();
}	
include 'config.php';

require_once 'vendor-linkedin/autoload.php';
$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";
$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
  $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
}

include 'pagination.php';
$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;
$per_page = 25; // Set how many records do you want to display per page.
$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts_in where fb_id='".$_SESSION['uid']."' && status=1";

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
function moneyFormatIndia($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}

$getID = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM adAccounts_in WHERE account_id = '".$_GET['id']."'"));
$cName = $getID['name'];
$pgHeadline = $cName.' - Reports';


$stDt = "dateRange.start.day=".date('d',strtotime($_SESSION['stDt']))."&dateRange.start.month=".date('m',strtotime($_SESSION['stDt']))."&dateRange.start.year=".date('Y',strtotime($_SESSION['stDt']))."&";
$enDt = "dateRange.end.day=".date('d',strtotime($_SESSION['enDt']))."&dateRange.end.month=".date('m',strtotime($_SESSION['enDt']))."&dateRange.end.year=".date('Y',strtotime($_SESSION['enDt']));

//$valAcc = $linkedIn->get('v2/adCampaignsV2?q=search&search.account.values[0]=urn:li:sponsoredAccount:'.$_GET['id'].'&search.campaignGroup.values[0]=urn:li:sponsoredCampaignGroup:602208225&sort.field=ID&fields=id,name,campaignGroup');


//$val = $linkedIn->get('v2/adAnalyticsV2?campaignGroups[0]=urn:li:sponsoredCampaignGroup:602208225&q=statistics&pivots[0]=CAMPAIGN&timeGranularity=ALL&'.$stDt.''.$enDt);
//CampaignGroup[0]=urn:li:sponsoredCampaignGroup:602208225

//d($valAcc); 

//d($val); 
//exit;


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
                    <h2><?php echo $cName; ?> - Ads</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      
                    </ul>
                    <form method="post" action="creative.php?id=<?php echo $_GET['id']; ?>">
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
                                        <th>Campaign Name</th>
                                    	<th>Spend</th>                                    	
                                        <th>Leads</th>
                                        <th>Impr.</th>
                                        <th>Engage.</th>
                                        <th>Conv.</th>
                                        <th>Clicks</th>
                                        <th>Likes</th>
                                        <th>Comments</th>
                                    </thead>
                                    <tbody>
                                    	<?php
										$cg_arr = array();		
										$val['elements'] = array();
										
										//$valAcc = $linkedIn->get('v2/adCampaignsV2?q=search&search.account.values[0]=urn:li:sponsoredAccount:'.$_GET['id'].'&sort.field=ID&fields=id,name');
										$valAcc = $linkedIn->get('v2/adCreativesV2?q=search&search.account.values[0]=urn:li:sponsoredAccount:'.$_GET['id'].'&search.campaign.values[0]=urn:li:sponsoredCampaign:'.$_GET['cgid']);
										//d($valAcc); exit;
										foreach($valAcc['elements'] as $key => $v1) 
										{
											//$cg_arr[$v1['id']] = $v1['name'];
										}
										
										$val = $linkedIn->get('v2/adAnalyticsV2?campaigns[0]=urn:li:sponsoredCampaign:'.$_GET['cgid'].'&q=statistics&pivots[0]=CREATIVE&timeGranularity=ALL&fields=oneClickLeads,externalWebsiteConversions,likes,clicks,shares,totalEngagements,actionClicks,impressions,comments,dateRange,costInLocalCurrency,costInUsd,pivotValue&'.$stDt.''.$enDt);

/*
d($valAcc); 

d($val); 
exit;
*/
										//$val['data'] = array();
										//echo 'https://graph.facebook.com/'.$api_ver.'/'.$_GET['id'].'/insights?level=campaign&fields=account_id,spend,reach,impressions,account_currency&ids=['.$campIds.']&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'';
										//$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$_GET['id'].'/insights?level=campaign&ids=['.$campIds.']&fields=account_id,spend,reach,impressions,account_currency&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
										//if(isset($_GET['pgId']) && isset($_GET['pgType'])) { $after='&'.$_GET['pgType'].'='.$_GET['pgId']; } else { $after=''; }
										//echo 'https://graph.facebook.com/'.$api_ver.'/act_'.$_GET['id'].'/insights?level=campaign&fields=campaign_name,campaign_id,spend,reach,impressions,account_currency&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).''.$after;
										//$val = get_data('https://graph.facebook.com/'.$api_ver.'/act_'.$_GET['id'].'/insights?level=campaign&fields=account_name,campaign_name,campaign_id,spend,reach,impressions,account_currency&&filtering=[{"field":"campaign.impressions","operator":"GREATER_THAN","value":0}]&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).''.$after);
										//d($val);	
										//echo count($val['data']); 
										if(count($val['elements'])>0) 
										{
											foreach($val['elements'] as $key => $rep) 
											{ 
												$cgId = str_replace("urn:li:sponsoredCreative:", "", $rep['pivotValue']);
											?>
											<tr>
												<td><?php echo $i; ?></td>
												<td>Ad - <?php echo  $cgId; ?></td>
												<td><span class="fa fa-rupee"></span> <?php if(isset($rep['costInLocalCurrency'])) { echo moneyFormatIndia(round($rep['costInLocalCurrency'])); } else { echo 0; } ?></td>
												<td><?php if(isset($rep['oneClickLeads'])) { echo moneyFormatIndia(round($rep['oneClickLeads'])); } else { echo 0; }  ?></td>                       	
												<td><?php if(isset($rep['impressions'])) { echo moneyFormatIndia(round($rep['impressions'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['totalEngagements'])) { echo moneyFormatIndia(round($rep['totalEngagements'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['externalWebsiteConversions'])) { echo moneyFormatIndia(round($rep['externalWebsiteConversions'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['clicks'])) { echo moneyFormatIndia(round($rep['clicks'])); } else { echo 0; }  ?></td>                 
												<td><?php if(isset($rep['likes'])) { echo moneyFormatIndia(round($rep['likes'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['comments'])) { echo moneyFormatIndia(round($rep['comments'])); } else { echo 0; }  ?></td>
											</tr>  
											<?php $i++;
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