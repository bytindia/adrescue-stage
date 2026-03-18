<?php session_start();
if(!isset($_SESSION['client'])) {
	
	echo "<script>window.location = 'login.php';</script>";
	exit();
} else {
   if($_SESSION['client'] == 'casa') {
		echo "<script>window.location = 'placement-casa.php';</script>"; exit;
   }
   if($_SESSION['client'] == 'other') {
		echo "<script>window.location = 'placement-lg.php';</script>";  exit;
   }
	echo "<script>window.location = 'placement-lg.php';</script>";
	exit();
}