<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
<title>Altis - Status Vs Campaign wise report</title>
<!-- Bootstrap -->
<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<!-- bootstrap-daterangepicker -->
<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
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



//d($data_g); exit;
$cqV = '';

//d($new);
//exit;
if(isset($_POST['submit'])) {
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, "r");
    $c = 0;
    
    $allowed =  array('csv',);
    $filename = $_FILES['file']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if(!in_array($ext,$allowed) ) {
        $_SESSION['err'] = 'Please upload CSV file format!';	
        echo "<script>window.location = 'altis-upload.php';</script>";
        exit();
    }
    $j = 1; 
    while(($filesop = fgetcsv($handle, 10000, ",")) !== false)
	{
			if($c==0) {
				
				$csvFields[] = $filesop;
			}
			if($c>=1) {
                if(trim($filesop[2])=='') { $filesop[2]='Others'; }
                $status_col = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($filesop[2]));
                if(strlen(trim($filesop[0]))>=10) {
                    
                    $csvData2[$status_col][] = $filesop[0]; 
                    $csvData[] = $filesop;
                }
			}
			$j ++; 
			$c = $c + 1;
	}
    $calQty = $phNos = $calQty_uni = $phNos_uni = array();
    if(count($csvData)>0)
	{
			//$ids = array_column($csvData, 3);
			$calQty = array_column($csvData,2);
            $phNos = array_column($csvData,0);
    }
    if(count($phNos)>0)
	{
        $calQty_uni = array_unique($calQty); 
        $calQty_uni = array_values($calQty_uni); 

        $phNos_uni = array_unique($phNos); 
        $phNos_uni = array_values($phNos_uni); 

        $phNos_comma = "'".implode("','",$phNos_uni)."'"; 
    }
    //d($csvData2); exit;
    /*
    //d($phNos_comma); exit;
    $extQry = 'AND phone IN('.$phNos_comma.')';
    echo $sql_g = "SELECT COUNT(*) as tot,source,sub_source,phone,campaign,cq_id FROM `sales_ninja` WHERE cId=12 $extQry AND (source LIKE '%face%' || source LIKE '%google%') GROUP BY  CASE WHEN campaign IS NULL OR campaign = '' THEN '' ELSE campaign END,CASE WHEN cq_id IS NULL OR cq_id = '' THEN '' ELSE cq_id END   order by cq_id desc"; exit;
    $result_g = $conn->query($sql_g);
    $result_g = $conn->query($sql_g);
    while($row_g = $result_g->fetch_assoc()) {
        if($row_g['cq_id']!='' && isset($calQty[$row_g['cq_id']])) {
            $row_g['cq_txt'] = $calQty[$row_g['cq_id']];
            
        } else {
            $row_g['cq_txt'] = 'Others';
            $row_g['cq_id'] = 0;
        }
        
        $row_g['ss_string'] = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($row_g['campaign']));
        $campLeadTot[$row_g['cq_id']][$row_g['ss_string']] = $row_g['tot'];
        $row_g[$row_g['cq_id']][$row_g['ss_string']] = $row_g['tot'];
        $data_g[] = $row_g;
    }*/

}
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
                    
                    <h4>Altis - Status Vs Campaign wise report (CSV)</h4>
</div>
<div class="clearfix"></div>
<br />
                    
        <?php
        include 'alert.php';
        if(isset($_POST['submit'])) {
            ?>
             <?php
                               // $camp_name = array_unique(array_column($data_g, 'campaign'));
                               foreach($calQty_uni as $k1 => $v1) {
                                  $status_col = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($v1));
                                  $phNos_comma = "'".implode("','",$csvData2[$status_col])."'"; 
                                    //$camp_name_ss = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($val_c));
                                   $extQry = 'AND phone IN('.$phNos_comma.')';
                                   
                                    $sql_g = "SELECT COUNT(*) as tot,campaign,cq_id FROM `sales_ninja` WHERE cId=12 $extQry AND (source LIKE '%face%' || source LIKE '%google%') GROUP BY  CASE WHEN campaign IS NULL OR campaign = '' THEN '' ELSE campaign END order by cq_id desc"; //exit;
                                    
                                    $result_g = $conn->query($sql_g);
                                    while($row_g = $result_g->fetch_assoc()) {
                                        if($row_g['campaign']=='') { $row_g['campaign']= 'Others'; }
                                        $camp_name_ss = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($row_g['campaign']));
                                        $data_g[$status_col][$camp_name_ss] = $row_g['tot'];
                                        $campNames[] = $row_g['campaign'];
                                        //$data_g[] = $row_g;

                                        //$row_g['ss_string'] = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($row_g['campaign']));
                                        //$campLeadTot[$row_g['cq_id']][$row_g['ss_string']] = $row_g['tot'];
                                        //$row_g[$row_g['cq_id']][$row_g['ss_string']] = $row_g['tot'];
                                        //$data_g[] = $row_g;
                                    }
                                    ?>
                                       
                                    <?php
                                     }
                                    // $campNames = array_unique($campNames);
                                    //d($data_g); exit;
                               // }
                            ?>

<div style="overflow-x:auto;"> 
<table  class="table table-hover table-striped table-bordered dataTable no-footer" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <?php
                                            foreach($calQty_uni as $k1 => $v1) {
                                                ?>
                                                        <th><?php echo $v1; ?></th>
                                                <?php
                                            }
                                     ?>
                                </tr>
                            </thead>
                           <?php
                               // $camp_name = array_unique(array_column($data_g, 'campaign'));
                               $campNames = array_unique($campNames);
                               //d()
                                foreach($campNames as $key_c => $val_c) {
                                    $camp_name_ss = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($val_c));
                                    ?>
                                         <tr>
                                            <td><?php echo $val_c; ?></td>
                                            <?php
                                           foreach($calQty_uni as $k1 => $v1) {
                                            $v1 = preg_replace('/[^a-zA-Z0-9_.]/', '_', trim($v1));
                                            if(isset($data_g[$v1][$camp_name_ss])) { $countV=$data_g[$v1][$camp_name_ss]; } else { $countV=''; }
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
<?php
       } else {
        ?>
            <form id="demo-form2" data-parsley-validate class="form-horizontal form-label-left" enctype="multipart/form-data" method="post" action="">

            <div class="form-group">
            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Upload CSV file<span class="required">*</span>
            </label>                        
                <div class="col-md-6 col-sm-6 col-xs-12">                                                    
                <input type="file" class="form-control has-feedback-left" name="file" required="required"  />
                
                <small><b>csv format:</b> phone,email,status</small>
            </div>
            </div>

            <div class="ln_solid"></div>
            <div class="form-group">
            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                <a href="loading.php?pg=leads.php" class="btn btn-primary" type="button">Cancel</a>
                <button type="submit" name="submit" class="btn btn-success">Submit</button>
            </div>
            </div>

            </form>
        <?php
       }
            ?>
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
                window.location = 'altis-utm-fb2.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'altis-utm-fb2.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
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