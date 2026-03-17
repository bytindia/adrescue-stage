<?php
session_start();

$password = "^JoA2#fI9@hDz"; // change your password

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
Auth(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Ads Report</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Marked.js for Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <style>
        :root {
            /* Claude AI Color Palette */
            --bg-primary: #1a1a1a;
            --bg-secondary: #212121;
            --bg-tertiary: #2f2f2f;
            --bg-hover: #3a3a3a;
            --bg-input: #2f2f2f;
            --bg-code: #1e1e1e;
            
            --text-primary: #ececec;
            --text-secondary: #b4b4b4;
            --text-muted: #8e8e8e;
            --text-code: #e6db74;
            
            --accent: #d97757;
            --accent-secondary: #f5a97f;
            --accent-hover: #e8856a;
            --accent-subtle: rgba(217, 119, 87, 0.15);
            
            --border: #424242;
            --border-subtle: #333333;
            
            --success: #4ade80;
            --warning: #fbbf24;
            --error: #f87171;
            --info: #60a5fa;
            
            --max-content-width: 850px;
            --header-height: 56px;
            --sidebar-width: 260px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            min-height: 100vh;
            overflow: hidden;
        }
        
        .app-container { display: flex; height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .sidebar-header { padding: 16px; border-bottom: 1px solid var(--border-subtle); }
        
        .new-chat-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        
        .new-chat-btn:hover { background: var(--bg-hover); }
        
        .new-chat-btn svg { width: 18px; height: 18px; stroke: var(--text-secondary); }
        
        .sidebar-content { flex: 1; overflow-y: auto; padding: 12px; }
        
        .sidebar-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 12px 8px 8px;
        }
        
        .chat-history-item {
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease;
            margin-bottom: 2px;
        }
        
        .chat-history-item:hover { background: var(--bg-hover); }
        .chat-history-item.active { background: var(--bg-tertiary); }
        
        .chat-history-title {
            font-size: 14px;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-history-date { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        
        .sidebar-footer { padding: 16px; border-top: 1px solid var(--border-subtle); }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        
        .user-profile:hover { background: var(--bg-hover); }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: white;
        }
        
        .user-name { font-size: 14px; font-weight: 500; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        /* Header */
        .header {
            height: var(--header-height);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-subtle);
            background: var(--bg-secondary);
        }
        
        .header-left { display: flex; align-items: center; gap: 12px; }
        
        .logo { display: flex; align-items: center; gap: 10px; }
        
        .logo-icon {
            width: 28px;
            height: 28px;
            background: var(--accent);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon svg { width: 16px; height: 16px; fill: white; }
        
        .logo-text { font-weight: 600; font-size: 16px; letter-spacing: -0.01em; }
        
        .model-selector {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-tertiary);
            border-radius: 6px;
            font-size: 13px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        
        .model-selector:hover { background: var(--bg-hover); color: var(--text-primary); }
        
        /* Chat Area */
        .chat-container { flex: 1; overflow-y: auto; padding: 24px; scroll-behavior: smooth; }
        
        .chat-content { max-width: var(--max-content-width); margin: 0 auto; }
        
        /* Welcome Screen */
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 200px);
            text-align: center;
            animation: fadeIn 0.4s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-logo {
            width: 56px;
            height: 56px;
            background: var(--accent);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(217, 119, 87, 0.25);
        }
        
        .welcome-logo svg { width: 28px; height: 28px; fill: white; }
        
        .welcome-title { font-size: 28px; font-weight: 600; margin-bottom: 12px; letter-spacing: -0.02em; }
        
        .welcome-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 480px;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .welcome-subtitle strong { color: var(--accent); }
        
        .suggestion-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            width: 100%;
            max-width: 560px;
        }
        
        .suggestion-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 16px;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .suggestion-card:hover {
            background: var(--bg-tertiary);
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        
        .suggestion-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .suggestion-card:nth-child(1) .suggestion-icon { background: rgba(102, 126, 234, 0.2); }
        .suggestion-card:nth-child(2) .suggestion-icon { background: rgba(67, 233, 123, 0.2); }
        .suggestion-card:nth-child(3) .suggestion-icon { background: rgba(79, 172, 254, 0.2); }
        .suggestion-card:nth-child(4) .suggestion-icon { background: rgba(250, 112, 154, 0.2); }
        
        .suggestion-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .suggestion-desc { font-size: 13px; color: var(--text-muted); }
        
        /* Messages */
        .messages { display: flex; flex-direction: column; gap: 32px; }
        
        .message { display: flex; gap: 16px; animation: messageIn 0.3s ease; }
        
        @keyframes messageIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .message.user .message-avatar { background: var(--bg-tertiary); color: var(--text-primary); }
        .message.assistant .message-avatar { background: var(--accent); color: white; }
        
        .message-body { flex: 1; min-width: 0; }
        
        .message-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        
        .message-role { font-weight: 600; font-size: 14px; }
        
        .client-badge {
            background: var(--accent-subtle);
            color: var(--accent);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .message-content { color: var(--text-primary); line-height: 1.7; }
        .message-content p { margin-bottom: 12px; }
        .message-content p:last-child { margin-bottom: 0; }
        .message-content strong { font-weight: 600; }
        
        .message-content code {
            background: var(--bg-code);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Source Code Pro', monospace;
            font-size: 13px;
            color: var(--text-code);
        }
        
        .message-content pre {
            background: var(--bg-code);
            border-radius: 8px;
            padding: 16px;
            overflow-x: auto;
            margin: 16px 0;
            border: 1px solid var(--border-subtle);
        }
        
        .message-content pre code { background: none; padding: 0; }
        .message-content ul, .message-content ol { margin: 12px 0 12px 24px; }
        .message-content li { margin-bottom: 8px; }
        
        /* Visualizations */
        .visualization-container {
            margin: 20px 0;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .viz-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .viz-title { font-size: 15px; font-weight: 600; }
        
        .viz-actions { display: flex; gap: 8px; }
        
        .viz-action-btn {
            padding: 6px 10px;
            background: var(--bg-tertiary);
            border: none;
            border-radius: 6px;
            color: var(--text-secondary);
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        
        .viz-action-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
        
        .viz-body { padding: 20px; }
        
        /* Metric Cards */
        .metric-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
        
        .metric-card {
            background: var(--bg-tertiary);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        
        .metric-card:hover { transform: translateY(-2px); }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        
        .metric-card.spend::before { background: #667eea; }
        .metric-card.impressions::before { background: #f5576c; }
        .metric-card.clicks::before { background: #4facfe; }
        .metric-card.ctr::before { background: #43e97b; }
        .metric-card.cpl::before { background: #fa709a; }
        .metric-card.conversions::before { background: #a8edea; }
        .metric-card.cpc::before { background: #fbbf24; }
        .metric-card.roas::before { background: #60a5fa; }
        
        .metric-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        
        .metric-value { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; }
        
        .metric-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .metric-change.positive { color: var(--success); background: rgba(74, 222, 128, 0.15); }
        .metric-change.negative { color: var(--error); background: rgba(248, 113, 113, 0.15); }
        
        /* Data Table */
        .data-table-wrapper { overflow-x: auto; }
        
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        
        .data-table th {
            text-align: left;
            padding: 12px 16px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-subtle);
            background: var(--bg-tertiary);
        }
        
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-primary);
        }
        
        .data-table tr:hover td { background: var(--bg-hover); }
        .data-table tr:last-child td { border-bottom: none; }
        
        .table-metric { font-weight: 600; font-variant-numeric: tabular-nums; }
        .table-metric.positive { color: var(--success); }
        .table-metric.negative { color: var(--error); }
        
        /* Comparison */
        .comparison-view { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        
        .comparison-period { background: var(--bg-tertiary); border-radius: 12px; padding: 20px; }
        
        .comparison-period-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-subtle);
        }
        
        .comparison-period-title { font-weight: 600; font-size: 14px; }
        .comparison-period-date { font-size: 12px; color: var(--text-muted); }
        
        .comparison-metrics { display: flex; flex-direction: column; gap: 12px; }
        
        .comparison-metric-row { display: flex; justify-content: space-between; align-items: center; }
        
        .comparison-metric-label { font-size: 13px; color: var(--text-secondary); }
        .comparison-metric-value { font-size: 15px; font-weight: 600; font-variant-numeric: tabular-nums; }
        
        /* Suggestions */
        .suggestions-container {
            margin-top: 20px;
            background: var(--accent-subtle);
            border: 1px solid rgba(217, 119, 87, 0.3);
            border-radius: 12px;
            padding: 16px 20px;
        }
        
        .suggestions-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .suggestions-icon { width: 20px; height: 20px; color: var(--accent); }
        .suggestions-title { font-weight: 600; font-size: 14px; color: var(--accent); }
        .suggestions-list { list-style: none; }
        
        .suggestions-list li {
            padding: 8px 0;
            font-size: 14px;
            color: var(--text-primary);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .suggestions-list li::before { content: '→'; color: var(--accent); flex-shrink: 0; }
        
        /* Typing Indicator */
        .typing-indicator { display: flex; align-items: center; gap: 12px; padding: 16px 0; }
        .typing-dots { display: flex; gap: 4px; }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            animation: typingPulse 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typingPulse {
            0%, 60%, 100% { transform: scale(1); opacity: 0.4; }
            30% { transform: scale(1.2); opacity: 1; }
        }
        
        .typing-text { color: var(--text-muted); font-size: 14px; }
        
        /* Error Message */
        .error-message {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.3);
            border-radius: 12px;
            padding: 16px 20px;
            color: var(--error);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .error-icon { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
        
        /* Input Area */
        .input-area { padding: 16px 24px 24px; background: linear-gradient(to top, var(--bg-primary) 85%, transparent); }
        
        .input-wrapper { max-width: var(--max-content-width); margin: 0 auto; position: relative; }
        
        /* Client Dropdown */
        .client-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 8px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 -8px 32px rgba(0,0,0,0.4);
            z-index: 100;
        }
        
        .client-dropdown.show { display: block; animation: dropdownIn 0.15s ease; }
        
        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-header {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-subtle);
            position: sticky;
            top: 0;
            background: var(--bg-secondary);
        }
        
        .client-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.1s ease;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .client-option:hover, .client-option.selected { background: var(--bg-hover); }
        
        .client-option-name { font-weight: 500; font-size: 14px; }
        .client-option-nic { font-size: 12px; color: var(--text-muted); }
        .mention-text { color: var(--accent); font-weight: 500; }
        
        .input-box {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 12px 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .input-box:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(217, 119, 87, 0.15);
        }
        
        .input-field {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 15px;
            font-family: inherit;
            resize: none;
            max-height: 150px;
            line-height: 1.5;
            min-height: 24px;
        }
        
        .input-field::placeholder { color: var(--text-muted); }
        .input-field:focus { outline: none; }
        
        .send-button {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--accent);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .send-button:hover:not(:disabled) { background: var(--accent-hover); transform: scale(1.05); }
        .send-button:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .send-button svg { width: 18px; height: 18px; fill: white; }
        
        .input-hint { text-align: center; font-size: 12px; color: var(--text-muted); margin-top: 12px; }
        
        .input-hint kbd {
            display: inline-block;
            padding: 2px 6px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            font-family: inherit;
            font-size: 11px;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
        
        /* Responsive */
        @media (max-width: 900px) {
            .sidebar { display: none; }
            .suggestion-grid { grid-template-columns: 1fr; }
            .comparison-view { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 640px) {
            .chat-container { padding: 16px; }
            .input-area { padding: 12px 16px 20px; }
            .welcome-title { font-size: 22px; }
            .metric-cards { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" onclick="startNewChat()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Chat
                </button>
            </div>
            <div class="sidebar-content">
                <div class="sidebar-section-title">Recent</div>
                <div id="chatHistory"></div>
            </div>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar" id="userAvatar">U</div>
                    <span class="user-name" id="userName">User</span>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <div class="logo">
                        <div class="logo-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                        <span class="logo-text"><img src="https://stage.adrescue.in/images/adRes-w.png" height="25">.AI</span>
                    </div>
                    <div class="model-selector">
                        <span>Claude 3.5 Sonnet</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
            </header>
            
            <!-- Chat Container -->
            <div class="chat-container" id="chatContainer">
                <div class="chat-content">
                    <!-- Welcome Screen -->
                    <div class="welcome-screen" id="welcomeScreen">
                        <div class="welcome-logo">
                            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                        <h1 class="welcome-title">AI Ads Report Assistant</h1>
                        <p class="welcome-subtitle">
                            Analyze your Meta &amp; Google Ads performance. Type <strong>@</strong> to select a client, then ask your question.
                        </p>
                        <div class="suggestion-grid" id="suggestionGrid"></div>
                    </div>
                    
                    <!-- Messages -->
                    <div class="messages" id="messagesContainer"></div>
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="input-area">
                <div class="input-wrapper">
                    <div class="client-dropdown" id="clientDropdown">
                        <div class="dropdown-header">Select a client</div>
                        <div id="clientList"></div>
                    </div>
                    
                    <div class="input-box">
                        <textarea id="inputField" class="input-field" placeholder="Type @ to select a client, then ask your question..." rows="1"></textarea>
                        <button class="send-button" id="sendButton" disabled>
                            <svg viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        </button>
                    </div>
                    <div class="input-hint">
                        <kbd>Enter</kbd> to send · <kbd>Shift+Enter</kbd> for new line · <kbd>@</kbd> to mention client
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ============ CONFIGURATION ============
        const CONFIG = {
            configApiUrl: 'https://stage.adrescue.in/ai-report/config-api.php',
            chatApiUrl: 'api.php',
            maxHistoryLength: 20
        };
        
        // ============ STATE ============
        const state = {
            clients: [],
            configData: null,
            selectedClientIndex: -1,
            isLoading: false,
            conversationHistory: [],
            chatSessions: [],
            currentSessionId: null
        };
        
        // ============ DOM ELEMENTS ============
        const elements = {
            inputField: document.getElementById('inputField'),
            sendButton: document.getElementById('sendButton'),
            clientDropdown: document.getElementById('clientDropdown'),
            clientList: document.getElementById('clientList'),
            messagesContainer: document.getElementById('messagesContainer'),
            welcomeScreen: document.getElementById('welcomeScreen'),
            chatContainer: document.getElementById('chatContainer'),
            suggestionGrid: document.getElementById('suggestionGrid'),
            chatHistory: document.getElementById('chatHistory'),
            userName: document.getElementById('userName'),
            userAvatar: document.getElementById('userAvatar')
        };
        
        // ============ INITIALIZATION ============
        async function init() {
            await loadConfig();
            loadChatHistory();
            setupEventListeners();
            updateSuggestions();
        }
        
        async function loadConfig() {
            try {
                const response = await fetch(CONFIG.configApiUrl);
                const data = await response.json();
                
                if (data?.status && data?.data) {
                    state.configData = data.data;
                    
                    if (data.data.accounts?.list) {
                        state.clients = data.data.accounts.list.map(a => ({
                            name: (a.client_name || '').trim(),
                            nic: (a.nic_name || '').trim(),
                            fbIds: a.facebook_ids || [],
                            gIds: a.google_ids || [],
                            project: a.project_name || ''
                        })).filter(c => c.name.length > 0);
                    }
                    
                    if (data.data.user) {
                        elements.userName.textContent = data.data.user.name || 'User';
                        elements.userAvatar.textContent = (data.data.user.name || 'U').charAt(0).toUpperCase();
                    }
                }
            } catch (e) {
                console.error('Failed to load config:', e);
            }
        }
        
        function updateSuggestions() {
            const sampleClient = state.clients.length > 0 ? state.clients[0].name : 'ClientName';
            
            const suggestions = [
                { icon: '📊', title: 'Performance Overview', desc: 'View key metrics and trends', query: `@${sampleClient} show performance for last 7 days` },
                { icon: '📈', title: 'Best Performing Ads', desc: 'Find your top performers', query: `@${sampleClient} show best performing ads this month` },
                { icon: '💰', title: 'Spend Analysis', desc: 'Track your budget usage', query: `@${sampleClient} analyze spend breakdown by campaign` },
                { icon: '🎯', title: 'CPL Report', desc: 'Cost per lead insights', query: `@${sampleClient} what's my CPL for this month` }
            ];
            
            elements.suggestionGrid.innerHTML = suggestions.map(s => `
                <div class="suggestion-card" onclick="useSuggestion('${escapeHtml(s.query)}')">
                    <div class="suggestion-icon">${s.icon}</div>
                    <div class="suggestion-title">${s.title}</div>
                    <div class="suggestion-desc">${s.desc}</div>
                </div>
            `).join('');
        }
        
        // ============ EVENT LISTENERS ============
        function setupEventListeners() {
            elements.inputField.addEventListener('input', handleInputChange);
            elements.inputField.addEventListener('keydown', handleKeyDown);
            elements.sendButton.addEventListener('click', sendMessage);
            elements.clientList.addEventListener('click', handleClientClick);
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#inputField, #clientDropdown')) hideClientDropdown();
            });
        }
        
        function handleInputChange() {
            const el = elements.inputField;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 150) + 'px';
            elements.sendButton.disabled = !el.value.trim();
            handleClientMention();
        }
        
        function handleClientMention() {
            const val = elements.inputField.value;
            const cursorPos = elements.inputField.selectionStart || val.length;
            const beforeCursor = val.substring(0, cursorPos);
            const atIndex = beforeCursor.lastIndexOf('@');
            
            if (atIndex === -1) { hideClientDropdown(); return; }
            
            const afterAt = beforeCursor.substring(atIndex + 1);
            if (afterAt.includes(' ')) { hideClientDropdown(); return; }
            
            showClientDropdown(afterAt.toLowerCase());
        }
        
        function showClientDropdown(searchTerm) {
            if (!state.clients.length) { hideClientDropdown(); return; }
            
            let filtered = state.clients.filter(c => {
                if (!searchTerm) return true;
                return c.name.toLowerCase().includes(searchTerm) || (c.nic && c.nic.toLowerCase().includes(searchTerm));
            }).slice(0, 10);
            
            if (!filtered.length) { hideClientDropdown(); return; }
            
            elements.clientList.innerHTML = filtered.map((c, i) => {
                const highlightedName = searchTerm
                    ? c.name.replace(new RegExp(`(${escapeRegex(searchTerm)})`, 'gi'), '<span class="mention-text">$1</span>')
                    : c.name;
                return `
                    <div class="client-option ${i === state.selectedClientIndex ? 'selected' : ''}" data-index="${i}" data-name="${escapeHtml(c.name)}">
                        <span class="client-option-name">${highlightedName}</span>
                        ${c.nic ? `<span class="client-option-nic">${escapeHtml(c.nic)}</span>` : ''}
                    </div>
                `;
            }).join('');
            
            elements.clientDropdown.classList.add('show');
            state.selectedClientIndex = -1;
        }
        
        function hideClientDropdown() {
            elements.clientDropdown.classList.remove('show');
            state.selectedClientIndex = -1;
        }
        
        function handleClientClick(e) {
            const option = e.target.closest('.client-option');
            if (option) selectClient(option.dataset.name);
        }
        
        function selectClient(name) {
            const val = elements.inputField.value;
            const cursorPos = elements.inputField.selectionStart || val.length;
            const beforeCursor = val.substring(0, cursorPos);
            const afterCursor = val.substring(cursorPos);
            const atIndex = beforeCursor.lastIndexOf('@');
            if (atIndex === -1) return;
            
            const beforeAt = beforeCursor.substring(0, atIndex);
            const newText = beforeAt + '@' + name + ' ' + afterCursor.trimStart();
            elements.inputField.value = newText;
            
            const newPos = (beforeAt + '@' + name + ' ').length;
            elements.inputField.setSelectionRange(newPos, newPos);
            elements.inputField.focus();
            hideClientDropdown();
            elements.sendButton.disabled = !elements.inputField.value.trim();
        }
        
        function handleKeyDown(e) {
            const dropdownVisible = elements.clientDropdown.classList.contains('show');
            const options = elements.clientList.querySelectorAll('.client-option');
            
            if (dropdownVisible && options.length) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    state.selectedClientIndex = Math.min(state.selectedClientIndex + 1, options.length - 1);
                    updateSelectedOption(options);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    state.selectedClientIndex = Math.max(state.selectedClientIndex - 1, 0);
                    updateSelectedOption(options);
                } else if (e.key === 'Enter' && state.selectedClientIndex >= 0) {
                    e.preventDefault();
                    selectClient(options[state.selectedClientIndex].dataset.name);
                } else if (e.key === 'Escape') {
                    hideClientDropdown();
                }
            } else if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }
        
        function updateSelectedOption(options) {
            options.forEach((opt, i) => opt.classList.toggle('selected', i === state.selectedClientIndex));
            if (state.selectedClientIndex >= 0 && options[state.selectedClientIndex]) {
                options[state.selectedClientIndex].scrollIntoView({ block: 'nearest' });
            }
        }
        
        // ============ MESSAGE HANDLING ============
        async function sendMessage() {
            const query = elements.inputField.value.trim();
            if (!query || state.isLoading) return;
            
            elements.welcomeScreen.style.display = 'none';
            
            const clientMatch = query.match(/@([^\s]+)/);
            const clientName = clientMatch ? clientMatch[1] : null;
            
            addMessage('user', query, clientName);
            
            elements.inputField.value = '';
            elements.inputField.style.height = 'auto';
            elements.sendButton.disabled = true;
            
            state.conversationHistory.push({ role: 'user', content: query });
            
            state.isLoading = true;
            const loadingId = addLoadingMessage();
            
            try {
                const response = await fetch(CONFIG.chatApiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        query: query,
                        client: clientName,
                        history: state.conversationHistory,
                        config: state.configData
                    })
                });
                
                const data = await response.json();
                removeLoadingMessage(loadingId);
                
                if (data.status) {
                    state.conversationHistory.push({ role: 'assistant', content: data.response });
                    addAssistantMessage(data.response, data.data);
                    saveChatSession(query);
                } else {
                    state.conversationHistory.pop();
                    addErrorMessage(data.message || 'Something went wrong. Please try again.');
                }
            } catch (error) {
                removeLoadingMessage(loadingId);
                state.conversationHistory.pop();
                addErrorMessage('Failed to connect to server. Please try again.');
            }
            
            state.isLoading = false;
            scrollToBottom();
        }
        
        // ============ UI RENDERING ============
        function addMessage(role, content, clientName = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}`;
            
            const avatar = role === 'user' ? (state.configData?.user?.name || 'U').charAt(0).toUpperCase() : 'AI';
            const roleName = role === 'user' ? 'You' : 'AI Assistant';
            const formattedContent = formatMarkdown(content);
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-body">
                    <div class="message-header">
                        <span class="message-role">${roleName}</span>
                        ${clientName ? `<span class="client-badge">@${escapeHtml(clientName)}</span>` : ''}
                    </div>
                    <div class="message-content">${formattedContent}</div>
                </div>
            `;
            
            elements.messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }
        
        function addAssistantMessage(content, data = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message assistant';
            
            let messageContent = '', visualizationHtml = '', suggestionsHtml = '';
            
            if (data && typeof data === 'object') {
                messageContent = data.message || content;
                if (data.visualization) visualizationHtml = renderVisualization(data.visualization);
                if (data.suggestions?.length) suggestionsHtml = renderSuggestions(data.suggestions);
            } else {
                messageContent = content;
            }
            
            messageDiv.innerHTML = `
                <div class="message-avatar">AI</div>
                <div class="message-body">
                    <div class="message-header"><span class="message-role">AI Assistant</span></div>
                    <div class="message-content">
                        ${formatMarkdown(messageContent)}
                        ${visualizationHtml}
                        ${suggestionsHtml}
                    </div>
                </div>
            `;
            
            elements.messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }
        
        function renderVisualization(viz) {
            const vizId = 'viz-' + Date.now();
            let content = '';
            
            switch (viz.type) {
                case 'cards': content = renderMetricCards(viz.data); break;
                case 'table': content = renderDataTable(viz.data, viz.columns); break;
                case 'comparison': content = renderComparison(viz.data); break;
                default: return '';
            }
            
            return `
                <div class="visualization-container" id="${vizId}">
                    <div class="viz-header">
                        <span class="viz-title">${escapeHtml(viz.title || 'Results')}</span>
                        <div class="viz-actions">
                            <button class="viz-action-btn" onclick="exportData('${vizId}')">Export</button>
                        </div>
                    </div>
                    <div class="viz-body">${content}</div>
                </div>
            `;
        }
        
        function renderMetricCards(data) {
            if (!Array.isArray(data)) return '';
            return `
                <div class="metric-cards">
                    ${data.map(item => `
                        <div class="metric-card ${(item.metric || '').toLowerCase().replace(/\s+/g, '')}">
                            <div class="metric-label">${escapeHtml(item.label || item.metric || '')}</div>
                            <div class="metric-value">${escapeHtml(formatMetricValue(item.value, item.format))}</div>
                            ${item.change !== undefined ? `
                                <div class="metric-change ${item.change >= 0 ? 'positive' : 'negative'}">
                                    ${item.change >= 0 ? '↑' : '↓'} ${Math.abs(item.change).toFixed(1)}%
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function renderDataTable(data, columns) {
            if (!Array.isArray(data) || !data.length) return '<p>No data available</p>';
            
            if (!columns || !columns.length) {
                columns = Object.keys(data[0]).map(key => ({
                    key: key,
                    label: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                }));
            }
            
            return `
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>${columns.map(col => `<th>${escapeHtml(col.label || col.key)}</th>`).join('')}</tr>
                        </thead>
                        <tbody>
                            ${data.map(row => `
                                <tr>
                                    ${columns.map(col => {
                                        const value = row[col.key];
                                        const formatted = formatMetricValue(value, col.format);
                                        const className = col.highlight && typeof value === 'number' ? (value >= 0 ? 'positive' : 'negative') : '';
                                        return `<td class="${col.isMetric ? 'table-metric' : ''} ${className}">${escapeHtml(formatted)}</td>`;
                                    }).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        function renderComparison(data) {
            if (!data || !data.period1 || !data.period2) return '';
            return `
                <div class="comparison-view">
                    ${['period1', 'period2'].map(period => `
                        <div class="comparison-period">
                            <div class="comparison-period-header">
                                <span class="comparison-period-title">${escapeHtml(data[period].label || period)}</span>
                                <span class="comparison-period-date">${escapeHtml(data[period].dateRange || '')}</span>
                            </div>
                            <div class="comparison-metrics">
                                ${(data[period].metrics || []).map(m => `
                                    <div class="comparison-metric-row">
                                        <span class="comparison-metric-label">${escapeHtml(m.label)}</span>
                                        <span class="comparison-metric-value">${formatMetricValue(m.value, m.format)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function renderSuggestions(suggestions) {
            if (!suggestions?.length) return '';
            return `
                <div class="suggestions-container">
                    <div class="suggestions-header">
                        <svg class="suggestions-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span class="suggestions-title">Recommendations</span>
                    </div>
                    <ul class="suggestions-list">
                        ${suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        function addLoadingMessage() {
            const id = 'loading-' + Date.now();
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message assistant';
            loadingDiv.id = id;
            loadingDiv.innerHTML = `
                <div class="message-avatar">AI</div>
                <div class="message-body">
                    <div class="typing-indicator">
                        <div class="typing-dots">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                        <span class="typing-text">Analyzing your data...</span>
                    </div>
                </div>
            `;
            elements.messagesContainer.appendChild(loadingDiv);
            scrollToBottom();
            return id;
        }
        
        function removeLoadingMessage(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }
        
        function addErrorMessage(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'message assistant';
            errorDiv.innerHTML = `
                <div class="message-avatar" style="background: var(--error);">!</div>
                <div class="message-body">
                    <div class="error-message">
                        <svg class="error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>${escapeHtml(message)}</span>
                    </div>
                </div>
            `;
            elements.messagesContainer.appendChild(errorDiv);
            scrollToBottom();
        }
        
        // ============ UTILITY FUNCTIONS ============
        function formatMarkdown(text) {
            if (typeof marked !== 'undefined') return marked.parse(text);
            let formatted = escapeHtml(text);
            formatted = formatted.replace(/```(\w*)\n?([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
            formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
            formatted = formatted.replace(/\n/g, '<br>');
            return formatted;
        }
        
        function formatMetricValue(value, format) {
            if (value === null || value === undefined) return '-';
            switch (format) {
                case 'currency': return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                case 'percent': return Number(value).toFixed(2) + '%';
                case 'number': return Number(value).toLocaleString('en-US');
                default:
                    if (typeof value === 'number') {
                        if (value >= 1000000) return '$' + (value / 1000000).toFixed(2) + 'M';
                        if (value >= 1000) return '$' + (value / 1000).toFixed(1) + 'K';
                        return value.toLocaleString('en-US');
                    }
                    return String(value);
            }
        }
        
        function scrollToBottom() {
            setTimeout(() => { elements.chatContainer.scrollTop = elements.chatContainer.scrollHeight; }, 50);
        }
        
        function useSuggestion(text) {
            elements.inputField.value = text;
            elements.inputField.focus();
            elements.sendButton.disabled = false;
            handleClientMention();
        }
        
        function startNewChat() {
            state.conversationHistory = [];
            state.currentSessionId = null;
            elements.messagesContainer.innerHTML = '';
            elements.welcomeScreen.style.display = 'flex';
            elements.inputField.value = '';
            elements.inputField.focus();
        }
        
        function saveChatSession(query) {
            const session = {
                id: state.currentSessionId || Date.now().toString(),
                title: query.substring(0, 50) + (query.length > 50 ? '...' : ''),
                date: new Date().toLocaleDateString(),
                history: state.conversationHistory
            };
            
            if (!state.currentSessionId) {
                state.currentSessionId = session.id;
                state.chatSessions.unshift(session);
                if (state.chatSessions.length > CONFIG.maxHistoryLength) state.chatSessions.pop();
            } else {
                const index = state.chatSessions.findIndex(s => s.id === state.currentSessionId);
                if (index >= 0) state.chatSessions[index] = session;
            }
            
            localStorage.setItem('ai_ads_chat_history', JSON.stringify(state.chatSessions));
            renderChatHistory();
        }
        
        function loadChatHistory() {
            try {
                const saved = localStorage.getItem('ai_ads_chat_history');
                if (saved) { state.chatSessions = JSON.parse(saved); renderChatHistory(); }
            } catch (e) { console.error('Failed to load chat history:', e); }
        }
        
        function renderChatHistory() {
            elements.chatHistory.innerHTML = state.chatSessions.map(session => `
                <div class="chat-history-item ${session.id === state.currentSessionId ? 'active' : ''}" onclick="loadChatSession('${session.id}')">
                    <div class="chat-history-title">${escapeHtml(session.title)}</div>
                    <div class="chat-history-date">${session.date}</div>
                </div>
            `).join('');
        }
        
        function loadChatSession(sessionId) {
            const session = state.chatSessions.find(s => s.id === sessionId);
            if (!session) return;
            
            state.currentSessionId = sessionId;
            state.conversationHistory = session.history || [];
            elements.messagesContainer.innerHTML = '';
            elements.welcomeScreen.style.display = 'none';
            
            state.conversationHistory.forEach(msg => {
                if (msg.role === 'user') {
                    const clientMatch = msg.content.match(/@([^\s]+)/);
                    addMessage('user', msg.content, clientMatch ? clientMatch[1] : null);
                } else {
                    addMessage('assistant', msg.content);
                }
            });
            
            renderChatHistory();
            scrollToBottom();
        }
        
        function exportData(vizId) {
            const viz = document.getElementById(vizId);
            if (!viz) return;
            const table = viz.querySelector('.data-table');
            if (table) {
                const rows = Array.from(table.querySelectorAll('tr'));
                const csv = rows.map(row => Array.from(row.querySelectorAll('th, td')).map(cell => `"${cell.textContent.replace(/"/g, '""')}"`).join(',')).join('\n');
                downloadFile(csv, 'data-export.csv', 'text/csv');
            }
        }
        
        function downloadFile(content, filename, type) {
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }
        
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // Initialize
        init();
    </script>
</body>
</html>
