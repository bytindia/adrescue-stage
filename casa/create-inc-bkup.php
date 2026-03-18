<?php

$LA_aud_ids = array(
427933624679014 => array(23851760091460399,	23851760092930399),
1744658879060010 => array(23852305946760187,23852591091130187),
1196015513904250 => array(23850896202320787,23850896203170787),
569001103516290 => array(23851760091460399,23852317449700399),
462105354650207 => array(23850896202320787,23850896203170787),
488023135037290 => array(23850896202320787,23850896203170787),
370327086813811 => array(23851860195460343,23851860199170343),
807092046796106 => array(23850896202320787,23850896203170787),
735957617015640 => array(23850896202320787,23850896203170787),
834297526950773 => array(23850896202320787,23850896203170787),
1317725875075400 => array(23851338695150102,23851338717290102),
5656356187816184 => array(23853233937860609,23852504964240609)
);



    $accId = '5656356187816184';
    $page_id = 172141376273056;
    $form_id = 853395315962636;
    //$daily_budget = 100;

    $target_age = '"age_max": 60,"age_min": 30';
    $target_ci = '"flexible_spec": [{"interests": [{"id": "1434559786872259","name": "NoBroker.com"},{"id": "6002868978310","name": "InterContinental Hotels Group"},{"id": "6002948635355","name": "Godrej Group"},{"id": "6002962194246","name": "Swing trading"},{"id": "6002988237368","name": "Marriott International"},{"id": "6003072462582","name": "JW Marriott Hotels"},{"id": "6003102712240","name": "Indiabulls"},{"id": "6003104558317","name": "Day trading"},{"id": "6003126209645","name": "Stock exchange"},{"id": "6003174415534","name": "First-time buyer"},{"id": "6003175076849","name": "Four Seasons Hotels and Resorts"},{"id": "6003183450612","name": "Sheraton Hotels and Resorts"},{"id": "6003307244821","name": "Trulia"},{"id": "6003347800581","name": "Foreign exchange market"},{"id": "6003349860951","name": "Stock market"},{"id": "6003383552337","name": "Luxury Resorts"},{"id": "6003402867347","name": "Lodha Group"},{"id": "6003431489469","name": "Residential area"},{"id": "6003446239080","name": "Real estate investing"},{"id": "6003488127669","name": "99acres"},{"id": "6003595635263","name": "Hilton Hotels & Resorts"},{"id": "6003622523903","name": "Bombay Stock Exchange"},{"id": "6003715005316","name": "Luxury yacht"},{"id": "6003728466560","name": "BSE SENSEX"},{"id": "6003737798116","name": "Oberoi Realty"},{"id": "6003833310052","name": "Marriott Hotels & Resorts"},{"id": "6003909817136","name": "Zillow"},{"id": "6004052252096","name": "Nifty Fiftys"},{"id": "6005301103600","name": "Prestige Group"},{"id": "6005999968525","name": "Shapoorji Pallonji Group"},{"id": "6002973217774","name": "Barclays"},{"id": "6003340642996","name": "Morgan Stanley"},{"id": "6003472328263","name": "Merrill Lynch"},{"id": "6002969718768","name": "Oracle Corporation"},{"id": "6003341956023","name": "JPMorgan Chase"},{"id": "6003142740861","name": "Capgemini"},{"id": "6004050939096","name": "Tata Consultancy Services"},{"id": "6003579742642","name": "Goldman Sachs"},{"id": "6003294938911","name": "Wipro"},{"id": "6004050939096","name": "Tata Consultancy Services"},{"id": "6003278152399","name": "Ernst & Young"},{"id": "6003605021020","name": "Accenture"},{"id": "6003648979716","name": "KPMG"},{"id": "6003294938911","name": "Wipro"},{"id": "6003301729369","name": "Infosys"},{"id": "6003332604088","name": "Tech Mahindra"},{"id": "6003077797429","name": "Chennai"}]}]';
    $target_ext ='"flexible_spec": [{"interests": [{"id": "6003077797429","name": "Chennai"}]}]';
    $target_TN = '"geo_locations":{"regions": [{"key":"1744"}]}';
    $target_CH = '"geo_locations":{"cities": [{"key":"1021534","radius":40, "distance_unit":"kilometer"}]}';

    if($upImage!=='') {
        //Campaign Creation
       $campN = 'TN '.date('d-M-Y');
        $post = [
            'name' => $campN,
            'objective' => 'LEAD_GENERATION',
            'special_ad_categories'   => '[]',
            'status' => 'PAUSED',
            'access_token' => $access_token,
            'targeting' => $target_TN
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/campaigns";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        echo 'camp:<br>'; d($res);
        $campId = $res['id']; // 23853184945720609
        

        //$campId = 23853184945720609;

        //AdSet Creation - CI
        $adSetN = 'TN - Open - '.date('d M');
        $post = [
            'name' => $adSetN,
            'optimization_goal' => 'LEAD_GENERATION',
            'billing_event' => 'IMPRESSIONS',
            'campaign_id' => $campId,
            'targeting' => '{'.$target_TN.','.$target_age.','.$target_ext.'}',
            'status' => 'PAUSED',
            'promoted_object' => '{"page_id":"'.$page_id.'"}',
            'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
            'daily_budget' => 100 * $daily_budget,
            'access_token' => $access_token
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        echo 'adset:<br>'; d($res);
        $adSetId[0] = $res['id']; // 23853185081750609;
        //exit;

        //AdSet Creation - CI
        $adSetN = 'TN - CI - '.date('d M');
        $post = [
            'name' => $adSetN,
            'optimization_goal' => 'LEAD_GENERATION',
            'billing_event' => 'IMPRESSIONS',
            'campaign_id' => $campId,
            'targeting' => '{'.$target_TN.','.$target_age.','.$target_ci.'}',
            'status' => 'PAUSED',
            'promoted_object' => '{"page_id":"'.$page_id.'"}',
            'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
            'daily_budget' => 100 * $daily_budget,
            'access_token' => $access_token
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        //echo 'adset:<br>'; d($res);
        $adSetId[1] = $res['id'];

        //AdSet Creation - LA 1%
        $adSetN = 'TN - LA 1% -'.date('d M');
        $post = [
            'name' => $adSetN,
            'optimization_goal' => 'LEAD_GENERATION',
            'billing_event' => 'IMPRESSIONS',
            'campaign_id' => $campId,
            'targeting' => '{'.$target_TN.','.$target_age.','.$target_ext.',"custom_audiences": [{"id":"'.$LA_aud_ids[$accId][0].'"}]}',
            'status' => 'PAUSED',
            'promoted_object' => '{"page_id":"'.$page_id.'"}',
            'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
            'daily_budget' => 100 * $daily_budget,
            'access_token' => $access_token
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        //echo 'adset:<br>'; d($res);
        $adSetId[2] = $res['id'];

        //AdSet Creation - LA 2%
        $adSetN = 'TN - LA 2% -'.date('d M');
        $post = [
            'name' => $adSetN,
            'optimization_goal' => 'LEAD_GENERATION',
            'billing_event' => 'IMPRESSIONS',
            'campaign_id' => $campId,
            'targeting' => '{'.$target_TN.','.$target_age.','.$target_ext.',"custom_audiences": [{"id":"'.$LA_aud_ids[$accId][1].'"}]}',
            'status' => 'PAUSED',
            'promoted_object' => '{"page_id":"'.$page_id.'"}',
            'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
            'daily_budget' => 100 * $daily_budget,
            'access_token' => $access_token
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
        $req = curlPost($url, $post);
        $res = json_decode($req, true);  
        //echo 'adset:<br>'; d($res);
        $adSetId[3] = $res['id'];

        
    

        //$adSetId = 23853196821470609;
        //Image upload
        //if($upImage!=='') {
          //  echo  $fileContent = '@' . realpath("casa.jpeg", 'image/jpeg');
          if (function_exists('curl_file_create')) {
            $fileContent = curl_file_create("uploads/".$upImage, 'image/'.$upImage_ext);
        } else {
            $fileContent = '@' . realpath("uploads/".$upImage, 'image/'.$upImage_ext);
        }

        $post = [
            'filename' => $fileContent,
            'access_token' => $access_token
        ];

        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adimages";
        $req = curlPost($url,$post);
        $res = json_decode($req, true); 
        //echo 'adimages:<br>'; d($res); // exit;
        $hash = $res['images'][$upImage]['hash'];
        echo 'adimages: '.$hash.'<br>'; 
        //d($res['images'][$upImage]['hash']); //e77cb9b0fd81dcd274937757a9846899

        $post = [
            'name' => 'Casa Creative '.date('d M'),
            'object_story_spec' => '{
                "page_id": "'.$page_id.'",
                "link_data": {
                  "call_to_action": {"type":"SIGN_UP","value":{ "lead_gen_form_id":"'.$form_id.'"}}, 
                  "image_hash": "'.$hash.'",
                  "link": "http:\/\/fb.me\/",
                  "message": "message here",
                  "description": "description here",
                  "name" : "name here",
                  "use_flexible_image_aspect_ratio" : false 
                }
              }',
            'access_token' => $access_token
        ];

        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        echo 'adcreatives:<br>'; d($res); //exit;

        $creativeId = $res['id'];
        if($creativeId!='') {
            //d($adSetId);
            foreach($adSetId as $k => $v) {
                $adSetN = 'Image - '.date('d M');
                $post = [
                    'name' => $adSetN,
                    'adset_id' => $v,
                    'creative' => '{"creative_id": "'.$creativeId.'"}',
                    'status' => 'PAUSED',
                    'access_token' => $access_token
                ];

                $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/ads";
                $req = curlPost($url,$post);
                $res = json_decode($req, true);
                echo 'ads:<br>'; d($res);
            }
        }
    }
    
    exit;
/*
    //Video Upload
    if($upVideo!=='') {
        $fileContent = $server_path.'casa/uploads/' . realpath($upVideo, 'image/'.$upVideo_ext.'');
        $post = [
            'name' => 'CasaVideo',
            'source' => $fileContent,
            'access_token' => $access_token
        ];

        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/advideos";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        $videoId = $res['id']; //548256773756565
       // d($res); exit; 

       $post = [
            'name' => 'Casa Creative Sample',
            'object_story_spec' => '{
                "page_id": "'.$page_id.'",
                "video_data": {
                    "call_to_action": {"type":"SIGN_UP","value":{"link": "http://fb.me/", "lead_gen_form_id":"'.$form_id.'"}}, 
                    "link_description": "Creative description 1", 
                    "video_id": "'.$videoId.'", 
                    "image_url": "https://scontent.fmaa12-2.fna.fbcdn.net/v/t15.5256-10/330484840_708583587423326_8975372160092761810_n.jpg?_nc_cat=110&ccb=1-7&_nc_sid=f2c4d5&_nc_ohc=CJxNLQG1fOUAX86s3qL&_nc_ht=scontent.fmaa12-2.fna&edm=APRAPSkEAAAA&oh=00_AfBe1_n-5MOyT7sOoPiZyw2cWZaOEmrvm8t0BuzkELVYTA&oe=63F0465A"
                }
            }',
            'access_token' => $access_token
        ];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        //d($res); exit;
        $creativeId = $res['id'];
    }

   

    $adSetN = 'Casa Ad Tg TN - '.date('d M');

    $post = [
        'name' => $adSetN,
        'adset_id' => $adSetId,
        'creative' => '{"creative_id": "'.$creativeId.'"}',
        'status' => 'PAUSED',
        'access_token' => $access_token
    ];

    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/ads";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    d($res);
    */