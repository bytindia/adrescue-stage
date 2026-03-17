<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Ad Rule - Setup';
$pgID = 8;
$err =''; 
function moneyFormatIndia($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}
if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE leads_acc SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'leads-acc.php';</script>";
	exit();
}

function arrayHasEmptyValue(array $array) {    
    $empAr = array();
    foreach ($array  as $k => $value) {
        if (trim($value) === '') {
            $empAr[] = $k;
        }
    }
    return $empAr;
}

if(isset($_POST['submit'])) {
    //d($_POST); exit;
    foreach($_POST['act_val'] as $key => $v) { 
        if(trim($v)==''){
            unset($_POST['act_metrics'][$key]);
            unset($_POST['act_oper'][$key]);
            unset($_POST['act_val'][$key]);
        }
    }
    $cond_metrics = serialize(array_filter($_POST['act_metrics']));
    $cond_opr = serialize(array_filter($_POST['act_oper']));
    $cond_val = serialize(array_filter($_POST['act_val']));
    //d($cond_val); exit;
    //$x = arrayHasEmptyValue($_POST['act_val']);

    //d($x); exit;
    if(isset($_GET['id'])) {
        $cirSql = "UPDATE ad_rule SET uid='".$_SESSION['uid']."', rule_name='".mysqli_real_escape_string($conn, $_POST['rule_name'])."', acc_id='".mysqli_real_escape_string($conn, $_POST['ad_acc'])."',  acc_level='".mysqli_real_escape_string($conn, $_POST['ad_level'])."', check_time='".mysqli_real_escape_string($conn, $_POST['check_time'])."', action='".mysqli_real_escape_string($conn, $_POST['ad_action'])."', bud_val='".mysqli_real_escape_string($conn, $_POST['bud_val'])."', cond_match='".mysqli_real_escape_string($conn, $_POST['cond_match'])."', cond_metrics='".mysqli_real_escape_string($conn, $cond_metrics)."',  cond_opr='".mysqli_real_escape_string($conn, $cond_opr)."', cond_val='".mysqli_real_escape_string($conn, $cond_val)."',  wa_alert='".mysqli_real_escape_string($conn, $_POST['wa_alert'])."', updated=now() WHERE id=".$_GET['id']."";
        mysqli_query($conn, $cirSql) or die(mysqli_error()); 
        $lastId = $_GET['id'];
    } else {
         $cirSql = "INSERT INTO ad_rule (uid, rule_name, acc_id, acc_level, check_time, action, bud_val, cond_match, cond_metrics, cond_opr, cond_val, wa_alert, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['rule_name'])."', '".mysqli_real_escape_string($conn, $_POST['ad_acc'])."', '".mysqli_real_escape_string($conn, $_POST['ad_level'])."', '".mysqli_real_escape_string($conn, $_POST['check_time'])."', '".mysqli_real_escape_string($conn, $_POST['ad_action'])."', '".mysqli_real_escape_string($conn, $_POST['bud_val'])."', '".mysqli_real_escape_string($conn, $_POST['cond_match'])."', '".mysqli_real_escape_string($conn, $cond_metrics)."', '".mysqli_real_escape_string($conn, $cond_opr)."', '".mysqli_real_escape_string($conn, $cond_val)."', '".mysqli_real_escape_string($conn, $_POST['wa_alert'])."', now(), now());"; 
         mysqli_query($conn, $cirSql) or die(mysqli_error());
         $lastId = mysqli_insert_id($conn);
    }
    $_SESSION['suc'] = 'Successfully Updated!';	
    echo "<script>window.location = 'ad-rules.php';</script>";
    exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " leads_acc WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>
 <style>
      .hide_alert, #reportrange { display: none !important; }
   
 
        #rowAdder {
            margin-left: 17px;
        }
    .form-inline > h3:first-child  { display:none; }


    [data-role="dynamic-fields"] > .form-inline + .form-inline {
        margin-top: 1em;
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

    .card{width:400px;background-color:#fff;border:none;border-radius: 12px}
    label.radio{cursor: pointer;width: 100%}
    .form-control {    border: 1px solid #a4cef6;
    border-radius: 10px;
    margin: 0 5px 0 0;}
    .form-control:focus{box-shadow: none;border: 2px solid #039BE5}
    .agree-text{font-size: 12px}
    .terms{font-size: 12px;text-decoration: none;color: #039BE5}
    .confirm-button{height: 50px;border-radius: 10px}
    .nav-sm span.fa, .nav-sm .menu_section h3 {
    display: block !important; 
}
.dyn-form {
    margin-bottom: 10px;
}
    </style>
    
    <link href="//cdn.datatables.net/buttons/1.5.6/css/buttons.bootstrap4.min.css" rel="stylesheet">
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
                     
$tot_bal = $tot = 0;
$sqlRev=mysqli_query($conn, "SELECT name as text, account_id as value, name as continent FROM adAccounts WHERE uid='2' and account_status=1");
//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");
$acc_id_label = '';
while($sqlROW=mysqli_fetch_assoc($sqlRev))
{ 
	//$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text'])).' (' .$sqlROW['value'].')';
  $sqlROW['text'] = $sqlROW['text'];
  $sqlROW['continent'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['continent']));
  $acc_id_label .= '{label: "'.$sqlROW['text'].'", value: "'.$sqlROW['value'].'"},';
  $ad_acc[] =  $acc_data[] = $sqlROW;
    
    //$acc_data2[$sqlROW['value']] = 'value: '.$sqlROW['value'].',text: "'.$sqlROW['continent'].'",continent: "'.$sqlROW['continent'].'"';
    
}
$acc_data = json_encode($acc_data);
$report_type = array(1=>'Custom Report', 2=> 'Placements', 3=> 'CPL Comparission', 4=>'Day wise report', 5=>'Hourly Breakdown', 6=>'Pause Campaigns');
$metrics = array(
  'spend' => 'Spend',
  'leadgen_grouped' => 'Leads (LG)',
  'cpl' => 'CPL',
  'conversions' => 'Conversions',
  'clicks' => 'Clicks',
  'reach' => 'Reach',
  'impressions' => 'Impressions',
  'post_engagement' => 'Post Engagement',
  'link_click' => 'Link Clicks',
  'cpc' => 'CPC',
  'cpm' => 'CPM',
  'ctr' => 'CTR',
  'like' => 'Likes',
  'comment' => 'Comments',
  'video_view' => 'Video View',
  'page_engagement' => 'Page Engagement'
);

$operation = array(
    '<' => '< less than',
    '>' => '> grater than',
    '<=' => '<= less than or equal',
    '>=' => '>= grater than or equal',
    '=' => '= equal',
);

$check_every = array(
    '15m' => '15 mins',
    '30m' => '30 mins',
    '1h' => '1 hour',
    '2h' => '2 hours',
    '3h' => '3 hours',
    '12h' => '12 hours',
    '24h' => '24 hours',
);
								
$editData = array();
$editData['email_notif'] =0;
if(isset($_GET['id'])) {
	$sqlD=mysqli_query($conn, "SELECT rule_name, acc_id, acc_level, check_time, action, bud_val, cond_match, cond_metrics, cond_opr, cond_val, wa_alert FROM ad_rule WHERE id='".$_GET['id']."'");
	while($Rdata=mysqli_fetch_assoc($sqlD)) {
		$editData = $Rdata;
	}
   // d($editData);
}

$ad_level = array(1=>'Campaign', 2=>'AdSet', 3=>'Ad'); 
$ad_action = array(1=>'Pause', 2=>'Budget Increase', 3=>'Budget Reduce'); 
?>
                                
                               <form method="post" action="">
            <div class="">
                <div class="col-lg-12">
                    
                    <div class="row ">
                        <div class="">
                            <div class="form-inline1">
                                        
                                        <div class="form-group col-md-6">
                                        <label>Rule Name: </label> <br>
                                            <input type="text" name="rule_name" class="form-control" <?php if(isset($editData['rule_name'])) { ?> value="<?php echo $editData['rule_name']; ?>" <?php } ?> required />
                                        </div>
                            </div>
                        </div>
                    </div>
                    <br><br>

                    <div class="row ">
                    <div class="col-md-12">
                                <div class="form-inline">
                              
                                
                                <div class="form-group">
                                <label>Account: </label> <br>
                                                        <select name="ad_acc" class="form-control" required>
                                                            <option value="">Ad Account</option>
                                                            <?php foreach($ad_acc as $k => $v) {   ?>
                                                                <option value="<?php echo $v['value']; ?>" <?php if(isset($editData['acc_id']) && $editData['acc_id']==$v['value']) { echo "selected='selected'"; } ?>><?php echo $v['text']; ?></option>
                                                            <?php } ?>
                                                        </select>
                                </div>
                                
                                <div class="form-group">
                                    
                                <label>Level: </label> <br>
                                                        <select name="ad_level" class="form-control" required>
                                                        <?php foreach($ad_level as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>" <?php if(isset($editData['acc_level']) && $editData['acc_level']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                         <?php } ?>
                                                        </select>
                                </div>
                                <div class="form-group">
                                <label>Check report: </label> <br>
                                                        <select name="check_time" class="form-control" require>
                                                            <option value="1">Every</option>
                                                            <?php foreach($check_every as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>" <?php if(isset($editData['check_time']) && $editData['check_time']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                            <?php } ?>
                                                        </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br><br>


                    <div class="row ">
                    
                    <div class="col-md-12">
                                <div class="form-inline">
                                <div class="form-group">
                                <label>Action: </label> <br>
                                                        <select name="ad_action" class="form-control ad_action" require>
                                                        <?php foreach($ad_action as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>" <?php if(isset($editData['action']) && $editData['action']==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                                         <?php } ?>
                                                        </select>
                                </div>
                                <div class="form-group bud_val">
                                <label>Value (in %): </label> <br>
                                                        <input type="text" name="bud_val" <?php if(isset($editData['bud_val'])) { ?> value="<?php echo $editData['bud_val']; ?>" <?php } ?>  class="form-control" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <br><br>

                    <div class="row ">
                        <div class="col-md-12">
                            
                            <div class="form-inline">
                            <label>Conditions must match: </label>
                                    <div class="form-group">
                                    &nbsp;&nbsp; <input class="form-check-input" type="radio" name="cond_match" id="inlineRadio1" value="all" checked>
                                        <label class="form-check-label" for="inlineRadio1"> All</label>
                                    </div>
                                    <div class="form-group">
                                    &nbsp;&nbsp; <input class="form-check-input" type="radio" name="cond_match" id="inlineRadio2" value="any" <?php if(isset($editData['cond_match']) && $editData['cond_match']=='any') { echo "checked"; } ?>>
                                        <label class="form-check-label" for="inlineRadio2"> Any</label>
                                    </div>
                            </div>
                            <br>
                           
                            <?php 
							if(isset($editData['cond_metrics']) && $editData['cond_metrics']!='') { 
									$rules_metrics = unserialize($editData['cond_metrics']);
									$rules_opr = unserialize($editData['cond_opr']);
                                    $rules_val = unserialize($editData['cond_val']); 
                                    //d($rules_metrics); exit;
									if(count($rules_metrics)>0) {
									$f=1;
									$f_count = count($rules_metrics);
                                    
									foreach($rules_metrics as $r_key => $r_val) {
															?>
                                <div class="row <?php if($f!=$f_count) { ?> not-first<?php } ?>">
                                <div data-role="dynamic-fields" class="dyn-form">
                                <div class="form-inline">
                                  
                                    <div class="form-group">
                                        <select name="act_metrics[]" class="form-control" require>
                                            <option value="">Metrics</option>
                                            <?php foreach($metrics as $k => $v) {   ?>
                                                <option value="<?php echo $k; ?>" <?php if(isset($rules_metrics[$r_key]) && $rules_metrics[$r_key]==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <select name="act_oper[]" class="form-control" require>
                                            <option value="">Operator</option>
                                            <?php foreach($operation as $k => $v) {   ?>
                                                <option value="<?php echo $k; ?>" <?php if(isset($rules_opr[$r_key]) && $rules_opr[$r_key]==$k) { echo "selected='selected'"; } ?>><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="act_val[]" placeholder="Value" class="form-control" <?php if(isset($rules_val[$r_key])) { ?> value="<?php echo $rules_val[$r_key]; ?>" <?php } ?>/>
                                    </div>
                                    
                                    <?php if($f==$f_count) { ?>  
                                    <button class="btn btn-primary form-control" data-role="add">
                                        <span class="fa fa-plus"></span>
                                    </button>
                                    <?php } ?>
                                    <button class="btn btn-danger form-control" data-role="remove">
                                        <span class="fa fa-times"></span>
                                    </button>
                                    <br>
                                </div>  </div> </div> 
                                <?php $f++; } }
                                } else {
								?>
                                <div class="row ">
                                                         
                                                                <div data-role="dynamic-fields">
                                <div class="form-inline">
                                  
                                  <div class="form-group">
                                      <select name="act_metrics[]" class="form-control" require>
                                          <option value="">Metrics</option>
                                          <?php foreach($metrics as $k => $v) {   ?>
                                              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                          <?php } ?>
                                      </select>
                                  </div>
                                  <div class="form-group">
                                      <select name="act_oper[]" class="form-control" require>
                                          <option value="">Operator</option>
                                          <?php foreach($operation as $k => $v) {   ?>
                                              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                          <?php } ?>
                                      </select>
                                  </div>
                                  <div class="form-group">
                                      <input type="text" name="act_val[]" placeholder="Value" class="form-control" />
                                  </div>

                                  <button class="btn btn-primary form-control" data-role="add">
                                      <span class="fa fa-plus"></span>
                                  </button>
                                  <button class="btn btn-danger form-control" data-role="remove">
                                      <span class="fa fa-times"></span>
                                  </button>
                                                                                                      
                              </div>  </div>  </div>  
                                <?php } ?>
                                <!-- /div.form-inline -->
                            </div>  <!-- /div[data-role="dynamic-fields"] -->
                        </div>  <!-- /div.col-md-12 -->
                    </div>  <!-- /div.row -->
                    <br><br> 
                    <div class="row ">
                        <div class="">
                            <div class="form-inline1">
                                        
                                        <div class="form-group col-md-6"  style="margin-top: 20px;">
                                        <label>Whatsapp Alert: (Results)</label> <br>
                                            <input type="text" name="wa_alert" class="form-control" <?php if(isset($editData['wa_alert'])) { ?> value="<?php echo $editData['wa_alert']; ?>" <?php } ?> required />
                                        </div>
                            </div>
                        </div>
                    </div>
                    <br><br>
                    <div class="row ">
                        <div class="col-md-12 text-left">
                        <a href="ad-rules.php" class="btn btn-lg btn-default">Cancel</a>
                        <input type="submit" name="submit" value="Submit" class="btn btn-lg btn-primary" />
                        </div>
                    </div>
                    <br>
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


<script type="text/javascript">
        $('.bud_val').hide();
        $('.ad_action').on('change', function() {
            var sel = $(this).find(":selected").val();
            if(sel!=1) { 
                $('.bud_val').show();
            } else {
                $('.bud_val').hide();
            }
        });
 $(function() {
       // $("#rowAdder").click(function(e) {
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
    <script src="js/bundle.min.js"></script>
<script>
function formValidation()
{
    
    $('.hide_alert').css('display','none');

    var errMsg ='';
    var rep_ty = $('#report_type').val();
    var ad_acc = $('#ad_acc').val();
    var metrics = $('#metrics').val();
    
    if(rep_ty==''){
        errMsg ='Select the report types!';
    }
    if(ad_acc==''){
        errMsg ='Ad account(s) should not be empty!';
    }
    if(rep_ty==1 && metrics==''){
      errMsg ='Metrics should not be empty for custom report!';
    }
    if(errMsg!=''){ //alert(1);
        $('.hide_alert').css('display','block');
        $('.alertMsg').html('<b>'+errMsg+'</b>');
        return false;
    } else { //alert(2);
        $('.hide_alert').css('display','none');
        return true;
    }
}

  //alert($('#report_type').val());
  if($('#report_type').val()==1) {
    $('.metrics_cls').show();
  } else {
    $('.metrics_cls').hide();
  }
  

  var single = new MultiSelect2(".single-select", {
    options: [<?php foreach($report_type as $k => $v) { echo '{label: "'.$v.'", value: "'.$k.'"},'; } ?>],
    <?php if(isset($_SESSION['report_type']) && $_SESSION['report_type']!='') { ?>
      value: '<?php echo $_SESSION['report_type']; ?>',
    <?php } ?>
    autocomplete: true,
    onChange: value => {
      $('#report_type').val(value);
      if(value==1) { $('.metrics_cls').show(); } else { $('.metrics_cls').hide(); }
    },
  });

  var autocomplete = new MultiSelect2(".autocomplete-select", {
      options: [<?php foreach($metrics as $k => $v) { echo '{label: "'.$v.'", value: "'.$k.'"},'; } ?>],
      value: [<?php if(isset($_SESSION['metrics']) && $_SESSION['metrics']!='') { echo "'".str_replace(",","','",$_SESSION['metrics'])."'"; } ?>],
      multiple: true,
      autocomplete: true,
      icon: "fa fa-times",
      onChange: value => {
        $('#metrics').val(value);
      },
  });

  var multi = new MultiSelect2(".multi-select", {
        options: [<?php echo $acc_id_label; ?>],
        value: [<?php if(isset($_SESSION['ad_acc']) && $_SESSION['ad_acc']!='') { echo "'".str_replace(",","','",$_SESSION['ad_acc'])."'"; } ?>],
        multiple: true,
        autocomplete: true,
        icon: "fa fa-times",
        onChange: value => { $('#ad_acc').val(value); },
      });
  
  //console.log(single);
  
</script>