<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/google-ads-v15/vendor/autoload.php';
include 'db.php';

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
       campaign.status = 'ENABLED'
        AND segments.date BETWEEN '$start' AND '$end'
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

// =======================
// STEP 1: Fetch all GIDs
// =======================

$allGids = [];

$sqlRev = mysqli_query($conn, "SELECT tbl_id, client_name, g_id, camp_name FROM budget_reminder WHERE uid='2' AND cc_card=1 AND delete_status=0 AND hide_temp='0'");

while ($row = mysqli_fetch_assoc($sqlRev)) {
    if (!empty($row['g_id'])) {
        $gids = array_map('trim', explode(',', $row['g_id']));
        foreach ($gids as $gid) {
            if ($gid !== '') {
                $allGids[] = [
                    'g_id' => $gid,
                    'client_name' => $row['client_name'],
                    'tbl_id' => $row['tbl_id'],
                    'camp_name' => $row['camp_name']
                ];
            }
        }
    }
}

// =============================
// STEP 2: Get MCC & Token Info
// =============================

$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id = 2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$g_ref_tok = $row['g_refresh_token']; // e.g. "1//0g..."
$g_mcc = $row['g_mcc'];               // e.g. "123-456-7890"

// =============================
// STEP 3: Fetch Account Budgets
// =============================

$budgets = GetDailyBudgets::fetchBudgets($g_mcc, $g_ref_tok, $allGids);

// =============================
// STEP 4: Display or Process
// =============================


//d($budgets); exit;
// Optional: write to DB, file, or send email

// Group budgets by bud_tbl_id and sum daily budgets
// Group budgets by bud_tbl_id and sum daily budgets
$grouped = [];

foreach ($budgets as $row) {
    $tblId = $row['tbl_id'];
    $client = $row['client_name'];
    $budget = $row['account_level_daily_budget'];

    if (!isset($grouped[$tblId])) {
        $grouped[$tblId] = [
            'client' => $client,
            'total_budget' => 0
        ];
    }

    $grouped[$tblId]['total_budget'] += $budget;
}

mysqli_query($conn, "TRUNCATE TABLE topup");

// Insert grouped budgets into your table

foreach ($grouped as $tblId => $data) {
    $client = mysqli_real_escape_string($conn, $data['client']);
    $dailyBudget = $data['total_budget'];
    $today = date('Y-m-d H:i:s');

    $insert = "INSERT INTO topup (bud_tbl_id, client, daily_budget, updated)
               VALUES ('$tblId', '$client', '$dailyBudget', '$today')";

    if (!mysqli_query($conn, $insert)) {
        echo "Error inserting tbl_id $tblId: " . mysqli_error($conn) . "<br>";
    }
}


// =============================
// STEP 5: Facebook Budgets
// =============================

function loopAdRep($url) {
    global $output;
    $requests = file_get_contents_curl($url);
    $fb_response = json_decode($requests, true);
    if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = array_merge($output, $fb_response['data']);
    } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = $fb_response['data'];
    }
    if(isset($fb_response['paging']['next'])) {
        loopAdRep($fb_response['paging']['next']);
    } else {
        return $output['data'] = $output;
    }
}

$allFbIds = [];
$sqlRevFb = mysqli_query($conn, "SELECT tbl_id, client_name, fb_id, camp_name FROM budget_reminder WHERE uid='2' AND cc_card=1 AND delete_status=0 AND hide_temp='0'");
while ($row = mysqli_fetch_assoc($sqlRevFb)) {
    if (!empty($row['fb_id'])) {
        $fbids = array_map('trim', explode(',', $row['fb_id']));
        foreach ($fbids as $fbid) {
            if ($fbid !== '') {
                $allFbIds[] = [
                    'fb_id' => $fbid,
                    'client_name' => $row['client_name'],
                    'tbl_id' => $row['tbl_id'],
                    'camp_name' => $row['camp_name']
                ];
            }
        }
    }
}

// Get FB access token and API version
$query = "SELECT access_token FROM users WHERE tbl_id = 2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token'];
$api_ver = 'v18.0';

$fbBudgets = [];
foreach ($allFbIds as $acc) {
    $fbId = $acc['fb_id'];
    $clientName = $acc['client_name'];
    $tblId = $acc['tbl_id'];
    $camp_contains = $acc['camp_name'];
    preg_match('/\[(.*?)\]/', $camp_contains, $matches);
    $filter_keywords = [];
    if (!empty($matches[1])) {
        $filter_keywords = array_map('trim', explode(',', $matches[1]));
    }
    // Combine filters into a single array
    $filtering = [
        [
            "field" => "ad.effective_status",
            "operator" => "IN",
            "value" => ["ACTIVE"]
        ]
    ];
    if (!empty($filter_keywords)) {
        foreach ($filter_keywords as $word) {
            $filtering[] = [
                "field" => "campaign.name",
                "operator" => "CONTAIN",
                "value" => $word
            ];
        }
    }
    $filtering_param = '&filtering=' . urlencode(json_encode($filtering));

    // Get active ad campaigns
    $url_ad = "https://graph.facebook.com/$api_ver/act_$fbId/ads?fields=id,configured_status,status,effective_status,issues_info,campaign_id,adset_id$filtering_param&access_token=$access_token&limit=750";
    $output = [];
    loopAdRep($url_ad);
    $res_ad = $output;
    $act_ads_camp_ids = [];
    if(isset($res_ad) && count($res_ad)>0) {
        foreach($res_ad as $vv) {
            if(isset($vv['effective_status']) && $vv['effective_status']=='ACTIVE'){
                $act_ads_camp_ids[] = $vv['campaign_id'];
            }
        }
    }
    $act_ads_camp_ids = array_unique($act_ads_camp_ids);

    // For campaigns endpoint, use campaign.effective_status
    $filtering_camp = [
        [
            "field" => "campaign.effective_status",
            "operator" => "IN",
            "value" => ["ACTIVE"]
        ]
    ];
    if (!empty($filter_keywords)) {
        foreach ($filter_keywords as $word) {
            $filtering_camp[] = [
                "field" => "campaign.name",
                "operator" => "CONTAIN",
                "value" => $word
            ];
        }
    }
    $filtering_param_camp = '&filtering=' . urlencode(json_encode($filtering_camp));

    // Get campaign/adset budgets
    $url = "https://graph.facebook.com/$api_ver/act_$fbId/campaigns?fields=id,name,bid_strategy,effective_status,daily_budget,lifetime_budget,end_time,adsets.limit(50){id,daily_budget,lifetime_budget,effective_status,end_time,bid_strategy,ads.limit(50){id,adset_id,effective_status,configured_status,status}}$filtering_param_camp&access_token=$access_token&limit=750";
    $output = [];
    loopAdRep($url);
    $res = $output;

    $daily_bud_camp_manual = $daily_bud_camp_auto = $life_bud_camp_manual = $life_bud_camp_auto = [];
    $daily_bud_adset_manual = $daily_bud_adset_auto = $life_bud_adset_manual = $life_bud_adset_auto = [];

    if(isset($res) && count($res)>0){ 
        foreach($res as $v1) {
            $camp_act = 'y';
            if(isset($v1['end_time'])) { 
                $end_time = new DateTime($v1['end_time']);
                $currentDateTime = new DateTime('now', $end_time->getTimezone());
                if($end_time < $currentDateTime) { $camp_act = 'n'; }
            }
            if(isset($v1['effective_status']) && $v1['effective_status']=='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act=='y') {  
                // Campaign-level budgets
                if(isset($v1['daily_budget']) && isset($v1['bid_strategy'])) {
                    if($v1['bid_strategy']=='LOWEST_COST_WITHOUT_CAP') {
                        $daily_bud_camp_auto[] = $v1['daily_budget'];
                    } else {
                        $daily_bud_camp_manual[] = $v1['daily_budget'];
                    }
                }
                if(isset($v1['lifetime_budget']) && isset($v1['bid_strategy'])) {
                    if($v1['bid_strategy']=='LOWEST_COST_WITHOUT_CAP') {
                        $life_bud_camp_auto[] = $v1['lifetime_budget'];
                    } else {
                        $life_bud_camp_manual[] = $v1['lifetime_budget'];
                    }
                }
                // Adset-level budgets
                if(isset($v1['adsets']['data'])){
                    foreach($v1['adsets']['data'] as $as_v) {
                        $ads_act = 'n';
                        if(isset($as_v['effective_status']) && $as_v['effective_status']=='ACTIVE'){
                            if(isset($as_v['ads']['data'])){
                                $endCheck = 'y';
                                if(isset($as_v['end_time'])) { 
                                    $end_time = new DateTime($as_v['end_time']);
                                    $currentDateTime = new DateTime('now', $end_time->getTimezone());
                                    if($end_time < $currentDateTime) { $ads_act = 'n'; $endCheck='n'; }
                                }
                                if($endCheck=='y'){
                                    foreach($as_v['ads']['data'] as $ad_v) {
                                        if(isset($ad_v['effective_status']) && $ad_v['effective_status']=='ACTIVE'){
                                            $ads_act = $camp_act = 'y';
                                        }
                                    }
                                    if(isset($as_v['daily_budget']) && isset($as_v['bid_strategy']) && $ads_act == 'y'){
                                        if($as_v['bid_strategy']=='LOWEST_COST_WITHOUT_CAP') {
                                            $daily_bud_adset_auto[] = $as_v['daily_budget'];
                                        } else {
                                            $daily_bud_adset_manual[] = $as_v['daily_budget'];
                                        }
                                    }
                                    if(isset($as_v['lifetime_budget']) && isset($as_v['bid_strategy']) && $ads_act == 'y'){
                                        if($as_v['bid_strategy']=='LOWEST_COST_WITHOUT_CAP') {
                                            $life_bud_adset_auto[] = $as_v['lifetime_budget'];
                                        } else {
                                            $life_bud_adset_manual[] = $as_v['lifetime_budget'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $camp_daily_bud_m = $camp_daily_bud_a = $camp_life_bud_m = $camp_life_bud_a = 0;
    $adset_daily_bud_m = $adset_daily_bud_a = $adset_life_bud_m = $adset_life_bud_a = 0;

    if(count($daily_bud_camp_manual)>0) { $camp_daily_bud_m = round(array_sum($daily_bud_camp_manual)/100); }
    if(count($life_bud_camp_manual)>0) { $camp_life_bud_m = round(array_sum($life_bud_camp_manual)/100); }
    if(count($daily_bud_camp_auto)>0) { $camp_daily_bud_a = round(array_sum($daily_bud_camp_auto)/100); }
    if(count($life_bud_camp_auto)>0) { $camp_life_bud_a = round(array_sum($life_bud_camp_auto)/100); }

    if(count($daily_bud_adset_manual)>0) { $adset_daily_bud_m = round(array_sum($daily_bud_adset_manual)/100); }
    if(count($life_bud_adset_manual)>0) { $adset_life_bud_m = round(array_sum($life_bud_adset_manual)/100); }
    if(count($daily_bud_adset_auto)>0) { $adset_daily_bud_a = round(array_sum($daily_bud_adset_auto)/100); }
    if(count($life_bud_adset_auto)>0) { $adset_life_bud_a = round(array_sum($life_bud_adset_auto)/100); }

    $total_daily_budget = $camp_daily_bud_m + $camp_daily_bud_a + $adset_daily_bud_m + $adset_daily_bud_a;

    $fbBudgets[] = [
        'client_name' => $clientName,
        'fb_id' => $fbId,
        'tbl_id' => $tblId,
        'account_level_daily_budget' => $total_daily_budget
    ];
}

// Collect daily budgets per tbl_id as an array
$fbBudgetMap = [];
foreach ($fbBudgets as $row) {
    $tblId = $row['tbl_id'];
    $fbBudgetMap[$tblId][] = $row['account_level_daily_budget'];
}

// Store as comma-separated string in daily_budget_fb
foreach ($fbBudgetMap as $tblId => $budgetsArr) {
    $client = '';
    foreach ($allFbIds as $acc) {
        if ($acc['tbl_id'] == $tblId) {
            $client = mysqli_real_escape_string($conn, $acc['client_name']);
            break;
        }
    }
    $dailyBudgetStr = implode(',', $budgetsArr);
    $today = date('Y-m-d H:i:s');
    $check = mysqli_query($conn, "SELECT bud_tbl_id FROM topup WHERE bud_tbl_id='$tblId' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $update = "UPDATE topup SET daily_budget_fb='$dailyBudgetStr', updated='$today' WHERE bud_tbl_id='$tblId'";
        if (!mysqli_query($conn, $update)) {
            echo "Error updating tbl_id $tblId: " . mysqli_error($conn) . "<br>";
        }
    } else {
        $insert = "INSERT INTO topup (bud_tbl_id, client, daily_budget_fb, updated) VALUES ('$tblId', '$client', '$dailyBudgetStr', '$today')";
        if (!mysqli_query($conn, $insert)) {
            echo "Error inserting tbl_id $tblId: " . mysqli_error($conn) . "<br>";
        }
    }
}

header("Location: topup.php");
    exit();