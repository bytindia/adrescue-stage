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

// Set default date range if not set
if(!isset($_SESSION['stDt'])) {
    $start = date('m/d/Y',strtotime('first day of this month'));
    $end = date('m/d/Y');
    $_SESSION['stDt'] = $start;
    $_SESSION['enDt'] = $end;
}

// Handle form submission
$showReport = false;
$selectedClients = [];
$reportData = [];

if(isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedClients = isset($_POST['clients']) ? $_POST['clients'] : [];
    $stDt = $_POST['start_date'];
    $enDt = $_POST['end_date'];
    
    // Store selections in session for persistence
    $_SESSION['stDt'] = $stDt;
    $_SESSION['enDt'] = $enDt;
    $_SESSION['selected_clients'] = $selectedClients;
    
    // Convert date format for API calls
    $dtRange1 = date("Y-m-d", strtotime($stDt));
    $dtRange2 = date("Y-m-d", strtotime($enDt));
    $dtRange1_compact = date("Ymd", strtotime($stDt));
    $dtRange2_compact = date("Ymd", strtotime($enDt));

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
       // echo "SELECT da.tbl_id, da.client_name, da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain, br.total_budget FROM dashboard_accounts da LEFT JOIN budget_reminder br ON da.bud_id = br.tbl_id WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $clientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0"; 
        $cirRes = mysqli_query($conn, "SELECT da.tbl_id, da.client_name, da.nic_name,da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain, br.total_budget FROM dashboard_accounts da LEFT JOIN budget_reminder br ON da.bud_id = br.tbl_id WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $clientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0");
        $row = mysqli_fetch_assoc($cirRes);
        
        if($row) {
            $reportData[$clientId] = [
                'client_name' => $row['client_name'],
                'meta_spend' => 0,
                'meta_leads' => 0,
                'instagram_spend' => 0,
                'instagram_leads' => 0,
                'google_spend' => 0,
                'google_leads' => 0,
                'tooltip_html' => ''
            ];
            //d($row); exit;
            // Get account IDs
            $fb_acc_ids = array_filter(explode(',', $row['fb_id']));
            $g_acc_ids = array_filter(explode(',', $row['g_id']));
            $ta_acc_ids = array_filter(explode(',', $row['ta_id']));
            $in_acc_ids = array_filter(explode(',', $row['in_id']));

            // Build tooltip HTML with account names and links including selected date range
            $tooltipParts = [];
            if(!empty($fb_acc_ids)) {
                $part = '<div style="margin-bottom:6px;"><strong>Meta</strong><ul style="padding-left:16px; margin:4px 0;">';
                foreach($fb_acc_ids as $acc) {
                    $acc = trim($acc);
                    $accName = isset($fbAccN[$acc]) ? $fbAccN[$acc] : ('act_'.$acc);
                    $link = 'https://business.facebook.com/adsmanager/manage/campaigns?act='.$acc.'&date[since]='.$dtRange1.'&date[until]='.$dtRange2;
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
                    $link = 'https://ads.google.com/aw/campaigns?ocid='.$acc.'&__c='.$acc.'&__o=c&r=custom&start='.$dtRange1_compact.'&end='.$dtRange2_compact;
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
            
            // Debug: Log filtering arrays
            // error_log("Client: " . $row['client_name'] . " - Project names: " . print_r($proj_names, true));
            // error_log("Client: " . $row['client_name'] . " - Name contains: " . print_r($name_contain, true));
            
            // Get Meta data
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
                
                $fb_data = [];
                foreach($fb_acc_ids as $fb_id) {
                     $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&breakdowns=publisher_platform&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$dtRange1."&time_range[until]=".$dtRange2."&access_token=".$access_token."&limit=1750";
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
                         $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$dtRange1."&time_range[until]=".$dtRange2."&access_token=".$access_token."&limit=1750";
                        $req = file_get_contents_curl($url);
                        $res = json_decode($req, true);  
                        if(isset($res['data'])){
                            $fb_data[] = $res['data'];
                        }
                    }
                }
                
                // Process Meta data with filtering and split by publisher_platform
                $meta_spend = 0;
                $meta_leads = 0;
                $instagram_spend = 0;
                $instagram_leads = 0;
                
                
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
                                        // error_log("Campaign included by project name: $proj_name");
                                        break;
                                    }
                                }
                            }
                            
                            // Check name contains
                            if(!empty($name_contain) && !$should_include) {
                                $proj_key = contains_proj($campaign_name);
                                if($proj_key !== '') {
                                    $should_include = true;
                                    // error_log("Campaign included by name contains: $proj_key");
                                }
                            }
                            
                            if(!$should_include) {
                                // Campaign filtered out
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
                            
                            
                            // Debug: Log grouping
                            // error_log("Grouping - Campaign: $campaign_id, Platform: $publisher_platform, Spend: " . $campaign['spend']);
                            
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
                                if(isset($platform_data['actions'])) {   $leads += LeadGen($platform_data['actions'], 'onsite_conversion.messaging_conversation_started_7d'); } 
                            }
                            
                            
                            // Split data based on publisher_platform
                            if($platform === 'instagram') {
                                $instagram_spend += $platform_data['spend'];
                                $instagram_leads += $leads;
                            } else {
                                // Default to Facebook for any other platform or missing platform
                                $meta_spend += $platform_data['spend'];
                                $meta_leads += $leads;
                            }
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
                                    $meta_leads += LeadGen($campaign['actions'], 'onsite_conversion.messaging_conversation_started_7d');
                                }
                            }
                        }
                    }
                }
                
                
                $reportData[$clientId]['meta_spend'] = $meta_spend;
                $reportData[$clientId]['meta_leads'] = $meta_leads;
                $reportData[$clientId]['instagram_spend'] = $instagram_spend;
                $reportData[$clientId]['instagram_leads'] = $instagram_leads;
            }
            
            // Get Google data
            if(!empty($g_acc_ids)) {
                $google_spend = 0;
                $google_leads = 0;
                
                foreach($g_acc_ids as $g_id) {
                    $adAccounts = [$g_id];
                    $g_data = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $dtRange1, $dtRange2, $extQry='');
                    //d($g_data); exit;
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
                
                $reportData[$clientId]['google_spend'] = $google_spend;
                $reportData[$clientId]['google_leads'] = $google_leads;
            }
        }
    }
}

function moneyFormatIndia($num) {
    $num = round($num);
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3);
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits;
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].",";
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    if($thecash==0) { $thecash='-'; }
    return $thecash;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AdRescue - Multi-Client Ads Report</title>
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

     .bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) { width: 100%; }   
    </style>
    <style>
        /* Force hide T and Y columns by default */
        .col-t,
        .col-y { 
            display: none !important; 
        }
        
        /* Force show M columns */
        .col-m {
            display: table-cell !important;
        }
        
        /* When T/Y columns are shown */
        .col-t.show,
        .col-y.show {
            display: table-cell !important;
        }
        
        /* Override any conflicting styles */
        table th.col-t,
        table th.col-y,
        table td.col-t,
        table td.col-y {
            display: none !important;
        }
        
        table th.col-m,
        table td.col-m {
            display: table-cell !important;
        }
        
        /* When T/Y columns are shown, override the hidden state */
        table th.col-t.show,
        table th.col-y.show,
        table td.col-t.show,
        table td.col-y.show {
            display: table-cell !important;
        }
    </style>
    <style>
/* Client column */
.client-col {
  background-color: #f5f5f5 !important; /* light gray */
  font-weight: 600;
}

/* Meta columns */
.meta-col {
  background-color: #e6f2ff !important; /* light blue */
}

/* Instagram columns */
.insta-col {
  background-color: #fff0f6 !important; /* light pink */
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
                                
                                <!-- Date Range -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        
                                        <div id="reportrange" style="background: #fff; cursor: pointer; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span id="dateRangeText"><?php echo $_SESSION['stDt'].' - '.$_SESSION['enDt']; ?></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                        <input type="hidden" name="start_date" id="start_date" value="<?php echo $_SESSION['stDt']; ?>">
                                        <input type="hidden" name="end_date" id="end_date" value="<?php echo $_SESSION['enDt']; ?>">
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
                        </form>
                    </div>
                </div>
                
                <!-- Report Section -->
                <?php if($showReport && !empty($reportData)): ?>
                <div class="report-container">
                    <div class="card">
                        <div class="card-header" style="display:flex; justify-content: space-between; align-items: center;">
                           <small class="text-muted">Reporting time: <?php echo date("d-m-Y, h:i a"); ?></small>
                           <div style="display:flex; gap: 15px;">
                               <a href="#" id="toggleGST" class="btn btn-link btn-sm">Incl. GST</a>
                               <a href="#" id="toggleInsta" class="btn btn-link btn-sm">Show Insta.</a>
                           </div>
                        </div>
                        <div class="card-body">
                            <div id="reportTable">
                                <!-- Combined view: Meta(+Insta), Google, Total -->
                                <div id="combinedTableContainer">
                                <table id="adsReportCombined" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th style="vertical-align: middle;">
        <div style="font-weight: normal;">
          <i class="fa fa-calendar" style="color:white; margin-right:8px;"></i>
          <?php echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?>
        </div>
      </th>
      <th colspan="3">Meta</th>
      <th colspan="3">Google</th>
      <th colspan="3">Total</th>
    </tr>
    <tr>
      <th>Client</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $grand_meta_spend_c   = 0;
    $grand_meta_leads_c   = 0;
    $grand_google_spend_c = 0;
    $grand_google_leads_c = 0;

    foreach($reportData as $clientId => $data):
        $meta_spend_c   = $data['meta_spend'] + $data['instagram_spend'];
        $meta_leads_c   = $data['meta_leads'] + $data['instagram_leads'];
        $google_spend_c = $data['google_spend'];
        $google_leads_c = $data['google_leads'];

        $total_spend_c = $meta_spend_c + $google_spend_c;
        $total_leads_c = $meta_leads_c + $google_leads_c;

        $meta_cpl_c    = ($meta_leads_c > 0) ? round($meta_spend_c / $meta_leads_c) : 0;
        $google_cpl_c  = ($google_leads_c > 0) ? round($google_spend_c / $google_leads_c) : 0;
        $total_cpl_c   = ($total_leads_c > 0) ? round($total_spend_c / $total_leads_c) : 0;

        // Calculate with GST (18%)
        $meta_spend_c_gst   = $meta_spend_c * 1.18;
        $google_spend_c_gst = $google_spend_c * 1.18;
        $total_spend_c_gst  = $total_spend_c * 1.18;
        
        $meta_cpl_c_gst    = $meta_cpl_c * 1.18;
        $google_cpl_c_gst  = $google_cpl_c * 1.18;
        $total_cpl_c_gst   = $total_cpl_c * 1.18;

        // Accumulate totals
        $grand_meta_spend_c   += $meta_spend_c;
        $grand_meta_leads_c   += $meta_leads_c;
        $grand_google_spend_c += $google_spend_c;
        $grand_google_leads_c += $google_leads_c;
    ?>
    <tr>
      <td class="client-col" style="text-align:center;">
        <b>
        <a href="#" class="client-info" data-toggle="popover" data-html="true" data-trigger="click" data-placement="auto" data-container="body" title="<?php echo htmlspecialchars($data['client_name'], ENT_QUOTES); ?>" data-content="<?php echo isset($data['tooltip_html']) ? $data['tooltip_html'] : ''; ?>"><?php echo $data['client_name']; ?></a>
        </b>
      </td>
      <td class="meta-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($meta_spend_c); ?>" data-gst="<?php echo moneyFormatIndia(round($meta_spend_c_gst)); ?>"><?php echo moneyFormatIndia($meta_spend_c); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_c); ?></td>
      <td class="meta-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($meta_cpl_c); ?>" data-gst="<?php echo moneyFormatIndia(round($meta_cpl_c_gst)); ?>"><?php echo moneyFormatIndia($meta_cpl_c); ?></td>
      <td class="google-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($google_spend_c); ?>" data-gst="<?php echo moneyFormatIndia(round($google_spend_c_gst)); ?>"><?php echo moneyFormatIndia($google_spend_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads_c); ?></td>
      <td class="google-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($google_cpl_c); ?>" data-gst="<?php echo moneyFormatIndia(round($google_cpl_c_gst)); ?>"><?php echo moneyFormatIndia($google_cpl_c); ?></td>
      <td class="total-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($total_spend_c); ?>" data-gst="<?php echo moneyFormatIndia(round($total_spend_c_gst)); ?>"><?php echo moneyFormatIndia($total_spend_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads_c); ?></td>
      <td class="total-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($total_cpl_c); ?>" data-gst="<?php echo moneyFormatIndia(round($total_cpl_c_gst)); ?>"><?php echo moneyFormatIndia($total_cpl_c); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>

  <?php 
  if($grand_meta_spend_c>0 || $grand_google_spend_c>0) { 
    $grand_meta_cpl_c = ($grand_meta_leads_c > 0) ? round($grand_meta_spend_c / $grand_meta_leads_c) : 0;
    $grand_google_cpl_c = ($grand_google_leads_c > 0) ? round($grand_google_spend_c / $grand_google_leads_c) : 0;
    $grand_total_spend_c = $grand_meta_spend_c + $grand_google_spend_c;
    $grand_total_leads_c = $grand_meta_leads_c + $grand_google_leads_c;
    $grand_total_cpl_c = ($grand_total_leads_c > 0) ? round($grand_total_spend_c / $grand_total_leads_c) : 0;
    
    // Calculate with GST
    $grand_meta_spend_c_gst = $grand_meta_spend_c * 1.18;
    $grand_google_spend_c_gst = $grand_google_spend_c * 1.18;
    $grand_total_spend_c_gst = $grand_total_spend_c * 1.18;
    $grand_meta_cpl_c_gst = $grand_meta_cpl_c * 1.18;
    $grand_google_cpl_c_gst = $grand_google_cpl_c * 1.18;
    $grand_total_cpl_c_gst = $grand_total_cpl_c * 1.18;
  ?>
  <tfoot>
    <tr class="total-row">
      <td class="client-col" style="text-align:center;"><b>Total</b></td>
      <td class="meta-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_meta_spend_c); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_meta_spend_c_gst)); ?>"><?php echo moneyFormatIndia($grand_meta_spend_c); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads_c); ?></td>
      <td class="meta-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_meta_cpl_c); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_meta_cpl_c_gst)); ?>"><?php echo moneyFormatIndia($grand_meta_cpl_c); ?></td>
      <td class="google-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_google_spend_c); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_google_spend_c_gst)); ?>"><?php echo moneyFormatIndia($grand_google_spend_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads_c); ?></td>
      <td class="google-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_google_cpl_c); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_google_cpl_c_gst)); ?>"><?php echo moneyFormatIndia($grand_google_cpl_c); ?></td>
      <td class="total-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_total_spend_c); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_total_spend_c_gst)); ?>"><?php echo moneyFormatIndia($grand_total_spend_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($grand_total_leads_c); ?></td>
      <td class="total-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_total_cpl_c); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_total_cpl_c_gst)); ?>"><?php echo moneyFormatIndia($grand_total_cpl_c); ?></td>
    </tr>
  </tfoot>
  <?php } ?>
                                </table>
                                </div>

                                <!-- Split view: Meta, Instagram, Google, Total -->
                                <div id="splitTableContainer" style="display:none;">
                                <table id="adsReport" class="table table-bordered table-striped">
   <thead>
    <tr>
      <th style="vertical-align: middle;">
        <div style="font-weight: normal;">
          <i class="fa fa-calendar" style="color:white; margin-right:8px;"></i>
          <?php echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?>
        </div>
      </th>
      <th colspan="3">Meta</th>
      <th colspan="3">Instagram</th>
      <th colspan="3">Google</th>
      <th colspan="3">Total</th>
    </tr>
    <tr>
      <th>Client</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
      <th>Spends</th>
      <th>Leads</th>
      <th>CPL</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $grand_meta_spend = $grand_instagram_spend = $grand_google_spend = 0;
    $grand_meta_leads = $grand_instagram_leads = $grand_google_leads = 0;

    foreach($reportData as $clientId => $data):
        $meta_spend      = $data['meta_spend'];
        $meta_leads      = $data['meta_leads'];
        $instagram_spend = $data['instagram_spend'];
        $instagram_leads = $data['instagram_leads'];
        $google_spend    = $data['google_spend'];
        $google_leads    = $data['google_leads'];

        $total_spend = $meta_spend + $instagram_spend + $google_spend;
        $total_leads = $meta_leads + $instagram_leads + $google_leads;

        $meta_cpl      = ($meta_leads > 0) ? round($meta_spend / $meta_leads) : 0;
        $insta_cpl     = ($instagram_leads > 0) ? round($instagram_spend / $instagram_leads) : 0;
        $google_cpl    = ($google_leads > 0) ? round($google_spend / $google_leads) : 0;
        $total_cpl     = ($total_leads > 0) ? round($total_spend / $total_leads) : 0;

        // Calculate with GST (18%)
        $meta_spend_gst      = $meta_spend * 1.18;
        $instagram_spend_gst = $instagram_spend * 1.18;
        $google_spend_gst   = $google_spend * 1.18;
        $total_spend_gst    = $total_spend * 1.18;
        
        $meta_cpl_gst      = $meta_cpl * 1.18;
        $insta_cpl_gst     = $insta_cpl * 1.18;
        $google_cpl_gst    = $google_cpl * 1.18;
        $total_cpl_gst     = $total_cpl * 1.18;

        // Accumulate totals
        $grand_meta_spend      += $meta_spend;
        $grand_instagram_spend += $instagram_spend;
        $grand_google_spend    += $google_spend;
        $grand_meta_leads      += $meta_leads;
        $grand_instagram_leads += $instagram_leads;
        $grand_google_leads    += $google_leads;
    ?>
    <tr>
      <td class="client-col" style="text-align:center;">
        <b>
        <a href="#" class="client-info" data-toggle="popover" data-html="true" data-trigger="click" data-placement="auto" data-container="body" title="<?php echo htmlspecialchars($data['client_name'], ENT_QUOTES); ?>" data-content="<?php echo isset($data['tooltip_html']) ? $data['tooltip_html'] : ''; ?>"><?php echo $data['client_name']; ?></a>
        </b>
      </td>
      <td class="meta-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($meta_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($meta_spend_gst)); ?>"><?php echo moneyFormatIndia($meta_spend); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads); ?></td>
      <td class="meta-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($meta_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($meta_cpl_gst)); ?>"><?php echo moneyFormatIndia($meta_cpl); ?></td>
      <td class="insta-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($instagram_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($instagram_spend_gst)); ?>"><?php echo moneyFormatIndia($instagram_spend); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($instagram_leads); ?></td>
      <td class="insta-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($insta_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($insta_cpl_gst)); ?>"><?php echo moneyFormatIndia($insta_cpl); ?></td>
      <td class="google-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($google_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($google_spend_gst)); ?>"><?php echo moneyFormatIndia($google_spend); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads); ?></td>
      <td class="google-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($google_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($google_cpl_gst)); ?>"><?php echo moneyFormatIndia($google_cpl); ?></td>
      <td class="total-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($total_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($total_spend_gst)); ?>"><?php echo moneyFormatIndia($total_spend); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads); ?></td>
      <td class="total-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($total_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($total_cpl_gst)); ?>"><?php echo moneyFormatIndia($total_cpl); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>

  <?php 
  if($grand_meta_spend>0 || $grand_instagram_spend>0 || $grand_google_spend>0) { 
    $grand_meta_cpl = ($grand_meta_leads > 0) ? round($grand_meta_spend / $grand_meta_leads) : 0;
    $grand_instagram_cpl = ($grand_instagram_leads > 0) ? round($grand_instagram_spend / $grand_instagram_leads) : 0;
    $grand_google_cpl = ($grand_google_leads > 0) ? round($grand_google_spend / $grand_google_leads) : 0;
    $grand_total_spend = $grand_meta_spend + $grand_instagram_spend + $grand_google_spend;
    $grand_total_leads = $grand_meta_leads + $grand_instagram_leads + $grand_google_leads;
    $grand_total_cpl = ($grand_total_leads > 0) ? round($grand_total_spend / $grand_total_leads) : 0;
    
    // Calculate with GST
    $grand_meta_spend_gst = $grand_meta_spend * 1.18;
    $grand_instagram_spend_gst = $grand_instagram_spend * 1.18;
    $grand_google_spend_gst = $grand_google_spend * 1.18;
    $grand_total_spend_gst = $grand_total_spend * 1.18;
    $grand_meta_cpl_gst = $grand_meta_cpl * 1.18;
    $grand_instagram_cpl_gst = $grand_instagram_cpl * 1.18;
    $grand_google_cpl_gst = $grand_google_cpl * 1.18;
    $grand_total_cpl_gst = $grand_total_cpl * 1.18;
  ?>
  <tfoot>
    <tr class="total-row">
      <td class="client-col" style="text-align:center;"><b>Total</b></td>
      <td class="meta-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_meta_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_meta_spend_gst)); ?>"><?php echo moneyFormatIndia($grand_meta_spend); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads); ?></td>
      <td class="meta-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_meta_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_meta_cpl_gst)); ?>"><?php echo moneyFormatIndia($grand_meta_cpl); ?></td>
      <td class="insta-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_instagram_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_instagram_spend_gst)); ?>"><?php echo moneyFormatIndia($grand_instagram_spend); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($grand_instagram_leads); ?></td>
      <td class="insta-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_instagram_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_instagram_cpl_gst)); ?>"><?php echo moneyFormatIndia($grand_instagram_cpl); ?></td>
      <td class="google-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_google_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_google_spend_gst)); ?>"><?php echo moneyFormatIndia($grand_google_spend); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads); ?></td>
      <td class="google-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_google_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_google_cpl_gst)); ?>"><?php echo moneyFormatIndia($grand_google_cpl); ?></td>
      <td class="total-col spend-cell" data-no-gst="<?php echo moneyFormatIndia($grand_total_spend); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_total_spend_gst)); ?>"><?php echo moneyFormatIndia($grand_total_spend); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($grand_total_leads); ?></td>
      <td class="total-col cpl-cell" data-no-gst="<?php echo moneyFormatIndia($grand_total_cpl); ?>" data-gst="<?php echo moneyFormatIndia(round($grand_total_cpl_gst)); ?>"><?php echo moneyFormatIndia($grand_total_cpl); ?></td>
    </tr>
  </tfoot>
  <?php } ?>
</table>
                                </div>
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

    // Initialize date range picker
    try {
        var start = moment('<?php echo $_SESSION['stDt']; ?>', 'MM/DD/YYYY');
        var end = moment('<?php echo $_SESSION['enDt']; ?>', 'MM/DD/YYYY');

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
            }
        }, function (start, end) {
            $('#dateRangeText').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            $('#start_date').val(start.format('MM/DD/YYYY'));
            $('#end_date').val(end.format('MM/DD/YYYY'));
        });

        console.log('Date range picker initialized');
    } catch (e) {
        console.error('Error initializing date range picker:', e);
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

    // Initialize DataTables only for visible table to avoid width calc issues
    var dtCombined = null;
    var dtSplit = null;

    function initCombinedIfNeeded() {
        if (!dtCombined && $('#combinedTableContainer').is(':visible')) {
            try {
                var $table = $('#adsReportCombined');
                // Store original tfoot HTML BEFORE DataTables touches it
                var originalTfootHtml = $table.find('tfoot').length > 0 ? $table.find('tfoot')[0].outerHTML : null;
                
                // Remove tfoot temporarily to prevent DataTables from corrupting it
                var $tfoot = $table.find('tfoot').detach();
                
                dtCombined = $table.DataTable({
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
                    order: [],
                    drawCallback: function(settings) {
                        // Preserve tfoot on every draw
                        var $tableNode = $(this.api().table().node());
                        $tableNode.find('tfoot').remove();
                        if (originalTfootHtml) {
                            $tableNode.append($(originalTfootHtml));
                            // Update GST status text based on current state
                            var isGstEnabled = $('#reportTable').hasClass('gst-enabled');
                            var $tfoot = $tableNode.find('tfoot');
                            var $firstCell = $tfoot.find('tr.total-row td').first();
                            if ($firstCell.length > 0) {
                                if (isGstEnabled) {
                                    $firstCell.html('<b>Total (Incl. GST)</b>');
                                } else {
                                    $firstCell.html('<b>Total (Excl. GST)</b>');
                                }
                            }
                        }
                    }
                });
                
                // Immediately restore the original tfoot after DataTables init
                var $tableNode = $(dtCombined.table().node());
                $tableNode.find('tfoot').remove(); // Remove any tfoot DataTables might have created
                if (originalTfootHtml) {
                    $tableNode.append($(originalTfootHtml));
                    // Set default text (Excl. GST since gstEnabled starts as false)
                    var $tfoot = $tableNode.find('tfoot');
                    var $firstCell = $tfoot.find('tr.total-row td').first();
                    if ($firstCell.length > 0) {
                        $firstCell.html('<b>Total (Excl. GST)</b>');
                    }
                }
            } catch (e) { console.error('Error initializing Combined table:', e); }
        } else if (dtCombined) {
            dtCombined.columns.adjust().draw(false);
        }
    }

    function initSplitIfNeeded() {
        if (!dtSplit && $('#splitTableContainer').is(':visible')) {
            try {
                var $table = $('#adsReport');
                // Store original tfoot HTML BEFORE DataTables touches it
                var originalTfootHtml = $table.find('tfoot').length > 0 ? $table.find('tfoot')[0].outerHTML : null;
                
                // Remove tfoot temporarily to prevent DataTables from corrupting it
                var $tfoot = $table.find('tfoot').detach();
                
                dtSplit = $table.DataTable({
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
                    order: [],
                    drawCallback: function(settings) {
                        // Preserve tfoot on every draw
                        var $tableNode = $(this.api().table().node());
                        $tableNode.find('tfoot').remove();
                        if (originalTfootHtml) {
                            $tableNode.append($(originalTfootHtml));
                            // Update GST status text based on current state
                            var isGstEnabled = $('#reportTable').hasClass('gst-enabled');
                            var $tfoot = $tableNode.find('tfoot');
                            var $firstCell = $tfoot.find('tr.total-row td').first();
                            if ($firstCell.length > 0) {
                                if (isGstEnabled) {
                                    $firstCell.html('<b>Total (Incl. GST)</b>');
                                } else {
                                    $firstCell.html('<b>Total (Excl. GST)</b>');
                                }
                            }
                        }
                    }
                });
                
                // Immediately restore the original tfoot after DataTables init
                var $tableNode = $(dtSplit.table().node());
                $tableNode.find('tfoot').remove(); // Remove any tfoot DataTables might have created
                if (originalTfootHtml) {
                    $tableNode.append($(originalTfootHtml));
                    // Set default text (Excl. GST since gstEnabled starts as false)
                    var $tfoot = $tableNode.find('tfoot');
                    var $firstCell = $tfoot.find('tr.total-row td').first();
                    if ($firstCell.length > 0) {
                        $firstCell.html('<b>Total (Excl. GST)</b>');
                    }
                }
            } catch (e) { console.error('Error initializing Split table:', e); }
        } else if (dtSplit) {
            dtSplit.columns.adjust().draw(false);
        }
    }

    // Store original tfoot HTMLs before DataTables initialization
    var originalTfootCombined = $('#adsReportCombined tfoot').length > 0 ? $('#adsReportCombined tfoot')[0].outerHTML : null;
    var originalTfootSplit = $('#adsReport tfoot').length > 0 ? $('#adsReport tfoot')[0].outerHTML : null;
    
    // Init default visible table
    initCombinedIfNeeded();

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

    // Toggle GST view
    var gstEnabled = false;
    $('#toggleGST').on('click', function (e) {
        e.preventDefault();
        gstEnabled = !gstEnabled;
        
        // Only toggle cells in the visible table container
        var $visibleContainer = $('#combinedTableContainer:visible, #splitTableContainer:visible');
        var $table = $visibleContainer.find('table');
        
        // Get the correct original tfoot HTML based on which table is visible
        var tableId = $table.attr('id');
        var originalTfootHtml = null;
        if (tableId === 'adsReportCombined') {
            originalTfootHtml = originalTfootCombined;
        } else if (tableId === 'adsReport') {
            originalTfootHtml = originalTfootSplit;
        }
        
        // Remove any duplicate tfoot elements first
        var $tfoots = $table.find('tfoot');
        if ($tfoots.length > 1) {
            $tfoots.slice(1).remove();
        }
        
        // Check if tfoot is missing or corrupted - restore if needed
        var $tfoot = $table.find('tfoot');
        var needsRestore = false;
        
        if ($tfoot.length === 0) {
            needsRestore = true;
        } else {
            var $totalRow = $tfoot.find('tr.total-row');
            var expectedCells = $table.find('thead tr:last-child th').length;
            var actualCells = $totalRow.find('td').length;
            var firstCellText = $totalRow.find('td').first().text().toUpperCase();
            var hasTotalText = firstCellText.indexOf('TOTAL') !== -1;
            
            if ($totalRow.length === 0 || actualCells !== expectedCells || !hasTotalText) {
                needsRestore = true;
            }
        }
        
        // Restore tfoot if corrupted
        if (needsRestore && originalTfootHtml) {
            $table.find('tfoot').remove();
            $table.append($(originalTfootHtml));
            $tfoot = $table.find('tfoot');
            // Set GST status text after restoration
            var $firstCell = $tfoot.find('tr.total-row td').first();
            if ($firstCell.length > 0) {
                if (gstEnabled) {
                    $firstCell.html('<b>Total (Incl. GST)</b>');
                } else {
                    $firstCell.html('<b>Total (Excl. GST)</b>');
                }
            }
        }
        
        // Update cell values in tbody
        $visibleContainer.find('tbody .spend-cell, tbody .cpl-cell').each(function() {
            var $cell = $(this);
            var gstValue = $cell.attr('data-gst');
            var noGstValue = $cell.attr('data-no-gst');
            if (gstValue && noGstValue) {
                $cell.text(gstEnabled ? gstValue : noGstValue);
            }
        });
        
        // Update cell values in tfoot
        if ($tfoot.length > 0) {
            // Update the first cell (TOTAL text) to show GST status
            var $firstCell = $tfoot.find('tr.total-row td').first();
            if ($firstCell.length > 0) {
                if (gstEnabled) {
                    $firstCell.html('<b>Total (Incl. GST)</b>');
                } else {
                    $firstCell.html('<b>Total (Excl. GST)</b>');
                }
            }
            
            // Update spend and CPL cell values
            $tfoot.find('.spend-cell, .cpl-cell').each(function() {
                var $cell = $(this);
                var gstValue = $cell.attr('data-gst');
                var noGstValue = $cell.attr('data-no-gst');
                if (gstValue && noGstValue) {
                    $cell.text(gstEnabled ? gstValue : noGstValue);
                }
            });
        }
        
        // Update button text
        if (gstEnabled) {
            $(this).text('Excl. GST');
            $('#reportTable').addClass('gst-enabled');
        } else {
            $(this).text('Incl. GST');
            $('#reportTable').removeClass('gst-enabled');
        }
    });

    // Toggle Instagram split view
    $('#toggleInsta').on('click', function (e) {
        e.preventDefault();
        var splitVisible = $('#splitTableContainer').is(':visible');
        if (splitVisible) {
            // Switch to combined view
            $('#splitTableContainer').hide();
            $('#combinedTableContainer').show();
            $(this).text('Show Insta.');
            initCombinedIfNeeded();
            initClientPopovers();
        } else {
            // Switch to split view
            $('#combinedTableContainer').hide();
            $('#splitTableContainer').show();
            $(this).text('Hide Insta.');
            initSplitIfNeeded();
            initClientPopovers();
        }
        
        // Maintain GST state when switching views
        if (gstEnabled) {
            var $visibleContainer = $('#combinedTableContainer:visible, #splitTableContainer:visible');
            $visibleContainer.find('.spend-cell, .cpl-cell').each(function() {
                $(this).text($(this).data('gst'));
            });
        }
    });
});
    </script>
</body>
</html>
