<?php
function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

function checkPixel($url) {
    
    $ch = curl_init($url);

    // Set options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (if needed)
    
    // Execute cURL
    $response = curl_exec($ch);
    
    // Check for errors
    if ($response === false) {
        echo "Error: " . curl_error($ch);
        curl_close($ch);
        exit;
    }
    
    // Close cURL
    curl_close($ch);
    
    // Regex patterns for FB Pixel and Google Pixel
    $fbPixelPattern = '/fbq\(\'init\',\s*\'([0-9]+)\'\)/'; // Matches fbq('init', 'PIXEL_ID')
    $googlePixelPattern = '/gtag\(\'config\',\s*\'(UA-[0-9]+-[0-9]+)\'\)/'; // Matches gtag('config', 'TRACKING_ID')
    
    // Extract FB Pixel ID
    if (preg_match($fbPixelPattern, $response, $fbMatch)) {
        $fbPixelID = $fbMatch[1];
        $pixel['fb'] = 'fas fa-check-circle';
    } else {
        $pixel['fb'] = 'fas fa-times-circle';
    }
    
    // Extract Google Pixel ID
    if (preg_match($googlePixelPattern, $response, $googleMatch)) {
        $googlePixelID = $googleMatch[1];
        $pixel['g'] = 'fas fa-check-circle';
    } else {
        $pixel['g'] = 'fas fa-times-circle';
    }

        return $pixel;
}

$check = checkPixel($url='https://digitalazadi.com/op/dawb/1-2');
d($check);
?>