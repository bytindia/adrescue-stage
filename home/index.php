<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db.php';

$qry_str_ad = '&filter_set=SEARCH_BY_ADGROUP_IDS-STRING_SET%1EANY%1E[%22';
$qry_str_adset = '&filter_set=SEARCH_BY_CAMPAIGN_IDS-STRING_SET%1EANY%1E[%22';
$qry_str_camp = '&filter_set=SEARCH_BY_CAMPAIGN_GROUP_IDS-STRING_SET%1EANY%1E[%22';

$fb_url_ad = 'https://adsmanager.facebook.com/adsmanager/manage/ads?act=';
$fb_url_adset = 'https://adsmanager.facebook.com/adsmanager/manage/adsets?act=';
$fb_url_camp = 'https://adsmanager.facebook.com/adsmanager/manage/campaigns?act=';

$dt_qry_30 = '%22]&date='.date("Y-m-d", strtotime('-30 days')).'_'.date('Y-m-d',strtotime('today'));
$dt_qry_3 = '%22]&date='.date("Y-m-d", strtotime('-3 days')).'_'.date('Y-m-d',strtotime('today'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="/images/favicon.ico" type="image/ico" />
    <title>Troubleshoot - AdRescue</title>
 <!-- Semantic UI CSS -->
 <link href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" rel="stylesheet">

 <!-- DataTables CSS -->
 <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">

 <!-- DataTables Buttons CSS -->
 <link href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
    <!-- DataTable CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<style>
    .bg0 { background-color: #f5f3bb !important; }
    .bg25 { background-color: #fdcd93 !important; }
    .bg50 { background-color: #fdab93 !important; }
    .bg75 { background-color: #f57854 !important; color: white !important; }

    
    .bg25_1 { background-color: #c2ebc2 !important; }
    .bg50_1 { background-color: #88cd88 !important; }
    .bg75_1 { background-color: #228B22 !important; color: white !important; }
    table.table td:nth-child(-n+4) {
        text-align: left;
    }
    table.table td:nth-child(n+5) {
        text-align: right;
    }
    }
.tabs-container {
     margin-top: 5px !important; 
    }
</style>
<body>
<?php 
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'WC', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'WC'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'Sales', 'name'=>'Ecom', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];

$client_name = $last30d_cpl = array();
$sqlRev=mysqli_query($conn, "SELECT acc_id,client_name FROM troubleshoot WHERE uid='2' AND delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
}
$ext_q = $ext_q2 = 'leads>0 AND';
$sqlRev=mysqli_query($conn, "SELECT acc_id,obj,cpl FROM troubleshoot_cpl WHERE cpl>0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
    $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']] = $sqlROW['cpl'];
    $ext_q .= " (acc_id='".$sqlROW['acc_id']."' AND obj='".$sqlROW['obj']."' AND cpl>".round($sqlROW['cpl'])." ) OR ";
    $ext_q2 .= " (acc_id='".$sqlROW['acc_id']."' AND obj='".$sqlROW['obj']."' AND cpl<".round($sqlROW['cpl'])." AND cpl!=0 ) OR ";
}
$zero_lead_sum = array();
$cirRes = mysqli_query($conn, "SELECT acc_id, obj, COUNT(*) AS tot_lead_0 FROM troubleshoot_data WHERE leads=0 GROUP BY acc_id, obj");	
while($sqlROW=mysqli_fetch_array($cirRes))
{
    $zero_lead_sum[$sqlROW['obj']][] = array('acc_id'=>$sqlROW['acc_id'], 'acc_name'=>$client_name[$sqlROW['acc_id']], 'tot_adset'=>$sqlROW['tot_lead_0'], 'chart_div'=>$objectives[$sqlROW['obj']]['key'], 'title'=>$objectives[$sqlROW['obj']]['name2']);
}
//d($zero_lead_sum);

$cpl_lg = $cpl_lg2 = $cpl_con = $cpl_con2 = '';
$cirRes = mysqli_query($conn, "SELECT acc_id, obj, cpl FROM troubleshoot_data WHERE leads>0");	
while($sqlROW=mysqli_fetch_array($cirRes))
{
    //$zero_lead_sum[$sqlROW['obj']][] = array('acc_id'=>$sqlROW['acc_id'], 'acc_name'=>$client_name[$sqlROW['acc_id']], 'tot_adset'=>$sqlROW['tot_lead_0'], 'chart_div'=>$objectives[$sqlROW['obj']]['key'], 'title'=>$objectives[$sqlROW['obj']]['name2']);
   // $ext_q .= " (acc_id='".$sqlROW['acc_id']."' AND obj='".$sqlROW['obj']."' AND cpl>".round($sqlROW['cpl'])." ) OR ";
}
?>
<div class="container tabs-container" style="margin-top: 5px;">
   

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">O Lead</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">High CPL</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">Low CPL</a>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Tab 1 -->
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
            <!-- Filter Section -->
            

            <!-- Table -->
            <div class="table-container">
                    <?php
                        // Initialize an array to keep track of chart div IDs
                        $divsCreated = [];
                        // Create a container for the charts using Flexbox
                       /* echo "<div class='chart-container'>";

                        // Loop through the array and create a div for each chart
                        foreach ($zero_lead_sum as $category => $accounts) {
                            foreach ($accounts as $account) {
                                if (!in_array($account['chart_div'], $divsCreated)) {
                                    // Add the chart div within the container
                                    echo "<div id='" . $account['chart_div'] . "' class='chart'></div>";
                                    $divsCreated[] = $account['chart_div'];
                                }
                            }
                        }
                        echo "</div>";*/
                        ?>
                        <div class="filter-container">
                            <!-- Custom Filtering Dropdown -->
                            
                            <select id="filterOptions" class="form-control">
                                <option value="">Select Objective</option>
                                <option value="LG">LG</option>
                                <option value="WC">WC</option>
                                <option value="Ecom">Ecom</option>
                            </select>
                        </div>
                    <table id="table1" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>AdSet</th>
                                <th>Campaign</th>
                                <th>Objective</th>
                                <th>Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                                $sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE leads=0");										
                                while($sqlROW=mysqli_fetch_array($sqlRev))
                                {
                                    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
                                    $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
                                ?>
                                <tr>  
                                    <td><?php echo $client_name[$sqlROW['acc_id']]; ?></td>
                                    <td><?php echo '<a href="'.$as_link.'" target="_blank">'.$sqlROW['adset_name'].'</a>'; ?></td>
                                    <td><?php echo $sqlROW['camp_name']; ?></td>
                                    <td><?php echo $objectives[$sqlROW['obj']]['name']; ?></td>
                                    <td><?php echo round($sqlROW['spend']); ?></td>
                                </tr> 
                                <?php } ?>
                        </tbody>
                    </table>
            </div>
        </div>

        <!-- Tab 2 -->
        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="table-container">
                <div class="filter-container">
                    <select id="filterOptions2" class="form-control">
                        <option value="">Select Objective</option>
                        <option value="LG">LG</option>
                        <option value="WC">WC</option>
                        <option value="Ecom">Ecom</option>
                    </select>
                    <select id="filterOptions22" class="form-control">
                        <option value="">CPL Percentage</option>
                        <option value="25">25% Above</option>
                        <option value="50">50% Above</option>
                        <option value="75">70% Above</option>
                    </select>
                </div>
                <table id="table2" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>AdSet</th>
                                <th>Campaign</th>
                                <th>Objective</th>
                                <th>Spend</th>
                                <th>Leads</th>
                                <th>CPL</th>
                                <th>CPL %</th>
                                <th>CPL L30d</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                                $sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE {$ext_q} id=0");										
                                while($sqlROW=mysqli_fetch_array($sqlRev))
                                {
                                    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
                                    $pecentage =  round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100);
                                    $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
                                    $bg='';
                                    if($pecentage>0 && $pecentage<25) { $bg= 'bg0'; } 
                                    elseif($pecentage>24 && $pecentage<50) { $bg= 'bg25'; } 
                                    elseif($pecentage>49 && $pecentage<75) { $bg= 'bg50'; } 
                                    elseif($pecentage>74) { $bg= 'bg75'; } 
                                ?>
                                <tr class="<?php echo $bg; ?>">  
                                    <td><?php echo $client_name[$sqlROW['acc_id']]; ?></td>
                                    <td><?php echo '<a href="'.$as_link.'" target="_blank">'.$sqlROW['adset_name'].'</a>'; ?></td>
                                    <td><?php echo $sqlROW['camp_name']; ?></td>
                                    <td><?php echo $objectives[$sqlROW['obj']]['name']; ?></td>
                                    <td><?php echo round($sqlROW['spend']); ?></td>
                                    <td><?php echo $sqlROW['leads']; ?></td>
                                    <td><?php echo $sqlROW['cpl']; ?></td>
                                    <td data-value="<?php echo $pecentage; ?>"><?php echo $pecentage; ?>% <i class="fa fa-arrow-up" style="color:#d1dfe9"></i></td>
                                    <td><?php echo $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]; ?></td>
                                </tr> 
                                <?php } ?>
                        </tbody>
                    </table>
            </div>
        </div>

        <!-- Tab 3 -->
        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
            <div class="table-container">
                    <div class="filter-container">
                        <select id="filterOptions3" class="form-control">
                            <option value="">Select Objective</option>
                            <option value="LG">LG</option>
                            <option value="WC">WC</option>
                            <option value="Ecom">Ecom</option>
                        </select>
                        <select id="filterOptions33" class="form-control">
                            <option value="">CPL Percentage</option>
                            <option value="25">25% Below</option>
                            <option value="50">50% Below</option>
                            <option value="75">70% Below</option>
                        </select>
                    </div>
                    <table id="table3" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>AdSet</th>
                                <th>Campaign</th>
                                <th>Objective</th>
                                <th>Spend</th>
                                <th>Leads</th>
                                <th>CPL</th>
                                <th>CPL %</th>
                                <th>CPL L30d</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        //echo "SELECT * FROM troubleshoot_data  WHERE {$ext_q2} id=0 "; 
                                $sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE {$ext_q2} id=0 ");										
                                while($sqlROW=mysqli_fetch_array($sqlRev))
                                {
                                    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
                                    $pecentage =  abs(round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100));
                                    $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
                                    $bg='';
                                    if($pecentage>0 && $pecentage<25) { $bg= 'bg0'; } 
                                    elseif($pecentage>24 && $pecentage<50) { $bg= 'bg25_1'; } 
                                    elseif($pecentage>49 && $pecentage<75) { $bg= 'bg50_1'; } 
                                    elseif($pecentage>74) { $bg= 'bg75_1'; } 
                                ?>
                                <tr class="<?php echo $bg; ?>">  
                                    <td><?php echo $client_name[$sqlROW['acc_id']]; ?></td>
                                    <td><?php echo '<a href="'.$as_link.'" target="_blank">'.$sqlROW['adset_name'].'</a>'; ?></td>
                                    <td><?php echo $sqlROW['camp_name']; ?></td>
                                    <td><?php echo $objectives[$sqlROW['obj']]['name']; ?></td>
                                    <td><?php echo round($sqlROW['spend']); ?></td>
                                    <td><?php echo $sqlROW['leads']; ?></td>
                                    <td><?php echo $sqlROW['cpl']; ?></td>
                                    <td data-value="<?php echo abs($pecentage); ?>"><?php echo abs($pecentage); ?>% <i class="fa fa-arrow-down" style="color:#d1dfe9"></i></td>
                                    <td><?php echo $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]; ?></td>
                                </tr> 
                                <?php } ?>
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS, Popper.js, jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTables with buttons
        var table1 = $('#table1').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            lengthMenu: [25, 50, 100, 100],
        });

        var table2 = $('#table2').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            lengthMenu: [25, 50, 100, 100],
        });

        var table3 = $('#table3').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            lengthMenu: [25, 50, 100, 100],
        });

        // Filter functionality
        $('#filterOptions').on('change', function () {
            var selectedValue = $(this).val();
            table1.columns(3).search(selectedValue).draw();
            //table2.columns(3).search(selectedValue).draw();
            //table3.columns(3).search(selectedValue).draw();
        });
        $('#filterOptions').on('change', function () {
            var selectedValue = $(this).val();
            table1.columns(3).search(selectedValue).draw();
            //table2.columns(3).search(selectedValue).draw();
            //table3.columns(3).search(selectedValue).draw();
        });
        $('#filterOptions2').on('change', function () {
            var selectedValue = $(this).val();
            table2.columns(3).search(selectedValue).draw();
        });
        $('#filterOptions22').on('change', function () {
            var selectedValue = $(this).val();
            table2.rows().every(function () {
                var row = $(this.node());
                var cplPercentage = parseInt(row.find('td[data-value]').attr('data-value'), 10) || 0;

                if (selectedValue === "" || cplPercentage >= parseInt(selectedValue, 10)) {
                    row.show();
                } else {
                    row.hide();
                }
            });
            table2.draw();
        });
        $('#filterOptions3').on('change', function () {
            var selectedValue = $(this).val();
            table3.columns(3).search(selectedValue).draw(); 
        });
        $('#filterOptions33').on('change', function () {
            var selectedValue = $(this).val();
            table3.rows().every(function () {
                var row = $(this.node());
                var cplPercentage = parseInt(row.find('td[data-value]').attr('data-value'), 10) || 0;

                if (selectedValue === "" || cplPercentage <= parseInt(selectedValue, 10)) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        });
        $('#search').on('keyup', function() {
            table1.search(this.value).draw();
            table2.search(this.value).draw();
            table3.search(this.value).draw();
        });
    });
</script>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
       google.charts.load('current', {
        packages: ['corechart', 'bar']
    });

    google.charts.setOnLoadCallback(drawCharts);
        
        function drawCharts() {
            // Define the data for each chart
            var data = <?php echo json_encode($zero_lead_sum); ?>;
            
            // Loop through each category to draw charts
            for (var category in data) {
                var categoryData = data[category];

                // Create a data table for each category
                var chartData = new google.visualization.DataTable();
                chartData.addColumn('string', 'Account');
                chartData.addColumn('number', 'Total Ad Sets');
                chartData.addColumn('number', 'Leads');

                // Add rows for each account in the category
                categoryData.forEach(function(account) {
                    chartData.addRow([account.acc_name, parseInt(account.tot_adset), account.leads]);
                });

                // Draw the chart into the corresponding div
                var chartDivId = categoryData[0].chart_div;
                var chart = new google.visualization.ComboChart(document.getElementById(chartDivId));
                chart.draw(chartData, {
                    title: '0 '+categoryData[0].title,
                    chartArea: {
                        width: '80%',
                        height: '70%'
                    },
                    seriesType: 'bars',  // 'bars' is used for the vertical bars
                    series: {
                        1: {type: 'line'}  // The second series will be a line chart (Leads)
                    },
                    vAxis: {
                        title: 'Ad Sets'
                    },
                    hAxis: {
                        title: 'Client'
                    }
                });
            }
        }
    </script>
</body>
</html>
