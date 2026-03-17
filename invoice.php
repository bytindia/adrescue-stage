<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Invoice Management – Send';
$pgID = 7;
$err =''; 

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE accounts_invoice SET admin_delete='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'invoice.php';</script>";
	exit();
}
if(isset($_POST['submit'])) {
	//print_r($_POST); exit;
	
		if(isset($_POST['inv_no']) && $_POST['inv_no']!='') {
			$cirSql = "UPDATE inv_no SET inv_no='".mysqli_real_escape_string($conn, trim($_POST['inv_no']))."', inv_no_pi='".mysqli_real_escape_string($conn, trim($_POST['inv_no_pi']))."' WHERE tbl_id=1";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 			
		} 
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'invoice.php';</script>";
		exit();
	
}
$defaultDate = date('Y-m-d'); // Default: today's date

if (isset($_GET['inv_dt'])) {
    $dateParts = explode('-', $_GET['inv_dt']); // expecting d-m-Y
    if (count($dateParts) === 3) {
        // Validate date and convert to Y-m-d
        $d = $dateParts[0];
        $m = $dateParts[1];
        $y = $dateParts[2];

        if (checkdate((int)$m, (int)$d, (int)$y)) {
            $defaultDate = "$y-$m-$d"; // formatted for input[type=date]
        }
    }
}
/*
if(isset($_POST['submit']) && isset($_POST['daterange'])){
	
	//echo $_POST['daterange'];
	$dt_range = explode(" - ", $_POST['daterange']);
	//print_r($dt_range);
	$start = $dt_range[0];
	$end = $dt_range[1];
	
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
	
	//$_SESSION['suc'] = 'Successfully Uploaded!';	
	echo "<script>window.location = 'report.php';</script>";
	exit();
}
*/
//exit;

//include 'config.php';


include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 2; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " gaccounts where g_id='1' && status=1";

//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
} //exit;
//echo $request_url = "https://graph.facebook.com/v11.0/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";exit;
//echo $access_token; 
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
/* Custom modal height */
.modal-dialog.modal-fullscreen {
  width: 90%;
  max-width: none;
  height: 95vh;
  margin: 10px auto;
}

.modal-content {
  height: 100%;
}

.modal-body {
  height: calc(100% - 60px); /* subtract header height */
  padding: 0;
  overflow: hidden;
}

.modal-body iframe {
  width: 100%;
  height: 100%;
  border: none;
}
/* HTML: <div class="loader-msg"></div> */
/* HTML: <div class="loader-msg"></div> */

.loader-msg {
  width: fit-content;
  font-size: 35px;  /* Adjust the font size */
  font-family: system-ui, sans-serif;
  font-weight: bold;
  text-transform: none;  /* Keep first letter capitalized */
  color: #0000;  /* Transparent text color */
  -webkit-text-stroke: 1px #0b5ed7;  /* Blue text stroke */
  background: conic-gradient(#0b5ed7 0 0) 0/0% 100% no-repeat text;  /* Blue background gradient */
  animation: l1 1s linear infinite;  /* Background animation */
}

@keyframes l1 {
  to {
    background-size: 120% 100%;
  }
}

/* Modern DataTable styles */
#datatable {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  overflow: hidden;
}
#datatable thead th {
  color: #fff;
  font-weight: 700;
  border: none;
  font-size: 15px;
  letter-spacing: 0.5px;
}
#datatable tbody tr {
  transition: background 0.2s;
}
#datatable tbody tr:hover {
  background: #f1f7ff;
}
#datatable td {
  vertical-align: middle;
  font-size: 14px;
}
#datatable .btn-group .btn {
  
  
  font-size: 13px;
  padding: 3px 10px !important;
}
.dataTables_wrapper .dataTables_filter input {
  
}
.dataTables_wrapper .dt-buttons .btn {
  
}
.dataTables_wrapper .dt-buttons .btn:hover {
  background: #1769aa;
}

</style>
<div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">X</button>
        <h4 class="modal-title" id="iframeModalLabel">Dynamic Iframe</h4>
      </div>
      <div class="modal-body">
        <!-- Spinner overlay inside fixed container -->
        <div style="position:relative; height:100%;">
          <div id="iframeLoader"
              style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10;
                      background: #fff; display:flex; justify-content:center; align-items:center;">
            <i class="loader-msg"><span class="loader-msg-text">Loading...</span></i>
          </div>

          <iframe id="modalIframe" src=""></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        
<?php 
									$sqlD=mysqli_query($conn, "SELECT inv_no, inv_no_pi  FROM inv_no WHERE tbl_id=1");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
										//print_r($editData);
										
								?>
        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>
                      <div class="input-group input-group-sm" style="display: inline-flex;">
                        <input 
                          type="date" 
                          class="form-control" 
                          id="dateInput"
                          value="<?php echo $defaultDate; ?>"
                        >
                        <div class="input-group-append">
                           <button class="btn btn-outline-secondary btn-primary btn-sm" type="button" onclick="redirectWithDate()">
                            <i class="fa fa-check"></i>
                          </button>
                        </div>
                      </div>
</li>
                      <li>
						<form method="post" action="" class="form-inline" style="margin-right:50px;">
						<div class="form-group mb-2 mr-3">
							<label for="inv_no" class="mr-2">Last Invoice No:</label>
							<input type="number" name="inv_no" id="inv_no" class="form-control" style="width: 100px;"
							<?php if (isset($editData['inv_no'])) { ?> value="<?php echo $editData['inv_no']; ?>" <?php } ?>
							required>
						</div>

						<div class="form-group mb-2 mr-3">
							<label for="inv_no_pi" class="mr-2">PI No:</label>
							<input type="number" name="inv_no_pi" id="inv_no_pi" class="form-control" style="width: 100px;"
							<?php if (isset($editData['inv_no_pi'])) { ?> value="<?php echo $editData['inv_no_pi']; ?>" <?php } ?>
							required>
						</div>

						<button type="submit" name="submit" class="btn btn-info mb-2">Save</button>
						</form>
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a  class="btn btn-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal"
    data-url="link-accounts.php" data-name="Add Invoice Account"  data-val="edit">Add</a> 
                      	</div>    
                      </li>
                      <li> &nbsp;
                      </li>
                      <!--
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=fb-ads/demo/cron-invoice.php" target="_blank" onclick="return confirm('Are you sure you want to Send Invoice to All? You can not stop to send!');" class="btn btn-warning btn-sm">Send Invoice to All</a> 
                      	</div>    
                      </li>                      
                  -->
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
					<!-- Trigger button -->
                  		<?php include 'alert.php'; 
						$sqlRev=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
						$sqlRev2=mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");

	$fbAccN = $gAccN =  array();
						while($sqlROW=mysqli_fetch_array($sqlRev)) { $fbAccN[$sqlROW["account_id"]] = $sqlROW["name"]; }
						while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2["account_id"]] = $sqlROW2["name"]; }

										//$sqlRev=mysqli_query($conn, "SELECT l.fb_acc, l.g_acc, g.name as gname, f.name as fname FROM accounts_invoice as l INNER JOIN gaccounts as g ON l.g_acc=g.account_id INNER JOIN adAccounts as f ON l.fb_acc=f.account_id");
										
										$sqlRev=mysqli_query($conn, "SELECT l.tbl_id, l.client_name, l.fb_acc, l.g_acc, g.name as gname, f.name as fname, l.camp_name, l.gsheet FROM accounts_invoice as l LEFT JOIN gaccounts as g ON l.g_acc=g.account_id AND g.uid='".$_SESSION['uid']."' LEFT JOIN adAccounts as f ON l.fb_acc=f.account_id AND f.uid='".$_SESSION['uid']."' WHERE l.uid='".$_SESSION['uid']."' AND l.admin_delete=0 order by l.tbl_id desc");
										$i = (($page-1) * $per_page ) + 1;
										$getRows = $accIds = array();
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{
											 $getRows[] = $sqlROW;
											 $accIds[] = $sqlROW['g_acc'];
										}
										//print_r($accIds);
										//exit;
										//$fromDt = date('Ymd', strtotime($_SESSION['stDt']));
										//$enDt = date('Ymd', strtotime($_SESSION['enDt']));
										//include 'download-report.php';
										//ParallelReportDownload::main($accIds);
										//print_r($accIds);
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>                                        
                                    	<th style="width:20%;">Client Name</th>
                                        <th style="width:18%;">Meta</th>
                                    	<!--<th>FB ID</th>-->
                                    	<th style="width:18%;">Google</th>
                                    	 <?php if($_SESSION['user_ty']=='acc') { ?>
                                        <th>Send</th>
                                        <th style="width:18%;">Actions</th>
                                         <?php } else { ?>
                                        <th style="width:18%;">Actions</th>
                                         <?php } ?>
                                    </thead>
                                    <tbody>
                                    	<?php
										
										//$val = get_data('https://graph.facebook.com/v11.0/23843162173490372/insights?level=campaign&ids=[23843162173490372,23843162173460372]&fields=account_id,spend,reach,impressions,account_currency&access_token=REDACTED_FB_TOKEN');
										//d();
										//
										$inv_url = 'https://stage.adrescue.in/loading.php?pg=fb-ads/demo/cron-invoice.php?tbl_id=';
										foreach($getRows as $gR)
										{ 
											//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();
											//https://graph.facebook.com/v11.0/23843162173490372/insights?level=campaign&ids=[23843162173490372,23843162173460372]&fields=account_id,spend,reach,impressions,account_currency&access_token=REDACTED_FB_TOKEN
											//$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["id"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
											//$val = (new AdAccount("act_50358248"));
											//$val = $val->getInsights($fields, $params)->getResponse()->getContent();
											//print_r($val);
											
											//$rows = file('download/adgroup_'.$gR["g_acc"].'.csv');
											//$last_row = array_pop($rows);
											//$data = str_getcsv($last_row);
											//echo 'https://graph.facebook.com/'.$api_ver.'/'.$gR["fb_acc"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'';
											//$val = get_data('https://graph.facebook.com/'.$api_ver.'/act_'.$gR["fb_acc"].'/insights?level=account&fields=spend,reach,impressions&access_token='.$access_token.'&time_range[since]='.date("Y-m-d", strtotime($_SESSION['stDt'])).'&time_range[until]='.date("Y-m-d", strtotime($_SESSION['enDt'])).'');
											//if(isset($val['data'][0]['spend'])) {  $f= $val['data'][0]['spend']; }  else { $f= 0; }
											if($gR["gname"]=='') { $gR["gname"] = '--'; }
											if($gR["fname"]=='') { $gR["fname"] = '--'; }
                       $campName = $gR["camp_name"];
                                              $displayCamp = strpos($campName, '[') !== false ? trim(substr($campName, 0, strpos($campName, '['))) : $campName;
                                            //  echo htmlspecialchars($displayCamp);
                                            if($displayCamp!='') { $client_name = $gR["client_name"].' ('.$displayCamp.')'; } else { $client_name = $gR["client_name"]; }
										?>
                                        <tr>
                                        	<td><?php echo $i; ?></td>
                                            <td><?php 
                                              echo $client_name;
                                            ?></td>
                                        	   <td>
                                              <?php  
                                              if($gR["fb_acc"]!='') { //echo $gR["fb_acc"]; 
                                                $aFB_id = explode(',',$gR["fb_acc"]);
                                                foreach($aFB_id as $f_key => $f_val) {
                                                    echo "&#x2022 ".$fbAccN[$f_val]."<br>";
                                                }
                                              } ?>
                                            </td>
                                            <!--<td><?php echo $gR["fb_acc"]; ?></td>-->
                                            <td>
                                              <?php  
                                              if($gR["g_acc"]!='') { //echo $gR["fb_acc"]; 
                                                $aG_id = explode(',',$gR["g_acc"]);
                                                foreach($aG_id as $g_key => $g_val) {
                                                    echo "&#x2022 ".$gAccN[$g_val]."<br>";
                                                }
                                              } ?>
                                            </td>
                                            <?php if($_SESSION['user_ty']=='acc') { ?>
                                            
											<td>
                        
                          <div class="btn-group btn-group-sm" role="group">
                            <?php $invDt=''; if(isset($_GET['inv_dt'])) { $invDt = '&inv_dt='.$_GET['inv_dt']; } ?>
                            <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="<?php echo $inv_url.''.$gR["tbl_id"].''.$invDt; ?>&invoice" data-name="Invoice: <?php echo $gR["client_name"]; ?>" data-val="invoice" data-tt="tt" title="Send Invoice">IN</button>
                            <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="<?php echo $inv_url.''.$gR["tbl_id"].''.$invDt; ?>&invoice&pi=1" data-name="Proforma Invoice: <?php echo $gR["client_name"]; ?>" data-val="pi" data-tt="tt" title="Proforma Invoice">PI</button>
                            <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="<?php echo $inv_url.''.$gR["tbl_id"].''.$invDt; ?>&invoice&ci=1" data-name="Custom Invoice: <?php echo $gR["client_name"]; ?>" data-val="invoice" data-tt="tt" title="Custom Invoice">CI</button>
                          </div>
											</td>
											<td>
                          <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="wa-preview.php?id=<?php echo $gR["tbl_id"]; ?>" data-name="WhatsApp: <?php echo $gR["client_name"]; ?>" data-val="whatsapp" data-tt="tt"  title="Send Whatsapp"><i class="fa fa-whatsapp"></i></button>
                            <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="reminder-preview.php?id=<?php echo $gR["tbl_id"]; ?>" data-name="Reminder: <?php echo $gR["client_name"]; ?>" data-val="reminder" data-tt="tt"  title="Send Reminder"><i class="fa fa-bell"></i></button>
                            <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="tds-certificate-request.php?id=<?php echo $gR["tbl_id"]; ?>" data-name="TDS Certificate Request: <?php echo $gR["client_name"]; ?>" data-val="tds" data-tt="tt"  title="Send TDS Certificate Request"><i class="fa fa-file-text"></i></button>
                            <?php if($gR["gsheet"]!=''){ ?><a href="invoice-soa-download.php?tab=<?php echo $gR["gsheet"]; ?>" class="btn btn-outline-secondary"  data-tt="tt"  title="Download SOA"><i class="fa fa-download"></i></a><?php } ?>
                            <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="link-accounts.php?id=<?php echo $gR["tbl_id"]; ?>" data-name="Edit: <?php echo $gR["client_name"]; ?>" data-val="edit" data-tt="tt"  title="Edit"><i class="fa fa-pencil"></i></button>
                            <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="invoice.php?pause=<?php echo $gR["tbl_id"]; ?>" data-name="Pause: <?php echo $gR["client_name"]; ?>" data-val="pause" data-tt="tt"  title="Pause"><i class="fa fa-pause"></i></button>
                            <a  class="btn btn-outline-secondary" data-url="invoice.php?del=<?php echo $gR["tbl_id"]; ?>"  onclick="return confirm('Are you sure you want to delete this?');" data-tt="tt"  title="Delete"><i class="fa fa-trash-o"></i></a>
                          </div>
											</td>
                       <?php } else { ?>
                       <td>
                       <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="link-accounts.php?id=<?php echo $gR["tbl_id"]; ?>" data-name="Edit: <?php echo $gR["client_name"]; ?>" data-val="edit" data-tt="tt"  title="Edit"><i class="fa fa-pencil"></i></button>
                        <a  class="btn btn-outline-secondary" data-url="invoice.php?del=<?php echo $gR["tbl_id"]; ?>"  onclick="return confirm('Are you sure you want to delete this?');" data-tt="tt"  title="Delete"><i class="fa fa-trash-o"></i></a>
                       </div>
                       </td>
                        <?php } ?>
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								 
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->


<?php include 'footer.php'; ?>
<style>
/*@import "lesshat";

a[target="_blank"]:after {
  content: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==);
  margin: 0 3px 0 5px;
}*/
</style>
<script>
function redirectWithDate() {
  const selectedDate = document.getElementById('dateInput').value;
  if (selectedDate) {
    const [year, month, day] = selectedDate.split("-");
    const formattedDate = `${day}-${month}-${year}`;
    const currentUrl = window.location.pathname;
    window.location.href = `${currentUrl}?inv_dt=${formattedDate}`;
  }
}
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
      tds: [
        "Loading TDS Request Form",
        "Fetching Client Details",
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

$(document).ready(function() {
  if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#datatable')) {
    $('#datatable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        { extend: 'excel', className: 'btn btn-primary' },
        { extend: 'csv', className: 'btn btn-primary' },
        { extend: 'pdf', className: 'btn btn-primary' },
        { extend: 'print', className: 'btn btn-primary' }
      ],
      pageLength: 25,
      lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']]
    });
  }
});

</script>
