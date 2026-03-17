<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

auditAuth();
$pgHeadline = 'BYT Ads Rescue';
$pgID = 8;
$err =''; 


if(isset($_POST['submit'])){
	//print_r($_POST); exit;
	
	$rand_str = rand(); 
	$rand_id = hash("sha256", $rand_str); 

	if($_POST['acc_id']!='' && $_POST['page_id']!='') {
		
		$query = "SELECT tbl_id, name, fb_id, fb_token FROM audit_users WHERE tbl_id='".$_SESSION['uid']."'";  
		
		$getID = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name from audit_adAccounts where id='act_".$_POST['acc_id']."' limit 0,1"));
		$accName = $getID['name'];
		
		$getID2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pg_name, pg_token from audit_pages where pg_id='".$_POST['page_id']."' limit 0,1"));
		$pgName = $getID2['pg_name'];
		
		$getID3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fb_token from audit_users where tbl_id='".$_SESSION['uid']."' limit 0,1"));
		$access_token = $getID3['fb_token'];
		
		$pg_cont = curl_get_file_contents('https://graph.facebook.com/'.$api_ver.'/'.$_POST['page_id'].'/picture?redirect=0&type=large');
		$pg_photo = json_decode($pg_cont); 
		$_POST['rep_obj'] = 3;
		//exit;
		if(isset($_GET['id'])) {
			$cirSql = "UPDATE audit_reports SET acc_id='".mysqli_real_escape_string($conn, $_POST['acc_id'])."', acc_name='".mysqli_real_escape_string($conn, $accName)."', page_id='".mysqli_real_escape_string($conn, $_POST['page_id'])."', pg_name='".mysqli_real_escape_string($conn, $pgName)."',  pg_img='".mysqli_real_escape_string($conn, $pg_photo->data->url)."', acc_token='".mysqli_real_escape_string($conn, $access_token)."', page_token='".mysqli_real_escape_string($conn, $getID2['pg_token'])."', email_ids='".mysqli_real_escape_string($conn, $_POST['email_ids'])."', rep_obj='".mysqli_real_escape_string($conn, $_POST['rep_obj'])."', rand_id='".mysqli_real_escape_string($conn, $rand_id)."' WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO audit_reports (uId, fb_id, acc_id, page_id, acc_name, pg_name, acc_token, page_token, pg_img, email_ids, rep_obj, rand_id, created) VALUES ('".$_SESSION['uid']."', '".$_SESSION['fb_id']."', '".mysqli_real_escape_string($conn, $_POST['acc_id'])."', '".mysqli_real_escape_string($conn, $_POST['page_id'])."', '".mysqli_real_escape_string($conn, $accName)."', '".mysqli_real_escape_string($conn, $pgName)."', '".mysqli_real_escape_string($conn, $access_token)."', '".mysqli_real_escape_string($conn, $getID2['pg_token'])."', '".mysqli_real_escape_string($conn, $pg_photo->data->url)."', '".mysqli_real_escape_string($conn, $_POST['email_ids'])."', '".mysqli_real_escape_string($conn, $_POST['rep_obj'])."', '".mysqli_real_escape_string($conn, $rand_id)."', now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'audit-report.php?tbl_id=".$lastId."&refresh=1';</script>";
		exit();
	} 
}
?>
<style>
.nav-sm .main_container .top_nav, .nav-sm .container.body .right_col, .nav-sm footer { margin-left:0px; }
#menu_toggle { display:none; }
.form-control {
    border-radius: 0;
    width: 100%;
    height: 50px;
    font-size: 20px;
}
.btn-primary { background:#2283f3; }
.radio_btn {     font-size: 25px;
    /* line-height: 20px; */
    height: 50px;
}
.radio_btn input { height:40px; width:40px; }  
span.radio_btn label {
    vertical-align: middle;
    margin-top: -30px; font-weight:normal;
}
.radio_btn span {     vertical-align: text-top;; font-weight:normal; }
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title1 text-center">
                    <!--<img src="images/ads-rescue.png" alt="..." height="250">
                    <h2><?php echo $pgHeadline; ?></h2>             
                    <div class="clearfix"></div>
                    <hr>-->   
                  </div>
                  
                  <div class="x_content">
                  		<?php 
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT audit_name, acc_id, page_id, email_ids FROM audit_reports WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData);
										$sqlRev=mysqli_query($conn, "SELECT * FROM audit_adAccounts WHERE uid='".$_SESSION['uid']."' and status='0' order by name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM audit_pages WHERE uid='".$_SESSION['uid']."' and admin_delete='0' order by pg_name asc");
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">     
                                                	<!--<label>Report Name: </label>
                                                    <input type="text" name="audit_name" class="form-control" <?php if(isset($editData['audit_name'])) { ?> value="<?php echo $editData['audit_name']; ?>" <?php } ?>>                                                    	
                                                    <br />      
                                                       
                                                 	<label>Facebook Account: </label>-->     
                                                    <select name="page_id" id="page_id" class="form-control" required>
                                                    	<option value="">Select Facebook Page</option>
                                                    	<?php while($sqlROW2=mysqli_fetch_array($sqlRev2)) { ?>
                                                    	<option value="<?php echo $sqlROW2["pg_id"]; ?>" <?php if(isset($editData['page_id']) && $editData['page_id']==$sqlROW2["pg_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW2["pg_name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                   <br />  
                                                    
                                                    <select name="acc_id" id="acc_id" class="form-control" required>
                                                    	<option value="">Select Facebook Ad Account</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($editData['acc_id']) && $editData['acc_id']==$sqlROW["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                  
                                                    
                                                    <br>
                                                    <input  type="text" placeholder="Email" name="email_ids" class="form-control" <?php if(isset($editData['email_ids'])) { ?> value="<?php echo $editData['email_ids']; ?>" <?php } ?>>  </span>
                                                    <!--<br />
                                                    <span class="radio_btn">
                                                    <span>I Run :</span> <span><input type="radio" name="rep_obj" value="1"> <label> Lead Generation</label> </span>
                                                    <span><input type="radio" name="rep_obj" value="2"> <label>  Conversion</label></span>
                                                    <span><input type="radio" name="rep_obj" value="3" checked> <label> Both</label></span>
                                                    </span>
                                                    -->
                                                    <br><br>	
                                                    <input type="submit" name="submit" value="Audit My Ads!" class="btn btn-primary btn-lg" style="width: 200px; height: 60px; font-size: 25px;">
                                                 <a href="loading.php?pg=index.php" class="btn btn-default btn-lg" style="width: 120px; height: 60px; font-size: 25px;"> Cancel</a> 
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