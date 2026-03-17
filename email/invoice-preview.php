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
require($root_path.'PHPExcel-1.8/Classes/PHPExcel.php');
$uId=2; 
$tbl_id2=2;  
function moneyFormatIndia($num) {
   if (!is_numeric($num) || $num === '' || $num == null) {
        return '-';
    }
    
    $num = round($num);
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



$sqlRev2 = mysqli_query($conn, "SELECT * from invoice2 WHERE tbl_id=" . intval($_GET['id']) . " LIMIT 1");
$data = mysqli_fetch_assoc($sqlRev2);

$inv_nos = explode(',', $data['inv_no']);
//d($inv_nos);
if(count($inv_nos)> 0) { $inv_nos = array_values(array_unique(array_filter($inv_nos))); }
$grand_totals = explode(',', $data['grand_tot']);
if(count($grand_totals)> 0) { $grand_totals = array_values(array_unique(array_filter($grand_totals))); }
$inv_files = explode(',', $data['inv_files']);
$today = date('d-m-Y');
//d(unserialize($data['line_items'])); 
$line_items = unserialize($data['line_items']);
// Step 1: Fetch sheet name from DB fresh
$tbl_id3 = $data['acc_tbl_id'];
$sheetName = '';
$data['cont_name'] = $data['cont_email'] = '';
$checkQry = mysqli_query($conn, "SELECT gsheet,cont_name,cont_email FROM accounts_invoice WHERE tbl_id = ".$tbl_id3);
if ($row = mysqli_fetch_assoc($checkQry)) {
      $sheetName = $data['gsheet'] = trim($row['gsheet']);
      $data['cont_name'] = $row['cont_name'];
      $data['cont_email'] = $row['cont_email'];
}
if($data['cont_name'] == '') { $data['cont_name'] = 'Sir/Madam'; }
if($data['cont_email'] == '') { $data['cont_email'] = 'accounts@bytindia.com';}
//d($line_items); d($data);

if(isset($_POST['submit']))
{
   // d($_POST); 
    echo 'Submitting...';
    
    //d($inv_nos);
    $invN = $inv_nos[0];
    if(isset($inv_nos[1]) && $inv_nos[1]!='') { $invN = $inv_nos[1]; }
    if(isset($inv_nos[2]) && $inv_nos[2]!='') { $invN = $inv_nos[2]; }
    
    mysqli_query($conn, "UPDATE invoice2 SET approved='yes' where tbl_id=1");

    
    include $dirPath.'google-sheets-api/insert-row.php';
    require_once 'google-sheets-api/create-sheet-tab.php';
    
    $spreadsheetId='1VVSqg0Omly3omYW3u0SbI5SNwQ0Md1_ZW6FQAnS1DLY'; 


    $line_items = unserialize($data['line_items']);
    $leadV2 = array(); $in=0;
    $sheetNameCreated = false;
    //d($inv_nos); d($line_items); exit;
    foreach($line_items as $key => $lineItems){
        if (strlen($inv_nos[$in]) < 3) { $inv_nos[$in] = str_pad($inv_nos[$in], 3, '0', STR_PAD_LEFT); }
        $invNo = 'BYT/SW/25/'.$inv_nos[$in];
        $invTy = $lineItems['gst_tds']['inv_ty'];

        $sheetTab='BYT Invoice'; $sheetTab2='Invoice Items';
        if($invTy == 'pi' || $invTy == 'pi-2' || $invTy == 'pi-3') {
            $sheetTab='PI - Invoice'; $sheetTab2='PI - Invoice Items';
            $invNo = 'BYT/PI/25/'.$inv_nos[$in];
            mysqli_query($conn, "UPDATE inv_no SET inv_no_pi='".$invN."' where tbl_id=1");
        } else {
            mysqli_query($conn, "UPDATE inv_no SET inv_no='".$invN."' where tbl_id=1");
        }

        
        $tds = $lineItems['gst_tds']['tds'];
        $tds_amt = $lineItems['totals']['subtotal'] * ($tds / 100);
        $tds_amt = round($tds_amt);
        $receivable = $lineItems['totals']['grand_total'] - $tds_amt;
        
        $leadV = [[$invNo, date('d-m-Y'), mysqli_real_escape_string($conn, $data['gst_no']), mysqli_real_escape_string($conn, $data['client_name']), mysqli_real_escape_string($conn, $lineItems['line_items'][0]['label']), $lineItems['totals']['subtotal'], $lineItems['totals']['cgst'], $lineItems['totals']['sgst'], $lineItems['totals']['igst'], $lineItems['totals']['grand_total'], $tds, $tds_amt, $receivable,'', '', '']];

        $leadV_soa = [[
          $invNo,
          date('d-m-Y'),
          
          mysqli_real_escape_string($conn, $data['client_name']),
          mysqli_real_escape_string($conn, $lineItems['line_items'][0]['label']),
          $lineItems['totals']['subtotal'],
          $lineItems['totals']['cgst'],
          $lineItems['totals']['sgst'],
          $lineItems['totals']['igst'],
          $lineItems['totals']['grand_total'],
          $tds,
          $tds_amt,
          $receivable, // L (index 12)
          '',          // M (index 13)
          '=IF(AND(ISNUMBER(VALUE(SUBSTITUTE(INDIRECT("L"&ROW()), ",", ""))), ISNUMBER(VALUE(SUBSTITUTE(INDIRECT("M"&ROW()), ",", ""))), INDIRECT("N"&ROW()-1)<>""), INDIRECT("N"&ROW()-1) + VALUE(SUBSTITUTE(INDIRECT("L"&ROW()), ",", "")) - VALUE(SUBSTITUTE(INDIRECT("M"&ROW()), ",", "")), "")',
          ''           // O (index 15)
        ]];


        append_to_sheet($uId, $tbl_id2, $leadV, $spreadsheetId, $sheetTab);

        $gst_tds = $lineItems['gst_tds'];

        foreach($lineItems['line_items'] as $k => $v){
            $sub_tot = $v['amount'];
            //$taxable = $v['amount'] - $lineItems['totals']['cgst'] - $lineItems['totals']['sgst'] - $lineItems['totals']['igst'];
            //$taxable = round($taxable);
            
            $cgst = is_numeric($gst_tds['cgst']) ? (float)$gst_tds['cgst'] : 0;
            $sgst = is_numeric($gst_tds['sgst']) ? (float)$gst_tds['sgst'] : 0;
            $igst = is_numeric($gst_tds['igst']) ? (float)$gst_tds['igst'] : 0;
            $tds  = is_numeric($gst_tds['tds'])  ? (float)$gst_tds['tds']  : 0;

            $cgst_amt = round($sub_tot * ($cgst / 100));
            $sgst_amt = round($sub_tot * ($sgst / 100));
            $igst_amt = round($sub_tot * ($igst / 100));
            $tds_amt  = round($sub_tot * ($tds  / 100));

            $total_with_gst = $igst_amt > 0 ? $sub_tot + $igst_amt : $sub_tot + $cgst_amt + $sgst_amt;
            $receivable = $total_with_gst - $tds_amt;

            $leadV1[] = [$invNo, date('d-m-Y'), mysqli_real_escape_string($conn, $data['gst_no']), mysqli_real_escape_string($conn, $data['client_name']), mysqli_real_escape_string($conn, $v['label']),$sub_tot, $cgst_amt, $sgst_amt, $igst_amt, $total_with_gst, $tds, $tds_amt, $receivable,'', '', ''];
            $leadV2[] = [$invNo, date('d-m-Y'), mysqli_real_escape_string($conn, $data['client_name']), mysqli_real_escape_string($conn, $v['label']),$sub_tot, $cgst_amt, $sgst_amt, $igst_amt, $total_with_gst, $tds, $tds_amt, $receivable,'', '', ''];

        }

        $sheetName = $data['gsheet'];
        $sheetId='12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk';

        

        if ($sheetName == '' && !in_array($invTy, ['pi', 'pi-2', 'pi-3'])) {
          //$sheetName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($data['client_name']));
          $firstWord = strtok($data['client_name'], ' ');
          $sheetName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($firstWord));
          create_spreadsheet_tab($uId, $tbl_id2, $sheetId, $sheetName, $proj=array(), $repId='', $repMon='');
          $sheet_header[] = ['Invoice No','Invoice Date','Client','Invoice Description','Taxable Value','CGST','SGST','IGST','Invoice Value','TDS %','TDS Amount','Receivable','Receipt','Balance','Balance (Ex TDS)'];
          append_to_sheet($uId, $tbl_id2, $sheet_header, $sheetId, $sheetName); 
          $updateQuery = "UPDATE accounts_invoice SET gsheet = '$sheetName' WHERE tbl_id = ".$data['acc_tbl_id']."";
          mysqli_query($conn, $updateQuery);

          $sheetNameCreated = true;
          $data['gsheet'] = $sheetName;
        }
        //echo $sheetTab2.'- '.$data['gsheet']; exit;
        if(count($leadV2)>0){
          append_to_sheet($uId, $tbl_id2, $leadV1, $spreadsheetId, $sheetTab2); //invoice sheet
          if(!in_array($invTy, ['pi', 'pi-2', 'pi-3'])) { append_to_sheet($uId, $tbl_id2, $leadV_soa, $sheetId, $data['gsheet']); } //SOA sheet
        }

        $leadV1 = $leadV2 = $leadV_soa = array();
        $in++;
    }
    
   // $leadV = [[mysqli_real_escape_string($conn, $lead['name']),$lead['email'],$ph, $src, $med, $camp, date('d-m-Y, h:i a')]];
    //exit;
    if(!in_array($invTy, ['pi', 'pi-2', 'pi-3'])) { include 'invoice-soa-inc.php'; } // show soa
    include 'email/invoice2025.php';
    
    echo "<script>alert('Email has been sent!'); window.parent.$('#iframeModal').modal('hide');</script>"; exit;

    //echo "<script>window.location = 'https://stage.adrescue.in/invoice.php';</script>"; exit();
} else {
  if(!isset($_GET['pi']))  { include 'invoice-soa-inc.php'; } // show soa
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AdRescue - Email Composer with Attachment Preview</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html, body {
  height: 100%;
  overflow: hidden;
  margin: 0;
  padding: 0;
}

.modal,
.modal-dialog,
.modal-content {
  height: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
}

#previewModal .modal-body {
  height: 100%;
  padding: 0 !important;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

#attachmentIframe {
  flex-grow: 1;
  width: 100%;
  border: none;
  height: 100%;
}
    .email-card {
      max-width: 900px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.05);
    }
    textarea {
      min-height: 500px;
      resize: vertical;
    }
    .modal-iframe {
      width: 100%;
      height: 80vh;
      border: none;
      visibility: hidden;
    }
    .attachment-buttons .btn {
      margin-right: 10px;
      margin-bottom: 10px;
    }
  
#previewModal .modal-content {
  height: 100%;
  border-radius: 0;
  border: none;
}

#previewModal .modal-header {
  height: 48px;
  padding: 8px 15px;
  background-color: #3774a9; /* Dark blue or your desired color */
  color: #fff;
  border-bottom: 1px solid #dee2e6;
}

#previewModal .modal-title {
  font-size: 16px;
  font-weight: 500;
}

#previewModal .btn-close {
  filter: invert(1);
  opacity: 0.8;
}
body.modal-open {
  padding-right: 0 !important;
}

#previewModal .modal-dialog {
  margin: 0 !important;
  width: 100vw;
  height: 100vh;
  max-width: 100vw;
}

#previewModal .modal-content {
  height: 100%;
  border-radius: 0;
  border: none;
  margin: 0 !important;
}

#previewModal .modal-body {
  height: calc(100% - 48px); /* If header height is 48px */
  padding: 0 !important;
  overflow: hidden;
}

    #iframeLoader {
      z-index: 10;
    }
    
  </style>
</head>
<body>

<?php

//d($data);

// Prepare details block for insertion into textarea
 $inv_type = 'BYT/SW/25/'; $inv_ty_msg = ''; $inv_ty_soa = '& SOA ';
if(isset($_GET['pi'])) { $inv_type = 'BYT/PI/25/'; $inv_ty_msg = 'Proforma '; $inv_ty_soa = ''; }

$detailsBlock = ""; $j=1;


$inv_check = 'no';
if(isset($inv_nos) && count($inv_nos) > 0 && $inv_nos[0] != '') {
    $inv_check = 'yes';
}
//d($inv_nos);
$z = 1;
foreach ($inv_nos as $i => $inv_no) {
    $inv_no = trim($inv_no);
    if (strlen($inv_no) < 3) {
        $inv_no = str_pad($inv_no, 3, '0', STR_PAD_LEFT);
    }

    $amount = isset($grand_totals[$i]) && is_numeric($grand_totals[$i]) ? floatval($grand_totals[$i]) : 0;

    $detailsBlock .= "{$z}. {$inv_type}{$inv_no} | {$today} | Rs. " . moneyFormatIndia($amount) . " (incl. taxes)";

    if (count($inv_nos) > 1 && $i < count($inv_nos) - 1) {
        $detailsBlock .= "\n";
    }
    $z++;
}
//d($grand_totals); d($data['inv_no']);
//$inv_nos_arr = explode(',', $data['inv_no']);
//d($inv_nos_arr);
$formatted_inv_nos = array_map(function($inv) use ($inv_type) {
    if (strlen($inv) < 3) { $inv = str_pad($inv, 3, '0', STR_PAD_LEFT); }
    return $inv_type . trim($inv);
}, $inv_nos);
$subject_invoice_str = '(Invoice No: ' . implode(', ', $formatted_inv_nos) . ')';
?>

<div class="email-card" style="max-width: 1200px;">
  
  <?php if($inv_check=='no') { ?><div class="alert alert-danger text-center" role="alert">No invoice details were found. Please verify the configuration and try again.</div><?php } ?>
  <form method="post" action="">
    <div class="row">
      <!-- Column 1: Email Body -->
      <div class="col-md-7">
        <div class="mb-3">
          <label for="body" class="form-label">Email Body</label>
          <textarea class="form-control" name="body" id="body" rows="20">
Dear <?php echo $data['cont_name']; ?>,

Please find attached the <?php echo $inv_ty_msg; ?>invoice(s) for the <?php echo date("F", strtotime("first day of last month")); ?> 2025 along with the report.

Invoice Details:
{{invoice_details}}

<?php if(!isset($_GET['pi']))  { ?>Have also attached the statement of account (SOA)

<?php } ?>
​Can you please release the payments at the earliest? Thanks.

Warm regards,
​Accounts T​eam BYT
​B​YT Digital

Keerthi: 88257 87703 & ​Sriram: 74489 76361
​accounts@bytindia.com
          </textarea>
        </div>
      </div>

      <!-- Column 2: To, Subject, Attachments -->
      <div class="col-md-5">
        <div class="mb-3">
          <label for="to" class="form-label">To</label>
          <input type="text" class="form-control" name="to" placeholder="client@example.com" value="<?php echo $data['cont_email']; ?>">
        </div>
        <div class="mb-3">
          <label for="subject" class="form-label">Subject</label>
          <input class="form-control" name="subject" rows="3" required value="<?php echo $inv_ty_msg; ?>Invoice <?php echo $inv_ty_soa; ?><?php echo date("F Y", strtotime("first day of last month")); ?> - <?php echo $data['gsheet']; ?>" />
        </div>

        <div class="mt-4">
          <label class="form-label fw-bold">Preview Attachments:</label>
          <?php  if(count($inv_nos) == 0  && $data['report_files']=='') { ?>
            <div class="alert alert-warning" role="alert">No attachments available for preview.</div>
          <?php } ?>
          <div class="attachment-buttons">
            <?php $in = 1; foreach ($inv_files as $file): ?>
              <?php if (trim($file)): ?>
                <button type="button" class="btn btn-outline-primary btn-sm"
                  onclick="previewAttachment('https://stage.adrescue.in/fb-ads/demo/invoices/<?php echo trim($file); ?>.pdf')"
                  data-bs-toggle="modal" data-bs-target="#previewModal">Invoice # <?php echo $in++; ?></button>
              <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($data['report_files'])): ?>
              <button type="button" class="btn btn-outline-primary btn-sm"
                onclick="previewAttachment('https://stage.adrescue.in/download/<?php echo $data['report_files']; ?>')"
                data-bs-toggle="modal" data-bs-target="#previewModal">AdReport</button>
            <?php endif; ?>
            <?php if (!empty($fileN_SOA)): ?>
              <button type="button" class="btn btn-outline-primary btn-sm"
                onclick="previewAttachment('https://stage.adrescue.in/download/<?php echo $fileN_SOA; ?>')"
                data-bs-toggle="modal" data-bs-target="#previewModal">SOA</button>
            <?php endif; ?>
          </div>
          <div class="mb-3 d-flex " style="margin-top: 220px; float: right;">
            <?php if($inv_check=='yes') { ?>
                <button type="button" class="btn btn-outline-primary btn-md me-3" onclick="parent.$('#iframeModal').modal('hide');"> Close</button>
                <button type="submit" name="submit" id="submitBtn" class="btn btn-primary btn-md" onclick="return confirm('Are you sure you want to send it?');" >
                  Approve & Send Email
                </button>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="text-end mt-4">
      
    </div>
  </form>
</div>


<!-- Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center w-100">
        <h5 class="modal-title text-center flex-grow-1 m-0" id="previewModalLabel">
          Preview Attachment
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 position-relative">
        <div id="iframeLoader"
          class="position-absolute top-50 start-50 translate-middle bg-white p-3 rounded shadow d-none text-center">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="mt-2">Loading preview...</div>
        </div>
        <iframe id="attachmentIframe" class="modal-iframe" src=""></iframe>
      </div>
    </div>
  </div>
</div>

<script>
 
  // Replace invoice placeholder in textarea
  window.addEventListener("DOMContentLoaded", () => {
    const body = document.getElementById("body");
    body.value = body.value.replace("{{invoice_details}}", <?php echo json_encode($detailsBlock); ?>);
  });

  function previewAttachment(url) {
  const iframe = document.getElementById('attachmentIframe');
  const loader = document.getElementById('iframeLoader');
  const ext = url.split('.').pop().toLowerCase();
  const cacheBuster = '?t=' + new Date().getTime();

  loader.classList.remove('d-none');
  iframe.style.visibility = 'hidden';
  
  // Clear old src first
  iframe.src = '';

  let finalUrl = url;
  if (['docx', 'pptx'].includes(ext)) {
    finalUrl = 'https://docs.google.com/gview?url=' + encodeURIComponent(url) + '&embedded=true';
  } else if (['xls', 'xlsx'].includes(ext)) {
    finalUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(url + cacheBuster);
  } else if (ext === 'pdf') {
    finalUrl = url + '#toolbar=0&navpanes=0&scrollbar=0';
  } else {
    finalUrl = url + cacheBuster;
  }

  // Wait a moment before reassigning to force reload
  setTimeout(() => {
    iframe.src = finalUrl;
  }, 100);

  iframe.onload = function () {
    loader.classList.add('d-none');
    iframe.style.visibility = 'visible';
  };

  // Fallback: ensure loader is hidden even if iframe doesn't load
  setTimeout(() => {
    loader.classList.add('d-none');
    iframe.style.visibility = 'visible';
  }, 3000);
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
