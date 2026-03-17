<?php include 'header.php';    
date_default_timezone_set('America/New_York');
if(!isset($_SESSION['proj'])) { $_SESSION['proj'] = 'All'; }


if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	$_SESSION['proj'] = $_POST['proj'];
	echo "<script>window.location = 'leads.php?id=".$_GET['id']."';</script>";
	exit();
}

Auth();
$userId = $_SESSION['uid'];
//$userId = 2;
$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id={$userId}";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

$query = "SELECT client_name, pg_name FROM leads_acc WHERE pg_id='".$_GET['id']."' limit 0,1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$client_name = $row['client_name']; 

$pgHeadline = 'Ads Overview';
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

function getOutput($v1, $v2, $ty){
  //return $v1.', '.$v2;
  if($v1=='INF' || $v1=='') { $v1='-'; }
  if($v2=='INF' || $v2=='') { $v2='-'; }
  $msg = '';
  if($v1=='-' && $v2=='-'){
    return '--';
  }
  if($ty==1) { $high='HIGH'; $low='LOW'; $txt_red='txt_red'; $txt_green='txt_green';  } else { 
    //$low='HIGH'; $high='LOW'; 
    $high='HIGH'; $low='LOW';
    $txt_red='txt_green'; $txt_green='txt_red';  
  }
  if($v1!='-' && $v2!='-'){
    if($v1>$v2){
      $msg = '<span class="'.$txt_red.'">'.$high.'</span>';
    } else if($v1<$v2){
      $msg =  '<span class="'.$txt_green.'">'.$low.'</span>';
    } else {
      $msg =  '<span class="txt_green">EQUAL</span>';
    }
  }
  return $msg .'<br>'.$v1.' / '.$v2;
  //} else {
  //  return '--';
  //}
}

function sendWhatsapp($tok, $phoneNo) {
  $attachment =  array(
      
      'messaging_product' => 'whatsapp',
      'to' => $phoneNo,
      'type'=> 'template',
      'template' => 
        json_encode(
          array(
            'name' => 'ad_spend_report_test', 
            'language' => array('code'=>'en_US'), 
            'components'=> 
              array(array(
              "type" => "body",
              "parameters" => array(
                  array("type"=> "text","text"=> '1'),
                  array("type"=> "text","text"=> '2'),
                  array("type"=> "text","text"=> '3'),
                  array("type"=> "text","text"=> '4'),
                  array("type"=> "text","text"=> '5')
              )))
        ))
      );
      $ch = curl_init('https://graph.facebook.com/v14.0/100284149552425/messages'); // Initialise cURL
      $post = json_encode($attachment); // Encode the data array into a JSON string
      $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
      $result = curl_exec($ch); // Execute the cURL statement
      curl_close($ch); // Close the cURL connection
      return json_decode($result); // Return the received data
      //print_r($result); 

}

?>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.6/css/intlTelInput.css'>
<style>
   
.intl-tel-input {
  display: table-cell;
}
.intl-tel-input .selected-flag {
  z-index: 4;
}
.intl-tel-input .country-list {
  z-index: 5;
}
.input-group .intl-tel-input .form-control {
  border-top-left-radius: 4px;
  border-top-right-radius: 0;
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 0;
}
</style>
<style>
.table-scroll {
	position:relative;
	margin:auto;
	overflow:hidden;
	
}
.table-wrap {
	width:100%;
	overflow:auto;
}
.table-scroll table {
	width:100%;
	margin:auto;
	border-collapse:separate;
	border-spacing:0;
}
.table-scroll th, .table-scroll td {
	padding:5px 10px;
	border:1px solid #dddddd;
	white-space:nowrap;
	vertical-align:top;
}
.table-scroll thead, .table-scroll tfoot {
	background:#f9f9f9;
}
.clone {
	position:absolute;
	top:0;
	left:0;
	pointer-events:none;
}
.clone th, .clone td {
	visibility:hidden
}
.clone td, .clone th {
	border-color:transparent
}
.clone tbody th {
	visibility:visible;
	color:red;
}
.clone .fixed-side {
	border:1px solid #dddddd;
  background: #f9f9f9;
	visibility:visible;
  font-weight: bold;
  text-align:left;
}
tbody td {
    text-align: right;
}
.clone thead, .clone tfoot{background:transparent;}

.txt_red { color:red; font-weight:bold; }
.txt_green { color:#74d350; font-weight:bold; }
thead th {
    text-align: center !important;
}
table .fa { font-size: 15px; color: #b5b7be; cursor: pointer; }
table .fa-wa { color: #4db628; font-weight: bold; }
.wa_msg { font-weight: bold; }
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
         <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Send WhatsApp message</h4>
                    </div>
                    <div class="modal-body">
                    <form method="post" action="">
                                <input type="hidden"  id="countryCode" name ="countryCode" value="91" >
                                <input type="hidden"  id="acc_tok" name ="acc_tok" value="<?php echo $access_token; ?>" >
                                <input type="hidden"  id="wa_client" name ="wa_client" >
                                <input type="hidden"  id="wa_date" name ="wa_client" >
                                <input type="hidden"  id="wa_spend" name ="wa_client" >
                                <input type="hidden"  id="wa_lead" name ="wa_client" >
                                <input type="hidden"  id="wa_cpl" name ="wa_client" >

                               <div class="content">
                               
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">     
                                                	<label>Phone: </label>
                                                    <input type="text" id="phone" name="phone" class="form-control" placeholder="10 digit mobile number" minlength="10" maxlength="10" required>                                       	
                                                    <br />      
                                                    <br>
                                                    <label>Message Template: </label><br>
                                                    <p>
                                                    Client <span class="wa_client wa_msg"></span><br>
                                                    Date: <span class="wa_date wa_msg"></span><br>
                                                    Spend - <span class="wa_spend wa_msg"></span><br>
                                                    Lead - <span class="wa_lead wa_msg"></span><br>
                                                    CPL - <span class="wa_cpl wa_msg"></span><br><br>

                                                    - by AdRescue
                                                    </p>
                                                    <br />
                                                    <input type="submit" name="submit" value="Send Message" class="btn btn-info" id="wa-btn" onclick="return sendWA();">

                                                 <br />
                                                 <div class="alert alert-success" id="alertMsg"></div>

                                                </div>
                                        </div>
                                    </div>
                                </div>
                                </form>
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
          </div>

         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 <!--
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=leads-acc-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
                      	</div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  -->
                  <?php
                 // $access_token = 'REDACTED_FB_TOKEN';
                  $phone = '919176299010';
                 // $wa_res = sendWhatsapp($access_token, $phone);
                  //d($wa_res); exit;
				  $words_like = array();
				  $q3 = "SELECT updated FROM checklist WHERE uid='".$userId."' limit 0,1";
				  $r3 = mysqli_query($conn, $q3);
          //$numrow = mysqli_num_rows($r3);
				  if(mysqli_num_rows($r3)>0) { $row3 = mysqli_fetch_assoc($r3); $updated = date('d-m-Y, h:i a',strtotime($row3['updated'])); } 
				  //$words_like = array_filter($words_like)
				 // d($words_like);

         $sqlRev=mysqli_query($conn, "SELECT  *  FROM checklist WHERE uid='".$userId."' AND delete_status=0 order by tbl_id desc");
         $numrow = mysqli_num_rows($sqlRev);
				  ?>
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?> 
                    	<div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=cron-checklist.php"  class="btn btn-danger btn-sm" onclick="return confirm('This may take <?php echo (($numrow * 10)-10) ?> to <?php echo (($numrow * 10)+5) ?> seconds to load! Are you sure want to continue?')">Fetch Live Report</a>                            
                      	</div>
                     </h2>  
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=checklist-acc.php"  class="btn btn-success btn-sm">View Accounts</a>                            
                      	</div> 
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=checklist-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                            
                      	</div> 
                         
                      </li>
                      
                    </ul>
                    <!--<form method="post" action="leads.php?id=<?php echo $_GET['id']; ?>">
                    <ul class="nav navbar-right panel_toolbox">
                      <li>Filter : &nbsp;
                      </li>
                      <li>
                      <select name="proj" class="form-control">
                                 	<option value="All" <?php if($_SESSION['proj']=='All') { echo 'selected'; } ?>>All</option>
                                 	<?php foreach($words_like as $k => $v) { ?>
                                 	<option value="<?php echo $v; ?>" <?php if($_SESSION['proj']==$v) { echo 'selected'; } ?>><?php  echo $v;  ?></option>
                                    <?php } ?>                                    
                                 </select>
                                 &nbsp;
                        </li>
                      <li>
                      		     <div id="reportrange_right" class="pull-right1" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                                      <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                                      <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                                 </div>
                                 <input type="hidden" id="stDt" name="start" value="<?php echo $_SESSION['stDt']; ?>">
								<input type="hidden" id="enDt" name="end" value="<?php echo $_SESSION['enDt']; ?>"> 
                      </li>
                      <li><input type="submit" name="dt_submit" value="Submit" class="btn btn-primary"></li>
                    </ul>
                    </form>-->
                    
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                 
                  <p>* Last updated: <b><?php echo $updated; ?></b></p>
                    <div id="table-scroll" class="table-scroll">
                    <div class="table-wrap">
                      <table class="main-table">
                        <thead>
                        <tr>
                            <th class="fixed-side" scope="col" rowspan="2" colspan="3">Client</th>
                            <th scope="col" rowspan="2">Budget <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Total budget for the current month"></i></th>
                            <th scope="col" rowspan="2">Spent<br>Facebook <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Total spent in Facebook ads for the current month"></i></th>
                            <th scope="col" rowspan="2">Spent<br>Google <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Total spent in Facebook ads for the current month"></i></th>
                            <th scope="col" colspan="3">Facebook (LG)</th>

                            <th scope="col" colspan="3">Facebook (Conv)</th>
                            
                            <th scope="col" colspan="6">Facebook (Ecom)</th>

                            <th scope="col" colspan="3">Google (Conv)</th>
                          </tr>
                          <tr>

                          <th scope="col">L-3d / P-3d<br> CPL <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CPL (FB - LG)"></i></th>
                            <th scope="col">L-3d / Mon<br> CPL <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / current month<br> CPL (FB - LG)"></i></th>
                            <th scope="col">L-3d / P-3d<br> CTR <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CTR (FB - LG)"></i></th>
                            
                            <th scope="col">L-3d / P-3d<br> CPL <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CPL (FB - Conv.)"></i></th>
                            <th scope="col">L-3d / Mon<br> CPL <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / current month<br> CPL (FB - Conv.)"></i></th>
                            <th scope="col">L-3d / P-3d<br> CTR <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CTR (FB - Conv.)"></i></th>
                            
                            <th scope="col">L-3d / P-3d<br> CPC <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CPC (FB - Ecom)"></i></th>
                            <th scope="col">L-3d / Mon<br> CPC <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / current month<br> CPC (FB - Ecom) "></i></th>
                            <th scope="col">L-3d / P-3d<br> CPP <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CPP (FB - Ecom) "></i></th>
                            <th scope="col">L-3d / Mon<br> CPP <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / current month<br> CPP (FB - Ecom) "></i></th>
                            <th scope="col">L-3d / P-3d<br> ATC <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> ATC (FB - Ecom) "></i></th>
                            <th scope="col">L-3d / Mon<br> ATC <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / current month<br> ATC (FB - Ecom) "></i></th>

                            <th scope="col">L-3d / P-3d<br> CPL <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CPL (Google) "></i></th>
                            <th scope="col">L-3d / Mon<br> CPL <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / current month<br> CPL (Google)"></i></th>
                            <th scope="col">L-3d / P-3d<br> CTR <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-original-title="Last 3d / Previous 3d<br> CTR (Google)"></i></th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                        //echo "SELECT  *  FROM checklist WHERE uid='".$userId."' AND delete_status=0 order by tbl_id desc"; 
                        $i=1;
                          while($sqlROW=mysqli_fetch_array($sqlRev))
                          { 
                              //$col_1 = getOutput($sqlROW["prev3_cpl_lg"], $sqlROW["prev3_cpl_lg"]);
                          ?>
                                <tr>                                        	
                                  <td class="fixed-side"><?php echo $sqlROW["client_name"]; ?></td> 
                                  <td class="fixed-side"><a href="loading.php?pg=cron-checklist.php?tbl_id=<?php echo $sqlROW["tbl_id"]; ?>"  data-html="true" data-toggle="tooltip" data-original-title="Refresh"> <i class="fa fa-refresh"></i></a></td>
                                  <td class="fixed-side"><a href="loading.php?pg=#" data-id="1" data-toggle="modal" class="blue modal-cl wa_click" data-target=".bs-example-modal-lg" data-html="true"  data-original-title="WhatsApp" onclick="return popWA(': <?php echo $sqlROW["client_name"]; ?>','This month','<?php echo $sqlROW["spent_fb"]; ?>','250','198.25');"> <i class="fa fa-wa fa-whatsapp"></i></a></td>
                                  <td><?php //echo round($sqlROW["budget"]); ?></td> 
                                  <td><?php echo round($sqlROW["spent_fb"]); ?></td>    
                                  <td><?php echo round($sqlROW["spend_g"]); ?></td>    

                                  <td><?php echo getOutput($sqlROW["last3_cpl_lg"], $sqlROW["prev3_cpl_lg"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_cpl_lg"], $sqlROW["mon_cpl_lg"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_ctr_lg"], $sqlROW["prev3_ctr_lg"], 2); ?></td>

                                  <td><?php echo getOutput($sqlROW["last3_cpl_conv"], $sqlROW["prev3_cpl_conv"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_cpl_conv"], $sqlROW["mon_cpl_conv"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_ctr_conv"], $sqlROW["prev3_ctr_conv"], 2); ?></td>
                                  
                                  <td><?php echo getOutput($sqlROW["last3_cpc_conv"], $sqlROW["prev3_cpc_conv"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_cpc_conv"], $sqlROW["mon_cpc_conv"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_cpp_conv"], $sqlROW["prev3_cpp_conv"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_cpp_conv"], $sqlROW["mon_cpp_conv"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_atc_conv"], $sqlROW["prev3_atc_conv"], 1); ?></td>  
                                  <td><?php echo getOutput($sqlROW["last3_atc_conv"], $sqlROW["mon_atc_conv"], 1); ?></td>    

                                  <td><?php echo getOutput($sqlROW["last3_cpl_g"], $sqlROW["prev3_cpl_g"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_cpl_g"], $sqlROW["mon_cpl_g"], 1); ?></td>
                                  <td><?php echo getOutput($sqlROW["last3_ctr_g"], $sqlROW["prev3_ctr_g"], 2); ?></td>                         	
                                </tr>  
                                <?php $i++;
                          } ?>    
                          <tr>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>


                  
                  </div>
                  </div>
								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
       
<?php include 'footer.php'; ?>
<script src='https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.7/js/intlTelInput.js'></script>
<script>
  $("#alertMsg").hide();
  function popWA(v1, v2, v3, v4, v5){
    //alert(v1);
    $(".wa_client").html(v1); $("#wa_client").val(v1);
    $(".wa_date").html(v2); $("#wa_date").val(v2);
    $(".wa_spend").html(v3); $("#wa_spend").val(v3);
    $(".wa_lead").html(v4); $("#wa_lead").val(v4);
    $(".wa_cpl").html(v5); $("#wa_cpl").val(v5);
    return false; 
  }
  function sendWA(){
            var wa_client = $("#wa_client").val();
            var wa_date = $("#wa_date").val();
            var wa_spend = $("#wa_spend").val();
            var wa_lead = $("#wa_lead").val();
            var wa_cpl = $("#wa_cpl").val();
            var acc_tok = $("#acc_tok").val();
            var countryCode = $("#countryCode").val();
            var phone = $("#phone").val();
            //alert(extra4);
						// Returns successful data submission message when the entered information is stored in database.
						var dataString = 'wa_client=' + wa_client + '&wa_date=' + wa_date + '&wa_spend=' + wa_spend +'&wa_lead=' + wa_lead + '&wa_cpl=' + wa_cpl + '&acc_tok=' + acc_tok  + '&countryCode=' + countryCode + '&phone=' + phone;
						alert(dataString);
						// AJAX code to submit form.
						$.ajax({
							type: "POST",
							url: "ajax-sendWA.php",
							data: dataString,
							cache: false,
							success: function(res) {
                $("#alertMsg").show();
                $("#alertMsg").html(res);
							},
							error:function(err){
							  alert("error"+JSON.stringify(err));
							}
						});
						
						return false;

  }
  //$("#wa_click").click({
 

        $("#phone").intlTelInput({
          initialCountry: "auto",
          separateDialCode: true,
            preferredCountries:["in"],
            hiddenInput: "full",
          geoIpLookup: function(callback) {
              
            $.get('https://ipinfo.io', function() {}, "jsonp").always(function(resp) {
              var countryCode = (resp && resp.country) ? resp.country : "";
              callback(countryCode);
            });
          },
          utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.6/js/utils.js" // just for formatting/placeholders etc
        });

  </script>  
<script>
  
$(document).ready(function () {
    var input = $("#phone");
    input.intlTelInput();

    input.on("countrychange", function() {
        $("#countryCode").val($("#phone").intlTelInput("getSelectedCountryData").dialCode);
    });
});	


</script>
<script>
  $(document).ready(function() {
    
        $('[data-toggle="tooltip"]').tooltip()
        $(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');  
  });
</script>