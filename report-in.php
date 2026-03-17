<?php include 'header.php'; 


//print_r($_SESSION); exit;
Auth();
$pgHeadline = 'LinkedIn - Ad Account Reports';
$pgID = 2;
$err =''; 
if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE adAccounts_in SET status='0' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'report-in.php';</script>";
	exit();
}

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  'report-in.php';</script>";
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

$per_page = 10; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts_in where uid='".$_SESSION['uid']."' && status=1";

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

$stDt = "dateRange.start.day=".date('d',strtotime($_SESSION['stDt']))."&dateRange.start.month=".date('m',strtotime($_SESSION['stDt']))."&dateRange.start.year=".date('Y',strtotime($_SESSION['stDt']))."&";
$enDt = "dateRange.end.day=".date('d',strtotime($_SESSION['enDt']))."&dateRange.end.month=".date('m',strtotime($_SESSION['enDt']))."&dateRange.end.year=".date('Y',strtotime($_SESSION['enDt']));

//$val = $linkedIn->get('v2/adAnalyticsV2?accounts[0]=urn:li:sponsoredAccount:504525377&accounts[1]=urn:li:sponsoredAccount:504389587&q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=oneClickLeads,externalWebsiteConversions,likes,clicks,shares,totalEngagements,actionClicks,impressions,comments,dateRange,costInLocalCurrency,costInUsd&'.$stDt.''.$enDt);
//d($val); exit;
											
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
                    <form method="post" action="report-in.php">
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
						if(!isset($_SESSION['in_id']) || $_SESSION['in_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="loading.php?pg=linkedin-login.php">
                                      <img src="images/connect-linkedin.png" height="60">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} 
						else {
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
										$z = 0; 
										
										$val['elements'] = array();
										$accVal = array();
										if(mysqli_num_rows($sqlRev)) 
										{
											while($sqlROW=mysqli_fetch_array($sqlRev))
											{
												$accVal['id'][$sqlROW["account_id"]] = $sqlROW["account_id"];
												$accVal['name'][$sqlROW["account_id"]] = $sqlROW["name"];
												$accVal['tbl_id'][$sqlROW["account_id"]] = $sqlROW["tbl_id"];
												$accQry.='accounts['.$z.']=urn:li:sponsoredAccount:'.$sqlROW["id"].'&';
												$z++;
											}
											$val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=oneClickLeads,externalWebsiteConversions,likes,clicks,shares,totalEngagements,actionClicks,impressions,comments,dateRange,costInLocalCurrency,costInUsd,pivotValue&'.$stDt.''.$enDt);
										}
										//d($val); exit;
										
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th>Account Name</th>                                    	
                                    	<th>Spend</th>                                    	
                                        <th>Leads</th>
                                        <th>Impr.</th>
                                        <th>Engage.</th>
                                        <th>Conv.</th>
                                        <th>Clicks</th>
                                        <th>Likes</th>
                                        <th>Comments</th>
                                        <th>Remove</th>
                                        <!--<th>Invoice</th>-->
                                    </thead>
                                    <tbody>
                                    	<?php
										if(count($val['elements'])>0) 
										{
											foreach($val['elements'] as $key => $rep) 
											{ 	
												$cgId = str_replace("urn:li:sponsoredAccount:", "", $rep['pivotValue']);
											?>
											<tr>
												<td><?php echo $i; ?></td>
												<td><a href="loading.php?pg=campaigns-in.php?id=<?php echo $accVal['id'][$cgId]; ?>" target="_blank" class="blue"><?php echo $accVal['name'][$cgId]; ?></a></td>
												
												<td><span class="fa fa-rupee"></span> <?php if(isset($rep['costInLocalCurrency'])) { echo moneyFormatIndia(round($rep['costInLocalCurrency'])); } else { echo 0; } ?></td>
												<td><?php if(isset($rep['oneClickLeads'])) { echo moneyFormatIndia(round($rep['oneClickLeads'])); } else { echo 0; }  ?></td>                       	
												<td><?php if(isset($rep['impressions'])) { echo moneyFormatIndia(round($rep['impressions'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['totalEngagements'])) { echo moneyFormatIndia(round($rep['totalEngagements'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['externalWebsiteConversions'])) { echo moneyFormatIndia(round($rep['externalWebsiteConversions'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['clicks'])) { echo moneyFormatIndia(round($rep['clicks'])); } else { echo 0; }  ?></td>                 
												<td><?php if(isset($rep['likes'])) { echo moneyFormatIndia(round($rep['likes'])); } else { echo 0; }  ?></td>
												<td><?php if(isset($rep['comments'])) { echo moneyFormatIndia(round($rep['comments'])); } else { echo 0; }  ?></td>
											   
												<td>
												<a href="loading.php?pg=report-in.php?del=<?php echo $accVal['tbl_id'][$cgId]; ?>" onclick="return confirm('Are you sure you want to remove this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> Remove </a>
												</td> 
											</tr>  
											<?php $i++;
											}
										}?>                                      
                                    </tbody>
                                </table>
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div> 

         			<?php	}	?>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>