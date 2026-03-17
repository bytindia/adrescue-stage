<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set('Asia/Calcutta');  
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Auth/session (mirror multi-client)
$_SESSION['uid']=2;
if(!isset($_SESSION['uid'])) {
    $pg = '../login.php';
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
    exit();
}

include '../db.php';
include 'overview-config.php'; // sets $access_token, $api_ver, and loads $fbAccN map

// Helpers
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$output = [];
function loopAdRep($url) {
    global $output;
    $requests = file_get_contents_curl($url);
    $fb_response = json_decode($requests,true);
    if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = array_merge($output, $fb_response['data']);
    } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = $fb_response['data']; 
    }
    if(isset($fb_response['paging']['next'])) {
        loopAdRep($fb_response['paging']['next']);
    } else { 
        return $output['data'] = $output; 
    }
}

// Simple CURL wrapper if not present
if (!function_exists('file_get_contents_curl')) {
    function file_get_contents_curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}

$resolveApiCache = [];
function getVideoIdFromShareAd($ad_id, $token, $api_ver_local) {
    global $resolveApiCache;
    $cacheKey = 'v:' . $ad_id;
    if(isset($resolveApiCache[$cacheKey])) return $resolveApiCache[$cacheKey];
    $url = "https://graph.facebook.com/{$api_ver_local}/{$ad_id}?fields=effective_object_story_id,creative{object_type}&access_token={$token}";
    $data = json_decode(file_get_contents_curl($url), true);
    if (empty($data['effective_object_story_id'])) { $resolveApiCache[$cacheKey] = null; return null; }
    $story_id = $data['effective_object_story_id'];
    $url2 = "https://graph.facebook.com/{$api_ver_local}/{$story_id}?fields=object_id,attachments{media_type,media},permalink_url&access_token={$token}";
    $data2 = json_decode(file_get_contents_curl($url2), true);
    if (!empty($data2['object_id'])) { $resolveApiCache[$cacheKey] = $data2['object_id']; return $data2['object_id']; }
    if (!empty($data2['attachments'][0]['media']['id'])) { $resolveApiCache[$cacheKey] = $data2['attachments'][0]['media']['id']; return $data2['attachments'][0]['media']['id']; }
    $resolveApiCache[$cacheKey] = null;
    return null;
}

$showReport = false;
$selectedClients = [];

if(isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedClients = isset($_POST['clients']) ? array_map('intval', $_POST['clients']) : [];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>AdRescue - Hook Rate % (Video Ads)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <link href="/casa/css/style.css" rel="stylesheet">
    <link href="/casa/style.css" rel="stylesheet">
    <style>
        .container { max-width: 1200px; }
        .table th, .table td { vertical-align: middle; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .loading-overlay.active { display: flex; }
        .loading-content { background: #fff; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }
        table td, table th { text-align: left !important; }
        .bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) { width: 100%; }
    </style>
<?php /* Build report server-side to avoid long JS work */ ?>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h4>Loading...</h4>
            <p>Calculating hook rate for active video ads...</p>
            <p><small>This may take a few moments depending on the number of clients selected.</small></p>
        </div>
    </div>
    <br><br>
<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <select class="selectpicker" multiple data-live-search="true" name="clients[]" title="Choose clients...">
                            <?php
                            $clientsQuery = mysqli_query($conn, "SELECT tbl_id, client_name FROM dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ORDER BY client_name");
                            while($client = mysqli_fetch_assoc($clientsQuery)) {
                                $selected = (in_array((int)$client['tbl_id'], $selectedClients)) ? 'selected' : '';
                                echo '<option value="'.(int)$client['tbl_id'].'" '.$selected.'>'.h($client['client_name']).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-end mt-3 mt-md-0">
                        <button type="submit" name="submit_report" class="btn btn-primary"><i class="fa fa-bolt"></i> Hook rate percentage</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if($showReport && !empty($selectedClients)): ?>
    <div class="card mt-4" style="margin-top: 30px !important;">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="resultTable">
                <thead>
                    <tr>
                        <th>SNo</th>
                        <th>Client</th>
                        <th>Ad Account</th>
                        <th>Acc ID</th>
                        <th>Campaign</th>
                        <th>AdSet</th>
                        <th>Ad</th>
                        <th>Hook Rate %</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sno = 0;
                    foreach($selectedClients as $clientId) {
                        $accRes = mysqli_query($conn, "SELECT client_name, fb_id FROM dashboard_accounts WHERE tbl_id=".(int)$clientId." AND uid='".$_SESSION['uid']."' AND delete_status=0 LIMIT 1");
                        $accRow = mysqli_fetch_assoc($accRes);
                        if(!$accRow) continue;
                        $clientName = $accRow['client_name'];
                        $fbIdsRaw = $accRow['fb_id'] ?? '';
                        $fbIds = array_filter(array_unique(array_map('trim', explode(',', $fbIdsRaw))));

                        foreach($fbIds as $fbId){
                            if($fbId==='') continue;

                            // 1) Strictly follow adv-targeting.php logic to derive ACTIVE ad ids
                            $url_ad = "https://graph.facebook.com/{$api_ver}/act_{$fbId}/ads?fields=id,effective_status,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token={$access_token}&limit=750";
                            $output = [];
                            loopAdRep($url_ad);  
                            $res_ad = $output;
                            $act_ads_camp_ids = $act_ads_adset_ids = $act_ids = [];
                            if(isset($res_ad['data']) && count($res_ad['data'])>0) {
                                foreach($res_ad['data'] as $vv) {
                                    if(($vv['effective_status'] ?? '')==='ACTIVE' && isset($vv['campaign_id'])){
                                        $act_ads_camp_ids[] = $vv['campaign_id'];
                                    }
                                }
                            }
                            $act_ads_camp_ids = array_unique($act_ads_camp_ids);

                            $url = "https://graph.facebook.com/{$api_ver}/act_{$fbId}/campaigns?fields=id,name,effective_status,end_time,adsets.limit(50){id,name,effective_status,end_time,ads.limit(50){id,effective_status}}&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&access_token={$access_token}&limit=750";
                            $output = [];
                            loopAdRep($url);  
                            $res = $output;
                            if(isset($res['data']) && count($res['data'])>0){ 
                                foreach($res['data'] as $v1) {
                                    $camp_act = 'y';
                                    if(isset($v1['end_time'])) {
                                        $end_time = new DateTime($v1['end_time']);
                                        $currentDateTime = new DateTime('now', $end_time->getTimezone());
                                        if($end_time < $currentDateTime) { $camp_act = 'n'; }
                                    }
                                    if(($v1['effective_status'] ?? '')==='ACTIVE' && in_array($v1['id'], $act_ads_camp_ids) && $camp_act==='y') {
                                        if(isset($v1['adsets']['data'])){
                                            foreach($v1['adsets']['data'] as $as_v) {
                                                $ads_act = 'n';
                                                if(($as_v['effective_status'] ?? '')==='ACTIVE'){
                                                    $endCheck = 'y';
                                                    if(isset($as_v['end_time'])) { 
                                                        $end_time = new DateTime($as_v['end_time']);
                                                        $currentDateTime = new DateTime('now', $end_time->getTimezone());
                                                        if($end_time < $currentDateTime) { $ads_act = 'n'; $endCheck='n'; }
                                                    }
                                                    if($endCheck==='y' && isset($as_v['ads']['data'])){
                                                        foreach($as_v['ads']['data'] as $ad_v) {
                                                            if(($ad_v['effective_status'] ?? '')==='ACTIVE'){
                                                                $ads_act = 'y';
                                                                $act_ids[] = $ad_v['id'];
                                                            }
                                                        }
                                                        if($ads_act==='y'){
                                                            $act_ads_adset_ids[] = $as_v['id'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $act_ads_adset_ids = array_values(array_unique($act_ads_adset_ids));
                            if(empty($act_ads_adset_ids) || empty($act_ids)) continue;

                            // Prepare name maps for later display
                            $adIdToCamp = [];
                            $adIdToAdset = [];
                            $adIdToName = [];
                            // resolve ad names in chunks (ids=)
                            $chunks = array_chunk($act_ids, 50);
                            foreach($chunks as $chunk){
                                $idsParam = implode(',', $chunk);
                                $aurl = "https://graph.facebook.com/{$api_ver}/?ids=".urlencode($idsParam)."&fields=id,name,campaign_id,adset_id&access_token={$access_token}";
                                $ares = json_decode(file_get_contents_curl($aurl), true);
                                if(is_array($ares)){
                                    foreach($ares as $id=>$row){
                                        if(isset($row['campaign_id'])) $adIdToCamp[$id] = $row['campaign_id'];
                                        if(isset($row['adset_id'])) $adIdToAdset[$id] = $row['adset_id'];
                                        if(isset($row['name'])) $adIdToName[$id] = $row['name'];
                                    }
                                }
                            }

                            // 2) Filter only VIDEO ads (handles reels, story, auto-assets)
                            $video_ad_ids = [];
                            foreach ($act_ids as $ad_id) {
                                $creative_url = "https://graph.facebook.com/{$api_ver}/{$ad_id}?fields=creative{object_type,video_id,image_url,effective_object_story_id,asset_feed_spec},effective_object_story_id&access_token={$access_token}";
                                $creative_json = file_get_contents_curl($creative_url);
                                $creative_data = json_decode($creative_json, true);
                                $creative = $creative_data['creative'] ?? [];

                                $isVideo = false;

                                // Case 1: Direct uploaded video ad
                                if (!empty($creative['video_id'])) $isVideo = true;

                                // Case 2: Object type indicates video
                                if (!empty($creative['object_type']) && in_array($creative['object_type'], ['VIDEO','INSTAGRAM_VIDEO','VIDEO_MOBILE'])) {
                                    $isVideo = true;
                                }

                                // Case 3: Auto-format (Multiple placement video variants)
                                if (!empty($creative['asset_feed_spec']['videos'])) {
                                    foreach ($creative['asset_feed_spec']['videos'] as $v) {
                                        if (!empty($v['video_id'])) { $isVideo = true; break; }
                                    }
                                }

                                // Case 4: Boosted post / reel / story (resolve video via story)
                                if (!$isVideo && !empty($creative['effective_object_story_id'])) {
                                    $story_id = $creative['effective_object_story_id'];
                                    $story_url = "https://graph.facebook.com/{$api_ver}/{$story_id}?fields=object_id,attachments{media_type,media}&access_token={$access_token}";
                                    $story_data = json_decode(file_get_contents_curl($story_url), true);
                                    if (!empty($story_data['attachments'][0]['media_type']) && $story_data['attachments'][0]['media_type'] === 'video') {
                                        $isVideo = true;
                                    }
                                }

                                if ($isVideo) { $video_ad_ids[] = $ad_id; }
                            }
                            if (empty($video_ad_ids)) continue;

                            // 3) Batch insights for hook rate metrics
                            $batch = [];
                            foreach ($video_ad_ids as $ad_id) {
                                $batch[] = [
                                    "method" => "GET",
                                    "relative_url" => $ad_id."/insights?fields=impressions,actions,video_play_actions,video_p25_watched_actions&action_breakdowns=action_type&limit=1"
                                ];
                            }
                            $url = "https://graph.facebook.com/{$api_ver}/";
                            $postData = [
                                "access_token" => $access_token,
                                "batch" => json_encode($batch)
                            ];
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $response = curl_exec($ch);
                            curl_close($ch);
                            $responses = json_decode($response, true);

                            // 4) Resolve names for campaign/adset via batched ids
                            $campIds = array_values(array_unique(array_filter(array_map(function($aid) use ($adIdToCamp){ return $adIdToCamp[$aid] ?? null; }, $video_ad_ids))));
                            $adsetIds = array_values(array_unique(array_filter(array_map(function($aid) use ($adIdToAdset){ return $adIdToAdset[$aid] ?? null; }, $video_ad_ids))));
                            $campNames = [];
                            $adsetNames = [];
                            if(!empty($campIds)){
                                $chunks = array_chunk($campIds, 50);
                                foreach($chunks as $chunk){
                                    $idsParam = implode(',', $chunk);
                                    $cUrl = "https://graph.facebook.com/{$api_ver}/?ids=".urlencode($idsParam)."&fields=id,name,account_id&access_token={$access_token}";
                                    $cRes = json_decode(file_get_contents_curl($cUrl), true);
                                    if(is_array($cRes)){
                                        foreach($cRes as $id=>$row){ $campNames[$id] = $row['name'] ?? $id; }
                                    }
                                }
                            }
                            if(!empty($adsetIds)){
                                $chunks = array_chunk($adsetIds, 50);
                                foreach($chunks as $chunk){
                                    $idsParam = implode(',', $chunk);
                                    $aUrl = "https://graph.facebook.com/{$api_ver}/?ids=".urlencode($idsParam)."&fields=id,name,account_id&access_token={$access_token}";
                                    $aRes = json_decode(file_get_contents_curl($aUrl), true);
                                    if(is_array($aRes)){
                                        foreach($aRes as $id=>$row){ $adsetNames[$id] = $row['name'] ?? $id; }
                                    }
                                }
                            }

                            // 5) Render rows
                            if (is_array($responses)) {
                                foreach ($responses as $index => $res) {
                                    $ad_id = $video_ad_ids[$index] ?? null;
                                    if(!$ad_id) continue;
                                    $body = isset($res['body']) ? json_decode($res['body'], true) : [];
                                    $impressions = (int)($body['data'][0]['impressions'] ?? 0);
                                    $three_sec = 0;
                                    // 1) actions
                                    if (!empty($body['data'][0]['actions'])) {
                                        foreach ($body['data'][0]['actions'] as $x) {
                                            if (($x['action_type'] ?? '') === 'video_view_3_sec') {
                                                $three_sec = (int)($x['value'] ?? 0);
                                                break;
                                            }
                                        }
                                    }
                                    // 2) video_play_actions
                                    if ($three_sec === 0 && !empty($body['data'][0]['video_play_actions'])) {
                                        foreach ($body['data'][0]['video_play_actions'] as $x) {
                                            if (($x['action_type'] ?? '') === 'video_view_3_sec') {
                                                $three_sec = (int)($x['value'] ?? 0);
                                                break;
                                            }
                                        }
                                    }
                                    // 3) fallback 25%
                                    if ($three_sec === 0 && !empty($body['data'][0]['video_p25_watched_actions'][0]['value'])) {
                                        $three_sec = (int)$body['data'][0]['video_p25_watched_actions'][0]['value'];
                                    }
                                    $hook = ($impressions > 0) ? round(($three_sec / $impressions) * 100, 2) : 0;

                                    $campId = $adIdToCamp[$ad_id] ?? '';
                                    $adsetId = $adIdToAdset[$ad_id] ?? '';
                                    $campName = $campNames[$campId] ?? $campId;
                                    $adsetName = $adsetNames[$adsetId] ?? $adsetId;
                                    $adName = $adIdToName[$ad_id] ?? $ad_id;
                                    $accIdDisp = $fbId;
                                    $accNameDisp = isset($fbAccN[$fbId]) ? $fbAccN[$fbId] : $fbId;

                                    $sno++;
                                    $link = 'https://adsmanager.facebook.com/adsmanager/manage/ads/edit/standalone?act='.rawurlencode($fbId).'&selected_ad_ids='.rawurlencode($ad_id).'&current_step=0';
                                    echo '<tr>';
                                    echo '<td>'.(int)$sno.'</td>';
                                    echo '<td>'.h($clientName).'</td>';
                                    echo '<td>'.h($accNameDisp).'</td>';
                                    echo '<td>'.h($accIdDisp).'</td>';
                                    echo '<td>'.h($campName).'</td>';
                                    echo '<td>'.h($adsetName).'</td>';
                                    echo '<td>'.h($adName).'</td>';
                                    echo '<td>'.h(number_format($hook, 2)).'%</td>';
                                    echo '<td><a href="'.h($link).'" target="_blank">Open</a></td>';
                                    echo '</tr>';
                                }
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(function(){
  try { $('.selectpicker').selectpicker({ size: 8, liveSearch: true, actionsBox: true }); } catch(e){}

  <?php if(isset($_POST['submit_report'])): ?>
  $('.loading-overlay').removeClass('active');
  <?php endif; ?>

  $('form').on('submit', function(){
    $('.loading-overlay').addClass('active');
    var selectedClients = $('.selectpicker').val();
    if(!selectedClients || selectedClients.length === 0){
      alert('Please select at least one client.');
      $('.loading-overlay').removeClass('active');
      return false;
    }
  });

  if ($('#resultTable').length > 0) {
    try {
      $('#resultTable').DataTable({
        "ordering": true,
        "lengthMenu": [25, 50, 100],
        "pageLength": 25,
        "scrollX": true,
        "autoWidth": false,
        "lengthChange": true,
        "searching": true,
        "info": true,
        "paging": true,
        "order": [],
        "language": {
          "search": "Search:",
          "lengthMenu": "Show _MENU_ entries",
          "info": "Showing _START_ to _END_ of _TOTAL_ entries",
          "infoEmpty": "Showing 0 to 0 of 0 entries",
          "infoFiltered": "(filtered from _MAX_ total entries)",
          "paginate": {"first": "First","last": "Last","next": "Next","previous": "Previous"}
        }
      });
    } catch (e) { console.error('Error initializing DataTable:', e); }
  }
});
</script>

</body>
</html>


