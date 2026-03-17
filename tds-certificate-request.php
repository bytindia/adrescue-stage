<?php
require __DIR__ . '/email/vendor/autoload.php';
include 'email/config.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');
include 'db.php';
//d($_POST);
// Handle email sending if form is submitted
if (isset($_POST['body']) && !empty($_POST['body'])) {
    // Validate required fields
    if (empty($_POST['to']) || empty($_POST['body'])) {
        echo "<script>alert('Please fill in all required fields!');</script>";
    } else {
        // Include email sending script
        // $_POST['body'], $_POST['subject'], and $_POST['to'] will be available for reminder2025.php
        include 'email/reminder2025.php';
        echo "<script>alert('Email has been sent!'); window.parent.$('#iframeModal').modal('hide');</script>"; exit;
    }
}

$tbl_id = $_GET['id'];


// Get client details from accounts_invoice table
$clientQuery = mysqli_query($conn, "SELECT client_name, cont_name, cont_email FROM accounts_invoice WHERE tbl_id = " . intval($tbl_id) . " LIMIT 1");
$clientData = mysqli_fetch_assoc($clientQuery);

if (!$clientData) {
    echo "<div class='alert alert-danger'>Client not found!</div>";
    exit;
}

$client_name = $clientData['client_name'] ?: 'Client';
$cont_name = $clientData['cont_name'] ?: $client_name;
$cont_email = $clientData['cont_email'] ?: 'accounts@bytindia.com';

// Default email body
$defaultEmailBody = "Dear " . $cont_name . ",

As we are nearing the financial year end we need TDS certificate for all the quarters that has been filed till date (which is Q1,Q2,Q3), kindly send us the documents on or before 28th of January.

we need this document for our Audit purpose so kindly do the needful.

Warm regards,
Accounts Team BYT
BYT Digital

Keerthi: 88257 87703 & Sriram: 74489 76361
accounts@bytindia.com";

$defaultSubject = "TDS Certificates Required for FY 2024–25 Audit";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AdRescue - TDS Certificate Request</title>
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

    .email-card {
      max-width: 1200px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.05);
    }
    
    textarea {
      min-height: 400px;
      resize: vertical;
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
  
  <form method="post" action="" id="myForm">
    <div class="row">
      <!-- Column 1: Email Body -->
      <div class="col-md-7">
        <div class="mb-3">
          <label for="body" class="form-label">Email Body</label>
          <textarea class="form-control" name="body" id="body" rows="20"><?php echo htmlspecialchars($defaultEmailBody); ?></textarea>
        </div>
      </div>

      <!-- Column 2: To, Subject, Buttons -->
      <div class="col-md-5">
        <div class="mb-3">
          <label for="to" class="form-label">To</label>
          <input type="text" class="form-control" name="to" id="to" placeholder="client@example.com" value="<?php echo htmlspecialchars($cont_email); ?>">
        </div>
        
        <div class="mb-3">
          <label for="subject" class="form-label">Subject</label>
          <input type="text" class="form-control" name="subject" id="subject" value="<?php echo htmlspecialchars($defaultSubject); ?>" />
        </div>

        <div class="mb-3">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Note:</strong> No attachments will be sent with this email.
          </div>
        </div>

        <div class="mb-3 d-flex" style="margin-top: 50px; float: right;">
          <button type="button" class="btn btn-success btn-md me-2" onclick="shareWhatsApp()">
            <i class="fab fa-whatsapp"></i> Share via WhatsApp
          </button>
          <button type="submit" name="send_email" id="submitBtn" value="Submit" class="btn btn-primary btn-md me-2" onclick="return confirmAndSubmit();">
            <i class="fas fa-envelope"></i> Send Email
          </button>
          <button type="button" class="btn btn-outline-primary btn-md" onclick="parent.$('#iframeModal').modal('hide');">Close</button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
var isSubmitting = false;

function confirmAndSubmit() {
  const confirmed = confirm("Are you sure you want to send this TDS certificate request email?");
  
  if (confirmed) {
    if (isSubmitting) {
      return false;
    }

    isSubmitting = true;
    document.getElementById('submitBtn').innerHTML = "Please wait, submitting...";
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

// Function to share via WhatsApp
function shareWhatsApp() {
    const emailBody = document.getElementById('body');
    const subject = document.getElementById('subject');
    
    // Get the email content
    const emailContent = emailBody.value;
    
    // Create WhatsApp message without signature
    let whatsappMessage = `*TDS Certificate Request*\n\n`;
    
    // Extract only the main content (before the signature)
    const mainContent = emailContent.split('Warm regards,')[0].trim();
    whatsappMessage += mainContent;
    
    // Encode the message for WhatsApp URL
    const encodedMessage = encodeURIComponent(whatsappMessage);
    
    // Create WhatsApp URL
    const whatsappUrl = `https://wa.me?text=${encodedMessage}`;
    
    // Check if message is too long (WhatsApp has limits)
    if (encodedMessage.length > 2000) {
        alert('Message is too long for WhatsApp. Please shorten the content.');
        return;
    }
    
    // Try to open WhatsApp
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
