<?php
session_start();
$_SESSION = array();
unset($_SESSION);
session_destroy();
session_start();
$_SESSION['suc'] = "Successfully Logged Out!";
if(isset($_GET['placement'])){
    header("location: login.php");
    exit;
}
header("location: login.php");
exit;
?>