<!-- Include Required Prerequisites -->
<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  

if(!isset($_GET['tbl_id']) || $_GET['tbl_id']=='') {
	$pg = 'login.php';
	$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	exit();
} else {
    $tbl_id = $_GET['tbl_id'];
}
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
?>

<Title>AdRescue - Meta Share Report</Title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
<link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
<script src="/vendors/jquery/dist/jquery.min.js"></script>
<script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="/vendors/moment/min/moment.min.js"></script>
<script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
<link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
<link href="/web/pagination.css" rel="stylesheet">
<link href="/assets/css/pagination.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/casa/css/style.css" />
<link rel="stylesheet" type="text/css" href="/casa/style.css" />
<style>
    .meta-share-table th, .meta-share-table td { padding: 10px 15px; }
    .meta-share-table th { background: #f5f5f5; font-weight: 600; }
    .meta-share-table th.table-header-title { text-align: center; font-size: 1.1em; }
    .meta-share-table .label-col,
    .meta-share-table td:first-child { min-width: 200px; text-align: left; }
    .meta-share-table .value-col { text-align: right; font-weight: 500; }
    /* Desktop view - large */
    #metaShareWrap.view-desktop .meta-share-table { font-size: 17px; }
    #metaShareWrap.view-desktop .meta-share-table .label-col,
    #metaShareWrap.view-desktop .meta-share-table td:first-child { min-width: 200px; }
    /* Mobile view - compact for sharing */
    #metaShareWrap.view-mobile .meta-share-table { font-size: 13px; }
    #metaShareWrap.view-mobile .meta-share-table th,
    #metaShareWrap.view-mobile .meta-share-table td { padding: 6px 10px; }
    #metaShareWrap.view-mobile .meta-share-table .label-col,
    #metaShareWrap.view-mobile .meta-share-table td:first-child { min-width: 140px; }
    #metaShareWrap.view-mobile { max-width: 360px; margin: 0 auto; }
    .view-toggle { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .view-toggle .btn-view { padding: 6px 12px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 4px; }
    .view-toggle .btn-view:hover { background: #f0f0f0; }
    .view-toggle .btn-view.active { background: #007bff; color: #fff; border-color: #007bff; }
    .table-controls { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
</style>
<?php 
if(isset($_GET['st']) && $_GET['st']!='') {
    $_SESSION['st'] = $_GET['st'];
    $_SESSION['en'] = $_GET['en'];
} else {
    $start = date('d/m/Y', strtotime('first day of this month'));
    $end = date('d/m/Y');
    $_SESSION['st'] = $start;
    $_SESSION['en'] = $end;
}

include '../db.php';
$pg = 'facebook';
include 'config.php';

function moneyFormatIndia($num) {
    $num = round($num);
    $explrestunits = "";
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3);
        $restunits = (strlen($restunits)%2 == 1) ? "0".$restunits : $restunits;
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].",";
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    if($thecash==0) { $thecash='-'; }
    return $thecash;
}

/**
 * Sum value for given action_type from actions array.
 * If $actionTypes is array, sums all matching types.
 */
function getActionSum($arr, $actionTypes) {
    $r = 0;
    if (!is_array($arr) || empty($arr)) return $r;
    $types = is_array($actionTypes) ? $actionTypes : array($actionTypes);
    foreach ($arr as $item) {
        if (isset($item['action_type']) && in_array($item['action_type'], $types) && isset($item['value'])) {
            $r += (int)$item['value'];
        }
    }
    return $r;
}

if(isset($_SESSION['st'])) { 
    $stDt = $_SESSION['st']; 
    $enDt = $_SESSION['en']; 
    $dtRange = $_SESSION['st'].' - '.$_SESSION['en']; 
    $dtRange1 = str_replace('/', '-', $_SESSION['st']); 
    $dtRange2 = str_replace('/', '-', $_SESSION['en']);
    $urlParam = '?tbl_id='.$tbl_id.'&st='.$stDt.'&en='.$enDt;
} else { 
    $d = new DateTime('first day of this month');
    $stDt = $d->format('d/m/Y');
    $enDt = date('d/m/Y');
    $dtRange1 = $d->format('Y-m-d'); 
    $dtRange2 = date('Y-m-d');
    $urlParam = '?tbl_id='.$tbl_id;
}

// Meta Ads API - fetch from all fb ad accounts and sum
$fb_data = array();
$fb_acc_ids = $fb_acc_ids ?? array();
if (!empty($fb_acc_ids)) {
    foreach($fb_acc_ids as $k2 => $val2) {
        $url = "https://graph.facebook.com/".$api_ver."/act_".$val2."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2))."&access_token=".$access_token."&limit=1750";
        $req = file_get_contents_curl($url);
        $res = json_decode($req, true);
        if(isset($res['data'])) {
            $fb_data[$k2] = $res['data'];
        }
    }
}

// Apply project/campaign filter and sum across all ad accounts
$totals = array(
    'spend' => 0,
    'form_leads' => 0,
    'whatsapp_leads' => 0,
    'cpl' => 0,
    'impressions' => 0,
    'reach' => 0,
    'likes' => 0,
    'comments' => 0,
    'shares' => 0,
    'phone_call_leads' => 0,
    'email_leads' => 0
);

// Meta API action type mappings (Core Delivery: spend, impressions, reach from API directly)
$formLeadTypes = array('lead');
$whatsappLeadTypes = array('onsite_conversion.total_messaging_connection');
$phoneCallTypes = array('onsite_conversion.call');
$emailLeadTypes = array('onsite_conversion.email');

$proj_names = $proj_names ?? array();
$name_contain = $name_contain ?? array();

foreach($fb_data as $accData) {
    foreach($accData as $campaign) {
        // Filter campaign-wise (same logic as multi-client.php)
        $campaign_name = $campaign['campaign_name'] ?? '';
        $should_include = true;

        if (!empty($proj_names) || !empty($name_contain)) {
            $should_include = false;

            if (!empty($proj_names)) {
                foreach ($proj_names as $proj_name) {
                    if (stripos($campaign_name, $proj_name) !== false) {
                        $should_include = true;
                        break;
                    }
                }
            }

            if (!empty($name_contain) && !$should_include) {
                $proj_key = contains_proj($campaign_name);
                if ($proj_key !== '') {
                    $should_include = true;
                }
            }

            if (!$should_include) continue;
        }

        $totals['spend'] += (float)($campaign['spend'] ?? 0);
        $totals['impressions'] += (int)($campaign['impressions'] ?? 0);
        $totals['reach'] += (int)($campaign['reach'] ?? 0);

        $actions = $campaign['actions'] ?? array();
        $totals['form_leads'] += getActionSum($actions, $formLeadTypes);
        $totals['whatsapp_leads'] += getActionSum($actions, $whatsappLeadTypes);
        $totals['likes'] += getActionSum($actions, array('like'));
        $totals['comments'] += getActionSum($actions, array('comment'));
        $totals['shares'] += getActionSum($actions, array('onsite_conversion.post_save'));
        $totals['phone_call_leads'] += getActionSum($actions, $phoneCallTypes);
        $totals['email_leads'] += getActionSum($actions, $emailLeadTypes);
    }
}

$totalLeads = $totals['form_leads'] + $totals['whatsapp_leads'] + $totals['phone_call_leads'] + $totals['email_leads'];
if ($totalLeads > 0 && $totals['spend'] >= 0) {
    $totals['cpl'] = round($totals['spend'] / $totalLeads);
} else {
    $totals['cpl'] = 0;
}
?>
<body>
<?php 
$projURL = 'meta-share.php';
include 'menu-top.php'; 
?>

<br>
<div class="report-header">
  <h4><?php echo $_SESSION['client_name']; ?> - Meta Share Report</h4>
  <span class="font-italic percent">
    Reports on: 
    <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.
    Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b>
  </span>
</div>

<div class="table-controls">
    <div class="view-toggle">
        <span style="margin-right: 8px; font-size: 14px;">View:</span>
        <button type="button" class="btn-view active" id="btnDesktop" title="Desktop view (large)"><i class="fa fa-desktop"></i> Desktop</button>
        <button type="button" class="btn-view" id="btnMobile" title="Mobile view (compact for sharing)"><i class="fa fa-mobile"></i> Mobile</button>
    </div>
    <button id="copyTableImage" class="btn btn-info btn-sm"><i class="fa fa-copy"></i> Copy Table as Image</button>
</div>
<div id="metaShareWrap" class="view-desktop">
<span id="metaShareTable">
<table class="table table-bordered meta-share-table">
    <thead>
        <tr>
            <th colspan="2" class="table-header-title"><?php echo htmlspecialchars($_SESSION['client_name']); ?><br><span class="dt-range"><i class="fa fa-calendar" style="color:white;"> <?php echo date("d-m-Y", strtotime($dtRange1)).' to '.date("d-m-Y", strtotime($dtRange2)); ?></span></th>
        </tr>
        <tr>
            <th class="label-col">Label</th>
            <th class="value-col">Value</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($totals['spend'] != 0): ?><tr><td>Spends</td><td>₹ <?php echo moneyFormatIndia($totals['spend']); ?></td></tr><?php endif; ?>
        <?php if ($totals['form_leads'] != 0): ?><tr><td>Form Leads</td><td><?php echo $totals['form_leads']; ?></td></tr><?php endif; ?>
        <?php if ($totals['whatsapp_leads'] != 0): ?><tr><td>WhatsApp Leads</td><td><?php echo $totals['whatsapp_leads']; ?></td></tr><?php endif; ?>
        <?php if ($totals['cpl'] != 0): ?><tr><td>CPL</td><td><?php echo moneyFormatIndia($totals['cpl']); ?></td></tr><?php endif; ?>
        <?php if ($totals['impressions'] != 0): ?><tr><td>Impressions</td><td><?php echo moneyFormatIndia($totals['impressions']); ?></td></tr><?php endif; ?>
        <?php if ($totals['reach'] != 0): ?><tr><td>Reach</td><td><?php echo moneyFormatIndia($totals['reach']); ?></td></tr><?php endif; ?>
        <?php if ($totals['likes'] != 0): ?><tr><td>Likes</td><td><?php echo $totals['likes']; ?></td></tr><?php endif; ?>
        <?php if ($totals['comments'] != 0): ?><tr><td>Comments</td><td><?php echo $totals['comments']; ?></td></tr><?php endif; ?>
        <?php if ($totals['shares'] != 0): ?><tr><td>Shares</td><td><?php echo $totals['shares']; ?></td></tr><?php endif; ?>
        <?php if ($totals['phone_call_leads'] != 0): ?><tr><td>Phone Call leads</td><td><?php echo $totals['phone_call_leads']; ?></td></tr><?php endif; ?>
        <?php if ($totals['email_leads'] != 0): ?><tr><td>Email leads</td><td><?php echo $totals['email_leads']; ?></td></tr><?php endif; ?>
    </tbody>
</table>
</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
function changeModalTitle(newTitle) {
  if (window.parent && window.parent.document) {
    var modalTitle = window.parent.document.querySelector('#iframeModalLabel');
    if (modalTitle) {
      modalTitle.textContent = newTitle;
    }
  }
}
changeModalTitle("Meta Share Report: <?php echo addslashes($_SESSION['client_name']); ?>");

$('#btnDesktop').on('click', function() {
    $('#metaShareWrap').removeClass('view-mobile').addClass('view-desktop');
    $('#btnDesktop').addClass('active');
    $('#btnMobile').removeClass('active');
});
$('#btnMobile').on('click', function() {
    $('#metaShareWrap').removeClass('view-desktop').addClass('view-mobile');
    $('#btnMobile').addClass('active');
    $('#btnDesktop').removeClass('active');
});

document.getElementById('copyTableImage').addEventListener('click', function() {
    var table = document.getElementById('metaShareWrap');
    var scale = $('#metaShareWrap').hasClass('view-mobile') ? 3 : 2;
    html2canvas(table, {backgroundColor: null, scale: scale}).then(function(canvas) {
        canvas.toBlob(function(blob) {
            if (navigator.clipboard && window.ClipboardItem) {
                var item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function() {
                    alert('Table image copied to clipboard!');
                }, function(err) {
                    alert('Failed to copy image: ' + err);
                });
            } else {
                var url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            }
        });
    });
});

$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    
    var d1 = '<?php echo $_SESSION['st']; ?>';
    var d2 = '<?php echo $_SESSION['en']; ?>';
    var start = moment(d1.split(' ')[0].split("/").reverse().join("-"));
    var end = moment(d2.split(' ')[0].split("/").reverse().join("-"));
    $('#reportrange span').html(d1 + ' - ' + d2);
    
    $('#reportrange').daterangepicker({
        dateLimit: { days: 1000 },
        showDropdowns: true,
        showWeekNumbers: true,
        timePicker: false,
        startDate: start,
        endDate: end,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
            'Last 7 Days': [moment().subtract('days', 6), moment()],
            'Last 30 Days': [moment().subtract('days', 29), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
        },
        opens: 'left',
        format: 'DD/MM/YYYY',
        separator: ' to ',
        locale: {
            applyLabel: 'Submit',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom Range',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        }
    }, function(start, end) {
        $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        window.location = 'loading.php?pg=meta-share.php&tbl_id=<?php echo $tbl_id; ?>&st=' + start.format('DD/MM/YYYY') + '&en=' + end.format('DD/MM/YYYY');
    });
});
</script>
</body>
