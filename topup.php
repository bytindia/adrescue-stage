<?php include 'header.php';
// Enable error reporting for debugging (only for development)




//$result = $conn->query("SELECT * FROM topup");
$result=mysqli_query($conn, "SELECT * FROM topup");
if (!$result) {
  // die("Query failed: " . $conn->error);
}
// Optional: Enable custom logging to a file
ini_set("log_errors", 0);
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
$pgHeadline = 'Topup';
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
    .blue {
        cursor: pointer;
    }

    thead {
        color: green;
        background: #fff;
    }

    tfoot {
        color: red;
    }

    .even {
        background: #fff;
    }

    .blue_txt {
        color: blue;
        font-weight: bold;
    }

    /* General body styling */
    body {
        background-color: #f8f9fa;
        /* Light gray background */
        font-family: Arial, sans-serif;
    }

    /* Table styling */
    .table {

        width: 50%;
        border-radius: 10px;
        /* Rounded corners for the table */
        overflow: hidden;
        /* Ensure rounded corners apply */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Add shadow to the table */
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

    /* Button styling */
    .btn {
        border-radius: 20px;
        /* Rounded buttons */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Add shadow to buttons */
        transition: all 0.3s ease;
        /* Smooth hover effect */
    }

    .btn:hover {
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        /* Slightly deeper shadow on hover */
        transform: translateY(-2px);
        /* Lift effect on hover */
    }

    /* Card styling */
    .card {
        border-radius: 10px;
        /* Rounded corners for cards */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Add shadow to cards */
        overflow: hidden;
        /* Ensure rounded corners apply */
    }

    .card-header {
        background-color: #007bff;
        /* Primary color */
        color: white;
        /* White text */
        font-weight: bold;
        text-align: center;
    }

    /* Modal styling */
    .modal-content {
        border-radius: 10px;
        /* Rounded corners for modals */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Add shadow to modals */
    }

    /* Custom hover effects for links */
    a:hover {
        text-decoration: none;
        /* Remove underline on hover */
        color: #0056b3;
        /* Darker blue for hover */
    }

    .card_add {
        margin-left: 30%;
    }

    .editable-field {
        cursor: pointer;
        display: inline-block;
        min-width: 50px;
    }

    .editable-input {
        width: 80px;
        border: 1px solid #ccc;
        padding: 2px 4px;
    }

    .card_add.d-flex.justify-content-start.gap-2.mb-3 {
        display: flex !important;
    }
</style>
<?php
function calculateTopupAmount($dailyBudget, $currentBalance)
{
    // If daily budget is 0 or empty, return 0
    if (empty($dailyBudget) || $dailyBudget == 0) {
        return 0;
    }

    // Required amount = daily budget * 1.18, rounded up to nearest 100
    $required = ceil(($dailyBudget * 1.18) / 100) * 100;

    // If balance is sufficient
    if ($currentBalance >= $required) {
        return 0;
    }

    // Calculate the shortfall
    $shortfall = $required - $currentBalance;

    // Round up shortfall and ensure minimum of 500
    return max(500, ceil($shortfall / 100) * 100);
}
?>

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
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                                    <div style="flex:1; min-width:200px;">
                                        <h2 style="margin-bottom:0; text-align:left;">Topup</h2>
                                    </div>
                                    <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex:1;">
                                        <ul class="nav navbar-right panel_toolbox btn-group" style="margin-bottom:0;">
                                            <li><a href="loading.php?pg=topup-cron.php" class="btn btn-primary btn-sm">Fetch Live Budget</a></li>
                                            <li style="display:flex; align-items:center;">
                                                <input type="text" name="no_days_topup" placeholder="No. of days" class="no_days_topup form-control input-sm" value="1" style="max-width: 120px;">
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="x_content">
                                <?php include 'alert.php'; ?>
                                <div class="table-responsive">
                            <table id="datatable" class="table table-hover table-striped table-bordered datatable align-middle" style="width:100%;">
                                <thead class="text-center">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Daily Budget</th>
                                        <th>Balance</th>
                                        <th>Topup (1 d)</th>
                                        <th id="topupLabel">Topup (1 d)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php
$total_topup = 0;
//echo $result->num_rows; 

$sqlRev=mysqli_query($conn, "SELECT * FROM topup ");
	//while($sqlROW=mysqli_fetch_array($sqlRev)) { $getRows[] = $sqlROW; }
   // d($getRows);

    while ($row = mysqli_fetch_array($sqlRev)) {
        //d($row);
        $id = htmlspecialchars($row['tbl_id']);
        $client = htmlspecialchars($row['client']);
        $daily_budget_raw = $row['daily_budget'];
        $bud_bal_raw = $row['bud_bal'];
        $daily_bud = moneyFormatIndia($daily_budget_raw);
        $bud_balance = moneyFormatIndia($bud_bal_raw);
        $topup_amount = calculateTopupAmount($daily_budget_raw, $bud_bal_raw);

        $total_topup += $topup_amount;

        echo "<tr>
            <td class='text-center'>{$id}</td>
            <td>{$client}</td>
            <td class='text-end'>
                <span class='editable-field' data-id='{$id}' data-field='daily_budget'>{$daily_budget_raw}</span>
            </td>
            <td class='text-end'>
                <span class='editable-field' data-id='{$id}' data-field='bud_bal'>" 
                    . (!empty($bud_bal_raw) && $bud_bal_raw != 0 ? htmlspecialchars($bud_bal_raw) : '✎') .
                "</span>
            </td>
            <td class='text-end'>" . moneyFormatIndia($topup_amount) . "</td>
            <td class='text-end topup-calc'></td>
            <td class='text-center'>
                <div class='btn-group btn-group-sm' role='group'>
                    <a href='topup-edit.php?id={$id}' class='btn btn-outline-primary' title='Edit'>
                        <i class='fa fa-pencil'></i>
                    </a>
                    <a href='topup.php?id={$id}' class='btn btn-outline-danger' title='Delete' onclick=\"return confirm('Are you sure you want to delete this?');\">
                        <i class='fa fa-trash-o'></i>
                    </a>
                </div>
            </td>
        </tr>";
    }

?>
</tbody>

<tfoot>
    <tr>
        <td colspan="5" class="text-end fw-bold">Total</td>
        <td class="text-end fw-bold" id="topupTotal"><?= moneyFormatIndia($total_topup); ?></td>
        <td></td>
    </tr>
</tfoot>



                            </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /page content -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
  document.querySelector('.no_days_topup').addEventListener('input', function () {
    const days = this.value || 1;

    $.ajax({
        url: 'get_topup_total.php',
        method: 'POST',
        data: { days: days },
        success: function (res) {
            $('#topupTotal').text(res); // Update the footer total with PHP result
            updateTopups(); // Still update frontend display row-wise
        },
        error: function () {
            alert('Failed to fetch total topup from server.');
        }
    });
});

    </script>

    <script>
        $(document).on('click', '#refreshPage', function() {
            location.reload();
        });

        $(document).ready(function() {
            $(document).on('click', '.editable-field', function() {
                const span = $(this);
                const rawText = span.text().trim();
                const isEmpty = rawText === '✎';
                const value = isEmpty ? '' : rawText;
                const id = span.data('id');
                const field = span.data('field');

                const input = $('<input>')
                    .val(value)
                    .addClass('editable-input')
                    .data('id', id)
                    .data('field', field);

                span.replaceWith(input);
                input.focus();
            });

            $(document).on('blur', '.editable-input', function() {
                const input = $(this);
                const value = input.val().trim();
                const id = input.data('id');
                const field = input.data('field');

                // Save via AJAX
                $.ajax({
                    url: 'update_topup.php',
                    type: 'POST',
                    data: {
                        id: id,
                        field: field,
                        value: value
                    },
                    success: function(response) {
                        const displayValue = value !== '' ? value : '✎';
                        const newSpan = $('<span>')
                            .text(displayValue)
                            .addClass('editable-field')
                            .data('id', id)
                            .data('field', field);

                        input.replaceWith(newSpan);

                        // 🟢 Recalculate topups if needed
                        if (typeof updateTopups === 'function') {
                            updateTopups();
                        }
                    },
                    error: function() {
                        alert('Update failed.');
                        const displayValue = value !== '' ? value : '✎';
                        const fallbackSpan = $('<span>')
                            .text(displayValue)
                            .addClass('editable-field')
                            .data('id', id)
                            .data('field', field);
                        input.replaceWith(fallbackSpan);
                    }
                });
            });
        });
    </script>




    <!-- /page content -->
    <?php include 'footer.php'; ?>
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>-->

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#datatable')) {
                var dt = $('#datatable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'excel', className: 'btn btn-primary' },
                        { extend: 'csv', className: 'btn btn-primary' },
                        { extend: 'pdf', className: 'btn btn-primary' },
                        { extend: 'print', className: 'btn btn-primary' }
                    ],
                    pageLength: 50,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']]
                });
                dt.on('draw', function(){
                    // Recalculate after pagination/sort/search redraw
                    if (typeof updateTopups === 'function') { updateTopups(); }
                });
            }

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
                        window.location = 'topup.php';

                    } else {
                        //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                        $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                        window.location = 'topup.php?st=' + start.format('DD/MM/YYYY') + '&en=' + end.format('DD/MM/YYYY');
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
    <style>
    .btn-group .btn { margin-right: 1px; font-size: 13px; padding: 3px 10px !important; }
    .btn-outline-primary { color: #1976d2; background: #fff; border: 1px solid #1976d2; }
    .btn-outline-primary:hover { background: #1976d2; color: #fff; }
    .btn-outline-danger { color: #d32f2f; background: #fff; border: 1px solid #d32f2f; }
    .btn-outline-danger:hover { background: #d32f2f; color: #fff; }
    </style>
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
    <script>
        function ceilToNearest100(value) {
            return Math.ceil(value / 100) * 100;
        }

        function calculateTopup(dailyBudget, currentBalance, days) {
            if (!dailyBudget || dailyBudget == 0) return 0;

            const requiredAmount = ceilToNearest100(dailyBudget * 1.18 * days);
            if (currentBalance >= requiredAmount) return 0;

            const shortage = requiredAmount - currentBalance;
            return Math.max(500, ceilToNearest100(shortage));
        }

        function updateTopups() {
            const inputEl = document.querySelector('.no_days_topup');
            const days = parseFloat(inputEl && inputEl.value) || 1;

            document.querySelectorAll('#datatable tbody tr').forEach(row => {
                const dailyBudEl = row.querySelector('td:nth-child(3)');
                const balanceEl = row.querySelector('td:nth-child(4)');
                const topupCell = row.querySelector('td.topup-calc') || row.querySelector('td:nth-child(6)');

                const dailyBud = parseFloat((dailyBudEl && dailyBudEl.textContent || '').replace(/,/g, '')) || 0;
                const balance = parseFloat((balanceEl && balanceEl.textContent || '').replace(/,/g, '')) || 0;

                const topupValue = calculateTopup(dailyBud, balance, days);

                if (topupCell) {
                    topupCell.textContent = topupValue.toLocaleString('en-IN');
                }
            });
        }

        const daysInputEl = document.querySelector('.no_days_topup');
        if (daysInputEl) {
            daysInputEl.addEventListener('input', updateTopups);
        }

        // Run once on page load
        document.addEventListener('DOMContentLoaded', updateTopups);

        function pluralizeDay(n) {
            return n == 1 ? 'd' : 'd';
        }

        function updateTopupLabel(days) {
            const label = document.getElementById('topupLabel');
            if (label) {
                label.textContent = `Topup (${days} ${pluralizeDay(days)})`;
            }
        }

        document.querySelector('.no_days_topup').addEventListener('input', function() {
            const days = parseInt(this.value) || 1;
            updateTopupLabel(days);
            updateTopups(); // Recalculate row values too
        });

        // On page load
        document.addEventListener('DOMContentLoaded', () => {
            const initialDays = parseInt(document.querySelector('.no_days_topup').value) || 1;
            updateTopupLabel(initialDays);
        });
    </script>