<?php
function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

function checkPixel($url) {
    
    $ch = curl_init($url);

    // Set options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode == 403) {
        echo "Access Forbidden (403). Cannot fetch the content.\n";
    } elseif ($response === false) {
        echo "cURL Error: " . curl_error($ch);
    } else {
        // Check for Facebook Pixel
        if (preg_match("/fbq\\('init',\\s*'([0-9]+)'\\)/", $response, $fbMatch)) {
            echo "Facebook Pixel ID: " . $fbMatch[1] . "\n";
        } else {
            echo "Facebook Pixel not found.\n";
        }

        // Check for Google Pixel
        if (preg_match("/gtag\\('config',\\s*'([A-Z0-9\\-]+)'\\)/", $response, $googleMatch)) {
            echo "Google Pixel ID: " . $googleMatch[1] . "\n";
        } else {
            echo "Google Pixel not found.\n";
        }
    }
}

$check = checkPixel($url='https://digitalazadi.com/op/dawb/1-2');
d($check);

//PAGE SPEED

// API Endpoint & Parameters
$apiKey = 'AIzaSyBI0wwnd03_SGTXU83KSpItc8E9CWgHGSo';
$urlToTest = 'https://digitalazadi.com/dawb';
$apiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=$urlToTest&key=$apiKey";

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpStatus == 200) {
        // Decode the JSON response
        $data = json_decode($response, true);

        // Extract mobile and desktop scores
        $mobileScore = $data['lighthouseResult']['categories']['performance']['score'] * 100;
        $desktopScore = $data['lighthouseResult']['categories']['performance']['score'] * 100;
        
        // Output the results
        echo '<h3>Performance Scores:</h3>';
        echo 'Mobile Score: ' . $mobileScore . '/100<br>';
        echo 'Desktop Score: ' . $desktopScore . '/100<br>';
    } else {
        echo "API Request failed with HTTP Status Code: $httpStatus";
        echo '<pre>' . $response . '</pre>';
    }
}
?>