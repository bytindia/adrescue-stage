<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();
date_default_timezone_set("Asia/Calcutta");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loading...</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
  margin: 0;
  padding: 0;
  background: #ffffff;
  font-family: sans-serif;
}

.loader-container {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

/* Spinner Ring */
.ring {
  position: relative;
  width: 150px;
  height: 150px;
  background: transparent;
  border: 3px solid #d9d9d9;
  border-radius: 50%;
  box-shadow: 0 0 20px rgba(0,0,0,.1);
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Logo */
.ring img.logo {
  width: 120px;
  object-fit: contain;
}

/* Rotating Ring Border */
.ring:before {
  content: '';
  position: absolute;
  top: -3px;
  left: -3px;
  width: 100%;
  height: 100%;
  border: 3px solid transparent;
  border-top: 3px solid #0583f2;
  border-right: 3px solid #0583f2;
  border-radius: 50%;
  animation: animateC 2s linear infinite;
}

/* Moving Dot */
span {
  display: block;
  position: absolute;
  top: calc(50% - 2px);
  left: 50%;
  width: 50%;
  height: 4px;
  background: transparent;
  transform-origin: left;
  animation: animate 2s linear infinite;
}

span:before {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #0583f2;
  top: -6px;
  right: -8px;
  box-shadow: 0 0 20px #0583f280;
}

/* Subtitle */
.loading-subtitle {
  margin-top: 20px;
  font-size: 14px;
  color: #666;
  letter-spacing: 0.5px;
  text-align: center; /* ensures perfect centering */
}

@keyframes animateC {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes animate {
  0% { transform: rotate(45deg); }
  100% { transform: rotate(405deg); }
}
@keyframes fadePulse {
  0%   { opacity: 1; }
  50%  { opacity: 0.60; }
  100% { opacity: 1; }
}

.ring img.logo {
  width: 120px;
  object-fit: contain;
  animation: fadePulse 2.8s ease-in-out infinite;
}
</style>
</head>

<body>

<div class="loader-container">

    <div class="ring">
        <img src="https://stage.adrescue.in/images/adRes-b.png" class="logo" />
        <span></span>
    </div>

    <div class="loading-subtitle">
        <i>Loading… Please wait.</i>
    </div>

</div>

</body>
</html>

<?php //exit;


// ---------------------------------------------
// PHP Redirect Logic (unchanged)
// ---------------------------------------------

$dt_q = '';

if(isset($_GET['pg'])) { 
  if ($_GET['pg'] == '') {
    $_GET['pg'] = 'index.php';
}

$pg = $_GET['pg'];

// Get all query parameters except 'pg'
$qsArray = $_GET;
unset($qsArray['pg']);

$qs = http_build_query($qsArray);

$redPg = $pg;
if (!empty($qs)) {
    $redPg .= (strpos($pg, '?') === false ? '?' : '&') . $qs;
}

if (substr($redPg, -1) == '&') {
    $redPg = substr($redPg, 0, -1);
}

if(substr($redPg, -1)=='?') { $redPg=substr_replace($redPg ,"",-1); }

echo "<script>window.location = '".$redPg."';</script>";
exit();

}
?>
