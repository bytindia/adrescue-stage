<?php
header("Access-Control-Allow-Origin: https://imageking.in");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';

/* STEP 1: Get Tokens from DB */

$sql = "SELECT * FROM imageking_zoho LIMIT 1";
$res = mysqli_query($conn,$sql);
$row = mysqli_fetch_assoc($res);

$refresh_token = $row['ref_token'];


/* STEP 2: Generate New Access Token */

$client_id = "1000.6SUP0XFQT5WMH2EVNZIGQ3M3IVEY4W";
$client_secret = "cd95a74d5163b479798e7c4c2ca209d571a11c4dbf";

$token_url = "https://accounts.zoho.in/oauth/v2/token";

$postData = [
    "refresh_token" => $refresh_token,
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "grant_type" => "refresh_token"
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $token_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData)
]);

$token_response = curl_exec($ch);

curl_close($ch);



$token_data = json_decode($token_response, true);
//d($token_data);
//print_r($token_data); exit;
$access_token = $token_data['access_token'];



/*
//$access_token = $row['acc_token'];

$url = "https://www.zohoapis.in/crm/v2/settings/fields?module=Leads";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Zoho-oauthtoken $access_token"
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response,true);
d($data);
foreach($data['fields'] as $field){
    echo $field['field_label']." -> ".$field['api_name']."<br>";
}
exit;
*/

/* STEP 3: Update Access Token in DB */
mysqli_query($conn,"UPDATE imageking_zoho SET acc_token='$access_token', updated=NOW()");


/* STEP 4: Prepare Lead Data */

$input = file_get_contents("php://input");
$shopify = json_decode($input);

/* Detect Shopify webhook */
$shopify_topic = $_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? '';


if(isset($_POST['form']) && $_POST['form'] === "Enquiry"){

    /* WEBSITE FORM - https://imageking.in/pages/enquiry */

    $name = $_POST['name'];
    $name_arr = explode(" ", $name);

    $first_name = $name_arr[0];
    $last_name  = $name_arr[1] ?? "Lead";

    $email   = $_POST['email'] ?? '';
    $phone   = $_POST['mobile'] ?? '';
    $street  = $_POST['address'] ?? '';
    $city    = $_POST['city'] ?? '';
    $state   = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $country = $_POST['country'] ?? '';
    $id      = $_POST['id'] ?? '';

}
elseif($shopify_topic){

    /* SHOPIFY WEBHOOK - Chat Lead  */

    $first_name = $shopify->first_name ?? '';
    $last_name  = $shopify->last_name ?? 'Lead';
    $email      = $shopify->email ?? '';

    $address = $shopify->default_address ?? null;

    $phone   = $shopify->phone ?? ($address->phone ?? '');

    $street  = $address->address1 ?? '';
    $city    = $address->city ?? '';
    $state   = $address->province ?? '';
    $pincode = $address->zip ?? '';
    $country = $address->country ?? '';

    $shopify_id = isset($shopify->id) ? (string)$shopify->id : '';


}
else{

    /* Unknown request */
    http_response_code(400);
    exit("Invalid request");

}


/* Build Zoho Payload */

$data = [
 "data" => [[
  "First_Name" => $first_name,
  "Last_Name"  => $last_name,
  "Email"      => $email,
  "Mobile"     => $phone,

  "Street"   => $street,
  "City"     => $city,
  "State"    => $state,
  "Zip_Code" => $pincode,
  "Country"  => $country,

  "shopifyextension__Shopify_Lead_Source" => "Shopify",
  "shopifyextension__Shopify_Reference_Id" => $shopify_id
 ]],
 "trigger" => ["workflow"]
];


/* Push to Zoho */

$ch = curl_init();

curl_setopt_array($ch,[
    CURLOPT_URL=>"https://www.zohoapis.in/crm/v2/Leads",
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_POST=>true,
    CURLOPT_HTTPHEADER=>[
        "Authorization: Zoho-oauthtoken ".$access_token,
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS=>json_encode($data)
]);

$response = curl_exec($ch);

curl_close($ch);

echo $response;


/* Log response */

file_put_contents(
    "/home/digitalb2k/logs/zoho_payload.txt",
    date("Y-m-d H:i:s") . "\n" . $response . "\n\n",
    FILE_APPEND
);
?>