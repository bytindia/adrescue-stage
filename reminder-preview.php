<?php
require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Handle email sending if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body']) && isset($_POST['paymentStatus'])) {
    include 'email/reminder2025.php';
    echo "<script>alert('Email has been sent!'); window.parent.$('#iframeModal').modal('hide');</script>"; exit;
    //print_r($_POST); exit;
}

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
$sheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk'; 

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

// Get client details from accounts_invoice table
$clientQuery = mysqli_query($conn, "SELECT gsheet, cont_name, cont_email FROM accounts_invoice WHERE tbl_id = " . intval($tbl_id) . " LIMIT 1");
$clientData = mysqli_fetch_assoc($clientQuery);

if (!$clientData) {
    echo "<div class='alert alert-danger'>Client not found!</div>";
    exit;
}

$client_name = $sheetName = $clientData['gsheet'];
$cont_name = $clientData['cont_name'] ?: 'Sir/Madam';
$cont_email = $clientData['cont_email'] ?: 'accounts@bytindia.com';

$raw = read_sheet($uId, $sheetId, $sheetName);
$last_payment = null;
$outstanding = null;

if (isset($raw['data'])) {
    $soa_sheet = $raw['data'];

    // Loop through the sheet in reverse order to get the last valid last payment
    for ($i = count($soa_sheet) - 1; $i >= 0; $i--) {
        $soa_data = $soa_sheet[$i];

        // Check if the last payment (key 12) is not empty or zero
        if (!empty($soa_data[12]) && $soa_data[12] != 0) {
            $last_payment = $soa_data[12];
        }

        // Always set the total outstanding (key 13) from the last entry
        if ($i == count($soa_sheet) - 1) {
            $outstanding = $soa_data[13];
        }

        // Once the valid last payment is found, stop the loop
        if ($last_payment !== null) {
            break;
        }
    }
}

// Clean and convert the values to numbers
if ($outstanding !== null) {
    $outstanding = str_replace(',', '', $outstanding); // Remove commas
    $outstanding = floatval($outstanding); // Convert to float
}

if ($last_payment !== null) {
    $last_payment = str_replace(',', '', $last_payment); // Remove commas
    $last_payment = floatval($last_payment); // Convert to float
}

//d($last_payment); d($outstanding); d($soa_sheet);
// Get last 5 invoices from invoice2 table based on acc_tbl_id
$invoiceQuery = mysqli_query($conn, "SELECT inv_no, grand_tot FROM invoice2 WHERE acc_tbl_id = " . intval($tbl_id) . " ORDER BY tbl_id DESC LIMIT 5");
$invoices = [];

while ($row = mysqli_fetch_assoc($invoiceQuery)) {
    $inv_nos = explode(',', $row['inv_no']);
    $grand_totals = explode(',', $row['grand_tot']);
    
    // Clean and process invoice numbers and amounts
    $inv_nos = array_values(array_unique(array_filter($inv_nos)));
    $grand_totals = array_values(array_unique(array_filter($grand_totals)));
    
    foreach ($inv_nos as $index => $inv_no) {
        $inv_no = trim($inv_no);
        $amount = isset($grand_totals[$index]) && is_numeric($grand_totals[$index]) ? floatval($grand_totals[$index]) : 0;
        
        if ($inv_no && $amount > 0) {
            $invoices[] = [
                'inv_no' => $inv_no,
                'amount' => $amount
            ];
        }
    }
}
//print_r($_POST); // exit;
// Limit to last 5 invoices
$invoices = array_slice($invoices, 0, 5);

$today = date('d-m-Y');
$inv_type = 'BYT/SW/25/';
$outstanding_amnt = moneyFormatIndia($outstanding);
$received_amnt = moneyFormatIndia($last_payment);
include 'invoice-soa-inc.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AdRescue - Payment Reminder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <style>
    html, body {
      height: 100%;
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
      background-color: #3774a9;
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
      height: calc(100% - 48px);
      padding: 0 !important;
      overflow: hidden;
    }

    #iframeLoader {
      z-index: 10;
    }
    
    .btn-group {
      display: inline-flex;
      align-items: center;
    }

    .btn-group .btn {
      margin-right: 0;
    }

    .btn-group .btn-sm {
      padding: 5px 10px;
    }

    .fas.fa-trash {
      font-size: 14px;
      margin-left: 5px;
    }
    
    .mar-left {
      margin-left: 10px;
    }
    
    .invoice-summary {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .invoice-summary h6 {
      color: #495057;
      margin-bottom: 10px;
    }
    
    .total-amount {
      font-size: 18px;
      font-weight: bold;
      color: #dc3545;
    }

    .custom-input-section {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .custom-input-section h6 {
      color: #495057;
      margin-bottom: 15px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      font-weight: 500;
      color: #495057;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>

<div class="email-card" style="max-width: 1200px;">
  
  <?php if(count($invoices) == 0) { ?>
    <div class="alert alert-warning text-center" role="alert">No pending invoices found for this client.</div>
  <?php } ?>
  
  <form method="post" action="" id="myForm">
    <div class="row">
      <!-- Column 1: Email Body -->
      <div class="col-md-7">
        <div class="mb-3">
          <label for="body" class="form-label">Email Body</label>
          <textarea class="form-control" name="body" id="body" rows="20">{{email_content}}

Warm regards,
Accounts Team BYT
BYT Digital

Keerthi: 88257 87703 & Sriram: 74489 76361
accounts@bytindia.com
          </textarea>
        </div>
      </div>

      <!-- Column 2: To, Subject, Payment Status, Custom Inputs -->
      <div class="col-md-5">
        <div class="mb-3">
          <label for="to" class="form-label">To</label>
          <input type="text" class="form-control" name="to" placeholder="client@example.com" value="<?php echo $cont_email; ?>" readonly>
        </div>
        
        <div class="mb-3">
          <label for="subject" class="form-label">Subject</label>
          <input class="form-control" name="subject" id="subject" rows="3" value="Payment Reminder - <?php echo $client_name; ?>" />
        </div>

        <!-- Payment Status Selection -->
        <div class="invoice-summary">
          <h6><i class="fa fa-credit-card"></i> Payment Status</h6>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="paymentStatus" id="followUpPayment" value="follow_up_payment" checked>
            <label class="form-check-label" for="followUpPayment">
              <strong>Follow-up on Pending Payment</strong>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="paymentStatus" id="acknowledgementPayment" value="acknowledgement_payment">
            <label class="form-check-label" for="acknowledgementPayment">
              <strong>Acknowledgement of Payment</strong>
            </label>
          </div>
        </div>

        <!-- Custom Input Fields -->
        <div class="custom-input-section">
          <h6><i class="fa fa-edit"></i> Custom Input Fields</h6>
          
          <!-- Common fields for all types -->
          <div class="form-group">
            <label for="totalOutstanding">Total Outstanding</label>
            <input type="text" class="form-control" id="totalOutstanding" placeholder="Enter amount" value="<?php echo $outstanding_amnt; ?>">
          </div>
          
          <!-- Fields for Acknowledgement of Payment -->
          <div id="acknowledgementFields" style="display: none;">
            <div class="form-group">
              <label for="receivedAmount">Amount Received</label>
              <input type="text" class="form-control" id="receivedAmount" placeholder="Enter amount" value="<?php echo $received_amnt; ?>">
            </div>
          </div>
        </div>

        <!-- SOA File Attachments -->
        <div class="mt-4">
          <label class="form-label fw-bold">Preview Attachments:</label>
          <?php if(empty($fileN_SOA) && empty($fileN_SOA_PI)) { ?>
            <div class="alert alert-warning" role="alert">No SOA attachments available for preview.</div>
          <?php } ?>
          
          <div class="attachment-buttons">
            <?php if (!empty($fileN_SOA)): ?>
              <div id="soa_file" class="btn-group mar-left">
                <button type="button" class="btn btn-outline-primary btn-sm"
                  onclick="previewAttachment('https://stage.adrescue.in/download/<?php echo $fileN_SOA; ?>')"
                  data-bs-toggle="modal" data-bs-target="#previewModal">SOA</button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="removeFile('soa_file', 'soa_file')"  data-bs-toggle="tooltip" title="Remove SOA?">
                  <i class="fa-solid fa-close" style="font-size:20px;"></i>
                </button>
              </div>
            <?php endif; ?>
            <input type="hidden" name="soa_file" value="<?php if (!empty($fileN_SOA)): echo $fileN_SOA; endif; ?>">

            <?php if (!empty($fileN_SOA_PI)): ?>
              <div id="soa_pi_file" class="btn-group mar-left">
                <button type="button" class="btn btn-outline-primary btn-sm"
                  onclick="previewAttachment('https://stage.adrescue.in/download/<?php echo $fileN_SOA_PI; ?>')"
                  data-bs-toggle="modal" data-bs-target="#previewModal"  data-bs-toggle="tooltip" title="Preview PI SOA">SOA - PI</button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="removeFile('soa_pi_file', 'soa_pi_file')"   data-bs-toggle="tooltip" title="Remove PI SOA?">
                  <i class="fas fa-close" style="font-size:20px;"></i>
                </button>
              </div>
            <?php endif; ?>
            <input type="hidden" name="soa_pi_file" value="<?php if (!empty($fileN_SOA_PI)): echo $fileN_SOA_PI; endif; ?>">
          </div>
        </div>

        <div class="mb-3 d-flex" style="margin-top: 50px; float: right;">
          <button type="button" class="btn btn-outline-secondary btn-md me-2" onclick="restoreTemplate()" style="display: none;">Restore Template</button>
          <button type="button" class="btn btn-outline-info btn-md me-2" onclick="updateEmailContent()" style="display: none;">Test Update</button>
          <button type="button" class="btn btn-success btn-md me-2" onclick="shareWhatsApp()">
            <i class="fab fa-whatsapp"></i> Share via WhatsApp
          </button>
          <button type="submit" name="send_email" id="submitBtn" value="Submit" class="btn btn-primary btn-md me-2" onclick="return confirmAndSubmit();">
            <i class="fas fa-envelope"></i> Send Email
          </button>
          <button type="button" class="btn btn-outline-warning btn-md me-2" onclick="testWhatsApp()" style="display: none;">
            <i class="fab fa-whatsapp"></i> Test WA
          </button>
          <button type="button" class="btn btn-outline-primary btn-md" onclick="parent.$('#iframeModal').modal('hide');">Close</button>
        </div>
      </div>
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
// Store invoice data
const invoices = <?php echo json_encode($invoices); ?>;
const inv_type = '<?php echo $inv_type; ?>';
const today = '<?php echo $today; ?>';

// Store original template
const originalTemplate = `{{email_content}}

Warm regards,
Accounts Team BYT
BYT Digital

Keerthi: 88257 87703 & Sriram: 74489 76361
accounts@bytindia.com`;

// Function to format money
function moneyFormatIndia(num) {
    if (!isFinite(num) || num === '' || num == null) {
        return '-';
    }
    
    num = Math.round(num);
    let explrestunits = "";
    if(num.toString().length > 3) {
        const lastthree = num.toString().slice(-3);
        const restunits = num.toString().slice(0, -3);
        const paddedRest = restunits.length % 2 == 1 ? "0" + restunits : restunits;
        const expunit = paddedRest.match(/.{1,2}/g) || [];
        explrestunits = expunit.map((val, i) => i == 0 ? parseInt(val) + "," : val + ",").join("");
        return explrestunits + lastthree;
    } else {
        return num.toString();
    }
}

// Function to update email content
function updateEmailContent() {
    const paymentStatus = document.querySelector('input[name="paymentStatus"]:checked').value;
    
    // Get custom input values
    const totalOutstanding = document.getElementById('totalOutstanding').value || '<?php echo $outstanding_amnt; ?>';
    
    let emailContent = '';
    let subjectText = '';
    
    if (paymentStatus === 'follow_up_payment') {
        emailContent = `Dear <?php echo $cont_name; ?>,

Hope you are doing well, 
I have also emailed the SOA to you for your reference.
Total outstanding: Rs. ${totalOutstanding}/-

Can you please process the payments today? Thanks.`;
        
        subjectText = `Reminder: Invoices & SOA Attached – Request for Payment - <?php echo $client_name; ?>`;
        
    } else if (paymentStatus === 'acknowledgement_payment') {
        const receivedAmount = document.getElementById('receivedAmount').value || '<?php echo $received_amnt; ?>';
        
        emailContent = `Dear <?php echo $cont_name; ?>,

Thank you for the payment. We have received Rs. ${receivedAmount}/-

I have also emailed the SOA to you for your reference.
Total outstanding: Rs. ${totalOutstanding}/-

Can you please process the payments at your earliest? Thanks.`;
        
        subjectText = `Payment Received - <?php echo $client_name; ?>`;
    }
    
    // Update email body
    const emailBody = document.getElementById('body');
    let bodyText = emailBody.value;
    
    // Check if placeholders exist, if not use original template
    if (!bodyText.includes('{{cont_name}}') || !bodyText.includes('{{email_content}}')) {
        bodyText = originalTemplate;
    }
    
    // Replace placeholders with actual content
    bodyText = bodyText.replace(/{{cont_name}}/g, '<?php echo $cont_name; ?>');
    bodyText = bodyText.replace(/{{email_content}}/g, emailContent);
    
    // Update the email body
    emailBody.value = bodyText.trim();
    
    // Update subject
    const subject = document.getElementById('subject');
    subject.value = subjectText;
    
    console.log('Payment status:', paymentStatus);
    console.log('Updated body text:', bodyText);
}

// Payment status change handler
document.addEventListener('change', function(e) {
    if (e.target.name === 'paymentStatus') {
        console.log('Payment status changed:', e.target.value);
        
        const acknowledgementFields = document.getElementById('acknowledgementFields');
        
        // Hide all optional fields first
        acknowledgementFields.style.display = 'none';
        
        // Show relevant fields based on selection
        if (e.target.value === 'acknowledgement_payment') {
            acknowledgementFields.style.display = 'block';
        }
        
        updateEmailContent();
    }
});

// Input change handler for custom fields
document.addEventListener('input', function(e) {
    if (e.target.id === 'totalOutstanding' || 
        e.target.id === 'receivedAmount') {
        console.log('Custom field changed:', e.target.id, e.target.value);
        
        // Format the input value in Indian money format
        let value = e.target.value.replace(/[^\d]/g, ''); // Remove non-digits
        if (value) {
            value = moneyFormatIndia(parseInt(value));
            e.target.value = value;
        }
        
        updateEmailContent();
    }
});

// Function to restore original template
function restoreTemplate() {
    const emailBody = document.getElementById('body');
    emailBody.value = originalTemplate;
    updateEmailContent();
}

// Function to share via WhatsApp
function shareWhatsApp() {
    const emailBody = document.getElementById('body');
    const subject = document.getElementById('subject');
    
    // Get the email content
    const emailContent = emailBody.value;
    const subjectText = subject.value;
    
    // Create WhatsApp message without signature
    let whatsappMessage = `*Follow-up on Pending Payment*\n\n`;
    
    // Extract only the main content (before the signature)
    const mainContent = emailContent.split('Warm regards,')[0].trim();
    whatsappMessage += mainContent;
    
    // Encode the message for WhatsApp URL
    const encodedMessage = encodeURIComponent(whatsappMessage);
    
    // Create WhatsApp URL
    const whatsappUrl = `https://wa.me?text=${encodedMessage}`;
    
    console.log('WhatsApp URL:', whatsappUrl);
    console.log('Original message:', whatsappMessage);
    console.log('Encoded message length:', encodedMessage.length);
    
    // Check if message is too long (WhatsApp has limits)
    if (encodedMessage.length > 2000) {
        alert('Message is too long for WhatsApp. Please shorten the content.');
        return;
    }
    
    // Try to open WhatsApp with new link format
    try {
        window.open(whatsappUrl, '_blank');
    } catch (error) {
        console.error('Error opening WhatsApp:', error);
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(whatsappMessage).then(() => {
            alert('Message copied to clipboard. You can now paste it in WhatsApp manually.');
        }).catch(() => {
            alert('Please copy the message manually and paste it in WhatsApp.');
        });
    }
}

// Test WhatsApp function
function testWhatsApp() {
    const testMessage = "Hello, this is a test message from AdRescue!";
    const encodedMessage = encodeURIComponent(testMessage);
    const whatsappUrl = `https://wa.me?text=${encodedMessage}`;
    
    console.log('Test WhatsApp URL:', whatsappUrl);
    window.open(whatsappUrl, '_blank');
}

var isSubmitting = false;

function confirmAndSubmit() {
  const confirmed = confirm("Are you sure you want to send it?");
  
  if (confirmed) {
    if (isSubmitting) {
      return false;
    }

    isSubmitting = true;
    document.getElementById('submitBtn').innerHTML = "submitting...";
    document.getElementById('submitBtn').classList.add('btn-secondary');
    document.getElementById('submitBtn').removeAttribute("onclick");

    const form = document.getElementById('myForm');
    if (form) {
      form.submit();
    } else {
      console.error('Form not found');
    }

    return false;
  } else {
    return false;
  }
}

function removeFile(fileId, fileInputName) {
  // Show confirmation dialog before removal
  const confirmed = confirm("Are you sure you want to remove this file?");

  if (confirmed) {
    // Hide the entire button group (file preview and remove button)
    document.getElementById(fileId).style.display = 'none';

    // Update the corresponding hidden input value to 'removed'
    const hiddenInput = document.querySelector(`input[name='${fileInputName}']`);
    if (hiddenInput) {
      hiddenInput.value = ''; // Mark it as removed
    }
  }
}

function previewAttachment(url) {
  const iframe = document.getElementById('attachmentIframe');
  const loader = document.getElementById('iframeLoader');
  const ext = url.split('.').pop().toLowerCase();
  const cacheBuster = '?t=' + new Date().getTime();

  loader.classList.remove('d-none');
  iframe.style.visibility = 'hidden';
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

  setTimeout(() => {
    iframe.src = finalUrl;
  }, 100);

  iframe.onload = function () {
    loader.classList.add('d-none');
    iframe.style.visibility = 'visible';
  };

  setTimeout(() => {
    loader.classList.add('d-none');
    iframe.style.visibility = 'visible';
  }, 3000);
}

// Initialize email content
window.addEventListener("DOMContentLoaded", () => {
    console.log('DOM loaded, initializing...');
    console.log('Invoices data:', invoices);
    console.log('Original template:', originalTemplate);
    updateEmailContent();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>