<?php
// Outstanding Details Popup - Loaded via AJAX
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
$outstandingRows = isset($soaData['outstanding']) && is_array($soaData['outstanding']) ? $soaData['outstanding'] : [];
$receiptsRows = isset($soaData['receipts']) && is_array($soaData['receipts']) ? $soaData['receipts'] : [];

// Process receipts to find last payment date and amount per client
$lastPaymentByClient = [];
foreach ($receiptsRows as $receipt) {
    if (empty($receipt['date']) || empty($receipt['client'])) continue;
    
    $client = trim($receipt['client']);
    $dateStr = $receipt['date'];
    $amount = isset($receipt['amount']) ? (float)str_replace([',', ' '], '', $receipt['amount']) : 0;
    
    // Parse date (d-m-Y format)
    $dt = DateTime::createFromFormat('d-m-Y', $dateStr);
    if (!$dt) continue;
    
    // Normalize client name
    $clientNormalized = strtoupper(trim(preg_replace('/\s*-\s*PI\s*$/i', '', $client)));
    $clientNormalized = preg_replace('/\s+/', ' ', $clientNormalized);
    
    // Store both full name and normalized key with amount
    if (!isset($lastPaymentByClient[$clientNormalized]) || $dt > $lastPaymentByClient[$clientNormalized]['date']) {
        $lastPaymentByClient[$clientNormalized] = [
            'date' => $dt,
            'amount' => $amount
        ];
    }
    
    // Also store by first word for matching
    $firstWord = explode(' ', $clientNormalized)[0];
    if (strlen($firstWord) > 2 && (!isset($lastPaymentByClient[$firstWord]) || $dt > $lastPaymentByClient[$firstWord]['date'])) {
        $lastPaymentByClient[$firstWord] = [
            'date' => $dt,
            'amount' => $amount
        ];
    }
}

// Calculate days ago and prepare data
$today = new DateTime();
$tableData = [];

foreach ($outstandingRows as $row) {
    $outstanding = isset($row['outstanding']) ? (float)str_replace([',', ' '], '', $row['outstanding']) : 0;
    if ($outstanding > 0) {
        $client = isset($row['client']) ? trim($row['client']) : '';
        $clientKey = strtoupper(preg_replace('/\s+/', ' ', $client));
        
        // Try to find last payment
        $lastPaymentInfo = null;
        if (isset($lastPaymentByClient[$clientKey])) {
            $lastPaymentInfo = $lastPaymentByClient[$clientKey];
        } else {
            // Try matching by first word
            $firstWord = explode(' ', $clientKey)[0];
            if (strlen($firstWord) > 2 && isset($lastPaymentByClient[$firstWord])) {
                $lastPaymentInfo = $lastPaymentByClient[$firstWord];
            }
        }
        
        $lastPaymentText = '-';
        if ($lastPaymentInfo) {
            $diff = $today->diff($lastPaymentInfo['date']);
            $daysAgo = $diff->days;
            $amount = moneyFormatIndia((int)$lastPaymentInfo['amount']);
            $lastPaymentText = '₹' . $amount;
        }
        
        $daysAgoNum = null;
        if ($lastPaymentInfo) {
            $diff = $today->diff($lastPaymentInfo['date']);
            $daysAgoNum = $diff->days;
        }
        
        $tableData[] = [
            'client' => $client,
            'outstanding' => $outstanding,
            'lastPayment' => $lastPaymentText,
            'daysAgo' => $daysAgoNum,
            'outstandingSort' => $outstanding
        ];
    }
}

// Sort by outstanding amount (descending)
usort($tableData, function($a, $b) {
    return $b['outstandingSort'] <=> $a['outstandingSort'];
});
?>

<div class="table-responsive">
    <table id="outstandingDataTable" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th>Client</th>
                <th class="text-right">Outstandings</th>
                <th class="text-center">Last Payment</th>
                <th class="text-center">d ago</th>
                <th class="text-center">WhatsApp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableData as $item): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($item['client']); ?></strong></td>
                <td class="text-right" data-order="<?php echo $item['outstanding']; ?>">₹<?php echo moneyFormatIndia((int)$item['outstanding']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($item['lastPayment']); ?></td>
                <td class="text-center" data-order="<?php echo $item['daysAgo'] !== null ? $item['daysAgo'] : 999999; ?>"><?php echo $item['daysAgo'] !== null ? $item['daysAgo'] : '-'; ?></td>
                <td class="text-center">
                    <?php
                    $outstandingFormatted = moneyFormatIndia((int)$item['outstanding']);
                    $whatsappMessage = 'Hi ' . htmlspecialchars($item['client']) . ', your outstanding amount is ₹' . $outstandingFormatted . '.';
                    if ($item['lastPayment'] !== '-') {
                        $whatsappMessage .= ' Last payment: ' . htmlspecialchars($item['lastPayment']) . '.';
                    }
                    $whatsappLink = 'https://wa.me/?text=' . urlencode($whatsappMessage);
                    ?>
                    <a href="<?php echo $whatsappLink; ?>" target="_blank" class="whatsapp-link" title="Send WhatsApp">
                        <i class="fa fa-whatsapp" style="color: #25D366; font-size: 20px;"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
// Initialize DataTable after content is loaded
if ($.fn.DataTable && $('#outstandingDataTable').length) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#outstandingDataTable')) {
        $('#outstandingDataTable').DataTable().destroy();
    }
    $('#outstandingDataTable').DataTable({
        "order": [[1, "desc"]], // Sort by Outstanding column descending
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

