<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '5000M');
error_reporting(E_ALL);

//session_start();

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;
use Google\Ads\GoogleAds\V20\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;

class GetDailyBudgets
{
    public static function fetchBudgets($g_mcc, $g_ref_tok, $allGids)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile()
            ->withRefreshToken($g_ref_tok)
            ->build();

        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile()
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId((int)str_replace('-', '', $g_mcc))
            ->build();

        $results = [];

        foreach ($allGids as $acc) {
            $customerId = $acc['g_id'];
            $clientName = $acc['client_name'];
            $tblId = $acc['tbl_id'];
            $camp_contains = $acc['camp_name'];
            preg_match('/\[(.*?)\]/', $camp_contains, $matches);
            $filter_keywords = [];
            if (!empty($matches[1])) {
                $filter_keywords = array_map('trim', explode(',', $matches[1]));
            }
            $filtering_param_g = '';
            $like_clauses = [];
            if (!empty($filter_keywords)) {
                foreach ($filter_keywords as $word) {
                    $word = trim($word);
                    $like_clauses[] = "campaign.name LIKE '%" . $word . "%'";
                }
                if (count($like_clauses) > 1) {
                    $filtering_param_g = ' AND (' . implode(' OR ', $like_clauses) . ')';
                } elseif (count($like_clauses) === 1) {
                    $filtering_param_g = ' AND ' . $like_clauses[0];
                }
            }
            try {
                $accountBudget = self::getBudgetsForAccount($googleAdsClient, $customerId, $filtering_param_g);
                $results[] = [
                    'client_name' => $clientName,
                    'g_id' => $customerId,
                    'tbl_id' => $tblId,
                    'account_level_daily_budget' => $accountBudget['account_daily_budget']
                ];
            } catch (GoogleAdsException | ApiException $e) {
                echo "Error for account {$customerId}: " . $e->getMessage() . "<br>";
                continue;
            }
        }

        return $results;
    }

    private static function getBudgetsForAccount($googleAdsClient, $customerId, $filtering_param_g = '')
    {
        $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
        $start = date('Y-m-d', strtotime('-30 days'));
        $end = date('Y-m-d');
        $query = "
           SELECT
        campaign.id,
        campaign.name,
        campaign.status,
        campaign_budget.id,
        campaign_budget.amount_micros
    FROM campaign
    WHERE
       
         segments.date BETWEEN '$start' AND '$end'
        AND metrics.impressions > 0
        $filtering_param_g
        ";

        $request = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $query,
        ]);

        $stream = $googleAdsService->searchStream($request);

        $totalMicros = 0;
        $seenBudgets = [];

        foreach ($stream->iterateAllElements() as $row) {
            /** @var GoogleAdsRow $row */
            $campaign = $row->getCampaign();
            $budget = $row->getCampaignBudget();
            $budgetMicros = $budget->getAmountMicros();
            $budgetId = $budget->getId();
           // echo $campaign->getName().' - '.($budgetMicros/1000000).'<br>';
            // Avoid double-counting shared budgets
            if (!isset($seenBudgets[$budgetId])) {
                $seenBudgets[$budgetId] = true;
                $totalMicros += $budgetMicros;
            }
        }

        return [
            'account_daily_budget' => round($totalMicros / 1000000) // convert micros to standard unit
        ];
    }
}

class GoogleAdsAccountReport
{
    public static function fetchAccountReport($googleAdsClient, $customerId, $dateRanges)
    {
        $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
        $results = [];

        foreach ($dateRanges as $label => $range) {
            $query = "
                SELECT
                    campaign.advertising_channel_type,
                    metrics.cost_micros,
                    metrics.conversions,
                    metrics.impressions,
                    metrics.clicks
                FROM campaign
                WHERE segments.date BETWEEN '{$range['start']}' AND '{$range['end']}'
            ";

            $request = new SearchGoogleAdsStreamRequest([
                'customer_id' => $customerId,
                'query' => $query,
            ]);

            $stream = $googleAdsService->searchStream($request);

            $split = [];
            $total = [
                'spend' => 0,
                'conversions' => 0,
                'impressions' => 0,
                'clicks' => 0,
            ];

            foreach ($stream->iterateAllElements() as $row) {
                 $type = $row->getCampaign()->getAdvertisingChannelType();
                $metrics = $row->getMetrics();

                $spend = $metrics->getCostMicros() / 1000000;
                $conversions = $metrics->getConversions();
                $impressions = $metrics->getImpressions();
                $clicks = $metrics->getClicks();

                // Split by type
                if (!isset($split[$type])) {
                    $split[$type] = [
                        'spend' => 0,
                        'conversions' => 0,
                        'impressions' => 0,
                        'clicks' => 0,
                    ];
                }
                $split[$type]['spend'] += $spend;
                $split[$type]['conversions'] += $conversions;
                $split[$type]['impressions'] += $impressions;
                $split[$type]['clicks'] += $clicks;

                // Total
                $total['spend'] += $spend;
                $total['conversions'] += $conversions;
                $total['impressions'] += $impressions;
                $total['clicks'] += $clicks;
            }

            // Calculate CPM, CPC, CPL
            foreach ($split as $type => &$data) {
                $data['cpm'] = ($data['impressions'] > 0) ? ($data['spend'] / $data['impressions']) * 1000 : 0;
                $data['cpc'] = ($data['clicks'] > 0) ? ($data['spend'] / $data['clicks']) : 0;
                $data['cpl'] = ($data['conversions'] > 0) ? ($data['spend'] / $data['conversions']) : 0;
            }
            unset($data);
            $total['cpm'] = ($total['impressions'] > 0) ? ($total['spend'] / $total['impressions']) * 1000 : 0;
            $total['cpc'] = ($total['clicks'] > 0) ? ($total['spend'] / $total['clicks']) : 0;
            $total['cpl'] = ($total['conversions'] > 0) ? ($total['spend'] / $total['conversions']) : 0;

            $results[$label] = [
                'split' => $split,
                'total' => $total
            ];
        }

        return $results;
    }
}

// Function to get keyword groups from client data
function getClientKeywordGroups($client_data) {
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

    $groups = [];
    foreach($keyw_name as $index => $name) {
        $include_terms = isset($keyw_contain[$index]) ? array_map('trim', explode(',', $keyw_contain[$index])) : [];
        $exclude_terms = isset($keyw_exclude[$index]) ? array_map('trim', explode(',', $keyw_exclude[$index])) : [];
        
        $groups[$name] = [
            'include' => array_filter($include_terms),
            'exclude' => array_filter($exclude_terms)
        ];
    }
    
    return $groups;
}

function getSearchTermGroupConversions($googleAdsClient, $customerId, $dateRange, $groups) {
    $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();
    $query = "
        SELECT
            search_term_view.search_term,
            metrics.conversions
        FROM search_term_view
        WHERE
            segments.date BETWEEN '{$dateRange['start']}' AND '{$dateRange['end']}'
    ";

    $request = new SearchGoogleAdsStreamRequest([
        'customer_id' => $customerId,
        'query' => $query,
    ]);

    $stream = $googleAdsService->searchStream($request);

    $groupTotals = [];
    foreach ($groups as $groupName => $rules) {
        $groupTotals[$groupName] = 0;
    }
    $totalConversions = 0;

    foreach ($stream->iterateAllElements() as $row) {
        $searchTerm = strtolower($row->getSearchTermView()->getSearchTerm());
        $conversions = $row->getMetrics()->getConversions();
        $totalConversions += $conversions;

        $matched = false;
        foreach ($groups as $groupName => $rules) {
            // Check include
            $included = false;
            if(empty($rules['include'])) {
                $included = true; // If no include terms, include all
            } else {
                foreach ($rules['include'] as $inc) {
                    if (!empty($inc) && stripos($searchTerm, strtolower($inc)) !== false) {
                        $included = true;
                        break;
                    }
                }
            }
            if (!$included) continue;
            
            // Check exclude
            $excluded = false;
            foreach ($rules['exclude'] as $exc) {
                if (!empty($exc) && stripos($searchTerm, strtolower($exc)) !== false) {
                    $excluded = true;
                    break;
                }
            }
            if ($excluded) continue;
            
            // If included and not excluded, count for this group
            $groupTotals[$groupName] += $conversions;
            $matched = true;
            break; // Only count once per group
        }
    }
    $groupTotals['others'] = $totalConversions - array_sum($groupTotals);
    return $groupTotals;
}

// Function to check if session data needs to be refreshed (once per day)
function shouldRefreshSessionData() {
    $today = date('Y-m-d');
    if (!isset($_SESSION['last_data_refresh']) || $_SESSION['last_data_refresh'] !== $today) {
        $_SESSION['last_data_refresh'] = $today;
        return true;
    }
    return false;
}

// Function to format numbers in Indian format
function formatIndianNumber($num) {
    if ($num === null || $num === '' || !is_numeric($num)) {
        return '0';
    }
    
    $number = floatval($num);
    if ($number == 0) {
        return '0';
    }
    
    // For numbers with decimals (like CPM, CPC, CPL)
    if (fmod($number, 1) != 0) {
        return number_format($number, 2, '.', ',');
    }
    
    // For whole numbers
    return number_format($number, 0, '.', ',');
}

// Get client data and user credentials
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

// Get Google account IDs
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

// Get keyword groups from client data
$groups = getClientKeywordGroups($client_data);
if(empty($groups)) {
    echo "<div class='alert alert-warning'>No keyword groups configured for this client.</div>";
    exit();
}

// Check if we need to fetch daily budget (store in session)
if (!isset($_SESSION['daily_budget_'.$client_id]) || shouldRefreshSessionData()) {
    $budgets = GetDailyBudgets::fetchBudgets($g_mcc, $g_ref_tok, $allGids);
    $_SESSION['daily_budget_'.$client_id] = isset($budgets[0]['account_level_daily_budget']) ? $budgets[0]['account_level_daily_budget'] : 0;
} else {
    $budgets = [['account_level_daily_budget' => $_SESSION['daily_budget_'.$client_id]]];
}

$dateRanges = [
    'custom' => ['start' => date("Y-m-d", strtotime($dtRange1)), 'end' => date("Y-m-d", strtotime($dtRange2))],
    'yesterday' => [
        'start' => date('Y-m-d', strtotime('-1 day')),
        'end' => date('Y-m-d', strtotime('-1 day')),
    ],
    'last_7_days' => [
        'start' => date('Y-m-d', strtotime('-7 days')),
        'end' => date('Y-m-d', strtotime('-1 day')),
    ],
    'this_month' => ['start' => date('Y-m-01'), 'end' => date('Y-m-d')],
    'last_month' => [
        'start' => date('Y-m-01', strtotime('first day of last month')),
        'end' => date('Y-m-t', strtotime('last day of last month')),
    ],
];
$dateRanges_label = array($cust_range, 'Yesterday', 'Last 7 Days', 'This Month', 'Last Month');

$oAuth2Credential = (new OAuth2TokenBuilder())
    ->fromFile()
    ->withRefreshToken($g_ref_tok)
    ->build();

$googleAdsClient = (new GoogleAdsClientBuilder())
    ->fromFile()
    ->withOAuth2Credential($oAuth2Credential)
    ->withLoginCustomerId((int)str_replace('-', '', $g_mcc))
    ->build();

// Initialize report array
$report = [];

// Check if we need to fetch cached data (yesterday, last 7 days and this month)
$needToFetchCachedData = shouldRefreshSessionData() || 
                        !isset($_SESSION['cached_report_data_'.$client_id]) || 
                        !isset($_SESSION['cached_search_terms_'.$client_id]);

if ($needToFetchCachedData) {
    // Fetch yesterday, last_7_days, this_month, and last_month data
    $cachedDateRanges = [
        'yesterday' => $dateRanges['yesterday'],
        'last_7_days' => $dateRanges['last_7_days'],
        'this_month' => $dateRanges['this_month'],
        'last_month' => $dateRanges['last_month'],
    ];
    
    $cachedReport = GoogleAdsAccountReport::fetchAccountReport($googleAdsClient, $g_acc_ids[0], $cachedDateRanges);
    
    // Store in session
    $_SESSION['cached_report_data_'.$client_id] = $cachedReport;
    
    // Also fetch and cache search term data for these ranges
    $cachedSearchTerms = [];
    foreach ($cachedDateRanges as $label => $range) {
        $cachedSearchTerms[$label] = getSearchTermGroupConversions($googleAdsClient, $g_acc_ids[0], $range, $groups);
    }
    $_SESSION['cached_search_terms_'.$client_id] = $cachedSearchTerms;
    $_SESSION['cached_groups_'.$client_id] = $groups;
}

// Always fetch custom date range data (this changes with date picker)
$customReport = GoogleAdsAccountReport::fetchAccountReport($googleAdsClient, $g_acc_ids[0], ['custom' => $dateRanges['custom']]);

// Combine cached data with custom data
$report = array_merge($_SESSION['cached_report_data_'.$client_id], $customReport);

// Get search terms for custom range only
$customSearchTerms = getSearchTermGroupConversions($googleAdsClient, $g_acc_ids[0], $dateRanges['custom'], $groups);

// Combine cached search terms with custom search terms
$allSearchTerms = array_merge($_SESSION['cached_search_terms_'.$client_id], ['custom' => $customSearchTerms]);

// Table 1: Keyword-based Total Conversions
echo "<h3>Conversions by Keyword</h3>";
echo "<table class='table table-bordered' id='table1'><thead><tr><th>Keywords</th>";
foreach ($dateRanges_label as $label => $v) {
    if($label==0) {
        echo "<th class='cust_range'>" . $cust_range . "</th>";
    } else {
        echo "<th>" . ucfirst(str_replace('_', ' ', $v)) . "</th>";
    }
}
echo "</tr></thead><tbody>";
foreach ($groups as $groupName => $terms) {
    echo "<tr><td>" . ucfirst($groupName) . "</td>";
    foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
        $totals = $allSearchTerms[$label];
        $value = isset($totals[$groupName]) ? $totals[$groupName] : 0;
        echo "<td>" . formatIndianNumber($value) . "</td>";
    }
    echo "</tr>";
}
// Add "Others" row
echo "<tr><td>Others</td>";
foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
    $totals = $allSearchTerms[$label];
    $value = isset($totals['others']) ? $totals['others'] : 0;
    echo "<td>" . formatIndianNumber($value) . "</td>";
}
echo "</tr></tbody>";

// Add TOTAL row for Table 1
$totals_row = array('Total');
foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
    $sum = 0;
    foreach ($groups as $groupName => $terms) {
        $totals = $allSearchTerms[$label];
        $sum += isset($totals[$groupName]) ? $totals[$groupName] : 0;
    }
    $sum += isset($allSearchTerms[$label]['others']) ? $allSearchTerms[$label]['others'] : 0;
    $totals_row[] = formatIndianNumber($sum);
}
echo "<tfoot><tr>";
foreach ($totals_row as $cell) {
    echo "<td><b>$cell</b></td>";
}
echo "</tr>";
echo "</tfoot></table>";

// Map campaign type integer keys to readable names (Google Ads API enum values)
$campaignTypeMap = [
    2 => 'Search',
    3 => 'Display',
    4 => 'Shopping',
    5 => 'Hotel',
    6 => 'Video',
    7 => 'Multi Channel',
    8 => 'Local',
    9 => 'Smart',
    10 => 'Performance Max',
    11 => 'Local Services',
    12 => 'Discovery',
    13 => 'Demand Gen',
    14 => 'Demand Gen',
];

// Table 2: CPM
echo "<br><h3>CPM</h3>";
echo "<table class='table table-bordered' id='table2'><thead><tr><th>CPM</th>";
foreach ($dateRanges_label as $label => $v) {
    if($label==0) {
        echo "<th class='cust_range'>" . $cust_range . "</th>";
    } else {
        echo "<th>" . ucfirst(str_replace('_', ' ', $v)) . "</th>";
    }
}
echo "</tr></thead><tbody>";
$channels = [];
foreach ($report as $label => $data) {
    foreach ($data['split'] as $channel => $channelData) {
        $channels[$channel] = true;
    }
}
foreach (array_keys($channels) as $channel) {
    $displayName = isset($campaignTypeMap[$channel]) ? $campaignTypeMap[$channel] : "Unknown ($channel)";
    $allZero = true;
    $rowVals = [];
    foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
        $val = isset($report[$label]['split'][$channel]['cpm']) ? $report[$label]['split'][$channel]['cpm'] : 0;
        $rowVals[] = $val;
        if ($val != 0) $allZero = false;
    }
    if ($allZero) continue;
    echo "<tr><td>" . $displayName . "</td>";
    foreach ($rowVals as $val) {
        echo "<td>" . formatIndianNumber($val) . "</td>";
    }
    echo "</tr>";
}
echo "</tbody><tfoot><tr><td><b>Total (Avg. CPM)</b></td>";
foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
    $val = isset($report[$label]['total']['cpm']) ? $report[$label]['total']['cpm'] : 0;
    echo "<td><b>" . formatIndianNumber($val) . "</b></td>";
}
echo "</tr></tfoot></table>";

// Table 3: CPC
echo "<br><h3>CPC</h3>";
echo "<table class='table table-bordered' id='table3'><thead><tr><th>CPC</th>";
foreach ($dateRanges_label as $label => $v) {
    if($label==0) {
        echo "<th class='cust_range'>" . $cust_range . "</th>";
    } else {
        echo "<th>" . ucfirst(str_replace('_', ' ', $v)) . "</th>";
    }
}
echo "</tr></thead><tbody>";
foreach (array_keys($channels) as $channel) {
    $displayName = isset($campaignTypeMap[$channel]) ? $campaignTypeMap[$channel] : "Unknown ($channel)";
    $allZero = true;
    $rowVals = [];
    foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
        $val = isset($report[$label]['split'][$channel]['cpc']) ? $report[$label]['split'][$channel]['cpc'] : 0;
        $rowVals[] = $val;
        if ($val != 0) $allZero = false;
    }
    if ($allZero) continue;
    echo "<tr><td>" . $displayName . "</td>";
    foreach ($rowVals as $val) {
        echo "<td>" . formatIndianNumber($val) . "</td>";
    }
    echo "</tr>";
}
echo "</tbody><tfoot><tr><td><b>Total (Avg. CPC)</b></td>";
foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
    $val = isset($report[$label]['total']['cpc']) ? $report[$label]['total']['cpc'] : 0;
    echo "<td><b>" . formatIndianNumber($val) . "</b></td>";
}
echo "</tr></tfoot></table>";

// Table 4: CPL
echo "<br><h3>CPL</h3>";
echo "<table class='table table-bordered' id='table4'><thead><tr><th>CPL</th>";
foreach ($dateRanges_label as $label => $v) {
     if($label==0) {
        echo "<th class='cust_range'>" . $cust_range . "</th>";
    } else {
        echo "<th>" . ucfirst(str_replace('_', ' ', $v)) . "</th>";
    }
}
echo "</tr></thead><tbody>";
foreach (array_keys($channels) as $channel) {
    $displayName = isset($campaignTypeMap[$channel]) ? $campaignTypeMap[$channel] : "Unknown ($channel)";
    $allZero = true;
    $rowVals = [];
    foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
        $val = isset($report[$label]['split'][$channel]['cpl']) ? $report[$label]['split'][$channel]['cpl'] : 0;
        $rowVals[] = $val;
        if ($val != 0) $allZero = false;
    }
    if ($allZero) continue;
    echo "<tr><td>" . $displayName . "</td>";
    foreach ($rowVals as $val) {
        echo "<td>" . formatIndianNumber($val) . "</td>";
    }
    echo "</tr>";
}
echo "</tbody><tfoot><tr><td><b>Total (Avg. CPL)</b></td>";
foreach (['custom', 'yesterday', 'last_7_days', 'this_month', 'last_month'] as $label) {
    $val = isset($report[$label]['total']['cpl']) ? $report[$label]['total']['cpl'] : 0;
    echo "<td><b>" . formatIndianNumber($val) . "</b></td>";
}
echo "</tr></tfoot></table>";

// Underspend / Overspend Table
$daily_budget = isset($budgets[0]['account_level_daily_budget']) ? $budgets[0]['account_level_daily_budget'] : 0;
$custom_spend = isset($report['custom']['total']['spend']) ? $report['custom']['total']['spend'] : 0;
$custom_start = $dateRanges['custom']['start'];
$custom_end = $dateRanges['custom']['end'];
$start_date = new DateTime($custom_start);
$end_date = new DateTime($custom_end);
$interval = $start_date->diff($end_date);
$num_days = $interval->days + 1;
$expected_spend = $daily_budget * $num_days;
$us_os_percentage = $expected_spend > 0 ? (($custom_spend - $expected_spend) / $expected_spend) * 100 : 0;
echo "<br><h3>Underspend / Overspend</h3>";
echo "<table class='table table-bordered' id='table5'><thead><tr><th>Daily Budget</th><th class='cust_range'>".$cust_range."</th><th>No. of Days</th><th>US/OS Percentage</th></tr></thead><tbody>";
echo "<tr>";
echo "<td>" . formatIndianNumber($daily_budget) . "</td>";
echo "<td>" . formatIndianNumber($custom_spend) . "</td>";
echo "<td>" . formatIndianNumber($num_days) . "</td>";
echo "<td>" . formatIndianNumber($us_os_percentage) . "%</td>";
echo "</tr>";
echo "</tbody></table>";

// Add blue header style for all report tables
echo '<style>
.table thead th { background: #2196f3 !important; color: #fff !important; }
</style>';
?>