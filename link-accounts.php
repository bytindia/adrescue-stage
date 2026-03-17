<?php include 'header.php'; 
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); 
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

$acc_type = array(1=>'Real Estate', 2=>'Coaching', 3=>'Education', 4=>'Ecommerce', 5=>'Others');

Auth();
$pgHeadline = 'Add Invoice Account';
$pgID = 6;
$err =''; 

if(isset($_POST['submit'])){
  
	//d($_POST); exit;
	//if($_POST['fb_acc']!='' || $_POST['g_acc']!='') {
		if (!empty($_POST['client_name'])) {

    $_POST['fb_acc'] = implode(',', array_filter($_POST['fb_acc']));
    $_POST['g_acc'] = implode(',', array_filter($_POST['g_acc']));
    $_POST['li_acc'] = implode(',', array_filter($_POST['li_acc']));
    $_POST['ta_acc'] = implode(',', array_filter($_POST['ta_acc']));

    // Set default empty values for optional fields
    $fields = [
        'byt_fee_c', 'byt_fee_i', 'fb_fee_i', 'g_fee_i', 'li_fee', 'li_fee_i',
        'ads_mgnt_c', 'ads_mgnt_i', 'ads_spend_c', 'ads_spend_i',
        'seo_c', 'seo_i', 'seo_m', 'add_project_c', 'add_project_i', 'add_project_m',
        'gif_ban_c', 'gif_ban_i', 'gif_ban_m', 'linkedin_c', 'linkedin_i', 'linkedin_m',
        'web_maint_c', 'web_maint_i', 'web_maint_m', 'shopify_c', 'shopify_i', 'shopify_m',
        'cust_val_c', 'cust_val_i', 'cust_val_m', 'fb_spend_inc', 'fb_spend_inc_i', 'g_spend_inc', 'g_spend_inc_i', 'li_spend_inc', 'li_spend_inc_i',
        'ads_budget', 'ads_budget_i', 'ads_budget2', 'ads_budget2_i', 'gsheet'
    ];

    foreach ($fields as $field) {
        if (!isset($_POST[$field])) $_POST[$field] = '';
    }

    $ext_labels = serialize(array_filter($_POST['ext_label']));
    $ext_values = serialize(array_filter($_POST['ext_value']));
    $ext_inv    = serialize(array_filter($_POST['ext_inv']));
    $_POST['pm_i'] = '';
    if (!empty($_POST['pm_i1']) || !empty($_POST['pm_i2'])) {
      $_POST['pm_i'] = 'on';
    }
    if (!isset($_GET['id'])) {
        $cirSql = "INSERT INTO accounts_invoice (
            uid, client_name, camp_name, fb_acc, g_acc, li_acc, ta_acc,
            ads_mgnt, ads_mgnt_c, ads_mgnt_i, ads_spend, ads_spend_c, ads_spend_i,
            gst, addr1, addr2, byt_fee, byt_fee_c, byt_fee_i,
            fb_fee, fb_fee_i, g_fee, g_fee_i, li_fee, li_fee_i,
            ext_label, ext_value, ext_inv,
            igst_gst, igst, cgst, sgst,
            tax_note, tax_note2, email_ids, cont_name, cont_email, acc_type, ads_budget, ads_budget_i, ads_budget2, ads_budget2_i, gsheet, pm_i, fb_spend_inc, fb_spend_inc_i, g_spend_inc, g_spend_inc_i, li_spend_inc, li_spend_inc_i, bud_tbl, created, updated
        ) VALUES (
            '{$_SESSION['uid']}',
            '" . mysqli_real_escape_string($conn, $_POST['client_name']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['camp_name']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['fb_acc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['g_acc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['li_acc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ta_acc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_mgnt']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_mgnt_c']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_mgnt_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_spend']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_spend_c']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_spend_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['gst']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['addr1']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['addr2']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['byt_fee']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['byt_fee_c']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['byt_fee_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['fb_fee']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['fb_fee_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['g_fee']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['g_fee_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['li_fee']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['li_fee_i']) . "', 
            '" . mysqli_real_escape_string($conn, $ext_labels) . "',
            '" . mysqli_real_escape_string($conn, $ext_values) . "',
            '" . mysqli_real_escape_string($conn, $ext_inv) . "',
            '" . mysqli_real_escape_string($conn, $_POST['igst_gst']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['igst']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['cgst']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['sgst']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['tax_note']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['tax_note2']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['email_ids']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['cont_name']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['cont_email']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['acc_type']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_budget']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_budget_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_budget2']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['ads_budget2_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['gsheet']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['pm_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['fb_spend_inc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['fb_spend_inc_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['g_spend_inc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['g_spend_inc_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['li_spend_inc']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['li_spend_inc_i']) . "',
            '" . mysqli_real_escape_string($conn, $_POST['bud_tbl']) . "',
            NOW(), NOW()
        )";

        mysqli_query($conn, $cirSql) or die(mysqli_error($conn));
        $lastId = mysqli_insert_id($conn);

    } else {
        $id = intval($_GET['id']);
        $cirSql = "UPDATE accounts_invoice SET 
            client_name='" . mysqli_real_escape_string($conn, $_POST['client_name']) . "',
            camp_name='" . mysqli_real_escape_string($conn, $_POST['camp_name']) . "',
            fb_acc='" . mysqli_real_escape_string($conn, $_POST['fb_acc']) . "',
            g_acc='" . mysqli_real_escape_string($conn, $_POST['g_acc']) . "',
            li_acc='" . mysqli_real_escape_string($conn, $_POST['li_acc']) . "',
            ta_acc='" . mysqli_real_escape_string($conn, $_POST['ta_acc']) . "',
            gst='" . mysqli_real_escape_string($conn, $_POST['gst']) . "',
            addr1='" . mysqli_real_escape_string($conn, $_POST['addr1']) . "',
            addr2='" . mysqli_real_escape_string($conn, $_POST['addr2']) . "',
            byt_fee='" . mysqli_real_escape_string($conn, $_POST['byt_fee']) . "',
            byt_fee_c='" . mysqli_real_escape_string($conn, $_POST['byt_fee_c']) . "',
            byt_fee_i='" . mysqli_real_escape_string($conn, $_POST['byt_fee_i']) . "',
            ads_mgnt='" . mysqli_real_escape_string($conn, $_POST['ads_mgnt']) . "',
            ads_mgnt_c='" . mysqli_real_escape_string($conn, $_POST['ads_mgnt_c']) . "',
            ads_mgnt_i='" . mysqli_real_escape_string($conn, $_POST['ads_mgnt_i']) . "',
            ads_spend='" . mysqli_real_escape_string($conn, $_POST['ads_spend']) . "',
            ads_spend_c='" . mysqli_real_escape_string($conn, $_POST['ads_spend_c']) . "',
            ads_spend_i='" . mysqli_real_escape_string($conn, $_POST['ads_spend_i']) . "',
            fb_fee='" . mysqli_real_escape_string($conn, $_POST['fb_fee']) . "',
            fb_fee_i='" . mysqli_real_escape_string($conn, $_POST['fb_fee_i']) . "',
            g_fee='" . mysqli_real_escape_string($conn, $_POST['g_fee']) . "',
            g_fee_i='" . mysqli_real_escape_string($conn, $_POST['g_fee_i']) . "',
            li_fee='" . mysqli_real_escape_string($conn, $_POST['li_fee']) . "',
            li_fee_i='" . mysqli_real_escape_string($conn, $_POST['li_fee_i']) . "',  
            ext_label='" . mysqli_real_escape_string($conn, $ext_labels) . "',
            ext_value='" . mysqli_real_escape_string($conn, $ext_values) . "',
            ext_inv='" . mysqli_real_escape_string($conn, $ext_inv) . "',
            igst_gst='" . mysqli_real_escape_string($conn, $_POST['igst_gst']) . "',
            igst='" . mysqli_real_escape_string($conn, $_POST['igst']) . "',
            cgst='" . mysqli_real_escape_string($conn, $_POST['cgst']) . "',
            sgst='" . mysqli_real_escape_string($conn, $_POST['sgst']) . "',
            tax_note='" . mysqli_real_escape_string($conn, $_POST['tax_note']) . "',
            tax_note2='" . mysqli_real_escape_string($conn, $_POST['tax_note2']) . "',
            email_ids='" . mysqli_real_escape_string($conn, $_POST['email_ids']) . "',
            cont_name='" . mysqli_real_escape_string($conn, $_POST['cont_name']) . "',
            cont_email='" . mysqli_real_escape_string($conn, $_POST['cont_email']) . "',
            acc_type='" . mysqli_real_escape_string($conn, $_POST['acc_type']) . "',
            ads_budget='" . mysqli_real_escape_string($conn, $_POST['ads_budget']) . "',
            ads_budget_i='" . mysqli_real_escape_string($conn, $_POST['ads_budget_i']) . "',
            ads_budget2='" . mysqli_real_escape_string($conn, $_POST['ads_budget2']) . "',
            ads_budget2_i='" . mysqli_real_escape_string($conn, $_POST['ads_budget2_i']) . "',
            gsheet='" . mysqli_real_escape_string($conn, $_POST['gsheet']) . "',
            pm_i = '" . mysqli_real_escape_string($conn, $_POST['pm_i']) . "',
            fb_spend_inc = '" . mysqli_real_escape_string($conn, $_POST['fb_spend_inc']) . "',
            g_spend_inc = '" . mysqli_real_escape_string($conn, $_POST['g_spend_inc']) . "',
            li_spend_inc = '" . mysqli_real_escape_string($conn, $_POST['li_spend_inc']) . "',
            fb_spend_inc_i = '" . mysqli_real_escape_string($conn, $_POST['fb_spend_inc_i']) . "',
            g_spend_inc_i = '" . mysqli_real_escape_string($conn, $_POST['g_spend_inc_i']) . "',  
            li_spend_inc_i = '" . mysqli_real_escape_string($conn, $_POST['li_spend_inc_i']) . "',
            bud_tbl = '" . mysqli_real_escape_string($conn, $_POST['bud_tbl']) . "',
            updated=NOW()
        WHERE tbl_id = {$id}";

        mysqli_query($conn, $cirSql) or die(mysqli_error($conn));
        $lastId = $id;
    }
}

	
	//d($_POST); exit;
	$_SESSION['suc'] = 'Successfully Updated!';	
	//echo "<script>window.location = 'link-accounts.php?id=".$lastId."';</script>";
  echo "<script> window.parent.$('#iframeModal').modal('hide'); window.parent.location.href = 'invoice.php';</script>"; exit;
}

?>
<style>
.panel-heading {
  position: relative;
}
.panel-heading[data-toggle="collapse"]:after {
  font-family: 'Glyphicons Halflings';
  content: "\e072"; /* "play" icon */
  position: absolute;
  color: #b0c5d8;
  font-size: 18px;
  line-height: 22px;
  right: 20px;
  top: calc(50% - 10px);

  /* rotate "play" icon from > (right arrow) to down arrow */
  -webkit-transform: rotate(-90deg);
  -moz-transform:    rotate(-90deg);
  -ms-transform:     rotate(-90deg);
  -o-transform:      rotate(-90deg);
  transform:         rotate(-90deg);
}
.panel-heading[data-toggle="collapse"].collapsed:after {
  /* rotate "play" icon from > (right arrow) to ^ (up arrow) */
  -webkit-transform: rotate(90deg);
  -moz-transform:    rotate(90deg);
  -ms-transform:     rotate(90deg);
  -o-transform:      rotate(90deg);
  transform:         rotate(90deg);
}
.panel-group .panel { cursor:pointer; }
label { font-weight:400 }

.form-inline { margin-left:10px; }
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
body { padding-top: 0px !important; overflow-x: hidden;}
.panel-default>.panel-heading { background-color: #4086c3; color: white; border-color: #4086c3; }
.panel-default { border-color: #4086c3; }
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include 'menu-left.php';
		//	include 'menu-top.php'; 
		?>

        

        <!-- page content -->
         <div class="" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  
                  
                  <div class="x_content">
                  		<?php 
                      include 'alert.php';
										$getData = array();
										$getData['igst_gst'] = 0;
										if(isset($_GET['id'])) {
											//echo "SELECT client_name, fb_acc, g_acc, gst, addr1, addr2, byt_fee, fb_fee, g_fee, seo, seo_m, add_project, add_project_m, gif_ban, gif_ban_m, linkedin, linkedin_m, web_maint, web_maint_m,  shopify, shopify_m, igst_gst, igst, cgst, sgst, tax_note, acc_type FROM accounts_invoice WHERE tbl_id=".$_GET['id']."";
											$sqlD=mysqli_query($conn, "SELECT uid, client_name, camp_name, fb_acc, g_acc, li_acc, ta_acc, ads_mgnt, ads_mgnt_c, ads_mgnt_i, ads_spend, ads_spend_c, ads_spend_i, gst, addr1, addr2, byt_fee, byt_fee_c, byt_fee_i, fb_fee, fb_fee_i, g_fee, g_fee_i, li_fee, li_fee_i, ext_label, ext_value, ext_inv, igst_gst, igst, cgst, sgst, tax_note, tax_note2, email_ids, cont_name, cont_email, created, acc_type, ads_budget, ads_budget_i, ads_budget2, ads_budget2_i,gsheet, pm_i, fb_spend_inc, fb_spend_inc_i, g_spend_inc, g_spend_inc_i, li_spend_inc, li_spend_inc_i, bud_tbl FROM accounts_invoice WHERE uid='".$_SESSION['uid']."' AND tbl_id='".$_GET['id']."'");
											while($Rdata=mysqli_fetch_array($sqlD)) {
												$getData = $Rdata;
											}
										}
										
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                    $sqlRev3=mysqli_query($conn, "SELECT * FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev4=mysqli_query($conn, "SELECT * FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");

                    $sqlROWs = $sqlROWs2 = $sqlROWs3 = $sqlROWs4 = array();

										while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs[] = $sqlROW; }
										while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $sqlROWs2[] = $sqlROW2; }
                    while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $sqlROWs3[] = $sqlROW3; }
                    while($sqlROW4=mysqli_fetch_array($sqlRev4)) { $sqlROWs4[] = $sqlROW4; }

                    $sqlRev5=mysqli_query($conn, "SELECT * FROM budget_reminder WHERE uid='".$_SESSION['uid']."' AND delete_status=0 AND hide_temp='0' order by client_name asc");
										while($sqlROW5=mysqli_fetch_array($sqlRev5)) { $sqlROWs5[] = $sqlROW5; }
									//	d($sqlROWs4); d($getData);
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                    	 <!-- Accordion START -->
                              <div class="panel-group" id="accordion">
                                                              
                                <div class="panel panel-default">
                                  <div class="panel-heading accordion-toggle collapsed firstCls" data-toggle="collapse" data-parent="#accordion" data-target="#collapseTwo">
                                    <h4 class="panel-title">Client Information</h4>
                                  </div>
                                  <div id="collapseTwo" class="panel-collapse collapse">
                                    <div class="panel-body">
                       						<div class="row">		
                                                <div class="col-md-6">          
				                                    <label>Company Name :  </label>
                                                    <input type="text" name="client_name" class="form-control" <?php if(isset($getData['client_name'])) { ?> value="<?php echo $getData['client_name']; ?>" <?php } ?>>                              
                                                 </div>
                                                  <div class="col-md-6">              
                                                    <label>GST No:  </label>
                                                    <input type="text" name="gst" class="form-control" <?php if(isset($getData['gst'])) { ?> value="<?php echo $getData['gst']; ?>" <?php } ?>>                                
                                                  </div>
                                             </div>
                                             <div class="row">		
                                                  <div class="col-md-6">            
                                                    <label>Address Line 1:  </label>
                                                    <input type="text" name="addr1" class="form-control" <?php if(isset($getData['addr1'])) { ?> value="<?php echo $getData['addr1']; ?>" <?php } ?>>                   
                                                   </div>
                                                   <div class="col-md-6">            
                                                     <label>Address Line 2:  </label>
                                                    <input type="text" name="addr2" class="form-control" <?php if(isset($getData['addr2'])) { ?> value="<?php echo $getData['addr2']; ?>" <?php } ?>>                                           
                                                   </div>
                                             </div>
                                    </div>
                                   </div>
                                </div>
                                
                                <div class="panel panel-default">
                                  <div class="panel-heading accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion" data-target="#collapseOne">
                                    <h4 class="panel-title">Link Ad Accounts</h4>
                                  </div>
                                  <div id="collapseOne" class="panel-collapse collapse">
                                    <div class="panel-body">
                                    <div class="row">
                                          <div class="col-md-6">          
                                          <label>Account Category: </label>
                                            <select name="acc_type" id="acc_type" class="form-control selCls">
                                                <option value="">Account Category</option>
                                                <?php foreach($acc_type as $k => $v){  ?>
                                                  <option value="<?php echo $k; ?>" <?php if(isset($getData["acc_type"]) && $k==$getData["acc_type"]) { echo "selected='selected'"; } ?> ><?php echo $v; ?></option>
                                              
                                                <?php } ?>
                                              </select>                              
                                            </div>
                                            <div class="col-md-6">              
                                                    <label>Campaign Name Contains:  </label>
                                                    <input type="text" name="camp_name" class="form-control" placeholder="Project Name[name contains]" <?php if(isset($getData['camp_name'])) { ?> value="<?php echo $getData['camp_name']; ?>" <?php } ?>>                                
                                                  </div>
                                          </div>
                                          
                                    	<div class="row col-md-12">		
                                               
                                                <div class="col-md-6">          
                                      	<label>Facebook Account: </label>
                                        <span  class="cls_em">
                                                    	
                                                        <div data-role="dynamic-fields">
                                                        <?php 
														//if(isset($editData['fb_id']) && $editData['fb_id']!='') 
														//{
                              $aFB_id = array();
                              if(isset($getData['fb_acc'])) {
															  $aFB_id = explode(',',$getData['fb_acc']);
                              }

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
                                             </div>
                                              <div class="col-md-6">              
                                                    
                                                    <label>Google Account :  </label>
                                                    <span  class="cls_em">
                                                    	
                                                        <div data-role="dynamic-fields">
                                                         <?php 
                                                         $aG_id = array();
                                                         if(isset($getData['g_acc'])) {
                                                           $aG_id = explode(',',$getData['g_acc']);
                                                         }
															//$aG_amount = explode(',',$getData['g_stDt']);
															if(count($aG_id)>0) {
																$g=1;
																$g_count = count($aG_id);
																foreach($aG_id as $g_key => $g_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="g_acc[]" id="g_acc" class="form-control selCls">
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
                                               </div>
                                         </div>
                                         <div class="row col-md-12">		
                                               
                                                <div class="col-md-6">          
                                      	<label>LinkedIn Account: </label>
                                        <span  class="cls_em">
                                                    	
                                                        <div data-role="dynamic-fields">
                                                        <?php 
														//if(isset($editData['fb_id']) && $editData['fb_id']!='') 
														//{
                              $aLI_id = array();
                              if(isset($getData['li_acc'])) {
															  $aLI_id = explode(',',$getData['li_acc']);
                              }

															if(count($aLI_id)>0) {
																$f=1;
																$f_count = count($aLI_id);
																foreach($aLI_id as $li_key => $li_val) {
															?>
															<div class="form-inline">
																<div class="form-group col-md-6">
																		<select name="li_acc[]" id="li_acc" class="form-control selCls">
																			   <option value="">Select LinkedIn Account</option>
																				<?php foreach($sqlROWs3 as $sqlROW3){  ?>
                                          <option value="<?php echo $sqlROW3["account_id"]; ?>" <?php if(isset($li_val) && $li_val==$sqlROW3["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW3["name"].' ('.$sqlROW3["account_id"].')'; ?></option>
																			
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
                                                                    <select name="li_acc[]" id="li_acc" class="form-control selCls">
                                                                           <option value="">Select LinkedIn Account</option>
                                                                            <?php foreach($sqlROWs3 as $sqlROW3){  ?>
                                                                            <option value="<?php echo $sqlROW3["account_id"]; ?>"><?php echo $sqlROW3["name"].' ('.$sqlROW3["account_id"].')'; ?></option>
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
                                             </div>
                                              <div class="col-md-6">              
                                                    
                                                    <label>Taboola Account :  </label>
                                                    <span  class="cls_em">
                                                    	
                                                        <div data-role="dynamic-fields">
                                                         <?php 
                                                         $aTa_id = array();
                                                         if(isset($getData['ta_acc'])) {
                                                           $aTa_id = explode(',',$getData['ta_acc']);
                                                         }
															//$aTa_amount = explode(',',$getData['ta_stDt']);
															if(count($aTa_id)>0) {
																$g=1;
																$ta_count = count($aTa_id);
																foreach($aTa_id as $ta_key => $ta_val) {
															?>
                                                    	<div class="form-inline">
                                                        	<div class="form-group  col-md-6">
                                                                    <select name="ta_acc[]" id="ta_acc" class="form-control selCls">
                                                                           <option value="">Select Taboola Account</option>
                                                                            <?php foreach($sqlROWs4 as $sqlROW4) { ?>
                                                                              <option value="<?php echo $sqlROW4["account_id"]; ?>" <?php if(isset($ta_val) && $ta_val==$sqlROW4["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW4["name"].' ('.$sqlROW4["account_id"].')'; ?></option>
                                                                             <?php } ?>
                                                                      </select>
                                                             </div>
                                                                      
                                                               <?php if($g==$ta_count) { ?>                                                         
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
                                                                    <select name="ta_acc[]" id="ta_acc" class="form-control selCls">
                                                                           <option value="">Select Taboola Account</option>
                                                                             <?php foreach($sqlROWs4 as $sqlROW4) { ?>
                                                                            <option value="<?php echo $sqlROW4["account_id"]; ?>"  ><?php echo $sqlROW4["name"].' ('.$sqlROW4["account_id"].')'; ?></option>
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
                                               </div>

                                               <div class="row col-md-12">
                                                <label>Budget: </label><br />  
                                                <div class="form-group  col-md-6">
                                                                                          <select name="bud_tbl" id="bud_tbl" class="form-control selCls">
                                                                                                <option value="">Select Budget Account</option>
                                                                                                  <?php foreach($sqlROWs5 as $sqlROW5) { ?>
                                                                                                  <option value="<?php echo $sqlROW5["tbl_id"]; ?>" <?php if($getData['bud_tbl']==$sqlROW5["tbl_id"]) { echo 'selected'; } ?> ><?php echo $sqlROW5["client_name"]; ?></option>
                                                                                                  <?php } ?>
                                                                                            </select>
                                                                          </div>    
                                                <br>
</div> 
                                         </div>
                                    </div>
                                  </div>
                                </div>
                                
                                <div class="panel panel-default">
                                  <div class="panel-heading accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion" data-target="#collapseThree">
                                    <h4 class="panel-title">Ads Services</h4>
                        
                                  </div>
                                  <div id="collapseThree" class="panel-collapse collapse">
                                    <div class="panel-body">                                    	
                                    	<div class="row">
                                        	  <div class="col-md-2">          
                                      			 <label>Ads Budget (Cur. month)</label>
                                                    <input type="text" name="ads_budget" class="form-control " <?php if(isset($getData['ads_budget'])) { ?> value="<?php echo $getData['ads_budget']; ?>" <?php } ?>>
                                                    <label class="cls_g"><input type="checkbox" name="ads_budget_i" <?php if(isset($getData['ads_budget_i']) && $getData['ads_budget_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label> 
                                             </div>
                                             <div class="col-md-2"> 
                                             <label>Adj. Amount (Last month)</label>
                                                    <input type="text" name="ads_budget2" class="form-control " <?php if(isset($getData['ads_budget2'])) { ?> value="<?php echo $getData['ads_budget2']; ?>" <?php } ?>>
                                                    <label class="cls_g"><input type="checkbox" name="ads_budget2_i" <?php if(isset($getData['ads_budget2_i']) && $getData['ads_budget2_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label> 
                                             </div>
                                             <div class="col-md-2">          
                                      				<label>FB Ads Management Fees (in %)</label>
                                                    <input type="text" name="fb_fee" class="form-control " <?php if(isset($getData['fb_fee'])) { ?> value="<?php echo $getData['fb_fee']; ?>" <?php } ?>>
                                                    <label class="cls_g"><input type="checkbox" name="fb_fee_i" <?php if(isset($getData['fb_fee_i']) && $getData['fb_fee_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label> 
                                                     <label class="cls_g"><input type="checkbox" name="pm_i1" <?php if(isset($getData['pm_i']) && $getData['pm_i']=='on') { ?> checked <?php } ?>> Perform. Market.</label> 
                                             </div>
                                            <div class="col-md-3">              
                                                    <label>Google Ads Management Fees (in %)</label>
                                                    <input type="text" name="g_fee" class="form-control" <?php if(isset($getData['g_fee'])) { ?> value="<?php echo $getData['g_fee']; ?>" <?php } ?>>                      
                                                    <label class="cls_g"><input type="checkbox" name="g_fee_i" <?php if(isset($getData['g_fee_i']) && $getData['g_fee_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>       
                                                    <label class="cls_g"><input type="checkbox" name="pm_i2" <?php if(isset($getData['pm_i']) && $getData['pm_i']=='on') { ?> checked <?php } ?>> Perform. Market.</label>                        
                                            </div>
                                            <div class="col-md-3">              
                                                    <label>Linkedin Ads Management Fees (in %)</label>
                                                    <input type="text" name="li_fee" class="form-control" <?php if(isset($getData['li_fee'])) { ?> value="<?php echo $getData['li_fee']; ?>" <?php } ?>>                      
                                                    <label class="cls_g"><input type="checkbox" name="li_fee_i" <?php if(isset($getData['li_fee_i']) && $getData['li_fee_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>       
                                                    <label class="cls_g"><input type="checkbox" name="pm_i2" <?php if(isset($getData['pm_i']) && $getData['pm_i']=='on') { ?> checked <?php } ?>> Perform. Market.</label>                        
                                            </div>
                                         </div>
                                         <div class="row">
                                                <div class="col-md-3">              
                                                    <label>Include FB Spend</label>
                                                    <label class="cls_g"><input type="checkbox" name="fb_spend_inc" <?php if(isset($getData['fb_spend_inc']) && $getData['fb_spend_inc']=='on') { ?> checked <?php } ?>> Yes</label>    
                                                    <label class="cls_g"><input type="checkbox" name="fb_spend_inc_i" <?php if(isset($getData['fb_spend_inc_i']) && $getData['fb_spend_inc_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>                          
                                                </div>
                                                <div class="col-md-3">              
                                                    <label>Include Google Spend</label>
                                                    <label class="cls_g"><input type="checkbox" name="g_spend_inc" <?php if(isset($getData['g_spend_inc']) && $getData['g_spend_inc']=='on') { ?> checked <?php } ?>> Yes</label>    
                                                    <label class="cls_g"><input type="checkbox" name="g_spend_inc_i" <?php if(isset($getData['g_spend_inc_i']) && $getData['g_spend_inc_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>                          
                                                </div>
                                                <div class="col-md-3">              
                                                    <label>Include LinkedIn Spend</label>
                                                    <label class="cls_g"><input type="checkbox" name="li_spend_inc" <?php if(isset($getData['li_spend_inc']) && $getData['li_spend_inc']=='on') { ?> checked <?php } ?>> Yes</label>    
                                                    <label class="cls_g"><input type="checkbox" name="li_spend_inc_i" <?php if(isset($getData['li_spend_inc_i']) && $getData['li_spend_inc_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>                          
                                                </div>
                                            <!--<div class="col-md-4">     
                                              <label>Digital & Social Media Marketing -Strategy & Execution Fee:  </label>
                                                    <input type="text" name="byt_fee" class="form-control numOnly" <?php if(isset($getData['byt_fee'])) { ?> value="<?php echo $getData['byt_fee']; ?>" <?php } ?>>  
                                                    <label class="cls_g"><input type="checkbox" name="byt_fee_c" <?php if(isset($getData['byt_fee_c']) && $getData['byt_fee_c']=='on') { ?> checked <?php } ?>> Current</label>
                                                    <label class="cls_g"><input type="checkbox" name="byt_fee_i" <?php if(isset($getData['byt_fee_i']) && $getData['byt_fee_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>
                                             </div>
                                             <div class="col-md-4">          
                                      				<label>Ads Management Fees </label>
                                                    <input type="text" name="ads_mgnt" class="form-control numOnly" <?php if(isset($getData['ads_mgnt'])) { ?> value="<?php echo $getData['ads_mgnt']; ?>" <?php } ?>>
                                                    <label class="cls_g"><input type="checkbox" name="ads_mgnt_c" <?php if(isset($getData['ads_mgnt_c']) && $getData['ads_mgnt_c']=='on') { ?> checked <?php } ?>> Current</label>
                                                    <label class="cls_g"><input type="checkbox" name="ads_mgnt_i" <?php if(isset($getData['ads_mgnt_i']) && $getData['ads_mgnt_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>
                                             </div>
                                              <div class="col-md-4">              
                                                    <label>Facebook & Google Ads Spend  </label>
                                                    <input type="text" name="ads_spend" class="form-control numOnly" <?php if(isset($getData['ads_spend'])) { ?> value="<?php echo $getData['ads_spend']; ?>" <?php } ?>>                  
                                                    <label class="cls_g"><input type="checkbox" name="ads_spend_c" <?php if(isset($getData['ads_spend_c']) && $getData['ads_spend_c']=='on') { ?> checked <?php } ?>> Current</label>
                                                    <label class="cls_g"><input type="checkbox" name="ads_spend_i" <?php if(isset($getData['ads_spend_i']) && $getData['ads_spend_i']=='on') { ?> checked <?php } ?>> Invoice - 1%</label>                                 
                                               </div>-->
                                         </div>
                                    </div>
                                  </div>
                                </div>
                                
                                
                                
                                 <div class="panel panel-default">
                                  <div class="panel-heading accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion" data-target="#collapseFive">
                                    <h4 class="panel-title">Tax</h4>                        
                                  </div>
                                  <div id="collapseFive" class="panel-collapse collapse">
                                    <div class="panel-body">    
                                    	<div class="row">
                                        	<div class="col-md-4">      
                                    				<label ><input type="radio" name="igst_gst" value="0" id="rad_em1" <?php if(isset($getData['igst_gst']) && $getData['igst_gst']==0) { ?> checked <?php } ?> > CGST / SGST </label> &nbsp;&nbsp;&nbsp;
                                                    <label ><input type="radio" name="igst_gst"  value="1"  id="rad_em2" <?php if(isset($getData['igst_gst']) && $getData['igst_gst']==1) { ?> checked <?php } ?>> IGST </label><br />
                                              </div>
                                        </div>                                            	
                                    	<div class="row cls_em1">
                                        	<div class="col-md-4">          
                                      				<label>IGST </label>
                                                    <input type="text" name="igst" class="form-control numOnly" <?php if(isset($getData['igst'])) { ?> value="<?php echo $getData['igst']; ?>" <?php } ?>>  
                                             </div>
                                            </div>
                                        <div class="row cls_em2">
                                             <div class="col-md-4">          
                                      				<label>CGST </label>
                                                    <input type="text" name="cgst" class="form-control numOnly" <?php if(isset($getData['cgst'])) { ?> value="<?php echo $getData['cgst']; ?>" <?php } ?>>
                                             </div>
                                              <div class="col-md-4">              
                                                    <label>SGST </label>
                                                    <input type="text" name="sgst" class="form-control numOnly" <?php if(isset($getData['sgst'])) { ?> value="<?php echo $getData['sgst']; ?>" <?php } ?>>                                                   
                                               </div>
                                         </div>
                                         <div class="row">
                                        	<div class="col-md-12">          
                                      				<label>Show Tax Note (for Inovie 1%) </label>
                                                    <input type="text" name="tax_note2" class="form-control" <?php if(isset($getData['tax_note2'])) { ?> value="<?php echo $getData['tax_note2']; ?>" <?php } ?>>
                                             </div>
                                        </div> 
                                        <div class="row">
                                        	<div class="col-md-12">          
                                      				<label>Show Tax Note (for Inovie 10%) </label>
                                                    <input type="text" name="tax_note" class="form-control" <?php if(isset($getData['tax_note'])) { ?> value="<?php echo $getData['tax_note']; ?>" <?php } ?>>
                                             </div>
                                         </div>  
                                    </div>
                                  </div>
                                </div>
                                
                                
                                <div class="panel panel-default">
                                  <div class="panel-heading accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion" data-target="#collapseSx">
                                    <h4 class="panel-title">Email Ids</h4>                        
                                  </div>
                                  <div id="collapseSx" class="panel-collapse collapse">
                                    <div class="panel-body">    
                                      <div class="row ">
                                             <div class="col-md-4">          
                                      				<label>Contact Name </label>
                                                   <input type="text" name="cont_name" class="form-control" <?php if(isset($getData['cont_name'])) { ?> value="<?php echo $getData['cont_name']; ?>" <?php } ?>>  
                                             </div>
                                              <div class="col-md-6">              
                                                    <label>Email Ids </label>
                                                   <input type="text" name="cont_email" class="form-control" <?php if(isset($getData['cont_email'])) { ?> value="<?php echo $getData['cont_email']; ?>" <?php } ?>>                                                  
                                               </div>
                                         </div>
                                         <div class="row ">
                                              <div class="col-md-6">              
                                                    <label>Reports Ids </label>
                                                   <input type="text" name="email_ids" class="form-control" <?php if(isset($getData['email_ids'])) { ?> value="<?php echo $getData['email_ids']; ?>" <?php } ?>>                                                  
                                               </div>
                                         </div>
                                    </div>
                                  </div>
                                </div>
                                
                                
                              </div>
                              <!-- Accordion END -->
                              <div class="col-md-3">          
                                      				<label>Googlesheet Tab </label>
                                                    <input type="text" name="gsheet" class="form-control" <?php if(isset($getData['gsheet'])) { ?> value="<?php echo $getData['gsheet']; ?>" <?php } ?>>  
                                             
                              </div>
                              <span  class="cls_em col-md-12"><br>
                                                    <label>Custom Label</label>
                                                    </span>
                                                    <div class="container">
                                                    	<?php
                                                        //print_r($getData);
														$k=1;
														if(isset($getData['ext_value'])) 
														{
															$extLab = unserialize($getData['ext_label']);
															$extVal = unserialize($getData['ext_value']);
															$extInv = unserialize($getData['ext_inv']);
															$extLab_count = count($extVal);
														} else {
															$extLab = $extVal = $extInv = array(''); 
															$extLab_count = 1; 
														}
														
														if(count($extLab)>0) {
														foreach($extLab as $key => $val) {
														?>
                                                        <div class="row <?php if($k!=$extLab_count) { ?> not-first<?php } ?>">
                                                            <div class="col-md-12">
                                                                <div data-role="dynamic-fields">
                                                                    <div class="form-inline">
                                                                    	<div class="form-group">
                                                                            <label class="sr-only" for="field-value">Label</label>
                                                                            <input type="text" name="ext_label[]"  class="form-control" id="field-value" placeholder="Label" size="80" value="<?php echo $extLab[$key]; ?>">
                                                                        </div>
                                                                       
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group">&#8377;
                                                                        <input class="form-control note" class="note" id="p_note" name="ext_value[]" placeholder="Amount" type="text" value="<?php echo $extVal[$key]; ?>"> 
                                                                        </div>
                                                                        <div class="form-group">
                                                                        <select name="ext_inv[]" id="ext_inv" class="form-control selCls">
                                                                            <option value="1" <?php if(isset($extInv[$key]) && $extInv[$key]==1) { ?> selected <?php } ?>>Invoice 10%</option>
                                                                            <option value="2" <?php if(isset($extInv[$key]) && $extInv[$key]==2) { ?> selected <?php } ?>>Invoice - 1%</option>
                                                                            <option value="11" <?php if(isset($extInv[$key]) && $extInv[$key]==11) { ?> selected <?php } ?>>Invoice 10% (E)</option>
                                                                            <option value="22" <?php if(isset($extInv[$key]) && $extInv[$key]==22) { ?> selected <?php } ?>>Invoice - 1% (E)</option>
                                                                            <option value="3" <?php if(isset($extInv[$key]) && $extInv[$key]==3) { ?> selected <?php } ?>>PI - 10%</option>
                                                                            <option value="4" <?php if(isset($extInv[$key]) && $extInv[$key]==4) { ?> selected <?php } ?>>PI - 1%</option>
                                                                            <option value="7" <?php if(isset($extInv[$key]) && $extInv[$key]==7) { ?> selected <?php } ?>>PI - No GST - 1%</option>
                                                                            <option value="8" <?php if(isset($extInv[$key]) && $extInv[$key]==8) { ?> selected <?php } ?>>PI - No GST - 10%</option>
                                                                            <option value="5" <?php if(isset($extInv[$key]) && $extInv[$key]==5) { ?> selected <?php } ?>>CI - 10%</option>
                                                                            <option value="6" <?php if(isset($extInv[$key]) && $extInv[$key]==6) { ?> selected <?php } ?>>CI - 1%</option>
                                                                        </select>

                                                                        <!--<input  type="text" name="ext_inv[]"  class="form-control" id="field-value" placeholder="1 = Invoice-1, 2 = Invoice-2" size="20" value="<?php echo $extInv[$key]; ?>" >-->
                                                                        </div>
                                                                        <?php if($k==$extLab_count) { ?>
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
                                                                            <label class="sr-only" for="field-value">Label</label>
                                                                            <input type="text" name="ext_label[]"  class="form-control" id="field-value" placeholder="Label" size="80" >
                                                                        </div>
                                                                       
                                                                        <span class="fa fa-paper-plane"> - </span>
                                                                        <div class="form-group">&#8377;
                                                                        <input class="form-control note" class="note" id="p_note" name="ext_value[]" placeholder="Amount" type="text" > 
                                                                        </div>
                                                                        <div class="form-group">
                                                                        <select name="ext_inv[]" id="ext_inv" class="form-control selCls">
                                                                            <option value="1">Invoice 10%</option>
                                                                            <option value="2">Invoice - 1%</option>
                                                                            <option value="11">Invoice 10% (E)</option>
                                                                            <option value="22">Invoice - 1% (E)</option>
                                                                            <option value="3">PI - 10%</option>
                                                                            <option value="4">PI - 1%</option>
                                                                            <option value="7">PI - No GST - 1%</option>
                                                                            <option value="8">PI - No GST - 10%</option>
                                                                            <option value="5">CI - 10%</option>
                                                                            <option value="6">CI - 1%</option>
                                                                        </select>
                                                                        <!--<input  type="text" name="ext_inv[]"  class="form-control" id="field-value" placeholder="1 = Invoice-1, 2 = Invoice-2" size="20" value="1"  >-->
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
                                        <a   class="btn btn-default"  onclick="parent.$('#iframeModal').modal('hide');">  Close</a>  
                                <input type="submit" name="submit" value="Submit" class="btn btn-success">     
                                    </div>
                                </div>
                                </form>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
					<!-- END CONTENT -->
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<script>
 $(document).ready(function () {
	 $('.firstCls').click();
	 <?php if(isset($getData['igst_gst']) && $getData['igst_gst']==0) { ?> 
	 	$( ".cls_em1" ).hide();	
	 <?php  } else { ?>
	 	$( ".cls_em2" ).hide();
	<?php  } ?>
	
	//if($( "#fb_acc" ).val()!='') { $( "#cls_f" ).show(); }
	$('input:radio[name="igst_gst"]').change(
    function(){
        if ($(this).is(':checked') && $(this).val() == 0) {
            // append goes here
			//alert(1);
			 $( ".cls_em2" ).show();	$( ".cls_em1" ).hide();
        } else {
			//alert(2);
			 $( ".cls_em2" ).hide();	 $( ".cls_em1" ).show();
		}
	});
	
	$(".numOnly").keypress(function (e) {
		 //if the letter is not digit then display error and don't type anything
		 if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			//display error message
			$("#errmsg").html("Digits Only").show().fadeOut("slow");
				   return false;
		}
	});
});	

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
</script>