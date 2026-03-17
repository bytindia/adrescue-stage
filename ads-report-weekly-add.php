<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Add Account - Ads Report Summary';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])){
	//print_r($_POST); exit;
	if($_POST['fb_acc']!='' || $_POST['fb_acc']!='') {
		
		if(!isset($_POST['weekly'])) { $_POST['weekly']=0; }
		if(!isset($_POST['monthly'])) { $_POST['monthly']=0; }
		
		if(isset($_GET['id'])) {
			$cirSql = "UPDATE ads_report_weekly SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', fb_acc='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', pg_id='".mysqli_real_escape_string($conn, $_POST['pg_id'])."',  g_acc='".mysqli_real_escape_string($conn, $_POST['g_acc'])."', weekly='".mysqli_real_escape_string($conn, $_POST['weekly'])."', monthly='".mysqli_real_escape_string($conn, $_POST['monthly'])."', email_ids='".mysqli_real_escape_string($conn, $_POST['email_ids'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO ads_report_weekly (uid, fb_id, client_name, fb_acc, g_acc, pg_id, weekly, monthly, email_ids, created, updated) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['pg_id'])."', '".mysqli_real_escape_string($conn, $_POST['weekly'])."', '".mysqli_real_escape_string($conn, $_POST['monthly'])."', '".mysqli_real_escape_string($conn, $_POST['email_ids'])."', now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		
		if(isset($_FILES['file']['tmp_name'])) {
			$file = $_FILES['file']['tmp_name'];
			$handle = fopen($file, "r");
			$c = 0;
			
			$allowed =  array('csv',);
			$filename = $_FILES['file']['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if(!in_array($ext,$allowed) ) {
				$_SESSION['err'] = 'Please upload CSV file format!';	
				echo "<script>window.location = 'ads-report-weekly.php?id=".$lastId."';</script>";
				exit();
			}
			
			$carsClient = array('Qualified','Appointment Fixed','Test drive fixed','Booking Taken');
			$realEstate = array('Qualified','Qualified');
					
			while(($filesop = fgetcsv($handle, 1000, ",")) !== false)
			{
				if($c>=1) {
					$feedback = array();
					$name = $filesop[0];
					$email = $filesop[1];
					$phone = $filesop[2];
					//$feedback[] = $filesop[3];
					
					if(isset($filesop[3]) && trim($filesop[3])!='' && in_array($filesop[3], $carsClient)) { array_push($feedback, $filesop[3]); }
					if(isset($filesop[4]) && trim($filesop[4])!='' && in_array($filesop[4], $carsClient)) { array_push($feedback, $filesop[4]); }
					if(isset($filesop[5]) && trim($filesop[5])!='' && in_array($filesop[5], $carsClient)) { array_push($feedback, $filesop[5]); }
					if(isset($filesop[6]) && trim($filesop[6])!='' && in_array($filesop[6], $carsClient)) { array_push($feedback, $filesop[6]); }
					if(count($feedback)>0) { $feedb=implode(',',$feedback); } else { $feedb=''; }
				
					//echo "INSERT INTO csv (name, email) VALUES ('$name','$email') <br>";
					//$sql = mysql_query("INSERT INTO csv (name, email) VALUES ('$name','$email')");
					/*$sql = "INSERT INTO leads_feedback (ref_tbl, page_id, name, email, phone, feedback_type, created) VALUES ('".$lastId."', '".$_POST['pg_id']."', '".$name."', '".$email."', '".$phone."',  '".$feedback."', now())";
					$conn->query($sql);*/
					$phoneN = substr($phone, -10);
					if($phoneN!='') {
					//echo "UPDATE leads SET feedback='".mysqli_real_escape_string($conn,$feedback)."' WHERE page_id='".$_POST['pg_id']."' AND lead like '%".$phoneN."%' <br>";
					mysqli_query($conn, "UPDATE leads SET feedback='".mysqli_real_escape_string($conn,$feedb)."' WHERE page_id='".$_POST['pg_id']."' AND lead like '%".$phoneN."%'") or die(mysqli_error());
					mysqli_query($conn, "UPDATE leads_google SET feedback='".mysqli_real_escape_string($conn,$feedb)."' WHERE phone like '%".$phoneN."%'") or die(mysqli_error());
					mysqli_query($conn, "UPDATE leads_chat SET feedback='".mysqli_real_escape_string($conn,$feedb)."' WHERE phone like '%".$phoneN."%'") or die(mysqli_error());
					}
					
					
				}
				$c = $c + 1;
			}
		} //exit();
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'ads-report-weekly.php';</script>";
		exit();
	} 
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
										$sqlD=mysqli_query($conn, "SELECT client_name, fb_acc,  g_acc, email_notif, email_ids, monthly, weekly, pg_id FROM ads_report_weekly WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData);
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev1=mysqli_query($conn, "SELECT * FROM pages WHERE uid='".$_SESSION['uid']."' order by pg_name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										
								?>
                                <form enctype="multipart/form-data" method="post"  action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">     
                                                	<label>Client Name: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client_name'])) { ?> value="<?php echo $editData['client_name']; ?>" <?php } ?>>                                                    	
                                                    <br />      
                                                            
                                                 	<label>Facebook Account: </label>
                                                    <select name="fb_acc" id="fb_acc" class="form-control">
                                                    	<option value="">Select Facebook Ad Account</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($editData['fb_acc']) && $editData['fb_acc']==$sqlROW["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                   
                                                    <br />
                                                    
                                                    <label>Facebook Page: </label>
                                                    <select name="pg_id" id="pg_id" class="form-control">
                                                    	<option value="">Select Facebook Page</option>
                                                    	<?php while($sqlROW1=mysqli_fetch_array($sqlRev1)) { ?>
                                                    	<option value="<?php echo $sqlROW1["pg_id"]; ?>" <?php if(isset($editData['pg_id']) && $editData['pg_id']==$sqlROW1["pg_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW1["pg_name"].' ('.$sqlROW1["pg_id"].')'; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                   
                                                    <br />
                                                   
                                                    <label>Google Account :  </label>
                                                    <select name="g_acc" id="g_acc" class="form-control">
                                                    	<option value="">Select Adwords Ad Account</option>
                                                    	<?php while($sqlROW2=mysqli_fetch_array($sqlRev2)) { ?>
                                                    	<option value="<?php echo $sqlROW2["account_id"]; ?>" <?php if(isset($editData['g_acc']) && $editData['g_acc']==$sqlROW2["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW2["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                  
                                                    <br />
                                                    
                                                    <label>Upload Feedback Leads : (Optional) </label>
                                                    <input type="file" class="form-control has-feedback-left" name="file"  />
                                                    <small><b>csv format:</b> name, email, phone, feedback</small>
                                                    <!--
                                                    <label>Schedule Report: </label><br />
                                                   
                                                    <label ><input type="checkbox" name="monthly"  value="1"  <?php if(isset($editData['monthly']) && $editData['monthly']==1) { ?> checked <?php } ?>> Monthly  </label>  &nbsp;&nbsp;                                                
                                                     <label ><input type="checkbox" name="weekly"  value="1" <?php if(isset($editData['weekly']) && $editData['weekly']==1) { ?> checked <?php } ?>> Weekly  </label><br />-->
                                                     <br /><br />
                                                     
                                                    <span  class="cls_em">
                                                    <label>Email Ids: </label>
                                                    <input  type="text" name="email_ids" class="form-control" <?php if(isset($editData['email_ids'])) { ?> value="<?php echo $editData['email_ids']; ?>" <?php } ?>>  </span>
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
<script>
$(document).ready(function () {
	$( ".cls_f" ).hide();  $( ".cls_g" ).hide(); 
	//alert($( "#fb_acc" ).val()); 
	if($( "#fb_acc" ).val()!='') { $( ".cls_f" ).show(); }
	if($( "#g_acc" ).val()!='') { $( ".cls_g" ).show(); }
	//alert($( "#rad_em2" ).val());
	var $radios = $('#rad_em2');
	var $radios3 = $('#rad_em3'); 
    if($radios.is(':checked') === true || $radio3s.is(':checked') === true) {
       $( ".cls_em" ).show(); 
    }
	<?php if(isset($editData['email_notif']) && ($editData['email_notif']==2 || $editData['email_notif']==1)) { ?> $( ".cls_em" ).show();  <?php } else { ?> $( ".cls_em" ).hide(); <?php } ?>
	//if($( "#fb_acc" ).val()!='') { $( "#cls_f" ).show(); }
});	

$( "#fb_acc" ).change(function() {  if($( "#fb_acc" ).val()=='') {   $( ".cls_f" ).hide();  } else {   $( ".cls_f" ).show();  } });
$( "#g_acc" ).change(function() {  if($( "#g_acc" ).val()=='') {   $( ".cls_g" ).hide();  } else {   $( ".cls_g" ).show();  } });
$('input[type=radio][name=email_notif]').change(function() {  if(this.value==0) { $( ".cls_em" ).hide(); } else {   $( ".cls_em" ).show();  } });

</script>