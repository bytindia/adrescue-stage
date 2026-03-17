<?php include 'header.php'; 
// Enable error reporting for debugging (only for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic PHP logic for Top-Up (Minimum 500 + 1.5% GST)
function calculateTopUp($daily_budget, $current_balance) {
    $difference = $daily_budget - $current_balance;
    $gst = 0.015;

    if ($difference < 500) {
        $topup = 500;
    } else {
        $topup = $difference;
    }

    $topup_with_gst = $topup + ($topup * $gst);
    return round($topup_with_gst, 2);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $clients_list = $_POST['clients_list'];
    $daily_budget = (int)$_POST['daily_budget'];
    $current_balance = (int)$_POST['current_balance'];
    $status = $_POST['status'];
    $topup_amount = calculateTopUp($daily_budget, $current_balance);
    $total_amount = $topup_amount;

    $stmt = $conn->prepare("INSERT INTO insert_topup (clients_list, daily_budget, current_balance, topup_amount, total_amount, status, date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siidss", $clients_list, $daily_budget, $current_balance, $topup_amount, $total_amount, $status);
    $stmt->execute();

    $_SESSION['suc'] = "Client Top-up added successfully!";
    header("Location: cards.php");
    exit;
}

$result = $conn->query("SELECT * FROM cards_data");

if (!$result) {
    die("Query failed: " . $conn->error);
}
// Optional: Enable custom logging to a file
ini_set("log_errors", 1);
ini_set("error_log", "logs/php-error.log"); // Make sure this path is writable

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
$dt_q ='';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
}

Auth();
$pgHeadline = 'BYT Cards';
$pgID = 8;
$err =''; 
function moneyFormatIndia($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}
// if(isset($_GET['del'])) {
// 	mysqli_query($conn, "UPDATE card_changes SET delete_status='1' where tbl_id=".$_GET['del']."");
// 	$_SESSION['suc'] = 'Successfully Deleted!';	
// 	echo "<script>window.location = 'cards.php';</script>";
// 	exit();
// }

// include 'pagination.php';

// $page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
// if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

// $startpoint = ($page * $per_page) - $per_page;
// $statement = " card WHERE uid='".$_SESSION['uid']."' AND delete_status=0";



?>   

<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">-->
<!-- Bootstrap Icons -->
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">-->
<style>
    body {
        background-color: #f8f9fa; /* Light gray background */
    }
    .table {
        border-radius: 10px; /* Rounded corners for the table */
        overflow: hidden; /* Ensure rounded corners apply */
    }
    .btn-primary, .btn-danger, .btn-success {
        border-radius: 20px; /* Rounded buttons */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to buttons */
    }
    .btn-primary:hover, .btn-danger:hover, .btn-success:hover {
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* Slightly deeper shadow on hover */
    }
    .table thead {
        background-color: #343a40; /* Dark background for table header */
        color: white; /* White text for header */
    }
    .table-hover tbody tr:hover {
        background-color: #f1f1f1; /* Light gray hover effect for rows */
    }
</style>
<style>
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }

/* General body styling */
body {
    background-color: #f8f9fa; /* Light gray background */
    font-family: Arial, sans-serif;
}

/* Table styling */
.table {
    border-radius: 10px; /* Rounded corners for the table */
    overflow: hidden; /* Ensure rounded corners apply */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to the table */
}

.table thead {
    background-color: #343a40; /* Dark background for table header */
    color: white; /* White text for header */
}

.table-hover tbody tr:hover {
    background-color: #f1f1f1; /* Light gray hover effect for rows */
}

/* Button styling */
.btn {
    border-radius: 20px; /* Rounded buttons */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to buttons */
    transition: all 0.3s ease; /* Smooth hover effect */
}

.btn:hover {
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* Slightly deeper shadow on hover */
    transform: translateY(-2px); /* Lift effect on hover */
}

/* Card styling */
.card {
    border-radius: 10px; /* Rounded corners for cards */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to cards */
    overflow: hidden; /* Ensure rounded corners apply */
}

.card-header {
    background-color: #007bff; /* Primary color */
    color: white; /* White text */
    font-weight: bold;
    text-align: center;
}

/* Modal styling */
.modal-content {
    border-radius: 10px; /* Rounded corners for modals */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to modals */
}

/* Custom hover effects for links */
a:hover {
    text-decoration: none; /* Remove underline on hover */
    color: #0056b3; /* Darker blue for hover */
}

/* General body styling */
body {
    background-color: #f8f9fa; /* Light gray background */
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

/* Card styling */
.card {
    border-radius: 10px; /* Rounded corners for cards */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to cards */
    overflow: hidden; /* Ensure rounded corners apply */
    margin-bottom: 20px; /* Add spacing between cards */
}

.card-header {
    background-color: #007bff; /* Primary color */
    color: white; /* White text */
    font-weight: bold;
    text-align: center;
    padding: 15px;
}

.card-body {
    padding: 20px;
}

/* Form styling */
.form-label {
    font-weight: bold;
    margin-bottom: 5px;
}

.form-control {
    border-radius: 5px;
    padding: 10px;
    font-size: 14px;
}

/* Button styling */
.btn {
    border-radius: 20px; /* Rounded buttons */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow to buttons */
    transition: all 0.3s ease; /* Smooth hover effect */
    font-size: 16px;
    padding: 10px 15px;
}

.btn:hover {
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* Slightly deeper shadow on hover */
    transform: translateY(-2px); /* Lift effect on hover */
}

/* Responsive design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    .card {
        margin: 10px 0;
    }

    .form-control {
        font-size: 12px;
        padding: 8px;
    }

    .btn {
        font-size: 14px;
        padding: 8px 12px;
    }

    .card-header h4 {
        font-size: 18px;
    }
}

@media (max-width: 576px) {
    .card-header h4 {
        font-size: 16px;
    }

    .btn {
        font-size: 12px;
        padding: 6px 10px;
    }
}
</style>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

       <!-- page content -->
<!-- page content -->
<div class="right_col" role="main">
  <div class="row">
  

  <div class="container p-5" style="padding: 30px;>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="text-center">Add New TopUp Details</h4>
                    </div>
                    <div class="card-body">
                    <form method="POST">
                    <div class="mb-3">
                        <label>Client Name</label>
                        <input type="text" name="clients_list" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Daily Budget</label>
                        <input type="number" name="daily_budget" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Current Balance</label>
                        <input type="number" name="current_balance" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Active">Active</option>
                            <option value="Paused">Paused</option>
                            <option value="Stopped">Stopped</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Add</button>
                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
      </div>
    </div>
  </div>
</div>
<!-- /page content -->


        <!-- /page content -->
        <?php include 'footer.php'; ?>
        <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>-->

  <script>
$(document).ready(function() {
	
var defSt = '01/01/2018';
var defEnd = '01/01/2024';

        $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            ranges: {
                'All': [defSt, defEnd],
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                'Last 7 Days': [moment().subtract('days', 6), moment()],
                'Last 30 Days': [moment().subtract('days', 29), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
            },
            opens: 'left',
            buttonClasses: ['btn btn-default'],
            applyClass: 'btn-small btn-primary',
            cancelClass: 'btn-small',
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
        },
        function(start, end) {
            if(start.format('DD/MM/YYYY')==defSt) {
                console.log("Callback has been called!");
                $('#reportrange span').html(''); 
                $('#stDt_upd').val('');
                $('#enDt_upd').val('');
                window.location = 'cards.php';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'cards.php?st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );

        <?php if(isset($_GET['st']) && $_GET['st']!='') { ?>
          var d1 = '<?php echo $_GET['st']; ?>';
          var d2 = '<?php echo $_GET['en']; ?>';
          $('#reportrange span').html(d1 + ' - ' + d2);
          $("#reportrange").data().daterangepicker.startDate = moment(d1, datepicker.data().daterangepicker.format );
          $("#reportrange").data().daterangepicker.endDate = moment(d2, datepicker.data().daterangepicker.format );
          $("#reportrange").data().daterangepicker.updateCalendars();
        <?php }  ?>
});
					
	</script>
  <script>
function onAjax(id,ty,clName) {
					//var id = $(this).attr('data-id');
					//alert(id);
          <?php
          if(isset($_GET['st']) && $_GET['st']!=''){
          ?>
            var page = 'cron-cards.php?tbl_id='+id+'&refresh=1&ajax=1&<?php echo $dt_q; ?>';
            var dt1 = '(<?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['st']))); ?> to <?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['en']))); ?>)';
           // var dt2 = '<?php echo date('d-m-Y', strtotime(str_replace("/","-",$_GET['en']))); ?>';
          <?php
          } else {
          ?>
            var page = 'ajax-cards.php';
            var dt1 ='';
          <?php
          }
          ?>

					$('.modal-body').html('loading...');
          $('#myModalLabel').html(clName+' '+dt1);
					$.ajax({
              type: 'POST',
              url: page,
              data:{id: id, ty: ty},
              success: function(data) {
                //alert(data);
               // $('#myModalLabel').html(clName);
                $('.modal-body').html(data);
              },
              error:function(err){
                alert("error"+JSON.stringify(err));
              }
					});
					
};
</script>
