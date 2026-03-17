<?php
// AJAX handler for assign media buyer - same file, run before any HTML output
if(isset($_POST['assign_media_buyer']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest') {
	include 'db.php';
	session_start();
	if(!isset($no_auth) || $no_auth!=1) { Auth(); }
	$med_by = (int)($_POST['med_by']??0);
	$tbl_ids = isset($_POST['tbl_ids']) && is_array($_POST['tbl_ids']) ? array_map('intval', $_POST['tbl_ids']) : [];
	$tbl_ids = array_filter($tbl_ids);
	header('Content-Type: application/json');
	if($med_by<=0) { echo json_encode(['success'=>0,'msg'=>'Please select a media buyer.']); exit; }
	if(empty($tbl_ids)) { echo json_encode(['success'=>0,'msg'=>'Please select at least one client.']); exit; }
	$ids_str = implode(',', $tbl_ids);
	$safe_ids = preg_replace('/[^0-9,]/','',$ids_str);
	mysqli_query($conn, "UPDATE dashboard_accounts SET med_by='".mysqli_real_escape_string($conn,$med_by)."' WHERE tbl_id IN ({$safe_ids}) AND uid='".mysqli_real_escape_string($conn,$_SESSION['uid'])."' AND delete_status=0");
	echo json_encode(['success'=>1,'msg'=>'Media buyer assigned successfully!']);
	exit;
}

include 'header.php';


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Client Dashboard';
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
	mysqli_query($conn, "UPDATE dashboard_accounts SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'dashboard-clients.php';</script>";
	exit();
}

// Media buyer options
$media_by = array(1=>'Ramesh', 14=>'Charan', 15=>'Mughil', 16=>'Karthik');

// Form POST fallback (non-AJAX) - redirect after update
if(isset($_POST['assign_media_buyer']) && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])!='xmlhttprequest')) {
	$med_by = isset($_POST['med_by']) ? (int)$_POST['med_by'] : 0;
	$tbl_ids = isset($_POST['tbl_ids']) && is_array($_POST['tbl_ids']) ? array_map('intval', $_POST['tbl_ids']) : [];
	$tbl_ids = array_filter($tbl_ids);
	$pageParam = '?page='.(isset($_GET['page'])?(int)$_GET['page']:1);
	if($med_by <= 0) { $_SESSION['err'] = 'Please select a media buyer.'; header('Location: dashboard-clients.php'.$pageParam); exit(); }
	if(empty($tbl_ids)) { $_SESSION['err'] = 'Please select at least one client.'; header('Location: dashboard-clients.php'.$pageParam); exit(); }
	$ids_str = implode(',', $tbl_ids);
	$safe_ids = preg_replace('/[^0-9,]/', '', $ids_str);
	mysqli_query($conn, "UPDATE dashboard_accounts SET med_by='".mysqli_real_escape_string($conn, $med_by)."' WHERE tbl_id IN ({$safe_ids}) AND uid='".mysqli_real_escape_string($conn, $_SESSION['uid'])."' AND delete_status=0");
	$_SESSION['suc'] = 'Media buyer assigned successfully!';
	header('Location: dashboard-clients.php'.$pageParam);
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>
<style>
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.btn-group-sm > .btn, .btn-sm {  font-size: 12px; line-height: 1.5; }
.card-footer .btn-group { display: flex; }
.card-footer .btn-group .btn { flex: 1; }
/* Custom modal styles from invoice.php */
.modal-dialog.modal-fullscreen { width: 90%; max-width: none; height: 95vh; margin: 10px auto; }
.modal-content { height: 100%; }
.modal-body { height: calc(100% - 60px); padding: 0; overflow: hidden; }
.modal-body iframe { width: 100%; height: 100%; border: none; }
.loader { width: fit-content; font-size: 35px; font-family: system-ui, sans-serif; font-weight: bold; text-transform: none; color: #0000; -webkit-text-stroke: 1px #1d69ab; background: conic-gradient(#1d69ab 0 0) 0/0% 100% no-repeat text; animation: l1 1s linear infinite; }
@keyframes l1 { to { background-size: 120% 100%; } }
.audit-link {     color: #65abf3; cursor: pointer; }
.table-bottom-bar { width: 100%; }
.table-bottom-bar .assign-media-wrap { flex-shrink: 0; }
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
          <div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
            <div class="modal-dialog modal-fullscreen" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="iframeModalLabel">Loading...</h4>
                    </div>
                    <div class="modal-body">
                        <div style="position:relative; height:100%;">
                            <div id="iframeLoader" style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10; background: #fff; display:flex; justify-content:center; align-items:center;">
                                
                            </div>
                            <iframe id="modalIframe" src="" style="display:none;"></iframe>
                        </div>
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
                        	<a data-url="dashboard-clients-add.php?menu=hide"  class="btn btn-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-name="Add New">Add</a>                 
                      </div>    
                      </li>
                      <li> &nbsp;
                      </li>
                      
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                      include 'alert.php'; 
                       $fbAccN = $gAccN = $inAccN = $taAccN = array();
										
                       $sqlRev1=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                       
                       $sqlRev2=mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                       
                       $sqlRev3=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
                       
                       $sqlRev4=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");

                       while($sqlROW1=mysqli_fetch_array($sqlRev1)) { $fbAccN[$sqlROW1["account_id"]] = $sqlROW1["name"]; }
                       while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2["account_id"]] = $sqlROW2["name"]; }
                       while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $inAccN[$sqlROW3["account_id"]] = $sqlROW3["name"]; }
                       while($sqlROW4=mysqli_fetch_array($sqlRev4)) { $taAccN[$sqlROW4["account_id"]] = $sqlROW4["name"]; }

										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
								<form method="post" id="assignMediaBuyerForm">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                  <thead>
                                    <tr>
                                      <th><input type="checkbox" id="selectAllRows" title="Select all on this page"></th>
                                      <th>SNo</th>
                                      <th>Client Name</th>
                                      <th>Facebook</th>
                                      <th>Google</th>
                                      <th>LinkedIn</th>
                                      <th>Projects</th>
                                      <th>Actions</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php
                                    $totRows = mysqli_num_rows($sqlRev);
                                    if($totRows == 0){
                                      echo '<tr><td colspan="8" class="text-center text-muted">No client dashboards found.</td></tr>';
                                    }
                                    while($sqlROW=mysqli_fetch_array($sqlRev))
                                    { 
                                    ?>
                                    <tr>
                                      <td><input type="checkbox" class="row-checkbox" name="tbl_ids[]" value="<?php echo (int)$sqlROW['tbl_id']; ?>"></td>
                                      <td><?php echo $i; ?></td>
                                      <td><?php echo htmlspecialchars($sqlROW["client_name"]); ?></td>
                                      <td><?php
                                        if(!empty($sqlROW["fb_id"])){
                                          $fb_accounts = [];
                                          $aFB_id = explode(',', $sqlROW["fb_id"]);
                                          foreach($aFB_id as $f_val) {
                                            if(isset($fbAccN[$f_val])) $fb_accounts[] = '<a class="audit-link openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=audit/audit-latest.php?act='.$f_val.'" data-name="Audit : '.htmlspecialchars($fbAccN[$f_val]).'" data-val="report" data-tt="tt"  title="Audit Ad Account">'.htmlspecialchars($fbAccN[$f_val]).'</a>';
                                          }
                                          echo implode(' <span style="color:#ddd;">|</span> ', $fb_accounts);
                                        } else { echo '<i style="color: #adb5bd;">Not linked</i>'; }
                                      ?></td>
                                      <td><?php
                                        if(!empty($sqlROW["g_id"])){
                                          $g_accounts = [];
                                          $aG_id = explode(',', $sqlROW["g_id"]);
                                          foreach($aG_id as $g_val) {
                                            if(isset($gAccN[$g_val])) $g_accounts[] = htmlspecialchars($gAccN[$g_val]);
                                          }
                                          echo implode(' <span style="color:#ddd;">|</span> ', $g_accounts);
                                        } else { echo '<i style="color: #adb5bd;">Not linked</i>'; }
                                      ?></td>
                                      <td><?php
                                        if(!empty($sqlROW["in_id"])){
                                          $in_accounts = [];
                                          $aIn_id = explode(',', $sqlROW["in_id"]);
                                          foreach($aIn_id as $in_val) {
                                            if(isset($inAccN[$in_val])) $in_accounts[] = htmlspecialchars($inAccN[$in_val]);
                                          }
                                          echo implode(' <span style="color:#ddd;">|</span> ', $in_accounts);
                                        } else { echo '<i style="color: #adb5bd;">Not linked</i>'; }
                                      ?></td>
                                      <td><?php
                                        $proj_names = [];
                                        if($sqlROW['proj_name']!='') { $proj_names = array_filter(unserialize($sqlROW['proj_name'])); }
                                        if(!empty($proj_names)){
                                          $project_list = [];
                                          foreach($proj_names as $p_val) {
                                            $project_list[] = htmlspecialchars($p_val);
                                          }
                                          echo implode(' <span style="color:#ddd;">|</span> ', $project_list);
                                        } else { echo '<i style="color: #adb5bd;">No projects</i>'; }
                                      ?></td>
                                      <td>
                                        <div class="btn-group btn-group-sm" role="group" style="margin-bottom:4px;">
                                          <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=dash/spend.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>" data-name="Ads Report: <?php echo htmlspecialchars($sqlROW["client_name"]); ?>" data-val="report" data-tt="tt"  title="View Ads Report">Ads</button>
                                          <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=dash/spend-month.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&type=mon" data-name="MoM Report: <?php echo htmlspecialchars($sqlROW["client_name"]); ?>" data-val="report" data-tt="tt"  title="View Month on Month report">MoM</button>
                                          <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=dash/spend-compare.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>&type=mon" data-name="Comparison Report: <?php echo htmlspecialchars($sqlROW["client_name"]); ?>" data-val="report" data-tt="tt"  title="Compare reports by daterange">Comp</button>
                                          <?php if(!empty($sqlROW["keyw_name"]) && $sqlROW["keyw_name"]!='a:1:{i:0;s:0:"";}' && !empty($sqlROW["g_id"])) { ?>
                                          <button type="button" class="btn btn-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=dash/keywords.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>" data-name="<?php echo htmlspecialchars($sqlROW["client_name"]); ?> : Keywords report" data-val="report">Key</button>
                                          <?php } ?>
                                          <button type="button" class="btn btn-success openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=dash/meta-share.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>" data-name="Share Meta Report: <?php echo htmlspecialchars($sqlROW["client_name"]); ?>" data-val="report" title="Share Meta Report"><i class="fa fa-share-alt"></i></button>

                                        </div>
                                        <div class="btn-group btn-group-sm" role="group">
                                          <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="dashboard-clients-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>&menu=hide" data-name="Edit Client: <?php echo htmlspecialchars($sqlROW["client_name"]); ?>" data-val="edit" data-tt="tt" title="Edit"><i class="fa fa-pencil"></i></button>
                                          <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="dashboard-clients.php?del=<?php echo $sqlROW["tbl_id"]; ?>" data-name="Delete Client: <?php echo htmlspecialchars($sqlROW["client_name"]); ?>" data-val="delete" data-confirm="Are you sure you want to delete this client?" data-tt="tt" title="Delete"><i class="fa fa-trash-o"></i></button>
                                        </div>
                                      </td>
                                    </tr>
                                    <?php $i++;
                                    } 
                                    ?>                                      
                                  </tbody>
								</table>

								<div id="assignMsg" class="alert" style="display:none; margin-top:8px;"></div>
								<div class="table-bottom-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-top:12px; gap:12px;">
									<div class="assign-media-wrap" style="order:1;">
										<div class="assign-media-row" style="display:flex; align-items:center; flex-wrap:nowrap; gap:8px;">
											<label class="mb-0" style="white-space:nowrap;">Assign Media Buyer:</label>
											<select name="med_by" class="form-control form-control-sm" style="width:auto; min-width:140px;">
												<option value="">-- Select --</option>
												<?php foreach($media_by as $mb_id => $mb_name): ?>
												<option value="<?php echo $mb_id; ?>"><?php echo htmlspecialchars($mb_name); ?></option>
												<?php endforeach; ?>
											</select>
											<input type="hidden" name="assign_media_buyer" value="1">
											<button type="submit" class="btn btn-sm btn-success">Assign to Selected</button>
										</div>
									</div>
									<div id="pagDiv" style="order:2;"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>
								</div>
								</form>
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>

<script>

function onAjax(id,ty,clName) {
    // This is the original function for the modal that shows account details
    // The new iframe modal is separate. We need to target the *other* modal.
    var targetModal = $('.bs-example-modal-lg'); 
    targetModal.find('.modal-title').text(clName);
    targetModal.find('.modal-body').html('Loading...');

    $.ajax({
        type: 'POST',
        url: 'ajax-dashboard-clients.php',
        data:{id: id, ty: ty},
        success: function(data) {
          targetModal.find('.modal-body').html(data);
        },
        error:function(err){
          targetModal.find('.modal-body').html('<p>Error loading content.</p>');
          console.error("error"+JSON.stringify(err));
        }
    });
};

// Iframe Modal script from invoice.php
document.addEventListener('DOMContentLoaded', function () {
  const modalIframe = document.getElementById('modalIframe');
  const modalTitle = document.getElementById('iframeModalLabel');
  const iframeLoader = document.getElementById('iframeLoader');
  const loaderTextSpan = document.querySelector('.loader .loader-text');
  let textInterval;

  const textOptions = {
    report: ["Fetching Report", "Analyzing Data", "Building Visuals", "Almost Done"],
    edit: ["Loading Form", "Fetching Details", "Almost Ready"],
    delete: ["Processing Request", "Deleting Client", "Cleaning Up"],
    default: ["Loading...", "Please Wait", "Initializing"]
  };

  function cycleText(texts) {
      let currentTextIndex = 0;
      if (loaderTextSpan) {
        loaderTextSpan.innerText = texts[currentTextIndex];
        textInterval = setInterval(() => {
            currentTextIndex = (currentTextIndex + 1) % texts.length;
            loaderTextSpan.innerText = texts[currentTextIndex];
        }, 1500);
      }
  }

  // Use event delegation for openModal buttons to work with DataTable pagination
  $(document).on('click', '.openModal', function (event) {
    const dataVal = this.getAttribute('data-val') || 'default';
    const confirmMessage = this.getAttribute('data-confirm');
    
    if (confirmMessage) {
      if (!confirm(confirmMessage)) {
          event.preventDefault();
          event.stopPropagation();
          return;
      }
    }

    const url = this.getAttribute('data-url');
    const name = this.getAttribute('data-name') || '';
    
    modalTitle.textContent = name;
    iframeLoader.style.display = 'flex';
    modalIframe.style.display = 'none'; 
    modalIframe.src = 'about:blank'; // Clear previous content

    setTimeout(() => { // Ensure the DOM updates before setting src
        modalIframe.src = url;
    }, 50);

    //const texts = textOptions[dataVal] || textOptions['default'];
    //clearInterval(textInterval);
    //cycleText(texts);
  });

  modalIframe.onload = function () {
    setTimeout(() => {
        iframeLoader.style.display = 'none';
        modalIframe.style.display = 'block';
    }, 500);
  };

  $('#iframeModal').on('hidden.bs.modal', function () {
    modalIframe.src = 'about:blank';
    iframeLoader.style.display = 'flex';
    //clearInterval(textInterval);
    //if (loaderTextSpan) loaderTextSpan.innerText = 'Loading...';
  });
});

$(document).ready(function () {
  // Assign Media Buyer - AJAX submit (same file)
  $('#assignMediaBuyerForm').on('submit', function(e) {
    e.preventDefault();
    var ids = [];
    $(this).find('.row-checkbox:checked').each(function() { ids.push($(this).val()); });
    var medBy = $(this).find('[name=med_by]').val();
    if(!medBy) { alert('Please select a media buyer.'); return; }
    if(ids.length === 0) { alert('Please select at least one client.'); return; }
    var $btn = $(this).find('button[type=submit]');
    $btn.prop('disabled', true).text('Assigning...');
    $.ajax({
      url: 'dashboard-clients.php',
      type: 'POST',
      data: { assign_media_buyer: 1, med_by: medBy, 'tbl_ids[]': ids },
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function(r) {
        var $m = $('#assignMsg');
        $m.removeClass('alert-success alert-danger').addClass(r.success ? 'alert-success' : 'alert-danger').html(r.msg || 'Error occurred.').show();
        if(r.success) {
          $('.row-checkbox:checked').prop('checked', false);
          $('#selectAllRows').prop('checked', false);
        }
        setTimeout(function() { $m.fadeOut(); }, 4000);
      },
      error: function() {
        $('#assignMsg').removeClass('alert-success').addClass('alert-danger').html('Request failed. Please try again.').show();
        setTimeout(function() { $('#assignMsg').fadeOut(); }, 4000);
      },
      complete: function() { $btn.prop('disabled', false).text('Assign to Selected'); }
    });
  });

  // Select all / deselect all checkboxes
  $(document).on('change', '#selectAllRows', function () {
    var isChecked = this.checked;
    $('#datatable .row-checkbox').each(function () {
      this.checked = isChecked;
    });
  });
  $(document).on('change', '.row-checkbox', function () {
    var total = $('#datatable .row-checkbox').length;
    var checked = $('#datatable .row-checkbox:checked').length;
    $('#selectAllRows').prop('checked', total > 0 && total === checked);
  });

  // Initialize DataTable with proper configuration for pagination
  if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#datatable')) {
    var table = $('#datatable').DataTable({
      dom: '<"d-flex align-items-center mb-2"lBf>rtip',
      buttons: [
        { extend: 'excel', className: 'btn btn-excel', title: 'Clients Export', exportOptions: { columns: [1,2,3,4,5,6,7] } }
      ],
      pageLength: 50,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      scrollX: true,
      columnDefs: [
        { orderable: false, targets: 0 }
      ],
      drawCallback: function() {
        $('#selectAllRows').prop('checked', false);
        $('[data-toggle="tooltip"]').tooltip();
      },
      initComplete: function() {
        // Move assign controls to bottom-left: prepend to DataTable's bottom row (left of "Showing X to Y")
        var $assignWrap = $('.assign-media-wrap').first();
        var $dtBottom = $('#datatable_info').parent();
        if ($assignWrap.length && $dtBottom.length && $dtBottom.find('.assign-media-wrap').length === 0) {
          $assignWrap.prependTo($dtBottom).css('margin-right', '20px');
        }
      }
    });
    // Move Download Excel button inline with search bar
    var $downloadBtn = $('<button id="downloadExcel" class="btn btn-excel" style="margin-left:8px;"><i class="fa fa-download"></i> Download Excel</button>');
    $downloadBtn.on('click', function() { table.button(0).trigger(); });
    $("#datatable_filter").append($downloadBtn);
  }
});
</script>

<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Client Details</h4>
            </div>
            <div class="modal-body">
                <!-- Content loaded by onAjax -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

