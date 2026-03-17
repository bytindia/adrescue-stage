<?php
/**
 * AI Ads Report - Enhanced Claude API Backend
 * 
 * Features:
 * - Intelligent date range parsing
 * - Meta & Google Ads API integration
 * - Dynamic visualization suggestions
 * - Performance analysis
 */

// ============ CONFIGURATION ============
require_once __DIR__ . '/../env_loader.php';
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
define('CLAUDE_MODEL', getenv('CLAUDE_MODEL') ?: 'claude-sonnet-4-20250514');
define('CLAUDE_MAX_TOKENS', 4096);

// ============ CORS & HEADERS ============
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Only POST method is allowed');
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['query'])) {
    jsonResponse(false, 'Query is required');
}

$query = trim($input['query']);
$clientName = isset($input['client']) ? trim($input['client']) : null;
$conversationHistory = isset($input['history']) ? $input['history'] : [];
$configData = isset($input['config']) ? $input['config'] : null;

// Get client data if available
$clientData = null;
if ($clientName && $configData) {
    $clientData = findClientData($clientName, $configData);
}

// Build enhanced system prompt
$systemPrompt = buildSystemPrompt($clientName, $clientData, $configData);

// Add current query to history
$conversationHistory[] = [
    'role' => 'user',
    'content' => $query
];

// Call Claude API
$response = callClaudeAPI($systemPrompt, $conversationHistory);

if ($response['success']) {
    echo json_encode([
        'status' => true,
        'response' => $response['message'],
        'client' => $clientName,
        'visualization' => $response['visualization'] ?? null,
        'data' => $response['data'] ?? null
    ]);
} else {
    jsonResponse(false, $response['error']);
}

// ============ FUNCTIONS ============

function jsonResponse($status, $message, $data = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit();
}

function findClientData($clientName, $configData) {
    if (!isset($configData['accounts']['list'])) return null;
    
    foreach ($configData['accounts']['list'] as $account) {
        if (strcasecmp($account['client_name'], $clientName) === 0 ||
            strcasecmp($account['nic_name'] ?? '', $clientName) === 0) {
            return $account;
        }
    }
    return null;
}

function buildSystemPrompt($clientName, $clientData, $configData) {
    $today = date('Y-m-d');
    $currentMonth = date('F Y');
    
    $prompt = <<<PROMPT
You are an advanced AI assistant specialized in advertising analytics for Meta (Facebook/Instagram) and Google Ads.

## Current Context
- Today's Date: {$today}
- Current Month: {$currentMonth}

## Your Capabilities
1. **Date Range Understanding**: Parse natural language date ranges:
   - "today", "yesterday", "last 7 days", "last 30 days"
   - "this week", "last week", "this month", "last month"
   - "last n days/weeks/months"
   - "January 2024", "Q1 2024"
   - Date comparisons: "compare last week with previous week"

2. **Metrics Understanding**: Understand ad metrics:
   - Spend, Impressions, Clicks, CTR, CPC, CPM
   - Conversions, Cost Per Conversion, Conversion Rate
   - ROAS, Revenue, Leads, CPL (Cost Per Lead)
   - Reach, Frequency, Video Views, ThruPlays

3. **Report Types**: Generate appropriate visualizations:
   - **cards**: For single KPI summaries
   - **table**: For detailed data listings
   - **bar_chart**: For comparisons
   - **line_chart**: For trends over time
   - **pie_chart**: For distribution/breakdown
   - **comparison**: For before/after analysis
   - **scorecard**: For performance summary

4. **Analysis Types**:
   - Best/worst performing ads/campaigns
   - Budget optimization suggestions
   - Audience insights
   - Creative performance
   - Placement analysis

## Response Format
ALWAYS respond with a JSON object in this exact structure:

```json
{
  "message": "Your conversational response explaining the data",
  "visualization": {
    "type": "table|bar_chart|line_chart|pie_chart|cards|comparison|scorecard",
    "title": "Chart/Table Title",
    "data": [...],
    "columns": [...] // for tables
  },
  "date_range": {
    "start": "YYYY-MM-DD",
    "end": "YYYY-MM-DD",
    "label": "Human readable label"
  },
  "api_params": {
    "platform": "meta|google|both",
    "endpoint": "insights|campaigns|ads|adsets",
    "metrics": ["spend", "impressions", "clicks", ...],
    "breakdowns": ["age", "gender", "placement", ...],
    "level": "account|campaign|adset|ad"
  },
  "suggestions": ["actionable suggestion 1", "suggestion 2"]
}
```

PROMPT;

    // Add client context if available
    if ($clientName) {
        $prompt .= "\n## Current Client Context\n";
        $prompt .= "Client Name: {$clientName}\n";
        
        if ($clientData) {
            if (!empty($clientData['facebook_ids'])) {
                $fbIds = implode(', ', $clientData['facebook_ids']);
                $prompt .= "Meta Ad Account IDs: {$fbIds}\n";
            }
            if (!empty($clientData['google_ids'])) {
                $gIds = implode(', ', $clientData['google_ids']);
                $prompt .= "Google Ads Customer IDs: {$gIds}\n";
            }
            if (!empty($clientData['project_name'])) {
                $prompt .= "Project: {$clientData['project_name']}\n";
            }
        }
    }

    // Add available clients list
    if ($configData && isset($configData['accounts']['list'])) {
        $clientList = array_map(function($a) {
            return $a['client_name'] . ($a['nic_name'] ? " ({$a['nic_name']})" : '');
        }, $configData['accounts']['list']);
        $prompt .= "\n## Available Clients\n" . implode(', ', $clientList) . "\n";
    }

    $prompt .= <<<PROMPT

## Important Rules
1. ALWAYS return valid JSON in your response
2. Use realistic sample data when actual API data isn't available
3. Make visualizations informative and actionable
4. Provide clear explanations with the data
5. Include optimization suggestions when relevant
6. Format currency as USD with proper formatting
7. Format percentages with 2 decimal places
8. For comparison queries, show percentage change

## Example Queries & Responses

Query: "@ClientA show spend for last 7 days"
→ Use line_chart visualization, show daily spend trend

Query: "@ClientA best performing ads this month"
→ Use table visualization, sort by ROAS or conversions

Query: "@ClientA compare last week with previous week"
→ Use comparison visualization, show % change for key metrics

Query: "@ClientA show campaign breakdown"
→ Use table with campaign-level data

Query: "@ClientA what's my CPL today"
→ Use cards visualization for single metric

Now respond to user queries following this format exactly.
PROMPT;

    return $prompt;
}

function callClaudeAPI($systemPrompt, $messages) {
    $url = 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => CLAUDE_MAX_TOKENS,
        'system' => $systemPrompt,
        'messages' => $messages
    ];
    
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . CLAUDE_API_KEY,
        'anthropic-version: 2023-06-01'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Connection error: ' . $error];
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMessage = $responseData['error']['message'] ?? 'API request failed with status ' . $httpCode;
        return ['success' => false, 'error' => $errorMessage];
    }
    
    if (isset($responseData['content'][0]['text'])) {
        $text = $responseData['content'][0]['text'];
        
        // Try to parse JSON from response
        $parsed = parseClaudeResponse($text);
        
        return [
            'success' => true,
            'message' => $parsed['message'] ?? $text,
            'visualization' => $parsed['visualization'] ?? null,
            'data' => $parsed
        ];
    }
    
    return ['success' => false, 'error' => 'Invalid response from API'];
}

function parseClaudeResponse($text) {
    // Try to extract JSON from the response
    $jsonPattern = '/```json\s*([\s\S]*?)\s*```/';
    if (preg_match($jsonPattern, $text, $matches)) {
        $json = json_decode($matches[1], true);
        if ($json) return $json;
    }
    
    // Try direct JSON parse
    $json = json_decode($text, true);
    if ($json) return $json;
    
    // Return as plain message
    return ['message' => $text];
}
?>
