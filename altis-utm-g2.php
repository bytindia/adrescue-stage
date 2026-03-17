<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

<!-- Bootstrap -->
<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<!-- bootstrap-daterangepicker -->
<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

<title>Altis - Status Vs Campaign wise report</title>

<style>
body, html {
    width: 95%; 
    margin: 10 auto;
}
ul.nav.navbar-right.panel_toolbox {
    float: right;
    margin-bottom: 20px;
}
footer {
    margin-top: 25px;
    float: right;
    font-size: 14px;
}
</style>

<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
//echo $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

include '/home/digitalb2k/public_html/salesninja.app/altis/db.php';
$calQty = $data_g = array();
$sql = "SELECT * FROM callqty_text where cId =12 AND admin_del=0";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $calQty[$row['id']] = $row['txt'];
}

$dt_q = $extQry = '';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
  $extQry .= " AND created BETWEEN '".date('Y-m-d H:i:s',strtotime(strtr($_GET['st'], '/', '-').' 00:00:00'))."' AND  '".date('Y-m-d H:i:s',strtotime(strtr($_GET['en'], '/', '-').' 23:59:59'))."'";
}

$campLeadTot = array();

$sql_g = "SELECT COUNT(*) as tot,source,sub_source,cq_id FROM `sales_ninja` WHERE cId=12 $extQry AND source LIKE '%google%' GROUP BY  CASE WHEN sub_source IS NULL OR sub_source = '' THEN '' ELSE sub_source END,CASE WHEN cq_id IS NULL OR cq_id = '' THEN '' ELSE cq_id END order by cq_id desc";
$result_g = $conn->query($sql_g);
while($row_g = $result_g->fetch_assoc()) {
    if($row_g['cq_id']!='' && isset($calQty[$row_g['cq_id']])) {
         $row_g['cq_txt'] = $calQty[$row_g['cq_id']];
         
    } else {
         $row_g['cq_txt'] = 'Others';
         $row_g['cq_id'] = 0;
    }
    
    $row_g['ss_string'] = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($row_g['sub_source']));
    $campLeadTot[$row_g['cq_id']][$row_g['ss_string']] = $row_g['tot'];
    $row_g[$row_g['cq_id']][$row_g['ss_string']] = $row_g['tot'];
    $data_g[] = $row_g;
}

//d($campLeadTot); exit;
$cqV = '';
$filterBy = 66;
//$new = array_unique(array_column($data_g, 'sub_source'));
//$new = array_unique(array_map(function ($i) { return $i['sub_source']; }, $data_g));
//$new = array_unique(array_column($data_g, 'sub_source'));
//$camp_name = array_unique(array_column($data_g, 'sub_source'));
//d($camp_name); exit;

?>
<div class="x_title">
                    
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp; </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=altis-utm-g2.php" class="btn btn-success btn-sm">Google</a> 
                      	</div>    
                      </li>
                      <li> &nbsp; </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        <a href="loading.php?pg=altis-utm-fb2.php" class="btn btn-primary btn-sm">Facebook</a> 
                      	</div>    
                      </li>   
                      <li> &nbsp; </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=altis-upload.php" class="btn btn-warning btn-sm">Upload</a> 
                      	</div>    
                      </li>
                      <li> &nbsp; </li>
                      <li>
                        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                        </div>     
                      </li>                       
                    </ul>                   
                    
                    <h4>Altis - Status Vs Campaign wise report (Google)</h4>
</div>

<div class="clearfix"></div>
            <?php 
           // $cqV = $value['cq_id'];
        //}
    
   // 
    ?>
    <div style="overflow-x:auto;"> 
    <table  class="table table-hover table-striped table-bordered dataTable no-footer" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <?php
                                            foreach($calQty as $k1 => $v1) {
                                                ?>
                                                        <th><?php echo $v1; ?></th>
                                                <?php
                                            }
                                     ?>
                                </tr>
                            </thead>
                            <?php
                                $camp_name = array_unique(array_column($data_g, 'sub_source'));
                                foreach($camp_name as $key_c => $val_c) {
                                    $camp_name_ss = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($val_c));
                                    ?>
                                         <tr>
                                            <td><?php echo $val_c; ?></td>
                                            <?php
                                            foreach($calQty as $k1 => $v1) {
                                                if(isset($campLeadTot[$k1][$camp_name_ss])) { $countV=$campLeadTot[$k1][$camp_name_ss]; } else { $countV=''; }
                                                ?>
                                                        <th><?php echo $countV; ?></th>
                                                <?php
                                            }
                                     ?>
                                        </tr>
                                    <?php
                                }
                            ?>
                        </table>
    </div>
<?php include 'footer.php'; ?>

<script>
    //$(document).ready(function () {
        $('table.table').dataTable({
	 		 "pageLength": 50,
			 "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
       dom: 'Bfrtip',
            buttons: [
                       
                        'csv', 
            ]
	} );
//});
</script>
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
                window.location = 'altis-utm-g2.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'altis-utm-g2.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
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