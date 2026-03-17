<?php
/**
 * Sagehill Analytics Chart Dashboard
 * Fetches data from sagehill-analytics.php and displays it
 */

$apiUrl = 'https://stage.adrescue.in/dash/sagehill-analytics.php';

// Fetch JSON from API (try cURL first, fallback to file_get_contents)
$response = false;
if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $response = @curl_exec($ch);
    curl_close($ch);
}
if ($response === false && ini_get('allow_url_fopen')) {
    $options = ['http' => ['method' => 'GET', 'header' => 'Accept: application/json', 'timeout' => 10]];
    $response = @file_get_contents($apiUrl, false, stream_context_create($options));
}

if ($response === false) {
    $data = [
        'summary' => [
            'totalSessions' => 0,
            'engagementRate' => '0%',
            'avgEngagementTimePerSession' => '00:00'
        ],
        'top_cities' => []
    ];
} else {
    $raw = json_decode($response, true) ?: [];

    // API returns "traffic" + "topCities" format - transform to "summary" + "top_cities"
    if (isset($raw['traffic']) || isset($raw['topCities'])) {
        $traffic = $raw['traffic'] ?? [];
        $data = [
            'summary' => [
                'totalSessions' => $traffic['totalSessions'] ?? 0,
                'engagementRate' => isset($traffic['engagementRate']) ? (round($traffic['engagementRate'], 2) . '%') : '0%',
                'avgEngagementTimePerSession' => $traffic['avgEngagementTimePerSession_fmt'] ?? '00:00'
            ],
            'top_cities' => []
        ];
        $formatTime = function($sec) {
            $sec = (int) round($sec ?? 0);
            return sprintf('%02d:%02d', floor($sec / 60), $sec % 60);
        };
        foreach ($raw['topCities'] ?? [] as $cityName => $cityData) {
            $data['top_cities'][] = [
                'city' => trim($cityName) !== '' ? $cityName : 'Other',
                'totalSessions' => $cityData['activeUsers'] ?? 0,
                'engagementRate' => isset($cityData['engagementRate']) ? (round($cityData['engagementRate'], 2) . '%') : '0%',
                'avgEngagementTimePerSession' => $formatTime($cityData['avgEngagementPerActiveUser_sec'] ?? 0),
                'LP_Lead' => $cityData['LP_Lead'] ?? 0
            ];
        }
    } else {
        // API returns "summary" + "top_cities" format - use as-is
        $data = $raw;
        $data['summary'] = $raw['summary'] ?? ['totalSessions' => 0, 'engagementRate' => '0%', 'avgEngagementTimePerSession' => '00:00'];
        $data['top_cities'] = $raw['top_cities'] ?? [];
        foreach ($data['top_cities'] as &$item) {
            if (trim($item['city'] ?? '') === '') {
                $item['city'] = 'Other';
            }
        }
        unset($item);
    }
}

$dataJson = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --bg-color: #f0f4f8;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            --accent-blue: #5c7cfa;
            --accent-green: #51cf66;
            --accent-purple: #845ef7;
        }

        body { 
            background-color: var(--bg-color); 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding-top: 40px;
        }

        /* --- Summary Metric Cards --- */
        .metric-card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            position: relative;
            height: 100%;
            transition: transform 0.2s ease;
        }

        .metric-card:hover { transform: translateY(-3px); }

        /* Colored top borders to match your reference image */
        .border-blue { border-top: 5px solid var(--accent-blue); }
        .border-green { border-top: 5px solid var(--accent-green); }
        .border-purple { border-top: 5px solid var(--accent-purple); }

        .icon-box {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .bg-light-blue { background: #e7f5ff; color: var(--accent-blue); }
        .bg-light-green { background: #ebfbee; color: var(--accent-green); }
        .bg-light-purple { background: #f3f0ff; color: var(--accent-purple); }

        .m-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #868e96;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .m-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #212529;
            margin: 4px 0;
        }

        .m-subtext {
            font-size: 0.8rem;
            color: #adb5bd;
        }

        /* --- Top Cities Performance Cards --- */
        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .city-card {
            background: #ffffff;
            border: none;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }

        .city-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .city-card-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        #city-list-container > .city-col:nth-child(1) .city-card-header { background: linear-gradient(135deg, #5c7cfa 0%, #4c6ef5 100%); }
        #city-list-container > .city-col:nth-child(2) .city-card-header { background: linear-gradient(135deg, #51cf66 0%, #40c057 100%); }
        #city-list-container > .city-col:nth-child(3) .city-card-header { background: linear-gradient(135deg, #845ef7 0%, #7950f2 100%); }
        #city-list-container > .city-col:nth-child(4) .city-card-header { background: linear-gradient(135deg, #fa5252 0%, #f03e3e 100%); }
        #city-list-container > .city-col:nth-child(5) .city-card-header { background: linear-gradient(135deg, #ff922b 0%, #fd7e14 100%); }

        .city-card-title {
            font-weight: 800;
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .city-card-body {
            padding: 1rem 1.25rem;
        }

        .city-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            border-bottom: 1px solid #f1f3f5;
            font-size: 0.85rem;
        }

        .city-stat-row:last-child { border-bottom: none; }

        .city-stat-label { color: #868e96; font-weight: 600; }
        .city-stat-value { color: #212529; font-weight: 800; }

        .city-lead-badge {
            display: inline-block;
            background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
            color: #0d9488;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

<div class="container mb-5">
    
    <div class="row g-4" id="summary-container">
    </div>

    <div class="row">
        <div class="row g-4" id="city-list-container">
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function() {
    const data = <?= $dataJson ?>;

    // Render Summary
    const summaryHtml = `
        <div class="col-md-4">
            <div class="metric-card border-blue">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="m-label">Total Sessions</div>
                        <div class="m-value">${(data.summary.totalSessions || 0).toLocaleString()}</div>
                        <div class="m-subtext">Traffic Volume</div>
                    </div>
                    <div class="icon-box bg-light-blue"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card border-green">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="m-label">Engagement Rate</div>
                        <div class="m-value">${data.summary.engagementRate || '0%'}</div>
                        <div class="m-subtext">Interaction Quality</div>
                    </div>
                    <div class="icon-box bg-light-green"><i class="bi bi-lightning-charge-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card border-purple">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="m-label">Avg. Duration</div>
                        <div class="m-value">${data.summary.avgEngagementTimePerSession || '00:00'}</div>
                        <div class="m-subtext">Time per Session</div>
                    </div>
                    <div class="icon-box bg-light-purple"><i class="bi bi-stopwatch-fill"></i></div>
                </div>
            </div>
        </div>
    `;
    $('#summary-container').html(summaryHtml);

    // Render City Cards
    let cityHtml = '';
    (data.top_cities || []).forEach(function(item) {
        cityHtml += `
            <div class="col-sm-6 col-lg city-col">
                <div class="card city-card">
                    <div class="city-card-header">
                        <h6 class="city-card-title">
                            <i class="bi bi-geo-alt-fill"></i> ${item.city || 'Not Specified'}
                        </h6>
                    </div>
                    <div class="city-card-body">
                        <div class="city-stat-row">
                            <span class="city-stat-label">Sessions</span>
                            <span class="city-stat-value">${item.totalSessions}</span>
                        </div>
                        <div class="city-stat-row">
                            <span class="city-stat-label">Eng. Rate</span>
                            <span class="city-stat-value">${item.engagementRate}</span>
                        </div>
                        <div class="city-stat-row">
                            <span class="city-stat-label">Avg. Time</span>
                            <span class="city-stat-value">${item.avgEngagementTimePerSession}</span>
                        </div>
                        <span class="city-lead-badge">${item.LP_Lead} Leads</span>
                    </div>
                </div>
            </div>
        `;
    });
    $('#city-list-container').html(cityHtml);
});
</script>

</body>
</html>
