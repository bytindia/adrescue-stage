<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'BYT Cards - Add Accounts';
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
		$_POST['in_acc'] = implode(',',$_POST['in_acc']);
		$_POST['ta_acc'] = implode(',',$_POST['ta_acc']);
		
        $_POST['fb_received'] = implode(',',$_POST['fb_received']);
        
		$_POST['date_fb'] = implode(',',$_POST['date_fb']);
        $_POST['date_fb2'] = implode(',',$_POST['date_fb2']);
		$_POST['date_g'] = implode(',',$_POST['date_g']);
        $_POST['date_g2'] = implode(',',$_POST['date_g2']);
		$_POST['date_in'] = implode(',',$_POST['date_in']);
        $_POST['date_in2'] = implode(',',$_POST['date_in2']);
		$_POST['date_ta'] = implode(',',$_POST['date_ta']);
        $_POST['date_ta2'] = implode(',',$_POST['date_ta2']);
		
			
		$dates = serialize(array_filter($_POST['date']));
		$amounts = serialize(array_filter($_POST['amount']));
		$notes = serialize(array_filter($_POST['note']));
		
		$p_dates = serialize(array_filter($_POST['p_date']));
		$p_amounts = serialize(array_filter($_POST['p_amount']));
		$p_notes = serialize(array_filter($_POST['p_note']));
		
		//echo count(array_filter($_POST['amount'])); exit;
		//print_r($_POST);  exit;
		if(count(array_filter($_POST['amount']))>0) {
			$tot_received = array_sum(array_filter($_POST['amount']));
		}
		if(count(array_filter($_POST['p_amount']))>0) {
			$p_tot_received = array_sum(array_filter($_POST['p_amount']));
		}
		if(isset($_GET['id'])) {
				$cirSql = "UPDATE cards SET card_name='".mysqli_real_escape_string($conn, $_POST['client'])."', email='".mysqli_real_escape_string($conn, $_POST['email'])."', daily_budget='".mysqli_real_escape_string($conn, $_POST['daily_budget'])."', fb_id='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."',  fb_received='".mysqli_real_escape_string($conn, $_POST['fb_received'])."', fb_stDt='".mysqli_real_escape_string($conn, $_POST['date_fb'])."', fb_enDt='".mysqli_real_escape_string($conn, $_POST['date_fb2'])."', g_id='".mysqli_real_escape_string($conn, $_POST['g_acc'])."',  g_stDt='".mysqli_real_escape_string($conn, $_POST['date_g'])."', g_enDt='".mysqli_real_escape_string($conn, $_POST['date_g2'])."', in_id='".mysqli_real_escape_string($conn, $_POST['in_acc'])."',  in_stDt='".mysqli_real_escape_string($conn, $_POST['date_in'])."', in_enDt='".mysqli_real_escape_string($conn, $_POST['date_in2'])."', ta_id='".mysqli_real_escape_string($conn, $_POST['ta_acc'])."',  ta_stDt='".mysqli_real_escape_string($conn, $_POST['date_ta'])."', ta_enDt='".mysqli_real_escape_string($conn, $_POST['date_ta2'])."', tot_paid=".$tot_received.", tot_penalty=".$p_tot_received.", updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id'];
		} else {
			  $cirSql = "INSERT INTO cards (uid, card_name, email, daily_budget, fb_id, fb_received, fb_stDt, fb_enDt, g_id, g_stDt,  g_enDt, in_id, in_stDt, in_enDt, ta_id, ta_stDt, ta_enDt, tot_paid, tot_penalty, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['email'])."', '".mysqli_real_escape_string($conn, $_POST['daily_budget'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['fb_received'])."', '".mysqli_real_escape_string($conn, $_POST['date_fb'])."', '".mysqli_real_escape_string($conn, $_POST['date_fb2'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['date_g'])."', '".mysqli_real_escape_string($conn, $_POST['date_g2'])."', '".mysqli_real_escape_string($conn, $_POST['in_acc'])."', '".mysqli_real_escape_string($conn, $_POST['date_in'])."', '".mysqli_real_escape_string($conn, $_POST['date_in2'])."', '".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', '".mysqli_real_escape_string($conn, $_POST['date_ta'])."', '".mysqli_real_escape_string($conn, $_POST['date_ta2'])."', ".$tot_received.", ".$p_tot_received.", now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		}
		
		//if(count(array_filter($_POST['amount']))>0) {
			$cirRes = mysqli_query($conn, "select tbl_id from cards_payments WHERE cashflow_id='".$lastId."' AND uid='".$_SESSION['uid']."'");						
			if(mysqli_num_rows($cirRes)==0) {
				$cirSql = "INSERT INTO cards_payments (uid, cashflow_id, amount, date, note,  p_amount, p_date, p_note, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $lastId)."', '".mysqli_real_escape_string($conn, $amounts)."', '".mysqli_real_escape_string($conn, $dates)."', '".mysqli_real_escape_string($conn, $notes)."', '".mysqli_real_escape_string($conn, $p_amounts)."', '".mysqli_real_escape_string($conn, $p_dates)."', '".mysqli_real_escape_string($conn, $p_notes)."', now(), now());"; 
				 mysqli_query($conn, $cirSql) or die(mysqli_error());
			} else {
				$cirSql = "UPDATE cards_payments SET amount='".mysqli_real_escape_string($conn, $amounts)."', date='".mysqli_real_escape_string($conn, $dates)."', note='".mysqli_real_escape_string($conn, $notes)."', p_amount='".mysqli_real_escape_string($conn, $p_amounts)."', p_date='".mysqli_real_escape_string($conn, $p_dates)."', p_note='".mysqli_real_escape_string($conn, $p_notes)."', updated=now() WHERE cashflow_id='".$lastId."' AND uid='".$_SESSION['uid']."'";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				
			}
		//}
		//exit();
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'cards.php';</script>";
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
										$sqlD=mysqli_query($conn, "SELECT c.card_name, c.email, c.daily_budget, c.fb_id, c.fb_stDt, c.fb_enDt, c.fb_received, c.g_id, c.g_stDt, c.in_id, c.in_stDt, c.ta_id, c.ta_stDt, cp.amount, c.g_enDt, c.in_enDt, c.ta_enDt, cp.date, cp.note, cp.p_amount, cp.p_date, cp.p_note FROM cards as c, cards_payments as cp WHERE c.tbl_id='".$_GET['id']."' AND c.tbl_id=cp.cashflow_id");
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
                                               
                                               
       
       

                                                <div class="col-md-8">     
                                                	<label>Client Name: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['card_name'])) { ?> value="<?php echo $editData['card_name']; ?>" <?php } ?>>                                                    	
                                                    <br /> 
                                                    <!--
                                                    <label>Client Email: </label>
                                                    <input type="text" name="email" class="form-control" <?php if(isset($editData['email'])) { ?> value="<?php echo $editData['email']; ?>" <?php } ?>>                                                    	
                                                    <br />   -->    
                                                    <label>Current Balance: </label>
                                                    <input type="text" name="daily_budget" class="form-control" <?php if(isset($editData['daily_budget'])) { ?> value="<?php echo $editData['daily_budget']; ?>" <?php } ?>>                                                    	
                                                    <br />      
                                                                                               	  
                                                    <span  class="cls_em">
                                                    	<label>Facebook </label>
                                                        <div data-role="dynamic-fields">
                                                        <?php 
														//if(isset($editData['fb_id']) && $editData['fb_id']!='') 
														//{
															$aFB_id = explode(',',$editData['fb_id']);
															$aFB_amount = explode(',',$editData['fb_stDt']);
                                                            $aFB_received = explode(',',$editData['fb_received']); 
                                                            if($editData['fb_enDt']!=''){
                                                                $fb_enDt = explode(',',$editData['fb_enDt']);
                                                            }

															if(count($aFB_id)>0) {
																$f=1;
																$f_count = count($aFB_id);
																foreach($aFB_id as $fb_key => $fb_val) {
															?>
															<div class="form-inline">
																<div class="form-group col-md-4">
																		<select name="fb_acc[]" id="fb_acc" class="form-control selCls">
																			   <option value="">Select Facebook Account</option>
																				<?php foreach($sqlROWs as $sqlROW){  ?>
																				<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($fb_val) && $fb_val==$sqlROW["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
																				 <?php } ?>
																		  </select>
																 </div>
																 <span class="fa fa-paper-plane"> - </span>
																 <div class="form-group"> <i class="fa fa-calendar"></i>
																		   <input class="form-control date" class="date" id="date" name="date_fb[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($aFB_amount[$fb_key])) { ?> value="<?php echo $aFB_amount[$fb_key]; ?>" <?php } ?> />
																 </div>
                                                                 <div class="form-group"> <i class="fa fa-calendar"></i>
																		   <input class="form-control date" class="date" id="date" name="date_fb2[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($fb_enDt[$fb_key])) { ?> value="<?php echo $fb_enDt[$fb_key]; ?>" <?php } ?> />
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
                                                        	<div class="form-group col-md-4">
                                                                    <select name="fb_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Facebook Account</option>
                                                                            <?php foreach($sqlROWs as $sqlROW){  ?>
                                                                            <option value="<?php echo $sqlROW["account_id"]; ?>"><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_fb[]" placeholder="MM/DD/YYYY" type="text" />
                                                             </div>  
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_fb2[]" placeholder="MM/DD/YYYY" type="text" />
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
                                                            $g_enDt = explode(',',$editData['g_enDt']);
															if(count($aG_id)>0) {
																$g=1;
																$g_count = count($aG_id);
																foreach($aG_id as $g_key => $g_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-4">
                                                                    <select name="g_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Google Account</option>
                                                                            <?php foreach($sqlROWs2 as $sqlROW2) { ?>
                                                                            <option value="<?php echo $sqlROW2["account_id"]; ?>" <?php if(isset($g_val) && $g_val==$sqlROW2["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW2["name"].' ('.$sqlROW2["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_g[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($aG_amount[$g_key])) { ?> value="<?php echo $aG_amount[$g_key]; ?>" <?php } ?> />
                                                             </div>    
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_g2[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($g_enDt[$g_key])) { ?> value="<?php echo $g_enDt[$g_key]; ?>" <?php } ?> />
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
                                                        	<div class="form-group  col-md-4">
                                                                    <select name="g_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Google Account</option>
                                                                             <?php foreach($sqlROWs2 as $sqlROW2) { ?>
                                                                            <option value="<?php echo $sqlROW2["account_id"]; ?>"  ><?php echo $sqlROW2["name"].' ('.$sqlROW2["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_g[]" placeholder="MM/DD/YYYY" type="text"/>
                                                             </div> 
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_g2[]" placeholder="MM/DD/YYYY" type="text"/>
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
                                                            $in_enDt = explode(',',$editData['in_enDt']);
															if(count($aIN_id)>0) {
																$in=1;
																$in_count = count($aIN_id);
																foreach($aIN_id as $in_key => $in_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-4">
                                                                    <select name="in_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select LinkedIn Account</option>
                                                                           <?php foreach($sqlROWs3 as $sqlROW3) { ?>
                                                                            <option value="<?php echo $sqlROW3["account_id"]; ?>" <?php if(isset($in_val) && $in_val==$sqlROW3["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW3["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_in[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($aIN_amount[$in_key])) { ?> value="<?php echo $aIN_amount[$in_key]; ?>" <?php } ?> />
                                                             </div> 
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_in2[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($in_enDt[$in_key])) { ?> value="<?php echo $in_enDt[$in_key]; ?>" <?php } ?> />
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
                                                        	<div class="form-group  col-md-4">
                                                                    <select name="in_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select LinkedIn Account</option>
                                                                           <?php foreach($sqlROWs2 as $sqlROW2) { ?>
                                                                            <option value="<?php echo $sqlROW3["account_id"]; ?>"><?php echo $sqlROW3["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_in[]" placeholder="MM/DD/YYYY" type="text" />
                                                             </div>  
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_in2[]" placeholder="MM/DD/YYYY" type="text" />
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
                                                            $ta_enDt = explode(',',$editData['ta_enDt']);
															if(count($aTA_id)>0) {
																$ta=1;
																$ta_count = count($aTA_id);
																foreach($aTA_id as $ta_key => $ta_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-4">
                                                                    <select name="ta_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Taboola Account</option>
                                                                             <?php foreach($sqlROWs4 as $sqlROW4) { ?>
                                                                            <option value="<?php echo $sqlROW4["account_id"]; ?>" <?php if(isset($ta_val) && $ta_val==$sqlROW4["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW4["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_ta[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($aTA_amount[$ta_key])) { ?> value="<?php echo $aTA_amount[$ta_key]; ?>" <?php } ?> />
                                                             </div>       
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_ta2[]" placeholder="MM/DD/YYYY" type="text" <?php if(isset($ta_enDt[$ta_key])) { ?> value="<?php echo $ta_enDt[$ta_key]; ?>" <?php } ?> />
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
                                                        	<div class="form-group  col-md-4">
                                                                    <select name="ta_acc[]" id="fb_acc" class="form-control selCls">
                                                                           <option value="">Select Taboola Account</option>
                                                                             <?php foreach($sqlROWs4 as $sqlROW4) { ?>
                                                                            <option value="<?php echo $sqlROW4["account_id"]; ?>" ><?php echo $sqlROW4["name"]; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                             <span class="fa fa-paper-plane"> - </span>
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_ta[]" placeholder="MM/DD/YYYY" type="text"  />
                                                             </div>  
                                                             <div class="form-group"><i class="fa fa-calendar"></i>
                                                             		   <input class="form-control date" class="date" id="date" name="date_ta2[]" placeholder="MM/DD/YYYY" type="text"  />
                                                             </div>      
                                                              <button class="btn btn-primary" data-role="add"><span class="glyphicon glyphicon-plus"></span></button>
                                                                
																 <button class="btn btn-danger" data-role="remove"><span class="glyphicon glyphicon-remove"></span></button>                                                                                                                                   
                                                         </div>
                                                         <?php   } ?>
                                                         </div>
                                                    </span>
                                                    <br />
                                                    
                                                    <span  class="cls_em">
                                                    <label>Prepaid Amount</label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
														if(isset($editData['amount'])) 
														{
															$amount = unserialize($editData['amount']);
															$date = unserialize($editData['date']);
															$note = unserialize($editData['note']);
															$amount_count = count($amount);
														} else {
															$date = $amount = $note = array(''); 
															$amount_count = 1; 
														}
														if(count($amount)>0) {
														foreach($amount as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$amount_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Amount</label>
                                                                            <input type="text" name="amount[]"  class="form-control" id="field-value" placeholder="Amount" size="40" value="<?php echo $amount[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="date[]" placeholder="MM/DD/YYYY" type="text" value="<?php echo $date[$key]; ?>"> 
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-comment"></i>
                                                                        <input class="form-control note" class="note" id="note" name="note[]" placeholder="Notes" type="text" value="<?php echo $note[$key]; ?>"> 
                                                                        </div>
                                                                        <?php if($k==$amount_count) { ?>
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
                                                                            <label class="sr-only" for="field-value">Amount</label>
                                                                            <input type="text" name="amount[]"  class="form-control" id="field-value" placeholder="Amount" size="40" >
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="date[]" placeholder="MM/DD/YYYY" type="text" > 
                                                                        </div>    
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-comment"></i>
                                                                        <input class="form-control note" class="note" id="note" name="note[]" placeholder="Notes" type="text" > 
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
                                                    
                                                    <span  class="cls_em">
                                                    <label>Paid Amount </label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
														if(isset($editData['p_amount'])) 
														{
															$p_amount = unserialize($editData['p_amount']);
															$p_date = unserialize($editData['p_date']);
															$p_note = unserialize($editData['p_note']);
															$p_amount_count = count($p_amount);
														} else {
															$p_date = $p_amount = $p_note = array(''); 
															$p_amount_count = 1; 
														}
														if(count($p_amount)>0) {
														foreach($p_amount as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$p_amount_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Amount</label>
                                                                            <input type="text" name="p_amount[]"  class="form-control" id="field-value" placeholder="Amount" size="40" value="<?php echo $p_amount[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="p_date[]" placeholder="MM/DD/YYYY" type="text" value="<?php echo $p_date[$key]; ?>"> 
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-comment"></i>
                                                                        <input class="form-control note" class="note" id="p_note" name="p_note[]" placeholder="Notes" type="text" value="<?php echo $p_note[$key]; ?>"> 
                                                                        </div>
                                                                        <?php if($k==$p_amount_count) { ?>
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
                                                                            <label class="sr-only" for="field-value">Amount</label>
                                                                            <input type="text" name="p_amount[]"  class="form-control" id="field-value" placeholder="Amount" size="40" >
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-calendar"></i>
                                                                        <input class="form-control date" class="date" id="date" name="p_date[]" placeholder="MM/DD/YYYY" type="text" > 
                                                                        </div>    
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group"><i class="fa fa-comment"></i>
                                                                        <input class="form-control note" class="note" id="p_note" name="p_note[]" placeholder="Notes" type="text" > 
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
                                                    <a href="loading.php?pg=cards.php"  class="btn btn-default">   Back</a>  
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