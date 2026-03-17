<?php
/**
 * AI Ads Report - Claude API Backend
 * 
 * Place this file in the same directory as your HTML file
 * Make sure to set your Claude API key below
 */

// ============ CONFIGURATION ============
require_once __DIR__ . '/../env_loader.php';
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
define('CLAUDE_MODEL', getenv('CLAUDE_MODEL') ?: 'claude-sonnet-4-20250514');
define('CLAUDE_MAX_TOKENS', 1024);

// ============ CORS & HEADERS ============
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============ MAIN LOGIC ============
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Only POST method is allowed'
    ]);
    exit();
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['query'])) {
    echo json_encode([
        'status' => false,
        'message' => 'Query is required'
    ]);
    exit();
}

$query = trim($input['query']);
$clientName = isset($input['client']) ? trim($input['client']) : null;
$conversationHistory = isset($input['history']) ? $input['history'] : [];

// Build system prompt
$systemPrompt = "You are an AI assistant specialized in advertising and marketing analytics. 
You help users understand their ad campaign performance data.

When responding to queries:
1. If a client name is mentioned (via @mention), focus your analysis on that specific client
2. Provide clear, structured insights about metrics like spend, impressions, clicks, CTR, conversions, ROAS, etc.
3. When showing metrics, format them clearly with proper formatting
4. Be concise but thorough
5. If you don't have actual data, explain what metrics would typically be relevant and offer to help analyze if data is provided
6. Use markdown formatting for better readability (bold for important numbers, bullet points for lists)

Current client context: " . ($clientName ?: 'No specific client mentioned');

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
        'client' => $clientName
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => $response['error']
    ]);
}

// ============ FUNCTIONS ============
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
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Connection error: ' . $error
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMessage = isset($responseData['error']['message']) 
            ? $responseData['error']['message'] 
            : 'API request failed with status ' . $httpCode;
        
        return [
            'success' => false,
            'error' => $errorMessage
        ];
    }
    
    if (isset($responseData['content'][0]['text'])) {
        return [
            'success' => true,
            'message' => $responseData['content'][0]['text']
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Invalid response from API'
    ];
}
?>
