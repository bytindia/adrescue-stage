<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Check if user is logged in
$_SESSION['uid']=2;
if(!isset($_SESSION['uid'])) {
    $pg = '../login.php';
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
    exit();
}

include '../db.php';
include 'overview-config.php';
include 'google-campaigns.php';

// Set default date range if not set
if(!isset($_SESSION['stDt'])) {
    $start = date('m/d/Y',strtotime('first day of this month'));
    $end = date('m/d/Y');
    $_SESSION['stDt'] = $start;
    $_SESSION['enDt'] = $end;
}

// Handle form submission
$showReport = false;
$selectedClients = [];
$reportData = [];

if(isset($_POST['submit_report'])) {
    $showReport = true;
    $selectedClients = isset($_POST['clients']) ? $_POST['clients'] : [];
    $stDt = $_POST['start_date'];
    $enDt = $_POST['end_date'];
    
    // Store selections in session for persistence
    $_SESSION['stDt'] = $stDt;
    $_SESSION['enDt'] = $enDt;
    $_SESSION['selected_clients'] = $selectedClients;
    
    // Convert date format for API calls
    $dtRange1 = date("Y-m-d", strtotime($stDt));
    $dtRange2 = date("Y-m-d", strtotime($enDt));
    
    // Get data for each selected client
    foreach($selectedClients as $clientId) {
        // Get client details using the correct query structure
       // echo "SELECT da.tbl_id, da.client_name, da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain, br.total_budget FROM dashboard_accounts da LEFT JOIN budget_reminder br ON da.bud_id = br.tbl_id WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $clientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0"; 
        $cirRes = mysqli_query($conn, "SELECT da.tbl_id, da.client_name, da.fb_id, da.g_id, da.in_id, da.ta_id, da.proj_name, da.name_contain, br.total_budget FROM dashboard_accounts da LEFT JOIN budget_reminder br ON da.bud_id = br.tbl_id WHERE da.tbl_id = '".mysqli_real_escape_string($conn, $clientId)."' AND da.uid='".$_SESSION['uid']."' AND da.delete_status=0");
        $row = mysqli_fetch_assoc($cirRes);
        
        if($row) {
            $reportData[$clientId] = [
                'client_name' => $row['client_name'],
                'meta_spend' => 0,
                'meta_leads' => 0,
                'google_spend' => 0,
                'google_leads' => 0
            ];
            //d($row); exit;
            // Get account IDs
            $fb_acc_ids = array_filter(explode(',', $row['fb_id']));
            $g_acc_ids = array_filter(explode(',', $row['g_id']));
            $ta_acc_ids = array_filter(explode(',', $row['ta_id']));
            $in_acc_ids = array_filter(explode(',', $row['in_id']));
            
            // Get project names and name contains
            $proj_names = $name_contain = [];
            if($row['proj_name'] != '') { 
                $proj_names = array_filter(unserialize($row['proj_name'])); 
            }
            if($row['name_contain'] != '') { 
                $name_contain = array_filter(unserialize($row['name_contain'])); 
            }
            
            // Get Meta data
            if(!empty($fb_acc_ids)) {
                // Meta API call
                $obj_arr = array(
                    'POST_ENGAGEMENT' => 'post_engagement', 
                    'LINK_CLICKS' => 'link_click',
                    'VIDEO_VIEWS' => 'video_view',
                    'LEAD_GENERATION' => 'lead', 
                    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
                    'MESSAGES' => 'onsite_conversion.messaging_block',
                    'OUTCOME_LEADS' => 'lead'
                );
                
                $fb_data = [];
                foreach($fb_acc_ids as $fb_id) {
                     $url = "https://graph.facebook.com/".$api_ver."/act_".$fb_id."/insights?level=campaign&fields=campaign_id,campaign_name,adset_id,adset_name,reach,impressions,spend,objective,actions&time_range[since]=".$dtRange1."&time_range[until]=".$dtRange2."&access_token=".$access_token."&limit=1750";
                    $req = file_get_contents_curl($url);
                    $res = json_decode($req, true);  
                    //d($res);
                    if(isset($res['data'])){
                        $fb_data[] = $res['data'];
                    }
                }
                
                // Process Meta data with filtering
                $meta_spend = 0;
                $meta_leads = 0;
                foreach($fb_data as $data) {
                    foreach($data as $campaign) {
                        // Apply filtering based on project names and name contains
                        $campaign_name = $campaign['campaign_name'];
                        $should_include = true;
                        
                        // Check if campaign should be included based on filtering
                        if(!empty($proj_names) || !empty($name_contain)) {
                            $should_include = false;
                            
                            // Check project names
                            if(!empty($proj_names)) {
                                foreach($proj_names as $proj_name) {
                                    if(stripos($campaign_name, $proj_name) !== false) {
                                        $should_include = true;
                                        break;
                                    }
                                }
                            }
                            
                            // Check name contains
                            if(!empty($name_contain) && !$should_include) {
                                $proj_key = contains_proj($campaign_name);
                                if($proj_key !== '') {
                                    $should_include = true;
                                }
                            }
                        }
                        
                        if($should_include) {
                            $meta_spend += $campaign['spend'];
                            
                            if(isset($campaign['actions'])) {
                                if(array_key_exists($campaign['objective'], $obj_arr)) {
                                    $leads = LeadGen($campaign['actions'], $obj_arr[$campaign['objective']]);
                                    $meta_leads += $leads;
                                }
                            }
                        }
                    }
                }
                
                $reportData[$clientId]['meta_spend'] = $meta_spend;
                $reportData[$clientId]['meta_leads'] = $meta_leads;
            }
            
            // Get Google data
            if(!empty($g_acc_ids)) {
                $google_spend = 0;
                $google_leads = 0;
                
                foreach($g_acc_ids as $g_id) {
                    $adAccounts = [$g_id];
                    $g_data = GetCampaignsFromMultipleAccounts::main($g_refresh_token, $g_mcc, $adAccounts, $dtRange1, $dtRange2, $extQry='');
                    //d($g_data); exit;
                    foreach($g_data as $campaign) {
                        // Apply filtering for Google campaigns too
                        $campaign_name = $campaign['camp_name'];
                        $should_include = true;
                        
                        // Check if campaign should be included based on filtering
                        if(!empty($proj_names) || !empty($name_contain)) {
                            $should_include = false;
                            
                            // Check project names
                            if(!empty($proj_names)) {
                                foreach($proj_names as $proj_name) {
                                    if(stripos($campaign_name, $proj_name) !== false) {
                                        $should_include = true;
                                        break;
                                    }
                                }
                            }
                            
                            // Check name contains
                            if(!empty($name_contain) && !$should_include) {
                                $proj_key = contains_proj($campaign_name);
                                if($proj_key !== '') {
                                    $should_include = true;
                                }
                            }
                        }
                        
                        if($should_include) {
                            $google_spend += $campaign['cost'];
                            $google_leads += $campaign['conv'];
                        }
                    }
                }
                
                $reportData[$clientId]['google_spend'] = $google_spend;
                $reportData[$clientId]['google_leads'] = $google_leads;
            }
        }
    }
}

function moneyFormatIndia($num) {
    $num = round($num);
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3);
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits;
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>AdRescue - Multi-Client Ads Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="/casa/css/style.css" rel="stylesheet">
    <link href="/casa/style.css" rel="stylesheet">
    <link href="/css/table.css" rel="stylesheet">
    <!-- Bootstrap-select CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    
    <!-- jQuery first -->
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    
    <!-- Moment.js -->
    <script src="/vendors/moment/min/moment.min.js"></script>
    
    <!-- Date Range Picker -->
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    
    <!-- DataTables -->
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    
    <!-- Bootstrap-select JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    
    <style>
        .container { max-width: 1200px; }
        .form-group { margin-bottom: 20px; }
        .table th, .table td { text-align: center; vertical-align: middle; }
        .table th { background-color: #f8f9fa; }
        .total-row { background-color: #bedbff; font-weight: bold; }
        .client-select { max-height: 200px; overflow-y: auto; }
        .date-range-container { margin: 20px 0; }
        .report-container { margin-top: 30px; }
        .btn-generate { margin-top: 20px; }
        
        /* Change all green text to black */
        label strong, .form-group label strong { color: #000 !important; }
        .card-header h5 { color: #000 !important; }
        h3 { color: #000 !important; }
        
        /* Loading overlay styles */
      .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    display: none; /* hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex; /* flex centering when active */
}

.loading-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

        
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h4>Loading...</h4>
            <p>Fetching data from Meta and Google APIs...</p>
            <p><small>This may take a few moments depending on the number of clients selected.</small></p>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                
                
                <!-- Form Section -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row align-items-end">
                                <!-- Client Selection -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Select Clients:</strong></label>
                                        <select class="selectpicker" multiple data-live-search="true" name="clients[]" title="Choose clients...">
                                            <?php
                                            $clientsQuery = mysqli_query($conn, "SELECT tbl_id, client_name FROM dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ORDER BY client_name");
                                            while($client = mysqli_fetch_assoc($clientsQuery)) {
                                                $selected = '';
                                                if(isset($_SESSION['selected_clients']) && in_array($client['tbl_id'], $_SESSION['selected_clients'])) {
                                                    $selected = 'selected';
                                                } elseif(isset($_POST['clients']) && in_array($client['tbl_id'], $_POST['clients'])) {
                                                    $selected = 'selected';
                                                }
                                                echo '<option value="'.$client['tbl_id'].'" '.$selected.'>'.$client['client_name'].'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Date Range -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        
                                        <div id="reportrange" style="background: #fff; cursor: pointer; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span id="dateRangeText"><?php echo $_SESSION['stDt'].' - '.$_SESSION['enDt']; ?></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                        <input type="hidden" name="start_date" id="start_date" value="<?php echo $_SESSION['stDt']; ?>">
                                        <input type="hidden" name="end_date" id="end_date" value="<?php echo $_SESSION['enDt']; ?>">
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        
                                        <button type="submit" name="submit_report" class="btn btn-primary btn-block">
                                            <i class="fa fa-search"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Report Section -->
                <?php if($showReport && !empty($reportData)): ?>
                <div class="report-container">
                    <div class="card">
                        <div class="card-header text-right">
                           <small class="text-muted">Reporting time: <?php echo date("d-m-Y, h:i a"); ?></small>
                        </div>
                        <div class="card-body">
                            <div id="reportTable">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="vertical-align: middle;">
                                                <div style="font-weight: normal;">
                                                    <i class="fa fa-calendar" style="color:white; margin-right:8px;"></i> <?php echo date("d-m-Y", strtotime($dtRange1)).' ~ '.date("d-m-Y", strtotime($dtRange2)); ?>
                                                </div>
                                            </th>
                                            <th colspan="3">Meta</th>
                                            <th colspan="3">Google</th>
                                            <th colspan="3">Total</th>
                                        </tr>
                                        <tr>
                                            <th>Client</th>
                                            <th>Spends</th>
                                            <th>Leads</th>
                                            <th>CPL</th>
                                            <th>Spends</th>
                                            <th>Leads</th>
                                            <th>CPL</th>
                                            <th>Spends</th>
                                            <th>Leads</th>
                                            <th>CPL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $grand_meta_spend = $grand_google_spend = $grand_meta_leads = $grand_google_leads = 0;
                                        
                                        foreach($reportData as $clientId => $data):
                                            $meta_spend = $data['meta_spend'];
                                            $meta_leads = $data['meta_leads'];
                                            $google_spend = $data['google_spend'];
                                            $google_leads = $data['google_leads'];
                                            
                                            $total_spend = $meta_spend + $google_spend;
                                            $total_leads = $meta_leads + $google_leads;
                                            
                                            $meta_cpl = ($meta_leads > 0) ? round($meta_spend / $meta_leads) : 0;
                                            $google_cpl = ($google_leads > 0) ? round($google_spend / $google_leads) : 0;
                                            $total_cpl = ($total_leads > 0) ? round($total_spend / $total_leads) : 0;
                                            
                                            // Accumulate totals
                                            $grand_meta_spend += $meta_spend;
                                            $grand_google_spend += $google_spend;
                                            $grand_meta_leads += $meta_leads;
                                            $grand_google_leads += $google_leads;
                                        ?>
                                        <tr>
                                            <td style="text-align:center;"><b><?php echo $data['client_name']; ?></b></td>
                                            <td><?php echo moneyFormatIndia($meta_spend); ?></td>
                                            <td><?php echo moneyFormatIndia($meta_leads); ?></td>
                                            <td><?php echo moneyFormatIndia($meta_cpl); ?></td>
                                            <td><?php echo moneyFormatIndia($google_spend); ?></td>
                                            <td><?php echo moneyFormatIndia($google_leads); ?></td>
                                            <td><?php echo moneyFormatIndia($google_cpl); ?></td>
                                            <td><?php echo moneyFormatIndia($total_spend); ?></td>
                                            <td><?php echo moneyFormatIndia($total_leads); ?></td>
                                            <td><?php echo moneyFormatIndia($total_cpl); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <?php if($grand_meta_spend>0) { ?>
                                    <tfoot>
                                        <!-- Grand Total Row -->
                                        <tr class="total-row">
                                            <td style="text-align:center;"><b>TOTAL</b></td>
                                            <td><?php echo moneyFormatIndia($grand_meta_spend); ?></td>
                                            <td><?php echo moneyFormatIndia($grand_meta_leads); ?></td>
                                            <td><?php echo moneyFormatIndia(($grand_meta_leads > 0) ? round($grand_meta_spend / $grand_meta_leads) : 0); ?></td>
                                            <td><?php echo moneyFormatIndia($grand_google_spend); ?></td>
                                            <td><?php echo moneyFormatIndia($grand_google_leads); ?></td>
                                            <td><?php echo moneyFormatIndia(($grand_google_leads > 0) ? round($grand_google_spend / $grand_google_leads) : 0); ?></td>
                                            <td><?php echo moneyFormatIndia($grand_meta_spend + $grand_google_spend); ?></td>
                                            <td><?php echo moneyFormatIndia($grand_meta_leads + $grand_google_leads); ?></td>
                                            <td><?php echo moneyFormatIndia((($grand_meta_leads + $grand_google_leads) > 0) ? round(($grand_meta_spend + $grand_google_spend) / ($grand_meta_leads + $grand_google_leads)) : 0); ?></td>
                                        </tr>
                                    </tfoot>
                                    <?php } ?>
                                </table>
                            </div>
                            
                            <div style="text-align: right; margin-top: 10px;">
                                <button id="copyTableImage" class="btn btn-info">
                                    <i class="fa fa-copy"></i> Copy Table as Image
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <br>
                                        
    <!-- HTML2Canvas for copying table as image -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    
    <script>
       $(document).ready(function () {
    console.log('Document ready');

    // On page reload after form submission, hide overlay quickly
    <?php if(isset($_POST['submit_report'])): ?>
    $('.loading-overlay').removeClass('active');
    <?php endif; ?>

    // Show overlay when form is submitted
    $('form').on('submit', function () {
        $('.loading-overlay').addClass('active');

        // Prevent submission if no clients selected
        var selectedClients = $('.selectpicker').val();
        if (!selectedClients || selectedClients.length === 0) {
            alert('Please select at least one client.');
            $('.loading-overlay').removeClass('active');
            return false;
        }
    });

    // Initialize Bootstrap-select safely
    try {
        $('.selectpicker').selectpicker({
            size: 8,
            liveSearch: true,
            actionsBox: true,
            selectAllText: 'Select All',
            deselectAllText: 'Deselect All'
        });
        console.log('Bootstrap-select initialized');
    } catch (e) {
        console.error('Error initializing Bootstrap-select:', e);
    }

    // Initialize date range picker
    try {
        var start = moment('<?php echo $_SESSION['stDt']; ?>', 'MM/DD/YYYY');
        var end = moment('<?php echo $_SESSION['enDt']; ?>', 'MM/DD/YYYY');

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            opens: 'left',
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function (start, end) {
            $('#dateRangeText').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            $('#start_date').val(start.format('MM/DD/YYYY'));
            $('#end_date').val(end.format('MM/DD/YYYY'));
        });

        console.log('Date range picker initialized');
    } catch (e) {
        console.error('Error initializing date range picker:', e);
    }

    // Copy table as image
    $('#copyTableImage').click(function () {
        const table = document.getElementById('reportTable');
        html2canvas(table, { backgroundColor: null, scale: 8 }).then(function (canvas) {
            canvas.toBlob(function (blob) {
                if (navigator.clipboard && window.ClipboardItem) {
                    const item = new ClipboardItem({ 'image/png': blob });
                    navigator.clipboard.write([item]).then(function () {
                        alert('Table image copied to clipboard!');
                    }, function (err) {
                        alert('Failed to copy image: ' + err);
                    });
                } else {
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');
                }
            });
        });
    });

    // Initialize DataTable
    try {
        $('.table').dataTable({
            ordering: true,
            lengthMenu: [25, 50, 100],
            pageLength: 25,
            scrollX: true,
            autoWidth: false,
            bLengthChange: false,
            bSearch: false,
            searching: false,
            bInfo: false,
            info: false,
            bPaginate: false,
            paging: false,
            order: []
        });
        console.log('DataTable initialized');
    } catch (e) {
        console.error('Error initializing DataTable:', e);
    }
});
    </script>
</body>
</html>
