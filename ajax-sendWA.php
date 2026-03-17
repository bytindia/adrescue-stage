<?php 		
//include 'db.php';

//header('Content-type: application/json');

//$_POST['id']=90;
function sendWhatsapp($tok, $phone, $wa_client, $wa_date, $wa_spend, $wa_lead, $wa_cpl) {
    $attachment =  array(
        
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type'=> 'template',
        'template' => 
          json_encode(
            array(
              'name' => 'ad_spend_report_test', 
              'language' => array('code'=>'en_US'), 
              'components'=> 
                array(array(
                "type" => "body",
                "parameters" => array(
                    array("type"=> "text","text"=> $wa_client),
                    array("type"=> "text","text"=> $wa_date),
                    array("type"=> "text","text"=> $wa_spend),
                    array("type"=> "text","text"=> $wa_lead),
                    array("type"=> "text","text"=> $wa_cpl)
                )))
          ))
        );
        $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
        $post = json_encode($attachment); // Encode the data array into a JSON string
        $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection
        return json_decode($result); // Return the received data
        //print_r($result); 
  
  }

  
$output = array();
//$_POST['id'] = 
if(isset($_POST['acc_tok']) && $_POST['acc_tok']!='')
{
    //$phone = '919176299010';
    // $wa_res = sendWhatsapp($access_token, $phone);
   // print_r($_POST);
    $phone = $_POST['countryCode'].''.$_POST['phone'];
    $wa_client = $_POST['wa_client'];
    $wa_date = $_POST['wa_date'];
    $wa_spend = $_POST['wa_spend'];
    $wa_lead = $_POST['wa_lead'];
    $wa_cpl = $_POST['wa_cpl'];
    $wa_res = sendWhatsapp($_POST['acc_tok'], $phone, $wa_client, $wa_date, $wa_spend, $wa_lead, $wa_cpl);
    print_r($wa_res);
}
echo json_encode($output);

