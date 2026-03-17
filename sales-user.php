<?php 
$pgHeadline = 'SalesNinja - Sales Team';
include 'header.php'; 


?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>
<style>
.table > tbody > tr > td {
     vertical-align: middle;
}
</style>
<?php
$extQry = $extQry2=   "";
if(isset($_GET['sort']) && $_GET['sort']==1) { 
	$act=1; 
	$extQry =  "created >= DATE(NOW()) - INTERVAL 0 DAY AND";
	$extQry2 =  "where assigned >= DATE(NOW()) - INTERVAL 0 DAY ";
} else if(isset($_GET['sort']) && $_GET['sort']==2) {
	$act=2; 
	$extQry =  "DATE(created) = DATE(NOW() - INTERVAL 1 DAY) AND";
	$extQry2 =  "where DATE(assigned) = DATE(NOW() - INTERVAL 1 DAY) ";
} else if(isset($_GET['sort']) && $_GET['sort']==3) {
	$act=3; 
	$extQry =  "created >= DATE(NOW()) - INTERVAL 7 DAY AND";
	$extQry2 =  "where assigned >= DATE(NOW()) - INTERVAL 7 DAY ";
} else if(isset($_GET['sort']) && $_GET['sort']==4) {
	$act=4; 
	$extQry =  "created >= DATE(NOW()) - INTERVAL 30 DAY AND";
	$extQry2 =  "where assigned >= DATE(NOW()) - INTERVAL 30 DAY ";
} else {
	$act=0; $extQry = $extQry2=   "";
} 

if(isset($_GET['id']) && $_GET['act']==1) { 
	$sql = "UPDATE sales_ninja_users SET active='1' WHERE id = {$_GET['id']} ";
	$result = $conn->query($sql);

	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'sales-user.php';</script>";
	exit();
}


?>
  <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">       

        <!-- page content -->
        <div class="right_col" role="main">
          
          <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2>Sales Team</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=print.php?sort=<?php echo $act; ?>" target="_blank" class="  btn btn-default glyphicon glyphicon-print" type="button"> Print </a>                        
                      </div>    
                      </li>
                    </ul>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>Records : &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        <a href="loading.php?pg=sales-user.php" class="btn btn-default  <?php if($act==0) { echo 'active'; } ?>" type="button">All</a>
                        <a href="loading.php?pg=sales-user.php?sort=1" class="btn btn-default <?php if($act==1) { echo 'active'; } ?>" type="button">Today</a>
                        <a href="loading.php?pg=sales-user.php?sort=2" class="btn btn-default <?php if($act==2) { echo 'active'; } ?>" type="button">Yesterday</a>
                        <a href="loading.php?pg=sales-user.php?sort=3" class="btn btn-default <?php if($act==3) { echo 'active'; } ?>" type="button">Last 7 Days</a>
                        <a href="loading.php?pg=sales-user.php?sort=4" class="btn btn-default <?php if($act==4) { echo 'active'; } ?>" type="button">Last 30 Days</a>
                      </div>    
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                    
                      <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                          <div class="modal-content">
    
                            <div class="modal-header">
                              <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                              </button>
                              <h4 class="modal-title" id="myModalLabel">Modal title</h4>
                            </div>
                            <div class="modal-body">
                              Loading...
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                             
                            </div>
    
                          </div>
                        </div>
                      </div>
                      
                    <table id="datatable" class="table table-striped table-striped projects">
                      <thead>
                              <tr>                              	
                                <th>SNo</th>
                                <th>Name</th>
                                <th>Leads</th>
                                <th>RNR</th>
                                <th>Intrested</th>
                                <th>NI</th>       
                                <th>Callback</th>  
                                <th>SV</th>                                 
                                <th>Edit</th>
                                <th>Stats</th>
                                <th>Assign Leads</th>
                              </tr>
                      </thead>


                      <tbody>
                      <?php  					 
					    $q0 = $conn->query("SELECT sal_ex, COUNT(*) as tot FROM sales_ninja $extQry2 GROUP BY sal_ex");
					  	while($r0 = $q0->fetch_assoc()) { $d0[$r0['sal_ex']] = $r0['tot']; }
						  
					  	$q1 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_calls where $extQry callDuration=0 AND callType=1 GROUP BY uId");
					  	while($r1 = $q1->fetch_assoc()) { $d1[$r1['uId']] = $r1['tot']; }
						
						$q2 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_callqty where $extQry leadQty=0 GROUP BY uId");
					  	while($r2 = $q2->fetch_assoc()) { $d2[$r2['uId']] = $r2['tot']; }
						
						$q3 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_callqty where $extQry leadQty=1 GROUP BY uId");
					  	while($r3 = $q3->fetch_assoc()) { $d3[$r3['uId']] = $r3['tot']; }
						
						$q4 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_callqty where $extQry leadQty=2 GROUP BY uId");
					  	while($r4 = $q4->fetch_assoc()) { $d4[$r4['uId']] = $r4['tot']; }	
						
						$q5 = $conn->query("SELECT added, COUNT(*) as tot FROM sales_ninja where $extQry id!=0 GROUP BY added");
					  	while($r5 = $q5->fetch_assoc()) { $d5[$r5['added']] = $r5['tot']; }		
						
						//print_r($data1);
								$i=1; 
								$sql = "SELECT * FROM sales_ninja_users order by id desc";
								$result = $conn->query($sql);
								//if ($result->num_rows == 0) { echo 'No records found!'; }
								while($row = $result->fetch_assoc()) 
								{
									$prog = rand(20,100);
							  ?>
                        	<tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo $row["name"]; ?></td>
                                <th><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="0" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d0[$row["id"]])) { echo $d0[$row["id"]]; } else { echo 0; } ?></a></th>
                                <th><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="1" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d1[$row["id"]])) { echo $d1[$row["id"]]; } else { echo 0; } ?></a></th>
                                <th><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="2" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d2[$row["id"]])) { echo $d2[$row["id"]]; } else { echo 0; } ?></a></th>
                                <th><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="3" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d3[$row["id"]])) { echo $d3[$row["id"]]; } else { echo 0; } ?></a></th>       
                                <th><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="4" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d4[$row["id"]])) { echo $d4[$row["id"]]; } else { echo 0; } ?></a></th> 
                                <th><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="5" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d5[$row["id"]])) { echo $d5[$row["id"]]; } else { echo 0; } ?></a></th> 
                                <td>                                	
                                    <a href="loading.php?pg=sales-user-add.php?id=<?php echo $row["id"]; ?>" class="btn btn-info btn-s"><i class="fa fa-pencil"></i> Edit </a>
                                    
                                </td>
                                <td><a href="loading.php?pg=sales-user-stats.php?id=<?php echo $row["id"]; ?>" class="btn btn-primary btn-s">View Stats</a></td>
                                <td><?php if($row["active"]==1) { ?><a href="loading.php?pg=sales-user-assign2.php?id=<?php echo $row["id"]; ?>" class="btn btn-success btn-s">Assign Leads</a><?php } else { ?><a href="loading.php?pg=sales-user.php?id=<?php echo $row["id"]; ?>&act=1" class="btn btn-info btn-s">Activate</a>
								<?php } ?></td>
                            </tr>
                              <?php $i++; } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
<?php include 'footer.php'; ?>
<!-- jQuery -->
   
    <!-- Datatables -->
    <script>
					$('.btn-block1').on('click',function()
					{
					var id = $(this).data('id');
					var ty = $(this).data('ref');
					var act = <?php echo $act; ?>;
					var tyArr = {0: 'Leads', 1: 'Call History - RNR', 2:'SMS History', 3:'Reminder Logs', 4:'Call Quality' };
					//alert(id+', '+ty);
					$('.modal-body').html('loading');
				
					   $.ajax({
						type: 'POST',
						url: 'ajaxStats2.php',
						data:{id: id, ty: ty, act: act},
						success: function(data) {
							//alert(data);
						  $('#myModalLabel').html(tyArr[ty]);
						  $('.modal-body').html(data);
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
				 });
	</script>
    <script src="vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>