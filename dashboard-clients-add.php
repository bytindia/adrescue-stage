<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Ads Budget - Add Accounts';
$pgID = 8;
$err =''; 

include 'config.php'; 

function getLead($pg_id, $pg_access_token,$app_id,$api_ver) {
    //fetch lead info from FB API
    $graph_url= 'https://graph.facebook.com/'.$api_ver.'/act_'.$pg_id.'/subscribed_apps?app_id='.$app_id.'';
	//https://graph.facebook.com/'.$api_ver.'/'.$pg_id.'/subscribed_apps?subscribed_fields=leadgen';
    $acc_tok = array('access_token'=>$pg_access_token);
	
	$ch = curl_init($graph_url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $acc_tok);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);	
	curl_close($ch);
	//print_r($result); //exit;
    return json_decode($result);
	
	
}

if(isset($_POST['submit'])){
	
	if($_POST['client']!='') {
		
		/*echo count($_POST['fb_acc']);
		echo count($_POST['ta_acc']);
		print_r($_POST);  exit;		*/
		if(count($_POST['fb_acc'])>0 && $_POST['fb_acc']!=''){
			

			foreach($_POST['fb_acc'] as $val){
				$subs = getLead($val, $access_token,$app_id,$api_ver);
				//d($subs);
			}
		}
		//exit;
		$tot_received = $p_tot_received =0;
		
		$_POST['fb_acc'] = implode(',',array_filter($_POST['fb_acc']));
		$_POST['g_acc'] = implode(',',array_filter($_POST['g_acc']));
		$_POST['in_acc'] = implode(',',array_filter($_POST['in_acc']));
		$_POST['ta_acc'] = implode(',',array_filter($_POST['ta_acc']));

		$proj_name = serialize($_POST['proj_name']);
		$name_contain = serialize($_POST['name_contain']);

		$keyw_name = serialize($_POST['keyw_name']);
		$keyw_contain = serialize($_POST['keyw_contain']);
		$keyw_exclude = serialize($_POST['keyw_exclude']);
		
		//d($_POST); exit;
        
		if(isset($_GET['id'])) {
				 $cirSql = "UPDATE dashboard_accounts SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', username='".mysqli_real_escape_string($conn, $_POST['username'])."', password='".mysqli_real_escape_string($conn, $_POST['password'])."', fb_id='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."',   g_id='".mysqli_real_escape_string($conn, $_POST['g_acc'])."', in_id='".mysqli_real_escape_string($conn, $_POST['in_acc'])."', ta_id='".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', proj_name='".mysqli_real_escape_string($conn, $proj_name)."', name_contain='".mysqli_real_escape_string($conn, $name_contain)."',  keyw_name='".mysqli_real_escape_string($conn, $keyw_name)."', keyw_contain='".mysqli_real_escape_string($conn, $keyw_contain)."', keyw_exclude='".mysqli_real_escape_string($conn, $keyw_exclude)."', bud_id='".mysqli_real_escape_string($conn, $_POST['bud_id'])."', client_ty='".mysqli_real_escape_string($conn, $_POST['client_ty'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id']; //exit;
		} else {
			$bytes = random_bytes(20);
			$uni_rand = bin2hex($bytes); 

			echo $cirSql = "INSERT INTO dashboard_accounts (uid, client_name, username, password, fb_id, g_id, in_id, ta_id, rand, proj_name, name_contain, keyw_name, keyw_contain, keyw_exclude, bud_id, client_ty, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['username'])."', '".mysqli_real_escape_string($conn, $_POST['password'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['in_acc'])."', '".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', '".mysqli_real_escape_string($conn, $uni_rand)."', '".mysqli_real_escape_string($conn, $proj_name)."', '".mysqli_real_escape_string($conn, $name_contain)."', '".mysqli_real_escape_string($conn, $keyw_name)."', '".mysqli_real_escape_string($conn, $keyw_contain)."', '".mysqli_real_escape_string($conn, $keyw_exclude)."', '".mysqli_real_escape_string($conn, $_POST['bud_id'])."', '".mysqli_real_escape_string($conn, $_POST['client_ty'])."', now(), now());"; 
			mysqli_query($conn, $cirSql) or die(mysqli_error());
			$lastId = mysqli_insert_id($conn);
		}
		
		//if(count(array_filter($_POST['amount']))>0) {
            /*
			$cirRes = mysqli_query($conn, "select tbl_id from cashflow_payments WHERE cashflow_id='".$lastId."' AND uid='".$_SESSION['uid']."'");						
			if(mysqli_num_rows($cirRes)==0) {
				$cirSql = "INSERT INTO cashflow_payments (uid, cashflow_id, amount, date, note,  p_amount, p_date, p_note, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $lastId)."', '".mysqli_real_escape_string($conn, $amounts)."', '".mysqli_real_escape_string($conn, $dates)."', '".mysqli_real_escape_string($conn, $notes)."', '".mysqli_real_escape_string($conn, $p_amounts)."', '".mysqli_real_escape_string($conn, $p_dates)."', '".mysqli_real_escape_string($conn, $p_notes)."', now(), now());"; 
				 mysqli_query($conn, $cirSql) or die(mysqli_error());
			} else {
				$cirSql = "UPDATE cashflow_payments SET amount='".mysqli_real_escape_string($conn, $amounts)."', date='".mysqli_real_escape_string($conn, $dates)."', note='".mysqli_real_escape_string($conn, $notes)."', p_amount='".mysqli_real_escape_string($conn, $p_amounts)."', p_date='".mysqli_real_escape_string($conn, $p_dates)."', p_note='".mysqli_real_escape_string($conn, $p_notes)."', updated=now() WHERE cashflow_id='".$lastId."' AND uid='".$_SESSION['uid']."'";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				
			}*/
		//}
		//exit();
		$_SESSION['suc'] = 'Successfully Updated!';	
		//if(isset($_GET['id'])) { echo "<script>window.parent.$('#iframeModal').modal('hide');</script>";  }
		echo "<script>window.parent.$('#iframeModal').modal('hide'); window.parent.location.href = 'dashboard-clients.php';</script>";
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
                 
                  <!--<div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>                      
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>-->
                  <div class="clearfix"></div>
                  <div class="x_content">
                  		<?php 
							
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										//echo "SELECT c.client_name, c.email, c.password, c.fb_id, c.fb_stDt, c.fb_received, c.g_id, c.g_stDt, c.in_id, c.in_stDt, c.ta_id, c.ta_stDt FROM dashboard_accounts as c WHERE c.tbl_id=".$_GET['id'].""; 
										$sqlD=mysqli_query($conn, "SELECT c.client_name, c.username, c.password, c.fb_id, c.g_id,  c.in_id, c.ta_id, c.name_contain, c.proj_name, c.keyw_contain, c.keyw_name, c.keyw_exclude, c.bud_id, c.client_ty FROM dashboard_accounts as c WHERE c.tbl_id=".$_GET['id']."");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										$sqlROWs = $sqlROWs2 = $sqlROWs3 = $sqlROWs4 = $sqlROWs5 = array();
										
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs[] = $sqlROW; }
										
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $sqlROWs2[] = $sqlROW2; }
										
										$sqlRev3=mysqli_query($conn, "SELECT * FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $sqlROWs3[] = $sqlROW3; }
										
										$sqlRev4=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW4=mysqli_fetch_assoc($sqlRev4)) { $sqlROWs4[] = $sqlROW4; }

										$sqlRev5=mysqli_query($conn, "SELECT * FROM budget_reminder WHERE uid='".$_SESSION['uid']."' AND delete_status=0 AND hide_temp='0' order by client_name asc");
										while($sqlROW5=mysqli_fetch_array($sqlRev5)) { $sqlROWs5[] = $sqlROW5; }
										
										$gsquare_ta1 = array('account_id' => 'bytdigital-inr-gsquare-sc','name'=>'BYT Digital - INR - G Square - SC');
										$gsquare_ta2 = array('account_id' => 'gsquarechennai-inr-pp-sc','name'=>'G Square Chennai - INR - PP - SC');

										$sqlROWs4[count($sqlROWs4)] = $gsquare_ta1;
										$sqlROWs4[count($sqlROWs4)+1] = $gsquare_ta2;
										//d($sqlROWs5);
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                               
       
       

                                                <div class="col-md-10">     
                                                	<label>Client: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client_name'])) { ?> value="<?php echo $editData['client_name']; ?>" <?php } ?>>                                                    	
                                                    <br /> 
                                                    <!--
                                                    <label>Username: </label>
                                                    <input type="text" name="username" class="form-control" value="">                                                    	
                                                    <br />  
                                                    <label>Password: </label>
                                                    <input type="password" name="password" class="form-control" value="">                                                    	
                                                    <br />      -->
													<div class="clearfix"></div> 
													<label>Client Type:</label><br />
													<div class="form-group col-md-6">

													<?php
													$clientTypes = [
														"RE"    => "Real estate",
														"Ecom"  => "Ecommerce",
														"Coach" => "Coaching",
														"Sch"   => "School / College",
														"Oth"   => "Others"
													];
													?>

													<select name="client_ty" id="client_ty" class="form-control selCls">
														<option value="">Select Client Type</option>

														<?php foreach($clientTypes as $val => $label) { ?>
															<option value="<?php echo $val; ?>"
																<?php if($editData['client_ty'] == $val) { echo 'selected'; } ?>>
																<?php echo $label; ?>
															</option>
														<?php } ?>

													</select>

													</div>
													<div class="clearfix"></div> 
													<label>Budget: </label><br />  
													<div class="form-group  col-md-6">
                                                                    <select name="bud_id" id="bud_id" class="form-control selCls">
                                                                           <option value="">Select Budget Account</option>
                                                                             <?php foreach($sqlROWs5 as $sqlROW5) { ?>
                                                                            <option value="<?php echo $sqlROW5["tbl_id"]; ?>" <?php if($editData['bud_id']==$sqlROW5["tbl_id"]) { echo 'selected'; } ?> ><?php echo $sqlROW5["client_name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                    </div>    
													<br>
													<div class="clearfix"></div>                    	  
                                                    <span  class="cls_em">
                                                    	<label>Facebook </label>
                                                        <div data-role="dynamic-fields">
                                                        <?php 
														//if(isset($editData['fb_id']) && $editData['fb_id']!='') 
														//{
															$aFB_id = explode(',',$editData['fb_id']);
															$aFB_amount = explode(',',$editData['fb_stDt']);
                                                            $aFB_received = explode(',',$editData['fb_received']); 

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
																				<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($fb_val) && $fb_val==$sqlROW["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
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
                                                                            <option value="<?php echo $sqlROW["account_id"]; ?>"><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
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
															$aG_id = explode(',',$editData['g_id']);
															$aG_amount = explode(',',$editData['g_stDt']);
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
                                                                            <option value="<?php echo $sqlROW2["account_id"]; ?>" <?php if(isset($g_val) && $g_val==$sqlROW2["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW2["name"].' ('.$sqlROW2["account_id"].')'; ?></option>
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
                                                                            <option value="<?php echo $sqlROW2["account_id"]; ?>"  ><?php echo $sqlROW2["name"].' ('.$sqlROW2["account_id"].')'; ?></option>
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
                                                    	<label>LinkedIn </label>
                                                        <div data-role="dynamic-fields">
                                                         <div data-role="dynamic-fields">
                                                         <?php 
															$aIN_id = explode(',',$editData['in_id']);
															$aIN_amount = explode(',',$editData['in_stDt']);
															if(count($aIN_id)>0) {
																$in=1;
																$in_count = count($aIN_id);
																foreach($aIN_id as $in_key => $in_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="in_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select LinkedIn Account</option>
                                                                           <?php foreach($sqlROWs3 as $sqlROW3) { ?>
                                                                            <option value="<?php echo $sqlROW3["account_id"]; ?>" <?php if(isset($in_val) && $in_val==$sqlROW3["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW3["name"].' ('.$sqlROW3["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div> 
                                                             <?php if($in==$in_count) { ?>                                                         
																 <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                 <?php } ?>
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>                                                                                                                          
                                                         </div>
                                                          <?php  
															 		$in++;
																}
															}
															else {
																?>
                                                                <div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="in_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select LinkedIn Account</option>
                                                                           <?php foreach($sqlROWs2 as $sqlROW2) { ?>
                                                                            <option value="<?php echo $sqlROW3["account_id"]; ?>"><?php echo $sqlROW3["name"].' ('.$sqlROW3["account_id"].')'; ?></option>
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
                                                    	<label>Taboola </label>
                                                         <div data-role="dynamic-fields">
                                                         <?php 
															$aTA_id = explode(',',$editData['ta_id']);
															$aTA_amount = explode(',',$editData['ta_stDt']);
															if(count($aTA_id)>0) {
																$ta=1;
																$ta_count = count($aTA_id);
																foreach($aTA_id as $ta_key => $ta_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="ta_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Taboola Account</option>
                                                                             <?php foreach($sqlROWs4 as $sqlROW4) { ?>
                                                                            <option value="<?php echo $sqlROW4["account_id"]; ?>" <?php if(isset($ta_val) && $ta_val==$sqlROW4["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW4["name"].' ('.$sqlROW4["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <?php if($ta==$ta_count) { ?>                                                         
																 <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                 <?php } ?>
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>                                                                                                                           
                                                         </div>
                                                         <?php  
															 		$ta++;
																}
															}
															else {
																?>
                                                                <div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="ta_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Taboola Account</option>
                                                                             <?php foreach($sqlROWs4 as $sqlROW4) { ?>
                                                                            <option value="<?php echo $sqlROW4["account_id"]; ?>" ><?php echo $sqlROW4["name"].' ('.$sqlROW4["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>   
                                                              <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>                                                                                                                                   
                                                         </div>
                                                         <?php   } ?>
														 <span  class="cls_em">
															<br>
                                                    <label>Projects</label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
														//$proj_name = $name_contain = array();
														if(isset($editData['name_contain'])) 
														{
															$proj_name = unserialize($editData['proj_name']);
															$name_contain = unserialize($editData['name_contain']);
															$emails_count = count($name_contain);
														} else  {
															$name_contain = $proj_name = array(''); 
															$emails_count = 1; 
														}
														foreach($name_contain as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$emails_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline dyn">
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-name">Project Name</label>
                                                                            <input type="text" name="proj_name[]" class="form-control" id="field-name" placeholder="Project Name" size="30" value="<?php echo $proj_name[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Campaign Name Contains</label>
                                                                            <input type="text" name="name_contain[]"  class="form-control" id="field-value" placeholder="Campaing name contains" size="40" value="<?php echo $val; ?>">
                                                                        </div>
                                                                        <?php if($k==$emails_count) { ?>
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
														?>
                                                        
                                                        <br>
                                                    
                                                    </div>

													<span  class="cls_em">
															<br>
                                                    <label>Keywords</label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
														//$proj_name = $keyw_contain = array();
														if(isset($editData['keyw_contain'])) 
														{
															$keyw_name = unserialize($editData['keyw_name']);
															$keyw_contain = unserialize($editData['keyw_contain']);
															$keyw_exclude = unserialize($editData['keyw_exclude']);
															$emails_count = count($keyw_contain);
														} else  {
															$keyw_contain = $keyw_name = $keyw_exclude = array(''); 
															$emails_count = 1; 
														}
														foreach($keyw_contain as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$emails_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline dyn">
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-name">Project Name</label>
                                                                            <input type="text" name="keyw_name[]" class="form-control" id="field-name" placeholder="Keyword Name" size="30" value="<?php echo $keyw_name[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Campaign Name Contains</label>
                                                                            <input type="text" name="keyw_contain[]"  class="form-control" id="field-value" placeholder="Keyword contains" size="40" value="<?php echo $val; ?>">
                                                                        </div>
																		<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Campaign Name Contains</label>
                                                                            <input type="text" name="keyw_exclude[]"  class="form-control" id="field-value" placeholder="Keyword exclude" size="40" value="<?php echo $keyw_exclude[$key]; ?>">
                                                                        </div>
                                                                        <?php if($k==$emails_count) { ?>
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
														?>
                                                        
                                                        <br>
                                                    
                                                    </div>
                                                         </div>
                                                    </span>
                                                    <br />
                                                   
                                                    
                                                   
                                                    
                                                    </div>
                                                    <br />
													<a href="loading.php?pg=budget3.php"  class="btn btn-default">   Back</a>  
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-primary">
                                                 
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