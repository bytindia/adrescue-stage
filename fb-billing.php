<?php include 'header.php';  


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'BYT - FB Billing';
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
                          $fb_name = $fbN = array();
                          //echo "SELECT fb_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."'";
                                        $sqlAcc = mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."'");
                                        while($sqlROW2=mysqli_fetch_assoc($sqlAcc))
										                    {   
                                            $fb_name[$sqlROW2['account_id']] = $sqlROW2['name'];
                                            //array_push($fb_name, $sqlROW2["name"]);
                                        }
                                       
                                       
                                       // d($fbN);
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Client Name</th>                                    	
                                        <th>Account</th>
                                        <th>Balance</th>    
                                        <th width="10%">View</th>  	
                                    </thead>
                                    <tbody>
                                    	<?php
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%.0n', $amount);
                    $totRows = mysqli_num_rows($sqlRev);
                    $grandBudget = $grandSpent = $grandBalance = 0;
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
                                            $colSpan = "";
                                            
                                            $fb_acc = explode(',',$sqlROW["fb_id"]);
                                            $fb_bal = explode(',',$sqlROW["fb_balance"]);
                                            $no_fb_acc = count($fb_acc);

                                            if(trim($sqlROW["fb_id"])!='' && $no_fb_acc>1){
                                                $colSpan =  'rowspan="'.$no_fb_acc.'"';
                                            }
										?>
                                        <tr>                                        	
                                            <td <?php echo $colSpan; ?>><?php echo $i; ?></td>
                                        	<td <?php echo $colSpan; ?>><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 1, '<?php echo $sqlROW["client_name"]; ?>');" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $sqlROW["client_name"]; ?></a><!-- <i class="fa fa-facebook"></i> <i class="fa fa-google"></i> <i class="fa fa-linkedin"></i> <i class="fa fa-taboola"></i>  --></td>       
                                            <?php $z = 1;
                                            foreach ($fb_acc as $k => $v) {
                                                if($z!=1) {
                                                    echo '<tr>';
                                                }
                                                echo '<td><a href="loading.php?pg=https://business.facebook.com/ads/manager/billing_history/summary/?act='.$v.'&pid=p1&business_id=866116510072687&global_scope_id=866116510072687&page=billing_history&tab=summary" target="_blank"class="blue modal-cl">'.$fb_name[$v].'</a></td>';
                                                echo '<td class="text-right">'.$fmt->format($fb_bal[$k]).'</td>';
                                                echo '<td class="text-right"><a href="loading.php?pg=https://business.facebook.com/ads/manager/billing_history/summary/?act='.$v.'&pid=p1&business_id=866116510072687&global_scope_id=866116510072687&page=billing_history&tab=summary"  target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View </a></td>';
                                                if($z!=1) {
                                                    echo '</tr>';
                                                }
                                                if($z==$colSpan) { echo '</tr>'; }
                                                $z++;
                                            }    
                                            ?>    
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

