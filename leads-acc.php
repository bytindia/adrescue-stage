<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Meta Leads - Setup';
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
	mysqli_query($conn, "UPDATE leads_acc SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'leads-acc.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " leads_acc WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                      <div style="flex:1; min-width:200px;">
                        <h2 style="margin-bottom:0; text-align:left;"><?php echo $pgHeadline; ?></h2>
                      </div>
                      <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex:1;">
                        <ul class="nav navbar-right panel_toolbox btn-group" style="margin-bottom:0;">
                          <li><a href="#" class="btn btn-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=leads-acc-add.php&menu=hide" data-name="Add Account" data-val="edit">Add</a></li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                      include 'alert.php';
                      
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc ");
										$i = (($page-1) * $per_page ) + 1;
										$getRows = array();
										while($sqlROW=mysqli_fetch_array($sqlRev)) { $getRows[] = $sqlROW; }
								?>
                                <table id="datatable" class="table table-hover table-striped table-bordered datatable" style="width:100%;">
                                  <thead>
                                    <tr>
                                      <th>SNo</th>
                                      <th>Client Name</th>
                                      <th>FB Page</th>
                                      <th>FB Page ID</th>
                                      <th>Email IDs</th>
                                      <th>View/Sheet</th>
                                      <th>Edit/Delete</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach($getRows as $row) { ?>
                                    <tr>
                                      <td><?php echo $i++; ?></td>
                                      <td><?php echo htmlspecialchars($row["client_name"]); ?></td>
                                      <td><a href="https://www.facebook.com/<?php echo $row["pg_id"]; ?>" target="_blank"><?php echo htmlspecialchars($row["pg_name"]); ?></a></td>
                                      <td><?php echo htmlspecialchars($row["pg_id"]); ?></td>
                                      <td><?php echo htmlspecialchars($row["email_ids"]); ?></td>
                                      <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                          <button type="button" class="btn btn-outline-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=leads-view.php?id=<?php echo $row["pg_id"]; ?>&menu=hide" data-name="View: <?php echo htmlspecialchars($row["client_name"]); ?>" data-val="view" data-tt="tt" title="View Leads"><i class="fa fa-user"></i></button>
                                          <?php if($row["googlesheet"]=='Yes' && $row["googlesheet_id"]!='') { ?>
                                            <a href="https://docs.google.com/spreadsheets/d/<?php echo $row["googlesheet_id"]; ?>/edit" class="btn btn-outline-secondary btn-sm" target="_blank" data-tt="tt"  title="Googlesheet Leads"><i class="fa fa-table"></i></a>
                                          <?php } ?>
                                        </div>
                                      </td>
                                      <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                          <button type="button" class="btn btn-outline-primary openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=leads-acc-add.php?id=<?php echo $row["tbl_id"]; ?>&menu=hide" data-name="Edit: <?php echo htmlspecialchars($row["client_name"]); ?>" data-val="edit" data-tt="tt"  title="Edit: Leads setup"><i class="fa fa-pencil"></i></button>
                                          <a href="loading.php?pg=leads-acc.php?del=<?php echo $row["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-outline-danger" data-tt="tt"  title="Delete Account?"><i class="fa fa-trash-o"></i></a>
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
        <!-- /page content -->

<?php include 'footer.php'; ?>

<!-- Modal for Add/Edit (90% width) -->
<div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
  <div class="modal-dialog modal-xl" style="width: 90%; max-width: 90%;" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="iframeModalLabel">Account</h4>
      </div>
      <div class="modal-body" style="padding: 0;">
        <div style="position:relative; height:85vh;">
          <div id="leadsIframeLoader" style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10; background:#fff; display:flex; justify-content:center; align-items:center;">
            <i class="leads-loader-msg"><span class="leads-loader-msg-text">Loading...</span></i>
          </div>
          <iframe id="modalIframe" src="" style="width: 100%; height: 85vh; border: none; display: none;"></iframe>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- DataTable and modal JS -->
<script>
// Modal/iframe loader logic for leads
let leadsTexts = [];
let leadsCurrentTextIndex = 0;
let leadsTextInterval;
let leadsLoaderText;

function cycleLeadsText() {
  if (leadsLoaderText) {
    leadsLoaderText.innerText = leadsTexts[leadsCurrentTextIndex];
    leadsCurrentTextIndex = (leadsCurrentTextIndex + 1) % leadsTexts.length;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const modalIframe = document.getElementById('modalIframe');
  const modalTitle = document.getElementById('iframeModalLabel');
  leadsLoaderText = document.querySelector('.leads-loader-msg .leads-loader-msg-text');
  
  // Use event delegation for openModal buttons to work with DataTable pagination
  $(document).on('click', '.openModal', function (e) {
    e.preventDefault();
    const url = this.getAttribute('data-url');
    const name = this.getAttribute('data-name') || 'Loading...';
    const dataVal = this.getAttribute('data-val') || 'default';
    
    modalTitle.textContent = name;
    document.getElementById('leadsIframeLoader').style.display = 'flex';
    modalIframe.style.display = 'block';
    modalIframe.src = url;
    
    const textOptions = {
      view: ["Fetching Lead Details", "Loading Data", "Almost Ready"],
      edit: ["Loading Form", "Fetching Details", "Almost Ready"],
      default: ["Loading...", "Please Wait", "Initializing"]
    };
    
    leadsTexts = textOptions[dataVal] || textOptions['default'];
    leadsCurrentTextIndex = 0;
    cycleLeadsText();
    clearInterval(leadsTextInterval);
    leadsTextInterval = setInterval(cycleLeadsText, 1500);
  });
  
  modalIframe.onload = function () {
    setTimeout(() => {
      document.getElementById('leadsIframeLoader').style.display = 'none';
    }, 500);
  };
  
  modalIframe.onerror = function () {
    document.getElementById('leadsIframeLoader').style.display = 'none';
  };
  
  $('#iframeModal').on('hidden.bs.modal', function () {
    leadsCurrentTextIndex = 0;
    modalIframe.src = '';
    document.getElementById('leadsIframeLoader').style.display = 'flex';
    clearInterval(leadsTextInterval);
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
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']]
    });
  }
});

</script>
<style>
.btn-group .btn { margin-right: 1px; font-size: 13px; padding: 3px 10px !important; }
.btn-outline-primary { color: #1976d2; background: #fff; border: 1px solid #1976d2; }
.btn-outline-primary:hover { background: #1976d2; color: #fff; }
.btn-outline-danger { color: #d32f2f; background: #fff; border: 1px solid #d32f2f; }
.btn-outline-danger:hover { background: #d32f2f; color: #fff; }

/* Simple DataTable styles */
#datatable tbody tr:hover {
  background-color: #f8f9fa !important;
}
</style>