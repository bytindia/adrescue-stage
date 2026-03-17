<?php 


//session_start();
require __DIR__ . '/google-ads-v15/vendor/autoload.php';


use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;

class GoogleAdsDataFetcher
{
    public static function fetchAllData($g_mcc, $g_ref_tok, $allGids)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile()
            ->withRefreshToken($g_ref_tok)
            ->build();

        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile()
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId((int)str_replace('-', '', $g_mcc))
            ->build();

        $results = [];

        foreach ($allGids as $acc) {
            $customerId = $acc;

            try {
                $data = self::getAdGroupPerformanceAndChangeHistory($googleAdsClient, $customerId);

                

                $results[] = [
                    'adset_data' => $data['ad_groups'],
                    'last3_spend' => $data['last3_spend'],
                    'last3_leads' => $data['last3_leads'],
                    'last3_cpl' => $data['last3_cpl'],
                    'placement' => 'N',
                    'last_updated' => $data['latest_change']
                        ? date('Y-m-d H:i:s', strtotime($data['latest_change']))
                        : null
                ];
            } catch (GoogleAdsException | ApiException $e) {
                echo "Error for account {$customerId}: " . $e->getMessage() . "<br>";
                continue;
            }
        }

        return $results;
    }

    private static function getAdGroupPerformanceAndChangeHistory($googleAdsClient, $customerId)
    {
        $googleAdsService = $googleAdsClient->getGoogleAdsServiceClient();

        // Step 1: AdGroup-level performance for last 3 days (excluding today)
        $start = date('Y-m-d', strtotime('-1 days'));
        $end = date('Y-m-d', strtotime('0 days'));

        $query1 = "
            SELECT
                ad_group.id,
                ad_group.name,
                metrics.cost_micros,
                metrics.conversions
            FROM ad_group
            WHERE
                ad_group.status = 'ENABLED'
                AND campaign.status = 'ENABLED'
                AND segments.date BETWEEN '$start' AND '$end'
                AND metrics.impressions > 0
        ";

        $request1 = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $query1,
        ]);

        $stream1 = $googleAdsService->searchStream($request1);

        $adGroups = [];

        foreach ($stream1->iterateAllElements() as $row) {
            $adGroupId = $row->getAdGroup()->getId();
            $adGroupName = $row->getAdGroup()->getName();
            $costMicros = $row->getMetrics()->getCostMicros();
            $conversions = $row->getMetrics()->getConversions();

            $spend = round($costMicros / 1000000, 2);
            $cpl = $conversions > 0 ? round($spend / $conversions, 2) : 0;

            $adGroups[] = [
                'adset_id' => $adGroupId,
                'adset_name' => $adGroupName,
                'spend' => $spend,
                'leads' => $conversions,
                'cpl' => $cpl
            ];
        }

        // Step 2: Most recent change status in last 30 days
        $changeStart = date('Y-m-d', strtotime('-30 days'));
        $changeEnd = date('Y-m-d');

        $query2 = "
            SELECT change_status.last_change_date_time
            FROM change_status
            WHERE
                change_status.last_change_date_time BETWEEN '$changeStart' AND '$changeEnd'
                AND change_status.resource_type IN (CAMPAIGN, AD_GROUP, AD_GROUP_AD)
            ORDER BY change_status.last_change_date_time DESC
            LIMIT 1
        ";

        $request2 = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $query2,
        ]);

        $recentChange = null;
        $stream2 = $googleAdsService->searchStream($request2);
        foreach ($stream2->iterateAllElements() as $row) {
            echo $recentChange = $row->getChangeStatus()->getLastChangeDateTime();
            break;
        }

        // === Query 3: Account-Level Aggregation
        $start = date('Y-m-d', strtotime('-4 days'));
        $end = date('Y-m-d', strtotime('-2 days'));
        $query3 = "
            SELECT
                customer.id,
                metrics.cost_micros,
                metrics.conversions
            FROM customer
            WHERE segments.date BETWEEN '$start' AND '$end'
        ";

        $request3 = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $query3,
        ]);

        $accountSpend = 0;
        $accountLeads = 0;

        $stream3 = $googleAdsService->searchStream($request3);
        foreach ($stream3->iterateAllElements() as $row) {
            $accountSpend += $row->getMetrics()->getCostMicros();
            $accountLeads += $row->getMetrics()->getConversions();
        }

        $accountSpend = round($accountSpend / 1000000, 2);
        $accountCPL = $accountLeads > 0 ? round($accountSpend / $accountLeads, 2) : 0;

        return [
            'ad_groups' => $adGroups,
            'last3_spend' => $accountSpend,
            'last3_leads' => $accountLeads,
            'last3_cpl' => $accountCPL,
            'latest_change' => $recentChange
        ];
    }
}


