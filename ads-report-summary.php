<?php include 'header.php'; 

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location = 'ads-report-summary.php';</script>";
	exit();
}
Auth();
$pgHeadline = 'Ads Summary - Download Report';
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
	mysqli_query($conn, "UPDATE ads_report_summary SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'ads-report-summary.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " ads_report_summary WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

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
                        <h2 style="margin-bottom:0; text-align:left;">Ads Summary - Download Report</h2>
                      </div>
                      <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex:1;">
                        <ul class="nav navbar-right panel_toolbox btn-group" style="margin-bottom:0;">
                          <li><a href="loading.php?pg=ads-report-summary-add.php" class="btn btn-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=ads-report-summary-add.php" data-name="Add Account" data-val="edit">Add Accounts</a></li>
                          <li><form method="post" action="ads-report-summary.php" style="display:inline-flex; align-items:center; gap:8px; margin-bottom:0;">
                            <span>Filter:</span>
                            <div id="reportrange_right" class="pull-right1" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                              <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                              <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                            </div>
                            <input type="hidden" id="stDt" name="start" value="<?php echo $_SESSION['stDt']; ?>">
                            <input type="hidden" id="enDt" name="end" value="<?php echo $_SESSION['enDt']; ?>">
                            <input type="submit" name="dt_submit" value="Submit" class="btn btn-outline-primary btn-sm">
                          </form></li>
                        </ul>
                      </div>
                    </div>
                  </div>
                 
                  
                  <div class="x_content">
                  
                  		<?php 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Client Name</th>                                    	
                                        <th>FB Account ID</th>
                                        <th>Google Account ID</th>   
                                        <th width="20%">Download</th>
                                        <th width="16%">Edit</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["client_name"]; ?></td>
                                        	<td><?php echo $sqlROW["fb_acc"]; ?></td>                                            
                                            <td><?php echo $sqlROW["g_acc"]; ?></td>  
                                            <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                              <a href="loading.php?pg=insights-ads-summary.php?id=<?php echo $sqlROW["tbl_id"]; ?>&t=1" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa fa-eye"></i> View</a>
                                              <a href="loading.php?pg=insights-ads-summary.php?id=<?php echo $sqlROW["tbl_id"]; ?>&t=2" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa fa-file-powerpoint-o"></i> PPT</a>
                                              <a href="loading.php?pg=insights-ads-summary.php?id=<?php echo $sqlROW["tbl_id"]; ?>&t=3" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa fa-file-excel-o"></i> Excel</a>
                                            </div>
                                            </td>
                                            <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                              <a href="loading.php?pg=ads-report-summary-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-outline-primary btn-sm openModal" data-toggle="modal" data-target="#iframeModal" data-url="loading.php?pg=ads-report-summary-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" data-name="Edit: <?php echo $sqlROW["client_name"]; ?>" data-val="edit"><i class="fa fa-pencil"></i> Edit</a>
                                              <a href="loading.php?pg=ads-report-summary.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash-o"></i> Delete</a>
                                            </div>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>

<!-- Modal for Add/Edit Account -->
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
  loaderText = document.querySelector('.loader .loader-text'); // ✅

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
      edit: [
        "Loading Form",
        "Fetching Details",
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

<style>
.btn-group .btn { margin-right: 1px; font-size: 13px; padding: 3px 10px !important; }
.btn-outline-primary { color: #1976d2; background: #fff; border: 1px solid #1976d2; }
.btn-outline-primary:hover { background: #1976d2; color: #fff; }
.btn-outline-danger { color: #d32f2f; background: #fff; border: 1px solid #d32f2f; }
.btn-outline-danger:hover { background: #d32f2f; color: #fff; }
</style>

