<?php
// Cust Audience
$cust_aud_list = $lookalike_aud_list = $remark_aud_list = $cust_aud_ty = $age_target = $work_position = $work_emp = $interests_target = $placement = $dynamic = array();
if(count($act_ads_adset_ids)>0) {

    //Get All Custom Audience
    $qry_ca = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fbId.'/customaudiences?fields=name,id,subtype,delivery_status&limit=500&access_token='.$access_token.'';
    $res_ca = file_get_contents_curl($qry_ca);
    $cust_aud_acc = json_decode($res_ca,true);
    if(isset($cust_aud_acc['data']) && count($cust_aud_acc['data'])>0) {
        foreach($cust_aud_acc['data'] as $kk => $vv) {
            $cust_aud_ty[$vv['id']] = $vv['subtype'];
        }
    }

    $accessToken = $access_token;
    //$accessToken = 'REDACTED_FB_TOKEN';
    
    // List of ad set IDs to check
    //$adSetIds = ['120214730598500481', '120214730598480481', '120214730598460481']; // Replace with your actual Ad Set IDs
    
    // Prepare batch request
    $batch = [];
    foreach ($act_ads_adset_ids as $k => $adSetId) {
        $batch[] = [
            'method' => 'GET',
            'relative_url' => "$adSetId?fields=id,targeting,is_dynamic_creative"
        ];
    }
    
    // Convert batch request to JSON
    $batchRequest = json_encode($batch);
    
    // API endpoint for batch requests
    $batchUrl = "https://graph.facebook.com/".$api_ver."/";
    
    // Initialize cURL for batch request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $batchUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'access_token' => $accessToken,
        'batch' => $batchRequest
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute the request
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Check if the request was successful
    if ($response === false) {
        die('Error fetching batch data');
    }
    
    $batchResponse = json_decode($response, true);
    //print_r($batchResponse); exit;
    // Process each ad set's response
    foreach ($batchResponse as $index => $adSetResponse) {
        //d($adSetResponse); exit;
        if (isset($adSetResponse['body'])) {
            $adSetData = json_decode($adSetResponse['body'], true);
            //d($adSetData); exit;
            if (isset($adSetData['targeting']['custom_audiences']) && count($adSetData['targeting']['custom_audiences']) > 0) {
                //echo "<h3>Custom Audiences for Ad Set: " . htmlspecialchars($cust_aud_list[$index]) . "</h3>";
                

                foreach ($adSetData['targeting']['custom_audiences'] as $audience) {
                    $adset_id = $adSetData['id'];
                    $aud_id = $audience['id'];
                    if(isset($cust_aud_ty[$aud_id]) && $cust_aud_ty[$aud_id]=='CUSTOM'){ $cust_aud_list[] = $adset_id; }
                    if(isset($cust_aud_ty[$aud_id]) && $cust_aud_ty[$aud_id]=='LOOKALIKE'){ $lookalike_aud_list[] = $adset_id; }
                    if(isset($cust_aud_ty[$aud_id]) && $cust_aud_ty[$aud_id]=='WEBSITE'){ $remark_aud_list[] = $adset_id; }

                    /*$cust_aud_list[] =  htmlspecialchars($audience['name']);
                    if (isset($audience['type']) && $audience['type'] === 'LOOKALIKE') {
                        $lookalike_aud_list[] = htmlspecialchars($audience['name']);
                    }*/
                }
            } else {
                //echo "<p>No custom audiences found for Ad Set: " . $index . "</p>";
            }
           
            
            //Age Range targeting or Age Min/Max
            if (!empty($adSetData['targeting']['age_range']) && count($adSetData['targeting']['age_range']) === 2) {
                $age_target[] = $adSetData['targeting']['age_range'][0] . ' to ' . $adSetData['targeting']['age_range'][1]; 
            } else if (isset($adSetData['targeting']['age_max']) && isset($adSetData['targeting']['age_min']) > 0) {
                $age_target[] = $adSetData['targeting']['age_min'].' to '.$adSetData['targeting']['age_max'];
            }


            //work emp
            if (isset($adSetData['targeting']['flexible_spec'][0]['work_employers']) && count($adSetData['targeting']['flexible_spec'][0]['work_employers']) > 0) {
                
                foreach ($adSetData['targeting']['flexible_spec'][0]['work_employers'] as $work_em) {
                    $work_emp[] = $work_em['name'];
                }
            }
            //work position
            if (isset($adSetData['targeting']['flexible_spec'][0]['work_positions']) && count($adSetData['targeting']['flexible_spec'][0]['work_positions']) > 0) {
                foreach ($adSetData['targeting']['flexible_spec'][0]['work_positions'] as $work_pos) {
                    $work_position[] = $work_pos['name'];
                }
            }
            //interests
            if (isset($adSetData['targeting']['flexible_spec'][0]['interests']) && count($adSetData['targeting']['flexible_spec'][0]['interests']) > 0) {
                foreach ($adSetData['targeting']['flexible_spec'][0]['interests'] as $intr_tar) {
                    $interests_target[] = $intr_tar['name'];
                }
            }
            //Placement
            $pubPlat = $devicePlat = 0;
            if(!isset($adSetData['targeting']['publisher_platforms']) || (isset($adSetData['targeting']['publisher_platforms']) && count($adSetData['targeting']['publisher_platforms'])==4)) {
                $pubPlat = 1;
            } 
            if(!isset($adSetData['targeting']['device_platforms']) || (isset($adSetData['targeting']['device_platforms']) && count($adSetData['targeting']['device_platforms'])==2)) {
                $devicePlat = 1;
            } 
            if($pubPlat==1 && $devicePlat == 1) { $placement_ty['auto'][] = $adSetData['id']; } else { $placement_ty['manual'][] = $adSetData['id']; }

            //Dynamic creative
			if(isset($adSetData['is_dynamic_creative']) && $adSetData['is_dynamic_creative']!='') { $dynamic['y'][] =$adSetData['id']; } else { $dynamic['n'][] = $adSetData['id']; } 
            //d($placement_ty); d($dynamic);
            
        } else {
            //echo "<p>Error fetching data for Ad Set: " . htmlspecialchars($cust_aud_list[$index]) . "</p>";
        }
    }
    
    //print_r($cust_aud_list);

}
//d($cust_aud_list); d($remark_aud_list);
?>