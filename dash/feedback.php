<?php session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (isset($_GET['upload']) && $_GET['upload'] == 1) {
    unset($_SESSION['csv1'], $_SESSION['csv2'], $_SESSION['filters']);
    echo "<script>window.location = 'feedback.php';</script>"; exit();
}
// Helper functions
include '/home/digitalb2k/stage.adrescue.in/db.php';

function sanitize_phone($phone) {
    return preg_replace('/\D/', '', $phone);
}

function update_counts(&$group_array, $keyword, $matched_status) {
    if (!isset($group_array[$keyword])) $group_array[$keyword] = [];
    if (!isset($group_array[$keyword][$matched_status])) $group_array[$keyword][$matched_status] = 0;
    $group_array[$keyword][$matched_status]++;
}
function parse_keywords($input_string) {
    $keywords = array_map('trim', explode(',', $input_string));
    $parsed = [];

    foreach ($keywords as $keyword) {
        if (strpos($keyword, '[!=') !== false) {
            // Extract keyword and all [!=...] exclusions
            preg_match('/(.*?)\[(.*?)\]/', $keyword, $matches);

            if (isset($matches[1]) && isset($matches[2])) {
                $main_keyword = strtolower(trim($matches[1]));
                $exclusions_raw = $matches[2]; // example: !=sv & wa, !=price

                // Find all != exclusions
                preg_match_all('/!=([^,]+)/', $exclusions_raw, $exclusion_matches);
                $exclusions = array_map(function($e) {
                    return strtolower(trim($e));
                }, $exclusion_matches[1]);

                $parsed[] = [
                    'keyword' => $main_keyword,
                    'exclude' => $exclusions
                ];
            }
        } else {
            $parsed[] = [
                'keyword' => strtolower(trim($keyword)),
                'exclude' => []
            ];
        }
    }

    return $parsed;
}

function match_condition($text, $condition) {
    $text = strtolower($text);
    $keyword = $condition['keyword'];
    $excludes = $condition['exclude'];

    if (strpos($text, $keyword) !== false) {
        if (!empty($excludes)) {
            foreach ($excludes as $exclude) {
                if (strpos($text, $exclude) !== false) {
                    return false; // If any exclude matches, reject
                }
            }
        }
        return true;
    }
    return false;
}
function print_datatable($table_id, $group_array, $all_status_types, $title) {
    echo "<h3 class='mt-5'>{$title}</h3>";
    echo "<table id='{$table_id}' class='table table-striped table-bordered'>";
    
    // THEAD
    echo "<thead><tr><th>Keyword</th>";
    foreach (array_keys($all_status_types) as $status) {
        echo "<th class='text-end'>".ucwords($status)."</th>";
    }
    echo "<th class='text-end'>Total</th></tr></thead><tbody>";

    // Initialize column totals
    $column_totals = array_fill_keys(array_keys($all_status_types), 0);
    $grand_total = 0;

    // TBODY
    foreach ($group_array as $keyword => $counts) {
        echo "<tr><td>".ucwords($keyword)."</td>";
        $row_total = array_sum($counts);
        $grand_total += $row_total;

        foreach (array_keys($all_status_types) as $status) {
            $count = $counts[$status] ?? 0;
            $column_totals[$status] += $count;
            $percentage = $row_total > 0 ? round(($count / $row_total) * 100) : 0;

            echo "<td class='text-end'><span>{$count}</span><br><small class='text-success'>{$percentage}%</small></td>";
        }

        echo "<td class='text-end'><b>{$row_total}</b></td></tr>";
    }

    echo "</tbody>";

    // TFOOT with total row + percentages
    echo "<tfoot><tr><th>Total</th>";
    foreach (array_keys($all_status_types) as $status) {
        $count = $column_totals[$status];
        $percentage = $grand_total > 0 ? round(($count / $grand_total) * 100) : 0;
        echo "<th class='text-end text-right'><span><b>{$count}</b></span><br><small class='text-default'>{$percentage}%</small></th>";
    }
    echo "<th class='text-end text-right'><b>{$grand_total}</b><br><small class='text-default'>100%</small></th></tr></tfoot>";
    echo "</table><br><br>";

}

if (isset($_POST['submit'])) {
    $dataSource = $_POST['data_source'] ?? '';
    $csv1 = [];
    $csv2 = [];

    // 🔹 If CSV is selected
    if ($dataSource === 'csv') {
        $allowed = ['csv'];
        $file = $_FILES['file']['tmp_name'];
        $file2 = $_FILES['file2']['tmp_name'];

        if ($_FILES['file']['size'] == 0 || $_FILES['file2']['size'] == 0) {
            $_SESSION['err'] = 'Please upload both files under 10MB!';
            echo "<script>window.location = 'feedback.php';</script>";
            exit();
        }

        if (!in_array(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION), $allowed) ||
            !in_array(pathinfo($_FILES['file2']['name'], PATHINFO_EXTENSION), $allowed)) {
            $_SESSION['err'] = 'Please upload CSV files only!';
            echo "<script>window.location = 'feedback.php';</script>";
            exit();
        }

        // ✅ Parse Raw Data
        if (($handle = fopen($file, "r")) !== false) {
            $c = 0;
            while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                if ($c++ == 0) continue;
                if (isset($row[1]) && strlen(trim($row[1])) >= 1) {
                    $csv1[] = $row;
                }
            }
            fclose($handle);
        }

        // ✅ Parse Feedback Data
        if (($handle2 = fopen($file2, "r")) !== false) {
            $c = 0;
            while (($row2 = fgetcsv($handle2, 10000, ",")) !== false) {
                if ($c++ == 0) continue;
                if (isset($row2[1]) && strlen(trim($row2[1])) >= 1) {
                    $csv2[] = $row2;
                }
            }
            fclose($handle2);
        }

    }

    // 🔹 If Client Dropdown selected
    elseif ($dataSource === 'client') {
        $clientIds = $_POST['client_ids'] ?? [];

        if (!empty($clientIds)) {
            foreach ($clientIds as $clientId) {
                $clientId = mysqli_real_escape_string($conn, $clientId);
                $sqlRev = mysqli_query($conn, "SELECT * FROM leads WHERE page_id='$clientId' ORDER BY tbl_id DESC");

                while ($sqlROW = mysqli_fetch_array($sqlRev)) {
                    $leads = unserialize($sqlROW["lead"]);
                    $csv1[] = [
                        $leads['full_name'] ?? '',
                        $leads['email'] ?? '',
                        $leads['phone_number'] ?? '',
                        $sqlROW['adN'] ?? '',
                        $sqlROW['adsetN'] ?? '',
                        $sqlROW['campN'] ?? '',
                        $sqlROW['formN'] ?? '',
                        'fb',
                        date('m/d/Y h:i a', $sqlROW['created_time'] + 34199)
                    ];
                }
            }
        }

        // Feedback CSV (optional if needed in both cases)
        if (!empty($_FILES['file2']['tmp_name'])) {
            if (($handle2 = fopen($_FILES['file2']['tmp_name'], "r")) !== false) {
                $c = 0;
                while (($row2 = fgetcsv($handle2, 10000, ",")) !== false) {
                    if ($c++ == 0) continue;
                    if (isset($row2[1]) && strlen(trim($row2[1])) >= 1) {
                        $csv2[] = $row2;
                    }
                }
                fclose($handle2);
            }
        }
    }

    // ✅ Store session
    $_SESSION['csv1'] = $csv1;
    $_SESSION['csv2'] = $csv2;
    $_SESSION['filters'] = [
        'ad_name' => $_POST['ad_name'] ?? '',
        'adset_name' => $_POST['adset_name'] ?? '',
        'form_name' => $_POST['form_name'] ?? '',
        'camp_name' => $_POST['camp_name'] ?? ''
    ];

    echo "<script>window.location = 'feedback.php';</script>";
    exit();
}

//d($_SESSION['csv1']); d($_SESSION['csv2']);

if (isset($_SESSION['csv1'], $_SESSION['csv2'], $_SESSION['filters'])) {

    $csv1 = $_SESSION['csv1'];
    $csv2 = $_SESSION['csv2'];
    $filters = $_SESSION['filters'];

    // Step 1: Rebuild the status lookup
    $status_lookup = [];
    $all_status_types = [];
    $priority = ['Sitevisit', 'Sitevisited', 'Sitevisit Planned', 'SV', 'SVP', 'Qualified', 'Qualified Lead', 'Hot', 'Warm', 'Followup', 'RNR', 'rnr'];

    foreach ($csv2 as $sale) {
        $phone = sanitize_phone($sale[0]);
        $status = strtolower(trim($sale[2]));
        $status_lookup[$phone] = $status;
        $all_status_types[$status] = true;
    }

    // Step 2: Sort statuses based on priority
    uksort($all_status_types, function($a, $b) use ($priority) {
        $priorityLower = array_map('strtolower', $priority);
        $aIndex = array_search(strtolower($a), $priorityLower);
        $bIndex = array_search(strtolower($b), $priorityLower);
        if ($aIndex !== false && $bIndex !== false) return $aIndex - $bIndex;
        if ($aIndex !== false) return -1;
        if ($bIndex !== false) return 1;
        return strcmp($a, $b);
    });

    // Step 3: Parse saved filters
    $ad_name_keywords = parse_keywords($filters['ad_name']);
    $adset_name_keywords = parse_keywords($filters['adset_name']);
    $form_name_keywords = parse_keywords($filters['form_name']);
    $camp_name_keywords = parse_keywords($filters['camp_name']);

    // Step 4: Date range filter setup
    $start_date = isset($_GET['st']) ? DateTime::createFromFormat('d/m/Y', $_GET['st']) : null;
    $end_date = isset($_GET['en']) ? DateTime::createFromFormat('d/m/Y', $_GET['en']) : null;

    // Step 5: Initialize group arrays
    $group_by_adname = [];
    $group_by_adset = [];
    $group_by_camp = [];
    $group_by_form = [];
    $group_by_platform = [];
    $group_by_created_date = [];
    $group_by_day_name = [];

    // Step 6: Process each lead
    foreach ($csv1 as $lead) {
        $phone = sanitize_phone($lead[2]);
        $ad_name = strtolower($lead[3] ?? '');
        $adset_name = strtolower($lead[4] ?? '');
        $camp_name = strtolower($lead[5] ?? '');
        $form_name = strtolower($lead[6] ?? '');
        $platform_name = strtolower($lead[7] ?? '');
        $date_raw = trim($lead[8] ?? '');
        $matched_status = $status_lookup[$phone] ?? 'rnr';

        // Step 6.1: Apply Date Range Filter
        $include = true;
        if ($start_date && $end_date && $date_raw !== '') {
            $created = DateTime::createFromFormat('m/d/y', $date_raw); // CSV file date format (example: 04/25/25)
            $include = $created && $created >= $start_date && $created <= $end_date;
        }

        if (!$include) continue; // Skip if outside date range

        // Step 6.2: Groupings
        foreach ($ad_name_keywords as $condition) {
            if (match_condition($ad_name, $condition)) {
                update_counts($group_by_adname, $condition['keyword'], $matched_status);
            }
        }
        foreach ($adset_name_keywords as $condition) {
            if (match_condition($adset_name, $condition)) {
                update_counts($group_by_adset, $condition['keyword'], $matched_status);
            }
        }
        foreach ($camp_name_keywords as $condition) {
            if (match_condition($camp_name, $condition)) {
                update_counts($group_by_camp, $condition['keyword'], $matched_status);
            }
        }
        foreach ($form_name_keywords as $condition) {
            if (match_condition($form_name, $condition)) {
                update_counts($group_by_form, $condition['keyword'], $matched_status);
            }
        }

        if ($platform_name !== '') {
            update_counts($group_by_platform, $platform_name, $matched_status);
        }

        if ($date_raw !== '') {
            $timestamp = strtotime($date_raw);
            if ($timestamp !== false) {
                $created_date = date('m/d/y', $timestamp);
                $day_name = date('l', $timestamp);
                update_counts($group_by_created_date, $created_date, $matched_status);
                update_counts($group_by_day_name, $day_name, $matched_status);
            }
        }
    }


}
?>





<link rel="stylesheet" type="text/css" href="/casa/css/style.css" />
    <link rel="stylesheet" type="text/css" href="/casa/style.css" />
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
<link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
<title>Leads Feedback - AdRescue</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Bootstrap -->
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
<style>
body, html {
    width: 80%;
    margin: 10 auto;
    font-family: Trebuchet MS, sans-serif;
}
.table { font-size: 14px;}
ul.nav.navbar-right.panel_toolbox {
    float: right;
    margin-bottom: 20px;
}
footer {
    margin-top: 25px;
    float: right;
    font-size: 14px;
}
@media (min-width: 768px) {
.form-horizontal .control-label {
  
    text-align: left !important; 
}}
tfoot, thead {
    background: #3e99e8;
    font-weight: bold;
    color: white;
}
tfoot td {
    text-align: right !important;
}
thead td {
    text-align: center !important;
}
tbody td:not(:nth-child(2)) {
    text-align: right;
}
.text-warning {
    color: #dfa747;
}
.text-danger {
    color: #ff7a78;
}
.text-success {
    color: #45b146;
}
td.text-end {
    text-align: right; 
}
.h3, h3 {
    font-size: 24px;
    text-align: center;
}
th.text-end.text-right {
    text-align: right;
}
</style>

<?php
//echo $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);


$calQty = $data_g = array();


$dt_q = $extQry = '';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
  $extQry .= " AND created BETWEEN '".date('Y-m-d H:i:s',strtotime(strtr($_GET['st'], '/', '-').' 00:00:00'))."' AND  '".date('Y-m-d H:i:s',strtotime(strtr($_GET['en'], '/', '-').' 23:59:59'))."'";
}


$campLeadTot = array();



//d($data_g); exit;
$cqV = '';

//d($new);
//exit;

?>

<div class="x_title">
                 
                    <center><img src="/images/adrescue-2021.png" alt="VLookUp" style="height: 45px;"><h3>vLookUp - Leads Feedback</h3></center>
</div>
<div class="clearfix"></div>
<div class="col-6 pull-right">
          <?php if (isset($_SESSION['csv1'], $_SESSION['csv2'], $_SESSION['filters'])) { ?>
          <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                              <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                              <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
          </div>
          <?php } ?>
</div>
<br />
                    
        <?php
        include '../alert.php';
        if (isset($_SESSION['csv1'], $_SESSION['csv2'], $_SESSION['filters'])) {

            // Step 6: Print Tables
        print_datatable('table_adname', $group_by_adname, $all_status_types, "Visual");
        print_datatable('table_adset', $group_by_adset, $all_status_types, "Target");
        print_datatable('table_camp', $group_by_camp, $all_status_types, "Campaign");
        print_datatable('table_form', $group_by_form, $all_status_types, "Form");
        print_datatable('table_platform', $group_by_platform, $all_status_types, "Platform");
        print_datatable('table_created_date', $group_by_created_date, $all_status_types, "Date wise");
        print_datatable('table_day_name', $group_by_day_name, $all_status_types, "Day wise");

            ?>

            <br>
            <a href="feedback.php?upload=1" class="btn btn-danger btn-lg float-right">Upload Again</a>

<?php
       } else {
               
        ?>
        <div class="row col-8">
                    <form id="demo-form2" class="form-horizontal form-label-left" enctype="multipart/form-data" method="post" action="">

                        <!-- Common Filters -->
                        <div class="form-group">
                            <label class="control-label col-md-3" for="ad-name">Ad name contains:</label>
                            <input type="text" class="form-control" name="ad_name" />
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3" for="adset-name">Ad set name contains:</label>
                            <input type="text" class="form-control" name="adset_name" />
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3" for="form-name">Form name contains:</label>
                            <input type="text" class="form-control" name="form_name" />
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3" for="campaign-name">Campaign name contains:</label>
                            <input type="text" class="form-control" name="camp_name" />
                        </div>

                        <!-- Toggle Option -->
<div class="form-group">
  <label class="control-label col-md-3">Choose data source:</label>
  <div class="col-md-6">
    <label class="radio-inline">
      <input type="radio" name="data_source" value="csv" checked onchange="toggleInput()"> Upload CSV
    </label>
    <label class="radio-inline" style="margin-left: 20px;">
      <input type="radio" name="data_source" value="client" onchange="toggleInput()"> Select Client
    </label>
  </div>
</div>

<!-- CSV Upload Input -->
<div class="form-group" id="csvInput">
  <label class="control-label col-md-3">Raw data file:</label>
  <div class="col-md-6">
    <input type="file" class="form-control" name="file" />
    <small>csv: email, campaign</small>
  </div>
</div>
<style>
    .bootstrap-select .dropdown-menu {
  max-height: 300px !important; /* Adjust height */
  overflow-y: auto !important;
  z-index: 1051; /* Ensure it's above modals or sticky headers */
}
</style>
<!-- Client Dropdown Input -->
<div class="form-group" id="clientSelect" style="display: none;">
  <label class="control-label col-md-3">Select Client:</label>
  <div class="col-md-6">
    <select class="form-control selectpicker" multiple data-live-search="true"  name="client_ids[]" >
        <?php
         $statement = " leads_acc WHERE uid='2' AND delete_status=0";
         echo "SELECT * FROM ".$statement." order by tbl_id desc "; 
                $sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc ");
            while($sqlROW=mysqli_fetch_array($sqlRev)) {
                echo "<option value='{$sqlROW["pg_id"]}'>{$sqlROW["client_name"]}</option>";
            }
        ?>
    </select>
  </div>
</div>

                        <!-- Feedback CSV -->
                        <div class="form-group">
                            <label class="control-label col-md-3" for="feedback-data">Feedback data:</label>
                            <input type="file" class="form-control" name="file2" />
                            <small>csv: email, status/feedback</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="submit" class="btn btn-success btn-lg">Submit</button>
                        </div>
                    </form>

                        <script>
                        function toggleInput() {
                            const source = document.querySelector('input[name="data_source"]:checked').value;
                            document.getElementById('csvInput').style.display = (source === 'csv') ? 'block' : 'none';
                            document.getElementById('clientSelect').style.display = (source === 'client') ? 'block' : 'none';
                        }
                        </script>

                </div>
        <?php
       }
            ?>
    <!-- Custom Theme Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- DataTables datetime-moment plugin -->
    <script src="https://cdn.datatables.net/plug-ins/1.13.4/sorting/datetime-moment.js"></script>

	<script type="text/javascript">
    var isMob = false;
    $(document).ready(function() {
        $('select').selectpicker('render');
        // Tell DataTables to understand MM/DD/YY format
        $.fn.dataTable.moment('DD/MM/YY');

        // Initialize all tables
        $('.table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copyHtml5', footer: true },
                { extend: 'excelHtml5', footer: true },
                { extend: 'csvHtml5', footer: true },
                { extend: 'pdfHtml5', footer: true },
                { extend: 'print', footer: true }
            ],
            pageLength: 50,
            lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]],
            scrollY: "400px",
            scrollCollapse: true,
            paging: false,
            fixedHeader: true
        });

        var defSt = '01/01/2018';
var defEnd = '01/01/2024';
var d1 = '<?php echo $_SESSION['st']; ?>';
var d2 = '<?php echo $_SESSION['en']; ?>';
var start = moment(defSt.split(' ')[0].split("/").reverse().join("-"));
var end = moment(d2.split(' ')[0].split("/").reverse().join("-"));
$('#reportrange span').html(d1 + ' - ' + d2);
        $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            opens: 'left',
            buttonClasses: ['btn btn-default'],
            applyClass: 'btn-small btn-primary',
            cancelClass: 'btn-small',
            format: 'DD/MM/YYYY',
            separator: ' to ',
            drops: 'down',
            opens: 'right',
            locale: {
                applyLabel: 'Submit',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom Range',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: 1
            }
        },
        function(start, end) {
            if(start.format('DD/MM/YYYY')==defSt) {
                console.log("Callback has been called!");
                $('#reportrange span').html(''); 
                $('#stDt_upd').val('');
                $('#enDt_upd').val('');
                window.location = 'loading.php?pg=feedback.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=feedback.php&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );
    });

</script>