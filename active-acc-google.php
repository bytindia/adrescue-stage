<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

/**
 * Combined Google Ads API class for active account monitoring
 * Combines functionality from google-ads.php and dash/google-campaigns.php
 */

require __DIR__ . '/google-ads-v15/vendor/autoload.php';

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

class ActiveAccGoogle
{
    /**
     * Get aggregated spend data for an account (from google-ads.php)
     */
    public static function getSpendData($conn, $g_refresh_token, $g_mcc, $gId, $st, $en)
    {
        try {
            $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_refresh_token)->build();
            $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
                ->withOAuth2Credential($oAuth2Credential)
                ->withLoginCustomerId($g_mcc)
                ->build();

            return self::getSpendMetrics($googleAdsClient, $gId, $st, $en);
        } catch (GoogleAdsException $googleAdsException) {
            error_log("Google Ads API error: " . $googleAdsException->getMessage());
            return array('cost' => 0, 'conversions' => 0, 'clicks' => 0, 'ctr' => 0, 'impressions' => 0);
        } catch (ApiException $apiException) {
            error_log("API Exception: " . $apiException->getMessage());
            return array('cost' => 0, 'conversions' => 0, 'clicks' => 0, 'ctr' => 0, 'impressions' => 0);
        } catch (Throwable $e) {
            error_log("General error in getSpendData: " . $e->getMessage());
            return array('cost' => 0, 'conversions' => 0, 'clicks' => 0, 'ctr' => 0, 'impressions' => 0);
        }
    }

    /**
     * Get spend metrics (internal method)
     */
    private static function getSpendMetrics(GoogleAdsClient $googleAdsClient, int $customerId, $st, $en)
    {
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
        
        $query = "SELECT 
            campaign.id, 
            campaign.name, 
            metrics.impressions,
            metrics.clicks,
            metrics.ctr,
            metrics.cost_micros,
            metrics.average_cpc,
            metrics.conversions,
            segments.date
            FROM campaign WHERE segments.date >= '".$st."' AND segments.date <= '".$en."' ORDER BY campaign.id";

        $request = new SearchGoogleAdsStreamRequest(['customer_id' => $customerId,'query' => $query]);
        $stream = $googleAdsServiceClient->searchStream($request);

        $cost = $conv = $clicks = $ctr = $impressions = array();
        
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $metrics = $googleAdsRow->getMetrics();
            $cost[] = round($metrics->getCostMicros() /1000000);
            $conv[] = round($metrics->getConversions());
            $clicks[] = round($metrics->getClicks());
            $ctr[] = round($metrics->getCtr());
            $impressions[] = round($metrics->getImpressions());
        }
        
        return array(
            'cost' => array_sum($cost), 
            'conversions' => array_sum($conv), 
            'clicks' => array_sum($clicks), 
            'ctr' => array_sum($ctr), 
            'impressions' => array_sum($impressions)
        );
    }

    /**
     * Get active campaigns with status (from dash/google-campaigns.php)
     */
    public static function getActiveCampaigns($g_refresh_token, $g_mcc, $gId, $st, $en)
    {
        try {
            $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_refresh_token)->build();
            $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
                ->withOAuth2Credential($oAuth2Credential)
                ->withLoginCustomerId($g_mcc)
                ->build();

            return self::getCampaignsWithStatus($googleAdsClient, $gId, $st, $en);
        } catch (GoogleAdsException $googleAdsException) {
            error_log("Google Ads API error in getActiveCampaigns: " . $googleAdsException->getMessage());
            return array();
        } catch (ApiException $apiException) {
            error_log("API Exception in getActiveCampaigns: " . $apiException->getMessage());
            return array();
        } catch (Throwable $e) {
            error_log("General error in getActiveCampaigns: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Get campaigns with status (internal method)
     */
    private static function getCampaignsWithStatus(GoogleAdsClient $googleAdsClient, $clientCustomerId, $st, $en)
    {
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        $query = "SELECT 
            campaign.id, 
            campaign.name, 
            campaign.status, 
            metrics.impressions,
            metrics.clicks,
            metrics.ctr,
            metrics.average_cpc,
            metrics.cost_micros,
            metrics.average_cost,
            metrics.average_cpm,
            metrics.cost_per_all_conversions,
            metrics.conversions, 
            metrics.conversions_value,
            metrics.all_conversions_from_order,
            campaign_budget.amount_micros
            FROM campaign WHERE segments.date >= '".$st."' AND segments.date <= '".$en."' ORDER BY campaign.id";

        $request = new SearchGoogleAdsStreamRequest(['customer_id' => $clientCustomerId,'query' => $query]);
        $stream = $googleAdsServiceClient->searchStream($request);

        $campaigns = array();
        $seenCampaigns = array(); // To avoid duplicate campaigns

        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $campaign = $googleAdsRow->getCampaign();
            $campaign_bud = $googleAdsRow->getCampaignBudget();
            $metrics = $googleAdsRow->getMetrics();

            $campaignId = $campaign->getId();
            
            // Avoid duplicate campaigns
            if (!isset($seenCampaigns[$campaignId])) {
                $seenCampaigns[$campaignId] = true;
                
                // Map campaign status from numeric to string
                $campaignStatus = $campaign->getStatus();
                $camp_status_map = array(
                    0 => 'UNSPECIFIED',
                    1 => 'UNKNOWN', 
                    2 => 'ENABLED',
                    3 => 'PAUSED',
                    4 => 'REMOVED'
                );
                
                $campaigns[] = array(
                    'id' => $campaignId,
                    'name' => $campaign->getName(),
                    'status' => isset($camp_status_map[$campaignStatus]) ? $camp_status_map[$campaignStatus] : 'UNKNOWN'
                );
            }
        }

        return $campaigns;
    }

    /**
     * Get active campaigns count for zero-spend monitoring
     */
    public static function getActiveCampaignsCount($gId, $g_refresh_token, $g_mcc)
    {
        try {
            $today = date('Y-m-d');
            $campaigns = self::getActiveCampaigns($g_refresh_token, $g_mcc, $gId, $today, $today);
            
            $activeCampaigns = 0;
            foreach ($campaigns as $campaign) {
                if (isset($campaign['status']) && $campaign['status'] === 'ENABLED') {
                    $activeCampaigns++;
                }
            }
            
            return $activeCampaigns;
        } catch (Throwable $e) {
            error_log("Error in getActiveCampaignsCount: " . $e->getMessage());
            return 0;
        }
    }
}
