<?php
require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');

$tbl_id = $_GET['id'];
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';

$uId = 2; $tbl_id2 = 2;

function moneyFormatIndia($num) {
   if (!is_numeric(str_replace(',', '', (string)$num)) || $num === '' || $num == null) {
        return '-';
    }
    $num = (int)round(str_replace(',', '', (string)$num));
    $explrestunits = "";
    if(strlen($num)>3) {
        $lastthree = substr($num, -3);
        $restunits = substr($num, 0, -3);
        $restunits = (strlen($restunits)%2 == 1) ? "0".$restunits : $restunits;
        $expunit = str_split($restunits, 2);
        foreach($expunit as $i => $val) {
            $explrestunits .= ($i == 0) ? (int)$val."," : $val.",";
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return ($thecash == 0) ? '-' : $thecash;
}

$sqlRev = mysqli_query($conn, "SELECT cont_name, gsheet FROM accounts_invoice WHERE tbl_id=$tbl_id");

$row = mysqli_fetch_assoc($sqlRev);
$sheetName = $row['gsheet'];
$cont_name = $row['cont_name'];

if($cont_name == '' ) { $cont_name = 'Sir/Madam'; }

//d($sheetRows);

$fileN_SOA = $fileN_SOA_PI = '';
$outstanding = '';
//$sheetName = 'Urbando';

if(isset($sheetName) && $sheetName != '' && $tbl_id!= '') {
    
    $sheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk'; // Default sheet ID
    //if($pi_soa == 'yes') { $sheetName = $sheetName.' - PI'; } 

    $raw = read_sheet($uId, $sheetId, $sheetName);
    if (isset($raw['data'])) {
        $soa_sheet = $raw['data']; // ✅ extract the actual rows

        foreach ($soa_sheet as &$soa_data) {
            $soa_data = array_slice(array_pad($soa_data, 14, ''), 0, 14);
        }
        unset($soa_data);

        //d($soa_sheet);
        $lastRow = end($soa_sheet);
        $outstanding = $lastRow[13];

     } else {
        $fileN_SOA = '';
        echo "<script>window.parent.$('#iframeModal').modal('hide');</script>"; exit;
    }
    //pi_soa
} else {
     echo "<script>window.parent.$('#iframeModal').modal('hide');</script>"; exit;
}

// Prepare values for UI
$outstanding_clean = $outstanding;
if ($outstanding_clean !== '') { $outstanding_clean = str_replace(',', '', $outstanding_clean); }
$outstanding_formatted = moneyFormatIndia($outstanding_clean);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AdRescue - WhatsApp Reminder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    .card-wrap { max-width: 900px; margin: 30px auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.05);} 
    textarea { min-height: 300px; resize: vertical; }
  </style>
</head>
<body>

<div class="card-wrap">
  <?php include 'alert.php'; ?>
  <div class="mb-3">
    <label for="waMessage" class="form-label">Whatsapp message</label>
    <textarea class="form-control" id="waMessage" rows="6"><?php echo "Hi ".$cont_name.",\nWe have sent the invoices, reports & SOA till date.\nThe current outstanding is Rs. ".$outstanding_formatted.".\nCan you please let us know when we can expect this payment?"; ?></textarea>
  </div>
  <div class="text-end">
    <button type="button" class="btn btn-success" onclick="shareWhatsApp()"><i class="fab fa-whatsapp"></i> Share via Whatsapp</button>
    <a href="invoice-soa-download.php?tab=<?php echo $sheetName; ?>" class="btn btn-primary"  title="Download SOA">Download SOA <i class="fa fa-download"></i> </a>
    <button type="button" class="btn btn-outline-secondary" onclick="window.parent.$('#iframeModal').modal('hide');">Close</button>
  </div>
</div>

<script>
function shareWhatsApp(){
  var msg = document.getElementById('waMessage').value || '';
  var url = 'https://wa.me?text=' + encodeURIComponent(msg);
  window.open(url, '_blank');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
