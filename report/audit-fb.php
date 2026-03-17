<?php session_start();
include '../db.php';

if(isset($_POST['submit'])) {
  //d($_POST); exit;
  //$_SESSION['fb_pg'] = $_POST['fb_pg'];
  //$_SESSION['metrics'] = $_POST['metrics'];
 // $_SESSION['ad_acc'] = $_POST['ad_acc'];

  if($_POST['ad_acc']!=''){
    echo "<script>window.location = 'loading.php?pg=report-view.php?fb=".$_POST['ad_acc']."';</script>";
	  exit();
  }

}
?>
<!doctype html>
<html>
<head>
    <title>Audit Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link href="css/multi-select.css" rel="stylesheet">
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
      .hide_alert, #reportrange { display: none }
    </style>
</head>
<body>
<?php


$tot_bal = $tot = 0;
$sqlRev=mysqli_query($conn, "SELECT name as text, account_id as value, name as continent FROM adAccounts WHERE uid='2' and account_status=1");
//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");
$acc_id_label = '';
while($sqlROW=mysqli_fetch_assoc($sqlRev))
{ 
	//$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text'])).' (' .$sqlROW['value'].')';
  //$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text']));
  //$sqlROW['continent'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['continent']));
  $acc_id_label .= '{label: "'.$sqlROW['text'].' ('.$sqlROW['value'].')", value: "'.$sqlROW['value'].'"},';
  $acc_data[$sqlROW['value']] = $sqlROW['text'].' ('.$sqlROW['value'].')';
    //$acc_data2[$sqlROW['value']] = 'value: '.$sqlROW['value'].',text: "'.$sqlROW['continent'].'",continent: "'.$sqlROW['continent'].'"';
}
//$acc_data = json_encode($acc_data);
$sqlRev_pg = mysqli_query($conn, "SELECT pg_name as text, pg_id as value, pg_name as continent FROM pages WHERE uid='2'");
//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");
//$acc_id_label = '';

while($sqlROW_pg=mysqli_fetch_assoc($sqlRev_pg))
{ 
	//$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text'])).' (' .$sqlROW['value'].')';
  $sqlROW_pg['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW_pg['text']));
  $sqlROW_pg['continent'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW_pg['continent']));
  $pg_id_label .= '{label: "'.$sqlROW_pg['text'].'", value: "'.$sqlROW_pg['value'].'"},';
  $pg_data[$sqlROW_pg['value']] = $sqlROW_pg['text'];
    //$acc_data2[$sqlROW['value']] = 'value: '.$sqlROW['value'].',text: "'.$sqlROW['continent'].'",continent: "'.$sqlROW['continent'].'"';
    
}
?>
<div>
  <div class="right_col" role="main">
      <div class="row">
        <div class="col-8">
        <center><h3>Audit Report</h3></center><br>
          <form method="post" action="" >
           <!-- <span class="rep_cls">
              <h3>Page:</h3>
              <div class="single-select"></div>
              <input type="hidden" name="fb_pg" id="fb_pg" <?php if(isset($_SESSION['fb_pg']) && $_SESSION['fb_pg']!='') { ?> value="<?php echo $_SESSION['fb_pg']; ?>" <?php } ?> />
            </span>
-->

            <span class="add_acc_cls">
              <h3>Ad Account:</h3>
              <span class="single-select2"></span>
              <input type="hidden" name="ad_acc" id="ad_acc" <?php if(isset($_SESSION['ad_acc']) && $_SESSION['ad_acc']!='') { ?> value="<?php echo $_SESSION['ad_acc']; ?>" <?php } ?>/>
            </span>

            <br>
            <div class="alert alert-danger hide_alert" role="alert">
              <span class="alertMsg"></span>
            </div>
            <br>

            <div class="form-group col-md-12" style="padding:0px; ">
              <button type="submit" name="submit" value="Submit" class="btn btn-lg btn-success btn-block" onclick="return formValidation();">Audit Report</button>
            </div>
          </form>
        </div>       
      </div>
  </div>
    

</div>
<script src="js/bundle.min.js"></script>
<script>
function formValidation()
{
    
    $('.hide_alert').css('display','none');

    var errMsg ='';
    var fb_pg = $('#fb_pg').val();
    var ad_acc = $('#ad_acc').val();
    //var metrics = $('#metrics').val();
    //alert(fb_pg);
    if(fb_pg==''){
       // errMsg ='Select the report types!';
    }
    if(ad_acc==''){
        errMsg ='Ad account(s) should not be empty!';
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


  var single = new MultiSelect2(".single-select", {
    options: [<?php foreach($pg_data as $k => $v) { echo '{label: "'.$v.'", value: "'.$k.'"},'; } ?>],
    <?php if(isset($_SESSION['fb_pg']) && $_SESSION['fb_pg']!='') { ?>
      value: '<?php echo $_SESSION['fb_pg']; ?>',
    <?php } ?>
    autocomplete: true,
    onChange: value => {
      $('#fb_pg').val(value);
      if(value==1) { $('.metrics_cls').show(); } else { $('.metrics_cls').hide(); }
    },
  });

  var single2 = new MultiSelect2(".single-select2", {
    options: [<?php echo $acc_id_label; ?>],
        value: [<?php if(isset($_SESSION['ad_acc']) && $_SESSION['ad_acc']!='') { echo "'".str_replace(",","','",$_SESSION['ad_acc'])."'"; } ?>],
        multiple: true,
        autocomplete: true,
        icon: "fa fa-times",
    onChange: value => {
      $('#ad_acc').val(value);
      if(value==1) { $('.metrics_cls').show(); } else { $('.metrics_cls').hide(); }
    },
  });

  //console.log(single);
  
</script>
</body>
</html>
