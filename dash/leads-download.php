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
            
            // Get user access token for campaign/adset/ad data
            $userQuery = "SELECT access_token FROM users WHERE tbl_id=2";
            $userResult = mysqli_query($conn, $userQuery);
            $userData = mysqli_fetch_assoc($userResult);
            $user_access_token = $userData['access_token'];
            
                // Fetch leads for each selected form
            foreach($selectedForms as $form_id) {
                // Get form name from session data
                $form_name = '';
                if(isset($_SESSION['page_forms'][$selectedPage])) {
                    foreach($_SESSION['page_forms'][$selectedPage] as $form) {
                        if($form['id'] == $form_id) {
                            $form_name = $form['name'];
                            break;
                        }
                    }
                }
                
                // Get leads from Facebook API for this form
                $leads = fetchLeadsFromForm($form_id, $page_token, $user_access_token, $dtRange1, $dtRange2);
                
                if(!empty($leads)) {
                    foreach($leads as $lead) {
                        $lead['form_id'] = $form_id; // Track which form this lead came from
                        $lead['form_name'] = $form_name; // Add form name
                        $reportData[] = $lead;
                    }
                }
            }
            
        }
    }
}

function fetchLeadsFromForm($form_id, $page_token, $user_access_token, $start_date, $end_date) {
    global $api_ver;
    
    $leads = [];
    
    
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
    $url = "https://graph.facebook.com/".$api_ver."/".$form_id."/leads?fields=created_time,id,field_data,platform&since=".$since_timestamp."&until=".$until_timestamp."&access_token=".$page_token."&limit=1000";
    
    // Debug: Log the API URL and date conversion
    error_log("Facebook API URL for form $form_id: " . $url);
    error_log("Date range: $start_date to $end_date (timestamps: $since_timestamp to $until_timestamp)");
    error_log("Date range in UTC: " . $since_datetime->format('Y-m-d H:i:s') . " to " . $until_datetime->format('Y-m-d H:i:s'));
    
    // Paginate through all lead pages
    do {
        $output = file_get_contents_curl($url);
        $response = json_decode($output, true);
        $url = null;
        
        // Debug: Log the response for troubleshooting
        error_log("Lead API Response for form $form_id: " . print_r($response, true));
        
        if(isset($response['data']) && !empty($response['data'])) {
            $lead_ids = [];
            $temp_leads = [];
            
            foreach($response['data'] as $leadData) {
                $lead = [];
                
                if(isset($leadData['field_data'])) {
                    foreach($leadData['field_data'] as $field) {
                        $lead[$field['name']] = isset($field['values'][0]) ? $field['values'][0] : '';
                    }
                }
                
                $lead['created_time'] = $leadData['created_time'];
                $lead['lead_id'] = $leadData['id'];
                $lead['platform'] = isset($leadData['platform']) ? $leadData['platform'] : '';
                
                $lead_created_time = strtotime($leadData['created_time']);
                $lead_created_date = date('Y-m-d', $lead_created_time);
                
                error_log("Lead created: " . $leadData['created_time'] . " (date: $lead_created_date, timestamp: $lead_created_time)");
                
                if($lead_created_date >= $start_date && $lead_created_date <= $end_date) {
                    $lead_ids[] = $leadData['id'];
                    $temp_leads[$leadData['id']] = $lead;
                    error_log("Lead included in date range: $lead_created_date");
                } else {
                    error_log("Lead excluded from date range: $lead_created_date (range: $start_date to $end_date)");
                }
            }
            
            if(!empty($lead_ids)) {
                $campaign_data = fetchBulkCampaignData($lead_ids, $user_access_token);
                
                foreach($temp_leads as $lead_id => $lead) {
                    if(isset($campaign_data[$lead_id])) {
                        $lead['campaign_name'] = $campaign_data[$lead_id]['campaign_name'] ?? '';
                        $lead['adset_name'] = $campaign_data[$lead_id]['adset_name'] ?? '';
                        $lead['ad_name'] = $campaign_data[$lead_id]['ad_name'] ?? '';
                    } else {
                        $lead['campaign_name'] = '';
                        $lead['adset_name'] = '';
                        $lead['ad_name'] = '';
                    }
                    $leads[] = $lead;
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

function file_get_contents_curl_post($url, $post_data) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
}

function fetchBulkCampaignData($lead_ids, $user_access_token) {
    global $api_ver;
    
    $campaign_data = [];
    
    // Facebook Batch API allows up to 50 requests per batch
    $batch_size = 50;
    $batches = array_chunk($lead_ids, $batch_size);
    
    foreach($batches as $batch) {
        $batch_requests = [];
        
        // Create batch requests for each lead ID
        foreach($batch as $index => $lead_id) {
            $batch_requests[] = [
                'method' => 'GET',
                'relative_url' => $lead_id . '?fields=campaign_name,adset_name,ad_name',
                'name' => 'lead_' . $index
            ];
        }
        
        // Create batch request
        $batch_data = [
            'batch' => json_encode($batch_requests),
            'access_token' => $user_access_token
        ];
        
        // Make batch API call
        $batch_url = "https://graph.facebook.com/" . $api_ver . "/";
        $batch_output = file_get_contents_curl_post($batch_url, $batch_data);
        $batch_response = json_decode($batch_output, true);
        
        // Process batch response
        if(isset($batch_response) && is_array($batch_response)) {
            foreach($batch_response as $response) {
                if(isset($response['body'])) {
                    $lead_data = json_decode($response['body'], true);
                    if(isset($lead_data['id'])) {
                        $lead_id = $lead_data['id'];
                        $campaign_data[$lead_id] = [
                            'campaign_name' => $lead_data['campaign_name'] ?? '',
                            'adset_name' => $lead_data['adset_name'] ?? '',
                            'ad_name' => $lead_data['ad_name'] ?? ''
                        ];
                    }
                }
            }
        }
    }
    
    return $campaign_data;
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
    <title>AdRescue - Leads Download</title>
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
        
        /* Questions column width */
        .table th:nth-child(5), .table td:nth-child(5) { width: 20%; }
        /* New columns styling */
        .table th:nth-child(6), .table td:nth-child(6) { width: 8%; } /* Platform */
        .table th:nth-child(7), .table td:nth-child(7) { width: 15%; } /* Form Name */
        .table th:nth-child(8), .table td:nth-child(8) { width: 15%; } /* Campaign */
        .table th:nth-child(9), .table td:nth-child(9) { width: 15%; } /* AdSet */
        .table th:nth-child(10), .table td:nth-child(10) { width: 15%; } /* Ad */
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
table#leadsReport td {
    text-align: left !important;
}
.bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) { width: 100%; }
#leadsReport thead {
  position: relative;
  z-index: 1;
}
.tooltip {
  z-index: 1080 !important;  /* stays above table header */
}
#leadsReport th:nth-child(5),
#leadsReport td:nth-child(5) {
  width: 30%;
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
                            <div id="reportTable">
                                <table id="leadsReport" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>SNo</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Questions</th>
                                            <th>Platform</th>
                                            <th>Form Name</th>
                                            <th>Campaign</th>
                                            <th>AdSet</th>
                                            <th>Ad</th>
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
                                                if($attr == 'created_time' || $attr == 'lead_id' || $attr == 'has_free_form_input' || $attr == 'is_junk' || $attr == 'validation_reason' || $attr == 'form_id' || $attr == 'platform' || $attr == 'campaign_name' || $attr == 'adset_name' || $attr == 'ad_name' || $attr == 'form_name') continue; // Skip non-form fields, system fields, and new dedicated columns
                                                
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
                                        ?>
                                        <tr>
                                            <td><?php echo $sno++; ?></td>
                                            <td><?php echo htmlspecialchars($lName); ?></td>
                                            <td><?php echo htmlspecialchars($lEmail); ?></td>
                                            <td><?php echo htmlspecialchars($lPhone); ?></td>
                                            <td style="text-align: left; max-width: none; word-wrap: break-word;"><?php if (!empty($lCustom)) { echo  '<ol class="question-list">' . $lCustom . '</ol>'; } ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['platform']) ? $lead['platform'] : ''); ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['form_name']) ? $lead['form_name'] : ''); ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['campaign_name']) ? $lead['campaign_name'] : ''); ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['adset_name']) ? $lead['adset_name'] : ''); ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['ad_name']) ? $lead['ad_name'] : ''); ?></td>
                                            <td><?php echo $createdDate; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
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
    <?php if(isset($_POST['submit_report'])): ?>
    $('.loading-overlay').removeClass('active');
    <?php endif; ?>

    // Show overlay when form is submitted
    $('form').on('submit', function () {
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
            formsSelect.html('<option value="">Select a page first...</option>');
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
            order: []
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
    
});
    </script>
</body>
</html>
