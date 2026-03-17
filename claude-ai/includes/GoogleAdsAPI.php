<?php
/**
 * Google Ads API Helper
 * 
 * Handles all Google Ads API calls for insights, campaigns, ads, etc.
 * Uses Google Ads API v15
 */

class GoogleAdsAPI {
    private $accessToken;
    private $refreshToken;
    private $developerToken;
    private $mccId;
    private $apiVersion = 'v15';
    private $baseUrl = 'https://googleads.googleapis.com/';
    
    public function __construct($accessToken, $refreshToken = null, $developerToken = null, $mccId = null) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->developerToken = $developerToken;
        $this->mccId = $mccId;
    }
    
    /**
     * Get account performance
     */
    public function getAccountPerformance($customerId, $startDate, $endDate) {
        $query = "
            SELECT
                metrics.cost_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.conversions_value,
                metrics.ctr,
                metrics.average_cpc,
                segments.date
            FROM customer
            WHERE segments.date BETWEEN '$startDate' AND '$endDate'
        ";
        
        return $this->search($customerId, $query);
    }
    
    /**
     * Get campaign performance
     */
    public function getCampaignPerformance($customerId, $startDate, $endDate) {
        $query = "
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.advertising_channel_type,
                metrics.cost_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.conversions_value,
                metrics.ctr,
                metrics.average_cpc
            FROM campaign
            WHERE segments.date BETWEEN '$startDate' AND '$endDate'
                AND campaign.status != 'REMOVED'
            ORDER BY metrics.cost_micros DESC
        ";
        
        return $this->search($customerId, $query);
    }
    
    /**
     * Get ad group performance
     */
    public function getAdGroupPerformance($customerId, $startDate, $endDate) {
        $query = "
            SELECT
                ad_group.id,
                ad_group.name,
                ad_group.status,
                campaign.name,
                metrics.cost_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.ctr,
                metrics.average_cpc
            FROM ad_group
            WHERE segments.date BETWEEN '$startDate' AND '$endDate'
                AND ad_group.status != 'REMOVED'
            ORDER BY metrics.cost_micros DESC
        ";
        
        return $this->search($customerId, $query);
    }
    
    /**
     * Get ad performance
     */
    public function getAdPerformance($customerId, $startDate, $endDate) {
        $query = "
            SELECT
                ad_group_ad.ad.id,
                ad_group_ad.ad.name,
                ad_group_ad.ad.type,
                ad_group.name,
                campaign.name,
                metrics.cost_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.ctr,
                metrics.average_cpc
            FROM ad_group_ad
            WHERE segments.date BETWEEN '$startDate' AND '$endDate'
                AND ad_group_ad.status != 'REMOVED'
            ORDER BY metrics.cost_micros DESC
        ";
        
        return $this->search($customerId, $query);
    }
    
    /**
     * Get keyword performance
     */
    public function getKeywordPerformance($customerId, $startDate, $endDate) {
        $query = "
            SELECT
                ad_group_criterion.keyword.text,
                ad_group_criterion.keyword.match_type,
                ad_group.name,
                campaign.name,
                metrics.cost_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.ctr,
                metrics.average_cpc,
                metrics.search_impression_share
            FROM keyword_view
            WHERE segments.date BETWEEN '$startDate' AND '$endDate'
            ORDER BY metrics.cost_micros DESC
            LIMIT 100
        ";
        
        return $this->search($customerId, $query);
    }
    
    /**
     * Get daily performance trend
     */
    public function getDailyTrend($customerId, $startDate, $endDate) {
        $query = "
            SELECT
                segments.date,
                metrics.cost_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.ctr,
                metrics.average_cpc
            FROM customer
            WHERE segments.date BETWEEN '$startDate' AND '$endDate'
            ORDER BY segments.date
        ";
        
        return $this->search($customerId, $query);
    }
    
    /**
     * Search using GAQL
     */
    private function search($customerId, $query) {
        $customerId = str_replace('-', '', $customerId);
        
        $url = $this->baseUrl . $this->apiVersion . "/customers/{$customerId}/googleAds:search";
        
        $data = ['query' => trim($query)];
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];
        
        if ($this->developerToken) {
            $headers[] = 'developer-token: ' . $this->developerToken;
        }
        
        if ($this->mccId) {
            $headers[] = 'login-customer-id: ' . str_replace('-', '', $this->mccId);
        }
        
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
            return ['error' => $error];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            return ['error' => $data['error']['message'] ?? 'Unknown error'];
        }
        
        return $this->formatResults($data);
    }
    
    /**
     * Format Google Ads results
     */
    private function formatResults($data) {
        if (!isset($data['results'])) {
            return ['data' => []];
        }
        
        $formatted = [];
        
        foreach ($data['results'] as $row) {
            $item = [];
            
            // Extract metrics
            if (isset($row['metrics'])) {
                $metrics = $row['metrics'];
                $item['spend'] = isset($metrics['costMicros']) ? $metrics['costMicros'] / 1000000 : 0;
                $item['impressions'] = $metrics['impressions'] ?? 0;
                $item['clicks'] = $metrics['clicks'] ?? 0;
                $item['conversions'] = $metrics['conversions'] ?? 0;
                $item['conversions_value'] = $metrics['conversionsValue'] ?? 0;
                $item['ctr'] = isset($metrics['ctr']) ? round($metrics['ctr'] * 100, 2) : 0;
                $item['cpc'] = isset($metrics['averageCpc']) ? $metrics['averageCpc'] / 1000000 : 0;
            }
            
            // Extract campaign info
            if (isset($row['campaign'])) {
                $item['campaign_id'] = $row['campaign']['id'] ?? null;
                $item['campaign_name'] = $row['campaign']['name'] ?? null;
                $item['campaign_status'] = $row['campaign']['status'] ?? null;
                $item['campaign_type'] = $row['campaign']['advertisingChannelType'] ?? null;
            }
            
            // Extract ad group info
            if (isset($row['adGroup'])) {
                $item['adgroup_id'] = $row['adGroup']['id'] ?? null;
                $item['adgroup_name'] = $row['adGroup']['name'] ?? null;
            }
            
            // Extract segments
            if (isset($row['segments'])) {
                $item['date'] = $row['segments']['date'] ?? null;
            }
            
            $formatted[] = $item;
        }
        
        return ['data' => $formatted];
    }
    
    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken($clientId, $clientSecret) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            return $result['access_token'];
        }
        
        return null;
    }
    
    /**
     * Calculate derived metrics
     */
    public static function calculateMetrics($data) {
        $metrics = [
            'spend' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'conversions_value' => 0
        ];
        
        foreach ($data as $row) {
            $metrics['spend'] += floatval($row['spend'] ?? 0);
            $metrics['impressions'] += intval($row['impressions'] ?? 0);
            $metrics['clicks'] += intval($row['clicks'] ?? 0);
            $metrics['conversions'] += floatval($row['conversions'] ?? 0);
            $metrics['conversions_value'] += floatval($row['conversions_value'] ?? 0);
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
        $metrics['roas'] = $metrics['spend'] > 0 
            ? round($metrics['conversions_value'] / $metrics['spend'], 2) 
            : 0;
        $metrics['cpa'] = $metrics['conversions'] > 0 
            ? round($metrics['spend'] / $metrics['conversions'], 2) 
            : 0;
        
        return $metrics;
    }
}
?>
