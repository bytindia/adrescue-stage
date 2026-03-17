<?php
session_start();
include '../db.php';
require_once __DIR__ . '/../config-passwords.php';
unset($_SESSION['err']);
if(isset($_POST['submit'])){
    //d($_POST);
    //if ($_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
    if (in_array($_POST['username'], $bytUser) && in_array($_POST['password'], $bytPw)) {
        $_SESSION['username'] = $_POST['username'];  // Save the session
         // Redirect to the Add Account page

          $user['id'] = array_search($_POST['username'], $bytUser); //$user['id']; 
        $_SESSION['user_id'] = $user['id'];
        
         
        $_SESSION['log']=1;        

        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $stmt = $conn->prepare("INSERT INTO user_logs (user_id, ip_address, user_agent, action) VALUES (?, ?, ?, 'login')");
        $stmt->bind_param("iss", $user['id'], $ip, $user_agent);
        $stmt->execute();

        header("Location: index.php"); 

    } else {
        $_SESSION['err'] = "Invalid login credentials!";
    }
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">

    <title>AdsNinja - Rescue Your Campaigns</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/navbar-fixed/">

    <!-- Bootstrap core CSS -->
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="https://getbootstrap.com/docs/4.0/examples/navbar-fixed/navbar-top-fixed.css" rel="stylesheet">
  </head>

  <body>
  <?php // include 'menu.php'; ?>
  <main role="main" class="container">
      <div class="jumbotron">
    <h2 class="mt-5">Login Page</h2>
    <?php echo $_SESSION['err']; ?>
    <form action="login.php" method="POST" id="loginForm">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" name="submit" class="btn btn-primary">Login</button>
    </form>
  </div>
</main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
