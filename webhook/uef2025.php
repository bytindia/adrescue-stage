<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';
/*
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
include $dirPath . 'google-sheets-api/insert-row.php';
$uId =2;
$tbl_id = 2;
$spreadsheetId = '1FgMShgsBN1FziCmJBi1mVSP7WwPNP5M_av9x1WwlmTg';
$sheetTab = 'Leads';
*/


$input = file_get_contents('php://input');
$lead = json_decode($input, true);

//print_r($_POST); print_r($lead); exit;
if (isset($_POST['type']) && $_POST['type']=='add_lead') {

    
    $source = $medium = $campaign = '';
    
    $created_at = date('Y-m-d H:i:s');

    // Sanitize and format phone number
    $ph = isset($_POST['phone']) ? str_replace(' ', '', $_POST['phone']) : '';
    $ph = str_replace('+91', 'p:+91', $ph);

    // Safely get UTM parameters
    $source = isset($_POST['source']) ? $_POST['source'] : '';
    $medium = isset($_POST['medium']) ? $_POST['medium'] : '';
    $campaign = isset($_POST['campaign']) ? $_POST['campaign'] : '';
    $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : '';
    $srd = isset($_POST['srd']) ? $_POST['srd'] : '';
    $term = isset($_POST['term']) ? $_POST['term'] : '';
    $iam = isset($_POST['iam']) ? $_POST['iam'] : '';
    $company = isset($_POST['company']) ? $_POST['company'] : '';
    $designation = isset($_POST['designation']) ? $_POST['designation'] : '';
    $num_tickets = isset($_POST['num_tickets']) ? $_POST['num_tickets'] : '1';
    $amount = isset($_POST['amount_paid']) ? $_POST['amount_paid'] : '';
    $lead_id = isset($_POST['lead_id']) ? $_POST['lead_id'] : '';
    $pan = isset($_POST['pan']) ? $_POST['pan'] : '';
    $amount_paid = 'Pending';
    $has_code = isset($_POST['has_code']) ? $_POST['has_code'] : '';
    $verify_code = isset($_POST['verify_code']) ? $_POST['verify_code'] : '';
    $categoryDescription = isset($_POST['category_description']) ? $_POST['category_description'] : '';
    $trans_id=$form_type='';

    // Prepare values and sanitize
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $form = isset($_POST['form']) ? $_POST['form'] : '';
    $formatted_date = date('d-m-Y, h:i a');
    $address = ''; // Address is empty on initial insert
    $stmt = $conn->prepare("
    INSERT INTO uef2025
    (
        name, email, phone, iam, company, designation, has_code, verify_code, 
        category_description, amount, trans_id, trans_status, source, medium, 
        campaign, term, lead_id, pan, address, type, num_tickets
    ) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssssssssssssssss",
        $name,
        $email,
        $ph,              // phone with your special formatting
        $iam,
        $company,
        $designation,
        $has_code,
        $verify_code,
        $categoryDescription,
        $amount,          // stored as varchar in DB
        $trans_id,
        $amount_paid,
        $source,
        $medium,
        $campaign,
        $term,
        $lead_id,
        $pan,
        $address,               // address is empty on initial insert
        $form_type,        // type (e.g. 'add_lead')
        $num_tickets
    );

    if ($stmt->execute()) {
        echo "✅ Inserted successfully";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();

    // Create the lead data array
   // $leadV = [[$name, $email, $ph, $iam, $pan, $company, $designation, $amount, $amount_paid, $source, $medium, $campaign, $lead_id, $formatted_date, '']];
}

if (isset($_POST['type']) && $_POST['type']=='upd_lead') {
    $lead_id = $_POST['lead_id'] ?? '';
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $file = __DIR__ . "/gsheet_log.txt"; // saves next to your PHP file

        // Save the response
       // file_put_contents($file, $lead_id.' - ' .$razorpay_payment_id.' - '.$payment_status. PHP_EOL, FILE_APPEND);
    if(!empty($lead_id)) {

        $stmt = $conn->prepare("
            UPDATE uef2025 
            SET trans_status = ?, trans_id = ? 
            WHERE lead_id = ?
        ");

        $stmt->bind_param("sss",  $payment_status, $razorpay_payment_id, $lead_id);

        if($stmt->execute()) {
            echo "✅ Payment status updated successfully";
        } else {
            echo "❌ Update Failed: " . $stmt->error;
        }

        $stmt->close();
    }
}

if (isset($_POST['type']) && $_POST['type']=='upd_payment') {
    
    //$lead_id = $_POST['lead_id'] ?? '';
    $order_id = $_POST['order_id'] ?? '';
    $razorpay_payment_id = $_POST['payment_id'] ?? '';
    $payment_status = $_POST['status'] ?? '';
    $file = __DIR__ . "/trans.txt"; // saves next to your PHP file
     file_put_contents($file, $order_id.' - '.$razorpay_payment_id.' - '.$payment_status. PHP_EOL, FILE_APPEND);

    if(!empty($razorpay_payment_id)) {

        $stmt = $conn->prepare("
            UPDATE uef2025 
            SET trans_status = ?
            WHERE order_id = ?
        ");

        $stmt->bind_param("ss", $payment_status, $order_id);

        if($stmt->execute()) {
            echo "✅ Payment status updated successfully";
        } else {
            echo "❌ Update Failed: " . $stmt->error;
        }

        $stmt->close();
    }
        
}

if (isset($_POST['type']) && $_POST['type']=='upd_pay_id') {
    
    $lead_id = $_POST['lead_id'] ?? '';
    $order_id = $_POST['order_id'] ?? '';
    $payment_status = 'pending';
    $file = __DIR__ . "/trans1.txt"; // saves next to your PHP file
     file_put_contents($file, $order_id.' - '.$lead_id. PHP_EOL, FILE_APPEND);

    if(!empty($order_id)) {

        $stmt = $conn->prepare("
            UPDATE uef2025 
            SET order_id = ?, trans_status = ?
            WHERE lead_id = ?
        ");

        $stmt->bind_param("sss", $order_id, $payment_status, $lead_id);

        if($stmt->execute()) {
            echo "✅ Payment status updated successfully";
        } else {
            echo "❌ Update Failed: " . $stmt->error;
        }

        $stmt->close();
    }
       
}

if (isset($_POST['type']) && $_POST['type']=='upd_extra') {

    $lead_id = $_POST['lead_id'] ?? '';
    $pan = strtoupper($_POST['pan'] ?? '');
    $company = $_POST['company'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $address = $_POST['address'] ?? '';

    if(!empty($lead_id)) {

        $stmt = $conn->prepare("
            UPDATE uef2025
            SET pan = ?, company = ?, designation = ?, address = ?
            WHERE lead_id = ?
        ");

        $stmt->bind_param("sssss", $pan, $company, $designation, $address, $lead_id);

        if($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Extra details updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }

        $stmt->close();
        exit; // ✅ Make sure script ends
    }

    echo json_encode(["status" => "error", "message" => "Lead ID missing"]);
    exit;
}
if (isset($_POST['type']) && $_POST['type']=='upd_extra') {

    $lead_id = $_POST['lead_id'] ?? '';
    $pan = strtoupper($_POST['pan'] ?? '');
    $company = $_POST['company'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $address = $_POST['address'] ?? '';

    if(!empty($lead_id)) {

        $stmt = $conn->prepare("
            UPDATE uef2025
            SET pan = ?, company = ?, designation = ?, address = ?
            WHERE lead_id = ?
        ");

        $stmt->bind_param("sssss", $pan, $company, $designation, $address, $lead_id);

        if($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Extra details updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }

        $stmt->close();
        exit; // ✅ Make sure script ends
    }

    echo json_encode(["status" => "error", "message" => "Lead ID missing"]);
    exit;
}
