<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'AdTracker - Add Accounts';
$pgID = 8;
$err =''; 



if(isset($_POST['submit'])){
	
	if($_POST['acc_id']!='') {
		
		
		//print_r($_POST);  exit;
		
		$query = "SELECT id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' && id='".$_POST['acc_id']."'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_assoc($result);
		$acc_id = $row['id']; 
		$acc_name = $row['name']; 
		
		if(isset($_GET['id'])) {
				$cirSql = "UPDATE troubleshoot SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', acc_name='".mysqli_real_escape_string($conn, $acc_name)."', acc_id='".mysqli_real_escape_string($conn, $_POST['acc_id'])."', target_ctr='".mysqli_real_escape_string($conn, $_POST['target_ctr'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id'];
		} else {
				  $cirSql = "INSERT INTO troubleshoot (uid, client_name, acc_name, acc_id, target_ctr, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $acc_name)."', '".mysqli_real_escape_string($conn, $_POST['acc_id'])."', '".mysqli_real_escape_string($conn, $_POST['target_ctr'])."', now(), now());"; 
				 mysqli_query($conn, $cirSql) or die(mysqli_error());
				 $lastId = mysqli_insert_id($conn);
		}
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'troubleshoot-list.php';</script>";
		exit();
	} 
}
?>
<style>


[data-role="dynamic-fields"] > .form-inline + .form-inline {
    margin-top: 0.5em;
}

[data-role="dynamic-fields"] > .form-inline [data-role="add"] {
    display: none;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="add"] {
    display: inline-block;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"] {
    display: none;
}

.not-first [data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"] {
    display: inline-block;
}
</style>
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
                      <li>
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=troubleshoot-view.php"  class="btn btn-success btn-sm">View AdTracker Accounts</a>                            
                      	</div> 
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=troubleshoot-list.php"  class="btn btn-success btn-sm">View Accounts</a>                            
                      	</div> 
                          
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
                   // echo "SELECT client_name, acc_name, acc_id, email_ids, words_like, emails_to, webhook_url FROM troubleshoot WHERE tbl_id='".$_GET['id']."'"; 
										 $sqlD=mysqli_query($conn, "SELECT client_name, acc_name, acc_id, target_ctr FROM troubleshoot WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
                  d($editData);
										//print_r($editData['words_like']);
										//$words_like = unserialize($editData['words_like']);
										//print_r($words_like);
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										//$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										
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
                                                            
                                                 	<label>FB Page: </label>
                                                    <select name="acc_id" id="fb_acc" class="form-control">
                                                    	<option value="">Select Facebook Ad Account</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["id"]; ?>" <?php if(isset($editData['acc_id']) && $editData['acc_id']==$sqlROW["id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                   
                                                    <br />
                                                    <label>Target CTR: </label>
                                                    <input type="text" name="target_ctr" class="form-control" <?php if(isset($editData['target_ctr'])) { ?> value="<?php echo $editData['target_ctr']; ?>" <?php } ?>>                                                    	
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
$(function() {
	//$('[data-role="dynamic-fields"] > .form-inline [data-role="add"]').click();
    // Remove button click
    $(document).on('click', '[data-role="dynamic-fields"] > .form-inline [data-role="remove"]', function(e) {
            e.preventDefault();
            $(this).closest('.form-inline').remove();
        }
    );
    // Add button click
    $(document).on('click',  '[data-role="dynamic-fields"] > .form-inline [data-role="add"]', function(e) {
            e.preventDefault();
            var container = $(this).closest('[data-role="dynamic-fields"]');
            new_field_group = container.children().filter('.form-inline:first-child').clone();
            new_field_group.find('input').each(function(){
                $(this).val('');
            });
            container.append(new_field_group);
        }
    );
});


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