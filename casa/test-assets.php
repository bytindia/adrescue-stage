<iframe src="https://www.facebook.com/ads/api/preview_iframe.php?d=AQL1uCKpzWRLXkWwCu-nB0lVw4KLbunVmKTG401f5sRt8Pj00lcLsFvVMVQ-AMnah89aylg8P89A5qB6SF7606EwUSI2sF4bAWHIqSEwvNxVVsVqlUnNm-EQUP8aUPiHKOGgr_Pg7gSvNN4BjZbVJBUloKjsT6kBdUS8D1r_PvQs50fQY1xrpDV0BFKE2RwgUTE8WqznB1zSqyY4lZPg42h1fmKIHJhKhCFbpz4MOMHNj5vJYnfkYUWpgiDHFHE99B-JUXTb4YmggwVivo4Zp-CiIPaVSvX3gCdkWz-caqLDIVtorRPh4e29QnF9UFCJOjYIImPkuQDF6AwzHQV20K2cW_xOs_DdilElLmuD5gLWTaYi3-pwcRiYROH9XRVRdC8A2O85QitiJL-74kd9CRFkG2rPvTbtTC-MCAKjwdVOvCPx9wo4qw9S7HfaW_jHOno&t=AQLdk3LRME1Jm6_riWc" width="540" height="690" scrolling="yes" style="border: none;"></iframe>

<iframe src="https://www.facebook.com/ads/api/preview_iframe.php?d=AQLNjEKze6BkeQkTP-EEBilM4yAPU6htjelMZI2-Vm1Dmp3-a531bSZK-c6i4HsyYrWWJ0FOWJzWRmbMZ9B1PvPFrVtSiMNBJEwZhZECTKqHTtCxrH7zM1cvyoqBuxnJ4oEKCmOb3S_kWxdQG1w-bRQt1ngMQ5ScwA7tyOhXzi6UunHjNlmGDgtbdFX83gaVkCNSAQ2wbqssixMMvK04wQs2hfvH2XyfjjMFD6YeD3UwRiNc30yv6IM_WcwzfK93JXbCx6nUsfMQtn6WXh_E11VnN1pwSdbvEKowVY6Qy9ayCfZs3p0whRhELb13ryQ-Gioxbi8Io3oOl-6mGQUmiGQoL_EtLXmrlfAvHnSWi5C2cd5FvBaVGCcR_dxUm0mZc9sbGgmiyM8nRgJjQyvoZ-T4XyTp3V_8O6Bkz4rJF4pybPgYc1R3tbD71DbDwMNf8mI&t=AQIyKckNsebtskct6ac" width="335" height="450" scrolling="yes" style="border: none;"></iframe> <?php exit;
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);


function curlPost($url, $post){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

include '../db.php';
$pg='facebook';
include 'config.php';

$url = "https://graph.facebook.com/".$api_ver."/866116510072687/previews?access_token=".$access_token."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true);  
    d($res); exit;
    $fb_data[$k] = $res['data'];

    $accId = '5656356187816184';
    $page_id = 172141376273056;
    $form_id = 853395315962636;
    $insta_actor_id = 1261351683879935;


    $fileContent = $server_path.'' . realpath('casa.mp4', 'video/mp3');
        $post = [
            'name' => 'CasaVideo',
            'file_url' => 'https://adsninja.adrescue.in/casa/casa.mp4',
            'access_token' => $access_token
        ];

        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/advideos";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
        d($res); exit;
        $videoId = $res['id'];

        $hash = 'e77cb9b0fd81dcd274937757a9846899';
        $post = [
          'name' => 'Casa Creative '.date('d M'),
          "object_story_spec" => '{
              "page_id": "'.$page_id.'"
            }',
            "asset_feed_spec"=>'{
              "images": [
                {"url":"https://adsninja.adrescue.in/casa/uploads/1200x628.png", "adlabels":[{"name":"image1"}]}, 
                {"url":"https://adsninja.adrescue.in/casa/uploads/1080.png", "adlabels":[{"name":"image3"}]},
                {"url":"https://adsninja.adrescue.in/casa/casa.jpeg", "adlabels":[{"name":"image2"}]}
              ],
              "bodies": [
                {"adlabels": [{"name": "body1"}],"text": "message here"}
              ],
              "call_to_action_types": ["SIGN_UP"],
              "call_to_actions": [ {"type": "SIGN_UP","value": {"lead_gen_form_id": "'.$form_id.'"}}],
              "descriptions": [{"text": "description here"}],
              "link_urls": [ { "adlabels": [{"name": "link1"}], "website_url": "http://fb.me/","display_url": ""}],
              "ad_formats": ["SINGLE_IMAGE"],
              "titles": [{"text":"Link title 1 goes here", "adlabels":[{"name":"title1"}]}],
              "asset_customization_rules": [
                {
                  "customization_spec": {
                    "publisher_platforms": ["facebook","instagram","audience_network"],
                    "facebook_positions": ["facebook_reels","story"],
                    "instagram_positions": ["story","reels"],
                    "audience_network_positions": ["classic"]
                  },
                  "image_label": {"name": "image3"},
                  "body_label": { "name": "body1"}, 
                  "link_url_label": {"name": "link1"},
                  "title_label": {"name": "title1"},
                  "priority": 1
                },
                {
                  "customization_spec": {"publisher_platforms": ["facebook"],"facebook_positions": ["instant_article","search"]},
                  "image_label": {"name": "image2"},
                  "body_label": { "name": "body1"}, 
                  "link_url_label": {"name": "link1"},
                  "title_label": {"name": "title1"},
                  "priority": 2
                },
                {
                  "customization_spec": {},
                  "image_label": {"name": "image1"},
                  "body_label": { "name": "body1"}, 
                  "link_url_label": {"name": "link1"},
                  "title_label": {"name": "title1"},
                  "priority": 3
                }
              ],
              "optimization_type": "PLACEMENT",
              "additional_data": {
                "multi_share_end_card": false
              }
            }',
          "instagram_actor_id"=> "1261351683879935",
          'access_token' => $access_token
      ];

        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
       //
        d($res); exit;


        echo 'adcreatives:<br>'; d($res); //exit;
       

        $creativeId = $res['id'];
        
        echo $adSetN = 'Image - '.rand(0,99);
                $post = [
                    'name' => $adSetN,
                    'adset_id' => '23853249267600609',
                    'creative' => '{"creative_id": "'.$creativeId.'"}',
                    'status' => 'PAUSED',
                    'access_token' => $access_token
                ];

                $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/ads";
                $req = curlPost($url,$post);
                $res = json_decode($req, true);
                echo 'ads:<br>'; d($res);