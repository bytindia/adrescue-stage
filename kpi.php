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
$pgHeadline = 'KPI';
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
	mysqli_query($conn, "UPDATE kpi SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'kpi.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " kpi WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ";

$acc_type = array(1=>'Real Estate', 2=>'Coaching', 3=>'Education', 4=>'Ecommerce', 5=>'Others');

                                        $label = array(
                                            1 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'SV',	'CSV', 'Sales'),
                                            2 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Attendees', 'Sale', 'CPS','ROAS'),
                                            3 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'Applications',	'Admission'),
                                            4 => array('Client', 'Spend', 'Budget', 'Sale', 'CPS', 'ATC',	'CATC',	'Purchase Value','ROAS'),
                                            5 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'SV',	'CSV', 'Sales')
                                        );
?>
<style>
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
input[type="text"] {
    width: 100px;
}
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
                        	<a href="loading.php?pg=kpi-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
                      </div>    
                      </li>
                      <li> &nbsp;</li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=cron-kpi.php?refresh=1"  class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to Email Cashflow report?');" ><i class="fa fa-refresh"></i> Refresh All</a>                        
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
                      $sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by acc_cat asc");
                      $i = (($page-1) * $per_page ) + 1;
                      $totRows = mysqli_num_rows($sqlRev);
                      //$grandRecived = $grandSpent = $grandBalance = 0;
                      $acc_cat = 'no';
                      while($sqlROW=mysqli_fetch_array($sqlRev))
                      {
                            //d($sqlROW);
                            $metrics = unserialize($sqlROW['metrics']);
                            //
                            //d($metrics);
                            //echo $acc_cat.'-'.$sqlROW["acc_cat"];
                            if($acc_cat!='no' && $acc_cat != $sqlROW["acc_cat"]) {
                                echo '</tbody></table> '; 
                            }

                            if($acc_cat != $sqlROW["acc_cat"]) { //echo 33; 
                               
                                echo '<h4 class="x_title" style="text-align: center;">'.$acc_type[$sqlROW['acc_cat']].'</h4> <div class="clearfix"></div>';
                                echo '<table class="table table-hover table-striped table-bordered"><thead>';
                                foreach($label[$sqlROW['acc_cat']] as $k => $v){ 
                                echo '<th>'.$v.'</th>';
                                }
                                echo '<th>Edit</th>';
                                echo '</thead><tbody>';
                            }
                            
                            echo '<tr data-id="'.$sqlROW['tbl_id'].'"><td data-field="name">'.$sqlROW["client"].' </td>';
                            echo '<td data-field="spend">'.$sqlROW['spend'].' </td>';
                            $in=1;
                            foreach($metrics as $k => $v){ 
                            echo '<td data-field="metrics_'.$in.'">'.$v.'</td>';
                            $in++;
                            }
                            echo '<td><a class="btn btn-md edit" title="Edit"><i class="fa fa-pencil" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a></td>';
                            echo '</tr>';
                            $acc_cat = $sqlROW["acc_cat"];
                      }
                      echo '</tbody></table> ';

								     ?>
                             <!--  
                               <table  class="table table-hover table-striped table-bordered">
          <thead>
            <tr>
              <th>Name</th>
              <th>Birthday</th>
              <th>Age</th>
              <th>Sex</th>
              <th>Edit</th>
            </tr>
          </thead>
          <tbody>
            <tr data-id="1">
              <td data-field="name">Dave Gamache</td>
              <td data-field="birthday">May 19, 2015</td>
              <td data-field="metrics[]">26</td>
              <td data-field="metrics[]">Male</td>
              <td>
                <a class="button button-small edit" title="Edit">
                  <i class="fa fa-pencil"></i>
                </a>
              </td>
            </tr>
            <tr data-id="2">
              <td data-field="name">Dwayne Johnson</td>
              <td data-field="birthday">May 19, 2015</td>
              <td data-field="metrics[]">42</td>
              <td data-field="metrics[]">Male</td>
              <td>
                <a class="button button-small edit" title="Edit">
                  <i class="fa fa-pencil"></i>
                </a>
              </td>
            </tr>
            <tr data-id="3">
              <td data-field="name">Halyna Nadia</td>
              <td data-field="birthday">May 25, 2015</td>
              <td data-field="metrics[1]">22</td>
              <td data-field="metrics[2]">Female</td>
              <td>
                <a class="button button-small edit" title="Edit">
                  <i class="fa fa-pencil"></i>
                </a>
              </td>
            </tr>
          </tbody>
        </table> -->
                
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
                window.location = 'kpi.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'kpi.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
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
 
  <script src="build/js/table-edits.min.js"></script>
  <script>
   // var jq = jQuery.noConflict();
    $(function() {
      var pickers = {};

      $('table tr').editable({
        
        edit: function(values) { 
          $(".fa-times, .fa-check").remove();
          $("td input").addClass('form-control');
          $(".edit i", this).removeClass('fa-pencil').addClass('fa-save').attr('title', 'Save');
        },
        save: function(values) {
         
          $(".edit i", this).removeClass('fa-save').addClass('fa-pencil').attr('title', 'Edit');
            var id = $(this).data('id');
            
            $.ajax({
              type: 'POST',
              url: 'ajax-kpi-save.php',
              data:{id: id, values: values},
              //success: function(data) {
                success: (data) => {
                //alert(data);
                $(".edit", this).append(' <i class="fa fa-check" style="color:#33e033;font-size: 20px; margin: 0px 0 0 20px;"></i>');
               // $('#myModalLabel').html(clName);
                //$('.modal-body').html(data);
              },
              error: (err) => {
                $(".edit", this).append(' <i class="fa fa-times" style="color:red; font-size: 20px; margin: 0px 0 0 20px;"></i>');
                alert("error"+JSON.stringify(err));
              }
					});
          if (this in pickers) {
            pickers[this].destroy();
            delete pickers[this];
          }
        },
        cancel: function(values) {
          $(".edit i", this).removeClass('fa-save').addClass('fa-pencil').attr('title', 'Edit');

          if (this in pickers) {
            pickers[this].destroy();
            delete pickers[this];
          }
        }
      });
    });
  </script>
  <script>
function onAjax(id,ty,clName) {
					//var id = $(this).attr('data-id');
					//alert(id);
          <?php
          if(isset($_GET['st']) && $_GET['st']!=''){
          ?>
            var page = 'cron-kpi.php?tbl_id='+id+'&refresh=1&ajax=1&<?php echo $dt_q; ?>';
            var dt1 = '(<?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['st']))); ?> to <?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['en']))); ?>)';
           // var dt2 = '<?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['en']))); ?>';
          <?php
          } else {
          ?>
            var page = 'ajax-kpi.php';
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
