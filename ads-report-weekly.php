<?php date_default_timezone_set("Asia/Kolkata");  
include 'header.php'; 
$datePick = 1;
$datePickURL = '';
if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location = 'ads-report-weekly.php';</script>";
	exit();
}
if(isset($_GET['st'])){
	$start = $_GET['st'];
	$end =  $_GET['en'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
  $datePickURL = 'st='.$_SESSION['stDt'].'&en='.$_SESSION['enDt'].'&';
	//echo "<script>window.location = 'ads-report-weekly.php';</script>";
	//exit();
}
if(isset($_GET['reset'])){
  $start = date('m/d/Y',strtotime('today'));
	$end =  date('m/d/Y',strtotime('today'));
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
}
Auth();

//print_r($_POST);

$pgHeadline = 'Ads - Email Report';
$pgID = 8;
$err =''; 
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
if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE ads_report_weekly SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'ads-report-weekly.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " ads_report_weekly WHERE uid='".$_SESSION['uid']."' AND delete_status=0";


?>
<style>
  .fa {
    font-weight: bold !important;
    font-size: 14px !important;
}
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

         <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Modal title</h4>
                    </div>
                    <div class="modal-body">
                    <!--<h4>Text in a modal</h4>
                    <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.</p>
                    <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>-->
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
          </div>
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=ads-report-weekly-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                
                      	</div>  
                      </li>
                      
                    </ul>
                    <form method="post" action="ads-report-weekly.php">
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
                      <?php //if(isset($_GET['st'])){ ?>
                        <li><a class="btn btn-default" href="ads-report-weekly.php?reset">Reset</a></li>
                      <?php //} ?>
                    </ul>
                    </form>
                    <div class="clearfix"></div>
                  </div>
                 
                  
                  <div class="x_content">
                  
                  		<?php 
                      echo date('m/d/Y',strtotime('today')); 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="insights-daily-report.php">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>      
                                        <th>Select</th>                                  
                                        <th>SNo</th>
                                        <th>Client Name</th>                                    	
                                        <th>FB Account ID</th>
                                        <th>Google Account ID</th>   
                                        <th width="25%">Report</th>
                                        <th width="16%">Edit</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                        <td><input type="checkbox" name="ids[]" value="<?php echo $sqlROW["tbl_id"]; ?>"></td>
                                        <td><?php echo $i; ?></td>
                                        	<td><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 1, '<?php echo $sqlROW["client_name"]; ?>');" data-id="1" data-toggle="modal" data-target=".bs-example-modal-lg" class="btn btn-success btn-sm"> <i class="fa fa-whatsapp"></i> </a>  <?php echo $sqlROW["client_name"]; ?></td>
                                        	<td><?php echo $sqlROW["fb_acc"]; ?></td>                                            
                                            <td><?php echo $sqlROW["g_acc"]; ?></td>  
                                            <td>
                                           <!-- <a href="loading.php?pg=insights-ads-weekly.php?id=<?php echo $sqlROW["tbl_id"]; ?>" target="_blank" class="btn btn-success btn-sm"> Weekly <i class="fa fa-paper-plane"></i> </a>
                                            <a href="loading.php?pg=insights-ads-monthly.php?id=<?php echo $sqlROW["tbl_id"]; ?>" target="_blank" class="btn btn-warning btn-sm"> Monthly <i class="fa fa-paper-plane"></i> </a>-->
                                            <a href="loading.php?pg=insights-ads-custom.php?id=<?php echo $sqlROW["tbl_id"]; ?>" target="_blank" class="btn btn-warning btn-sm"> Email  <i class="fa fa-paper-plane"></i> </a> 
                                            <a href="loading.php?pg=insights-ads-custom.php?id=<?php echo $sqlROW["tbl_id"]; ?>&download=1" target="_blank" class="btn btn-success btn-sm"> Download  <i class="fa fa-download"></i> </a>

                                            
                                            </td>
                                            <td>
                                            <a href="loading.php?pg=ads-report-weekly-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Edit </a>
                                    <a href="loading.php?pg=ads-report-weekly.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> Delete </a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                <br><br>
                                <input type="submit" name="whatsapp" value="Whatsapp" class="btn-lg btn-success">
                                <input type="submit" name="download" value="Download" class="btn-lg btn-info">
                                <input type="submit" name="troubleshoot" value="Email Report" class="btn-lg btn-warning">
                                </form>
                                
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <script>
function onAjax(id,ty,clName) {
					//var id = $(this).attr('data-id');
					//alert(id);
          $('#myModalLabel').html(clName);
					$('.modal-body').html('Please wait... fetching report and it will take few seconds!');
					$.ajax({
						type: 'POST',
						url: 'insights-daily-whatsapp.php?<?php echo $datePickURL; ?>id='+id,
						data:{id: id, ty: ty},
						success: function(data) {
							//alert(data);
						  
						  $('.modal-body').html(data);
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
					
};
</script>
<?php include 'footer.php'; ?>

