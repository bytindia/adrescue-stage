<?php session_start(); 
if(isset($_POST['submit'])) {
    $_SESSION['acc_ids'] = $_POST['tag1'];
}

include '../db.php';

$tot_bal = $tot = 0;
$sqlRev=mysqli_query($conn, "SELECT name as text, account_id as value, name as continent FROM adAccounts WHERE uid='2'");
//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");

while($sqlROW=mysqli_fetch_assoc($sqlRev))
{ 
	$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text'])).' (' .$sqlROW['value'].')';
	$sqlROW['continent'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['continent']));
    $acc_data[] = $sqlROW;
    $acc_data2[$sqlROW['value']] = 'value: '.$sqlROW['value'].',text: "'.$sqlROW['continent'].'",continent: "'.$sqlROW['continent'].'"';
    
}
$acc_data = json_encode($acc_data);
$expIds = explode(',',$_SESSION['acc_ids']);
//d($_SESSION);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/css/bootstrap.min.css" rel="stylesheet" type="text/css">
<link href="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/dist/bootstrap-tagsinput.css" rel="stylesheet" type="text/css">
<link href="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/assets/app.css" rel="stylesheet" type="text/css">
 <style>
	.container{
  margin: 20px;
}

/* autocomplete tagsinput*/
.label-info {
  background-color: #04aadb;
  display: inline-block;
  padding: 5px 15px;
  font-size: 100%;
  font-weight: 700;
  line-height: 1;
  color: #fff;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25em;
}
.btn-group-sm>.btn, .btn-sm {
    padding: 7px 15px;
}
</style>

</head>

<body>

  <div class="container">
    <div class="row">
      <div class="col-12">
      <form action="" method="post">
            <div class="btn-toolbar mb-3" role="toolbar" aria-label="Toolbar with button groups">
            <div class="form-group">
                <input type="hidden" id="tag2" class="form-control" name="tag2" />
                <input type="text" id="tag1" class="form-control" name="tag1" placeholder="more ad account"  onchange="myFunction()" aria-label="Input group example" aria-describedby="btnGroupAddon">
            </div>
            <div class="form-group">
                <input type="submit" name="submit" value="Submit" class="btn btn-success btn-sm">
            </div>
            </div>
      </form>
      </div>
    </div>
  </div>  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/js/bootstrap.min.js"></script> 
   
	<script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script> 
    <script src="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js"></script>
<script>
var data = '<?php echo $acc_data; ?>';

//get data pass to json
var task = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace("text"),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  local: jQuery.parseJSON(data) //your can use json type
});

task.initialize();

var elt = $("#tag1");
elt.tagsinput({
    maxChars: 25,
    maxTags: 5,
  itemValue: "value",
  itemText: "continent",
  typeaheadjs: {
    minLength: 1,
    maxLength: 1,
    highlight: true,
    name: "task",
    displayKey: "text",
    source: task.ttAdapter()
  }
});

//insert data to input in load page $_SESSION['acc_ids']
<?php  foreach($expIds  as $k => $val)   { ?>
    elt.tagsinput("add", { <?php  echo $acc_data2[$k]; ?> });
<?php   } ?>
function myFunction() {
	//alert(JSON.stringify($('#tag1').tagsinput('items')));
	$('#tag2').val(JSON.stringify($('#tag1').tagsinput('items')));
}


    </script>
</body>
</html>

