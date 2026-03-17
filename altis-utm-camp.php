<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
//echo $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

function getLead($leadgen_id,$api_ver) {
    //fetch lead info from FB API
    $user_access_token = 'REDACTED_FB_TOKEN';

    $graph_url= 'https://graph.facebook.com/'.$api_ver.'/'.$leadgen_id.'?fields=campaign_name&access_token='.$user_access_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graph_url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch); 
    curl_close($ch);

    //work with the lead data
    $leaddata = json_decode($output);
    //print_r($leaddata->campaign_name);
    if(isset($leaddata->campaign_name)){
        return $leaddata->campaign_name;
    } else 
    {
        return '';
    }
}
include '/home/digitalb2k/public_html/salesninja.app/altis/db.php';
$calQty = $data_g = array();
//$sql = "SELECT leadgen_id FROM leads WHERE campN='' || campN IS null GROUP BY leadgen_id limit 0,1";
$sql = "SELECT phone FROM sales_ninja WHERE cId=12 AND source LIKE '%face%' AND phone!='' AND (cam_up IS null || cam_up='') order by id desc limit 0,500";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    //$ret = getLead($row['leadgen_id']);
    //echo "SELECT leadgen_id,campN FROM leads WHERE lead like '".$row['phone']."'"; 
    $ph = trim(substr($row['phone'], -10));
    $query = $conn->query("SELECT * FROM leads WHERE lead like '%".$ph."%' limit 0,1");
    $Y='';
    if(mysqli_num_rows($query)>0) 
    {
        $r = $query->fetch_assoc();
        if($r['campN']!=''){
            $campN = $r['campN'];
        } else {
            $campN = getLead($r['leadgen_id'],$api_ver);
        }
        if($campN!=''){
            $Y='Y';
            $conn->query("UPDATE sales_ninja SET campaign ='".mysqli_real_escape_string($conn, $campN)."', cam_up='Y' where phone like '%".$ph."%'");
            //echo $ph.'<br>';
        }
    }
    if($Y==''){
        $conn->query("UPDATE sales_ninja SET cam_up='Y' where phone like '%".$ph."%'");
    }
    //print_r($r['leadgen_id']);
}