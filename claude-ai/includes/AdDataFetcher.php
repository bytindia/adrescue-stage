<?php
/**
 * Ad Data Fetcher Service
 * 
 * Fetches real ad data from Meta and Google APIs
 * Called by the Claude API to get actual insights
 */

require_once __DIR__ . '/includes/MetaAdsAPI.php';
require_once __DIR__ . '/includes/GoogleAdsAPI.php';

class AdDataFetcher {
    private $metaApi;
    private $googleApi;
    private $configData;
    
    public function __construct($configData) {
        $this->configData = $configData;
        
        if (!empty($configData['user']['facebook']['access_token'])) {
            $this->metaApi = new MetaAdsAPI($configData['user']['facebook']['access_token']);
        }
        
        if (!empty($configData['user']['google']['g_token'])) {
            $this->googleApi = new GoogleAdsAPI(
                $configData['user']['google']['g_token'],
                $configData['user']['google']['g_refresh_token'] ?? null,
                null,
                $configData['user']['google']['g_mcc'] ?? null
            );
        }
    }
    
    public function fetchData($params) {
        $results = ['meta' => null, 'google' => null, 'combined' => []];
        
        $platform = $params['platform'] ?? 'both';
        $clientData = $this->findClient($params['client'] ?? null);
        
        if (!$clientData) {
            return ['error' => 'Client not found'];
        }
        
        if (($platform === 'meta' || $platform === 'both') && $this->metaApi && !empty($clientData['facebook_ids'])) {
            $results['meta'] = $this->fetchMetaData($clientData['facebook_ids'], $params);
        }
        
        if (($platform === 'google' || $platform === 'both') && $this->googleApi && !empty($clientData['google_ids'])) {
            $results['google'] = $this->fetchGoogleData($clientData['google_ids'], $params);
        }
        
        $results['combined'] = $this->combineResults($results['meta'], $results['google']);
        
        return $results;
    }
    
    private function findClient($clientName) {
        if (!$clientName || !isset($this->configData['accounts']['list'])) return null;
        
        foreach ($this->configData['accounts']['list'] as $account) {
            if (strcasecmp($account['client_name'], $clientName) === 0 ||
                strcasecmp($account['nic_name'] ?? '', $clientName) === 0) {
                return $account;
            }
        }
        return null;
    }
    
    private function fetchMetaData($accountIds, $params) {
        $allData = [];
        
        foreach ($accountIds as $accountId) {
            $accountId = trim($accountId);
            if (empty($accountId)) continue;
            
            try {
                $apiParams = [
                    'time_range' => json_encode([
                        'since' => $params['date_range']['start'] ?? date('Y-m-d', strtotime('-7 days')),
                        'until' => $params['date_range']['end'] ?? date('Y-m-d')
                    ])
                ];
                
                if (!empty($params['breakdowns'])) {
                    $apiParams['breakdowns'] = implode(',', $params['breakdowns']);
                }
                
                $level = $params['level'] ?? 'account';
                
                switch ($level) {
                    case 'campaign':
                        $data = $this->metaApi->getCampaignInsights($accountId, $apiParams);
                        break;
                    case 'adset':
                        $data = $this->metaApi->getAdSetInsights($accountId, $apiParams);
                        break;
                    case 'ad':
                        $data = $this->metaApi->getAdInsights($accountId, $apiParams);
                        break;
                    default:
                        $data = $this->metaApi->getAccountInsights($accountId, $apiParams);
                }
                
                if ($data && !isset($data['error'])) {
                    $allData[] = ['account_id' => $accountId, 'data' => $data['data'] ?? []];
                }
            } catch (Exception $e) {
                error_log("Meta API Error: " . $e->getMessage());
            }
        }
        
        return $allData;
    }
    
    private function fetchGoogleData($customerIds, $params) {
        $allData = [];
        $startDate = $params['date_range']['start'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $params['date_range']['end'] ?? date('Y-m-d');
        
        foreach ($customerIds as $customerId) {
            $customerId = trim($customerId);
            if (empty($customerId)) continue;
            
            try {
                $level = $params['level'] ?? 'account';
                
                switch ($level) {
                    case 'campaign':
                        $data = $this->googleApi->getCampaignPerformance($customerId, $startDate, $endDate);
                        break;
                    case 'adgroup':
                        $data = $this->googleApi->getAdGroupPerformance($customerId, $startDate, $endDate);
                        break;
                    case 'ad':
                        $data = $this->googleApi->getAdPerformance($customerId, $startDate, $endDate);
                        break;
                    default:
                        $data = $this->googleApi->getAccountPerformance($customerId, $startDate, $endDate);
                }
                
                if ($data && !isset($data['error'])) {
                    $allData[] = ['customer_id' => $customerId, 'data' => $data['data'] ?? []];
                }
            } catch (Exception $e) {
                error_log("Google API Error: " . $e->getMessage());
            }
        }
        
        return $allData;
    }
    
    private function combineResults($metaData, $googleData) {
        $combined = [
            'summary' => ['spend' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0, 'leads' => 0],
            'by_platform' => ['meta' => [], 'google' => []],
            'details' => []
        ];
        
        // Process Meta data
        if ($metaData) {
            $metaSummary = ['spend' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0, 'leads' => 0];
            
            foreach ($metaData as $account) {
                foreach ($account['data'] as $row) {
                    $metaSummary['spend'] += floatval($row['spend'] ?? 0);
                    $metaSummary['impressions'] += intval($row['impressions'] ?? 0);
                    $metaSummary['clicks'] += intval($row['clicks'] ?? 0);
                    
                    if (isset($row['actions'])) {
                        foreach ($row['actions'] as $action) {
                            if ($action['action_type'] === 'lead') {
                                $metaSummary['leads'] += intval($action['value']);
                            }
                            if (in_array($action['action_type'], ['purchase', 'complete_registration'])) {
                                $metaSummary['conversions'] += intval($action['value']);
                            }
                        }
                    }
                    
                    $combined['details'][] = array_merge($row, ['platform' => 'Meta']);
                }
            }
            
            $combined['by_platform']['meta'] = $metaSummary;
            $combined['summary']['spend'] += $metaSummary['spend'];
            $combined['summary']['impressions'] += $metaSummary['impressions'];
            $combined['summary']['clicks'] += $metaSummary['clicks'];
            $combined['summary']['conversions'] += $metaSummary['conversions'];
            $combined['summary']['leads'] += $metaSummary['leads'];
        }
        
        // Process Google data
        if ($googleData) {
            $googleSummary = ['spend' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0];
            
            foreach ($googleData as $account) {
                foreach ($account['data'] as $row) {
                    $googleSummary['spend'] += floatval($row['spend'] ?? 0);
                    $googleSummary['impressions'] += intval($row['impressions'] ?? 0);
                    $googleSummary['clicks'] += intval($row['clicks'] ?? 0);
                    $googleSummary['conversions'] += floatval($row['conversions'] ?? 0);
                    
                    $combined['details'][] = array_merge($row, ['platform' => 'Google']);
                }
            }
            
            $combined['by_platform']['google'] = $googleSummary;
            $combined['summary']['spend'] += $googleSummary['spend'];
            $combined['summary']['impressions'] += $googleSummary['impressions'];
            $combined['summary']['clicks'] += $googleSummary['clicks'];
            $combined['summary']['conversions'] += $googleSummary['conversions'];
        }
        
        // Calculate derived metrics
        $s = &$combined['summary'];
        $s['ctr'] = $s['impressions'] > 0 ? round(($s['clicks'] / $s['impressions']) * 100, 2) : 0;
        $s['cpc'] = $s['clicks'] > 0 ? round($s['spend'] / $s['clicks'], 2) : 0;
        $s['cpm'] = $s['impressions'] > 0 ? round(($s['spend'] / $s['impressions']) * 1000, 2) : 0;
        $s['cpl'] = $s['leads'] > 0 ? round($s['spend'] / $s['leads'], 2) : 0;
        $s['cpa'] = $s['conversions'] > 0 ? round($s['spend'] / $s['conversions'], 2) : 0;
        
        return $combined;
    }
    
    /**
     * Generate sample data for demo/testing
     */
    public static function generateSampleData($params) {
        $startDate = new DateTime($params['date_range']['start'] ?? '-7 days');
        $endDate = new DateTime($params['date_range']['end'] ?? 'now');
        $days = $startDate->diff($endDate)->days + 1;
        
        $baseSpend = rand(500, 2000);
        $data = [
            'summary' => [
                'spend' => $baseSpend * $days,
                'impressions' => rand(50000, 200000) * $days,
                'clicks' => rand(1000, 5000) * $days,
                'conversions' => rand(50, 200) * $days,
                'leads' => rand(20, 100) * $days
            ],
            'daily' => [],
            'campaigns' => [],
            'ads' => []
        ];
        
        // Calculate derived metrics
        $s = &$data['summary'];
        $s['ctr'] = round(($s['clicks'] / $s['impressions']) * 100, 2);
        $s['cpc'] = round($s['spend'] / $s['clicks'], 2);
        $s['cpm'] = round(($s['spend'] / $s['impressions']) * 1000, 2);
        $s['cpl'] = round($s['spend'] / $s['leads'], 2);
        $s['cpa'] = round($s['spend'] / $s['conversions'], 2);
        
        // Generate daily data
        $current = clone $startDate;
        while ($current <= $endDate) {
            $daySpend = $baseSpend * (0.8 + (rand(0, 40) / 100));
            $dayImpressions = rand(50000, 200000);
            $dayClicks = rand(1000, 5000);
            
            $data['daily'][] = [
                'date' => $current->format('Y-m-d'),
                'spend' => round($daySpend, 2),
                'impressions' => $dayImpressions,
                'clicks' => $dayClicks,
                'ctr' => round(($dayClicks / $dayImpressions) * 100, 2),
                'conversions' => rand(5, 30),
                'leads' => rand(2, 15)
            ];
            
            $current->modify('+1 day');
        }
        
        // Generate campaign data
        $campaignNames = ['Brand Awareness', 'Lead Generation', 'Conversions', 'Traffic', 'Retargeting'];
        foreach ($campaignNames as $i => $name) {
            $campSpend = $s['spend'] * (rand(10, 30) / 100);
            $campImpressions = rand(10000, 50000);
            $campClicks = rand(200, 1500);
            $campLeads = rand(5, 30);
            
            $data['campaigns'][] = [
                'campaign_name' => $name,
                'campaign_id' => '12345678' . $i,
                'status' => $i < 3 ? 'ACTIVE' : 'PAUSED',
                'spend' => round($campSpend, 2),
                'impressions' => $campImpressions,
                'clicks' => $campClicks,
                'ctr' => round(($campClicks / $campImpressions) * 100, 2),
                'cpc' => round($campSpend / $campClicks, 2),
                'leads' => $campLeads,
                'cpl' => round($campSpend / max($campLeads, 1), 2)
            ];
        }
        
        // Generate ad data
        $adNames = [
            'Summer Sale - Image 1', 'Summer Sale - Video', 'Product Showcase A',
            'Product Showcase B', 'Testimonial Ad', 'Limited Offer', 
            'Free Trial CTA', 'Demo Request', 'Case Study', 'Webinar Promo'
        ];
        
        foreach ($adNames as $i => $name) {
            $adSpend = rand(100, 800);
            $adImpressions = rand(5000, 25000);
            $adClicks = rand(100, 800);
            $adLeads = rand(1, 15);
            
            $data['ads'][] = [
                'ad_name' => $name,
                'ad_id' => '98765432' . $i,
                'campaign_name' => $campaignNames[array_rand($campaignNames)],
                'status' => rand(0, 10) > 2 ? 'ACTIVE' : 'PAUSED',
                'spend' => round($adSpend, 2),
                'impressions' => $adImpressions,
                'clicks' => $adClicks,
                'ctr' => round(($adClicks / $adImpressions) * 100, 2),
                'cpc' => round($adSpend / max($adClicks, 1), 2),
                'leads' => $adLeads,
                'cpl' => round($adSpend / max($adLeads, 1), 2),
                'roas' => round(rand(150, 450) / 100, 2)
            ];
        }
        
        // Sort ads by performance
        usort($data['ads'], function($a, $b) {
            return $b['roas'] <=> $a['roas'];
        });
        
        return $data;
    }
}
