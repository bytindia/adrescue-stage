<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Set Invoice Number';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])) {
	//print_r($_POST); exit;
	
		if(isset($_POST['inv_no']) && $_POST['inv_no']!='') {
			$cirSql = "UPDATE inv_no SET inv_no='".mysqli_real_escape_string($conn, trim($_POST['inv_no']))."', inv_no_pi='".mysqli_real_escape_string($conn, trim($_POST['inv_no_pi']))."' WHERE tbl_id=1";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 			
		} 
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'set-invoice-no.php';</script>";
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
									$sqlD=mysqli_query($conn, "SELECT inv_no, inv_no_pi  FROM inv_no WHERE tbl_id=1");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
										//print_r($editData);
										
								?>
                               
                               <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-5">     
                                                	<label> Last Invoice Number: </label>
                                                    	<input type="text" name="inv_no" class="form-control" <?php if(isset($editData['inv_no'])) { ?> value="<?php echo $editData['inv_no']; ?>" <?php } ?> required>             <small> * Enter only the numeric number</small>                                       	
                                                    <br />      
                                                    <input type="text" name="inv_no_pi" class="form-control" <?php if(isset($editData['inv_no_pi'])) { ?> value="<?php echo $editData['inv_no_pi']; ?>" <?php } ?> required>             <small> * Enter only the numeric number</small>                                       	
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
