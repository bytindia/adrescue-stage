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
    /*$campN = 'Byt Test '.rand(1,99);
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
    /*
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

    /*
    $hash = 'e77cb9b0fd81dcd274937757a9846899';
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

    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    $creativeId = $res['id'];
    //d($res['id']); //23853185368610609
    */
    
    $creativeId = 23853185368610609; 

    $adSetN = 'Byt Ad '.rand(1,99);

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