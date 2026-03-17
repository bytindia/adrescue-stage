<?php include 'header.php'; 



Auth();
$pgHeadline = 'Facebook - Page Performance';
$pgID = 3;
$err =''; 



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
.bg1 { background-color: #f9efe0; }
.bg2 { background-color: #dcfde5; }
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
								
								
								if(isset($_GET['sort']) && $_GET['sort']=='week') 
								{ 
									$filter_lm='last_week'; $filter_cm='this_week'; $m_text='Week'; 
									
									$start_lm = strtotime('monday last week'); //date('m/01/Y');
									$end_lm = strtotime("sunday last week");
									
									$start_cm = strtotime('monday this week'); //date('m/01/Y');
									$end_cm = strtotime("now"); //sunday last week
								} else { 
									$filter_lm='last_month'; $filter_cm='this_month'; $m_text='Month'; 
									
									$start_lm = strtotime("first day of last month"); //date('m/01/Y');
									$end_lm = strtotime("last day of last month");
									
									$start_cm = strtotime("first day of this month"); //date('m/01/Y');
									$end_cm = strtotime("now");
								} 
								
								/*
								$start_lm = strtotime("first day of last month"); //date('m/01/Y');
								$end_lm = strtotime("last day of last month");
								
								$start_cm = strtotime("first day of this month"); //date('m/01/Y');
								$end_cm = strtotime("now");
								
								$start_lw = strtotime('monday last week'); //date('m/01/Y');
								$end_lw = strtotime("sunday last week");
								
								$start_cw = strtotime('monday this week'); //date('m/01/Y');
								$end_cw = strtotime("now"); //sunday last week*/
								
								$q0 = mysqli_query($conn, "SELECT pg_id,pg_name FROM `pages` WHERE uid='".$_SESSION['uid']."' AND active=1 AND admin_delete=0");
								
								$q1 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$_SESSION['uid']."' AND end_time_unix>=".$start_lm." AND end_time_unix<=".$end_lm." GROUP BY pg_id");
								
								$q2 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$_SESSION['uid']."' AND created_time_unix>=".$start_lm." AND created_time_unix<=".$end_lm." GROUP BY pg_id");								
								
								$q3 = mysqli_query($conn, "SELECT pg_id, SUM(org_reach) as r1, SUM(paid_reach) as r2, SUM(engagement) as r3 FROM `page_insights` WHERE uid='".$_SESSION['uid']."' AND end_time_unix>=".$start_cm." AND end_time_unix<=".$end_cm." GROUP BY pg_id");
								
								$q4 = mysqli_query($conn, "SELECT pg_id, count(*) as c1, SUM(post_clicks) as c2, SUM(post_activity) as c3, SUM(post_like) as c4, SUM(post_share) as c5, SUM(post_comment) as c6, (post_clicks+post_activity) as c7 FROM `post_insights` WHERE uid='".$_SESSION['uid']."' AND created_time_unix>=".$start_cm." AND created_time_unix<=".$end_cm." GROUP BY pg_id");
								
								$r0 = $r1 = $r2 = $r3 = $r4 = array();
								while($rw0=mysqli_fetch_assoc($q0)) { $r0[$rw0['pg_id']] = $rw0['pg_name']; }
								while($rw1=mysqli_fetch_assoc($q1)) { $r1[$rw1['pg_id']] = $rw1; }
								while($rw2=mysqli_fetch_assoc($q2)) { $r2[$rw2['pg_id']] = $rw2; }
								while($rw3=mysqli_fetch_assoc($q3)) { $r3[$rw3['pg_id']] = $rw3; }
								while($rw4=mysqli_fetch_assoc($q4)) { $r4[$rw4['pg_id']] = $rw4; }
								
								//d($r0); exit;
								//$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
								//$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th colspan="2"></th>
                                                                                
                                        <th colspan="8" class="blue upcase bg1">Last <?php echo $m_text; ?> (<?php echo date('M d', $start_lm).' - '.date('d, Y', $end_lm); ?>)</th>
                                         
                                        <th colspan="8" class="blue upcase bg2">This <?php echo $m_text; ?> (<?php echo date('M d', $start_cm).' - '.date('d, Y', $end_cm); ?>)</th>                                                     	
                                    </thead>
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Page</th>
                                        
                                        <th class="bg1">Organic</th>
                                    	<th class="bg1">Paid</th>  
                                        <th class="bg1">Engmt.</th>
                                        <th class="bg1">Clicks</th>
                                        <th class="bg1">Likes</th>
                                        <th class="bg1">Shares</th>
                                        <th class="bg1">Comnt.</th> 
                                        <th class="bg1">Posts</th> 
                                         
                                        <th class="bg2">Organic</th>
                                    	<th class="bg2">Paid</th>  
                                        <th class="bg2">Engmt</th> 
                                        <th class="bg2">Clicks</th>  
                                        <th class="bg2">Like</th>
                                        <th class="bg2">Share</th>
                                        <th class="bg2">Comnt.</th> 
                                        <th class="bg2">Posts</th>                                                     	
                                    </thead>
                                    <tbody>
                                    	<?php
										 $i =1;
										foreach($r0 as $k => $v)
										{ 	//d($r0); exit;	 								
										?>
                                        <tr>
                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><a href="loading.php?pg=posts.php?id=<?php echo $k; ?>" target="_blank" class="blue"><?php echo $r0[$k]; ?></td></td>
                                            
                                        	<td class="bg1"><?php if(isset($r1[$k]['r1']) && $r1[$k]['r1']!='') { echo moneyFormatIndia($r1[$k]['r1']); } else { echo 0; } ?></td>
                                            <td class="bg1"><?php if(isset($r1[$k]['r2']) && $r1[$k]['r2']!='') { echo moneyFormatIndia($r1[$k]['r2']); } else { echo 0; } ?></td>
                                        	<td class="bg1"><?php if(isset($r1[$k]['r3']) && $r1[$k]['r3']!='') { echo moneyFormatIndia($r1[$k]['r3']); } else { echo 0; } ?></td> 
                                            <td class="bg1"><?php if(isset($r2[$k]['c2']) && $r2[$k]['c2']!='') { echo moneyFormatIndia($r2[$k]['c2']); } else { echo 0; } ?></td>
                                            <td class="bg1"><?php if(isset($r2[$k]['c4']) && $r2[$k]['c4']!='') { echo moneyFormatIndia($r2[$k]['c4']); } else { echo 0; } ?></td>
                                            <td class="bg1"><?php if(isset($r2[$k]['c5']) && $r2[$k]['c5']!='') { echo moneyFormatIndia($r2[$k]['c5']); } else { echo 0; } ?></td>
                                        	<td class="bg1"><?php if(isset($r2[$k]['c6']) && $r2[$k]['c6']!='') { echo moneyFormatIndia($r2[$k]['c6']); } else { echo 0; } ?></td>
                                            <td class="bg1"><?php if(isset($r2[$k]['c1']) && $r2[$k]['c1']!='') { echo moneyFormatIndia($r2[$k]['c1']); } else { echo 0; } ?></td>
                                            
                                            <td class="bg2"><?php if(isset($r3[$k]['r1']) && $r3[$k]['r1']!='') { echo moneyFormatIndia($r3[$k]['r1']); } else { echo 0; } ?></td>
                                            <td class="bg2"><?php if(isset($r3[$k]['r2']) && $r3[$k]['r2']!='') { echo moneyFormatIndia($r3[$k]['r2']); } else { echo 0; } ?></td>
                                        	<td class="bg2"><?php if(isset($r3[$k]['r1']) && $r3[$k]['r3']!='') { echo moneyFormatIndia($r3[$k]['r3']); } else { echo 0; } ?></td> 
                                            <td class="bg2"><?php if(isset($r4[$k]['c2']) && $r4[$k]['c2']!='') { echo moneyFormatIndia($r4[$k]['c2']); } else { echo 0; } ?></td>
                                            <td class="bg2"><?php if(isset($r4[$k]['c4']) && $r4[$k]['c4']!='') { echo moneyFormatIndia($r4[$k]['c4']); } else { echo 0; } ?></td>
                                            <td class="bg2"><?php if(isset($r4[$k]['c5']) && $r4[$k]['c5']!='') { echo moneyFormatIndia($r4[$k]['c5']); } else { echo 0; } ?></td>
                                        	<td class="bg2"><?php if(isset($r4[$k]['c6']) && $r4[$k]['c6']!='') { echo moneyFormatIndia($r4[$k]['c6']); } else { echo 0; } ?></td>
                                            <td class="bg2"><?php if(isset($r4[$k]['c1']) && $r4[$k]['c1']!='') { echo moneyFormatIndia($r4[$k]['c1']); } else { echo 0; } ?></td>                                                                              	
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