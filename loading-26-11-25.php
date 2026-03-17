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
      background: #fff;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .loading-container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: #fff;
    }

    .loading-content {
      text-align: center;
      padding: 40px;
      background: white;
      border-radius: 12px;
      
      min-width: 300px;
    }

    .loading-spinner {
      font-size: 3em;
      color: #2196f3;
      margin-bottom: 20px;
      display: block;
    }

    .loading-title {
      font-size: 18px;
      color: #333;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .loading-subtitle {
      font-size: 14px;
      color: #666;
      margin-bottom: 0;
    }

    /* Fallback for older browsers without FontAwesome */
    .loading-spinner:before {
      content: "⟳";
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
  
  <!-- FontAwesome for better spinner icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

  <div class="loading-container">
    <div class="loading-content">
      <i class="fa fa-spinner fa-spin loading-spinner"></i>
      <div class="loading-title">Loading...</div>
      <div class="loading-subtitle"><i>Please wait, Fetching report summary…</i></div>
    </div>
  </div>

</body>
</html>
<?php //exit; 

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

// Optional: remove trailing '&' if exists
if (substr($redPg, -1) == '&') {
    $redPg = substr($redPg, 0, -1);
}

//echo $redPg;
  // exit;

   if(substr($redPg, -1)=='?') { $redPg=substr_replace($redPg ,"",-1); }


   
  // echo $redPg; exit;
	echo "<script>window.location = '".$redPg."';</script>";
	exit();
}

?>