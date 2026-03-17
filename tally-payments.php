<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'header.php';
Auth();
$pgHeadline = 'Tally Payments';
$pgID = 7;

// Defaults for date filter (this month)
$defaultStart = date('Y-m-01');
$defaultEnd   = date('Y-m-d');

$st = isset($_GET['st']) && $_GET['st'] != '' ? $_GET['st'] : $defaultStart;
$en = isset($_GET['en']) && $_GET['en'] != '' ? $_GET['en'] : $defaultEnd;

// Validate dates (expecting Y-m-d)
foreach (['st' => &$st, 'en' => &$en] as $k => &$d) {
    $ts = strtotime($d);
    if ($ts === false) { $d = ($k === 'st') ? $defaultStart : $defaultEnd; }
    $d = date('Y-m-d', strtotime($d));
}
unset($d);

// Voucher type filter: all | payment | receipt
$voucherType = isset($_GET['voucher_type']) ? strtolower(trim($_GET['voucher_type'])) : 'all';
if (!in_array($voucherType, ['all','payment','receipt'])) { $voucherType = 'all'; }

function formatIndianNumber($num) {
    $isNegative = $num < 0;
    $num = abs(round($num, 2));
    $explrestunits = "";
    if(strlen($num) > 3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3);
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits;
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            if($i==0) { $explrestunits .= (int)$expunit[$i].","; }
            else { $explrestunits .= $expunit[$i].","; }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    if(strpos($thecash, '0,') === 0) { $thecash = substr($thecash, 2); }
    if($isNegative) { $thecash = '-' . $thecash; }
    return $thecash;
}

// Function to convert YYYYMMDD to Y-m-d format
function convertDate($dateStr) {
    if(strlen($dateStr) == 8) {
        $year = substr($dateStr, 0, 4);
        $month = substr($dateStr, 4, 2);
        $day = substr($dateStr, 6, 2);
        return $year . '-' . $month . '-' . $day;
    }
    return $dateStr;
}
?>
<style>
#datatable { background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow:hidden; }
#datatable thead th { background:#2196f3; color:#fff; font-weight:700; border:none; font-size:15px; letter-spacing:0.5px; }
#datatable tbody tr:hover { background:#f1f7ff; }
#datatable td { vertical-align:middle; font-size:14px; }
.filters { display:flex; align-items:center; gap:10px; flex-wrap:nowrap; }
.filters li { display:flex; align-items:center; white-space:nowrap; }
.filters .form-control { border-radius:6px; border:1px solid #2196f3; height:34px; padding:6px 10px; }
.date-pill { padding:6px 10px; background:#fff; border:1px solid #2196f3; border-radius:6px; display:flex; align-items:center;  height:34px; line-height:20px; }
.date-pill i { margin-right:6px; color:#2196f3; }
.date-pill span { white-space:nowrap; }
.daterangepicker { z-index: 3000; }
.x_title span { color:#4a4646;}
</style>

<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <?php 
        include 'menu-left.php';
        include 'menu-top.php';
      ?>

      <div class="right_col" role="main">
        <div class="row">
          <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
              <div class="x_title">
                <h2><?php echo $pgHeadline; ?></h2>
                <ul class="nav navbar-right panel_toolbox filters">
                  <li style="min-width:160px;">
                    <select class="form-control" id="voucherTypeSelect" onchange="applyFilters()">
                      <option value="all" <?php echo ($voucherType=='all')?'selected':''; ?>>All</option>
                      <option value="payment" <?php echo ($voucherType=='payment')?'selected':''; ?>>Payment</option>
                      <option value="receipt" <?php echo ($voucherType=='receipt')?'selected':''; ?>>Receipt</option>
                    </select>
                  </li>
                  <li style="padding-left:10px;">
                    <div id="reportrange_created" class="date-pill" style="cursor:pointer;">
                      <i class="fa fa-calendar"></i>
                      <span></span> <b class="caret"></b>
                    </div>
                    <input type="hidden" id="st" value="<?php echo htmlspecialchars($st); ?>" />
                    <input type="hidden" id="en" value="<?php echo htmlspecialchars($en); ?>" />
                  </li>
                </ul>
                <div class="clearfix"></div>
              </div>

              <div class="x_content">
                <?php 
                include 'alert.php'; 
                
                // Build query - fetch records where voucher_date is in range OR NULL (we'll filter NULL ones by JSON date)
                $where = [];
                $where[] = "(voucher_date BETWEEN '".mysqli_real_escape_string($conn,$st)."' AND '".mysqli_real_escape_string($conn,$en)."' OR voucher_date IS NULL)";
                $whereSql = implode(' AND ', $where);

                $sql = mysqli_query($conn, "SELECT id, unique_key, voucher_date, voucher_no, voucher_type, data FROM tally_payments WHERE $whereSql ORDER BY voucher_date DESC, id DESC");

                $rows = [];
                while($r = mysqli_fetch_assoc($sql)) { 
                    $rows[] = $r; 
                }
                
                // Process rows and extract entries
                $tableRows = [];
                $totalDebit = 0;
                $totalCredit = 0;
                $processedVouchers = []; // Track processed voucher numbers to show only first entry
                
                foreach($rows as $row) {
                    $jsonData = json_decode($row['data'], true);
                    if(!$jsonData || !isset($jsonData['entries'])) continue;
                    
                    // Get date from JSON or use voucher_date
                    $entryDate = '';
                    if(isset($jsonData['date']) && !empty($jsonData['date'])) {
                        $entryDate = convertDate($jsonData['date']);
                    } elseif($row['voucher_date']) {
                        $entryDate = $row['voucher_date'];
                    }
                    
                    // Skip if date is not in range (for records with NULL voucher_date)
                    if($entryDate) {
                        $entryDateYmd = date('Y-m-d', strtotime($entryDate));
                        if($entryDateYmd < $st || $entryDateYmd > $en) {
                            continue;
                        }
                    }
                    
                    // Format date for display
                    $displayDate = $entryDate ? date('d-m-Y', strtotime($entryDate)) : '--';
                    
                    // Get voucher details
                    $vchType = isset($jsonData['type']) ? $jsonData['type'] : ($row['voucher_type'] ? $row['voucher_type'] : '--');
                    $vchNo = isset($jsonData['voucher_no']) ? $jsonData['voucher_no'] : ($row['voucher_no'] ? $row['voucher_no'] : '--');
                    
                    // Filter by voucher type
                    if($voucherType != 'all') {
                        $vchTypeLower = strtolower($vchType);
                        if($vchTypeLower != $voucherType) {
                            continue;
                        }
                    }
                    
                    // Skip if this voucher number has already been processed
                    if(isset($processedVouchers[$vchNo])) {
                        continue;
                    }
                    
                    // Process each entry - take only the first one that's not a bank account
                    foreach($jsonData['entries'] as $entry) {
                        $ledger = isset($entry['ledger']) ? $entry['ledger'] : '--';
                        
                        // Skip entries with "Canara Bank" in the ledger name
                        if(stripos($ledger, 'Canara Bank') !== false) {
                            continue;
                        }
                        
                        $amount = isset($entry['amount']) ? floatval($entry['amount']) : 0;
                        
                        // Use absolute value for display
                        $absAmount = abs($amount);
                        
                        // Payment -> Debit, Receipt -> Credit
                        $debit = 0;
                        $credit = 0;
                        if(strtolower($vchType) == 'payment') {
                            $debit = $absAmount;
                            $totalDebit += $debit;
                        } elseif(strtolower($vchType) == 'receipt') {
                            $credit = $absAmount;
                            $totalCredit += $credit;
                        }
                        
                        // Mark this voucher as processed
                        $processedVouchers[$vchNo] = true;
                        
                        $tableRows[] = [
                            'date' => $displayDate,
                            'ledger' => $ledger,
                            'vch_type' => $vchType,
                            'vch_no' => $vchNo,
                            'debit' => $debit,
                            'credit' => $credit
                        ];
                        
                        // Only process the first entry per voucher
                        break;
                    }
                }
                ?>

                <table id="datatable" class="table table-hover table-striped table-bordered">
                  <thead>
                    <th>Sno.</th>
                    <th>Date</th>
                    <th>Ledger / Particulars</th>
                    <th>Vch Type</th>
                    <th>Vch No</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                  </thead>
                  <tbody>
                    <?php 
                      $i=1;
                      foreach($tableRows as $tableRow):
                    ?>
                    <tr>
                      <td><?php echo $i++; ?></td>
                      <td><?php echo htmlspecialchars($tableRow['date']); ?></td>
                      <td><?php echo htmlspecialchars($tableRow['ledger']); ?></td>
                      <td><?php echo htmlspecialchars($tableRow['vch_type']); ?></td>
                      <td><?php echo htmlspecialchars($tableRow['vch_no']); ?></td>
                      <td class="text-right"><?php echo $tableRow['debit'] > 0 ? formatIndianNumber($tableRow['debit']) : '--'; ?></td>
                      <td class="text-right"><?php echo $tableRow['credit'] > 0 ? formatIndianNumber($tableRow['credit']) : '--'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr style="background:#e4f3ff; font-weight:bold;">
                      <td colspan="5" style="text-align:center;">TOTAL</td>
                      <td class="text-right"><?php echo $totalDebit > 0 ? formatIndianNumber($totalDebit) : '--'; ?></td>
                      <td class="text-right"><?php echo $totalCredit > 0 ? formatIndianNumber($totalCredit) : '--'; ?></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include 'footer.php'; ?>

      <script>
      document.addEventListener('DOMContentLoaded', function(){
        // DataTable
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
            lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']]
          });
        }

        // Date range picker (uses moment + daterangepicker from footer)
        var start = moment('<?php echo $st; ?>', 'YYYY-MM-DD');
        var end   = moment('<?php echo $en; ?>', 'YYYY-MM-DD');
        function cb(a,b){ $('#reportrange_created span').html(a.format('D MMM YYYY') + ' - ' + b.format('D MMM YYYY')); }
        $('#reportrange_created').daterangepicker({
          startDate: start,
          endDate: end,
          opens: 'left',
          drops: 'down',
          ranges: {
            'Today': [moment(), moment()],
            'Last 7 Days': [moment().subtract(6,'days'), moment()],
            'Last 30 Days': [moment().subtract(29,'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')]
          }
        }, cb).on('apply.daterangepicker', function(ev, picker){
          $('#st').val(picker.startDate.format('YYYY-MM-DD'));
          $('#en').val(picker.endDate.format('YYYY-MM-DD'));
          applyFilters();
        });
        cb(start,end);
      });

      function applyFilters(){
        var vt = document.getElementById('voucherTypeSelect').value;
        var st = document.getElementById('st').value;
        var en = document.getElementById('en').value;
        var qs = [];
        if(vt && vt != 'all') qs.push('voucher_type='+encodeURIComponent(vt));
        if(st) qs.push('st='+encodeURIComponent(st));
        if(en) qs.push('en='+encodeURIComponent(en));
        var url = window.location.pathname + (qs.length?('?'+qs.join('&')):'');
        window.location.href = url;
      }
      </script>
    </div>
  </div>
</body>

