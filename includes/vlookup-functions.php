<?php
/**
 * VLOOKUP Module - Helper Functions
 * Reuses existing DB connection ($conn) from db.php
 */

if (!defined('VLOOKUP_LOADED')) define('VLOOKUP_LOADED', true);

// ─── Phone Normalization ────────────────────────────────────────────────────

/**
 * Normalize a phone number to last 10 digits (Indian numbers)
 */
function vlookup_normalize_phone($phone) {
    if (empty($phone)) return '';
    $phone = preg_replace('/[^0-9]/', '', $phone); // strip non-digits
    // Remove country code prefix
    if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
        $phone = substr($phone, 2);
    }
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        $phone = substr($phone, 1);
    }
    // Return last 10 digits
    return substr($phone, -10);
}

// ─── CSV Parsing ─────────────────────────────────────────────────────────────

/**
 * Parse CSV file and return [headers, rows]
 */
function vlookup_parse_csv($filepath) {
    $rows    = [];
    $headers = [];
    if (!file_exists($filepath)) return [[], []];
    if (($fh = fopen($filepath, 'r')) !== false) {
        $first = true;
        while (($row = fgetcsv($fh, 0, ',')) !== false) {
            if ($first) {
                $headers = array_map('trim', $row);
                $first   = false;
            } else {
                if (array_filter($row)) { // skip blank lines
                    $rows[] = $row;
                }
            }
        }
        fclose($fh);
    }
    return [$headers, $rows];
}

// ─── Leads from DB ───────────────────────────────────────────────────────────

/**
 * Fetch leads from MySQL for a given page_id and date range
 * Returns array of lead arrays with keys: phone, full_name, email, campN, adsetN, adN, formN
 */
function vlookup_get_db_leads($conn, $page_id, $stDt = null, $enDt = null) {
    $page_id = mysqli_real_escape_string($conn, $page_id);
    $dtQ = '';
    if ($stDt && $enDt) {
        $st = strtotime($stDt) - 34199;
        $en = strtotime($enDt) + 60 * 60 * 11 + 12599;
        $dtQ = "AND (created_time >= $st AND created_time <= $en)";
    }
    $sql = "SELECT * FROM leads WHERE page_id='$page_id' $dtQ ORDER BY tbl_id DESC";
    $res = mysqli_query($conn, $sql);
    $leads = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $lead_data = unserialize($row['lead']);
        $phone     = isset($lead_data['phone_number']) ? $lead_data['phone_number'] : '';
        // Try alternate field names
        if (empty($phone)) {
            foreach ($lead_data as $k => $v) {
                if (stripos($k, 'phone') !== false || stripos($k, 'mobile') !== false) {
                    $phone = $v; break;
                }
            }
        }
        $leads[] = [
            'phone'     => vlookup_normalize_phone($phone),
            'phone_raw' => $phone,
            'full_name' => isset($lead_data['full_name']) ? $lead_data['full_name'] : '',
            'email'     => isset($lead_data['email']) ? $lead_data['email'] : '',
            'campN'     => $row['campN'],
            'adsetN'    => $row['adsetN'],
            'adN'       => $row['adN'],
            'formN'     => $row['formN'],
            'camp_id'   => $row['camp_id'],
            'created'   => date('d-m-Y', $row['created_time'] + 34199),
        ];
    }
    return $leads;
}

/**
 * Get all pages/accounts for dropdown
 */
function vlookup_get_pages($conn, $uid) {
    $uid = (int)$uid;
    $pages = [];
    $res = mysqli_query($conn, "SELECT DISTINCT l.pg_id, l.pg_name, l.client_name FROM leads_acc l WHERE l.uid='$uid' AND l.delete_status=0 ORDER BY l.client_name ASC");
    while ($row = mysqli_fetch_assoc($res)) {
        $pages[] = $row;
    }
    return $pages;
}

// ─── Matching Engine ──────────────────────────────────────────────────────────

/**
 * Match leads against feedback rows using normalized phone number.
 * $col_map = ['phone' => 3, 'status' => 5, ...]  (feedback CSV column indexes)
 * Returns ['matched'=>[], 'unmatched'=>[], 'all_statuses'=>[]]
 */
function vlookup_match_leads($leads, $feedback_headers, $feedback_rows, $col_map) {
    $phone_col  = (int)$col_map['phone'];
    $status_col = isset($col_map['status']) ? (int)$col_map['status'] : -1;

    // Build feedback lookup: normalized_phone => row
    $fb_lookup = [];
    foreach ($feedback_rows as $frow) {
        $ph = isset($frow[$phone_col]) ? vlookup_normalize_phone($frow[$phone_col]) : '';
        if ($ph !== '') {
            $fb_lookup[$ph] = $frow;
        }
    }

    $matched   = [];
    $unmatched = [];
    $all_statuses = [];

    foreach ($leads as $lead) {
        $ph = $lead['phone'];
        if ($ph !== '' && isset($fb_lookup[$ph])) {
            $fb_row = $fb_lookup[$ph];
            $status = ($status_col >= 0 && isset($fb_row[$status_col])) ? trim($fb_row[$status_col]) : 'N/A';
            if ($status !== '' && $status !== 'N/A') {
                $all_statuses[$status] = ($all_statuses[$status] ?? 0) + 1;
            }
            $matched[] = array_merge($lead, [
                'fb_row' => $fb_row,
                'status' => $status,
            ]);
        } else {
            $unmatched[] = $lead;
        }
    }

    return ['matched' => $matched, 'unmatched' => $unmatched, 'all_statuses' => $all_statuses];
}

// ─── Grouping ─────────────────────────────────────────────────────────────────

/**
 * Group results by a key (campN / adsetN / adN) and compute stats.
 * $status_keys = ['contacted', 'qualified', 'visit', 'closed'] (mapped from col_map)
 */
function vlookup_group_results($matched, $unmatched, $group_by, $status_map, $all_statuses) {
    $groups = [];
    $all_leads = array_merge(
        array_map(fn($l) => array_merge($l, ['_matched' => true]),  $matched),
        array_map(fn($l) => array_merge($l, ['_matched' => false]), $unmatched)
    );

    foreach ($all_leads as $lead) {
        $key = trim($lead[$group_by] ?? 'Unknown');
        if ($key === '') $key = 'Unknown';
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'label'    => $key,
                'total'    => 0,
                'matched'  => 0,
                'unmatched'=> 0,
                'statuses' => [],
            ];
        }
        $groups[$key]['total']++;
        if ($lead['_matched']) {
            $groups[$key]['matched']++;
            $st = $lead['status'] ?? '';
            if ($st !== '') {
                $groups[$key]['statuses'][$st] = ($groups[$key]['statuses'][$st] ?? 0) + 1;
            }
        } else {
            $groups[$key]['unmatched']++;
        }
    }

    uasort($groups, fn($a, $b) => $b['total'] - $a['total']);
    return array_values($groups);
}

// ─── Standard Status Buckets ──────────────────────────────────────────────────

/**
 * Return standard bucket counts from a status string
 * Buckets: contacted, qualified, visit, closed
 */
function vlookup_bucket_counts($statuses) {
    $contacted = $qualified = $visit = $closed = 0;
    foreach ($statuses as $st => $cnt) {
        $stl = strtolower($st);
        if (str_contains_any($stl, ['contact', 'call', 'reach', 'attempted'])) {
            $contacted += $cnt;
        }
        if (str_contains_any($stl, ['qualif', 'hot', 'interest', 'prospect'])) {
            $qualified += $cnt;
        }
        if (str_contains_any($stl, ['visit', 'demo', 'meeting', 'walk'])) {
            $visit += $cnt;
        }
        if (str_contains_any($stl, ['close', 'sale', 'book', 'won', 'paid', 'convert'])) {
            $closed += $cnt;
        }
    }
    return compact('contacted', 'qualified', 'visit', 'closed');
}

function str_contains_any($haystack, $needles) {
    foreach ($needles as $n) {
        if (strpos($haystack, $n) !== false) return true;
    }
    return false;
}

// ─── Export CSV ───────────────────────────────────────────────────────────────

function vlookup_output_csv($filename, $headers, $rows) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
