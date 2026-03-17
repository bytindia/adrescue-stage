<?php
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
ini_set('display_errors', '1'); 
function d($d) {
	echo '<pre>';
	print_r($d);
	echo '</pre>';
}
//$symbol = 'RIO,NAKA,WELSH,BEAMX,JASMY,ROSE,DOGE,PAAL';
$symbol = '4166,12749,29079,28298,27178,8425,7653,74';
$url = "https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest?id=".$symbol;
//$url = "https://sandbox-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest?id=".$symbol;

$currentDate = date('d');

if($currentDate<16) {
  $apiKey = 'c7c6c9e5-aa91-410b-8522-eacc4e4ddc1e';
} else {
  $apiKey = '711b9709-21ab-4e11-baa7-433a831f1757';
}

$headers = [
  'Accepts: application/json',
  'X-CMC_PRO_API_KEY: '.$apiKey
];

$request = "{$url}"; // create the request URL

$curl = curl_init(); // Get cURL resource
// Set cURL options
curl_setopt_array($curl, array(
  CURLOPT_URL => $request,            // set the request URL
  CURLOPT_HTTPHEADER => $headers,     // set the headers 
  CURLOPT_RETURNTRANSFER => 1         // ask for raw response instead of bool
));

$response = curl_exec($curl); // Send the request, save the response
$json = json_decode($response,true);
curl_close($curl); // Close request
//d($json['data']); exit; 

//var_dump($json->data[0]->quote->USD->price);
//var_dump($json->data[1]->quote->USD->price);
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Crypto - API</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.3/dist/bootstrap-table.min.css">
  </head>
  <style>
    body {width: 80%; padding-top: 20px; margin: 0 auto;}
    </style>
  <body>

  <table data-toggle="table">
  <thead>
  <tr>
        <td>ID</td>
        <td>Symbol</td>
        <td>Price (USD)</td>
        <td>Updated last</td>
  </tr>
  </thead>
      <tbody>
  <? foreach($json['data'] as $key => $value) { 
     ?>
  <tr>
        <td><?php echo $value['id']; ?></td>
        <td><?php echo $value['symbol']; ?></td>
        <td><?php if(isset($value['quote']['USD']['price'])) { echo round($value['quote']['USD']['price'], 4); } ?></td>
        <td><?php if(isset($value['quote']['USD']['last_updated'])) { echo  date("d-m-Y h:i a", strtotime($value['quote']['USD']['last_updated'])); } ?></td>
  </tr>
      
  <? } ?>
  </tbody>
</table>
<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.3/dist/bootstrap-table.min.js"></script>
  </body>
</html>

<?php

