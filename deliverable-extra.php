<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

$dt_q ='';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
}

Auth();
$pgHeadline = 'Deliverables - Manage Clients / SM / Designer / Label';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])) {
  //d($_POST); //exit;

  $tbl_id = $_POST['tbl_id'];
  $ty = $_POST['ty'];
  
  if($tbl_id==0) {
       $cirSql = "INSERT INTO deliverable_".$ty." (name, status, created) VALUES ('".mysqli_real_escape_string($conn, $_POST['name'])."', '0', now());"; 
       mysqli_query($conn, $cirSql) or die(mysqli_error());
       $lastId = mysqli_insert_id($conn);
  } else {
       $cirSql = "UPDATE deliverable_".$ty." SET name='".mysqli_real_escape_string($conn, $_POST['name'])."' WHERE  tbl_id='".$tbl_id."'";
       mysqli_query($conn, $cirSql) or die(mysqli_error());
  }
  //exit;
  $_SESSION['suc'] = 'Successfully Updated!';	
  echo "<script>window.location = 'deliverable-extra.php';</script>";
  exit();

}

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE deliverable_".$_GET['ty']." SET status='1' where tbl_id=".$_GET['id']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'deliverable-extra.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " cashflow2 WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>
<style>
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.change-message {
    display: none;
}
th {text-align: center;}
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
                    <h4 class="modal-title" id="myModalLabel"></h4>
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
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="deliverable.php"  class="btn btn-success btn-sm">Deliverables Dashboard</a>                        
                      </div>    
                      </li>
                      
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                        $today = date('d', time());
                        $nthWeek = 1 + floor(($today - 1) / 7);
                        $cur_month = date('n');
                        $cur_year = date('Y');

                        $weeks_filter = array('1'=>'1st week','2'=>'2st week','3'=>'3rd week','4'=>'4th week','5'=>'5th week');
                        $month_filter = array('1'=>'Jan','2'=>'Feb','3'=>'Mar','4'=>'Apr','5'=>'May','6'=>'Jun','7'=>'Jul','8'=>'Aug','9'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec');
                        $year_filter = array(2023=>2023, 2024=>2024);

                        $labels = $managers = $designers = $clients = $data = $comit_data = $deli_data = $comit_data_manager = $deli_data_manager =  $delsigner_rep = array();

                        $sqlRev=mysqli_query($conn, "SELECT * FROM deliverable_labels WHERE status=0 ");
                        while($sqlROW=mysqli_fetch_array($sqlRev)) { $labels[$sqlROW['tbl_id']] = $sqlROW['name']; }
                        
                        $sqlRev2=mysqli_query($conn, "SELECT * FROM deliverable_manager WHERE  status=0 order by name asc");
                        while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $managers[$sqlROW2['tbl_id']] = $sqlROW2['name']; }
                        
                        $sqlRev3=mysqli_query($conn, "SELECT * FROM deliverable_designer WHERE  status=0 order by name asc");
                        while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $designers[$sqlROW3['tbl_id']] = $sqlROW3['name']; }

                        $sqlRev4=mysqli_query($conn, "SELECT * FROM deliverable_clients WHERE status=0 order by name asc");
					    while($sqlROW4=mysqli_fetch_array($sqlRev4)) { $clients[$sqlROW4['tbl_id']] = $sqlROW4['name']; }

                        //echo "SELECT * FROM deliverable_reports WHERE  week='".$nthWeek."' AND month='".$cur_month."' AND year='".$cur_year."'"; 
                        $sql_report=mysqli_query($conn, "SELECT client_id, manager_id, designer_id, committed, delivered FROM deliverable_reports WHERE week='".$nthWeek."' AND month='".$cur_month."' AND year='".$cur_year."'");
					              while($row_rep=mysqli_fetch_assoc($sql_report)) { 
                          $row_rep['designer_id'] = unserialize($row_rep['designer_id']);
                          $row_rep['committed'] = unserialize($row_rep['committed']);
                          $row_rep['delivered'] = unserialize($row_rep['delivered']);

                          $data[$row_rep['client_id']][] = $row_rep; 
                          $comit_data[$row_rep['client_id']][] = $row_rep['committed'];
                          $deli_data[$row_rep['client_id']][] = $row_rep['delivered'];
                          
                          $comit_data_manager[$row_rep['manager_id']][] = $row_rep['committed'];
                          $deli_data_manager[$row_rep['manager_id']][] = $row_rep['delivered'];

                          $delsigner_rep[] = $row_rep['designer_id'];
                        }
                        //d($delsigner_rep);
                        //echo array_sum(array_column($comit_data[5], 1)); 
                        //d($clients);  d($designers);
                        $sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
                        $i = (($page-1) * $per_page ) + 1;
                        $z=1; 
								?>
                
                                
                                <div class="clearfix"></div><br>
                                <div class="col-md-3">
                                <h4 class="text-center">Clients <a href="#" onClick="onAjax(0,'New','clients');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-plus"></i> Add </a></h4>
                                
                                
                                <table  class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        
                                        <th rowspan="2">Clients</th>
                                        <th>Edit</th>
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach($clients as $ck => $cv) {
                                        ?>
                                        <tr>
                                            
                                            <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','clients');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                            <td>
                                                <a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','clients');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-pencil"></i> </a>  
                                                <a href="deliverable-extra.php?del=1&ty=clients&&id=<?php echo $ck; ?>" class=" btn btn-danger btn-sm"  onclick="return confirm('Are you sure you want to delete this?');"><i class="fa fa-trash"></i> </a>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                </div>

                                <div class="col-md-3">
                                <h4 class="text-center">SM Team <a href="#" onClick="onAjax(0,'New','manager');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-plus"></i> Add </a></h4>
                                <table  class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        
                                        <th rowspan="2">SM Team</th>
                                        <th>Edit</th>
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach($managers as $ck => $cv) {
                                        ?>
                                        <tr>
                                            
                                            <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','manager');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                            <td>
                                                <a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','manager');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-pencil"></i> </a>  
                                                <a href="deliverable-extra.php?del=1&ty=manager&&id=<?php echo $ck; ?>" class=" btn btn-danger btn-sm"  onclick="return confirm('Are you sure you want to delete this?');"><i class="fa fa-trash"></i> </a>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                </div>

                                <div class="col-md-3">
                                <h4 class="text-center">Designers <a href="#" onClick="onAjax(0,'New','designer');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-plus"></i> Add </a></h4>
                                <table  class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        
                                        <th rowspan="2">Designer</th>
                                        <th>Edit</th>
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach($designers as $ck => $cv) {
                                        ?>
                                        <tr>
                                            
                                            <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','designer');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                            <td>
                                                <a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','designer');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-pencil"></i> </a>  
                                                <a href="deliverable-extra.php?del=1&ty=designer&&id=<?php echo $ck; ?>" class=" btn btn-danger btn-sm"  onclick="return confirm('Are you sure you want to delete this?');"><i class="fa fa-trash"></i> </a>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                </div>

                                <div class="col-md-3">
                                <h4 class="text-center">Label <a href="#" onClick="onAjax(0,'New','labels');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-plus"></i> Add </a></h4>
                                <table  class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        
                                        <th rowspan="2">Label</th>
                                        <th>Edit</th>
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach($labels as $ck => $cv) {
                                        ?>
                                        <tr>
                                            
                                            <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>','labels');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                            <td>
                                                <a href="#" onClick="onAjax(<?php echo $ck; ?>, '<?php echo $cv; ?>','labels');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl btn btn-primary btn-sm" data-target=".bs-example-modal-lg"><i class="fa fa-pencil"></i> </a>  
                                                <a href="deliverable-extra.php?del=1&ty=labels&&id=<?php echo $ck; ?>" class=" btn btn-danger btn-sm"  onclick="return confirm('Are you sure you want to delete this?');"><i class="fa fa-trash"></i> </a>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                </div>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>
<script>

var $form;
var origForm;
$(document).on('change input', "form :input", function (evt) {
    //$('form :input').on('change input', function() {
    $('.change-message').toggle($form.serialize() !== origForm);
});

$('.number_only').bind('keyup paste', function(){
        this.value = this.value.replace(/[^0-9]/g, '');
});

function onAjax(id, clName, ty) {
					//var id = $(this).attr('data-id');
					//alert(id+','+clName);
          $('#myModalLabel').html(clName);
					$('.modal-body').html('<br><center><img src="images/ajax-loader.gif" /></center><br>'); //return false;
					$.ajax({
						type: 'POST',
						url: 'deliverable-extra-edit.php',
						data:{id: id, ty: ty},
						success: function(data) {
							//alert(data);
              

						  $('#myModalLabel').html(clName);
						  $('.modal-body').html(data);

              $form = $('form'),
              origForm = $form.serialize();
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
					
};
			
	</script>