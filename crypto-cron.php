<?php
ini_set('display_errors', 0);
date_default_timezone_set("Asia/Calcutta");   

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/update-row-bulk.php';
//include $dirPath.'google-sheets-api/clear-sheet.php';
include $dirPath.'google-sheets-api/read-sheet.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1JyzDetZRVHycIJIsWpEFSycnTvG3StcykHNU_Gj7g_c'; 
$sheetTab='Symbol'; 

$readsheet = read_sheet($uId, $spreadsheetId, $sheetTab);

$sym_arr = array();
if(isset($readsheet['data'])){
  foreach($readsheet['data'] as $key => $v) { 
    //$sym_arr[] = trim($v[0]);
    if(isset($v[1]) && $v[1]!=null && trim($v[1])!=''){
      $link = str_replace("https://coinmarketcap.com/currencies/","",trim($v[1]));
      //$link = strstr($link, '/', true);
      //$link = strstr($link, '?', true);
      if (str_contains($link, "/")) { 
        $link = substr($link, 0, strpos($link, "/"));
      }
      $sym_arr[] = $link;
    }
  }
}
//d($sym_arr); //exit;
if(count($sym_arr)>0){
  $sym_arr = array_unique($sym_arr);
  $symbol = implode(',',$sym_arr); //exit;
} else {
  exit;
}
$sheetTab='API'; 

//clear_sheet($uId, $spreadsheetId, '906989703'); exit;

//$symbol = 'RIO,NAKA,WELSH,BEAMX,JASMY,ROSE,DOGE,PAAL';
//$symbol = '4166,12749,29079,28298,27178,8425,7653,74';
//$symbol = 'RIO,NAKA,WELSH,BEAM,JASMY,ROSE,DOGE,PAAL,FTM,MATIC,PYR,AR,RUNE,OCEAN,DOT,THETA,VET,GALA,TRIAS,ROSE,TIA,SEI,DYM,STX,PLAY,LONG,OMNOM,ZIL,RAY,FIL,SUSHI,NEXA,GFAL';
//$symbol='LONG,MOCHI';
$url = "https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest?slug=".$symbol; //exit;
//$url = "https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest?slug=".$symbol;
//$url = "https://sandbox-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest?id=".$symbol;

$currentDate = date('d');
//echo $currentDate; exit;
if($currentDate<11) {
  $apiKey = 'c7c6c9e5-aa91-410b-8522-eacc4e4ddc1e';
} else if($currentDate>10 && $currentDate<20) {
  $apiKey = '711b9709-21ab-4e11-baa7-433a831f1757';
} else {
  $apiKey = 'f9cc4220-baf3-4b3a-821a-d39cd023b5b0';
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
//d($json); exit; 

//var_dump($json->data[0]->quote->USD->price);
//var_dump($json->data[1]->quote->USD->price);

if(isset($_GET['print'])){
  echo $url.'<br>'; 
  d($json['data']); exit; 
}

$rowNo = 1; $leadV = array();
//d($json['data']); //exit;
foreach($json['data'] as $key => $v) { 
  //d($v);
    $price = $updated = $last_1h = $last_24h = $last_7d = '';
    if(isset($v)) {
      $value = $v;
      //if($value['symbol']=='MOCHI') { $value = $v[1]; }
      
      //if(isset($value['quote']['USD']['price'])) { $price = round($value['quote']['USD']['price'], 4); }
      if(isset($value['quote']['USD']['price'])) { if (strpos($value['quote']['USD']['price'], "E") !== false) { $price = number_format($value['quote']['USD']['price'],15); } else { $price = number_format($value['quote']['USD']['price'],8); } }

      if(isset($value['quote']['USD']['percent_change_1h'])) { if (strpos($value['quote']['USD']['percent_change_1h'], "E") !== false) { $last_1h = number_format($value['quote']['USD']['percent_change_1h'],15); } else { $last_1h = number_format($value['quote']['USD']['percent_change_1h'],8); } }

      if(isset($value['quote']['USD']['percent_change_24h'])) { if (strpos($value['quote']['USD']['percent_change_24h'], "E") !== false) { $last_24h = number_format($value['quote']['USD']['percent_change_24h'],15); } else { $last_24h = number_format($value['quote']['USD']['percent_change_24h'],8); } }

      if(isset($value['quote']['USD']['percent_change_7d'])) { if (strpos($value['quote']['USD']['percent_change_7d'], "E") !== false) { $last_7d = number_format($value['quote']['USD']['percent_change_7d'],15); } else { $last_7d = number_format($value['quote']['USD']['percent_change_7d'],8); } }
      
      if(isset($value['quote']['USD']['last_updated'])) { $updated =  date("Y-m-d H:i:s", strtotime($value['quote']['USD']['last_updated'])); }  
      if($value['symbol']=='$MICHI') { $value['symbol']='MICHI'; }
      if($value['symbol']=='SPX') { $value['symbol']='SPX6900'; }
       $leadV[] = array($value['id'],$value['symbol'], $price, $last_1h.'%', $last_24h.'%', $last_7d.'%', $updated);
      //d($leadV); exit;
      
      
    } else {
      //$leadV = [['',$v, $price, $updated]];
    }
     // exit;
    $rowNo++;
   //
   // echo $rowNo; 
}
//d($leadV);
update_row_bulk($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab, 'A', $rowNo=1);
?> Success