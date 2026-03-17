<?php
// all-reports.php
// Flexible reporting UI + API for Meta + Google Ads (config‑driven)

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| CONFIG (keys + endpoints live here, not in logic)
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../env_loader.php';
$OPENAI_API_KEY = getenv('OPENAI_API_KEY');

$CONFIG_API_URL = (getenv('SITE_URL') ? rtrim(getenv('SITE_URL'), '/') : 'https://stage.adrescue.in') . '/config-api.php';
$GOOGLE_DEVELOPER_TOKEN = getenv('GOOGLE_DEVELOPER_TOKEN');
$OPENAI_MODEL = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';

// All reporting endpoints, fields and GAQL live here
$REPORT_CONFIG = [
    'meta' => [
        'base_url' => 'https://graph.facebook.com',
        'version'  => 'v24.0',
        'levels'   => [
            'campaign' => [
                'endpoint' => '/act_{account_id}/insights',
                'params'   => [
                    'level'  => 'campaign',
                    'fields' => [
                        'campaign_id', 'campaign_name',
                        'impressions', 'clicks', 'spend', 'actions'
                    ],
                    'limit'  => 5000
                ]
            ],
            'adset' => [
                'endpoint' => '/act_{account_id}/insights',
                'params'   => [
                    'level'  => 'adset',
                    'fields' => [
                        'campaign_id', 'campaign_name',
                        'adset_id', 'adset_name',
                        'impressions', 'clicks', 'spend', 'actions'
                    ],
                    'limit'  => 5000
                ]
            ],
            'ad' => [
                'endpoint' => '/act_{account_id}/insights',
                'params'   => [
                    'level'  => 'ad',
                    'fields' => [
                        'campaign_id', 'campaign_name',
                        'adset_id', 'adset_name',
                        'ad_id', 'ad_name',
                        'impressions', 'clicks', 'spend', 'actions'
                    ],
                    'limit'  => 5000
                ]
            ],
        ],
        // Metric formulas are implemented in PHP but documented here
        'metrics' => [
            'spend'      => ['type' => 'sum'],
            'leads'      => ['type' => 'sum'],
            'conversions'=> ['type' => 'sum'],
            'cpl'        => ['type' => 'ratio', 'num' => 'spend', 'den' => 'leads', 'smaller_is_better' => true],
            'cpa'        => ['type' => 'ratio', 'num' => 'spend', 'den' => 'leads+conversions', 'smaller_is_better' => true],
            'cpc'        => ['type' => 'ratio', 'num' => 'spend', 'den' => 'clicks', 'smaller_is_better' => true],
            'ctr'        => ['type' => 'ratio', 'num' => 'clicks', 'den' => 'impressions', 'percent' => true],
            'hook_rate'  => ['type' => 'ratio', 'num' => 'video_views', 'den' => 'impressions', 'percent' => true],
        ],
    ],
    'google' => [
        'base_url' => 'https://googleads.googleapis.com',
        'version'  => 'v20',
        'levels'   => [
            'campaign' => [
                'gaql' => "
                    SELECT
                      campaign.id,
                      campaign.name,
                      metrics.impressions,
                      metrics.clicks,
                      metrics.cost_micros,
                      metrics.conversions
                    FROM campaign
                    WHERE segments.date BETWEEN '%FROM%' AND '%TO%'
                "
            ],
            'adset' => [
                'gaql' => "
                    SELECT
                      ad_group.id,
                      ad_group.name,
                      campaign.id,
                      campaign.name,
                      metrics.impressions,
                      metrics.clicks,
                      metrics.cost_micros,
                      metrics.conversions
                    FROM ad_group
                    WHERE segments.date BETWEEN '%FROM%' AND '%TO%'
                "
            ],
            'ad' => [
                'gaql' => "
                    SELECT
                      ad_group_ad.ad.id,
                      ad_group_ad.ad.name,
                      ad_group.id,
                      ad_group.name,
                      campaign.id,
                      campaign.name,
                      metrics.impressions,
                      metrics.clicks,
                      metrics.cost_micros,
                      metrics.conversions
                    FROM ad_group_ad
                    WHERE segments.date BETWEEN '%FROM%' AND '%TO%'
                "
            ],
        ],
        'metrics' => [
            'spend'      => ['type' => 'sum'],
            'leads'      => ['type' => 'sum'],
            'conversions'=> ['type' => 'sum'],
            'cpl'        => ['type' => 'ratio', 'num' => 'spend', 'den' => 'leads', 'smaller_is_better' => true],
            'cpa'        => ['type' => 'ratio', 'num' => 'spend', 'den' => 'conversions', 'smaller_is_better' => true],
            'cpc'        => ['type' => 'ratio', 'num' => 'spend', 'den' => 'clicks', 'smaller_is_better' => true],
            'ctr'        => ['type' => 'ratio', 'num' => 'clicks', 'den' => 'impressions', 'percent' => true],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/
$userQueryRaw = trim($_GET['q'] ?? '');
$userQuery    = strtolower($userQueryRaw);

if (!$userQuery) {
    exit(json_encode(["status" => false, "message" => "Query missing"], JSON_PRETTY_PRINT));
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function curlJson($url, $headers = [], $postBody = null, $timeout = 15) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
    ];
    if ($postBody !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $postBody;
    }
    curl_setopt_array($ch, $opts);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$res, $err, $http];
}

function tokenizeQuery($q) {
    $q = strtolower($q);
    $q = preg_replace('/[^a-z0-9\s\-]/', ' ', $q);
    $parts = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

    $stop = [
        'what','is','are','was','were','show','give','report','this','month','last','week','today','yesterday',
        'spend','spent','cost','cpl','cpa','lead','leads','conversion','conversions','purchase','sales','revenue',
        'meta','facebook','google','ads','ad','for','of','in','on','from','to','between','top','best','worst'
    ];

    $tokens = [];
    foreach ($parts as $p) {
        if (strlen($p) < 2) continue;
        if (in_array($p, $stop, true)) continue;
        $tokens[] = $p;
    }
    return array_values(array_unique($tokens));
}

function scoreAccountMatch($acc, $query, $tokens) {
    $client = strtolower(trim($acc['client_name'] ?? ''));
    $nic    = strtolower(trim($acc['nic_name'] ?? ''));
    $queryLower = strtolower($query);

    $score = 0;
    $matchedTokens = 0;

    foreach ($tokens as $t) {
        if ($nic && $t === $nic) {
            $score += 100;
            $matchedTokens++;
        }
    }

    foreach ($tokens as $t) {
        if (strlen($t) >= 2 && strpos($client, $t) !== false) {
            $score += 70;
            $matchedTokens++;
        }
    }

    foreach ($tokens as $t) {
        if ($nic && strpos($nic, $t) !== false) {
            $score += 40;
            $matchedTokens++;
        }
    }

    if ($nic && strpos($queryLower, $nic) !== false) {
        $score += 30;
    }

    $firstWord = trim(strtok($client, " "));
    if ($firstWord && strlen($firstWord) >= 2 && strpos($queryLower, $firstWord) !== false) {
        $score += 15;
    }

    if ($matchedTokens > 1) {
        $score += ($matchedTokens - 1) * 10;
    }

    return $score;
}

// Reuse same date-range parser logic style as index.php (no ChatGPT for dates)
function resolveDateRangeFromQuery($query) {
    $q = strtolower($query);
    $today = date('Y-m-d');

    if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(to|till|until|-)\s+(\d{4}-\d{2}-\d{2})/i', $q, $m)) {
        return ['from' => $m[1], 'to' => $m[3]];
    }

    if (preg_match('/(jan|january|feb|february|mar|march|apr|april|may|jun|june|jul|july|aug|august|sep|september|oct|october|nov|november|dec|december)\s+(\d{1,2})(?:st|nd|rd|th)?\s+(to|till|until)/i', $q, $m)) {
        $monthName = strtolower($m[1]);
        $day       = (int)$m[2];
        $monthMap  = [
            'jan' => 1, 'january' => 1, 'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3, 'apr' => 4, 'april' => 4,
            'may' => 5, 'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7, 'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9, 'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11, 'dec' => 12, 'december' => 12
        ];
        $monthNum = $monthMap[$monthName] ?? date('n');
        $year     = date('Y');
        return [
            'from' => sprintf('%04d-%02d-%02d', $year, $monthNum, $day),
            'to'   => $today
        ];
    }

    if (preg_match('/last\s+month\s+(to|till|until)/i', $q)) {
        $firstDayLastMonth = date('Y-m-01', strtotime('first day of last month'));
        return ['from' => $firstDayLastMonth, 'to' => $today];
    }

    if (preg_match('/(?:the\s+)?(?:last|past)\s+(\d+)\s+days?/i', $q, $m)) {
        $days = max(1, (int)$m[1]);
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        return ['from' => $from, 'to' => $today];
    }

    if (preg_match('/last\s+month/i', $q) && strpos($q, 'to') === false && strpos($q, 'till') === false && strpos($q, 'until') === false) {
        $firstDay = date('Y-m-01', strtotime('first day of last month'));
        $lastDay  = date('Y-m-t', strtotime('first day of last month'));
        return ['from' => $firstDay, 'to' => $lastDay];
    }

    if (strpos($q, 'yesterday') !== false) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return ['from' => $yesterday, 'to' => $yesterday];
    }

    if (strpos($q, 'today') !== false && strpos($q, 'to') === false && strpos($q, 'till') === false && strpos($q, 'until') === false) {
        return ['from' => $today, 'to' => $today];
    }

    if (strpos($q, 'last week') !== false) {
        $lastMonday = date('Y-m-d', strtotime('last monday'));
        $lastSunday = date('Y-m-d', strtotime('last sunday'));
        return ['from' => $lastMonday, 'to' => $lastSunday];
    }

    return ['from' => date('Y-m-01'), 'to' => $today];
}

/*
|--------------------------------------------------------------------------
| STEP 1: Load config api
|--------------------------------------------------------------------------
*/
[$cfgRes, $cfgErr, $cfgHttp] = curlJson($CONFIG_API_URL, [], null, 15);
if ($cfgErr || $cfgHttp >= 400) {
    exit(json_encode([
        "status" => false,
        "message" => "Config API error",
        "http" => $cfgHttp,
        "error" => $cfgErr
    ], JSON_PRETTY_PRINT));
}

$config = json_decode($cfgRes, true);
if (!is_array($config) || empty($config['data']['accounts']['list'])) {
    exit(json_encode([
        "status" => false,
        "message" => "Config JSON invalid or accounts missing"
    ], JSON_PRETTY_PRINT));
}

$accounts = $config['data']['accounts']['list'];
$user     = $config['data']['user'] ?? [];

/*
|--------------------------------------------------------------------------
| STEP 2: Client match
|--------------------------------------------------------------------------
*/
$tokens = tokenizeQuery($userQuery);

if (empty($tokens)) {
    preg_match_all('/\b[a-z0-9]{3,}\b/i', $userQuery, $m);
    $tokens = array_values(array_unique(array_map('strtolower', $m[0] ?? [])));
}

$bestScore = 0;
$bestClientName = null;
$matched = [];

foreach ($accounts as $acc) {
    $s = scoreAccountMatch($acc, $userQuery, $tokens);
    if ($s > $bestScore) {
        $bestScore = $s;
        $bestClientName = $acc['client_name'];
    }
}

if ($bestScore > 0 && $bestClientName !== null) {
    foreach ($accounts as $acc) {
        if (($acc['client_name'] ?? '') === $bestClientName) {
            $matched[] = $acc;
        }
    }
}

if (empty($matched)) {
    exit(json_encode([
        "status" => false,
        "message" => "Client not found",
        "debug" => ["tokens" => $tokens, "bestScore" => $bestScore]
    ], JSON_PRETTY_PRINT));
}

/*
|--------------------------------------------------------------------------
| STEP 3: Ask ChatGPT for reporting intent (entity, metric, ranking)
|--------------------------------------------------------------------------
*/
function askChatGPTForReportIntent($question, $apiKey, $model) {
    $prompt = <<<PROMPT
Return STRICT JSON only (no text, no markdown).
Infer what kind of performance report is requested.

Schema:
{
  "platform": "meta|google|both",
  "entity_level": "account|campaign|adset|ad|creative",
  "metric": "cpa|cpl|ctr|cpc|spend|leads|conversions|hook_rate",
  "direction": "best|worst",
  "top_n": 5,
  "view": "card|table|chart",
  "need_preview": true|false
}

Examples:
- "which campaign is performing well based on cpa" ->
  { "platform": "both", "entity_level": "campaign", "metric": "cpa", "direction": "best", "top_n": 5, "view": "table", "need_preview": false }
- "worst performing adsets by cpl" ->
  { "platform": "both", "entity_level": "adset", "metric": "cpl", "direction": "worst", "top_n": 10, "view": "table", "need_preview": false }
- "best hookrate ads" ->
  { "platform": "meta", "entity_level": "ad", "metric": "hook_rate", "direction": "best", "top_n": 5, "view": "card", "need_preview": true }

Now infer JSON for:
"$question"
PROMPT;

    $payload = [
        "model" => $model,
        "messages" => [["role" => "user", "content" => $prompt]],
        "temperature" => 0
    ];

    [$res, $err, $http] = curlJson(
        "https://api.openai.com/v1/chat/completions",
        ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        json_encode($payload),
        15
    );

    if ($err || $http >= 400) return null;

    $json = json_decode($res, true);
    $content = $json['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($content, true);

    return is_array($parsed) ? $parsed : null;
}

$intent = askChatGPTForReportIntent($userQueryRaw, $OPENAI_API_KEY, $OPENAI_MODEL) ?? [];

// sanitize intent
$platform       = in_array(($intent['platform'] ?? 'both'), ['meta','google','both'], true) ? $intent['platform'] : 'both';
$entityLevel    = in_array(($intent['entity_level'] ?? 'campaign'), ['account','campaign','adset','ad','creative'], true) ? $intent['entity_level'] : 'campaign';
$metricKey      = in_array(($intent['metric'] ?? 'cpa'), ['cpa','cpl','ctr','cpc','spend','leads','conversions','hook_rate'], true) ? $intent['metric'] : 'cpa';
$direction      = in_array(($intent['direction'] ?? 'best'), ['best','worst'], true) ? $intent['direction'] : 'best';
$topN           = (int)($intent['top_n'] ?? 5);
if ($topN <= 0 || $topN > 50) $topN = 5;

$view           = in_array(($intent['view'] ?? 'table'), ['card','table','chart'], true) ? $intent['view'] : 'table';
$needPreview    = !empty($intent['need_preview']);

/*
|--------------------------------------------------------------------------
| STEP 4: Date range
|--------------------------------------------------------------------------
*/
$date = resolveDateRangeFromQuery($userQueryRaw);

/*
|--------------------------------------------------------------------------
| STEP 5: Meta detailed report
|   We always pull rich rows, then rank/filter in PHP
|--------------------------------------------------------------------------
*/
function metaDetailedReport($adAccount, $token, $from, $to, $entityLevel) {
    global $REPORT_CONFIG;

    $cfg = $REPORT_CONFIG['meta'];

    // pick level config, default to campaign
    $levelKey = in_array($entityLevel, ['campaign','adset','ad','creative'], true)
        ? ($entityLevel === 'creative' ? 'ad' : $entityLevel)
        : 'campaign';

    $levelCfg = $cfg['levels'][$levelKey];
    $endpoint = $levelCfg['endpoint'];
    $params   = $levelCfg['params'];

    $level   = $params['level'];
    $fields  = implode(',', $params['fields']);
    $limit   = (int)($params['limit'] ?? 5000);

    $base = rtrim($cfg['base_url'], '/') . '/' . $cfg['version'];
    $endpoint = str_replace('{account_id}', $adAccount, $endpoint);

    $url = $base . $endpoint
         . "?level={$level}"
         . "&fields={$fields}"
         . "&time_range[since]={$from}&time_range[until]={$to}"
         . "&limit={$limit}"
         . "&access_token=" . urlencode($token);

    [$res, $err, $http] = curlJson($url, [], null, 20);
    if ($err || $http >= 400) {
        return ["error" => true, "http" => $http, "raw" => $res];
    }

    $json = json_decode($res, true);
    return is_array($json) ? $json : ["error" => true, "raw" => $res];
}

/*
|--------------------------------------------------------------------------
| STEP 6: Google detailed report (GAQL)
|--------------------------------------------------------------------------
*/

function googleDetailedReport($cid, $token, $from, $to, $devToken, $entityLevel, $loginCustomerId = null) {
    global $REPORT_CONFIG;

    $cfg = $REPORT_CONFIG['google'];

    $levelKey = in_array($entityLevel, ['campaign','account','adset','ad','creative'], true)
        ? ($entityLevel === 'account' ? 'campaign' : ($entityLevel === 'creative' ? 'ad' : $entityLevel))
        : 'campaign';

    $levelCfg = $cfg['levels'][$levelKey];
    $gaql     = $levelCfg['gaql'];

    // inject date range
    $gaql = str_replace(['%FROM%','%TO%'], [$from, $to], $gaql);

    $headers = [
        "Authorization: Bearer $token",
        "developer-token: $devToken",
        "Content-Type: application/json"
    ];
    if ($loginCustomerId) {
        $headers[] = "login-customer-id: $loginCustomerId";
    }

    $base = rtrim($cfg['base_url'], '/') . '/' . $cfg['version'];
    $url  = $base . "/customers/$cid/googleAds:search";
    [$res, $err, $http] = curlJson($url, $headers, json_encode(["query" => $gaql]), 30);

    if ($err || $http >= 400) return ["error" => true, "http" => $http, "raw" => $res];

    $json = json_decode($res, true);
    return is_array($json) ? $json : ["error" => true, "raw" => $res];
}

/*
|--------------------------------------------------------------------------
| STEP 7: Aggregate + compute metrics and ranking
|--------------------------------------------------------------------------
*/
$fbToken = $user['facebook']['access_token'] ?? '';
$gToken  = $user['google']['g_token'] ?? '';
$gMcc    = $user['google']['g_mcc'] ?? null;

$metaRows   = [];
$googleRows = [];
$errors     = [];

foreach ($matched as $client) {
    foreach (($client['facebook_ids'] ?? []) as $fb) {
        if (!$fbToken || ($platform === 'google')) continue;
        $data = metaDetailedReport($fb, $fbToken, $date['from'], $date['to'], $entityLevel);
        if (!empty($data['error'])) {
            $errors[] = ["platform" => "meta", "account" => $fb, "detail" => $data];
            continue;
        }
        foreach ($data['data'] ?? [] as $row) {
            $actions = $row['actions'] ?? [];
            $leads = 0;
            $convs = 0;
            $videoViews = 0;
            foreach ($actions as $a) {
                $type = $a['action_type'] ?? '';
                $val  = (int)($a['value'] ?? 0);
                if (in_array($type, ['lead','offsite_conversion.lead'], true)) $leads += $val;
                if (in_array($type, ['purchase','offsite_conversion.purchase'], true)) $convs += $val;
                if (in_array($type, ['video_view','thruplay'], true)) $videoViews += $val;
            }

            $impr  = (int)($row['impressions'] ?? 0);
            $click = (int)($row['clicks'] ?? 0);
            $spend = (float)($row['spend'] ?? 0);

            $cpl = $leads > 0 ? $spend / $leads : 0;
            $cpa = ($leads + $convs) > 0 ? $spend / ($leads + $convs) : 0;
            $ctr = $impr > 0 ? ($click / $impr) * 100 : 0;
            $cpc = $click > 0 ? $spend / $click : 0;
            $hookRate = $impr > 0 ? ($videoViews / $impr) * 100 : 0;

            $metaRows[] = [
                "platform"      => "meta",
                "account_id"    => $fb,
                "campaign_id"   => $row['campaign_id'] ?? null,
                "campaign_name" => $row['campaign_name'] ?? null,
                "adset_id"      => $row['adset_id'] ?? null,
                "adset_name"    => $row['adset_name'] ?? null,
                "ad_id"         => $row['ad_id'] ?? null,
                "ad_name"       => $row['ad_name'] ?? null,
                "impressions"   => $impr,
                "clicks"        => $click,
                "spend"         => $spend,
                "leads"         => $leads,
                "conversions"   => $convs,
                "cpl"           => $cpl,
                "cpa"           => $cpa,
                "cpc"           => $cpc,
                "ctr"           => $ctr,
                "hook_rate"     => $hookRate
            ];
        }
    }

    foreach (($client['google_ids'] ?? []) as $gid) {
        if (!$gToken || ($platform === 'meta')) continue;
        $data = googleDetailedReport($gid, $gToken, $date['from'], $date['to'], $GOOGLE_DEVELOPER_TOKEN, $entityLevel, $gMcc);
        if (!empty($data['error'])) {
            $errors[] = ["platform" => "google", "account" => $gid, "detail" => $data];
            continue;
        }
        foreach ($data['results'] ?? [] as $r) {
            $metricsRow = $r['metrics'] ?? [];
            $impr  = (int)($metricsRow['impressions'] ?? 0);
            $click = (int)($metricsRow['clicks'] ?? 0);
            $spend = ((float)($metricsRow['costMicros'] ?? 0)) / 1e6;
            $convs = (int)($metricsRow['conversions'] ?? 0);

            $cpa = $convs > 0 ? $spend / $convs : 0;
            $cpl = $cpa; // if we don't have separate leads metric, treat conversions as leads
            $cpc = $click > 0 ? $spend / $click : 0;
            $ctr = $impr > 0 ? ($click / $impr) * 100 : 0;

            $googleRows[] = [
                "platform"      => "google",
                "account_id"    => $gid,
                "campaign_id"   => $r['campaign']['id']   ?? null,
                "campaign_name" => $r['campaign']['name'] ?? null,
                "adset_id"      => $r['adGroup']['id']    ?? null,
                "adset_name"    => $r['adGroup']['name']  ?? null,
                "ad_id"         => $r['adGroupAd']['ad']['id']   ?? null,
                "ad_name"       => $r['adGroupAd']['ad']['name'] ?? null,
                "impressions"   => $impr,
                "clicks"        => $click,
                "spend"         => $spend,
                "leads"         => $convs,
                "conversions"   => $convs,
                "cpl"           => $cpl,
                "cpa"           => $cpa,
                "cpc"           => $cpc,
                "ctr"           => $ctr,
                "hook_rate"     => 0.0 // not directly available from GA data we selected
            ];
        }
    }
}

/*
|--------------------------------------------------------------------------
| STEP 8: Filter rows according to entity level and metric, then rank
|--------------------------------------------------------------------------
*/
function metricValue($row, $metricKey) {
    switch ($metricKey) {
        case 'spend':       return (float)($row['spend'] ?? 0);
        case 'leads':       return (float)($row['leads'] ?? 0);
        case 'conversions': return (float)($row['conversions'] ?? 0);
        case 'ctr':         return (float)($row['ctr'] ?? 0);
        case 'cpc':         return (float)($row['cpc'] ?? 0);
        case 'cpl':         return (float)($row['cpl'] ?? 0);
        case 'cpa':         return (float)($row['cpa'] ?? 0);
        case 'hook_rate':   return (float)($row['hook_rate'] ?? 0);
        default:            return 0.0;
    }
}

function entityKeyLabel($row, $entityLevel) {
    if ($entityLevel === 'campaign') {
        return [$row['campaign_id'] ?? null, $row['campaign_name'] ?? null];
    }
    if ($entityLevel === 'adset') {
        return [$row['adset_id'] ?? null, $row['adset_name'] ?? null];
    }
    if ($entityLevel === 'ad' || $entityLevel === 'creative') {
        return [$row['ad_id'] ?? null, $row['ad_name'] ?? null];
    }
    // account level
    return [$row['account_id'] ?? null, $row['account_id'] ?? null];
}

$allRows = [];
if ($platform === 'meta' || $platform === 'both')   $allRows = array_merge($allRows, $metaRows);
if ($platform === 'google' || $platform === 'both') $allRows = array_merge($allRows, $googleRows);

// Group by entity (e.g. campaign) across accounts / platforms if needed
$grouped = [];
foreach ($allRows as $row) {
    [$id, $name] = entityKeyLabel($row, $entityLevel);
    if (!$id && !$name) continue;
    $key = $row['platform'] . ':' . ($id ?: $name);

    if (!isset($grouped[$key])) {
        $grouped[$key] = $row;
        $grouped[$key]['entity_id']   = $id ?: $name;
        $grouped[$key]['entity_name'] = $name ?: $id;
    } else {
        // sum numeric metrics
        foreach (['impressions','clicks','spend','leads','conversions'] as $f) {
            $grouped[$key][$f] = (float)($grouped[$key][$f] ?? 0) + (float)($row[$f] ?? 0);
        }
    }
}

// Recompute ratios after grouping
foreach ($grouped as &$gr) {
    $impr = (float)($gr['impressions'] ?? 0);
    $click = (float)($gr['clicks'] ?? 0);
    $spend = (float)($gr['spend'] ?? 0);
    $leads = (float)($gr['leads'] ?? 0);
    $convs = (float)($gr['conversions'] ?? 0);

    $gr['cpc'] = $click > 0 ? $spend / $click : 0;
    $gr['cpl'] = $leads > 0 ? $spend / $leads : 0;
    $gr['cpa'] = ($leads + $convs) > 0 ? $spend / ($leads + $convs) : 0;
    $gr['ctr'] = $impr > 0 ? ($click / $impr) * 100 : 0;
}
unset($gr);

// Build sortable array
$rankable = array_values($grouped);
usort($rankable, function ($a, $b) use ($metricKey, $direction) {
    $va = metricValue($a, $metricKey);
    $vb = metricValue($b, $metricKey);
    if ($va == $vb) return 0;

    // for CPA/CPL/CPC smaller is better, for others bigger is better
    $smallerIsBetter = in_array($metricKey, ['cpa','cpl','cpc'], true);
    if ($direction === 'best') {
        return $smallerIsBetter ? ($va < $vb ? -1 : 1) : ($va > $vb ? -1 : 1);
    } else {
        return $smallerIsBetter ? ($va > $vb ? -1 : 1) : ($va < $vb ? -1 : 1);
    }
});

$rankable = array_slice($rankable, 0, $topN);

/*
|--------------------------------------------------------------------------
| STEP 9: Optional analysis/suggestions via ChatGPT (read-only)
|         Returned as structured DO / DON'T lists (no markdown)
|--------------------------------------------------------------------------
*/
$analysisDo   = [];
$analysisDont = [];
if (!empty($rankable)) {
    // build compact payload (top 5 rows max)
    $sampleRows = array_slice($rankable, 0, min(5, count($rankable)));
    $analysisPayload = [
        'client'     => $matched[0]['client_name'],
        'date_range' => $date,
        'intent'     => [
            'platform'     => $platform,
            'entity_level' => $entityLevel,
            'metric'       => $metricKey,
            'direction'    => $direction,
            'top_n'        => $topN,
        ],
        'rows'       => []
    ];
    foreach ($sampleRows as $r) {
        $analysisPayload['rows'][] = [
            'platform'    => $r['platform'] ?? null,
            'entity_name' => $r['entity_name'] ?? ($r['campaign_name'] ?? ($r['adset_name'] ?? ($r['ad_name'] ?? null))),
            'impressions' => $r['impressions'] ?? 0,
            'clicks'      => $r['clicks'] ?? 0,
            'spend'       => $r['spend'] ?? 0,
            'leads'       => $r['leads'] ?? 0,
            'conversions' => $r['conversions'] ?? 0,
            'cpa'         => $r['cpa'] ?? 0,
            'cpl'         => $r['cpl'] ?? 0,
            'cpc'         => $r['cpc'] ?? 0,
            'ctr'         => $r['ctr'] ?? 0,
            'hook_rate'   => $r['hook_rate'] ?? 0,
        ];
    }

    $analysisPrompt = <<<PROMPT
You are a senior performance marketer.
Given this JSON with performance data, return STRICT JSON only (no markdown, no bullets).

Schema:
{
  "do": ["actionable recommendation 1", "actionable recommendation 2", ...],
  "dont": ["what not to do 1", "what not to do 2", ...]
}

Rules:
- Max 5 items in "do" and 5 items in "dont".
- Each item should be one short, clear sentence.
- Mention specific campaigns/adsets/ads by name when relevant.

JSON:
PROMPT;

    $payload = [
        "model" => $OPENAI_MODEL,
        "messages" => [
            ["role" => "user", "content" => $analysisPrompt . "\n" . json_encode($analysisPayload)]
        ],
        "temperature" => 0.3
    ];

    [$aRes, $aErr, $aHttp] = curlJson(
        "https://api.openai.com/v1/chat/completions",
        ["Authorization: Bearer $OPENAI_API_KEY", "Content-Type: application/json"],
        json_encode($payload),
        20
    );

    if (!$aErr && $aHttp < 400) {
        $aJson = json_decode($aRes, true);
        $content = $aJson['choices'][0]['message']['content'] ?? '';
        $parsed  = json_decode($content, true);
        if (is_array($parsed)) {
            if (!empty($parsed['do']) && is_array($parsed['do']))   $analysisDo   = array_values($parsed['do']);
            if (!empty($parsed['dont']) && is_array($parsed['dont'])) $analysisDont = array_values($parsed['dont']);
        }
    }
}

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/
$response = [
    "status"  => true,
    "client"  => $matched[0]['client_name'],
    "date_range" => $date,
    "intent" => [
        "platform"     => $platform,
        "entity_level" => $entityLevel,
        "metric"       => $metricKey,
        "direction"    => $direction,
        "top_n"        => $topN,
        "view"         => $view,
        "need_preview" => $needPreview
    ],
    "results" => $rankable,
    "errors"  => $errors,
    "analysis" => [
        "do"   => $analysisDo,
        "dont" => $analysisDont
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI All Reports</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body {
    background: #0f172a;
    color: #e5e7eb;
    font-family: 'Inter', sans-serif;
}
.chat-box {
    max-width: 1000px;
    margin: 60px auto;
}
.chat-input {
    border: 1px solid #334155;
    color: #fff;
    padding: 18px;
    border-radius: 14px;
}
.chat-input:focus {
    outline: none;
    border-color: #38bdf8;
}
.ask-btn {
    background: linear-gradient(135deg, #38bdf8, #6366f1);
    border: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 600;
}
.loader {
    display: none;
}
.result-card {
    background: #020617;
    border: 1px solid #334155;
    border-radius: 16px;
    padding: 24px;
    margin-top: 30px;
}
.sub {
    font-size: 13px;
    color: #94a3b8;
}
table.report-table {
    width: 100%;
    color: #e5e7eb;
    font-size: 13px;
}
table.report-table th,
table.report-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #1f2937;
}
table.report-table th {
    background: #020617;
    position: sticky;
    top: 0;
    z-index: 1;
}
.suggestions-menu {
    position: absolute;
    z-index: 1000;
    width: 100%;
    max-height: 260px;
    overflow-y: auto;
    background: #020617;
    border: 1px solid #334155;
    border-radius: 12px;
    margin-top: 4px;
    text-align: left;
}
.suggestion-item {
    padding: 8px 14px;
    cursor: pointer;
    font-size: 14px;
    color: #e5e7eb;
}
.suggestion-item small {
    display: block;
    color: #94a3b8;
}
.suggestion-item:hover {
    background: #1f2937;
}
.metric-bar {
    height: 10px;
    border-radius: 999px;
    background: #111827;
    overflow: hidden;
}
.metric-bar-inner {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #16a34a);
}
</style>
</head>

<body>

<div class="chat-box text-center">
    <img src="/images/adRes-w.png" style="height: 30px; margin-bottom: 20px;"/> <img src="chatgpt-logo.png" style="margin-left: 25px; margin-bottom: 20px;"/>
    
    <p class="text-secondary mb-4">
        Ask advanced questions like <em>“best CPA campaigns for @RWD Waterfront in last 30 days”</em>
    </p>

    <div class="position-relative mb-3">
        <div class="input-group">
            <input type="text" id="queryAll" class="form-control chat-input"
                   placeholder="Type @ to pick a client, then your question about campaigns/adsets/ads...">
            <button class="btn ask-btn" id="askAllBtn">Ask</button>
        </div>
        <div id="clientSuggestionsAll" class="suggestions-menu" style="display:none;"></div>
    </div>

    <div class="loader text-info mt-3">
        ⏳
    </div>

    <div id="resultAll"></div>

</div>

<script>
let aiClientsAll = [];

// Load client list for @mentions
$.getJSON('https://stage.adrescue.in/config-api.php', function (cfg) {
    try {
        if (cfg && cfg.status && cfg.data && cfg.data.accounts && cfg.data.accounts.list) {
            aiClientsAll = cfg.data.accounts.list
                .map(function (a) {
                    return {
                        name: (a.client_name || '').trim(),
                        nic: (a.nic_name || '').trim()
                    };
                })
                .filter(function (c) { return c.name.length > 0; });
        }
    } catch (e) {
        console.error('Failed to load client list', e);
    }
});

function showClientSuggestionsAll(term) {
    const box = $('#clientSuggestionsAll');
    if (!aiClientsAll.length) {
        box.hide();
        return;
    }

    const q = (term || '').toLowerCase();
    let filtered = aiClientsAll.filter(function (c) {
        if (!q) return true;
        return c.name.toLowerCase().includes(q) || (c.nic && c.nic.toLowerCase().includes(q));
    });

    filtered = filtered.slice(0, 8);

    if (!filtered.length) {
        box.hide();
        return;
    }

    let html = '';
    filtered.forEach(function (c) {
        html += '<div class="suggestion-item" data-name="' + c.name.replace(/"/g, '&quot;') + '">';
        html += c.name;
        if (c.nic) {
            html += '<small>' + c.nic + '</small>';
        }
        html += '</div>';
    });

    box.html(html).show();
}

function applyClientToQueryAll(name) {
    const input = document.getElementById('queryAll');
    if (!input) return;

    const val = input.value;
    const cursorPos = input.selectionStart || val.length;
    const beforeCursor = val.substring(0, cursorPos);
    const afterCursor = val.substring(cursorPos);

    const atIndex = beforeCursor.lastIndexOf('@');
    if (atIndex === -1) return;

    const beforeAt = beforeCursor.substring(0, atIndex);
    const newText = beforeAt + '@' + name + ' ' + afterCursor.replace(/^\s*/, '');

    input.value = newText;

    const newPos = (beforeAt + '@' + name + ' ').length;
    input.setSelectionRange(newPos, newPos);
    $('#clientSuggestionsAll').hide();
}

$('#askAllBtn').on('click', function () {
    let q = $('#queryAll').val().trim();
    if (!q) return;

    $('.loader').show();
    $('#resultAll').html('');

    $.ajax({
        url: 'all-reports.php?ajax=1',
        method: 'GET',
        data: { q: q },
        success: function (res) {
            $('.loader').hide();

            if (!res.status) {
                $('#resultAll').html(`
                    <div class="alert alert-danger mt-4">
                        ${res.message || 'Something went wrong'}
                    </div>
                `);
                return;
            }

            const intent = res.intent || {};
            const rows = res.results || [];
            const analysis = res.analysis || {};

            if (!rows.length) {
                $('#resultAll').html(`
                    <div class="alert alert-info mt-4">
                        No data found for this query.
                    </div>
                `);
                return;
            }

            function nf(x, d) {
                if (x === null || x === undefined) return '-';
                return Number(x).toFixed(d);
            }

            // Header
            let headerHtml = `
                <div class="result-card text-start">
                    <h5 class="mb-1">${res.client}</h5>
                    <div class="sub mb-1">
                        ${res.date_range.from} → ${res.date_range.to}
                    </div>
                    <div class="sub mb-3">
                        Platform: <strong>${intent.platform}</strong> ·
                        Level: <strong>${intent.entity_level}</strong> ·
                        Metric: <strong>${intent.metric}</strong> (${intent.direction}) ·
                        Top: <strong>${intent.top_n}</strong>
                    </div>
            `;

            let contentHtml = '';
            const view = intent.view || 'table';

            if (view === 'card') {
                // Show each row as a card
                contentHtml += `<div class="row">`;
                rows.forEach(function (r, idx) {
                    const name = (r.entity_name || r.campaign_name || r.adset_name || r.ad_name || '-');
                    contentHtml += `
                        <div class="col-md-6 mb-3">
                            <div class="result-card text-start">
                                <div class="sub mb-1">${idx + 1}. ${r.platform || '-'} · ${intent.entity_level}</div>
                                <h6 class="mb-2">${name}</h6>
                                <div class="sub mb-1">Impr: <strong>${r.impressions ?? '-'}</strong> · Clicks: <strong>${r.clicks ?? '-'}</strong></div>
                                <div class="sub mb-1">Spend: <strong>${nf(r.spend,2)}</strong> · Leads: <strong>${r.leads ?? '-'}</strong> · Conv: <strong>${r.conversions ?? '-'}</strong></div>
                                <div class="sub mb-1">CPA: <strong>${nf(r.cpa,2)}</strong> · CPL: <strong>${nf(r.cpl,2)}</strong> · CPC: <strong>${nf(r.cpc,2)}</strong></div>
                                <div class="sub mb-1">CTR: <strong>${nf(r.ctr,2)}%</strong> · Hook: <strong>${nf(r.hook_rate,2)}%</strong></div>
                            </div>
                        </div>
                    `;
                });
                contentHtml += `</div>`;

            } else if (view === 'chart') {
                // Simple horizontal bar chart on chosen metric
                const metric = intent.metric || 'cpa';
                let maxVal = 0;
                rows.forEach(function (r) {
                    const v = Number(r[metric] || 0);
                    if (v > maxVal) maxVal = v;
                });
                if (maxVal <= 0) maxVal = 1;

                contentHtml += `<div class="mb-2 sub">Bar length represents <strong>${metric.toUpperCase()}</strong> (${intent.direction}).</div>`;
                contentHtml += `<div class="result-card text-start">`;
                rows.forEach(function (r, idx) {
                    const name = (r.entity_name || r.campaign_name || r.adset_name || r.ad_name || '-');
                    const v = Number(r[metric] || 0);
                    const pct = Math.max(5, Math.min(100, (v / maxVal) * 100));
                    contentHtml += `
                        <div class="mb-2">
                            <div class="sub mb-1">${idx + 1}. ${name} &nbsp; <span style="color:#a5b4fc;">${nf(v,2)}</span></div>
                            <div class="metric-bar"><div class="metric-bar-inner" style="width:${pct}%;"></div></div>
                        </div>
                    `;
                });
                contentHtml += `</div>`;

            } else {
                // Default table view
                contentHtml += `
                    <div class="table-responsive" style="max-height:420px;">
                        <table class="report-table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Platform</th>
                                    <th>Entity</th>
                                    <th>Impr</th>
                                    <th>Clicks</th>
                                    <th>Spend</th>
                                    <th>Leads</th>
                                    <th>Conv</th>
                                    <th>CPA</th>
                                    <th>CPL</th>
                                    <th>CPC</th>
                                    <th>CTR %</th>
                                    <th>Hook %</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                rows.forEach(function (r, idx) {
                    const name = (r.entity_name || r.campaign_name || r.adset_name || r.ad_name || '-');
                    contentHtml += `
                        <tr>
                            <td>${idx + 1}</td>
                            <td>${r.platform || '-'}</td>
                            <td>${name}</td>
                            <td>${r.impressions ?? '-'}</td>
                            <td>${r.clicks ?? '-'}</td>
                            <td>${nf(r.spend, 2)}</td>
                            <td>${r.leads ?? '-'}</td>
                            <td>${r.conversions ?? '-'}</td>
                            <td>${nf(r.cpa, 2)}</td>
                            <td>${nf(r.cpl, 2)}</td>
                            <td>${nf(r.cpc, 2)}</td>
                            <td>${nf(r.ctr, 2)}</td>
                            <td>${nf(r.hook_rate, 2)}</td>
                        </tr>
                    `;
                });

                contentHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            // DO / DON'T section
            let adviceHtml = '';
            const doList = Array.isArray(analysis.do) ? analysis.do : [];
            const dontList = Array.isArray(analysis.dont) ? analysis.dont : [];

            if (doList.length || dontList.length) {
                adviceHtml += `<div class="result-card text-start mt-3">`;
                if (doList.length) {
                    adviceHtml += `<h6 class="mb-2">What to DO</h6><ol style="padding-left:18px;font-size:13px;color:#e5e7eb;">`;
                    doList.forEach(function (item) {
                        adviceHtml += `<li class="mb-1">${item}</li>`;
                    });
                    adviceHtml += `</ol>`;
                }
                if (dontList.length) {
                    adviceHtml += `<h6 class="mt-3 mb-2">What NOT to DO</h6><ol style="padding-left:18px;font-size:13px;color:#e5e7eb;">`;
                    dontList.forEach(function (item) {
                        adviceHtml += `<li class="mb-1">${item}</li>`;
                    });
                    adviceHtml += `</ol>`;
                }
                adviceHtml += `</div>`;
            }

            const footerHtml = `</div>`; // close main result-card

            $('#resultAll').html(headerHtml + contentHtml + footerHtml + adviceHtml);
        },
        error: function () {
            $('.loader').hide();
            $('#resultAll').html(`
                <div class="alert alert-danger mt-4">
                    Server error. Please try again.
                </div>
            `);
        }
    });
});

// Enter key support
$('#queryAll').on('keypress', function (e) {
    if (e.which === 13) $('#askAllBtn').click();
});

// @clientname suggestions on input
$('#queryAll').on('input', function () {
    const input = this;
    const val = input.value;
    const cursorPos = input.selectionStart || val.length;
    const beforeCursor = val.substring(0, cursorPos);

    const atIndex = beforeCursor.lastIndexOf('@');
    if (atIndex === -1) {
        $('#clientSuggestionsAll').hide();
        return;
    }

    const term = beforeCursor.substring(atIndex + 1).trim();
    showClientSuggestionsAll(term);
});

// Click on suggestion
$('#clientSuggestionsAll').on('click', '.suggestion-item', function () {
    const name = $(this).data('name');
    if (name) {
        applyClientToQueryAll(name);
    }
});

// Hide suggestions when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('#queryAll, #clientSuggestionsAll').length) {
        $('#clientSuggestionsAll').hide();
    }
});
</script>

</body>
</html>
