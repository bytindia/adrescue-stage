<?php include 'header.php'; 

Auth();
$pgHeadline = 'Google - Ad Account Reports';
$pgID = 22;
$err =''; 

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE gaccounts SET status='0' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'report-g.php';</script>";
	exit();
}

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  'report-g.php';</script>";
	exit();
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

$per_page = 10; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " gaccounts where uid='".$_SESSION['uid']."' && status=1";

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
//echo $request_url = "https://graph.facebook.com/v11.0/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";exit;
//echo $access_token; 
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
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>                      
                    </ul>
                     <form method="post" action="report-g.php">
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
						if(!isset($_SESSION['g_id']) || $_SESSION['g_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="loading.php?pg=login-g.php">
                                      <img src="images/g-login.png">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} 
						else {
								if(!isset($_SESSION['g_mcc']) || $_SESSION['g_mcc']=='') 
								{
									echo "<script>window.location = 'choose-mcc.php';</script>";
									exit();
								}
								$sqlRev=mysqli_query($conn, "SELECT tbl_id, name, account_id, currency FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
								$i = (($page-1) * $per_page ) + 1;
								$getRows = $accIds = array();
								while($sqlROW=mysqli_fetch_array($sqlRev))
								{
										 $getRows[] = $sqlROW;
										 $accIds[] = $sqlROW['account_id'];
								}
								if(count($accIds)>0) {			
										$g_mcc = $_SESSION['g_mcc'];
										$g_refresh_token = $_SESSION['g_refresh_token'];

										$fromDt = date('Ymd', strtotime($_SESSION['stDt']));
										$enDt = date('Ymd', strtotime($_SESSION['enDt']));
										
										include 'download-report.php';
										//exit;
										//ParallelReportDownload::main($accIds);
										//print_r($accIds);
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th>Account Name</th>
                                        <!--<th>Keywords</th> 
                                    	<th>Account ID</th>
                                    	<th>Currency</th>-->
                                    	<th>Spend</th>
                                    	<th>Clicks</th>
                                        <th>Impressions</th> 
                                        <th>AvgCost</th>
                                    	<th>AvgCPM</th>
                                        <th>CPL</th>  
                                        <th>CTR</th>                                           
                                        <th>Actions</th>                                     
                                    </thead>
                                    <tbody>
                                    	<?php
										
										//$val = get_data('https://graph.facebook.com/v11.0/23843162173490372/insights?level=campaign&ids=[23843162173490372,23843162173460372]&fields=account_id,spend,reach,impressions,account_currency&access_token=REDACTED_FB_TOKEN');
										//d();
										//
										foreach($getRows as $gR)
										{ 
											//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();
											//https://graph.facebook.com/v11.0/23843162173490372/insights?level=campaign&ids=[23843162173490372,23843162173460372]&fields=account_id,spend,reach,impressions,account_currency&access_token=REDACTED_FB_TOKEN
											//$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["id"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
											//$val = (new AdAccount("act_50358248"));
											//$val = $val->getInsights($fields, $params)->getResponse()->getContent();
											//print_r($val);
											
											$rows = file('download/account_'.$gR["account_id"].'.csv');
											$last_row = array_pop($rows);
											$data = str_getcsv($last_row);
											
										?>
                                        <tr>
                                        	<td><?php echo $i; ?></td>
                                        	<td><a href="loading.php?pg=campaigns-g.php?id=<?php echo $gR["account_id"]; ?>" target="_blank"  class="blue"><?php echo $gR["name"]; ?></a></td>
                                            <!--<td><a href="loading.php?pg=keywords.php?id=<?php echo $gR["account_id"]; ?>" target="_blank"  class="blue">Keywords</a></td> 
                                        	<td><?php echo $gR["account_id"]; ?></td
                                        	<td><?php echo $gR["currency"]; ?></td>>-->
                                        	<td><span class="fa fa-rupee"></span>  <?php echo moneyFormatIndia(round($data[5]/1000000)); ?></td>
                                        	<td><?php echo moneyFormatIndia($data[4]);  ?></td>
                                            <td><?php echo moneyFormatIndia($data[3]);  ?></td>    
                                            <td><?php echo moneyFormatIndia(round($data[6]/1000000)); ?></td>
                                            <td><?php echo moneyFormatIndia(round($data[7]/1000000)); ?></td>
                                            <td><?php echo moneyFormatIndia(round($data[8]/1000000)); ?></td>  
                                            <td><?php echo $data[9]; ?></td>                                              
                                            <td>
                                           
                                            <div class="btn-group">
                          					<a data-toggle="dropdown" class="btn btn-primary btn-sm  dropdown-toggle" aria-expanded="false"> Action <i class="fa fa-sort-desc"></i></a>
                                            <ul role="menu" class="dropdown-menu pull-right">                                                 
                                                  <li><a href="loading.php?pg=keywords.php?id=<?php echo $gR["account_id"]; ?>" target="_blank"  class="blue">Keywords</a>
                                                  </li>
                                                  <li><a href="loading.php?pg=keywords-display.php?id=<?php echo $gR["account_id"]; ?>" target="_blank"  class="blue">Display Keywords</a>
                                                  </li>
                                                  <li class="divider"></li>
                                                  <li> <a href="loading.php?pg=report-g.php?del=<?php echo $gR["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to remove this?');"  > Remove </a>
                                                  </li>
                                                </ul>
                                            </div>
                                    		</td>                                  
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

				<?php	}	
						}?>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>