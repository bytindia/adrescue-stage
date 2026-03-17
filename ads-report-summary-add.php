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
			$cirSql = "UPDATE ads_report_summary SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', fb_acc='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', ta_acc='".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', g_acc='".mysqli_real_escape_string($conn, $_POST['g_acc'])."', weekly='".mysqli_real_escape_string($conn, $_POST['weekly'])."', monthly='".mysqli_real_escape_string($conn, $_POST['monthly'])."', email_ids='".mysqli_real_escape_string($conn, $_POST['email_ids'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO ads_report_summary (uid, fb_id, client_name, fb_acc, g_acc, ta_acc, weekly, monthly, email_ids, created, updated) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', '".mysqli_real_escape_string($conn, $_POST['weekly'])."', '".mysqli_real_escape_string($conn, $_POST['monthly'])."', '".mysqli_real_escape_string($conn, $_POST['email_ids'])."', now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'ads-report-summary.php';</script>";
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
										$sqlD=mysqli_query($conn, "SELECT client_name, fb_acc,  g_acc, ta_acc, email_notif, email_ids, monthly, weekly FROM ads_report_summary WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData);
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev3=mysqli_query($conn, "SELECT * FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");
										
								?>
                                <form method="post" action="">
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
                                                    	<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($editData['fb_acc']) && $editData['fb_acc']==$sqlROW["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                   
                                                    <br /><br />
                                                   
                                                    <label>Google Account :  </label>
                                                    <select name="g_acc" id="g_acc" class="form-control">
                                                    	<option value="">Select Adwords Ad Account</option>
                                                    	<?php while($sqlROW2=mysqli_fetch_array($sqlRev2)) { ?>
                                                    	<option value="<?php echo $sqlROW2["account_id"]; ?>" <?php if(isset($editData['g_acc']) && $editData['g_acc']==$sqlROW2["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW2["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                  
                                                    <br /><br />
                                                    
                                                    <label>Taboola Account :  </label>
                                                    <select name="ta_acc" id="ta_acc" class="form-control">
                                                    	<option value="">Select Taboola Ad Account</option>
                                                    	<?php while($sqlROW3=mysqli_fetch_array($sqlRev3)) { ?>
                                                    	<option value="<?php echo $sqlROW3["account_id"]; ?>" <?php if(isset($editData['ta_acc']) && $editData['ta_acc']==$sqlROW3["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW3["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                  
                                                    <br /><br />
                                                    
                                                    <label>Schedule Report: </label><br />
                                                   
                                                    <label ><input type="checkbox" name="monthly"  value="1"  <?php if(isset($editData['monthly']) && $editData['monthly']==1) { ?> checked <?php } ?>> Monthly  </label>  &nbsp;&nbsp;                                                
                                                     <label ><input type="checkbox" name="weekly"  value="1" <?php if(isset($editData['weekly']) && $editData['weekly']==1) { ?> checked <?php } ?>> Weekly  </label><br />
                                                     <br />
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