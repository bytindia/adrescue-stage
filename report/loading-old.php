<?php ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  ?>
<title>Loading...</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<br><br><br>
<center><div class="progress"></div><br> Loading . . .</center>

<style>
.progress {
   width: 100.8px;
   height: 16.8px;
   -webkit-mask: linear-gradient(90deg,#2e94d5 70%,#0000 0) left/20% 100%;
   background: linear-gradient(#2e94d5 0 0) left/0% 100% no-repeat
       #dbdcef;
   animation: progress-422c3u 1.6s infinite steps(6);
}

@keyframes progress-422c3u {
   100% {
      background-size: 120% 100%;
   }
}
center {
  font-family: Trebuchet MS, sans-serif;
  padding: 15px;
  position: absolute;
  top: 50%;
  left: 50%;
  -ms-transform: translateX(-50%) translateY(-50%);
  -webkit-transform: translate(-50%,-50%);
  transform: translate(-50%,-50%);
  font-style: italic;
}  
</style>
<?php //exit; 

$dt_q = '';
if(isset($_GET['st']) && $_GET['st']!='' && $_GET['st']!='Invalid date') {
   $_SESSION['st'] = $_GET['st'];
   $_SESSION['en'] = $_GET['en'];
   //$dt_q = '&st='.$_SESSION['st'].'&en='.$_SESSION['en'];
} else if(isset($_SESSION['st']) && $_SESSION['st']!='' && $_SESSION['st']!='Invalid date') {
   $dt_q = '&st='.$_SESSION['st'].'&en='.$_SESSION['en'];
}

if(isset($_GET['pg'])) {
	$pg = $_GET['pg'];
     $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[REQUEST_URI]";
     //$key = array('key');
     $filteredURL = preg_replace('~(\?|&)pg=[^&]*~', '$1', $fullUrl);;
    // $filteredURL = str_replace("https://stage.adrescue.in/placements/loading.php", "");
     parse_str($filteredURL,$myArray);
    // print_r($myArray);
     array_shift($myArray);
   // $vars = array('page' => 23, 'search' => 'etutorialspoint');
   $qs = http_build_query($myArray); //exit;

   //if($qs=='') { $qs=substr_replace($string ,"",-1); }
   //echo 
   $redPg = $pg."?".$qs."".$dt_q;

   if(substr($redPg, -1)=='?') { $redPg=substr_replace($redPg ,"",-1); }
   //echo $redPg; exit;
	echo "<script>window.location = '".$redPg."';</script>";
	exit();
}

?>