<?php session_start(); date_default_timezone_set('Asia/Kolkata');
include '../db.php';

if(isset($_POST['submit'])) {
  //d($_POST); 
  $pg_id = $_POST['fb_pg'];
  $acc_id = $_POST['ad_acc'];

}
//d($_GET); exit;
$accIds = array();
if(isset($_GET['fb']) && $_GET['fb']!='') {
  //echo "SELECT name, account_id FROM adAccounts WHERE uid='2' and account_id IN (".$_GET['fb'].") account_status=1"; 
  $sqlRev=mysqli_query($conn, "SELECT name, account_id FROM adAccounts WHERE uid='2' AND account_id IN (".$_GET['fb'].") AND account_status=1");
  while($sqlROW=mysqli_fetch_assoc($sqlRev))
  { 
    $accIds[$sqlROW['account_id']] = $sqlROW['name'];
  }

} else {
  $accIds = array(
    732236261026706 => 'RWD',
    1246709765756836 => 'Voora',
    1782824538572369 => 'Dugar',
    543552406049077 => 'EdenPark',
    597312711090623  => 'Jain Avolan',
    376583790733205 => 'Jain Antareeksh',
    615262527033827  => 'Vibrant Homes'
  );
}

//print_r($accIds); exit;

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 

$ac_status = array(
  1 => 'ACTIVE',
  2 => 'DISABLED',
  3 => 'UNSETTLED',
  7 => 'PENDING_RISK_REVIEW',
  8 => 'PENDING_SETTLEMENT',
  9 => 'IN_GRACE_PERIOD',
  100 => 'PENDING_CLOSURE',
  101 => 'CLOSED',
  201 => 'ANY_ACTIVE',
  202 => 'ANY_CLOSED'
);

$d = new DateTime('first day of this month');
$d2 = new DateTime('today');
$EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate = $d->format('Y-m-d').' 00:00:00';
//$EndDate = $d->format('Y-m-d').' 23:59:59';
$dtRange = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate)).'';

$d_3d = new DateTime('-4 day');
$d2_3d = new DateTime('-1 day');

$EndDate_3d = $d2_3d->format('Y-m-d').' 23:59:59'; // or your date as well
$SatrtDate_3d = $d_3d->format('Y-m-d').' 00:00:00';
$dtRange_3d = 'time_range[since]='.date("Y-m-d",strtotime($SatrtDate_3d)).'&time_range[until]='.date("Y-m-d", strtotime($EndDate_3d)).'';

$labels = array(
'Acoount Active?',
'Running campaings?',
'Running Lead Generation Campaigns?',
'Running WC (Lead) Campaigns?',
'Running Sale Campaigns?',
'Running Catalog Campaigns?',
'Running Remarketing Campaigns?',
'Running Lookalike Campaigns?',
'Targeting locations?',
'Placements (In Stream)?',
'Type of Ads? (video ads, image ads & etc)',
'Running IG DM ads?',
'What is the daily budget?',
'Running campaigns without leads? (Last 3 days)',
'Running campaigns with high cost leads? (Last 3 days)',
'Using custom questions in the lead forms?',
);

function fbReturn($url) {
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests,true);
	foreach ($fb_response['data'] as $key => $response) {			
			//echo 'ID: ' . $response->id . '<br />';
	}  
	if(isset($fb_response['paging']['next'])) {
		//echo $fb_response->paging->next;
		fbReturn($fb_response['paging']['next']);
	} else {
		//exit;
	}
}
function LeadGen($arr, $filt) { 
	$r = 0;
	if(is_array($arr) || is_object($arr) && count($arr)>0) {
		for($q=0; $q<count($arr); $q++) {
				if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
		}
	}
	return $r;
}
$obj_arr = array(
  'POST_ENGAGEMENT' => 'post_engagement', 
  'LINK_CLICKS' => 'link_click',
  'VIDEO_VIEWS' => 'video_view',
  'LEAD_GENERATION' => 'leadgen_grouped',
  'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
  'MESSAGES' => 'onsite_conversion.messaging_block',
  'OUTCOME_LEADS' => 'lead'
);

foreach ($accIds as $acc_id => $acc_val) 
{
    //Get Age & Status
    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$acc_id.'/?level=account&fields=name,age,created_time,account_status&access_token='.$access_token.'';
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    $acc_name = $fb_response['name'];
    if(isset($fb_response['account_status']) && $fb_response['account_status']==1) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = $ac_status[$fb_response['account_status']]; } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] = $ac_status[$fb_response['account_status']]; }
   


    //Get Campaigns Total
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/campaigns?fields=objective,id,status,effective_status,daily_budget,insights{actions}&filtering=[{'field':'campaign.impressions','operator':'GREATER_THAN','value':0}]&summary=total_count&access_token=".$access_token."&".$dtRange."&limit=750";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    $camp_obj =  $camp_ids = $camp_bud = array(); 
    foreach ($fb_response['data'] as $key => $res) 
    {
      $camp_obj[] = $res['objective'];
      $camp_ids[] = $res['id'];
      if(isset($res['daily_budget'])) { $camp_bud[$res['id']]=$res['daily_budget']; }
    }
    if(isset($fb_response['summary']['total_count']) && $fb_response['summary']['total_count']>0) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count(array_unique($camp_ids));  } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }

    if (in_array("OUTCOME_LEADS", $camp_obj) || in_array("LEAD_GENERATION", $camp_obj) ) { $rep_result[$acc_id][] = 'Yes'; $a = count(array_keys($camp_obj, "OUTCOME_LEADS")); $b = count(array_keys($camp_obj, "LEAD_GENERATION")); $rep_value[$acc_id][] = $a+$b; } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] = 0; }
    if (in_array("CONVERSIONS", $camp_obj) ) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count(array_keys($camp_obj, "CONVERSIONS")); } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }
    if (in_array("OUTCOME_SALES", $camp_obj) ) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count(array_keys($camp_obj, "OUTCOME_SALES")); } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }
    if (in_array("PRODUCT_CATALOG_SALES", $camp_obj) ) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count(array_keys($camp_obj, "PRODUCT_CATALOG_SALES")); } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }

    //Remarketing
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/customaudiences?fields=name,subtype,delivery_status,ads{campaign_id,effective_status,configured_status},sharing_status,operation_status&filtering=[{'field':'delivery_status.code','operator':'IN','value':['200']},{'field':'subtype','operator':'IN','value':['WEBSITE']}]&access_token=".$access_token."&".$dtRange."&limit=750";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    $ads_REM = $camp_REM = $result = array();
    foreach ($fb_response['data'] as $key => $res) 
    {
      $ads[] = $res['ads']['data'];
      foreach ($res['ads']['data'] as $k1 => $v1){
          if($v1['effective_status']=='ACTIVE' && $v1['configured_status']=='ACTIVE'){
            $ads_REM[] = $v1['id'];
            $camp_REM[] = $v1['campaign_id'];
          }
      }
    }
    $result = array_intersect(array_unique($camp_REM), array_unique($camp_ids));
    if(count($result)>0) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count(array_unique($ads_REM)).' ads / '.count($result).' campaigns'; } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }

    //LA
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/customaudiences?fields=name,subtype,delivery_status,ads{campaign_id,effective_status,configured_status},sharing_status,operation_status&filtering=[{'field':'delivery_status.code','operator':'IN','value':['200']},{'field':'subtype','operator':'IN','value':['LOOKALIKE']}]&access_token=".$access_token."&".$dtRange."&limit=750";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    $ads_LA = $camp_LA = $result = array();
    foreach ($fb_response['data'] as $key => $res) 
    {
      
      foreach ($res['ads']['data'] as $k1 => $v1){
          if($v1['effective_status']=='ACTIVE' && $v1['configured_status']=='ACTIVE'){
            $ads_LA[] = $v1['id'];
            $camp_LA[] = $v1['campaign_id'];
          }
      }
    }
    $result = array_intersect(array_unique($camp_LA), array_unique($camp_ids));
    if(count($result)>0) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count(array_unique($ads_LA)).' ads / '.count($result).' campaigns'; } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }

    //Location - targeting
    $request_url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/adsets?fields=targeting{geo_locations,publisher_platforms,device_platforms},daily_budget,campaign_id,effective_status,configured_status,destination_type,ads{configured_status,campaign_id,effective_status}&filtering=[{'field':'campaign.impressions','operator':'GREATER_THAN','value':0}]&access_token=".$access_token."&".$dtRange."&limit=750";
    $requests = file_get_contents_curl($request_url);
    $fb_response = json_decode($requests,true);
    $adset_Targ = $camp_Targ = $result = $adset_bud = array();
    $daily_bud_tot = $placement_auto = $placement_manual = $insta_dm = 0;
    foreach ($fb_response['data'] as $key => $res) 
    {
      if($res['effective_status']=='ACTIVE' && $res['configured_status']=='ACTIVE'){
        $daily_bud_tot += $res['daily_budget'];
        $pubPlat = $devicePlat = '';
        if(!isset($res['targeting']['publisher_platforms']) || (isset($res['targeting']['publisher_platforms']) && count($res['targeting']['publisher_platforms'])==4)) {
          $pubPlat = 1;
        } 
        if(!isset($res['targeting']['device_platforms']) || (isset($res['targeting']['device_platforms']) && count($res['targeting']['device_platforms'])==2)) {
          $devicePlat = 1;
        } 
        if($pubPlat==1 && $devicePlat == 1) { $placement_auto += 1; } else { $placement_manual += 1; }
        
        if($res['destination_type']=='INSTAGRAM_DIRECT') { $insta_dm += 1; }

      }
      foreach ($res['ads']['data'] as $k1 => $v1){
          if($v1['effective_status']=='ACTIVE' && $v1['configured_status']=='ACTIVE'){
            $adset_Targ[] = $v1['adset_id'];
            $camp_Targ[] = $v1['campaign_id'];
            if(isset($res['daily_budget'])) { $adset_bud[$res['id']]=$res['daily_budget']; }
          }
      }
    }

    $result = array_intersect(array_unique($camp_Targ), array_unique($camp_ids));
    if(count($result)>0) { $rep_result[$acc_id][] = 'Yes'; $rep_value[$acc_id][] = count($adset_Targ).' adsets / '.count($result).' campaigns'; } else { $rep_result[$acc_id][] = 'No'; $rep_value[$acc_id][] =0; }


    $totBudget = array_sum($adset_bud) + array_sum($camp_bud);

    if($insta_dm>0) { $rep_result[$acc_id][11] = 'Yes'; $rep_value[$acc_id][11] = round($insta_dm); } else { $rep_result[$acc_id][11] = 'No'; $rep_value[$acc_id][11] =0; }
    if($totBudget>100) { $rep_result[$acc_id][12] = 'Yes'; $rep_value[$acc_id][12] = round($totBudget/100); } else { $rep_result[$acc_id][12] = 'No'; $rep_value[$acc_id][12] =0; }

    $url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/insights?level=campaign&breakdowns=publisher_platform,device_platform,platform_position&fields=campaign_id,adset_id,adset_name,reach,impressions,spend,objective,actions&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&access_token=".$access_token."&".$dtRange."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true); 

    //d($res); 
    $instream_lead = 0; $instream_adset = $lead_tot =  $spend_tot = array();
    $fb_feed = $ig_feed = $oth_place = 0;
    foreach($res['data'] as $k => $val) {
      $lead = $cpl = 0;

      if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

      if($lead !='-' || $lead !=0) $cpl = @($val['spend']/$lead);

      //$reach_tot += $val['reach'];
    // $impr_tot += $val['impressions'];
      $lead_tot[] = $lead;
      $spend_tot[] = $val['spend'];
      //d($spend_tot); 
      $tbl_key2 = $val['publisher_platform'].'_#_'.$val['platform_position'].'_#_'.$val['device_platform'];
      if($val['publisher_platform']=='facebook' && $val['platform_position']=='instream_video' && $val['device_platform']=='mobile_app') {
        $instream_lead += $lead;
        //$instream_adset[] = $val['adset_id'];
      } 
      if($val['publisher_platform']=='facebook' && $val['platform_position']=='feed') {
        $fb_feed += $lead;
      } else if($val['publisher_platform']=='instagram' && $val['platform_position']=='feed') {
        $ig_feed += $lead;
      } else {
        $oth_place += $lead;
      }
    }

    if(round((($oth_place / array_sum($lead_tot))*100)) <= 20) { $rep_result[$acc_id][9] = 'Yes'; } else { $rep_result[$acc_id][9] = 'No'; }
    $rep_value[$acc_id][9] = 'FB Feed: '.round((($fb_feed / array_sum($lead_tot))*100)).'%, IG Feed: '.round((($ig_feed / array_sum($lead_tot))*100)).'%, Others: '.round((($oth_place / array_sum($lead_tot))*100)).'%';

    $url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/insights?level=campaign&fields=campaign_id,actions,spend,objective&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&access_token=".$access_token."&".$dtRange_3d."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true); 
    $lead_tot = $cmpIds = $spend_tot = array(); $zero_lead_camp = 0;
    foreach($res['data'] as $k => $val) {
      $lead = $cpl = 0;

      if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

      if($lead !='-' || $lead !=0) $cpl = @($val['spend']/$lead);
      $lead_tot[] = $lead;
      $spend_tot[] = $val['spend'];
      $cmpIds[] = $val['campaign_id'];
      if($lead==0){
        $zero_lead_camp += 1;
      }
      
    }
    if($zero_lead_camp==0) { $rep_result[$acc_id][13] = 'Yes'; $rep_value[$acc_id][13] = $zero_lead_camp.' Campaign. ('.array_sum($lead_tot).' leads / '.count($cmpIds).' Campaigns)'; } else { $rep_result[$acc_id][13] = 'No'; $rep_value[$acc_id][13] = $zero_lead_camp.' Campaigns. ('.array_sum($lead_tot).' leads / '.count($cmpIds).' Campaigns)'; }


    $url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/insights?level=campaign&fields=campaign_id,actions,spend,objective&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&access_token=".$access_token."&".$dtRange."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true); 
    $lead_tot = $cmpIds = $spend_tot = array(); $zero_lead_camp = 0;
    foreach($res['data'] as $k => $val) {
      $lead = $cpl = 0;

      if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

      if($lead !='-' || $lead !=0) $cpl = @($val['spend']/$lead);
      $lead_tot[] = $lead;
      $spend_tot[] = $val['spend'];
      $cmpIds[] = $val['campaign_id'];
    
      
    }
    $avgCost = round((@(array_sum($spend_tot)/array_sum($lead_tot))));
    //echo $avgCost ; 

    $url = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/insights?level=campaign&fields=campaign_id,actions,spend,objective&filtering=[{'field':'campaign.objective','operator':'IN','value':['LEAD_GENERATION','OUTCOME_LEADS']}]&access_token=".$access_token."&".$dtRange_3d."&limit=750";
    $req = file_get_contents_curl($url);
    $res = json_decode($req, true); 
    $lead_tot = $cmpIds2 = $spend_tot = array(); $zero_lead_camp = 0;
    foreach($res['data'] as $k => $val) {
      $lead = $cpl = 0;

      if(isset($val['actions'])) { if(array_key_exists($val['objective'], $obj_arr)) { $lead = LeadGen($val['actions'], $obj_arr[$val['objective']]); } }

      if($lead !='-' || $lead !=0) $cpl = @($val['spend']/$lead);

      if($avgCost<$cpl) {
        $lead_tot[] = $lead;
        $spend_tot[] = $val['spend'];
        $cmpIds2[] = $val['campaign_id'];
      }
    }

    if(count($cmpIds2)==0) { $rep_result[$acc_id][14] = 'Yes'; $rep_value[$acc_id][14] = count($cmpIds2).' / '.count($cmpIds).' Campaigns. (Monthly Avg. cost: '.$avgCost.')'; } else { $rep_result[$acc_id][14] = 'No'; $rep_value[$acc_id][14] = count($cmpIds2).' / '.count($cmpIds).' Campaigns. (Monthly Avg. Cost: '.$avgCost.')';  }
}
?>
<!doctype html>
<html>
<head>
    <title>Real Estate clients : Audit Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link href="css/multi-select.css" rel="stylesheet">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
   <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js" charset="utf-8"></script>
    <!-- Custom Theme Scripts -->
    
    <link href="//cdn.datatables.net/buttons/1.5.6/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="/web/pagination.css" rel="stylesheet">
    <link href="/assets/css/pagination.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <style>
      .hide_alert, #reportrange { display: none }
      tr td {
    text-align: center !important;
}
.fa { font-size: 18px; }
.icon_check, .green_txt { color: green; }
.icon_close, .red_txt { color:red; }
.percent {     color: #2d89d7;  font-size: 12px; font-style: italic; }
.font-italic b, strong {
    font-weight: 700;
    color: #13a911;
    font-size: 13px;
}
</style>
</head>
<body>
  <center><h3>Real Estate clients : Audit Report</h3></center>
  <center><span class="font-italic percent">Reports on: <b><?php echo date("d-m-Y", strtotime($SatrtDate)).'</b> to <b>'.date("d-m-Y", strtotime($EndDate)); ?></b>.<br> Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b></span></center>
    <table class="table">
      <thead>
        <th>SNo</th>
        <th>Label</th>
        <!--<th>Result</th>-->
        <?php foreach ($accIds as $acc_id => $acc_val) { ?>
        <th><?php echo $acc_val; ?></th>
        <?php } ?>
      </thead>
      <tbody>
        <?php $j=1; foreach ($labels as $key => $label) { ?>
        <tr>
          <td><?php echo $j; ?></td>
          <td class="text-center"><?php echo $label; ?></td>
          <?php foreach ($accIds as $acc_id => $acc_val) { ?>
            <td class="text-center <?php if(isset($rep_result[$acc_id][$key]) && $rep_result[$acc_id][$key]=='Yes') { echo 'green_txt'; } else { echo 'red_txt';} ?>"><?php if(isset($rep_value[$acc_id][$key])) { echo $rep_value[$acc_id][$key]; } ?></td>
          <?php } ?>
        </tr>
        <?php $j++; } ?>
      </tbody>
    </table>
    <center><a href="audit-fb.php" class="btn btn-success btn-lg">Audit Again</a></center>
</body>
</html>