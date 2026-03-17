<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Leads - Email Setup';
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



?>
<style>
  html, body {
    margin: 0;
    padding: 0;
     /* Prevent parent scroll */
    height: 100%;
  }
  iframe {
    width: 100%;
    min-height: 100vh;
    border: none;
    display: block;
    transition: height 0.3s ease;
  }
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
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  
                  
                  <div class="x_content">
                  		<?php 
                      include 'alert.php';
                      $page = (isset($_GET['page'])) ? $_GET['page'] : 'multi-client';
                      if($page=='multi-client') { $pgName = 'Multi-Account Dashboard'; $url = 'dash/multi-client.php'; }
                      if($page=='multi-client2') { $pgName = 'Multi-Account Dashboard - V2'; $url = 'dash/multi-client2.php'; }
                      if($page=='multi-client3') { $pgName = 'Multi-Account with Multi Daterange'; $url = 'dash/multi-client3.php'; }
                      if($page=='camp-reports') { $pgName = 'Campaign-wise Report'; $url = 'camp-reports/'; }
                      if($page=='adset-reports') { $pgName = 'AdSet-wise Report'; $url = 'adset-reports/'; }
                      if($page=='adv-targeting') { $pgName = 'Similar Audience Ads'; $url = 'dash/adv-targeting.php'; }
                      if($page=='management') { $pgName = 'Management Dashboard'; $url = 'management.php'; }
                      if($page=='troubleshoot') { $pgName = 'Troubleshoot'; $url = 'home/'; }
                      if($page=='audit') { $pgName = 'Audit Ad Account'; $url = 'audit/audit-latest.php'; }
                      if($page=='lead-placements') { $pgName = 'Lead Placements Report'; $url = 'audit/lead-placements.php'; }
                      if($page=='da-vlookup') { $pgName = 'Digital Azadi - VLookup'; $url = 'https://ads.adrescue.in/vlookup-da/index.php'; }
                      if($page=='lead-analysis') { $pgName = 'Lead Analysis'; $url = 'dash/lead-analysis.php'; }
                      if($page=='lead-download') { $pgName = 'Leads Download'; $url = 'dash/leads-download.php'; }
                      if($page=='lead-capi') { $pgName = 'Leads - CAPI Update'; $url = 'dash/lead-capi.php'; }
                      if($page=='feedback') { $pgName = 'Leads Feedback Dashboard'; $url = 'dash/feedback-dashboard.php'; }
                      if($page=='accounts') { $pgName = 'Accounts Overview'; $url = 'loading.php?pg=accounts/?menu=hide'; }
                      if($page=='sagehil-lead-dash') { $pgName = 'Sagehill Leads Dashboard'; $url = 'dash/sagehil-lead-dash.php'; }
                      if($page=='iyra-lead-dash') { $pgName = 'iYRA Leads Dashboard'; $url = 'dash/iyra-leads-dash.php'; }
                      if($page=='calendar-view') { $pgName = 'Calendar View Dashboard'; $url = 'dash/calendar-view.php'; }
                      if($page=='meta-share') { $pgName = 'Share Meta data'; $url = 'dash/meta-share.php'; }
                      if($page=='client-performance') { $pgName = 'Client Performance Dashboard'; $url = 'dash/client-performance.php'; }
                      if($page=='client-performance-roas') { $pgName = 'Client Performance - ROAS SV CAC'; $url = 'dash/client-performance-roas.php'; }
                      
								?>
                               <div class="x_title">
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;  margin-top: 50px;">
                                <div style="flex:1; min-width:200px;">
                                    <h2 style="margin-bottom:0; text-align:left;"><?php echo $pgName; ?></h2>
                                </div>
                                
                                </div>
                            </div>
                              <iframe id="reportFrame" src="<?php echo $url; ?>"></iframe>

<script>
  const frame = document.getElementById('reportFrame');

  // Listen for height messages
  window.addEventListener("message", (event) => {
    // You can restrict to a known domain if needed
    // if (event.origin !== "https://stage.adrescue.in") return;
    if (event.data && event.data.type === "iframeHeight") {
      frame.style.height = event.data.height + "px";
    }
  });
</script>

                </div>
              </div></div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>

<!-- Modal for Add/Edit (90vw) -->
<div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
  <div class="modal-dialog" style="width:90vw; max-width:1200px; min-width:320px;" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="iframeModalLabel">Account</h4>
      </div>
      <div class="modal-body" style="height:80vh; padding:0; overflow:hidden;">
        <div style="position:relative; height:100%;">
          <div id="leadsIframeLoader" style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10; background:#fff; display:flex; justify-content:center; align-items:center;">
            <i class="leads-loader-msg"><span class="leads-loader-msg-text">Loading...</span></i>
          </div>
          <iframe id="modalIframe" src="" style="width:100%; height:100%; border:none;"></iframe>
        </div>
      </div>
    </div>
  </div>
</div>