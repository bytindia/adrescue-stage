<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Invoice - Accounts';
$pgID = 7;
$err =''; 

if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE accounts_invoice SET admin_delete='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'invoice-filter.php';</script>";
	exit();
}

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

$dt_q ='';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
 // $extQry2 .= " AND created BETWEEN '".date('Y-m-d H:i:s',strtotime(strtr($_GET['st'], '/', '-').' 00:00:00'))."' AND  '".date('Y-m-d H:i:s',strtotime(strtr($_GET['en'], '/', '-').' 23:59:59'))."'";
}

?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.modal-dialog.modal-fullscreen {
  width: 90%;
  max-width: none;
  height: 95vh;
  margin: 10px auto;
}
.modal-content { height: 100%; }
.modal-body { height: calc(100% - 60px); padding: 0; overflow: hidden; }
.modal-body iframe { width: 100%; height: 100%; border: none; }
.loader { width: fit-content; font-size: 35px; font-family: system-ui, sans-serif; font-weight: bold; text-transform: none; color: #0000; -webkit-text-stroke: 1px #1d69ab; background: conic-gradient(#1d69ab 0 0) 0/0% 100% no-repeat text; animation: l1 1s linear infinite; }
@keyframes l1 { to { background-size: 120% 100%; } }
.btn-group .btn { margin-right: 1px; font-size: 13px; padding: 3px 10px !important; }
.btn-outline-primary { color: #1976d2; background: #fff; border: 1px solid #1976d2; }
.btn-outline-primary:hover { background: #1976d2; color: #fff; }
.btn-outline-danger { color: #d32f2f; background: #fff; border: 1px solid #d32f2f; }
.btn-outline-danger:hover { background: #d32f2f; color: #fff; }
</style>
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
            <i class="loader"><span class="loader-text">Loading...</span></i>
          </div>
          <iframe id="modalIframe" src="" style="width:100%; height:100%; border:none;"></iframe>
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
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                  <div style="flex:1; min-width:200px;">
                    <h2 style="margin-bottom:0; text-align:left;">Invoice - Accounts</h2>
                  </div>
                  <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex:1;">
                    <ul class="nav navbar-right panel_toolbox btn-group" style="margin-bottom:0;">
                      <li><a class="btn btn-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="link-accounts.php" data-name="Add New" data-val="edit">Add New</a></li>
                      <li><div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc"><i class="glyphicon glyphicon-calendar fa fa-calendar"></i> <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b></div></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="x_content">
                <?php 
                  $sqlRev=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                  $sqlRev2=mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                  $fbAccN = $gAccN =  array();
                  while($sqlROW=mysqli_fetch_array($sqlRev)) { $fbAccN[$sqlROW["account_id"]] = $sqlROW["name"]; }
                  while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2["account_id"]] = $sqlROW2["name"]; }
                  $sqlRev=mysqli_query($conn, "SELECT l.tbl_id, l.client_name, l.fb_acc, l.g_acc, g.name as gname, f.name as fname, l.gsheet, l.email_ids FROM accounts_invoice as l LEFT JOIN gaccounts as g ON l.g_acc=g.account_id AND g.uid='".$_SESSION['uid']."' LEFT JOIN adAccounts as f ON l.fb_acc=f.account_id AND f.uid='".$_SESSION['uid']."' WHERE l.uid='".$_SESSION['uid']."' AND l.admin_delete=0 order by l.tbl_id desc");
                  $i = 1;
                  $getRows = $accIds = array();
                  while($sqlROW=mysqli_fetch_array($sqlRev)) {
                    $getRows[] = $sqlROW;
                    $accIds[] = $sqlROW['g_acc'];
                  }
                  $inv_url = 'https://stage.adrescue.in/loading.php?pg=fb-ads/demo/cron-invoice.php?tbl_id=';
                ?>
                <table id="datatable" class="table table-hover table-striped table-bordered datatable" style="width:100%;">
                  <thead>
                    <tr>
                      <th>SNo</th>
                      <th>Client Name</th>
                      <th>Facebook</th>
                      <th>Google</th>
                      <th>Projects</th>
                      <th>Email IDs</th>
                      <th>Invoice</th>
                      <th>Edit</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($getRows as $gR) { ?>
                    <tr>
                      <td><?php echo $i++; ?></td>
                      <td><?php echo htmlspecialchars($gR["client_name"]); ?></td>
                      <td>
                        <?php  if(!empty($gR["fb_acc"])) { $aFB_id = explode(',',$gR["fb_acc"]); foreach($aFB_id as $f_key => $f_val) { echo "&#x2022; ".(isset($fbAccN[$f_val]) ? htmlspecialchars($fbAccN[$f_val]) : $f_val)."<br>"; } } else { echo '<i style="color: #adb5bd;">No Facebook accounts</i>'; } ?>
                      </td>
                      <td>
                        <?php  if(!empty($gR["g_acc"])) { $aG_id = explode(',',$gR["g_acc"]); foreach($aG_id as $g_key => $g_val) { echo "&#x2022; ".(isset($gAccN[$g_val]) ? htmlspecialchars($gAccN[$g_val]) : $g_val)."<br>"; } } else { echo '<i style="color: #adb5bd;">No Google accounts</i>'; } ?>
                      </td>
                      <td>
                        <?php  $proj_names = []; if(isset($gR['camp_name']) && $gR['camp_name']!='') { $proj_names = array_filter(unserialize($gR['camp_name'])); } if(!empty($proj_names)){ foreach($proj_names as $p_val) { echo htmlspecialchars($p_val)."<br>"; } } else { echo '<i style="color: #adb5bd;">No projects</i>'; } ?>
                      </td>
                      <td>
                        <?php if(!empty($gR['email_ids'])) { $emails = explode(',', $gR['email_ids']); $emails = array_map('trim', $emails); echo implode('<br>', array_map('htmlspecialchars', $emails)); } else { echo '<i style="color: #adb5bd;">No email IDs</i>'; } ?>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm" role="group">
                          <a  class="btn btn-primary openModal" href="https://stage.adrescue.in/fb-ads/demo/cron-invoice.php?tbl_id=<?php echo $gR['tbl_id']; ?>&report<?php echo $dt_q; ?>" target="_blank">Email</a>
                        </div>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm" role="group">
                          <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="link-accounts.php?id=<?php echo $gR['tbl_id']; ?>" data-name="Edit: <?php echo $gR['client_name']; ?>" data-val="edit"><i class="fa fa-pencil"></i></button>
                          <button type="button" class="btn btn-outline-secondary openModal" data-toggle="modal" data-target="#iframeModal" data-url="invoice-filter.php?del=<?php echo $gR['tbl_id']; ?>" data-name="Delete: <?php echo $gR['client_name']; ?>" data-val="delete" data-confirm="Are you sure you want to delete this?"><i class="fa fa-trash-o"></i></button>
                        </div>
                      </td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
                <div id="pagDiv"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
<?php include 'footer.php'; ?>
<script src="js/moment/moment.min.js"></script>
<script src="js/datepicker/daterangepicker.js"></script>
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
                window.location = 'invoice-filter.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'invoice-filter.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
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

<script>
// Modal/iframe loader logic
let texts = [];
let currentTextIndex = 0;
let textInterval;
let loaderText;
function cycleText() {
  if (loaderText) {
    loaderText.innerText = texts[currentTextIndex];
    currentTextIndex = (currentTextIndex + 1) % texts.length;
  }
}
document.addEventListener('DOMContentLoaded', function () {
  const modalIframe = document.getElementById('modalIframe');
  const modalTitle = document.getElementById('iframeModalLabel');
  loaderText = document.querySelector('.loader .loader-text');
  
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
      email: ["Fetching AdReport", "Creating Invoices", "Fetching SOA", "Finalizing Report", "Processing Data", "Almost Done"],
      edit: ["Loading Form", "Fetching Details", "Almost Ready"],
      default: ["Loading...", "Please Wait", "Initializing"]
    };
    texts = textOptions[dataVal] || textOptions['default'];
    currentTextIndex = 0;
    cycleText();
    clearInterval(textInterval);
    textInterval = setInterval(cycleText, 1500);
  });
  const modalIframe = document.getElementById('modalIframe');
  modalIframe.onload = function () {
    document.getElementById('iframeLoader').style.display = 'none';
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
  // DataTable init with proper configuration for pagination
  if ($.fn.DataTable) {
    if ($.fn.DataTable.isDataTable('#datatable')) {
      $('#datatable').DataTable().destroy();
    }
    $("#datatable").DataTable({
      dom: 'Bfrtip',
      buttons: [
        { extend: 'excel', className: 'btn btn-primary' },
        { extend: 'csv', className: 'btn btn-primary' },
        { extend: 'pdf', className: 'btn btn-primary' },
        { extend: 'print', className: 'btn btn-primary' }
      ],
      pageLength: 50,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      drawCallback: function() {
        // Re-initialize any UI elements after each draw
        $('[data-toggle="tooltip"]').tooltip();
      }
    });
  }
});
</script>