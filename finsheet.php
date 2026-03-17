<?php
// Connect to your database
include 'db.php';
// Handle form submission
// Check if the request method is POST
function money($num) {
    if (!is_numeric($num)) return $num;
    $num = round($num);
    $explrestunits = "";
    if (strlen($num) > 3) {
        $lastthree = substr($num, -3);
        $restunits = substr($num, 0, -3);
        $restunits = (strlen($restunits) % 2 != 0) ? "0" . $restunits : $restunits;
        $expunit = str_split($restunits, 2);
        foreach ($expunit as $i => $val) {
            $explrestunits .= ($i == 0) ? intval($val) . "," : $val . ",";
        }
        $thecash = $explrestunits . $lastthree;
    } else {
        $thecash = $num;
    }
    return '₹ ' . $thecash;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture form data
    $_POST['updated_at'] = date('Y-m-d H:i:s');
    $data = $_POST;

    // Convert to JSON for saving in DB
    $jsonData = json_encode($data);

    // Escape JSON string for safe DB insertion
    $escapedJson = mysqli_real_escape_string($conn, $jsonData);

    // Store JSON in the database (finesheet_data column)
    mysqli_query($conn, "UPDATE finesheet SET finesheet_data = '$escapedJson' WHERE id = 1") or die(mysqli_error($conn));

    // Format number into Indian currency with ₹
    

    // Helper function to append a line to the message if the value exists and is numeric
    // Also adds to total if $total is passed
    function addLineIfValid(&$msg, $label, $key, $data, &$total = null) {
        if (isset($data[$key]) && is_numeric($data[$key]) && $data[$key] !== '') {
            $msg .= "$label: " . money($data[$key]) . "\n";
            if ($total !== null) $total += floatval($data[$key]);
        }
    }

    // Start message with title
    $msg = "*FinSheet Summary:*\n\n";

    // ====== PAYABLES SECTION ======
    $totalPayables = 0;

    addLineIfValid($msg, "Payable (EOM)", 'payable_end_month', $data, $totalPayables);
    addLineIfValid($msg, "Cash", 'cash_on_hand', $data); // not included in total
    addLineIfValid($msg, "G - CC", 'g_cc_payables', $data, $totalPayables);
    addLineIfValid($msg, "Meta", 'meta_payable', $data);
    addLineIfValid($msg, "GST", 'gst', $data);
    addLineIfValid($msg, "TDS", 'tds', $data);

    $msg .= "\n"; // Line break before receivables

    // ====== RECEIVABLES SECTION ======
    $totalReceivables = 0;

    addLineIfValid($msg, "W1 Receivable", 'receivable_1st', $data);
    addLineIfValid($msg, "W2 Receivable", 'receivable_2nd', $data);
    addLineIfValid($msg, "W3 Receivable", 'receivable_3rd', $data);
    addLineIfValid($msg, "W4 Receivable", 'receivable_4th', $data);
    addLineIfValid($msg, "Old Receivable", 'old_receivable', $data);
    addLineIfValid($msg, "Curr. Receivable", 'current_receivable', $data, $totalReceivables);
    addLineIfValid($msg, "PI Receivables", 'pi_receivables', $data);
    addLineIfValid($msg, "PI Expected", 'pi_expected', $data, $totalReceivables);

    // ====== TOTALS & SUMMARY ======
    if ($totalPayables > 0 || $totalReceivables > 0 || !empty($data['cash_on_hand'])) {
        $msg .= "\n-----------------------------\n";
        if ($totalPayables > 0)
            $msg .= "Total Payables: " . money($totalPayables) . "\n";
        if ($totalReceivables > 0)
            $msg .= "Total Receivables: " . money($totalReceivables) . "\n";
        if (!empty($data['cash_on_hand']) && is_numeric($data['cash_on_hand']))
            $msg .= "Cash: " . money($data['cash_on_hand']) . "\n";
    }

    if (!empty($data['custom_message'])) {
        $msg .= "-----------------------------\n";
        $msg .= "*Note:*\n" . trim($data['custom_message']) . "\n";
    }
    // ====== SEND TO WHATSAPP ======
    $encoded = urlencode($msg); // encode for URL
    $link = "https://api.whatsapp.com/send/?text=$encoded"; // WhatsApp share link

    // Close modal and open WhatsApp in new tab
    echo "<script>window.parent.$('#finsheetModal').modal('hide'); window.open('$link', '_blank'); </script>";
    exit;
}



// Fetch existing data to prefill
$formData = [];
$cirSql = "SELECT finesheet_data FROM finesheet WHERE id = 1";
$result = mysqli_query($conn, $cirSql) or die(mysqli_error($conn));
$row = mysqli_fetch_assoc($result);
$formData = json_decode($row['finesheet_data'], true);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FinSheet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  .form-container {
    padding: 5px;
    border-radius: 8px;
    box-shadow: none;
    background-color: #fff;
  }
  .section-title {
    font-size: 1.1rem;
    margin-bottom: 15px;
    font-weight: 600;
  }
  .card {
    background-color: #f9f9f9;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 6px;
  }
  .form-label {
    font-size: 0.9rem;
    margin-bottom: 2px;
  }
  input.form-control {
    height: 34px;
    font-size: 0.9rem;
  }
</style>
</head>
<body>

<div class="container-fluid">
  <div class="form-container">
    <form method="post">
  <form method="post">
  <?php
  function inlineInputCol($name, $label, $formData) {
      $value = htmlspecialchars($formData[$name] ?? '');
      return <<<HTML
      <div class="col-md-6 mb-3">
        <div class="row align-items-center">
          <label class="col-sm-5 col-form-label">{$label}</label>
          <div class="col-sm-7">
            <input type="text" name="{$name}" value="{$value}" class="form-control form-control-sm" placeholder="Amount">
          </div>
        </div>
      </div>
      HTML;
  }
  ?>

  <!-- Payables Section -->
  <div class="card p-3 mb-3">
    <div class="section-title fw-bold mb-3">Payables</div>
    <div class="row">
      <?php
      echo inlineInputCol('payable_end_month', 'Payable (EOM)', $formData);
      echo inlineInputCol('cash_on_hand', 'Cash', $formData);
      echo inlineInputCol('g_cc_payables', 'G - CC', $formData);
      echo inlineInputCol('meta_payable', 'Meta', $formData);
      echo inlineInputCol('gst', 'GST', $formData);
      echo inlineInputCol('tds', 'TDS', $formData);
      ?>
    </div>
  </div>

  <!-- Receivables Section -->
  <div class="card p-3 mb-3">
    <div class="section-title fw-bold mb-3">Receivables</div>
    <div class="row">
      <?php
      echo inlineInputCol('receivable_1st', 'W1 Receivable', $formData);
      echo inlineInputCol('receivable_2nd', 'W2 Receivable', $formData);
      echo inlineInputCol('receivable_3rd', 'W3 Receivable', $formData);
      echo inlineInputCol('receivable_4th', 'W4 Receivable', $formData);
      echo inlineInputCol('old_receivable', 'Old Receivable', $formData);
      echo inlineInputCol('current_receivable', 'Curr. Receivable', $formData);
      echo inlineInputCol('pi_receivables', 'PI Receivables', $formData);
      echo inlineInputCol('pi_expected', 'PI Expected', $formData);
      ?>
    </div>
  </div>

  <!-- Custom Message -->
  <div class="card p-3 mb-3">
    <div class="section-title fw-bold mb-3">Custom Message</div>
    <div class="row">
      <div class="col-12">
        <textarea name="custom_message" class="form-control" rows="4" placeholder="Enter any custom notes or message..."><?php echo htmlspecialchars($formData['custom_message'] ?? ''); ?></textarea>
      </div>
    </div>
  </div>

  <!-- Buttons -->
  <div class="d-flex justify-content-end mt-3">
    <button type="submit" class="btn btn-primary btn-sm me-2">Submit</button>
    <button type="reset" class="btn btn-outline-secondary btn-sm" onclick="parent.$('#finsheetModal').modal('hide');">Cancel</button>
  </div>
</form>

  </div>
</div>

</body>
</html>
