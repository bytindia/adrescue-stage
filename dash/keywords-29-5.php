<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '5000M');
ini_set('max_execution_time', 300);
error_reporting(E_ALL);

session_start();
$_SESSION['uid'] = $_SESSION['logged'] = 2;
if(!isset($_SESSION['logged'])) {
    $pg = '../login.php';
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    //echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
    exit();
}
require  '../google-ads-v15/vendor/autoload.php';
include '../db.php';

// Get client ID from URL parameter
$client_id = isset($_GET['tbl_id']) ? (int)$_GET['tbl_id'] : 0;

if($client_id == 0) {
    echo "<div class='alert alert-danger'>Invalid client ID</div>";
    exit();
}

// Get client details from dashboard_accounts table
$client_query = "SELECT * FROM dashboard_accounts WHERE tbl_id = '".mysqli_real_escape_string($conn, $client_id)."' AND uid='".$_SESSION['uid']."' AND delete_status=0";
$client_result = mysqli_query($conn, $client_query);
$client_data = mysqli_fetch_assoc($client_result);

if(!$client_data) {
    echo "<div class='alert alert-danger'>Client not found</div>";
    exit();
}

// Parse keyword data from client
$keyw_name = [];
$keyw_contain = [];
$keyw_exclude = [];

if($client_data['keyw_name'] != '') {
    $keyw_name = array_filter(unserialize($client_data['keyw_name']));
}
if($client_data['keyw_contain'] != '') {
    $keyw_contain = array_filter(unserialize($client_data['keyw_contain']));
}
if($client_data['keyw_exclude'] != '') {
    $keyw_exclude = array_filter(unserialize($client_data['keyw_exclude']));
}

// Build keyword groups from client data
$groups = [];
foreach($keyw_name as $index => $name) {
    $include_terms = isset($keyw_contain[$index]) ? array_map('trim', explode(',', $keyw_contain[$index])) : [];
    $exclude_terms = isset($keyw_exclude[$index]) ? array_map('trim', explode(',', $keyw_exclude[$index])) : [];
    
    $groups[$name] = [
        'include' => array_filter($include_terms),
        'exclude' => array_filter($exclude_terms)
    ];
}

// Date logic
if(isset($_GET['date']) && $_GET['date']!='') {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('d/m/Y'); // Default to today
}
$selected_date_prev1 = date('d/m/Y', strtotime(str_replace('/', '-', $selected_date) . ' -1 day'));
$selected_date_prev2 = date('d/m/Y', strtotime(str_replace('/', '-', $selected_date) . ' -2 day'));

// Replace formatIndianNumber with Indian grouping
function formatIndianNumber($num) {
    if ($num === null || $num === '' || !is_numeric($num)) {
        return '0';
    }
    $number = round(floatval($num));
    $result = '';
    $num = (string)$number;
    $len = strlen($num);
    if ($len > 3) {
        $result = substr($num, -3);
        $num = substr($num, 0, $len - 3);
        while (strlen($num) > 2) {
            $result = substr($num, -2) . ',' . $result;
            $num = substr($num, 0, strlen($num) - 2);
        }
        if ($num) {
            $result = $num . ',' . $result;
        }
    } else {
        $result = $num;
    }
    return $result;
}

// Get user's Google Ads credentials
$user_query = "SELECT g_refresh_token, g_mcc FROM users WHERE tbl_id = '".$_SESSION['uid']."'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

if(!$user_data || empty($user_data['g_refresh_token'])) {
    echo "<div class='alert alert-danger'>Google Ads credentials not found</div>";
    exit();
}

$g_ref_tok = $user_data['g_refresh_token'];
$g_mcc = $user_data['g_mcc'];

// Get Google account IDs (multiple accounts supported)
$g_acc_ids = array_filter(explode(',', $client_data['g_id']));
if(empty($g_acc_ids)) {
    echo "<div class='alert alert-warning'>No Google Ads accounts linked to this client.</div>";
    exit();
}

$allGids = [];
foreach($g_acc_ids as $g_id) {
    $allGids[] = [
        'g_id' => $g_id,
        'client_name' => $client_data['client_name'],
        'tbl_id' => $client_id,
        'camp_name' => ''
    ];
}

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

$oAuth2Credential = (new OAuth2TokenBuilder())
    ->fromFile()
    ->withRefreshToken($g_ref_tok)
    ->build();
$googleAdsClient = (new GoogleAdsClientBuilder())
    ->fromFile()
    ->withOAuth2Credential($oAuth2Credential)
    ->withLoginCustomerId((int)str_replace('-', '', $g_mcc))
    ->build();

function getKeywordGroupData($googleAdsClient, $customerId, $date, $groups) {
    $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
    $dateYMD = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
    $query = "
        SELECT
            search_term_view.search_term,
            metrics.impressions,
            metrics.cost_micros,
            metrics.average_cpm,
            metrics.clicks,
            metrics.average_cpc,
            metrics.conversions
        FROM search_term_view
        WHERE segments.date = '{$dateYMD}'
    ";
    $request = new SearchGoogleAdsStreamRequest([
        'customer_id' => $customerId,
        'query' => $query,
    ]);
    $stream = $googleAdsService->searchStream($request);
    $groupData = [];
    foreach ($groups as $groupName => $terms) {
        $groupData[$groupName] = [
            'spend' => 0, 'impressions' => 0, 'cpm' => 0, 'clicks' => 0, 'cpc' => 0, 'leads' => 0, 'cpl' => 0
        ];
    }
    $groupData['other'] = ['spend' => 0, 'impressions' => 0, 'cpm' => 0, 'clicks' => 0, 'cpc' => 0, 'leads' => 0, 'cpl' => 0];
    $accountTotal = ['spend' => 0, 'impressions' => 0, 'cpm' => 0, 'clicks' => 0, 'cpc' => 0, 'leads' => 0, 'cpl' => 0];
    
    foreach ($stream->iterateAllElements() as $row) {
        $impressions = $row->getMetrics()->getImpressions() ?? 0;
        $spend = ($row->getMetrics()->getCostMicros() ?? 0) / 1000000;
        $clicks = $row->getMetrics()->getClicks() ?? 0;
        $leads = $row->getMetrics()->getConversions() ?? 0;
        $searchTerm = strtolower($row->getSearchTermView()->getSearchTerm());
        $groupMatched = null;
        
        foreach ($groups as $groupName => $criteria) {
            $include = $criteria['include'];
            $exclude = $criteria['exclude'];
            $matched = false;
            
            // Check include terms
            if(empty($include)) {
                $matched = true; // If no include terms, match all
            } else {
                foreach ($include as $inc) {
                    if (strpos($searchTerm, strtolower($inc)) !== false) {
                        $matched = true;
                        break;
                    }
                }
            }
            
            // Check exclude terms
            if ($matched && !empty($exclude)) {
                foreach ($exclude as $exc) {
                    if (strpos($searchTerm, strtolower($exc)) !== false) {
                        $matched = false;
                        break;
                    }
                }
            }
            
            if ($matched) {
                $groupMatched = $groupName;
                break;
            }
        }
        
        if ($groupMatched) {
            $groupData[$groupMatched]['spend'] += $spend;
            $groupData[$groupMatched]['impressions'] += $impressions;
            $groupData[$groupMatched]['clicks'] += $clicks;
            $groupData[$groupMatched]['leads'] += $leads;
        } else {
            $groupData['other']['spend'] += $spend;
            $groupData['other']['impressions'] += $impressions;
            $groupData['other']['clicks'] += $clicks;
            $groupData['other']['leads'] += $leads;
        }
        
        $accountTotal['spend'] += $spend;
        $accountTotal['impressions'] += $impressions;
        $accountTotal['clicks'] += $clicks;
        $accountTotal['leads'] += $leads;
    }
    
    // Calculate CPM, CPC, CPL for each group
    foreach ($groupData as $g => &$d) {
        $d['cpm'] = ($d['impressions'] > 0) ? ($d['spend'] / $d['impressions']) * 1000 : 0;
        $d['cpc'] = ($d['clicks'] > 0) ? ($d['spend'] / $d['clicks']) : 0;
        $d['cpl'] = ($d['leads'] > 0) ? ($d['spend'] / $d['leads']) : 0;
    }
    unset($d);
    
    $accountTotal['cpm'] = ($accountTotal['impressions'] > 0) ? ($accountTotal['spend'] / $accountTotal['impressions']) * 1000 : 0;
    $accountTotal['cpc'] = ($accountTotal['clicks'] > 0) ? ($accountTotal['spend'] / $accountTotal['clicks']) : 0;
    $accountTotal['cpl'] = ($accountTotal['leads'] > 0) ? ($accountTotal['spend'] / $accountTotal['leads']) : 0;
    
    return [
        'groups' => $groupData,
        'account_total' => $accountTotal
    ];
}

function getAccountLevelTotals($googleAdsClient, $customerId, $date) {
    $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
    if (is_array($date)) {
        $start = date('Y-m-d', strtotime(str_replace('/', '-', $date[0])));
        $end = date('Y-m-d', strtotime(str_replace('/', '-', $date[1])));
        $date_filter = "segments.date BETWEEN '{$start}' AND '{$end}'";
    } else {
        $dateYMD = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
        $date_filter = "segments.date = '{$dateYMD}'";
    }
    $query = "
        SELECT
            metrics.cost_micros,
            metrics.impressions,
            metrics.clicks,
            metrics.conversions
        FROM customer
        WHERE $date_filter
    ";
    $request = new Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest([
        'customer_id' => $customerId,
        'query' => $query,
    ]);
    $spend = $impressions = $clicks = $leads = 0;
    foreach ($googleAdsService->searchStream($request)->iterateAllElements() as $row) {
        $spend += ($row->getMetrics()->getCostMicros() ?? 0) / 1000000;
        $impressions += $row->getMetrics()->getImpressions() ?? 0;
        $clicks += $row->getMetrics()->getClicks() ?? 0;
        $leads += $row->getMetrics()->getConversions() ?? 0;
    }
    $cpm = ($impressions > 0) ? ($spend / $impressions) * 1000 : 0;
    $cpc = ($clicks > 0) ? ($spend / $clicks) : 0;
    $cpl = ($leads > 0) ? ($spend / $leads) : 0;
    return [
        'spend' => $spend,
        'impressions' => $impressions,
        'cpm' => $cpm,
        'clicks' => $clicks,
        'cpc' => $cpc,
        'leads' => $leads,
        'cpl' => $cpl
    ];
}

function getSearchTermTotals($googleAdsClient, $customerId, $date) {
    $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
    $dateYMD = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
    $query = "
        SELECT
            search_term_view.search_term,
            metrics.impressions,
            metrics.cost_micros,
            metrics.average_cpm,
            metrics.clicks,
            metrics.average_cpc,
            metrics.conversions
        FROM search_term_view
        WHERE segments.date = '{$dateYMD}'
    ";
    $request = new Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest([
        'customer_id' => $customerId,
        'query' => $query,
    ]);
    $stream = $googleAdsService->searchStream($request);
    $totals = [
        'search_terms' => ['spend'=>0,'impressions'=>0,'cpm'=>0,'clicks'=>0,'cpc'=>0,'leads'=>0,'cpl'=>0],
        'other_search' => ['spend'=>0,'impressions'=>0,'cpm'=>0,'clicks'=>0,'cpc'=>0,'leads'=>0,'cpl'=>0]
    ];
    foreach ($stream->iterateAllElements() as $row) {
        $impressions = $row->getMetrics()->getImpressions() ?? 0;
        $spend = ($row->getMetrics()->getCostMicros() ?? 0) / 1000000;
        $clicks = $row->getMetrics()->getClicks() ?? 0;
        $leads = $row->getMetrics()->getConversions() ?? 0;
        $searchTerm = $row->getSearchTermView()->getSearchTerm();
        // If searchTerm is null or '--', treat as Other search terms
        if ($searchTerm === null || $searchTerm === '' || $searchTerm === '--') {
            $totals['other_search']['spend'] += $spend;
            $totals['other_search']['impressions'] += $impressions;
            $totals['other_search']['clicks'] += $clicks;
            $totals['other_search']['leads'] += $leads;
        }
        $totals['search_terms']['spend'] += $spend;
        $totals['search_terms']['impressions'] += $impressions;
        $totals['search_terms']['clicks'] += $clicks;
        $totals['search_terms']['leads'] += $leads;
    }
    foreach (['search_terms','other_search'] as $type) {
        $d = &$totals[$type];
        $d['cpm'] = ($d['impressions'] > 0) ? ($d['spend'] / $d['impressions']) * 1000 : 0;
        $d['cpc'] = ($d['clicks'] > 0) ? ($d['spend'] / $d['clicks']) : 0;
        $d['cpl'] = ($d['leads'] > 0) ? ($d['spend'] / $d['leads']) : 0;
    }
    return $totals;
}

function getAccountLevelTotalsPerDay($googleAdsClient, $customerId, $month_start_date, $month_end_date) {
    $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
    $start = date('Y-m-d', strtotime(str_replace('/', '-', $month_start_date)));
    $end = date('Y-m-d', strtotime(str_replace('/', '-', $month_end_date)));
    $query = "
        SELECT
            segments.date,
            metrics.cost_micros,
            metrics.impressions,
            metrics.clicks,
            metrics.conversions
        FROM customer
        WHERE segments.date BETWEEN '{$start}' AND '{$end}'
    ";
    $request = new Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest([
        'customer_id' => $customerId,
        'query' => $query,
    ]);
    $perDay = [];
    foreach ($googleAdsService->searchStream($request)->iterateAllElements() as $row) {
        $date = $row->getSegments()->getDate();
        $spend = ($row->getMetrics()->getCostMicros() ?? 0) / 1000000;
        $impressions = $row->getMetrics()->getImpressions() ?? 0;
        $clicks = $row->getMetrics()->getClicks() ?? 0;
        $leads = $row->getMetrics()->getConversions() ?? 0;
        $perDay[$date] = [
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'leads' => $leads
        ];
    }
    return $perDay;
}

function sumMetrics($days) {
    $sum = ['spend'=>0,'impressions'=>0,'clicks'=>0,'leads'=>0];
    foreach ($days as $d) {
        foreach ($sum as $k => &$v) {
            $v += $d[$k];
        }
    }
    $sum['cpm'] = ($sum['impressions'] > 0) ? ($sum['spend'] / $sum['impressions']) * 1000 : 0;
    $sum['cpc'] = ($sum['clicks'] > 0) ? ($sum['spend'] / $sum['clicks']) : 0;
    $sum['cpl'] = ($sum['leads'] > 0) ? ($sum['spend'] / $sum['leads']) : 0;
    return $sum;
}

function ordinal($n) {
    $suffix = 'th';
    if (!in_array(($n % 100), [11,12,13])) {
        switch ($n % 10) {
            case 1: $suffix = 'st'; break;
            case 2: $suffix = 'nd'; break;
            case 3: $suffix = 'rd'; break;
        }
    }
    return $n . $suffix;
}

function collectGroupSearchTerms($googleAdsClient, $customerId, $date, $groups) {
    $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
    $dateYMD = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
    $query = "
        SELECT
            search_term_view.search_term,
            metrics.impressions,
            metrics.cost_micros,
            metrics.average_cpm,
            metrics.clicks,
            metrics.average_cpc,
            metrics.conversions,
            campaign.name,
            ad_group.name
        FROM search_term_view
        WHERE segments.date = '{$dateYMD}'
    ";
    $request = new Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest([
        'customer_id' => $customerId,
        'query' => $query,
    ]);
    $stream = $googleAdsService->searchStream($request);
    $result = [];
    foreach (array_keys($groups) as $groupKey) {
        $result[$groupKey] = [];
    }
    $result['other'] = [];
    
    foreach ($stream->iterateAllElements() as $row) {
        $searchTerm = strtolower($row->getSearchTermView()->getSearchTerm());
        $impressions = $row->getMetrics()->getImpressions() ?? 0;
        $spend = ($row->getMetrics()->getCostMicros() ?? 0) / 1000000;
        $clicks = $row->getMetrics()->getClicks() ?? 0;
        $leads = $row->getMetrics()->getConversions() ?? 0;
        $cpm = $row->getMetrics()->getAverageCpm() / 1000000;
        $cpc = $row->getMetrics()->getAverageCpc() / 1000000;
        $cpl = ($leads > 0) ? $spend / $leads : 0;
        $campaignName = $row->getCampaign() ? $row->getCampaign()->getName() : '';
        $adGroupName = $row->getAdGroup() ? $row->getAdGroup()->getName() : '';
        $groupMatched = 'other';
        
        foreach ($groups as $groupName => $criteria) {
            $include = $criteria['include'];
            $exclude = $criteria['exclude'];
            $matched = false;
            
            if(empty($include)) {
                $matched = true;
            } else {
                foreach ($include as $inc) {
                    if (strpos($searchTerm, strtolower($inc)) !== false) {
                        $matched = true;
                        break;
                    }
                }
            }
            
            if ($matched && !empty($exclude)) {
                foreach ($exclude as $exc) {
                    if (strpos($searchTerm, strtolower($exc)) !== false) {
                        $matched = false;
                        break;
                    }
                }
            }
            
            if ($matched) {
                $groupMatched = $groupName;
                break;
            }
        }
        
        $result[$groupMatched][] = [
            'search_term' => $row->getSearchTermView()->getSearchTerm(),
            'spend' => $spend,
            'impressions' => $impressions,
            'cpm' => $cpm,
            'clicks' => $clicks,
            'cpc' => $cpc,
            'leads' => $leads,
            'cpl' => $cpl,
            'campaign_name' => $campaignName,
            'ad_group_name' => $adGroupName
        ];
    }
    return $result;
}

// Fetch data for all three days
$data = [];
$account_totals = [];
$search_term_totals = [];
$group_search_terms = [];
$date_keys = [
    'today' => $selected_date,
    'yesterday' => $selected_date_prev1,
    'daybefore' => $selected_date_prev2
];

foreach ($date_keys as $key => $date) {
    $data[$key] = getKeywordGroupData($googleAdsClient, $allGids[0]['g_id'], $date, $groups);
    $account_totals[$key] = getAccountLevelTotals($googleAdsClient, $allGids[0]['g_id'], $date);
    $search_term_totals[$key] = getSearchTermTotals($googleAdsClient, $allGids[0]['g_id'], $date);
    $group_search_terms[$key] = collectGroupSearchTerms($googleAdsClient, $allGids[0]['g_id'], $date, $groups);
}

// Calculate Tot. Other search as Account Total - Tot. Search terms (for each metric)
$other_search = [];
foreach ($date_keys as $key => $date) {
    $other_search[$key] = [];
    foreach(['spend','impressions','clicks','leads'] as $k) {
        $other_search[$key][$k] = $account_totals[$key][$k] - $search_term_totals[$key]['search_terms'][$k];
    }
    $other_search[$key]['cpm'] = ($other_search[$key]['impressions'] > 0) ? ($other_search[$key]['spend'] / $other_search[$key]['impressions']) * 1000 : 0;
    $other_search[$key]['cpc'] = ($other_search[$key]['clicks'] > 0) ? ($other_search[$key]['spend'] / $other_search[$key]['clicks']) : 0;
    $other_search[$key]['cpl'] = ($other_search[$key]['leads'] > 0) ? ($other_search[$key]['spend'] / $other_search[$key]['leads']) : 0;
}

// Pass this data to JS as a JSON object
?>
<script>
window.groupSearchTerms = <?php echo json_encode($group_search_terms); ?>;
window.dateKeys = <?php echo json_encode($date_keys); ?>;
</script>
<br>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Keyword Dashboard - <?php echo htmlspecialchars($client_data['client_name']); ?></title>
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .table { 
            width: 100%; 
            margin: 0 auto; 
            table-layout: fixed; 
            font-size: 16px;
        }
        .table th, .table td { 
            text-align: center; 
            vertical-align: middle; 
            font-family: 'Segoe UI', 'Arial', sans-serif;
            padding: 4px 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 16px;
        }
        .table thead th { 
            background: #2196f3 !important; 
            color: #fff !important; 
            font-size: 16px;
            padding: 6px 2px;
        }
        .table th:first-child, .table td:first-child { 
            text-align: left;
            width: 120px;
            position: sticky;
            left: 0;
            background: inherit;
            z-index: 1;
        }
        .table td:not(:first-child), .table th:not(:first-child) {
            width: calc((100% - 120px) / 21);
        }
        .col-today { background: #eaf6ff !important; }
        .col-yesterday { background: #fffbe6 !important; }
        .col-daybefore { background: #fff2e6 !important; }
        .leads-zero { background-color: #e18e9b !important; }
        .container { 
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .container.mt-4 {
            margin: 0 !important;
            padding: 10px !important;
        }
        .date-filter-row {
            padding-right: 10px;
        }
        .expandable-group { cursor: pointer; }
        .expandable-group:hover { background-color: #f0f0f0; }
        .copy-buttons {
            position: sticky;
            right: 15px;
            margin-top: 18px;
            text-align: right;
        }
        .col-month { background: #e6ffe6 !important; }
        .white-bg { background: white !important; }
        .cpc-high { background-color: #ffeb3b !important; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="date-filter-row pull-right">
        <form method="get" class="form-inline" style="display: flex; align-items: center;">
            <input type="hidden" name="tbl_id" value="<?php echo $client_id; ?>">
            <label for="date" class="form-label mb-0">Select Date:</label>
            <input type="text" id="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" autocomplete="off" readonly style="background:#fff;cursor:pointer;max-width:120px; margin-right:8px;">
        </form>
    </div>
    <!--<h3 class="mb-4" style="margin:0;flex-grow:1;">Keyword Dashboard - <?php echo htmlspecialchars($client_data['client_name']); ?></h3>-->
    <div class="container mt-4">
      <div id="table-scroll" class="table-scroll">
        <div class="table-wrap">
          <table class="table table-bordered" id="keywordsTable">
            <thead>
            <tr>
                <th rowspan="2" style="text-align: center;"><?php echo htmlspecialchars($client_data['client_name']); ?><br><?php echo date('F', strtotime(str_replace('/', '-', $selected_date))); ?></th>
                <th colspan="3">Spends</th>
                <th colspan="3">Leads</th>
                <th colspan="3">CPL</th>
                <th colspan="3">Clicks</th>
                <th colspan="3">CPC</th>
                <th colspan="3">CPM</th>
                <th colspan="3">Impression</th>
            </tr>
            <tr>
                <?php
                $date_classes = ['today' => 'col-today', 'yesterday' => 'col-yesterday', 'daybefore' => 'col-daybefore'];
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    echo '<th class="' . $date_classes[$dkey] . '" data-date-header>' . date('d', strtotime(str_replace('/', '-', $date_keys[$dkey]))) . '</th>';
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            // Calculate Tot. Search terms (sum of main groups)
            $totals = [];
            $main_groups = array_keys($groups);
            foreach ($date_keys as $key => $date) {
                $totals[$key] = [
                    'spend'=>0,'impressions'=>0,'cpm'=>0,'clicks'=>0,'cpc'=>0,'leads'=>0,'cpl'=>0
                ];
                foreach ($main_groups as $gkey) {
                    $row = isset($data[$key]['groups'][$gkey]) ? $data[$key]['groups'][$gkey] : ['spend'=>0,'impressions'=>0,'cpm'=>0,'clicks'=>0,'cpc'=>0,'leads'=>0,'cpl'=>0];
                    foreach(['spend','impressions','clicks','leads'] as $k) {
                        $totals[$key][$k] += $row[$k];
                    }
                }
                $totals[$key]['cpm'] = ($totals[$key]['impressions'] > 0) ? ($totals[$key]['spend'] / $totals[$key]['impressions']) * 1000 : 0;
                $totals[$key]['cpc'] = ($totals[$key]['clicks'] > 0) ? ($totals[$key]['spend'] / $totals[$key]['clicks']) : 0;
                $totals[$key]['cpl'] = ($totals[$key]['leads'] > 0) ? ($totals[$key]['spend'] / $totals[$key]['leads']) : 0;
            }
            
            // Display keyword groups
            foreach ($groups as $key => $groupData) {
                echo "<tr id='row-$key' class='main-group-row'>";
                echo "<td class='expandable-group' data-group='$key' style='cursor:pointer;'>$key</td>";
                
                // Precompute max CPC for this row
                $cpc_vals = [
                    floatval(isset($data['today']['groups'][$key]['cpc']) ? $data['today']['groups'][$key]['cpc'] : 0),
                    floatval(isset($data['yesterday']['groups'][$key]['cpc']) ? $data['yesterday']['groups'][$key]['cpc'] : 0),
                    floatval(isset($data['daybefore']['groups'][$key]['cpc']) ? $data['daybefore']['groups'][$key]['cpc'] : 0)
                ];
                $max_cpc = max($cpc_vals);
                
                foreach (['spend','leads','cpl','clicks','cpc','cpm','impressions'] as $metric) {
                    foreach (['today', 'yesterday', 'daybefore'] as $i => $dkey) {
                        $val = isset($data[$dkey]['groups'][$key][$metric]) ? $data[$dkey]['groups'][$key][$metric] : 0;
                        $cellClass = $date_classes[$dkey] . ' ' . $dkey;
                        if ($metric === 'cpc' && floatval($val) == $max_cpc && $max_cpc > 0) {
                            $cellClass .= ' cpc-high';
                        }
                        if ($metric === 'leads' && $val == 0) {
                            $cellClass .= ' leads-zero';
                        }
                        echo "<td class='$cellClass'>".formatIndianNumber($val)."</td>";
                    }
                }
                echo "</tr>";
            }
            
            // Other row
            echo "<tr class='total-row white-bg'><td>Other</td>";
            foreach (['spend','leads','cpl','clicks','cpc','cpm','impressions'] as $metric) {
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    $val = isset($data[$dkey]['groups']['other'][$metric]) ? $data[$dkey]['groups']['other'][$metric] : 0;
                    $cellClass = $date_classes[$dkey] . ' ' . $dkey;
                    echo "<td class='$cellClass'>".formatIndianNumber($val)."</td>";
                }
            }
            echo "</tr>";
            
            // Tot. Search terms row
            echo "<tr class='total-row white-bg'><td>Tot. Search terms</td>";
            foreach (['spend','leads','cpl','clicks','cpc','cpm','impressions'] as $metric) {
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    $val = $search_term_totals[$dkey]['search_terms'][$metric];
                    $cellClass = $date_classes[$dkey] . ' ' . $dkey;
                    echo "<td class='$cellClass'>".formatIndianNumber($val)."</td>";
                }
            }
            echo "</tr>";
            
            // Tot. Other search row
            echo "<tr class='total-row white-bg'><td>Tot. Other search</td>";
            foreach (['spend','leads','cpl','clicks','cpc','cpm','impressions'] as $metric) {
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    $val = $other_search[$dkey][$metric];
                    $cellClass = $date_classes[$dkey] . ' ' . $dkey;
                    echo "<td class='$cellClass'>".formatIndianNumber($val)."</td>";
                }
            }
            echo "</tr>";
            
            // Calculate monthly data first
            $month_start_date = date('01/m/Y');
            $month_end_date = date('d/m/Y');
            $this_month_account = getAccountLevelTotals($googleAdsClient, $allGids[0]['g_id'], [$month_start_date, $month_end_date]);

            // Calculate monthly averages
            $days_in_month = (int)date('d'); // Current day of month for average calculation
            $monthly_avg = [
                'spend' => $this_month_account['spend'] / $days_in_month,
                'impressions' => $this_month_account['impressions'] / $days_in_month,
                'cpm' => $this_month_account['cpm'],
                'clicks' => $this_month_account['clicks'] / $days_in_month,
                'cpc' => $this_month_account['cpc'],
                'leads' => $this_month_account['leads'] / $days_in_month,
                'cpl' => $this_month_account['cpl']
            ];

            // Tot. Account row with improved highlighting
            echo "<tr class='total-row white-bg'><td>Tot. Account</td>";
            foreach (['spend','leads','cpl','clicks','cpc','cpm','impressions'] as $metric) {
                foreach (['today', 'yesterday', 'daybefore'] as $dkey) {
                    $val = isset($account_totals[$dkey][$metric]) ? $account_totals[$dkey][$metric] : 0;
                    $cellClass = $date_classes[$dkey] . ' ' . $dkey;
                    
                    // Add highlighting based on metric type
                    if ($monthly_avg[$metric] > 0) { // Prevent division by zero
                        switch($metric) {
                            case 'spend':
                            case 'impressions':
                            case 'clicks':
                            case 'leads':
                                // Highlight if below daily average
                                if ($val < $monthly_avg[$metric]) {
                                    $cellClass .= ' leads-zero';
                                }
                                break;
                            case 'cpm':
                            case 'cpc':
                            case 'cpl':
                                // Highlight if above daily average
                                if ($val > $monthly_avg[$metric] * 1.1) { // 10% threshold for rate metrics
                                    $cellClass .= ' leads-zero';
                                }
                                break;
                        }
                    }
                    
                    echo "<td class='$cellClass'>".formatIndianNumber($val)."</td>";
                }
            }
            echo "</tr>";

            // This Month (Account) row
            echo "<tr class='total-row white-bg' style='background:#e6ffe6;font-weight:bold;'><td>This Month (Account)</td>";
            foreach (['spend','leads','cpl','clicks','cpc','cpm','impressions'] as $metric) {
                $val = isset($this_month_account[$metric]) ? $this_month_account[$metric] : 0;
                echo "<td colspan='3' style='font-weight:bold;'>".formatIndianNumber($val)."</td>";
            }
            echo "</tr>";

            // After This Month (Account) row, aggregate and display week rows
            $month_start_date = date('01/m/Y');
            $month_end_date = date('d/m/Y');
            $perDayData = getAccountLevelTotalsPerDay($googleAdsClient, $allGids[0]['g_id'], $month_start_date, $month_end_date);

            $weeks = [];
            $firstDate = DateTime::createFromFormat('d/m/Y', $month_start_date);
            $lastDate = DateTime::createFromFormat('d/m/Y', $month_end_date);
            $cur = clone $firstDate;
            $weekIndex = 1;
            while ($cur <= $lastDate) {
                $weekdays = [];
                $weekends = [];
                $weekStart = clone $cur;
                // Find the end of this week (Sunday or last day of month)
                $weekEnd = clone $cur;
                $weekEnd->modify('sunday this week');
                if ($weekEnd > $lastDate) $weekEnd = clone $lastDate;
                // Collect days in this week
                $tmp = clone $cur;
                while ($tmp <= $weekEnd) {
                    $dateStr = $tmp->format('Y-m-d');
                    $dow = $tmp->format('N'); // 1=Mon, 7=Sun
                    if (isset($perDayData[$dateStr])) {
                        if ($dow <= 5) {
                            $weekdays[$dateStr] = $perDayData[$dateStr];
                        } else {
                            $weekends[$dateStr] = $perDayData[$dateStr];
                        }
                    }
                    $tmp->modify('+1 day');
                }
                if (count($weekdays) > 0) $weeks[$weekIndex]['weekdays'] = $weekdays;
                if (count($weekends) > 0) $weeks[$weekIndex]['weekends'] = $weekends;
                $cur = $weekEnd;
                $cur->modify('+1 day');
                $weekIndex++;
            }
            
            $metricOrder = ['spend','leads','cpl','clicks','cpc','cpm','impressions'];
            $weekColors = [
                '#b3e5fc', // 1st week: blue
                '#b3e5fc', // 2nd week: blue
                '#b3e5fc', // 3rd week: blue
                '#b3e5fc', // 4th week: blue
                '#b3e5fc', // 5th week: blue
            ];
            
            // Display all weekdays first
            foreach ($weeks as $i => $w) {
                if (isset($w['weekdays'])) {
                    $colorIdx = ($i-1) % count($weekColors);
                    $weekdayBg = $weekColors[$colorIdx];
                    $sum = sumMetrics($w['weekdays']);
                    echo "<tr class='total-row white-bg' style='background:{$weekdayBg};'><td>".ordinal($i)." Weekdays</td>";
                    foreach ($metricOrder as $metric) {
                        echo "<td colspan='3' style='font-weight:bold;background:{$weekdayBg};'>".formatIndianNumber(isset($sum[$metric]) ? $sum[$metric] : 0)."</td>";
                    }
                    echo "</tr>";
                }
            }
            
            // Then display all weekends
            foreach ($weeks as $i => $w) {
                if (isset($w['weekends'])) {
                    $colorIdx = ($i-1) % count($weekColors);
                    $weekendBg = '#dcedc8'; // All weekends in green
                    $sum = sumMetrics($w['weekends']);
                    echo "<tr class='total-row white-bg' style='background:{$weekendBg};'><td>".ordinal($i)." Weekend</td>";
                    foreach ($metricOrder as $metric) {
                        echo "<td colspan='3' style='font-weight:bold;background:{$weekendBg};'>".formatIndianNumber(isset($sum[$metric]) ? $sum[$metric] : 0)."</td>";
                    }
                    echo "</tr>";
                }
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="copy-buttons">
        <button id="copyTableImage" class="btn btn-default btn-sm mx-1 mb-1" style="margin-left: 8px;">Copy Table as Image</button>
        <button id="copyTableText" class="btn btn-default btn-sm mx-1 mb-1" style="margin-left: 8px;">Copy Table as Text</button>
    </div>
</div>

<div class="modal fade" id="groupDetailsModal" tabindex="-1" aria-labelledby="groupDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width:90vw;width:90vw;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="groupDetailsModalLabel">Group Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:2rem;line-height:1;color:#000;opacity:0.7;background:none;border:none;position:absolute;right:20px;top:10px;z-index:10;">&times;</button>
      </div>
      <div class="modal-body" id="groupDetailsModalBody">
        <!-- Details table will be injected here -->
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
    $('#date').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: { format: 'DD/MM/YYYY' },
        maxDate: moment().subtract(0, 'days')
    });
    
    $('#date').val('<?php echo $selected_date; ?>');
    
    $('#date').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY'));
        var date = $(this).val();
        var url = 'loading.php?pg=keywords.php&tbl_id=<?php echo $client_id; ?>&date=' + encodeURIComponent(date);
        window.location.href = url;
    });
    
    function formatIndianNumber(num) {
        if (num === undefined || num === null || isNaN(num)) return '-';
        return num.toLocaleString('en-IN', {maximumFractionDigits: 0});
    }
    
    $('.expandable-group').off('click').on('click', function() {
        var group = $(this).data('group');
        var groupLabel = $(this).text().replace(/\s*[-+]\s*$/, '');
        var groupSearchTerms = window.groupSearchTerms;
        var dateKeys = Object.keys(window.dateKeys);
        var allTerms = {};
        
        dateKeys.forEach(function(dkey) {
            (groupSearchTerms[dkey][group] || []).forEach(function(row) {
                if (!allTerms[row.search_term]) allTerms[row.search_term] = {};
                allTerms[row.search_term][dkey] = row;
            });
        });
        
        var dateHeaders = document.querySelectorAll('th[data-date-header]');
        var headerDates = [];
        for (var i = 0; i < dateHeaders.length / 7; i++) {
            headerDates.push(dateHeaders[i]?.innerText || '');
        }
        
        var html = '<div style="overflow-x:auto;"><table class="table table-bordered table-sm" style="background:#f9f9f9; min-width:1600px;">';
        html += '<thead>' +
            '<tr>' +
                '<th rowspan="2">Keyword</th>' +
                '<th colspan="3">Spends</th>' +
                '<th colspan="3">Impression</th>' +
                '<th colspan="3">CPM</th>' +
                '<th colspan="3">Clicks</th>' +
                '<th colspan="3">CPC</th>' +
                '<th colspan="3">Leads</th>' +
                '<th colspan="3">CPL</th>' +
            '</tr>' +
            '<tr>';
        for (var i = 0; i < 7; i++) {
            for (var j = 0; j < headerDates.length; j++) {
                html += '<th>' + (headerDates[j] || '-') + '</th>';
            }
        }
        html += '</tr></thead><tbody>';
        
        var terms = Object.keys(allTerms);
        if (terms.length === 0) {
            html += '<tr><td colspan="22" class="text-center">No search terms found for this group.</td></tr>';
        } else {
            terms.forEach(function(term) {
                html += '<tr>' +
                    '<td>' + (term || '(Other)') + '</td>';
                ['spend','impressions','cpm','clicks','cpc','leads','cpl'].forEach(function(metric) {
                    dateKeys.forEach(function(dkey) {
                        var val = allTerms[term][dkey] ? allTerms[term][dkey][metric] : undefined;
                        html += '<td>' + (val !== undefined ? formatIndianNumber(Math.round(val)) : '-') + '</td>';
                    });
                });
                html += '</tr>';
            });
        }
        html += '</tbody></table></div>';
        
        $('#groupDetailsModalLabel').text(groupLabel + ' - Details');
        $('#groupDetailsModalBody').html(html);
        $('#groupDetailsModal').modal('show');
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
document.getElementById('copyTableImage').addEventListener('click', function() {
    const table = document.getElementById('keywordsTable');
    html2canvas(table, {backgroundColor: null, scale: 8}).then(function(canvas) {
        canvas.toBlob(function(blob) {
            if (navigator.clipboard && window.ClipboardItem) {
                // Copy to clipboard as image
                const item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function() {
                    alert('Table image copied to clipboard!');
                }, function(err) {
                    alert('Failed to copy image: ' + err);
                });
            } else {
                // Fallback: open image in new tab
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            }
        });
    });
});
document.getElementById('copyTableText').addEventListener('click', function() {
    const table = document.getElementById('keywordsTable');
    let text = '';
    // Get headers
    const theadRows = table.querySelectorAll('thead tr');
    theadRows.forEach(function(row) {
        let rowText = [];
        row.querySelectorAll('th').forEach(function(cell) {
            rowText.push(cell.innerText.trim());
        });
        text += rowText.join('\t') + '\n';
    });
    // Get body rows
    const tbodyRows = table.querySelectorAll('tbody tr');
    tbodyRows.forEach(function(row) {
        let rowText = [];
        row.querySelectorAll('td').forEach(function(cell) {
            rowText.push(cell.innerText.trim());
        });
        text += rowText.join('\t') + '\n';
    });
    // Copy to clipboard
    navigator.clipboard.writeText(text).then(function() {
        alert('Table text copied to clipboard!');
    }, function(err) {
        alert('Failed to copy text: ' + err);
    });
});
</script>

<?php
// Show all keywords with leads > 0 for all 3 days
$lead_keywords = [];
$day_map = [
    ['data' => $group_search_terms['today'], 'date' => $selected_date],
    ['data' => $group_search_terms['yesterday'], 'date' => $selected_date_prev1],
    ['data' => $group_search_terms['daybefore'], 'date' => $selected_date_prev2],
];

foreach ($day_map as $day) {
    foreach ($day['data'] as $group => $rows) {
        foreach ($rows as $row) {
            if (isset($row['leads']) && $row['leads'] > 0) {
                $lead_keywords[] = [
                    'group' => $group,
                    'keyword' => $row['search_term'],
                    'campaign_name' => isset($row['campaign_name']) ? $row['campaign_name'] : '',
                    'ad_group_name' => isset($row['ad_group_name']) ? $row['ad_group_name'] : '',
                    'leads' => $row['leads'],
                    'date' => $day['date']
                ];
            }
        }
    }
}

$total_leads = array_sum(array_column($lead_keywords, 'leads'));
if (count($lead_keywords) > 0) {
    echo '<br><div class="container mt-4"><h4>Leads by Keywords (Total: ' . $total_leads . ')</h4>';
    echo '<table class="table table-bordered table-sm" id="leadsByKeywordsTable" style="margin:0 auto;">';
    echo '<thead><tr><th>Keyword Group</th><th>Keyword</th><th>Campaign Name</th><th>Adset</th><th>No. of Leads</th><th>Date</th></tr></thead><tbody>';
    foreach ($lead_keywords as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['group']) . '</td>';
        echo '<td>' . htmlspecialchars($row['keyword']) . '</td>';
        echo '<td>' . htmlspecialchars($row['campaign_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['ad_group_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['leads']) . '</td>';
        echo '<td>' . htmlspecialchars($row['date']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    echo '<script>$(function() { $("#leadsByKeywordsTable").DataTable({ pageLength: 25, paging: true, searching: true, ordering: true, info: false, fixedHeader: true, dom: "lfrtip" }); });</script>';
}
?>
</body>
</html>
