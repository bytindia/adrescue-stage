<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Meta - Invoices';
$pgID = 3;
$err =''; 
function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
 }


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


require __DIR__ . '/vendor/autoload.php';

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Exception\RequestException;

include 'config.php';

$api = Api::init($app_id, $app_secret, $access_token);
$api->setLogger(new CurlLogger());

$fields = array(
'spend', 
'reach', 'impressions'
);
$params = array(
'level' => 'account', 
'filtering' => array(), 
'breakdowns' => array(), 
'time_range' => array('since' => date("Y-m-d", strtotime($_SESSION['stDt'])),'until' => date("Y-m-d", strtotime($_SESSION['enDt'])))
);
include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts WHERE uid='".$_SESSION['uid']."'";

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
}
$ac_status = array(
1 => 'ACTIVE',
2 => 'DISABLED',
3 => 'UNSETTLED',
7 => 'PENDING_RISK_REVIEW',
8 => 'PENDING_SETTLEMENT',
9 => 'IN_GRACE_PERIOD',
100 => 'PENDING_CLOSURE',
101 => 'CLOSED',
201 => 'ANY_ACTIVE',
202 => 'ANY_CLOSED'
);
$invoice_data = [];
if(isset($_POST['submit'])){
	
	//d($_POST); exit;

    $url = 'https://graph.facebook.com/'.$api_ver.'/me/businesses?access_token=' . $access_token;

                                        $requests = curl_get_file_contents($url);
                                        $fb_response = json_decode($requests,true); 

    $businessId = $_POST['business_account'];
    $monthInput = $_POST['month']; // e.g., "May 2025"

    // Persist user selections for defaulting after reload
    $_SESSION['invoice_meta_business'] = $businessId;
    $_SESSION['invoice_meta_month'] = $monthInput;

    // Convert "May 2025" to start and end date
    $startDate = date('Y-m-01', strtotime($monthInput));        // e.g., 2025-05-01
    $endDate   = date('Y-m-t', strtotime($monthInput));         // e.g., 2025-05-31

    // Construct the Meta Graph API URL
     $url = "https://graph.facebook.com/$api_ver/$businessId/business_invoices?access_token=$access_token" .
           "&fields=ad_account_ids,amount,invoice_id,advertiser_name,amount_due," .
           "billing_period,download_uri,invoice_date&" .
           "start_date=$startDate&end_date=$endDate&limit=100";
    
    $requests = curl_get_file_contents($url);
    $fb_response = json_decode($requests,true); 
	if(isset($fb_response['data']) && count($fb_response['data'])>0){
        $invoice_data = $fb_response['data'];
    }
	
}
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.blue {
    color: #0987f7;
}
/* Ensure page content fills viewport so footer sits at bottom */
.right_col { min-height: calc(100vh - 140px); }
/* Loading overlay styles (modeled after dash/multi-client.php) */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    display: flex; /* visible by default; will hide on load */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
.loading-content {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.right_col { min-height: 88vh;}
</style>
  <body class="nav-md">
    <div class="loading-overlay" id="pageLoadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h4>Loading...</h4>
            <p>Fetching invoices from Meta… Please wait.</p>
        </div>
    </div>
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
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>  <a  class="btn btn-primary btn-sm " href="invoice-cron.php" target="_blank"> SOA - Update Meta Invoices </a> </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
						if(!isset($_SESSION['fb_id']) || $_SESSION['fb_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="loading.php?pg=fb-login.php">
                                      <img src="images/fb-login.png">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} 
						else {
							//echo "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}";
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by name asc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
                                        $url = 'https://graph.facebook.com/'.$api_ver.'/me/businesses?access_token=' . $access_token;

                                        $requests = curl_get_file_contents($url);
                                        $fb_response = json_decode($requests,true); 
                                       // d($fb_response); exit;
								?>
                                <?php
                                // Determine selected defaults from POST or SESSION
                                $selectedBusiness = '';
                                $selectedMonth = '';
                                if(isset($_POST['business_account'])) { $selectedBusiness = $_POST['business_account']; }
                                elseif(isset($_SESSION['invoice_meta_business'])) { $selectedBusiness = $_SESSION['invoice_meta_business']; }
                                if(isset($_POST['month'])) { $selectedMonth = $_POST['month']; }
                                elseif(isset($_SESSION['invoice_meta_month'])) { $selectedMonth = $_SESSION['invoice_meta_month']; }

                                // Generate last 6 months dynamically, EXCLUDING current month
                                $months = [];
                                for ($i = 1; $i <= 6; $i++) {
                                    $months[] = date("M Y", strtotime("-$i months"));
                                }
                                // Ensure previously selected month appears only if it's not the current month
                                $currentMonthLabel = date("M Y", strtotime("now"));
                                if($selectedMonth && $selectedMonth !== $currentMonthLabel && !in_array($selectedMonth, $months)) {
                                    array_unshift($months, $selectedMonth);
                                    $months = array_values(array_unique($months));
                                }
                                ?>

                                <!-- Inline Single-Line Form -->
                                <div class="container my-4">
                                    <form method="POST" action="invoice-meta.php" id="invoiceMetaForm">
                                        <div class="row" style="gap:10px; align-items:center;">
                                            <div class="col-md-2 col-sm-6 col-xs-12">
                                                <select class="form-control" id="businessAccount" name="business_account" required>
                                                    <option value="">Select Business Account</option>
                                                    <?php foreach ($fb_response['data'] as $account): ?>
                                                        <option value="<?= $account['id'] ?>" <?= ($selectedBusiness==$account['id']?'selected':'') ?>><?= $account['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 col-sm-4 col-xs-12">
                                                <select class="form-control" id="month" name="month" required>
                                                    <option value="">Select Month</option>
                                                    <?php foreach ($months as $month): ?>
                                                        <option value="<?= $month ?>" <?= ($selectedMonth==$month?'selected':'') ?>><?= $month ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 col-sm-2 col-xs-12">
                                                <button name="submit" type="submit" class="btn btn-primary btn-block">Fetch</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <br><br>
                                <?php if(count($invoice_data)>0) { 
                                    $totalAmount = 0;
                                    foreach ($invoice_data as $invoice) {
                                        $totalAmount += $invoice['amount_due']['amount_in_hundredths'] / 100;
                                    }
                                    $sqlRev=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
						

	$fbAccN = $gAccN =  array();
						while($sqlROW=mysqli_fetch_array($sqlRev)) { $fbAccN[$sqlROW["account_id"]] = $sqlROW["name"]; }
						


                                    ?>
                                <!-- Bootstrap Table -->
                                <div class="container my-5">
                                    
                                    <table id="datatable" class="table table-bordered table-hover table-striped align-middle shadow-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Invoice ID</th>
                                                <th>Advertiser</th>
                                                <th>Ad Account</th>
                                                <th>Billing Period</th>
                                                <th>Invoice Date</th>
                                                <th>Amount (INR)</th>
                                                <th>Download</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($invoice_data as $index => $invoice): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= $invoice['invoice_id'] ?></td>
                                                    <td><?= $invoice['advertiser_name'] ?></td>
                                                    <td><?php if(isset($fbAccN[$invoice['ad_account_ids'][0]])) { echo $fbAccN[$invoice['ad_account_ids'][0]]; } else { echo $invoice['ad_account_ids'][0]; } ?></td>
                                                    <td><?= date('F Y', strtotime($invoice['billing_period'])) ?></td>
                                                    <td><?= date('d M Y', strtotime($invoice['invoice_date'])) ?></td>
                                                    <td><?= $invoice['amount_due']['amount'] ?> <?= $invoice['amount_due']['currency'] ?></td>
                                                    <td class="text-center"><a href="<?= $invoice['download_uri'] ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fa fa-download"></i></a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <!-- Total Row -->
            <tr class="fw-bold table-light">
                <td colspan="6" class="text-end">Total</td>
                <td colspan="2"><?= number_format($totalAmount, 2) ?> INR</td>
            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php } ?>
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

					<?php	}	?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<script src="/vendors/jquery/dist/jquery.min.js"></script>
<script>
    (function(){
        function hideOverlay(){
            var el = document.getElementById('pageLoadingOverlay');
            if(el){ el.style.display = 'none'; }
        }
        if(document.readyState === 'complete'){
            hideOverlay();
        } else {
            window.addEventListener('load', hideOverlay);
        }
        // Show overlay on form submit
        document.addEventListener('DOMContentLoaded', function(){
            var form = document.getElementById('invoiceMetaForm');
            if(form){
                form.addEventListener('submit', function(){
                    var el = document.getElementById('pageLoadingOverlay');
                    if(el){ el.style.display = 'flex'; }
                });
            }
        });
    })();
</script>