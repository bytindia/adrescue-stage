<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Post promotion - setup';
$pgID = 8;
$err =''; 
$query = "SELECT access_token FROM users WHERE tbl_id='".$_SESSION['uid']."'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
	
$access_token = $row['access_token']; 

function getLead($pg_id, $pg_access_token, $api_ver) {
    //fetch lead info from FB API
    $graph_url= 'https://graph.facebook.com/'.$api_ver.'/'.$pg_id.'/subscribed_apps?subscribed_fields=feed';
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

function get_data($url, $key) {
    echo $url;
	$ch[$key] = curl_init();
	$timeout = 5;
	curl_setopt($ch[$key], CURLOPT_URL, $url);
		curl_setopt($ch[$key], CURLOPT_HEADER, 0);
		curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, 0);
	$data = curl_exec($ch[$key]);
	curl_close($ch[$key]);
	$data = json_decode($data,true);
    //curl_multi_add_handle($mh, $multiCurl[$key]);
    unset($ch[$key]);
	return $data;
}

function callCURL($fetchURLs) {
    // array of curl handles
    $multiCurl = $result = array();

    $mh = curl_multi_init();
    foreach($fetchURLs as $key => $val) {
        //$fetchURL = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["region"]&type=adgeolocation&q='.trim($val).'&access_token='.$access_token;
        //$fetchURL = 'https://webkul.com&customerId='.$id;
        $multiCurl[$key] = curl_init();
        curl_setopt($multiCurl[$key], CURLOPT_URL,$val);
        curl_setopt($multiCurl[$key], CURLOPT_HEADER,0);
        curl_setopt($multiCurl[$key], CURLOPT_RETURNTRANSFER,1);
        curl_multi_add_handle($mh, $multiCurl[$key]);
    }
    $index=null;
    do {
    curl_multi_exec($mh,$index);
    } while($index > 0);
    // get content and remove handles
    foreach($multiCurl as $k => $ch) {
        $res = curl_multi_getcontent($ch);
        $result[$k] = json_decode($res,true);
        curl_multi_remove_handle($mh, $ch);
     
    }
    //d($result);
    // close
    curl_multi_close($mh); //exit;
    return $result;
}

function stripQuotes($text) {
    return preg_replace('/^(\'[^\']*\'|"[^"]*")$/', '$2$3', $text);
} 

if(isset($_POST['submit'])){
	
	if($_POST['pg_id']!='') {
		
		
		//d($_POST);  exit;
        $cust_aud = '';
        if($_POST['googlesheet']) {
            $cust_aud = $_POST['cust_aud'];
        }
		
		$query = "SELECT pg_id, pg_name, pg_token FROM pages WHERE uid='".$_SESSION['uid']."' && pg_id='".$_POST['pg_id']."'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_assoc($result);
		$pg_id = $row['pg_id']; 
		$pg_name = $row['pg_name']; 
		$pg_token = $row['pg_token']; 
		
		//$subs = getLead($pg_id, $pg_token,$api_ver);
		//echo $subs->error['message']; 
		//d($subs); exit;
		$subs->success = 1;
		if(isset($subs->success) && $subs->success==1) {

            $loc_reg_val = $loc_city_val = $loc_country_val = array();
            $urls_reg = $urls_city = $urls_country = '';
            //Location - Region
            if(isset($_POST['loc_reg']) && trim($_POST['loc_reg'])!=''){
                $loc_reg = stripQuotes($_POST['loc_reg']);
                $loc_reg_ar = explode(',', $loc_reg);
                foreach($loc_reg_ar as $key => $val) {
                    $urls_reg[] = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["region"]&type=adgeolocation&q='.trim($val).'&access_token='.$access_token;
                   
                }
                if($urls_reg!=''){
                    $loc_reg_res = callCURL($urls_reg);
                    foreach($loc_reg_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $loc_reg_val[$val['data'][0]['key']] = $val['data'][0]['name'];
                        }
                    }
                }
            }
            //Location - City
            if(isset($_POST['loc_city']) && trim($_POST['loc_city'])!=''){
                $loc_city = stripQuotes($_POST['loc_city']);
                $loc_city_ar = explode(',', $loc_city);
                foreach($loc_city_ar as $key => $val) {
                    $urls_city[] = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["city"]&type=adgeolocation&q='.trim($val).'&access_token='.$access_token;
                   
                }
                if($urls_city!=''){
                    $loc_city_res = callCURL($urls_city);
                    foreach($loc_city_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $loc_city_val[$val['data'][0]['key']] = $val['data'][0]['name'];
                        }
                    }
                }
            }
            //Location - Country
            if(isset($_POST['loc_country']) && trim($_POST['loc_country'])!=''){
                $loc_country = stripQuotes($_POST['loc_country']);
                $loc_country_ar = explode(',', $loc_country);
                foreach($loc_country_ar as $key => $val) {
                    $urls_country[] = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["country"]&type=adgeolocation&q='.trim($val).'&access_token='.$access_token;
                   
                }
                if($urls_country!=''){
                    $loc_country_res = callCURL($urls_country);
                    foreach($loc_country_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $loc_country_val[$val['data'][0]['key']] = $val['data'][0]['name'];
                        }
                    }
                }
            }
            d($loc_reg_val); d($loc_city_val); d($loc_country_val); exit;

            //search?type=adinterest&class=interests&q=NoBroker.com&limit=1

            
		} 
        if(isset($subs->error->message) && $subs->error->message!='') { 
            $_SESSION['err'] = $subs->error->message;	
		    echo "<script>window.location = 'leads-acc.php';</script>"; exit;
        } //exit;
        
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'leads-acc.php';</script>"; exit;
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
                  		<?php 
                        $sqlROWs_acc =  array();
										
                        $sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                        while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs_acc[] = $sqlROW; }


									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT client_name, pg_name, pg_id, email_ids, words_like, emails_to, webhook_url,googlesheet,googlesheet_tab,googlesheet_id FROM leads_acc WHERE tbl_id='".$_GET['id']."'");
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
                                                	<label>Client: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client_name'])) { ?> value="<?php echo $editData['client_name']; ?>" <?php } ?>>                                                    	
                                                    <br />      
                                                            
                                                 	<label>FB Page: </label>
                                                    <select name="pg_id" id="pg_id" class="form-control">
                                                    	<option value="">Select Facebook Ad Account</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["pg_id"]; ?>" <?php if(isset($editData['pg_id']) && $editData['pg_id']==$sqlROW["pg_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["pg_name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                   
                                                    <br />

                                                    <label>FB Ad Account: </label>
                                                    <select name="fb_acc" id="fb_acc" class="form-control">
                                                    	<option value="">Facebook Ad Account</option>
                                                    	<?php foreach($sqlROWs_acc as $sqlROW){  ?>
																				<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($fb_val) && $fb_val==$sqlROW["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
																				 <?php } ?>
                                                    </select>
                                                    <br />

                                                    <span class="cust_aud">
                                                    </span>

                                                    <span  class="cls_em">
                                                    <label>Age: </label>
                                                    <div class="form-inline">
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-name">Minimum</label>
                                                                            <input type="text" name="age_min" class="form-control" id="field-name" placeholder="Minimum" size="35" value="<?php echo $words_like[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Maximum</label>
                                                                            <input type="text" name="age_max"  class="form-control" id="field-value" placeholder="Maximum" size="35" value="<?php echo $val; ?>">
                                                                        </div>
                                                    </div>
                                                     </span>
                                                     <br />     
                                                     <span  class="cls_em">
                                                    <label>Location: </label>
                                                    <div class="form-inline">
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-name">Region</label>
                                                                            <input type="text" name="loc_reg" class="form-control" id="field-name" placeholder="Region" size="35" value="<?php echo $words_like[$key]; ?>">
                                                                        </div>
                                                                        <span class="fa fa-paper-plane"> </span>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">City</label>
                                                                            <input type="text" name="loc_city"  class="form-control" id="field-value" placeholder="City" size="35" value="<?php echo $val; ?>">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="sr-only" for="field-value">Country</label>
                                                                            <input type="text" name="loc_country"  class="form-control" id="field-value" placeholder="Country" size="35" value="<?php echo $val; ?>">
                                                                        </div>
                                                    </div>
                                                     </span>
                                                    <br />      
                                                    <span  class="cls_em">
                                                    <label>Behaviors:</label>
                                                    <textarea  name="behaviour" class="form-control"> <?php if(isset($editData['email_ids'])) { echo $editData['email_ids']; } ?></textarea>  </span>  <small>use comma separated for multiple behaviours</small>
                                                    <br /><br />
                                                    
                                                    <span  class="cls_em">
                                                    <label>Interests:</label>
                                                    <textarea  name="interest" class="form-control"><?php if(isset($editData['googlesheet_tab'])) {  echo $editData['googlesheet_tab'];  } ?></textarea>  </span>
                                                    <small>use comma separated for multiple interests</small>
                                                    <br /><br />
                                                  
                                                    <br />
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
<script>
$( "#fb_acc" ).change(function() {  
    var ac_id = this.value;
    $( ".cust_aud" ).hide();
    if($( "#fb_acc" ).val()=='') {   
        $( ".cust_aud" ).hide(); //alert(1); 
    } else {   
         //alert(this.value); 

        $.ajax({
              type: 'POST',
              url: 'ajax-cust-aud.php',
              data:{id: ac_id, uid: '<?php echo $_SESSION['uid']; ?>'},
              success: (data) => { 
                if(data!='no-aud'){
                    $( ".cust_aud" ).show();
                    $(".cust_aud").html(data);
                }
              },
              error: (err) => {
               // $(".cust_aud", this).append(' <i class="fa fa-times" style="color:red; font-size: 20px; margin: 0px 0 0 20px;"></i>');
                $( ".cust_aud" ).hide();
                alert("error"+JSON.stringify(err));
              }
		});
    } 
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


$( "#g_acc" ).change(function() {  if($( "#g_acc" ).val()=='') {   $( ".cls_g" ).hide();  } else {   $( ".cls_g" ).show();  } });
$('input[type=radio][name=email_notif]').change(function() {  if(this.value==0) { $( ".cls_em" ).hide(); } else {   $( ".cls_em" ).show();  } });

</script>