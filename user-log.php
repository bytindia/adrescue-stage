<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Users Management';
$pgID = 8;
$err =''; 

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE users SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'users.php';</script>";
	exit();
}


include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " users WHERE delete_status=0";

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
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=add-user.php"  class="btn btn-success btn-sm">Add Users</a>                        
                      </div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php //echo "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}"; 
                        //$bytUser = array('admin', 'shaheena','mughil','charan','simin');
										$sqlRev=mysqli_query($conn, "SELECT * FROM user_logs  ORDER BY timestamp DESC limit 0,1000");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                    <th>Username</th>
                                    <th>Action</th>
                                    <th>Page URL</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>                          	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                              
                                            <td><?php echo $all_user[$sqlROW['user_id']]; ?></td>
                                            <td><?php echo $sqlROW['action']; ?></td>
                                            <td><?php echo $sqlROW['page_url']; ?></td>
                                            <td><?php echo $sqlROW['ip_address']; ?></td>
                                            <td><?php echo $sqlROW['timestamp']; ?></td>                               	
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