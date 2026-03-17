<?php
function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}
// Set up your access token and ad set IDs
$accessToken = 'REDACTED_FB_TOKEN';



// Global output variable to hold the results
global $output;

function loopAdRep($url) {
    global $output;

    // Make the API request via cURL (you should define your file_get_contents_curl function to handle the cURL requests)
    $requests = file_get_contents_curl($url);
    
    // Decode the response from JSON to an array
    $fb_response = json_decode($requests, true);

    // If we already have data, merge the new data with it
    if (isset($output) && count($output) > 0 && isset($fb_response['data']) && count($fb_response['data']) > 0) {
        $output = array_merge($output, $fb_response['data']);
    } else if (isset($fb_response['data']) && count($fb_response['data']) > 0) {
        // If we don't have previous data, initialize with the new data
        $output = $fb_response['data'];
    }

    // Check if there is a 'next' page for pagination
    if (isset($fb_response['paging']['next'])) {
        // Recursively call the function to fetch the next page of results
        loopAdRep($fb_response['paging']['next']);
    } else {
        // Return the accumulated data when there are no more pages
        return $output['data'] = $output;
    }
}

// Function to retrieve pixel events
function getPixelEvents($accessToken, $pixelId, $eventName = null) {
    global $output;
    
    // Define the base API URL for Pixel events
    $pixelEventsUrl = "https://graph.facebook.com/v17.0/$pixelId/events";

    // Parameters to filter the events (you can also specify specific event types)
    $params = [
        'access_token' => $accessToken,
        'event' => $eventName, // Optional: Filter for specific event like 'Purchase', 'AddToCart', etc.
        'limit' => 100, // Set a reasonable limit for pagination
    ];

    // Build the full URL with query parameters
    $url = $pixelEventsUrl . '?' . http_build_query($params);

    // Initialize the loop to fetch the data
    loopAdRep($url);

    // After looping through all pages, return the pixel event data
    return $output;
}

// Example usage of the getPixelEvents function
$accessToken = 'YOUR_ACCESS_TOKEN';
$pixelId = 'YOUR_PIXEL_ID'; // Replace with the actual Pixel ID
$eventName = 'Purchase'; // Optional: Filter for specific event type, like 'Purchase'

// Call the function to fetch pixel events
$pixelEvents = getPixelEvents($accessToken, $pixelId, $eventName);

// Output the events
echo "<pre>";
print_r($pixelEvents);
echo "</pre>";

?>
