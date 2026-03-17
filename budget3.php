<?php include 'header.php';  
echo "<script>window.location = 'budget4.php';</script>"; exit;
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Ads Budget - detailed report';
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
	mysqli_query($conn, "UPDATE budget_reminder SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'budget2.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " budget_reminder WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

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
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=budget-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
                      </div>    
                      </li>
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=cron-budget3.php?refresh=1"  class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to Email Cashflow report?');" ><i class="fa fa-refresh"></i> Refresh All</a>                        
                      </div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                           $maxDays = date('t');
                           $nDay = date("d");

										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Clients</th>                                    	
                                        <th>Budget</th>
                                        <th>Est. daily spend</th> 
                                        <th>Actual daily spend</th> 
                                        <th>FB spent</th>
                                        <th>G spent</th>
                                        <th>Tot. spent</th>
                                        <th>Rem. budget</th>
                                        <th>Yest. spent</th>
                                        <th>Est. month end</th>
                                        <th width="12%">Edit</th>     	
                                    </thead>
                                    <tbody>
                                    	<?php
                                      
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%.0n', $amount);
                    $totRows = mysqli_num_rows($sqlRev);
                    $grandBudget = $grandSpent = $grandBalance = 0;
                    $grandEstSpent = $grandActSpent = $grandFbSpent = $grandGSpent = $grandBalance = $grandYestSpent = $grandMonEnd = 0;

										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
											
											$fb_spent = $g_spent = $in_spent = $ta_spent = 0;
                                            $fb_spent_y = $g_spent_y = $in_spent_y = $ta_spent_y = 0;
											
											if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
											if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
											if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
											if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }

                                            if($sqlROW["fb_spent_y"]!='') { $fb_spent_y = explode(',',$sqlROW["fb_spent_y"]); $fb_spent_y = array_sum(array_filter($fb_spent_y)); }
											if($sqlROW["g_spent_y"]!='') { $g_spent_y = explode(',',$sqlROW["g_spent_y"]); $g_spent_y = array_sum(array_filter($g_spent_y)); }
											if($sqlROW["in_spent_y"]!='') { $in_spent_y = explode(',',$sqlROW["in_spent_y"]); $in_spent_y = array_sum(array_filter($in_spent_y)); }
											if($sqlROW["ta_spent_y"]!='') { $ta_spent_y = explode(',',$sqlROW["ta_spent_y"]); $ta_spent_y = array_sum(array_filter($ta_spent_y)); }
											
											$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
                                            $tot_spend_y = $fb_spent_y + $g_spent_y + $in_spent_y + $ta_spent_y;
											
											$tot_bal = $sqlROW["total_budget"] - $tot_spend ;

                      $grandBudget = $grandBudget + $sqlROW["total_budget"];
                      $grandSpent = $grandSpent + $tot_spend;
                      $grandBalance = $grandBalance + $tot_bal;
                      $grandEstSpent = $grandEstSpent + ($sqlROW["total_budget"]/$maxDays);
                      $grandActSpent = $grandActSpent + ($tot_spend/$nDay);
                      $grandFbSpent = $grandFbSpent + $fb_spent;
                      $grandGSpent = $grandGSpent + $g_spent;
                      
                      $grandYestSpent = $grandYestSpent + $tot_spend_y;
                      $grandMonEnd = $grandMonEnd + (($tot_spend/$nDay)*$maxDays);
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 1, '<?php echo $sqlROW["client_name"]; ?>');" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $sqlROW["client_name"]; ?></a><!-- <i class="fa fa-facebook"></i> <i class="fa fa-google"></i> <i class="fa fa-linkedin"></i> <i class="fa fa-taboola"></i>  --></td>                                      
                                            <td class="text-right"> <?php echo $fmt->format($sqlROW["total_budget"]); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format(($sqlROW["total_budget"]/$maxDays)); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format(($tot_spend/$nDay)); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format($fb_spent); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format($g_spent); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format($tot_spend); ?></td>    
                                            
                                            <td class="text-right"> <?php echo $fmt->format($tot_bal); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format($tot_spend_y); ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format((($tot_spend/$nDay)*$maxDays)); ?>
                                          <?php if($sqlROW["total_budget"]<(($tot_spend/$nDay)*$maxDays)) { echo '<i class="fa fa-arrow-up" style="color:#ff5f5f"></i>'; } else { echo '<i class="fa fa-arrow-down" style="color:#06c54a"></i>'; } ?>
                                          </td> 
                                            <td class="text-right">
                                            <a href="loading.php?pg=cron-budget3.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
                                            <a href="loading.php?pg=budget-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-default btn-sm"><i class="fa fa-pencil"></i> </a>
                                    <a href="loading.php?pg=budget2.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-default btn-sm"><i class="fa fa-trash-o"></i></a>
                                    
                                    
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
                              if($i==($totRows+1)){ ?>
                                <td><b><?php echo $i; ?></b></td>
                                <td class="text-right"><b>Total</b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudget); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandEstSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandActSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandFbSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandGSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBalance); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandYestSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandMonEnd); ?></b></td>
                                <td></td>
  <?php
                              }
										} 
                    
                    ?>                                      
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



<script>
 
function onAjax(id,ty,clName) {
					//var id = $(this).attr('data-id');
					//alert(id);
					$('.modal-body').html('loading');
					$.ajax({
						type: 'POST',
						url: 'ajax-budget2.php',
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

