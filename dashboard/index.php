<?php session_start();
if(!isset($_SESSION['logged'])) {
	
	echo "<script>window.location = 'login.php';</script>";
	exit();
} else {
  
	echo "<script>window.location = 'overview.php';</script>";
	exit();
}