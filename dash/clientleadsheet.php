<?php
date_default_timezone_set('Asia/Kolkata');



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================
// DATABASE CONNECTION CODE
// ============================================
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'digitalb2k_adsninja', getenv('DB_PASS'), 'digitalb2k_adsninja');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($conn, 'digitalb2k_adsninja');
$clients = [
    'dra' => [
        'name' => 'DRA',
        'page_id' => '507904945740093',
        'ad_account_id' => '1054629569707600',
        'csv_map' => [
            'name' => 0,      // A
            'email' => 1,     // B
            'phone' => 2,     // C
            'feedback' => 9   // J
        ]
    ],
    'rld' => [
        'name' => 'RLD',
        'page_id' => '876265075561810',
        'ad_account_id' => '1866059083840423',
        'csv_map' => [
            'name' => 0,      // A
            'email' => 1,     // B
            'phone' => 2,     // C
            'feedback' => 10  // K
        ]
    ],
    'iyra' => [
        'name' => 'IYRA',
        'page_id' => '957263474126805',
        'ad_account_id' => '6473800839396603',
        'csv_map' => [
            'name' => 3,      // D
            'email' => 6,     // G
            'phone' => 4,     // E
            'feedback' => 9   // J
        ]
    ]
];



// CSV Upload and Processing
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file']) && $client_config) {
    if ($_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $file_name = $_FILES['csv_file']['name'];

        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        if (strtolower($ext) != 'csv') {
            $err = 'Please upload a CSV file only!';
        } else {
            // 1. Fetch ALL leads for this page_id first to build an in-memory lookup map
            // This is much faster/efficient than querying for every row
            $page_id = $client_config['page_id'];
            $page_id_escaped = mysqli_real_escape_string($conn, $page_id);

            $leads_query = "SELECT * FROM leads WHERE page_id = '$page_id_escaped'";
            $leads_result = mysqli_query($conn, $leads_query);

            $leads_map_email = [];
            $leads_map_phone = [];
            $leads_map_name = []; // Note: Names might not be unique, but needed as fallback

            while ($lead_row = mysqli_fetch_assoc($leads_result)) {
                $lead_data = unserialize($lead_row['lead']);

                // Extract and normalize lead data
                $l_email = isset($lead_data['email']) ? normalizeEmail($lead_data['email']) : '';

                $l_phone = '';
                if (isset($lead_data['phone_number']))
                    $l_phone = normalizePhone($lead_data['phone_number']);
                elseif (isset($lead_data['phone']))
                    $l_phone = normalizePhone($lead_data['phone']);

                $l_name = '';
                if (isset($lead_data['full_name'])) {
                    $l_name = normalizeName($lead_data['full_name']);
                } elseif (isset($lead_data['name'])) {
                    $l_name = normalizeName($lead_data['name']);
                } elseif (isset($lead_data['first_name'])) {
                    $l_name = normalizeName($lead_data['first_name'] . ' ' . ($lead_data['last_name'] ?? ''));
                }

                // Store in maps (using ID as value to prevent duplicate overwrites, though we just need one match)
                if ($l_email)
                    $leads_map_email[$l_email] = $lead_row;
                if ($l_phone)
                    $leads_map_phone[$l_phone] = $lead_row;
                if ($l_name)
                    $leads_map_name[$l_name] = $lead_row;
            }

            // 2. Process CSV
            $handle = fopen($file, "r");
            $row_count = 0;
            $matched = 0;
            $updated = 0;
            $inserted = 0;
            $skipped = 0;

            // Config for columns
            $col_map = $client_config['csv_map'];
            $client_name = $client_config['name'];

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $row_count++;

                // Extract data using config map
                $raw_name = isset($row[$col_map['name']]) ? trim($row[$col_map['name']]) : '';
                $raw_email = isset($row[$col_map['email']]) ? trim($row[$col_map['email']]) : '';
                $raw_phone = isset($row[$col_map['phone']]) ? trim($row[$col_map['phone']]) : '';
                $raw_feedback = isset($row[$col_map['feedback']]) ? trim($row[$col_map['feedback']]) : '';

                // Basic Validation
                if (empty($raw_feedback)) {
                    $skipped++;
                    continue;
                } // Skip if no feedback

                // Normalize for matching
                $n_name = normalizeName($raw_name);
                $n_email = normalizeEmail($raw_email);
                $n_phone = normalizePhone($raw_phone);

                // 3. Find Match in Leads Map
                // Priority: Email, then Phone, then Name
                $matched_lead = null;

                if ($n_email && isset($leads_map_email[$n_email])) {
                    $matched_lead = $leads_map_email[$n_email];
                } elseif ($n_phone && isset($leads_map_phone[$n_phone])) {
                    $matched_lead = $leads_map_phone[$n_phone];
                } elseif ($n_name && isset($leads_map_name[$n_name])) {
                    // Name match is weak, but allowed as fallback
                    $matched_lead = $leads_map_name[$n_name];
                }

                if ($matched_lead) {
                    $matched++;

                    // Extract DB Data
                    $lead_data = unserialize($matched_lead['lead']);

                    // Data to store
                    $full_name = '';
                    if (isset($lead_data['full_name']))
                        $full_name = $lead_data['full_name'];
                    elseif (isset($lead_data['name']))
                        $full_name = $lead_data['name'];
                    elseif (isset($lead_data['first_name']))
                        $full_name = $lead_data['first_name'] . ' ' . ($lead_data['last_name'] ?? '');
                    if (empty($full_name))
                        $full_name = $raw_name; // Fallback to CSV name

                    $email = isset($lead_data['email']) ? $lead_data['email'] : $raw_email;

                    $phone = '';
                    if (isset($lead_data['phone_number']))
                        $phone = $lead_data['phone_number'];
                    elseif (isset($lead_data['phone']))
                        $phone = $lead_data['phone'];
                    if (empty($phone))
                        $phone = $raw_phone;

                    $ad_name = $matched_lead['adN'] ?? '';
                    $adset_name = $matched_lead['adsetN'] ?? '';
                    $campaign_name = $matched_lead['campN'] ?? '';
                    $form_name = $matched_lead['formN'] ?? '';
                    $platform = 'FB';

                    // Correct date handling
                    $created_ts = isset($matched_lead['created_time']) ? $matched_lead['created_time'] : time();
                    // Adjust timezone if needed (original code had +34199 adjustment?)
                    // Keeping original adjustment logic if it was intentioned, or standard timestamp
                    $created_date = date('Y-m-d H:i:s', $created_ts);

                    $form_id = $matched_lead['form_id'] ?? '';
                    $fb_lead_id = $matched_lead['leadgen_id'] ?? '';

                    $status = $raw_feedback ?: 'RNR';
                    $feedback = $raw_feedback;

                    // DB Operations
                    $email_esc = mysqli_real_escape_string($conn, $email);
                    $name_esc = mysqli_real_escape_string($conn, $full_name); // storing DB name usually better

                    // Check existence
                    // Matching criteria for update: Page ID + Email (strong) OR Phone (strong)
                    // Simplified: Check by unique FB Lead ID if available, otherwise Email+Name

                    $check_sql = "SELECT id FROM clientleadsheetcsv WHERE page_id = '$page_id_escaped' AND fb_lead_id = '$fb_lead_id' AND fb_lead_id != '' LIMIT 1";
                    // Fallback check if fb_lead_id is empty
                    if (empty($fb_lead_id)) {
                        $check_sql = "SELECT id FROM clientleadsheetcsv WHERE page_id = '$page_id_escaped' AND email = '$email_esc' LIMIT 1";
                    }

                    $check_res = mysqli_query($conn, $check_sql);

                    $full_name_esc = mysqli_real_escape_string($conn, $full_name);
                    $email_esc = mysqli_real_escape_string($conn, $email); // Re-escape just in case
                    $phone_esc = mysqli_real_escape_string($conn, $phone);
                    $ad_name_esc = mysqli_real_escape_string($conn, $ad_name);
                    $adset_name_esc = mysqli_real_escape_string($conn, $adset_name);
                    $campaign_name_esc = mysqli_real_escape_string($conn, $campaign_name);
                    $form_name_esc = mysqli_real_escape_string($conn, $form_name);
                    $status_esc = mysqli_real_escape_string($conn, $status);
                    $feedback_esc = mysqli_real_escape_string($conn, $feedback);
                    $form_id_esc = mysqli_real_escape_string($conn, $form_id);
                    $fb_lead_id_esc = mysqli_real_escape_string($conn, $fb_lead_id);
                    $client_name_esc = mysqli_real_escape_string($conn, $client_name);

                    if ($check_res && mysqli_num_rows($check_res) > 0) {
                        $existing = mysqli_fetch_assoc($check_res);
                        $update_sql = "UPDATE clientleadsheetcsv SET 
                            status = '$status_esc',
                            feedback = '$feedback_esc',
                            matched_with_db = 1
                            WHERE id = " . $existing['id'];
                        if (mysqli_query($conn, $update_sql))
                            $updated++;
                    } else {
                        $insert_sql = "INSERT INTO clientleadsheetcsv 
                        (page_id, form_id, fb_lead_id, client_name, full_name, email, phone_number, ad_name, adset_name, campaign_name, form_name, platform, status, feedback, created_date, matched_with_db) 
                        VALUES 
                        ('$page_id_escaped', '$form_id_esc', '$fb_lead_id_esc', '$client_name_esc', '$full_name_esc', '$email_esc', '$phone_esc', '$ad_name_esc', '$adset_name_esc', '$campaign_name_esc', '$form_name_esc', '$platform', '$status_esc', '$feedback_esc', '$created_date', 1)";

                        if (mysqli_query($conn, $insert_sql))
                            $inserted++;
                        else {
                            // echo mysqli_error($conn); 
                            $skipped++;
                        }
                    }

                } else {
                    $skipped++; // No match found in DB
                }
            }
            fclose($handle);
            $success = "Processed {$client_name}: $matched matched (Updated: $updated, Inserted: $inserted). Skipped: $skipped.";
        }
    } else {
        $err = "Upload failed.";
    }
} ?>