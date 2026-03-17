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
$pgHeadline = 'Deliverables - Dashboard';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])) {
  //d($_POST); //exit;

  $comit = $_POST['comit'];
  $deliver = $_POST['deliver'];
  $designer = $_POST['designer'];

  $cirRes = mysqli_query($conn, "select * from deliverable_reports WHERE client_id='".$_POST['client_id']."'  AND week='".$_POST['week']."' AND month='".$_POST['month']."' AND year='".$_POST['year']."'");

  if(mysqli_num_rows($cirRes)==0) {
       $cirSql = "INSERT INTO deliverable_reports (client_id, manager_id, designer_id, committed, delivered, scope, reasons, week, month, year, created, updated) VALUES ('".mysqli_real_escape_string($conn, $_POST['client_id'])."', '".mysqli_real_escape_string($conn, $_POST['manager'])."', '".mysqli_real_escape_string($conn, serialize($designer))."', '".mysqli_real_escape_string($conn, serialize($comit))."', '".mysqli_real_escape_string($conn, serialize($deliver))."',  '".mysqli_real_escape_string($conn, $_POST['scope'])."', '".mysqli_real_escape_string($conn, $_POST['reason'])."', '".mysqli_real_escape_string($conn, $_POST['week'])."', '".mysqli_real_escape_string($conn, $_POST['month'])."', '".mysqli_real_escape_string($conn, $_POST['year'])."',  now(), now());"; 
       mysqli_query($conn, $cirSql) or die(mysqli_error());
       $lastId = mysqli_insert_id($conn);
  } else {
       $cirSql = "UPDATE deliverable_reports SET manager_id='".mysqli_real_escape_string($conn, $_POST['manager'])."', designer_id='".mysqli_real_escape_string($conn, serialize($designer))."', committed='".mysqli_real_escape_string($conn,  serialize($comit))."', delivered='".mysqli_real_escape_string($conn,  serialize($deliver))."', scope='".mysqli_real_escape_string($conn, $_POST['scope'])."', reasons='".mysqli_real_escape_string($conn, $_POST['reason'])."', client_id='".mysqli_real_escape_string($conn, $_POST['client_id'])."', week='".mysqli_real_escape_string($conn, $_POST['week'])."', month='".mysqli_real_escape_string($conn, $_POST['month'])."', year='".mysqli_real_escape_string($conn, $_POST['year'])."', updated=now() WHERE  client_id='".$_POST['client_id']."'  AND week='".$_POST['week']."' AND month='".$_POST['month']."' AND year='".$_POST['year']."'";
       mysqli_query($conn, $cirSql) or die(mysqli_error());
  }
  //exit;
  $_SESSION['suc'] = 'Successfully Updated!';	
  echo "<script>window.location = 'deliverable.php';</script>";
  exit();

}
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
                        	<a href="deliverable-extra.php"  class="btn btn-success btn-sm">Manage Clients / SM / Designer</a>                        
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
                <div class="navbar-right row col-md-10">  
                                  <div class="col-md-2">  
                                      <select name="week" class="form-control" required>
                                          <option value="">Week (All)</option>
                                          <?php foreach($weeks_filter as $k => $v){  ?>
                                          <option value="<?php echo $k; ?>" <?php if($nthWeek==$k) { echo 'selected="selected"'; } ?>><?php echo $v; ?></option>
                                          <?php } ?>
                                      </select>
                                  </div>
                                  <div class="col-md-2">        
                                                      <select name="month" class="form-control" required>
                                                          <option value="">Month (All)</option>
                                                          <?php foreach($month_filter as $k => $v){  ?>
                                                          <option value="<?php echo $k; ?>" <?php if($cur_month==$k) { echo 'selected="selected"'; } ?>><?php echo $v; ?></option>
                                                          <?php } ?>
                                                      </select>
                                    </div>
                                  <div class="col-md-2">            
                                                      <select name="year" class="form-control" required>
                                                          <option value="">Year (All)</option>
                                                          <?php foreach($year_filter as $k => $v){  ?>
                                                          <option value="<?php echo $k; ?>" <?php if($cur_year==$k) { echo 'selected="selected"'; } ?>><?php echo $v; ?></option>
                                                          <?php } ?>
                                                      </select>
                                    
                                  </div>
                                  <div class="col-md-2">   
                                    <input type="submit" name="submit" value="Submit" class="btn btn-primary">
                                    <a href="deliverable.php" class="btn btn-default">Reset</a>
                                  </div>
                                  <div class="col-md-2">   
                                    
                                  </div>
                                </div>
                  </div>
                                <form method="post" action="">
                                <div class="clearfix"></div><br>
                                <h4 class="text-center">Clients - Deliverable Report</h4>
                                <div class="col-md-12">
                                
                                <table  class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        <th rowspan="2">SNo</th>
                                        <th rowspan="2">Clients</th>
                                        <?php
                                        foreach($labels as $k => $v) {
                                        ?>
                                        <th colspan="2"><?php echo $v; ?></th>
                                        <?php
                                       } ?>	
                                       </tr>   
                                       <tr>  
                                       <?php
                                        foreach($labels as $k => $v) {
                                        ?>
                                        <td>Commit.</td>
                                        <td>Deliver.</td>
                                        <?php
                                        } ?>	
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach($clients as $ck => $cv) {
                                        ?>
                                        <tr>
                                        <td><?php echo $z; ?></td>
                                        <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                        <?php
                                          foreach($labels as $lk => $lv) {
                                            $com_v = $del_v = 0; 
                                            if(isset($comit_data[$ck])) { $com_v = array_sum(array_column($comit_data[$ck], $lk)); }
                                            if(isset($deli_data[$ck])) { $del_v = array_sum(array_column($deli_data[$ck], $lk)); } 
                                          ?>
                                          <td><?php if($com_v!=0) { echo $com_v; } else { echo '-'; } ?></td>
                                          <td><?php if($del_v!=0) { echo $del_v; } else { echo '-'; } ?></td>
                                          <?php
                                          }   $z++;
                                         }
                                          ?>
                                        </tr>
                                    </tbody>
                                    
                                </table>

                                <div class="clearfix"></div><br>
                                <h4 class="text-center">SM Team - Deliverable Report</h4>

                                <table class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        <th rowspan="2">SNo</th>
                                        <th rowspan="2">SM Team</th>
                                        <?php
                                        foreach($labels as $k => $v) {
                                        ?>
                                        <th colspan="2"><?php echo $v; ?></th>
                                        <?php
                                       } ?>	
                                       </tr>   
                                       <tr>  
                                       <?php
                                        foreach($labels as $k => $v) {
                                        ?>
                                        <td>Commit.</td>
                                        <td>Deliver.</td>
                                        <?php
                                        } ?>	
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php $z=1;
                                        foreach($managers as $ck => $cv) {
                                        ?>
                                        <tr>
                                        <td><?php echo $z; ?></td>
                                        <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                        <?php
                                          foreach($labels as $lk => $lv) {
                                            $com_v = $del_v = 0; 
                                            if(isset($comit_data_manager[$ck])) { $com_v = array_sum(array_column($comit_data_manager[$ck], $lk)); }
                                            if(isset($deli_data_manager[$ck])) { $del_v = array_sum(array_column($deli_data_manager[$ck], $lk)); } 
                                          ?>
                                          <td><?php if($com_v!=0) { echo $com_v; } else { echo '-'; } ?></td>
                                          <td><?php if($del_v!=0) { echo $del_v; } else { echo '-'; } ?></td>
                                          <?php
                                          }   $z++;
                                         }
                                          ?>
                                        </tr>
                                    </tbody>
                                    
                                </table>

                                <div class="clearfix"></div><br>
                                <h4 class="text-center">Designers - Report</h4>

                                <table class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        <th rowspan="2">SNo</th>
                                        <th rowspan="2">Designer</th>
                                        <th rowspan="2">Visuals</th>
                                    </tr>  
                                    </thead>
                                    <tbody>
                                        <?php $z=1;
                                        foreach($designers as $ck => $cv) {
                                        ?>
                                        <tr>
                                        <td><?php echo $z; ?></td>
                                        <td><a href="#" onClick="onAjax(<?php echo $ck; ?>,'<?php echo $cv; ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $cv; ?></a></td>
                                        <?php
                                         // foreach($labels as $lk => $lv) {
                                            //$com_v = $del_v = 0; 
                                            $com_v = array_sum(array_column($delsigner_rep, $ck)); 
                                            
                                          ?>
                                          <td><?php if($com_v!=0) { echo $com_v; } else { echo '-'; } ?></td>
                                          <?php
                                             $z++;
                                         }
                                          ?>
                                        </tr>
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

var $form;
var origForm;
$(document).on('change input', "form :input", function (evt) {
    //$('form :input').on('change input', function() {
    $('.change-message').toggle($form.serialize() !== origForm);
});

$('.number_only').bind('keyup paste', function(){
        this.value = this.value.replace(/[^0-9]/g, '');
});

function onAjax(id, clName) {
					//var id = $(this).attr('data-id');
					//alert(id+','+clName);
          $('#myModalLabel').html(clName);
					$('.modal-body').html('<br><center><img src="images/ajax-loader.gif" /></center><br>'); //return false;
					$.ajax({
						type: 'POST',
						url: 'deliverable-edit.php',
						data:{id: id, wk:<?php echo $nthWeek; ?>, mon:<?php echo $cur_month; ?>, yr:<?php echo $cur_year; ?>},
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