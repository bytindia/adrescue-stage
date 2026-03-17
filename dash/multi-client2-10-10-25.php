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

    // Compute Today (T), Yesterday (Y), and Month-to-date (M) windows relative to end date
    $t_since = $dtRange2;      $t_until = $dtRange2;
    $y_since = date('Y-m-d', strtotime($dtRange2.' -1 day')); $y_until = $y_since;
    $m_since = date('Y-m-01', strtotime($dtRange2));          $m_until = $dtRange2;

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
        $cirRes = mysqli_query($conn, "SELECT da.tbl_id, da.client_name, da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain, br.total_budget FROM dashboard_accounts da LEFT JOIN budget_reminder br ON da.bud_id = br.tbl_id WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $clientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0");
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
                    'POST_ENGAGEMENT' => 'post_engagement', 
                    'LINK_CLICKS' => 'link_click',
                    'VIDEO_VIEWS' => 'video_view',
                    'LEAD_GENERATION' => 'lead', 
                    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
                    'MESSAGES' => 'onsite_conversion.messaging_block',
                    'OUTCOME_LEADS' => 'lead'
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

                // T/Y/M accumulators
                $meta_spend_t = 0; $meta_leads_t = 0; $insta_spend_t = 0; $insta_leads_t = 0;
                $meta_spend_y = 0; $meta_leads_y = 0; $insta_spend_y = 0; $insta_leads_y = 0;
                $meta_spend_m = 0; $meta_leads_m = 0; $insta_spend_m = 0; $insta_leads_m = 0;
                
                
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
                                }
                            }
                        }
                    }
                }
                
                
                $reportData[$clientId]['meta_spend'] = $meta_spend;
                $reportData[$clientId]['meta_leads'] = $meta_leads;
                $reportData[$clientId]['instagram_spend'] = $instagram_spend;
                $reportData[$clientId]['instagram_leads'] = $instagram_leads;

                // Fetch T/Y/M via lightweight calls per window
                $windows = [
                    't' => [$t_since, $t_until],
                    'y' => [$y_since, $y_until],
                    'm' => [$m_since, $m_until]
                ];
                foreach($windows as $winKey => $dates) {
                    list($sinceD, $untilD) = $dates;
                    $win_meta_spend = 0; $win_meta_leads = 0; $win_insta_spend = 0; $win_insta_leads = 0;
                    $grouped = [];
                    foreach($fb_acc_ids as $fb_id) {
                        $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&breakdowns=publisher_platform&fields=campaign_id,campaign_name,spend,objective,actions&time_range[since]=".$sinceD."&time_range[until]=".$untilD."&access_token=".$access_token."&limit=1750";
                        $req = file_get_contents_curl($url);
                        $res = json_decode($req, true);
                        if(isset($res['data'])) {
                            foreach($res['data'] as $campaign) {
                                $campaign_name = $campaign['campaign_name'];
                                $should_include = true;
                                if(!empty($proj_names) || !empty($name_contain)) {
                                    $should_include = false;
                                    if(!empty($proj_names)) {
                                        foreach($proj_names as $proj_name) { if(stripos($campaign_name, $proj_name) !== false) { $should_include = true; break; } }
                                    }
                                    if(!empty($name_contain) && !$should_include) { $proj_key = contains_proj($campaign_name); if($proj_key !== '') { $should_include = true; } }
                                }
                                if(!$should_include) continue;
                                $platform = isset($campaign['publisher_platform']) ? $campaign['publisher_platform'] : 'facebook';
                                $leads = 0;
                                if(isset($campaign['actions']) && array_key_exists($campaign['objective'], $obj_arr)) {
                                    $leads = LeadGen($campaign['actions'], $obj_arr[$campaign['objective']]);
                                }
                                if($platform === 'instagram') { $win_insta_spend += $campaign['spend']; $win_insta_leads += $leads; }
                                else { $win_meta_spend += $campaign['spend']; $win_meta_leads += $leads; }
                            }
                        }
                    }
                    if($winKey==='t'){ $meta_spend_t=$win_meta_spend; $meta_leads_t=$win_meta_leads; $insta_spend_t=$win_insta_spend; $insta_leads_t=$win_insta_leads; }
                    if($winKey==='y'){ $meta_spend_y=$win_meta_spend; $meta_leads_y=$win_meta_leads; $insta_spend_y=$win_insta_spend; $insta_leads_y=$win_insta_leads; }
                    if($winKey==='m'){ $meta_spend_m=$win_meta_spend; $meta_leads_m=$win_meta_leads; $insta_spend_m=$win_insta_spend; $insta_leads_m=$win_insta_leads; }
                }

                $reportData[$clientId]['meta_spend_t'] = $meta_spend_t;
                $reportData[$clientId]['meta_leads_t'] = $meta_leads_t;
                $reportData[$clientId]['meta_spend_y'] = $meta_spend_y;
                $reportData[$clientId]['meta_leads_y'] = $meta_leads_y;
                $reportData[$clientId]['meta_spend_m'] = $meta_spend_m;
                $reportData[$clientId]['meta_leads_m'] = $meta_leads_m;
                $reportData[$clientId]['instagram_spend_t'] = $insta_spend_t;
                $reportData[$clientId]['instagram_leads_t'] = $insta_leads_t;
                $reportData[$clientId]['instagram_spend_y'] = $insta_spend_y;
                $reportData[$clientId]['instagram_leads_y'] = $insta_leads_y;
                $reportData[$clientId]['instagram_spend_m'] = $insta_spend_m;
                $reportData[$clientId]['instagram_leads_m'] = $insta_leads_m;
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

                // Google T/Y/M
                $g_windows = [
                    't' => [$t_since, $t_until],
                    'y' => [$y_since, $y_until],
                    'm' => [$m_since, $m_until]
                ];
                foreach($g_windows as $winKey => $dates) {
                    list($sinceD, $untilD) = $dates;
                    $win_g_spend = 0; $win_g_leads = 0;
                    foreach($g_acc_ids as $g_id) {
                        $adAccounts = [$g_id];
                        $g_data_win = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $sinceD, $untilD, $extQry='');
                        foreach($g_data_win as $campaign) {
                            $campaign_name = $campaign['camp_name'];
                            $should_include = true;
                            if(!empty($proj_names) || !empty($name_contain)) {
                                $should_include = false;
                                if(!empty($proj_names)) { foreach($proj_names as $proj_name) { if(stripos($campaign_name, $proj_name) !== false) { $should_include = true; break; } } }
                                if(!empty($name_contain) && !$should_include) { $proj_key = contains_proj($campaign_name); if($proj_key !== '') { $should_include = true; } }
                            }
                            if(!$should_include) continue;
                            $win_g_spend += $campaign['cost'];
                            $win_g_leads += $campaign['conv'];
                        }
                    }
                    if($winKey==='t'){ $reportData[$clientId]['google_spend_t']=$win_g_spend; $reportData[$clientId]['google_leads_t']=$win_g_leads; }
                    if($winKey==='y'){ $reportData[$clientId]['google_spend_y']=$win_g_spend; $reportData[$clientId]['google_leads_y']=$win_g_leads; }
                    if($winKey==='m'){ $reportData[$clientId]['google_spend_m']=$win_g_spend; $reportData[$clientId]['google_leads_m']=$win_g_leads; }
                }
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
                        <div class="card-header" style="display:flex; justify-content: space-between; align-items: center; gap: 12px;">
                           <small class="text-muted">Reporting time: <?php echo date("d-m-Y, h:i a"); ?></small>
                           <div>
                             <a href="#" id="toggleYestMonth" class="btn btn-link btn-sm">Show Yesterday/This Month</a>
                             <a href="#" id="toggleInsta" class="btn btn-link btn-sm">Show Instagram report</a>
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
          <?php //echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?>
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
      <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_c); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_c); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_spend_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_cpl_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_spend_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_cpl_c); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>

  <?php if($grand_meta_spend_c>0 || $grand_google_spend_c>0) { ?>
  <tfoot>
    <tr class="total-row">
      <td class="client-col" style="text-align:center;"><b>TOTAL</b></td>
      <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_spend_c); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads_c); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia(($grand_meta_leads_c > 0) ? round($grand_meta_spend_c / $grand_meta_leads_c) : 0); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($grand_google_spend_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads_c); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia(($grand_google_leads_c > 0) ? round($grand_google_spend_c / $grand_google_leads_c) : 0); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($grand_meta_spend_c + $grand_google_spend_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($grand_meta_leads_c + $grand_google_leads_c); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia((($grand_meta_leads_c + $grand_google_leads_c) > 0) ? round(($grand_meta_spend_c + $grand_google_spend_c) / ($grand_meta_leads_c + $grand_google_leads_c)) : 0); ?></td>
    </tr>
  </tfoot>
  <?php } ?>
                                </table>
                                </div>

                                <!-- T/Y/M view: extra columns hidden by default -->
                                <div id="tymTableContainer" style="display:none; margin-top:16px;">
                                <table id="adsReportTYM" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th style="vertical-align: middle;">
        <div style="font-weight: normal;">
          <i class="fa fa-calendar" style="color:white; margin-right:8px;"></i>
          <?php //echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?>
        </div>
      </th>
      <th colspan="9">Meta</th>
      <th colspan="9">Google</th>
      <th colspan="9">Total</th>
    </tr>
    <tr>
      <th>Client</th>
      <th colspan="3">Spend</th>
      <th colspan="3">Leads</th>
      <th colspan="3">CPL</th>
      <th colspan="3">Spend</th>
      <th colspan="3">Leads</th>
      <th colspan="3">CPL</th>
      <th colspan="3">Spend</th>
      <th colspan="3">Leads</th>
      <th colspan="3">CPL</th>
    </tr>
    <tr>
      <th></th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
      <th>T</th><th>Y</th><th>M</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($reportData as $clientId => $data):
        $meta_spend_t = ($data['meta_spend_t'] ?? 0) + ($data['instagram_spend_t'] ?? 0);
        $meta_spend_y = ($data['meta_spend_y'] ?? 0) + ($data['instagram_spend_y'] ?? 0);
        $meta_spend_m = ($data['meta_spend_m'] ?? 0) + ($data['instagram_spend_m'] ?? 0);
        $meta_leads_t = ($data['meta_leads_t'] ?? 0) + ($data['instagram_leads_t'] ?? 0);
        $meta_leads_y = ($data['meta_leads_y'] ?? 0) + ($data['instagram_leads_y'] ?? 0);
        $meta_leads_m = ($data['meta_leads_m'] ?? 0) + ($data['instagram_leads_m'] ?? 0);
        $google_spend_t = $data['google_spend_t'] ?? 0;
        $google_spend_y = $data['google_spend_y'] ?? 0;
        $google_spend_m = $data['google_spend_m'] ?? 0;
        $google_leads_t = $data['google_leads_t'] ?? 0;
        $google_leads_y = $data['google_leads_y'] ?? 0;
        $google_leads_m = $data['google_leads_m'] ?? 0;
        $total_spend_t = $meta_spend_t + $google_spend_t;
        $total_spend_y = $meta_spend_y + $google_spend_y;
        $total_spend_m = $meta_spend_m + $google_spend_m;
        $total_leads_t = $meta_leads_t + $google_leads_t;
        $total_leads_y = $meta_leads_y + $google_leads_y;
        $total_leads_m = $meta_leads_m + $google_leads_m;
        $meta_cpl_t = ($meta_leads_t > 0) ? round($meta_spend_t / $meta_leads_t) : 0;
        $meta_cpl_y = ($meta_leads_y > 0) ? round($meta_spend_y / $meta_leads_y) : 0;
        $meta_cpl_m = ($meta_leads_m > 0) ? round($meta_spend_m / $meta_leads_m) : 0;
        $google_cpl_t = ($google_leads_t > 0) ? round($google_spend_t / $google_leads_t) : 0;
        $google_cpl_y = ($google_leads_y > 0) ? round($google_spend_y / $google_leads_y) : 0;
        $google_cpl_m = ($google_leads_m > 0) ? round($google_spend_m / $google_leads_m) : 0;
        $total_cpl_t = ($total_leads_t > 0) ? round($total_spend_t / $total_leads_t) : 0;
        $total_cpl_y = ($total_leads_y > 0) ? round($total_spend_y / $total_leads_y) : 0;
        $total_cpl_m = ($total_leads_m > 0) ? round($total_spend_m / $total_leads_m) : 0;
    ?>
    <tr>
      <td class="client-col" style="text-align:center;"><b><?php echo $data['client_name']; ?></b></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_t); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_y); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_spend_m); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_t); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_y); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads_m); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_t); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_y); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl_m); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_spend_t); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_spend_y); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_spend_m); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads_t); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads_y); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads_m); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_cpl_t); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_cpl_y); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_cpl_m); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_spend_t); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_spend_y); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_spend_m); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads_t); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads_y); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads_m); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_cpl_t); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_cpl_y); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_cpl_m); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
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
          <?php //echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?>
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
      <td class="meta-col"><?php echo moneyFormatIndia($meta_spend); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_leads); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($meta_cpl); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($instagram_spend); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($instagram_leads); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($insta_cpl); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_spend); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_leads); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($google_cpl); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_spend); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_leads); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($total_cpl); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>

  <?php if($grand_meta_spend>0 || $grand_instagram_spend>0 || $grand_google_spend>0) { ?>
  <tfoot>
    <tr class="total-row">
      <td class="client-col" style="text-align:center;"><b>TOTAL</b></td>
      <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_spend); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia($grand_meta_leads); ?></td>
      <td class="meta-col"><?php echo moneyFormatIndia(($grand_meta_leads > 0) ? round($grand_meta_spend / $grand_meta_leads) : 0); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($grand_instagram_spend); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia($grand_instagram_leads); ?></td>
      <td class="insta-col"><?php echo moneyFormatIndia(($grand_instagram_leads > 0) ? round($grand_instagram_spend / $grand_instagram_leads) : 0); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($grand_google_spend); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia($grand_google_leads); ?></td>
      <td class="google-col"><?php echo moneyFormatIndia(($grand_google_leads > 0) ? round($grand_google_spend / $grand_google_leads) : 0); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($grand_meta_spend + $grand_instagram_spend + $grand_google_spend); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia($grand_meta_leads + $grand_instagram_leads + $grand_google_leads); ?></td>
      <td class="total-col"><?php echo moneyFormatIndia((($grand_meta_leads + $grand_instagram_leads + $grand_google_leads) > 0) ? round(($grand_meta_spend + $grand_instagram_spend + $grand_google_spend) / ($grand_meta_leads + $grand_instagram_leads + $grand_google_leads)) : 0); ?></td>
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
    var dtTYM = null;

    function initCombinedIfNeeded() {
        if (!dtCombined && $('#combinedTableContainer').is(':visible')) {
            try {
                dtCombined = $('#adsReportCombined').DataTable({
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
            } catch (e) { console.error('Error initializing Combined table:', e); }
        } else if (dtCombined) {
            dtCombined.columns.adjust().draw(false);
        }
    }

    function initSplitIfNeeded() {
        if (!dtSplit && $('#splitTableContainer').is(':visible')) {
            try {
                dtSplit = $('#adsReport').DataTable({
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
            } catch (e) { console.error('Error initializing Split table:', e); }
        } else if (dtSplit) {
            dtSplit.columns.adjust().draw(false);
        }
    }

    function initTYMIfNeeded() {
        if (!dtTYM && $('#tymTableContainer').is(':visible')) {
            try {
                dtTYM = $('#adsReportTYM').DataTable({
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
            } catch (e) { console.error('Error initializing TYM table:', e); }
        } else if (dtTYM) {
            dtTYM.columns.adjust().draw(false);
        }
    }

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

    // Toggle Instagram split view
    $('#toggleInsta').on('click', function (e) {
        e.preventDefault();
        var splitVisible = $('#splitTableContainer').is(':visible');
        if (splitVisible) {
            // Switch to combined view
            $('#splitTableContainer').hide();
            $('#combinedTableContainer').show();
            $(this).text('Show Instagram report');
            initCombinedIfNeeded();
            initClientPopovers();
        } else {
            // Switch to split view
            $('#combinedTableContainer').hide();
            $('#splitTableContainer').show();
            $(this).text('Hide Instagram report');
            initSplitIfNeeded();
            initClientPopovers();
        }
    });

    // Toggle Yesterday/This Month view
    $('#toggleYestMonth').on('click', function (e) {
        e.preventDefault();
        var tymVisible = $('#tymTableContainer').is(':visible');
        if (tymVisible) {
            $('#tymTableContainer').hide();
            $(this).text('Show Yesterday/This Month');
        } else {
            $('#tymTableContainer').show();
            $(this).text('Hide Yesterday/This Month');
            initTYMIfNeeded();
        }
    });
});
    </script>
</body>
</html>
