<?php session_start();


require_once 'vendor/autoload.php';
require_once 'class-db.php';
  
require_once 'config.php';
if(isset($_SESSION['uid'])) { $_GET['uid']=$_SESSION['uid'];  }
if(isset($_GET['uid'])) { $_GET['uid']=$_GET['uid']; $_SESSION['uid']=$_GET['uid']; }
try {
    $adapter->authenticate();
    $token = $adapter->getAccessToken(); 
    //print_r($token); exit;
    $db = new DB();
   // echo $_SESSION['uid']; echo '<br>';
   // echo json_encode($token); exit;
    $db->update_access_token($_SESSION['uid'], json_encode($token));
    echo "Access token inserted successfully.";
    echo "<script>window.location = 'https://stage.adrescue.in/leads-acc.php';</script>";
   exit();
}
catch( Exception $e ){
    echo $e->getMessage() ;
}