<?php
session_start();
$_SESSION['vrx_logged_in'] = false;
unset($_SESSION['vrx_logged_in'], $_SESSION['vrx_user']);
session_destroy();
header('Location: login.php');
exit();
