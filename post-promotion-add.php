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
	//d($_POST);  exit;
	if($_POST['pg_id']!='') {
		
		
		//d($_POST);  exit;
        $cust_aud = $insta_act_id = '';
        if(isset($_POST['cust_aud']) && count($_POST['cust_aud'])>0) {
            $cust_aud_ar = array_filter($_POST['cust_aud']);
            if(count($cust_aud_ar)>0) {
                $cust_aud = implode(",",$cust_aud_ar);
            }
        }
		//d($_POST); d($cust_aud);  exit;
		$query = "SELECT pg_id, pg_name, pg_token FROM pages WHERE uid='".$_SESSION['uid']."' && pg_id='".$_POST['pg_id']."'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_assoc($result);
		$pg_id = $row['pg_id']; 
		$pg_name = $row['pg_name']; 
		$pg_token = $row['pg_token']; 
		
		$subs = getLead($pg_id, $pg_token, $api_ver);
		//echo $subs->error['message']; 
		//d($subs); exit;
		//$subs->success = 1;
        if(isset($subs->error->message) && $subs->error->message!='') { 
            $_SESSION['err'] = $subs->error->message;	
		    echo "<script>window.location = 'post-promotion-add.php';</script>"; exit;
        } 

		if(isset($subs->success) && $subs->success==1) {

            $loc_reg_val = $loc_city_val = $loc_country_val = $behaviour_val = $interests_val = array();
            $urls_reg = $urls_city = $urls_country = $urls_behaviour = $urls_behaviour = array();

            //Instagram ID
            $insta_url[] = 'https://graph.facebook.com/'.$api_ver.'/'.$_POST['pg_id'].'?fields=page_backed_instagram_accounts{id,username}&access_token='.$pg_token;
            $inst_res = callCURL($insta_url);
            foreach($inst_res as $key => $val) {
                if(isset($val['page_backed_instagram_accounts']['data'][0]['id'])){
                    $insta_act_id = $val['page_backed_instagram_accounts']['data'][0]['id'];
                }
            }
           

            //Location - Region
            if(isset($_POST['loc_reg']) && trim($_POST['loc_reg'])!=''){
                $loc_reg = stripQuotes($_POST['loc_reg']);
                $loc_reg_ar = explode(',', $loc_reg);
                foreach($loc_reg_ar as $key => $val) {
                    $urls_reg[] = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["region"]&type=adgeolocation&q='.urlencode(trim($val)).'&limit=1&access_token='.$access_token;
                   
                }
                if(count($urls_reg)>0){
                    $loc_reg_res = callCURL($urls_reg);
                    
                    foreach($loc_reg_res as $key => $val) {
                       // d($val);
                        if(isset($val['data'][0])){
                            $loc_reg_val[$val['data'][0]['key']] = $val['data'][0]['name'];
                        }
                    }
                }
            }
            //d($loc_reg_val); exit;
            //Location - City
            if(isset($_POST['loc_city']) && trim($_POST['loc_city'])!=''){
                $loc_city = stripQuotes($_POST['loc_city']);
                $loc_city_ar = explode(',', $loc_city);
                foreach($loc_city_ar as $key => $val) {
                    $urls_city[] = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["city"]&type=adgeolocation&q='.urlencode(trim($val)).'&limit=1&access_token='.$access_token;
                   
                }
                if(count($urls_city)>0){
                    $loc_city_res = callCURL($urls_city);
                    foreach($loc_city_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $loc_city_val[$val['data'][0]['key']] = $val['data'][0]['name'];
                        }
                    }
                }
            }
            //Location - Country
            if(isset($_POST['loc_country']) && trim($_POST['loc_country'])!='') {
                $loc_country = stripQuotes($_POST['loc_country']);
                $loc_country_ar = explode(',', $loc_country);
                foreach($loc_country_ar as $key => $val) {
                    $urls_country[] = 'https://graph.facebook.com/'.$api_ver.'/search?location_types=["country"]&type=adgeolocation&q='.urlencode(trim($val)).'&limit=1&access_token='.$access_token;
                   
                }
                if(count($urls_country)>0){
                    $loc_country_res = callCURL($urls_country);
                    foreach($loc_country_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $loc_country_val[$val['data'][0]['country_code']] = $val['data'][0]['name'];
                        }
                    }
                }
            }
            //d($_POST);
            //Behaviour
            if(isset($_POST['behaviour']) && trim($_POST['behaviour'])!='') {
                $behaviour = stripQuotes($_POST['behaviour']);
                $behaviour_ar = explode(',', $behaviour);
                foreach($behaviour_ar as $key => $val) {
                    $urls_behaviour[] = 'https://graph.facebook.com/'.$api_ver.'/search?type=adinterestsuggestion&class=behaviors&interest_list=["'.urlencode(trim($val)).'"]&limit=1&access_token='.$access_token;
                   
                }
                if(count($urls_behaviour)>0){
                    $behaviour_res = callCURL($urls_behaviour);
                    //d($behaviour_res);
                    foreach($behaviour_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $behaviour_val[$val['data'][0]['id']] = $val['data'][0]['name'];
                        }
                    }
                }
            }

            //Interest
            if(isset($_POST['interest']) && trim($_POST['interest'])!='') {
                $interest = stripQuotes($_POST['interest']);
                $interest_ar = explode(',', $interest);
                foreach($interest_ar as $key => $val) {
                    $urls_interest[] = 'https://graph.facebook.com/'.$api_ver.'/search?type=adinterest&class=interests&q='.urlencode(trim($val)).'&limit=1&access_token='.$access_token;
                   
                }
                if(count($urls_interest)>0){
                    $interest_res = callCURL($urls_interest);
                    //d($interest_res);
                    foreach($interest_res as $key => $val) {
                        if(isset($val['data'][0])){
                            $interest_val[$val['data'][0]['id']] = $val['data'][0]['name'];
                        }
                    }
                }
            }

            if(isset($_GET['id'])) { 
				$cirSql = "UPDATE post_promotion SET client='".mysqli_real_escape_string($conn, $_POST['client'])."', d_budget='".mysqli_real_escape_string($conn, $_POST['d_budget'])."', fb_pg='".mysqli_real_escape_string($conn, $_POST['pg_id'])."',  fb_acc='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', age_min='".mysqli_real_escape_string($conn, $_POST['age_min'])."', age_max='".mysqli_real_escape_string($conn,$_POST['age_max'])."', loc_reg='".mysqli_real_escape_string($conn,serialize($loc_reg_val))."', loc_city='".mysqli_real_escape_string($conn,serialize($loc_city_val))."', loc_country='".mysqli_real_escape_string($conn,serialize($loc_country_val))."', behaviour='".mysqli_real_escape_string($conn,serialize($behaviour_val))."', interests='".mysqli_real_escape_string($conn,serialize($interest_val))."', cust_aud='".mysqli_real_escape_string($conn,$cust_aud)."', insta_act_id='".mysqli_real_escape_string($conn,$insta_act_id)."', updated=now() WHERE tbl_id=".$_GET['id']."";
				mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				$lastId = $_GET['id']; //exit;
			} else { 
				$cirSql = "INSERT INTO post_promotion (uid, client, d_budget,fb_pg, fb_acc, age_min, age_max, loc_reg, loc_city, loc_country, behaviour, interests, cust_aud, insta_act_id, created) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['d_budget'])."', '".mysqli_real_escape_string($conn, $_POST['pg_id'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['age_min'])."','".mysqli_real_escape_string($conn, $_POST['age_max'])."', '".mysqli_real_escape_string($conn, serialize($loc_reg_val))."', '".mysqli_real_escape_string($conn, serialize($loc_city_val))."', '".mysqli_real_escape_string($conn, serialize($loc_country_val))."', '".mysqli_real_escape_string($conn, serialize($behaviour_val))."', '".mysqli_real_escape_string($conn, serialize($interest_val))."', '".mysqli_real_escape_string($conn, $cust_aud)."', '".mysqli_real_escape_string($conn,$insta_act_id)."', now());"; 
				 mysqli_query($conn, $cirSql) or die(mysqli_error());
				 $lastId = mysqli_insert_id($conn);
			}
            //exit;
            //d($behaviour_val); d($interest_val); exit;
            //d($loc_reg_val); d($loc_city_val); d($loc_country_val); exit;
            //search?type=adinterest&class=interests&q=NoBroker.com&limit=1
		} 

        if(isset($subs->error->message) && $subs->error->message!='') { 
            $_SESSION['err'] = $subs->error->message;	
		    echo "<script>window.location = 'post-promotion-add.php?id=".$lastId."';</script>"; exit;
        } //exit;
        
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'post-promotion-add.php?id=".$lastId."';</script>"; exit;
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
/*******************************
* Does not work properly if "in" is added after "collapse".
* Get free snippets on bootpen.com
*******************************/
.panel-group .panel {
        border-radius: 5px;
        box-shadow: none;
        border-color: #337ab7;
    }

    .panel-default > .panel-heading {
        padding: 0;
        border-radius: 0;
        color: #fff;
        background-color: #337ab7;
        border-color: #EEEEEE;
    }

    .panel-title {
        font-size: 14px;
    }

    .panel-title > a {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
    }

    .more-less {
        float: right;
        color: #fff;
    }

    .panel-default > .panel-heading + .panel-collapse > .panel-body {
        border-top-color: #EEEEEE;
    }

/* ----- v CAN BE DELETED v ----- */

.demo {
    padding-bottom: 60px;
}
.x_content h4 { font-weight: bold; }
.small, small { margin-left: 10px;}
.bootstrap-tagsinput {
     width: 100%;
}
.bootstrap-tagsinput .tag { margin-right: 5px !important; }
span.tag { background:#5591bd !important; padding: 5px 5px  !important; font-size: 12px; margin-bottom: 0px !important; } 
.bootstrap-tagsinput .tag:after {
    display: none;
}
.bootstrap-tagsinput .tag [data-role="remove"] { font-weight: bold; }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" integrity="sha512-xmGTNt20S0t62wHLmQec2DauG9T+owP9e6VU8GigI0anN7OXLip9i7IwEhelasml2osdxX71XcYm6BQunTQeQg==" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js" integrity="sha512-9UR1ynHntZdqHnwXKTaOm1s6V9fExqejKvg5XMawEMToW4sSw+3jtLrYfZPijvnwnnE8Uol1O9BcAskoxgec+g==" crossorigin="anonymous"></script>

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
                        include 'alert.php';
                        $sqlROWs_acc =  array();
										
                        $sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                        while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs_acc[] = $sqlROW; }


									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT client, d_budget, fb_pg, fb_acc, age_min, age_max, loc_reg, loc_city,loc_country,behaviour,interests,cust_aud,insta_act_id FROM post_promotion WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
                                    //d($editData);
										//print_r($editData['words_like']);
										//$words_like = unserialize($editData['words_like']);
										//print_r($words_like);
										$sqlRev=mysqli_query($conn, "SELECT * FROM pages WHERE uid='".$_SESSION['uid']."' order by pg_name asc");
										//$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										
								?>
                                  <form method="post" action="" class="col-md-12">
                                <div class="container demo">

    
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingOne">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    <i class="more-less glyphicon glyphicon-triangle-bottom"></i>
                    Page & Ad Account
                </a>
            </h4>
        </div>
        <div id="collapseOne" class="panel-collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
            <div class="form-row">
                                            <div class="form-group col-md-6">
                                            <label>Client: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client'])) { ?> value="<?php echo $editData['client']; ?>" <?php } ?> required>  
                                            </div>
                                            <div class="form-group col-md-6">
                                            <label>Daily Budget: </label>
                                                    <input type="text" name="d_budget" class="form-control" <?php if(isset($editData['d_budget'])) { ?> value="<?php echo $editData['d_budget']; ?>" <?php } ?> required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                            <label>FB Page: </label>
                                                    <select name="pg_id" id="pg_id" class="form-control"  required>
                                                    	<option value="">Facebook Page</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["pg_id"]; ?>" <?php if(isset($editData['fb_pg']) && $editData['fb_pg']==$sqlROW["pg_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["pg_name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                            
                                            <label>FB Ad Account: </label>
                                                    <select name="fb_acc" id="fb_acc" class="form-control"  required>
                                                    	<option value="">Facebook Ad Account</option>
                                                    	<?php foreach($sqlROWs_acc as $sqlROW){  ?>
																				<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($editData['fb_acc']) && $editData['fb_acc']==$sqlROW["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
																				 <?php } ?>
                                                    </select>
                                            </div>
                                        </div>
                                        <div class="form-row cust_aud">
                                        </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingTwo">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <i class="more-less glyphicon glyphicon-triangle-bottom"></i>
                    Targeting
                </a>
            </h4>
        </div>
        <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
            <div class="panel-body">
            <div class="form-row">
            <?php 
                                                    $edit_loc_reg = $edit_loc_city = $edit_loc_country = ''; 
                                                    if(isset($editData['loc_reg'])) { $int_reg = unserialize($editData['loc_reg']); $edit_loc_reg = implode(',', $int_reg); } 
                                                    if(isset($editData['loc_city'])) { $int_city = unserialize($editData['loc_city']); $edit_loc_city = implode(',', $int_city); } 
                                                    if(isset($editData['loc_country'])) { $int_country = unserialize($editData['loc_country']); $edit_loc_country = implode(',', $int_country); } 
                                        ?>
                                            <div class="form-group col-md-3">
                                            <label>Age Min: </label>
                                            <input type="text" name="age_min" class="form-control" id="field-name" placeholder="Minimum" size="35" <?php if(isset($editData['age_min'])) { ?> value="<?php echo $editData['age_min']; ?>" <?php } ?>>
                                            </div>
                                            <div class="form-group col-md-3">
                                            <label>Age Max: </label>
                                            <input type="text" name="age_max"  class="form-control" id="field-value" placeholder="Maximum" size="35" <?php if(isset($editData['age_max'])) { ?> value="<?php echo $editData['age_max']; ?>" <?php } ?>>
                                            </div>
                                            <div class="form-group col-md-6">
                                            <label>State: </label>
                                            <input type="text" name="loc_reg" class="form-control"  size="35" value="<?php echo $edit_loc_reg; ?>"  data-role="tagsinput" id="tags">
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                        
                                        <div class="form-row">
                                            
                                            <div class="form-group col-md-6">
                                            <label>City: </label>
                                            <input type="text" name="loc_city" rows=""  class="form-control"  size="35" value="<?php echo $edit_loc_city; ?>"  data-role="tagsinput" id="tags"> 
                                            </div>
                                            <div class="form-group col-md-6">
                                            <label>Country: </label>
                                            <input type="text" name="loc_country"  class="form-control"   size="35"  value="<?php echo $edit_loc_country; ?>" data-role="tagsinput" id="tags">
                                            </div>
                                        </div>
                                        <?php 
                                                    $edit_loc_behaviour = $edit_loc_interest = ''; 
                                                    if(isset($editData['behaviour'])) { $int_behaviour = unserialize($editData['behaviour']); $edit_loc_behaviour = implode(',', $int_behaviour); } 
                                                    
                                                    if(isset($editData['interests'])) { $int_interest = unserialize($editData['interests']); $edit_loc_interest = implode(',', $int_interest); } 
                                        ?>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                            <label>Behaviors:</label>
                                                    <input type="text"   name="behaviour" rows="3" class="form-control" data-role="tagsinput" id="tags" value="<?php echo  $edit_loc_behaviour; ?>" />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                            <label>Interests:</label>
                                                    <input type="text"  name="interest" rows="3" class="form-control" data-role="tagsinput" id="tags" value="<?php echo  $edit_loc_interest; ?>" />
                                            </div>
                                            
                                        </div>
                                        <small><u>Note:</u> * use comma(,) separated for multiple location, interests & behaviours</small><br>
                                        
            </div>
        </div>
    </div>



</div><!-- container -->
                              
                                        
                                        
                                        <div class="form-row">
                                        
                                            <div class="form-group col-md-12">
                                                    <br>
                                                    <a href="post-promotion.php" class="btn btn-lg btn-default">Cancel</a>
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-lg btn-primary">
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

$( ".panel-title #collapseOne" ).click();
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
                   // clearInterval(interval);
                   $('.selectpicker').selectpicker();
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

<?php if(isset($editData['fb_acc']) && $editData['fb_acc']!='') { ?>
    $( ".cust_aud" ).show();
    $("#fb_acc").val('<?php echo $editData['fb_acc']; ?>').change();
    //$("#cust_aud").val('<?php echo $editData['cust_aud']; ?>').change();
   // var interval = setInterval(doStuff, 2000);
<?php } ?>
<?php if(isset($editData['fb_acc']) && $editData['fb_acc']!='' && isset($editData['cust_aud']) && $editData['cust_aud']!='') { ?>
    var interval = setInterval(doStuff, 2000); //2000 ms = start after 2sec 
    //clearInterval(interval);
function doStuff() {
        //$('#cust_aud').val(['<?php echo $editData['cust_aud']; ?>']);
        //$("#cust_aud").val('<?php echo $editData['cust_aud']; ?>');
       // $("#cust_aud").multiselect("rebuild");
        let values = "<?php echo $editData['cust_aud']; ?>";
        $('#cust_aud').selectpicker('val', values.split(','));
        clearInterval(interval);
   
}
<?php } ?>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>