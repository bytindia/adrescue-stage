<?php
include 'db.php'; // or your DB connection

$days = isset($_POST['days']) ? max(1, (int)$_POST['days']) : 1;

function calculateTopupAmount($dailyBudget, $currentBalance, $days)
{
    if (empty($dailyBudget) || $dailyBudget == 0) return 0;

    $required = ceil(($dailyBudget * 1.18 * $days) / 100) * 100;
    if ($currentBalance >= $required) return 0;

    $shortfall = $required - $currentBalance;
    return max(500, ceil($shortfall / 100) * 100);
}

function moneyFormatIndia($num)
{
    $explrestunits = "";
    if (strlen($num) > 3) {
        $lastthree = substr($num, strlen($num) - 3);
        $restunits = substr($num, 0, strlen($num) - 3);
        $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
        $expunit = str_split($restunits, 2);
        foreach ($expunit as $i => $val) {
            $explrestunits .= ($i == 0) ? (int)$val . "," : $val . ",";
        }
        $num = $explrestunits . $lastthree;
    }
    return $num;
}

$total = 0;
$res = mysqli_query($conn, "SELECT daily_budget, bud_bal FROM topup");
while ($row = mysqli_fetch_assoc($res)) {
    $total += calculateTopupAmount($row['daily_budget'], $row['bud_bal'], $days);
}

echo moneyFormatIndia($total);
