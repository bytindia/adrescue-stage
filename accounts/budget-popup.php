<?php
// Total Budget Details Popup - Loaded via AJAX
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

// Process budget data (bud)
$tableData = [];
foreach ($clientRows as $row) {
    $bud = isset($row['bud']) ? (float)str_replace([',', ' '], '', $row['bud']) : 0;
    if ($bud > 0) {
        $tableData[] = [
            'client' => isset($row['name']) ? trim($row['name']) : '',
            'budget' => $bud,
            'cc_card' => isset($row['cc_card']) ? $row['cc_card'] : ''
        ];
    }
}

// Sort by budget (descending)
usort($tableData, function($a, $b) {
    return $b['budget'] <=> $a['budget'];
});
?>

<div class="table-responsive">
    <table id="budgetDataTable" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th>Client</th>
                <th>CC Card</th>
                <th class="text-right">Budget</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableData as $item): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($item['client']); ?></strong></td>
                <td><?php echo htmlspecialchars($item['cc_card']); ?></td>
                <td class="text-right" data-order="<?php echo $item['budget']; ?>">₹<?php echo moneyFormatIndia((int)$item['budget']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
// Initialize DataTable after content is loaded
if ($.fn.DataTable && $('#budgetDataTable').length) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#budgetDataTable')) {
        $('#budgetDataTable').DataTable().destroy();
    }
    $('#budgetDataTable').DataTable({
        "order": [[2, "desc"]], // Sort by Budget column descending
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

