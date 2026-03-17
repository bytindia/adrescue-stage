<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle download invoices (must run before any output)
if (isset($_POST['download_invoices']) && !empty($_POST['ids']) && is_array($_POST['ids'])) {
	session_start();
	include 'db.php';
	Auth();
	$ids = array_map('intval', $_POST['ids']);
	$ids = array_filter($ids);
	if (!empty($ids)) {
		$idList = implode(',', $ids);
		$sql = mysqli_query($conn, "SELECT inv_files FROM invoice2 WHERE tbl_id IN ($idList) AND approved='yes'");
		$allFiles = [];
		while ($r = mysqli_fetch_assoc($sql)) {
			$files = array_filter(array_map('trim', explode(',', (string)($r['inv_files'] ?? ''))));
			foreach ($files as $f) {
				if ($f !== '') {
					$allFiles[] = (substr($f, -4) === '.pdf') ? $f : $f . '.pdf';
				}
			}
		}
		$invPath = $server_path . 'fb-ads/demo/invoices/';
		$zipName = 'invoices_' . date('YmdHis') . '_' . uniqid() . '.zip';
		$zipPath = $invPath . $zipName;
		if (class_exists('ZipArchive') && !empty($allFiles)) {
			$zip = new ZipArchive();
			if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
				$added = 0;
				foreach ($allFiles as $file) {
					$fullPath = $invPath . $file;
					if (file_exists($fullPath)) {
						$zip->addFile($fullPath, $file);
						$added++;
					}
				}
				$zip->close();
				if ($added > 0) {
					header('Content-Type: application/zip');
					header('Content-Disposition: attachment; filename="' . $zipName . '"');
					header('Content-Length: ' . filesize($zipPath));
					readfile($zipPath);
					exit;
				}
			}
		}
	}
	$_SESSION['error'] = 'No invoices could be zipped. Please ensure files exist.';
	echo "<script>window.location = 'invoice-list-filter.php';</script>";
	exit;
}

include 'header.php';
Auth();
$pgHeadline = 'Invoices - Sent Items';
$pgID = 7;

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE invoice2 SET approved = 'no', deleted = NOW() where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'invoice-list-filter.php';</script>";
	exit();
}

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

// Invoice type filter: all | invoice | pi
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'all';
if (!in_array($type, ['all','invoice','pi'])) { $type = 'all'; }

function formatIndianNumber($num) {
    $isNegative = $num < 0;
    $num = abs(round($num));
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

// Parse serialized line_items and compute totals
function computeTotalsFromLineItems($serialized) {
    $totalSubtotal = 0; // excl GST
    $totalGrand = 0;    // incl GST
    if(!empty($serialized)) {
        $data = @unserialize($serialized);
        if(is_array($data)) {
            foreach($data as $invoice) {
                if(isset($invoice['totals'])) {
                    $totalSubtotal += floatval($invoice['totals']['subtotal'] ?? 0);
                    $totalGrand    += floatval($invoice['totals']['grand_total'] ?? 0);
                }
            }
        }
    }
    return [$totalSubtotal, $totalGrand];
}
?>
<style>
.modal-dialog.modal-fullscreen { width: 90%; max-width: none; height: 95vh; margin: 10px auto; }
.modal-content { height: 100%; }
.modal-body { height: calc(100% - 60px); padding: 0; overflow: hidden; }
.modal-body iframe { width: 100%; height: 100%; border: none; }
#datatable { background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.07); overflow:hidden; }
#datatable thead th { color:#fff; font-weight:700; border:none; font-size:15px; letter-spacing:0.5px; }
#datatable tbody tr:hover { background:#f1f7ff; }
#datatable td { vertical-align:middle; font-size:14px; }
.filters { display:flex; align-items:center; gap:10px; flex-wrap:nowrap; }
.filters li { display:flex; align-items:center; white-space:nowrap; }
.filters .form-control { border-radius:6px; border:1px solid #2196f3; height:34px; padding:6px 10px; }
.date-pill { padding:6px 10px; background:#fff; border:1px solid #2196f3; border-radius:6px; display:flex; align-items:center;  height:34px; line-height:20px; }
.date-pill i { margin-right:6px; color:#2196f3; }
.date-pill span { white-space:nowrap; }
#datatable td a.openModal { color:#007bff; }
.daterangepicker { z-index: 3000; }
.x_title span { color:#4a4646;}
.modal-header { padding: 5px 15px; } 
.modal-header .close {
    margin-top: 2px; opacity: 10;
}
#downloadInvoicesForm {
  display: block !important;
  text-align: left !important;
  margin: 15px 0 0 0 !important;
  width: auto !important;
  float: left !important;
  clear: left !important;
}
#downloadInvoicesForm .btn { float: left; }
</style>

<div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"  title="Close Window"><i class="fa fa-times" aria-hidden="true"></i></button>
        <h4 class="modal-title" id="iframeModalLabel">Invoice : Preview</h4>
      </div>
      <div class="modal-body">
        <div style="position:relative; height:100%;">
          <div id="iframeLoader" style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10; background:#fff; display:flex; justify-content:center; align-items:center;">
            <strong>Loading...</strong>
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

      <div class="right_col" role="main">
        <div class="row">
          <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
              <div class="x_title">
                <h2><?php echo $pgHeadline; ?></h2>
                <ul class="nav navbar-right panel_toolbox filters">
                  <li style="min-width:160px;">
                    <select class="form-control" id="invoiceTypeSelect" onchange="applyFilters()">
                      <option value="all" <?php echo ($type=='all')?'selected':''; ?>>All</option>
                      <option value="invoice" <?php echo ($type=='invoice')?'selected':''; ?>>Invoice</option>
                      <option value="pi" <?php echo ($type=='pi')?'selected':''; ?>>Proforma Invoice</option>
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
                  // Build conditions
                  $where = ["approved='yes'"];
                  if ($type === 'pi') { $where[] = "inv_ty='pi'"; }
                  elseif ($type === 'invoice') { $where[] = "(inv_ty!='pi' OR inv_ty IS NULL OR inv_ty='')"; }
                  // created between
                  $where[] = "DATE(created) BETWEEN '".mysqli_real_escape_string($conn,$st)."' AND '".mysqli_real_escape_string($conn,$en)."'";
                  $whereSql = implode(' AND ', $where);

                  $sql = mysqli_query($conn, "SELECT tbl_id, acc_tbl_id, client_name, inv_no, inv_ty, inv_mon, inv_dt, inv_files, line_items, created FROM invoice2 WHERE $whereSql ORDER BY created DESC, tbl_id DESC");

                  $rows = [];
                  while($r = mysqli_fetch_assoc($sql)) { $rows[] = $r; }
                  $allRowIds = array_map(function($r){ return (int)$r['tbl_id']; }, $rows);
                ?>

                <table id="datatable" class="table table-hover table-striped table-bordered">
                  <thead>
                    <th><input type="checkbox" id="checkAll" title="Check All" /></th>
                    <th>#</th>
                    <th>Invoice No</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Invoice Month</th>
                    <th>Invoice Date</th>
                    <th>Invoice Created</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Tot(+GST)</th>
                    <th>Delete</th>
                  </thead>
                  <tbody>
                    <?php 
                      $i=1; $sumSubtotal=0; $sumGrand=0;
                      foreach($rows as $row):
                        list($subtotal, $grand) = computeTotalsFromLineItems($row['line_items']);
                        $sumSubtotal += $subtotal; $sumGrand += $grand;
                        // Build prefixed invoice numbers without leading commas
                        $rawInvs = array_filter(array_map(function($v){ return trim($v); }, explode(',', (string)$row['inv_no'])));
                        // show numbers only (zero-padded), no prefixes
                        $invNosArr = [];
                        foreach($rawInvs as $rv){ if($rv==='') continue; $n = (strlen($rv)<3) ? str_pad($rv,3,'0',STR_PAD_LEFT) : $rv; $invNosArr[] = $n; }
                        $invNos = count($invNosArr) ? implode(', ', $invNosArr) : '--';
                        $invType = ($row['inv_ty'] == 'pi') ? 'PI' : 'Invoice';
                        $invMonth = $row['inv_mon'] ? $row['inv_mon'] : '--';
                        $invSent = $row['inv_dt'] ? $row['inv_dt'] : '--';
                        $files = array_filter(array_map('trim', explode(',', (string)$row['inv_files'])));
                        $invCreated = $row['created'] ? date('d-m-Y h:i:s', strtotime($row['created'])) : '--';
                    ?>
                    <tr>
                      <td><input type="checkbox" class="row-check" data-tbl-id="<?php echo (int)$row['tbl_id']; ?>" data-inv-files="<?php echo htmlspecialchars($row['inv_files'] ?? ''); ?>" /></td>
                      <td><?php echo $i++; ?></td>
                      <td>
                        <?php 
                          if(count($files)>0){
                            $labelInvs = $invNosArr; // map numbers to files
                            $basePdf = 'https://stage.adrescue.in/fb-ads/demo/invoices/';
                            $idx = 0; $out = [];
                            foreach($files as $file){
                              $label = isset($labelInvs[$idx]) ? $labelInvs[$idx] : ('File '.($idx+1));
                              $url = $basePdf . $file . '.pdf'; // do not encode
                              $out[] = '<a href="#" class="openModal" data-name="'.$label.'" data-url="'.$url.'" data-val="invoice" data-tt="tt"  title="Preview Invoice #'.$label.'">'.$label.'</a>';
                              $idx++;
                            }
                            echo implode(', ', $out);
                          } else { echo htmlspecialchars($invNos); }
                        ?>
                      </td>
                      <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                      <td><?php echo $invType; ?></td>
                      <td><?php echo htmlspecialchars($invMonth); ?></td>
                      <td><?php echo htmlspecialchars($invSent); ?></td>
                      <td><?php echo htmlspecialchars($invCreated); ?></td>
                      <td class="text-right"><?php echo $subtotal>0 ? formatIndianNumber($subtotal) : '--'; ?></td>
                      <td class="text-right"><?php echo $grand>0 ? formatIndianNumber($grand) : '--'; ?></td>
                      <td><a href="invoice-list-filter.php?del=<?php echo $row['tbl_id']; ?>" onclick="return confirm('Are you sure you want to delete this invoice?');" style="color:red;">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr style="background:#e4f3ff; font-weight:bold;">
                      <td colspan="8" style="text-align:center;">TOTAL</td>
                      <td class="text-right"><?php echo $sumSubtotal>0 ? formatIndianNumber($sumSubtotal) : '--'; ?></td>
                      <td class="text-right"><?php echo $sumGrand>0 ? formatIndianNumber($sumGrand) : '--'; ?></td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
                <form id="downloadInvoicesForm" method="post" action="invoice-list-filter.php" style="margin-top:15px;">
                  <input type="hidden" name="download_invoices" value="1" />
                  <div id="downloadIdsContainer"></div>
                  <button type="submit" id="downloadInvoicesBtn" class="btn btn-primary" disabled><i class="fa fa-download"></i> Download Invoices</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include 'footer.php'; ?>

      <script>
      // Modal preview handlers
      document.addEventListener('DOMContentLoaded', function(){
        var modalIframe = document.getElementById('modalIframe');
        $(document).on('click', '.openModal', function(e){
          e.preventDefault();
          var url = this.getAttribute('data-url');
          document.getElementById('iframeLoader').style.display = 'flex';
          modalIframe.src = url;
          $('#iframeModal').modal('show');
        });
        modalIframe.onload = function(){ document.getElementById('iframeLoader').style.display = 'none'; };

        // DataTable
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#datatable')) {
          $('#datatable').DataTable({
            dom: 'Bfrtip',
            columnDefs: [{ orderable: false, targets: 0 }],
            buttons: [
              { extend: 'excel', className: 'btn btn-primary' },
              { extend: 'csv', className: 'btn btn-primary' },
              { extend: 'pdf', className: 'btn btn-primary' },
              { extend: 'print', className: 'btn btn-primary' }
            ],
            pageLength: 50,
            lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']]
          }).on('draw.dt', function(){ syncCheckboxesFromSelection(); updateCheckAllState(); toggleDownloadBtn(); });
        }

        // Created daterange picker (uses moment + daterangepicker from footer)
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

      // Check-all and download logic (cross-page selection)
      var allRowIds = <?php echo json_encode($allRowIds); ?>;
      var selectedIds = {};
      function syncCheckboxesFromSelection(){
        $('.row-check').each(function(){
          var id = parseInt($(this).data('tbl-id'), 10);
          $(this).prop('checked', !!selectedIds[id]);
        });
      }
      function updateCheckAllState(){
        var allSelected = allRowIds.length > 0 && allRowIds.every(function(id){ return selectedIds[id]; });
        $('#checkAll').prop('checked', allSelected);
      }
      $('#checkAll').on('change', function(){
        var checked = this.checked;
        allRowIds.forEach(function(id){ selectedIds[id] = checked; });
        syncCheckboxesFromSelection();
        toggleDownloadBtn();
      });
      $(document).on('change', '.row-check', function(){
        var id = parseInt($(this).data('tbl-id'), 10);
        selectedIds[id] = this.checked;
        updateCheckAllState();
        toggleDownloadBtn();
      });
      function toggleDownloadBtn(){
        var cnt = Object.keys(selectedIds).filter(function(k){ return selectedIds[k]; }).length;
        $('#downloadInvoicesBtn').prop('disabled', cnt === 0);
      }
      $('#downloadInvoicesForm').on('submit', function(e){
        var ids = Object.keys(selectedIds).filter(function(k){ return selectedIds[k]; });
        if (ids.length === 0) { e.preventDefault(); return false; }
        $('#downloadIdsContainer').empty();
        ids.forEach(function(id){ $('#downloadIdsContainer').append($('<input type="hidden" name="ids[]" value="'+id+'" />')); });
      });
      // Initial sync (e.g. when "All" rows shown)
      syncCheckboxesFromSelection();
      updateCheckAllState();
      toggleDownloadBtn();

      function applyFilters(){
        var t = document.getElementById('invoiceTypeSelect').value;
        var st = document.getElementById('st').value;
        var en = document.getElementById('en').value;
        var qs = [];
        if(t) qs.push('type='+encodeURIComponent(t));
        if(st) qs.push('st='+encodeURIComponent(st));
        if(en) qs.push('en='+encodeURIComponent(en));
        var url = window.location.pathname + (qs.length?('?'+qs.join('&')):'');
        window.location.href = url;
      }
      </script>
    </div>
  </div>
</body>


