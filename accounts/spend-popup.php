<?php
// Total Spend Details Popup - Loaded via AJAX
session_start();
require_once __DIR__ . '/../db.php';
Auth();

// Helper: fetch remote URL
if (!function_exists('curl_get_contents1')) {
    function curl_get_contents1($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}

// Helper: Indian money format
if (!function_exists('moneyFormatIndia')) {
    function moneyFormatIndia($num)
    {
        if ($num < 0) {
            return '-' . moneyFormatIndia(abs($num));
        }
        $explrestunits = "";
        if (strlen($num) > 3) {
            $lastthree = substr($num, strlen($num) - 3, strlen($num));
            $restunits = substr($num, 0, strlen($num) - 3);
            $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
            $expunit = str_split($restunits, 2);
            for ($i = 0; $i < sizeof($expunit); $i++) {
                if ($i == 0) {
                    $explrestunits .= (int)$expunit[$i] . ",";
                } else {
                    $explrestunits .= $expunit[$i] . ",";
                }
            }
            $thecash = $explrestunits . $lastthree;
        } else {
            $thecash = $num;
        }
        return $thecash;
    }
}

// Fetch Budget API data
$budgetJson = curl_get_contents1("https://stage.adrescue.in/budget-api-json.php");
$budgetData = json_decode($budgetJson, true);
$clientRows = isset($budgetData['client']) && is_array($budgetData['client']) ? $budgetData['client'] : [];

// Process spend data
$tableData = [];
foreach ($clientRows as $row) {
    $spend = isset($row['spend']) ? (float)str_replace([',', ' '], '', $row['spend']) : 0;
    if ($spend > 0) {
        $tableData[] = [
            'client' => isset($row['name']) ? trim($row['name']) : '',
            'spend' => $spend,
            'spend_fb' => isset($row['spend_fb']) ? (float)str_replace([',', ' '], '', $row['spend_fb']) : 0,
            'spend_g' => isset($row['spend_g']) ? (float)str_replace([',', ' '], '', $row['spend_g']) : 0
        ];
    }
}

// Sort by spend (descending)
usort($tableData, function($a, $b) {
    return $b['spend'] <=> $a['spend'];
});
?>

<div class="table-responsive">
    <table id="spendDataTable" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th>Client</th>
                <th class="text-right">Meta Spend</th>
                <th class="text-right">Google Spend</th>
                <th class="text-right">Total Spend</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableData as $item): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($item['client']); ?></strong></td>
                <td class="text-right" data-order="<?php echo $item['spend_fb']; ?>">₹<?php echo moneyFormatIndia((int)$item['spend_fb']); ?></td>
                <td class="text-right" data-order="<?php echo $item['spend_g']; ?>">₹<?php echo moneyFormatIndia((int)$item['spend_g']); ?></td>
                <td class="text-right" data-order="<?php echo $item['spend']; ?>">₹<?php echo moneyFormatIndia((int)$item['spend']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
// Initialize DataTable after content is loaded
if ($.fn.DataTable && $('#spendDataTable').length) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#spendDataTable')) {
        $('#spendDataTable').DataTable().destroy();
    }
    $('#spendDataTable').DataTable({
        "order": [[3, "desc"]], // Sort by Total Spend column descending
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "No entries to show",
            "infoFiltered": "(filtered from _MAX_ total entries)"
        }
    });
}
</script>

