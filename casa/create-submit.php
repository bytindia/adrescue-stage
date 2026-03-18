<?php //echo phpinfo(); exit; ?>
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
<link href="/web/pagination.css" rel="stylesheet">
<link href="/assets/css/pagination.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/style.css" />
<link rel="stylesheet" type="text/css" href="style.css" />
<style>
#body_container { text-align: center; }
a { text-decoration: none;}
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
    padding: 10px;
    text-align: center !important;
    /* position: relative; */
    /* float: left; */
    /* width: 100%; */
    /* display: inline-table; */
}
</style>
<body>
<span class=" float-right">
  <a class="btn btn-sm btn-success" href="loading.php?pg=create.php">Create Ad</a>
  <a class="btn btn-sm btn-danger" href="loading.php?pg=pause-ad.php">Stop Ad</a>
  <a class="btn btn-sm btn-default" href="logout.php?placement">Logout</a>
</span>
<div class="clearfix"></div>
<div id="body_container">
<?php //echo phpinfo(); print_r($_FILES);
include '../db.php';
$pg='facebook';
include 'config.php';

$adStatus = 'ACTIVE';

function curlPost($url, $post){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
//echo '<h3 class="loadmsg">Please wait! Loading</h3>';

if(isset($_POST['submit']))
{
   // print_r($_POST); print_r($_FILES); exit;
   
    $upImage = $upVideo = $upImage_ext = $upVideo_ext = $upImage2 =  $upImage_ext2 = $upImage3 =  $upImage_ext3 ='';

    $accId = $_POST['ad_acc'];
    $title_ad = mysqli_real_escape_string($conn, $_POST['title']);
    $desc_ad = mysqli_real_escape_string($conn, $_POST['desc']);
    $msg_ad = mysqli_real_escape_string($conn, $_POST['message']);
    $daily_budget = $_POST['d_budget'];
    //d($msg_ad); 
     if(isset($_FILES['up_video']) && $_FILES['up_video']['name']!='') {
        $errors= array();
        $file_name = $_FILES['up_video']['name'];
        $file_size =$_FILES['up_video']['size'];
        $file_tmp =$_FILES['up_video']['tmp_name'];
        $file_type=$_FILES['up_video']['type'];
        $file_ext=strtolower(end(explode('.',$_FILES['up_video']['name'])));
        
        $extensions= array("mov","mp4");
        
        if(in_array($file_ext,$extensions)=== false){
           $errors[]="extension not allowed, please choose a mov or mp4 file.";
        }
        
        if($file_size > 2097152){
           //$errors[]='File size must be excately 2 MB';
        }
        
        if(empty($errors)==true){
           $temp = explode(".", $_FILES["up_video"]["name"]);
           $newfilename = 'Casa_'.date('dmY_his'). '.' . $file_ext;
           move_uploaded_file($file_tmp,"uploads/".$newfilename);
           //echo "Success";
           $upVideo = $newfilename;
           
        }else{
           print_r($errors); exit;
        }
     }
     // image 1
     if(isset($_FILES['up_image']) && $_FILES['up_image']['name']!='') {
        $errors= array();
        $file_name = $_FILES['up_image']['name'];
        $file_size =$_FILES['up_image']['size'];
        $file_tmp =$_FILES['up_image']['tmp_name'];
        $file_type=$_FILES['up_image']['type'];
        $file_ext=strtolower(end(explode('.',$_FILES['up_image']['name'])));
        
        $extensions= array("jpeg","jpg","png");
        
        if(in_array($file_ext,$extensions)=== false){
           $errors[]="extension not allowed, please choose a JPEG or PNG file.";
        }
        
        if($file_size > 2097152){
           //$errors[]='File size must be excately 2 MB';
        }
        
        if(empty($errors)==true){
           $temp = explode(".", $_FILES["up_image"]["name"]);
           $newfilename = 'Casa_img_1_'.date('dmY_his'). '.' . $file_ext;
           move_uploaded_file($file_tmp,"uploads/".$newfilename);
           //echo "Success";
           $upImage = $newfilename;
           $upImage_ext = $file_ext;
           //echo $upImage; exit;
           //include 'create-inc.php';
           //echo "Success";
        }else{
           print_r($errors); exit;
        }
     }
     // image 2
     if(isset($_FILES['up_image2']) && $_FILES['up_image2']['name']!='') {
      $errors= array();
      $file_name2 = $_FILES['up_image2']['name'];
      $file_size2 =$_FILES['up_image2']['size'];
      $file_tmp2 =$_FILES['up_image2']['tmp_name'];
      $file_type2 =$_FILES['up_image2']['type'];
      $file_ext2 =strtolower(end(explode('.',$_FILES['up_image2']['name'])));
      
      $extensions2 = array("jpeg","jpg","png");
      
      if(in_array($file_ext2,$extensions2)=== false){
         $errors[]="extension not allowed, please choose a JPEG or PNG file.";
      }
      
      if($file_size2 > 2097152){
         //$errors[]='File size must be excately 2 MB';
      }
      
      if(empty($errors)==true){
         $temp2 = explode(".", $_FILES["up_image2"]["name"]);
         $newfilename2 = 'Casa_img_2_'.date('dmY_his'). '.' . $file_ext2;
         move_uploaded_file($file_tmp2,"uploads/".$newfilename2);
         //echo "Success";
         $upImage2 = $newfilename2;
         $upImage_ext2 = $file_ext2;
         //include 'create-inc.php';
         //echo "Success";
      }else{
         print_r($errors); exit;
      }
   }
   // image 3
   if(isset($_FILES['up_image3']) && $_FILES['up_image3']['name']!='') {
      $errors= array();
      $file_name3 = $_FILES['up_image3']['name'];
      $file_size3 =$_FILES['up_image3']['size'];
      $file_tmp3 =$_FILES['up_image3']['tmp_name'];
      $file_type3 =$_FILES['up_image3']['type'];
      $file_ext3 =strtolower(end(explode('.',$_FILES['up_image3']['name'])));
      
      $extensions3 = array("jpeg","jpg","png");
      
      if(in_array($file_ext3,$extensions3)=== false){
         $errors[]="extension not allowed, please choose a JPEG or PNG file.";
      }
      
      if($file_size3 > 2097152){
         //$errors[]='File size must be excately 2 MB';
      }
      
      if(empty($errors)==true){
         $temp3 = explode(".", $_FILES["up_image3"]["name"]);
         $newfilename3 = 'Casa_img_3_'.date('dmY_his'). '.' . $file_ext3;
         move_uploaded_file($file_tmp3,"uploads/".$newfilename3);
         //echo "Success";
         $upImage3 = $newfilename3;
         $upImage_ext3 = $file_ext3;
         //include 'create-inc.php';
         //echo "Success";
      }else{
         print_r($errors); exit;
      }
   }
   if(($upImage!='' && $upImage2!='' && $upImage3!='') || $upVideo!=''){
      include 'create-inc.php'; 
   } else {
      echo 'Image / Video upload failed! <a href="create.php" class="btn btn-sm btn-success">Go Back</a>'; exit;
   }
     exit;
     
}
?>
</div>
</body>
<a class="btn btn-sm btn-success" href="loading.php?pg=create.php">Create New Ad</a> | <a class="btn btn-sm btn-success" href="loading.php?pg=create.php">Cancel</a>