<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php'; // mysqli connection = $conn

if(isset($_POST['pageId']) && $_POST['pageId']!='')
{
    $lead = $_POST['lead'];
    $source = $_POST['formName'];

    $user = unserialize($lead);
    $name  = $user['full_name'] ?? '';
    $phone = $user['phone_number'] ?? '';
    $email = $user['email'] ?? '';

    $status = 'New'; // default status

    // Correct insert query
    $stmt = $conn->prepare("INSERT INTO imagestar_dashboard_leads 
        (name, phone, email, enquiry_date, status, source)
        VALUES (?, ?, ?, NOW(), ?, ?)");

    $stmt->bind_param(
        "sssss",
        $name,
        $phone,
        $email,
        $status,
        $source
    );

    if($stmt->execute()){
        echo "Lead stored successfully";
    } else {
        echo "DB Error: ".$stmt->error;
    }

    $stmt->close();
}
?>
