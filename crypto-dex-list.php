<?php
// Function to get DEX pairs for a specific coin ID from CoinGecko
function getDexPairs($coin_id) {
    $dex_url = "https://api.coingecko.com/api/v3/coins/{$coin_id}/tickers";
    
    // Fetch the data from CoinGecko API
    $response = file_get_contents($dex_url);
    
    // Decode the JSON response into an associative array
    $dex_data = json_decode($response, true);
    
    return $dex_data['tickers'];
}

// Step 1: Get the coin ID for LOCKIN (you need to check if LOCKIN exists in CoinGecko)
$coin_slug = 'lockin';  // Replace 'lockin' with the actual slug for your coin if different
$dex_pairs = getDexPairs($coin_slug);

print_r($dex_pairs);