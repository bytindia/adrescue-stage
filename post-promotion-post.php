<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); 
 error_reporting(E_ALL ^ E_NOTICE);  
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
//error_log( "Hello, errors!" );

function curlPost($url, $post){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

include 'db.php';
$pg= 'facebook';
include 'casa/config.php';

$accId = '5656356187816184';
//$_POST['page_id'] = $_POST['user_id'] = 158034550097;
//$_POST['post_id'] = '158034550097_196957043325828';
$form_id = 853395315962636;
$insta_actor_id = '1261351683879935';
$daily_budget = 100;
/*
$target_age = '"age_max": 60,"age_min": 30';

$target_ext ='"flexible_spec": [{"interests": [{"id": "6003077797429","name": "Chennai"}]}]';

$target_freq ='"flexible_spec": [{"behaviors": [{"id": "6022788483583","name": "Frequent international travelers"}]}]';

$target_ci = '"flexible_spec": [{"interests": [{"id": "1434559786872259","name": "NoBroker.com"},{"id": "6002868978310","name": "InterContinental Hotels Group"},{"id": "6002948635355","name": "Godrej Group"},{"id": "6002962194246","name": "Swing trading"},{"id": "6002988237368","name": "Marriott International"},{"id": "6003072462582","name": "JW Marriott Hotels"},{"id": "6003102712240","name": "Indiabulls"},{"id": "6003104558317","name": "Day trading"},{"id": "6003126209645","name": "Stock exchange"},{"id": "6003174415534","name": "First-time buyer"},{"id": "6003175076849","name": "Four Seasons Hotels and Resorts"},{"id": "6003183450612","name": "Sheraton Hotels and Resorts"},{"id": "6003307244821","name": "Trulia"},{"id": "6003347800581","name": "Foreign exchange market"},{"id": "6003349860951","name": "Stock market"},{"id": "6003383552337","name": "Luxury Resorts"},{"id": "6003402867347","name": "Lodha Group"},{"id": "6003431489469","name": "Residential area"},{"id": "6003446239080","name": "Real estate investing"},{"id": "6003488127669","name": "99acres"},{"id": "6003595635263","name": "Hilton Hotels & Resorts"},{"id": "6003622523903","name": "Bombay Stock Exchange"},{"id": "6003715005316","name": "Luxury yacht"},{"id": "6003728466560","name": "BSE SENSEX"},{"id": "6003737798116","name": "Oberoi Realty"},{"id": "6003833310052","name": "Marriott Hotels & Resorts"},{"id": "6003909817136","name": "Zillow"},{"id": "6004052252096","name": "Nifty Fiftys"},{"id": "6005301103600","name": "Prestige Group"},{"id": "6005999968525","name": "Shapoorji Pallonji Group"},{"id": "6002973217774","name": "Barclays"},{"id": "6003340642996","name": "Morgan Stanley"},{"id": "6003472328263","name": "Merrill Lynch"},{"id": "6002969718768","name": "Oracle Corporation"},{"id": "6003341956023","name": "JPMorgan Chase"},{"id": "6003142740861","name": "Capgemini"},{"id": "6004050939096","name": "Tata Consultancy Services"},{"id": "6003579742642","name": "Goldman Sachs"},{"id": "6003294938911","name": "Wipro"},{"id": "6004050939096","name": "Tata Consultancy Services"},{"id": "6003278152399","name": "Ernst & Young"},{"id": "6003605021020","name": "Accenture"},{"id": "6003648979716","name": "KPMG"},{"id": "6003294938911","name": "Wipro"},{"id": "6003301729369","name": "Infosys"},{"id": "6003332604088","name": "Tech Mahindra"},{"id": "6003077797429","name": "Chennai"}]}]';
$target_CH = '"geo_locations":{"cities": [{"key":"1021534","radius":40, "distance_unit":"kilometer"}]}';

$locTarget = $target_CH;
*/
$_POST['page_id'] = 133323657231697;
$_POST['post_id'] = '133323657231697_699042675567883';
$page_id = $_POST['page_id'];
$targets = array('CI', 'Freq');
$date_mon = date('M_Y');
$campIds = array();
//d($_POST); //
if(isset($_POST['post_id'])) 
{
    $page_post_id = $_POST['post_id'];
   
    $q3 = "SELECT campIds, date_mon from post_promote_campaign where page_id='".$_POST['page_id']."' AND date_mon='".$date_mon."' limit 0,1";
    $r3 = mysqli_query($conn, $q3);
    if(mysqli_num_rows($r3)>0) 
    {
        $row3 = mysqli_fetch_assoc($r3);
        $campIds = explode(',',$row3['campIds']);
    }
    //d($campIds); exit;
    echo "SELECT client, d_budget, fb_pg, cust_aud, fb_acc, age_min, age_max, loc_reg, loc_city,loc_country,behaviour,interests,cust_aud,insta_act_id FROM post_promotion WHERE fb_pg='".$page_id."' AND  del_status=0"; 
    $sqlD=mysqli_query($conn, "SELECT client, d_budget, fb_pg, cust_aud, fb_acc, age_min, age_max, loc_reg, loc_city,loc_country,behaviour,interests,cust_aud,insta_act_id FROM post_promotion WHERE fb_pg='".$page_id."' AND  del_status=0");
    $z =1;
	while($Rdata=mysqli_fetch_array($sqlD)) {
        
        d($Rdata);
        $accId = $Rdata['fb_acc'];
        $daily_budget = $Rdata['d_budget'];

        
        $target_age = $geoLoc = $target_behaviour =  $target_loc = $target_interests = $geoTarg = $target_audience = ''; 

        if($Rdata['cust_aud']!='') { 
            $cust_aud = explode(',',$Rdata['cust_aud']);
           // $target_audience = '"custom_audiences":[{"id":'.$Rdata['cust_aud'].'}],';
            if(count($cust_aud)>0) { $target_audience = '"custom_audiences": [';  }
            $a=1;
            foreach($cust_aud as $key => $val){
                $target_audience .= '{"id":'.$val.'}';
                if($i<count($cust_aud)) { $target_audience .=','; }
                $a++;
            }
            if(count($cust_aud)>0) { $target_audience .= '],'; }
        }

        if($Rdata['age_min']!='') { 
            $target_age = '"age_max": '.$Rdata['age_max'].',"age_min": '.$Rdata['age_min'].','; 
        }

        if(isset($Rdata['loc_reg']) && $Rdata['loc_reg']!='a:0:{}' && $Rdata['loc_reg']!='') { 
            $loc_reg = unserialize($Rdata['loc_reg']); 
            if(count($loc_reg)>0) { $target_loc = '"geo_locations":{"regions": ['; $geoTarg = 1; }
            $i=1;
            foreach($loc_reg as $key => $val){
                $target_loc .= '{"key":"'.$key.'"}';
                if($i<count($loc_reg)) { $target_loc .=','; }
                $i++;
            }
            if(count($loc_reg)>0) { $target_loc .= ']},'; }
        } 
        else if(isset($Rdata['loc_city']) && $Rdata['loc_city']!='a:0:{}' && $Rdata['loc_city']!='') { 
            $loc_city = unserialize($Rdata['loc_city']); 
            if(count($loc_city)>0) { $target_loc = '"geo_locations":{"cities": ['; $geoTarg = 1; }
            $j=1;
            foreach($loc_city as $key => $val){
                $target_loc .= '{"key":"'.$key.'","radius": 25,"distance_unit": "mile"}';
                if($j<count($loc_city)) { $target_loc .=','; }
                $j++;
            }
            if(count($loc_city)>0) { $target_loc .= ']},'; }
        } 
        else if(isset($Rdata['loc_country']) && $Rdata['loc_country']!='a:0:{}' && $Rdata['loc_country']!='') { 
            $loc_country = unserialize($Rdata['loc_country']); 
            if(count($loc_country)>0) { $target_loc = '"geo_locations":{"countries": ['; $geoTarg = 1; }
            $m=1;
            foreach($loc_country as $key => $val){
                //$target_loc .= '{"key":"'.$key.'"},';
                $target_loc .= "'".$key."'";
                if($m<count($loc_country)) { $target_loc .=','; }
                $m++;
            }
            if(count($loc_country)>0) { $target_loc .= ']},'; }
        } 

        if(isset($Rdata['behaviour'])) { 
            $behaviour = unserialize($Rdata['behaviour']); 
            if(count($behaviour)>0) { $target_behaviour = '"flexible_spec": [{"behaviors": ['; $behTarg = 1; }
            $n=1;
            foreach($behaviour as $key => $val){
                $target_behaviour .= '{"id":"'.$key.'","name": "'. $val.'"}';
                if($n<count($behaviour)) { $target_behaviour .=','; }
                $n++;
            }
            if(count($behaviour)>0) { $target_behaviour .= ']}],'; }
        } 
        if(isset($Rdata['interests'])) { 
            $interests = unserialize($Rdata['interests']); 
            if(count($interests)>0) { $target_interests = '"flexible_spec": [{"interests": ['; $intTarg = 1; }
            $o=1;
            foreach($interests as $key => $val){
                $target_interests .= '{"id":"'.$key.'","name": "'. $val.'"}';
                if($o<count($interests)) { $target_interests .=','; }
                $o++;
            }
            if(count($interests)>0) { $target_interests .= ']}]'; }
        }


       // echo '{'.$target_age.''.$target_audience.''.$target_loc.''.$target_behaviour.''.$target_interests.'}'; exit;



        echo sizeof($campIds);
        if(sizeof($campIds)==0) {
            //Campaign
            //foreach($targets as $tarV) { echo 'y';
                
                //if($tarV=='CI') { $locTarget = $target_ci; } else { $locTarget = $target_freq; }
                
                $campN = 'PostEnge: '.date('M, Y');
                $post = [
                    'name' => $campN,
                    'objective' => 'POST_ENGAGEMENT',
                    'special_ad_categories'   => '[]',
                    'status' => 'PAUSED',
                    'access_token' => $access_token,
                    'targeting' => '{'.$target_age.''.$target_audience.''.$target_loc.''.$target_behaviour.''.$target_interests.'}',
                ];
                d($post);
                $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/campaigns";
                $req = curlPost($url,$post);
                $res = json_decode($req, true);  

                if(isset($res['id'])) {
                    $campIds[] = $res['id']; d($res);
                    echo 'Campaign <br>';
                } else { 
                    echo 'Failed: AdCreative creation!'; d($res); exit;
                }
           // } 
            if(sizeof($campIds)>0) {
                $InsSql_f = "INSERT INTO  post_promote_campaign (page_id, post_id, campIds, date_mon, del, created) VALUES ('".$_POST['page_id']."', '".$_POST['post_id']."', '".implode(",",$campIds)."', '".$date_mon."', 'No', now())"; 
                mysqli_query($conn, $InsSql_f) or die(mysqli_error());
            }
        }
        echo '{'.$target_loc.''.$target_behaviour.''.$target_interests.'}'; 
        //d($campIds); exit;

        

        //AdCreative
        //$page_post_id = '158034550097_201244782897054';
        $post = [
            'name' => $Rdata['client'].' Creative Sample',
            'object_type' => 'STATUS',
            'object_story_id' => $page_post_id,
            'access_token' => $access_token,
            "instagram_actor_id"=> '1495108597218859',
        ];
        //d($post);
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adcreatives";
        $req = curlPost($url,$post);
        $res = json_decode($req, true);  
       // d($res); exit;
        if(isset($res['id'])) {
            $creativeId = $res['id'];  d($res); error_log(print_r($res, true));
        } else { 
            $creativeId =''; d($res); error_log(print_r($res, true));
        } 
        //$creativeId = 23855031873380609;

        if($creativeId!='') {
        
            //foreach($targets as $tarV) {
                //if($tarV=='CI') { $locTarget = $target_ci; } else { $locTarget = $target_freq; }

                if(count($campIds)>0) {
                    foreach($campIds as $key => $cam_id) {

                        //if($key==0) { $locTarget = $target_ci; } else { $locTarget = $target_freq; }
                        $adSetId ='';
                        //AdSet
                        //$campId = $res['id'];
                        //AdSet Creation - CI
                        $adSetN = 'AdSet - '.date('d M');
                        $post = [
                            'name' => $adSetN,
                            'optimization_goal' => 'POST_ENGAGEMENT',
                            'billing_event' => 'IMPRESSIONS',
                            'campaign_id' => $cam_id,
                            'targeting' => '{'.$target_age.''.$target_audience.''.$target_loc.''.$target_behaviour.''.$target_interests.'}',
                            'status' => 'PAUSED',
                            'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
                            'daily_budget' => 100 * $daily_budget,
                            'access_token' => $access_token
                        ];
                        d($post);
                        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/adsets";
                        $req = curlPost($url,$post);
                        $res = json_decode($req, true);  
                        

                        if(isset($res['id'])) {
                            $adSetId = $res['id'];  d($res); error_log(print_r($res, true));
                        // echo 'Adset - '.$tarV.' - Open '.$tickIcon.' <a href="'.$adsetURL.''.$res['id'].'" target="_blank">View</a><br>';
                        } else {  error_log(print_r($res, true));
                            echo 'Failed: AdSet '; d($res); exit; 
                        }
                    

                        //Ad
                        if($adSetId!='') {
                            $adSetN = 'Ad - '.date('d M');
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
                            //echo 'ads:<br>'; d($res);
                            if(isset($res['id'])) {
                                echo 'Ad ';  d($res); error_log(print_r($res, true));
                                $InsSql_f = "INSERT INTO  post_promote_ads (uid, page_id, post_id, camp_id, date_mon, adset_id, ad_id, creativeId, created) VALUES ('".$_POST['user_id']."', '".$_POST['page_id']."', '".$_POST['post_id']."', '".$cam_id."', '".$date_mon."', '".$adSetId."', '".$res['id']."', '".$creativeId."', now())"; 
                                mysqli_query($conn, $InsSql_f) or die(mysqli_error());
                            } else {  error_log(print_r($res, true));
                                echo 'Failed: AdSet '; d($res); exit;
                            }
                        }
                    }
                }
            //}
        }
        $z++;
    }

}