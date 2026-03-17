<?php ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta"); 

$pgHeadline = 'Login';
$pgID = 1;
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
include '../db.php'; 
//exit;
$pgHeadline = 'Login';
$pgID = 1;
if(isset($_SESSION['logged']) && !isset($_GET['id'])) {
	$pg = 'spend.php';
	echo "<script>window.location = '$pg';</script>";
	exit();
}

$bytUser = array('rwd','byt_rwd');
$bytPw = array('@-x&Mn29j5c+_rbX','n!Bte6gRTgQCW7Jj', 'byt123');

if(isset($_GET['id'])) { 
  echo 'Loading...';
	$rand = $_GET['id'];
	$cirRes = mysqli_query($conn, "SELECT tbl_id, client_name  FROM dashboard_accounts WHERE rand='$rand'");
	if (mysqli_num_rows($cirRes)==1) {
    $row = mysqli_fetch_assoc($cirRes);
		$_SESSION['tbl_id'] = $row['tbl_id'];
    $_SESSION['client_name'] = $row['client_name'];
		$_SESSION['logged'] = 1;
    //d($row); exit;
    $page = 'spend.php';
    if(isset($_GET['type']) && $_GET['type']='mon') { $page = 'spend-month.php'; } 
    echo "<script>window.location = '".$page."';</script>";
    //echo "<script>window.location = 'loading.php?pg=spend.php';</script>";
		exit();	
	}  else {
	
				$_SESSION['err'] = 'Invalid Login URL!';		
				echo "<script>window.location = 'loading.php?pg=login.php';</script>";
				exit();		
				
	}
}

if(isset($_GET['id'])) {
  echo 'Loading...';
	$rand = $_GET['id'];
	$cirRes = mysqli_query($conn, "SELECT tbl_id, client_name  FROM dashboard_accounts WHERE rand='$rand'");
	if (mysqli_num_rows($cirRes)==1) {
    $row = mysqli_fetch_assoc($cirRes);
		$_SESSION['tbl_id'] = $row['tbl_id'];
		$_SESSION['logged'] = 1;
    $page = 'spend.php';
    if(isset($_GET['type']) && $_GET['type']='mon') { $page = 'spend-month.php'; }
    echo "<script>window.location = '".$page."';</script>";
		exit();	
	}  else {
	
				$_SESSION['err'] = 'Invalid Login URL!';		
				echo "<script>window.location = 'login.php';</script>";
				exit();		
				
	}
}

if(isset($_POST['submit'])) {
  $filename = "proj_list.txt";
  $handle = fopen($filename, "r");
  $proj_list_txt = fread($handle, filesize($filename));
  fclose($handle);
  $pr_list = explode(",",$proj_list_txt ?? '');
  $proj_list_s = array();
  foreach($pr_list as $k => $v) {
    $proj_list_s[] = ucfirst(trim(strtolower($v)));
  }
  $proj_list_s = array_unique(array_filter($proj_list_s));
  $_SESSION['proj_list'] = $proj_list_s;
  //echo 'Loading...';
	$uname = 	$_POST['username'];
	$pw = 	$_POST['password'];

  if(isset($_GET['redirect']) && $_GET['redirect']!=''){
    $pg = $_GET['redirect'];		
  } else {
    $pg ='';
  }

  if($uname == 'rwd' && ($pw=='GH!8iW@a' || $pw=='9930')){
      $_SESSION['client'] = 'casa';
      if($pg=='') { $pg = 'spend.php'; }
      echo "<script>window.location = 'loading.php?pg=".$pg."';</script>"; exit;
  } else if($uname == 'casa' && $pw=='7$bUc51'){
      $_SESSION['client'] = 'other';
      if($pg=='') { $pg = 'placement-lg.php'; }
      echo "<script>window.location = 'loading.php?pg=".$pg."';</script>";  exit;
  } else {

      $_SESSION['err'] = 'Invalid Login';		
      echo "<script>window.location = 'login.php';</script>";
      exit();		
      
  }
 
}
?> 
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="images/favicon.ico" type="image/ico" />

    <title>RWD - Ads dashboard</title>

    <!-- Bootstrap -->
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="/vendors/animate.css/animate.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="/build/css/custom.css" rel="stylesheet">
    
    <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    <!--=======Font Awesome======-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <!--=======Custom Style======-->
    <link rel="stylesheet" href="/css/login.css">
  </head>

  <body class="login">
  
  	<form method="post" action="" id="login-form">  
      
     
      <div class="head"><img src="/images/adrescue-2021.png" alt="..." height="40"  > <br />
   
  </div>
     
  <h4 style="text-align: center;">Ads dashboard</h4>
      <br>
      
      <br>
      <div class="social">
       <!-- <h4> Connect with</h4>
        <ul>
          <li> 
          <a href="fb-login.php" class="facebook">
            <span class="fa fa-facebook"></span>
          </a>
          </li>         
          <li>
            <a href="login-g.php" class="google-plus">
              <span class="fa fa-google"></span>
            </a>
          </li>
        </ul>
       </div>

       <div class="divider">
         <span>or</span>
       </div>-->
	  
      <div class="input-field">     
      <?php include '../alert.php'; ?> 
        <label for="username">Username</label> 
        <input type="text" name="username" required  class="form-control" /><br>
        <label for="password">Password</label> 
        <input type="password" name="password" required  class="form-control" /><br>
        <input type="submit" name="submit" class="btn-info" value="Login" />
        
      </div>

  </form>
  
  </body>
</html>
