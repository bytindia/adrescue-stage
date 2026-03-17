<?php
session_start();
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include 'db.php';
include 'config.php';
include_once('linkedin_oauth_config2.php');


if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $linkedIn->setAccessToken($_SESSION['access_token']); 
  $accessToken = $_SESSION['access_token'];
}

if ($linkedIn->isAuthenticated()) 
{
   echo $_SESSION['access_token'] = (string) $linkedIn->getAccessToken();
   //$userData = $linkedIn->get('v1/people/~:(firstName,lastName,headline,id,emailAddress)');
   $userData = $linkedIn->get('v2/me?projection=(firstName,lastName,headline,id,emailAddress)');

   d($userData);
   exit;
   
   /*echo "You are logged in!<br>";
   
   
   $_SESSION['facebook_access_token'];
   
   $accessToken = $_SESSION['facebook_access_token'];
   $response = $fb->get('/me?locale=en_US&fields=name,email,first_name,picture,last_name,location', $accessToken); 
   $profile = $response->getGraphNode()->asArray();
  //d($profile); exit;
  //$_SESSION['userdata'] = $profile; 
   //print_r($profile);
  // $_SESSION['uid'] = $profile['id'];
   $_SESSION['fb_id'] = $profile['id'];
   $userRes = mysqli_query($conn, "select tbl_id from users WHERE fb_id='".$profile['id']."'");				
   if(mysqli_num_rows($userRes)==0) {
					$userSql = "INSERT INTO users (fb_id, name, email, access_token, created) VALUES ('".mysqli_real_escape_string($conn,$profile['id'])."', '".mysqli_real_escape_string($conn, $profile['name'])."', '".mysqli_real_escape_string($conn, $profile['email'])."', '".mysqli_real_escape_string($conn, $accessToken)."', now());"; 
					mysqli_query($conn, $userSql) or die(mysqli_error()); 					
					$redURL = 'adAccounts.php';
				} else {					
					
					$userSql = "UPDATE users SET name='".mysqli_real_escape_string($conn, $profile['name'])."', email='".mysqli_real_escape_string($conn, $profile['email'])."', access_token='".mysqli_real_escape_string($conn, $accessToken)."', updated=now() WHERE fb_id='".$profile['id']."'";
					mysqli_query($conn, $userSql) or die(mysqli_error()); 
					
					$redURL = 'index.php';
   }
   
   $userRes2 = mysqli_query($conn, "select tbl_id, fb_id, g_id from users WHERE fb_id='".$profile['id']."'");	
   $getRw2 = mysqli_fetch_assoc($userRes2);
   //$lastId = $getRw['tbl_id']; 
   
   $_SESSION['uid'] = $getRw2['tbl_id'];
   $_SESSION['fb_id'] = $profile['id'];
   $_SESSION['g_id'] = $getRw2['g_id'];
   
   echo "<script>window.location = '".$redURL."';</script>";
   exit;*/
 

} else {
  //$permissions = ['email','ads_management','ads_read','manage_pages','read_insights','leads_retrieval'];
  //$loginUrl = $helper->getLoginUrl($siteURL.'fb-login.php', $permissions);
  //d($linkedIn);
  //echo $linkedInAuthUrl; exit;
  echo "<script>window.location = '".$linkedInAuthUrl."';</script>"; exit;
  //echo '<a href="loading.php?pg=' . $loginUrl . '"><img src="assets/fbconnect.png" /></a>';
}
exit;
?>

<?php
include_once('linkedin_oauth_config2.php');
?>
</head>
<body class="">
  <div class="container">
    <h2>LinkedIn Login</h2>
    <div class="well">
        <?php if (isset($linkedInAuthUrl)): ?>
      <form action="<?php echo $linkedInAuthUrl; ?>" method="get">
        <a href="loading.php?pg=<?php echo $linkedInAuthUrl; ?>">
        <img class="resource-paragraph-image lazy-load lazy-load-src" alt="Sign in with LinkedIn" src="https://content.linkedin.com/content/dam/developer/global/en_US/site/img/signin-button.png" pagespeed_url_hash="1811720327" onload="pagespeed.CriticalImages.checkImageForCriticality(this);">
        </a>
    </form>
      <!-- Show User Profile otherwise-->
        <?php else: ?>
      <h3>Successfully! Authenticated, Welcome <?php echo $userData['firstName'] .' '.$userData['lastName'] ?></h3>
      <a class="btn btn-danger" href="?logout=true">Logout</a>
        <?php endif ?>
      </div>
 
  </div>