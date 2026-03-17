<?php include 'header.php'; 

echo 1; 
//print_r($_SESSION); exit;
Auth();
$pgHeadline = 'Facebook - Ad Account Reports';
$pgID = 2;
$err =''; 
if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE adAccounts SET status='0' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'report.php';</script>";
	exit();
}
echo 2; 
if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  'report.php';</script>";
	exit();
}
echo __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
echo 3;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Exception\RequestException;

include 'config.php';
echo 4;
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
$statement = " adAccounts where uid='".$_SESSION['uid']."' && status=1";

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
echo 6;
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
                    <form method="post" action="report.php">
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
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th>Account Name</th>
                                    	<th>Account ID</th>
                                    	<th>Currency</th>
                                    	<th>Spend</th>
                                    	<th>Reach</th>
                                        <th>Impressions</th>
                                        <th>Remove</th>
                                        <!--<th>Invoice</th>-->
                                    </thead>
                                    <tbody>
                                    	<?php
										
										//$val = get_data('https://graph.facebook.com/v11.0/23843162173490372/insights?level=campaign&ids=[23843162173490372,23843162173460372]&fields=account_id,spend,reach,impressions,account_currency&access_token=REDACTED_FB_TOKEN');
										//d();
										//
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();
											//https://graph.facebook.com/v11.0/23843162173490372/insights?level=campaign&ids=[23843162173490372,23843162173460372]&fields=account_id,spend,reach,impressions,account_currency&access_token=REDACTED_FB_TOKEN
											//echo 'https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["id"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).''; exit;
											$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["id"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
											//$val = (new AdAccount("act_50358248"));
											//$val = $val->getInsights($fields, $params)->getResponse()->getContent();
											//print_r($val); exit;
											
										?>
                                        <tr>
                                        	<td><?php echo $i; ?></td>
                                        	<td><a href="loading.php?pg=campaigns.php?id=<?php echo $sqlROW["account_id"]; ?>" target="_blank" class="blue"><?php echo $sqlROW["name"]; ?></a></td>
                                        	<td><?php echo $sqlROW["account_id"]; ?></td>
                                        	<td><?php echo $sqlROW["currency"]; ?></td>
                                        	<td><span class="fa fa-rupee"></span> <?php if(isset($val['data'][0]['spend'])) { echo moneyFormatIndia(round($val['data'][0]['spend'])); } else { echo 0; } ?></td>
                                        	<td><?php if(isset($val['data'][0]['reach'])) { echo moneyFormatIndia(round($val['data'][0]['reach'])); } else { echo 0; }  ?></td>
                                            <td><?php if(isset($val['data'][0]['impressions'])) { echo moneyFormatIndia(round($val['data'][0]['impressions'])); } else { echo 0; }  ?></td>
                                            <!--<td><a href="loading.php?pg=invoice.php?id=<?php echo $sqlROW["account_id"]; ?>" target="_blank">View / Download</a></td>-->
                                            <td>
                                            <a href="loading.php?pg=report.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to remove this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> Remove </a>
                                    		</td> 
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div> 

         			<?php	}	?>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>