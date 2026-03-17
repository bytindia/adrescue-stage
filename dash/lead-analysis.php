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

/**
 * cURL POST helper for batch API (used by fetchCampaignSpend)
 */
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
$campaignSummary = [];
$adsetSummary = [];
$adSummary = [];

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
            
            // Enrich leads with campaign, adset, ad names (page token for ad details)
            if(!empty($reportData)) {
                $reportData = fetchAdNamesForLeads($reportData, $page_token);
            }
            
            // Validate leads with ChatGPT if we have leads
            if(!empty($reportData)) {
                $reportData = validateLeadsWithChatGPT($reportData);
            }
            
            // Campaign / Adset / Ad-wise summary: fetch spend via batch API using user token
            $campaignSummary = $adsetSummary = $adSummary = [];
            if(!empty($reportData)) {
                $campaign_ids = array_unique(array_filter(array_column($reportData, 'campaign_id')));
                $adset_ids = array_unique(array_filter(array_column($reportData, 'adset_id')));
                $ad_ids = array_unique(array_filter(array_column($reportData, 'ad_id')));
                $campaignSpend = !empty($campaign_ids) ? fetchEntitySpend($campaign_ids, $access_token, $dtRange1, $dtRange2) : [];
                $adsetSpend = !empty($adset_ids) ? fetchEntitySpend($adset_ids, $access_token, $dtRange1, $dtRange2) : [];
                $adSpend = !empty($ad_ids) ? fetchEntitySpend($ad_ids, $access_token, $dtRange1, $dtRange2) : [];
                $campaignSummary = calculateCampaignSummary($reportData, $campaignSpend);
                $adsetSummary = calculateAdsetSummary($reportData, $adsetSpend);
                $adSummary = calculateAdSummary($reportData, $adSpend);
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
    
    // Try different date parameter formats for Facebook API (include ad_id, adset_id, campaign_id for ad-level data)
    $url = "https://graph.facebook.com/".$api_ver."/".$form_id."/leads?fields=created_time,id,field_data,ad_id,adset_id,campaign_id&since=".$since_timestamp."&until=".$until_timestamp."&access_token=".$page_token."&limit=1000";
    
    // Alternative URL format (some versions use different parameter names)
    $alt_url = "https://graph.facebook.com/".$api_ver."/".$form_id."/leads?fields=created_time,id,field_data,ad_id,adset_id,campaign_id&since=".$start_date."&until=".$end_date."&access_token=".$page_token."&limit=1000";
    
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
            $lead['ad_id'] = isset($leadData['ad_id']) ? $leadData['ad_id'] : '';
            $lead['adset_id'] = isset($leadData['adset_id']) ? $leadData['adset_id'] : '';
            $lead['campaign_id'] = isset($leadData['campaign_id']) ? $leadData['campaign_id'] : '';
            
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

/**
 * Enrich leads with campaign_name, adset_name, ad_name by fetching from Facebook Ad API.
 * Requires ads_management permission on the access token.
 */
function fetchAdNamesForLeads($leads, $page_token) {
    global $api_ver;
    
    // Collect unique ad_ids
    $adIds = [];
    foreach($leads as $lead) {
        if(!empty($lead['ad_id'])) {
            $adIds[$lead['ad_id']] = true;
        }
    }
    $adIds = array_keys($adIds);
    
    if(empty($adIds)) {
        return $leads;
    }
    
    // Cache: ad_id => [campaign_name, adset_name, ad_name]
    $adNamesCache = [];
    
    foreach($adIds as $ad_id) {
        $ad_url = "https://graph.facebook.com/".$api_ver."/".$ad_id."?fields=name,adset{name},campaign{name}&access_token=".$page_token;
        $ad_output = file_get_contents_curl($ad_url);
        $ad_response = json_decode($ad_output, true);
        
        $campaign_name = '';
        $adset_name = '';
        $ad_name = '';
        
        if(!isset($ad_response['error'])) {
            $ad_name = isset($ad_response['name']) ? $ad_response['name'] : '';
            if(isset($ad_response['adset']['name'])) {
                $adset_name = $ad_response['adset']['name'];
            }
            if(isset($ad_response['campaign']['name'])) {
                $campaign_name = $ad_response['campaign']['name'];
            }
        }
        
        $adNamesCache[$ad_id] = [
            'campaign_name' => $campaign_name,
            'adset_name' => $adset_name,
            'ad_name' => $ad_name
        ];
    }
    
    // Attach names to each lead
    foreach($leads as &$lead) {
        $lead['campaign_name'] = '';
        $lead['adset_name'] = '';
        $lead['ad_name'] = '';
        if(!empty($lead['ad_id']) && isset($adNamesCache[$lead['ad_id']])) {
            $lead['campaign_name'] = $adNamesCache[$lead['ad_id']]['campaign_name'];
            $lead['adset_name'] = $adNamesCache[$lead['ad_id']]['adset_name'];
            $lead['ad_name'] = $adNamesCache[$lead['ad_id']]['ad_name'];
        }
    }
    unset($lead);
    
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

/**
 * Fetch spend per entity (campaign, adset, or ad) using Facebook Batch API.
 * Uses user access token (like spend.php). Works for /{entity_id}/insights.
 * Returns array: entity_id => spend (float)
 */
function fetchEntitySpend($entity_ids, $user_access_token, $start_date, $end_date) {
    global $api_ver;
    
    $entity_ids = array_values(array_unique(array_filter($entity_ids)));
    if(empty($entity_ids) || empty($user_access_token)) {
        return [];
    }
    
    $result = [];
    $batch_size = 50; // Facebook Batch API limit
    $batches = array_chunk($entity_ids, $batch_size);
    
    foreach($batches as $batch_index => $batch) {
        $batch_requests = [];
        $tr_params = 'fields=spend&time_range[since]='.$start_date.'&time_range[until]='.$end_date;
        
        foreach($batch as $idx => $entity_id) {
            $batch_requests[] = [
                'method' => 'GET',
                'relative_url' => $entity_id.'/insights?'.$tr_params,
                'name' => 'ent_'.$batch_index.'_'.$idx
            ];
        }
        
        $batch_data = [
            'batch' => json_encode($batch_requests),
            'access_token' => $user_access_token
        ];
        
        $batch_url = "https://graph.facebook.com/".$api_ver."/";
        $batch_output = file_get_contents_curl_post($batch_url, $batch_data);
        $batch_response = json_decode($batch_output, true);
        
        if(isset($batch_response) && is_array($batch_response)) {
            foreach($batch_response as $idx => $resp) {
                $entity_id = isset($batch[$idx]) ? $batch[$idx] : '';
                if(!$entity_id) continue;
                if(isset($resp['body']) && (!isset($resp['code']) || $resp['code'] == 200)) {
                    $body = json_decode($resp['body'], true);
                    $spend = 0;
                    if(isset($body['data']) && is_array($body['data']) && !isset($body['error'])) {
                        foreach($body['data'] as $row) {
                            $spend += isset($row['spend']) ? floatval(str_replace(',', '', $row['spend'])) : 0;
                        }
                    }
                    $result[$entity_id] = isset($result[$entity_id]) ? $result[$entity_id] + $spend : $spend;
                }
            }
        }
    }
    
    return $result;
}

/**
 * Calculate campaign-wise lead summary: Campaign | Spend | Lead | CPL | Genuine | Genuine %
 */
function calculateCampaignSummary($reportData, $campaignSpend = []) {
    $byCampaign = [];
    
    foreach($reportData as $lead) {
        $campaign_id = isset($lead['campaign_id']) ? $lead['campaign_id'] : '_other_';
        $campaign_name = isset($lead['campaign_name']) && $lead['campaign_name'] !== '' ? $lead['campaign_name'] : 'Organic / Other';
        
        if(!isset($byCampaign[$campaign_id])) {
            $byCampaign[$campaign_id] = [
                'campaign_id' => $campaign_id,
                'campaign_name' => $campaign_name,
                'leads' => 0,
                'genuine' => 0,
                'spend' => isset($campaignSpend[$campaign_id]) ? $campaignSpend[$campaign_id] : 0
            ];
        }
        $byCampaign[$campaign_id]['leads']++;
        if(isset($lead['is_junk']) && $lead['is_junk'] === 'N') {
            $byCampaign[$campaign_id]['genuine']++;
        }
    }
    
    $rows = [];
    foreach($byCampaign as $c) {
        $cpl = ($c['leads'] > 0 && $c['spend'] > 0) ? round($c['spend'] / $c['leads'], 0) : ($c['leads'] > 0 ? 0 : '-');
        $genuine_pct = ($c['leads'] > 0) ? round(($c['genuine'] / $c['leads']) * 100, 1) : '-';
        $rows[] = [
            'campaign_name' => $c['campaign_name'],
            'spend' => $c['spend'],
            'leads' => $c['leads'],
            'cpl' => $cpl,
            'genuine' => $c['genuine'],
            'genuine_pct' => $genuine_pct
        ];
    }
    return $rows;
}

/**
 * Calculate adset-wise lead summary: Ad Set | Campaign | Spend | Lead | CPL | Genuine | Genuine %
 */
function calculateAdsetSummary($reportData, $adsetSpend = []) {
    $byAdset = [];
    
    foreach($reportData as $lead) {
        $adset_id = isset($lead['adset_id']) ? $lead['adset_id'] : '_other_';
        $adset_name = isset($lead['adset_name']) && $lead['adset_name'] !== '' ? $lead['adset_name'] : 'Organic / Other';
        $campaign_name = isset($lead['campaign_name']) && $lead['campaign_name'] !== '' ? $lead['campaign_name'] : 'Organic / Other';
        
        if(!isset($byAdset[$adset_id])) {
            $byAdset[$adset_id] = [
                'adset_id' => $adset_id,
                'adset_name' => $adset_name,
                'campaign_name' => $campaign_name,
                'leads' => 0,
                'genuine' => 0,
                'spend' => isset($adsetSpend[$adset_id]) ? $adsetSpend[$adset_id] : 0
            ];
        }
        $byAdset[$adset_id]['leads']++;
        if(isset($lead['is_junk']) && $lead['is_junk'] === 'N') {
            $byAdset[$adset_id]['genuine']++;
        }
    }
    
    $rows = [];
    foreach($byAdset as $a) {
        $cpl = ($a['leads'] > 0 && $a['spend'] > 0) ? round($a['spend'] / $a['leads'], 0) : ($a['leads'] > 0 ? 0 : '-');
        $genuine_pct = ($a['leads'] > 0) ? round(($a['genuine'] / $a['leads']) * 100, 1) : '-';
        $rows[] = [
            'adset_name' => $a['adset_name'],
            'campaign_name' => $a['campaign_name'],
            'spend' => $a['spend'],
            'leads' => $a['leads'],
            'cpl' => $cpl,
            'genuine' => $a['genuine'],
            'genuine_pct' => $genuine_pct
        ];
    }
    return $rows;
}

/**
 * Calculate ad-wise lead summary: Ad Name | Campaign | Spend | Lead | CPL | Genuine | Genuine %
 */
function calculateAdSummary($reportData, $adSpend = []) {
    $byAd = [];
    
    foreach($reportData as $lead) {
        $ad_id = isset($lead['ad_id']) ? $lead['ad_id'] : '_other_';
        $ad_name = isset($lead['ad_name']) && $lead['ad_name'] !== '' ? $lead['ad_name'] : 'Organic / Other';
        $campaign_name = isset($lead['campaign_name']) && $lead['campaign_name'] !== '' ? $lead['campaign_name'] : 'Organic / Other';
        $adset_name = isset($lead['adset_name']) && $lead['adset_name'] !== '' ? $lead['adset_name'] : 'Organic / Other';
        
        if(!isset($byAd[$ad_id])) {
            $byAd[$ad_id] = [
                'ad_id' => $ad_id,
                'ad_name' => $ad_name,
                'campaign_name' => $campaign_name,
                'adset_name' => $adset_name,
                'leads' => 0,
                'genuine' => 0,
                'spend' => isset($adSpend[$ad_id]) ? $adSpend[$ad_id] : 0
            ];
        }
        $byAd[$ad_id]['leads']++;
        if(isset($lead['is_junk']) && $lead['is_junk'] === 'N') {
            $byAd[$ad_id]['genuine']++;
        }
    }
    
    $rows = [];
    foreach($byAd as $a) {
        $cpl = ($a['leads'] > 0 && $a['spend'] > 0) ? round($a['spend'] / $a['leads'], 0) : ($a['leads'] > 0 ? 0 : '-');
        $genuine_pct = ($a['leads'] > 0) ? round(($a['genuine'] / $a['leads']) * 100, 1) : '-';
        $rows[] = [
            'ad_name' => $a['ad_name'],
            'campaign_name' => $a['campaign_name'],
            'adset_name' => isset($a['adset_name']) ? $a['adset_name'] : 'Organic / Other',
            'spend' => $a['spend'],
            'leads' => $a['leads'],
            'cpl' => $cpl,
            'genuine' => $a['genuine'],
            'genuine_pct' => $genuine_pct
        ];
    }
    return $rows;
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
            if(!in_array($attr, ['full_name', 'email', 'phone_number', 'phone', 'first_name', 'first name', 'created_time', 'lead_id', 'has_free_form_input', 'is_junk', 'validation_reason', 'form_id', 'ad_id', 'adset_id', 'campaign_id', 'campaign_name', 'adset_name', 'ad_name'])) {
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
    
    // Site visit question validation rules - these answers are ALWAYS genuine (not junk)
    $siteVisitRules = "SITE VISIT / VISIT TIME question rules: For questions about site visit, preferred visit time, or when to visit, the following answers are ALWAYS genuine (not junk): Today, tomorrow; Next week, this week; This month, next month; Weekday names (Monday, Tuesday, etc.); Date in ANY format (dd-mm-yyyy, d/m/y, 15th March, etc.); Day; Yes, no; Soon, later; Few days, few months; Send details, call back; Whatsapp, call (or similar). Spelling mistakes in these answers (e.g. tommorow, wek, monday) are still genuine. Treat dates in any format as genuine.";
    
    // Contact method question validation - phone number, WhatsApp, call = genuine; names = junk
    $contactRules = "CONTACT / PREFERRED CONTACT question rules: For questions like 'right way to contact you', 'call/whatsapp', 'preferred contact method', 'how to reach you', etc.: GENUINE (not junk): Phone numbers (digits, e.g. 8778871641, 8680886040); WhatsApp, Whatsapp, Whatsap (any spelling variant); Only WhatsApp; Call; similar contact preferences. Spelling mistakes like Whatsap, Whatsup are valid. JUNK: Person names (e.g. 'Agostin Guria') as answer - names are NOT valid contact methods.";
    
    // API payload using working format from chatgpt.php
    $payload = [
        "model" => "gpt-4o-mini",
        "response_format" => ["type" => "json_object"],
        "messages" => [
            ["role" => "system", "content" => "You are a lead quality classifier for Meta LeadGen forms. Analyze each lead and determine if it's genuine (real potential customer) or junk (fake, spam, or low quality). Consider factors like: realistic responses, relevant answers to questions, and overall lead quality.\n\n" . $siteVisitRules . "\n\n" . $contactRules],
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
        #leadsReport th:nth-child(8), #leadsReport td:nth-child(8) { width: 25%; }
        /* FFI column width */
        #leadsReport th:nth-child(9), #leadsReport td:nth-child(9) { width: 5%; text-align: center; }
        /* Genuine column width */
        #leadsReport th:nth-child(10), #leadsReport td:nth-child(10) { width: 10%; text-align: center; }

        /* Force left alignment for this table */
        #leadsReport th, #leadsReport td { text-align: left !important; }
        /* FFI and Genuine columns center alignment override */
        #leadsReport th:nth-child(9), #leadsReport td:nth-child(9),
        #leadsReport th:nth-child(10), #leadsReport td:nth-child(10) { text-align: center !important; }
        
        /* Lead Analysis Summary table alignment */
        .table th, .table td { text-align: center; vertical-align: middle; }
        .table th:first-child, .table td:first-child { text-align: left; }
        .table th:nth-child(2), .table td:nth-child(2) { text-align: left; }
        
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
        .junk-info {
            margin-left: 5px;
            cursor: pointer;
            color: #007bff;
        }
        
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
#leadsReport th:nth-child(8),
#leadsReport td:nth-child(8) {
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
/* Toggle dropdown - prevent clipping, ensure visible */
.card.leads-analysis-card { overflow: visible !important; }
.report-container .card-body .table-responsive { overflow-x: auto; overflow-y: visible; }
/* Filters - ensure bootstrap-select displays as multi-select, keep on one line */
.leads-analysis-card .bootstrap-select .dropdown-menu { z-index: 99999 !important; }
.leads-analysis-card .bootstrap-select { min-width: 150px; }
#leadsFilters .bootstrap-select { margin-right: 8px; }
#leadsFilters .bootstrap-select:last-child { margin-right: 0; }
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
                                            <th>Genuine</th>
                                            <th>Junk</th>
                                            <th>Genuine %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($summary['form_stats'] as $form): 
                                            $genuinePct = ($form['total_leads'] > 0) ? round(($form['genuine_leads'] / $form['total_leads']) * 100, 1) : '-';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($form['form_id']); ?></td>
                                            <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                                            <td>
                                                <?php 
                                                $ffiStatus = $form['ffi_status'];
                                                $ffiIconClass = $ffiStatus === 'Y' ? 'fa-check' : 'fa-times';
                                                ?>
                                                <span class="fa <?php echo $ffiIconClass; ?>"></span>
                                            </td>
                                            <td><?php echo $form['total_leads']; ?></td>
                                            <td class="text-success"><?php echo $form['genuine_leads']; ?></td>
                                            <td class="text-danger"><?php echo $form['junk_leads']; ?></td>
                                            <td><?php echo $genuinePct . ($genuinePct !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tfoot>
                                        <?php 
                                        $totalGenuinePct = ($summary['totals']['total_leads'] > 0) ? round(($summary['totals']['genuine_leads'] / $summary['totals']['total_leads']) * 100, 1) : '-';
                                        ?>
                                        <tr class="table-info bg-warning" style="font-weight: bold;">
                                            <td colspan="3">Total</td>
                                            <td><?php echo $summary['totals']['total_leads']; ?></td>
                                            <td class="text-success"><?php echo $summary['totals']['genuine_leads']; ?></td>
                                            <td class="text-danger"><?php echo $summary['totals']['junk_leads']; ?></td>
                                            <td><?php echo $totalGenuinePct . ($totalGenuinePct !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        </tfoot>
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Detailed Leads Table -->
                    <div class="card leads-analysis-card">
                        <div class="card-header">
                            <h5 class="mb-0">Leads Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div id="leadsFilters" class="d-flex align-items-center flex-nowrap justify-content-end">
                                <select class="campaign-filter mr-2" multiple title="Campaign">
                                    <?php
                                    $uniqueCampaigns = [];
                                    foreach($reportData as $lead) {
                                        $cn = isset($lead['campaign_name']) && $lead['campaign_name'] !== '' ? $lead['campaign_name'] : 'Organic / Other';
                                        $uniqueCampaigns[$cn] = true;
                                    }
                                    foreach(array_keys($uniqueCampaigns) as $cn): ?>
                                    <option value="<?php echo htmlspecialchars($cn); ?>" selected><?php echo htmlspecialchars($cn); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="adset-filter mr-2" multiple title="Ad Set">
                                    <?php
                                    $uniqueAdsets = [];
                                    foreach($reportData as $lead) {
                                        $an = isset($lead['adset_name']) && $lead['adset_name'] !== '' ? $lead['adset_name'] : 'Organic / Other';
                                        $uniqueAdsets[$an] = true;
                                    }
                                    foreach(array_keys($uniqueAdsets) as $an): ?>
                                    <option value="<?php echo htmlspecialchars($an); ?>" selected><?php echo htmlspecialchars($an); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="ad-filter mr-2" multiple title="Ad">
                                    <?php
                                    $uniqueAds = [];
                                    foreach($reportData as $lead) {
                                        $adn = isset($lead['ad_name']) && $lead['ad_name'] !== '' ? $lead['ad_name'] : 'Organic / Other';
                                        $uniqueAds[$adn] = true;
                                    }
                                    foreach(array_keys($uniqueAds) as $adn): ?>
                                    <option value="<?php echo htmlspecialchars($adn); ?>" selected><?php echo htmlspecialchars($adn); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="column-filter" multiple title="Columns">
                                    <option value="1">Name</option>
                                    <option value="2">Email</option>
                                    <option value="3">Phone</option>
                                    <option value="4" selected>Campaign</option>
                                    <option value="5" selected>Ad Set</option>
                                    <option value="6" selected>Ad Name</option>
                                    <option value="7" selected>Questions</option>
                                    <option value="8" selected>FFI</option>
                                    <option value="9" selected>Genuine?</option>
                                    <option value="10" selected>Created</option>
                                </select>
                            </div>
                            <div id="reportTable">
                                <table id="leadsReport" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>SNo</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Campaign</th>
                                            <th>Ad Set</th>
                                            <th>Ad Name</th>
                                            <th style="width:30%;">Questions</th>
                                            <th>FFI</th>
                                            <th>Genuine?</th>
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
                                                if($attr == 'created_time' || $attr == 'lead_id' || $attr == 'has_free_form_input' || $attr == 'is_junk' || $attr == 'validation_reason' || $attr == 'form_id' || $attr == 'ad_id' || $attr == 'adset_id' || $attr == 'campaign_id' || $attr == 'campaign_name' || $attr == 'adset_name' || $attr == 'ad_name') continue; // Skip non-form fields and system fields
                                                
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
                                            <td><?php echo htmlspecialchars(isset($lead['campaign_name']) ? $lead['campaign_name'] : '-'); ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['adset_name']) ? $lead['adset_name'] : '-'); ?></td>
                                            <td><?php echo htmlspecialchars(isset($lead['ad_name']) ? $lead['ad_name'] : '-'); ?></td>
                                            <td style="text-align: left; max-width: none; word-wrap: break-word;"><?php if (!empty($lCustom)) { echo  '<ol class="question-list">' . $lCustom . '</ol>'; } ?></td>
                                            <td>
                                                <?php 
                                                $ffiStatus = isset($lead['has_free_form_input']) ? $lead['has_free_form_input'] : 'N';
                                                $ffiIconClass = $ffiStatus === 'Y' ? 'fa-check' : 'fa-times';
                                                ?>
                                                <span class="fa <?php echo $ffiIconClass; ?>"></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $junkStatus = isset($lead['is_junk']) ? $lead['is_junk'] : 'N/A';
                                                $junkClass = '';
                                                $junkIconClass = '';
                                                if($junkStatus === 'Y') {
                                                    $junkClass = 'text-danger font-weight-bold';
                                                    $junkIconClass = 'fa-times';
                                                } else if($junkStatus === 'N') {
                                                    $junkClass = 'text-success font-weight-bold';
                                                    $junkIconClass = 'fa-check';
                                                } else {
                                                    $junkClass = 'text-muted';
                                                }
                                                ?>
                                                <?php if($junkStatus !== 'N/A'): ?>
                                                <span class="fa <?php echo $junkIconClass; ?>"></span>
                                                <?php endif; ?>
                                                <?php if(isset($lead['validation_reason']) && $lead['validation_reason'] !== 'API not configured' && $lead['validation_reason'] !== 'Not analyzed'): ?>
                                                <i class="fa fa-info-circle junk-info" data-toggle="tooltip" data-placement="top" title="<?php echo htmlspecialchars($lead['validation_reason']); ?>"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $createdDate; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campaign-wise Lead Analysis -->
                    <?php if(!empty($campaignSummary)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Campaign-wise Lead Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="campaignSummaryTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Campaign</th>
                                            <th>Spend</th>
                                            <th>Lead</th>
                                            <th>CPL</th>
                                            <th>Genuine</th>
                                            <th>Genuine %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($campaignSummary as $row): ?>
                                        <tr data-campaign="<?php echo htmlspecialchars($row['campaign_name']); ?>" data-spend="<?php echo floatval($row['spend']); ?>" data-leads="<?php echo intval($row['leads']); ?>" data-genuine="<?php echo intval($row['genuine']); ?>">
                                            <td><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                            <td><?php echo $row['spend'] > 0 ? number_format($row['spend'], 0) : '-'; ?></td>
                                            <td><?php echo $row['leads']; ?></td>
                                            <td><?php echo $row['cpl'] === '-' ? '-' : number_format($row['cpl'], 0); ?></td>
                                            <td class="text-success"><?php echo $row['genuine']; ?></td>
                                            <td><?php echo $row['genuine_pct'] . ($row['genuine_pct'] !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <?php
                                        $campTotalSpend = array_sum(array_column($campaignSummary, 'spend'));
                                        $campTotalLeads = array_sum(array_column($campaignSummary, 'leads'));
                                        $campTotalGenuine = array_sum(array_column($campaignSummary, 'genuine'));
                                        $campTotalCpl = ($campTotalLeads > 0 && $campTotalSpend > 0) ? round($campTotalSpend / $campTotalLeads, 2) : '-';
                                        $campTotalGenuinePct = ($campTotalLeads > 0) ? round(($campTotalGenuine / $campTotalLeads) * 100, 1) : '-';
                                        ?>
                                        <tr class="table-info bg-warning" style="font-weight: bold;">
                                            <td>Total</td>
                                            <td><?php echo $campTotalSpend > 0 ? number_format($campTotalSpend, 0) : '-'; ?></td>
                                            <td><?php echo $campTotalLeads; ?></td>
                                            <td><?php echo $campTotalCpl === '-' ? '-' : number_format($campTotalCpl, 0); ?></td>
                                            <td class="text-success"><?php echo $campTotalGenuine; ?></td>
                                            <td><?php echo $campTotalGenuinePct . ($campTotalGenuinePct !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Adset-wise Lead Analysis -->
                    <?php if(!empty($adsetSummary)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Ad Set-wise Lead Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="adsetSummaryTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Ad Set</th>
                                            <th>Campaign</th>
                                            <th>Spend</th>
                                            <th>Lead</th>
                                            <th>CPL</th>
                                            <th>Genuine</th>
                                            <th>Genuine %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($adsetSummary as $row): ?>
                                        <tr data-campaign="<?php echo htmlspecialchars($row['campaign_name']); ?>" data-adset="<?php echo htmlspecialchars($row['adset_name']); ?>" data-spend="<?php echo floatval($row['spend']); ?>" data-leads="<?php echo intval($row['leads']); ?>" data-genuine="<?php echo intval($row['genuine']); ?>">
                                            <td><?php echo htmlspecialchars($row['adset_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                            <td><?php echo $row['spend'] > 0 ? number_format($row['spend'], 0) : '-'; ?></td>
                                            <td><?php echo $row['leads']; ?></td>
                                            <td><?php echo $row['cpl'] === '-' ? '-' : number_format($row['cpl'], 0); ?></td>
                                            <td class="text-success"><?php echo $row['genuine']; ?></td>
                                            <td><?php echo $row['genuine_pct'] . ($row['genuine_pct'] !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <?php
                                        $adsetTotalSpend = array_sum(array_column($adsetSummary, 'spend'));
                                        $adsetTotalLeads = array_sum(array_column($adsetSummary, 'leads'));
                                        $adsetTotalGenuine = array_sum(array_column($adsetSummary, 'genuine'));
                                        $adsetTotalCpl = ($adsetTotalLeads > 0 && $adsetTotalSpend > 0) ? round($adsetTotalSpend / $adsetTotalLeads, 2) : '-';
                                        $adsetTotalGenuinePct = ($adsetTotalLeads > 0) ? round(($adsetTotalGenuine / $adsetTotalLeads) * 100, 1) : '-';
                                        ?>
                                        <tr class="table-info bg-warning" style="font-weight: bold;">
                                            <td colspan="2">Total</td>
                                            <td><?php echo $adsetTotalSpend > 0 ? number_format($adsetTotalSpend, 0) : '-'; ?></td>
                                            <td><?php echo $adsetTotalLeads; ?></td>
                                            <td><?php echo $adsetTotalCpl === '-' ? '-' : number_format($adsetTotalCpl, 0); ?></td>
                                            <td class="text-success"><?php echo $adsetTotalGenuine; ?></td>
                                            <td><?php echo $adsetTotalGenuinePct . ($adsetTotalGenuinePct !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Ad-wise Lead Analysis -->
                    <?php if(!empty($adSummary)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Ad-wise Lead Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="adSummaryTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Ad Name</th>
                                            <th>Campaign</th>
                                            <th>Spend</th>
                                            <th>Lead</th>
                                            <th>CPL</th>
                                            <th>Genuine</th>
                                            <th>Genuine %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($adSummary as $row): ?>
                                        <tr data-campaign="<?php echo htmlspecialchars($row['campaign_name']); ?>" data-adset="<?php echo htmlspecialchars(isset($row['adset_name']) ? $row['adset_name'] : 'Organic / Other'); ?>" data-ad="<?php echo htmlspecialchars($row['ad_name']); ?>" data-spend="<?php echo floatval($row['spend']); ?>" data-leads="<?php echo intval($row['leads']); ?>" data-genuine="<?php echo intval($row['genuine']); ?>">
                                            <td><?php echo htmlspecialchars($row['ad_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                            <td><?php echo $row['spend'] > 0 ? number_format($row['spend'], 0) : '-'; ?></td>
                                            <td><?php echo $row['leads']; ?></td>
                                            <td><?php echo $row['cpl'] === '-' ? '-' : number_format($row['cpl'], 0); ?></td>
                                            <td class="text-success"><?php echo $row['genuine']; ?></td>
                                            <td><?php echo $row['genuine_pct'] . ($row['genuine_pct'] !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <?php
                                        $adTotalSpend = array_sum(array_column($adSummary, 'spend'));
                                        $adTotalLeads = array_sum(array_column($adSummary, 'leads'));
                                        $adTotalGenuine = array_sum(array_column($adSummary, 'genuine'));
                                        $adTotalCpl = ($adTotalLeads > 0 && $adTotalSpend > 0) ? round($adTotalSpend / $adTotalLeads, 2) : '-';
                                        $adTotalGenuinePct = ($adTotalLeads > 0) ? round(($adTotalGenuine / $adTotalLeads) * 100, 1) : '-';
                                        ?>
                                        <tr class="table-info bg-warning" style="font-weight: bold;">
                                            <td colspan="2">Total</td>
                                            <td><?php echo $adTotalSpend > 0 ? number_format($adTotalSpend, 0) : '-'; ?></td>
                                            <td><?php echo $adTotalLeads; ?></td>
                                            <td><?php echo $adTotalCpl === '-' ? '-' : number_format($adTotalCpl, 0); ?></td>
                                            <td class="text-success"><?php echo $adTotalGenuine; ?></td>
                                            <td><?php echo $adTotalGenuinePct . ($adTotalGenuinePct !== '-' ? '%' : ''); ?></td>
                                        </tr>
                                        </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
        $(function () {
  $('[data-toggle="tooltip"]').tooltip({
    container: 'body',   // moves tooltip out of the table
    boundary: 'viewport'
  });

  // if using DataTables
  $('#leadsReport').on('draw.dt', function () {
    $('[data-toggle="tooltip"]').tooltip({
      container: 'body',
      boundary: 'viewport'
    });
  });
});

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

    // Initialize Bootstrap-select safely (forms + campaign filter)
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
        // Init filter selectpickers FIRST (before DataTable) so filter works on first draw
        if ($('.column-filter').length) {
            $('.column-filter').selectpicker({
                liveSearch: true,
                actionsBox: true,
                selectAllText: 'Select All',
                deselectAllText: 'Deselect All',
                selectedTextFormat: 'count',
                countSelectedText: '{0} columns',
                size: 8,
                width: '180px',
                noneSelectedText: 'Columns'
            });
        }
        if ($('.campaign-filter').length) {
            $('.campaign-filter').selectpicker({
                liveSearch: true,
                actionsBox: true,
                selectAllText: 'Select All',
                deselectAllText: 'Deselect All',
                selectedTextFormat: 'count',
                countSelectedText: '{0} campaigns',
                size: 6,
                width: '180px',
                noneSelectedText: 'Campaign'
            });
        }
        if ($('.adset-filter').length) {
            $('.adset-filter').selectpicker({
                liveSearch: true,
                actionsBox: true,
                selectAllText: 'Select All',
                deselectAllText: 'Deselect All',
                selectedTextFormat: 'count',
                countSelectedText: '{0} ad sets',
                size: 6,
                width: '180px',
                noneSelectedText: 'Ad Set'
            });
        }
        if ($('.ad-filter').length) {
            $('.ad-filter').selectpicker({
                liveSearch: true,
                actionsBox: true,
                selectAllText: 'Select All',
                deselectAllText: 'Deselect All',
                selectedTextFormat: 'count',
                countSelectedText: '{0} ads',
                size: 6,
                width: '180px',
                noneSelectedText: 'Ad'
            });
        }
        
        var leadsTable = $('#leadsReport').DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row mb-2"<"col-sm-12"B>>' +
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
                { targets: [1, 2, 3], visible: false }  // Hide Name, Email, Phone by default
            ],
            initComplete: function() {
                var $btnRow = $('#leadsReport').closest('.dataTables_wrapper').find('.dt-buttons').closest('.col-sm-12');
                if ($btnRow.length && $('#leadsFilters').length) {
                    $btnRow.addClass('col-md-4').after($('<div class="col-sm-12 col-md-8 d-flex align-items-center justify-content-end"></div>').append($('#leadsFilters')));
                }
            }
        });
        
        // Column filter - show/hide columns based on multi-select
        function applyColumnFilter() {
            var sel = $('.column-filter');
            if (!sel.length) return;
            var selected = [];
            try { selected = sel.selectpicker('val') || []; } catch(e) {}
            selected = selected.map(function(v) { return parseInt(v, 10); });
            for (var c = 1; c <= 10; c++) {
                leadsTable.column(c).visible(selected.indexOf(c) !== -1);
            }
        }
        $('.column-filter').on('changed.bs.select change', function() {
            applyColumnFilter();
        });
        applyColumnFilter();
        
        // Combined filter helpers
        function getSelectedCampaigns() {
            var sel = $('.campaign-filter');
            if (!sel.length) return [];
            try { var v = sel.selectpicker('val'); return Array.isArray(v) ? v : (v ? [v] : []); } catch(e) { return []; }
        }
        function getSelectedAdsets() {
            var sel = $('.adset-filter');
            if (!sel.length) return [];
            try { var v = sel.selectpicker('val'); return Array.isArray(v) ? v : (v ? [v] : []); } catch(e) { return []; }
        }
        function getSelectedAds() {
            var sel = $('.ad-filter');
            if (!sel.length) return [];
            try { var v = sel.selectpicker('val'); return Array.isArray(v) ? v : (v ? [v] : []); } catch(e) { return []; }
        }
        var leadsFilterSearchFn = function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'leadsReport') return true;
            var campaigns = getSelectedCampaigns();
            var adsets = getSelectedAdsets();
            var ads = getSelectedAds();
            if ((!campaigns || campaigns.length === 0) && (!adsets || adsets.length === 0) && (!ads || ads.length === 0)) return false;
            var camp = String(data[4] || '').trim() || 'Organic / Other';
            var adset = String(data[5] || '').trim() || 'Organic / Other';
            var ad = String(data[6] || '').trim() || 'Organic / Other';
            var campMatch = (!campaigns || campaigns.length === 0) || (campaigns.indexOf(camp) !== -1);
            var adsetMatch = (!adsets || adsets.length === 0) || (adsets.indexOf(adset) !== -1);
            var adMatch = (!ads || ads.length === 0) || (ads.indexOf(ad) !== -1);
            return campMatch && adsetMatch && adMatch;
        };
        $.fn.dataTable.ext.search.push(leadsFilterSearchFn);
        
        // Filter Campaign-wise, Ad Set-wise, Ad-wise tables by selected campaigns, adsets, ads + update totals
        function filterSummaryTables() {
            var campaigns = getSelectedCampaigns();
            var adsets = getSelectedAdsets();
            var ads = getSelectedAds();
            var noFilter = (!campaigns || campaigns.length === 0) && (!adsets || adsets.length === 0) && (!ads || ads.length === 0);
            $('#campaignSummaryTable tbody tr').each(function() {
                var $row = $(this);
                var camp = $row.attr('data-campaign') || '';
                var campOk = (!campaigns || campaigns.length === 0) || (campaigns.indexOf(camp) !== -1);
                $row.toggle(!noFilter && campOk);
            });
            $('#adsetSummaryTable tbody tr').each(function() {
                var $row = $(this);
                var camp = $row.attr('data-campaign') || '';
                var adset = $row.attr('data-adset') || '';
                var campOk = (!campaigns || campaigns.length === 0) || (campaigns.indexOf(camp) !== -1);
                var adsetOk = (!adsets || adsets.length === 0) || (adsets.indexOf(adset) !== -1);
                $row.toggle(!noFilter && campOk && adsetOk);
            });
            $('#adSummaryTable tbody tr').each(function() {
                var $row = $(this);
                var camp = $row.attr('data-campaign') || '';
                var adset = $row.attr('data-adset') || '';
                var ad = $row.attr('data-ad') || '';
                var campOk = (!campaigns || campaigns.length === 0) || (campaigns.indexOf(camp) !== -1);
                var adsetOk = (!adsets || adsets.length === 0) || (adsets.indexOf(adset) !== -1);
                var adOk = (!ads || ads.length === 0) || (ads.indexOf(ad) !== -1);
                $row.toggle(!noFilter && campOk && adsetOk && adOk);
            });
            updateSummaryTotals();
        }
        
        function updateSummaryTotals() {
            var campaigns = getSelectedCampaigns();
            var adsets = getSelectedAdsets();
            var ads = getSelectedAds();
            var noFilter = (!campaigns || campaigns.length === 0) && (!adsets || adsets.length === 0) && (!ads || ads.length === 0);
            function rowVisible($row, tableId) {
                var camp = $row.attr('data-campaign') || '';
                var campOk = (!campaigns || campaigns.length === 0) || (campaigns.indexOf(camp) !== -1);
                if (tableId === 'campaign') return !noFilter && campOk;
                var adset = $row.attr('data-adset') || '';
                var adsetOk = (!adsets || adsets.length === 0) || (adsets.indexOf(adset) !== -1);
                if (tableId === 'adset') return !noFilter && campOk && adsetOk;
                var ad = $row.attr('data-ad') || '';
                var adOk = (!ads || ads.length === 0) || (ads.indexOf(ad) !== -1);
                return !noFilter && campOk && adsetOk && adOk;
            }
            function fmtNum(n, decimals) {
                if (decimals === 0) return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return parseFloat(n).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            [['#campaignSummaryTable', 'campaign'], ['#adsetSummaryTable', 'adset'], ['#adSummaryTable', 'ad']].forEach(function(pair) {
                var sel = pair[0], tableId = pair[1];
                var $tbl = $(sel);
                if (!$tbl.length) return;
                var spend = 0, leads = 0, genuine = 0;
                $tbl.find('tbody tr').each(function() {
                    var $r = $(this);
                    if (!rowVisible($r, tableId)) return;
                    spend += parseFloat($r.attr('data-spend') || 0);
                    leads += parseInt($r.attr('data-leads') || 0, 10);
                    genuine += parseInt($r.attr('data-genuine') || 0, 10);
                });
                var cpl = (leads > 0 && spend > 0) ? fmtNum(spend / leads, 0) : '-';
                var genuinePct = (leads > 0) ? fmtNum((genuine / leads) * 100, 1) : '-';
                var $footer = $tbl.find('tfoot tr');
                $footer.find('td').eq(1).text(spend > 0 ? fmtNum(spend, 0) : '-');
                $footer.find('td').eq(2).text(leads);
                $footer.find('td').eq(3).text(cpl);
                $footer.find('td').eq(4).text(genuine);
                $footer.find('td').eq(5).text(genuinePct + (genuinePct !== '-' ? '%' : ''));
            });
        }
        
        $('.campaign-filter, .adset-filter, .ad-filter').on('changed.bs.select change', function() {
            leadsTable.draw();
            filterSummaryTables();
        });
        
        // Apply campaign filter to summary tables on initial load
        filterSummaryTables();
    } catch (e) {
        console.error('Error initializing DataTable:', e);
    }
    <?php endif; ?>
    
    // Load forms on page load if page is already selected
    var selectedPageId = $('#page_id').val();
    if(selectedPageId) {
        $('#page_id').trigger('change');
    }
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
    </script>
</body>
</html>
