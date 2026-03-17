<?php session_start();  $_SESSION['uid']=9;
if($_SERVER["HTTPS"] != "on")
{
   // header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    //exit();
}
include 'db.php'; auditAuth(); 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
//d($_SESSION);
if(isset($_GET['permission']) && $_GET['permission']==1 && $_SESSION['fb_id']!='') {
	
	$query = "SELECT tbl_id, name, fb_id, fb_token FROM audit_users WHERE tbl_id='".$_SESSION['uid']."'"; 
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$url = "https://graph.facebook.com/".$api_ver."/".$row['fb_id']."/permissions?access_token=".$row['fb_token'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	
    $_SESSION = array();
	unset($_SESSION);
	session_destroy();
	
	
	$_SESSION['suc'] = "Successfully Logged Out!";
	echo "<script>window.location = 'login.php';</script>";
	exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="fixed sidebar-left-collapsed">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="images/favicon.ico" type="image/ico" />
	<?php if(!isset($pgHeadline)) { $pgHeadline = 'BYT Ads Rescue'; } ?>
    <title><?php echo $pgHeadline; ?></title>

    <!-- Bootstrap -->
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	
    <!-- bootstrap-progressbar -->
    <link href="/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="/vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="/build/css/custom.css" rel="stylesheet">
    <link href="/web/pagination.css" rel="stylesheet">
    <link href="/assets/css/pagination.css" rel="stylesheet">
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
  </head>