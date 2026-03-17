<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Add Employee ';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])) {
	//print_r($_POST); exit;
	
		if(isset($_GET['id'])) {
			$cirSql = "UPDATE employee SET name='".mysqli_real_escape_string($conn, $_POST['name'])."', phone='".mysqli_real_escape_string($conn, $_POST['phone'])."', email='".mysqli_real_escape_string($conn, $_POST['email'])."', active='yes' WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO employee (name, phone, email, created) VALUES ('".mysqli_real_escape_string($conn, $_POST['name'])."', '".mysqli_real_escape_string($conn, $_POST['phone'])."', '".mysqli_real_escape_string($conn, $_POST['email'])."', now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'employee-add.php?id=".$lastId."';</script>";
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
										$sqlD=mysqli_query($conn, "SELECT name, email, phone  FROM employee WHERE tbl_id='".$_GET['id']."'");
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
                                                    <label> Phone: </label>
                                                    <input type="text" name="phone" class="form-control" <?php if(isset($editData['phone'])) { ?> value="<?php echo $editData['phone']; ?>" <?php } ?> required>                                                    	
                                                    <br />     
                                                   <label>Email: </label>
                                                    <input type="email" name="email" class="form-control" <?php if(isset($editData['email'])) { ?> value="<?php echo $editData['email']; ?>" <?php } ?>>        
                                                    <br />      
                                                    <a href="employee.php" class="btn btn-default">Back</a> 
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
