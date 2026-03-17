<?php exit;
date_default_timezone_set("Asia/Kolkata");


if(isset($_POST['pageId']) && $_POST['pageId']!='')
{
    // Prepare variables
    $lead = $_POST['lead'];
    $user = unserialize($lead);

    $rawPhone        = $user["phone_number"] ?? '';
    $userEmail    = $user["email"] ?? '';
    $userName     = $user["full_name"] ?? '';
    //$country      = $user["country"] ?? '';
    //$countryCode  = $user["countrycode"] ?? '';
    $transcriptId = $user["transcriptid"] ?? '';
    $chatSource   = '';
    $utmCampaign  = $_POST['campaign'] ?? '';
    $formName  = $_POST['formName'] ?? '';

    $lCustom = '';
					$j = 1;
					foreach($user as $attr=>$val) 
					{
						$fields = ucwords(str_replace("_"," ",$attr));
						if($attr!='full_name' && $attr!='full name' && $attr!='email' && $attr!='phone_number') {
							$lCustom .= '[Q'.$j.']: '.str_replace("_"," ",$attr).' = '.str_replace("_"," ",$val).' ';
							$j++;
						} 
					}

    $countryCode = '+91'; // default
    $cleanPhone  = preg_replace('/[^0-9]/', '', $rawPhone); // strip non-digits
    // Check if number starts with country code
    if (preg_match('/^\+(\d{1,4})(\d{6,})$/', $rawPhone, $matches)) {
        // Format: +<code><number>, e.g., +919876543210
        $countryCode = '+' . $matches[1]; // e.g., +91
        $cleanPhone  = $matches[2];       // e.g., 9876543210
    } elseif (preg_match('/^(\d{2,4})(\d{6,})$/', $cleanPhone, $matches)) {
        // Format: country code without +, e.g., 919876543210
        $countryCode = '+' . $matches[1];
        $cleanPhone  = $matches[2];
    } 

    $erp_success = false;

    

    $erp_base  = 'http://mahendrahomes09.remserp.com/IVR_Inbound.aspx';

    $erp_q = [
        'UID'      => 'fourqt',
        'PWD'      => 'wn9mxO76f34=',
        'f'        => 'm',
        'con'      => $rawPhone,
        'email'    => $userEmail,
        'name'     => $userName,
        'Remark'   => '',
        'UTM_CAMP' => $utmCampaign,
        'utm_source' => $lCustom,
    ];
    if (stripos($formName, 'aarya') !== false) {
        $erp_q['src'] = 'FB-Aarya';
        $erp_q['ch'] = 'FBArya';
        $erp_q['Proj'] = 'Mahendra-Aarya';
    }
    if (stripos($formName, 'helix') !== false) {
        $erp_q['src'] = 'Facebook-Helix';
        $erp_q['ch'] = 'FBHelix';
        $erp_q['Proj'] = 'Mahendra Aarto Helix';
    }
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

    // ðŸ”¹ Error handler
    if ($erp_code !== 200) {
        echo json_encode([
            'status'  => 'error',
            'erp'     => false,
            'message' => "ERP Failed ($erp_code): " . htmlspecialchars($erp_response)
        ]);
        exit;
    }

    $erp_success = true;

    $mail_body="<html><body><h2>Form : $formName </h2>";
		$mail_body.="<br>
		<style>
		table{
			border-collapse: separate;
			border-spacing: 10px; /* Apply cell spacing */
		}
		table, th, td{
			border: 1px solid #666;
		}
		table th, table td{
			padding: 5px; /* Apply cell padding */
		}
	</style>
		<table border='1' style='border-collapse: collapse; border-spacing: 10px;'  cellpadding='10' cellspacing='10'>
		";
            foreach($user as $attr=>$val) 
				{
					$fields = ucwords(str_replace("_"," ",$attr));
					if($attr=='full_name') { $fields='Name'; }
					if($attr=='email') { $fields='Email'; }
					if($attr=='phone_number') { $fields='Phone'; }
					$mail_body.= "<tr><td>".$fields."</td><td> ".$val."</td></tr>";
				}
				$mail_body.= "<tr><td>Project </td><td> ".$erp_q['Proj']."</td></tr>";
				$mail_body.= "<tr><td>Campaign</td><td> ".$_POST['campaign']."</td></tr>";
				$mail_body.= "<tr><td>Adset</td><td> ".$conn, $_POST['adset']."</td></tr>";
				$mail_body.= "<tr><td>Ad</td><td> ".$conn, $_POST['ad']."</td></tr>";
                $mail_body.="
		</table><br>
		</body></html>";

}