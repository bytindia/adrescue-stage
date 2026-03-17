<?php include 'header.php'; 
$budget_pg =1;
date_default_timezone_set("Asia/Calcutta"); 
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
//$media_by = array(1=>'Ramesh', 2=>'RajKumar', 3=>'Simin', 4=>'Bargavi', 5=>'Shaheena', 6=>'Samadh',  8=>'Radhika',  10=>'Nida', 11=>'Maha', 12=>'Bala', 14=>'Charan', 15=>'Mughil');
$media_by = array(1=>'Ramesh',  3=>'Simin',  5=>'Shaheena', 14=>'Charan', 15=>'Mughil', 16=>'Karthik');
$cc_card = array(1=>'BYT', 2=>'Client'); 
Auth();

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
	echo "<script>window.location = 'budget4.php';</script>";
	exit();
}
$extQ = '';

if(isset($_POST['submit_hide'])) {
        // $extQ = ' AND tbl_id IN ('.implode(",",$_POST['tbl_id']).')';
        $cirSql = "UPDATE budget_reminder SET hide_temp='1' WHERE tbl_id IN (".implode(',',$_POST['tbl_id']).")";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
        echo "<script>window.location = 'budget4.php';</script>";
}

if(isset($_POST['submit_show'])) {
  // $extQ = ' AND tbl_id IN ('.implode(",",$_POST['tbl_id']).')';
  $cirSql = "UPDATE budget_reminder SET hide_temp='0' WHERE tbl_id IN (".implode(',',$_POST['tbl_id']).")";
  mysqli_query($conn, $cirSql) or die(mysqli_error()); 
  echo "<script>window.location = 'budget4.php';</script>";
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
$tblN = 'budget_reminder';
$mon = 'This Month';
$last_mon = '<a href="budget4.php?last_mon=1" style="color: #e50aff;">View last month budget</a>';
if(isset($_GET['last_mon'])){
  $tblN = 'budget_reminder_last_mon';
  $mon = 'Last Month';
  $last_mon = '<a href="budget4.php" style="color: #e50aff;">View this month budget</a>';
}
$pgHeadline = 'Ads Budget - '.$mon;
$statement = " $tblN WHERE uid='".$_SESSION['uid']."' AND delete_status=0";


$q_last_up = "SELECT last_updated FROM last_updated WHERE type='budget'";
$res_last_up = mysqli_query($conn, $q_last_up);
$rw_last_up = mysqli_fetch_assoc($res_last_up);
$last_updated = $rw_last_up['last_updated']; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
.table-scroll th, .table-scroll td {
  padding:5px !important; font-size: 12px !important;
}
input[type=checkbox] {
    height: 20px;
    margin-top: 5px;
}
</style>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
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
          <!-- Bootstrap 3-compatible FinSheet Modal -->
        <div class="modal fade" id="finsheetModal" tabindex="-1" role="dialog" aria-labelledby="finsheetModalLabel">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="finsheetModalLabel">Payable & Receivable Summary</h4>
              </div>
              <div class="modal-body" style="padding:0;">
                <iframe id="finsheetIframe" src="" style="width:100%; height:80vh; border:none;"></iframe>
              </div>
            </div>
          </div>
        </div>

         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 <?php  if(!isset($_GET['menu'])) { ?>
                  <div class="x_title">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                      <div style="flex:1; min-width:200px;">
                        <h2 style="margin-bottom:0; text-align:left;"><?php echo $pgHeadline; ?></h2>
                        <div style="text-align:center; margin-top:2px; margin-bottom:2px;">
                          <span class="font-italic" style="font-size:10px; color:#666;">Reporting time: <b><?php echo date("d-m-Y, h:i a", strtotime($last_updated)); ?></b> | <?php echo $last_mon; ?></span>
                        </div>
                      </div>
                      <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex:1;">
                        <ul class="nav navbar-right panel_toolbox btn-group" style="margin-bottom:0;">
                          <li><a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#finsheetModal" onclick="loadFinSheet()"><i class="fa fa-whatsapp"></i> FinSheet</a></li>
                          <li><a href="budget4.php?hide=1" class="btn btn-primary btn-sm">Hidden Accounts</a></li>
                          <li><a href="#" class="btn btn-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=budget-add.php" data-name="Add Account" data-val="edit">Add Accounts</a></li>
                          <li><a href="loading.php?pg=cron-budget4.php?refresh=1" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to Email Cashflow report?');"><i class="fa fa-refresh"></i> Refresh All</a></li>
                        </ul>
                      </div>
                    </div>
                   
                  </div>
               <?php  } ?>   
                  <div class="x_content">
                  		<?php 
                           $maxDays = date('t');
                           $nDay = date("d");
                    //echo "SELECT * FROM ".$statement." $extQ order by cc_card asc LIMIT {$startpoint} , {$per_page}";
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." $extQ order by cc_card asc LIMIT {$startpoint} , {$per_page}");
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
                                        <th  class="fixed-side no-export"><input type="checkbox" id="checkAll" name="checkAll" value="" class="form-control" data-html="true" data-toggle="tooltip" data-original-title="Select All" /></th>
                                        <th  class="fixed-side" scope="col" >Client</th>  
                                        <th>Acc</th>                                  	
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total Budget">Budget</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Received amount">Rcd</th>
                                        <?php
                                        // Determine which columns to show based on user type
                                        $user_ty = isset($_SESSION['user_ty']) ? $_SESSION['user_ty'] : 'admin';
                                        $show_ret_fee_due = ($user_ty === 'admin' || $user_ty === 'acc');
                                        $show_est_to_cpl = ($user_ty === 'admin' || $user_ty === 'ads');
                                        ?>
                                        <?php if($show_ret_fee_due): ?>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Received amount">Ret. Fee</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Due amount">Due</th>
                                        <?php endif; ?>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Budget Balance">Bud. bal</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="The received amount will be spent">Rcd. Reach</th>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Total spend of this month">Tot Sp</th>
                                        <?php if($show_est_to_cpl): ?>
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
                                        <?php endif; ?>
                                        <th data-html="true" data-toggle="tooltip" data-original-title="Media buyer">Med By</th>
                                    </thead>
                                    <tbody>
                                    	<?php
                                      
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%.0n', $amount);
                    $totRows = mysqli_num_rows($sqlRev);
                    $grandBudget = $grandSpent =   $grandEstSpent = $grandActSpent = $grandFbSpent = $grandGSpent = $grandBalance = $grandYestSpent = $grandMonEnd = $grandBudBal = $grandLeads = $grandLeadSpend = $grandLeads2 = $grandLeadSpend2 = $grandBudRecived = $grandClientCash = $grandRetainer = 0;

                    $grandBudget_byt = $grandSpent_byt =   $grandEstSpent_byt = $grandActSpent_byt = $grandFbSpent_byt = $grandGSpent_byt = $grandBalance_byt = $grandYestSpent_byt = $grandMonEnd_byt = $grandBudBal_byt = $grandLeads_byt = $grandLeadSpend_byt = $grandLeads2_byt = $grandLeadSpend2_byt = $grandBudRecived_byt = $grandClientCash_byt = $grandRetainer_byt = 0;

                    $grandBudget_cl = $grandSpent_cl =   $grandEstSpent_cl = $grandActSpent_cl = $grandFbSpent_cl = $grandGSpent_cl = $grandBalance_cl = $grandYestSpent_cl = $grandMonEnd_cl = $grandBudBal_cl = $grandLeads_cl = $grandLeadSpend_cl = $grandLeads2_cl = $grandLeadSpend2_cl = $grandBudRecived_cl = $grandClientCash_cl = $grandRetainer_cl = 0;
                    
                    $med_bud = $med_est = array();


										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
											
											$fb_spent = $g_spent = $in_spent = $ta_spent = $tot_bal = $fb_leads_spend=  $fb_leads_spend2 = 0;
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
                        $fb_cpl_exp2 = explode(',',$sqlROW["fb_cpl2"] ?? '');
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
                      
											if($sqlROW["total_budget"]=='') { $sqlROW["total_budget"]=0; }
                      if($sqlROW["retainer_fee"]=='') { $sqlROW["retainer_fee"]=0; }
											$tot_bal = $sqlROW["total_budget"] - $tot_spend ;

                      
                                            $bud_recived = $bud_bal = $bud_reach = 0;
											                      if(isset($sqlROW['budget_received']) && $sqlROW['budget_received']!='') 
                                            { 
												                            $bud_recived_exp = explode(',', $sqlROW['budget_received'] ?? '');
                                                    $bud_recived = array_sum($bud_recived_exp);
                                                    
                                            }
                                            //$bud_bal =  $bud_recived - $sqlROW["total_budget"]; 
                                            if($sqlROW["cc_card"]==2){ $bud_bal = - $sqlROW["retainer_fee"]; } else {  $bud_bal =  $bud_recived - ($tot_spend + $sqlROW["retainer_fee"]); }
                                           
                                            if ($bud_bal < 0) { $bal_color ='red'; } else { $bal_color ='#20be20'; }
                                            
                                            $client_cash = ($bud_recived-$tot_spend);
                                            if ($client_cash < 0) { $cl_cash_color ='red'; } else { $cl_cash_color ='#20be20'; }
                                            if($sqlROW["total_budget"]!=0) {
                                              $bud_reach = @($client_cash / @($sqlROW["total_budget"]/$maxDays)); //exit;
                                              $grandEstSpent = $grandEstSpent + ($sqlROW["total_budget"]/$maxDays);
                                            }
                                            

                                            $grandBudget = $grandBudget + $sqlROW["total_budget"];
                                            $grandRetainer = $grandRetainer + $sqlROW["retainer_fee"];
                                            $grandSpent = $grandSpent + $tot_spend;
                                            $grandBalance = $grandBalance + $tot_bal;
                                            
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
                                                                if($sqlROW["total_budget"]!=0) { $grandEstSpent_byt = $grandEstSpent_byt + ($sqlROW["total_budget"]/$maxDays); }
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
                                                                $grandRetainer_byt = $grandRetainer_byt + $sqlROW["retainer_fee"];
                                            }
                                            
                                            if($sqlROW["cc_card"]==2){
                                              $grandBudget_cl = $grandBudget_cl + $sqlROW["total_budget"];
                                              $grandSpent_cl = $grandSpent + $tot_spend;
                                              $grandBalance_cl = $grandBalance_cl + $tot_bal;
                                              if($sqlROW["total_budget"]!=0) { $grandEstSpent_cl = $grandEstSpent_cl + ($sqlROW["total_budget"]/$maxDays); }
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
                                              $grandRetainer_cl = $grandRetainer_cl + $sqlROW["retainer_fee"];
                                          }

                                          $med_bud[$sqlROW["media_by"]][] = $sqlROW["total_budget"];
                                          $med_est[$sqlROW["media_by"]][] = (($tot_spend/$nDay)*$maxDays);

										?>
                                        <tr>                                        	
                                           <th  class="fixed-side no-export"><input type="checkbox" name="tbl_id[]" value="<?php echo $sqlROW["tbl_id"]; ?>"  class="form-control" /></th>
                                        	 <td  class="fixed-side"><a href="#" data-id="<?php echo $sqlROW["tbl_id"]; ?>" data-client="<?php echo htmlspecialchars($sqlROW["client_name"], ENT_QUOTES); ?>" class="fixed-side blue modal-cl"><?php echo $sqlROW["client_name"]; ?></a>
                                           
                                           
                                           <div class="dropdown  dropdown-to-hide">
                                                <a class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-caret-down" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a>
                                                <ul class="dropdown-menu pull-right">
                                                    <li><a href="#" class="openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=budget-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>&menu=hide" data-name="Edit: <?php echo $sqlROW["client_name"]; ?>" data-val="edit">Edit</a></li>
                                                    <li><a href="loading.php?pg=cron-budget4.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&refresh=1">Refresh</a></li>
                                                    <li><a href="loading.php?pg=budget4.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');" >Delete</a></li>
                                                </ul>
                                            </div> </td>                                      
                                            <td class="text-right"> <?php if($sqlROW["cc_card"]!='' && isset($cc_card[$sqlROW["cc_card"]])) { echo $cc_card[$sqlROW["cc_card"]]; } ?></td> 
                                            <td class="text-right"> <?php echo $fmt->format($sqlROW["total_budget"]); ?></td>
                                            <td class="text-right"> <?php if($bud_recived>0) { echo $fmt->format($bud_recived); } else { echo '-'; } ?></td>
                                            <?php if($show_ret_fee_due): ?>
                                            <td class="text-right"> <?php if($sqlROW["retainer_fee"]>0) { echo $fmt->format($sqlROW["retainer_fee"]); } else { echo '-'; } ?></td> 
                                            <td class="text-right"><span style="color: <?php echo $bal_color; ?>"><?php  echo $fmt->format($bud_bal);  ?></span></td> 
                                            <?php endif; ?>
                                            <td class="text-right"> <?php if($tot_bal>0) { echo $fmt->format($tot_bal); } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($bud_reach>0) { echo round($bud_reach).' days'; } else { echo '-'; } ?></td> 
                                            <td class="text-right"> <?php if($tot_spend>0) { echo $fmt->format($tot_spend); } else { echo '-'; } ?></td>  
                                            <?php if($show_est_to_cpl): ?>
                                            <td class="text-right"> <?php if($sqlROW["total_budget"]!=0) { echo $fmt->format(($sqlROW["total_budget"]/$maxDays)); } ?></td> 
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
                                            <?php endif; ?>
                                            <td class="text-right"> <?php if($sqlROW["media_by"]!='' && isset($media_by[$sqlROW["media_by"]])) { echo $media_by[$sqlROW["media_by"]]; } ?></td>
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
                               <tr class="footer">
                                   <th class="fixed-side"></th>
                                   <td class="fixed-side text-right"><b>Total</b></td>
                                   <td class="text-right">-</td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBudget); ?></b></td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBudRecived); ?></b></td>
                                   <?php if($show_ret_fee_due): ?>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandRetainer); ?></b></td>
                                   <td class="text-right"><b> <span style="color: <?php echo $bal_color; ?>"><?php echo $fmt->format($grandBudBal); ?></span></b></td>
                                   <?php endif; ?>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBalance); ?></b></td>
                                   <td class="text-right">-</td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandSpent); ?></b></td>
                                   <?php if($show_est_to_cpl): ?>
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
                                   <?php endif; ?>
                                   <td></td>
                               </tr>
                               <tr class="footer_byt">
                                   <th class="fixed-side"></th>
                                   <td class="fixed-side text-right"><b>Total (BYT)</b></td>
                                   <td class="text-right"></td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBudget_byt); ?></b></td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBudRecived_byt); ?></b></td>
                                   <?php if($show_ret_fee_due): ?>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandRetainer_byt); ?></b></td>
                                   <td class="text-right"><b> <span style="color: <?php echo $bal_color_byt; ?>"><?php echo $fmt->format($grandBudBal_byt); ?></span></b></td>
                                   <?php endif; ?>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBalance_byt); ?></b></td>
                                   <td class="text-right">-</td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandSpent_byt); ?></b></td>
                                   <?php if($show_est_to_cpl): ?>
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
                                   <?php endif; ?>
                                   <td></td>
                               </tr>
                               <tr class="footer_cl">
                                   <th class="fixed-side"></th>
                                   <td class="fixed-side text-right"><b>Total (Client)</b></td>
                                   <td class="text-right"></td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBudget_cl); ?></b></td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBudRecived_cl); ?></b></td>
                                   <?php if($show_ret_fee_due): ?>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandRetainer_cl); ?></b></td>
                                   <td class="text-right"><b> <span style="color: <?php echo $bal_color_cl; ?>"><?php echo $fmt->format($grandBudBal_cl); ?></span></b></td>
                                   <?php endif; ?>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandBalance_cl); ?></b></td>
                                   <td class="text-right">-</td>
                                   <td class="text-right"><b> <?php echo $fmt->format($grandSpent_cl); ?></b></td>
                                   <?php if($show_est_to_cpl): ?>
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
                                   <?php endif; ?>
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
                                <a href="budget4.php" class="btn btn-default">Reset</a> <input type="submit" name="submit" value="Submit" class="btn btn-primary"> 
                                <?php if(!isset($_GET['hide'])) { ?>
                                <input type="submit" name="submit_hide" value="Hide" class="btn btn-danger"> 
                                <?php } else { ?>
                                  <input type="submit" name="submit_show" value="Show" class="btn btn-danger"> 
                                  <?php }  ?>

								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>
                <br></form>
                
                <?php if(!isset($_GET['menu'])) { ?>
                <h3 class="text-center">Media buyer vs Budget</h3>
                
                <table class="table table-hover table-striped table-bordered datatable media-buyer-table" style="width:100%;">
                  <thead>
                    <tr>
                      <th>Media Buyer</th>
                      <th>Budget</th>
                      <th>Est. Mon. End</th>
                      <th>Est. Diff</th>
                      <th>Est. Diff in %</th>
                      <th>Spend</th>
                      <th>Penalty</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($med_bud as $k => $v){ if(isset($media_by[$k])){ $tot_bud = array_sum($med_bud[$k]); $tot_est = array_sum($med_est[$k]); $tot_dif = $tot_bud - $tot_est; $bud_2per = $tot_bud * 0.05; if($tot_dif>0) { $spendTy= 'Underspend'; $penalty = $tot_dif - $bud_2per; } else { $spendTy= 'Overspend'; $penalty = ($tot_dif + $bud_2per) * -1; } if( $penalty > 0 ) { $bgcolor='#f97878'; } else { $bgcolor='#14bb34'; $penalty=0; } ?>
                    <tr>
                      <td><?php echo $media_by[$k]; ?></td>
                      <td><?php echo $fmt->format($tot_bud); ?></td>
                      <td><?php echo $fmt->format(round($tot_est)); ?></td>
                      <td><?php echo $fmt->format(round($tot_dif)); ?></td>
                      <td><?php echo ($tot_bud != 0) ? round(($tot_dif / $tot_bud) * 100, 2) : 0; ?> % </td>
                      <td><?php echo $spendTy; ?></td>
                      <td style="background-color:<?php echo $bgcolor; ?>;text-align: right; color:white;"><?php echo $fmt->format(round($penalty)); ?></td>
                    </tr>
                    <?php }} ?>
                  </tbody>
                </table>
                <?php } ?>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>

        <script>
         function loadFinSheet() {
            document.getElementById('finsheetIframe').src = 'finsheet.php';
          }
          
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
      
      // Destroy existing DataTable instance if it exists to prevent reinitialization warning
      if ($.fn.DataTable.isDataTable('#datatable')) {
        $('#datatable').DataTable().destroy();
      }
      
      var table = $('#datatable').DataTable({
        "pageLength": 25,
        "lengthMenu": [[25, 50, 100], [25, 50, 100]],
        "drawCallback": function() {
          // Re-initialize tooltips after each draw
          $('[data-toggle="tooltip"]').tooltip();
        }
      });
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
    
    // Fix for modal clicks on paginated DataTable rows
    // Using event delegation to handle dynamically loaded rows from DataTable pagination
    $(document).on('click', '.modal-cl', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var clName = $(this).data('client');
        onAjax(id, 1, clName);
        $('.bs-example-modal-lg').modal('show');
    });
});
</script>

<!-- Modal for Add/Edit Account (90% width) -->
<div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
  <div class="modal-dialog" style="width:90vw; max-width:1200px; min-width:320px;" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="iframeModalLabel">Account</h4>
      </div>
      <div class="modal-body" style="height:80vh; padding:0; overflow:hidden;">
        <div style="position:relative; height:100%;">
          <div id="iframeLoader" style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10; background:#fff; display:flex; justify-content:center; align-items:center;">
            <i class="loader-msg"><span class="loader-msg-text">Loading...</span></i>
          </div>
          <iframe id="modalIframe" src="" style="width:100%; height:100%; border:none;"></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Modal/iframe loader logic with plain text cycling
let texts = [];
let currentTextIndex = 0;
let textInterval;
let loaderText; // target the inner span

function cycleText() {
  if (loaderText) {
    loaderText.innerText = texts[currentTextIndex]; // keep animations intact
    currentTextIndex = (currentTextIndex + 1) % texts.length;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const modalIframe = document.getElementById('modalIframe');
  const modalTitle = document.getElementById('iframeModalLabel');
  loaderText = document.querySelector('.loader-msg .loader-msg-text'); // ✅

  // Use event delegation for openModal buttons to work with DataTable pagination
  $(document).on('click', '.openModal', function (e) {
    e.preventDefault();
    const url = this.getAttribute('data-url');
    const name = this.getAttribute('data-name') || 'Loading...';
    const dataVal = this.getAttribute('data-val') || 'default';

    modalTitle.textContent = name;
    document.getElementById('iframeLoader').style.display = 'flex';
    modalIframe.style.display = 'block';
    modalIframe.src = url;

    const textOptions = {
      invoice: [
        "Fetching AdReport",
        "Creating Invoices",
        "Fetching SOA",
        "Finalizing Report",
        "Processing Data",
        "Almost Done"
      ],
      pi: [
        "Creating Invoices",
        "Finalizing Report",
        "Processing Data",
        "Almost Done"
      ],
      edit: [
        "Loading Form",
        "Fetching Details",
        "Almost Ready"
      ],
      reminder: [
        "Loading Reminder Form",
        "Fetching Invoice Details",
        "Preparing Email Template",
        "Almost Ready"
      ],
      default: [
        "Loading...",
        "Please Wait",
        "Initializing"
      ]
    };

    texts = textOptions[dataVal] || textOptions['default'];

    currentTextIndex = 0;
    cycleText();
    clearInterval(textInterval);
    textInterval = setInterval(cycleText, 1500);
  });

  modalIframe.onload = function () {
    setTimeout(() => {
      document.getElementById('iframeLoader').style.display = 'none';
    }, 500);
  };

  modalIframe.onerror = function () {
    document.getElementById('iframeLoader').style.display = 'none';
  };

  $('#iframeModal').on('hidden.bs.modal', function () {
    currentTextIndex = 0;
    modalIframe.src = '';
    document.getElementById('iframeLoader').style.display = 'flex';
    clearInterval(textInterval);
  });
});
</script>