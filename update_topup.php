<?php
include 'db.php'; // or 'config.php' depending on your setup
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $field = $_POST['field'];
    $value = floatval($_POST['value']);

    if (!in_array($field, ['daily_budget', 'bud_bal'])) {
        echo "Invalid field";
        exit;
    }

    $stmt = $conn->prepare("UPDATE topup SET $field = ? WHERE tbl_id = ?");
    $stmt->bind_param("di", $value, $id);
    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Database error";
    }
    $stmt->close();
    $conn->close();
}
?>
