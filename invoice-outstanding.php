<?php 
ini_set('display_errors', 0);
error_reporting(0);

include 'header.php';

$pgHeadline = 'Invoice - Outstanding Summary';

include $dirPath.'functions-report.php'; 
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/modified_time.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk'; 
$result_sheet = get_spreadsheet_modified_time($uId, $spreadsheetId);
//print_r($result_sheet);
?>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
            include 'menu-left.php';
            include 'menu-top.php'; 
        ?>
<?php
// Get last updated time from soa_data
$soa_last_updated = '';
$q_last_up = mysqli_query($conn, "SELECT MAX(created_at) AS last_up FROM soa_data");
if($q_last_up && $rw = mysqli_fetch_assoc($q_last_up)) {
    if(!empty($rw['last_up'])) {
        $soa_last_updated = date('d-m-y, h:i a', strtotime($rw['last_up']));
    }
}
 
// Fetch all clients with their gsheet names and bud_tbl
$clients = array();
$res = mysqli_query($conn, "SELECT tbl_id, client_name, gsheet, bud_tbl FROM accounts_invoice WHERE uid='".$_SESSION['uid']."' AND admin_delete=0 ORDER BY TRIM(LOWER(client_name)) ASC");
while($row = mysqli_fetch_assoc($res)) {
    $clients[] = $row;
}

// Helper: parse dd-mm-YYYY into Y-m for grouping
function ymFromDmY($dmy) {
    $dmy = trim((string)$dmy);
    if($dmy==='') return '';
    $parts = explode('-', $dmy);
    if(count($parts) !== 3) return '';
    $d = (int)$parts[0]; $m = (int)$parts[1]; $y = (int)$parts[2];
    if(!checkdate($m, $d, $y)) return '';
    return sprintf('%04d-%02d', $y, $m);
}

// Define current and last month keys
$now = new DateTime('now');
$curYm = $now->format('Y-m');
$lastMonth = (new DateTime('first day of last month'))->format('Y-m');

// Indian number format without decimals
function formatIndian($num) {
    $num = (int)round((float)$num);
    $neg = $num < 0;
    $n = abs($num);
    $s = (string)$n;
    if(strlen($s) <= 3) return ($neg ? '-' : '').$s;
    $last3 = substr($s, -3);
    $rest = substr($s, 0, -3);
    $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
    return ($neg ? '-' : '').$rest.','.$last3;
}

// Words to ignore in invoice number
$ignoreWords = array('opening balance', 'opending', 'balance');

// Build client -> aggregates
$rows = array();
$seenTabs = array(); // avoid duplicate gsheet tabs
foreach($clients as $c) {
    $clientName = $c['client_name'];
    $sheetTab = trim($c['gsheet']);
    if($sheetTab === '') continue; // no mapping
    if(isset($seenTabs[$sheetTab])) continue; // skip duplicates
    $seenTabs[$sheetTab] = true;

    // Aggregate from soa_data
    $q = "SELECT invoice_no, invoice_date, invoice_description, receivable, receipt, balance
          FROM soa_data WHERE sheet_tab='".$conn->real_escape_string($sheetTab)."'";
    $rs = mysqli_query($conn, $q);

    $totalOutstanding = 0.0;
    $thisMonth = 0.0;
    $lastMonthAmt = 0.0;
    $adsSpend = 0.0;
    $lastPaymentDate = null;
    $lastBalanceVal = 0.0; // track last seen balance (final row total)
    $oldReceivableSum = 0.0; // receivable older than last month
    $openingBalanceSum = 0.0; // sum of opening balance 'balance' cells

    while($r = mysqli_fetch_assoc($rs)) {
        $invNo = strtolower(trim((string)$r['invoice_no']));
        $invDesc = strtolower(trim((string)$r['invoice_description']));
        $invDate = trim((string)$r['invoice_date']); // dd-mm-YYYY
        $ym = ymFromDmY($invDate);

        // Check for Opening Balance row (case-insensitive) and capture its balance
        $isOpeningBalance = (stripos(trim((string)$r['invoice_no']), 'opening balance') === 0);
        if($isOpeningBalance) {
            $ob = (float)str_replace(array(',', ' '), '', (string)$r['balance']);
            if(!is_numeric($ob)) { $ob = 0.0; }
            $openingBalanceSum += $ob;
        }

        // Ignore rows where invoice number includes the ignore words for other aggregations
        $ignore = false;
        foreach($ignoreWords as $w) { if($w!=='' && strpos($invNo, $w)!==false) { $ignore=true; break; } }
        if($ignore) continue;

        // Determine if this row represents a receipt (payment) via NEFT keyword, then skip for receivable aggregation
        $isReceiptRow = (strpos($invNo, 'neft') !== false) || (strpos($invDesc, 'neft') !== false);

        // Track last payment date for NEFT entries
        if($isReceiptRow && $invDate !== '') {
            $paymentDate = DateTime::createFromFormat('d-m-Y', $invDate);
            if($paymentDate && (!$lastPaymentDate || $paymentDate > $lastPaymentDate)) {
                $lastPaymentDate = $paymentDate;
            }
        }

        // Track the last Balance value (sheet's total outstanding is last row)
        $balance = (float)str_replace(array(',', ' '), '', (string)$r['balance']);
        if(!is_numeric($balance)) { $balance = 0.0; }
        $lastBalanceVal = $balance; // overwrite each row; ends with last row's balance

        if(!$isReceiptRow) {
            // For month buckets use receivable (invoice amount)
            $receivable = (float)str_replace(array(',', ' '), '', (string)$r['receivable']);
            if(!is_numeric($receivable)) { $receivable = 0.0; }
            if($ym === $curYm) { $thisMonth += $receivable; }
            elseif($ym === $lastMonth) { $lastMonthAmt += $receivable; }
            else { $oldReceivableSum += $receivable; } // include missing or older dates in Old outs
        }
    }

    // Set total outstanding to last Balance value from the sheet
    $totalOutstanding = $lastBalanceVal;
    // Old outstanding = older receivables + opening balance adjustments
    $oldOutstanding = $oldReceivableSum + $openingBalanceSum;

    // Allocate the outstanding across This -> Last -> Old as per rule
    $allocThis = 0.0; $allocLast = 0.0; $allocOld = 0.0;
    $remaining = max(0.0, (float)$totalOutstanding);
    if($remaining > 0) {
        $allocThis = min($thisMonth, $remaining); $remaining -= $allocThis;
        if($remaining > 0) { $allocLast = min($lastMonthAmt, $remaining); $remaining -= $allocLast; }
        if($remaining > 0) { $allocOld = min($oldOutstanding, $remaining); }
    }

    // Calculate ads spend from budget_reminder table if bud_tbl is set
    if(!empty($c['bud_tbl'])) {
        $budgetQuery = "SELECT fb_spent, g_spent, in_spent, ta_spent FROM budget_reminder WHERE tbl_id = '".$conn->real_escape_string($c['bud_tbl'])."' AND uid='".$_SESSION['uid']."' AND delete_status=0";
        $budgetResult = mysqli_query($conn, $budgetQuery);
        if($budgetRow = mysqli_fetch_assoc($budgetResult)) {
            $fb_spent = $g_spent = $in_spent = $ta_spent = 0;
            
            if($budgetRow["fb_spent"]!='') { 
                $fb_spent = explode(',',$budgetRow["fb_spent"]); 
                $fb_spent = array_sum(array_filter($fb_spent)); 
            }
            if($budgetRow["g_spent"]!='') { 
                $g_spent = explode(',',$budgetRow["g_spent"]); 
                $g_spent = array_sum(array_filter($g_spent)); 
            }
            if($budgetRow["in_spent"]!='') { 
                $in_spent = explode(',',$budgetRow["in_spent"]); 
                $in_spent = array_sum(array_filter($in_spent)); 
            }
            if($budgetRow["ta_spent"]!='') { 
                $ta_spent = explode(',',$budgetRow["ta_spent"]); 
                $ta_spent = array_sum(array_filter($ta_spent)); 
            }
            
            $adsSpend = $fb_spent + $g_spent + $in_spent + $ta_spent;
        }
    }

    // Skip clients with all zero values
    if((float)$totalOutstanding == 0.0 && (float)$thisMonth == 0.0 && (float)$lastMonthAmt == 0.0) {
        continue;
    }

    // Calculate days since last payment
    $daysSinceLastPayment = null;
    if($lastPaymentDate) {
        $now = new DateTime();
        $daysSinceLastPayment = $now->diff($lastPaymentDate)->days;
    }

    $rows[] = array(
        'client' => $clientName,
        'gsheet' => $sheetTab,
        'tbl_id' => $c['tbl_id'],
        'total_outstanding' => round($totalOutstanding, 2),
        'ads_spend' => round($adsSpend, 2),
        'this_month' => round($allocThis, 2),
        'last_month' => round($allocLast, 2),
        'old_outs' => round($allocOld, 2),
        'last_payment_days' => $daysSinceLastPayment
    );
}

?>
<style>
#datatable thead th { color:#fff; }

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
  background: #2196f3;
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
<div class="right_col" role="main">
  <div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
      <div class="x_panel">
        <div class="x_title">
          <h2><?php echo $pgHeadline; ?></h2>
          <ul class="nav navbar-right panel_toolbox">
            <li style="padding-top: 10px;"> <i class="fa fa-table" style="font-size: 15px; color: #25a129;"></i> <?php if($soa_last_updated!=''){ ?><small style=" font-size:12px; color: #25a129;"  data-tt="tt" title="Last updated in SOA sheet by team">Sheet Updated: <?php echo $result_sheet['modified_time_ist'].' | '.$result_sheet['last_modified_by']; ?></small><?php } ?> &nbsp; | &nbsp;</li>
                      
                      <li style="padding-top: 10px;"> <i class="fa fa-clock-o" style="font-size: 15px; color: #25a129;"></i> <?php if($soa_last_updated!=''){ ?><small style=" font-size:12px; color:#25a129;" data-tt="tt" title="Last fetched from SOA sheet by AdRescue">Last Sync: <?php echo $soa_last_updated; ?></small><?php } ?></li>
                      <li>
                        <a href="loading.php?pg=soa-db-cron.php"  class="btn btn-primary btn-sm">Sync SOA sheet</a> 
                        </li>
                        
                        </ul>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <?php include 'alert.php'; ?>
          <table id="datatable" class="table table-hover table-striped table-bordered">
            <thead>
              <tr>
                <th>SNo</th>
                <th>Client Name</th>
                <th>Total Outs.</th>
                <th>Ads Spend</th>
                <th>This Month</th>
                <th>Last Month</th>
                <th>Old Outs.</th>
                <th>Last Payment</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php $i=1; foreach($rows as $r) { ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($r['gsheet']); ?></td>
                <td class="text-right"><?php echo ((float)$r['total_outstanding'] != 0.0) ? formatIndian($r['total_outstanding']) : '-'; ?></td>
                <td class="text-right"><?php echo ((float)$r['ads_spend'] != 0.0) ? formatIndian($r['ads_spend']) : '-'; ?></td>
                <td class="text-right"><?php echo ((float)$r['this_month'] != 0.0) ? formatIndian($r['this_month']) : '-'; ?></td>
                <td class="text-right"><?php echo ((float)$r['last_month'] != 0.0) ? formatIndian($r['last_month']) : '-'; ?></td>
                <td class="text-right"><?php echo ((float)$r['old_outs'] != 0.0) ? formatIndian($r['old_outs']) : '-'; ?></td>
                <td class="text-right"><?php echo $r['last_payment_days'] !== null ? $r['last_payment_days'] . ' days' : '-'; ?></td>
                <td class="text-center">
                  <?php if(!empty($r['gsheet'])) { ?>
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-success btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="wa-preview.php?id=<?php echo $r['tbl_id']; ?>" data-name="WhatsApp Reminder: <?php echo htmlspecialchars($r['gsheet']); ?>" data-val="reminder"  data-tt="tt" title="Send WhatsApp Reminder">
                        <i class="fa fa-whatsapp"></i>
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="reminder-preview.php?id=<?php echo $r['tbl_id']; ?>" data-name="Reminder: <?php echo htmlspecialchars($r['gsheet']); ?>" data-val="reminder"  data-tt="tt" title="Send Email Reminder">
                        <i class="fa fa-envelope"></i>
                      </button>
                      <a href="invoice-soa-download.php?tab=<?php echo urlencode($r['gsheet']); ?>" class="btn btn-outline-primary btn-sm"  data-tt="tt" title="Download SOA">
                        <i class="fa fa-download"></i>
                      </a>
                    </div>
                  <?php } ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
            <tfoot>
              <?php 
                $grand_total = 0.0; $grand_ads = 0.0; $grand_this = 0.0; $grand_last = 0.0; $grand_old = 0.0;
                foreach($rows as $r) { 
                    $grand_total += (float)$r['total_outstanding'];
                    $grand_ads += (float)$r['ads_spend'];
                    $grand_this += (float)$r['this_month'];
                    $grand_last += (float)$r['last_month'];
                    $grand_old += (float)$r['old_outs'];
                }
              ?>
              <tr>
                <th colspan="2" class="text-right">Total</th>
                <th class="text-right"><?php echo ($grand_total!=0.0)?formatIndian($grand_total):'-'; ?></th>
                <th class="text-right"><?php echo ($grand_ads!=0.0)?formatIndian($grand_ads):'-'; ?></th>
                <th class="text-right"><?php echo ($grand_this!=0.0)?formatIndian($grand_this):'-'; ?></th>
                <th class="text-right"><?php echo ($grand_last!=0.0)?formatIndian($grand_last):'-'; ?></th>
                <th class="text-right"><?php echo ($grand_old!=0.0)?formatIndian($grand_old):'-'; ?></th>
                <th class="text-right">-</th>
                <th class="text-right"></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal for WhatsApp Reminder -->
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

<?php include 'footer.php'; ?>
      </div>
    </div>
  </body>

<script>
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
        "Preparing WhatsApp Template",
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
      pageLength: 100,
      order: [[1, 'asc']]
    });
  }
});
</script>

