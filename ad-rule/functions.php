<?php



$n=5;
function getName($n) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}


function moneyFormatIndia($num) {
  $num = round($num);
  $explrestunits = "" ;
  if(strlen($num)>3) {
      $lastthree = substr($num, strlen($num)-3, strlen($num));
      $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
      $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
      $expunit = str_split($restunits, 2);
      for($i=0; $i<sizeof($expunit); $i++) {
          // creates each of the 2's group and adds a comma to the end
          if($i==0) {
              $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
          } else {
              $explrestunits .= $expunit[$i].",";
          }
      }
      $thecash = $explrestunits.$lastthree;
  } else {
      $thecash = $num;
  }
  if($thecash==0) { $thecash='-'; }
  return $thecash; // writes the final format where $currency is the currency symbol.
}
function sendWhatsapp($tok, $ph, $head_param, $body_param, $rep_on) {
  $phone = '91'.$ph; // exit;
  //$wa_msg = 'The *'.$acc_name.'* ad account has reached *'.$percentage.'%* of its total spend, which is *₹'.$spend.'* out of *₹'.$budget_V.'*';
  $attachment =  array(
     'messaging_product' => 'whatsapp',
     'to' => $phone,
     'type'=> 'template',
     'template' => 
       json_encode(
         array(
           'name' => 'ad_pause_notify', 
           'language' => array('code'=>'en'), 
           'components'=> 
             array(
                 array(
                     "type" => "header",
                     "parameters" => array(array("type"=> "text", "text"=>$head_param))
                 ),
                 array(
                     "type" => "body",
                     "parameters" => array(
                          array("type"=> "text","text"=> $rep_on),
                          array("type"=> "text","text"=> $body_param)
                     )
                 )
             )
       ))
     );
     
     //print_r($attachment); exit;

     $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
     $post = json_encode($attachment); // Encode the data array into a JSON string
     $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
     curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); //Inject the token into the header
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
     curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
     $result = curl_exec($ch); // Execute the cURL statement
     curl_close($ch); // Close the cURL connection
     return json_decode($result); // Return the received data
     print_r($result); // exit;
}
function curlPost($url, $post){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}
function contains($str, array $arr)
{
  foreach($arr as $a) {
    //print_r($a);
      if (stripos($str,$a) !== false) { return $a; }
  }
  return false;
}

function LeadGen($arr, $filt) {
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}

function criteriaMet($value1, $operator, $value2)
{
    switch ($operator) {
        case '<':
            return $value1 < $value2;
        case '<=':
            return $value1 <= $value2;
        case '>':
            return $value1 > $value2;
        case '>=':
            return $value1 >= $value2;
        case '=':
            return $value1 == $value2;
        case '!=':
            return $value1 != $value2;
        default:
            return false;
    }
}
