<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        return $contents;
        
}
function curlPost($url, $post){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
} 

$pgHeadline = 'Post promotion - Pause Ad';
$pgID = 8;
$err =''; 
$query = "SELECT access_token FROM users WHERE tbl_id='".$_SESSION['uid']."'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
	
$access_token = $row['access_token']; 


if(isset($_GET['pg_id']) && isset($_GET['ad_id']) ){
	//d($_POST);  exit;
	// print_r($_POST); print_r($_FILES); exit;
  $ad_id = $_GET['ad_id'];
  $post = ['status' => 'PAUSED','access_token' => $access_token];
  $url  = "https://graph.facebook.com/".$api_ver."/".$ad_id."";
  $req = curlPost($url, $post);
  //d($req); exit;
  mysqli_query($conn, "UPDATE post_promote_ads SET pause_ad='1' where ad_id=".$_GET['ad_id']." AND page_id=".$_GET['pg_id']."");
  $_SESSION['suc'] = 'Ad paused successfully!';
  echo "<script>window.location = 'post-promo-view.php?pg_id=".$_GET['pg_id']."';</script>";
}
?>
<style>
    .box {
   background: #fff;
   border-radius: 4px;
   padding-bottom: 100%;
}

.col-lg-2, .col-md-3, .col-xs-6{
    margin-top: 30px !important;
}
.fb_iframe_widget {
  background: url(images/ezgif-4-bb9e5e7733.gif) no-repeat center;
  height: 420px;
  overflow: hidden;
  background-position: top center;
}
</style>

  <body class="nav-md">
  <div id="fb-root"></div>
<?php
include 'pagination.php';
$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;
$per_page = 20;
?>
<script async="1" defer="1" crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v17.0" nonce="FCEn2uE5"></script>
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
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=post-promotion.php"  class="btn btn-success btn-sm">View Accounts</a>                        
                      </div>    
                      </li>                  
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                        function get_string_between($string, $start, $end){
                            $string = ' ' . $string;
                            $ini = strpos($string, $start);
                            if ($ini == 0) return '';
                            $ini += strlen($start);
                            $len = strpos($string, $end, $ini) - $ini;
                            return substr($string, $ini, $len);
                        }

                        include 'alert.php';
                        
                        $page_id = 158034550097;
                        $page_id = $_GET['pg_id'];
                        //$sqlRev=mysqli_query($conn, "SELECT * FROM post_promote_ads WHERE uid='".$_SESSION['uid']."' and page_id='".$page_id."' order by tbl_id desc");
                        $startpoint = ($page * $per_page) - $per_page;
                        //$statement = " adAccounts WHERE uid='".$_SESSION['uid']."'";
                        $statement = " post_promote_ads  WHERE  page_id='".$page_id."' AND pause_ad='0'";
                        //echo "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}"; 
                        $sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
                        //$sqlRev=mysqli_query($conn, "SELECT * FROM post_promote_ads WHERE  page_id='".$page_id."' AND pause_ad='0' order by tbl_id desc");
                       
										
								?>
             
                            <div class="container mt-5 mb-5">
                                    <?php
                                     while($sqlROW=mysqli_fetch_array($sqlRev))
                                         {  
                                         
                                          //echo $sqlROW['uid'].'_'.$sqlROW['post_id'];   
                                          $exp_postId = explode('_',$sqlROW['post_id']);  
                                         // echo $exp_postId[1];
                                         // echo '<br>';
                                         ?>
                                      
                                        <div class="col-md-3" style="min-height: 420px;">
                                            <div class="p-card bg-white p-2 rounded px-3">
                                            <div class="fb-post" data-href="https://www.facebook.com/permalink.php?story_fbid=<?php echo $exp_postId[1]; ?>&id=<?php echo $sqlROW['uid']; ?>" data-width="350"></div>
                                            </div>
                                            <div class="d-flex flex-row align-items-center text-center">
                                              
                                                 <a href="post-promo-view.php?pg_id=<?php echo $page_id; ?>&ad_id=<?php echo $sqlROW['ad_id']; ?>" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to pause this Ad?');">Pause Ad</a>
                                                 <b>Created:</b> <?php echo date('d-m-Y, h:i a', strtotime($sqlROW['created'])); ?>
                                            </div>
                                        </div>
                                                                        
                                        <?php } ?>
                                        
                                        
                                </div>
                                <div id="pagDiv" style=" float: right;"><?php echo pagination($statement,$per_page,$page,$url='?pg_id='.$page_id.'&',''); ?></div>
        				</div>
                </div>
              </div></div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
