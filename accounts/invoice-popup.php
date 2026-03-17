<?php
// Invoice Sent Details Popup - Loaded via AJAX
session_start();
require_once __DIR__ . '/../db.php';
Auth();

// Helper: Parse serialized line_items and compute totals
if (!function_exists('computeTotalsFromLineItems')) {
    function computeTotalsFromLineItems($serialized)
    {
        $totalSubtotal = 0;
        $totalGrand    = 0;
        if (!empty($serialized)) {
            $data = @unserialize($serialized);
            if (is_array($data)) {
                foreach ($data as $invoice) {
                    if (isset($invoice['totals'])) {
                        $totalSubtotal += floatval($invoice['totals']['subtotal'] ?? 0);
                        $totalGrand    += floatval($invoice['totals']['grand_total'] ?? 0);
                    }
                }
            }
        }
        return [$totalSubtotal, $totalGrand];
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

// Helper: Parse various date formats
function parseInvDate($dateStr) {
    if (empty($dateStr)) return null;
    
    $dt = DateTime::createFromFormat('d-m-Y', $dateStr);
    if ($dt) return $dt;
    
    $dt = DateTime::createFromFormat('Y-m-d', $dateStr);
    if ($dt) return $dt;
    
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
    if ($dt) return $dt;
    
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', substr($dateStr, 0, 19));
    if ($dt) return $dt;
    
    $dt = DateTime::createFromFormat('d/m/Y', $dateStr);
    if ($dt) return $dt;
    
    return null;
}

// Helper: Check if date is in current month
function isInCurrentMonth($dateStr) {
    $currentYear = date('Y');
    $currentMonth = date('m');
    
    $dt = parseInvDate($dateStr);
    if (!$dt) return false;
    
    return ($dt->format('Y') == $currentYear && $dt->format('m') == $currentMonth);
}

// Fetch invoice data for current month (excluding PI)
$currentYear = date('Y');
$currentMonth = date('m');

$where = ["i.approved='yes'", "(i.inv_ty!='pi' OR i.inv_ty IS NULL OR i.inv_ty='')"];
$whereSql = implode(' AND ', $where);

$sql = mysqli_query($conn, "SELECT i.client_name, i.inv_dt, i.created, i.line_items, i.acc_tbl_id, 
    COALESCE(a.gsheet, i.client_name) as gsheet_client
    FROM invoice2 i 
    LEFT JOIN accounts_invoice a ON i.acc_tbl_id = a.tbl_id 
    WHERE {$whereSql} ORDER BY i.created DESC");

$tableData = [];
while ($row = mysqli_fetch_assoc($sql)) {
    $dateToCheck = !empty($row['inv_dt']) ? $row['inv_dt'] : $row['created'];
    
    if (isInCurrentMonth($dateToCheck)) {
        if (!empty($row['line_items'])) {
            list(, $grand) = computeTotalsFromLineItems($row['line_items']);
            if ($grand > 0) {
                // Format date for display
                $dt = parseInvDate($dateToCheck);
                $invDateDisplay = $dt ? $dt->format('d-m-Y') : $dateToCheck;
                
                $tableData[] = [
                    'inv_date' => $invDateDisplay,
                    'inv_date_sort' => $dt ? $dt->format('Y-m-d') : $dateToCheck,
                    'client' => !empty($row['gsheet_client']) ? $row['gsheet_client'] : $row['client_name'],
                    'total' => $grand
                ];
            }
        }
    }
}

// Sort by date descending
usort($tableData, function($a, $b) {
    return strcmp($b['inv_date_sort'], $a['inv_date_sort']);
});
?>

<div class="table-responsive">
    <table id="invoiceDataTable" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th>Inv. Date</th>
                <th>Client</th>
                <th class="text-right">Total (with GST)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableData as $item): ?>
            <tr>
                <td data-order="<?php echo $item['inv_date_sort']; ?>"><?php echo htmlspecialchars($item['inv_date']); ?></td>
                <td><?php echo htmlspecialchars($item['client']); ?></td>
                <td class="text-right" data-order="<?php echo $item['total']; ?>">₹<?php echo moneyFormatIndia((int)$item['total']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
// Initialize DataTable after content is loaded
if ($.fn.DataTable && $('#invoiceDataTable').length) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#invoiceDataTable')) {
        $('#invoiceDataTable').DataTable().destroy();
    }
    $('#invoiceDataTable').DataTable({
        "order": [[0, "desc"]], // Sort by Inv. Date descending
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

