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

// Helper function to clean phone number
function cleanPhone($raw) {
    $ph = trim(strtolower($raw));
    $ph = preg_replace("/^p:/", "", $ph);
    $ph = preg_replace("/[\s\-\(\)]/", "", $ph);
    // Remove any non-digit characters except +
    $ph = preg_replace('/\D/', '', $ph);
    return $ph;
}

// Handle lead stage update
if(isset($_POST['update_lead_stage'])) {
    $selected_leads = isset($_POST['selected_leads']) ? $_POST['selected_leads'] : [];
    $lead_stage = isset($_POST['lead_stage']) ? $_POST['lead_stage'] : '';
    $ad_account = isset($_POST['ad_account']) ? $_POST['ad_account'] : '';
    $pixel_id = isset($_POST['pixel_id']) ? $_POST['pixel_id'] : '';
    
    if(!empty($selected_leads) && !empty($lead_stage) && !empty($ad_account) && !empty($pixel_id)) {
        // Get access token from users table
        $tokenQuery = "SELECT access_token FROM users WHERE tbl_id='".$_SESSION['uid']."'";
        $tokenResult = mysqli_query($conn, $tokenQuery);
        $tokenRow = mysqli_fetch_assoc($tokenResult);
        $access_token = isset($tokenRow['access_token']) ? $tokenRow['access_token'] : '';
        
        if(empty($access_token)) {
            echo "<script>alert('Error: Access token not found. Please configure your Meta access token.');</script>";
            exit;
        }
        
        // Prepare events array for Meta CAPI
        $events = [];
        $updatedLeads = [];
        
        // Process each selected lead
        foreach($selected_leads as $leadDataJson) {
            $leadData = json_decode($leadDataJson, true);
            if($leadData && isset($leadData['leadgen_id'])) {
                $leadgen_id = mysqli_real_escape_string($conn, $leadData['leadgen_id']);
                $phone = isset($leadData['phone']) ? $leadData['phone'] : '';
                $email = isset($leadData['email']) ? $leadData['email'] : '';
                
                // Clean and hash phone number
                $cleanPh = cleanPhone($phone);
                $phHash = hash('sha256', $cleanPh);
                
                // Hash email
                $emHash = hash('sha256', strtolower(trim($email)));
                
                // Get CRM lead ID (if exists in database)
                $crmLeadQuery = "SELECT tbl_id FROM leads WHERE leadgen_id = '".$leadgen_id."' LIMIT 1";
                $crmLeadResult = mysqli_query($conn, $crmLeadQuery);
                $crmLeadId = '';
                if($crmLeadResult && mysqli_num_rows($crmLeadResult) > 0) {
                    $crmLeadRow = mysqli_fetch_assoc($crmLeadResult);
                    $crmLeadId = $crmLeadRow['tbl_id'];
                }
                
                // Prepare event for Meta CAPI
                $events[] = [
                    "event_name" => "LeadStageUpdate",
                    "event_time" => time(),
                    "event_id" => uniqid('leadstage_', true),
                    "user_data" => [
                        "em" => $emHash,
                        "ph" => $phHash
                    ],
                    "custom_data" => [
                        "lead_stage" => $lead_stage,
                        "leadgen_id" => $leadgen_id,
                        "crm_lead_id" => $crmLeadId
                    ],
                    "event_source_url" => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                    "action_source" => "crm"
                ];
                
                // Update lead stage in database
                //$updateQuery = "UPDATE leads SET lead_stage = '".mysqli_real_escape_string($conn, $lead_stage)."', ad_account = '".mysqli_real_escape_string($conn, $ad_account)."', pixel_id = '".mysqli_real_escape_string($conn, $pixel_id)."' WHERE leadgen_id = '".$leadgen_id."'";
                //mysqli_query($conn, $updateQuery) or die(mysqli_error($conn));
                
                $updatedLeads[] = $leadgen_id;
            }
        }
        
        // Send events to Meta CAPI in batches (max 100 events per request)
        if(!empty($events) && !empty($access_token)) {
            global $api_ver;
            $endpoint = "https://graph.facebook.com/".$api_ver."/".$pixel_id."/events";
            $chunks = array_chunk($events, 100);
            $batchCount = 0;
            $successCount = 0;
            $errorCount = 0;
            
            foreach($chunks as $batch) {
                $batchCount++;
                $event_payload = [
                    "data" => $batch
                ];
                
                // Build URL with access token as query parameter
                $url = $endpoint . "?access_token=" . urlencode($access_token);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event_payload));
                
                $response = curl_exec($ch);
                $error = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if($error) {
                    error_log("Meta CAPI Batch {$batchCount} Error: {$error}");
                    $errorCount++;
                } else {
                    $responseData = json_decode($response, true);
                    if($httpCode == 200 && isset($responseData['events_received'])) {
                        $successCount += count($batch);
                        error_log("Meta CAPI Batch {$batchCount}: Successfully sent " . count($batch) . " events");
                    } else {
                        error_log("Meta CAPI Batch {$batchCount} Response: " . $response);
                        $errorCount++;
                    }
                }
            }
            
            // Log summary
            error_log("Meta CAPI Summary: {$successCount} events sent successfully, {$errorCount} batches failed");
        }
        
        // Show success message
        $totalLeads = count($updatedLeads);
        echo "<script>alert('Lead stage updated successfully for {$totalLeads} lead(s)! Events sent to Meta CAPI.'); window.location = 'lead-capi.php';</script>";
    } else {
        echo "<script>alert('Please fill all required fields: select leads, lead stage, ad account, and pixel.');</script>";
    }
}

// Set default date range if not set
if(!isset($_SESSION['stDt'])) {
    $start = date('m/d/Y',strtotime('first day of this month'));
    $end = date('m/d/Y');
    $_SESSION['stDt'] = $start;
    $_SESSION['enDt'] = $end;
}

// Handle form submission
$showReport = false;
$selectedPage = '';
$selectedForms = [];
$reportData = [];

if(isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedPage = $_POST['page_id'];
    $selectedForms = isset($_POST['forms']) ? $_POST['forms'] : [];
    $stDt = $_POST['start_date'];
    $enDt = $_POST['end_date'];
    
    // Debug: Log the received date values
    error_log("Received dates from POST: start_date='$stDt', end_date='$enDt'");
    
    // Store selections in session for persistence
    $_SESSION['stDt'] = $stDt;
    $_SESSION['enDt'] = $enDt;
    $_SESSION['selected_page'] = $selectedPage;
    $_SESSION['selected_forms'] = $selectedForms;
    
    // Convert date format for API calls
    // Handle different date formats (MM/DD/YYYY from date picker)
    $dtRange1 = date("Y-m-d", strtotime($stDt));
    $dtRange2 = date("Y-m-d", strtotime($enDt));
    
    // Debug: Log the date conversion
    error_log("Date conversion - Input: $stDt to $enDt, Output: $dtRange1 to $dtRange2");
    error_log("Current timezone: " . date_default_timezone_get());
    
    // Additional debugging for date parsing
    $parsed_start = strtotime($stDt);
    $parsed_end = strtotime($enDt);
    error_log("Parsed timestamps - Start: $parsed_start (" . date('Y-m-d H:i:s', $parsed_start) . "), End: $parsed_end (" . date('Y-m-d H:i:s', $parsed_end) . ")");
    
    if(!empty($selectedPage) && !empty($selectedForms)) {
        // Get page access token from pages table using page_id
        $query2 = "SELECT pg_id, pg_name, pg_token FROM pages WHERE uid='".$_SESSION['uid']."' && pg_id='".$selectedPage."'";
        $pageResult = mysqli_query($conn, $query2);
        $pageData = mysqli_fetch_assoc($pageResult);
        
        if($pageData) {
            $page_token = $pageData['pg_token'];
            
            // Fetch leads for each selected form
            foreach($selectedForms as $form_id) {
                // Get leads from Facebook API for this form
                $leads = fetchLeadsFromForm($form_id, $page_token, $dtRange1, $dtRange2);
                
                if(!empty($leads)) {
                    foreach($leads as $lead) {
                        $lead['form_id'] = $form_id; // Track which form this lead came from
                        $reportData[] = $lead;
                    }
                }
            }
        }
    }
}

function fetchLeadsFromForm($form_id, $page_token, $start_date, $end_date) {
    global $api_ver;
    
    $leads = [];
    
    // First, get form structure to check field types using the correct API endpoint
    $form_url = "https://graph.facebook.com/".$api_ver."/".$form_id."?fields=questions&access_token=".$page_token;
    $form_output = file_get_contents_curl($form_url);
    $form_response = json_decode($form_output, true);
    
    $has_free_form_input = false;
    
    // Debug: Log the form structure response
    error_log("Form structure for form $form_id: " . print_r($form_response, true));
    
    // Check if API call was successful
    if(isset($form_response['error'])) {
        error_log("API Error for form $form_id: " . print_r($form_response['error'], true));
        // If API fails, default to 'N' for free form input
        $has_free_form_input = false;
    }
    
    // Check if form has free text input fields based on the actual API response structure
    if(isset($form_response['questions']) && is_array($form_response['questions'])) {
        foreach($form_response['questions'] as $question) {
            if(isset($question['type']) && isset($question['key'])) {
                $field_type = $question['type'];
                $field_key = $question['key'];
                
                // Skip standard fields
                if(in_array($field_key, ['full_name', 'email', 'phone_number', 'phone', 'first_name', 'first name'])) {
                    continue;
                }
                
                // Check if it's a free form input (CUSTOM type with no options)
                // Only consider it free form if it's CUSTOM type AND has no options array or empty options
                if($field_type === 'CUSTOM') {
                    // Check if options is null, not set, or empty array
                    if(!isset($question['options']) || $question['options'] === null || (is_array($question['options']) && empty($question['options']))) {
                        $has_free_form_input = true;
                        error_log("Found free form input field: " . (isset($question['label']) ? $question['label'] : $field_key));
                        break;
                    }
                }
            }
        }
    } else {
        // If no questions found or API response is invalid, default to no free form input
        error_log("No questions found in form $form_id or invalid API response");
        $has_free_form_input = false;
    }
    
    // Fetch leads from Facebook API
    // Convert dates to Unix timestamps for Facebook API
    // Create DateTime objects to handle timezone properly
    $since_datetime = new DateTime($start_date . " 00:00:00", new DateTimeZone('Asia/Calcutta'));
    $until_datetime = new DateTime($end_date . " 23:59:59", new DateTimeZone('Asia/Calcutta'));
    
    // Convert to UTC for Facebook API
    $since_datetime->setTimezone(new DateTimeZone('UTC'));
    $until_datetime->setTimezone(new DateTimeZone('UTC'));
    
    $since_timestamp = $since_datetime->getTimestamp();
    $until_timestamp = $until_datetime->getTimestamp();
    
    // Try different date parameter formats for Facebook API
    $url = "https://graph.facebook.com/".$api_ver."/".$form_id."/leads?fields=created_time,id,field_data&since=".$since_timestamp."&until=".$until_timestamp."&access_token=".$page_token."&limit=1000";
    
    // Debug: Log the API URL and date conversion
    error_log("Facebook API URL for form $form_id: " . $url);
    error_log("Date range: $start_date to $end_date (timestamps: $since_timestamp to $until_timestamp)");
    error_log("Date range in UTC: " . $since_datetime->format('Y-m-d H:i:s') . " to " . $until_datetime->format('Y-m-d H:i:s'));
    
    // Paginate through all lead pages
    do {
        $output = file_get_contents_curl($url);
        $response = json_decode($output, true);
        $url = null;
        
        error_log("Lead API Response for form $form_id: " . print_r($response, true));
        
        if(isset($response['data']) && !empty($response['data'])) {
            foreach($response['data'] as $leadData) {
                $lead = [];
                
                if(isset($leadData['field_data'])) {
                    foreach($leadData['field_data'] as $field) {
                        $lead[$field['name']] = isset($field['values'][0]) ? $field['values'][0] : '';
                    }
                }
                
                $lead['created_time'] = $leadData['created_time'];
                $lead['lead_id'] = $leadData['id'];
                $lead['leadgen_id'] = $leadData['id'];
                $lead['has_free_form_input'] = $has_free_form_input ? 'Y' : 'N';
                
                $lead_created_time = strtotime($leadData['created_time']);
                $lead_created_date = date('Y-m-d', $lead_created_time);
                
                error_log("Lead created: " . $leadData['created_time'] . " (date: $lead_created_date, timestamp: $lead_created_time)");
                
                if($lead_created_date >= $start_date && $lead_created_date <= $end_date) {
                    $leads[] = $lead;
                    error_log("Lead included in date range: $lead_created_date");
                } else {
                    error_log("Lead excluded from date range: $lead_created_date (range: $start_date to $end_date)");
                }
            }
        } else {
            error_log("No leads found for form $form_id. Response: " . print_r($response, true));
        }
        
        if(isset($response['paging']['next'])) {
            $url = $response['paging']['next'];
        }
    } while($url);
    
    return $leads;
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
    <title>AdRescue - Lead CAPI</title>
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
    <!-- DataTables Buttons (export) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.bootstrap.min.css">
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    
    <!-- Bootstrap-select JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    
     <style>
        .container, body { width: 100%; }
        .form-group { margin-bottom: 20px; }
        .table th, .table td { text-align: left; vertical-align: middle; }
        .table th { background-color: #f8f9fa; }
        
        /* Email column width - reduced */
        #leadsReport th:nth-child(4), #leadsReport td:nth-child(4) { width: 12%; }
        
        /* Phone column width - reduced */
        #leadsReport th:nth-child(5), #leadsReport td:nth-child(5) { width: 12%; }
        
        /* Questions column width - expanded */
        #leadsReport th:nth-child(6), #leadsReport td:nth-child(6) { width: 40%; }
        
        /* FFI column width */
        #leadsReport th:nth-child(7), #leadsReport td:nth-child(7) { width: 5%; text-align: center; }

        /* Force left alignment for this table */
        #leadsReport th, #leadsReport td { text-align: left !important; }
        /* FFI column center alignment override */
        #leadsReport th:nth-child(7), #leadsReport td:nth-child(7) { text-align: center !important; }
        
        /* Checkbox column */
        #leadsReport th:first-child, #leadsReport td:first-child { width: 40px; text-align: center; }
        
        /* Junk column icons */
        .junk-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .junk-y { background-color: #dc3545; }
        .junk-n { background-color: #28a745; }
        
        /* FFI column icons */
        .ffi-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .ffi-y { background-color: #28a745; }
        .ffi-n { background-color: #dc3545; }
        
        /* Fix tooltip z-index issue */
        .tooltip {
            z-index: 9999 !important;
        }
        .total-row { background-color: #bedbff; font-weight: bold; }
        .page-select { max-height: 200px; overflow-y: auto; }
        .date-range-container { margin: 20px 0; }
        .report-container { margin-top: 30px; }
        .btn-generate { margin-top: 20px; }
        
        /* Change all green text to black */
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
table.table td, .table th:nth-child(2), .table td:nth-child(2) {
    text-align: -webkit-center;
}
 span.fa.fa-check {
    color: #28a745;
}
span.fa.fa-times {
    color: #dc3545;
}       
.bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) { width: 100%; }
#leadsReport thead {
  position: relative;
  z-index: 1;
}
.tooltip {
  z-index: 1080 !important;  /* stays above table header */
}
#leadsReport th:nth-child(6),
#leadsReport td:nth-child(6) {
  width: 40%;
  word-wrap: break-word;
  white-space: normal;
}
/* Remove default padding/margin for question list inside table */
#leadsReport .question-list {
  margin: 0;
  padding: 0;
  list-style-position: inside; /* keeps numbers close to text */
}

/* Make list compact and aligned */
#leadsReport .question-list li {
  margin: 0;
  padding: 0;
  line-height: 1.3;
  list-style-type: decimal;
  white-space: normal;
}

/* Lead stage update form */
.lead-stage-form {
    padding: 0;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.lead-stage-form label {
    white-space: nowrap;
    margin-right: 5px;
}

.lead-stage-form select {
    flex: 1;
    min-width: 200px;
    max-width: 250px;
}

.lead-stage-form button {
    flex-shrink: 0;
}
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h4>Loading...</h4>
            <p>Fetching lead data from Facebook API...</p>
            <p><small>This may take a few moments depending on the number of forms selected.</small></p>
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
                                <!-- Page Selection -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select class="form-control" name="page_id" id="page_id" required>
                                            <option value="">Select a page...</option>
                                            <?php
                                            $pagesQuery = mysqli_query($conn, "SELECT pg_id, pg_name, client_name FROM leads_acc WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ORDER BY client_name, pg_name");
                                            while($page = mysqli_fetch_assoc($pagesQuery)) {
                                                $selected = '';
                                                if(isset($_SESSION['selected_page']) && $_SESSION['selected_page'] == $page['pg_id']) {
                                                    $selected = 'selected';
                                                } elseif(isset($_POST['page_id']) && $_POST['page_id'] == $page['pg_id']) {
                                                    $selected = 'selected';
                                                }
                                                echo '<option value="'.$page['pg_id'].'" '.$selected.'>'.$page['pg_name'].'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Forms Selection -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select class="selectpicker" multiple data-live-search="true" name="forms[]" id="forms" title="Select forms..." disabled>
                                            <option value="">Select forms first...</option>
                                            <?php
                                            // Show selected forms from session if available
                                            if(isset($_SESSION['selected_forms']) && !empty($_SESSION['selected_forms']) && isset($_SESSION['page_forms'][$_SESSION['selected_page']])) {
                                                $pageForms = $_SESSION['page_forms'][$_SESSION['selected_page']];
                                                foreach($_SESSION['selected_forms'] as $formId) {
                                                    // Find form name from stored forms
                                                    $formName = 'Loading...';
                                                    foreach($pageForms as $form) {
                                                        if($form['id'] == $formId) {
                                                            $formName = $form['name'];
                                                            break;
                                                        }
                                                    }
                                                    echo '<option value="'.$formId.'" selected>'.$formName.'</option>';
                                                }
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
                                            <i class="fa fa-search"></i> Fetch Leads
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Report Section -->
                <?php if($showReport): ?>
                    <?php if(!empty($reportData)): ?>
                <div class="report-container">
                    <!-- Detailed Leads Table -->
                    <div class="card">
                        <div class="card-header">
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="leadStageForm">
                                <div id="reportTable">
                                    <table id="leadsReport" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="selectAllLeads" title="Select All"></th>
                                                <th>SNo</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Questions</th>
                                                <th>FFI</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sno = 1;
                                            foreach($reportData as $lead):
                                                // Apply the custom logic for lead splitting
                                                $lName = $lEmail = $lPhone = $lCustom = '';
                                                $j = 1;
                                                
                                                foreach($lead as $attr => $val) {
                                                    if($attr == 'created_time' || $attr == 'lead_id' || $attr == 'leadgen_id' || $attr == 'has_free_form_input' || $attr == 'is_junk' || $attr == 'validation_reason' || $attr == 'form_id') continue; // Skip non-form fields and system fields
                                                    
                                                    $fields = ucwords(str_replace("_"," ",$attr));
                                                    if($attr=='full_name' || $attr=='full name' || $attr=='first_name' || $attr=='first name' || $attr=='name') { 
                                                        $lName = $val; 
                                                    }
                                                    else if($attr=='email') { 
                                                        $lEmail = $val;  
                                                    }
                                                    else if($attr=='phone_number' || $attr=='phone number' || $attr=='phone') { 
                                                        $lPhone = 'p:'.$val;  
                                                    }
                                                    else { 
                                                        $lCustom .= '<li>'.str_replace("_"," ",$attr).' = '.str_replace("_"," ",$val).'</li>';
                                                        $j++;
                                                    } 
                                                }
                                                
                                                $createdDate = date('d-m-Y h:i a', strtotime($lead['created_time']));
                                                $leadgen_id = isset($lead['leadgen_id']) ? $lead['leadgen_id'] : (isset($lead['lead_id']) ? $lead['lead_id'] : '');
                                                // Extract phone number from lPhone (remove 'p:' prefix if present)
                                                $phoneNumber = str_replace('p:', '', $lPhone);
                                                // Create JSON value for checkbox
                                                $checkboxValue = json_encode([
                                                    'leadgen_id' => $leadgen_id,
                                                    'phone' => $phoneNumber,
                                                    'email' => $lEmail
                                                ]);
                                            ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_leads[]" value="<?php echo htmlspecialchars($checkboxValue); ?>" class="lead-checkbox">
                                                </td>
                                                <td><?php echo $sno++; ?></td>
                                                <td><?php echo htmlspecialchars($lName); ?></td>
                                                <td><?php echo htmlspecialchars($lEmail); ?></td>
                                                <td><?php echo htmlspecialchars($lPhone); ?></td>
                                                <td style="text-align: left; max-width: none; word-wrap: break-word;"><?php if (!empty($lCustom)) { echo  '<ol class="question-list">' . $lCustom . '</ol>'; } ?></td>
                                                <td>
                                                    <?php 
                                                    $ffiStatus = isset($lead['has_free_form_input']) ? $lead['has_free_form_input'] : 'N';
                                                    $ffiIconClass = $ffiStatus === 'Y' ? 'fa-check' : 'fa-times';
                                                    ?>
                                                    <span class="fa <?php echo $ffiIconClass; ?>"></span>
                                                </td>
                                                <td><?php echo $createdDate; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Lead Stage Update Form -->
                    <div class="mt-4" style="background-color: #ebe9e9; padding: 15px; border-radius: 10px; margin-top: 10px;">
                        <form method="POST" action="" id="leadStageFormBottom">
                            <div class="lead-stage-form">
                                    <label for="lead_stage" style="margin: 0; font-weight: bold;">Select Lead Stage:</label>
                                    <select name="lead_stage" id="lead_stage" class="form-control" required>
                                        <option value="">Select Lead Stage</option>
                                        <option value="Qualified">Qualified</option>
                                        <option value="Not qualified">Not qualified</option>
                                        <option value="Site Visit Done">Site Visit Done</option>
                                        <option value="Intake">Intake</option>
                                        <option value="Converted">Converted</option>
                                        <option value="Lost">Lost</option>
                                    </select>
                                    
                                    <label for="ad_account" style="margin: 0; font-weight: bold;">Select Ad Account:</label>
                                    <select name="ad_account" id="ad_account" class="form-control" required>
                                        <option value="">Select Ad Account</option>
                                        <?php
                                        // Fetch ad accounts from budget_reminder table
                                        $budgetQuery = "SELECT DISTINCT fb_id FROM budget_reminder WHERE uid='".$_SESSION['uid']."' AND delete_status=0 AND fb_id != ''";
                                        $budgetResult = mysqli_query($conn, $budgetQuery);
                                        $allFbIds = [];
                                        
                                        while($budgetRow = mysqli_fetch_assoc($budgetResult)) {
                                            if(!empty($budgetRow['fb_id'])) {
                                                // Split comma-separated fb_ids
                                                $fbIds = explode(',', $budgetRow['fb_id']);
                                                foreach($fbIds as $fbId) {
                                                    $fbId = trim($fbId);
                                                    if(!empty($fbId) && !in_array($fbId, $allFbIds)) {
                                                        $allFbIds[] = $fbId;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Get account names from adAccounts table
                                        if(!empty($allFbIds)) {
                                            $escapedFbIds = array();
                                            foreach($allFbIds as $fbId) {
                                                $escapedFbIds[] = "'" . mysqli_real_escape_string($conn, $fbId) . "'";
                                            }
                                            $fbIdsStr = implode(',', $escapedFbIds);
                                            $accountQuery = "SELECT account_id, name FROM adAccounts WHERE account_id IN ($fbIdsStr) AND uid='".$_SESSION['uid']."' ORDER BY name ASC";
                                            $accountResult = mysqli_query($conn, $accountQuery);
                                            
                                            while($accountRow = mysqli_fetch_assoc($accountResult)) {
                                                echo '<option value="'.$accountRow['account_id'].'">'.$accountRow['name'].' ('.$accountRow['account_id'].')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    
                                    <label for="pixel_id" style="margin: 0; font-weight: bold;">Select Pixel:</label>
                                    <select name="pixel_id" id="pixel_id" class="form-control" required disabled>
                                        <option value="">Select Ad Account first</option>
                                    </select>
                                    
                                    <button type="submit" name="update_lead_stage" class="btn btn-success">
                                        <i class="fa fa-save"></i> Update Lead Stage
                                    </button>
                            </div>
                        </form>
                    </div>
                </div>
                    <?php else: ?>
                <div class="report-container">
                    <div class="card">
                        <div class="card-header">
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h4>No Leads Found</h4>
                                <p>No leads were found for the selected page, forms, and date range. Please try:</p>
                                <ul>
                                    <li>Selecting a different date range</li>
                                    <li>Choosing different forms</li>
                                    <li>Verifying that the page has lead forms and leads in the selected period</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <br>
                                        
    <script>
       $(document).ready(function () {
    console.log('Document ready');

    // On page reload after form submission, hide overlay quickly
    <?php if(isset($_POST['submit_report']) || isset($_POST['update_lead_stage'])): ?>
    $('.loading-overlay').removeClass('active');
    <?php endif; ?>

    // Show overlay when form is submitted
    $('form[method="POST"]').on('submit', function (e) {
        // Don't show overlay for lead stage update form
        if(!$(this).find('[name="update_lead_stage"]').length) {
            $('.loading-overlay').addClass('active');

            // Prevent submission if no page or forms selected
            var selectedPage = $('#page_id').val();
            var selectedForms = $('.selectpicker').val();
            
            if (!selectedPage) {
                alert('Please select a page.');
                $('.loading-overlay').removeClass('active');
                return false;
            }
            
            if (!selectedForms || selectedForms.length === 0) {
                alert('Please select at least one form.');
                $('.loading-overlay').removeClass('active');
                return false;
            }
        } else {
            // Handle lead stage form submission - get checkboxes from main form
            var selectedLeads = $('#leadStageForm .lead-checkbox:checked').length;
            if(selectedLeads === 0) {
                alert('Please select at least one lead.');
                e.preventDefault();
                return false;
            }
            
            // Copy selected checkboxes to bottom form
            var checkedBoxes = [];
            $('#leadStageForm .lead-checkbox:checked').each(function() {
                checkedBoxes.push($(this).clone());
            });
            
            // Clear and add checkboxes to bottom form
            $('#leadStageFormBottom input[name="selected_leads[]"]').remove();
            checkedBoxes.forEach(function(box) {
                box.css('display', 'none');
                $('#leadStageFormBottom').append(box);
            });
            
            var leadStage = $('#lead_stage').val();
            if(!leadStage) {
                alert('Please select a lead stage.');
                e.preventDefault();
                return false;
            }
            
            var adAccount = $('#ad_account').val();
            if(!adAccount) {
                alert('Please select an ad account.');
                e.preventDefault();
                return false;
            }
            
            var pixelId = $('#pixel_id').val();
            if(!pixelId) {
                alert('Please select a pixel.');
                e.preventDefault();
                return false;
            }
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

    // Handle page selection change
    $('#page_id').on('change', function() {
        var pageId = $(this).val();
        var formsSelect = $('#forms');
        
        if(pageId) {
            // Enable forms select and fetch forms
            formsSelect.prop('disabled', false);
            formsSelect.html('<option value="">Loading forms...</option>');
            
            $.ajax({
                url: 'ajax-fetch-forms.php',
                type: 'POST',
                data: { page_id: pageId },
                dataType: 'json',
                success: function(response) {
                    formsSelect.html('');
                    if(response.success && response.forms.length > 0) {
                        // Get previously selected forms for this page from session
                        var selectedForms = <?php echo json_encode(isset($_SESSION['selected_forms']) ? $_SESSION['selected_forms'] : []); ?>;
                        
                        $.each(response.forms, function(index, form) {
                            var isSelected = selectedForms.includes(form.id);
                            var option = '<option value="' + form.id + '"';
                            if(isSelected) {
                                option += ' selected';
                            }
                            option += '>' + form.name + '</option>';
                            formsSelect.append(option);
                        });
                    } else {
                        formsSelect.html('<option value="">No forms found</option>');
                    }
                    
                    // Refresh bootstrap-select
                    formsSelect.selectpicker('refresh');
                },
                error: function() {
                    formsSelect.html('<option value="">Error loading forms</option>');
                    formsSelect.selectpicker('refresh');
                }
            });
        } else {
            // Disable forms select
            formsSelect.prop('disabled', true);
            formsSelect.html('<option value="">Select a page first</option>');
            formsSelect.selectpicker('refresh');
        }
    });

    // Initialize DataTables
    <?php if($showReport && !empty($reportData)): ?>
    try {
        $('#leadsReport').DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"B>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                { extend: 'copy', className: 'btn btn-primary btn-sm' },
                { extend: 'csv', className: 'btn btn-primary btn-sm' },
                { extend: 'excel', className: 'btn btn-primary btn-sm' },
                { extend: 'pdf', className: 'btn btn-primary btn-sm' },
                { extend: 'print', className: 'btn btn-primary btn-sm' }
            ],
            ordering: true,
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
            scrollX: true,
            autoWidth: false,
            bLengthChange: true,
            searching: true,
            info: true,
            paging: true,
            order: [],
            columnDefs: [
                { orderable: false, targets: 0 } // Disable sorting on checkbox column
            ]
        });
    } catch (e) {
        console.error('Error initializing DataTable:', e);
    }
    <?php endif; ?>
    
    // Load forms on page load if page is already selected
    var selectedPageId = $('#page_id').val();
    if(selectedPageId) {
        $('#page_id').trigger('change');
    }
    
    // Select all checkbox functionality
    $('#selectAllLeads').on('change', function() {
        $('.lead-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Handle ad account selection change
    $('#ad_account').on('change', function() {
        var adAccountId = $(this).val();
        var pixelSelect = $('#pixel_id');
        
        if(adAccountId) {
            // Enable pixel select and fetch pixels
            pixelSelect.prop('disabled', false);
            pixelSelect.html('<option value="">Loading pixels...</option>');
            
            $.ajax({
                url: 'ajax-fetch-pixels.php',
                type: 'POST',
                data: { ad_account_id: adAccountId },
                dataType: 'json',
                success: function(response) {
                    pixelSelect.html('');
                    if(response.success && response.pixels.length > 0) {
                        pixelSelect.append('<option value="">Select Pixel</option>');
                        $.each(response.pixels, function(index, pixel) {
                            var option = '<option value="' + pixel.id + '">' + pixel.name + ' (' + pixel.id + ')</option>';
                            pixelSelect.append(option);
                        });
                    } else {
                        pixelSelect.html('<option value="">No pixels found</option>');
                        if(response.message) {
                            alert('Error: ' + response.message);
                        }
                    }
                },
                error: function() {
                    pixelSelect.html('<option value="">Error loading pixels</option>');
                    alert('Error loading pixels. Please try again.');
                }
            });
        } else {
            // Disable pixel select
            pixelSelect.prop('disabled', true);
            pixelSelect.html('<option value="">Select Ad Account first</option>');
        }
    });
});
    </script>
</body>
</html>

