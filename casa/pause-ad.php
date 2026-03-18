<!-- Include Required Prerequisites -->
<?php 
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start(); 
date_default_timezone_set("Asia/Calcutta");  

if(!isset($_SESSION['client'])) {
	$pg = 'login.php';
	$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<script>window.location = '".$pg."?redirect=".$fullUrl."';</script>";
	exit();
}
//print_r($_SESSION);
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
?>
<Title>AdRescue - Ads </Title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<!-- NProgress -->
<link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
<!-- iCheck -->
<link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">

<!-- jQuery -->
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
<link rel="stylesheet" type="text/css" href="style.css" />
<style> .hide_alert, #reportrange { display: none }
    .form-row { 
      width: 80%;
    text-align: center;
    margin: 0 auto; }
    .progress {
   width: 100.8px;
   height: 16.8px;
   -webkit-mask: linear-gradient(90deg,#2e94d5 70%,#0000 0) left/20% 100%;
   background: linear-gradient(#2e94d5 0 0) left/0% 100% no-repeat
       #dbdcef;
   animation: progress-422c3u 1.6s infinite steps(6);
}

@keyframes progress-422c3u {
   100% {
      background-size: 120% 100%;
   }
}
center {
  font-family: Trebuchet MS, sans-serif;
  padding: 15px;
  position: absolute;
  top: 50%;
  left: 50%;
  -ms-transform: translateX(-50%) translateY(-50%);
  -webkit-transform: translate(-50%,-50%);
  transform: translate(-50%,-50%);
  font-style: italic;
} 
#progress { display: none }
    </style>
<body>
<?php //echo phpinfo(); print_r($_FILES);
include '../db.php';
$pg='facebook';
include 'config.php';
global $camIds;
//$camIds = array();
function curlPost($url, $post){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}
function adAccounts($url) {
	//$request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
	$requests = file_get_contents_curl($url);
	$fb_response = json_decode($requests);
	//d($fb_response); exit;
	foreach ($fb_response->data as $key => $response) {			
			$camIds[] = $response->id;
     // echo  $response->id; exit;
	}  
	if(isset($fb_response->paging->next)) {
   // d($camIds); exit;
		adAccounts($fb_response->paging->next);
	} else {
		//exit;
    return $camIds;
	}
  return $camIds;
}
if(isset($_POST['submit']))
{
   // print_r($_POST); print_r($_FILES); exit;
   $acc_id = $_POST['ad_acc'];
  // $acc_id = 5656356187816184; 
   //act_735957617015640/campaigns?fields=effective_status&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['PAUSED']}]&date_preset=maximum
   //$request_url = "https://graph.facebook.com/v14.0/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=100";
   $request_url  = "https://graph.facebook.com/".$api_ver."/act_".$acc_id."/campaigns?fields=effective_status&filtering=[{'field':'campaign.effective_status','operator':'IN','value':['ACTIVE']}]&date_preset=maximum&access_token=".$access_token."&limit=1500";
   $getIds = adAccounts($request_url);
  // d($getIds); exit;
   foreach ($getIds as $key => $val) {		
      $post = ['status' => 'PAUSED','access_token' => $access_token];
      $url  = "https://graph.facebook.com/".$api_ver."/".$val."";
      $req = curlPost($url,$post);
      //d($req); exit;
   } 
   //window.location = 'pause-ad.php';
   $_SESSION['suc'] = 'Campaign paused successfully!';
   echo "<script>window.location = 'pause-ad.php?suc=1';</script>";
}
$ad_acc = array(
  
    427933624679014=>'Cloud9',
    1744658879060018=>'Boulevard',
    1196015513904250=>'Southbrooke',
    569001103516290=>'Aria',
    462105354650207=>'Elysium',
    370327086813811=>'Firstcity',
    807092046796106=>'Flagship',
    735957617015640=>'PlatinumJoy',
    834297526950773=>'Majestica',
    1317725875075405=>'Zenith',
    488023135037290=>'Divinity',
    689707531525571=>'Keatsway'
);
?> 
<div id="progress">
<center><div class="progress" style="display: none;"></div><img src="images/progress-bar.gif" /><br> Pausing campaigns... Please wait! <br><br><small>This will take few seconds!</small></center>
</div>
<div id="body_container">
<?php include 'menu-top.php'; ?>
<br><br>
<h3>CasaGrand - Pause Campaigns </h3>    <br>

<form method="post" method="post">

   <div class="form-row">
    
    <?php if(isset($_GET['suc'])) { ?>
      <div class="alert text-center alert-success">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <?php echo 'Success: All the Campaigns has been paused!'; ?></div>
    <?php unset($_GET['suc']); } ?>

    <div class="form-group col-md-12">
      <label for="inputState">Ad Account</label>
      <select name="ad_acc" id="ad_acc" class="form-control" required>
        <option value="" selected>Select Ad Account</option>
        <?php foreach($ad_acc as $k => $v) { ?>
        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="clearfix"></div>
    <div class="alert alert-danger hide_alert" role="alert">
      <span class="alertMsg"></span>
    </div>

    <div class="clearfix"></div>
    <div class="form-group col-md-12">
      <button type="submit" name="submit" class="btn btn-lg btn-danger btn-block" onclick="return formValidation();">Pause Campaigns</button>
    </div>
  </div>
</form>
</div>
<script>
//var fname = "the file name here.ext";
$('#progress').css('display','none');
function formValidation()
{
    $('#progress').css('display','none');
    $('#body_container').css('display','block');
    $('.hide_alert').css('display','none');
    var errMsg ='';
    var ad_acc = $('#ad_acc').val();
    
    if(ad_acc==''){
        errMsg ='Select the Ad account';
    }
    if(errMsg!=''){ //alert(1);
        $('.hide_alert').css('display','block');
        $('.alertMsg').html('<b>'+errMsg+'</b>');
        return false;
    } else { //alert(2);
        $('.hide_alert').css('display','none');
        if (confirm("Are you sure, you want to pause all active campaigns?") == true) {
          $('#body_container').css('display','none');
          $('#progress').css('display','block');
          return true;
        } else {
          $('#body_container').css('display','block');
          return false;
        }
        
    }
}
</script>
</body>