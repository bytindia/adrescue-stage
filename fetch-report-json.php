<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'functions-report.php';

// --- Helpers ---
function curl_get_file_contents($URL) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    curl_close($c);
    return $contents ? $contents : false;
}

function parseDate($dateStr, $isStart = true) {
    $format = 'd-m-Y';
    $time = $isStart ? '00:00:00' : '23:59:59';
    $dt = DateTime::createFromFormat($format, $dateStr);
    return $dt ? $dt->format('Y-m-d') . ' ' . $time : null;
}

function LeadGen($arr, $filt) {
    $r = 0;
    if (is_array($arr) && count($arr) > 0) {
        foreach ($arr as $a) {
            if (isset($a['action_type']) && $a['action_type'] == $filt) {
                $r = $a['value'];
            }
        }
    }
    return $r;
}

// --- Input Dates ---
$start = isset($_GET['start']) ? parseDate($_GET['start'], true) : null;
$end   = isset($_GET['end'])   ? parseDate($_GET['end'], false) : null;

if (!$start || !$end) {
    $start = (new DateTime('first day of this month'))->format('Y-m-d') . ' 00:00:00';
    $end = (new DateTime('today'))->format('Y-m-d') . ' 23:59:59';
}

$start_date = date("Y-m-d", strtotime($start));
$end_date   = date("Y-m-d", strtotime($end));

// --- User Fetch ---
$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token'];
$uId = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['g_id'] = $row['g_id'];
$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
$_SESSION['g_token'] = $row['g_token'];
$_SESSION['g_mcc'] = $row['g_mcc'];

// --- Filter by Tag ---
$extQ = '';
if (isset($_GET['tag'])) {
    $extQ = "(tags = '" . mysqli_real_escape_string($conn, $_GET['tag']) . "' OR client_name='" . mysqli_real_escape_string($conn, $_GET['tag']) . "') AND ";
}

// --- Fetch Relevant Accounts ---
$sql = "SELECT tbl_id, fb_id, g_id, fb_received, camp_name FROM budget_reminder WHERE $extQ uid = '$uId' AND delete_status = 0 AND hide_temp = '0' LIMIT 1";
$sqlRev = mysqli_query($conn, $sql);

// --- Initialize Arrays ---
$fbSpent = $fblead = $fbcpl = $gSpent = $gConv = [];

while ($sqlROW = mysqli_fetch_assoc($sqlRev)) {

    $camp_contains = $sqlROW['camp_name'];
	preg_match('/\[(.*?)\]/', $camp_contains, $matches);
	$filter_keywords = [];
	if (!empty($matches[1])) {
		$filter_keywords = array_map('trim', explode(',', $matches[1]));
	}
	$filtering_param = $filtering_param_g = '';
	$filtering = [];
	$like_clauses = [];

	if (!empty($filter_keywords)) {
		foreach ($filter_keywords as $word) {
			$word = trim($word);
			$filtering[] = [
				"field" => "campaign.name",
				"operator" => "CONTAIN",
				"value" => $word
			];
	
			$like_clauses[] = "campaign.name LIKE '%" . $word . "%'";
		}
	
		$filtering_param = '&filtering=' . urlencode(json_encode($filtering));
	
		if (count($like_clauses) > 1) {
			$filtering_param_g = ' AND (' . implode(' OR ', $like_clauses) . ')';
		} elseif (count($like_clauses) === 1) {
			$filtering_param_g = ' AND ' . $like_clauses[0];
		}
	}

    // --- Facebook Insights ---
    if ($sqlROW['fb_id'] != '') {
        $fbIds = explode(',', $sqlROW['fb_id']);
        foreach ($fbIds as $fbId) {
            $dtRange = "time_range[since]=$start_date&time_range[until]=$end_date";
            $api_ver = "v19.0";
            $request_url = "https://graph.facebook.com/$api_ver/act_$fbId/insights?level=account&fields=spend,actions,cost_per_action_type" . $filtering_param . "&access_token=$access_token&$dtRange"; 
            $requests = curl_get_file_contents($request_url);
            $fb_response = json_decode($requests, true);

            $fbSpent[] = isset($fb_response['data'][0]['spend']) ? round($fb_response['data'][0]['spend']) : 0;
            $fblead[]  = isset($fb_response['data'][0]['actions']) ? LeadGen($fb_response['data'][0]['actions'], 'lead') : 0;
            $fbcpl[]   = isset($fb_response['data'][0]['cost_per_action_type']) ? LeadGen($fb_response['data'][0]['cost_per_action_type'], 'lead') : 0;
        }
    }

    // --- Google Ads ---
    if ($sqlROW['g_id'] != '') {
        include 'google-ads.php'; // this must define GetCampaigns::main()
        $gaIds = explode(',', $sqlROW['g_id']);
        foreach ($gaIds as $gaId) {
            $getAccRep = GetCampaigns::main(
                $conn,
                $_SESSION['g_refresh_token'],
                $_SESSION['g_mcc'],
                $gaId,
                $start_date,
                $end_date
            );
            $gSpent[] = isset($getAccRep['cost']) ? round($getAccRep['cost']) : 0;
            $gConv[]  = isset($getAccRep['conversions']) ? round($getAccRep['conversions']) : 0;
        }
    }
}

// --- Calculate Totals ---
$tot_fb_spend = array_sum($fbSpent);
$tot_fb_leads = array_sum($fblead);
$tot_g_spend  = array_sum($gSpent);
$tot_g_leads  = array_sum($gConv);
$tot_spend    = $tot_fb_spend + $tot_g_spend;
$tot_leads    = $tot_fb_leads + $tot_g_leads;

// --- Output as JSON ---
header('Content-Type: application/json');
echo json_encode([
    'tot_fb_spend' => $tot_fb_spend,
    'tot_fb_leads' => $tot_fb_leads,
    'tot_g_spend'  => $tot_g_spend,
    'tot_g_leads'  => $tot_g_leads,
    'tot_spend'    => $tot_spend,
    'tot_leads'    => $tot_leads
]);
