<?php session_start();
if(!isset($_SESSION['logged'])) {
	
	echo "<script>window.location = 'login.php';</script>";
	exit();
} 
include '../db.php'; 
include 'config.php';
include 'functions.php';
function loopAdRep($url) {
    global $output;
    $requests = file_get_contents_curl($url);
    $fb_response = json_decode($requests,true);
    if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = array_merge($output, $fb_response['data']);
    } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
        $output = $fb_response['data']; 
    }
    
    if(isset($fb_response['paging']['next'])) {
        loopAdRep($fb_response['paging']['next']);
    } else { 
        return $output['data'] = $output; 
    }
}
?>
<html>
<head>
  <title>AdRescue - AdRule</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/all.css">
<!------ Include the above in your HEAD tag ---------->
</head>
<style>
    .icon { margin-left:10px; cursor: pointer; }
    article { width: 90% !important; }
    a { text-decoration: none; }
    .remove-me { color: red; border: 1px solid red; background: white !important; }
    button#add-more { color: #2db937; border: 1px solid #2db937; background: white !important; }
    .icon {     background: white !important; color: grey; }
</style>
<body>
<?php 
error_reporting(E_ALL); ini_set('display_errors', '1');

if(isset($_POST['submit'])) {
    
    $cust_con_check = $cust_conv_ids = '';
    if(isset($_POST['cust_con_check']) && count($_POST['cust_con_check'])>0) { $cust_con_check = serialize($_POST['cust_con_check']); }
    if(isset($_POST['cust_con']) && count($_POST['cust_con'])>0) { $cust_conv_ids = serialize($_POST['cust_con']); }
    
    if(isset($_GET['id'])) {
        $cirSql = "UPDATE ad_rules SET 
        rule_name='".mysqli_real_escape_string($conn, $_POST['rule_name'])."', 
        ad_acc='".mysqli_real_escape_string($conn, $_POST['ad_acc'])."', 
        run_script='".mysqli_real_escape_string($conn, $_POST['run_script'])."', 
        check_rep='".mysqli_real_escape_string($conn,  $_POST['check_rep'])."',
        acc_type='".mysqli_real_escape_string($conn,  $_POST['acc_type'])."',
        ad_level='".mysqli_real_escape_string($conn,  serialize($_POST['ad_level']))."',
        cust_conv_check='".mysqli_real_escape_string($conn, $cust_con_check)."',
        cust_conv_ids='".mysqli_real_escape_string($conn, $cust_conv_ids)."',
        rule_match='".mysqli_real_escape_string($conn,  serialize($_POST['rule_match']))."',
        name_cont1='".mysqli_real_escape_string($conn,  serialize($_POST['name_cont1']))."',
        name_cont2='".mysqli_real_escape_string($conn,  serialize($_POST['name_cont2']))."',
        act_metrics='".mysqli_real_escape_string($conn,  serialize($_POST['act_metrics']))."',
        act_oper='".mysqli_real_escape_string($conn,  serialize($_POST['act_oper']))."',
        act_val='".mysqli_real_escape_string($conn,  serialize($_POST['act_val']))."',
        email_alert='".mysqli_real_escape_string($conn, $_POST['email_alert'])."', 
        wa_alert='".mysqli_real_escape_string($conn, $_POST['wa_alert'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
        mysqli_query($conn, $cirSql) or die(mysqli_error()); 
        $lastId = $_GET['id'];
    } else {
         $cirSql = "INSERT INTO ad_rules (uId, rule_name, ad_acc, run_script, check_rep, acc_type, ad_level, cust_conv_check, cust_conv_ids, rule_match, name_cont1, name_cont2, act_metrics, act_oper, act_val, email_alert, wa_alert, created, updated) VALUES (2, '".mysqli_real_escape_string($conn, $_POST['rule_name'])."', '".mysqli_real_escape_string($conn, $_POST['ad_acc'])."', '".mysqli_real_escape_string($conn, $_POST['run_script'])."', '".mysqli_real_escape_string($conn, $_POST['check_rep'])."', '".mysqli_real_escape_string($conn, $_POST['acc_type'])."', '".mysqli_real_escape_string($conn, serialize($_POST['ad_level']))."', '".mysqli_real_escape_string($conn, $cust_con_check)."', '".mysqli_real_escape_string($conn, $cust_conv_ids)."', '".mysqli_real_escape_string($conn, serialize($_POST['rule_match']))."', '".mysqli_real_escape_string($conn, serialize($_POST['name_cont1']))."', '".mysqli_real_escape_string($conn, serialize($_POST['name_cont2']))."', '".mysqli_real_escape_string($conn, serialize($_POST['act_metrics']))."', '".mysqli_real_escape_string($conn, serialize($_POST['act_oper']))."', '".mysqli_real_escape_string($conn, serialize($_POST['act_val']))."', '".mysqli_real_escape_string($conn, $_POST['email_alert'])."', '".mysqli_real_escape_string($conn, $_POST['wa_alert'])."', now(), now());"; 
         mysqli_query($conn, $cirSql) or die(mysqli_error());
         $lastId = mysqli_insert_id($conn);
    } 
    echo "<script>window.location = 'index.php';</script>";
	exit();
}
?>
<?php 
$editData = $output = $cust_conv_list = array();

if(isset($_GET['id'])) {
    //echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
    $sqlD=mysqli_query($conn, "SELECT rule_name, ad_acc, run_script, check_rep, acc_type, ad_level, cust_conv_check, cust_conv_ids, rule_match, name_cont1, name_cont2, act_metrics, act_oper, act_val, email_alert, wa_alert FROM ad_rules WHERE tbl_id='".$_GET['id']."'");
    while($Rdata=mysqli_fetch_array($sqlD)) {
        $editData = $Rdata;
    }
    if(isset($editData['ad_acc']) && $editData['ad_acc']!=''){
        $accId = $editData['ad_acc'];
        $url = "https://graph.facebook.com/".$api_ver."/act_".$accId."/customconversions?fields=name,default_conversion_value,is_archived,is_unavailable,custom_event_type,rule&filtering=[{'field':'is_archived','operator':'EQUAL','value':false}]&access_token=".$access_token."&limit=1750";
                //$req = file_get_contents_curl($url);
                
        
        loopAdRep($url);  
        $cust_conv_list = $output;
    }
}
//d($cust_conv_list);
$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='2' and status='0' order by name asc");
while($Rdata=mysqli_fetch_array($sqlRev)) {
    $ad_acc[$Rdata['account_id']] = $Rdata['name'];
}
//d($ad_acc);

?>                            
<div class="container">
<br><br>
<div class="card">
<article class="card-body mx-auto">
<div class="float-right"><a type="button" class="btn btn btn btn-outline-success" href="index.php">Home</a>  <a type="button" class="btn btn btn-outline-danger ml-2" href="logout.php">Logout</a></div>
<div class="clearfix"></div>

<form method="post" action="" class="mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="row form col-md-12">
                
                
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="rule_name">Rule Name</label>
                            <input type="text" name="rule_name" class="form-control" <?php if(isset($editData['rule_name'])) { ?> value="<?php echo $editData['rule_name']; ?>" <?php } ?> required />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="ad_acc">Ad Account</label>
                            <select name="ad_acc" class="form-control" required>
                                    <option value="">Ad Account</option>
                                    <?php foreach($ad_acc as $k => $v) {   ?>
                                        <option value="<?php echo $k; ?>" <?php if(isset($editData['ad_acc']) && $editData['ad_acc']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                    <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="run_script">Trigger</label>
                            <select name="run_script" class="form-control" required>
                                                            <option value="1">Every</option>
                                                            <?php foreach($check_every as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>" <?php if(isset($editData['run_script']) && $editData['run_script']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                            <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="check_rep">Check Report</label>
                            <select name="check_rep" class="form-control" required>
                                                        <?php foreach($check_rep as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>" <?php if(isset($editData['check_rep']) && $editData['check_rep']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                        <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="acc_type">Acc Type</label>
                            <select name="acc_type" class="form-control" required>
                                                        <option value="">Select</option>
                                                        <?php foreach($acc_type as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>" <?php if(isset($editData['acc_type']) && $editData['acc_type']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                        <?php } ?>
                            </select>
                        </div>
                    </div>


                
            </div>
        </div>
    </div>

                
                <!-- Button -->
                <div class="form-group">
                <div class="col-md-4">
                    <button id="add-more" name="add-more" class="btn btn-success btn-sm btn-blcok">Add Rule</button>
                </div>
                </div>
                <div class="form">
    
                <div id="field"></div>                                        
                    <?php $nxt = 0;
                    if(isset($editData['ad_level']) && count(unserialize($editData['ad_level']))>0) {  
                    $cust_conv_check_v =  $cust_conv_ids_v = '';
                    $ad_level_v = unserialize($editData['ad_level']);
                    $rule_match_v = unserialize($editData['rule_match']);
                    $name_cont1_v = unserialize($editData['name_cont1']);
                    $name_cont2_v = unserialize($editData['name_cont2']);
                    $act_metrics_v = unserialize($editData['act_metrics']);
                    $act_oper_v = unserialize($editData['act_oper']);
                    $act_val_v = unserialize($editData['act_val']);
                    if($editData['cust_conv_check']!='') { $cust_conv_check_v = unserialize($editData['cust_conv_check']); }
                    if($editData['cust_conv_ids']!='') { $cust_conv_ids_v = unserialize($editData['cust_conv_ids']); }
                    //d($act_metrics_v); d($act_val_v);
                    foreach($ad_level_v as $ad_k => $ad_v) { 
                    ?>
                    <div id="field">
                    <div class="ad-level<?php echo $nxt; ?>  col-md-12">
                        <div class="row form">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <select name="ad_level[<?php echo $nxt; ?>]" class="form-control" required>
                                                                <option value="">Ad Level</option>
                                                                <?php foreach($ad_level as $k => $v) {   ?>
                                                                        <option value="<?php echo $k; ?>" <?php if(isset($ad_v) && $ad_v==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                                <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <select name="rule_match[<?php echo $nxt; ?>]" class="form-control" required>
                                                                <?php foreach($rule_match as $k => $v) {   ?>
                                                                        <option value="<?php echo $k; ?>" <?php if(isset($rule_match_v[$ad_k]) && $rule_match_v[$ad_k]==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                                <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text" name="name_cont1[<?php echo $nxt; ?>]" class="form-control" placeholder="Campaign Name Contains" <?php if(isset($name_cont1_v[$ad_k])) { ?> value="<?php echo $name_cont1_v[$ad_k]; ?>" <?php } ?> />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text" name="name_cont2[<?php echo $nxt; ?>]" class="form-control" placeholder="Campaign Name Not Contains" <?php if(isset($name_cont2_v[$ad_k])) { ?> value="<?php echo $name_cont2_v[$ad_k]; ?>" <?php } ?> />
                                    </div>
                                </div>
                        </div>
                        
                            
                    </div> <!-- end ad-level -->
                    <?php foreach($act_metrics_v[$ad_k] as $ad_k2 => $ad_v2) { ?>
                    <div id="field<?php echo $nxt; ?>">
                            <div data-role="dynamic-fields">
                                    <div class="form-inline mb-3 ml-3">
                                    <div class="form-group input-group">
                                        
                                        <select class="custom-select input-group-prepend" name="act_metrics[<?php echo $nxt; ?>][]">
                                            <option value="">Select Metrics</option>
                                            <?php foreach($metrics as $k => $v) {   ?>
                                              <option value="<?php echo $k; ?>" <?php if(isset($act_metrics_v[$ad_k][$ad_k2]) && $act_metrics_v[$ad_k][$ad_k2]==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                        <select name="act_oper[<?php echo $nxt; ?>][]" class="form-control" require>
                                            <option value="">Select Operator</option>
                                            <?php foreach($operation as $k => $v) {   ?>
                                              <option value="<?php echo $k; ?>" <?php if(isset($act_oper_v[$ad_k][$ad_k2]) && $act_oper_v[$ad_k][$ad_k2]==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                        <input type="text" name="act_val[<?php echo $nxt; ?>][]" placeholder="Value" class="form-control" <?php if(isset($act_val_v[$ad_k][$ad_k2])) { ?> value="<?php echo $act_val_v[$ad_k][$ad_k2]; ?>" <?php } ?> />
                                      
                                    </div>
                                    <div class="form-group input-group">
                                    <div class="input-group" data-role="add">
                                            <span class="input-group-text icon"> <i class="fa fa-plus"></i> </span>
                                        </div>
                                        <div class="input-group" data-role="remove">
                                            <span class="input-group-text icon"> <i class="fa fa-trash"></i> </span>
                                        </div>
                                    </div>                 
                                    </div>  
                            </div>   <!-- dynamic-fields -->
                            
                            <?php } ?>
                    <div class="col-md-2 form-inline  mb-3">
                            <div class="form-group input-group">
                                    <label> Cust. Conversion 
                                    <input type="checkbox"  class="form-control cust_con_check ml-3 mr-3" name="cust_con_check[<?php echo $nxt; ?>]" value="<?php echo $nxt; ?>" <?php if(isset($cust_conv_check_v[$ad_k])) { ?> checked <?php } ?> /></label>
                                    <span class="cust_con_sel_<?php echo $nxt; ?> cust-conv-sel">
                                        <?php
                                        if(count($cust_conv_list['data'])>0 && isset($cust_conv_ids_v[$ad_k])){ ?>
                                            <select name="cust_con[<?php echo $nxt; ?>]" class="form-control">
                                                <option value="">Select Conversion</option>
                                                <?php foreach($cust_conv_list['data'] as $k => $v) {   ?>
                                                    <option value="<?php echo $v['id']; ?>" <?php if(isset($cust_conv_ids_v[$ad_k]) && $cust_conv_ids_v[$ad_k]==$v['id']) { echo "selected='selected'"; } ?>><?php echo $v['name']; ?></option>
                                                <?php } ?>
                                            </select> <?php
                                        }
                                        ?>
                                    </span>
                            </div>
                            <div class="form-group input-group"></div>                 
                    </div>  
                    </div> <!-- field0 -->
                    
                </div> <!-- field -->
                <? if($nxt<(count($ad_level_v)-1)) { ?>
                <button id="remove<?php echo $nxt; ?>" class="btn btn-default btn-sm remove-me ml-3">Delete Rule</button>
                <hr class="hr<?php echo $nxt; ?>">
                <? } ?>
                <? 
                $nxt++; } } else { 
                    $nxt = 1;
                ?>
                    <div id="field">
                    <div class="ad-level0  col-md-12">
                        <div class="row form">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <select name="ad_level[0]" class="form-control" required>
                                                                <option value="">Ad Level</option>
                                                                <?php foreach($ad_level as $k => $v) {   ?>
                                                                        <option value="<?php echo $k; ?>" <?php if(isset($editData['acc_level']) && $editData['acc_level']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                                <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <select name="rule_match[0]" class="form-control" required>
                                                                <option value="1">Match All</option>
                                                                <option value="2">Match Any</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text" name="name_cont1[0]" class="form-control" placeholder="Campaign Name Contains" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text" name="name_cont2[0]" class="form-control" placeholder="Campaign Name Not Contains" />
                                    </div>
                                </div>
                        </div>
                        
                            
                    </div> <!-- end ad-level -->
                    
                    <div id="field0">
                            <div data-role="dynamic-fields">
                                    <div class="form-inline mb-3 ml-3">
                                    <div class="form-group input-group">
                                        
                                        <select class="custom-select input-group-prepend" name="act_metrics[0][]">
                                            <option value="">Select Metrics</option>
                                            <?php foreach($metrics as $k => $v) {   ?>
                                              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                        <select name="act_oper[0][]" class="form-control" require>
                                            <option value="">Select Operator</option>
                                            <?php foreach($operation as $k => $v) {   ?>
                                              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                        <input type="text" name="act_val[0][]" placeholder="Value" class="form-control" />
                                      
                                    </div>
                                    <div class="form-group input-group">
                                    <div class="input-group" data-role="add">
                                            <span class="input-group-text icon"> <i class="fa fa-plus"></i> </span>
                                        </div>
                                        <div class="input-group" data-role="remove">
                                            <span class="input-group-text icon"> <i class="fa fa-trash"></i> </span>
                                        </div>
                                    </div>                 
                                    </div>  
                            </div>   <!-- dynamic-fields -->
                            <div class="col-md-2 form-inline mb-3">
                                <div class="form-group input-group">
                                        <label> Cust. Conversion 
                                        <input type="checkbox"  class="form-control cust_con_check ml-3 mr-3" name="cust_con_check[0]" value="0" /></label>
                                        <span class="cust_con_sel_0 cust-conv-sel"></span>
                                </div>
                                <div class="form-group input-group"></div>                 
                            </div>
                    </div> <!-- field0 -->
                </div> <!-- field -->
                <? } ?>
                <hr class="mt-3">
                <div class="col-md-12 ">
                         <div class="row">
                         <div class="col-md-6"><input type="text" name="email_alert" class="form-control" placeholder="Email Alert" <?php if(isset($editData['email_alert'])) { ?> value="<?php echo $editData['email_alert']; ?>" <?php } ?> /></div>
                         <div class="col-md-6"><input type="text" name="wa_alert" class="form-control" placeholder="Whatsapp Alert" <?php if(isset($editData['wa_alert'])) { ?> value="<?php echo $editData['wa_alert']; ?>" <?php } ?> /></div>
                        </div>
                </div> 
                <hr class="mt-3">
                <div class="col-md-12 ">
                         <div class="row">
                         <div class="col-md-6"><a href="index.php" class="btn btn-secondary btn-block">Cancel</a></div>
                         <div class="col-md-6"><input type="submit" name="submit" value="Submit" class="btn btn-primary btn-block" /></div>
                        </div>
                </div>                          
    </form>
</article>
</div> <!-- card.// -->

</div> 
<!--container end.//-->

     


    </body>

</html>	

<script>
 $(document).ready(function () 
 {   
    $("select[name=ad_acc]").change(function() { 
        $('.cust-conv-sel').hide();
        $('.cust_con_check').prop('checked', false);
    });

    $('body').on('change', '.cust_con_check',function (e) {
//$('.cust_con_check').change(function() {
        var rule_id = $(this).val();
        var act = $("select[name=ad_acc]").val();
        if(act!='') {
            if($(this).is(":checked")) {
                $('.cust_con_sel_'+rule_id).show();
                $('.cust_con_sel_'+rule_id).html('<img src="loading-bar2.gif" />');
                $.ajax({
                    url: "cust-conv-ajax.php",
                    type: "post",
                    data: {act: act, rule_id: rule_id},
                    success: function (response) {
                        //alert(act);
                        $('.cust_con_sel_'+rule_id).show();
                        $('.cust_con_sel_'+rule_id).html(response);
                    
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('.cust_con_sel_'+rule_id).prop('checked', false);
                        alert(textStatus, errorThrown);
                    }
                });
            } else {
                $('.cust_con_sel_'+rule_id).hide();
            }    
        } else {
            alert('Select Ad account!');
            $(this).prop('checked', false);
            $('.cust_con_sel_'+rule_id).hide();
        }
        
    });
});

$(".remove-me").click(function(){
    
    var fieldNum = this.id.charAt(this.id.length-1);
                        var fieldID = "#field" + fieldNum;
                        $(this).remove();
                        $(fieldID).remove();
                        $('.ad-level' + (fieldNum) + '').remove();
                        $('.hr' + (fieldNum) + '').remove();
                        //return false;
});
    $(document).ready(function () 
    {
        var next = <?php echo ($nxt-1); ?>;
        $("#add-more").click(function(e){ //alert(next);
                e.preventDefault();
                var addto = "#field" + next;
                var addRemove = "#field" + (next);
                next = next + 1;
                var newIn = ' <div id="field'+ next +'" name="field'+ next +'"><div class="ad-level'+ next +'  col-md-12 mt-2"><div class="row form"><div class="col-md-2"><div class="form-group"><select name="ad_level['+next+']" class="form-control" required><option value="">Ad Level</option><?php foreach($ad_level as $k => $v) {   ?><option value="<?php echo $k; ?>" <?php if(isset($editData['acc_level']) && $editData['acc_level']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option><?php } ?></select></div></div><div class="col-md-2"><div class="form-group"><select name="rule_match['+next+']" class="form-control" required><option value="1">Match All</option><option value="2">Match Any</option></select></div></div><div class="col-md-4"><div class="form-group"><input type="text" name="name_cont1['+next+']" class="form-control" placeholder="Campaign Name Contains" /></div></div><div class="col-md-4"><div class="form-group"><input type="text" name="name_cont2['+next+']" class="form-control" placeholder="Campaign Name Not Contains" /></div></div></div></div> <!-- end ad-level --><div data-role="dynamic-fields"><div class="form-inline mb-3 ml-3"><div class="form-group input-group"><select class="custom-select input-group-prepend" name="act_metrics['+next+'][]"><option value="">Select Metrics</option><?php foreach($metrics as $k => $v) {   ?><option value="<?php echo $k; ?>"><?php echo $v; ?></option><?php } ?></select><select name="act_oper['+next+'][]" class="form-control" require><option value="">Select Operator</option><?php foreach($operation as $k => $v) {   ?><option value="<?php echo $k; ?>"><?php echo $v; ?></option><?php } ?></select><input type="text" name="act_val['+next+'][]" placeholder="Value" class="form-control" /></div><div class="form-group input-group"><div class="input-group" data-role="add"><span class="input-group-text icon"> <i class="fa fa-plus"></i> </span></div><div class="input-group" data-role="remove"><span class="input-group-text icon"> <i class="fa fa-trash"></i> </span></div></div></div></div>   <!-- dynamic-fields --><div class="col-md-2 form-inline"><div class="form-group input-group mb-3"><label>Cust. Conversion </label><input type="checkbox"  class="form-control cust_con_check ml-3 mr-3" name="cust_con_check['+next+']" value="'+next+'" /><span class="cust_con_sel_'+next+' cust-conv-sel"></span></div><div class="form-group input-group"></div></div>';

                var newInput = $(newIn);
                var removeBtn = '<button id="remove' + (next - 1) + '" class="btn btn-default btn-sm remove-me ml-3" >Delete Rule</button><hr class="hr' + (next - 1) + '"></div></div><div id="field">';
                var removeButton = $(removeBtn);
                
                $(addto).after(newInput);
                $(addRemove).after(removeButton);
                $("#field" + next).attr('data-source',$(addto).attr('data-source'));
                //$(addto).before('<div class="ad-level'+ next +'">'+ next +'</div>');
                $("#count").val(next);  
                
            $('.remove-me').click(function(e){
                        e.preventDefault();
                        var fieldNum = this.id.charAt(this.id.length-1);
                        var fieldID = "#field" + fieldNum;
                        $(this).remove();
                        $(fieldID).remove();
                        $('.ad-level' + (fieldNum) + '').remove();
                        $('.hr' + (fieldNum) + '').remove();
            });
        });

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
</script>