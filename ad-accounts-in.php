<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'LinkedIn - Ad Account List';
$pgID = 3;
$err =''; 

if(isset($_POST['submit'])){
	
	$cirSql = "UPDATE adAccounts_in SET status='0', updated=now() where uid='".$_SESSION['uid']."'";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	
	if(isset($_POST['ads_id']) && count($_POST['ads_id'])>0) {
		//echo (count($_POST['ads_id'])); 
		$ids = implode(",", $_POST['ads_id']);
		$cirSql2 = "UPDATE adAccounts_in SET status='1', updated=now() where tbl_id in (".$ids.") && uid='".$_SESSION['uid']."'";
		mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
	} 
	//echo count($_POST['ads_id']); exit;
	//print_r($_POST['ads_id']);
	//exit;
	
	
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'ad-accounts-in.php';</script>";
	exit();
}


include 'config.php';

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts_in WHERE uid='".$_SESSION['uid']."'";

//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();

//d($_SESSION);
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
                    <?php if(isset($_SESSION['in_id']) || $_SESSION['in_id']!='') 	{ ?>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> Ad Account Missing? </li>
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=adAccounts-in.php"  class="btn btn-primary btn-sm">Sync New Accounts</a>                
                      	</div>  
                      </li>
                      
                    </ul>
                     <?php } ?>
                   
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
                                      <img src="images/connect-linkedin.png"  height="60">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} 
						else {
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
										?>
                                        <tr>
                                        	<td><input type="checkbox" name="ads_id[]" value="<?php echo $sqlROW["tbl_id"]; ?>" <?php if($sqlROW["status"]==1) { echo "checked='checked'"; }; ?></td>
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["name"]; ?></td>
                                        	<td><?php echo $sqlROW["account_id"]; ?></td>
                                        	<td><?php echo $sqlROW["currency"]; ?></td>  
                                            <td><?php echo $sqlROW["account_status"]; ?></td>                                                                           	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                <br><br>
                                <input type="submit" value="Acivate" name="submit" class="btn btn-primary btn-sm">
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

					<?php	}	?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>