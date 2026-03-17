<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

require __DIR__ . '/google-ads-v15/vendor/autoload.php';

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;
use Google\ApiCore\ApiException;

class GetCampaigns
{
    private const CUSTOMER_ID = '2470684564';

    public static function main($conn, $g_ref_tok, $g_mcc, $adAccId, $st, $en)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()
            ->withRefreshToken($g_ref_tok)
            ->build();

        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId($g_mcc)
            ->build();

        try {
            return self::fetchConversionData($googleAdsClient, $adAccId, $st, $en);
        } catch (ApiException $apiException) {
            printf("API Exception: %s\n", $apiException->getMessage());
        }
    }

    public static function fetchConversionData(GoogleAdsClient $googleAdsClient, int $customerId, $st, $en)
    {
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        // 🚀 Query 1: Fetch Campaign-Level Conversion Data
        // Initialize
        $campaignData = [];
        $campaignTotals = [];

        // ---- 1️⃣ FETCH CAMPAIGN CONVERSION DATA ---- //
        $campaignQuery = "SELECT 
            campaign.id, 
            campaign.name, 
            segments.conversion_action_name, 
            metrics.conversions, 
            metrics.conversions_value, 
            metrics.all_conversions, 
            metrics.all_conversions_value, 
            segments.date
        FROM campaign
        WHERE 
            segments.date BETWEEN '".$st."' AND '".$en."' 
            AND segments.conversion_action_name IN ('BL-New-property-RIA (web) purchase', 'Add to cart (Google Analytics event add_to_cart)') 
        ORDER BY segments.date DESC";

        $campaignRequest = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $campaignQuery
        ]);

        $campaignStream = $googleAdsServiceClient->searchStream($campaignRequest);

        foreach ($campaignStream->iterateAllElements() as $googleAdsRow) {
            $campaignId = $googleAdsRow->getCampaign()->getId();
            $campaignName = $googleAdsRow->getCampaign()->getName();
            $conversionAction = $googleAdsRow->getSegments()->getConversionActionName();
            $date = $googleAdsRow->getSegments()->getDate();

            $conversions = $googleAdsRow->getMetrics()->hasConversions() 
                ? round($googleAdsRow->getMetrics()->getConversions(), 2) 
                : 0;

            $conversionsValue = $googleAdsRow->getMetrics()->hasConversionsValue() 
                ? round($googleAdsRow->getMetrics()->getConversionsValue(), 2) 
                : 0;

            $allConversions = $googleAdsRow->getMetrics()->hasAllConversions() 
                ? round($googleAdsRow->getMetrics()->getAllConversions(), 2) 
                : 0;

            $allConversionsValue = $googleAdsRow->getMetrics()->hasAllConversionsValue() 
                ? round($googleAdsRow->getMetrics()->getAllConversionsValue(), 2) 
                : 0;

            // Raw data (optional)
            $campaignData[] = [
                'date' => $date,
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'conversion_action' => $conversionAction,
                'conversions' => $conversions,
                'conversion_value' => $conversionsValue,
                'all_conversions' => $allConversions,
                'all_conversions_value' => $allConversionsValue
            ];

            // Aggregating totals by Campaign + Conversion Action
            if (!isset($campaignTotals[$campaignId])) {
                $campaignTotals[$campaignId] = [
                    'campaign_id' => $campaignId,
                    'campaign_name' => $campaignName,
                    'purchase' => [
                        'all_conversions' => 0,
                        'all_conversions_value' => 0
                    ],
                    'add_to_cart' => [
                        'all_conversions' => 0,
                        'all_conversions_value' => 0
                    ]
                ];
            }

            // Check conversion action & update correct category
            if ($conversionAction === 'BL-New-property-RIA (web) purchase') {
                $campaignTotals[$campaignId]['purchase']['all_conversions'] += $allConversions;
                $campaignTotals[$campaignId]['purchase']['all_conversions_value'] += $allConversionsValue;
            } elseif ($conversionAction === 'Add to cart (Google Analytics event add_to_cart)') {
                $campaignTotals[$campaignId]['add_to_cart']['all_conversions'] += $allConversions;
                $campaignTotals[$campaignId]['add_to_cart']['all_conversions_value'] += $allConversionsValue;
            }
        }

        // ---- 2️⃣ FETCH CAMPAIGN METRICS & SPEND DATA ---- //

        $metricsQuery = "SELECT 
            campaign.id, 
            campaign.name, 
            metrics.impressions,
            metrics.clicks,
            metrics.ctr,
            metrics.unique_users,
            metrics.average_cpc,
            metrics.cost_micros,
            metrics.average_cost,
            metrics.average_cpm,
            metrics.cost_per_all_conversions,
            metrics.conversions, 
            metrics.conversions_value,
            metrics.all_conversions_from_order
        FROM campaign 
        WHERE segments.date BETWEEN '".$st."' AND '".$en."' 
        AND metrics.impressions > 0 
        ORDER BY campaign.id";

        $metricsRequest = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $metricsQuery
        ]);

        $metricsStream = $googleAdsServiceClient->searchStream($metricsRequest);

        $campaignSpendData = [];

        foreach ($metricsStream->iterateAllElements() as $googleAdsRow) {
            $campaign = $googleAdsRow->getCampaign();
            $metrics = $googleAdsRow->getMetrics();

            $campaignId = $campaign->getId();
            $campaignName = $campaign->getName();
            $impressions = $metrics->getImpressions();
            $clicks = $metrics->getClicks();
            $ctr = round($metrics->getCtr() * 100, 2);
            $reach = $metrics->getUniqueUsers();
            $avgCpc = round($metrics->getAverageCpc() / 1000000, 2);
            $avgCost = round($metrics->getAverageCost() / 1000000, 2);
            $avgCpm = round($metrics->getAverageCpm() / 1000000, 2);
            $cost = round($metrics->getCostMicros() / 1000000, 2);
            $conversions = round($metrics->getConversions(), 2);
            $conversionValue = round($metrics->getConversionsValue(), 2);
            $allConversionsFromOrder = round($metrics->getAllConversionsFromOrder(), 2);
            $costPerConversion = ($conversions > 0) ? round($cost / $conversions, 2) : 0;

            $campaignSpendData[$campaignId] = [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'impressions' => $impressions,
                'reach' => $reach,
                'clicks' => $clicks,
                'ctr' => $ctr,
                'average_cpc' => $avgCpc,
                'average_cost' => $avgCost,
                'average_cpm' => $avgCpm,
                'cost' => $cost,
                'conversions' => $conversions,
                'conversion_value' => $conversionValue,
                'all_conversions_from_order' => $allConversionsFromOrder,
                'cost_per_conversion' => $costPerConversion
            ];
        }

        // ---- 3️⃣ MERGE CONVERSION + METRICS DATA ---- //

        $finalCampaignReport = [];

        foreach ($campaignSpendData as $campaignId => $spendData) {
            $conversionData = isset($campaignTotals[$campaignId]) ? $campaignTotals[$campaignId] : [
                'purchase' => ['all_conversions' => 0, 'all_conversions_value' => 0],
                'add_to_cart' => ['all_conversions' => 0, 'all_conversions_value' => 0]
            ];

            $finalCampaignReport[] = array_merge($spendData, $conversionData);
        }

        // ---- 4️⃣ FINAL RETURN ---- //
        return  $finalCampaignReport;
        /*return [
            'campaign_conversions_raw' => $campaignData,
            'campaign_conversions_grouped' => array_values($campaignTotals),
            'campaign_spend_data' => array_values($campaignSpendData),
            'campaign_combined_report' => $finalCampaignReport
        ];*/
        
    }
}

// Example Usage
//$getConversions = GetDetailedConversions::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'], '2470684564', '2022-05-01', '2022-05-16');
//print_r($getConversions);
