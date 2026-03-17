<?php include 'header.php'; 


Auth();
$pgHeadline = 'Facebook - Pages';
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
$statement = " pages WHERE uid='".$_SESSION['uid']."' AND active='1'";

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}
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
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.upcase { text-transform: uppercase; }
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
                    <?php if(isset($_SESSION['fb_id']) || $_SESSION['fb_id']!='') 	{ ?>
                    <span class="nav navbar-right panel_toolbox">                     
                      
                        Filter by: <a href="loading.php?pg=insights.php">Monthly</a> |  <a href="loading.php?pg=insights.php?sort=week">Weekly</a> 
                      
                    </span>
                    <?php } ?>
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
								if(isset($_GET['sort']) && $_GET['sort']=='week') { $filter_lm='last_week'; $filter_cm='this_week'; $m_text='Week'; } else { $filter_lm='last_month'; $filter_cm='this_month'; $m_text='Month'; } 
								
								$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
								$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table class="table table-hover table-striped">
                                    <thead>                                        
                                        <th colspan="2"></th>
                                                                                
                                        <th colspan="3" class="blue upcase">Last <?php echo $m_text; ?></th>
                                         
                                        <th colspan="3" class="blue upcase">This <?php echo $m_text; ?></th>                                                     	
                                    </thead>
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Page</th>
                                        
                                        <th>Organic</th>
                                    	<th>Paid</th>  
                                        <th>Engagement</th> 
                                         
                                        <th>Organic</th>
                                    	<th>Paid</th>  
                                        <th>Engagement</th>                                                       	
                                    </thead>
                                    <tbody>
                                    	<?php
										
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										//echo ''.$sqlROW["pg_id"].'/insights?access_token='.$sqlROW["pg_token"].'&fields=values&period=day&date_preset=this_week&metric=page_impressions_organic_unique,page_impressions_paid_unique,page_post_engagements'; 
										
											$val_lm = get_data('https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["pg_id"].'/insights?access_token='.$sqlROW["pg_token"].'&fields=values&period=day&date_preset='.$filter_lm.'&metric=page_impressions_organic_unique,page_impressions_paid_unique,page_post_engagements');
											//$val->data[0]->values;
											//d($val['data'][0]['values']); 
											$org_reach_lm = array_sum(array_column($val_lm['data'][0]['values'], 'value'));
											$paid_reach_lm = array_sum(array_column($val_lm['data'][1]['values'], 'value'));
											$tot_eng_lm = array_sum(array_column($val_lm['data'][2]['values'], 'value'));
											
											
											$val_cm = get_data('https://graph.facebook.com/'.$api_ver.'/'.$sqlROW["pg_id"].'/insights?access_token='.$sqlROW["pg_token"].'&fields=values&period=day&date_preset='.$filter_cm.'&metric=page_impressions_organic_unique,page_impressions_paid_unique,page_post_engagements');
											//$val->data[0]->values;
											//d($val['data'][0]['values']); 
											$org_reach_cm = array_sum(array_column($val_cm['data'][0]['values'], 'value'));
											$paid_reach_cm = array_sum(array_column($val_cm['data'][1]['values'], 'value'));
											$tot_eng_cm = array_sum(array_column($val_cm['data'][2]['values'], 'value'));
											
											//exit;
										?>
                                        <tr>
                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><a href="loading.php?pg=posts.php?id=<?php echo $sqlROW["pg_id"]; ?>" target="_blank" class="blue"><?php echo $sqlROW["pg_name"]; ?></td></td>
                                        	<td><?php echo moneyFormatIndia($org_reach_lm); ?></td>
                                            <td><?php echo moneyFormatIndia($paid_reach_lm); ?></td>
                                        	<td><?php echo moneyFormatIndia($tot_eng_lm); ?></td> 
                                            
                                            <td><?php echo moneyFormatIndia($org_reach_cm); ?></td>
                                            <td><?php echo moneyFormatIndia($paid_reach_cm); ?></td>
                                        	<td><?php echo moneyFormatIndia($tot_eng_cm); ?></td>                                                                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                <input type="submit" value="Acivate" name="submit" class="btn btn-info btn-sm">
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

					<?php	}	?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>