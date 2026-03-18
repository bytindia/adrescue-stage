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

#progress { display: none }
i.fa.fa-question-circle {
    cursor: pointer; font-size: 16px;
}
 .modal-body { background: #f2f3f5; }
</style>
<body>
<div class="right_col" role="main">
          <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel"></h4>
                    </div>
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
</div>

<div id="progress">
<center><div class="progress" style="display: none;"></div><img src="images/progress-bar.gif" /><br> Creating Ads... Please wait! <br><br><small>This will take 1 to 2 minutes!</small></center>
</div>
<style>
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
input[type='file'] {
    border: none;
    box-shadow: none;
}
.modal-body { width: 100%; }
</style>
<?php //echo phpinfo(); print_r($_FILES);
include '../db.php';
$pg='facebook';
include 'config.php';

function curlPost($url, $post){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

 $ad_acc = array(
  5656356187816184 =>'ep',
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

<div id="body_container">
<!--<span class=" float-right">
  <a class="btn btn-sm btn-success" href="loading.php?pg=create.php">Create Ad</a>
  <a class="btn btn-sm btn-danger" href="loading.php?pg=pause-ad.php">Stop Ad</a>
  <a class="btn btn-sm btn-default" href="logout.php?placement">Logout</a>
</span>-->
<?php include 'menu-top.php'; ?>
<br><br>
<h3>CasaGrand - Create Ad </h3>    <br>
<form method="post" action="create-submit.php" method="post" enctype="multipart/form-data" onsubmit="return formValidation();">

  <div class="form-row">
    <div class="form-group col-md-12">
      
      <select name="ad_acc" id="ad_acc" class="form-control" required>
        <option value="" selected>Select Ad Account</option>
        <?php foreach($ad_acc as $k => $v) { ?>
        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="clearfix"></div>
   
    <div class="form-group col-md-6">
      <label for="inputZip">Daily budget</label>
      <input type="text" value="100" class="form-control" name="d_budget" id="d_budget" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');" required>
    </div>
    <div class="form-group col-md-6">
      <label for="inputZip">Link Title</label> <a onClick="onAjax(2);" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
      <input type="text" value="" class="form-control" name="title" id="title"  required>
    </div>
    <div class="form-group col-md-6">
      <label for="inputZip">Link Description</label> <a onClick="onAjax(2);" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
      <textarea type="text" value="" class="form-control" name="desc" id="desc"  required rows="3"></textarea>
    </div>
    <div class="form-group col-md-6">
      <label for="inputZip">Post message</label> <a onClick="onAjax(2);" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
      <textarea type="text" value="" class="form-control" name="message" id="message"  required rows="3"></textarea>
    </div>
    
  </div>
  <div class="clearfix"></div>
  <h4>Images / Video</h4><br>
  <div class="form-row">
    <div class="form-group col-md-3">
      <label for="inputState">Image 1 <small>(feed, in-stream. 1080 x 1080 px)</small> <a onClick="onAjax(1);" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><i class="fa fa-question-circle" aria-hidden="true"></i></a></label>
      <input type="file" name="up_image" class="form-control" id="up_image"  accept=".jpg,.gif,.png,.jpeg">
    </div>
    <div class="form-group col-md-3">
      <label for="inputState">Image 2 <small>(stories, reels.  1080 x 1920 px)</small> <a onClick="onAjax(1);" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><i class="fa fa-question-circle" aria-hidden="true"></i></a></label>
      <input type="file" name="up_image2" class="form-control" id="up_image2"  accept=".jpg,.gif,.png,.jpeg">
    </div>
    <div class="form-group col-md-3">
      <label for="inputState">Image 3 <small>(articles, search.  1200 x 630 px)</small> <a onClick="onAjax(1);" data-id="1" data-toggle="modal" class="blue modal-cl" data-target=".bs-example-modal-lg"><i class="fa fa-question-circle" aria-hidden="true"></i></a></label>
      <input type="file" name="up_image3" class="form-control" id="up_image3"  accept=".jpg,.gif,.png,.jpeg">
    </div>
    <div class="form-group col-md-3">
      <label for="inputZip">Video</label>
      <input type="file" name="up_video" class="form-control" id="up_video" accept=".mp4,.mov">
    </div>
    
  </div>
  <div class="clearfix"></div>
  <div class="alert alert-danger hide_alert text-center" role="alert">
   <span class="alertMsg"></span>
</div>
<br>
  <div class="form-group">
    <div class="form-group col-md-12">
        <button type="submit" name="submit" class="btn btn-lg btn-success btn-block" onclick="return formValidation();">Create Ad</button>
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
    var d_budget = $('#d_budget').val();
    var up_image = $('#up_image').val();
    var up_video = $('#up_video').val();
    var title = $('#title').val();
    var desc = $('#desc').val();
    var message = $('#message').val();
    //alert(up_image);
    
    
    
    if(message==''){
        errMsg ='Post message should not be empty!';
    }
    if(desc==''){
        errMsg ='Post message should not be empty!';
    }
    if(title==''){
        errMsg ='Post message should not be empty!';
    }
    if(up_image=='' && up_video==''){
       errMsg ='You must upload a image / video';
    }
    if(up_image!='' && (up_image2=='' || up_image3=='')){
       errMsg ='You must upload all 3 images';
    }
    if(d_budget=='' || d_budget<100){
        errMsg ='budget should not be empty or below 100';
    }
    if(ad_acc==''){
        errMsg ='Select the Ad account';
    }
    
    if(errMsg!=''){ //alert(1);
        $('.hide_alert').css('display','block');
        $('.alertMsg').html('<b>'+errMsg+'</b>');
        $('#body_container').css('display','block');
        return false;
    } else { //alert(2);
        $('.hide_alert').css('display','none');
        $('#body_container').css('display','none');
        $('#progress').css('display','block');
        return true;
    }
}
function onAjax(ty) {
    $('.modal-body').html('loading...');
    $('.modal-body').css('text-align','center');
    $('.modal-body').show();
    if(ty=='1') {
      $('#myModalLabel').html('Ad Image Placements');
      $('.modal-body').html('<img src="images/ref-image.png" style="width:100%" />');
    } else {
      $('#myModalLabel').html('Ad Text Preview');
      $('.modal-body').html('<img src="images/ref-post.png"  />');
    }
    
					
};
</script>
</body>