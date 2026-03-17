<?php
session_start();

require_once __DIR__ . '/../env_loader.php';
$password = getenv('AI_REPORT_PASSWORD') ?: ''; // set AI_REPORT_PASSWORD in .env

// Check if already logged in
if (!isset($_SESSION['access_granted'])) {

    // Check submitted password
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['access_granted'] = true;
    } else {
        // Show simple password form
        echo '
        <form method="post" style="margin:100px auto; width:300px; text-align:center;">
            <h3>Enter Password</h3>
            <input type="password" name="password" required style="padding:8px; width:100%;"><br><br>
            <button type="submit">Submit</button>
        </form>
        ';
        exit; // stop page loading
    }
}
?>
<?php //session_start();
require_once __DIR__ . '/../db.php';
Auth();
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/
$OPENAI_API_KEY = getenv('OPENAI_API_KEY');

$CONFIG_API_URL = (getenv('SITE_URL') ? rtrim(getenv('SITE_URL'), '/') : 'https://stage.adrescue.in') . '/config-api.php';
$GOOGLE_DEVELOPER_TOKEN = getenv('GOOGLE_DEVELOPER_TOKEN');

$OPENAI_MODEL = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/
$userQueryRaw = trim($_GET['q'] ?? '');
$userQuery = strtolower($userQueryRaw);

if (!$userQuery) {
    exit(json_encode(["status" => false, "message" => "Query missing"]));
}

// NOTE: caching removed — always fetch live data

/*
|--------------------------------------------------------------------------
| HELPERS: CURL JSON
|--------------------------------------------------------------------------
*/
function curlJson($url, $headers = [], $postBody = null, $timeout = 12) {
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
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$res, $err, $http];
}

/*
|--------------------------------------------------------------------------
| STEP 1: Load config api
|--------------------------------------------------------------------------
*/
[$cfgRes, $cfgErr, $cfgHttp] = curlJson($CONFIG_API_URL, [], null, 12);
if ($cfgErr || $cfgHttp >= 400) {
    exit(json_encode([
        "status" => false,
        "message" => "Config API error",
        "http" => $cfgHttp,
        "error" => $cfgErr
    ]));
}

$config = json_decode($cfgRes, true);
if (!is_array($config) || empty($config['data']['accounts']['list'])) {
    exit(json_encode([
        "status" => false,
        "message" => "Config JSON invalid or accounts missing"
    ]));
}

$accounts = $config['data']['accounts']['list'];
$user = $config['data']['user'] ?? [];

/*
|--------------------------------------------------------------------------
| STEP 2: Client matching (DO NOT depend on ChatGPT)
|--------------------------------------------------------------------------
*/
function tokenizeQuery($q) {
    $q = strtolower($q);
    $q = preg_replace('/[^a-z0-9\s\-]/', ' ', $q);
    $parts = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

    // remove generic words that will confuse matching
    $stop = [
        'what','is','are','was','were','show','give','report','this','month','last','week','today','yesterday',
        'spend','spent','cost','cpl','lead','leads','conversion','conversions','purchase','sales','revenue',
        'meta','facebook','google','ads','ad','for','of','in','on','from','to','between'
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

    // Strong: token matches nic_name exactly (e.g., RWD, EP)
    foreach ($tokens as $t) {
        if ($nic && $t === $nic) {
            $score += 100;
            $matchedTokens++;
        }
    }

    // Strong: token appears in client_name as whole word or substring (e.g., rwd in "rwd waterfront", waterfront in "rwd waterfront")
    foreach ($tokens as $t) {
        if (strlen($t) >= 2) {
            // Check if token appears in client name (case-insensitive substring match)
            if (strpos($client, $t) !== false) {
                $score += 70;
                $matchedTokens++;
                
                // Bonus if it's a whole word match (word boundary)
                if (preg_match('/\b' . preg_quote($t, '/') . '\b/i', $client)) {
                    $score += 20;
                }
            }
        }
    }

    // Moderate: token appears in nic_name partially
    foreach ($tokens as $t) {
        if ($nic && strpos($nic, $t) !== false) {
            $score += 40;
            $matchedTokens++;
        }
    }

    // Check if query contains full client name or significant parts
    if (strpos($queryLower, $client) !== false) {
        $score += 50;
    }
    
    // Check if client name contains significant words from query
    $clientWords = preg_split('/\s+/', $client);
    foreach ($clientWords as $cw) {
        if (strlen($cw) >= 3 && strpos($queryLower, $cw) !== false) {
            $score += 25;
        }
    }

    // Weak: query contains full nic_name
    if ($nic && strpos($queryLower, $nic) !== false) {
        $score += 30;
    }

    // Weak: query contains first word of client
    $firstWord = trim(strtok($client, " "));
    if ($firstWord && strlen($firstWord) >= 2 && strpos($queryLower, $firstWord) !== false) {
        $score += 15;
    }
    
    // Bonus: if multiple tokens match, increase score
    if ($matchedTokens > 1) {
        $score += ($matchedTokens - 1) * 10;
    }

    return $score;
}

$tokens = tokenizeQuery($userQuery);

// If user typed only "rwd spend this month" tokens will be ["rwd"]
// If tokens empty, fallback: take any 3+ char word from query
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

// collect all accounts that share the best matched client_name (so it sums multiple rows if any)
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
        "debug" => [
            "tokens" => $tokens,
            "bestScore" => $bestScore
        ]
    ]));
}

/*
|--------------------------------------------------------------------------
| STEP 3: Ask ChatGPT ONLY for date_range + metrics (optional)
|--------------------------------------------------------------------------
*/
function askChatGPTForMetricsAndDate($question, $apiKey, $model) {
    $prompt = <<<PROMPT
Return STRICT JSON only (no text, no markdown):

{
  "date_range": "this_month|last_month|last_7_days|last_week|yesterday|today|custom",
  "date_from": "YYYY-MM-DD or null",
  "date_to": "YYYY-MM-DD or null",
  "metrics": ["spend","leads","cpl","conversions","purchase"]
}

Interpret the question: "$question"

For date_range:
- "today" = today only
- "yesterday" = yesterday only  
- "last_7_days" = last 7 days including today
- "last_week" = last complete week (Monday to Sunday)
- "this_month" = from 1st of current month to today
- "last_month" = from 1st to last day of previous month
- "custom" = use date_from and date_to fields

If user says "jan 1st to till" or "jan 1st to today", use custom with date_from="YYYY-01-01" and date_to=null (which means today).
If user says "last month to today", use custom with date_from=first day of last month and date_to=null.

Return dates in YYYY-MM-DD format.
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
        12
    );

    if ($err || $http >= 400) return null;

    $json = json_decode($res, true);
    $content = $json['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($content, true);

    return is_array($parsed) ? $parsed : null;
}

// If OpenAI fails, we still proceed using heuristics from query text
$intent = askChatGPTForMetricsAndDate($userQueryRaw, $OPENAI_API_KEY, $OPENAI_MODEL);

/*
|--------------------------------------------------------------------------
| DATE RANGE (fallback safe)
|--------------------------------------------------------------------------
*/
function resolveDateRangeFromQuery($query) {
    $q = strtolower($query);
    $today = date('Y-m-d');

    // 1) Explicit YYYY-MM-DD range: "2024-01-01 to 2024-01-31"
    if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(to|till|until|-)\s+(\d{4}-\d{2}-\d{2})/i', $q, $m)) {
        return [
            'from' => $m[1],
            'to'   => $m[3]
        ];
    }

    // 2) Month name start to today: "jan 1st to till", "jan 1 to today"
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

    // 3) "last month to today" / "last month till"
    if (preg_match('/last\s+month\s+(to|till|until)/i', $q)) {
        $firstDayLastMonth = date('Y-m-01', strtotime('first day of last month'));
        return [
            'from' => $firstDayLastMonth,
            'to'   => $today
        ];
    }

    // 4) Rolling N days: "last 3 days", "the last 7 days", "past 14 days"
    if (preg_match('/(?:the\s+)?(?:last|past)\s+(\d+)\s+days?/i', $q, $m)) {
        $days = max(1, (int)$m[1]);
        // "last 3 days" = today and previous 2 days
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        return [
            'from' => $from,
            'to'   => $today
        ];
    }

    // 5) "last month" (previous calendar month)
    if (preg_match('/last\s+month/i', $q) && strpos($q, 'to') === false && strpos($q, 'till') === false && strpos($q, 'until') === false) {
        $firstDay = date('Y-m-01', strtotime('first day of last month'));
        $lastDay  = date('Y-m-t', strtotime('first day of last month'));
        return [
            'from' => $firstDay,
            'to'   => $lastDay
        ];
    }

    // 6) "yesterday"
    if (strpos($q, 'yesterday') !== false) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return [
            'from' => $yesterday,
            'to'   => $yesterday
        ];
    }

    // 7) "today" (only if not part of a "to/till today" phrase already handled)
    if (strpos($q, 'today') !== false && strpos($q, 'to') === false && strpos($q, 'till') === false && strpos($q, 'until') === false) {
        return [
            'from' => $today,
            'to'   => $today
        ];
    }

    // 8) "last week" – previous Monday-Sunday
    if (strpos($q, 'last week') !== false) {
        $lastMonday = date('Y-m-d', strtotime('last monday'));
        $lastSunday = date('Y-m-d', strtotime('last sunday'));
        return [
            'from' => $lastMonday,
            'to'   => $lastSunday
        ];
    }

    // 9) Default: this month (1st of current month → today)
    return [
        'from' => date('Y-m-01'),
        'to'   => $today
    ];
}

$date = resolveDateRangeFromQuery($userQueryRaw);

/*
|--------------------------------------------------------------------------
| METRICS NORMALIZER (always works even if OpenAI fails)
|--------------------------------------------------------------------------
*/
function normalizeMetrics($intentMetrics, $query) {
    $q = strtolower($query);
    $intentMetrics = is_array($intentMetrics) ? array_map('strtolower', $intentMetrics) : [];

    $map = [
        'spend' => ['spend','spent','cost','budget'],
        'leads' => ['lead','leads','enquiry','enquiries'],
        'cpl' => ['cpl','cost per lead'],
        'conversions' => ['conversion','conversions'],
        'purchase' => ['purchase','sales','revenue','orders']
    ];

    $final = [];

    foreach ($map as $key => $words) {
        foreach ($words as $w) {
            if (in_array($w, $intentMetrics, true) || strpos($q, $w) !== false) {
                $final[] = $key;
                break;
            }
        }
    }

    // If user didn't mention any metric, default to spend
    if (empty($final)) $final = ['spend'];

    return array_values(array_unique($final));
}

$metrics = normalizeMetrics($intent['metrics'] ?? [], $userQueryRaw);

/*
|--------------------------------------------------------------------------
| META API (timeout + curl)
|--------------------------------------------------------------------------
*/
function metaReport($adAccount, $token, $from, $to) {
    $url = "https://graph.facebook.com/v24.0/act_{$adAccount}/insights"
         . "?fields=spend,clicks,actions"
         . "&time_range[since]=$from&time_range[until]=$to"
         . "&access_token=" . urlencode($token);

    [$res, $err, $http] = curlJson($url, [], null, 12);
    if ($err || $http >= 400) return ["error" => true, "http" => $http, "raw" => $res];

    $json = json_decode($res, true);
    return is_array($json) ? $json : ["error" => true, "raw" => $res];
}

/*
|--------------------------------------------------------------------------
| GOOGLE ADS API (timeout + curl)
|--------------------------------------------------------------------------
*/
function googleReport($cid, $token, $from, $to, $devToken, $loginCustomerId = null) {
    $query = "
        SELECT
            metrics.clicks,
            metrics.cost_micros,
            metrics.conversions
        FROM customer
        WHERE segments.date BETWEEN '$from' AND '$to'
    ";

    $headers = [
        "Authorization: Bearer $token",
        "developer-token: $devToken",
        "Content-Type: application/json"
    ];

    // If you use MCC, uncomment this header line and pass loginCustomerId:
    if ($loginCustomerId) {
        $headers[] = "login-customer-id: $loginCustomerId";
    }

    $url = "https://googleads.googleapis.com/v20/customers/$cid/googleAds:search";
    [$res, $err, $http] = curlJson($url, $headers, json_encode(["query" => $query]), 12);

    if ($err || $http >= 400) return ["error" => true, "http" => $http, "raw" => $res];

    $json = json_decode($res, true);
    return is_array($json) ? $json : ["error" => true, "raw" => $res];
}

/*
|--------------------------------------------------------------------------
| AGGREGATE (sum multiple accounts)
|--------------------------------------------------------------------------
*/
$fbToken = $user['facebook']['access_token'] ?? '';
$gToken  = $user['google']['g_token'] ?? '';
$gMcc    = $user['google']['g_mcc'] ?? null; // may be needed as login-customer-id

$summary = [
    "meta" => ["spend" => 0.0, "leads" => 0],
    "google" => ["spend" => 0.0, "leads" => 0]
];

$errors = [];

foreach ($matched as $client) {

    // META
    foreach (($client['facebook_ids'] ?? []) as $fb) {
        if (!$fbToken) continue;
        $data = metaReport($fb, $fbToken, $date['from'], $date['to']);

        if (!empty($data['error'])) {
            $errors[] = ["platform" => "meta", "account" => $fb, "detail" => $data];
            continue;
        }

        foreach ($data['data'] ?? [] as $row) {
            $summary['meta']['spend'] += (float)($row['spend'] ?? 0);

            foreach ($row['actions'] ?? [] as $a) {
                $type = $a['action_type'] ?? '';
                if (in_array($type, ['lead', 'offsite_conversion.lead', 'purchase', 'offsite_conversion.purchase'], true)) {
                    $summary['meta']['leads'] += (int)($a['value'] ?? 0);
                }
            }
        }
    }

    // GOOGLE
    foreach (($client['google_ids'] ?? []) as $gid) {
        if (!$gToken) continue;

        // If you face "PERMISSION_DENIED" for child accounts, $gMcc as login-customer-id usually fixes it.
        $data = googleReport($gid, $gToken, $date['from'], $date['to'], $GOOGLE_DEVELOPER_TOKEN, $gMcc);

        if (!empty($data['error'])) {
            $errors[] = ["platform" => "google", "account" => $gid, "detail" => $data];
            continue;
        }

        foreach ($data['results'] ?? [] as $r) {
            $metricsRow = $r['metrics'] ?? [];
            $summary['google']['spend'] += ((float)($metricsRow['costMicros'] ?? 0)) / 1e6;
            $summary['google']['leads'] += (int)($metricsRow['conversions'] ?? 0);
        }
    }
}

/*
|--------------------------------------------------------------------------
| FINAL METRICS
|--------------------------------------------------------------------------
*/
$responseMetrics = [];

// Indian currency formatter (e.g., ₹12,34,567.89)
function formatINR($amount) {
    $amount = round((float)$amount, 2);
    $negative = $amount < 0;
    $amount = abs($amount);

    $parts   = explode('.', number_format($amount, 2, '.', ''));
    $integer = $parts[0];
    $decimal = $parts[1] ?? '00';

    $len = strlen($integer);
    if ($len > 3) {
        $last3 = substr($integer, -3);
        $rest  = substr($integer, 0, $len - 3);
        $rest  = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
        $formatted = $rest . ',' . $last3;
    } else {
        $formatted = $integer;
    }

    $formatted .= '.' . $decimal;
    return ($negative ? '-' : '') . '₹' . $formatted;
}

$totalSpend = round($summary['meta']['spend'] + $summary['google']['spend'], 2);
$totalLeads = $summary['meta']['leads'] + $summary['google']['leads'];

foreach ($metrics as $m) {
    if ($m === 'spend') {
        $responseMetrics['spend'] = formatINR($totalSpend);
    }
    if ($m === 'leads') {
        // Keep leads as plain integer count
        $responseMetrics['leads'] = (int)$totalLeads;
    }
    if ($m === 'cpl') {
        $cpl = $totalLeads ? ($totalSpend / $totalLeads) : 0;
        $responseMetrics['cpl'] = formatINR($cpl);
    }
    // conversions / purchase can be added later with proper mapping per platform
}

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/
$response = [
    "status" => true,
    "client" => $matched[0]['client_name'],
    "date_range" => $date,
    "metrics" => $responseMetrics
];

// Optional debug (uncomment if needed)
// $response["debug"] = ["tokens" => $tokens, "intent" => $intent, "summary" => $summary];
// $response["errors"] = $errors;

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Ads Report</title>

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
    max-width: 800px;
    margin: 60px auto;
}
.chat-input {
   
    border: 1px solid #334155;
  
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
.result-card {
    background: #020617;
    border: 1px solid #334155;
    border-radius: 16px;
    padding: 24px;
    margin-top: 30px;
}
.metric {
    font-size: 28px;
    font-weight: 700;
}
.sub {
    font-size: 13px;
    color: #94a3b8;
}
.loader-div {
    display: none;
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

.typing-loader-div {
    display: inline-block;
    background: #020617;
    border: 1px solid #334155;
    padding: 12px 18px;
    border-radius: 14px;
    font-size: 15px;
    color: #cbd5e1;
    letter-spacing: 0.2px;
}

/* Dots animation only */
.dots::after {
    content: '';
    animation: dots 1.4s infinite;
}

@keyframes dots {
    0%   { content: ''; }
    25%  { content: '.'; }
    50%  { content: '..'; }
    75%  { content: '...'; }
    100% { content: ''; }
}
</style>
</head>

<body>

<div class="chat-box text-center">
    <img src="/images/adRes-w.png" style="height: 30px; margin-bottom: 20px;"/>.AI 
    
    <p class="text-secondary mb-4">
        Ask anything like <em>“what is rwd spend this month”</em>
    </p>

    <div class="position-relative mb-3">
        <div class="input-group">
            <input type="text" id="query" class="form-control chat-input"
                   placeholder="Type @ to pick a client, then your question...">
            <button class="btn ask-btn" id="askBtn">Ask</button>
        </div>
        <div id="clientSuggestions" class="suggestions-menu" style="display:none;"></div>
    </div>

    <div class="loader-div text-info mt-3">
        <img src="/images/ajax-loader.gif" alt="Loading...">
    </div>

    <div id="result"></div>

</div>

<script>
let aiClients = [];

// Load client list for @mentions
$.getJSON('https://stage.adrescue.in/config-api.php', function (cfg) {
    try {
        if (cfg && cfg.status && cfg.data && cfg.data.accounts && cfg.data.accounts.list) {
            aiClients = cfg.data.accounts.list
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

function showClientSuggestions(term) {
    const box = $('#clientSuggestions');
    if (!aiClients.length) {
        box.hide();
        return;
    }

    const q = (term || '').toLowerCase();
    let filtered = aiClients.filter(function (c) {
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

function applyClientToQuery(name) {
    const input = document.getElementById('query');
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
    $('#clientSuggestions').hide();
}

$('#askBtn').on('click', function () {

    let q = $('#query').val().trim();
    if (!q) return;

    $('.loader-div').show();
    $('#result').html('');

    $.ajax({
        url: '?ajax=1',
        method: 'GET',
        data: { q: q },
        success: function (res) {

            $('.loader-div').hide();

            if (!res.status) {
                $('#result').html(`
                    <div class="alert alert-danger mt-4">
                        ${res.message || 'Something went wrong'}
                    </div>
                `);
                return;
            }

            let metricsHtml = '';
            $.each(res.metrics, function (k, v) {
                metricsHtml += `
                    <div class="col-md-4 mb-3">
                        <div class="result-card text-center">
                            <div class="metric">${v}</div>
                            <div class="sub text-uppercase">${k}</div>
                        </div>
                    </div>
                `;
            });

            $('#result').html(`
                <div class="result-card">
                    <h5 class="mb-2">${res.client}</h5>
                    <div class="sub mb-3">
                        ${res.date_range.from} → ${res.date_range.to}
                    </div>
                    <div class="row">
                        ${metricsHtml}
                    </div>
                </div>
            `);
        },
        error: function () {
            $('.loader-div').hide();
            $('#result').html(`
                <div class="alert alert-danger mt-4">
                    Server error. Please try again.
                </div>
            `);
        }
    });

});

// Enter key support
$('#query').on('keypress', function (e) {
    if (e.which === 13) $('#askBtn').click();
});

// @clientname suggestions on input
$('#query').on('input', function () {
    const input = this;
    const val = input.value;
    const cursorPos = input.selectionStart || val.length;
    const beforeCursor = val.substring(0, cursorPos);

    const atIndex = beforeCursor.lastIndexOf('@');
    if (atIndex === -1) {
        $('#clientSuggestions').hide();
        return;
    }

    const term = beforeCursor.substring(atIndex + 1).trim();
    showClientSuggestions(term);
});

// Click on suggestion
$('#clientSuggestions').on('click', '.suggestion-item', function () {
    const name = $(this).data('name');
    if (name) {
        applyClientToQuery(name);
    }
});

// Hide suggestions when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('#query, #clientSuggestions').length) {
        $('#clientSuggestions').hide();
    }
});
</script>

</body>
</html>