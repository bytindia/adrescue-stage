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

// Helpers (from management-cron.php)


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

// Form handling
$showReport = false;
$selectedClients = [];
$rows = [];

if(isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedClients = isset($_POST['clients']) ? array_map('intval', $_POST['clients']) : [];
}

// UI helpers
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

?>

<!DOCTYPE html>
<html>
<head>
    <title>AdRescue - Advanced Targeting Check</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <!-- DataTables -->
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <link href="/casa/css/style.css" rel="stylesheet">
    <link href="/casa/style.css" rel="stylesheet">
    
    <style>
        .container { max-width: 1200px; }
        .table th, .table td { vertical-align: middle; }
        /* Loading overlay styles (from multi-client) */
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .loading-overlay.active { display: flex; }
        .loading-content { background: #fff; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }
        table td, table th { text-align: left !important; }
        .bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) { width: 100%; }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h4>Loading...</h4>
            <p>Fetching data from Meta APIs...</p>
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
                        <button type="submit" name="submit_report" class="btn btn-primary"><i class="fa fa-search"></i> Similar Audience Ads</button>
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
                        <th>Client</th>
                        <th>Acc ID</th>
                        <th>Acc Name</th>
                        <th>Adset Name</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($selectedClients as $clientId) {
                        $accRes = mysqli_query($conn, "SELECT client_name, fb_id FROM dashboard_accounts WHERE tbl_id=".(int)$clientId." AND uid='".$_SESSION['uid']."' AND delete_status=0 LIMIT 1");
                        $accRow = mysqli_fetch_assoc($accRes);
                        if(!$accRow) continue;
                        $clientName = $accRow['client_name'];
                        $fbIdsRaw = $accRow['fb_id'] ?? '';
                        $fbIds = array_filter(array_unique(array_map('trim', explode(',', $fbIdsRaw))));
                        foreach($fbIds as $fbId){
                            if($fbId==='') continue;
                            // 1) Build ACTIVE adset id list using management-cron logic
                            $url_ad = "https://graph.facebook.com/{$api_ver}/act_{$fbId}/ads?fields=id,effective_status,campaign_id,adset_id&filtering=[{'field':'ad.effective_status','operator':'IN','value':['ACTIVE']}]&access_token={$access_token}&limit=750";
                            $output = [];
                            loopAdRep($url_ad);  
                            $res_ad = $output;
                            $act_ads_camp_ids = $act_ads_adset_ids = [];
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
                            if(empty($act_ads_adset_ids)) continue;

                            // 2) Query targeting for those adsets in chunks
                            $chunks = array_chunk($act_ads_adset_ids, 50);
                            foreach($chunks as $chunk){
                                $idsParam = implode(',', $chunk);
                                // Using ids= to batch
                                $turl = "https://graph.facebook.com/{$api_ver}/?ids=".urlencode($idsParam)."&fields=id,name,account_id,effective_status,targeting{targeting_optimization}&access_token={$access_token}";
                                $tres = json_decode(file_get_contents_curl($turl), true);
                                if(!is_array($tres)) continue;
                                foreach($tres as $adsetId => $item){
                                    if(!is_array($item)) continue;
                                    $eff = $item['effective_status'] ?? '';
                                    $opt = $item['targeting']['targeting_optimization'] ?? '';
                                    if($eff==='ACTIVE' && $opt==='expansion_all'){
                                        $accId = $item['account_id'] ?? '';
                                        $accName = $fbAccN[$accId] ?? $accId;
                                        $adsetName = $item['name'] ?? $adsetId;
                                        $link = 'https://adsmanager.facebook.com/adsmanager/manage/adsets/edit/standalone?act='.rawurlencode($accId).'&selected_adset_ids='.rawurlencode($adsetId).'&current_step=0';
                                        echo '<tr>';
                                        echo '<td>'.h($clientName).'</td>';
                                        echo '<td>'.h($accId).'</td>';
                                        echo '<td>'.h($accName).'</td>';
                                        echo '<td>'.h($adsetName).'</td>';
                                        echo '<td><a href="'.h($link).'" target="_blank">Open</a></td>';
                                        echo '</tr>';
                                    }
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

   // On page reload after form submission, hide overlay quickly
   <?php if(isset($_POST['submit_report'])): ?>
   $('.loading-overlay').removeClass('active');
   <?php endif; ?>

   // Show overlay when form is submitted and validate selection
   $('form').on('submit', function(){
     $('.loading-overlay').addClass('active');
     var selectedClients = $('.selectpicker').val();
     if(!selectedClients || selectedClients.length === 0){
       alert('Please select at least one client.');
       $('.loading-overlay').removeClass('active');
       return false;
     }
   });

   // Initialize DataTable only if table exists
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
           "paginate": {
             "first": "First",
             "last": "Last",
             "next": "Next",
             "previous": "Previous"
           }
         }
       });
       console.log('DataTable initialized successfully');
     } catch (e) {
       console.error('Error initializing DataTable:', e);
     }
   }
 });
 </script>

</body>
</html>


