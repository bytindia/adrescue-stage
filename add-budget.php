<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Add Account - Budget & Reminder';
$pgID = 8;
$err =''; 

if(isset($_POST['submit'])){
	//print_r($_POST); exit;
	if($_POST['fb_acc']!='' || $_POST['fb_acc']!='') {
		
		if(!isset($_POST['fb_bud'])) { $_POST['fb_bud']=''; }
		if(!isset($_POST['fb_every_month'])) { $_POST['fb_every_month']=''; }
		if(!isset($_POST['g_bud'])) { $_POST['g_bud']=''; }
		if(!isset($_POST['g_every_month'])) { $_POST['g_every_month']=''; }
		if(!isset($_POST['email_ids'])) { $_POST['email_ids']=''; }
		if(!isset($_POST['both_budget'])) { $_POST['both_budget']=''; }
		
		if(isset($_GET['id'])) {
			$cirSql = "UPDATE budget SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', fb_acc='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', fb_bud='".mysqli_real_escape_string($conn, $_POST['fb_bud'])."', fb_every_month='".mysqli_real_escape_string($conn, $_POST['fb_every_month'])."', g_acc='".mysqli_real_escape_string($conn, $_POST['g_acc'])."', g_bud='".mysqli_real_escape_string($conn, $_POST['g_bud'])."', g_every_month='".mysqli_real_escape_string($conn, $_POST['g_every_month'])."', both_budget='".mysqli_real_escape_string($conn, $_POST['both_budget'])."', email_notif='".mysqli_real_escape_string($conn, $_POST['email_notif'])."', email_ids='".mysqli_real_escape_string($conn, $_POST['email_ids'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO budget (uid, fb_id, client_name, fb_acc, fb_bud, fb_every_month, g_acc, g_bud, g_every_month, both_budget, email_notif, email_ids, created, updated) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['fb_bud'])."', '".mysqli_real_escape_string($conn, $_POST['fb_every_month'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['g_bud'])."', '".mysqli_real_escape_string($conn, $_POST['g_every_month'])."', '".mysqli_real_escape_string($conn, $_POST['both_budget'])."', '".mysqli_real_escape_string($conn, $_POST['email_notif'])."', '".mysqli_real_escape_string($conn, $_POST['email_ids'])."', now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'add-budget.php?id=".$lastId."';</script>";
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
										$sqlD=mysqli_query($conn, "SELECT client_name, fb_acc, fb_bud, fb_every_month, g_acc, g_bud, g_every_month, email_notif, email_ids, both_budget FROM budget WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData);
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										
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
                                                    
                                                    <label class="cls_f">Total Monthly Budget Allocate(Facebook): </label>
                                                    <input type="text" name="fb_bud" class="form-control cls_f" <?php if(isset($editData['fb_bud'])) { ?> value="<?php echo $editData['fb_bud']; ?>" <?php } ?>>  
                                                    <label class="cls_f"><input type="checkbox" name="fb_every_month" <?php if(isset($editData['fb_every_month']) && $editData['fb_every_month']=='on') { ?> checked <?php } ?>> Same Budget for every Month</label> &nbsp;&nbsp;
                                                    <label class="cls_f"><input type="checkbox" name="both_budget" <?php if(isset($editData['both_budget']) && $editData['both_budget']=='on') { ?> checked <?php } ?>> Including Google Budget</label>
                                                    <br /><br />
                                                   
                                                    <label>Google Account :  </label>
                                                    <select name="g_acc" id="g_acc" class="form-control">
                                                    	<option value="">Select Adwords Ad Account</option>
                                                    	<?php while($sqlROW2=mysqli_fetch_array($sqlRev2)) { ?>
                                                    	<option value="<?php echo $sqlROW2["account_id"]; ?>" <?php if(isset($editData['g_acc']) && $editData['g_acc']==$sqlROW2["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW2["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <label class="cls_g">Total Monthly Budget Allocate(Adwords): </label>
                                                    <input type="text" name="g_bud" class="form-control cls_g" <?php if(isset($editData['g_bud'])) { ?> value="<?php echo $editData['g_bud']; ?>" <?php } ?>>  
                                                    <label class="cls_g"><input type="checkbox" name="g_every_month" <?php if(isset($editData['g_every_month']) && $editData['g_every_month']=='on') { ?> checked <?php } ?>> Same Budget for every Month</label>
                                                    <br /><br />
                                                    
                                                    <label>Email Notification: </label><br />
                                                    <label ><input type="radio" name="email_notif" value="0" id="rad_em1" <?php if(isset($editData['email_notif']) && $editData['email_notif']==0) { ?> checked <?php } ?>> None </label>  &nbsp;&nbsp; 
                                                    <label ><input type="radio" name="email_notif"  value="1"  id="rad_em2" <?php if(isset($editData['email_notif']) && $editData['email_notif']==1) { ?> checked <?php } ?>> Daily Once </label>  &nbsp;&nbsp;                                                
                                                     <label ><input type="radio" name="email_notif"  value="2"  id="rad_em3" <?php if(isset($editData['email_notif']) && $editData['email_notif']==2) { ?> checked <?php } ?>> Weekly Once </label><br />
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