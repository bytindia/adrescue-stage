<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

auditAuth();
$pgHeadline = 'Audit Report - List';
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
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
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
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="add-account.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
                      </div>    
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
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Audit Name</th>                                    	
                                        <th>Facebook Account</th>
                                        <th>Facebook Page</th> 
                                        <th width="16%">Audit Report</th> 
                                        <th width="16%">Edit</th>     	
                                    </thead>
                                    <tbody>
                                    	<?php
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%!i', $amount);
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["audit_name"]; ?></td>
                                            <td><?php echo $sqlROW["acc_id"]; ?></td>
                                            <td><?php echo $sqlROW["page_id"]; ?></td>
                                            <td> <a href="audit.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-warning btn-sm"><i class="fa fa-eye"></i> View</a></td>
                                            <td>
                                            <a href="cron-audit.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1" class="btn btn-warning btn-sm"><i class="fa fa-refresh"></i></a>
                                            <a href="add-account.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> </a>
                                    <a href="#" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i></a>
                                    
                                    
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
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