<?php include 'header.php';
// Enable error reporting for debugging (only for development)




$result = $conn->query("SELECT * FROM cards_data");

if (!$result) {
    die("Query failed: " . $conn->error);
}
// Optional: Enable custom logging to a file
ini_set("log_errors", 1);
ini_set("error_log", "logs/php-error.log"); // Make sure this path is writable

if (!isset($_SESSION['stDt'])) {
    $start = date('m/d/Y', strtotime('today - 30 days'));
    $end = date('m/d/Y');
    $_SESSION['stDt'] = $start;
    $_SESSION['enDt'] = $end;
}
$dt_q = '';
if (isset($_GET['st']) && $_GET['st'] != '') {
    $dt_q = '&st=' . $_GET['st'] . '&en=' . $_GET['en'];
}

Auth();
$pgHeadline = 'BYT Cards';
$pgID = 8;
$err = '';
function moneyFormatIndia($num)
{
    $explrestunits = "";
    if (strlen($num) > 3) {
        $lastthree = substr($num, strlen($num) - 3, strlen($num));
        $restunits = substr($num, 0, strlen($num) - 3); // extracts the last three digits
        $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for ($i = 0; $i < sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if ($i == 0) {
                $explrestunits .= (int)$expunit[$i] . ","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i] . ",";
            }
        }
        $thecash = $explrestunits . $lastthree;
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
<link rel="stylesheet" href="css/table.css" />
<style>
    body {
        background-color: #f8f9fa;
        /* Light gray background */
    }

    .table {
        border-radius: 10px;
        /* Rounded corners for the table */
        overflow: hidden;
        /* Ensure rounded corners apply */
    }

    .btn-primary,
    .btn-danger,
    .btn-success {
        border-radius: 20px;
        /* Rounded buttons */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Add shadow to buttons */
    }

    .btn-primary:hover,
    .btn-danger:hover,
    .btn-success:hover {
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        /* Slightly deeper shadow on hover */
    }

    .table thead {
        background-color: #343a40;
        /* Dark background for table header */
        color: white;
        /* White text for header */
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
        /* Light gray hover effect for rows */
    }
</style>
<style>
  
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
                    <div class="container  " style="padding: 20px;">
                        <h2 class=" mb-4">Card Details</h2>
                        <div class="card_add d-flex justify-content-end mb-3">
                            <a href="add_card.php" class="btn btn-success openModal" data-toggle="modal" data-target="#iframeModal" data-url="add_card.php" data-name="Add Card" data-val="edit"><i class="glyphicon glyphicon-plus"></i> Add Card</a>
                        </div>
                        <div class="table-responsive">
                            <table id="datatable" class="table table-hover table-bordered align-middle">
                                <thead class="text-center">
                                    <tr>
                                        <th>ID</th>
                                        <th>Card Name</th>
                                        <th>Card Limit</th>
                                        <th>Available Limit</th>
                                        <th>Total Payable</th>
                                        <th>Percentage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $id = htmlspecialchars($row['id']);
                                            $card = htmlspecialchars($row['card']);
                                            $card_limit = moneyFormatIndia($row['card_limit']);
                                            $available = moneyFormatIndia($row['available_limit']);
                                            $payable = moneyFormatIndia($row['total_payable']);
                                            $percentage = number_format($row['percentage'], 2);
                                            echo "<tr>
<td class='text-center'>{$id}</td>
<td>{$card}</td>
<td class='text-end'>{$card_limit}</td>
<td class='text-end'>{$available}</td>
<td class='text-end'>{$payable}</td>
<td class='text-center'>{$percentage}%</td>
<td class='text-center'>
  <div class='btn-group btn-group-sm' role='group'>
    <a href='edit_card.php?id={$id}' class='btn btn-primary openModal' data-toggle='modal' data-target='#iframeModal' data-url='edit_card.php?id={$id}' data-name='Edit Card' data-val='edit' title='Edit'>
      <i class='glyphicon glyphicon-pencil'></i>
    </a>
    <a href='delete_card.php?id={$id}' class='btn btn-danger' title='Delete' onclick=\"return confirm('Delete this card?');\">
      <i class='glyphicon glyphicon-trash'></i>
    </a>
  </div>
</td>
</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No cards found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
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

            $('#reportrange').daterangepicker({

                    dateLimit: {
                        days: 1000
                    },
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
                        daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                        firstDay: 1
                    }
                },
                function(start, end) {
                    if (start.format('DD/MM/YYYY') == defSt) {
                        console.log("Callback has been called!");
                        $('#reportrange span').html('');
                        $('#stDt_upd').val('');
                        $('#enDt_upd').val('');
                        window.location = 'cards.php';

                    } else {
                        //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                        $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                        window.location = 'cards.php?st=' + start.format('DD/MM/YYYY') + '&en=' + end.format('DD/MM/YYYY');
                        startDate = start;
                        endDate = end;
                        $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                        $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));

                    }
                }
            );

            <?php if (isset($_GET['st']) && $_GET['st'] != '') { ?>
                var d1 = '<?php echo $_GET['st']; ?>';
                var d2 = '<?php echo $_GET['en']; ?>';
                $('#reportrange span').html(d1 + ' - ' + d2);
                $("#reportrange").data().daterangepicker.startDate = moment(d1, datepicker.data().daterangepicker.format);
                $("#reportrange").data().daterangepicker.endDate = moment(d2, datepicker.data().daterangepicker.format);
                $("#reportrange").data().daterangepicker.updateCalendars();
            <?php }  ?>
        });
    </script>
    <script>
        function onAjax(id, ty, clName) {
            //var id = $(this).attr('data-id');
            //alert(id);
            <?php
            if (isset($_GET['st']) && $_GET['st'] != '') {
            ?>
                var page = 'cron-cards.php?tbl_id=' + id + '&refresh=1&ajax=1&<?php echo $dt_q; ?>';
                var dt1 = '(<?php echo date('d-m-Y', strtotime(str_replace("/", "-", $_GET['st']))); ?> to <?php echo date('d-m-Y', strtotime(str_replace("/", "-", $_GET['en']))); ?>)';
                // var dt2 = '<?php echo date('d-m-Y', strtotime(str_replace("/", "-", $_GET['en']))); ?>';
            <?php
            } else {
            ?>
                var page = 'ajax-cards.php';
                var dt1 = '';
            <?php
            }
            ?>

            $('.modal-body').html('loading...');
            $('#myModalLabel').html(clName + ' ' + dt1);
            $.ajax({
                type: 'POST',
                url: page,
                data: {
                    id: id,
                    ty: ty
                },
                success: function(data) {
                    //alert(data);
                    // $('#myModalLabel').html(clName);
                    $('.modal-body').html(data);
                },
                error: function(err) {
                    alert("error" + JSON.stringify(err));
                }
            });

        };
    </script>
    <!-- Modal for Add/Edit Card -->
    <div class="modal fade" id="iframeModal" tabindex="-1" role="dialog" aria-labelledby="iframeModalLabel">
      <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">X</button>
            <h4 class="modal-title" id="iframeModalLabel">Card</h4>
          </div>
          <div class="modal-body">
            <div style="position:relative; height:100%;">
              <div id="iframeLoader"
                  style="position:absolute; top:0; left:0; right:0; bottom:0; z-index:10;
                          background: #fff; display:flex; justify-content:center; align-items:center;">
                <i class="loader"><span class="loader-text">Loading...</span></i>
              </div>
              <iframe id="modalIframe" src="" style="width:100%; height:100%; border:none;"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script>
    // Modal logic (copied/adapted from invoice.php)
    let texts = [];
    let currentTextIndex = 0;
    let textInterval;
    let loaderText;
    function cycleText() {
      if (loaderText) {
        loaderText.innerText = texts[currentTextIndex];
        currentTextIndex = (currentTextIndex + 1) % texts.length;
      }
    }
    document.addEventListener('DOMContentLoaded', function () {
      const modalIframe = document.getElementById('modalIframe');
      const modalTitle = document.getElementById('iframeModalLabel');
      loaderText = document.querySelector('.loader .loader-text');
      // Use event delegation for openModal buttons to work with DataTable pagination
      $(document).on('click', '.openModal', function (e) {
        e.preventDefault();
        const url = this.getAttribute('data-url');
        const name = this.getAttribute('data-name') || 'Loading...';
        const dataVal = this.getAttribute('data-val') || 'default';
        modalTitle.textContent = name;
        document.getElementById('iframeLoader').style.display = 'flex';
        modalIframe.style.display = 'block';
        modalIframe.src = url;
        const textOptions = {
          edit: ["Loading Form", "Fetching Details", "Almost Ready"],
          default: ["Loading...", "Please Wait", "Initializing"]
        };
        texts = textOptions[dataVal] || textOptions['default'];
        currentTextIndex = 0;
        cycleText();
        clearInterval(textInterval);
        textInterval = setInterval(cycleText, 1500);
        $('#iframeModal').modal('show');
      });
      modalIframe.onload = function () {
        setTimeout(() => {
          document.getElementById('iframeLoader').style.display = 'none';
        }, 500);
      };
      $('#iframeModal').on('hidden.bs.modal', function () {
        currentTextIndex = 0;
        modalIframe.src = '';
        document.getElementById('iframeLoader').style.display = 'flex';
        clearInterval(textInterval);
      });
    });
    // DataTable initialization with proper configuration for pagination
    $(document).ready(function() {
      if ($.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#datatable')) {
          $('#datatable').DataTable().destroy();
        }
        $('#datatable').DataTable({
          dom: 'Bfrtip',
          buttons: [
            { extend: 'excel', className: 'btn btn-primary' },
            { extend: 'csv', className: 'btn btn-primary' },
            { extend: 'pdf', className: 'btn btn-primary' },
            { extend: 'print', className: 'btn btn-primary' }
          ],
          pageLength: 50,
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
          drawCallback: function() {
            // Re-initialize any UI elements after each draw
            $('[data-toggle="tooltip"]').tooltip();
          }
        });
      }
    });
    </script>
</body>
</html>