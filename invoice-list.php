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
$pgHeadline = 'Invoices - Sent Items (Monthly View)';
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
			mysqli_query($conn, $cirSql) or die(mysqli_error($conn)); 			
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

// Function to generate last 5 months (excluding current month)
function getLastFiveMonths() {
    $months = [];
    for ($i = 1; $i <= 5; $i++) { // Start from 1 to exclude current month
        $date = date('Y-m-01', strtotime("-$i month"));
        $months[] = [
            'value' => $date,
            'label' => date('F-Y', strtotime($date)) // Changed to F-Y format (August-2025)
        ];
    }
    return $months;
}
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
  -webkit-text-stroke: 1px #1d69ab;  /* Blue text stroke */
  background: conic-gradient(#1d69ab 0 0) 0/0% 100% no-repeat text;  /* Blue background gradient */
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
.form-control {
  border-radius: 6px;
  border: 1px solid #2196f3;
  box-shadow: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

/* Line items styling */
.line-item-container {
  margin: 0;
  padding: 0;
}

.line-item-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 5px;
  font-size: 12px;
  padding: 2px 0;
}

.line-item-label {
  font-size: 11px;
  flex: 1;
  text-align: left;
  word-wrap: break-word;
  margin-right: 10px;
}

.line-item-amount {
  color: #0089ff;
  font-weight: bold;
  text-align: right;
  white-space: nowrap;
  min-width: 50px;
}
.glyphicon-download-alt, .fa-download { color: #0089ff; }
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
                        <div class="form-group">
                          <select class="form-control" id="invoiceTypeSelect" onchange="redirectWithFilters()">
                            <option value="invoice" <?php echo (!isset($_GET['type']) || $_GET['type'] == 'invoice') ? 'selected' : ''; ?>>Invoice</option>
                            <option value="pi" <?php echo (isset($_GET['type']) && $_GET['type'] == 'pi') ? 'selected' : ''; ?>>Proforma Invoice</option>
                          </select>
                        </div>
                      </li>
                      <li>
                        <div class="form-group">
                          <select class="form-control" id="monthSelect" onchange="redirectWithFilters()">
                            <option value="">Select Month</option>
                            <?php 
                            $months = getLastFiveMonths();
                            $defaultMonth = $months[0]['value']; // Previous month (first option)
                            foreach($months as $index => $month): ?>
                              <option value="<?php echo $month['value']; ?>" <?php echo (isset($_GET['month']) && $_GET['month'] == $month['value']) || (!isset($_GET['month']) && $index == 0) ? 'selected' : ''; ?>>
                                <?php echo $month['label']; ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </li>
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
					<!-- Trigger button -->
                  		<?php 
						$sqlRev=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
						$sqlRev2=mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");

	$fbAccN = $gAccN =  array();
						while($sqlROW=mysqli_fetch_array($sqlRev)) { $fbAccN[$sqlROW["account_id"]] = $sqlROW["name"]; }
						while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2["account_id"]] = $sqlROW2["name"]; }

										//$sqlRev=mysqli_query($conn, "SELECT l.fb_acc, l.g_acc, g.name as gname, f.name as fname FROM accounts_invoice as l INNER JOIN gaccounts as g ON l.g_acc=g.account_id INNER JOIN adAccounts as f ON l.fb_acc=f.account_id");
										//echo "SELECT l.tbl_id, l.client_name, l.fb_acc, l.g_acc, g.name as gname, f.name as fname, l.camp_name FROM accounts_invoice as l LEFT JOIN gaccounts as g ON l.g_acc=g.account_id AND g.uid='".$_SESSION['uid']."' LEFT JOIN adAccounts as f ON l.fb_acc=f.account_id AND f.uid='".$_SESSION['uid']."' WHERE l.uid='".$_SESSION['uid']."' AND l.admin_delete=0 order by l.client_name asc";
										$sqlRev=mysqli_query($conn, "SELECT l.tbl_id, l.client_name, l.fb_acc, l.g_acc, g.name as gname, f.name as fname, l.camp_name, l.gsheet FROM accounts_invoice as l LEFT JOIN gaccounts as g ON l.g_acc=g.account_id AND g.uid='".$_SESSION['uid']."' LEFT JOIN adAccounts as f ON l.fb_acc=f.account_id AND f.uid='".$_SESSION['uid']."' WHERE l.uid='".$_SESSION['uid']."' AND l.admin_delete=0 ORDER BY TRIM(LOWER(l.client_name)) ASC");
										$i = (($page-1) * $per_page ) + 1;
										$getRows = $accIds = array();
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{
											 $getRows[] = $sqlROW;
											 $accIds[] = $sqlROW['g_acc'];
										}
										//d($getRows);
										//exit;
										//$fromDt = date('Ymd', strtotime($_SESSION['stDt']));
										//$enDt = date('Ymd', strtotime($_SESSION['enDt']));
										//include 'download-report.php';
										//ParallelReportDownload::main($accIds);
										//print_r($accIds);
										
										// Get selected invoice type and month
										$selectedInvoiceType = isset($_GET['type']) ? $_GET['type'] : 'invoice';
										$selectedMonth = isset($_GET['month']) ? date('F-Y', strtotime($_GET['month'])) : date('F-Y', strtotime('-1 month'));
										
										// Build SQL query based on invoice type
										$invTypeCondition = ($selectedInvoiceType == 'pi') ? "inv_ty='pi'" : "inv_ty!='pi'";
										
										// Fetch invoice2 data for approved invoices - group by client and month
										$invoice2Data = array();
										$sqlInvoice2 = mysqli_query($conn, "SELECT acc_tbl_id, inv_mon, grand_tot, line_items FROM invoice2 WHERE approved='yes' AND $invTypeCondition ORDER BY acc_tbl_id, inv_mon");
										while($invoiceRow = mysqli_fetch_array($sqlInvoice2)) {
											$accTblId = $invoiceRow['acc_tbl_id'];
											$invMon = $invoiceRow['inv_mon'];
											
											// If this client-month combination already exists, merge the data
											if(isset($invoice2Data[$accTblId][$invMon])) {
												// Merge grand totals (comma-separated)
												$existingGrandTot = $invoice2Data[$accTblId][$invMon]['grand_tot'];
												$newGrandTot = $invoiceRow['grand_tot'];
												$invoice2Data[$accTblId][$invMon]['grand_tot'] = $existingGrandTot . ',' . $newGrandTot;
												
												// Merge line items by combining the serialized data
												$existingLineItems = $invoice2Data[$accTblId][$invMon]['line_items'];
												$newLineItems = $invoiceRow['line_items'];
												
												// Unserialize existing data
												$existingData = !empty($existingLineItems) ? unserialize($existingLineItems) : array();
												$newData = !empty($newLineItems) ? unserialize($newLineItems) : array();
												
												// Merge the arrays
												$mergedData = array_merge($existingData, $newData);
												
												// Serialize back
												$invoice2Data[$accTblId][$invMon]['line_items'] = serialize($mergedData);
											} else {
												// First time for this client-month combination
												$invoice2Data[$accTblId][$invMon] = [
													'grand_tot' => $invoiceRow['grand_tot'],
													'line_items' => $invoiceRow['line_items']
												];
											}
										}
										
										// Function to parse line items and categorize amounts
										function parseLineItems($lineItemsData) {
											$adsSpendItems = [];
											$adsMgntItems = [];
											$retainerOtherItems = [];
											$totalSubtotal = 0;
											
											if(!empty($lineItemsData)) {
												$data = unserialize($lineItemsData);
												
												if(is_array($data)) {
													foreach($data as $invoice) {
														if(isset($invoice['line_items']) && is_array($invoice['line_items'])) {
															foreach($invoice['line_items'] as $item) {
																$label = $item['label'];
																$amount = floatval($item['amount']);
																$labelLower = strtolower($label);
																
																// Ads Management: contains "ads management fees" (check this first)
																if(strpos($labelLower, 'ads management fees') !== false) {
																	$adsMgntItems[] = [
																		'label' => $label,
																		'amount' => $amount
																	];
																}
																// Ads Spend: contains "ads spend" or "adjustment of" (but not ads management fees)
																elseif(strpos($labelLower, 'ads spend') !== false || strpos($labelLower, 'adjustment of') !== false) {
																	$adsSpendItems[] = [
																		'label' => $label,
																		'amount' => $amount
																	];
																}
																// Retainer & Other: everything else
																else {
																	$retainerOtherItems[] = [
																		'label' => $label,
																		'amount' => $amount
																	];
																}
															}
														}
														
														// Add subtotal to total (without GST)
														if(isset($invoice['totals']['subtotal'])) {
															$totalSubtotal += floatval($invoice['totals']['subtotal']);
														}
													}
												}
											}
											
											return [
												'ads_spend_items' => $adsSpendItems,
												'ads_mgnt_items' => $adsMgntItems,
												'retainer_other_items' => $retainerOtherItems,
												'total_subtotal' => $totalSubtotal
											];
										}
										
										// Function to format numbers in Indian format
										function formatIndianNumber($num) {
											$isNegative = $num < 0;
											$num = abs(round($num)); // Work with absolute value
											$explrestunits = "";
											if(strlen($num) > 3) {
												$lastthree = substr($num, strlen($num)-3, strlen($num));
												$restunits = substr($num, 0, strlen($num)-3);
												$restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits;
												$expunit = str_split($restunits, 2);
												for($i=0; $i<sizeof($expunit); $i++) {
													if($i==0) {
														$explrestunits .= (int)$expunit[$i].",";
													} else {
														$explrestunits .= $expunit[$i].",";
													}
												}
												$thecash = $explrestunits.$lastthree;
											} else {
												$thecash = $num;
											}
											
											// Remove leading zero and comma for amounts less than 1 lakh
											if(strpos($thecash, '0,') === 0) {
												$thecash = substr($thecash, 2);
											}
											
											// Add negative sign back if needed
											if($isNegative) {
												$thecash = '-' . $thecash;
											}
											
											return $thecash;
										}
										
										// Function to format line items for display
										function formatLineItems($items) {
											if(empty($items)) {
												return '--';
											}
											
											$output = '<div class="line-item-container">';
											foreach($items as $item) {
												$output .= '<div class="line-item-row">' . 
													'<span class="line-item-label">• ' . htmlspecialchars($item['label']) . '</span>' . 
													'<span class="line-item-amount">' . formatIndianNumber($item['amount']) . '</span>' . 
													'</div>';
											}
											$output .= '</div>';
											return $output;
										}
										
										// Function to fetch outstanding data from SOA API
										function getOutstandingData() {
											$url = 'https://stage.adrescue.in/soa-api.php';
											$ch = curl_init();
											curl_setopt($ch, CURLOPT_URL, $url);
											curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
											curl_setopt($ch, CURLOPT_TIMEOUT, 10);
											$data = curl_exec($ch);
											curl_close($ch);
											
											if($data) {
												$jsonData = json_decode($data, true);
												if(isset($jsonData['outstanding'])) {
													return $jsonData['outstanding'];
												}
											}
											return array();
										}
										
										// Function to map client name with outstanding data
										function getOutstandingForClient($clientName, $outstandingData) {
											// Clean and normalize client name for matching
											$cleanClientName = trim(strtolower($clientName));
											
											foreach($outstandingData as $outstanding) {
												$cleanOutstandingClient = trim(strtolower($outstanding['client']));
												
												// Direct match
												if($cleanClientName === $cleanOutstandingClient) {
													return $outstanding;
												}
												
												// Partial match for common variations
												if(strpos($cleanClientName, $cleanOutstandingClient) !== false || 
												   strpos($cleanOutstandingClient, $cleanClientName) !== false) {
													return $outstanding;
												}
												
												// Handle common client name variations
												$variations = array(
													'rwd' => array('rwd waterfront', 'waterfront'),
													'vistawh' => array('vista marbella', 'vista'),
													'bigcup' => array('big cup'),
													'vibrant' => array('vibrant offices', 'vibrant homes'),
													'hasbro' => array('hasbro toys'),
													'azadi' => array('digital azadi', 'azadi digital'),
													'urbando' => array('urbando homes'),
													'pythagurus' => array('pythagorus'),
													'ambili' => array('ambili homes'),
													'amvisha' => array('amvisha homes'),
													'casa' => array('casa homes'),
													'sounds good' => array('soundsgood'),
													'eden park' => array('pragnya eden park', 'pragnya'),
													'kvce' => array('kvcet'),
													'mi life style' => array('lifestyle', 'mi lifestyle'),
													'kmc' => array('kmc homes'),
													'technofunda' => array('techno funda'),
													'old kent' => array('oke', 'old kent estates'),
													'sofakings' => array('sofa kings'),
													'cams' => array('cams homes'),
													'crescent' => array('crescent homes'),
													'mizaj' => array('mizaj homes'),
													'mahendra' => array('mahendra homes'),
													'pvr' => array('pvr homes')
												);
												
												foreach($variations as $key => $values) {
													if($cleanOutstandingClient === $key) {
														foreach($values as $variation) {
															if(strpos($cleanClientName, $variation) !== false) {
																return $outstanding;
															}
														}
													}
												}
											}
											
											return null;
										}
										
										// Fetch outstanding data from SOA API
										$outstandingData = getOutstandingData();
										
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>                                        
                                    	<th>Client Name</th>
                                    	<th>Ads Spend</th>
                                    	<th>Ads Management Fee</th>
                                    	<th>Retainer & Others</th>
                                    	<th>Total</th>
                                    	<th>Tot(+GST)</th>
                                    	<th>Outstanding</th>
                                      <th>SOA</th>
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
										?>
                                        <tr>
                                        	<td><?php echo $i; ?></td>
                                            <td><?php 
                                              $campName = $gR["camp_name"];
                                              $displayCamp = strpos($campName, '[') !== false ? trim(substr($campName, 0, strpos($campName, '['))) : $campName;
                                              
                                              if(!empty($displayCamp) && $displayCamp != '--') {
                                                  echo $gR["client_name"] . ' (' . htmlspecialchars($displayCamp) . ')';
                                              } else {
                                                  echo $gR["client_name"];
                                              }
                                            ?></td>
                                            <td><?php 
                                              // Ads Spend from parsed line items
                                              $accTblId = $gR["tbl_id"];
                                              $adsSpendDisplay = '--';
                                              
                                              if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                  $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                  $parsedData = parseLineItems($lineItemsData);
                                                  $adsSpendDisplay = formatLineItems($parsedData['ads_spend_items']);
                                              }
                                              echo $adsSpendDisplay;
                                            ?></td>
                                            <td><?php 
                                              // Ads Mgnt from parsed line items
                                              $adsMgntDisplay = '--';
                                              
                                              if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                  $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                  $parsedData = parseLineItems($lineItemsData);
                                                  $adsMgntDisplay = formatLineItems($parsedData['ads_mgnt_items']);
                                              }
                                              echo $adsMgntDisplay;
                                            ?></td>
                                            <td><?php 
                                              // Retainer & Oth. from parsed line items
                                              $retainerOtherDisplay = '--';
                                              
                                              if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                  $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                  $parsedData = parseLineItems($lineItemsData);
                                                  $retainerOtherDisplay = formatLineItems($parsedData['retainer_other_items']);
                                              }
                                              echo $retainerOtherDisplay;
                                            ?></td>
                                            <td  class="text-right"><?php 
                                              // Total (subtotal without GST) from parsed line items
                                              $totalSubtotal = '--';
                                              
                                              if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                  $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                  $parsedData = parseLineItems($lineItemsData);
                                                  $totalSubtotal = $parsedData['total_subtotal'] > 0 ? formatIndianNumber($parsedData['total_subtotal']) : '--';
                                              }
                                              echo $totalSubtotal;
                                            ?></td>
                                            <td  class="text-right"><?php 
                                              // Total - Inc. GST from invoice2 table
                                              $totalWithGST = '--';
                                              
                                              if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                  $grandTot = $invoice2Data[$accTblId][$selectedMonth]['grand_tot'];
                                                  // Handle comma-separated values
                                                  if(strpos($grandTot, ',') !== false) {
                                                      $totals = explode(',', $grandTot);
                                                      $totalWithGST = formatIndianNumber(array_sum($totals));
                                                  } else {
                                                      $totalWithGST = formatIndianNumber($grandTot);
                                                  }
                                              }
                                              echo $totalWithGST;
                                            ?></td>
                                            <td data-sort="<?php 
                                              // Outstanding amount from SOA API
                                              $clientName = $gR["client_name"];
                                              $outstandingInfo = getOutstandingForClient($clientName, $outstandingData);
                                              
                                              if($outstandingInfo && !empty($outstandingInfo['outstanding'])) {
                                                  $outstandingAmount = $outstandingInfo['outstanding'];
                                                  $numericValue = floatval(str_replace(',', '', $outstandingAmount));
                                                  echo $numericValue;
                                              } else {
                                                  echo '0';
                                              }
                                            ?>"  class="text-right"><?php 
                                              if($outstandingInfo && !empty($outstandingInfo['outstanding'])) {
                                                  $outstandingAmount = $outstandingInfo['outstanding'];
                                                  $numericValue = floatval(str_replace(',', '', $outstandingAmount));
                                                  $formattedAmount = formatIndianNumber($numericValue);
                                                  $color = ($numericValue < 0) ? 'color: #00aa00;' : 'color: #ff4444;';
                                                  echo '<span style="font-weight: bold; ' . $color . '">' . $formattedAmount . '</span>';
                                              } else {
                                                  echo '--';
                                              }
                                            ?></td>
                                            <td class="text-center">
                                              <?php if($gR["gsheet"]!=''){ ?><a href="invoice-soa-download.php?tab=<?php echo $gR["gsheet"]; ?>" title="Download SOA"><i class="fa fa-download"></i></a><?php } ?>
                                              </td>
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                    <tfoot>
                                        <tr style="background-color: #e4f3ff; font-weight: bold;">
                                            <td colspan="2" style="text-align: center;">TOTAL</td>
                                            <td><?php 
                                                // Calculate total for Ads Spend
                                                $totalAdsSpend = 0;
                                                foreach($getRows as $gR) {
                                                    $accTblId = $gR["tbl_id"];
                                                    if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                        $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                        $parsedData = parseLineItems($lineItemsData);
                                                        foreach($parsedData['ads_spend_items'] as $item) {
                                                            $totalAdsSpend += $item['amount'];
                                                        }
                                                    }
                                                }
                                                echo $totalAdsSpend > 0 ? formatIndianNumber($totalAdsSpend) : '--';
                                            ?></td>
                                            <td><?php 
                                                // Calculate total for Ads Management Fee
                                                $totalAdsMgnt = 0;
                                                foreach($getRows as $gR) {
                                                    $accTblId = $gR["tbl_id"];
                                                    if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                        $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                        $parsedData = parseLineItems($lineItemsData);
                                                        foreach($parsedData['ads_mgnt_items'] as $item) {
                                                            $totalAdsMgnt += $item['amount'];
                                                        }
                                                    }
                                                }
                                                echo $totalAdsMgnt > 0 ? formatIndianNumber($totalAdsMgnt) : '--';
                                            ?></td>
                                            <td><?php 
                                                // Calculate total for Retainer & Others
                                                $totalRetainerOther = 0;
                                                foreach($getRows as $gR) {
                                                    $accTblId = $gR["tbl_id"];
                                                    if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                        $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                        $parsedData = parseLineItems($lineItemsData);
                                                        foreach($parsedData['retainer_other_items'] as $item) {
                                                            $totalRetainerOther += $item['amount'];
                                                        }
                                                    }
                                                }
                                                echo $totalRetainerOther > 0 ? formatIndianNumber($totalRetainerOther) : '--';
                                            ?></td>
                                            <td class="text-right"><?php 
                                                // Calculate total for Total (subtotal)
                                                $totalSubtotal = 0;
                                                foreach($getRows as $gR) {
                                                    $accTblId = $gR["tbl_id"];
                                                    if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                        $lineItemsData = $invoice2Data[$accTblId][$selectedMonth]['line_items'];
                                                        $parsedData = parseLineItems($lineItemsData);
                                                        $totalSubtotal += $parsedData['total_subtotal'];
                                                    }
                                                }
                                                echo $totalSubtotal > 0 ? formatIndianNumber($totalSubtotal) : '--';
                                            ?></td>
                                            <td  class="text-right"><?php 
                                                // Calculate total for Total (+GST)
                                                $totalWithGST = 0;
                                                foreach($getRows as $gR) {
                                                    $accTblId = $gR["tbl_id"];
                                                    if(isset($invoice2Data[$accTblId][$selectedMonth])) {
                                                        $grandTot = $invoice2Data[$accTblId][$selectedMonth]['grand_tot'];
                                                        if(strpos($grandTot, ',') !== false) {
                                                            $totals = explode(',', $grandTot);
                                                            $totalWithGST += array_sum($totals);
                                                        } else {
                                                            $totalWithGST += floatval($grandTot);
                                                        }
                                                    }
                                                }
                                                echo $totalWithGST > 0 ? formatIndianNumber($totalWithGST) : '--';
                                            ?></td>
                                            <td  class="text-right"><?php 
                                                // Calculate total outstanding for all unique clients (by client name from JSON)
                                                $totalOutstanding = 0;
                                                $processedClients = array();
                                                
                                                foreach($getRows as $gR) {
                                                    $clientName = $gR["client_name"];
                                                    
                                                    // Skip if we've already processed this client name
                                                    if(in_array($clientName, $processedClients)) {
                                                        continue;
                                                    }
                                                    
                                                    $outstandingInfo = getOutstandingForClient($clientName, $outstandingData);
                                                    if($outstandingInfo && !empty($outstandingInfo['outstanding'])) {
                                                        $outstandingAmount = str_replace(',', '', $outstandingInfo['outstanding']);
                                                        $totalOutstanding += floatval($outstandingAmount);
                                                    }
                                                    
                                                    // Mark this client name as processed
                                                    $processedClients[] = $clientName;
                                                }
                                                $color = ($totalOutstanding < 0) ? 'color: #00aa00;' : 'color: #ff4444;';
                                                echo '<span style="font-weight: bold; ' . $color . '">' . formatIndianNumber($totalOutstanding) . '</span>';
                                            ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
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
function redirectWithFilters() {
  const selectedMonth = document.getElementById('monthSelect').value;
  const selectedType = document.getElementById('invoiceTypeSelect').value;
  
  const currentUrl = window.location.pathname;
  let params = [];
  
  if (selectedMonth) {
    params.push(`month=${selectedMonth}`);
  }
  
  if (selectedType) {
    params.push(`type=${selectedType}`);
  }
  
  const queryString = params.length > 0 ? '?' + params.join('&') : '';
  window.location.href = `${currentUrl}${queryString}`;
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
      pageLength: 50,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      columnDefs: [
        {
          targets: 7, // Outstanding column (8th column, 0-indexed)
          type: 'num',
          render: function(data, type, row) {
            if (type === 'sort' || type === 'type') {
              // Use the data-sort attribute for sorting
              var $cell = $(row[7]);
              return $cell.attr('data-sort') || 0;
            }
            return data;
          }
        }
      ]
    });
  }
});

</script>
