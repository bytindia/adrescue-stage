<?php


//$creativeId = '';



if($upVideo!=='' && $creativeId!='') {
    foreach($targets as $tarV) {
        if($tarV=='TN') { $locTarget =$target_TN; } else { $locTarget =$target_CH; }

        //echo '<br><u>Target: '.$tarV.' (Video)</u><br><br>';
        $table_str .='<tr><td colspan="3"><u>Target: '.$tarV.' (Video)</u></td></tr>';
        //Campaign Creation

        $campN = $tarV.' - Vid -'.date('d-M-Y');
        $post = [
            'name' => $campN,
            'objective' => 'LEAD_GENERATION',
            'special_ad_categories'   => '[]',
            'status' => $adStatus,
            'access_token' => $access_token,
            'targeting' => $locTarget
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/campaigns";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        if(isset($res['id'])) {
            $campId = $res['id'];
            //echo 'Campaign '.$tickIcon.' <a href="'.$campURL.''.$res['id'].'" target="_blank">View</a><br>';
            $table_str .='<tr><td>Campaign</td><td>'.$tickIcon.'</td><td><a href="'.$campURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
        } else { 
            echo 'Failed: AdCreative creation! <a href="create.php">Go Back</a>'; d($res); exit;
        }

        //$campId = 23853184945720609;

        if($campId!='') {
            $campId = $res['id'];
            //AdSet Creation - CI
            $adSetN = $tarV.' - Vid - Open - '.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.','.$target_ext.'}',
                'status' => $adStatus,
                'promoted_object' => '{"page_id":"'.$page_id.'"}',
                'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
                'daily_budget' => 100 * $daily_budget,
                'access_token' => $access_token
            ];
            $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
            $req = curlPost($url,$post);
            $res = json_decode($req, true);  
            

            if(isset($res['id'])) {
                $adSetId[0] = $res['id']; 
                //echo 'Adset - '.$tarV.' - Open '.$tickIcon.' <a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br>';
                $table_str .='<tr><td>Adset - '.$tarV.' - Open</td><td>'.$tickIcon.'</td><td><a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
            } else { 
                echo 'Failed: AdSet '.$tarV.' - Open! <a href="create.php">Go Back</a>'; d($res); exit;
            }

            //AdSet Creation - CI
            $adSetN = $tarV.' - Vid - CI - '.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.','.$target_ci.'}',
                'status' => $adStatus,
                'promoted_object' => '{"page_id":"'.$page_id.'"}',
                'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
                'daily_budget' => 100 * $daily_budget,
                'access_token' => $access_token
            ];
            $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
            $req = curlPost($url,$post);
            $res = json_decode($req, true);  
           
            if(isset($res['id'])) {
                $adSetId[1] = $res['id']; 
                //echo 'Adset - '.$tarV.' - CI '.$tickIcon.' <a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br>';
                $table_str .='<tr><td>Adset - '.$tarV.' - CI</td><td>'.$tickIcon.'</td><td><a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
            } else { 
                echo 'Failed: AdSet '.$tarV.' - CI! <a href="create.php">Go Back</a>'; d($res); exit;
            }

            //AdSet Creation - LA 1%
            $adSetN = $tarV.' - Vid - LA 1% -'.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.','.$target_ext.',"custom_audiences": [{"id":"'.$LA_aud_ids[$accId][0].'"}]}',
                'status' => $adStatus,
                'promoted_object' => '{"page_id":"'.$page_id.'"}',
                'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
                'daily_budget' => 100 * $daily_budget,
                'access_token' => $access_token
            ];
            $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
            $req = curlPost($url,$post);
            $res = json_decode($req, true);  
            if(isset($res['id'])) {
                $adSetId[2] = $res['id']; 
                //echo 'Adset - '.$tarV.' - LA 1% '.$tickIcon.' <a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br>';
                $table_str .='<tr><td>Adset - '.$tarV.' - LA 1% </td><td>'.$tickIcon.'</td><td><a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
            } else { 
                echo 'Failed: AdSet '.$tarV.' - LA 1%! <a href="create.php">Go Back</a>'; d($res); exit;
            }

            //AdSet Creation - LA 2%
            $adSetN = $tarV.' - Vid - LA 2% -'.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.','.$target_ext.',"custom_audiences": [{"id":"'.$LA_aud_ids[$accId][1].'"}]}',
                'status' => $adStatus,
                'promoted_object' => '{"page_id":"'.$page_id.'"}',
                'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
                'daily_budget' => 100 * $daily_budget,
                'access_token' => $access_token
            ];
            $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
            $req = curlPost($url, $post);
            $res = json_decode($req, true);  
            if(isset($res['id'])) {
                $adSetId[3] = $res['id']; 
                //echo 'Adset -  '.$tarV.' - LA 2% '.$tickIcon.' <a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br>';
                $table_str .='<tr><td>Adset - '.$tarV.' - LA 2% </td><td>'.$tickIcon.'</td><td><a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
            } else { 
                echo 'Failed: AdSet  '.$tarV.' - LA 2%! <a href="create.php">Go Back</a>'; d($res); exit;
            }
    } else {
                echo 'Failed: Campaign Creation <br><br>';
                d($res); exit;
    }
        
    


    if($creativeId!='' && count($adSetId)>0) {
            $z=1;
            foreach($adSetId as $k => $v) 
            {
                $adSetN = 'Image - '.date('d M');
                $post = [
                    'name' => $adSetN,
                    'adset_id' => $v,
                    'creative' => '{"creative_id": "'.$creativeId.'"}',
                    'status' => $adStatus,
                    'access_token' => $access_token
                ];

                $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/ads";
                $req = curlPost($url,$post);
                $res = json_decode($req, true);
                //echo 'ads:<br>'; d($res);
                if(isset($res['id'])) {
                    //echo 'Ad - '.$z.' '.$tickIcon.' <a href="'.$adURL.''.$res['id'].'" target="_blank">View</a><br>';
                    $table_str .='<tr><td>Ad - '.$z.'</td><td>'.$tickIcon.'</td><td><a href="'.$adURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
                } else { 
                    //echo 'Failed: AdSet '.$tarV.' - LA 2%! <a href="create.php">Go Back</a>'; exit;
                }
                $z++;
            }
    }
    }
}