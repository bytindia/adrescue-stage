<?php
/**
 * Meta (Facebook) Ads API Helper
 * 
 * Handles all Meta Ads API calls for insights, campaigns, ads, etc.
 */

class MetaAdsAPI {
    private $accessToken;
    private $apiVersion = 'v18.0';
    private $baseUrl = 'https://graph.facebook.com/';
    
    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }
    
    /**
     * Get account insights
     */
    public function getAccountInsights($accountId, $params = []) {
        $defaults = [
            'fields' => 'spend,impressions,clicks,ctr,cpc,cpm,reach,frequency,actions,cost_per_action_type',
            'date_preset' => 'last_7d',
            'level' => 'account'
        ];
        
        $params = array_merge($defaults, $params);
        
        return $this->request("act_{$accountId}/insights", $params);
    }
    
    /**
     * Get campaign insights
     */
    public function getCampaignInsights($accountId, $params = []) {
        $defaults = [
            'fields' => 'campaign_name,campaign_id,spend,impressions,clicks,ctr,cpc,actions,cost_per_action_type,objective',
            'date_preset' => 'last_7d',
            'level' => 'campaign'
        ];
        
        $params = array_merge($defaults, $params);
        
        return $this->request("act_{$accountId}/insights", $params);
    }
    
    /**
     * Get ad set insights
     */
    public function getAdSetInsights($accountId, $params = []) {
        $defaults = [
            'fields' => 'adset_name,adset_id,campaign_name,spend,impressions,clicks,ctr,cpc,actions,cost_per_action_type',
            'date_preset' => 'last_7d',
            'level' => 'adset'
        ];
        
        $params = array_merge($defaults, $params);
        
        return $this->request("act_{$accountId}/insights", $params);
    }
    
    /**
     * Get ad insights
     */
    public function getAdInsights($accountId, $params = []) {
        $defaults = [
            'fields' => 'ad_name,ad_id,adset_name,campaign_name,spend,impressions,clicks,ctr,cpc,actions,cost_per_action_type',
            'date_preset' => 'last_7d',
            'level' => 'ad'
        ];
        
        $params = array_merge($defaults, $params);
        
        return $this->request("act_{$accountId}/insights", $params);
    }
    
    /**
     * Get insights with custom date range
     */
    public function getInsightsWithDateRange($accountId, $startDate, $endDate, $level = 'account', $params = []) {
        $params['time_range'] = json_encode([
            'since' => $startDate,
            'until' => $endDate
        ]);
        $params['level'] = $level;
        
        unset($params['date_preset']);
        
        return $this->getAccountInsights($accountId, $params);
    }
    
    /**
     * Get insights with breakdown (age, gender, placement, etc.)
     */
    public function getInsightsWithBreakdown($accountId, $breakdown, $params = []) {
        $params['breakdowns'] = $breakdown;
        
        return $this->getAccountInsights($accountId, $params);
    }
    
    /**
     * Get leads from Lead Ads
     */
    public function getLeads($formId, $params = []) {
        $defaults = [
            'fields' => 'created_time,field_data'
        ];
        
        $params = array_merge($defaults, $params);
        
        return $this->request("{$formId}/leads", $params);
    }
    
    /**
     * Get page insights
     */
    public function getPageInsights($pageId, $pageToken, $params = []) {
        $defaults = [
            'metric' => 'page_impressions,page_engaged_users,page_post_engagements',
            'period' => 'day'
        ];
        
        $params = array_merge($defaults, $params);
        $params['access_token'] = $pageToken;
        
        return $this->request("{$pageId}/insights", $params);
    }
    
    /**
     * Make API request
     */
    private function request($endpoint, $params = []) {
        $params['access_token'] = $this->accessToken;
        
        $url = $this->baseUrl . $this->apiVersion . '/' . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => $error];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            return ['error' => $data['error']['message'] ?? 'Unknown error'];
        }
        
        return $data;
    }
    
    /**
     * Parse date range from natural language
     */
    public static function parseDateRange($text) {
        $today = new DateTime();
        $start = clone $today;
        $end = clone $today;
        
        $text = strtolower(trim($text));
        
        // Handle specific patterns
        if ($text === 'today') {
            // Already set to today
        } elseif ($text === 'yesterday') {
            $start->modify('-1 day');
            $end->modify('-1 day');
        } elseif (preg_match('/last\s+(\d+)\s+days?/', $text, $m)) {
            $start->modify("-{$m[1]} days");
        } elseif (preg_match('/last\s+(\d+)\s+weeks?/', $text, $m)) {
            $weeks = $m[1] * 7;
            $start->modify("-{$weeks} days");
        } elseif (preg_match('/last\s+(\d+)\s+months?/', $text, $m)) {
            $start->modify("-{$m[1]} months");
        } elseif ($text === 'this week') {
            $start->modify('monday this week');
        } elseif ($text === 'last week') {
            $start->modify('monday last week');
            $end->modify('sunday last week');
        } elseif ($text === 'this month') {
            $start->modify('first day of this month');
        } elseif ($text === 'last month') {
            $start->modify('first day of last month');
            $end->modify('last day of last month');
        } elseif (preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{4})$/i', $text, $m)) {
            $start = new DateTime("first day of {$m[1]} {$m[2]}");
            $end = new DateTime("last day of {$m[1]} {$m[2]}");
        }
        
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'label' => ucfirst($text)
        ];
    }
    
    /**
     * Calculate metrics from raw data
     */
    public static function calculateMetrics($data) {
        $metrics = [
            'spend' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'reach' => 0,
            'leads' => 0,
            'conversions' => 0
        ];
        
        foreach ($data as $row) {
            $metrics['spend'] += floatval($row['spend'] ?? 0);
            $metrics['impressions'] += intval($row['impressions'] ?? 0);
            $metrics['clicks'] += intval($row['clicks'] ?? 0);
            $metrics['reach'] += intval($row['reach'] ?? 0);
            
            // Parse actions for leads/conversions
            if (isset($row['actions'])) {
                foreach ($row['actions'] as $action) {
                    if ($action['action_type'] === 'lead') {
                        $metrics['leads'] += intval($action['value']);
                    }
                    if (in_array($action['action_type'], ['purchase', 'complete_registration', 'submit_application'])) {
                        $metrics['conversions'] += intval($action['value']);
                    }
                }
            }
        }
        
        // Calculate derived metrics
        $metrics['ctr'] = $metrics['impressions'] > 0 
            ? round(($metrics['clicks'] / $metrics['impressions']) * 100, 2) 
            : 0;
        $metrics['cpc'] = $metrics['clicks'] > 0 
            ? round($metrics['spend'] / $metrics['clicks'], 2) 
            : 0;
        $metrics['cpm'] = $metrics['impressions'] > 0 
            ? round(($metrics['spend'] / $metrics['impressions']) * 1000, 2) 
            : 0;
        $metrics['cpl'] = $metrics['leads'] > 0 
            ? round($metrics['spend'] / $metrics['leads'], 2) 
            : 0;
        
        return $metrics;
    }
}
?>
