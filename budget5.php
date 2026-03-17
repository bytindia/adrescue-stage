<?php include 'header.php'; 
$budget_pg =1;
date_default_timezone_set("Asia/Calcutta"); 
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
$media_by = array(1=>'Ramesh', 2=>'RajKumar', 3=>'Simin', 4=>'Bargavi', 5=>'Shaheena', 6=>'Samadh', 7=>'Vedika', 8=>'Radhika', 9=>'Dhanush', 10=>'Nida', 11=>'Maha', 12=>'Bala', 13=>'Pavithra');
$cc_card = array(1=>'BYT', 2=>'Client'); 
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
	echo "<script>window.location = 'budget5.php';</script>";
	exit();
}
$extQ = '';

if(isset($_POST['submit_hide'])) {
        // $extQ = ' AND tbl_id IN ('.implode(",",$_POST['tbl_id']).')';
        $cirSql = "UPDATE budget_reminder SET hide_temp='1' WHERE tbl_id IN (".implode(',',$_POST['tbl_id']).")";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
        echo "<script>window.location = 'budget5.php';</script>";
}

if(isset($_POST['submit_show'])) {
  // $extQ = ' AND tbl_id IN ('.implode(",",$_POST['tbl_id']).')';
  $cirSql = "UPDATE budget_reminder SET hide_temp='0' WHERE tbl_id IN (".implode(',',$_POST['tbl_id']).")";
  mysqli_query($conn, $cirSql) or die(mysqli_error()); 
  echo "<script>window.location = 'budget5.php';</script>";
}

if(isset($_POST['submit'])) {
	if(count($_POST['tbl_id'])>0){
    $extQ = ' AND tbl_id IN ('.implode(",",$_POST['tbl_id']).')';
  }
	//exit();
}
if(!isset($_GET['hide'])) {
  $extQ .= " AND hide_temp='0' ";
} else {
  $extQ .= " AND hide_temp='1' ";
}
include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " budget_reminder WHERE uid='".$_SESSION['uid']."' AND delete_status=0";


$q_last_up = "SELECT last_updated FROM last_updated WHERE type='budget'";
$res_last_up = mysqli_query($conn, $q_last_up);
$rw_last_up = mysqli_fetch_assoc($res_last_up);
$last_updated = $rw_last_up['last_updated']; 

?>
<style>
.blue { cursor:pointer; } 
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.dropdown-menu { min-width: 80px !important; }
.font-italic b, strong {
    font-weight: 700;
    color: #13a911;
    font-size: 12px;
    font-style: italic;
}
center { font-style: italic; } 
</style>
<style>
.table-scroll {
	position:relative;
	margin:auto;
	overflow:hidden;
	
}
.table-wrap {
	width:100%;
	overflow:auto;
}
.table-scroll table {
	width:100%;
	margin:auto;
	border-collapse:separate;
	border-spacing:0;
}
.table-scroll th, .table-scroll td {
	padding:5px 10px;
	border:1px solid #dddddd;
	white-space:nowrap;
	vertical-align:top;
}
.table-scroll thead, .table-scroll tfoot {
	background:#f9f9f9;
}
.clone {
	position:absolute;
	top:0;
	left:0;
	pointer-events:none;
}
.clone th, .clone td {
	visibility:hidden
}
.clone td, .clone th {
	border-color:transparent
}
.clone tbody th {
	visibility:visible;
	color:red;
}
.clone .fixed-side {
	border:1px solid #dddddd;
  background: #f9f9f9;
	visibility:visible;
  font-weight: bold;
  text-align:left;
}
tbody td {
    text-align: right;
}
.clone thead, .clone tfoot{background:transparent;}

.txt_red { color:red; font-weight:bold; }
.txt_green { color:#74d350; font-weight:bold; }
thead th {
    text-align: center !important;
}
.fixed-side .fa { font-size: 12px; color: #5dc169; cursor: pointer; margin-left: 5px; }
table .fa-wa { color: #4db628; font-weight: bold; }
.wa_msg { font-weight: bold; }
small { font-size: ''; }
input[type=search], select.form-control{
    display: inline;
    width: 200px;
    margin-left: 25px;
    padding: 5px;
    border: 1px solid #bcb5b5;
  }
  tfoot td, thead th {
    background: #e4f3ff;
}
tbody td { padding-bottom: 2px !important; }
.table th:first-child, .table td:first-child
{
  position:sticky;
  left:0px;
  background-color:#fff; 
}
input[type=checkbox] {
  display: grid;
    grid-template-columns: 1em auto;
    gap: 0.3em;
    margin-top: -5px;
    cursor: pointer;
}
.dropdown, .dropup {
    position: relative;
    display: inline-block;
}
tr th {
    text-align: center !important;
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
                        	<a href="budget5.php?hide=1"  class="btn btn-default btn-sm">Hidden Accounts</a>                        
                      </div>    
                      </li>
                      </li>
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
                        	<a href="loading.php?pg=cron-budget5.php?refresh=1"  class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to Email Cashflow report?');" ><i class="fa fa-refresh"></i> Refresh All</a>                        
                      </div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  <center><span class="font-italic">Reporting time: <b><?php echo date("d-m-Y, h:i a", strtotime($last_updated)); ?></b></span></center><br>
                  		<?php 
                           $maxDays = date('t');
                           $nDay = date("d");
                    echo "SELECT * FROM ".$statement." $extQ order by cc_card asc";
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." $extQ order by cc_card asc");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <div class="category-filter">
                                    <select id="categoryFilter" class="form-control">
                                        <option value="">Media Buyer (All)</option>
                                        <?php foreach($media_by as $mb_key => $mb_val){  ?>
                                        <option value="<?php echo $mb_val; ?>"><?php echo $mb_val; ?></option>
                                        <?php } ?>
                                    </select>
                                    <select id="categoryFilter2" class="form-control">
                                        <option value="">Account (All)</option>
                                        <option value="BYT">BYT</option>
                                        <option value="Client">Client</option>
                                    </select>
                                </div>
                                <div id="table-scroll" class="table-scroll">
                    <div class="table-wrap">
                                <table id="datatable" class="table table-hover table-striped table-bordered datatable" data-ordering="true">
                                    <thead>                                        
                                        <th  class="fixed-side"><input type="checkbox" id="checkAll" name="checkAll" value="" class="form-control" data-html="true" data-toggle="tooltip" data-original-title="Select All" /></th>
                                        <th  class="fixed-side" scope="col" >Client</th>  
                                        <th>Acc</th>                                  	
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total Budget">Budget</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Received amount">Rcd</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Due amount">Due</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Client Cash">Client Cash</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Budget Balance">Bud. bal</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="The received amount will be spent">Rcd. Reach</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total spend of this month">Tot Sp</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Estimated Spend per day">Est Sp/d</th> 
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Actual spend per day">Act. Sp/d</th> 
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total Facebook spend of this month">Fb Sp</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total Google spend of this month">Gg Sp</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Yesterday Spend">Yst Sp</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Estimated spend of month end">Est Mon. End</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total Leads this month">Lead</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Cost per lead this month">CPL</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="total Leads last month">Lead(L)</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Cost per lead last month">CPL(L)</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Media buyer">Med By</th>
                                    </thead>
                                    <tbody>
                                    	<?php
                                      
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%.0n', $amount);
                    $totRows = mysqli_num_rows($sqlRev);
                    $grandBudget = $grandSpent =   $grandEstSpent = $grandActSpent = $grandFbSpent = $grandGSpent = $grandBalance = $grandYestSpent = $grandMonEnd = $grandBudBal = $grandLeads = $grandLeadSpend = $grandLeads2 = $grandLeadSpend2 = $grandBudRecived = $grandClientCash = 0;

                    $grandBudget_byt = $grandSpent_byt =   $grandEstSpent_byt = $grandActSpent_byt = $grandFbSpent_byt = $grandGSpent_byt = $grandBalance_byt = $grandYestSpent_byt = $grandMonEnd_byt = $grandBudBal_byt = $grandLeads_byt = $grandLeadSpend_byt = $grandLeads2_byt = $grandLeadSpend2_byt = $grandBudRecived_byt = $grandClientCash_byt = 0;

                    $grandBudget_cl = $grandSpent_cl =   $grandEstSpent_cl = $grandActSpent_cl = $grandFbSpent_cl = $grandGSpent_cl = $grandBalance_cl = $grandYestSpent_cl = $grandMonEnd_cl = $grandBudBal_cl = $grandLeads_cl = $grandLeadSpend_cl = $grandLeads2_cl = $grandLeadSpend2_cl = $grandBudRecived_cl = $grandClientCash_cl =0;
                    
                    $med_bud = $med_est = array();


										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
											
											$fb_spent = $g_spent = $in_spent = $ta_spent = 0;
                      $fb_spent_y = $g_spent_y = $in_spent_y = $ta_spent_y = $fb_leads = $fb_cpl = $fb_leads2 = $fb_cpl2 = 0;
											
											if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
											if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
											if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
											if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }

                                            if($sqlROW["fb_spent_y"]!='') { $fb_spent_y = explode(',',$sqlROW["fb_spent_y"]); $fb_spent_y = array_sum(array_filter($fb_spent_y)); }
											if($sqlROW["g_spent_y"]!='') { $g_spent_y = explode(',',$sqlROW["g_spent_y"]); $g_spent_y = array_sum(array_filter($g_spent_y)); }
											if($sqlROW["in_spent_y"]!='') { $in_spent_y = explode(',',$sqlROW["in_spent_y"]); $in_spent_y = array_sum(array_filter($in_spent_y)); }
											if($sqlROW["ta_spent_y"]!='') { $ta_spent_y = explode(',',$sqlROW["ta_spent_y"]); $ta_spent_y = array_sum(array_filter($ta_spent_y)); }

                      if($sqlROW["fb_leads"]!='') { 
                        $fb_leads_exp = $fb_leads_spend = array();
                        $fb_leads_exp = explode(',',$sqlROW["fb_leads"]); 
                        $fb_leads = array_sum(array_filter($fb_leads_exp)); 
                        $fb_cpl_exp = explode(',',$sqlROW["fb_cpl"]);
                        if($fb_leads>0) {
                          foreach($fb_leads_exp as $lKey => $lval){
                            $fb_leads_spend[] = $lval * $fb_cpl_exp[$lKey];
                          }
                          $fb_leads_spend = array_sum(array_filter($fb_leads_spend));
                          $fb_cpl = round(($fb_leads_spend / $fb_leads));
                        } else {
                          $fb_leads = $fb_leads_spend = 0;
                        }
                      }

                      if($sqlROW["fb_leads2"]!='') { 
                        $fb_leads_exp2 = $fb_leads_spend2 = array();
                        $fb_leads_exp2 = explode(',',$sqlROW["fb_leads2"]); 
                        $fb_leads2 = array_sum(array_filter($fb_leads_exp2)); 
                        $fb_cpl_exp2 = explode(',',$sqlROW["fb_cpl2"]);
                        if($fb_leads2>0) {
                          foreach($fb_leads_exp2 as $lKey => $lval){
                            $fb_leads_spend2[] = $lval * $fb_cpl_exp2[$lKey];
                          }
                          $fb_leads_spend2 = array_sum(array_filter($fb_leads_spend2));
                          $fb_cpl2 = round(($fb_leads_spend2 / $fb_leads2));
                        } else {
                          $fb_leads2 = $fb_leads_spend2 = 0;
                        }
                      }
                      //if($sqlROW["fb_leads"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
											
											$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
                                            $tot_spend_y = $fb_spent_y + $g_spent_y + $in_spent_y + $ta_spent_y;
											
											$tot_bal = $sqlROW["total_budget"] - $tot_spend ;

                      
                                            $bud_recived = $bud_bal = $bud_reach = '';
											                      if(isset($sqlROW['budget_received']) && $sqlROW['budget_received']!='') 
                                            { 
												                            $bud_recived_exp = explode(',', $sqlROW['budget_received']);
                                                    $bud_recived = array_sum($bud_recived_exp);
                                                    
                                            }
                                            $bud_bal =  $bud_recived - $sqlROW["total_budget"]; 
                                            if ($bud_bal < 0) { $bal_color ='red'; } else { $bal_color ='#20be20'; }
                                            
                                            $client_cash = ($bud_recived-$tot_spend);
                                            if ($client_cash < 0) { $cl_cash_color ='red'; } else { $cl_cash_color ='#20be20'; }
                                            $bud_reach = ($client_cash / ($sqlROW["total_budget"]/$maxDays)); //exit;

                                            $grandBudget = $grandBudget + $sqlROW["total_budget"];
                                            $grandSpent = $grandSpent + $tot_spend;
                                            $grandBalance = $grandBalance + $tot_bal;
                                            $grandEstSpent = $grandEstSpent + ($sqlROW["total_budget"]/$maxDays);
                                            $grandActSpent = $grandActSpent + ($tot_spend/$nDay);
                                            $grandFbSpent = $grandFbSpent + $fb_spent;
                                            $grandGSpent = $grandGSpent + $g_spent;
                                            $grandYestSpent = $grandYestSpent + $tot_spend_y;
                                            $grandMonEnd = $grandMonEnd + (($tot_spend/$nDay)*$maxDays);
                                            $grandLeads = $grandLeads + $fb_leads;
                                            $grandLeadSpend = $grandLeadSpend + $fb_leads_spend;
                                            $grandLeads2 = $grandLeads2 + $fb_leads2;
                                            $grandLeadSpend2 = $grandLeadSpend2 + $fb_leads_spend2;
                                            $grandBudBal = $grandBudBal + $bud_bal;
                                            $grandBudRecived = $grandBudRecived + $bud_recived;
                                            $grandClientCash = $grandClientCash + $client_cash;

                                            if($sqlROW["cc_card"]==1){
                                                                $grandBudget_byt = $grandBudget_byt + $sqlROW["total_budget"];
                                                                $grandSpent_byt = $grandSpent + $tot_spend;
                                                                $grandBalance_byt = $grandBalance_byt + $tot_bal;
                                                                $grandEstSpent_byt = $grandEstSpent_byt + ($sqlROW["total_budget"]/$maxDays);
                                                                $grandActSpent_byt = $grandActSpent_byt + ($tot_spend/$nDay);
                                                                $grandFbSpent_byt = $grandFbSpent_byt + $fb_spent;
                                                                $grandGSpent_byt = $grandGSpent_byt + $g_spent;
                                                                $grandYestSpent_byt = $grandYestSpent_byt + $tot_spend_y;
                                                                $grandMonEnd_byt = $grandMonEnd_byt + (($tot_spend/$nDay)*$maxDays);
                                                                $grandLeads_byt = $grandLeads_byt + $fb_leads;
                                                                $grandLeadSpend_byt = $grandLeadSpend_byt + $fb_leads_spend;
                                                                $grandLeads2_byt = $grandLeads2_byt + $fb_leads2;
                                                                $grandLeadSpend2_byt = $grandLeadSpend2_byt + $fb_leads_spend2;
                                                                $grandBudBal_byt = $grandBudBal_byt + $bud_bal;
                                                                $grandBudRecived_byt = $grandBudRecived_byt + $bud_recived;
                                                                $grandClientCash_byt = $grandClientCash_byt + $client_cash;
                                            }
                                            
                                            if($sqlROW["cc_card"]==2){
                                              $grandBudget_cl = $grandBudget_cl + $sqlROW["total_budget"];
                                              $grandSpent_cl = $grandSpent + $tot_spend;
                                              $grandBalance_cl = $grandBalance_cl + $tot_bal;
                                              $grandEstSpent_cl = $grandEstSpent_cl + ($sqlROW["total_budget"]/$maxDays);
                                              $grandActSpent_cl = $grandActSpent_cl + ($tot_spend/$nDay);
                                              $grandFbSpent_cl = $grandFbSpent_cl + $fb_spent;
                                              $grandGSpent_cl = $grandGSpent_cl + $g_spent;
                                              $grandYestSpent_cl = $grandYestSpent_cl + $tot_spend_y;
                                              $grandMonEnd_cl = $grandMonEnd_cl + (($tot_spend/$nDay)*$maxDays);
                                              $grandLeads_cl = $grandLeads_cl + $fb_leads;
                                              $grandLeadSpend_cl = $grandLeadSpend_cl + $fb_leads_spend;
                                              $grandLeads2_cl = $grandLeads2_cl + $fb_leads2;
                                              $grandLeadSpend2_cl = $grandLeadSpend2_cl + $fb_leads_spend2;
                                              $grandBudBal_cl = $grandBudBal_cl + $bud_bal;
                                              $grandBudRecived_cl = $grandBudRecived_cl + $bud_recived;
                                              $grandClientCash_cl = $grandClientCash_cl + $client_cash;
                                          }

                                          $med_bud[$sqlROW["media_by"]][] = $sqlROW["total_budget"];
                                          $med_est[$sqlROW["media_by"]][] = (($tot_spend/$nDay)*$maxDays);

										?>
                                        <tr>                                        	
                                           <th  class="fixed-side"><input type="checkbox" name="tbl_id[]" value="<?php echo $sqlROW["tbl_id"]; ?>"  class="form-control" /></th>
                                        	 <td  class="fixed-side"><a onClick="onAjax(<?php echo $sqlROW["tbl_id"]; ?>, 1, '<?php echo $sqlROW["client_name"]; ?>');" data-id="1" data-toggle="modal" class="fixed-side blue modal-cl" data-target=".bs-example-modal-lg"><?php echo $sqlROW["client_name"]; ?></a>
                                           
                                           
                                           <div class="dropdown">
                                                <a class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-caret-down" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a>
                                                <ul class="dropdown-menu pull-right">
                                                    <li><a href="loading.php?pg=cron-budget5.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1">Refresh</a></li>
                                                    <li><a href="loading.php?pg=budget-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>">Edit</a></li>
                                                    <li><a href="loading.php?pg=budget5.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');" >Delete</a></li>
                                                </ul>
                                            </div> 
                                            <!--<a href="loading.php?pg=budget-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>"><i class="fa fa-caret-down" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a> 
                                           <a href="loading.php?pg=cron-budget5.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1" ><i class="fa fa-refresh" data-html="true" data-toggle="tooltip" data-original-title="Refresh"></i></a>--> </td>                                      
                                            <td class="text-right"> <?php if($sqlROW["cc_card"]!='') { echo $cc_card[$sqlROW["cc_card"]]; } ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format($sqlROW["total_budget"]); ?></td> 
                                            <td class="text-right"> <?php if($bud_recived>0) { echo $fmt->format($bud_recived); } else { echo '-'; } ?></td> 
                                            <td class="text-right"><span style="color: <?php echo $bal_color; ?>"><?php  echo $fmt->format($bud_bal);  ?></span></td> 
                                            <td class="text-right"><span style="color: <?php echo $cl_cash_color; ?>"><?php  echo $fmt->format($client_cash); ?></span></td> 
                                            <td class="text-right"> <?php if($tot_bal>0) { echo $fmt->format($tot_bal); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($bud_reach>0) { echo round($bud_reach).' days'; } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($tot_spend>0) { echo $fmt->format($tot_spend); } else { echo '-'; } ?></td>  
                                            <td class="text-right"> <?php echo $fmt->format(($sqlROW["total_budget"]/$maxDays)); ?></td> 
                                            <td class="text-right"> <?php if($tot_spend>0) { echo $fmt->format(($tot_spend/$nDay)); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($fb_spent>0) { echo $fmt->format($fb_spent); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($g_spent>0) { echo $fmt->format($g_spent); } else { echo '-'; } ?></td> 
                                            
                                            <td class="text-right"> <?php if($tot_spend_y>0) { echo $fmt->format($tot_spend_y); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format((($tot_spend/$nDay)*$maxDays)); ?>
                                          <?php if($sqlROW["total_budget"]<(($tot_spend/$nDay)*$maxDays)) { echo '<i class="fa fa-arrow-up" style="color:#ff5f5f"></i>'; } else { echo '<i class="fa fa-arrow-down" style="color:#06c54a"></i>'; } ?>
                                          </td> 
                                            <td class="text-right"> <?php if($fb_leads>0) { echo $fmt->format($fb_leads); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($fb_cpl>0) { echo $fmt->format($fb_cpl); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($fb_leads2>0) { echo $fmt->format($fb_leads2); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($fb_cpl2>0) { echo $fmt->format($fb_cpl2); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($sqlROW["media_by"]!='') { echo $media_by[$sqlROW["media_by"]]; } ?></td>
                                        </tr>  
                                        <?php $i++;
                              if($i==($totRows+1)){ 
                                if ($grandBudBal < 0) { $bal_color ='red'; } else { $bal_color ='#20be20'; }
                                if ($grandBudBal_byt < 0) { $bal_color_byt ='red'; } else { $bal_color_byt ='#20be20'; }
                                if ($grandBudBal_cl < 0) { $bal_color_cl ='red'; } else { $bal_color_cl ='#20be20'; }
                                
                                if ($grandClientCash < 0) { $cl_cash_color ='red'; } else { $cl_cash_color ='#20be20'; }
                                if ($grandClientCash_byt < 0) { $cl_cash_color_byt ='red'; } else { $cl_cash_color_byt ='#20be20'; }
                                if ($grandClientCash_cl < 0) { $cl_cash_color_cl ='red'; } else { $cl_cash_color_cl ='#20be20'; }
                                
                                ?>
                               <tfoot> 
                               <tr  class=" footer"> 
                                <th  class="fixed-side"></th>
                                <td class="fixed-side text-right"><b>Total</b></td>
                                <td class="text-right">-</td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudget); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudRecived); ?></b></td>
                                <td class="text-right"><b> <span style="color: <?php echo $bal_color; ?>"><?php echo $fmt->format($grandBudBal); ?></span></td>
                                <td class="text-right"><b> <span style="color: <?php echo $cl_cash_color; ?>"><?php echo $fmt->format($grandClientCash); ?></span></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBalance); ?></b></td>
                                <td class="text-right">-</td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandSpent); ?></b></td>
                                
                                <td class="text-right"><b> <?php echo $fmt->format($grandEstSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandActSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandFbSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandGSpent); ?></b></td>
                                
                                <td class="text-right"><b> <?php echo $fmt->format($grandYestSpent); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandMonEnd); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandLeads); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format(($grandLeadSpend/$grandLeads)); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandLeads2); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format(($grandLeadSpend2/$grandLeads2)); ?></b></td>
                                <td></td> 
                              </tr>  
                             
                               <tr class=" footer_byt"> 
                                <th  class="fixed-side"></th>
                                <td class="fixed-side text-right"><b>Total (BYT)</b></td>
                                <td class="text-right"></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudget_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudRecived_byt); ?></b></td>
                                <td class="text-right"><b> <span style="color: <?php echo $bal_color_byt; ?>"><?php echo $fmt->format($grandBudBal_byt); ?></span></td>
                                <td class="text-right"><b> <span style="color: <?php echo $cl_cash_color_byt; ?>"><?php echo $fmt->format($grandClientCash_byt); ?></span></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBalance_byt); ?></b></td>
                                <td class="text-right">-</td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandSpent_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandEstSpent_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandActSpent_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandFbSpent_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandGSpent_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandYestSpent_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandMonEnd_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandLeads_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format(($grandLeadSpend_byt/$grandLeads_byt)); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandLeads2_byt); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format(($grandLeadSpend2_byt/$grandLeads2_byt)); ?></b></td>
                                <td></td> 
                              </tr>  
                              <tr class=" footer_cl"> 
                                <th  class="fixed-side"></th>
                                <td class="fixed-side text-right"><b>Total (Client)</b></td>
                                <td class="text-right"></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudget_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBudRecived_cl); ?></b></td>
                                <td class="text-right"><b> <span style="color: <?php echo $bal_color_cl; ?>"><?php echo $fmt->format($grandBudBal_cl); ?></span></td>
                                <td class="text-right"><b> <span style="color: <?php echo $cl_cash_color_cl; ?>"><?php echo $fmt->format($grandClientCash_cl); ?></span></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandBalance_cl); ?></b></td>
                                <td class="text-right">-</td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandSpent_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandEstSpent_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandActSpent_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandFbSpent_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandGSpent_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandYestSpent_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandMonEnd_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandLeads_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format(($grandLeadSpend_cl/$grandLeads_cl)); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format($grandLeads2_cl); ?></b></td>
                                <td class="text-right"><b> <?php echo $fmt->format(($grandLeadSpend2_cl/$grandLeads2_cl)); ?></b></td>
                                <td></td> 
                              </tr>
                            </tfoot> 
  <?php
                              }
										} 
                    
                    ?>                                      
                                    </tbody>
                                </table>
                                
                               
                                </div></div>
                                <a href="budget5.php" class="btn btn-default">Reset</a> <input type="submit" name="submit" value="Submit" class="btn btn-primary"> 
                                <?php if(!isset($_GET['hide'])) { ?>
                                <input type="submit" name="submit_hide" value="Hide" class="btn btn-danger"> 
                                <?php } else { ?>
                                  <input type="submit" name="submit_show" value="Show" class="btn btn-danger"> 
                                  <?php }  ?>

								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>
                <br>
                <h3 class="text-center">Meida buyer vs Budget</h3>
                </form>
                <table class="table table-hover table-striped table-bordered datatable" >
                  <tr>
                    <th>Media Buyer</th>
                    <th>Budget</th>
                    <th>Est. Mon. End</th>
                    <th>Est. Diff</th>
                    <th>Est. Diff in %</th>
                    <th>Spend</th>
                    <th>Penalty</th>
                                </tr>
                
                <?php    // d($med_bud); d($med_est); 

                  foreach($med_bud as $k => $v){ 
                    
                    $tot_bud = array_sum($med_bud[$k]);
                    $tot_est = array_sum($med_est[$k]);
                    $tot_dif = $tot_bud - $tot_est;
                    $bud_2per = $tot_bud * 0.05;

                    if($tot_dif>0) { $spendTy= 'Underspend'; $penalty = $tot_dif - $bud_2per; } else { $spendTy= 'Overspend'; $penalty = ($tot_dif + $bud_2per) * -1; }
                    if( $penalty > 0 ) { $bgcolor='#f97878'; } else { $bgcolor='#14bb34'; $penalty=0; }
                    ?>
                    <tr>
                        <td><?php echo $media_by[$k]; ?></td>
                        <td><?php echo $fmt->format($tot_bud); ?></td>
                        <td><?php echo $fmt->format(round($tot_est)); ?></td>
                        <td><?php echo $fmt->format(round($tot_dif)); ?></td> 
                        <td><?php echo round(($tot_dif/$tot_bud)*100,2); ?> % </td>
                        <td><?php echo $spendTy; ?></td>
                        <td style="background-color:<?php echo $bgcolor; ?>;text-align: right; color:white;"><?php echo $fmt->format(round($penalty)); ?></td>
                  </tr>
                    <?
                  }
                
                ?>
								  </table>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>

        <script>
          $('.footer_byt,.footer_cl').hide(); 
          <?php if(!isset($_POST['submit'])) { ?>
          $("#categoryFilter2").val('BYT');
          $('.footer_byt').show(); $('.footer_cl, .footer').hide();
          <?php } ?>
          $("#checkAll").click(function(){
              $('input:checkbox').not(this).prop('checked', this.checked);
          });
          $('#categoryFilter2').on('change', function() {
            //alert( this.value );
            //alert(selectedItem2);
            if(this.value=='BYT') {
                $('.footer_byt').show(); $('.footer_cl, .footer').hide();
            }
            else if(this.value=='Client') {
                $('.footer_cl').show(); $('.footer_byt, .footer').hide();
            } else {
                $('.footer').show(); $('.footer_byt, .footer_cl').hide();
            }
          });
    $("document").ready(function () {
     
      //$(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');  
      
      var table = $('#datatable').DataTable();
      $("#datatable_filter.dataTables_filter").append($("#categoryFilter"));
      $("#datatable_filter.dataTables_filter").append($("#categoryFilter2"));

      var categoryIndex = 0;
      var categoryIndex2 = 0;
      $("#datatable th").each(function (i) {
        if ($($(this)).html() == "Med By") {
          categoryIndex = i; //return false;
        }
        if ($($(this)).html() == "Acc") {
          categoryIndex2 = i; //return false;
        }
      });
      $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
          var selectedItem = $('#categoryFilter').val();
          var selectedItem2 = $('#categoryFilter2').val();
          var category = data[categoryIndex];
          var category2 = data[categoryIndex2];
          if ((selectedItem === "" || category.includes(selectedItem)) && (selectedItem2 === "" || category2.includes(selectedItem2))) {
            return true;
          }
          
          return false;
        }
      );
      $("#categoryFilter, #categoryFilter2").change(function (e) {
        table.draw();
      });

      table.draw();
    });
  </script>

<script>
    /*$(function() {
        
        $('#datatable2').dataTable({
            "ordering": true,
            "lengthMenu": [25, 50, 100, 150, 200, 500],
            "pageLength": 50,
            scrollX: true,
            "autoWidth": false,
            "bLengthChange": false,
        });
});*/
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
$(document).ready(function() {
    
    $('[data-toggle="tooltip"]').tooltip()
    $(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');  
});
</script>