<?php

include '../db.php';
$pg='facebook';
include 'config.php';

function curlPost($url, $post){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

    $accId = '5656356187816184';
    $page_id = 172141376273056;
    $daily_budget = 100;

    //Campaign Creation
    /*$campN = 'Casa Target TN '.d('d-M-Y');
    $post = [
        'name' => $campN,
        'objective' => 'LEAD_GENERATION',
        'special_ad_categories'   => '[]',
        'status' => 'PAUSED',
        'access_token' => $access_token
    ];
    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/campaigns";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    $campId = $res['id']; // 23853184945720609
    */

    $campId = 23853184945720609;

    //AdSet Creation
    /*
    $adSetN = 'Byt Adset '.rand(1,99);
    $post = [
        'name' => $adSetN,
        'optimization_goal' => 'LEAD_GENERATION',
        'billing_event' => 'IMPRESSIONS',
        'campaign_id' => $campId,
        'targeting' => '{"geo_locations":{"countries":["IN"]}}',
        'status' => 'PAUSED',
        'promoted_object' => '{"page_id":"'.$page_id.'"}',
        'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
        'daily_budget' => 100 * $daily_budget,
        'access_token' => $access_token
    ];
    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    $adSetId = $res['id']; // 23853185081750609;
    */

    $adSetId = 23853185081750609;
    /* //Image upload
    if (function_exists('curl_file_create')) {
        $fileContent = curl_file_create("casa.jpeg", 'image/jpeg');
    } else {
        $fileContent = '@' . realpath("casa.jpeg", 'image/jpeg');
    }

    $post = [
        'filename' => $fileContent,
        'access_token' => $access_token
    ];

    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adimages";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  

    d($res['images']['casa.jpeg']['hash']); //e77cb9b0fd81dcd274937757a9846899
    */

    //Video Upload
    /* 
    if (function_exists('curl_file_create')) {
        $fileContent = curl_file_create("casa.mp4", 'video/mp4');
    } else {
        $fileContent = '@' . realpath("casa.mp4", 'video/mp4');
    }

    $post = [
        'name' => 'CasaVideo',
        'source' => $fileContent,
        'access_token' => $access_token
    ];

    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/advideos";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    $videoId = $res['id']; //548256773756565
    d($res); exit; */
    $videoId =  548256773756565;
    $hash = 'e77cb9b0fd81dcd274937757a9846899';
    $form_id = 853395315962636;
    //Image creative
    $post = [
        'name' => 'Casa Creative Sample',
        'object_story_spec' => '{
            "page_id": "'.$page_id.'",
            "link_data": {
              "image_hash": "'.$hash.'",
              "link": "https://facebook.com/'.$page_id.'",
              "message": "try it out"
            }
          }',
        'access_token' => $access_token
    ];
    /* // Video creative
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
    */
    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    //d($res); exit;
    $creativeId = $res['id'];
    //d($res['id']); //23853185368610609
    //d($res['id']);

    //$creativeId = 23853185368610609; 

    echo $adSetN = 'Byt Ad '.rand(1,99);

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