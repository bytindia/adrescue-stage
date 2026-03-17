<?php session_start();
require_once __DIR__ . '/../db.php';
Auth(); 

/*
|--------------------------------------------------------------------------
| Claude AI Ads Query Tool
|--------------------------------------------------------------------------
| A dynamic ads query system using Claude API that interprets user queries
| and fetches data from Meta/Google Ads APIs without predefined middleware
|--------------------------------------------------------------------------
*/

// ============================================================================
// CONFIGURATION - Replace YOUR_CLAUDE_API_KEY_HERE with your actual API key
// ============================================================================
$CLAUDE_API_KEY = getenv('CLAUDE_API_KEY') ?: '';
$CLAUDE_MODEL = getenv('CLAUDE_MODEL') ?: 'claude-sonnet-4-20250514';
$CONFIG_API_URL = (getenv('SITE_URL') ? rtrim(getenv('SITE_URL'), '/') : 'https://stage.adrescue.in') . '/ai-report/config-api.php';
$GOOGLE_DEVELOPER_TOKEN = getenv('GOOGLE_DEVELOPER_TOKEN');
$DEBUG_MODE = false; // Set to true to see raw API responses
$MAX_CONVERSATION_HISTORY = 10; // Keep last 10 exchanges for context

date_default_timezone_set('Asia/Kolkata');

// ============================================================================
// CONVERSATION HISTORY MANAGEMENT
// ============================================================================
if (!isset($_SESSION['conversation_history'])) {
    $_SESSION['conversation_history'] = [];
}
if (!isset($_SESSION['last_client'])) {
    $_SESSION['last_client'] = null;
}
if (!isset($_SESSION['last_context'])) {
    $_SESSION['last_context'] = [];
}

// Handle conversation reset
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $_SESSION['conversation_history'] = [];
    $_SESSION['last_client'] = null;
    $_SESSION['last_context'] = [];
    header('Content-Type: application/json');
    exit(json_encode(["status" => true, "message" => "Conversation reset"]));
}

// ============================================================================
// AJAX REQUEST HANDLER
// ============================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
    
    if (empty($userQuery)) {
        exit(json_encode(["status" => false, "message" => "Please enter a query"]));
    }
    
    // Validate API key
    if (empty($CLAUDE_API_KEY) || $CLAUDE_API_KEY === 'YOUR_CLAUDE_API_KEY_HERE') {
        exit(json_encode([
            "status" => false, 
            "message" => "Claude API key not configured. Please add your API key in the configuration section."
        ]));
    }
    
    // Fetch account configuration
    $apiConfig = fetchConfig($CONFIG_API_URL);
    if (!$apiConfig || !isset($apiConfig['status']) || !$apiConfig['status']) {
        exit(json_encode(["status" => false, "message" => "Failed to fetch account configuration from API"]));
    }
    
    // Get conversation context
    $conversationHistory = $_SESSION['conversation_history'] ?? [];
    $lastClient = $_SESSION['last_client'] ?? null;
    $lastContext = $_SESSION['last_context'] ?? [];
    
    // Process query with Claude AI (with conversation context)
    $result = processQueryWithClaude(
        $userQuery, 
        $apiConfig['data'], 
        $CLAUDE_API_KEY, 
        $CLAUDE_MODEL, 
        $GOOGLE_DEVELOPER_TOKEN,
        $DEBUG_MODE,
        $conversationHistory,
        $lastClient,
        $lastContext
    );
    
    // Update conversation history
    $_SESSION['conversation_history'][] = [
        'role' => 'user',
        'content' => $userQuery
    ];
    
    // Store assistant response summary
    $responseSummary = '';
    if (isset($result['client'])) {
        $_SESSION['last_client'] = $result['client'];
        $responseSummary = "Showed data for client: " . $result['client'];
        if (isset($result['metrics'])) {
            $responseSummary .= " with metrics: " . implode(', ', array_keys($result['metrics']));
        }
    }
    if (isset($result['response'])) {
        $responseSummary = $result['response'];
    }
    
    $_SESSION['conversation_history'][] = [
        'role' => 'assistant',
        'content' => $responseSummary
    ];
    
    // Update last context
    if (isset($result['client'])) {
        $_SESSION['last_context']['client'] = $result['client'];
    }
    if (isset($result['date_range'])) {
        $_SESSION['last_context']['date_range'] = $result['date_range'];
    }
    if (isset($result['metrics'])) {
        $_SESSION['last_context']['metrics'] = array_keys($result['metrics']);
    }
    
    // Trim history to max size
    while (count($_SESSION['conversation_history']) > $MAX_CONVERSATION_HISTORY * 2) {
        array_shift($_SESSION['conversation_history']);
    }
    
    // Add context info to result for debugging
    if ($DEBUG_MODE) {
        $result['context'] = [
            'last_client' => $_SESSION['last_client'],
            'history_count' => count($_SESSION['conversation_history'])
        ];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Fetch configuration from external API
 */
function fetchConfig($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error || $httpCode >= 400) {
        error_log("Config fetch error: $error (HTTP $httpCode)");
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Main function to process user query with Claude AI
 */
function processQueryWithClaude($query, $configData, $apiKey, $model, $devToken, $debug, $conversationHistory = [], $lastClient = null, $lastContext = []) {
    $accounts = $configData['accounts']['list'] ?? [];
    $user = $configData['user'] ?? [];
    
    // Create account summary for Claude
    $clientList = array_map(function($acc) {
        return [
            'client_name' => $acc['client_name'] ?? '',
            'nic_name' => $acc['nic_name'] ?? '',
            'has_meta' => !empty($acc['facebook_ids']),
            'has_google' => !empty($acc['google_ids']),
            'meta_accounts' => $acc['facebook_ids'] ?? [],
            'google_accounts' => $acc['google_ids'] ?? []
        ];
    }, $accounts);
    
    $systemPrompt = buildSystemPrompt($clientList, $user, $lastClient, $lastContext);
    
    // Build conversation context for Claude
    $messagesForClaude = buildConversationMessages($query, $conversationHistory, $lastClient, $lastContext);
    
    // First Claude call - interpret query and plan API calls
    $planResponse = callClaudeAPIWithHistory($apiKey, $model, $systemPrompt, $messagesForClaude);
    
    if (!$planResponse['success']) {
        return [
            "status" => false, 
            "message" => "Claude API error: " . ($planResponse['error'] ?? 'Unknown error')
        ];
    }
    
    $claudeResponse = $planResponse['content'];
    
    // Parse Claude's response for API calls
    $apiPlan = parseClaudeApiPlan($claudeResponse);
    
    if (empty($apiPlan['api_calls'])) {
        // Claude provided a direct answer without API calls needed
        return [
            "status" => true,
            "type" => "text",
            "response" => $claudeResponse,
            "query" => $query,
            "client" => $apiPlan['client_matched'] ?? $lastClient
        ];
    }
    
    // Execute planned API calls
    $apiResults = [];
    foreach ($apiPlan['api_calls'] as $call) {
        $result = executeApiCall(
            $call['platform'] ?? 'meta',
            $call['params'] ?? [],
            $user,
            $devToken
        );
        $apiResults[] = [
            'platform' => $call['platform'],
            'description' => $call['description'] ?? '',
            'result' => $result
        ];
    }
    
    // Second Claude call - interpret and format results
    $resultsPrompt = buildResultsPrompt($query, $apiPlan, $apiResults);
    $finalMessages = array_merge($messagesForClaude, [
        ['role' => 'assistant', 'content' => $claudeResponse],
        ['role' => 'user', 'content' => $resultsPrompt]
    ]);
    
    $finalResponse = callClaudeAPIWithHistory($apiKey, $model, $systemPrompt, $finalMessages);
    
    if (!$finalResponse['success']) {
        // Return raw results if interpretation fails
        return [
            "status" => true,
            "type" => "text",
            "response" => "Retrieved data but failed to format response.",
            "raw_results" => $apiResults,
            "query" => $query,
            "client" => $apiPlan['client_matched'] ?? $lastClient
        ];
    }
    
    // Parse final formatted response
    $formattedResult = parseClaudeFormattedResponse($finalResponse['content'], $query);
    
    // Ensure client is set from API plan if not in formatted result
    if (!isset($formattedResult['client']) && isset($apiPlan['client_matched'])) {
        $formattedResult['client'] = $apiPlan['client_matched'];
    }
    
    if ($debug) {
        $formattedResult['debug'] = [
            'api_calls_made' => count($apiResults),
            'raw_api_results' => $apiResults,
            'api_plan' => $apiPlan,
            'conversation_context' => [
                'last_client' => $lastClient,
                'history_length' => count($conversationHistory)
            ]
        ];
    }
    
    return $formattedResult;
}

/**
 * Build conversation messages with context
 */
function buildConversationMessages($currentQuery, $conversationHistory, $lastClient, $lastContext) {
    $messages = [];
    
    // Add relevant conversation history
    foreach ($conversationHistory as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Build context-aware current query
    $contextPrefix = '';
    if ($lastClient && !preg_match('/\b' . preg_quote($lastClient, '/') . '\b/i', $currentQuery)) {
        // Check if query seems like a follow-up (short, no client mentioned, uses pronouns or references)
        $followUpPatterns = [
            '/^which\s/i',
            '/^what\s/i', 
            '/^show\s/i',
            '/^list\s/i',
            '/^get\s/i',
            '/^give\s/i',
            '/^how\s/i',
            '/^why\s/i',
            '/^compare\s/i',
            '/^breakdown/i',
            '/^details/i',
            '/^more\s/i',
            '/^and\s/i',
            '/^also\s/i',
            '/\btheir\b/i',
            '/\bits\b/i',
            '/\bthis\b/i',
            '/\bthat\b/i',
            '/\bthe same\b/i',
            '/\bcampaign/i',
            '/\badset/i',
            '/\bad\b/i',
            '/\bperformance/i',
        ];
        
        $isFollowUp = false;
        foreach ($followUpPatterns as $pattern) {
            if (preg_match($pattern, $currentQuery)) {
                $isFollowUp = true;
                break;
            }
        }
        
        // Also consider short queries as follow-ups
        if (str_word_count($currentQuery) <= 5) {
            $isFollowUp = true;
        }
        
        if ($isFollowUp) {
            $contextPrefix = "[Context: The user is asking about client '{$lastClient}'";
            if (!empty($lastContext['date_range'])) {
                $contextPrefix .= " for date range {$lastContext['date_range']['from']} to {$lastContext['date_range']['to']}";
            }
            $contextPrefix .= "]\n\n";
        }
    }
    
    $messages[] = [
        'role' => 'user',
        'content' => $contextPrefix . $currentQuery
    ];
    
    return $messages;
}

/**
 * Build system prompt for Claude with API documentation
 */
function buildSystemPrompt($clientList, $user, $lastClient = null, $lastContext = []) {
    $clientJson = json_encode($clientList, JSON_PRETTY_PRINT);
    $today = date('Y-m-d');
    $thisMonthStart = date('Y-m-01');
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
    $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
    $lastWeekMonday = date('Y-m-d', strtotime('monday last week'));
    $lastWeekSunday = date('Y-m-d', strtotime('sunday last week'));
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $last7days = date('Y-m-d', strtotime('-7 days'));
    $last30days = date('Y-m-d', strtotime('-30 days'));
    
    // Build context section
    $contextSection = '';
    if ($lastClient) {
        $contextSection = <<<CONTEXT

## IMPORTANT: Conversation Context
The user has been asking about client: "{$lastClient}"
If the user's query doesn't explicitly mention a client name, assume they are asking about "{$lastClient}".
Follow-up questions like "which campaign", "show breakdown", "more details", "what about leads", etc. should use the same client.

CONTEXT;
        if (!empty($lastContext['date_range'])) {
            $contextSection .= "Last queried date range: {$lastContext['date_range']['from']} to {$lastContext['date_range']['to']}\n";
        }
    }
    
    return <<<PROMPT
You are an expert AI assistant for a digital marketing agency. You help analyze Meta (Facebook) and Google Ads performance data and provide actionable insights.

{$contextSection}

## Current Date Reference
- Today: {$today}
- Yesterday: {$yesterday}
- This Month: {$thisMonthStart} to {$today}
- Last Month: {$lastMonthStart} to {$lastMonthEnd}
- Last Week (Mon-Sun): {$lastWeekMonday} to {$lastWeekSunday}
- Last 7 Days: {$last7days} to {$today}
- Last 30 Days: {$last30days} to {$today}

## Available Clients/Accounts
{$clientJson}

## Meta Ads API (v24.0)

### Account/Campaign Insights Endpoint
`GET /act_{ad_account_id}/insights`

**Available Fields:**
- Basic: spend, impressions, clicks, reach, frequency
- Rates: cpm, cpc, ctr, cpp
- Actions: actions, action_values, cost_per_action_type, cost_per_unique_action_type
- Video: video_p25_watched_actions, video_p50_watched_actions, video_p75_watched_actions, video_p100_watched_actions, video_play_actions
- Engagement: post_engagement, page_engagement, post_reactions, comment, share
- Conversions: conversions, conversion_values, purchase_roas

**Parameters:**
- fields: comma-separated field list
- time_range: {"since":"YYYY-MM-DD","until":"YYYY-MM-DD"}
- level: account, campaign, adset, ad
- breakdowns: age, gender, country, region, publisher_platform, platform_position, device_platform
- filtering: [{"field":"spend","operator":"GREATER_THAN","value":"0"}]
- sort: ["spend_descending"] or ["cpc_ascending"]
- limit: number of results

**Level-specific fields to add:**
- campaign level: campaign_id, campaign_name
- adset level: adset_id, adset_name, targeting
- ad level: ad_id, ad_name, creative

**Action Types (in 'actions' array):**
- Leads: lead, onsite_conversion.lead_grouped, offsite_conversion.fb_pixel_lead
- Purchases: purchase, omni_purchase, offsite_conversion.fb_pixel_purchase
- Registrations: complete_registration, offsite_conversion.fb_pixel_complete_registration
- Engagement: link_click, landing_page_view, post_engagement, video_view
- App: app_install, app_use

**Video Hook Rate Calculation:**
Hook Rate = (3-second video views / impressions) × 100
Use video_play_actions or ThruPlay for video metrics

### Campaigns Endpoint
`GET /act_{ad_account_id}/campaigns`
Fields: id, name, status, objective, daily_budget, lifetime_budget, budget_remaining, effective_status

### Ad Sets Endpoint  
`GET /act_{ad_account_id}/adsets`
Fields: id, name, status, daily_budget, targeting, optimization_goal, bid_amount, billing_event

### Ads Endpoint
`GET /act_{ad_account_id}/ads`
Fields: id, name, status, creative, effective_status, adset_id, campaign_id

## Google Ads API (v20)

### GAQL Queries

**Account Performance:**
```sql
SELECT metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions, 
       metrics.conversions_value, metrics.all_conversions, metrics.ctr, metrics.average_cpc
FROM customer
WHERE segments.date BETWEEN 'YYYY-MM-DD' AND 'YYYY-MM-DD'
```

**Campaign Performance:**
```sql
SELECT campaign.id, campaign.name, campaign.status, campaign.advertising_channel_type,
       metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions,
       metrics.conversions_value, metrics.ctr, metrics.average_cpc
FROM campaign
WHERE segments.date BETWEEN 'YYYY-MM-DD' AND 'YYYY-MM-DD'
  AND campaign.status = 'ENABLED'
ORDER BY metrics.cost_micros DESC
```

**Ad Group Performance:**
```sql
SELECT ad_group.id, ad_group.name, ad_group.status, campaign.name,
       metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions
FROM ad_group
WHERE segments.date BETWEEN 'YYYY-MM-DD' AND 'YYYY-MM-DD'
```

**Ad Performance:**
```sql
SELECT ad_group_ad.ad.id, ad_group_ad.ad.name, ad_group_ad.ad.type,
       metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions
FROM ad_group_ad
WHERE segments.date BETWEEN 'YYYY-MM-DD' AND 'YYYY-MM-DD'
```

**Keyword Performance:**
```sql
SELECT ad_group_criterion.keyword.text, ad_group_criterion.keyword.match_type,
       metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions
FROM keyword_view
WHERE segments.date BETWEEN 'YYYY-MM-DD' AND 'YYYY-MM-DD'
```

IMPORTANT: Google cost_micros ÷ 1,000,000 = actual currency value

## Query Types You Must Handle

### 1. Daily Performance Monitoring
- "Show today's spend, leads, CPL for all campaigns"
- "Which campaigns are underperforming today?"
- "Compare today vs yesterday performance"
- "Any sudden drop in CTR or spike in CPC?"

### 2. Campaign Analysis
- "Which campaign has the best CPL in last 7 days?"
- "Rank campaigns by ROAS"
- "Which campaigns should be paused?"
- "List campaigns with high spend but zero conversions"

### 3. Ad Set & Audience Analysis
- "Which ad sets are fatiguing?"
- "Compare Lookalike vs Interest audiences"
- "Which audience has highest conversion rate?"

### 4. Creative Performance
- "Which creatives have the highest CTR?"
- "Best performing visual this week"
- "Compare image vs video performance"
- "What is the hook rate for each creative?"

### 5. Budget & Spend
- "Daily budget utilization report"
- "Any campaigns overspending?"
- "Suggest budget reallocation"

### 6. Funnel Analysis
- "High CTR but low conversion campaigns"
- "Where are we losing users in the funnel?"
- "Conversion rate by platform"

### 7. Client/Management Reports
- "Generate daily summary for [Client]"
- "Which clients need immediate attention?"
- "Top 5 risky accounts"
- "Best performing client this month"

### 8. Actionable Insights
- "What should I optimize first?"
- "Give me today's action items"
- "Which creative should we duplicate?"
- "Suggest next experiments"

## Response Guidelines

### CRITICAL RULES:
1. **Only use available data** - Never hallucinate or assume metrics
2. **State missing data clearly** - If a metric isn't available, say so
3. **Provide actionable insights** - Don't just show data, suggest actions
4. **Be specific** - Use actual numbers and campaign names
5. **Explain your reasoning** - Why is something underperforming?

### Response Format

For data queries, respond with JSON:
```json
{
  "api_calls": [
    {
      "platform": "meta",
      "params": {
        "account_id": "123456789",
        "fields": "campaign_name,spend,impressions,clicks,actions,cpc,ctr",
        "date_from": "YYYY-MM-DD",
        "date_to": "YYYY-MM-DD",
        "level": "campaign",
        "sort": "spend_descending",
        "limit": 10
      },
      "description": "Fetching top 10 campaigns by spend"
    }
  ],
  "client_matched": "Client Name",
  "interpretation": "User wants campaign performance ranking"
}
```

### Output Types Based on Query:

1. **Single Metric Query** → type: "card" with metrics grid
2. **Ranking/List Query** → type: "table" with sorted data  
3. **Comparison Query** → type: "table" or "chart"
4. **Summary/Report Query** → type: "card" with detailed summary
5. **Action Items Query** → type: "text" with bullet points

### Calculations You Should Perform:
- **CPL** = Total Spend ÷ Total Leads
- **ROAS** = Revenue ÷ Spend
- **CTR** = (Clicks ÷ Impressions) × 100
- **CPC** = Spend ÷ Clicks
- **Hook Rate** = (3-sec video views ÷ Impressions) × 100
- **Conversion Rate** = (Conversions ÷ Clicks) × 100

### When Data is Unavailable:
- Clearly state which metrics couldn't be retrieved
- Explain why (API error, metric not tracked, etc.)
- Provide whatever partial data is available
- Suggest how to get the missing data

PROMPT;
}

/**
 * Build prompt for interpreting API results
 */
function buildResultsPrompt($originalQuery, $apiPlan, $apiResults) {
    $resultsJson = json_encode($apiResults, JSON_PRETTY_PRINT);
    $clientMatched = $apiPlan['client_matched'] ?? 'Unknown';
    $interpretation = $apiPlan['interpretation'] ?? '';
    
    return <<<PROMPT
## Original Query
"{$originalQuery}"

## Client
{$clientMatched}

## Query Interpretation
{$interpretation}

## API Results
{$resultsJson}

## Your Task
Analyze the API results and provide a well-formatted response.

## Response Format Guidelines

### For Single Metric Queries (spend, leads, CPL, etc.) - use type "card":
```json
{
  "status": true,
  "type": "card",
  "client": "Client Name",
  "date_range": {"from": "YYYY-MM-DD", "to": "YYYY-MM-DD"},
  "metrics": {
    "spend": "₹X,XX,XXX.XX",
    "leads": 123,
    "cpl": "₹X,XXX.XX",
    "clicks": 1234,
    "ctr": "2.5%"
  },
  "summary": "Brief performance analysis with actionable insight"
}
```

### For Ranking/List Queries (top campaigns, best performers, etc.) - use type "table":
```json
{
  "status": true,
  "type": "table",
  "client": "Client Name",
  "date_range": {"from": "YYYY-MM-DD", "to": "YYYY-MM-DD"},
  "table_data": [
    {"rank": 1, "campaign": "Campaign A", "spend": "₹50,000", "leads": 45, "cpl": "₹1,111"},
    {"rank": 2, "campaign": "Campaign B", "spend": "₹40,000", "leads": 30, "cpl": "₹1,333"}
  ],
  "summary": "Analysis of the ranking with recommendations"
}
```

### For Action Items / Recommendations - use type "card" with action_items:
```json
{
  "status": true,
  "type": "card",
  "client": "Client Name",
  "date_range": {"from": "YYYY-MM-DD", "to": "YYYY-MM-DD"},
  "metrics": {"campaigns_reviewed": 10, "issues_found": 3},
  "action_items": [
    "🔴 Pause Campaign X - spent ₹5,000 with 0 conversions",
    "🟡 Monitor Campaign Y - CPL increased 40% vs last week",
    "🟢 Scale Campaign Z - best CPL at ₹200, has room to grow"
  ],
  "summary": "3 action items identified from today's performance review"
}
```

### For Summary/Report Queries - use type "card" with highlights:
```json
{
  "status": true,
  "type": "card",
  "client": "Client Name",
  "date_range": {"from": "YYYY-MM-DD", "to": "YYYY-MM-DD"},
  "metrics": {
    "total_spend": "₹X,XX,XXX",
    "total_leads": 123,
    "avg_cpl": "₹XXX"
  },
  "highlights": [
    "✅ Lead volume up 25% vs last week",
    "⚠️ CPL increased by ₹50 due to Campaign X",
    "📈 Best performer: Campaign Y with 45 leads at ₹180 CPL"
  ],
  "summary": "Executive summary of performance"
}
```

## Data Processing Rules

1. **Currency**: Format in Indian Rupees (₹) with lakhs system: ₹1,00,000 (1 lakh)
   
2. **Google Ads**: ALWAYS divide cost_micros by 1,000,000

3. **Calculations**:
   - CPL = Total Spend ÷ Total Leads
   - ROAS = Revenue ÷ Spend  
   - CTR = (Clicks ÷ Impressions) × 100
   - CPC = Spend ÷ Clicks
   - Hook Rate = (3-sec video views ÷ Impressions) × 100

4. **Meta Actions**: Sum values where action_type contains:
   - 'lead' for leads
   - 'purchase' for purchases
   - 'video_view' for video views
   - 'link_click' for link clicks

5. **Missing Data**: Include a "note" field explaining unavailable data

6. **Insights**: Always provide actionable recommendations, not just raw data

7. **Aggregation**: If multiple ad accounts, sum the totals

## Important
- Never make up data that isn't in the API results
- If a metric isn't available, say so clearly
- Provide specific campaign/ad names when relevant
- Suggest actions based on the data
PROMPT;
}

/**
 * Call Claude API with conversation history
 */
function callClaudeAPIWithHistory($apiKey, $model, $systemPrompt, $messages) {
    $url = "https://api.anthropic.com/v1/messages";
    
    $payload = [
        "model" => $model,
        "max_tokens" => 4096,
        "system" => $systemPrompt,
        "messages" => $messages
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-api-key: " . $apiKey,
            "anthropic-version: 2023-06-01"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ["success" => false, "error" => "Connection error: $error"];
    }
    
    if ($httpCode >= 400) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? "HTTP Error $httpCode";
        return ["success" => false, "error" => $errorMsg];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['content'][0]['text'])) {
        return ["success" => true, "content" => $data['content'][0]['text']];
    }
    
    return ["success" => false, "error" => "Invalid response format from Claude API"];
}

/**
 * Call Claude API (simple version for backward compatibility)
 */
function callClaudeAPI($apiKey, $model, $systemPrompt, $userMessage) {
    return callClaudeAPIWithHistory($apiKey, $model, $systemPrompt, [
        ['role' => 'user', 'content' => $userMessage]
    ]);
}

/**
 * Parse Claude's API plan response
 */
function parseClaudeApiPlan($response) {
    // Try to extract JSON from markdown code block
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
        $json = json_decode(trim($matches[1]), true);
        if ($json && isset($json['api_calls'])) {
            return $json;
        }
    }
    
    // Try direct JSON parse
    $json = json_decode($response, true);
    if ($json && isset($json['api_calls'])) {
        return $json;
    }
    
    return ['api_calls' => []];
}

/**
 * Parse Claude's formatted response
 */
function parseClaudeFormattedResponse($response, $query) {
    $json = null;
    
    // Try to extract JSON from markdown code block (handles ```json and ``` variations)
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
        $jsonStr = trim($matches[1]);
        $json = json_decode($jsonStr, true);
    }
    
    // If no markdown block found, try direct JSON parse
    if (!$json) {
        // Try to find JSON object in response
        if (preg_match('/\{[\s\S]*"status"[\s\S]*\}/', $response, $matches)) {
            $json = json_decode($matches[0], true);
        }
    }
    
    // If still no JSON, try the whole response
    if (!$json) {
        $json = json_decode($response, true);
    }
    
    // If we got valid JSON with status field
    if ($json && isset($json['status'])) {
        $json['query'] = $query;
        
        // Ensure metrics is properly formatted
        if (isset($json['metrics']) && is_array($json['metrics'])) {
            // Convert any nested objects in metrics to strings
            foreach ($json['metrics'] as $key => $value) {
                if (is_array($value)) {
                    $json['metrics'][$key] = json_encode($value);
                }
            }
        }
        
        // If there's additional data like top_campaign_by_roas, add it to summary
        $additionalInfo = [];
        $extraFields = ['top_campaign_by_roas', 'top_campaign', 'best_campaign', 'note', 'top_campaigns'];
        foreach ($extraFields as $field) {
            if (isset($json[$field])) {
                if (is_array($json[$field])) {
                    if (isset($json[$field]['name'])) {
                        $additionalInfo[] = "Top Campaign: " . $json[$field]['name'];
                        if (isset($json[$field]['spend'])) {
                            $additionalInfo[] = "Spend: " . $json[$field]['spend'];
                        }
                        if (isset($json[$field]['purchases'])) {
                            $additionalInfo[] = "Purchases: " . $json[$field]['purchases'];
                        }
                        if (isset($json[$field]['roas']) && $json[$field]['roas'] !== "Unable to calculate - purchase value data not available") {
                            $additionalInfo[] = "ROAS: " . $json[$field]['roas'];
                        }
                    }
                } else {
                    $additionalInfo[] = $json[$field];
                }
                unset($json[$field]); // Remove from main response
            }
        }
        
        // Append additional info to summary
        if (!empty($additionalInfo) && isset($json['summary'])) {
            $json['summary'] .= "\n\n" . implode(" | ", $additionalInfo);
        } elseif (!empty($additionalInfo)) {
            $json['summary'] = implode(" | ", $additionalInfo);
        }
        
        return $json;
    }
    
    // Return as text response if JSON parsing failed
    return [
        "status" => true,
        "type" => "text",
        "response" => $response,
        "query" => $query
    ];
}

/**
 * Execute API call based on platform
 */
function executeApiCall($platform, $params, $user, $devToken) {
    if ($platform === 'meta') {
        return executeMetaApiCall($params, $user);
    } elseif ($platform === 'google') {
        return executeGoogleApiCall($params, $user, $devToken);
    }
    
    return ["error" => "Unknown platform: $platform"];
}

/**
 * Execute Meta (Facebook) Ads API call
 */
function executeMetaApiCall($params, $user) {
    $accessToken = $user['facebook']['access_token'] ?? '';
    
    if (empty($accessToken)) {
        return ["error" => "Meta access token not configured"];
    }
    
    $accountId = $params['account_id'] ?? '';
    if (empty($accountId)) {
        return ["error" => "No Meta account ID specified"];
    }
    
    $fields = $params['fields'] ?? 'spend,impressions,clicks,actions,cost_per_action_type';
    $dateFrom = $params['date_from'] ?? date('Y-m-01');
    $dateTo = $params['date_to'] ?? date('Y-m-d');
    $level = $params['level'] ?? '';
    $breakdown = $params['breakdown'] ?? '';
    
    $baseUrl = "https://graph.facebook.com/v24.0/act_{$accountId}/insights";
    
    $queryParams = [
        'fields' => $fields,
        'time_range' => json_encode(['since' => $dateFrom, 'until' => $dateTo]),
        'access_token' => $accessToken
    ];
    
    if ($level) $queryParams['level'] = $level;
    if ($breakdown) $queryParams['breakdowns'] = $breakdown;
    
    $url = $baseUrl . '?' . http_build_query($queryParams);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return ["error" => $error, "http_code" => $httpCode];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['error'])) {
        return [
            "error" => $data['error']['message'] ?? 'Meta API Error', 
            "code" => $data['error']['code'] ?? null
        ];
    }
    
    return $data ?: ["error" => "Empty response from Meta API"];
}

/**
 * Execute Google Ads API call
 */
function executeGoogleApiCall($params, $user, $devToken) {
    $accessToken = $user['google']['g_token'] ?? '';
    $mccId = $user['google']['g_mcc'] ?? '';
    
    if (empty($accessToken)) {
        return ["error" => "Google access token not configured"];
    }
    
    $customerId = $params['account_id'] ?? '';
    if (empty($customerId)) {
        return ["error" => "No Google customer ID specified"];
    }
    
    // Remove hyphens from customer ID if present
    $customerId = str_replace('-', '', $customerId);
    
    $dateFrom = $params['date_from'] ?? date('Y-m-01');
    $dateTo = $params['date_to'] ?? date('Y-m-d');
    
    // Build or use provided query
    $query = $params['query'] ?? '';
    if (empty($query)) {
        $query = "SELECT metrics.clicks, metrics.impressions, metrics.cost_micros, metrics.conversions " .
                 "FROM customer WHERE segments.date BETWEEN '$dateFrom' AND '$dateTo'";
    }
    
    $url = "https://googleads.googleapis.com/v20/customers/{$customerId}/googleAds:search";
    
    $headers = [
        "Authorization: Bearer $accessToken",
        "developer-token: $devToken",
        "Content-Type: application/json"
    ];
    
    if ($mccId) {
        $headers[] = "login-customer-id: " . str_replace('-', '', $mccId);
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode(["query" => $query]),
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return ["error" => $error, "http_code" => $httpCode];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['error'])) {
        return ["error" => $data['error']['message'] ?? 'Google Ads API Error'];
    }
    
    return $data ?: ["error" => "Empty response from Google Ads API"];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>adRescue.AI</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #020617;
            --bg-hover: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-blue: #38bdf8;
            --accent-purple: #8b5cf6;
            --accent-green: #22c55e;
            --accent-gradient: linear-gradient(135deg, #38bdf8, #8b5cf6);
            --border-color: #334155;
            --error-color: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .app-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px 160px;
            min-height: 100vh;
        }
        
        /* Header */
        .app-header {
            text-align: center;
            margin-bottom: 48px;
            padding-top: 20px;
            position: relative;
        }
        
        .brand-container {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }
        
        .brand-icon {
            width: 52px;
            height: 52px;
            background: var(--accent-gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        
        .brand-text h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            text-align: left;
        }
        
        .powered-by {
            font-size: 13px;
            color: var(--text-secondary);
            text-align: left;
        }
        
        .powered-by span {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
        }
        
        .tagline {
            color: var(--text-secondary);
            font-size: 15px;
            margin-top: 8px;
        }
        
        .new-chat-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .new-chat-btn:hover {
            background: var(--bg-hover);
            border-color: var(--accent-blue);
        }
        
        .new-chat-btn i {
            font-size: 16px;
        }
        
        .context-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            font-size: 12px;
            color: var(--accent-blue);
            margin-top: 12px;
        }
        
        .context-badge i {
            font-size: 14px;
        }
        
        /* Chat Container */
        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        /* Messages */
        .message-wrapper {
            display: flex;
            gap: 14px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-wrapper.user {
            flex-direction: row-reverse;
        }
        
        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
        }
        
        .message-wrapper.user .avatar {
            background: var(--accent-gradient);
            color: white;
        }
        
        .message-wrapper.assistant .avatar {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--accent-blue);
        }
        
        .message-bubble {
            max-width: 85%;
        }
        
        .message-wrapper.user .message-bubble {
            background: var(--accent-gradient);
            padding: 14px 20px;
            border-radius: 20px 20px 6px 20px;
            font-size: 15px;
        }
        
        .message-wrapper.assistant .message-bubble {
            background: transparent;
        }
        
        /* Result Card */
        .result-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            overflow: hidden;
        }
        
        .result-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .client-info h3 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        
        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: var(--bg-secondary);
            border-radius: 10px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .date-badge i {
            color: var(--accent-blue);
        }
        
        .result-body {
            padding: 24px;
        }
        
        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }
        
        .metric-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 22px;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .metric-item:hover {
            border-color: var(--accent-blue);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(56, 189, 248, 0.15);
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Summary */
        .summary-box {
            margin-top: 20px;
            padding: 18px 22px;
            background: var(--bg-secondary);
            border-left: 4px solid var(--accent-blue);
            border-radius: 0 14px 14px 0;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.7;
        }
        
        /* Additional Data */
        .additional-data {
            margin-top: 20px;
            padding: 16px 20px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
        }
        
        .additional-info {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.8;
        }
        
        .additional-info strong {
            color: var(--accent-purple);
        }
        
        /* Note Box */
        .note-box {
            margin-top: 16px;
            padding: 14px 18px;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 10px;
            color: #fbbf24;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .note-box i {
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        /* Action Items Box */
        .action-items-box {
            margin-top: 20px;
            padding: 18px 22px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 14px;
        }
        
        .action-items-title {
            color: #f87171;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-items-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .action-items-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(239, 68, 68, 0.15);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .action-items-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        /* Highlights Box */
        .highlights-box {
            margin-top: 20px;
            padding: 18px 22px;
            background: rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.25);
            border-radius: 14px;
        }
        
        .highlights-title {
            color: #4ade80;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .highlights-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .highlights-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(34, 197, 94, 0.15);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .highlights-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        /* Table */
        .table-wrapper {
            overflow-x: auto;
            margin: 0 -24px -24px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            padding: 16px 20px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            background: var(--bg-secondary);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .data-table tbody tr:hover {
            background: rgba(56, 189, 248, 0.05);
        }
        
        /* Chart */
        .chart-wrapper {
            padding: 20px;
            min-height: 300px;
        }
        
        /* Text Response */
        .text-response {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 24px;
            color: var(--text-primary);
            line-height: 1.8;
            font-size: 15px;
        }
        
        /* Error */
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 14px;
            padding: 18px 22px;
            color: #fca5a5;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        
        .error-box i {
            font-size: 22px;
            color: var(--error-color);
            flex-shrink: 0;
        }
        
        /* Loading */
        .loading-indicator {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 22px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
        }
        
        .loading-dots {
            display: flex;
            gap: 6px;
        }
        
        .loading-dots span {
            width: 10px;
            height: 10px;
            background: var(--accent-blue);
            border-radius: 50%;
            animation: pulse 1.4s infinite;
        }
        
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes pulse {
            0%, 80%, 100% { transform: scale(0.7); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }
        
        .loading-text {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Welcome */
        .welcome-panel {
            text-align: center;
          
        }
        
        .welcome-icon {
            font-size: 72px;
            margin-bottom: 28px;
        }
        
        .welcome-title {
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 14px;
        }
        
        .welcome-desc {
            color: var(--text-secondary);
            font-size: 16px;
            max-width: 520px;
            margin: 0 auto 44px;
            line-height: 1.7;
        }
        
        .examples-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            max-width: 820px;
            margin: 0 auto;
        }
        
        .example-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 22px;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .example-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(56, 189, 248, 0.15);
        }
        
        .example-icon {
            width: 46px;
            height: 46px;
            background: var(--bg-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        
        .example-text {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        /* Input Area */
        .input-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, var(--bg-primary) 25%);
            padding: 35px 20px 28px;
            z-index: 100;
        }
        
        .input-box {
            max-width: 960px;
            margin: 0 auto;
            position: relative;
        }
        
        .suggestions-panel {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            margin-bottom: 12px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 -12px 50px rgba(0,0,0,0.4);
        }
        
        .suggestion-row {
            padding: 14px 20px;
            cursor: pointer;
            transition: background 0.15s;
            border-bottom: 1px solid var(--border-color);
        }
        
        .suggestion-row:last-child {
            border-bottom: none;
        }
        
        .suggestion-row:hover {
            background: var(--bg-secondary);
        }
        
        .suggestion-name {
            font-weight: 500;
            font-size: 15px;
        }
        
        .suggestion-nic {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 3px;
        }
        
        .input-group-custom {
            display: flex;
            gap: 14px;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 18px;
            padding: 10px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .input-group-custom:focus-within {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }
        
        #queryInput {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 16px;
            padding: 12px 16px;
            outline: none;
        }
        
        #queryInput::placeholder {
            color: var(--text-muted);
        }
        
        #askBtn {
            background: var(--accent-gradient);
            border: none;
            border-radius: 14px;
            padding: 14px 32px;
            color: white;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }
        
        #askBtn:hover:not(:disabled) {
            opacity: 0.9;
            transform: scale(1.02);
        }
        
        #askBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
        
        /* Responsive */
        @media (max-width: 640px) {
            .app-header { margin-bottom: 32px; }
            .welcome-title { font-size: 24px; }
            .metric-value { font-size: 24px; }
            .examples-grid { grid-template-columns: 1fr; }
            #askBtn { padding: 12px 20px; }
            #askBtn span { display: none; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="brand-container">
                <div class="brand-icon">🎯</div>
                <div class="brand-text">
                    <h1>adRescue.AI</h1>                    
                </div>
            </div>
            <p class="tagline">Ask anything about your Meta & Google Ads performance</p>
            <button class="new-chat-btn" id="newChatBtn" title="Start new conversation">
                <i class="bi bi-plus-circle"></i> New Chat
            </button>
        </header>
        
        <main class="chat-container" id="chatContainer">
            <div class="welcome-panel" id="welcomePanel">
                <div class="welcome-icon">💬</div>
                <h2 class="welcome-title">How can I help you today?</h2>
                <p class="welcome-desc">
                    Ask me about your advertising performance across Meta and Google Ads. 
                    I can help with spend analysis, lead metrics, CPL calculations, and more.
                </p>
                
                <div class="examples-grid">
                    <div class="example-card" data-query="Show today's spend, leads and CPL for RWD Waterfront">
                        <div class="example-icon">📊</div>
                        <div class="example-text">Today's performance metrics</div>
                    </div>
                    <div class="example-card" data-query="Which campaigns are underperforming for Eden Park this month?">
                        <div class="example-icon">⚠️</div>
                        <div class="example-text">Find underperforming campaigns</div>
                    </div>
                    <div class="example-card" data-query="Rank KMC campaigns by CPL in last 7 days">
                        <div class="example-icon">🏆</div>
                        <div class="example-text">Rank campaigns by performance</div>
                    </div>
                    <div class="example-card" data-query="Give me action items for Digital Azadi today">
                        <div class="example-icon">⚡</div>
                        <div class="example-text">Get actionable recommendations</div>
                    </div>
                    <div class="example-card" data-query="Compare today vs yesterday performance for RLD Altima">
                        <div class="example-icon">📈</div>
                        <div class="example-text">Compare performance periods</div>
                    </div>
                    <div class="example-card" data-query="Which ad sets have high spend but zero conversions for iYRA City?">
                        <div class="example-icon">🔴</div>
                        <div class="example-text">Find wasted spend</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div class="input-fixed">
        <div class="input-box">
            <div class="suggestions-panel" id="suggestionsPanel"></div>
            <div class="input-group-custom">
                <input type="text" id="queryInput" placeholder="Type @ to mention a client, or ask any question..." autocomplete="off">
                <button id="askBtn">
                    <i class="bi bi-send-fill"></i>
                    <span>Ask</span>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Global state
        let clients = [];
        let isLoading = false;
        let currentClient = null;
        const configUrl = '<?php echo $CONFIG_API_URL; ?>';
        
        // Initialize
        $(document).ready(function() {
            loadClientList();
            $('#queryInput').focus();
        });
        
        // New Chat button handler
        $('#newChatBtn').on('click', function() {
            if (confirm('Start a new conversation? This will clear the current context.')) {
                $.get('?reset=1', function() {
                    currentClient = null;
                    $('#chatContainer').html(`
                        <div class="welcome-panel" id="welcomePanel">
                            <div class="welcome-icon">💬</div>
                            <h2 class="welcome-title">How can I help you today?</h2>
                            <p class="welcome-desc">
                                Ask me about your advertising performance across Meta and Google Ads. 
                                I can help with spend analysis, lead metrics, CPL calculations, and more.
                            </p>
                            
                            <div class="examples-grid">
                                <div class="example-card" data-query="Show today's spend, leads and CPL for RWD Waterfront">
                                    <div class="example-icon">📊</div>
                                    <div class="example-text">Today's performance metrics</div>
                                </div>
                                <div class="example-card" data-query="Which campaigns are underperforming for Eden Park this month?">
                                    <div class="example-icon">⚠️</div>
                                    <div class="example-text">Find underperforming campaigns</div>
                                </div>
                                <div class="example-card" data-query="Rank Casagrand campaigns by CPL in last 7 days">
                                    <div class="example-icon">🏆</div>
                                    <div class="example-text">Rank campaigns by performance</div>
                                </div>
                                <div class="example-card" data-query="Give me action items for Digital Azadi today">
                                    <div class="example-icon">⚡</div>
                                    <div class="example-text">Get actionable recommendations</div>
                                </div>
                                <div class="example-card" data-query="Compare today vs yesterday performance for Urbando">
                                    <div class="example-icon">📈</div>
                                    <div class="example-text">Compare performance periods</div>
                                </div>
                                <div class="example-card" data-query="Which ad sets have high spend but zero conversions for Mahendra?">
                                    <div class="example-icon">🔴</div>
                                    <div class="example-text">Find wasted spend</div>
                                </div>
                            </div>
                        </div>
                    `);
                    updateContextBadge(null);
                    bindExampleCards();
                });
            }
        });
        
        function bindExampleCards() {
            $('.example-card').off('click').on('click', function() {
                const query = $(this).data('query');
                $('#queryInput').val(query);
                submitQuery();
            });
        }
        
        function updateContextBadge(clientName) {
            $('.context-badge').remove();
            if (clientName) {
                $('.app-header .tagline').after(`
                    <div class="context-badge">
                        <i class="bi bi-chat-dots"></i>
                        Talking about: <strong>${escapeHtml(clientName)}</strong>
                    </div>
                `);
            }
        }
        
        // Load clients for @ mentions
        function loadClientList() {
            $.getJSON(configUrl, function(data) {
                if (data?.status && data?.data?.accounts?.list) {
                    clients = data.data.accounts.list.map(a => ({
                        name: (a.client_name || '').trim(),
                        nic: (a.nic_name || '').trim()
                    })).filter(c => c.name);
                }
            }).fail(function() {
                console.warn('Could not load client list for suggestions');
            });
        }
        
        // Handle @ mention suggestions
        $('#queryInput').on('input', function() {
            const val = this.value;
            const cursorPos = this.selectionStart;
            const beforeCursor = val.substring(0, cursorPos);
            const atIndex = beforeCursor.lastIndexOf('@');
            
            if (atIndex === -1) {
                $('#suggestionsPanel').hide();
                return;
            }
            
            const searchTerm = beforeCursor.substring(atIndex + 1).toLowerCase();
            showSuggestions(searchTerm);
        });
        
        function showSuggestions(term) {
            if (!clients.length) {
                $('#suggestionsPanel').hide();
                return;
            }
            
            const matches = clients.filter(c => 
                c.name.toLowerCase().includes(term) || 
                (c.nic && c.nic.toLowerCase().includes(term))
            ).slice(0, 8);
            
            if (!matches.length) {
                $('#suggestionsPanel').hide();
                return;
            }
            
            const html = matches.map(c => `
                <div class="suggestion-row" data-name="${escapeHtml(c.name)}">
                    <div class="suggestion-name">${escapeHtml(c.name)}</div>
                    ${c.nic ? `<div class="suggestion-nic">${escapeHtml(c.nic)}</div>` : ''}
                </div>
            `).join('');
            
            $('#suggestionsPanel').html(html).show();
        }
        
        // Select suggestion
        $('#suggestionsPanel').on('click', '.suggestion-row', function() {
            const name = $(this).data('name');
            const input = document.getElementById('queryInput');
            const val = input.value;
            const cursorPos = input.selectionStart;
            const beforeCursor = val.substring(0, cursorPos);
            const afterCursor = val.substring(cursorPos);
            const atIndex = beforeCursor.lastIndexOf('@');
            
            if (atIndex !== -1) {
                const beforeAt = beforeCursor.substring(0, atIndex);
                input.value = beforeAt + name + ' ' + afterCursor.trimStart();
                input.focus();
                const newPos = (beforeAt + name + ' ').length;
                input.setSelectionRange(newPos, newPos);
            }
            
            $('#suggestionsPanel').hide();
        });
        
        // Hide suggestions on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#queryInput, #suggestionsPanel').length) {
                $('#suggestionsPanel').hide();
            }
        });
        
        // Example cards click - use bindExampleCards
        bindExampleCards();
        
        // Submit handlers
        $('#askBtn').on('click', submitQuery);
        
        $('#queryInput').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                submitQuery();
            }
        });
        
        function submitQuery() {
            const query = $('#queryInput').val().trim();
            if (!query || isLoading) return;
            
            isLoading = true;
            $('#welcomePanel').fadeOut(200, function() { $(this).remove(); });
            $('#askBtn').prop('disabled', true);
            
            // Add user message
            appendUserMessage(query);
            
            // Add loading indicator
            const loadingId = 'loading-' + Date.now();
            appendLoadingMessage(loadingId);
            
            // Clear input
            $('#queryInput').val('');
            
            // Make API call
            $.ajax({
                url: '?ajax=1',
                method: 'POST',
                data: { query: query },
                dataType: 'json',
                timeout: 120000
            })
            .done(function(response) {
                $(`#${loadingId}`).remove();
                renderResponse(response);
            })
            .fail(function(xhr, status, error) {
                $(`#${loadingId}`).remove();
                let errorMsg = 'Failed to process your request. Please try again.';
                if (status === 'timeout') {
                    errorMsg = 'Request timed out. Please try a simpler query.';
                }
                appendErrorMessage(errorMsg);
            })
            .always(function() {
                isLoading = false;
                $('#askBtn').prop('disabled', false);
                $('#queryInput').focus();
            });
        }
        
        function appendUserMessage(text) {
            const html = `
                <div class="message-wrapper user">
                    <div class="avatar"><i class="bi bi-person-fill"></i></div>
                    <div class="message-bubble">${escapeHtml(text)}</div>
                </div>
            `;
            $('#chatContainer').append(html);
            scrollToBottom();
        }
        
        function appendLoadingMessage(id) {
            const html = `
                <div class="message-wrapper assistant" id="${id}">
                    <div class="avatar"><i class="bi bi-robot"></i></div>
                    <div class="message-bubble">
                        <div class="loading-indicator">
                            <div class="loading-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <span class="loading-text">Analyzing your query and fetching data...</span>
                        </div>
                    </div>
                </div>
            `;
            $('#chatContainer').append(html);
            scrollToBottom();
        }
        
        function appendErrorMessage(msg) {
            const html = `
                <div class="message-wrapper assistant">
                    <div class="avatar"><i class="bi bi-robot"></i></div>
                    <div class="message-bubble">
                        <div class="error-box">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>${escapeHtml(msg)}</div>
                        </div>
                    </div>
                </div>
            `;
            $('#chatContainer').append(html);
            scrollToBottom();
        }
        
        function renderResponse(response) {
            if (!response.status) {
                appendErrorMessage(response.message || 'Something went wrong');
                return;
            }
            
            // Update current client context
            if (response.client) {
                currentClient = response.client;
                updateContextBadge(currentClient);
            }
            
            // Check if response text contains unparsed JSON
            if (response.type === 'text' && response.response) {
                let text = response.response;
                if (text.includes('"status"') && (text.includes('"metrics"') || text.includes('"client"'))) {
                    try {
                        let jsonStr = text.replace(/```json\s*|\s*```/g, '').trim();
                        let parsed = JSON.parse(jsonStr);
                        if (parsed && parsed.status) {
                            response = parsed;
                            response.type = response.type || 'card';
                            if (response.client) {
                                currentClient = response.client;
                                updateContextBadge(currentClient);
                            }
                        }
                    } catch (e) {
                        // Keep as text
                    }
                }
            }
            
            let contentHtml = '';
            
            switch (response.type) {
                case 'card':
                    contentHtml = buildCardHtml(response);
                    break;
                case 'table':
                    contentHtml = buildTableHtml(response);
                    break;
                case 'chart':
                    contentHtml = buildChartHtml(response);
                    break;
                default:
                    contentHtml = buildTextHtml(response);
            }
            
            const html = `
                <div class="message-wrapper assistant">
                    <div class="avatar"><i class="bi bi-robot"></i></div>
                    <div class="message-bubble">${contentHtml}</div>
                </div>
            `;
            
            $('#chatContainer').append(html);
            
            // Initialize chart if needed
            if (response.type === 'chart' && response.chart_data) {
                setTimeout(() => initChart(response.chart_data), 100);
            }
            
            scrollToBottom();
        }
        
        function buildCardHtml(data) {
            let metricsHtml = '';
            if (data.metrics) {
                Object.entries(data.metrics).forEach(([key, value]) => {
                    // Skip complex objects, arrays, or very long values
                    if (typeof value === 'object' && value !== null) {
                        return;
                    }
                    let displayValue = String(value);
                    // Truncate very long values
                    if (displayValue.length > 25 && !displayValue.startsWith('₹')) {
                        displayValue = displayValue.substring(0, 22) + '...';
                    }
                    metricsHtml += `
                        <div class="metric-item">
                            <div class="metric-value">${escapeHtml(displayValue)}</div>
                            <div class="metric-label">${formatKey(key)}</div>
                        </div>
                    `;
                });
            }
            
            // Handle action items
            let actionItemsHtml = '';
            if (data.action_items && Array.isArray(data.action_items)) {
                actionItemsHtml = `
                    <div class="action-items-box">
                        <div class="action-items-title"><i class="bi bi-lightning-charge-fill"></i> Action Items</div>
                        <ul class="action-items-list">
                            ${data.action_items.map(item => `<li>${item}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            // Handle highlights
            let highlightsHtml = '';
            if (data.highlights && Array.isArray(data.highlights)) {
                highlightsHtml = `
                    <div class="highlights-box">
                        <div class="highlights-title"><i class="bi bi-stars"></i> Key Highlights</div>
                        <ul class="highlights-list">
                            ${data.highlights.map(item => `<li>${item}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            // Handle additional campaign/performance data
            let additionalHtml = '';
            const extraFields = ['top_campaign_by_roas', 'top_campaign', 'best_campaign', 'top_campaigns', 'comparison'];
            extraFields.forEach(field => {
                if (data[field]) {
                    if (typeof data[field] === 'object') {
                        let info = data[field];
                        additionalHtml += `<div class="additional-info">`;
                        additionalHtml += `<strong>🏆 ${formatKey(field)}:</strong> `;
                        if (info.name) additionalHtml += `${escapeHtml(info.name)}`;
                        if (info.spend) additionalHtml += ` | Spend: ${escapeHtml(String(info.spend))}`;
                        if (info.purchases) additionalHtml += ` | Purchases: ${info.purchases}`;
                        if (info.leads) additionalHtml += ` | Leads: ${info.leads}`;
                        if (info.roas && !String(info.roas).includes('Unable')) additionalHtml += ` | ROAS: ${escapeHtml(String(info.roas))}`;
                        if (info.spend_change) additionalHtml += ` | Spend: ${escapeHtml(String(info.spend_change))}`;
                        if (info.leads_change) additionalHtml += ` | Leads: ${escapeHtml(String(info.leads_change))}`;
                        additionalHtml += `</div>`;
                    }
                }
            });
            
            // Handle notes
            let noteHtml = '';
            if (data.note) {
                noteHtml = `<div class="note-box"><i class="bi bi-info-circle"></i> ${escapeHtml(data.note)}</div>`;
            }
            
            return `
                <div class="result-card">
                    <div class="result-header">
                        <div class="client-info">
                            <h3>${escapeHtml(data.client || 'Results')}</h3>
                        </div>
                        ${data.date_range ? `
                            <div class="date-badge">
                                <i class="bi bi-calendar3"></i>
                                ${escapeHtml(data.date_range.from || '')} → ${escapeHtml(data.date_range.to || '')}
                            </div>
                        ` : ''}
                    </div>
                    <div class="result-body">
                        ${metricsHtml ? `<div class="metrics-grid">${metricsHtml}</div>` : ''}
                        ${additionalHtml ? `<div class="additional-data">${additionalHtml}</div>` : ''}
                        ${highlightsHtml}
                        ${actionItemsHtml}
                        ${data.summary ? `<div class="summary-box">${escapeHtml(data.summary)}</div>` : ''}
                        ${noteHtml}
                    </div>
                </div>
            `;
        }
        
        function buildTableHtml(data) {
            if (!data.table_data?.length) {
                return buildTextHtml({ response: 'No data available' });
            }
            
            const headers = Object.keys(data.table_data[0]);
            const headerHtml = headers.map(h => `<th>${formatKey(h)}</th>`).join('');
            const rowsHtml = data.table_data.map(row => 
                '<tr>' + headers.map(h => `<td>${escapeHtml(String(row[h] || '-'))}</td>`).join('') + '</tr>'
            ).join('');
            
            return `
                <div class="result-card">
                    ${data.client ? `
                        <div class="result-header">
                            <div class="client-info"><h3>${escapeHtml(data.client)}</h3></div>
                        </div>
                    ` : ''}
                    <div class="result-body" style="padding-bottom: 0;">
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead><tr>${headerHtml}</tr></thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                        </div>
                    </div>
                    ${data.summary ? `<div style="padding:24px 24px;"><div class="summary-box" style="margin-top: 16px;">${escapeHtml(data.summary)}</div></div>` : ''}
                </div>
            `;
        }
        
        function buildChartHtml(data) {
            const chartId = 'chart-' + Date.now();
            return `
                <div class="result-card">
                    ${data.client ? `
                        <div class="result-header">
                            <div class="client-info"><h3>${escapeHtml(data.client)}</h3></div>
                        </div>
                    ` : ''}
                    <div class="chart-wrapper">
                        <canvas id="${chartId}" height="280"></canvas>
                    </div>
                    ${data.summary ? `<div style="padding: 0 24px 24px;"><div class="summary-box">${escapeHtml(data.summary)}</div></div>` : ''}
                </div>
            `;
        }
        
        function buildTextHtml(data) {
            let text = data.response || data.summary || 'No response available';
            
            // Check if the text looks like JSON and try to parse it
            if (text.includes('"status"') && text.includes('"metrics"')) {
                try {
                    // Try to extract JSON from the text
                    let jsonMatch = text.match(/```(?:json)?\s*([\s\S]*?)\s*```/);
                    if (jsonMatch) {
                        let parsed = JSON.parse(jsonMatch[1]);
                        if (parsed && parsed.metrics) {
                            // Re-render as card
                            return buildCardHtml(parsed);
                        }
                    }
                    // Try direct parse
                    let directParsed = JSON.parse(text.replace(/```json|```/g, '').trim());
                    if (directParsed && directParsed.metrics) {
                        return buildCardHtml(directParsed);
                    }
                } catch (e) {
                    // Not valid JSON, continue with text display
                }
            }
            
            // Clean up any markdown code blocks for display
            text = text.replace(/```json\s*/g, '').replace(/```\s*/g, '').trim();
            
            // Format the text nicely
            text = text.replace(/\n/g, '<br>');
            
            return `<div class="text-response">${text}</div>`;
        }
        
        function initChart(chartData) {
            const canvas = document.querySelector('.chart-wrapper canvas:last-of-type');
            if (!canvas || !chartData) return;
            
            new Chart(canvas, {
                type: chartData.type || 'bar',
                data: {
                    labels: chartData.labels || [],
                    datasets: (chartData.datasets || []).map(ds => ({
                        ...ds,
                        backgroundColor: ds.backgroundColor || 'rgba(56, 189, 248, 0.6)',
                        borderColor: ds.borderColor || 'rgba(56, 189, 248, 1)',
                        borderWidth: 2
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#94a3b8' } }
                    },
                    scales: {
                        x: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } },
                        y: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } }
                    }
                }
            });
        }
        
        // Utility functions
        function formatKey(key) {
            return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
        
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }
        
        function scrollToBottom() {
            setTimeout(() => {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }, 100);
        }
    </script>
</body>
</html>
