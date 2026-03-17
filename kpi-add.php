<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'KPI - Add Accounts';
$pgID = 8;
$err =''; 



if(isset($_POST['submit'])){
	//print_r($_POST);  exit;
	if($_POST['client']!='') {
		
		/*echo count($_POST['fb_acc']);
		echo count($_POST['ta_acc']);
		print_r($_POST);  exit;		*/
		$tot_received = $p_tot_received =0;
		
		$_POST['fb_acc'] = implode(',',$_POST['fb_acc']);
		
			
		$metrics = serialize($_POST['kpi_val']);
		
		
		if(isset($_GET['id'])) {
			echo	$cirSql = "UPDATE kpi SET client='".mysqli_real_escape_string($conn, $_POST['client'])."', spend='".mysqli_real_escape_string($conn, $_POST['spend'])."', acc_cat='".mysqli_real_escape_string($conn, $_POST['acc_type'])."', metrics='".mysqli_real_escape_string($conn, $metrics)."', updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id'];
		} else {
			echo  $cirSql = "INSERT INTO kpi (uid, client, spend, acc_cat, metrics, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['spend'])."', '".mysqli_real_escape_string($conn, $_POST['acc_type'])."', '".mysqli_real_escape_string($conn, $metrics)."', now(), now());"; 
			  mysqli_query($conn, $cirSql) or die(mysqli_error());
			  $lastId = mysqli_insert_id($conn);
		}
		
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'kpi.php';</script>";
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
.selCls { width:100% !important; }
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
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT * FROM kpi WHERE tbl_id=".$_GET['id']."");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										$sqlROWs = $sqlROWs2 = $sqlROWs3 = $sqlROWs4 = array();
										
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs[] = $sqlROW; }
										
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $sqlROWs2[] = $sqlROW2; }
										
										$sqlRev3=mysqli_query($conn, "SELECT * FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $sqlROWs3[] = $sqlROW3; }
										
										$sqlRev4=mysqli_query($conn, "SELECT * FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW4=mysqli_fetch_array($sqlRev4)) { $sqlROWs4[] = $sqlROW4; }
										
										//d($editData);
										$acc_type = array(1=>'Real Estate', 2=>'Coaching', 3=>'Education', 4=>'Ecommerce', 5=>'Others');

                                        $label = array(
                                            1 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'SV',	'CSV', 'Sales'),
                                            2 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Attendees', 'Sale', 'CPS','ROAS'),
                                            3 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'Applications',	'Admission'),
                                            4 => array('Client', 'Spend', 'Budget', 'Sale', 'CPS', 'ATC',	'CATC',	'Purchase Value','ROAS'),
                                            5 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'SV',	'CSV', 'Sales')
                                        );
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                               
                                               
       
       

                                                <div class="col-md-6">     
                                                	<label>Client Name: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client'])) { ?> value="<?php echo $editData['client']; ?>" <?php } ?>>                                                    	
                                                    <br /> 

													
                                                    
                                                    <div class="form-group">
                                                    <label>Account Category: </label>
                                                        <select name="acc_type" id="acc_type" class="form-control selCls">
                                                            <option value="">Account Category</option>
                                                            <?php foreach($acc_type as $k => $v){  ?>
                                                            <option value="<?php echo $k; ?>" <?php if(isset($editData["acc_cat"]) && $k==$editData["acc_cat"]) { echo "selected='selected'"; } ?> ><?php echo $v; ?></option>
                                                        
                                                            <?php } ?>
                                                        </select>
                                                    </div>     
                                                    <label>Spend: </label>
                                                    <input type="text" name="spend" class="form-control" <?php if(isset($editData['spend'])) { ?> value="<?php echo $editData['spend']; ?>" <?php } ?>>                                                    	
                                                    <br />                                                          	  
                                                    <span  class="cls_em">
                                                    	
                                                        <div data-role="dynamic-fields">
                                                      
															<div class="form-inline1">
                                                                
                                                                 <div class="cat_all form-group cat_re cat_coach cat_edu cat_ecom">
																 <label>Budget </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Budget" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                 
                                                                 
                                                                 <div class="cat_all form-group cat_re cat_coach cat_edu">
																 <label>Lead </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Lead" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                 
                                                                 
																 <div class="cat_all form-group cat_re cat_coach cat_edu">
																 <label>CPL </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="CPL" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                 
                                                                 
                                                                 <div class="cat_all form-group cat_re cat_edu">
																 <label>Qualified Leads </label>
																 		   <input class="form-control" name="kpi_val[]" placeholder="Qualified Leads" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div>  
                                                                 
                                                                 
                                                                 <div class="cat_all form-group cat_re cat_edu">
																 <label>Qualified Leads in % </label>
																 		   <input class="form-control" name="kpi_val[]" placeholder="Qualified Leads in %" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div>   
                                                                 
                                                                 
                                                                 <div class="cat_all form-group cat_re">
																 <label>Site visit </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Site visit" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                 <div class="cat_all form-group cat_re">
                                                                 <label>Cost per SV </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Cost per SV" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                 
                                                                 <div class="cat_all form-group cat_re">
																 <label>Sales </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Sales" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                 <!-- Coaching -->
																 <div class="cat_all form-group cat_coach">
																 <label>Attendees </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Attendees" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div>
																 <div class="cat_all form-group cat_coach cat_ecom">
																 <label>Sales </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Sales" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
																 <div class="cat_all form-group cat_coach cat_ecom">
																 <label>Cost / Sales </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Cost / Sales" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
																 
																 <!-- Education -->
																 <div class="cat_all form-group cat_edu">
																 <label>Applications </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Applications" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div>
																 <div class="cat_all form-group cat_edu">
																 <label>Admission </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Admission" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 

																 <!-- Ecom -->
																 <div class="cat_all form-group cat_ecom">
																 <label>ATC </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="ATC" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div>
																 <div class="cat_all form-group cat_ecom">
																 <label>CATC </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="CATC" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
																 <div class="cat_all form-group cat_ecom">
																 <label>Purchase Value </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="Purchase Value" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div>


																 <div class="cat_all form-group cat_coach cat_ecom">
																 <label>ROAS </label>
																		   <input class="form-control" name="kpi_val[]" placeholder="ROAS" type="text" <?php if(isset($aFB_received[$fb_key])) { ?> value="<?php echo $aFB_received[$fb_key]; ?>" <?php } ?> />
																 </div> 
                                                                
															 </div>
															
                                                        </div>
                                                    </span>
                                                    <br />
                                                    
                                                    
                                                    </div>
                                                    <div class="clearfix"></div><div class="clearfix"></div><br />
                                                    <a href="loading.php?pg=kpi.php"  class="btn btn-default">   Back</a>  
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
<!-- Include Date Range Picker -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>

<script>

	$('.cat_all').hide();
	
	$('select').change(function () {
		var optionSelected = $(this).find("option:selected");
		var valueSelected  = optionSelected.val();
		var textSelected   = optionSelected.text();
		$('.cat_all').hide();
		$(".cat_all input").prop('disabled', true); 

		if(valueSelected==1 || valueSelected==5){ $('.cat_re').show(); $(".cat_re input").prop('disabled', false);  }
		else if(valueSelected==2){$('.cat_coach').show(); $(".cat_coach input").prop('disabled', false); }
		else if(valueSelected==3){$('.cat_edu').show(); $(".cat_edu input").prop('disabled', false); }
		else if(valueSelected==4){$('.cat_ecom').show(); $(".cat_ecom input").prop('disabled', false); }
 	});
	<?php
		if(isset($_GET['id'])) {
			
			?>
			$('#acc_type').val(<?php echo $editData["acc_cat"]; ?>).change();
			<?php
			$metrics = unserialize($editData['metrics']);
			$j=0;
			foreach($metrics as $k => $v){ 
				?>
				$('input[type="text"][name*="kpi_val"]').eq(<?php echo $j; ?>).val("<?php echo $v; ?>");
				<?php
				$j++;
			}
		}
	?>
	//const attachArray = [1,2,3,4,5,6,7,8];
	//$('[name="kpi_val"]').val( JSON.stringify(attachArray) );
	
	

	$(document).ready(function(){
		var date_input=$('.date'); //our date input has the name "date"
		var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
		date_input.datepicker({
			format: 'mm/dd/yyyy',
			container: container,
			todayHighlight: true,
			autoclose: true,
		})
	})
</script>

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
			
			var date_input=$('.date'); //our date input has the name "date"
			var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
			date_input.datepicker({
				format: 'mm/dd/yyyy',
				container: container,
				todayHighlight: true,
				autoclose: true,
			})
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