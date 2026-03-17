<?php //include 'header.php'; 
//include 'db.php'; 
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

$pgHeadline = 'Partha Sarathy - Facebook - Audit';
$pgID = 3;
$err =''; 


//include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts WHERE uid='".$_SESSION['uid']."'";

//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();

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
$ac_status = array(
1 => 'ACTIVE',
2 => 'DISABLED',
3 => 'UNSETTLED',
7 => 'PENDING_RISK_REVIEW',
8 => 'PENDING_SETTLEMENT',
9 => 'IN_GRACE_PERIOD',
100 => 'PENDING_CLOSURE',
101 => 'CLOSED',
201 => 'ANY_ACTIVE',
202 => 'ANY_CLOSED'
);
function dateDiffInDays($date1, $date2)  
{ 
    // Calulating the difference in timestamps 
    $diff = strtotime($date2) - strtotime($date1); 
      
    // 1 day = 24 hours 
    // 24 * 60 * 60 = 86400 seconds 
    return abs(round($diff / 86400)); 
} 

/*$html .= '<style>'.file_get_contents_curl('/home/faheems/webapps/adsninja/assets2/css/bootstrap.css').'</style>';
$html .= '<style>'.file_get_contents_curl('/home/faheems/webapps/adsninja/vendors/bootstrap/dist/css/bootstrap.min.css').'</style>';
$html .= '<style>'.file_get_contents_curl('/home/faheems/webapps/adsninja/vendors/font-awesome/css/font-awesome.min.css').'</style>';
$html .= '<style>'.file_get_contents_curl('/home/faheems/webapps/adsninja/assets2/fonts/icomoon.css').'</style>';
$html .= '<style>'.file_get_contents_curl('/home/faheems/webapps/adsninja/assets2/css/statistics-card.css').'</style>';
$html .= '<style>'.file_get_contents_curl('/home/faheems/webapps/adsninja/assets2/css/colors.css').'</style>';
echo $html;*/

?>
<link rel="stylesheet" type="text/css" href="/assets2/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="/assets2/fonts/icomoon.css">
<link rel="stylesheet" type="text/css" href="/assets2/css/statistics-card.css"> 
<link rel="stylesheet" type="text/css" href="/assets2/css/colors.css">
 <link rel="stylesheet" href="./chart/_styles/style.css" type="text/css">
    <link rel="stylesheet" href="./chart/_styles/simple-donut.css" type="text/css">
    <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js"></script>
    <script type="text/javascript" src="./chart/_scripts/simple-donut-jquery.js"></script>
<style>
.nav-sm .main_container .top_nav, .nav-sm .container.body .right_col, .nav-sm footer { margin-left:0px; }
#menu_toggle { display:none; }
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.main_container .top_nav, .nav-md .container.body .right_col { margin-left:0px; }
.scrollClass { height:188px; overflow-y: scroll; }
.list-group-item.active, .list-group-item.active:focus {
    z-index: 2;
    color: #fff;
    text-decoration: none;
    background-color: #2283f3;
    border-color: #2283f3;
}
.x_title h2 { font-size:25px; }
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include 'menu-left.php';
			include 'menu-top.php'; 
			
			$sqlR = mysqli_query($conn, "SELECT pg_name,pg_img,marks from audit_reports where tbl_id=".$_GET['tbl_id']."");
								
								while($sqlROW=mysqli_fetch_array($sqlR))
								{ 
									$rowR  = $sqlROW;
								}
		?>

        

        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  
                  <div class="x_title">
                    <h2 style="margin-top:70px;"><?php echo $rowR['pg_name']; ?> - Audit Report</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      <li>
                        <img src="images/ads-rescue.png" height="150" >   
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>   
                  
                  <div class="x_content">
                  		<?php
								$fields = array(
									0 => 'Account Age',
									1 => 'Account Status',
									2 => 'No. of Active Campaigns',
									3 => 'CBO Campaigns',
									4 => 'Pixel Codes',
									5 => 'Custom Conversions',
									6 => 'Remarketing List',
									7 => 'Top 25% Website visitors',
									8 => 'Look Alike of Top 25% Website visitors',
									9 => 'Rules',
									10 => 'Custom Audience',
									11 => 'Look-alike Audiences',
									12 => 'Narrow Audiences',
									13 => 'Budget type',
									14 => 'Day parting (Lifetime budget)',
									15 => 'Linked Instagram Account',
									16 => 'Instagram Followers',
									17 => 'Audience Expansion',
									18 => 'Dynamic Creative Ads',
									19 => 'New visual updated on',
									
									20 => 'Location Targets',
									21 => 'Bidding Type',
									22 => 'Placement Type (Auto/Manual)',
									
									23 => 'Age Group Targets',
									24 => 'Ad Assets',
									25 => 'Results based on Ad Assets',
									26 => 'Best Performing Ad Asset',
									27 => 'Ad Quality Ranking',
									
									28 => 'Objective wise cost per results',
									29 => 'Campaigns - High CPL',
									30 => 'Landing Pages',
									31 => 'Lead Form Questions',
									
									32 => 'Interest-based Targets',
									33 => 'Work Employer Targets',
									34 => 'Work Position Targets',
									
								);
								
								$Recom = array(
									7 => 'Create remarketing list & Start ads',
									9 => 'Create LA of Top 25% website visitors list & Start  ads',
									10 => 'Create Rules & control the cost per result',
									11 => 'Upload custom audience list & Start Ads',
									12 => 'Create LA of custom audience & start Ads',
									18 => 'Remove Narrow audience in your target. It will improve your leads quality',
									22 => 'Start manual bidding. It will control your cost per result',
									25 => 'Please start video ads as well as',
									19 => 'Start dynamic creative ads. It will help to reduce your lead cost',
									16 => 'Connect Instagram account & improve your branding value',
									32 => 'Add Questions (like budget, location, interest & etc.) in the lead form. It will improve your leads quality',
									20 => 'You should change ad visuals every 2 weeks once',
								);
								
								
								
								$sqlRev = mysqli_query($conn, "SELECT age,active, camp_tot, cbo_camp, pix_act, cust_conv, remarket, top_web_25, top_la_25, rules, cust_aud, la_aud, nar_aud, daily_life, day_part, insta_acc, ig_followers, exp_on, dynamic, img_updated, loc_type, bid_type, placement, age_group, adset_type, ad_type_res, ad_type_best, ad_qty, obj_cost_result, high_cpl, lp_url, lead_qus, interests, work_emp, work_pos FROM audit_data where rep_id='".$_GET['tbl_id']."'");
								
								while($sqlROW=mysqli_fetch_array($sqlRev))
								{ 
									$ans  = $sqlROW;
								}
								//d($d); 
						?>
                                <form method="post" action="">
								<div class="row">
								<div class="pull-right col-xs-12 col-sm-12 col-md-5 col-lg-4">
									<div id="specificChart" class="chart_wrapper">
										<div>
											<div class="pie-wrapper">
												  <div class="label">			    
													<div class="pie">
													  <div class="left-side half-circle"></div>
													  <div class="right-side half-circle"></div>
													</div>
													<div class="shadow inner-pie ">
													<div>
														<div class="profile">
															<img src="./chart/images/user.png" alt=""/>
															<h3>Faheem Ahmed</h3>
															<h5>You have scored</h5>
															<div class="score"><span>75</span>/100</div>
														</div>
													  <img src="./chart/images/logo-ads.png" alt="" class="adsLogo" />
												 </div>				  
													</div>
												</div>
											</div>
											<!--<div class="bottom-text">
												<h3>AdsRescue</h3>
												<p>Grading Scale<p>
											</div>-->
											<div class="footer">
												<img src="./chart/images/byt-logo.png" />
											</div>
										</div>
									  </div>
								</div>
								<div class="pull-right col-xs-12 col-sm-12 col-md-7 col-lg-8">
                                		<?php
										$Recom_list = array(); 
										$active_or_yes = array('active', 'yes');
										$inactive_or_no = array('inactive', 'no');
										
										
										
										//Columns must be a factor of 12 (1,2,3,4,6,12)
										$numOfCols = 4;
										$rowCount = 0;
										$bootstrapColWidth = 12 / $numOfCols;
										foreach ($fields as $k => $v)
										{ 
										  if($k<=27) {
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											
											$ans[$k] = str_replace('<>', '<br>', $ans[$k]);
											
											if(isset($Recom[($k+1)]) && in_array(strtolower($ans[$k]), $inactive_or_no) 
											   || (($k+1)==22) && (strpos(strtolower($ans[$k]), 'manual') == false) 
											   || (($k+1)==25) && (strpos(strtolower($ans[$k]), 'video') == false)
											   || (($k+1)==19 && strpos(strtolower($ans[$k]), 'yes') == false)
											   || (($k+1)==32 && trim($ans[$k])=='')
											) { $Recom_list[($k+1)] = $Recom[($k+1)]; } 
											if(isset($Recom[($k+1)]) && ($k+1)==20) {
												$now = time(); // or your date as well
												$your_date = strtotime($ans[$k]);
												$datediff = $now - $your_date;
												$dateDif = round($datediff / (60 * 60 * 24));
												if($dateDif>30) { $Recom_list[($k+1)] = $Recom[($k+1)]; } 
											}
											
										  if($rowCount % $numOfCols == 0) { ?> <div class="row"> <?php } 
											$rowCount++; 
											
											if(in_array(strtolower($ans[$k]), $active_or_yes)) { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; } else if(in_array(strtolower($ans[$k]), $inactive_or_no)) { $cls="class='deep-orange'"; $clr='deep-orange'; $icon="icon-close"; } else { $cls=""; $clr=''; $icon=""; }
											
											?>  
                                                <div class="col-xl-4 col-lg-8 col-xs-12" id="r<?php echo ($k+1); ?>">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="card-block">
                                                                <div class="media">
                                                                    <div class="media-body text-xs-left">
                                                                        <h5><?php echo ($k+1).'. '.$v; ?></h5>
                                                            			 <?php if($icon=='') { ?><span <?php echo $cls; ?>><?php echo $ans[$k]; ?></span><?php } ?>
                                                                    </div>
                                                                    <?php if($icon!='') { ?>
                                                                    <div class="media-left media-middle">
                                                                        <i class="<?php echo $icon.' '.$clr; ?> font-large-2 float-xs-right"></i>
                                                                    </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
										<?php
											if($rowCount % $numOfCols == 0) { ?> </div> <?php } 
										  } 
										}
										
										$numOfCols = 2;
										$rowCount = 0;
										$bootstrapColWidth = 12 / $numOfCols;
										foreach ($fields as $k => $v)
										{ 
											if($k>=28) {
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											
											$ans[$k] = str_replace('<>', '<br>', $ans[$k]);
											
											if(isset($Recom[($k+1)]) && in_array(strtolower($ans[$k]), $inactive_or_no) 
											   || (($k+1)==22) && (strpos(strtolower($ans[$k]), 'manual') == false) 
											   || (($k+1)==25) && (strpos(strtolower($ans[$k]), 'video') == false)
											   || (($k+1)==19 && strpos(strtolower($ans[$k]), 'yes') == false)
											   || (($k+1)==32 && trim($ans[$k])=='')
											) { $Recom_list[($k+1)] = $Recom[($k+1)]; } 
											if(isset($Recom[($k+1)]) && ($k+1)==20) {
												$now = time(); // or your date as well
												$your_date = strtotime($ans[$k]);
												$datediff = $now - $your_date;
												$dateDif = round($datediff / (60 * 60 * 24));
												if($dateDif>30) { $Recom_list[($k+1)] = $Recom[($k+1)]; } 
											}
											
										  if($rowCount % $numOfCols == 0) { ?> <div class="row"> <?php } 
											$rowCount++; 
											
											if(in_array(strtolower($ans[$k]), $active_or_yes)) { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; } else if(in_array(strtolower($ans[$k]), $inactive_or_no)) { $cls="class='deep-orange'"; $clr='deep-orange'; $icon="icon-close"; } else { $cls=""; $clr=''; $icon=""; }
											
											?>  
                                                <div class="col-xl-6 col-lg-8 col-xs-12" id="r<?php echo ($k+1); ?>">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="card-block">
                                                                <div class="media">
                                                                    <div class="media-body text-xs-left">
                                                                        <h5><?php echo ($k+1).'. '.$v; ?></h5>
                                                            			 <?php if($icon=='') { ?><div class="scrollClass" <?php echo $cls; ?>><?php echo $ans[$k]; ?></div><?php } ?>
                                                                    </div>
                                                                    <?php if($icon!='') { ?>
                                                                    <div class="media-left media-middle">
                                                                        <i class="<?php echo $icon.' '.$clr; ?> font-large-2 float-xs-right"></i>
                                                                    </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
										<?php
											if($rowCount % $numOfCols == 0) { ?> </div> <?php } } 
										}
										
										//d($Recom_list);
										?>

                                    	                                   
                                   
	</div>
	</div>
                                <br><br>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

						
         
        				</div>
                        <div class="row">
                          <div class="span9">
                          	<?php 
							if(count($Recom_list)>0) {
								echo '<div class="list-group">
								<a href="loading.php?pg=#" class="list-group-item list-group-item-action active">Recommendations</a>';
								foreach($Recom_list as $k => $v) {
									echo '<a href="loading.php?pg=#r'.$k.'" class="list-group-item list-group-item-action">'.$k.'. '.$v.'</a>';
								}
								echo '</div>';
							}
							?>
                          </div>
                        </div>	

                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php //include 'footer.php'; ?>