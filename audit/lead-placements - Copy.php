<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '2024M'); // 1GB

include '../db.php';
include '../media-buyer/include.php';
include '/home/digitalb2k/stage.adrescue.in/functions-report.php';

function Auth2()
{
	if(!isset($_SESSION['log'])) {
		$_SESSION['error'] = 'Please Login!';
        $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		echo "<script>window.location = 'login.php?redirect=".$fullUrl."';</script>";
		exit();
	}
} 
//Auth2();
function loopAdRep($url) {
    global $output;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
    if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = array_merge($output, $fb_response['data']);
    } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = $fb_response['data']; 
    }
    
	if(isset($fb_response['paging']['next'])) {
		loopAdRep($fb_response['paging']['next']);
	} else { 
        return $output['data'] = $output; 
	}
}
function curl_get_file_contents2($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
 }


// Helper function to extract lead count from actions array
function LeadGen($actions, $type) {
    $lead = 0;
    if(isset($actions) && is_array($actions)) {
        foreach($actions as $action) {
            if(isset($action['action_type'])) {
                if($type == 'lead' && ($action['action_type'] == 'lead' || $action['action_type'] == 'leadgen_grouped' || $action['action_type'] == 'offsite_conversion.fb_pixel_lead')) {
                    $lead += isset($action['value']) ? (int)$action['value'] : 0;
                }
            }
        }
    }
    return $lead;
}

// Helper to format numbers in Indian numbering system (e.g. 1,23,456.78)
function formatIndianNumber($num, $decimals = 0) {
    $negative = $num < 0;
    $num = abs($num);
    
    $parts = explode('.', number_format($num, $decimals, '.', ''));
    $integer = $parts[0];
    $decimal = isset($parts[1]) && $parts[1] !== '' ? '.' . $parts[1] : '';
    $len = strlen($integer);
    
    if ($len > 3) {
        $last3 = substr($integer, -3);
        $rest = substr($integer, 0, $len - 3);
        $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
        $integer = $rest . ',' . $last3;
    }
    
    return ($negative ? '-' : '') . $integer . $decimal;
}

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token'];
$fbId = "";
if(isset($_GET['act'])){
    $fbId = $_GET['act'];
}

// Get all unique Meta ad account IDs from dashboard_accounts table
$unique_fb_accounts = array();
$sql_fb_accounts = mysqli_query($conn, "SELECT DISTINCT fb_id FROM dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0 AND fb_id IS NOT NULL AND fb_id != ''");
while($row_fb = mysqli_fetch_array($sql_fb_accounts)) {
    if(!empty($row_fb['fb_id'])) {
        $fb_ids = explode(',', $row_fb['fb_id']);
        foreach($fb_ids as $fb_id) {
            $fb_id = trim($fb_id);
            if(!empty($fb_id)) {
                $unique_fb_accounts[$fb_id] = $fb_id;
            }
        }
    }
}

// Get account names from adAccounts table
$fb_account_names = array();
if(!empty($unique_fb_accounts)) {
    $account_ids_str = "'" . implode("','", array_keys($unique_fb_accounts)) . "'";
    $sql_names = mysqli_query($conn, "SELECT account_id, name FROM adAccounts WHERE account_id IN ($account_ids_str) AND uid='".$_SESSION['uid']."'");
    while($row_name = mysqli_fetch_array($sql_names)) {
        $fb_account_names[$row_name['account_id']] = $row_name['name'];
    }
}

// Default date range - this month
$defaultStartDate = date('Y-m-01'); // First day of current month
$defaultEndDate = date('Y-m-d', strtotime('last day of this month')); // Last day of current month

// Get form values
$selectedAccount = isset($_POST['act']) ? $_POST['act'] : (isset($_GET['act']) ? $_GET['act'] : '');
$campaignNameContains = isset($_POST['campaign_name']) ? trim($_POST['campaign_name']) : '';
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : $defaultStartDate;
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : $defaultEndDate;

// Convert date format if needed (MM/DD/YYYY to YYYY-MM-DD)
if(strpos($startDate, '/') !== false) {
    $startDate = date('Y-m-d', strtotime($startDate));
}
if(strpos($endDate, '/') !== false) {
    $endDate = date('Y-m-d', strtotime($endDate));
}

// Initialize data arrays
$dailyPlacements = array();
$chartData = array();

// Process data if account is selected
if(!empty($selectedAccount)) {
    $fbId = $selectedAccount;
    
    // Build filtering array
    $filtering = array();
    $filtering[] = array('field' => 'campaign.objective', 'operator' => 'IN', 'value' => array('LEAD_GENERATION', 'OUTCOME_LEADS'));
    
    // Add campaign name filter if provided
    if(!empty($campaignNameContains)) {
        $filtering[] = array('field' => 'campaign.name', 'operator' => 'CONTAIN', 'value' => $campaignNameContains);
    }
    
    $filteringJson = json_encode($filtering);
    
    // Build API URL with time_increment=1 for day-by-day data
    $url = "https://graph.facebook.com/".$api_ver."/act_".$fbId."/insights?level=adset&breakdowns=publisher_platform,device_platform,platform_position&fields=adset_id,adset_name,campaign_name,campaign_id,reach,impressions,spend,objective,actions,date_start&filtering=".urlencode($filteringJson)."&time_range[since]=".$startDate."&time_range[until]=".$endDate."&time_increment=1&access_token=".$access_token."&limit=1000";
    $fb_response = $output = array();
	loopAdRep($url);  
	$fb_response = $output;

    //$requests = file_get_contents_curl2($url);
    //$fb_response = json_decode($requests, true);
    
    // Debug: log the API response (remove in production)
    // error_log("API URL: " . $url);
    // error_log("API Response: " . print_r($fb_response, true));
    
    if(isset($fb_response['data']) && is_array($fb_response['data'])) {
        // Process each day's data
        $totalSpend = 0;
        $totalLeads = 0;
        
        foreach($fb_response['data'] as $k => $val) {
            $lead = 0;
            if(isset($val['actions'])) { 
                $lead = LeadGen($val['actions'], 'lead'); 
            }
            
            // Get date - with time_increment=1, date_start should be present
            $date = '';
            if(isset($val['date_start'])) {
                $date = $val['date_start'];
            } elseif(isset($val['date_stop'])) {
                $date = $val['date_stop'];
            } elseif(isset($val['date'])) {
                $date = $val['date'];
            }
            
            if(!empty($date)) {
                // Group by date and placement
                $publisher = isset($val['publisher_platform']) ? $val['publisher_platform'] : 'unknown';
                $position = isset($val['platform_position']) ? $val['platform_position'] : 'unknown';
                $device = isset($val['device_platform']) ? $val['device_platform'] : 'unknown';
                $placement_key = $publisher.'_#_'.$position.'_#_'.$device;
                
                if(!isset($dailyPlacements[$date])) {
                    $dailyPlacements[$date] = array();
                }
                
                if(!isset($dailyPlacements[$date][$placement_key])) {
                    $dailyPlacements[$date][$placement_key] = 0;
                }
                
                $dailyPlacements[$date][$placement_key] += $lead;
            }
            
            // Track overall spend and leads for the selected date range
            if (isset($val['spend'])) {
                $totalSpend += (float)$val['spend'];
            }
            $totalLeads += $lead;
        }
        
        // Keep placement-wise data for multi-line chart
        // Get all unique placement keys across all dates, but only if they have leads > 0
        $allPlacementKeys = array();
        $placementTotals = array(); // Track total leads per placement
        
        foreach($dailyPlacements as $date => $placements) {
            foreach($placements as $placement_key => $leads) {
                if(!in_array($placement_key, $allPlacementKeys)) {
                    $allPlacementKeys[] = $placement_key;
                }
                // Track totals
                if(!isset($placementTotals[$placement_key])) {
                    $placementTotals[$placement_key] = 0;
                }
                $placementTotals[$placement_key] += $leads;
            }
        }
        
        // Filter out placements with zero total leads
        $activePlacements = array();
        foreach($allPlacementKeys as $placement_key) {
            if(isset($placementTotals[$placement_key]) && $placementTotals[$placement_key] > 0) {
                $activePlacements[] = $placement_key;
            }
        }
        $allPlacementKeys = $activePlacements;
        
        // Prepare chart data - keep placement-wise data
        $sortedDates = array_keys($dailyPlacements);
        sort($sortedDates);
        
        // Store placement-wise data for chart
        $chartData = array(
            'dates' => $sortedDates,
            'placements' => $allPlacementKeys,
            'data' => $dailyPlacements,
            'totals' => $placementTotals, // Include totals for display in legend
            'summary' => array(
                'spend' => $totalSpend,
                'leads' => $totalLeads
            )
        );
    } else {
        // Log error if API call failed
        if(isset($fb_response['error'])) {
            error_log("Facebook API Error: " . print_r($fb_response['error'], true));
        }
    }
}

// Shortcut mapping for placement names
$shortcuts = array(
    'Facebook' => 'FB',
    'Reels' => 'RL',
    'Desktop' => 'DT',
    'Search' => 'SR',
    'Feed' => 'FD',
    'Mobile App' => 'Mb A',
    'Explore' => 'Ex',
    'Marketplace' => 'Mkpl',
    'story' => 'Sty',
    'Instant Article' => 'IN Art',
    'Stories' => 'Sty',
    'Overlay' => 'Ov',
    'Video' => 'V',
    'Instagram' => 'IG',
    'Unknown' => 'UKN'
);

if(isset($_GET['act'])){ include 'include.php'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/images/favicon.ico" type="image/ico" />
    <title>Lead Placements - AdRescue</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- Date Range Picker CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <!-- Google Charts -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="/media-buyer/style.css" rel="stylesheet">
    <!-- jQuery must load first, then moment, then daterangepicker -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-daterangepicker/daterangepicker.js"></script>
</head>
<style>
.container { max-width: 1400px; }
.filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.filter-section .form-group {
    margin-bottom: 15px;
}
.filter-section label {
    font-weight: 600;
    margin-bottom: 5px;
    display: block;
}
.chart-container {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 20px;
    position: relative;
}
#chart_div {
    width: 100%;
    height: 500px;
}

.chart-summary-overlay {
    position: absolute;
    left: 40%;
    top: 40px; /* near top inside chart */
    transform: translateX(-50%);
    z-index: 2;
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
}

.summary-box {
    background: #fffdec;
    border-radius: 4px;
    padding: 6px 10px;
    margin-left: 8px;
    border: 1px solid #ced4da;
    text-align: center;
    font-size: 12px;
    min-width: 90px;
}

.summary-box-label {
    font-weight: 600;
    display: block;
}

.summary-box-value {
    font-weight: 700;
}
/* Loading overlay styles */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex;
}

.loading-content {
    background: white;
    padding: 10px 50px;
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
g text { cursor: pointer; font-size: 12px; }
</style>
<body>
<div class="loading-overlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h4>Loading...</h4>
        <p>Fetching data from Meta API...</p>
        <p><small>This may take a few moments.</small></p>
    </div>
</div>
<div class="container mt-3">
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="POST" action="" id="filterForm">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="actSelect">Select Ad Account</label>
                        <select class="form-control" id="actSelect" name="act" required>
                            <option value="">Select Ad Account...</option>
                            <?php 
                            if(!empty($unique_fb_accounts)) {
                                foreach($unique_fb_accounts as $account_id) {
                                    $account_name = isset($fb_account_names[$account_id]) ? $fb_account_names[$account_id] : 'Account ' . $account_id;
                                    $selected = ($account_id == $selectedAccount) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($account_id) . '" ' . $selected . '>' . htmlspecialchars($account_name) . ' (' . $account_id . ')</option>';
                                }
                            } else {
                                echo '<option value="" disabled>No Meta Ad Accounts Found</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="campaign_name">Campaign Name Contains</label>
                        <input type="text" class="form-control" id="campaign_name" name="campaign_name" placeholder="Leave empty for all campaigns" value="<?php echo htmlspecialchars($campaignNameContains); ?>">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="reportrange">Date Range</label>
                        <input type="text" id="reportrange" class="form-control" style="cursor: pointer;" readonly value="<?php echo date('m/d/Y', strtotime($startDate)); ?> - <?php echo date('m/d/Y', strtotime($endDate)); ?>">
                        <span id="dateRangeText" style="display:none;"></span>
                        <input type="hidden" id="start_date" name="start_date" value="<?php echo date('m/d/Y', strtotime($startDate)); ?>">
                        <input type="hidden" id="end_date" name="end_date" value="<?php echo date('m/d/Y', strtotime($endDate)); ?>">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block" style="width: 100%;">
                            <i class="fas fa-search"></i> Submit
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Chart Section -->
    <?php if(!empty($selectedAccount) && !empty($chartData) && isset($chartData['dates'])): ?>
    <div class="chart-container" id="chartContainer" style="display:none;">
        <?php
            $summarySpend = isset($chartData['summary']['spend']) ? $chartData['summary']['spend'] : 0;
            $summaryLeads = isset($chartData['summary']['leads']) ? $chartData['summary']['leads'] : 0;
            $summaryCpl = $summaryLeads > 0 ? $summarySpend / $summaryLeads : 0;
        ?>
        <div class="chart-summary-overlay">
            <div class="summary-box">
                <span class="summary-box-label">Spend</span>
                <span class="summary-box-value"><?php echo formatIndianNumber($summarySpend, 0); ?></span>
            </div>
            <div class="summary-box">
                <span class="summary-box-label">Leads</span>
                <span class="summary-box-value"><?php echo formatIndianNumber($summaryLeads, 0); ?></span>
            </div>
            <div class="summary-box">
                <span class="summary-box-label">CPL</span>
                <span class="summary-box-value"><?php echo formatIndianNumber($summaryCpl, 0); ?></span>
            </div>
        </div>
        <div id="chart_div"></div>
    </div>
    <?php
        // Show shortcuts legend in 5 columns, ascending order
        if (!empty($shortcuts)) {
            $shortcuts_sorted = $shortcuts;
            ksort($shortcuts_sorted);
            $columns = 5;
            $perColumn = ceil(count($shortcuts_sorted) / $columns);
            $chunks = array_chunk($shortcuts_sorted, $perColumn, true);
    ?>
    <div class="mt-3">
        <h6>Placement Shortcuts</h6>
        <div class="row">
            <?php foreach ($chunks as $chunk): ?>
            <div class="col-md-2">
                <ul class="list-unstyled mb-1">
                    <?php foreach ($chunk as $full => $short): ?>
                    <li><?php echo htmlspecialchars($full); ?> = <?php echo htmlspecialchars($short); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php } ?>
    <?php elseif(!empty($selectedAccount) && (empty($chartData) || !isset($chartData['dates']))): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No data found for the selected filters and date range.
        <?php if(isset($fb_response['error'])): ?>
        <br><small>API Error: <?php echo htmlspecialchars(isset($fb_response['error']['message']) ? $fb_response['error']['message'] : 'Unknown error'); ?></small>
        <?php endif; ?>
        <br><small>Date Range: <?php echo date('m/d/Y', strtotime($startDate)); ?> to <?php echo date('m/d/Y', strtotime($endDate)); ?></small>
    </div>
    <?php else: ?>
    <div class="alert alert-secondary">
        <i class="fas fa-info-circle"></i> Please select an ad account and click Submit to view the report.
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Wait for all libraries to load
$(document).ready(function() {
    // Hide loading overlay on page load
    <?php if(!empty($selectedAccount)): ?>
    $('.loading-overlay').removeClass('active');
    <?php if(!empty($chartData) && isset($chartData['dates'])): ?>
    $('#chartContainer').show();
    <?php endif; ?>
    <?php endif; ?>
    
    // Show loading overlay when form is submitted
    $('#filterForm').on('submit', function() {
        $('.loading-overlay').addClass('active');
        $('#chartContainer').hide();
    });
    // Initialize date range picker - default to this month
    var startDateStr = '<?php echo date('m/d/Y', strtotime($startDate)); ?>';
    var endDateStr = '<?php echo date('m/d/Y', strtotime($endDate)); ?>';
    
    // Validate dates and set defaults if invalid
    var start = moment(startDateStr, 'MM/DD/YYYY');
    var end = moment(endDateStr, 'MM/DD/YYYY');
    
    if (!start.isValid()) {
        start = moment().startOf('month');
    }
    if (!end.isValid()) {
        end = moment().endOf('month');
    }
    
    function cb(start, end) {
        var startFormatted = start.format('MM/DD/YYYY');
        var endFormatted = end.format('MM/DD/YYYY');
        var dateRangeText = startFormatted + ' - ' + endFormatted;
        $('#reportrange').val(dateRangeText);
        $('#dateRangeText').html(dateRangeText);
        $('#start_date').val(startFormatted);
        $('#end_date').val(endFormatted);
    }
    
    // Initialize the date range picker - ensure jQuery and moment are loaded
    if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined') {
        try {
            $('#reportrange').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                opens: 'left',
                locale: {
                    format: 'MM/DD/YYYY'
                },
                autoUpdateInput: true
            }, cb);
            
            // Set initial display
            cb(start, end);
        } catch(e) {
            console.error('Error initializing date range picker:', e);
            $('#reportrange').val(startDateStr + ' - ' + endDateStr);
        }
    } else {
        // Retry if libraries not ready
        setTimeout(function() {
            if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined') {
                $('#reportrange').daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    opens: 'left',
                    locale: {
                        format: 'MM/DD/YYYY'
                    },
                    autoUpdateInput: true
                }, cb);
                cb(start, end);
            }
        }, 500);
    }
    
    <?php if(!empty($chartData) && isset($chartData['dates'])): 
        // Function to check if placement is good (should be shown by default)
        function isGoodPlacement($placement_key) {
            $key_lower = strtolower($placement_key);
            
            // Bad placements - hide by default (check these first)
            $bad_patterns = array(
                'audience_network',
                'rewarded_video',
                'in_stream_video',
                'in_stream',
                'right_column',
                'instant_article',
                'instant_articles',
                'overlay',
                'banner',
                'facebook_story',
                'instagram_story',
                'fb_story',
                'ig_story',
                'stories',
                'notification', // Facebook notifications
                'explore' // Instagram explore
            );
            
            foreach($bad_patterns as $pattern) {
                if(strpos($key_lower, $pattern) !== false) {
                    return false; // Bad placement
                }
            }
            
            // Good placements - show by default
            // Check for feed, reels, marketplace in various combinations
            $has_feed = (strpos($key_lower, 'feed') !== false);
            $has_reel = (strpos($key_lower, 'reel') !== false);
            $has_marketplace = (strpos($key_lower, 'marketplace') !== false || strpos($key_lower, 'mkpl') !== false);
            
            // If it has feed, reels, or marketplace, it's good (already checked bad patterns above)
            if (($has_feed || $has_reel || $has_marketplace)) {
                return true; // Good placement
            }
            
            // Default: hide if not explicitly good (neutral/unknown placements should be hidden)
            return false;
        }
        
        // Function to shorten placement name
        function shortenPlacementName($placement_key, $shortcuts) {
            $parts = explode('_#_', $placement_key);
            $shortened = array();
            foreach($parts as $part) {
                $part = ucwords(str_replace('_', ' ', strtolower($part)));
                // Apply shortcuts
                foreach($shortcuts as $full => $short) {
                    $part = str_ireplace($full, $short, $part);
                }
                $shortened[] = $part;
            }
            return implode(' - ', $shortened);
        }
    ?>
    // Load Google Charts
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    
    function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('date', 'Date');
        
        // Add columns for each placement with total leads in brackets
        // Track which placements are good/bad for visibility
        var placementVisibility = {};
        <?php 
        $placementIndex = 0;
        $overallLeads = isset($chartData['summary']['leads']) ? $chartData['summary']['leads'] : array_sum($chartData['totals']);
        foreach($chartData['placements'] as $placement_key) {
            $shortName = shortenPlacementName($placement_key, $shortcuts);
            $totalLeadsPlacement = isset($chartData['totals'][$placement_key]) ? $chartData['totals'][$placement_key] : 0;
            $percent = ($overallLeads > 0 && $totalLeadsPlacement > 0) ? round(($totalLeadsPlacement / $overallLeads) * 100) : 0;
            $leadsWord = ($totalLeadsPlacement == 1 ? ' lead' : ' leads');
            $labelWithTotal = $shortName . ' (' . $totalLeadsPlacement . $leadsWord . ', ' . $percent . '%)';
            echo "data.addColumn('number', '".addslashes($labelWithTotal)."');\n";
            $placementIndex++;
        }
        ?>
        
        // Add rows for each date
        var rows = [
            <?php 
            foreach($chartData['dates'] as $date) {
                $dt = new DateTime($date);
                $jsDate = "new Date(".$dt->format('Y').",".($dt->format('n')-1).",".$dt->format('j').")";
                $row = "[".$jsDate;
                foreach($chartData['placements'] as $placement_key) {
                    $leads = isset($chartData['data'][$date][$placement_key]) ? (int)$chartData['data'][$date][$placement_key] : 0;
                    $row .= ", ".$leads;
                }
                $row .= "],\n";
                echo $row;
            }
            ?>
        ];
        
        data.addRows(rows);
        
        // Track which series should be visible (all columns stay in view, but we'll set values to null when hidden)
        // This way legend shows all items but lines are hidden
        var seriesVisibility = {};
        <?php 
        $colIndex = 0; // Start from 0 (0 is date, always visible)
        foreach($chartData['placements'] as $placement_key) {
            // By default, show all series
            echo "seriesVisibility[".$colIndex."] = true;\n";
            $colIndex++;
        }
        ?>
        
        // Function to get visible data (set hidden series values to null)
        function getVisibleData() {
            var visibleRows = [];
            for (var i = 0; i < rows.length; i++) {
                var newRow = [rows[i][0]]; // Date column
                for (var j = 1; j < rows[i].length; j++) {
                    var seriesIdx = j - 1; // j=1 means series 0, j=2 means series 1, etc.
                    if (seriesVisibility[seriesIdx] === true) {
                        newRow.push(rows[i][j]); // Show value
                    } else {
                        newRow.push(null); // Hide by setting to null
                    }
                }
                visibleRows.push(newRow);
            }
            return visibleRows;
        }
        
        // If there are no rows (no data), show a friendly message and skip drawing the chart
        if (rows.length === 0) {
            var msg = '<div style="text-align:center;padding:40px;font-weight:600;">No data available for the selected filters and date range.</div>';
            var container = document.getElementById('chart_div');
            if (container) {
                container.innerHTML = msg;
            }
            if (typeof $ !== 'undefined') {
                $('.loading-overlay').removeClass('active');
                $('#chartContainer').show();
            }
            return;
        }
        
        // Create data table with visibility applied
        var visibleData = new google.visualization.DataTable();
        visibleData.addColumn('date', 'Date');
        <?php 
        $overallLeads = isset($chartData['summary']['leads']) ? $chartData['summary']['leads'] : array_sum($chartData['totals']);
        foreach($chartData['placements'] as $placement_key) {
            $shortName = shortenPlacementName($placement_key, $shortcuts);
            $totalLeadsPlacement = isset($chartData['totals'][$placement_key]) ? $chartData['totals'][$placement_key] : 0;
            $percent = ($overallLeads > 0 && $totalLeadsPlacement > 0) ? round(($totalLeadsPlacement / $overallLeads) * 100) : 0;
            $leadsWord = ($totalLeadsPlacement == 1 ? ' lead' : ' leads');
            $labelWithTotal = $shortName . ' (' . $totalLeadsPlacement . $leadsWord . ', ' . $percent . '%)';
            echo "visibleData.addColumn('number', '".addslashes($labelWithTotal)."');\n";
        }
        ?>
        visibleData.addRows(getVisibleData());
        
        // Calculate max value for Y-axis (only from visible series)
        var maxValue = 0;
        for (var i = 0; i < rows.length; i++) {
            for (var j = 1; j < rows[i].length; j++) {
                var seriesIdx = j - 1;
                if (seriesVisibility[seriesIdx] === true && rows[i][j] > maxValue) {
                    maxValue = rows[i][j];
                }
            }
        }
        
        // Set Y-axis max to be slightly above actual max (round up to next 5 or 10% above)
        var yAxisMax = Math.ceil(maxValue * 1.1);
        // Round to nearest 5 for cleaner display
        yAxisMax = Math.ceil(yAxisMax / 5) * 5;
        if (yAxisMax < 10) yAxisMax = 10;
        
        // For first half expansion, create custom ticks with more granularity in lower range
        // First half gets more ticks (0 to yAxisMax/2), second half gets fewer
        var firstHalfMax = Math.ceil(yAxisMax / 2);
        var secondHalfMax = yAxisMax;
        
        // Build unique X-axis ticks so each date appears only once
        var hAxisTicks = [];
        var seenTickTimes = {};
        for (var i = 0; i < rows.length; i++) {
            var t = rows[i][0].getTime();
            if (!seenTickTimes[t]) {
                seenTickTimes[t] = true;
                hAxisTicks.push(rows[i][0]);
            }
        }
        
        var options = {
            title: 'Placements Lead Count by Date',
            backgroundColor: '#DCE3E3',
            hAxis: {
                title: 'Date Range',
                titleTextStyle: {color: '#333'},
                format: 'd', // Show only day number on axis
                ticks: hAxisTicks, // one tick per unique date
                slantedText: true,
                slantedTextAngle: 45
            },
            vAxis: {
                title: 'Number of Leads',
                titleTextStyle: {color: '#333'},
                minValue: 0,
                maxValue: yAxisMax,
                viewWindow: {
                    min: 0,
                    max: yAxisMax
                },
                // Custom ticks - first half: 0, 2, 4, 6, 8, etc. (step of 2)
                ticks: (function() {
                    var ticks = [0];
                    // First half: step of 2 (0, 2, 4, 6, 8, 10, 12, ...)
                    for (var i = 2; i <= firstHalfMax; i += 2) {
                        ticks.push(i);
                    }
                    // Second half: less granular (every 5-10 units, compressed)
                    var secondHalfStep = Math.max(5, Math.ceil((secondHalfMax - firstHalfMax) / 4));
                    for (var i = firstHalfMax + secondHalfStep; i <= secondHalfMax; i += secondHalfStep) {
                        if (!ticks.includes(i)) {
                            ticks.push(i);
                        }
                    }
                    // Ensure max is included
                    if (!ticks.includes(yAxisMax)) {
                        ticks.push(yAxisMax);
                    }
                    return ticks.sort(function(a, b) { return a - b; });
                })()
            },
            legend: {
                position: 'right',
                textStyle: {fontSize: 11}
            },
            chartArea: {
                left: 50,
                top: 80, // leave room for summary boxes
                width: '78%',
                height: '65%',
                backgroundColor: '#DCE3E3'
            },
            lineWidth: 2,
            pointSize: 4,
            curveType: 'function', // Wave/smooth curve format
            animation: {
                startup: true,
                duration: 1000,
                easing: 'out'
            },
            // Note: Visibility is controlled by view.setColumns() instead of series.visible
            // This is more reliable for Google Charts
            series: {
                <?php 
                $seriesIndex = 0;
                $seriesConfig = array();
                foreach($chartData['placements'] as $placement_key) {
                    // All series visible in legend for clicking
                    $seriesConfig[] = $seriesIndex . ": {visibleInLegend: true}";
                    $seriesIndex++;
                }
                if (empty($seriesConfig)) {
                    echo "// No series configured";
                } else {
                    echo implode(",\n                ", $seriesConfig);
                }
                ?>
            },
            colors: [
                <?php 
                // Standard distinct colors - red, green, blue, yellow, orange, purple, pink, brown, etc.
                $colors = array(
                    '#FF0000', // Red
                    '#52da52', // Green
                    '#0000FF', // Blue
                    '#FF00FF', // Magenta/Pink
                    '#FF8000', // Orange
                    '#8000FF', // Purple
                    
                    '#804000', // Brown
                    '#00FFFF', // Cyan
                    '#FF0080', // Rose
                    '#0080FF', // Light Blue
                    '#80FF00', // Lime
                    '#FF4080', // Pink
                    '#FFFF00', // Yellow
                    '#4080FF', // Sky Blue
                    '#80FF80', // Light Green
                    '#FF8040'  // Coral
                );
                $colorList = array();
                foreach($chartData['placements'] as $idx => $placement_key) {
                    $color = isset($colors[$idx % count($colors)]) ? $colors[$idx % count($colors)] : '#FF0000';
                    $colorList[] = "'".$color."'";
                }
                echo implode(', ', $colorList);
                ?>
            ]
        };
        
        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        
        // Draw chart with visible data (all series shown; legend clicks only highlight)
        google.visualization.events.addListener(chart, 'ready', function () {
            // Add pointer cursor to legend labels on the right side
            var svg = document.querySelector('#chart_div svg');
            if (!svg) return;
            var legendTexts = svg.querySelectorAll('g[clip-path] text');
            legendTexts.forEach(function(t) {
                t.style.cursor = 'pointer';
            });
        });
        chart.draw(visibleData, options);
        
        // Hide loading overlay and show chart when ready
        $('.loading-overlay').removeClass('active');
        $('#chartContainer').show();
    }
    <?php endif; ?>
});
</script>
</body>
</html>
