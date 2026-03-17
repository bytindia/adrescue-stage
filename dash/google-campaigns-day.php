<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
/**
 * Day-wise Google Ads campaign metrics (spend & conversions per date).
 * Uses segments.date BETWEEN for date range and returns daily aggregates.
 */

require  '/home/digitalb2k/stage.adrescue.in/google-ads-v15/vendor/autoload.php';

use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

class GetCampaignsFromMultipleAccountsDay
{
    /**
     * Fetch day-wise spend and conversions for the given accounts and date range.
     * @param string $g_ref_tok Refresh token
     * @param string $g_mcc MCC ID
     * @param array $adAccId Array of customer IDs
     * @param string $st Start date Y-m-d
     * @param string $en End date Y-m-d
     * @return array [ 'YYYY-MM-DD' => [ 'cost' => float, 'conv' => float ], ... ]
     */
    public static function main($g_ref_tok, $g_mcc, $adAccId, $st, $en)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->withRefreshToken($g_ref_tok)->build();
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile()
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId($g_mcc)
            ->build();

        $merged = [];
        foreach ($adAccId as $accountId) {
            try {
                $perAccount = self::getCampaignsDaily($googleAdsClient, $accountId, $st, $en);
                foreach ($perAccount as $date => $row) {
                    if (!isset($merged[$date])) {
                        $merged[$date] = ['cost' => 0, 'conv' => 0];
                    }
                    $merged[$date]['cost'] += $row['cost'];
                    $merged[$date]['conv'] += $row['conv'];
                }
            } catch (GoogleAdsException $e) {
                // log and continue with other accounts
            } catch (ApiException $e) {
                // log and continue
            }
        }
        return $merged;
    }

    /**
     * Day-wise metrics for one account. Query uses segments.date BETWEEN.
     */
    public static function getCampaignsDaily(GoogleAdsClient $googleAdsClient, $clientCustomerId, $st, $en)
    {
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
        $dateFilter = "segments.date BETWEEN '".$st."' AND '".$en."'";
        $query = "SELECT 
            segments.date,
            metrics.cost_micros,
            metrics.conversions
        FROM campaign 
        WHERE ".$dateFilter." 
        ORDER BY segments.date";

        $request = new SearchGoogleAdsStreamRequest([
            'customer_id' => $clientCustomerId,
            'query' => $query
        ]);
        $stream = $googleAdsServiceClient->searchStream($request);

        $perDay = [];
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $segments = $googleAdsRow->getSegments();
            $date = $segments ? $segments->getDate() : null;
            if (!$date) continue;

            $metrics = $googleAdsRow->getMetrics();
            $cost = $metrics ? round($metrics->getCostMicros() / 1000000, 2) : 0;
            $conv = $metrics ? round($metrics->getConversions(), 2) : 0;

            if (!isset($perDay[$date])) {
                $perDay[$date] = ['cost' => 0, 'conv' => 0];
            }
            $perDay[$date]['cost'] += $cost;
            $perDay[$date]['conv'] += $conv;
        }
        return $perDay;
    }
}
