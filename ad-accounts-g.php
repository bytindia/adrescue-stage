<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Google - Ad Account List';
$pgID = 5;
$err =''; 

if(isset($_POST['submit'])){
	
	$cirSql = "UPDATE gaccounts SET status='0', updated=now() where uid='".$_SESSION['uid']."'";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	
	if(isset($_POST['ads_id']) && count($_POST['ads_id'])>0) {
		//echo (count($_POST['ads_id'])); 
		$ids = implode(",", $_POST['ads_id']);
		$cirSql2 = "UPDATE gaccounts SET status='1', updated=now() where tbl_id in (".$ids.") && uid='".$_SESSION['uid']."'";
		mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
	} 
	//echo count($_POST['ads_id']); exit;
	//print_r($_POST['ads_id']);
	//exit;
	
	
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'ad-accounts-g.php';</script>";
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

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
//$statement = " gaccounts WHERE g_id='1'";
$statement = " gaccounts WHERE uid='".$_SESSION['uid']."'";

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
$ac_status = array(
	2 => 'ACTIVE',
	3 => 'IN-ACTIVE'
);
?>
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
                    <?php if(isset($_SESSION['g_id']) || $_SESSION['g_id']!='') 	{ ?>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> Ad Account Missing? </li>
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=adAccounts-g.php"  class="btn btn-primary btn-sm">Sync New Accounts</a>                
                      	</div>  
                      </li>
                      
                    </ul>
                     <?php } ?>
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
										//echo "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}"; 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by name asc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>Report</th>
                                        <th>SNo</th>
                                        <th>Name</th>
                                    	<th>Account ID</th>
                                    	<th>Currency</th>  
                                        <th>Active Status</th>                               	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();
											//$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["id"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
											//$val = (new AdAccount("act_50358248"));
											//$val = $val->getInsights($fields, $params)->getResponse()->getContent();
											//print_r($val);
											if(ctype_digit($sqlROW["account_id"]) && strlen($sqlROW["account_id"]) == 10) {
												  $sqlROW["account_id"] = substr($sqlROW["account_id"], 0, 3) .'-'.
															substr($sqlROW["account_id"], 3, 3) .'-'.
															substr($sqlROW["account_id"], 6);
												}
											
										?>
                                        <tr>
                                        	<td><input type="checkbox" name="ads_id[]" value="<?php echo $sqlROW["tbl_id"]; ?>" <?php if($sqlROW["status"]==1) { echo "checked='checked'"; }; ?></td>
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["name"]; ?></td>
                                        	<td><?php echo str_replace("-","",$sqlROW["account_id"]); ?></td>
                                        	<td><?php echo $sqlROW["currency"]; ?></td>  
                                           <td><?php if(isset($ac_status[$sqlROW["account_status"]])) { echo $ac_status[$sqlROW["account_status"]]; } else { echo 'IN-ACTIVE'; } ?></td>                                      	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                <br><br>
                                <input type="submit" value="Acivate" name="submit" class="btn btn-info btn-sm">
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

						<?php	}	?>	  
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>