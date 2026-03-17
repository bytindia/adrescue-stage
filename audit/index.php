<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

auditAuth();
$pgHeadline = 'BYT Ads Rescue - Audit List';
$pgID = 8;
$err =''; 
if(isset($_GET['del'])) {
	//mysqli_query($conn, "UPDATE cashflow SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'cashflow.php';</script>";
	exit();
}

//include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " audit_reports WHERE uid='".$_SESSION['uid']."' ";

?>
<style>
.dataTables_wrapper { font-size:15px; } 
table.table-bordered.dataTable tbody th, table.table-bordered.dataTable tbody td { vertical-align:middle; }
.nav-sm .main_container .top_nav, .nav-sm .container.body .right_col, .nav-sm footer { margin-left:0px; }
#menu_toggle { display:none; }
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.btn-sm { color:#F9F5F5; }
.btn-primary { background:#2283f3; }
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include 'menu-left.php';
			include 'menu-top.php'; 
			
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <?php 
						if(mysqli_num_rows($sqlRev)!=0) {
					?>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-lg">
                        	<a href="add-account.php"  class="btn btn-primary btn-lg">GET STARTED</a>                        
                      </div>    
                      </li>
                    </ul>
                   <?php } ?>
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
										if(mysqli_num_rows($sqlRev)==0) {
											echo "<script>window.location = 'add-account.php';</script>"; 
											exit;
								?>
                                
                                <div class="container h-100 d-flex justify-content-center text-center">

                                <div class="jumbotron my-auto">
                            
                                  <h2 class="display-3"> Enter Your Details for us to generate your custom audit report!</h2>
                                  <h1> <a href="add-account.php"  class="btn btn-primary btn-lg">GET STARTED</a> </h1>
                            
                                </div>
                            
                             </div>
                                      
                                
                                <?php } else { ?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>                                  	
                                        <th>Facebook Ad Account</th>
                                        <th>Facebook Page</th> 
                                        <th>Objective Type</th> 
                                        <th>Created</th> 
                                        <th width="22%">Audit Report</th> 
                                        <th width="16%">Edit</th>     	
                                    </thead>
                                    <tbody>
                                    	<?php
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%!i', $amount);
										$objTy= array(1=>'Lead Generation',2=>'Conversion',3=>'Both',);
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											if($sqlROW["rep_obj"]=='') { $sqlROW["rep_obj"] = 3; }
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                            <td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["pg_name"]; ?></td>
                                             <td><?php echo $objTy[$sqlROW["rep_obj"]]; ?></td>
                                            <td><?php echo date('d-M-Y h:i a', strtotime($sqlROW["created"])); ?></td>
                                            <td>
                                            <?php if($sqlROW["sync"]==1) { ?>
                                            <div class="btn-group" role="group" aria-label="Basic example">
 
                                             <a href="audit.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-lg" target="_blank"> View</a> 
                                            <a href="pdf_from_url.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-lg"> Email</a>
                                            </div>

                                            <?php } else { ?>
                                            <a href="audit-report.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1" target="_blank" class="btn btn-default btn-lg"> Fetch Report</a>
                                            <?php } ?>
                                            </td>
                                            <td>
                                            <div class="btn-group" role="group" aria-label="Basic example">

                                            <a href="audit-report.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1" target="_blank" class="btn btn-primary btn-lg"><i class="fa fa-refresh"></i></a>
                                            <a href="add-account.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-lg"><i class="fa fa-pencil"></i> </a>
                                    <a href="#" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-primary btn-lg"><i class="fa fa-trash-o"></i></a>
                                    		</div>
                                    
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  <?php
								}
								?>
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
					$('.modal-body').html('loading');
					$.ajax({
						type: 'POST',
						url: 'ajax-cashflow.php',
						data:{id: id, ty: ty},
						success: function(data) {
							//alert(data);
						  $('#myModalLabel').html(clName);
						  $('.modal-body').html(data);
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
					
};
</script>
<?php include 'footer.php'; ?>