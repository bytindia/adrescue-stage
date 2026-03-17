<?php session_start(); 
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "on")
{
   // header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    //exit();
}
include 'db.php'; 

if(!isset($no_auth) || $no_auth!=1) { Auth(); }

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
//echo $_SESSION['stDt']; 
ini_set('display_errors', 0);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

error_reporting(error_reporting() ^ E_DEPRECATED);
?>
<!DOCTYPE html>
<html lang="en" class="fixed sidebar-left-collapsed">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="images/favicon-ar-32x32.png" type="image/png" />
	<?php if(!isset($pgHeadline)) { $pgHeadline = 'AdRescue'; } ?>
    <title><?php echo $pgHeadline; ?></title>

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    
    <!-- bootstrap-progressbar -->
    <link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom-test.css" rel="stylesheet">
    <link href="web/pagination.css" rel="stylesheet">
    <link href="assets/css/pagination.css" rel="stylesheet">
    <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="css/table.css" rel="stylesheet">
    
  </head>