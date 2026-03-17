<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Add User Account';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])) {
	//print_r($_POST); exit;
	
		if(isset($_GET['id'])) {
			$cirSql = "UPDATE users SET name='".mysqli_real_escape_string($conn, $_POST['name'])."', username='".mysqli_real_escape_string($conn, $_POST['username'])."', password='".mysqli_real_escape_string($conn, $_POST['password'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO users (name, username, password, created, updated) VALUES ('".mysqli_real_escape_string($conn, $_POST['name'])."', '".mysqli_real_escape_string($conn, $_POST['username'])."', '".mysqli_real_escape_string($conn, $_POST['password'])."', now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'add-user.php?id=".$lastId."';</script>";
		exit();
	
}
?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>                      
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT name, username, password  FROM users WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData);
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">     
                                                	<label> Name: </label>
                                                    <input type="text" name="name" class="form-control" <?php if(isset($editData['name'])) { ?> value="<?php echo $editData['name']; ?>" <?php } ?> required>                                                    	
                                                    <br />      
                                                   <label>Username: </label>
                                                    <input type="text" name="username" class="form-control" <?php if(isset($editData['username'])) { ?> value="<?php echo $editData['username']; ?>" <?php } ?> required>        
                                                    <br />      
                                                    <label>Password: </label>
                                                    <input type="password" name="password" class="form-control" <?php if(isset($editData['password'])) { ?> value="<?php echo $editData['password']; ?>" <?php } ?> required>        
                                                    <br />
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-info">
                                                 
                                                 <br />
                                                </div>
                                              
                                        </div>
                                    </div>
                                </div>
                                </form>
                                
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
