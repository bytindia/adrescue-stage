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
$pgHeadline = 'Cashflow 2';
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
	mysqli_query($conn, "UPDATE cashflow2 SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'cashflow2.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 250; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " cashflow2 WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

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
                        	<a href="loading.php?pg=cashflow-add2.php"  class="btn btn-primary btn-sm">Add Accounts</a>                        
                      </div>    
                      </li>
                      <li> &nbsp;</li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=cron-cashflow2.php?refresh=1"  class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to Email Cashflow report?');" ><i class="fa fa-refresh"></i> Refresh All</a>                        
                      </div>    
                      </li>
                      <li> &nbsp;</li>
                      <li>
                      <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                        </div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Client Name</th>                                    	
                                        <th>Payment Received</th>
                                        <th>Total Spent</th> 
                                        <th>Balance</th>
                                        <th width="16%">Edit</th>     	
                                    </thead>
                                    <tbody>
                                    	<?php
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = moneyFormatIndia($amount);
                    $totRows = mysqli_num_rows($sqlRev);
                    $grandRecived = $grandSpent = $grandBalance = 0;
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
											
											$fb_spent = $g_spent = $in_spent = $ta_spent = 0;
											
											if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
											if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
											if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
											if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }
											
											$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
											
											$tot_bal = $sqlROW["tot_paid"] - $tot_spend + $sqlROW["tot_penalty"];

                      $grandRecived = $grandRecived + $sqlROW["tot_paid"];
                      $grandSpent = $grandSpent + $tot_spend;
                      $grandBalance = $grandBalance + $tot_bal;
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 1, '<?php echo $sqlROW["client_name"]; ?>');" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $sqlROW["client_name"]; ?></a><!-- <i class="fa fa-facebook"></i> <i class="fa fa-google"></i> <i class="fa fa-linkedin"></i> <i class="fa fa-taboola"></i>  --></td>
                                        	<td><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 2, '<?php echo $sqlROW["client_name"]; ?> - Payment Received Detail');" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"> <?php echo sprintf('%01.2f', $sqlROW["tot_paid"]); ?></a></td>                                         
                                            <td><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 1, '<?php echo $sqlROW["client_name"]; ?> - Spent Detail');" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"> <?php echo sprintf('%01.2f', $tot_spend); ?></a></td>    
                                            <td> <?php echo sprintf('%01.2f', $tot_bal); ?></td> 
                                            <td>
                                            <a href="loading.php?pg=cron-cashflow2.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1" class="btn btn-primary btn-sm"><i class="fa fa-refresh"></i></a>
                                            <a href="loading.php?pg=cashflow-add2.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> </a>
                                    <a href="loading.php?pg=cashflow2.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-primary btn-sm"><i class="fa fa-trash-o"></i></a>
                                    
                                    
                                    		</td>                              	
                                        </tr>  
                                        
                                        <?php $i++;
                              if($i==($totRows+1)){ ?></tbody>
                              <tfoot><tr>
                                <td><b><?php echo $i; ?></b></td>
                                <td><b>Total</b></td>
                                <td><b> <?php echo sprintf('%01.2f', $grandRecived); ?></b></td>
                                <td><b> <?php echo sprintf('%01.2f', $grandSpent); ?></b></td>
                                <td><b> <?php echo sprintf('%01.2f', $grandBalance); ?></b></td>
                                <td></td>
                                </tr></tfoot>
  <?php
                              }
										} 
                    
                    ?>                                      
                                    
                                </table>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>
  <script>
$(document).ready(function() {
	
var defSt = '01/01/2018';
var defEnd = '01/01/2024';

        $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            ranges: {
                'All': [defSt, defEnd],
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                'Last 7 Days': [moment().subtract('days', 6), moment()],
                'Last 30 Days': [moment().subtract('days', 29), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
            },
            opens: 'left',
            buttonClasses: ['btn btn-default'],
            applyClass: 'btn-small btn-primary',
            cancelClass: 'btn-small',
            format: 'DD/MM/YYYY',
            separator: ' to ',
            locale: {
                applyLabel: 'Submit',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom Range',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: 1
            }
        },
        function(start, end) {
            if(start.format('DD/MM/YYYY')==defSt) {
                console.log("Callback has been called!");
                $('#reportrange span').html(''); 
                $('#stDt_upd').val('');
                $('#enDt_upd').val('');
                window.location = 'cashflow2.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'cashflow2.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );

        <?php if(isset($_GET['st']) && $_GET['st']!='') { ?>
          var d1 = '<?php echo $_GET['st']; ?>';
          var d2 = '<?php echo $_GET['en']; ?>';
          $('#reportrange span').html(d1 + ' - ' + d2);
          $("#reportrange").data().daterangepicker.startDate = moment(d1, datepicker.data().daterangepicker.format );
          $("#reportrange").data().daterangepicker.endDate = moment(d2, datepicker.data().daterangepicker.format );
          $("#reportrange").data().daterangepicker.updateCalendars();
        <?php }  ?>
});
					
	</script>
  <script>
function onAjax(id,ty,clName) {
					//var id = $(this).attr('data-id');
					//alert(id);
          <?php
          if(isset($_GET['st']) && $_GET['st']!=''){
          ?>
            var page = 'cron-cashflow2.php?tbl_id='+id+'&refresh=1&ajax=1&<?php echo $dt_q; ?>';
            var dt1 = '(<?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['st']))); ?> to <?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['en']))); ?>)';
           // var dt2 = '<?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['en']))); ?>';
          <?php
          } else {
          ?>
            var page = 'ajax-cashflow2.php';
            var dt1 ='';
          <?php
          }
          ?>

					$('.modal-body').html('loading...');
          $('#myModalLabel').html(clName+' '+dt1);
					$.ajax({
              type: 'POST',
              url: page,
              data:{id: id, ty: ty},
              success: function(data) {
                //alert(data);
               // $('#myModalLabel').html(clName);
                $('.modal-body').html(data);
              },
              error:function(err){
                alert("error"+JSON.stringify(err));
              }
					});
					
};
</script>
