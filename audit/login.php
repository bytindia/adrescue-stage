<?php session_start(); 
$pgHeadline = 'Login';
$pgID = 1;
$_SESSION['uid'] = 9; 
include 'db.php'; 

$pgHeadline = 'Login';
$pgID = 1;

if(isset($_GET['uid']) && $_GET['uid']!='') {
	$_SESSION['uid']=$_GET['uid'];
}
//$_SESSION['uid']=7;
if(isset($_SESSION['uid'])) {
	$pg = 'index.php';
	echo "<script>window.location = '$pg';</script>"; 
	exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config-passwords.php';
$bytUser = $auditUser;
$bytPw   = $auditPw;

if(isset($_POST['submit'])) {
	$uname = 	$_POST['email']; 
	$pw = 	$_POST['password'];
	
	if (in_array($uname, $bytUser) && in_array($pw, $bytPw)) {
		$cirRes = mysqli_query($conn, "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc  FROM users WHERE tbl_id=2");
		$_SESSION['guest'] = 1;
	} else {
		$cirRes = mysqli_query($conn, "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc  FROM users WHERE username='$uname' and password='$pw'");
	}
	//$results = $conn->query("SELECT tbl_id, g_id, access_token, fb_id FROM users WHERE username='$uname' and password='$pw'");
	//$username_exist = $results->num_rows;
	
	
	//if($uname=='admin' && $pw=='bytads2019')
	if(mysqli_num_rows($cirRes)==1)
	{
				//$row = mysqli_fetch_array($results);				
				//echo $row['id']; exit;
				//$row = $results->fetch_assoc();
				//print_r($row); exit;				
				//$query = "SELECT tbl_id, g_id, access_token, fb_id FROM users WHERE email='bytramesh@gmail.com'";
				//$result = mysqli_query($conn, $query);
				$row = mysqli_fetch_assoc($cirRes);
				$_SESSION['uid'] = $row['tbl_id'];
				$_SESSION['name'] = $row['name'];
				$_SESSION['fb_id'] = $row['fb_id'];
				$_SESSION['g_id'] = $row['g_id'];
				$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
				$_SESSION['g_token'] = $row['g_token'];
				$_SESSION['g_mcc'] = $row['g_mcc'];
				$access_token = $row['access_token']; 
				
				$userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='".$_SESSION['uid']."'");	
			    $getRw2 = mysqli_fetch_assoc($userRes2);
			    if(mysqli_num_rows($cirRes)==1)
				{		   
					$_SESSION['in_id'] = $getRw2['in_id'];
					$_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
				}
				$pg = 'client-dashboard.php';				
				//if(isset($_SESSION['pg'])) { $pg=$_SESSION['pg']; } else { $pg = '/seller-home'; }
				//header('Location:'.$pg);
				echo "<script>window.location = '$pg';</script>";
				exit();
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

    <title>BYT - AdsNinja Admin</title>

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
    <style>
	form#login-form {
		    max-width: 700px;
			}
	</style>
  </head>

  <body class="login">
  
  	<form method="post" action="" id="login-form">  
      
      <div class="head"><img src="https://www.bytindia.com/wp/wp-content/themes/bytindia_v4/images/logo.png" alt="..." ><br><br>
      <img src="images/logo-350.png" alt="...">
      </div>
      
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
      <?php //include '/alert.php'; 
	  ?> 
      <?php 
						if(!isset($_SESSION['fb_id']) || $_SESSION['fb_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="fb-login.php?update=1">
                                      <img src="images/fb-connect.png" height="80">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} ?><!--
        <label for="email">Username</label>
        <input type="text" name="email" required class="form-control" />
        <label for="password">Password</label> 
        <input type="password" name="password" required  class="form-control" />
        <input type="submit" name="submit" class="btn-info" value="Login" />
        -->
      </div>

  </form>
  
  </body>
</html>
