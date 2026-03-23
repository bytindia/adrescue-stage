<?php
/**
 * ============================================================
 * PATCH FILE: webhooks-leads.php — Custom Audience Push
 * ============================================================
 *
 * HOW TO APPLY:
 * In webhooks-leads.php, find the line:
 *
 *   mysqli_query($conn, $InsSql);  (the INSERT INTO leads line)
 *
 * IMMEDIATELY AFTER that mysqli_query, paste the function
 * vlookup_push_to_custom_audience() definition (once, at top level),
 * then call it as shown below.
 *
 * ============================================================
 */

// ── FUNCTION: Push lead to Meta Custom Audience ─────────────────────────────
// Paste this FUNCTION DEFINITION once, near the top of webhooks-leads.php
// (after include 'db.php';)

function push_lead_to_custom_audience($conn, $leads_acc_id, $leadgen_id, $lead_data, $access_token, $api_ver) {
    // 1. Check if custom audience is enabled for this leads_acc record
    $la_id = (int)$leads_acc_id;
    $res   = mysqli_query($conn, "SELECT cust_aud_enabled, cust_aud_ad_account, cust_aud_id FROM leads_acc WHERE tbl_id=$la_id LIMIT 1");
    $laRow = mysqli_fetch_assoc($res);

    if (!$laRow || !$laRow['cust_aud_enabled'] || empty($laRow['cust_aud_id'])) {
        return; // Not enabled or not configured
    }

    $audience_id = $laRow['cust_aud_id'];
    $ad_account  = $laRow['cust_aud_ad_account'];

    // 2. Normalize phone number (last 10 digits)
    $phone = '';
    foreach ($lead_data as $k => $v) {
        if (stripos($k, 'phone') !== false || stripos($k, 'mobile') !== false) {
            $phone = preg_replace('/[^0-9]/', '', $v);
            if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') { $phone = substr($phone, 2); }
            if (strlen($phone) === 11 && substr($phone, 0, 1) === '0')  { $phone = substr($phone, 1); }
            $phone = substr($phone, -10);
            break;
        }
    }

    // Phone with country code for Meta (91 = India)
    $phone_e164 = '91' . $phone;

    // 3. Hash data per Meta requirements (SHA-256, lowercase, trimmed)
    $hashed_phone = hash('sha256', $phone_e164);
    $email_raw = '';
    foreach ($lead_data as $k => $v) {
        if (stripos($k, 'email') !== false) { $email_raw = strtolower(trim($v)); break; }
    }
    $hashed_email = !empty($email_raw) ? hash('sha256', $email_raw) : '';

    // Full name
    $fn = $ln = '';
    if (!empty($lead_data['full_name'])) {
        $parts = explode(' ', trim($lead_data['full_name']), 2);
        $fn = strtolower(trim($parts[0] ?? ''));
        $ln = strtolower(trim($parts[1] ?? ''));
    }
    $hashed_fn = !empty($fn) ? hash('sha256', $fn) : '';
    $hashed_ln = !empty($ln) ? hash('sha256', $ln) : '';

    // 4. Build payload
    $user_data = [
        'PHONE' => [$hashed_phone],
    ];
    if (!empty($hashed_email))  { $user_data['EMAIL']      = [$hashed_email]; }
    if (!empty($hashed_fn))     { $user_data['FN']         = [$hashed_fn]; }
    if (!empty($hashed_ln))     { $user_data['LN']         = [$hashed_ln]; }

    $payload = [
        'payload' => json_encode([
            'schema' => array_keys($user_data),
            'data'   => [array_values(array_map(fn($v) => $v[0], $user_data))],
        ]),
        'access_token' => $access_token,
    ];

    // 5. POST to Meta Graph API
    $url = 'https://graph.facebook.com/' . $api_ver . '/' . $audience_id . '/users';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $resp_decoded = json_decode($response, true);
    $status = (isset($resp_decoded['num_received']) && $resp_decoded['num_received'] > 0) ? 'success' : 'failed';
    $phone_hash_esc = mysqli_real_escape_string($conn, $hashed_phone);
    $leadgen_esc    = mysqli_real_escape_string($conn, $leadgen_id);
    $aud_esc        = mysqli_real_escape_string($conn, $audience_id);
    $resp_esc       = mysqli_real_escape_string($conn, $response);

    // 6. Log the result
    mysqli_query($conn,
        "INSERT INTO leads_cust_aud_log
         (leads_acc_id, leadgen_id, audience_id, phone_hash, status, response, created)
         VALUES ($la_id, '$leadgen_esc', '$aud_esc', '$phone_hash_esc', '$status', '$resp_esc', NOW())"
    );

    error_log('[CustAud] LeadgenID=' . $leadgen_id . ' | Aud=' . $audience_id . ' | Status=' . $status . ' | Resp=' . $response);
}

// ─────────────────────────────────────────────────────────────────────────────
// HOW TO CALL in webhooks-leads.php:
// After the INSERT INTO leads query, add:
//
//   // ── Custom Audience Push ──────────────────────────────────────────────
//   $la_id_for_aud = $laID ?? 0; // ⚑ REPLACE with the actual leads_acc tbl_id variable
//   $lead_data_for_aud = $lead['lead'] ?? [];  // the deserialized lead field_data
//   push_lead_to_custom_audience(
//       $conn,
//       $la_id_for_aud,
//       $leadgen_id,
//       $lead_data_for_aud,
//       $access_token,
//       $api_ver
//   );
//
// ── ⚑ IMPORTANT VARIABLE NAMES TO MATCH:
//   In webhooks-leads.php, after the q3 SELECT, the leads_acc row tbl_id
//   is in $laRow['tbl_id'] or similar. Use that.
//   The $lead['lead'] array comes from getLead() return value parsed.
//   Check variable: $lead['lead'] which is already an array of field_data.
//
// ─────────────────────────────────────────────────────────────────────────────
?>
