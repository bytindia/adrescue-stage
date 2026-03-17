<?php
// Payment Received Details Popup - Loaded via AJAX
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

// Fetch SOA API data
$soaJson = curl_get_contents1("https://stage.adrescue.in/soa-api.php");
$soaData = json_decode($soaJson, true);
$receiptsRows = isset($soaData['receipts']) && is_array($soaData['receipts']) ? $soaData['receipts'] : [];

// Filter receipts for current month
$currentYear = date('Y');
$currentMonth = date('m');
$tableData = [];

foreach ($receiptsRows as $receipt) {
    if (empty($receipt['date'])) continue;
    
    // Parse date in d-m-Y format (e.g., "25-04-2025")
    $dt = DateTime::createFromFormat('d-m-Y', $receipt['date']);
    if ($dt && $dt->format('Y') == $currentYear && $dt->format('m') == $currentMonth) {
        $amount = isset($receipt['amount']) ? (float)str_replace([',', ' '], '', $receipt['amount']) : 0;
        if ($amount > 0) {
            $tableData[] = [
                'client' => isset($receipt['client']) ? trim($receipt['client']) : '',
                'date' => $receipt['date'],
                'date_sort' => $dt->format('Y-m-d'),
                'amount' => $amount
            ];
        }
    }
}

// Sort by date descending
usort($tableData, function($a, $b) {
    return strcmp($b['date_sort'], $a['date_sort']);
});
?>

<div class="table-responsive">
    <table id="paymentReceivedDataTable" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableData as $item): ?>
            <tr>
                <td data-order="<?php echo $item['date_sort']; ?>"><?php echo htmlspecialchars($item['date']); ?></td>
                <td><?php echo htmlspecialchars($item['client']); ?></td>
                <td class="text-right" data-order="<?php echo $item['amount']; ?>">₹<?php echo moneyFormatIndia((int)$item['amount']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
// Initialize DataTable after content is loaded
if ($.fn.DataTable && $('#paymentReceivedDataTable').length) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#paymentReceivedDataTable')) {
        $('#paymentReceivedDataTable').DataTable().destroy();
    }
    $('#paymentReceivedDataTable').DataTable({
        "order": [[0, "desc"]], // Sort by Date descending
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

