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

// OpenAI API Configuration
$OPENAI_API_KEY = getenv('OPENAI_API_KEY');

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
            
            // Validate leads with ChatGPT if we have leads
            if(!empty($reportData)) {
                $reportData = validateLeadsWithChatGPT($reportData);
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
                if(in_array($field_key, ['full_name', 'email', 'phone_number'])) {
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
    
    // Alternative URL format (some versions use different parameter names)
    $alt_url = "https://graph.facebook.com/".$api_ver."/".$form_id."/leads?fields=created_time,id,field_data&since=".$start_date."&until=".$end_date."&access_token=".$page_token."&limit=1000";
    
    // Debug: Log the API URL and date conversion
    error_log("Facebook API URL for form $form_id: " . $url);
    error_log("Date range: $start_date to $end_date (timestamps: $since_timestamp to $until_timestamp)");
    error_log("Date range in UTC: " . $since_datetime->format('Y-m-d H:i:s') . " to " . $until_datetime->format('Y-m-d H:i:s'));
    
    $output = file_get_contents_curl($url);
    $response = json_decode($output, true);
    
    // Debug: Log the response for troubleshooting
    error_log("Lead API Response for form $form_id: " . print_r($response, true));
    
    if(isset($response['data']) && !empty($response['data'])) {
        foreach($response['data'] as $leadData) {
            $lead = [];
            
            // Process field data
            if(isset($leadData['field_data'])) {
                foreach($leadData['field_data'] as $field) {
                    $lead[$field['name']] = isset($field['values'][0]) ? $field['values'][0] : '';
                }
            }
            
            $lead['created_time'] = $leadData['created_time'];
            $lead['lead_id'] = $leadData['id'];
            $lead['has_free_form_input'] = $has_free_form_input ? 'Y' : 'N';
            
            // Client-side date filtering (fallback if API filtering doesn't work)
            $lead_created_time = strtotime($leadData['created_time']);
            $lead_created_date = date('Y-m-d', $lead_created_time);
            
            // Debug: Log each lead's creation date
            error_log("Lead created: " . $leadData['created_time'] . " (date: $lead_created_date, timestamp: $lead_created_time)");
            
            // Only include leads within the selected date range
            if($lead_created_date >= $start_date && $lead_created_date <= $end_date) {
                $leads[] = $lead;
                error_log("Lead included in date range: $lead_created_date");
            } else {
                error_log("Lead excluded from date range: $lead_created_date (range: $start_date to $end_date)");
            }
        }
    } else {
        // Log if no data found
        error_log("No leads found for form $form_id. Response: " . print_r($response, true));
    }
    
    return $leads;
}

function calculateFormSummary($reportData, $selectedForms) {
    // Get form names from session
    $formNames = [];
    if(isset($_SESSION['page_forms'][$_SESSION['selected_page']])) {
        foreach($_SESSION['page_forms'][$_SESSION['selected_page']] as $form) {
            $formNames[$form['id']] = $form['name'];
        }
    }
    
    // Initialize form stats
    $formStats = [];
    foreach($selectedForms as $formId) {
        $formStats[$formId] = [
            'form_id' => $formId,
            'form_name' => isset($formNames[$formId]) ? $formNames[$formId] : 'Unknown Form',
            'ffi_status' => 'N', // Default to N, will be updated based on actual form structure
            'total_leads' => 0,
            'genuine_leads' => 0,
            'junk_leads' => 0
        ];
    }
    
    // Count leads by form
    foreach($reportData as $lead) {
        $formId = isset($lead['form_id']) ? $lead['form_id'] : 'unknown';
        
        if(isset($formStats[$formId])) {
            $formStats[$formId]['total_leads']++;
            
            // Update FFI status based on form structure (use first lead from each form)
            if($formStats[$formId]['total_leads'] == 1 && isset($lead['has_free_form_input'])) {
                $formStats[$formId]['ffi_status'] = $lead['has_free_form_input'];
            }
            
            // Count genuine/junk
            if(isset($lead['is_junk'])) {
                if($lead['is_junk'] === 'Y') {
                    $formStats[$formId]['junk_leads']++;
                } else if($lead['is_junk'] === 'N') {
                    $formStats[$formId]['genuine_leads']++;
                }
            }
        }
    }
    
    // Calculate totals
    $totalLeads = count($reportData);
    $totalGenuine = 0;
    $totalJunk = 0;
    
    foreach($formStats as $form) {
        $totalGenuine += $form['genuine_leads'];
        $totalJunk += $form['junk_leads'];
    }
    
    return [
        'form_stats' => $formStats,
        'totals' => [
            'total_leads' => $totalLeads,
            'genuine_leads' => $totalGenuine,
            'junk_leads' => $totalJunk
        ]
    ];
}

function validateLeadsWithChatGPT($leads) {
    global $OPENAI_API_KEY;
    
    if(empty($leads) || $OPENAI_API_KEY === "YOUR_API_KEY_HERE") {
        // Return default validation if no API key or no leads
        foreach($leads as &$lead) {
            $lead['is_junk'] = 'N/A';
            $lead['validation_reason'] = 'API not configured';
        }
        return $leads;
    }
    
    // Build message content dynamically - only custom questions
    $content = "Evaluate the following leads for quality (genuine vs junk):\n\n";
    foreach ($leads as $index => $lead) {
        $content .= "Lead ID: " . ($index + 1) . "\n";
        
        // Only add custom questions (exclude standard fields)
        $hasCustomQuestions = false;
        foreach($lead as $attr => $val) {
            if(!in_array($attr, ['full_name', 'email', 'phone_number', 'created_time', 'lead_id', 'has_free_form_input', 'is_junk', 'validation_reason', 'form_id'])) {
                $content .= ucwords(str_replace("_", " ", $attr)) . ": " . $val . "\n";
                $hasCustomQuestions = true;
            }
        }
        
        // If no custom questions, add a note
        if(!$hasCustomQuestions) {
            $content .= "No custom questions available\n";
        }
        $content .= "\n";
    }
    
    $content .= "Return a JSON object like this:\n";
    $content .= "{ \"lead_id\": { \"status\": \"genuine|junk|unclear\", \"reason\": \"short explanation\" } }";
    
    // API payload using working format from chatgpt.php
    $payload = [
        "model" => "gpt-4o-mini",
        "response_format" => ["type" => "json_object"],
        "messages" => [
            ["role" => "system", "content" => "You are a lead quality classifier for Meta LeadGen forms. Analyze each lead and determine if it's genuine (real potential customer) or junk (fake, spam, or low quality). Consider factors like: realistic responses, relevant answers to questions, and overall lead quality."],
            ["role" => "user", "content" => $content]
        ]
    ];
    
    // Initialize cURL
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $OPENAI_API_KEY"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if(!$response) {
        error_log("ChatGPT API Error: No response");
        foreach($leads as &$lead) {
            $lead['is_junk'] = 'Error';
            $lead['validation_reason'] = 'API Error';
        }
        return $leads;
    }
    
    $result = json_decode($response, true);
    
    if(!isset($result['choices'][0]['message']['content'])) {
        error_log("ChatGPT API Response Error: " . print_r($result, true));
        foreach($leads as &$lead) {
            $lead['is_junk'] = 'Error';
            $lead['validation_reason'] = 'Invalid Response';
        }
        return $leads;
    }
    
    $parsed = json_decode($result['choices'][0]['message']['content'], true);
    
    if(!$parsed) {
        error_log("ChatGPT JSON Parse Error: " . $result['choices'][0]['message']['content']);
        foreach($leads as &$lead) {
            $lead['is_junk'] = 'Error';
            $lead['validation_reason'] = 'Parse Error';
        }
        return $leads;
    }
    
    // Apply validation results to leads
    foreach($leads as $index => &$lead) {
        $leadId = $index + 1;
        if(isset($parsed[$leadId])) {
            $lead['is_junk'] = ($parsed[$leadId]['status'] === 'junk') ? 'Y' : 'N';
            $lead['validation_reason'] = $parsed[$leadId]['reason'];
        } else {
            $lead['is_junk'] = 'N/A';
            $lead['validation_reason'] = 'Not analyzed';
        }
    }
    
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
    <title>AdRescue - Lead Analysis</title>
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
        .table th:nth-child(5), .table td:nth-child(5) { width: 25%; }
        /* FFI column width */
        .table th:nth-child(6), .table td:nth-child(6) { width: 5%; text-align: center; }
        /* Junk column width */
        .table th:nth-child(7), .table td:nth-child(7) { width: 10%; text-align: center; }

        /* Force left alignment for this table */
        #leadsReport th, #leadsReport td { text-align: left !important; }
        /* FFI and Junk columns center alignment override */
        #leadsReport th:nth-child(6), #leadsReport td:nth-child(6),
        #leadsReport th:nth-child(7), #leadsReport td:nth-child(7) { text-align: center !important; }
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
                                            <i class="fa fa-search"></i> Lead Analysis
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
                    <!-- Summary Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Lead Analysis Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $summary = calculateFormSummary($reportData, $selectedForms);
                            ?>
                            
                            <!-- Detailed Form Summary Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Form ID</th>
                                            <th>Form Name</th>
                                            <th>FFI</th>
                                            <th>Total Leads</th>
                                            <th>Genuine Leads</th>
                                            <th>Junk Leads</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($summary['form_stats'] as $form): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($form['form_id']); ?></td>
                                            <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $form['ffi_status'] === 'Y' ? 'badge-warning' : 'badge-secondary'; ?>">
                                                    <?php echo $form['ffi_status']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?php echo $form['total_leads']; ?></td>
                                            <td class="text-center text-success"><?php echo $form['genuine_leads']; ?></td>
                                            <td class="text-center text-danger"><?php echo $form['junk_leads']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <!-- Total Row -->
                                        <tr class="table-info font-weight-bold">
                                            <td colspan="3"><strong>Total</strong></td>
                                            <td class="text-center"><strong><?php echo $summary['totals']['total_leads']; ?></strong></td>
                                            <td class="text-center text-success"><strong><?php echo $summary['totals']['genuine_leads']; ?></strong></td>
                                            <td class="text-center text-danger"><strong><?php echo $summary['totals']['junk_leads']; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Progress Bars -->
                            <?php if($summary['totals']['total_leads'] > 0): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h6>Overall Lead Quality Distribution</h6>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($summary['totals']['genuine_leads'] / $summary['totals']['total_leads']) * 100; ?>%">
                                            <?php echo round(($summary['totals']['genuine_leads'] / $summary['totals']['total_leads']) * 100, 1); ?>% Genuine
                                        </div>
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($summary['totals']['junk_leads'] / $summary['totals']['total_leads']) * 100; ?>%">
                                            <?php echo round(($summary['totals']['junk_leads'] / $summary['totals']['total_leads']) * 100, 1); ?>% Junk
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Detailed Leads Table -->
                    <div class="card">
                        <div class="card-header">
                           <small class="text-muted">Reporting time: <?php echo date("d-m-Y, h:i a"); ?></small>
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
                                            <th>FFI</th>
                                            <th>Junk?</th>
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
                                                if($attr == 'created_time' || $attr == 'lead_id' || $attr == 'has_free_form_input' || $attr == 'is_junk' || $attr == 'validation_reason' || $attr == 'form_id') continue; // Skip non-form fields and system fields
                                                
                                                $fields = ucwords(str_replace("_"," ",$attr));
                                                if($attr=='full_name') { 
                                                    $lName = $val; 
                                                }
                                                else if($attr=='full name') { 
                                                    $lName = $val; 
                                                }
                                                else if($attr=='email') { 
                                                    $lEmail = $val;  
                                                }
                                                else if($attr=='phone_number') { 
                                                    $lPhone = 'p:'.$val;  
                                                }
                                                else { 
                                                    $lCustom .= '[Q'.$j.']: '.str_replace("_"," ",$attr).' = '.str_replace("_"," ",$val).' ';
                                                    $j++;
                                                } 
                                            }
                                            
                                            $createdDate = date('d-m-Y H:i', strtotime($lead['created_time']));
                                        ?>
                                        <tr>
                                            <td><?php echo $sno++; ?></td>
                                            <td><?php echo htmlspecialchars($lName); ?></td>
                                            <td><?php echo htmlspecialchars($lEmail); ?></td>
                                            <td><?php echo htmlspecialchars($lPhone); ?></td>
                                            <td style="text-align: left; max-width: none; word-wrap: break-word;"><?php echo htmlspecialchars($lCustom); ?></td>
                                            <td><?php echo isset($lead['has_free_form_input']) ? $lead['has_free_form_input'] : 'N'; ?></td>
                                            <td>
                                                <?php 
                                                $junkStatus = isset($lead['is_junk']) ? $lead['is_junk'] : 'N/A';
                                                $junkClass = '';
                                                if($junkStatus === 'Y') {
                                                    $junkClass = 'text-danger font-weight-bold';
                                                } else if($junkStatus === 'N') {
                                                    $junkClass = 'text-success font-weight-bold';
                                                } else {
                                                    $junkClass = 'text-muted';
                                                }
                                                ?>
                                                <span class="<?php echo $junkClass; ?>"><?php echo $junkStatus; ?></span>
                                                <?php if(isset($lead['validation_reason']) && $lead['validation_reason'] !== 'API not configured'): ?>
                                                <br><small class="text-muted" title="<?php echo htmlspecialchars($lead['validation_reason']); ?>"><?php echo htmlspecialchars(substr($lead['validation_reason'], 0, 30)) . (strlen($lead['validation_reason']) > 30 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $createdDate; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
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
                    <?php else: ?>
                <div class="report-container">
                    <div class="card">
                        <div class="card-header">
                           <small class="text-muted">Reporting time: <?php echo date("d-m-Y, h:i a"); ?></small>
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
    <?php if($showReport && !empty($reportData)): ?>
    try {
        $('#leadsReport').DataTable({
            dom: 'Blfrtip',
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
