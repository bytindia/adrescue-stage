<?php 
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
ini_set('display_errors', 0);
// Client configurations
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
include $dirPath.'config.php';
//include 'header.php'; 
$clients = [
    'rwd' => [
        'name' => 'RWD',
        'page_id' => '507904945740093',
        'spreadsheet_id' => '1q-Pyr4Pqepv9wPo6T_5RqkM8iQjwRnlZY1b7Ad1bzX8',
        'sheet_tab' => 'Feedback -FB',
        'feedback_column' => 'I', // Column index 8 (0-based)
        'categories' => ['Qualified', 'Follow Up', 'RNR', 'SV', 'Drop'],
        'ad_account_id' => '665661671485180'
    ],
    'vibrant' => [
        'name' => 'Vibrant',
        'page_id' => '111002298510113',
        'spreadsheet_id' => '1_S2XqcuqqnNBgZdB2nUFMYqMhe0g2rAUemP_ja1Huhs',
        'sheet_tab' => 'MAY 2024',
        'feedback_column' => 'L', // Column index 11 (0-based)
        'categories' => ['Qualified', 'Follow Up', 'RNR', 'SV', 'Drop'],
        'ad_account_id' => '615262527033827'
    ],
    'edenpark' => [
        'name' => 'Eden Park',
        'page_id' => '133323657231697',
        'spreadsheet_id' => '1_IhZpagR02JRyzKW7HwHN1oXj0ml3lHtICV6Baz6ENw',
        'sheet_tab' => 'Meta',
        'feedback_column' => 'J', // Column index 9 (0-based)
        'categories' => ['Qualified', 'Follow Up', 'RNR', 'SV', 'Drop'],
        'ad_account_id' => '605103317327861'
    ]
];

// OpenAI API Key
$OPENAI_API_KEY = getenv('OPENAI_API_KEY');

// Get selected client (default: rwd)
$selected_client = isset($_GET['client']) ? $_GET['client'] : 'rwd';
if (!isset($clients[$selected_client])) {
    $selected_client = 'rwd';
}
$client_config = $clients[$selected_client];

// Date range filter - default to this month
$dt_q = '';
if(isset($_GET['st']) && $_GET['st']!='') { 
    $stDt = $_GET['st']; 
    $enDt = $_GET['en']; 
    $dtRange = $_GET['st'].' - '.$_GET['en']; 
    $dtRange1 = str_replace('/', '-', $_GET['st']); 
    $dtRange2 = str_replace('/', '-', $_GET['en']);
    $urlParam = '?client='.$selected_client.'&st='.$stDt.'&en='.$enDt;
} else { 
    $d = new DateTime('first day of this month');
    $stDt = $d->format('d/m/Y');
    $enDt = date('d/m/Y'); 
    $dtRange = 'this month';
    $dtRange1 = $d->format('Y-m-d'); 
    $dtRange2 = date('Y-m-d');
    $urlParam = '?client='.$selected_client;
}

// Form and Campaign filters
$form_filter = isset($_GET['form']) && $_GET['form'] != '' ? $_GET['form'] : '';
$campaign_filter = isset($_GET['campaign']) && $_GET['campaign'] != '' ? $_GET['campaign'] : '';

//Auth();
$pgHeadline = 'Leads Feedback Dashboard';
$pgID = 8;
$err = '';

// Google Sheets API setup
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';

// Meta API helper functions


function LeadGen($arr, $filt) {
    $r = 0;
    if(is_array($arr) || is_object($arr) && count($arr)>0) {
        for($q=0; $q<count($arr); $q++) {
            if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
        }
    }
    return $r;
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

// Convert column letter to index (A=0, B=1, etc.)
function columnLetterToIndex($letter) {
    $letter = strtoupper($letter);
    $index = 0;
    for ($i = 0; $i < strlen($letter); $i++) {
        $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
    }
    return $index - 1;
}

// Create feedback mapping cache table if it doesn't exist
function createFeedbackMappingTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS feedback_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_key VARCHAR(50) NOT NULL,
        original_feedback TEXT NOT NULL,
        categorized_feedback VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_mapping (client_key, original_feedback(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql);
}

// Get cached feedback mapping
function getCachedFeedbackMapping($conn, $client_key, $original_feedback) {
    $original_feedback_escaped = mysqli_real_escape_string($conn, $original_feedback);
    $client_key_escaped = mysqli_real_escape_string($conn, $client_key);
    $query = "SELECT categorized_feedback FROM feedback_mapping 
              WHERE client_key = '$client_key_escaped' 
              AND original_feedback = '$original_feedback_escaped' 
              LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['categorized_feedback'];
    }
    return null;
}

// Save feedback mapping to cache
function saveFeedbackMapping($conn, $client_key, $original_feedback, $categorized_feedback) {
    $original_feedback_escaped = mysqli_real_escape_string($conn, $original_feedback);
    $categorized_feedback_escaped = mysqli_real_escape_string($conn, $categorized_feedback);
    $client_key_escaped = mysqli_real_escape_string($conn, $client_key);
    $query = "INSERT INTO feedback_mapping (client_key, original_feedback, categorized_feedback) 
              VALUES ('$client_key_escaped', '$original_feedback_escaped', '$categorized_feedback_escaped')
              ON DUPLICATE KEY UPDATE categorized_feedback = '$categorized_feedback_escaped'";
    mysqli_query($conn, $query);
}

// Batch categorize feedback using OpenAI
function categorizeFeedbackBatch($openai_api_key, $client_key, $categories, $unique_feedback_values) {
    if (empty($unique_feedback_values)) {
        return [];
    }
    
    // Build prompt for OpenAI
    $categories_str = implode(', ', $categories);
    $feedback_list = implode("\n", array_map(function($fb, $idx) {
        return ($idx + 1) . ". " . addslashes($fb);
    }, $unique_feedback_values, array_keys($unique_feedback_values)));
    
    $prompt = "You are a lead feedback categorization system. Categorize each feedback text into one of these 5 categories: {$categories_str}\n\n";
    $prompt .= "Rules:\n";
    $prompt .= "1. If the feedback text exactly matches one of the categories (case-insensitive), return that category name exactly as shown in the list.\n";
    $prompt .= "2. If the feedback is similar in meaning to a category, map it to that category.\n";
    $prompt .= "3. Common mappings:\n";
    $prompt .= "   - 'Qualified', 'Qualify', 'Qualifying' -> Qualified\n";
    $prompt .= "   - 'Follow Up', 'Followup', 'Follow-up', 'Follow Up Required' -> Follow Up\n";
    $prompt .= "   - 'RNR', 'Not Reachable', 'Not Responding', 'Not Contactable' -> RNR\n";
    $prompt .= "   - 'SV', 'Site Visit', 'Site Visit Done', 'SV Done', 'SV - failed' -> SV\n";
    $prompt .= "   - 'Drop', 'Unqualified', 'Rejected', 'Not Interested' -> Drop\n";
    $prompt .= "4. Return the response as a JSON object where keys are the original feedback texts (exactly as provided) and values are one of the 5 categories.\n\n";
    $prompt .= "Feedback texts to categorize:\n{$feedback_list}\n\n";
    $prompt .= "Return ONLY valid JSON in this exact format: {\"feedback text 1\": \"category\", \"feedback text 2\": \"category\", ...}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Reduced timeout to 20 seconds
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key
    ]);
    
    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that categorizes lead feedback into predefined categories. Always return valid JSON only, no explanations or markdown.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.2,
        'max_tokens' => 3000
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("OpenAI API Error: HTTP $http_code - $response - CURL Error: $curl_error");
        return [];
    }
    
    $response_data = json_decode($response, true);
    if (!isset($response_data['choices'][0]['message']['content'])) {
        error_log("OpenAI API Error: Invalid response format - " . print_r($response_data, true));
        return [];
    }
    
    $content = trim($response_data['choices'][0]['message']['content']);
    // Remove markdown code blocks if present
    $content = preg_replace('/^```json\s*/', '', $content);
    $content = preg_replace('/^```\s*/', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);
    $content = trim($content);
    
    // Try to extract JSON from the response
    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $content = $matches[0];
    }
    
    $mapping = json_decode($content, true);
    if (!is_array($mapping)) {
        error_log("OpenAI API Error: Failed to parse JSON response. Content: " . substr($content, 0, 500));
        return [];
    }
    
    return $mapping;
}

$feedback_col_index = columnLetterToIndex($client_config['feedback_column']);

// Convert DD/MM/YYYY to YYYY-MM-DD
$stDt_parts = explode('/', $stDt);
$enDt_parts = explode('/', $enDt);
$date_start = $stDt_parts[2].'-'.$stDt_parts[1].'-'.$stDt_parts[0].' 00:00:00';
$date_end = $enDt_parts[2].'-'.$enDt_parts[1].'-'.$enDt_parts[0].' 23:59:59';
$date_start_meta = $stDt_parts[2].'-'.$stDt_parts[1].'-'.$stDt_parts[0];
$date_end_meta = $enDt_parts[2].'-'.$enDt_parts[1].'-'.$enDt_parts[0];

// Fetch Meta API data (spend and leads)
$meta_spend = 0;
$meta_leads = 0;
$meta_cpl = 0;

// Check if config.php variables are available
if (file_exists($dirPath.'config.php')) {
    // Get access token from config
    $query_meta = "SELECT access_token FROM users WHERE tbl_id=2";
    $result_meta = mysqli_query($conn, $query_meta);
    if ($result_meta && mysqli_num_rows($result_meta) > 0) {
        $row_meta = mysqli_fetch_assoc($result_meta);
        $access_token = $row_meta['access_token'];
        //$api_ver = 'v18.0'; // Default API version
        
        if (!empty($access_token) && !empty($client_config['ad_account_id'])) {
            $obj_arr = [
                'POST_ENGAGEMENT' => 'post_engagement', 
                'LINK_CLICKS' => 'link_click',
                'VIDEO_VIEWS' => 'video_view',
                'LEAD_GENERATION' => 'lead', 
                'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
                'MESSAGES' => 'onsite_conversion.messaging_block',
                'OUTCOME_LEADS' => 'lead'
            ];
            
            $url = "https://graph.facebook.com/".$api_ver."/act_".$client_config['ad_account_id']."/insights?level=campaign&fields=campaign_id,campaign_name,spend,objective,actions&time_range[since]=".$date_start_meta."&time_range[until]=".$date_end_meta."&access_token=".$access_token."&limit=1750";
            $req = file_get_contents_curl($url);
            $res = json_decode($req, true);
            //d($res);
            if(isset($res['data']) && is_array($res['data'])) {
                foreach($res['data'] as $campaign) {
                    if(isset($campaign['spend'])) {
                        $meta_spend += floatval($campaign['spend']);
                    }
                    
                    $lead = 0;
                    if(isset($campaign['actions']) && isset($campaign['objective']) && array_key_exists($campaign['objective'], $obj_arr)) {
                        $lead = LeadGen($campaign['actions'], $obj_arr[$campaign['objective']]);
                    }
                    $meta_leads += $lead;
                }
            }
            
            // Calculate CPL
            if($meta_leads > 0) {
                $meta_cpl = round($meta_spend / $meta_leads);
            }
        }
    }
}

// Fetch leads from database

$leads_query = "SELECT * FROM leads WHERE page_id='".$client_config['page_id']."' AND created >= '".$date_start."' AND created <= '".$date_end."'";
if ($form_filter != '') {
    $leads_query .= " AND formN LIKE '%".mysqli_real_escape_string($conn, $form_filter)."%'";
}
if ($campaign_filter != '') {
    $leads_query .= " AND campN LIKE '%".mysqli_real_escape_string($conn, $campaign_filter)."%'";
}
$leads_query .= " ORDER BY created DESC";

$leads_result = mysqli_query($conn, $leads_query);
$raw_leads = [];
$leads_by_email = [];
$leads_by_phone = [];

while($row = mysqli_fetch_assoc($leads_result)) {
    $lead_data = unserialize($row['lead']);
    $email = isset($lead_data['email']) ? strtolower(trim($lead_data['email'])) : '';
    $phone = isset($lead_data['phone_number']) ? preg_replace('/[^0-9]/', '', $lead_data['phone_number']) : '';
    
    // Normalize phone - keep last 10 digits
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    
    $lead_info = [
        'id' => $row['tbl_id'],
        'email' => $email,
        'phone' => $phone,
        'formN' => $row['formN'],
        'campN' => $row['campN'],
        'created' => $row['created'],
        'lead_data' => $lead_data
    ];
    
    $raw_leads[] = $lead_info;
    
    if ($email != '') {
        if (!isset($leads_by_email[$email])) {
            $leads_by_email[$email] = [];
        }
        $leads_by_email[$email][] = $lead_info;
    }
    
    if ($phone != '' && strlen($phone) >= 10) {
        if (!isset($leads_by_phone[$phone])) {
            $leads_by_phone[$phone] = [];
        }
        $leads_by_phone[$phone][] = $lead_info;
    }
}

// Fetch feedback from Google Sheets
$feedback_data = [];
try {
    $uId = 2;
    $sheet_data = read_sheet($uId, $client_config['spreadsheet_id'], $client_config['sheet_tab']);
    
    if (isset($sheet_data['data']) && is_array($sheet_data['data'])) {
        // Skip header row if needed
        $rows = $sheet_data['data'];
        if (count($rows) > 0) {
            // Find email and phone columns (usually in first few columns)
            $email_col = -1;
            $phone_col = -1;
            
            // Check first row for headers
            $first_row = $rows[0];
            foreach ($first_row as $idx => $val) {
                $val_lower = strtolower(trim($val));
                if (strpos($val_lower, 'email') !== false && $email_col == -1) {
                    $email_col = $idx;
                }
                if ((strpos($val_lower, 'phone') !== false || strpos($val_lower, 'mobile') !== false) && $phone_col == -1) {
                    $phone_col = $idx;
                }
            }
            
            // If not found in header, assume common positions (email: col 2, phone: col 2 or 3)
            if ($email_col == -1) $email_col = 2;
            if ($phone_col == -1) $phone_col = 2;
            
            // Process data rows
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (!isset($row[$email_col]) && !isset($row[$phone_col])) continue;
                
                $sheet_email = isset($row[$email_col]) ? strtolower(trim($row[$email_col])) : '';
                $sheet_phone = isset($row[$phone_col]) ? preg_replace('/[^0-9]/', '', $row[$phone_col]) : '';
                
                if (strlen($sheet_phone) > 10) {
                    $sheet_phone = substr($sheet_phone, -10);
                }
                
                $feedback = isset($row[$feedback_col_index]) ? trim($row[$feedback_col_index]) : '';
                
                if (($sheet_email != '' || ($sheet_phone != '' && strlen($sheet_phone) >= 10)) && $feedback != '') {
                    $feedback_data[] = [
                        'email' => $sheet_email,
                        'phone' => $sheet_phone,
                        'feedback' => $feedback
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    $err = 'Error fetching Google Sheets data: ' . $e->getMessage();
}

// Match leads with feedback
$matched_leads = [];
foreach ($raw_leads as $lead) {
    $matched = false;
    $feedback_value = '';
    
    // Try matching by email first
    if ($lead['email'] != '' && isset($leads_by_email[$lead['email']])) {
        foreach ($feedback_data as $fb) {
            if ($fb['email'] != '' && strtolower(trim($fb['email'])) == $lead['email']) {
                $feedback_value = $fb['feedback'];
                $matched = true;
                break;
            }
        }
    }
    
    // Try matching by phone if not matched
    if (!$matched && $lead['phone'] != '' && strlen($lead['phone']) >= 10) {
        foreach ($feedback_data as $fb) {
            if ($fb['phone'] != '' && $fb['phone'] == $lead['phone']) {
                $feedback_value = $fb['feedback'];
                $matched = true;
                break;
            }
        }
    }
    
    $matched_leads[] = [
        'lead' => $lead,
        'feedback' => $feedback_value,
        'matched' => $matched
    ];
}

// Create feedback mapping table if it doesn't exist
createFeedbackMappingTable($conn);

// Collect all unique feedback values from Google Sheet
$unique_feedback_values_raw = [];
foreach ($feedback_data as $fb) {
    $feedback = trim($fb['feedback']);
    if ($feedback != '' && !in_array($feedback, $unique_feedback_values_raw)) {
        $unique_feedback_values_raw[] = $feedback;
    }
}

// Check cache and categorize feedback
$feedback_mapping = [];
$uncached_feedback = [];

foreach ($unique_feedback_values_raw as $original_feedback) {
    // Check if feedback already matches a category exactly (case-insensitive)
    $matched_category = null;
    foreach ($client_config['categories'] as $category) {
        if (strcasecmp(trim($original_feedback), $category) === 0) {
            $matched_category = $category;
            break;
        }
    }
    
    if ($matched_category !== null) {
        // Feedback already matches a category, use it directly
        $feedback_mapping[$original_feedback] = $matched_category;
        // Save to cache for future use
        saveFeedbackMapping($conn, $selected_client, $original_feedback, $matched_category);
    } else {
        // Check cache
        $cached = getCachedFeedbackMapping($conn, $selected_client, $original_feedback);
        if ($cached !== null) {
            $feedback_mapping[$original_feedback] = $cached;
        } else {
            $uncached_feedback[] = $original_feedback;
        }
    }
}

// Batch categorize uncached feedback using OpenAI (split into chunks to avoid timeout)
if (!empty($uncached_feedback)) {
    $batch_size = 25; // Process 25 feedback items at a time to avoid timeout
    $chunks = array_chunk($uncached_feedback, $batch_size);
    $all_openai_mapping = [];
    
    foreach ($chunks as $chunk) {
        $openai_mapping = categorizeFeedbackBatch($OPENAI_API_KEY, $selected_client, $client_config['categories'], $chunk);
        if (!empty($openai_mapping)) {
            $all_openai_mapping = array_merge($all_openai_mapping, $openai_mapping);
        }
    }
    
    // Save to cache and merge with existing mapping
    foreach ($uncached_feedback as $original_fb) {
        $categorized = isset($all_openai_mapping[$original_fb]) ? $all_openai_mapping[$original_fb] : $client_config['categories'][0]; // Default to first category
        // Ensure categorized value is one of the allowed categories
        if (!in_array($categorized, $client_config['categories'])) {
            $categorized = $client_config['categories'][0]; // Default to first category if not valid
        }
        $feedback_mapping[$original_fb] = $categorized;
        saveFeedbackMapping($conn, $selected_client, $original_fb, $categorized);
    }
}

// Get unique categorized feedback values (these will be the 5 categories)
$unique_feedback_values = $client_config['categories'];

// Calculate summaries with dynamic feedback categories
$form_summary = [];
$campaign_summary = [];

// Store leads by category for modal display
$leads_by_category = [];
foreach ($unique_feedback_values as $fb_val) {
    $leads_by_category[$fb_val] = [];
}

foreach ($matched_leads as $item) {
    $lead = $item['lead'];
    $original_feedback = trim($item['feedback']);
    
    // Get categorized feedback from mapping
    $categorized_feedback = '';
    if ($original_feedback != '') {
        if (isset($feedback_mapping[$original_feedback])) {
            $categorized_feedback = $feedback_mapping[$original_feedback];
        } else {
            // If not in mapping, try to get from cache
            $cached = getCachedFeedbackMapping($conn, $selected_client, $original_feedback);
            if ($cached !== null) {
                $categorized_feedback = $cached;
                $feedback_mapping[$original_feedback] = $cached;
            } else {
                // Default to first category if not found
                $categorized_feedback = $client_config['categories'][0];
            }
        }
    }
    
    // Store lead details for modal if it has categorized feedback
    if ($categorized_feedback != '' && in_array($categorized_feedback, $unique_feedback_values)) {
        $lead_data_modal = $lead['lead_data'];
        $name = '';
        if (isset($lead_data_modal['full_name'])) {
            $name = $lead_data_modal['full_name'];
        } elseif (isset($lead_data_modal['name'])) {
            $name = $lead_data_modal['name'];
        } elseif (isset($lead_data_modal['first_name']) && isset($lead_data_modal['last_name'])) {
            $name = $lead_data_modal['first_name'] . ' ' . $lead_data_modal['last_name'];
        }
        
        $leads_by_category[$categorized_feedback][] = [
            'name' => $name,
            'email' => isset($lead_data_modal['email']) ? $lead_data_modal['email'] : '',
            'phone' => isset($lead_data_modal['phone_number']) ? $lead_data_modal['phone_number'] : (isset($lead_data_modal['phone']) ? $lead_data_modal['phone'] : ''),
            'feedback_sheet' => $original_feedback,
            'feedback_ai' => $categorized_feedback
        ];
    }
    
    $form = $lead['formN'];
    $campaign = $lead['campN'];
    
    // Initialize form summary with categories
    if (!isset($form_summary[$form])) {
        $form_summary[$form] = ['total' => 0];
        foreach ($unique_feedback_values as $fb_val) {
            $form_summary[$form][$fb_val] = 0;
        }
    }
    
    // Initialize campaign summary with categories
    if (!isset($campaign_summary[$campaign])) {
        $campaign_summary[$campaign] = ['total' => 0];
        foreach ($unique_feedback_values as $fb_val) {
            $campaign_summary[$campaign][$fb_val] = 0;
        }
    }
    
    // Increment totals
    $form_summary[$form]['total']++;
    $campaign_summary[$campaign]['total']++;
    
    // Increment categorized feedback count
    if ($categorized_feedback != '' && in_array($categorized_feedback, $unique_feedback_values)) {
        $form_summary[$form][$categorized_feedback]++;
        $campaign_summary[$campaign][$categorized_feedback]++;
    }
}

// Get unique forms and campaigns for filters
$unique_forms = [];
$unique_campaigns = [];
foreach ($raw_leads as $lead) {
    if ($lead['formN'] != '' && !in_array($lead['formN'], $unique_forms)) {
        $unique_forms[] = $lead['formN'];
    }
    if ($lead['campN'] != '' && !in_array($lead['campN'], $unique_campaigns)) {
        $unique_campaigns[] = $lead['campN'];
    }
}
sort($unique_forms);
sort($unique_campaigns);

// Calculate totals
$total_leads = count($raw_leads);
$total_feedback = [];
foreach ($unique_feedback_values as $fb_val) {
    $total_feedback[$fb_val] = 0;
}

foreach ($form_summary as $form => $data) {
    foreach ($unique_feedback_values as $fb_val) {
        if (isset($data[$fb_val])) {
            $total_feedback[$fb_val] += $data[$fb_val];
        }
    }
}

?>
<style>
.client-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 10px;
}
.client-nav a {
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s;
    font-weight: 500;
}
.client-nav a.active {
    background: #6c5ce7;
    color: white;
}
.client-nav a:not(.active) {
    background: #f0f0f0;
    color: #333;
}
.client-nav a:not(.active):hover {
    background: #e0e0e0;
}
.filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.kpi-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    transition: all 0.3s ease;
}
.kpi-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}
.kpi-card h3 {
    margin: 0;
    font-size: 24px;
    color: #333;
}
.kpi-card p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}
.summary-table {
    margin-top: 20px;
}
.dt-buttons {
    margin-bottom: 15px;
}
.dt-buttons .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}
/* Loading Overlay */
#loadingOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.95);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
#loadingOverlay .spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #6c5ce7;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
#loadingOverlay .loading-text {
    font-size: 18px;
    color: #333;
    font-weight: 500;
}
#loadingOverlay.hidden {
    display: none;
}
</style>

<Title>AdRescue - Ads </Title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">

<!-- jQuery -->
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
   <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js" charset="utf-8"></script>
    <!-- Custom Theme Scripts -->
    
    <link href="//cdn.datatables.net/buttons/1.5.6/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <!-- Custom Theme Style -->
    <link href="/web/pagination.css" rel="stylesheet">
    <link href="/assets/css/pagination.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/casa/css/style.css" />
    <link rel="stylesheet" type="text/css" href="/casa/style.css" />
    <style>
        th:first-child, td:first-child, stickyCls
        {
            position:sticky;
            left:0px;
            background-color: #fff;
        }
        h2 { text-align: center; }
        table, td, strong { font-size: 17px; }
        table.table td {
            text-align: center; 
        }
        td {
            background-color: #fff;
        }
    </style>
<body class="nav-md">
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Loading dashboard data...</div>
    </div>
    
    <div class="container body">
      <div class="main_container">
        <?php 
           // include 'menu-left.php';
          //  include 'menu-top.php'; 
        ?>

        <!-- page content -->
         <div class="right_col" role="main">
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                    <?php include 'alert.php'; ?>
                    
                    <!-- Client Navigation -->
                    <div class="client-nav">
                        <?php foreach ($clients as $key => $client): ?>
                            <a href="?client=<?php echo $key; ?><?php echo isset($_GET['st']) ? '&st='.$_GET['st'].'&en='.$_GET['en'] : ''; ?><?php echo $form_filter != '' ? '&form='.urlencode($form_filter) : ''; ?><?php echo $campaign_filter != '' ? '&campaign='.urlencode($campaign_filter) : ''; ?>" 
                               class="<?php echo $selected_client == $key ? 'active' : ''; ?>">
                                <?php echo $client['name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-section">
                        <form method="GET" action="" id="filterForm">
                            <input type="hidden" name="client" value="<?php echo $selected_client; ?>">
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label>Start Date</label>
                                    <input type="text" name="st" id="startDate" class="form-control" 
                                           value="<?php echo $stDt; ?>" placeholder="DD/MM/YYYY">
                                </div>
                                <div class="col-md-2">
                                    <label>End Date</label>
                                    <input type="text" name="en" id="endDate" class="form-control" 
                                           value="<?php echo $enDt; ?>" placeholder="DD/MM/YYYY">
                                </div>
                                <div class="col-md-3">
                                    <label>Form</label>
                                    <select name="form" class="form-control">
                                        <option value="">All Forms</option>
                                        <?php foreach ($unique_forms as $form): ?>
                                            <option value="<?php echo htmlspecialchars($form); ?>" 
                                                    <?php echo $form_filter == $form ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($form); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Campaign</label>
                                    <select name="campaign" class="form-control">
                                        <option value="">All Campaigns</option>
                                        <?php foreach ($unique_campaigns as $campaign): ?>
                                            <option value="<?php echo htmlspecialchars($campaign); ?>" 
                                                    <?php echo $campaign_filter == $campaign ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($campaign); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2" style="display: flex; align-items: flex-end; padding-bottom: 15px;">
                                    <button type="submit" class="btn btn-primary" style="margin-right: 5px;">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                    <a href="?client=<?php echo $selected_client; ?>" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- WhatsApp Share Button -->
                    <div style="text-align: right; margin-bottom: 20px;">
                        <?php
                        // Format date range for WhatsApp message (dd-mm-yy)
                        $date_start_formatted = date('d-m-y', strtotime($date_start));
                        $date_end_formatted = date('d-m-y', strtotime($date_end));
                        
                        // Build WhatsApp message
                        $whatsapp_message = "*" . $client_config['name'] . " - Spends & Feedback*\n";
                        $whatsapp_message .= "_" . $date_start_formatted . " to " . $date_end_formatted . "_\n\n";
                        $whatsapp_message .= "Total Spend: ₹" . moneyFormatIndia(round($meta_spend)) . "\n";
                        $whatsapp_message .= "Leads: " . $total_leads . "\n";
                        $whatsapp_message .= "CPL: ₹" . moneyFormatIndia($meta_cpl) . "\n\n";
                        
                        // Add feedback categories
                        foreach ($unique_feedback_values as $fb_val) {
                            $count = isset($total_feedback[$fb_val]) ? $total_feedback[$fb_val] : 0;
                            $whatsapp_message .= $fb_val . ": " . $count . "\n";
                        }
                        
                        $whatsapp_message .= "\nAd Account ID: " . $client_config['ad_account_id'];
                        
                        $whatsapp_url = "https://wa.me/?text=" . urlencode($whatsapp_message);
                        ?>
                        <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-success" style="background-color: #25D366; border-color: #25D366;">
                            <i class="fa fa-whatsapp"></i> Share WhatsApp
                        </a>
                    </div>
                    
                    <!-- KPI Cards -->
                    <div class="row">
                        <div class="col-md-2">
                            <div class="kpi-card">
                                <h3>₹<?php echo moneyFormatIndia(round($meta_spend)); ?></h3>
                                <p>SPENDS</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card">
                                <h3><?php echo $total_leads; ?></h3>
                                <p>LEADS TOTAL</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card">
                                <h3>₹<?php echo moneyFormatIndia($meta_cpl); ?></h3>
                                <p>CPL</p>
                            </div>
                        </div>
                        <?php 
                        // Show all 5 feedback categories as KPI cards
                        foreach ($unique_feedback_values as $fb_val): 
                        ?>
                        <div class="col-md-2">
                            <div class="kpi-card" style="cursor: pointer;" onclick="openFeedbackModal('<?php echo htmlspecialchars($fb_val); ?>')">
                                <h3><?php echo isset($total_feedback[$fb_val]) ? $total_feedback[$fb_val] : 0; ?></h3>
                                <p><?php echo htmlspecialchars($fb_val); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Form-wise Summary -->
                    <div class="summary-table">
                        <h3><i class="fa fa-file-text"></i> Form-wise Summary</h3>
                        <table id="formTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>FORM</th>
                                    <th>TOTAL LEADS</th>
                                    <?php foreach ($unique_feedback_values as $fb_val): ?>
                                        <th><?php echo htmlspecialchars($fb_val); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($form_summary as $form => $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($form); ?></td>
                                        <td><?php echo $data['total']; ?></td>
                                        <?php foreach ($unique_feedback_values as $fb_val): ?>
                                            <td><?php echo isset($data[$fb_val]) ? $data[$fb_val] : 0; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: bold;">
                                    <td>TOTAL</td>
                                    <td><?php echo $total_leads; ?></td>
                                    <?php foreach ($unique_feedback_values as $fb_val): ?>
                                        <td><?php echo isset($total_feedback[$fb_val]) ? $total_feedback[$fb_val] : 0; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Campaign-wise Summary -->
                    <div class="summary-table" style="margin-top: 30px;">
                        <h3><i class="fa fa-clock-o"></i> Campaign-wise Summary</h3>
                        <table id="campaignTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>CAMPAIGN</th>
                                    <th>TOTAL LEADS</th>
                                    <?php foreach ($unique_feedback_values as $fb_val): ?>
                                        <th><?php echo htmlspecialchars($fb_val); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaign_summary as $campaign => $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($campaign); ?></td>
                                        <td><?php echo $data['total']; ?></td>
                                        <?php foreach ($unique_feedback_values as $fb_val): ?>
                                            <td><?php echo isset($data[$fb_val]) ? $data[$fb_val] : 0; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: bold;">
                                    <td>TOTAL</td>
                                    <td><?php echo $total_leads; ?></td>
                                    <?php foreach ($unique_feedback_values as $fb_val): ?>
                                        <td><?php echo isset($total_feedback[$fb_val]) ? $total_feedback[$fb_val] : 0; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>

<script>
// Hide loading overlay when page is fully loaded
window.addEventListener('load', function() {
    setTimeout(function() {
        var overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
    }, 500); // Small delay to ensure smooth transition
});

$(document).ready(function() {
    // Hide loading overlay when DOM is ready
    setTimeout(function() {
        $('#loadingOverlay').addClass('hidden');
    }, 300);
    
    // Initialize DataTables with download buttons
    $('#formTable').DataTable({
        "pageLength": 50,
        "searching": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": [[1, "desc"]],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="fa fa-file-text-o"></i> CSV',
                title: 'Form-wise Summary - <?php echo $client_config['name']; ?>',
                className: 'btn btn-sm btn-info'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                title: 'Form-wise Summary - <?php echo $client_config['name']; ?>',
                className: 'btn btn-sm btn-success'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                title: 'Form-wise Summary - <?php echo $client_config['name']; ?>',
                className: 'btn btn-sm btn-danger',
                orientation: 'landscape',
                pageSize: 'A4'
            },
            {
                extend: 'copyHtml5',
                text: '<i class="fa fa-copy"></i> Copy',
                className: 'btn btn-sm btn-default'
            }
        ]
    });
    
    $('#campaignTable').DataTable({
        "pageLength": 50,
        "searching": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": [[1, "desc"]],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="fa fa-file-text-o"></i> CSV',
                title: 'Campaign-wise Summary - <?php echo $client_config['name']; ?>',
                className: 'btn btn-sm btn-info'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                title: 'Campaign-wise Summary - <?php echo $client_config['name']; ?>',
                className: 'btn btn-sm btn-success'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                title: 'Campaign-wise Summary - <?php echo $client_config['name']; ?>',
                className: 'btn btn-sm btn-danger',
                orientation: 'landscape',
                pageSize: 'A4'
            },
            {
                extend: 'copyHtml5',
                text: '<i class="fa fa-copy"></i> Copy',
                className: 'btn btn-sm btn-default'
            }
        ]
    });
    
    // Set default dates to this month
    var d = new Date();
    var firstDay = new Date(d.getFullYear(), d.getMonth(), 1);
    var lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    
    var startDateVal = '<?php echo $stDt; ?>';
    var endDateVal = '<?php echo $enDt; ?>';
    
    // Date picker for start date
    $('#startDate').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        format: 'DD/MM/YYYY',
        locale: {
            format: 'DD/MM/YYYY',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        }
    }, function(start) {
        $('#startDate').val(start.format('DD/MM/YYYY'));
    });
    
    // Date picker for end date
    $('#endDate').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        format: 'DD/MM/YYYY',
        locale: {
            format: 'DD/MM/YYYY',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        }
    }, function(end) {
        $('#endDate').val(end.format('DD/MM/YYYY'));
    });
    
    // Set initial values
    if (startDateVal) {
        $('#startDate').data('daterangepicker').setStartDate(moment(startDateVal, 'DD/MM/YYYY'));
    }
    if (endDateVal) {
        $('#endDate').data('daterangepicker').setStartDate(moment(endDateVal, 'DD/MM/YYYY'));
    }
});

// Leads data for modal
var leadsByCategory = <?php echo json_encode($leads_by_category); ?>;

// Function to open feedback modal
function openFeedbackModal(category) {
    // Get leads for this specific category
    var leads = leadsByCategory[category];
    if (!leads) {
        leads = [];
    }
    
    var modal = $('#feedbackModal');
    var table = $('#feedbackModalTable');
    
    // Set modal title
    $('#feedbackModalLabel').text(category + ' - Leads Details');
    
    // Destroy existing DataTable completely - must be done first
    if ($.fn.DataTable.isDataTable('#feedbackModalTable')) {
        try {
            var dt = table.DataTable();
            dt.clear();
            dt.destroy();
        } catch(e) {
            // If destroy fails, force remove
            table.removeClass('dataTable');
            table.find('.dataTables_wrapper').remove();
        }
    }
    
    // Get fresh reference to tbody and clear it completely
    var tableBody = table.find('tbody');
    tableBody.empty();
    
    // Populate table with new data for this category
    if (leads.length === 0) {
        tableBody.append('<tr><td colspan="5" class="text-center">No leads found for this category</td></tr>');
    } else {
        leads.forEach(function(lead) {
            var name = lead.name || '-';
            var email = lead.email || '-';
            var phone = lead.phone || '-';
            var feedbackSheet = lead.feedback_sheet || '-';
            var feedbackAI = lead.feedback_ai || '-';
            
            var row = '<tr>' +
                '<td>' + name + '</td>' +
                '<td>' + email + '</td>' +
                '<td>' + phone + '</td>' +
                '<td>' + feedbackSheet + '</td>' +
                '<td>' + feedbackAI + '</td>' +
                '</tr>';
            tableBody.append(row);
        });
    }
    
    // Show modal
    modal.modal('show');
    
    // Initialize DataTable after modal is fully shown
    modal.one('shown.bs.modal', function() {
        // Use one() to ensure this only runs once per modal show
        if (!$.fn.DataTable.isDataTable('#feedbackModalTable')) {
            table.DataTable({
                "pageLength": 25,
                "searching": true,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "order": [[0, "asc"]],
                "destroy": true
            });
        }
    });
}

// Clean up DataTable when modal is closed
$('#feedbackModal').on('hidden.bs.modal', function () {
    var table = $('#feedbackModalTable');
    if ($.fn.DataTable.isDataTable('#feedbackModalTable')) {
        try {
            table.DataTable().clear().destroy();
        } catch(e) {
            console.log('Error destroying DataTable:', e);
        }
    }
    // Clear the table body
    table.find('tbody').empty();
});
</script>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="feedbackModalLabel">Leads Details</h4>
            </div>
            <div class="modal-body">
                <table id="feedbackModalTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Feedback (Sheet)</th>
                            <th>Feedback (AI)</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>