
<?php exit;
//ini_set('display_errors','1'); ini_set('display_startup_errors','1');
//error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php'; // provides $g_refresh_token, $g_mcc

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token']; 

require $dirPath . 'google-ads-v15/vendor/autoload.php';
$iniPath = $dirPath . 'google_ads_php.ini'; // <- fixed path



header('Content-Type: application/json');

// quick sanity checks
if (!is_readable($iniPath)) {
  echo json_encode(['ok'=>false,'error'=>'INI missing/unreadable','path'=>$iniPath], JSON_PRETTY_PRINT); exit;
}
if (empty($g_refresh_token) || empty($g_mcc)) {
  echo json_encode(['ok'=>false,'error'=>'Missing $g_refresh_token or $g_mcc from db.php'], JSON_PRETTY_PRINT); exit;
}

// sanitize MCC to digits only
$loginCustomerId = preg_replace('/\D+/', '', (string)$g_mcc);

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V20\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsStreamRequest;

function buildGoogleAdsClient(string $iniPath, string $refreshToken, string $loginCustomerId): GoogleAdsClient {
  $oAuth2Credential = (new OAuth2TokenBuilder())
      ->fromFile($iniPath)
      ->withRefreshToken($refreshToken)
      ->build();

  return (new GoogleAdsClientBuilder())
      ->fromFile($iniPath)
      ->withOAuth2Credential($oAuth2Credential)
      ->withLoginCustomerId($loginCustomerId)
      ->build();
}

// stream first, then fall back to non-streaming search()
function getCampaignNameById(GoogleAdsClient $client, int $customerId, int $campaignId, array &$debug = []): ?string {
  $query = "SELECT campaign.id, campaign.name FROM campaign WHERE campaign.id = $campaignId LIMIT 1";
  $svc   = $client->getGoogleAdsServiceClient();

  // try streaming
  try {
    $req = new SearchGoogleAdsStreamRequest(['customer_id'=>$customerId, 'query'=>$query]);
    foreach ($svc->searchStream($req)->iterateAllElements() as $row) {
      return $row->getCampaign()->getName();
    }
    return null;
  } catch (\Throwable $e) {
    $debug['stream_error'] = $e->getMessage();
  }

  // fallback to non-streaming
  try {
    $resp = $svc->search($customerId, $query);
    foreach ($resp->iterateAllElements() as $row) {
      return $row->getCampaign()->getName();
    }
    return null;
  } catch (\Throwable $e) {
    $debug['search_error'] = $e->getMessage();
    return null;
  }
}


function loopAllData($data) {
    foreach ($data as $key => $value) {
      if (!is_array($value)) {
        if($key=='column_name') { $results .= $value . ": ";  }
        if($key=='string_value') { 
            $results .= $value .  "<br>\r\n"; 
            
        }
       // $results .= $key . ": " . $value . "<br>\r\n";
      }
      if (is_array($value)) {
        $results .= loopAllData($value);
      }
    }
    return $results;
}

function searchForId($id, $array) {
    foreach ($array as $key => $val) {
        if ($val['column_name'] === $id) {
          return $val['string_value'];
        }
    }
    return null;
}

$Google_data = file_get_contents("php://input");
$data = json_decode($Google_data, true);
$name = $email = $phone = '';

$phone = searchForId('User Phone', $data['user_column_data']);
$name = searchForId('Full Name', $data['user_column_data']);
$email = searchForId('User Email', $data['user_column_data']);

$projectKey = $data['google_key'];
$campaignId = $data['campaign_id'];

//$customerId = 1449416941;         // child (no dashes)
//$campaignId = 22013758920;        // the one you want

$erp_q = [
        'UID'      => 'fourqt',
        'PWD'      => 'wn9mxO76f34=',
        'f'        => 'm',
        'con'      => $phone,
        'email'    => $email,
        'name'     => $name,
        'Remark'   => '',
        'utm_source' => 'Google Form Ad',
];

if($projectKey=='mh-aarya'){
    $customerId = 5600153376; 
    $project="Mahendra-Aarya";
    $erp_q['src'] = 'Website-Aarya';
    $erp_q['ch'] = 'Website-Aarya';

} else if($projectKey=='mh-helix'){
    $customerId = 1449416941;
    $project="Mahendra Aarto Helix";
    $erp_q['src'] = 'Website-Helix';
    $erp_q['ch'] = 'MShelix';
}

try {
  $client = buildGoogleAdsClient($iniPath, $g_refresh_token, $loginCustomerId);
  $debug  = ['iniPath'=>$iniPath, 'loginCustomerId'=>$loginCustomerId, 'customerId'=>$customerId];
  $campaignName   = getCampaignNameById($client, $customerId, $campaignId, $debug);

  $erp_base  = 'http://mahendrahomes09.remserp.com/IVR_Inbound.aspx';

  $erp_q['UTM_CAMP'] = $campaignName ?? '';
  $erp_q['Proj'] = $project;
    
    
    //print_r($erp_q);
    // ðŸ”¹ Final URL with query string
    $erp_url = $erp_base . '?' . http_build_query($erp_q);

    // ðŸ”¹ cURL Setup
    $erp_ch = curl_init($erp_url);
    curl_setopt_array($erp_ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $erp_response  = curl_exec($erp_ch);
    $erp_code      = curl_getinfo($erp_ch, CURLINFO_HTTP_CODE);
    curl_close($erp_ch);
    //file_put_contents(__DIR__ . '/mahendra.txt', "[".date('c')."]\n".$erp_response."\n\n", FILE_APPEND | LOCK_EX);

} catch (\Throwable $t) {
  echo json_encode(['ok'=>false,'error'=>'Bootstrap: '.$t->getMessage()], JSON_PRETTY_PRINT);
}