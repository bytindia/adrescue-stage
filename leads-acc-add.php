<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Leads - Email Setup';
$pgID = 8;
$err =''; 

function getLead($pg_id, $pg_access_token,$api_ver) {
    //fetch lead info from FB API
    $graph_url= 'https://graph.facebook.com/'.$api_ver.'/'.$pg_id.'/subscribed_apps?subscribed_fields=leadgen';
    $acc_tok = array('access_token'=>$pg_access_token);
	//echo '<br>'.$pg_access_token;
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
	
	if($_POST['pg_id']!='') {
		
		
		//print_r($_POST);  exit;
        $googlesheet = 'No';
        if($_POST['googlesheet']) {
            $googlesheet = $_POST['googlesheet'];
        }
		
		$query = "SELECT pg_id, pg_name, pg_token FROM pages WHERE uid='".$_SESSION['uid']."' && pg_id='".$_POST['pg_id']."'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_assoc($result);
		$pg_id = $row['pg_id']; 
		$pg_name = $row['pg_name']; 
		$pg_token = $row['pg_token']; 
		
		$subs = getLead($pg_id, $pg_token,$api_ver);
		//echo $subs->error['message']; 
		//d($subs); exit;
		
		if(isset($subs->success) && $subs->success==1) {
			//d($subs); exit;
			$contains = serialize($_POST['contains']);
			$emails_to = serialize($_POST['emails_to']);

            $sheet_like = serialize($_POST['sheet_like']);
			$sheet_tabs = serialize($_POST['sheet_tabs']);

			//print_r($_POST);  exit;
			if(isset($_GET['id'])) { 
				$cirSql = "UPDATE leads_acc SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', pg_name='".mysqli_real_escape_string($conn, $pg_name)."',  pg_id='".mysqli_real_escape_string($conn, $_POST['pg_id'])."', email_ids='".mysqli_real_escape_string($conn, $_POST['email_ids'])."', words_like='".mysqli_real_escape_string($conn, $contains)."', emails_to='".mysqli_real_escape_string($conn,$emails_to)."', sheet_like='".mysqli_real_escape_string($conn, $sheet_like)."', sheet_tabs='".mysqli_real_escape_string($conn, $sheet_tabs)."', webhook_url='".mysqli_real_escape_string($conn,$_POST['webhook_url'])."', googlesheet='".mysqli_real_escape_string($conn,$googlesheet)."', googlesheet_id='".mysqli_real_escape_string($conn,$_POST['googlesheet_id'])."', googlesheet_tab='".mysqli_real_escape_string($conn,$_POST['googlesheet_tab'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id']; //exit;
			} else { 
				 $cirSql = "INSERT INTO leads_acc (uid, client_name, pg_name, pg_id, email_ids, words_like, emails_to, webhook_url, googlesheet, googlesheet_id, googlesheet_tab, sheet_like, sheet_tabs, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $pg_name)."', '".mysqli_real_escape_string($conn, $_POST['pg_id'])."', '".mysqli_real_escape_string($conn, $_POST['email_ids'])."','".mysqli_real_escape_string($conn, $contains)."', '".mysqli_real_escape_string($conn, $emails_to)."', '".mysqli_real_escape_string($conn,$_POST['webhook_url'])."', '".mysqli_real_escape_string($conn,$googlesheet)."', '".mysqli_real_escape_string($conn,$_POST['googlesheet_id'])."', '".mysqli_real_escape_string($conn,$_POST['googlesheet_tab'])."', '".mysqli_real_escape_string($conn, $sheet_like)."', '".mysqli_real_escape_string($conn, $sheet_tabs)."', now(), now());"; 
				 mysqli_query($conn, $cirSql) or die(mysqli_error());
				 $lastId = mysqli_insert_id($conn);
			}
		} 
        if(isset($subs->error->message) && $subs->error->message!='') { 
            $_SESSION['err'] = $subs->error->message;	
		    echo "<script>window.location = 'leads-acc-add.php?id={$lastId}&menu=hide';</script>"; exit;
        } //exit;
        
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>alert('Successfully Updated!'); window.parent.$('#iframeModal').modal('hide'); window.parent.location.href = 'leads-acc.php';</script>"; exit;
		exit();
	} 
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<style>


[data-role="dynamic-fields"] > .form-inline + .form-inline, [data-role="dynamic-fields2"] > .form-inline + .form-inline {
    margin-top: 0.5em;
}

[data-role="dynamic-fields"] > .form-inline [data-role="add"], [data-role="dynamic-fields2"] > .form-inline [data-role="add2"] {
    display: none;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="add"], [data-role="dynamic-fields2"] > .form-inline:last-child [data-role="add2"] {
    display: inline-block;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"], [data-role="dynamic-fields2"] > .form-inline:last-child [data-role="remove2"] {
    display: none;
}

.not-first [data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"], .not-first [data-role="dynamic-fields2"] > .form-inline:last-child [data-role="remove2"] {
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
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php include 'alert.php'; 
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT client_name, pg_name, pg_id, email_ids, words_like, emails_to, webhook_url,googlesheet,googlesheet_tab,googlesheet_id, sheet_like, sheet_tabs FROM leads_acc WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData['words_like']);
										//$words_like = unserialize($editData['words_like']);
										//print_r($words_like);
										$sqlRev=mysqli_query($conn, "SELECT * FROM pages WHERE uid='".$_SESSION['uid']."' order by pg_name asc");
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
                                                    <select name="pg_id" id="fb_acc" class="form-control">
                                                    	<option value="">Select Facebook Ad Account</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["pg_id"]; ?>" <?php if(isset($editData['pg_id']) && $editData['pg_id']==$sqlROW["pg_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["pg_name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                   
                                                    <br />
                                                    <span  class="cls_em">
                                                    <label>Webhook URL: </label>
                                                    <input  type="text" name="webhook_url" class="form-control" <?php if(isset($editData['webhook_url'])) { ?> value="<?php echo $editData['webhook_url']; ?>" <?php } ?>>  </span>
                                                    <br />      
                                                    <span  class="cls_em">
                                                    <label>Email Ids: </label>
                                                    <input  type="text" name="email_ids" class="form-control" <?php if(isset($editData['email_ids'])) { ?> value="<?php echo $editData['email_ids']; ?>" <?php } ?>>  </span>
                                                    <br />
                                                    <div class="row col-12">
                                                                       
                                                                       
                                                                        <div class="col-3">
                                                                        <b style="margin-left: 5px; font-size:14px; ">Leads to Googlesheet:</b> <input  type="checkbox" name="googlesheet" value="Yes" <?php if(isset($editData['googlesheet']) && $editData['googlesheet']=='Yes') { ?> checked="checked" <?php } ?>> 
                                                                        </div>
                                                    </div>
                                                    <br />
                                                    <span  class="cls_em">
                                                    <label>GoogleSheet ID: </label>
                                                    <input  type="text" name="googlesheet_id" class="form-control" <?php if(isset($editData['googlesheet_id'])) { ?> value="<?php echo $editData['googlesheet_id']; ?>" <?php } ?>>  </span>
                                                    <br />
                                                    <span  class="cls_em">
                                                    <label>GoogleSheet Tab Name: </label>
                                                    <input  type="text" name="googlesheet_tab" class="form-control" <?php if(isset($editData['googlesheet_tab'])) { ?> value="<?php echo $editData['googlesheet_tab']; ?>" <?php } ?>>  </span>
                                                    <br />
                                                    <span  class="cls_em">
                                                    <label>If the Form Name Contains the word - Sheet Tab</label>
                                                    </span>
                                                    <div class="container2">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
                                                        $sheet_tabs = $sheet_like = array(''); 
														$emails_count = 1; 
														if(isset($editData['sheet_tabs'])) 
														{
                                                            if (@unserialize($editData['sheet_tabs']) !== false || $editData['sheet_tabs'] === 'b:0;') {
                                                                $sheet_like = unserialize($editData['sheet_like']);
                                                                $sheet_tabs = unserialize($editData['sheet_tabs']);
                                                                $emails_count = count($sheet_tabs);
                                                            }
														} 
														foreach($sheet_tabs as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$emails_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields2">
                                                                    <div class="form-inline dyn2">
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-name">Form Name contains the word</label>
                                                                            <input type="text" name="sheet_like[]" class="form-control" id="field-name" placeholder="Form Name Contains" size="30" value="<?php echo $sheet_like[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Field Value</label>
                                                                            <input type="text" name="sheet_tabs[]"  class="form-control" id="field-value" placeholder="Sheet Tab" size="40" value="<?php echo $val; ?>">
                                                                        </div>
                                                                        <?php if($k==$emails_count) { ?>
                                                                        <button class="btn btn-primary" data-role="add2">
                                                                        	<span class="glyphicon glyphicon-plus"></span>
                                                                        </button>
                                                                        <?php } ?>
                                                                        <button class="btn btn-danger" data-role="remove2">
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
                                                    <label>If the Form Name Contains the word - Email To</label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($editData);
														$k=1;
														if(isset($editData['emails_to'])) 
														{
															$words_like = unserialize($editData['words_like']);
															$emails_to = unserialize($editData['emails_to']);
															$emails_count = count($emails_to);
														} else {
															$emails_to = $words_like = array(''); 
															$emails_count = 1; 
														}
														foreach($emails_to as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$emails_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline dyn">
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-name">Form Name contains the word</label>
                                                                            <input type="text" name="contains[]" class="form-control" id="field-name" placeholder="Form Name Contains" size="30" value="<?php echo $words_like[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Field Value</label>
                                                                            <input type="text" name="emails_to[]"  class="form-control" id="field-value" placeholder="Email to" size="40" value="<?php echo $val; ?>">
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

                                                    <br />                                                   
                                                    <a   class="btn btn-default"  onclick="parent.$('#iframeModal').modal('hide');">  Close</a>  
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
<script>
$(function() {
	//$('[data-role="dynamic-fields"] > .form-inline [data-role="add"]').click();
    // Remove button click
    $(document).on('click', '[data-role="dynamic-fields"] > .dyn [data-role="remove"]', function(e) {
            e.preventDefault();
            $(this).closest('.dyn').remove();
        }
    );
    // Add button click
    $(document).on('click',  '[data-role="dynamic-fields"] > .dyn [data-role="add"]', function(e) {
            e.preventDefault();
            var container = $(this).closest('[data-role="dynamic-fields"]');
            new_field_group = container.children().filter('.dyn:first-child').clone();
            new_field_group.find('input').each(function(){
                $(this).val('');
            });
            container.append(new_field_group);
        }
    );

    $(document).on('click', '[data-role="dynamic-fields2"] > .dyn2 [data-role="remove2"]', function(e) {
            e.preventDefault();
            $(this).closest('.dyn2').remove();
        }
    );
    // Add button click
    $(document).on('click',  '[data-role="dynamic-fields2"] > .dyn2 [data-role="add2"]', function(e) {
            e.preventDefault();
            var container = $(this).closest('[data-role="dynamic-fields2"]');
            new_field_group = container.children().filter('.dyn2:first-child').clone();
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