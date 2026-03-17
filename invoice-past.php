<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Invoice - Archive';
$pgID = 77;
$err =''; 

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE budget SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'budget.php';</script>";
	exit();
}

if(isset($_POST['submit'])){
	
	$cirSql = "UPDATE adAccounts SET status='0', updated=now() where uid='".$_SESSION['uid']."'";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	
	if(isset($_POST['ads_id']) && count($_POST['ads_id'])>0) {
		//echo (count($_POST['ads_id'])); 
		$ids = implode(",", $_POST['ads_id']);
		$cirSql2 = "UPDATE adAccounts SET status='1', updated=now() where tbl_id in (".$ids.") && uid='".$_SESSION['uid']."'";
		mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
	} 
	//echo count($_POST['ads_id']); exit;
	//print_r($_POST['ads_id']);
	//exit;
	
	
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'ad-accounts.php';</script>";
	exit();
}
include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 25; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " invoice WHERE uid='".$_SESSION['uid']."'";

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
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                     
                    </ul>
                  
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  			<?php 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table class="table table-hover table-striped">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Client Name</th>
                                    	<!--<th>FB Account</th>-->
                                        <th>Invoice No's</th>
                                    	<!--<th>Adwords Account</th> --> 
                                        <th>Invoice for Month</th>
                                        <th>Invoices</th>   
                                        <th>Report Files</th>
                                      </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											$c = array();
											if($sqlROW["inv_files"]!='') {
												$inF = explode(",",$sqlROW["inv_files"]);
												$inN = explode(",",$sqlROW["inv_no"]);
												$x=0;
												foreach($inF as $val) { 
													$c[] = '<a href="loading.php?pg=http://bytsocial.com/fb-ads/demo/invoices/'.$val.'.pdf" target="_blank" class="blue">BYT/SW/'.$inN[$x].'</a>';  
													$x++;
												}
											}
											
											$r = array();
											if($sqlROW["report_files"]!='') {
												$inR = explode(",",$sqlROW["report_files"]);
												$y=1;
												foreach($inR as $val2) { 
													if (strpos($val2, 'Facebook') !== false) { $typ = 'Facebook'; $fil= ''; } else { $typ = 'Google'; $fil= '~'; }
													$r[] = '<a href="loading.php?pg=http://bytsocial.com/fb-ads/demo/download/'.$val2.''.$fil.'.csv" target="_blank" class="blue">'.$typ.'</a>';  
													$y++;
												}
											}
											
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["client_name"]; ?></td>
                                        	<td><?php echo $sqlROW["inv_no"]; ?></td>
                                            <td><?php echo $sqlROW["inv_mon"]; ?></td>
                                        	<td><?php if(count($c)>0) { echo implode(', ',$c); } ?></td>  
                                            <td><?php if(count($c)>0) { echo implode(', ',$r); } ?></td>                               	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>