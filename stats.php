<?php 
$pgHeadline = 'SalesNinja - Leads';
include 'header.php'; 
 ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>
<style>
.btn-block1 { display: inline; cursor:pointer;  }
.table > tbody > tr > td {
     vertical-align: middle;
}
</style>
  <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">       
<?php 	
$tit = 'Calls';	
		if(!isset($_GET['ty']) || $_GET['ty']==1)
										{
											$callTypest = array(1=>'OUTGOING', 2=>'INCOMING', 3=>'MISSED');
											$filedName = array('Phone', 'Name', 'Call Type', 'Call Duration', 'Call Date');
											$tblFields = array('callNo', 'leadName', 'callType', 'callDuration', 'callTime');											
											$tblFieldsWhere = "callNo!=''";
											$tbl = 'sn_calls';
											$modelTitle = 1;
											$tit = 'Calls';	
											$_GET['ty'] = 1;
                                           // $sql = "SELECT * FROM $tbl where $tblFieldsWhere order by id desc";
										}
										else if(isset($_GET['ty']) && $_GET['ty']==2)
										{
											$callTypest = array(1=>'Delivered', 2=>'Pending', 3=>'Sent');
											$filedName = array('Phone', 'Name', 'Message', 'SMS Date');
											$tblFields = array('leadPh', 'leadName', 'leadMsg', 'created');											
											$tblFieldsWhere = "leadPh!=''";
											$tbl = 'sn_sms';
											$modelTitle = 2;
											$tit = 'SMS';	
                                           // $sql = "SELECT * FROM $tbl where $tblFieldsWhere order by id desc";
										}
										else if(isset($_GET['ty']) && $_GET['ty']==3)
										{
											$callTypest = array(0=>'Call', 1=>'SMS', 3=>'Sent');
											$filedName = array('Phone', 'Name', 'Reminder', 'Remind Time', 'Remind Date', 'Created');
											$tblFields = array('leadPh', 'leadName', 'remType', 'timeAt', 'remDate', 'created');											
											$tblFieldsWhere = "leadPh!=''";
											$tbl = 'sn_reminder';
											$modelTitle = 3;
											$tit = 'Follow-Ups';	
                                           // $sql = "SELECT * FROM $tbl where $tblFieldsWhere order by id desc";
										}
										else if(isset($_GET['ty']) && $_GET['ty']==4)
										{
											 $callTypest = array(0=>'Interested', 1=>'Not-Interested', 2=>'Reminder', 3=>'Others');
											$filedName = array('Phone', 'Name', 'Lead Quality', 'Comments', 'Date');
											$tblFields = array('leadPh', 'leadName', 'leadQty', 'leadCmnt', 'created');											
											$tblFieldsWhere = "leadPh!=''";
											$tbl = 'sn_callqty';
											$modelTitle = 4;
											$tit = 'Lead Quality';	
                                           // $sql = "SELECT * FROM $tbl where $tblFieldsWhere order by id desc";
										}
?>
        <!-- page content -->
        <div class="right_col" role="main">
          
          <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="">
            <div class="page-title">
              <div class="title_left">
                <h3><?php echo $tit; ?></h3>
              </div>
            </div>
                  <div class="x_content">
                    <?php include 'alert.php'; ?>  
                    
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
                    
                      <div class="" role="tabpanel" data-example-id="togglable-tabs">
                        <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
                          <li role="presentation" class="<?php if(!isset($_GET['ty']) || $_GET['ty']==1) { echo 'active'; } ?>"><a href="loading.php?pg=stats.php"  aria-expanded="true">Calls</a>
                          </li>
                          <li role="presentation" class="<?php if(isset($_GET['ty']) && $_GET['ty']==2) { echo 'active'; } ?>"><a href="loading.php?pg=stats.php?ty=2"  aria-expanded="false">SMS</a>
                          </li>
                          <li role="presentation" class="<?php if(isset($_GET['ty']) && $_GET['ty']==3) { echo 'active'; } ?>"><a href="loading.php?pg=stats.php?ty=3" aria-expanded="false">Follow-ups</a>
                          </li>
                          <li role="presentation" class="<?php if(isset($_GET['ty']) && $_GET['ty']==4) { echo 'active'; } ?>"><a href="loading.php?pg=stats.php?ty=4"  aria-expanded="false">Lead Quality</a>
                          </li>
                        </ul>
                        <div id="myTabContent" class="tab-content">
                          <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="home-tab">
                          
                         

                            <?php 		
							include 'web/pagination.php'; 
							$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
							if ($page <= 0) $page = 1;
							$per_page = 20; // Set how many records do you want to display per page.
							$startpoint = ($page * $per_page) - $per_page;	
							
							$statement = "$tbl where $tblFieldsWhere order by id desc";
							$result = $conn->query("SELECT ".implode(',', $tblFields)." FROM {$statement} LIMIT {$startpoint} , {$per_page}"); 
													
													  $i= $startpoint+1;											  
                                                      //$sql = "SELECT ".implode(',', $tblFields)." FROM $tbl where $tblFieldsWhere order by id desc";
                                                     // $result = $conn->query($sql);
													  
													  if($result->num_rows==0) {
														  echo '<br>No records found!';
													  } else {													  
													 ?>
                                              			<table class="table table-striped table-striped projects">
                                                                <thead>
                                                                  <tr>
                                                                    <th width="5%">SNo</th>
                                                                    <?php foreach($filedName as $fieldVal) { ?>
                                                                    <th><?php echo $fieldVal; ?></th>
                                                                    <?php } ?>
                                                                  </tr>
                                                                </thead>
                                                                <tbody>
                                                                 <?php 
                                                                 while($row = $result->fetch_assoc()) 
                                                                 {
                                                                  ?>
                                                                  <tr>
                                                                    <td><?php echo $i; ?></td>
                                                                    <?php 
																	$first = true;
																	foreach($tblFields as $tblFieldsVal) { 																		
																		if($tblFieldsVal=='created') { $row[$tblFieldsVal]=date("h:i a, d-M-Y", strtotime($row[$tblFieldsVal])); }
																		if($tblFieldsVal=='callTime') { //$row[$tblFieldsVal]=gmdate("Y-m-d\TH:i:s\Z", $row[$tblFieldsVal]); 
																		$epoch = substr(trim($row[$tblFieldsVal]), 0, 10);
																		$dt= new DateTime("@$epoch");
																		$row[$tblFieldsVal]= $dt->format('Y-m-d H:i:s');
																		}
																		if($tblFieldsVal=='callDuration') { 
																		//$row[$tblFieldsVal] = floor($row[$tblFieldsVal] / 60000) . ":" . floor($row[$tblFieldsVal]/1000 % 60); 
																		$row[$tblFieldsVal] = gmdate("i:s", $row[$tblFieldsVal]);
																		}
																		if($tblFieldsVal=='callType' || $tblFieldsVal=='msgStatus' || $tblFieldsVal=='remType' || $tblFieldsVal=='leadQty') { $row[$tblFieldsVal]=$callTypest[$row[$tblFieldsVal]]; }
																		if ( $first )
    																	{ 
																			$extrnalData[$i] = $row[$tblFieldsVal];
																		?>
																		<td>
																		<span data-toggle="modal" data-id="<?php echo $row[$tblFieldsVal]; ?>" data-ref="<?php echo $modelTitle; ?>"  data-target=".bs-example-modal-lg" class="btn-block1">
																		<a data-placement="top" data-toggle="tooltip"  data-original-title="View Stats" class="blue"><?php echo $row[$tblFieldsVal]; ?> <i class="fa fa-external-link"></i> </a>
																		</span>
																		</td>
																		<?php 
																		$first = false;
																		}
																		else
																		{
																			echo "<td>".$row[$tblFieldsVal]."</td>";
																		}
																	  }
																	 ?>
                                                                   		
                                                                  </tr>
                                                                  <?php $i++; } ?>
                                                                </tbody>
                                                              </table> 
                                                        <?php } ?>
                                                        <div id="pagDiv"><?php echo pagination($statement,$per_page,$page, $url='?ty='.$_GET['ty'].'&'); ?></div>

                          
                          </div>
                        </div>
                      </div>
                  </div>
                </div>
              </div>
        </div>
        </div>
         </div>
        <!-- /page content -->
<?php include 'footer.php'; ?>
<!-- jQuery -->
   <script>
					$('.btn-block1').on('click',function()
					{
					var id = $(this).data('id');
					var ty = $(this).data('ref');
					var tyArr = {1: 'Call History', 2:'SMS History', 3:'Reminder Logs', 4:'Call Quality' };
					//alert(id+', '+ty);
					$('.modal-body').html('loading');
				
					   $.ajax({
						type: 'POST',
						url: 'ajaxStats.php',
						data:{id: id, ty: ty},
						success: function(data) {
							//alert(data);
						  $('#myModalLabel').html(id+' - '+tyArr[ty]);
						  $('.modal-body').html(data);
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
				 });
				 </script>

    <!-- Datatables -->
    <script src="vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>