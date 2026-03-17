<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Check if user is logged in
$_SESSION['uid']=2;
if(!isset($_SESSION['uid'])) {
    $pg = '../login.php';
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
    exit();
}

include '../db.php';
include 'overview-config.php';
include 'google-campaigns.php';

// Set default date ranges if not set
if(!isset($_SESSION['stDt1'])) {
    // D1: Last weekdays (Monday to Friday)
    $lastMonday = date('m/d/Y', strtotime('last monday'));
    $lastFriday = date('m/d/Y', strtotime('last friday'));
    $_SESSION['stDt1'] = $lastMonday;
    $_SESSION['enDt1'] = $lastFriday;
}

if(!isset($_SESSION['stDt2'])) {
    // D2: Last weekend (Saturday and Sunday)
    $lastSaturday = date('m/d/Y', strtotime('last saturday'));
    $lastSunday = date('m/d/Y', strtotime('last sunday'));
    $_SESSION['stDt2'] = $lastSaturday;
    $_SESSION['enDt2'] = $lastSunday;
}

if(!isset($_SESSION['stDt3'])) {
    // D3: Today
    $today = date('m/d/Y');
    $_SESSION['stDt3'] = $today;
    $_SESSION['enDt3'] = $today;
}

// Handle form submission
$showReport = false;
$selectedClients = [];
$reportData = [];

if(isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedClients = isset($_POST['clients']) ? $_POST['clients'] : [];
    
    // Get date ranges from form
    $stDt1 = $_POST['start_date1'];
    $enDt1 = $_POST['end_date1'];
    $stDt2 = $_POST['start_date2'];
    $enDt2 = $_POST['end_date2'];
    $stDt3 = $_POST['start_date3'];
    $enDt3 = $_POST['end_date3'];
    
    // Store selections in session for persistence
    $_SESSION['stDt1'] = $stDt1;
    $_SESSION['enDt1'] = $enDt1;
    $_SESSION['stDt2'] = $stDt2;
    $_SESSION['enDt2'] = $enDt2;
    $_SESSION['stDt3'] = $stDt3;
    $_SESSION['enDt3'] = $enDt3;
    $_SESSION['selected_clients'] = $selectedClients;
    
    // Convert date format for API calls
    $dtRange1_1 = date("Y-m-d", strtotime($stDt1));
    $dtRange1_2 = date("Y-m-d", strtotime($enDt1));
    $dtRange1_1_compact = date("Ymd", strtotime($stDt1));
    $dtRange1_2_compact = date("Ymd", strtotime($enDt1));
    
    $dtRange2_1 = date("Y-m-d", strtotime($stDt2));
    $dtRange2_2 = date("Y-m-d", strtotime($enDt2));
    $dtRange2_1_compact = date("Ymd", strtotime($stDt2));
    $dtRange2_2_compact = date("Ymd", strtotime($enDt2));
    
    $dtRange3_1 = date("Y-m-d", strtotime($stDt3));
    $dtRange3_2 = date("Y-m-d", strtotime($enDt3));
    $dtRange3_1_compact = date("Ymd", strtotime($stDt3));
    $dtRange3_2_compact = date("Ymd", strtotime($enDt3));

    // Load account ID -> Name maps (similar to dashboard-clients.php)
    $fbAccN = $gAccN = $inAccN = $taAccN = array();
    $sqlRev1 = mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
    $sqlRev2 = mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
    $sqlRev3 = mysqli_query($conn, "SELECT account_id,name FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
    $sqlRev4 = mysqli_query($conn, "SELECT account_id,name FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");
    while($sqlROW1 = mysqli_fetch_array($sqlRev1)) { $fbAccN[$sqlROW1['account_id']] = $sqlROW1['name']; }
    while($sqlROW2 = mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2['account_id']] = $sqlROW2['name']; }
    while($sqlROW3 = mysqli_fetch_array($sqlRev3)) { $inAccN[$sqlROW3['account_id']] = $sqlROW3['name']; }
    while($sqlROW4 = mysqli_fetch_array($sqlRev4)) { $taAccN[$sqlROW4['account_id']] = $sqlROW4['name']; }
    
    // Get data for each selected client
    foreach($selectedClients as $clientId) {
        // Get client details using the correct query structure
        $cirRes = mysqli_query($conn, "SELECT da.tbl_id, da.client_name, da.nic_name, da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain, br.total_budget FROM dashboard_accounts da LEFT JOIN budget_reminder br ON da.bud_id = br.tbl_id WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $clientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0");
        $row = mysqli_fetch_assoc($cirRes);
        
        if($row) {
            $reportData[$clientId] = [
                'client_name' => $row['nic_name'],
                'meta_spend_d1' => 0, 'meta_spend_d2' => 0, 'meta_spend_d3' => 0,
                'meta_leads_d1' => 0, 'meta_leads_d2' => 0, 'meta_leads_d3' => 0,
                'google_spend_d1' => 0, 'google_spend_d2' => 0, 'google_spend_d3' => 0,
                'google_leads_d1' => 0, 'google_leads_d2' => 0, 'google_leads_d3' => 0,
                'tooltip_html' => ''
            ];
            
            // Get account IDs
            $fb_acc_ids = array_filter(explode(',', $row['fb_id']));
            $g_acc_ids = array_filter(explode(',', $row['g_id']));
            $ta_acc_ids = array_filter(explode(',', $row['ta_id']));
            $in_acc_ids = array_filter(explode(',', $row['in_id']));

            // Build tooltip HTML with account names and links including selected date ranges
            $tooltipParts = [];
            if(!empty($fb_acc_ids)) {
                $part = '<div style="margin-bottom:6px;"><strong>Meta</strong><ul style="padding-left:16px; margin:4px 0;">';
                foreach($fb_acc_ids as $acc) {
                    $acc = trim($acc);
                    $accName = isset($fbAccN[$acc]) ? $fbAccN[$acc] : ('act_'.$acc);
                    $link = 'https://business.facebook.com/adsmanager/manage/campaigns?act='.$acc.'&date[since]='.$dtRange1_1.'&date[until]='.$dtRange1_2;
                    $part .= '<li><a href="'.$link.'" target="_blank" rel="noopener">'.htmlspecialchars($accName, ENT_QUOTES).'</a></li>';
                }
                $part .= '</ul></div>';
                $tooltipParts[] = $part;
            }
            if(!empty($g_acc_ids)) {
                $part = '<div style="margin-bottom:6px;"><strong>Google Ads</strong><ul style="padding-left:16px; margin:4px 0;">';
                foreach($g_acc_ids as $acc) {
                    $acc = trim($acc);
                    $accName = isset($gAccN[$acc]) ? $gAccN[$acc] : $acc;
                    // Deep link to Google Ads with custom date range
                    $link = 'https://ads.google.com/aw/campaigns?ocid='.$acc.'&__c='.$acc.'&__o=c&r=custom&start='.$dtRange1_1_compact.'&end='.$dtRange1_2_compact;
                    $part .= '<li><a href="'.$link.'" target="_blank" rel="noopener">'.htmlspecialchars($accName, ENT_QUOTES).'</a></li>';
                }
                $part .= '</ul></div>';
                $tooltipParts[] = $part;
            }
            if(!empty($in_acc_ids)) {
                $part = '<div style="margin-bottom:6px;"><strong>Instagram</strong><ul style="padding-left:16px; margin:4px 0;">';
                foreach($in_acc_ids as $acc) {
                    $acc = trim($acc);
                    $accName = isset($inAccN[$acc]) ? $inAccN[$acc] : $acc;
                    $part .= '<li>'.htmlspecialchars($accName, ENT_QUOTES).'</li>';
                }
                $part .= '</ul></div>';
                $tooltipParts[] = $part;
            }
            if(!empty($ta_acc_ids)) {
                $part = '<div style="margin-bottom:6px;"><strong>Other</strong><ul style="padding-left:16px; margin:4px 0;">';
                foreach($ta_acc_ids as $acc) {
                    $acc = trim($acc);
                    $accName = isset($taAccN[$acc]) ? $taAccN[$acc] : $acc;
                    $part .= '<li>'.htmlspecialchars($accName, ENT_QUOTES).'</li>';
                }
                $part .= '</ul></div>';
                $tooltipParts[] = $part;
            }
            if(!empty($tooltipParts)) {
                $tooltipHtml = implode('', $tooltipParts);
                $reportData[$clientId]['tooltip_html'] = htmlspecialchars($tooltipHtml, ENT_QUOTES);
            }
            
            // Get project names and name contains
            $proj_names = $name_contain = [];
            if($row['proj_name'] != '') { 
                $proj_names = array_filter(unserialize($row['proj_name'])); 
            }
            if($row['name_contain'] != '') { 
                $name_contain = array_filter(unserialize($row['name_contain'])); 
            }
            
            // Get Meta data for all 3 date ranges
            if(!empty($fb_acc_ids)) {
                // Meta API call
                $obj_arr = array(
                    //'POST_ENGAGEMENT' => 'post_engagement', 
                    //'LINK_CLICKS' => 'link_click',
                    //'VIDEO_VIEWS' => 'video_view',
                    'LEAD_GENERATION' => 'lead', 
                    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
                    'MESSAGES' => 'onsite_conversion.messaging_block',
                    'OUTCOME_LEADS' => 'lead',
                    'OUTCOME_SALES' => 'purchase',
                    'PRODUCT_CATALOG_SALES' => 'purchase'
                );
                
                // Process each date range
                $dateRanges = [
                    'd1' => ['start' => $dtRange1_1, 'end' => $dtRange1_2],
                    'd2' => ['start' => $dtRange2_1, 'end' => $dtRange2_2],
                    'd3' => ['start' => $dtRange3_1, 'end' => $dtRange3_2]
                ];
                
                foreach($dateRanges as $rangeKey => $range) {
                    $fb_data = [];
                    foreach($fb_acc_ids as $fb_id) {
                        $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&breakdowns=publisher_platform&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$range['start']."&time_range[until]=".$range['end']."&access_token=".$access_token."&limit=1750";
                        $req = file_get_contents_curl($url);
                        $res = json_decode($req, true);  
                        
                        if(isset($res['data'])){
                            $fb_data[] = $res['data'];
                        }
                    }
                    
                    // Fallback: If no data with breakdown, try without breakdown
                    if(empty($fb_data) || (count($fb_data) == 1 && empty($fb_data[0]))) {
                        $fb_data = [];
                        foreach($fb_acc_ids as $fb_id) {
                            $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$range['start']."&time_range[until]=".$range['end']."&access_token=".$access_token."&limit=1750";
                            $req = file_get_contents_curl($url);
                            $res = json_decode($req, true);  
                            if(isset($res['data'])){
                                $fb_data[] = $res['data'];
                            }
                        }
                    }
                    
                    // Process Meta data with filtering
                    $meta_spend = 0;
                    $meta_leads = 0;
                    
                    // Group campaigns by campaign_id to handle breakdown data properly
                    $grouped_campaigns = [];
                    
                    foreach($fb_data as $data) {
                        foreach($data as $campaign) {
                            $campaign_id = $campaign['campaign_id'];
                            $campaign_name = $campaign['campaign_name'];
                            
                            // Apply filtering based on project names and name contains
                            $should_include = true;
                            
                            // Check if campaign should be included based on filtering
                            if(!empty($proj_names) || !empty($name_contain)) {
                                $should_include = false;
                                
                                // Check project names
                                if(!empty($proj_names)) {
                                    foreach($proj_names as $proj_name) {
                                        if(stripos($campaign_name, $proj_name) !== false) {
                                            $should_include = true;
                                            break;
                                        }
                                    }
                                }
                                
                                // Check name contains
                                if(!empty($name_contain) && !$should_include) {
                                    $proj_key = contains_proj($campaign_name);
                                    if($proj_key !== '') {
                                        $should_include = true;
                                    }
                                }
                            }
                            
                            if($should_include) {
                                // Group by campaign_id and platform
                                if(!isset($grouped_campaigns[$campaign_id])) {
                                    $grouped_campaigns[$campaign_id] = [
                                        'campaign_name' => $campaign_name,
                                        'objective' => $campaign['objective'],
                                        'platforms' => []
                                    ];
                                }
                                
                                // Get publisher platform (facebook or instagram)
                                $publisher_platform = isset($campaign['publisher_platform']) ? $campaign['publisher_platform'] : 'facebook';
                                
                                // Store platform-specific data
                                $grouped_campaigns[$campaign_id]['platforms'][$publisher_platform] = [
                                    'spend' => $campaign['spend'],
                                    'actions' => isset($campaign['actions']) ? $campaign['actions'] : []
                                ];
                            }
                        }
                    }
                    
                    // Process grouped campaigns
                    if(!empty($grouped_campaigns)) {
                        foreach($grouped_campaigns as $campaign_id => $campaign_data) {
                            foreach($campaign_data['platforms'] as $platform => $platform_data) {
                                // Calculate leads for this platform
                                $leads = 0;
                                if(!empty($platform_data['actions'])) {
                                    if(array_key_exists($campaign_data['objective'], $obj_arr)) {
                                        $leads = LeadGen($platform_data['actions'], $obj_arr[$campaign_data['objective']]);
                                    }
                                }
                                
                                // Add to Meta totals (combining Facebook and Instagram)
                                $meta_spend += $platform_data['spend'];
                                $meta_leads += $leads;
                            }
                        }
                    } else {
                        // Fallback: Process data without breakdown (original logic)
                        foreach($fb_data as $data) {
                            foreach($data as $campaign) {
                                // Apply filtering based on project names and name contains
                                $campaign_name = $campaign['campaign_name'];
                                $should_include = true;
                                
                                // Check if campaign should be included based on filtering
                                if(!empty($proj_names) || !empty($name_contain)) {
                                    $should_include = false;
                                    
                                    // Check project names
                                    if(!empty($proj_names)) {
                                        foreach($proj_names as $proj_name) {
                                            if(stripos($campaign_name, $proj_name) !== false) {
                                                $should_include = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Check name contains
                                    if(!empty($name_contain) && !$should_include) {
                                        $proj_key = contains_proj($campaign_name);
                                        if($proj_key !== '') {
                                            $should_include = true;
                                        }
                                    }
                                }
                                
                                if($should_include) {
                                    $meta_spend += $campaign['spend'];
                                    
                                    if(isset($campaign['actions'])) {
                                        if(array_key_exists($campaign['objective'], $obj_arr)) {
                                            $leads = LeadGen($campaign['actions'], $obj_arr[$campaign['objective']]);
                                            $meta_leads += $leads;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Store data for this date range
                    $reportData[$clientId]['meta_spend_'.$rangeKey] = $meta_spend;
                    $reportData[$clientId]['meta_leads_'.$rangeKey] = $meta_leads;
                }
            }
            
            // Get Google data for all 3 date ranges
            if(!empty($g_acc_ids)) {
                $dateRanges = [
                    'd1' => ['start' => $dtRange1_1, 'end' => $dtRange1_2],
                    'd2' => ['start' => $dtRange2_1, 'end' => $dtRange2_2],
                    'd3' => ['start' => $dtRange3_1, 'end' => $dtRange3_2]
                ];
                
                foreach($dateRanges as $rangeKey => $range) {
                    $google_spend = 0;
                    $google_leads = 0;
                    
                    foreach($g_acc_ids as $g_id) {
                        $adAccounts = [$g_id];
                        $g_data = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $range['start'], $range['end'], $extQry='');
                        
                        foreach($g_data as $campaign) {
                            // Apply filtering for Google campaigns too
                            $campaign_name = $campaign['camp_name'];
                            $should_include = true;
                            
                            // Check if campaign should be included based on filtering
                            if(!empty($proj_names) || !empty($name_contain)) {
                                $should_include = false;
                                
                                // Check project names
                                if(!empty($proj_names)) {
                                    foreach($proj_names as $proj_name) {
                                        if(stripos($campaign_name, $proj_name) !== false) {
                                            $should_include = true;
                                            break;
                                        }
                                    }
                                }
                                
                                // Check name contains
                                if(!empty($name_contain) && !$should_include) {
                                    $proj_key = contains_proj($campaign_name);
                                    if($proj_key !== '') {
                                        $should_include = true;
                                    }
                                }
                            }
                            
                            if($should_include) {
                                $google_spend += $campaign['cost'];
                                $google_leads += $campaign['conv'];
                            }
                        }
                    }
                    
                    // Store data for this date range
                    $reportData[$clientId]['google_spend_'.$rangeKey] = $google_spend;
                    $reportData[$clientId]['google_leads_'.$rangeKey] = $google_leads;
                }
            }
        }
    }
}

// Labels for thead date range groups (dynamic based on selection)
$computedD1Start = date('m/d/Y', strtotime('last monday'));
$computedD1End   = date('m/d/Y', strtotime('last friday'));
$computedD2Start = date('m/d/Y', strtotime('last saturday'));
$computedD2End   = date('m/d/Y', strtotime('last sunday'));
$computedD3Start = date('m/d/Y');
$computedD3End   = date('m/d/Y');

function buildRangeLabel($startMdY, $endMdY, $expectedStartMdY, $expectedEndMdY, $expectedLabel) {
    if ($startMdY === $expectedStartMdY && $endMdY === $expectedEndMdY) {
        return $expectedLabel;
    }
    return date('d-m-Y', strtotime($startMdY)).' to '.date('d-m-Y', strtotime($endMdY));
}

$labelD1 = buildRangeLabel(
    isset($_SESSION['stDt1']) ? $_SESSION['stDt1'] : $computedD1Start,
    isset($_SESSION['enDt1']) ? $_SESSION['enDt1'] : $computedD1End,
    $computedD1Start,
    $computedD1End,
    'Last Weekdays'
);

$labelD2 = buildRangeLabel(
    isset($_SESSION['stDt2']) ? $_SESSION['stDt2'] : $computedD2Start,
    isset($_SESSION['enDt2']) ? $_SESSION['enDt2'] : $computedD2End,
    $computedD2Start,
    $computedD2End,
    'Last Weekend'
);

$labelD3 = buildRangeLabel(
    isset($_SESSION['stDt3']) ? $_SESSION['stDt3'] : $computedD3Start,
    isset($_SESSION['enDt3']) ? $_SESSION['enDt3'] : $computedD3End,
    $computedD3Start,
    $computedD3End,
    'Today'
);

function moneyFormatIndia($num) {
    // Compact number formatting for this page
    // - If value is 0, show '-'
    // - If |value| >= 1000, show in thousands with one decimal, e.g., 1814 -> 1.8K
    // - Otherwise show with standard grouping
    if(!is_numeric($num)) { return '-'; }
    $n = floatval($num);
    if($n == 0) { return '-'; }
    $abs = abs($n);
    if($abs >= 1000) {
        $val = round($n / 1000, 1);
        // Remove trailing .0
        $valStr = (floor($val) == $val) ? (string)intval($val) : (string)$val;
        return $valStr.'K';
    }
    return number_format($n, 0);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AdRescue - Multi-Client Ads Report (3 Date Ranges)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="/casa/css/style.css" rel="stylesheet">
    <link href="/casa/style.css" rel="stylesheet">
    <link href="/css/table.css" rel="stylesheet">
    <!-- Bootstrap-select CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    
    <!-- jQuery first -->
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    
    <!-- Moment.js -->
    <script src="/vendors/moment/min/moment.min.js"></script>
    
    <!-- Date Range Picker -->
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    
    <!-- DataTables -->
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    
    <!-- Bootstrap-select JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    
     <style>
        .container, body { width: 100%; }
        .form-group { margin-bottom: 20px; }
        .table th, .table td { text-align: center; vertical-align: middle; }
        .table th { background-color: #f8f9fa; }
        .total-row { background-color: #bedbff; font-weight: bold; }
        .client-select { max-height: 200px; overflow-y: auto; }
        .date-range-container { margin: 20px 0; }
        .report-container { margin-top: 30px; }
        .btn-generate { margin-top: 20px; }
        
        /* Change all green text to black */
        label strong, .form-group label strong { color: #000 !important; }
        .card-header h5 { color: #000 !important; }
        h3 { color: #000 !important; }
        
        /* Loading overlay styles */
      .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    display: none; /* hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex; /* flex centering when active */
}

.loading-content {
    background: white;
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

/* Date range styling */
.date-range-group {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f9f9f9;
}

.date-range-group h6 {
    margin-bottom: 10px;
    color: #333;
    font-weight: bold;
}

/* Client column */
.client-col {
  background-color: #f5f5f5 !important; /* light gray */
  font-weight: 600;
}

/* Meta columns */
.meta-col {
  background-color: #e6f2ff !important; /* light blue */
}

/* Google columns */
.google-col {
  background-color: #f0fff4 !important; /* light green */
}

/* Total columns */
.total-col {
  background-color: #fff7e6 !important; /* light orange */
  font-weight: 600;
}

/* Grand Total row highlight */
.total-row td {
  background-color: #ffd9b3 !important; /* darker orange */
  font-weight: 700;
}

/* D1, D2, D3 column styling */
.d1-col { background-color: #e8f5e8 !important; }
.d2-col { background-color: #fff2e8 !important; }
.d3-col { background-color: #e8f0ff !important; }
 .bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) { width: 100%; }       
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h4>Loading...</h4>
            <p>Fetching data from Meta and Google APIs...</p>
            <p><small>This may take a few moments depending on the number of clients selected.</small></p>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                
                
                <!-- Form Section -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row align-items-end">
                                <!-- Client Selection -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select class="selectpicker" multiple data-live-search="true" name="clients[]" title="Select clients...">
                                            <?php
                                            $clientsQuery = mysqli_query($conn, "SELECT tbl_id, client_name FROM dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ORDER BY client_name");
                                            while($client = mysqli_fetch_assoc($clientsQuery)) {
                                                $selected = '';
                                                if(isset($_SESSION['selected_clients']) && in_array($client['tbl_id'], $_SESSION['selected_clients'])) {
                                                    $selected = 'selected';
                                                } elseif(isset($_POST['clients']) && in_array($client['tbl_id'], $_POST['clients'])) {
                                                    $selected = 'selected';
                                                }
                                                echo '<option value="'.$client['tbl_id'].'" '.$selected.'>'.$client['client_name'].'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <button type="submit" name="submit_report" class="btn btn-primary btn-block">
                                            <i class="fa fa-search"></i> Fetch Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date Ranges Section -->
                            <div class="row">
                                <!-- Date Range 1 (D1) -->
                                <div class="col-md-4">
                                    <div class="date-range-group">
                                        <h6>D1 - Last Weekdays (Mon-Fri)</h6>
                                        <div id="reportrange1" style="background: #fff; cursor: pointer; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span id="dateRangeText1"><?php echo $_SESSION['stDt1'].' - '.$_SESSION['enDt1']; ?></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                        <input type="hidden" name="start_date1" id="start_date1" value="<?php echo $_SESSION['stDt1']; ?>">
                                        <input type="hidden" name="end_date1" id="end_date1" value="<?php echo $_SESSION['enDt1']; ?>">
                                    </div>
                                </div>
                                
                                <!-- Date Range 2 (D2) -->
                                <div class="col-md-4">
                                    <div class="date-range-group">
                                        <h6>D2 - Last Weekend (Sat-Sun)</h6>
                                        <div id="reportrange2" style="background: #fff; cursor: pointer; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span id="dateRangeText2"><?php echo $_SESSION['stDt2'].' - '.$_SESSION['enDt2']; ?></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                        <input type="hidden" name="start_date2" id="start_date2" value="<?php echo $_SESSION['stDt2']; ?>">
                                        <input type="hidden" name="end_date2" id="end_date2" value="<?php echo $_SESSION['enDt2']; ?>">
                                    </div>
                                </div>
                                
                                <!-- Date Range 3 (D3) -->
                                <div class="col-md-4">
                                    <div class="date-range-group">
                                        <h6>D3 - Today</h6>
                                        <div id="reportrange3" style="background: #fff; cursor: pointer; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span id="dateRangeText3"><?php echo $_SESSION['stDt3'].' - '.$_SESSION['enDt3']; ?></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                        <input type="hidden" name="start_date3" id="start_date3" value="<?php echo $_SESSION['stDt3']; ?>">
                                        <input type="hidden" name="end_date3" id="end_date3" value="<?php echo $_SESSION['enDt3']; ?>">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Report Section -->
                <?php if($showReport && !empty($reportData)): ?>
                <div class="report-container">
                    <div class="card">
                        <div class="card-header" style="display:flex; justify-content: space-between; align-items: center;">
                           <small class="text-muted">Reporting time: <?php echo date("d-m-Y, h:i a"); ?></small>
                        </div>
                        <div class="card-body">
                            <div id="reportTable">
                                <table id="adsReport" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="vertical-align: middle;">
                                                <div style="font-weight: normal;">
                                                    <i class="fa fa-calendar" style="color:white; margin-right:8px;"></i>
                                                   
                                                </div>
                                            </th>
                                            <th colspan="9">Meta</th>
                                            <th colspan="9">Google</th>
                                            <th colspan="9">Total</th>
                                        </tr>
                                        <tr>
                                            <th>Client</th>
                                            <!-- Meta -->
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD1; ?></th>
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD2; ?></th>
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD3; ?></th>
                                            <!-- Google -->
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD1; ?></th>
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD2; ?></th>
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD3; ?></th>
                                            <!-- Total (Meta + Google) -->
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD1; ?></th>
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD2; ?></th>
                                            <th colspan="3" style="background-color: #007bff; color: white;"><?php echo $labelD3; ?></th>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <!-- Meta (3 x SLC) -->
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <!-- Google (3 x SLC) -->
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <!-- Total (D1,D2,D3) -->
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                            <th style="background-color: #007bff; color: white;">S</th>
                                            <th style="background-color: #007bff; color: white;">L</th>
                                            <th style="background-color: #007bff; color: white;">C</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $grand_meta_spend_d1 = $grand_meta_spend_d2 = $grand_meta_spend_d3 = $grand_meta_spend_total = 0;
                                        $grand_meta_leads_d1 = $grand_meta_leads_d2 = $grand_meta_leads_d3 = $grand_meta_leads_total = 0;
                                        $grand_google_spend_d1 = $grand_google_spend_d2 = $grand_google_spend_d3 = $grand_google_spend_total = 0;
                                        $grand_google_leads_d1 = $grand_google_leads_d2 = $grand_google_leads_d3 = $grand_google_leads_total = 0;
                                        // Combined grand totals (Meta + Google)
                                        $grand_total_spend_d1 = $grand_total_spend_d2 = $grand_total_spend_d3 = $grand_total_spend_all = 0;
                                        $grand_total_leads_d1 = $grand_total_leads_d2 = $grand_total_leads_d3 = $grand_total_leads_all = 0;

                                        foreach($reportData as $clientId => $data):
                                            $meta_spend_d1 = $data['meta_spend_d1'];
                                            $meta_spend_d2 = $data['meta_spend_d2'];
                                            $meta_spend_d3 = $data['meta_spend_d3'];
                                            $meta_leads_d1 = $data['meta_leads_d1'];
                                            $meta_leads_d2 = $data['meta_leads_d2'];
                                            $meta_leads_d3 = $data['meta_leads_d3'];
                                            
                                            $google_spend_d1 = $data['google_spend_d1'];
                                            $google_spend_d2 = $data['google_spend_d2'];
                                            $google_spend_d3 = $data['google_spend_d3'];
                                            $google_leads_d1 = $data['google_leads_d1'];
                                            $google_leads_d2 = $data['google_leads_d2'];
                                            $google_leads_d3 = $data['google_leads_d3'];

                                            // Calculate totals for each platform
                                            $meta_spend_total = $meta_spend_d1 + $meta_spend_d2 + $meta_spend_d3;
                                            $meta_leads_total = $meta_leads_d1 + $meta_leads_d2 + $meta_leads_d3;
                                            $google_spend_total = $google_spend_d1 + $google_spend_d2 + $google_spend_d3;
                                            $google_leads_total = $google_leads_d1 + $google_leads_d2 + $google_leads_d3;

                                            // Combined totals per date range and overall
                                            $total_spend_d1 = $meta_spend_d1 + $google_spend_d1;
                                            $total_spend_d2 = $meta_spend_d2 + $google_spend_d2;
                                            $total_spend_d3 = $meta_spend_d3 + $google_spend_d3;
                                            $total_leads_d1 = $meta_leads_d1 + $google_leads_d1;
                                            $total_leads_d2 = $meta_leads_d2 + $google_leads_d2;
                                            $total_leads_d3 = $meta_leads_d3 + $google_leads_d3;
                                            $total_spend_all = $meta_spend_total + $google_spend_total;
                                            $total_leads_all = $meta_leads_total + $google_leads_total;

                                            $meta_cpl_d1 = ($meta_leads_d1 > 0) ? round($meta_spend_d1 / $meta_leads_d1) : 0;
                                            $meta_cpl_d2 = ($meta_leads_d2 > 0) ? round($meta_spend_d2 / $meta_leads_d2) : 0;
                                            $meta_cpl_d3 = ($meta_leads_d3 > 0) ? round($meta_spend_d3 / $meta_leads_d3) : 0;
                                            $meta_cpl_total = ($meta_leads_total > 0) ? round($meta_spend_total / $meta_leads_total) : 0;
                                            
                                            $google_cpl_d1 = ($google_leads_d1 > 0) ? round($google_spend_d1 / $google_leads_d1) : 0;
                                            $google_cpl_d2 = ($google_leads_d2 > 0) ? round($google_spend_d2 / $google_leads_d2) : 0;
                                            $google_cpl_d3 = ($google_leads_d3 > 0) ? round($google_spend_d3 / $google_leads_d3) : 0;
                                            $google_cpl_total = ($google_leads_total > 0) ? round($google_spend_total / $google_leads_total) : 0;

                                            // Accumulate totals
                                            $grand_meta_spend_d1 += $meta_spend_d1;
                                            $grand_meta_spend_d2 += $meta_spend_d2;
                                            $grand_meta_spend_d3 += $meta_spend_d3;
                                            $grand_meta_spend_total += $meta_spend_total;
                                            $grand_meta_leads_d1 += $meta_leads_d1;
                                            $grand_meta_leads_d2 += $meta_leads_d2;
                                            $grand_meta_leads_d3 += $meta_leads_d3;
                                            $grand_meta_leads_total += $meta_leads_total;
                                            
                                            $grand_google_spend_d1 += $google_spend_d1;
                                            $grand_google_spend_d2 += $google_spend_d2;
                                            $grand_google_spend_d3 += $google_spend_d3;
                                            $grand_google_spend_total += $google_spend_total;
                                            $grand_google_leads_d1 += $google_leads_d1;
                                            $grand_google_leads_d2 += $google_leads_d2;
                                            $grand_google_leads_d3 += $google_leads_d3;
                                            $grand_google_leads_total += $google_leads_total;

                                            // Accumulate combined totals
                                            $grand_total_spend_d1 += $total_spend_d1;
                                            $grand_total_spend_d2 += $total_spend_d2;
                                            $grand_total_spend_d3 += $total_spend_d3;
                                            $grand_total_spend_all += $total_spend_all;
                                            $grand_total_leads_d1 += $total_leads_d1;
                                            $grand_total_leads_d2 += $total_leads_d2;
                                            $grand_total_leads_d3 += $total_leads_d3;
                                            $grand_total_leads_all += $total_leads_all;
                                        ?>
                                        <tr>
                                            <td class="client-col" style="text-align:center;">
                                                <b>
                                                <a href="#" class="client-info" data-toggle="popover" data-html="true" data-trigger="click" data-placement="auto" data-container="body" title="<?php echo htmlspecialchars($data['client_name'], ENT_QUOTES); ?>" data-content="<?php echo isset($data['tooltip_html']) ? $data['tooltip_html'] : ''; ?>"><?php echo $data['client_name']; ?></a>
                                                </b>
                                            </td>
                                            <!-- Meta D1 -->
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_d1); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_d1); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_d1); ?></td>
                                            <!-- Meta D2 -->
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_d2); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_d2); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_d2); ?></td>
                                            <!-- Meta D3 -->
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_d3); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_d3); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_d3); ?></td>
                                            <!-- Google D1 -->
                                            <td class="google-col"><?php echo moneyFormatIndia($google_spend_d1); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($google_leads_d1); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($google_cpl_d1); ?></td>
                                            <!-- Google D2 -->
                                            <td class="google-col"><?php echo moneyFormatIndia($google_spend_d2); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($google_leads_d2); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($google_cpl_d2); ?></td>
                                            <!-- Google D3 -->
                                            <td class="google-col"><?php echo moneyFormatIndia($google_spend_d3); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($google_leads_d3); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($google_cpl_d3); ?></td>
                                            
                                            <!-- Combined (Meta+Google) -->
                                            <td class="total-col"><?php echo moneyFormatIndia($total_spend_d1); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($total_leads_d1); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia(($total_leads_d1 > 0) ? round($total_spend_d1 / $total_leads_d1) : 0); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($total_spend_d2); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($total_leads_d2); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia(($total_leads_d2 > 0) ? round($total_spend_d2 / $total_leads_d2) : 0); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($total_spend_d3); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($total_leads_d3); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia(($total_leads_d3 > 0) ? round($total_spend_d3 / $total_leads_d3) : 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>

                                    <?php if($grand_meta_spend_d1>0 || $grand_meta_spend_d2>0 || $grand_meta_spend_d3>0 || $grand_google_spend_d1>0 || $grand_google_spend_d2>0 || $grand_google_spend_d3>0) { ?>
                                    <tfoot>
                                        <tr class="total-row">
                                            <td class="client-col" style="text-align:center;"><b>TOTAL</b></td>
                                            <!-- Meta D1 -->
                                            <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_spend_d1); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads_d1); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia(($grand_meta_leads_d1 > 0) ? round($grand_meta_spend_d1 / $grand_meta_leads_d1) : 0); ?></td>
                                            <!-- Meta D2 -->
                                            <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_spend_d2); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads_d2); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia(($grand_meta_leads_d2 > 0) ? round($grand_meta_spend_d2 / $grand_meta_leads_d2) : 0); ?></td>
                                            <!-- Meta D3 -->
                                            <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_spend_d3); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads_d3); ?></td>
                                            <td class="meta-col"><?php echo moneyFormatIndia(($grand_meta_leads_d3 > 0) ? round($grand_meta_spend_d3 / $grand_meta_leads_d3) : 0); ?></td>
                                            <!-- Google D1 -->
                                            <td class="google-col"><?php echo moneyFormatIndia($grand_google_spend_d1); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads_d1); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia(($grand_google_leads_d1 > 0) ? round($grand_google_spend_d1 / $grand_google_leads_d1) : 0); ?></td>
                                            <!-- Google D2 -->
                                            <td class="google-col"><?php echo moneyFormatIndia($grand_google_spend_d2); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads_d2); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia(($grand_google_leads_d2 > 0) ? round($grand_google_spend_d2 / $grand_google_leads_d2) : 0); ?></td>
                                            <!-- Google D3 -->
                                            <td class="google-col"><?php echo moneyFormatIndia($grand_google_spend_d3); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads_d3); ?></td>
                                            <td class="google-col"><?php echo moneyFormatIndia(($grand_google_leads_d3 > 0) ? round($grand_google_spend_d3 / $grand_google_leads_d3) : 0); ?></td>
                                            
                                            <!-- Combined Totals -->
                                            <td class="total-col"><?php echo moneyFormatIndia($grand_total_spend_d1); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($grand_total_leads_d1); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia(($grand_total_leads_d1 > 0) ? round($grand_total_spend_d1 / $grand_total_leads_d1) : 0); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($grand_total_spend_d2); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($grand_total_leads_d2); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia(($grand_total_leads_d2 > 0) ? round($grand_total_spend_d2 / $grand_total_leads_d2) : 0); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($grand_total_spend_d3); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia($grand_total_leads_d3); ?></td>
                                            <td class="total-col"><?php echo moneyFormatIndia(($grand_total_leads_d3 > 0) ? round($grand_total_spend_d3 / $grand_total_leads_d3) : 0); ?></td>
                                        </tr>
                                    </tfoot>
                                    <?php } ?>
                                </table>
                            </div>
                            
                            <div style="text-align: right; margin-top: 10px;">
                                <button id="copyTableImage" class="btn btn-info">
                                    <i class="fa fa-copy"></i> Copy Table as Image
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <br>
                                        
    <!-- HTML2Canvas for copying table as image -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    
    <script>
       $(document).ready(function () {
    console.log('Document ready');

    // On page reload after form submission, hide overlay quickly
    <?php if(isset($_POST['submit_report'])): ?>
    $('.loading-overlay').removeClass('active');
    <?php endif; ?>

    // Show overlay when form is submitted
    $('form').on('submit', function () {
        $('.loading-overlay').addClass('active');

        // Prevent submission if no clients selected
        var selectedClients = $('.selectpicker').val();
        if (!selectedClients || selectedClients.length === 0) {
            alert('Please select at least one client.');
            $('.loading-overlay').removeClass('active');
            return false;
        }
    });

    // Initialize Bootstrap-select safely
    try {
        $('.selectpicker').selectpicker({
            size: 8,
            liveSearch: true,
            actionsBox: true,
            selectAllText: 'Select All',
            deselectAllText: 'Deselect All'
        });
        console.log('Bootstrap-select initialized');
    } catch (e) {
        console.error('Error initializing Bootstrap-select:', e);
    }

    // Initialize date range pickers
    try {
        // Date Range 1 (D1) - Last weekdays
        var start1 = moment('<?php echo $_SESSION['stDt1']; ?>', 'MM/DD/YYYY');
        var end1 = moment('<?php echo $_SESSION['enDt1']; ?>', 'MM/DD/YYYY');

        $('#reportrange1').daterangepicker({
            startDate: start1,
            endDate: end1,
            ranges: {
                'Last Weekdays': [moment().subtract(1, 'week').startOf('week').add(1, 'day'), moment().subtract(1, 'week').endOf('week').subtract(2, 'day')],
                'This Weekdays': [moment().startOf('week').add(1, 'day'), moment().endOf('week').subtract(2, 'day')],
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()]
            },
            opens: 'left',
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function (start, end) {
            $('#dateRangeText1').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            $('#start_date1').val(start.format('MM/DD/YYYY'));
            $('#end_date1').val(end.format('MM/DD/YYYY'));
        });

        // Date Range 2 (D2) - Last weekend
        var start2 = moment('<?php echo $_SESSION['stDt2']; ?>', 'MM/DD/YYYY');
        var end2 = moment('<?php echo $_SESSION['enDt2']; ?>', 'MM/DD/YYYY');

        $('#reportrange2').daterangepicker({
            startDate: start2,
            endDate: end2,
            ranges: {
                'Last Weekend': [moment().subtract(1, 'week').startOf('week').add(6, 'day'), moment().subtract(1, 'week').endOf('week')],
                'This Weekend': [moment().startOf('week').add(6, 'day'), moment().endOf('week')],
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()]
            },
            opens: 'left',
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function (start, end) {
            $('#dateRangeText2').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            $('#start_date2').val(start.format('MM/DD/YYYY'));
            $('#end_date2').val(end.format('MM/DD/YYYY'));
        });

        // Date Range 3 (D3) - Today
        var start3 = moment('<?php echo $_SESSION['stDt3']; ?>', 'MM/DD/YYYY');
        var end3 = moment('<?php echo $_SESSION['enDt3']; ?>', 'MM/DD/YYYY');

        $('#reportrange3').daterangepicker({
            startDate: start3,
            endDate: end3,
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
            }
        }, function (start, end) {
            $('#dateRangeText3').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            $('#start_date3').val(start.format('MM/DD/YYYY'));
            $('#end_date3').val(end.format('MM/DD/YYYY'));
        });

        console.log('Date range pickers initialized');
    } catch (e) {
        console.error('Error initializing date range pickers:', e);
    }

    // Copy table as image
    $('#copyTableImage').click(function () {
        const table = document.getElementById('reportTable');
        html2canvas(table, { backgroundColor: null, scale: 8 }).then(function (canvas) {
            canvas.toBlob(function (blob) {
                if (navigator.clipboard && window.ClipboardItem) {
                    const item = new ClipboardItem({ 'image/png': blob });
                    navigator.clipboard.write([item]).then(function () {
                        alert('Table image copied to clipboard!');
                    }, function (err) {
                        alert('Failed to copy image: ' + err);
                    });
                } else {
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');
                }
            });
        });
    });

    // Initialize DataTables
    var dt = null;

    function initDataTable() {
        if (!dt) {
            try {
                dt = $('#adsReport').DataTable({
                    ordering: true,
                    lengthMenu: [25, 50, 100],
                    pageLength: 25,
                    scrollX: true,
                    autoWidth: false,
                    bLengthChange: false,
                    bSearch: false,
                    searching: false,
                    bInfo: false,
                    info: false,
                    bPaginate: false,
                    paging: false,
                    order: []
                });
            } catch (e) { console.error('Error initializing table:', e); }
        } else {
            dt.columns.adjust().draw(false);
        }
    }

    // Init table
    initDataTable();

    // Initialize popovers for client names
    function initClientPopovers() {
        try {
            $('.client-info').popover();
        } catch (e) { console.error('Error initializing popovers:', e); }
    }
    initClientPopovers();

    // Hide other popovers when one is shown, and close on outside click
    $(document).on('click', '.client-info', function(e) {
        e.preventDefault();
        $('.client-info').not(this).popover('hide');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.popover, .client-info').length) {
            $('.client-info').popover('hide');
        }
    });
});
    </script>
</body>
</html>
