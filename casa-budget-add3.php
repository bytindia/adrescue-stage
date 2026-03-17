<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'CasaGrand - Budget';
$pgID = 8;
$err =''; 

$_GET['id'] = 1;
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
if(isset($_POST['submit'])){
	//d($_POST); exit;
	if($_POST['client']!='') {
		
		/*echo count($_POST['fb_acc']);
		echo count($_POST['ta_acc']);
		print_r($_POST);  exit;		*/
		$tot_received = $p_tot_received =0;
		
		
		
			
		$dates = serialize(array_filter($_POST['date']));
        $dates2 = serialize(array_filter($_POST['date2']));
		$budget = serialize(array_filter($_POST['budget']));
		$leads = serialize(array_filter($_POST['leads']));
		$proj_list = serialize(array_filter($_POST['proj_list']));
        $p_ty = serialize(array_filter($_POST['p_ty']));
		
		//echo count(array_filter($_POST['amount'])); exit;
		//d($_POST); d($p_ty);  exit;
		
		if(isset($_GET['id'])) {
				$cirSql = "UPDATE budget_casa3 SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', budget='".mysqli_real_escape_string($conn, $budget)."', date='".mysqli_real_escape_string($conn, $dates)."', date2='".mysqli_real_escape_string($conn, $dates2)."', leads='".mysqli_real_escape_string($conn, $leads)."', proj_list='".mysqli_real_escape_string($conn, $proj_list)."', p_ty='".mysqli_real_escape_string($conn, $p_ty)."', created=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				//$lastId = $_GET['id']; exit;
		} else {
			 /* $cirSql = "INSERT INTO budget_casa3 (uid, client_name, email, daily_budget, fb_id, fb_received, fb_stDt, fb_enDt, g_id, g_stDt,  g_enDt, in_id, in_stDt, in_enDt, ta_id, ta_stDt, ta_enDt, tot_paid, tot_penalty, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['email'])."', '".mysqli_real_escape_string($conn, $_POST['daily_budget'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['fb_received'])."', '".mysqli_real_escape_string($conn, $_POST['date_fb'])."', '".mysqli_real_escape_string($conn, $_POST['date_fb2'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['date_g'])."', '".mysqli_real_escape_string($conn, $_POST['date_g2'])."', '".mysqli_real_escape_string($conn, $_POST['in_acc'])."', '".mysqli_real_escape_string($conn, $_POST['date_in'])."', '".mysqli_real_escape_string($conn, $_POST['date_in2'])."', '".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', '".mysqli_real_escape_string($conn, $_POST['date_ta'])."', '".mysqli_real_escape_string($conn, $_POST['date_ta2'])."', ".$tot_received.", ".$p_tot_received.", now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn); */
		}
		
		
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'casa-budget-add3.php';</script>";
		exit();
	} 
}
$ad_acc = array(
	427933624679014=>'Athens',
	1744658879060018=>'Boulevard',
	1196015513904250=>'Southbrooke',
	569001103516290=>'Aria',
	462105354650207=>'Jubliant',
	370327086813811=>'Firstcity',
	807092046796106=>'Flagship',
	735957617015640=>'PlatinumJoy',
	834297526950773=>'Tudor',
	1317725875075405=>'Zenith',
	488023135037290=>'Divinity'

);
$filename = "casa3/proj_list.txt";
  $handle = fopen($filename, "r");
  $proj_list_txt = fread($handle, filesize($filename));
  fclose($handle);
  $pr_list = explode(",",$proj_list_txt ?? '');
  $proj_list_s = array();
  foreach($pr_list as $k => $v) {
      $proj_list_s[] = trim($v);
  }
  $proj_list_s = array_unique(array_filter($proj_list_s));
  //$_SESSION['proj_list'] = $proj_list_s;
  //d($proj_list_s); exit;
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
									//$editData['email_notif'] =0;
									//if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT budget, client_name, date, leads, proj_list, p_ty, date2 from budget_casa3 where tbl_id=1");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									//}
										
										
									//	d($editData);
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                               
                                               
       
       

                                                <div class="col-md-12">     
                                                	<label>Client Name: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client_name'])) { ?> value="<?php echo $editData['client_name']; ?>" <?php } ?>>                                                    	
                                                    <br /> 
                                                    
                                                    
                                                    <span  class="cls_em">
                                                    <label>Budget </label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        $proj_type = array(1=>'RI (FB)', 2=>'NRI (FB)', 3=>'RI (G)', 4=>'NRI (G)');
                                                        //print_r($editData);
														$k=1;
														if(isset($editData['budget'])) 
														{
															$budget = unserialize($editData['budget']);
                                                            $p_ty = unserialize($editData['p_ty']);
															$date = unserialize($editData['date']);
                                                            $date2 = unserialize($editData['date2']);
															$leads = unserialize($editData['leads']);
															$proj_list = unserialize($editData['proj_list']);
															$budget_count = count($budget);
														} else {
															$date = $budget = $leads = $proj_list = array(''); 
															$budget_count = 1; 
														}
														if(count($budget)>0) {
														foreach($budget as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$budget_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
																		<div class="form-group">
																		<select name="proj_list[]" id="proj_list[]" class="form-control" required>
																			<option value="">Select Ad Account</option>
																			<?php foreach($proj_list_s as $k_ac => $v_ac) { 
																				$k_ac = str_replace(' ', '_', strtolower(trim($v_ac))); ?>
																			<option value="<?php echo $k_ac; ?>" <?php if(isset($proj_list[$key]) && $proj_list[$key]==$k_ac) { echo "selected='selected'"; } ?>><?php echo $v_ac; ?></option>
																			<?php } ?>
																		</select>
																		</div>
                                                                        <div class="form-group">
																		<select name="p_ty[]" id="p_ty[]" class="form-control" required>
																			<option value="">Project Type</option>
																			<?php foreach($proj_type as $k_ty => $v_ty) { 
																				$k_ac = str_replace(' ', '_', strtolower(trim($v_ty)));
																				?>
																			<option value="<?php echo $k_ty; ?>" <?php if(isset($p_ty[$key]) && $p_ty[$key]==$k_ty) { echo "selected='selected'"; } ?>><?php echo $v_ty; ?></option>
																			<?php } ?>
																		</select>
																		</div>
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Amount</label>
                                                                            <input type="text" name="budget[]"  class="form-control" id="field-value" placeholder="Amount" size="20" value="<?php echo $budget[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-user"></i>
                                                                        <input class="form-control note" class="note" id="leads" name="leads[]" placeholder="Leads" type="text" value="<?php echo $leads[$key]; ?>"> 
                                                                        </div>
                                                                        
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="date[]" placeholder="MM/DD/YYYY" type="text" value="<?php echo $date[$key]; ?>"> 
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date2" class="date2" id="date2" name="date2[]" placeholder="MM/DD/YYYY" type="text" value="<?php echo $date2[$key]; ?>"> 
                                                                        </div>
                                                                        
                                                                        <?php if($k==$budget_count) { ?>
                                                                        <button class="btn btn-primary" data-role="add">
                                                                        	<span class="glyphicon glyphicon-plus"></span>
                                                                        </button>
                                                                        <?php } ?>
                                                                        <button class="btn btn-danger" data-role="remove">
                                                                        	<span class="glyphicon glyphicon-remove"></span>
                                                                        </button>
                                                                                                                                            
                                                                    </div>  <!-- /div.form-inline -->
                                                                </div>  <!-- /div[data-role="dynamic-fields"] -->
                                                            </div>  <!-- /div.col-md-12 -->
                                                        </div>  <!-- /div.row -->
                                                        <?php 
															$k++;
														} 
														} else {
															?>
                                                        <div class="row ">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
																	<div class="form-group">
																		<select name="proj_list[]" id="proj_list[]" class="form-control" required>
																			<option value="">Select Ad Account</option>
																			<?php foreach($proj_list_s as $k_ac => $v_ac) { ?>
																			<option value="<?php echo $k_ac; ?>"><?php echo $v_ac; ?></option>
																			<?php } ?>
																		</select>
																		</div>
                                                                        <div class="form-group">
																		<select name="p_ty[]" id="p_ty[]" class="form-control" required>
																			<option value="">Project Type</option>
																			<?php foreach($proj_type as $k_ty => $v_ty) { ?>
                                                                                <option value="<?php echo $k_ty; ?>"><?php echo $v_ty; ?></option>
																			<?php } ?>
																		</select>
																		</div>
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Amount</label>
                                                                            <input type="text" name="budget[]"  class="form-control" id="field-value" placeholder="Amount" size="20" >
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-user"></i>
                                                                        <input class="form-control note" class="note" id="leads" name="leads[]" placeholder="Leads" type="text" > 
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="date[]" placeholder="MM/DD/YYYY" type="text" > 
                                                                        </div>    
                                                                                                                                            
                                                                        <button class="btn btn-primary" data-role="add">
                                                                        	<span class="glyphicon glyphicon-plus"></span>
                                                                        </button>
                                                                        <button class="btn btn-danger" data-role="remove">
                                                                        	<span class="glyphicon glyphicon-remove"></span>
                                                                        </button>
                                                                                                                                            
                                                                    </div>  <!-- /div.form-inline -->
                                                                </div>  <!-- /div[data-role="dynamic-fields"] -->
                                                            </div>  <!-- /div.col-md-12 -->
                                                        </div>  <!-- /div.row -->
                                                        <?php 
														}
														?>
                                                        
                                                        <br>
                                                    
                                                    </div>
                                                    <br />
                                                    <a href="loading.php?pg=casa-budget-add3.php"  class="btn btn-default">   Back</a>  
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
	$(document).ready(function(){
		var date_input=$('.date'); //our date input has the name "date"
		var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
		date_input.datepicker({
			format: 'mm/dd/yyyy',
			container: container,
			todayHighlight: true,
			autoclose: true,
		});
        var date_input2=$('.date2'); //our date input has the name "date"
		var container2=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
		date_input2.datepicker({
			format: 'mm/dd/yyyy',
			container: container2,
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
			});
            var date_input2=$('.date2'); //our date input has the name "date"
			var container2=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
			date_input2.datepicker({
				format: 'mm/dd/yyyy',
				container: container2,
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