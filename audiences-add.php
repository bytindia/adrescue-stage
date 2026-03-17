<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Create Custom Audiences';
$pgID = 8;
$err =''; 
include 'config.php';

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];

require __DIR__ . '/vendor/autoload.php';

		use FacebookAds\Object\AdAccount;
		use FacebookAds\Object\CustomAudience;
		use FacebookAds\Api;
		use FacebookAds\Logger\CurlLogger;
		
		use FacebookAds\Http\Exception\AuthorizationException;
		use FacebookAds\Http\Exception\RequestException;


$api = Api::init($app_id, $app_secret, $access_token);
$api->setLogger(new CurlLogger());
		
if(isset($_POST['submit'])){
	
	if($_POST['fb_acc']!='' || $_POST['fb_pg']!='' || $_POST['form_id']!='') 
	{
		if(isset($_GET['id'])) {
			$cirSql = "UPDATE audiences SET client_name='".mysqli_real_escape_string($conn, $_POST['client'])."', fb_acc='".mysqli_real_escape_string($conn, $_POST['fb_acc'])."',, audience_name='".mysqli_real_escape_string($conn, $_POST['audience_name'])."',  fb_pg='".mysqli_real_escape_string($conn, $_POST['fb_pg'])."',  form_type='".mysqli_real_escape_string($conn, $_POST['form_type'])."', form_type='".mysqli_real_escape_string($conn, $_POST['form_type'])."', updated=now() WHERE tbl_id=".$_GET['id']."";
			mysqli_query($conn, $cirSql) or die(mysqli_error()); 
			$lastId = $_GET['id'];
		} else {
			 echo $cirSql = "INSERT INTO audiences (uid, client_name, audience_name, fb_acc, fb_pg, form_id, form_type, created, updated) VALUES ('".$_SESSION['uid']."', '".mysqli_real_escape_string($conn, $_POST['client'])."', '".mysqli_real_escape_string($conn, $_POST['audience_name'])."', '".mysqli_real_escape_string($conn, $_POST['fb_acc'])."', '".mysqli_real_escape_string($conn, $_POST['fb_pg'])."', '".mysqli_real_escape_string($conn, $_POST['form_id'])."', '".mysqli_real_escape_string($conn, $_POST['form_type'])."', now(), now());"; 
			 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 $lastId = mysqli_insert_id($conn);
		} 
		
		//print_r($_POST); exit;
		
		$id = 'act_'.$_POST['fb_acc']; //account id
		$page_id = $_POST['fb_pg'];
		$form_id = $_POST['form_id'];
		
		
		
		$fields = array(
		);
		$params = array(
		  'name' => $_POST['audience_name'],
		  'rule' => array('inclusions' => array('operator' => 'or','rules' => array(array('event_sources' => array(array('id' => $form_id,'type' => 'lead')),'retention_seconds' => 7776000,'filter' => array('operator' => 'and', 'filters' => array(array('field' => 'event','operator' => 'eq','value' => $_POST['form_type']))))))),
		  'prefill' => '1',
		);
		echo json_encode((new AdAccount($id))->createCustomAudience($fields, $params)->exportAllData(), JSON_PRETTY_PRINT);

		
		$_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'audiences-list.php';</script>";
		exit();
	} 
}
?>

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
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
										$sqlD=mysqli_query($conn, "SELECT client_name, audience_name, fb_acc,  fb_pg, form_id FROM audiences WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
										//print_r($editData);
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM pages WHERE uid='".$_SESSION['uid']."' order by pg_name asc");
										$sqlRev3=mysqli_query($conn, "SELECT * FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">     
                                                	<label>Client Name: </label>
                                                    <input type="text" name="client" class="form-control" <?php if(isset($editData['client_name'])) { ?> value="<?php echo $editData['client_name']; ?>" <?php } ?>>                                                    	
                                                    <br />  
                                                    
                                                    <label>Custom Audience Name: (50 Character Max.)</label>
                                                    <input type="text" name="audience_name" class="form-control" <?php if(isset($editData['audience_name'])) { ?> value="<?php echo $editData['audience_name']; ?>" <?php } ?>>                                                    	
                                                    <br />      
                                                            
                                                 	<label>Facebook Account: </label>
                                                    <select name="fb_acc" id="fb_acc" class="form-control">
                                                    	<option value="">Select Facebook Ad Account</option>
                                                    	<?php while($sqlROW=mysqli_fetch_array($sqlRev)) { ?>
                                                    	<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($editData['fb_acc']) && $editData['fb_acc']==$sqlROW["account_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW["name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                   
                                                    <br /><br />
                                                   
                                                    <label>Facebook Page :  </label>
                                                    <select name="fb_pg" id="fb_pg" class="form-control">
                                                    	<option value="">Select Facebook Page</option>
                                                    	<?php while($sqlROW2=mysqli_fetch_array($sqlRev2)) { ?>
                                                    	<option value="<?php echo $sqlROW2["pg_id"]; ?>" <?php if(isset($editData['fb_pg']) && $editData['fb_pg']==$sqlROW2["pg_id"]) { echo "selected='selected'"; } ?>><?php echo $sqlROW2["pg_name"]; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    
                                                    <br /><br />
                                                   
                                                    <label>Lead Froms :  </label>
                                                    <select name="form_id" id="form_id" class="form-control">
                                                    	<option value="">Select Lead Form</option>
                                                    	
                                                    </select>
                                                  
                                                    <br /><br />
                                                    
                                                     <label>Lead Froms Type:  </label>
                                                    <select name="form_type" id="form_type" class="form-control">
                                                    	<option value="lead_generation_submitted">Lead Generation Submitted</option>
                                                        <option value="lead_generation_dropoff">Lead Generation Dropoff</option>
                                                    	<option value="lead_generation_opened">Lead Generation Opened</option>
                                                    </select>
                                                  
                                                    <br /><br />
                                                    
                                                   
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
<script type="text/javascript">
  $(document).ready(function()
  { /* PREPARE THE SCRIPT */
    $("#fb_pg").change(function(){ /* WHEN YOU CHANGE AND SELECT FROM THE SELECT FIELD */
      var fb_pg = $(this).val(); /* GET THE VALUE OF THE SELECTED DATA */
      var dataString = "fb_pg="+fb_pg; /* STORE THAT TO A DATA STRING */

      $.ajax({ /* THEN THE AJAX CALL */
        type: "POST", /* TYPE OF METHOD TO USE TO PASS THE DATA */
        url: "get-forms.php", /* PAGE WHERE WE WILL PASS THE DATA */
        data: dataString, /* THE DATA WE WILL BE PASSING */
        success: function(result){ /* GET THE TO BE RETURNED DATA */
			//alert(result);
          $("#form_id").html(result); /* THE RETURNED DATA WILL BE SHOWN IN THIS DIV */
        }
      });

    });
  });
</script>
