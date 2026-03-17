<?php
session_start();
$pg = 'leads-view.php?id=276254866158440';				
					//if(isset($_SESSION['pg'])) { $pg=$_SESSION['pg']; } else { $pg = '/seller-home'; }
					//header('Location:'.$pg);
					echo "<script>window.location = '$pg';</script>";
					exit();
					
?>