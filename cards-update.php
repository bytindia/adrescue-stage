<?php
include 'config.php'; // include your DB connection file

if (isset($_POST['save'])) {
    $tbl_id = $_POST['tbl_id'];
    $card_limit = $_POST['card_limit'];
    $available_limit = $_POST['available_limit'];
    $changed_at = date("Y-m-d H:i:s");

    $query = "UPDATE cards 
              SET card_limit = '$card_limit', 
                  available_limit = '$available_limit', 
                  changed_at = '$changed_at' 
              WHERE tbl_id = '$tbl_id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['suc'] = "Card details updated successfully!";
    } else {
        $_SESSION['err'] = "Update failed: " . mysqli_error($conn);
    }

    echo "<script>window.location = 'cards.php';</script>";
    exit();
}
?>
