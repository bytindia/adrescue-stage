<?php
date_default_timezone_set('Asia/Kolkata');

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';

// Fetch user access token
$query = "SELECT tbl_id, name, fb_id, g_id, access_token FROM users WHERE tbl_id = 2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token'];

/**
 * Fetch Ad Preview from Meta API
 */
function getAdPreview($ad_id, $access_token, $ad_format = 'MOBILE_FEED_STANDARD') {

    // STEP 1: Get Creative ID
    $creative_url = "https://graph.facebook.com/v21.0/{$ad_id}?fields=creative&access_token={$access_token}";
    $creative_json = file_get_contents($creative_url);
    $creative_data = json_decode($creative_json, true);

    if (!isset($creative_data['creative']['id'])) {
        return [
            'success' => false,
            'error' => "No creative found for Ad ID: {$ad_id}",
            'response' => $creative_data
        ];
    }

    $creative_id = $creative_data['creative']['id'];

    // STEP 2: Fetch Preview HTML
    $preview_url = "https://graph.facebook.com/v21.0/{$creative_id}/previews?ad_format={$ad_format}&access_token={$access_token}";
    $preview_json = file_get_contents($preview_url);
    $preview_data = json_decode($preview_json, true);

    if (!isset($preview_data['data'][0]['body'])) {
        return [
            'success' => false,
            'error' => "Unable to fetch preview",
            'response' => $preview_data
        ];
    }

    return [
        'success' => true,
        'creative_id' => $creative_id,
        'ad_id' => $ad_id,
        'preview_html' => $preview_data['data'][0]['body']
    ];
}

// Read ad ID from URL
$ad_id = $_GET['ad_id'] ?? '';
$result = getAdPreview($ad_id, $access_token);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ad Preview</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">

<style>
    body {
        background: #f3f3f3;
    }

    .preview-wrapper {
        width: 100%;
        max-width: 480px;  /* mobile-safe width */
        margin: 0 auto;
    }

    /* Force iframe to proper height */
    .preview-wrapper iframe {
        width: 100% !important;
        height: 680px !important; /* 🔥 Increase height */
        border: none;
        overflow: auto;
    }

    @media (max-width: 576px) {
        .preview-wrapper iframe {
            height: 950px !important; /* 🔥 Taller for mobile */
        }
    }
</style>
</head>
<body>

<div class="container py-2">

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 text-center">

            <div class="row justify-content-center align-items-center mb-2">
                <div class="col-auto">
                    <h5 class="mb-0 fw-bold">UEF 2025 : Ad Preview</h5>
                </div>
                <div class="col-auto mx-2"></div>
                <div class="col-auto">
                    <a href="#" onclick="event.preventDefault(); window.open('', '_self'); window.close();" class="btn btn btn-default" style="border: 1px solid #ccc;">
                        Close Window
                    </a>
                </div>
            </div>

            <div class="preview-wrapper shadow-sm p-3 bg-white rounded">
                <?php
                if ($result['success']) {
                    echo $result['preview_html'];  // iframe output
                } else {
                    echo "<pre class='text-start bg-white p-3 border rounded'>";
                    print_r($result);
                    echo "</pre>";
                }
                ?>
            </div>

        </div>
    </div>

</div>


</body>
</html>
