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
//$media_by = array(1=>'Ramesh', 2=>'RajKumar', 3=>'Simin', 4=>'Bargavi', 5=>'Shaheena', 6=>'Samadh',  8=>'Radhika',  10=>'Nida', 11=>'Maha', 12=>'Bala', 14=>'Charan', 15=>'Mughil');
$media_by = array(1=>'Ramesh',  3=>'Simin',  5=>'Shaheena', 14=>'Charan', 15=>'Mughil', 16=>'Karthik');

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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if(isset($_POST['submit'])){
	
	if($_POST['client']!='') {
		//d($_POST); //exit;
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
		
		$_POST['fb_acc'] = implode(',',$_POST['fb_acc']);
		$_POST['g_acc'] = implode(',',$_POST['g_acc']);
		$_POST['in_acc'] = implode(',',$_POST['in_acc']);
		$_POST['ta_acc'] = implode(',',$_POST['ta_acc']);
		/*
		if(isset($_POST['fb_received'])) {
			$_POST['fb_received'] = implode(',',$_POST['fb_received']);
		}
        
        
		$_POST['date_fb'] = implode(',',$_POST['date_fb']);
		$_POST['date_g'] = implode(',',$_POST['date_g']);
		$_POST['date_in'] = implode(',',$_POST['date_in']);
		$_POST['date_ta'] = implode(',',$_POST['date_ta']);
		
			
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
		}*/
		if(!isset($_POST['cc_card'])) { $_POST['cc_card']=''; }
		if(isset($_GET['id'])) {
				 $cirSql = "UPDATE budget_reminder SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', camp_name='".mysqli_real_escape_string($conn, $_POST['camp_name'])."', email='".mysqli_real_escape_string($conn, $_POST['email'])."', total_budget='".mysqli_real_escape_string($conn, $_POST['total_budget'])."', budget_received='".mysqli_real_escape_string($conn, $_POST['budget_received'])."', retainer_fee='".mysqli_real_escape_string($conn, $_POST['retainer_fee'])."', fb_id='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."',   g_id='".mysqli_real_escape_string($conn, $_POST['g_acc'])."', in_id='".mysqli_real_escape_string($conn, $_POST['in_acc'])."', ta_id='".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', cc_card='".mysqli_real_escape_string($conn, $_POST['cc_card'])."', media_by='".mysqli_real_escape_string($conn, $_POST['media_by'])."',updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id'];
		} else {
			 $cirSql = "INSERT INTO budget_reminder (uid, client_name, camp_name, email, total_budget, budget_received, retainer_fee,fb_id, g_id, in_id, ta_id, cc_card, media_by, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['camp_name'])."', '".mysqli_real_escape_string($conn, $_POST['email'])."', '".mysqli_real_escape_string($conn, $_POST['total_budget'])."', '".mysqli_real_escape_string($conn, $_POST['budget_received'])."', '".mysqli_real_escape_string($conn, $_POST['retainer_fee'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['g_acc'])."', '".mysqli_real_escape_string($conn, $_POST['in_acc'])."', '".mysqli_real_escape_string($conn, $_POST['ta_acc'])."', '".mysqli_real_escape_string($conn, $_POST['cc_card'])."', '".mysqli_real_escape_string($conn, $_POST['media_by'])."', now(), now());"; 
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
		  echo "<script>
  alert('Successfully Updated!');

  (function () {
    var p = window.parent || window.top;

    // If we can talk to the parent and it has jQuery/Bootstrap modal:
    if (p && p.$ && p.$('#iframeModal').length) {
      // Redirect the PARENT after the modal is fully hidden
      p.$('#iframeModal').one('hidden.bs.modal', function () {
        p.location.replace('budget3.php'); // or p.location.href = 'budget3.php';
      }).modal('hide');
    } else {
      // Fallback: redirect this frame
      window.location.replace('budget4.php');
    }
  })();
</script>";
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
body { margin-top: 0px !important;}
body { padding-top: 0px !important; overflow-x: hidden;}
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include 'menu-left.php';
			//include 'menu-top.php'; 
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
										//echo "SELECT c.client_name, c.email, c.total_budget, c.fb_id, c.fb_stDt, c.fb_received, c.g_id, c.g_stDt, c.in_id, c.in_stDt, c.ta_id, c.ta_stDt FROM budget_reminder as c WHERE c.tbl_id=".$_GET['id'].""; 
										$sqlD=mysqli_query($conn, "SELECT c.client_name, c.camp_name, c.email, c.total_budget, c.fb_id, c.fb_stDt, c.fb_received, c.g_id, c.g_stDt, c.in_id, c.in_stDt, c.ta_id, c.ta_stDt, c.cc_card, c.media_by, c.budget_received, c.retainer_fee FROM budget_reminder as c WHERE c.tbl_id=".$_GET['id']."");
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
										
										$sqlRev4=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");
										while($sqlROW4=mysqli_fetch_assoc($sqlRev4)) { $sqlROWs4[] = $sqlROW4; }
										
										$gsquare_ta1 = array('account_id' => 'bytdigital-inr-gsquare-sc','name'=>'BYT Digital - INR - G Square - SC');
										$gsquare_ta2 = array('account_id' => 'gsquarechennai-inr-pp-sc','name'=>'G Square Chennai - INR - PP - SC');

										$sqlROWs4[count($sqlROWs4)] = $gsquare_ta1;
										$sqlROWs4[count($sqlROWs4)+1] = $gsquare_ta2;
										
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
                                                    <label>Campaign Name Contains: </label>
                                                    <input type="text" name="camp_name" class="form-control" <?php if(isset($editData['camp_name'])) { ?> value="<?php echo $editData['camp_name']; ?>" <?php } ?> placeholder="Project Name[name contains]">                                                    	
                                                    <br /> 
                                                    <label>Client Email: </label>
                                                    <input type="text" name="email" class="form-control" <?php if(isset($editData['email'])) { ?> value="<?php echo $editData['email']; ?>" <?php } ?>>                                                    	
                                                    <br />  
                                                    <label>Monthly Budget: </label>
                                                    <input type="text" name="total_budget" class="form-control" <?php if(isset($editData['total_budget'])) { ?> value="<?php echo $editData['total_budget']; ?>" <?php } ?>>                                                    	
                                                    <br />      
													<?php if(!isset($_SESSION['ads'])) { ?>
													<label>Budget Received: </label>
                                                    <input type="text" name="budget_received" class="form-control" <?php if(isset($editData['budget_received'])) { ?> value="<?php echo $editData['budget_received']; ?>" <?php } ?>>                                                    	
                                                    <br />    
													
													<label>Retainer Fee: </label>
                                                    <input type="text" name="retainer_fee" class="form-control" <?php if(isset($editData['retainer_fee'])) { ?> value="<?php echo $editData['retainer_fee']; ?>" <?php } ?>>                                                    	
                                                    <br />
													<?php } else { ?>
														
                                                    <input type="hidden" name="budget_received" class="form-control" <?php if(isset($editData['budget_received'])) { ?> value="<?php echo $editData['budget_received']; ?>" <?php } ?>>                                                    	
                                                   
                                                    <input type="hidden" name="retainer_fee" class="form-control" <?php if(isset($editData['retainer_fee'])) { ?> value="<?php echo $editData['retainer_fee']; ?>" <?php } ?>>                                                    	
                                                    

													<?php } ?>
													<label>Media Buyer: </label>
                                                    <select name="media_by" id="media_by" class="form-control selCls">
																<option value="">Select Media Buyer</option>
																<?php foreach($media_by as $mb_key => $mb_val){  ?>
																	<option value="<?php echo $mb_key; ?>" <?php if(isset($mb_key) &&isset($editData["media_by"]) && $mb_key==$editData["media_by"]) { echo "selected='selected'"; } ?> ><?php echo $mb_val; ?></option>
																<?php } ?>
													</select>                                               	
                                                    <br />  
													<label>Account: </label>
													<div class="form-check">
														<input class="form-check-input" type="radio" value="1" name="cc_card" id="flexRadioDefault1" <?php if(isset($editData['cc_card']) && $editData['cc_card']==1) { echo 'checked'; } ?>>
														<label class="form-check-label" for="flexRadioDefault1">
															BYT
														</label>
														&nbsp; &nbsp; &nbsp;
														<input class="form-check-input" type="radio" value="2" name="cc_card" id="flexRadioDefault2" <?php if(isset($editData['cc_card']) && $editData['cc_card']==2) { echo 'checked'; } ?>>
														<label class="form-check-label" for="flexRadioDefault2">
															Client
														</label>
													</div>
                                                                                                             	  
                                                    <span  class="cls_em">
                                                    	<label>Facebook </label>
                                                        <div data-role="dynamic-fields">
                                                        <?php 
														//if(isset($editData['fb_id']) && $editData['fb_id']!='') 
														//{
															$aFB_id = explode(',',$editData['fb_id'] ?? '');
															$aFB_amount = explode(',',$editData['fb_stDt'] ?? '');
                                                            $aFB_received = explode(',',$editData['fb_received'] ?? ''); 

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
															$aG_id = explode(',',$editData['g_id'] ?? '');
															$aG_amount = explode(',',$editData['g_stDt'] ?? '');
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
															$aIN_id = explode(',',$editData['in_id'] ?? '');
															$aIN_amount = explode(',',$editData['in_stDt'] ?? '');
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
															$aTA_id = explode(',',$editData['ta_id'] ?? '');
															$aTA_amount = explode(',',$editData['ta_stDt'] ?? '');
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
                                                         </div>
                                                    </span>
                                                    <br />
                                                   
                                                    
                                                   
                                                    
                                                    </div>
                                                    <br />
													<a href="loading.php?pg=budget3.php"  class="btn btn-default">   Back</a>  
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