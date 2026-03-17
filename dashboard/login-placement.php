<?php session_start(); 
echo "<script>window.location = 'https://stage.adrescue.in/placements';</script>"; exit();
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
	$pg = 'overview.php';
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
		$_SESSION['logged'] = 1;
    echo "<script>window.location = 'overview.php';</script>";
		exit();	
	}  else {
	
				$_SESSION['err'] = 'Invalid Login URL!';		
				echo "<script>window.location = 'login.php';</script>";
				exit();		
				
	}
}

if(isset($_POST['submit'])) {
  echo 'Loading...';
	//$uname = 	$_POST['email'];
	$pw = 	$_POST['password'];

    if($pw=='7$bUc51'){
        $_SESSION['client'] = 'casa';
        echo "<script>window.location = 'placement-casa.php';</script>"; exit;
    } else if($pw=='VmT$945'){
        $_SESSION['client'] = 'other';
        echo "<script>window.location = 'placement-lg.php';</script>";  exit;
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

    <title>Client Ads dashboard - AdRescue</title>

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
      
     
      <div class="head"><img src="/images/adrescue-2021.png" alt="..." height="40"  >
    <!-- <img src="https://www.rwd.in/wp-content/uploads/2018/02/logo-2.jpg" alt="gsqure" height="40" >-->
  </div>
     
  <h4 style="text-align: center;">Ads Dashboard</h4>
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
     
        <label for="password">Password</label> 
        <input type="password" name="password" required  class="form-control" /><br>
        <input type="submit" name="submit" class="btn-info" value="Login" />
        
      </div>

  </form>
  
  </body>
</html>
