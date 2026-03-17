
<?php session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">

    <title>Troubleshoot - AdRescue</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>DataTable with Sorting and Search</title>

<!-- Bootstrap CSS (for styling) -->
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables CSS (for table features like search, sort) -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <style type="text/css">
    /* Tabs*/
section {
    padding: 25px 0;
}

section .section-title {
    text-align: center;
    color: #007b5e;
    margin-bottom: 50px;
}
#tabs{
	
}
#tabs h6.section-title{
    
}

#tabs .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
    
    background-color: transparent;
    border-color: transparent transparent #f3f3f3;
    border-bottom: 4px solid !important;
    font-size: 20px;
    font-weight: bold;
}
#tabs .nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: .25rem;
    border-top-right-radius: .25rem;
    
    font-size: 20px;
}    
 /* Flexbox layout for chart containers */
 .chart-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
        }

        .chart {
            flex-grow: 1;
            /* Adjust the width for the number of charts */
            min-width: 30%; /* Makes the chart take at least 30% of the width */
            max-width: 48%;
            height: 200px;
        }
        </style>
   
    
</head>
<body>
<?php 
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'WC', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Website Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'Sales', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];

$client_name = array();
$sqlRev=mysqli_query($conn, "SELECT acc_id,client_name FROM troubleshoot WHERE uid='2' AND delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
}

$zero_lead_sum = array();
$cirRes = mysqli_query($conn, "SELECT acc_id, obj, COUNT(*) AS tot_lead_0 FROM troubleshoot_data WHERE leads=0 GROUP BY acc_id, obj");	
while($sqlROW=mysqli_fetch_array($cirRes))
{
    $zero_lead_sum[$sqlROW['obj']][] = array('acc_id'=>$sqlROW['acc_id'], 'acc_name'=>$client_name[$sqlROW['acc_id']], 'tot_adset'=>$sqlROW['tot_lead_0'], 'chart_div'=>$objectives[$sqlROW['obj']]['key'], 'title'=>$objectives[$sqlROW['obj']]['name2']);
}
//d($zero_lead_sum);
?>
<!-- Tabs -->

<section id="tabs">
	<div class="container">
		<h6 class="section-title h1">Ads - Troubleshoot</h6>
		<div class="row">
			<div class="col-xs-12 ">
				<nav>
					<div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
						<a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">0 Leads</a>
						<a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Low CPL</a>
						<a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false">High CPL</a>
					</div>
				</nav>
				<div class="tab-content py-3 px-3 px-sm-0" id="nav-tabContent">
					<div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                         <br>
                        <?php
                        // Initialize an array to keep track of chart div IDs
                        $divsCreated = [];
                        // Create a container for the charts using Flexbox
                        echo "<div class='chart-container'>";

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
                        echo "</div>";
                        ?>
                        <br>
                       <table id="datatable" class="table table-hover table-striped table-bordered">
                            <thead>
                                <th>Client</th>
                                <th>AdSet</th>
                                <th>Campaign</th>
                                <th>Objective</th>
                                <th>Spend</th>
                                <th>View</th>
                            </thead>
                            <tbody>
                                <?php 
                                $sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE leads=0");										
                                while($sqlROW=mysqli_fetch_array($sqlRev))
                                {
                                    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
                                ?>
                                <tr>  
                                    <td><?php echo $client_name[$sqlROW['acc_id']]; ?></td>
                                    <td><?php echo $sqlROW['adset_name']; ?></td>
                                    <td><?php echo $sqlROW['camp_name']; ?></td>
                                    <td><?php echo $objectives[$sqlROW['obj']]['name']; ?></td>
                                    <td><?php echo $sqlROW['spend']; ?></td>
                                    <td><?php echo $sqlROW['id']; ?></td>
                                <tr> 
                                <?php } ?>
                            </tbody>
                        </table>
					</div>
					<div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
						Et et consectetur ipsum labore excepteur est proident excepteur ad velit occaecat qui minim occaecat veniam. Fugiat veniam incididunt anim aliqua enim pariatur veniam sunt est aute sit dolor anim. Velit non irure adipisicing aliqua ullamco irure incididunt irure non esse consectetur nostrud minim non minim occaecat. Amet duis do nisi duis veniam non est eiusmod tempor incididunt tempor dolor ipsum in qui sit. Exercitation mollit sit culpa nisi culpa non adipisicing reprehenderit do dolore. Duis reprehenderit occaecat anim ullamco ad duis occaecat ex.
					</div>
					<div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
						Et et consectetur ipsum labore excepteur est proident excepteur ad velit occaecat qui minim occaecat veniam. Fugiat veniam incididunt anim aliqua enim pariatur veniam sunt est aute sit dolor anim. Velit non irure adipisicing aliqua ullamco irure incididunt irure non esse consectetur nostrud minim non minim occaecat. Amet duis do nisi duis veniam non est eiusmod tempor incididunt tempor dolor ipsum in qui sit. Exercitation mollit sit culpa nisi culpa non adipisicing reprehenderit do dolore. Duis reprehenderit occaecat anim ullamco ad duis occaecat ex.
					</div>
				</div>
			
			</div>
		</div>
	</div>
    
</section>

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
                    title: categoryData[0].title,
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
    <script type="text/javascript">
        $(document).ready(function() {
            $('#datatable').DataTable({
                "paging": true,           // Enable pagination
                "searching": true,        // Enable search bar
                "ordering": true,         // Enable sorting
                "info": true,             // Show table info
                "lengthMenu": [5, 10, 25, 50],  // Set options for number of rows per page
                "pageLength": 5           // Set default number of rows per page
            });
        });
    </script>
</body>
</html>
