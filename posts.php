<?php include 'header.php'; 


Auth();
$pgHeadline = 'Post Engagement';
$pgID = 3;
$err =''; 

if(isset($_POST['submit'])){
	
	$cirSql = "UPDATE pages SET active='0', updated=now() where uid='".$_SESSION['uid']."'";
	mysqli_query($conn, $cirSql) or die(mysqli_error()); 
	
	if(isset($_POST['pg_id']) && count($_POST['pg_id'])>0) {
		//echo (count($_POST['ads_id'])); 
		$ids = implode(",", $_POST['pg_id']);
		$cirSql2 = "UPDATE pages SET active='1', updated=now() where tbl_id in (".$ids.") && uid='".$_SESSION['uid']."'";
		mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
	} 
	//echo count($_POST['ads_id']); exit;
	//print_r($_POST['ads_id']);
	//exit;
	
	
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'pages.php';</script>";
	exit();
}


include 'config.php';


include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 100; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " pages WHERE uid='".$_SESSION['uid']."'";

$getID = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pg_name FROM pages WHERE pg_id = '".$_GET['id']."'"));
$cName = $getID['pg_name'];
$pgHeadline = $cName.' - Post Engagement';

?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
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
						} 
						else {
								$userRes2 = mysqli_query($conn, "select pg_token from pages WHERE pg_id='".$_GET['id']."' and uid=".$_SESSION['uid']."");	
								$getRw2 = mysqli_fetch_assoc($userRes2);
										   //$lastId = $getRw['tbl_id']; 
										   
								$access_token = $getRw2['pg_token'];
								//$access_token ='REDACTED_FB_TOKEN';
										
								$start = strtotime(date('m/d/Y',strtotime('today - 10 days')));
								$end = strtotime(date('m/d/Y'));
											
 
								//$request_url = "https://graph.facebook.com/".$api_ver."/".$_GET['id']."/posts?access_token=".$access_token."&fields=id,name,created_time,updated_time,type,object_id&since=".$start."&until=".$end."&limit=50";
								 $request_url = "https://graph.facebook.com/".$api_ver."/".$_GET['id']."/posts?access_token=".$access_token."&fields=id,message,created_time,updated_time&limit=10";
										//FbPages($request_url, $conn);
								?>
                                <form method="post" action="">
                                <table class="table table-hover table-striped">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Post Id</th>
                                    	<th>Post Name</th>   
                                        <th>Post Type</th>  
                                        <th>Post Clicks</th> 
                                        <th>Post Activity</th> 
                                        <th>Total Engagement</th>                                                       	
                                    </thead>
                                    <tbody>
                                    	<?php
										$requests = file_get_contents_curl($request_url);
										$fb_response = json_decode($requests);
										$i =1;
										foreach ($fb_response->data as $key => $response) {
											//572822292892223_747430835431367/insights?fields=values&metric=post_activity_by_action_type,post_clicks,post_activity
											//$eng_url = "https://graph.facebook.com/".$api_ver."/".$response->id."/insights?access_token=".$access_token."&fields=values&metric=post_activity_by_action_type,post_clicks,post_activity";
											//$eng_req = file_get_contents_curl($eng_url);
											//$eng_res = json_decode($eng_req);
											//print_r($eng_res); 
											//print_r($eng_res->data[1]->values[0]->value);	exit;
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><a href="loading.php?pg=https://www.facebook.com/<?php echo $response->id; ?>" target="_blank" class="blue"><?php echo $response->id; ?></td></td>
                                        	<td><?php echo $response->message; ?></td>  
                                            <td><?php //echo $response->type; ?></td>          
                                            <td><?php //echo $eng_res->data[1]->values[0]->value; ?></td> 
                                            <td><?php //echo $eng_res->data[2]->values[0]->value; ?></td> 
                                            <td><?php //echo  ($eng_res->data[1]->values[0]->value) +  ($eng_res->data[2]->values[0]->value); ?></td>                                                                	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>

					<?php	}	?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>