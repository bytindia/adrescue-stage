<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);



//$accId = '5656356187816184';
$page_id = 172141376273056;
$form_id = 853395315962636;
$insta_actor_id = '1261351683879935';

$campURL = 'https://business.facebook.com/adsmanager/manage/campaigns/edit?act='.$accId.'&selected_campaign_ids=';
$adsetURL = 'https://business.facebook.com/adsmanager/manage/adsets/edit?act='.$accId.'&selected_adset_ids=';
$adURL = 'https://business.facebook.com/adsmanager/manage/ads/edit?act='.$accId.'&selected_ad_ids=';

$tickIcon = '<img src="images/tick.png" height="15" />';
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



    
    //$daily_budget = 100;

    $target_age = '"age_max": 60,"age_min": 30';
    $target_ci = '"flexible_spec": [{"interests": [{"id": "1434559786872259","name": "NoBroker.com"},{"id": "6002868978310","name": "InterContinental Hotels Group"},{"id": "6002948635355","name": "Godrej Group"},{"id": "6002962194246","name": "Swing trading"},{"id": "6002988237368","name": "Marriott International"},{"id": "6003072462582","name": "JW Marriott Hotels"},{"id": "6003102712240","name": "Indiabulls"},{"id": "6003104558317","name": "Day trading"},{"id": "6003126209645","name": "Stock exchange"},{"id": "6003174415534","name": "First-time buyer"},{"id": "6003175076849","name": "Four Seasons Hotels and Resorts"},{"id": "6003183450612","name": "Sheraton Hotels and Resorts"},{"id": "6003307244821","name": "Trulia"},{"id": "6003347800581","name": "Foreign exchange market"},{"id": "6003349860951","name": "Stock market"},{"id": "6003383552337","name": "Luxury Resorts"},{"id": "6003402867347","name": "Lodha Group"},{"id": "6003431489469","name": "Residential area"},{"id": "6003446239080","name": "Real estate investing"},{"id": "6003488127669","name": "99acres"},{"id": "6003595635263","name": "Hilton Hotels & Resorts"},{"id": "6003622523903","name": "Bombay Stock Exchange"},{"id": "6003715005316","name": "Luxury yacht"},{"id": "6003728466560","name": "BSE SENSEX"},{"id": "6003737798116","name": "Oberoi Realty"},{"id": "6003833310052","name": "Marriott Hotels & Resorts"},{"id": "6003909817136","name": "Zillow"},{"id": "6004052252096","name": "Nifty Fiftys"},{"id": "6005301103600","name": "Prestige Group"},{"id": "6005999968525","name": "Shapoorji Pallonji Group"},{"id": "6002973217774","name": "Barclays"},{"id": "6003340642996","name": "Morgan Stanley"},{"id": "6003472328263","name": "Merrill Lynch"},{"id": "6002969718768","name": "Oracle Corporation"},{"id": "6003341956023","name": "JPMorgan Chase"},{"id": "6003142740861","name": "Capgemini"},{"id": "6004050939096","name": "Tata Consultancy Services"},{"id": "6003579742642","name": "Goldman Sachs"},{"id": "6003294938911","name": "Wipro"},{"id": "6004050939096","name": "Tata Consultancy Services"},{"id": "6003278152399","name": "Ernst & Young"},{"id": "6003605021020","name": "Accenture"},{"id": "6003648979716","name": "KPMG"},{"id": "6003294938911","name": "Wipro"},{"id": "6003301729369","name": "Infosys"},{"id": "6003332604088","name": "Tech Mahindra"},{"id": "6003077797429","name": "Chennai"}]}]';
    $target_ext ='"flexible_spec": [{"interests": [{"id": "6003077797429","name": "Chennai"}]}]';
    //$target_ext ='"interests": [{"id":6003077797429,"name":"Chennai"}]';
    $target_TN = '"geo_locations":{"regions": [{"key":"1744"}]}';
    $target_CH = '"geo_locations":{"cities": [{"key":"1021534","radius":40, "distance_unit":"kilometer"}]}';

$creativeId = $creativeId_vid = '';

$table_str ='<table style="width: 100%;">';
$api_ver = 'v16.0';
if($upImage!=='') {
    $table_str .='<tr><td colspan="3"><b><u>Image:</u></b></td></tr>';
        $post = [
            'name' => 'Casa Creative '.date('d M'),
            "object_story_spec" => '{
                "page_id": "'.$page_id.'"
              }',
              "asset_feed_spec"=>'{
                "images": [
                  {"url":"https://adsninja.adrescue.in/casa/uploads/'.$upImage.'", "adlabels":[{"name":"image1"}]}, 
                  {"url":"https://adsninja.adrescue.in/casa/uploads/'.$upImage3.'", "adlabels":[{"name":"image2"}]},
                  {"url":"https://adsninja.adrescue.in/casa/uploads/'.$upImage2.'", "adlabels":[{"name":"image3"}]}
                ],
                "bodies": [
                  {"adlabels": [{"name": "body1"}],"text": "'.$msg_ad.'"}
                ],
                "call_to_action_types": ["SIGN_UP"],
                "call_to_actions": [ {"type": "SIGN_UP","value": {"lead_gen_form_id": "'.$form_id.'"}}],
                "descriptions": [{"text": "'.$desc_ad.'"}],
                "link_urls": [ { "adlabels": [{"name": "link1"}], "website_url": "http://fb.me/","display_url": ""}],
                "ad_formats": ["SINGLE_IMAGE"],
                "titles": [{"text":"'.$title_ad.'", "adlabels":[{"name":"title1"}]}],
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
        if(isset($res['id'])) {
            $creativeId = $res['id'];
            $table_str .='<tr><td>Creative upload</td><td>'.$tickIcon.'</td><td></td></tr>';
        } else { 
            echo 'Failed: Creative upload! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res);  exit;
        }
}

if($upVideo!=='') {
    //echo '<br><b><u>Video:</u></b> <br>';
    $table_str .='<tr><td colspan="3"><b><u>Video:</u></b></td></tr>';
    //$fileContent = $server_path.'' . realpath('casa.mp4', 'video/mp3');
        $post = [
            'name' => 'CasaVideo',
            'file_url' => 'https://adsninja.adrescue.in/casa/uploads/'.$upVideo,
            'access_token' => $access_token
        ];

    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/advideos";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
   // $videoId = $res['id']; //548256773756565
    if(isset($res['id'])) {
        $videoId = $res['id'];
        //echo 'Video Upload '.$tickIcon.' <br>';
        $table_str .='<tr><td>Video Upload</td><td>'.$tickIcon.'</td><td></td></tr>';
    } else { 
        echo 'Failed: Video Upload! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res); exit;
    }
    

   $post = [
        'name' => 'Casa Video '.date('d-M-Y'),
        'object_story_spec' => '{
            "page_id": "'.$page_id.'",
            "video_data": {
                "call_to_action": {"type":"SIGN_UP","value":{"link": "http://fb.me/", "lead_gen_form_id":"'.$form_id.'"}}, 
                "title": "'.$title_ad.'",
                "link_description": "'.$desc_ad.'", 
                "message": "'.$msg_ad.'",
                "video_id": "'.$videoId.'", 
                "image_url": "https://adsninja.adrescue.in/casa/images/casa-logo.png"
            }
        }',
        'access_token' => $access_token
    ];
    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
    $req = curlPost($url,$post);
    $res = json_decode($req, true); 
    if(isset($res['id'])) {
        $creativeId_vid = $res['id'];
        //echo 'AdCreative '.$tickIcon.' <br>';
        $table_str .='<tr><td>AdCreative</td><td>'.$tickIcon.'</td><td></td></tr>';
    } else { 
        echo 'Failed: AdCreative! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res);  exit;
    }
}


$targets = array('TN', 'Chennai');
//$targets = array('TN');

if($creativeId!='') {
foreach($targets as $tarV) {
    //echo '<br><u>Target: '.$tarV.' (Image)</u><br>';
    $table_str .='<tr><td colspan="3"><u>Target: '.$tarV.' (Image)</u></td></tr>';
    if($tarV=='TN') { $locTarget =$target_TN.','.$target_ext; } else { $locTarget =$target_CH; }
        //Campaign Creation
        $campN = $tarV.' '.date('d-M-Y');
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
            echo 'Failed: AdCreative creation! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res); exit;
        }

        //$campId = 23853184945720609;

        if($campId!='') {
            $campId = $res['id'];
            //AdSet Creation - CI
            $adSetN = $tarV.' - Open - '.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.'}',
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
                echo 'Failed: AdSet '.$tarV.' - Open! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res); exit;
            }

            //AdSet Creation - CI
            $adSetN = $tarV.' - CI - '.date('d M');
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
                echo 'Failed: AdSet '.$tarV.' - CI! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res); exit;
            }

            //AdSet Creation - LA 1%
            $adSetN = $tarV.' - LA 1% -'.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.',"custom_audiences": [{"id":"'.$LA_aud_ids[$accId][0].'"}]}',
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
                echo 'Failed: AdSet '.$tarV.' - LA 1%! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; d($res); exit;
            }

            //AdSet Creation - LA 2%
            $adSetN = $tarV.' - LA 2% -'.date('d M');
            $post = [
                'name' => $adSetN,
                'optimization_goal' => 'LEAD_GENERATION',
                'billing_event' => 'IMPRESSIONS',
                'campaign_id' => $campId,
                'targeting' => '{'.$locTarget.','.$target_age.',"custom_audiences": [{"id":"'.$LA_aud_ids[$accId][1].'"}]}',
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
                //echo 'Adset - '.$tarV.' - LA 2% '.$tickIcon.' <a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br>';
                $table_str .='<tr><td>Adset - '.$tarV.' - LA 2% </td><td>'.$tickIcon.'</td><td><a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br></td></tr>';
            } else { 
                echo 'Failed: AdSet '.$tarV.' - LA 2%! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; exit;
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
                        //echo 'Failed: AdSet TN - LA 2%! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; exit;
                    }
                    $z++;
                }
    }
} 
}
if($creativeId_vid!='') {
    $creativeId = $creativeId_vid;
    include 'create-vid-inc.php';
}
$table_str .='</table>';

echo $table_str; 
echo '<br><br>'; 

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
        'status' => $adStatus,
        'access_token' => $access_token
    ];

    $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/ads";
    $req = curlPost($url,$post);
    $res = json_decode($req, true);  
    d($res);
    */