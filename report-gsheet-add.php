<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Ads Report - Automation Setup';
$pgID = 8;
$err =''; 


if(isset($_POST['submit'])){
	
	if($_POST['client']!='') {
	
		/*echo count($_POST['fb_acc']);
		echo count($_POST['ta_acc']);
		print_r($_POST);  exit;		*/
		$tot_received = $p_tot_received =0;
		
		$_POST['fb_acc'] = implode(',',$_POST['fb_acc']);
		$_POST['g_acc'] = implode(',',$_POST['g_acc']);
		
		
			
		$month = serialize(array_filter($_POST['month']));
		$projects = serialize(array_filter($_POST['projects']));
		$budget = serialize(array_filter($_POST['budget']));
		$budget2 = serialize(array_filter($_POST['budget2']));
		
		
		if(isset($_GET['id'])) {
				$cirSql = "UPDATE report_gsheet SET client='".mysqli_real_escape_string($conn, $_POST['client'])."',  fb_acc='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."',  g_acc='".mysqli_real_escape_string($conn, $_POST['g_acc'])."', projects='".mysqli_real_escape_string($conn, $projects)."', budget='".mysqli_real_escape_string($conn, $budget)."', budget2='".mysqli_real_escape_string($conn, $budget2)."', month='".mysqli_real_escape_string($conn, $month)."', updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id'];
		} else {
			  echo $cirSql = "INSERT INTO report_gsheet (uid, client, fb_acc, g_acc, projects, budget, budget2, month, del, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $projects)."', '".mysqli_real_escape_string($conn, $budget)."', '".mysqli_real_escape_string($conn, $budget2)."', '".mysqli_real_escape_string($conn, $month)."', '0', now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		
		d($_POST); exit;	
		//}
		//exit();
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'report-gsheet-add.php';</script>";
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
									$editData = $editData['fb_acc'] = $editData['g_acc'] = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
                                        //echo "SELECT c.client, c.projects, c.budget, c.fb_acc, c.g_acc, c.month, c.del FROM report_gsheet as c WHERE c.tbl_id='".$_GET['id']."' AND c.del=0"; 
										$sqlD=mysqli_query($conn, "SELECT c.client, c.projects, c.budget, c.budget2, c.fb_acc, c.g_acc, c.month, c.del FROM report_gsheet as c WHERE c.tbl_id='".$_GET['id']."' AND c.del=0");
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
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                               
       
       

                                                <div class="col-md-8">     
                                                	<label>Client Name: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client'])) { ?> value="<?php echo $editData['client']; ?>" <?php } ?>>                                                    	
                                                    <br /> 
                                                    
                                                  
                                                                                                             	  
                                                    <span  class="cls_em">
                                                    	<label>Facebook </label>
                                                        <div data-role="dynamic-fields">
                                                        <?php 
														//if(isset($editData['fb_id']) && $editData['fb_id']!='') 
														//{
															$aFB_id = explode(',',$editData['fb_acc']);
															

															if(count($aFB_id)>0) {
																$f=1;
																$f_count = count($aFB_id);
																foreach($aFB_id as $fb_key => $fb_val) {
															?>
															<div class="form-inline">
																<div class="form-group col-md-6">
																		<select name="fb_acc[]" id="fb_acc" class="form-control selCls">
																			   <option value="">Select Facebook Account</option>
																				<?php foreach($sqlROWs as $sqlROW){  ?>
																				<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($fb_val) && $fb_val==$sqlROW["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW["name"]; ?></option>
																				 <?php } ?>
																		  </select>
																 </div>
																     
                                                                 <?php if($f==$f_count) { ?>                                                         
																 <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                 <?php } ?>
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>
															 </div>
															 <?php  
															 		$f++;
																}
															}
															else {
																?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group col-md-6">
                                                                    <select name="fb_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Facebook Account</option>
                                                                            <?php foreach($sqlROWs as $sqlROW){  ?>
                                                                            <option value="<?php echo $sqlROW["account_id"]; ?>"><?php echo $sqlROW["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                                                                                            
                                                             <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                             <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>
                                                         </div>
                                                         <?php
															}
														 //} ?>
                                                        </div>
                                                    </span>
                                                    <br />
                                                    
                                                     <span  class="cls_em">
                                                    	<label>Google </label>
                                                        <div data-role="dynamic-fields">
                                                         <?php 
															$aG_id = explode(',',$editData['g_acc']);
															//$aG_amount = explode(',',$editData['g_stDt']);
															if(count($aG_id)>0) {
																$g=1;
																$g_count = count($aG_id);
																foreach($aG_id as $g_key => $g_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="g_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Google Account</option>
                                                                            <?php foreach($sqlROWs2 as $sqlROW2) { ?>
                                                                            <option value="<?php echo $sqlROW2["account_id"]; ?>" <?php if(isset($g_val) && $g_val==$sqlROW2["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW2["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                                    
                                                               <?php if($g==$g_count) { ?>                                                         
																 <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                 <?php } ?>
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>                                                              
                                                         </div>
                                                          <?php  
															 		$g++;
																}
															}
															else {
																?>
                                                                <div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="g_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Google Account</option>
                                                                             <?php foreach($sqlROWs2 as $sqlROW2) { ?>
                                                                            <option value="<?php echo $sqlROW2["account_id"]; ?>"  ><?php echo $sqlROW2["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                                       
                                                                                                             
																 <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>                                                              
                                                         </div>
                                                         <?php } ?>
                                                         </div>
                                                    </span>
                                                    <br />
                                                    
                                                   
                                                    
                                                    <span  class="cls_em">
                                                    <label>Projects & Budget</label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
														if(isset($editData['projects'])) 
														{
															$projects = unserialize($editData['projects']);
															$budget = unserialize($editData['budget']);
                                                            $budget2 = unserialize($editData['budget2']);
															$month = unserialize($editData['month']);
															$project_count = count($projects);
														} else {
															$projects = $budget = $month = $budget2 = array(''); 
															$project_count = 1; 
														}
														if(count($projects)>0) {
														foreach($projects as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$project_count) { ?> not-first<?php } ?>">
                                                            <div class="">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Projects</label>
                                                                            <input type="text" name="projects[]"  class="form-control" id="field-value" placeholder="Projects" size="23" value="<?php echo $projects[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Budget FB</label>
                                                                            <input type="text" name="budget[]"  class="form-control" id="field-value" placeholder="Budget Meta" size="23" value="<?php echo $budget[$key]; ?>">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Budget G</label>
                                                                            <input type="text" name="budget2[]"  class="form-control" id="field-value" placeholder="Budget Google" size="23" value="<?php echo $budget2[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="month[]" placeholder="MM, YYYY" type="text" value="<?php echo $month[$key]; ?>"> 
                                                                        </div>
                                                                        
                                                                        <?php if($k==$project_count) { ?>
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
                                                            <div class="">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Projects</label>
                                                                            <input type="text" name="projects[]"  class="form-control" id="field-value" placeholder="Projects" size="23" >
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Budget</label>
                                                                            <input type="text" name="budget[]"  class="form-control" id="field-value" placeholder="Budget Meta" size="23" >
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Budget</label>
                                                                            <input type="text" name="budget2[]"  class="form-control" id="field-value" placeholder="Budget Google" size="23" >
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="month[]" placeholder="MM, YYYY" type="text" > 
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
                                                    
                                                    
                                                        <br>
                                                    
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
<!-- Include Date Range Picker -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>

<script>
	$(document).ready(function(){
		var date_input=$('.date'); //our date input has the name "date"
		var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
		date_input.datepicker({
			format: 'mm, yyyy',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,

        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).val($.datepicker.formatDate('MM yy', new Date(year, month, 1)));
        }
		});
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
				format: 'mm, yyyy',
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
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