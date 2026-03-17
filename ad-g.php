<?php include 'header.php'; 



Auth();
$pgHeadline = 'Campaign Reports';
$pgID = 22;
$err =''; 

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  'ad-g.php?id=".$_GET['id']."&cid=".$_GET['cid']."&camp=".$_GET['camp']."';</script>";
	exit();
}	

	$getID = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM gaccounts WHERE account_id = '".$_GET['id']."'"));
	$cName = $getID['name'];
	$pgHeadline = $cName.' - Reports';
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
                    <h2>Ad - <?php echo $_GET['camp']; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      
                    </ul>
                    <form method="post" action="ad-g.php?id=<?php echo $_GET['id']; ?>&cid=<?php echo $_GET['cid']; ?>&camp=<?php echo $_GET['camp']; ?>">
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
										$sqlRev=mysqli_query($conn, "SELECT name,currency FROM gaccounts WHERE account_id = '".$_GET['id']."'");
										$i = 1;
										
										$adgroupId = $_GET['cid'];
										$accIds[] = $_GET['id'];
										$fromDt = date('Ymd', strtotime($_SESSION['stDt']));
										$enDt = date('Ymd', strtotime($_SESSION['enDt']));
										include 'download-ad.php';
										
										
										$rows = file('download/ad/adgroup_'.$_GET['id'].'.csv');
										//$last_row = array_pop($rows);
										//$data = str_getcsv($last_row);
										//print_r($data);
										foreach ($rows as $key => $value)
										{
											$csv[$key] = str_getcsv($value);
										}
										array_shift($csv);
										array_shift($csv);
										/*echo '<pre>';
										print_r($csv);
										echo '</pre>';
										echo count($csv);*/
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th>Ad Name</th>
                                    	<th>Ad ID</th>
                                    	<th>Currency</th>
                                    	<th>Spend</th>
                                    	<th>Clicks</th>
                                        <th>Impressions</th>
                                    </thead>
                                    <tbody>
                                    <?php foreach($csv as $csvData) { ?>
                                        <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $csvData[1]; ?></td>
                                                    <td><?php echo $csvData[0]; ?></td>
                                                    <td><?php echo 'INR'; ?></td>
                                                    <td><?php echo round($csvData[5]/1000000); ?></td>
                                                    <td><?php echo $csvData[4]; ?></td>
                                                    <td><?php echo $csvData[3]; ?></td>
                                        </tr>  
                                     <?php $i++; 
									 } ?>                         
                                    </tbody>
                                </table>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>