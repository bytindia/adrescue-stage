<?php
$userdb = array(
    array(
        'uid' => '100',
        'name' => 'Sandra Shush',
        'pic_square' => 'urlof100'
    ),
    array(
        'uid' => '5465',
        'name' => 'Stefanie Mcmohn',
        'pic_square' => 'urlof100'
    ),
    array(
        'uid' => '40489',
        'name' => 'Michael',
        'pic_square' => 'urlof40489'
    )
);

function searchForId($id, $array) {
    foreach ($array as $key => $val) {
        if ($val['column_name'] === $id) {
            return $val['string_value'];
        }
    }
    return null;
 }

// Comment out this variable when you are ready to use this webhook:
$Google_data = '{
    "lead_id": "NDAwMDAwMDAwMDA6bGVhZF9pZDoxNTg4OTgxNjA1ODIw",
    "user_column_data": [{
        "column_name": "User Phone",
        "string_value": "+16505550123",
        "column_id": "PHONE_NUMBER"
      },
      {
        "column_name": "User Email",
        "string_value": "test@example.com",
        "column_id": "EMAIL"
      },
      {
        "column_name": "Postal Code",
        "string_value": "94043",
        "column_id": "POSTAL_CODE"
      },
      {
        "column_name": "Full Name",
        "string_value": "FirstName",
        "column_id": "FIRST_NAME"
      }
      
    ],
    "api_version": "1.0",
    "form_id": 40000000000,
    "campaign_id": 7581129110,
    "google_key": "12345",
    "is_test": true,
    "gcl_id": "123abc456def",
    "adgroup_id": 20000000000,
    "creative_id": 30000000000
  }';

  // Uncomment next line when you are ready to use this webhook:
  // $Google_data = file_get_contents("php://input");
  $data = json_decode($Google_data, true);

  $phone = searchForId('User Phone', $data['user_column_data']);
  $name = searchForId('Full Name', $data['user_column_data']);
  $email = searchForId('User Email', $data['user_column_data']);
  print_r($phone); print_r($name); print_r($email); exit; 


  $name = $email = $phone = '';
  $post = array();
  function loopAllData($data) {
    $j = 0;
    foreach ($data as $key => $value) {
     //   echo $j;
      if (!is_array($value)) {
        if($key=='column_name') { $results .= $value . ": "; $colN = $value; }
        if($key=='string_value') { 
           
            $results .= $value .  "<br>\r\n"; 
            if($colN=='Full Name') { $post['name'] = $value;  echo $colN.' - '.$value.'<br>'; }
            if($colN=='User Email') { $post['email'] = $value; }
            if($colN=='User Phone') { $post['phone'] = $value; }
        }
       // $results .= $key . ": " . $value . "<br>\r\n";
      }
      if (is_array($value)) {
        $results .= loopAllData($value);
      }
    }
    print_r($post);
    return $post;
  }

  // You set this in Google Ads, just under where you put the webhook URL
  $pass="12345";

  if ( $pass == $data["google_key"]) {

    $lead_id = $data['lead_id'];
    $api_version = $data['api_version'];
    $form_id = $data['form_id'];
    $campaign_id = $data['campaign_id'];
    // $google_key = $data['google_key'];
    // $is_test = $data['is_test'];
    // $gcl_id = $data['gcl_id'];
    // $adgroup_id = $data['adgroup_id'];
    // $creative_id = $data['creative_id'];
    $body_data = loopAllData($data['user_column_data']);
    print_r($body_data); exit;
    $bodyemail = "<br>Campaign ID: {$campaign_id}<br>\r\n";
    $bodyemail .= "lead ID: {$lead_id}<br>\r\n";
    $bodyemail .= "Form ID: {$form_id}<br>\r\n";
    $bodyemail .= $body_data;
    $bodyemail .= "API Version: {$api_version}<br>\r\n";

    // Change with your emails
    $emailfrom = "webhooks@email.com";
    $emailto = "your@email.com";
    $subject = "Brand - Google Ads - Lead form extension";
    $headers = "From: {$emailfrom}\r\n";
    $headers .= "Reply-To: {$emailfrom}\r\n"; //Optional
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";

    // Uncomment next line when you are ready to use this webhook
    // mail($emailto, $subject, $bodyemail, $headers);

    // Comment out next line when you are ready to use this webhook
    print_r($emailto. $subject. $bodyemail. $headers);

  }
?>