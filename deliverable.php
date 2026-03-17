<?php include 'header.php'; 
//d($_POST); exit;
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

$dt_q ='';
if(isset($_GET['st'])) { 
  $stDt =  $_GET['st']; 
  $enDt =  $_GET['en']; 
  $filter = 'yes';
  $dtRange = $_GET['st'].' - '.$_GET['en']; 
  $dtRange1 = str_replace('/', '-', $_GET['st']); $dtRange2 = str_replace('/', '-', $_GET['en']);
  $urlParam = '?st='.$stDt.'&en='.$enDt;
} else { 
  $stDt = date('d/m/Y');
  $enDt = date('d/m/Y'); 
  $filter = 'no';

  $d = new DateTime('first day of this month');
  //$d = new DateTime('first day of this month');
  //echo $d->format('d/m/Y');

  $stDt = $d->format('d/m/Y');
  $enDt = date('d/m/Y');
  
  $dtRange = 'this month';
  //$dtRange1 = '-29 days'; $dtRange2 = '0 days';
  $dtRange1 = $d->format('Y-m-d'); 
  $dtRange2 = '0 days';
  $urlParam = '';
} 

Auth();
$pgHeadline = 'Deliverables - Dashboard';
$pgID = 8;
$err =''; 
$nthWeek = $cur_month = $cur_year = '';

$extQ = "WHERE tbl_id!=0 ";

if(isset($_POST['submit_comit'])) {
  //d($_POST); exit;

  $cirRes = mysqli_query($conn, "select * from deliverable_comit_data WHERE cId='".$_POST['client_id']."' AND month='".$_POST['month']."' AND year='".$_POST['year']."'");

  if(mysqli_num_rows($cirRes)==0) {
       $cirSql = "INSERT INTO deliverable_comit_data (cId, month, year, comit, created, updated) VALUES ('".mysqli_real_escape_string($conn, $_POST['client_id'])."', '".mysqli_real_escape_string($conn, $_POST['month'])."', '".mysqli_real_escape_string($conn, $_POST['year'])."', '".mysqli_real_escape_string($conn, serialize($_POST['comit']))."', now(), now());"; 
       mysqli_query($conn, $cirSql) or die(mysqli_error());
       $lastId = mysqli_insert_id($conn);
  } else {
       $cirSql = "UPDATE deliverable_comit_data SET comit='".mysqli_real_escape_string($conn, serialize($_POST['comit']))."', updated=now() WHERE cId='".$_POST['client_id']."' AND month='".$_POST['month']."' AND year='".$_POST['year']."'";
       mysqli_query($conn, $cirSql) or die(mysqli_error());
  }
  //exit;
  $_SESSION['suc'] = 'Successfully Updated!';	
  echo "<script>window.location = 'deliverable.php';</script>";
  exit();
}
$extQ2 ='';
if(isset($_POST['submit_filter'])) {
  $week = $_POST['week'];
  $month = $_POST['month'];
  $year = $_POST['year'];

  if($week!=''){
    $extQ .= "AND week='".$week."' ";
    $nthWeek = $week;
  }
  if($month!=''){
    $extQ .= "AND month='".$month."' ";
    $cur_month = $month;
  }
  if($year!=''){
    $extQ .= "AND year='".$year."' ";
    $cur_year = $year;
  }
} else {
  $today = date('d', time());
  $nthWeek = 1 + floor(($today - 1) / 7);
  $cur_month = date('m');
  $cur_year = date('Y');
  $extQ .= "AND (STR_TO_DATE(date, '%d-%m-%Y') BETWEEN STR_TO_DATE('".$stDt."', '%d/%m/%Y') AND STR_TO_DATE('".$enDt."', '%d/%m/%Y'))";

  $datefilter_split = explode('/', $stDt);
  $extQ2 .= " month ='".$datefilter_split[1]."' AND year='".$datefilter_split[2]."'"; 
}
if(isset($_POST['admin_submit'])) {
  $admin_pass = $_POST['admin_pass'];
  if($admin_pass=='byt@123'){
    $_SESSION['admin'] = 1; 
    $_SESSION['suc'] = 'Successfully Logged In!';	
    echo "<script>window.location = 'deliverable.php';</script>";
    exit();
  } else {
    $_SESSION['err'] = 'Password Incorrect!';	
    echo "<script>window.location = 'deliverable.php';</script>";
    exit();
  }
}
if(isset($_GET['admin_logout'])) {
    unset($_SESSION["admin"]); 
    $_SESSION['suc'] = 'Successfully Logged out!';	
    echo "<script>window.location = 'deliverable.php';</script>";
    exit();
}
if(isset($_POST['submit'])) {
  //d($_POST); //exit;

  
  $deliver = $_POST['deliver'];
  $adapt = $_POST['adapt'];
  $designer = $_POST['designer'];
  $effort = $_POST['effort'];
  $label_id = $_POST['label_id'];
  $date_split = explode('-', $_POST['date']);

  $cirRes = mysqli_query($conn, "select * from deliverable_reports WHERE client_id='".$_POST['client_id']."'  AND date='".$_POST['date']."' AND label_id='". $label_id."'");

  if(mysqli_num_rows($cirRes)==0) {
       $cirSql = "INSERT INTO deliverable_reports (client_id, manager_id, label_id, designer_id, designer_id2, delivered, adapt, scope, reasons, date, month, year, created, updated) VALUES ('".mysqli_real_escape_string($conn, $_POST['client_id'])."', '".mysqli_real_escape_string($conn, $_POST['manager'])."', '".mysqli_real_escape_string($conn, $label_id)."', '".mysqli_real_escape_string($conn, serialize($designer))."', '".mysqli_real_escape_string($conn, serialize($effort))."', '".mysqli_real_escape_string($conn, $deliver)."',  '".mysqli_real_escape_string($conn, $adapt)."', '".mysqli_real_escape_string($conn, $_POST['scope'])."', '".mysqli_real_escape_string($conn, $_POST['reason'])."', '".mysqli_real_escape_string($conn, $_POST['date'])."', '".mysqli_real_escape_string($conn, $date_split[1])."', '".mysqli_real_escape_string($conn, $date_split[2])."', now(), now());"; 
       mysqli_query($conn, $cirSql) or die(mysqli_error());
       $lastId = mysqli_insert_id($conn);
  } else {
       $cirSql = "UPDATE deliverable_reports SET manager_id='".mysqli_real_escape_string($conn, $_POST['manager'])."', label_id='".mysqli_real_escape_string($conn,$label_id)."', designer_id='".mysqli_real_escape_string($conn, serialize($designer))."', designer_id2='".mysqli_real_escape_string($conn, serialize($effort))."', delivered='".mysqli_real_escape_string($conn,  $deliver)."', adapt='".mysqli_real_escape_string($conn,  $adapt)."', scope='".mysqli_real_escape_string($conn, $_POST['scope'])."', reasons='".mysqli_real_escape_string($conn, $_POST['reason'])."', client_id='".mysqli_real_escape_string($conn, $_POST['client_id'])."', date='".mysqli_real_escape_string($conn, $_POST['date'])."', month='".mysqli_real_escape_string($conn, $date_split[1])."', year='".mysqli_real_escape_string($conn, $date_split[2])."',  updated=now() WHERE client_id='".$_POST['client_id']."'  AND date='".$_POST['date']."' AND label_id='". $label_id."'";
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


include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " cashflow2 WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>
<style>
.edit1 { display: none; }
.blue { cursor:pointer; float: right; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.change-message {
    display: none;
}
th {text-align: center;}
.dropdown, .dropup {
    position: relative;
    display: inline-block;
}
.fa { font-size: 15px; }
#custom-pos {
  position: relative;
  right: -70px;
}
.dropdown-menu {
   left: auto !important;
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
                      <li> &nbsp;</li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="deliverable-extra.php"  class="btn btn-success btn-sm">Manage Clients / SM / Designer / Labels</a>                        
                      </div> 
                      </li>
                      <li> &nbsp;</li>   
                      <li>
                        <div class="btn-group  btn-group-sm">
                          <?php if(isset($_SESSION['admin'])) { ?>
                        	  <a href="deliverable.php?admin_logout=1" class="btn btn-primary btn-sm">Admin Logout</a>                        
                          <?php } else { ?>
                            <a onClick="adminLogin();" data-id="1" data-toggle="modal" class="btn btn-primary btn-sm" data-target=".bs-example-modal-lg">Admin Login</a>                        
                          <?php } ?>
                      </div> 
                      </li>
                      
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                      include 'alert.php';
                      /*
                        $today = date('d', time());
                        $nthWeek = 1 + floor(($today - 1) / 7);
                        $cur_month = date('n');
                        $cur_year = date('Y'); */

                        $weeks_filter = array('1'=>'1st week','2'=>'2nd week','3'=>'3rd week','4'=>'4th week','5'=>'5th week');
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

                        //echo "SELECT client_id, manager_id, designer_id, committed, delivered, label_id FROM deliverable_reports $extQ"; 
                        $sql_report=mysqli_query($conn, "SELECT client_id, manager_id, designer_id, committed, delivered, label_id FROM deliverable_reports $extQ");
					              while($row_rep=mysqli_fetch_assoc($sql_report)) { 
                          $row_rep['designer_id'] = unserialize($row_rep['designer_id']);
                          //$row_rep['committed'] = $row_rep['committed'];
                          //$row_rep['delivered'] = $row_rep['delivered'];

                          $data[$row_rep['client_id']][] = $row_rep; 
                          $comit_data[$row_rep['client_id']][$row_rep['label_id']][] = $row_rep['committed'];
                          $deli_data[$row_rep['client_id']][$row_rep['label_id']][] = $row_rep['delivered'];
                          
                          $comit_data_manager[$row_rep['manager_id']][$row_rep['label_id']][] = $row_rep['committed'];
                          $deli_data_manager[$row_rep['manager_id']][$row_rep['label_id']][] = $row_rep['delivered'];
                          //d($row_rep['designer_id']); exit;
                          foreach($row_rep['designer_id'] as $k => $v){
                            if($v!=''){
                              //$comit_data_designers[$k][$row_rep['label_id']][] = $row_rep['committed'];
                              $deli_data_designers[$k][$row_rep['label_id']][] = $v;
                            }
                          }

                          $delsigner_rep[] = $row_rep['designer_id'];
                        }
                        //d($deli_data_manager);
                        //echo "SELECT cId,month,year,comit FROM deliverable_comit_data WHERE $extQ2"; 
                        $data_comit = array();
                        $sql_report=mysqli_query($conn, "SELECT cId,month,year,comit FROM deliverable_comit_data WHERE $extQ2");
					              while($row_rep=mysqli_fetch_assoc($sql_report)) { 
                          //$row_rep['comit'] = unserialize($row_rep['comit']);
                          $data_comit[$row_rep['cId']] = unserialize($row_rep['comit']);
                        }
                        //d($data_comit);
                        //echo array_sum(array_column($comit_data[5], 1)); 
                        //d($clients);  d($designers);
                        $sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
                        $i = (($page-1) * $per_page ) + 1;
                        $z=1; 
								?>
                <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;</li>
                      <li>
                        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; margin-right: 15px;">
                                                  <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
                                                  <span><?php echo $stDt.' - '.$enDt; ?></span> <b class="caret"></b>
                        </div>
                      </li>
                      <li> &nbsp;</li>   
                      <li>
                      <a href="deliverable.php" class="btn btn-default">Reset</a>
                      </div> 
                      </li>
                    </ul>    

                                <div class="clearfix"></div><br>
                                <h4 class="text-center">Clients - Report</h4>
                                <div class="col-md-12">
                                
                                <table  class="table table-hover table-striped table-bordered table1" style="width: 100%;">
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
                                        <td><b><?php echo $cv; ?></b> <a onClick="onAjaxAdmin(<?php echo $ck; ?>, '<?php echo $cv; ?>', '<?php echo $cur_month; ?>','<?php echo $cur_year; ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl edit1" data-target=".bs-example-modal-lg"><i class="fa fa-pencil-square-o" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a></td>
                                        <?php
                                          foreach($labels as $lk => $lv) {
                                            $com_v = $del_v = 0; 
                                            //if(isset($comit_data[$ck][$lk])) { $com_v = array_sum($comit_data[$ck][$lk]); }
                                            if(isset($data_comit[$ck][$lk])) { $com_v = $data_comit[$ck][$lk]; }
                                            if(isset($deli_data[$ck][$lk])) { $del_v = array_sum($deli_data[$ck][$lk]); } 
                                          ?>
                                          <td>
                                            <?php if($com_v!=0) { echo $com_v; } else { echo ''; } ?>
                                            <!--<a onClick="onAjax(<?php echo $ck; ?>, <?php echo $lk; ?>, '<?php echo $cv; ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl edit1" data-target=".bs-example-modal-lg"><i class="fa fa-pencil-square-o" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a>-->
                                          </td>
                                          <td>
                                            <?php if($del_v!=0) { echo $del_v; } else { echo ''; } ?>
                                            <a onClick="onAjax(<?php echo $ck; ?>, <?php echo $lk; ?>, '<?php echo $cv; ?>', '<?php echo date('d-m-Y'); ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl edit1" data-target=".bs-example-modal-lg"><i class="fa fa-pencil-square-o" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a>
                                          </td>
                                          <?php
                                          }   $z++;
                                          ?>
                                          </tr>
                                          <?php
                                         }
                                          ?>
                                    </tbody>
                                    
                                </table>

                                <div class="clearfix"></div><br>
                                <h4 class="text-center">SM Team - Report</h4>

                                <table class="table table-hover table-striped table-bordered" style="width: 100%;">
                                    <thead>   
                                      <tr>
                                        <th rowspan="2">SNo</th>
                                        <th rowspan="2">SM Team</th>
                                        <?php
                                        foreach($labels as $k => $v) {
                                        ?>
                                        <th><?php echo $v; ?></th>
                                        <?php
                                       } ?>	
                                       </tr>   
                                       <tr>  
                                       <?php
                                        foreach($labels as $k => $v) {
                                        ?>
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
                                        <td><b><?php echo $cv; ?></b></td>
                                        <?php
                                          foreach($labels as $lk => $lv) {
                                            $com_v = $del_v = 0; 
                                            //if(isset($comit_data_manager[$ck][$lk])) { $com_v = array_sum($comit_data_manager[$ck][$lk]); }
                                            if(isset($deli_data_manager[$ck][$lk])) { $del_v = array_sum($deli_data_manager[$ck][$lk]); } 
                                          ?>
                                          <td><?php if($del_v!=0) { echo $del_v; } else { echo ''; } ?></td>
                                          <?php
                                          }   $z++;
                                          ?>
                                          </tr>
                                          <?php
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
                                        <th>SNo</th>
                                        <th>Designer</th>
                                        <?php
                                        foreach($labels as $k => $v) {
                                        ?>
                                        <th><?php echo $v; ?></th>
                                        <?php
                                       } ?>	
                                       </tr>   
                                    </tr>  
                                    </thead>
                                    <tbody>
                                    <?php $z=1;
                                        foreach($designers as $ck => $cv) {
                                        ?>
                                        <tr>
                                        <td><?php echo $z; ?></td>
                                        <td><b><?php echo $cv; ?></b></td>
                                        <?php
                                          foreach($labels as $lk => $lv) {
                                            $com_v = $del_v = 0; 
                                            //if(isset($comit_data_designers[$ck])) { $com_v = array_sum($comit_data_designers[$ck][$lk]); }
                                            if(isset($deli_data_designers[$ck][$lk])) { $del_v = array_sum($deli_data_designers[$ck][$lk]); }
                                          ?>
                                          
                                          <td><?php if($del_v!=0) { echo $del_v; } else { echo ''; } ?></td>
                                          <?php
                                          }   $z++;
                                          ?>
                                          </tr>
                                          <?php
                                         }
                                          ?>
                                        </tr>
                                    </tbody>
                                    
                                </table>
                                
								<div id="pagDiv"><?php // echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>

<script>

$('.table1 td').hover(function(){
    $('.edit1').hide();
    //var theText = $(this).text();
    //$(this).html('<input type="text" value="'+theText+'"/>');
    $(this).find('.edit1').show();
});

var $form;
var origForm;
$(document).on('change input', "form :input", function (evt) {
    //$('form :input').on('change input', function() {
    $('.change-message').toggle($form.serialize() !== origForm);
});

$('.number_only').bind('keyup paste', function(){
        this.value = this.value.replace(/[^0-9]/g, '');
});

function onAjax(id, lab_id, clName, date) {
					//var id = $(this).attr('data-id');
					//alert(id+','+clName);
          $('#myModalLabel').html(clName);
					$('.modal-body').html('<br><center><img src="images/ajax-loader.gif" /></center><br>'); //return false;
					$.ajax({
						type: 'POST',
						url: 'deliverable-edit.php',
						data:{id: id, lab_id: lab_id, date: date, clName: clName},
						success: function(data) {
							//alert(data);
              

						  $('#myModalLabel').html(clName);
						  $('.modal-body').html(data);

              $form = $('form'),
              origForm = $form.serialize();
              var date_input=$('.date'); //our date input has the name "date"
              var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
              date_input.datepicker({
                format: 'dd-mm-yyyy',
                container: '.modal-content',
                todayHighlight: true,
                autoclose: true
              }).on('change', function(e){
                  $(this).datepicker('hide');
              })
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
					
};
function onAjaxAdmin(id, clName, mon, yr) {
					//var id = $(this).attr('data-id');
					//alert(id+','+clName);
          $('#myModalLabel').html(clName);
					$('.modal-body').html('<br><center><img src="images/ajax-loader.gif" /></center><br>'); //return false;
					$.ajax({
						type: 'POST',
						url: 'deliverable-edit-admin.php',
						data:{id: id, mon: mon, yr: yr, clName: clName},
						success: function(data) {
							//alert(data);
              

						  $('#myModalLabel').html(clName);
						  $('.modal-body').html(data);

              $form = $('form'),
              origForm = $form.serialize();
              var date_input=$('.date'); //our date input has the name "date"
              var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
              date_input.datepicker({
                format: 'mm-yyyy',
                container: '.modal-content',
                todayHighlight: true,
                autoclose: true,
                startView: "months", 
                minViewMode: "months"
              })
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
					
};
function changeMon(id, monYr, clName) {
  var mon_Yr = monYr.value.split("-");
  //alert(id+', '+clName+', '+mon_Yr[0]);
  onAjaxAdmin(id, clName, mon_Yr[0], mon_Yr[1]);
  
}
function changeDt(id, lab_id, clName, dt) {
  var dt = date.value;
  //alert(id+', '+clName+', '+mon_Yr[0]);
  onAjax(id, lab_id, clName, dt);
  
}
function adminLogin() {
          $('#myModalLabel').html('Admin Login');
					$('.modal-body').html('<form method="post" style="width: 50%;"><label>Password:</lable><br><input type="password" class="form-control col-md-4" name="admin_pass" required /><br><br><input type="submit" class="btn btn-primary btn-sm" name="admin_submit" value="Login" /></form>'); 
          return false;
};

/*
$(".table").dataTable( {
  "sScrollX": "100%",
    "sScrollXInner": "110%",
} );*/
	</script>
  <?php
  
  if(isset($_GET['st']) && $_GET['st']!='') {
    $_SESSION['st'] = $_GET['st'];
	$_SESSION['en'] = $_GET['en'];
} else {
    $start = date('d/m/Y',strtotime('first day of this month'));
	$end = date('d/m/Y');
    $_SESSION['st'] = $start;
    $_SESSION['en'] = $end;
}
?>
  <script>


$(document).ready(function() {
	
	var defSt = '01/01/2018';
  var defEnd = '01/01/2024';
  var d1 = '<?php echo $_SESSION['st']; ?>';
  var d2 = '<?php echo $_SESSION['en']; ?>';
  var start = moment(d1.split(' ')[0].split("/").reverse().join("-"));
  var end = moment(d2.split(' ')[0].split("/").reverse().join("-"));
  $('#reportrange span').html(d1 + ' - ' + d2);
	
  $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            startDate: start,
            endDate: end,
            ranges: {
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
                window.location = 'deliverable.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'deliverable.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );

 });
</script> 