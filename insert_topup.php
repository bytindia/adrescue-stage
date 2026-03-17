<?php
include 'header.php';
Auth();
ini_set("log_errors", 1);
ini_set("error_log", "logs/php-error.log");

$pgHeadline = 'Top-up Details';
$pgID = 8;

// Define number of days (e.g., from sheet G1 = 3.5)
$days = 3.5;

// Fetch data from the correct table
$result = $conn->query("SELECT * FROM insert_topup ORDER BY date DESC");

// Format number as per Indian style
function moneyFormatIndia($num)
{
    $explrestunits = "";
    if (strlen($num) > 3) {
        $lastthree = substr($num, -3);
        $restunits = substr($num, 0, -3);
        $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
        $expunit = str_split($restunits, 2);
        foreach ($expunit as $i => $unit) {
            $explrestunits .= ($i == 0) ? (int)$unit . "," : $unit . ",";
        }
        return $explrestunits . $lastthree;
    }
    return $num;
}

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

// Excel-style calculation logic (GST 18% + Days based multiplier + Ceiling to nearest 100)
function calculateTopUpExcelStyle($daily_budget, $current_balance, $days) {
    $gst = 0.18;

    // Calculate Top-Up amount as per sheet logic
    if ($current_balance == 0) {
        $topup_amount = $daily_budget;
    } elseif ($current_balance == $daily_budget) {
        $topup_amount = $daily_budget;
    } else {
        $topup_amount = $current_balance;
    }

    $base = $topup_amount * (1 + $gst);

    // Decide multiplier
    if ($topup_amount == 0) {
        $multiplier = $days - 1;
    } else {
        $multiplier = $days;
    }

    $topup = $base * $multiplier;

    // Round up to the next multiple of 100
    $topup_rounded = ceil($topup / 100) * 100;

    return $topup_rounded;
}

?>

<style>
    body {
        background-color: #f8f9fa;
        font-family: Arial, sans-serif;
    }

    .table {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table thead {
        background-color: #343a40;
        color: white;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }

    .btn {
        border-radius: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .btn:hover {
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .card_add {
        display: flex;
        align-content: center;
        justify-content: flex-end;
        text-align: right;
        margin-bottom: 15px;
    }

    .container h2 {
        margin-bottom: 25px;
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
            <div class="right_col" role="main">
                <div class="row">
                    <div class="container mt-4">
                        <h2 class="text-center">Google Top-Up Details</h2>
                        <div class="card_add">
                            <a href="add_topup-details.php" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i> Add Client</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead class="text-center">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Daily Budget</th>
                                        <th>Current Balance</th>
                                        <th>Top-Up Amount</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $id = $row['id'];
                                            $client = htmlspecialchars($row['clients_list']);

                                            // Raw numeric values
                                            $daily_budget_raw = $row['daily_budget'];
                                            $current_balance_raw = $row['current_balance'];

                                            // Calculate both amounts
                                            $topup_calculated = calculateTopUp($daily_budget_raw, $current_balance_raw);
                                            $total_amount_calculated = calculateTopUpExcelStyle($daily_budget_raw, $current_balance_raw, $days);

                                            // Update DB if necessary
                                            if (!isset($row['topup_amount']) || $row['topup_amount'] != $topup_calculated) {
                                                $stmt = $conn->prepare("UPDATE insert_topup SET topup_amount = ? WHERE id = ?");
                                                $stmt->bind_param("di", $topup_calculated, $id);
                                                $stmt->execute();
                                            }

                                            if (!isset($row['total_amount']) || $row['total_amount'] != $total_amount_calculated) {
                                                $stmt = $conn->prepare("UPDATE insert_topup SET total_amount = ? WHERE id = ?");
                                                $stmt->bind_param("di", $total_amount_calculated, $id);
                                                $stmt->execute();
                                            }

                                            // Format for UI
                                            $daily_budget = moneyFormatIndia($daily_budget_raw);
                                            $current_balance = moneyFormatIndia($current_balance_raw);
                                            $topup = moneyFormatIndia($topup_calculated);
                                            $total = moneyFormatIndia($total_amount_calculated);
                                            $status = $row['status'];

                                            echo "<tr>
                                                <td class='text-center'>{$id}</td>
                                                <td>{$client}</td>
                                                <td class='text-end'>{$daily_budget}</td>
                                                <td class='text-end'>{$current_balance}</td>
                                                <td class='text-end'>{$topup}</td>
                                                <td class='text-end'>{$total}</td>
                                                <td class='text-center'>{$status}</td>
                                                <td class='text-center'>
                                                    <a href='edit_card.php?id={$id}' class='btn btn-sm btn-primary' title='Edit'>
                                                        <i class='glyphicon glyphicon-pencil'></i>
                                                    </a>
                                                    <a href='delete_card.php?id={$id}' class='btn btn-sm btn-danger' title='Delete' onclick=\"return confirm('Delete this record?');\">
                                                        <i class='glyphicon glyphicon-trash'></i>
                                                    </a>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No top-up data found.</td></tr>";
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

<?php include 'footer.php'; ?>
