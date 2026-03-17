
<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'WhatsApp Message';
$pgID = 8;
$err =''; 
function sendWhatsapp($tok, $phone, $wa_client, $wa_date, $wa_spend, $wa_lead, $wa_cpl) {
    $attachment =  array(
        
        'messaging_product' => 'whatsapp',
        'to' => $phone,
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
                    array("type"=> "text","text"=> $wa_client),
                    array("type"=> "text","text"=> $wa_date),
                    array("type"=> "text","text"=> $wa_spend),
                    array("type"=> "text","text"=> $wa_lead),
                    array("type"=> "text","text"=> $wa_cpl)
                )))
          ))
        );
        $ch = curl_init('https://graph.facebook.com/v16.0/100284149552425/messages'); // Initialise cURL
        $post = json_encode($attachment); // Encode the data array into a JSON string
        $authorization = "Authorization: Bearer ".$tok; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection
        return json_decode($result); // Return the received data
        //print_r($result); 
  
}
if(isset($_POST['submit'])){
	//print_r($_POST); exit;
	if($_POST['client']!='' || $_POST['client']!='') {
		
		    //$phone = $_POST['countryCode'].''.$_POST['phone'];
        $phone = $_POST['phone'];
        $wa_client = $_POST['client'];
        $wa_date = $_POST['date'];
        $wa_spend = $_POST['spend'];
        $wa_lead = $_POST['lead'];
        $wa_cpl = $_POST['cpl'];
      $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
	    $result = mysqli_query($conn, $query);
	    $row = mysqli_fetch_assoc($result);
	
	    $access_token = $row['access_token']; 

        $wa_res = sendWhatsapp($access_token, $phone, $wa_client, $wa_date, $wa_spend, $wa_lead, $wa_cpl);
        $wa_res = json_decode(json_encode($wa_res), true);
        if(isset($wa_res['error']['message'])) {
          //d($wa_res); exit;
            $_SESSION['err'] = 'Failed: '.$wa_res['error']['message'];
        }
        if(isset($wa_res['messages'][0]['id'])) {
            $_SESSION['suc'] = 'Success: Message sent!';
            //echo 'Success: Message sent! (message ID: '.$wa_res['messages'][0]['id'].')';
        }

		//$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'whatsapp.php';</script>";
		exit();
	} 
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
                  <?php include 'alert.php'; ?>
                  <?php 
						if(!isset($_SESSION['fb_id']) || $_SESSION['fb_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="loading.php?pg=fb-login.php">
                                      <img src="images/fb-login.png">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} else { ?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-5">     
                                                	<label>Phone: </label>
                                                   <!-- <input type="hidden"  id="countryCode" name ="countryCode" value="91" >
                                                    <input type="text" id="phone" name="phone" class="form-control" placeholder="10 digit mobile number" minlength="10" maxlength="10" required>                                           	
                                                    <br />      -->
                                                    <select name="phone" class="form-control" required>
                                                        <option value="919176299010">Prabhu - 91 9176299010</option>
                                                        <option value="919840619930">Ramesh - 91 9840619930</option>
                                                        <option value="919840031390">Faheem - 91 9840031390</option>
                                                    </select>
                                                    <br /> 

                                                    
                                                    <label>Client: </label>
                                                    <input type="text" name="client" class="form-control" required>                                                    	
                                                    <br /> 

                                                    <label>Date: </label>
                                                    <input type="text" name="date" class="form-control" required>                                                    	
                                                    <br />   

                                                    <label>Spend : </label>
                                                    <input type="text" name="spend" class="form-control" required>                                                    	
                                                    <br />   

                                                    <label>Lead : </label>
                                                    <input type="text" name="lead" class="form-control" required>                                                    	
                                                    <br />   

                                                    <label>CPL: </label>
                                                    <input type="text" name="cpl" class="form-control" required>                                                    	
                                                    <br />   
                                                    <br />
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-info">
                                                 
                                                 <br />
                                                </div>
                                        </div>
                                    </div>
                                </div>
                                </form>
                             <?php } ?>  
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<script src='https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.7/js/intlTelInput.js'></script>
<script>
  

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