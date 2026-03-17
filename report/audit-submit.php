<?php session_start();
//include '../db.php';

if(isset($_POST['submit'])) {
  //d($_POST); 
  //$pg_id = $_POST['fb_pg'];
  $acc_id = $_POST['ad_acc'];
  echo "<script>window.location = 'loading.php?pg=report-view.php?acc_id=".$acc_id."';</script>";
 exit();
}