<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath . 'db.php';
require_once $dirPath . 'google-sheets-api/vendor/autoload.php';
require_once $dirPath . 'google-sheets-api/class-db.php';
require_once $dirPath . 'google-sheets-api/config.php';
include $dirPath . 'google-sheets-api/insert-row.php';
$uId = 2;
$tbl_id = 2;



$input = file_get_contents('php://input');
$lead = json_decode($input, true);

//print_r($_POST); print_r($lead); exit;
if (isset($_POST)) {

    $spreadsheetId = '1FgMShgsBN1FziCmJBi1mVSP7WwPNP5M_av9x1WwlmTg';
    $sheetTab = 'Expo';

    if($_POST['type']=='add_lead_expo'){
        $tbl_name='uef2025_expo';
    } else {
        $tbl_name='uef2025';
    }
    
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
    $iam_other = isset($_POST['iam_other']) ? $_POST['iam_other'] : '';
    $company = isset($_POST['company']) ? $_POST['company'] : '';
    $designation = isset($_POST['designation']) ? $_POST['designation'] : '';
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
INSERT INTO uef2025_expo
(
    name, email, phone, iam, other_iam, company, designation,
    has_code, verify_code, category_description, amount,
    trans_id, trans_status, source, medium, campaign, term,
    lead_id, pan, address, type
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssssssssssssssss",
    $name,
    $email,
    $ph,
    $iam,
    $iam_other,
    $company,
    $designation,
    $has_code,
    $verify_code,
    $categoryDescription,
    $amount,
    $trans_id,
    $amount_paid,
    $source,
    $medium,
    $campaign,
    $term,
    $lead_id,
    $pan,
    $address,
    $form_type
);


    if ($stmt->execute()) {
        //echo "✅ Inserted successfully";
    } else {
       // echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
    
    //$source = $medium = $campaign = '';
    
    $created_at = date('Y-m-d H:i:s');

    
    $formatted_date = date('d-m-Y, h:i a');

    // Create the lead data array
    $leadV = [[$name, $email, $ph, $iam, $iam_other, $source, $medium, $campaign, $formatted_date]];


    append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab); // exit;
    //$cirSql = "UPDATE ananta_leads SET ref='yes' WHERE tbl_id=".$lastId."";

    $data = array('code' => 200, 'response' => "success");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
}
