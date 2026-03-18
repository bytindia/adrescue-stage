<?php ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  ?>
<title>Loading...</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<br><br><br>

<div class="ring">Loading
  <span></span>
</div>

<style>
    body
{
  margin:0;
  padding:0;
}
.ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
    width: 150px;
    height: 150px;
    background: transparent;
    /* border: 3px solid #6eb0f0; */
    border-radius: 50%;
    text-align: center;
    line-height: 150px;
    font-family: sans-serif;
    font-size: 20px;
    color: #6eb0f0;
    letter-spacing: 4px;
    text-transform: uppercase;
    /* text-shadow: 0 0 10px #6eb0f0; */
    box-shadow: 0 0 20px rgb(161 212 251 / 50%);
}
.ring:before
{
  content:'';
  position:absolute;
  top:-3px;
  left:-3px;
  width:100%;
  height:100%;
  border:3px solid transparent;
  border-top:3px solid #6eb0f0;
  border-right:3px solid #6eb0f0;
  border-radius:50%;
  animation:animateC 2s linear infinite;
}
span
{
  display:block;
  position:absolute;
  top:calc(50% - 2px);
  left:50%;
  width:50%;
  height:4px;
  background:transparent;
  transform-origin:left;
  animation:animate 2s linear infinite;
}
span:before
{
  content:'';
  position:absolute;
  width:16px;
  height:16px;
  border-radius:50%;
  background:#6eb0f0;
  top:-6px;
  right:-8px;
  box-shadow:0 0 20px #6eb0f0;
}
@keyframes animateC
{
  0%
  {
    transform:rotate(0deg);
  }
  100%
  {
    transform:rotate(360deg);
  }
}
@keyframes animate
{
  0%
  {
    transform:rotate(45deg);
  }
  100%
  {
    transform:rotate(405deg);
  }
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
    // $filteredURL = str_replace("https://adsninja.adrescue.in/placements/loading.php", "");
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