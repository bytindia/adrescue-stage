<?php session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db.php';

if(isset($_POST['submit'])) {
  //d($_POST); exit;
  $_SESSION['report_type'] = $_POST['report_type'];
  $_SESSION['metrics'] = $_POST['metrics'];
  $_SESSION['ad_acc'] = $_POST['ad_acc'];

  if($_SESSION['report_type']==1){
    echo "<script>window.location = 'loading.php?pg=custom-report.php';</script>";
	  exit();
  }

}
?>
<!doctype html>
<html lang="en">
 
<head>
    <title>MultiSelect2 example</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link href="/css/multi-select.css" rel="stylesheet">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <link href="/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
   <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js" charset="utf-8"></script>
    <!-- Custom Theme Scripts -->
    
    <link href="//cdn.datatables.net/buttons/1.5.6/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="/web/pagination.css" rel="stylesheet">
    <link href="/assets/css/pagination.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <style>
      .hide_alert, #reportrange { display: none !important; }
    </style>
 
    <style>
        body {
            display: flex;
            flex-direction: column;
            margin-top: 1%;
            justify-content: center;
            align-items: center;
        }
 
        #rowAdder {
            margin-left: 17px;
        }
    </style><style>
    .form-inline > h3:first-child  { display:none; }
 </style>
 <style>


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
    
    </style>
</head>
<?php


$tot_bal = $tot = 0;
$sqlRev=mysqli_query($conn, "SELECT name as text, account_id as value, name as continent FROM adAccounts WHERE uid='2' and account_status=1");
//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");
$acc_id_label = '';
while($sqlROW=mysqli_fetch_assoc($sqlRev))
{ 
	//$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text'])).' (' .$sqlROW['value'].')';
  $sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text']));
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
//d($ad_acc);
?>
<body>
    <h3> <i>Ad Rule - Automation</i></h3> <br><br>

   
    <div style="width:45%;">
 
        <form>
            <div class="">
                <div class="col-lg-12">
                    
                    <div class="row ">
                        <div class="col-md-11">
                            <div class="form-inline1">
                                        
                                        <div class="form-group">
                                        <label>Rule Name: </label> <br>
                                            <input type="text" name="rule_name" class="form-control" />
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
                                                        <select name="ad_acc" class="form-control" require>
                                                            <option value="">Ad Account</option>
                                                            <?php foreach($ad_acc as $k => $v) {   ?>
                                                                <option value="<?php echo $v['value']; ?>"><?php echo $v['text']; ?></option>
                                                            <?php } ?>
                                                        </select>
                                </div>
                                
                                <div class="form-group">
                                    
                                <label>Level: </label> <br>
                                                        <select name="ad_level" class="form-control" require>
                                                            <option value="Campaign">Campaign</option>
                                                            <option value="AdSet">AdSet</option>
                                                            <option value="Ad">Ad</option>
                                                        </select>
                                </div>
                                <div class="form-group">
                                <label>Check report: </label> <br>
                                                        <select name="check_time" class="form-control" require>
                                                            <option value="1">Every</option>
                                                            <?php foreach($check_every as $k => $v) {   ?>
                                                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
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
                                                            <option value="1">Pause</option>
                                                            <option value="2">Budget Increase</option>
                                                            <option value="3">Budget Reduce</option>
                                                        </select>
                                </div>
                                <div class="form-group bud_val">
                                <label>Value (in %): </label> <br>
                                                        <input type="text" name="bud_val" class="form-control" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <br><br>

                    <div class="row ">
                        <div class="col-md-12">
                            <label>Conditions: </label>
                            <div data-role="dynamic-fields" class="dyn-form">
                                <div class="form-inline">
                                  
                                    <div class="form-group">
                                        <select name="act_metrics" class="form-control" require>
                                            <option value="">Metrics</option>
                                            <?php foreach($metrics as $k => $v) {   ?>
                                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <select name="act_oper" class="form-control" require>
                                            <option value="">Operator</option>
                                            <?php foreach($operation as $k => $v) {   ?>
                                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="act_val" placeholder="Value" class="form-control" />
                                    </div>
                                    <!--<div class="form-group">
                                        <select name="metrics" class="form-control" require>
                                            <option value="">Check Every</option>
                                            <?php foreach($check_every as $k => $v) {   ?>
                                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    
                                     <span class="fa fa-paper-plane"> - </span>
                                    <div class="form-group"><i class="fa fa-calendar"></i>
                                    <input class="form-control date" class="date" id="date" name="p_date" placeholder="MM/DD/YYYY" type="text" > 
                                    </div>    
                                    <span class="fa fa-paper-plane"> - </span>
                                    <div class="form-group"><i class="fa fa-comment"></i>
                                    <input class="form-control note" class="note" id="p_note" name="p_note" placeholder="Notes" type="text" > 
                                    </div> -->

                                    <button class="btn btn-primary form-control" data-role="add">
                                        <span class="fa fa-plus"></span>
                                    </button>
                                    <button class="btn btn-danger form-control" data-role="remove">
                                        <span class="fa fa-times"></span>
                                    </button>
                                                                                                        
                                </div>  <!-- /div.form-inline -->
                            </div>  <!-- /div[data-role="dynamic-fields"] -->
                        </div>  <!-- /div.col-md-12 -->
                    </div>  <!-- /div.row -->
                    <br><br>
                    <div class="row ">
                        <div class="col-md-12 text-center">
                            <input type="submit" value="Submit" class="btn btn-lg btn-primary" />
                        </div>
                    </div>
                    <br>
                </div>
            </div>
        </form>
    </div>
 
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
       $(document).on('click',  '[data-role="dynamic-fields"] > .form-inline [data-role="add"]', function(e) {
           
            e.preventDefault();
            var container = $(this).closest('[data-role="dynamic-fields"]');
            new_field_group = container.children().filter('.form-inline:first-child').clone();
            new_field_group.find('input').each(function(){
                $(this).val('');
            });
            container.append(new_field_group);
        });
 
        $(document).on('click', '[data-role="dynamic-fields"] > .form-inline [data-role="remove"]', function(e) {
            e.preventDefault();
            $(this).closest('.form-inline').remove();
        });
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
</body>
 
</html>