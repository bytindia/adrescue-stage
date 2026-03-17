<?php include 'header.php'; 


//print_r($_SESSION); exit;
Auth();
$pgHeadline = 'Client Dashboard - Under construction by Prabhu';
$pgID = 2;
$err =''; 

$query = "SELECT access_token,g_mcc,g_refresh_token,g_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];


if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	echo "<script>window.location =  '';</script>";
	exit();
}


include 'config.php';

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 10; // Set how many records do you want to display per page.
$_GET['id'] = $_GET['act'];
$startpoint = ($page * $per_page) - $per_page;
$statement = " client_dashboard where uid='".$_SESSION['uid']."' && tbl_id=".$_GET['id']." && delete_status=0";

		$arr_lg = $arr_lg = $arr_con = $arr_goo = $getData = $fbStats = $gStats = array();
										 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
										$getV= array();
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											$getV = $sqlROW;
											if($sqlROW['g_acc']!='') { $gStats[] = $sqlROW['g_acc']; }
										}	

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

function LeadGenTot($arr, $filt) {
	$r = 1;
	if(is_array($arr)) {
		for($q=0; $q<count($arr); $q++) {
			//foreach($arr[$q] as $v) {
				if($arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
			//}
		}
	}
	return $r;
}
function LeadGenTot2($arr, $filt) {
	$r = 0;
	if(is_array($arr)) {
		for($q=0; $q<count($arr); $q++) {
			//foreach($arr[$q] as $v) {
				if($arr[$q][12]==$filt) $r++;	
			//}
		}
	}
	return $r;
}
function getCSVdata($csvFile){
	$file_handle = fopen($csvFile, 'r');
	while (!feof($file_handle) ) {
			$line_of_text[] = fgetcsv($file_handle, 1024);
	}
	fclose($file_handle);
	return $line_of_text;
}

$fb_objective =  array(0=>'APP_INSTALLS', 1=>'BRAND_AWARENESS', 2=>'CONVERSIONS', 3=>'EVENT_RESPONSES', 4=>'LEAD_GENERATION', 5=>'LINK_CLICKS', 6=>'LOCAL_AWARENESS', 7=>'MESSAGES', 8=>'OFFER_CLAIMS', 9=>'PAGE_LIKES', 10=>'POST_ENGAGEMENT', 11=>'PRODUCT_CATALOG_SALES', 12=>'REACH', 13=>'VIDEO_VIEWS');

$fb_obj_act =  array(0=>array('mobile_app_install','app_custom_event.fb_mobile_complete_registration'), 1=>array('link_click', 'outbound_clicks'), 2=> array('offsite_conversion.fb_pixel_lead'), 3 => array('rsvp'), 4 => array('leadgen.other'), 5=>  array('link_click','post_reaction','post_engagement'), 6=>array('link_click', 'outbound_clicks'), 7=>array('onsite_conversion.messaging_first_reply','onsite_conversion.messaging_reply'), 8=> array('receive_offer'), 9=>array('like'), 10=>array('post_engagement','post_reaction','post','comment'), 12=>array('link_click', 'outbound_clicks'), 13=>array('video_view','post_reaction','post','comment'));


$fb_labels = array('Amount Spent', 'Leads', 'Cost per Lead', 'Impression', 'Reach', 'CTR', 'CPM', 'Clicks', 'CPC', 'Relevance Score', 'Post Engagement', 'Cost per Post Engagement', 'Outbound Clicks', 'Cost Per Outbound Clicks',  'Link Clicks', 'Cost Per Link Clicks',  'Post reaction', 'Cost Per Post reaction',  'Page Likes', 'Cost Per Page Likes' );
$g_labels = array('Amount Spent', 'Conversion', 'Cost per Conversion', 'Impression', 'CTR', 'CPM', 'Clicks', 'CPC', 'Time on Site', 'Bounce Ratio', 'Current Active Campaigns', 'Quality Score', 'Search Terms', 'Best Performing Keywords', 'Ads Disapproved');

$in_labels = array('Amount Spent', 'Leads', 'Cost per Leads',  'Conversion', 'Cost per Conversion', 'Impression', 'Clicks', 'Engagement', 'Likes', 'Comments', 'Shares');

//$st_date = strtotime($_SESSION['stDt']);
$datediff = strtotime($_SESSION['enDt']) - strtotime($_SESSION['stDt']);

$diffDay = round($datediff / (60 * 60 * 24));
$diffDay_st = $diffDay +1;
$mod_date = strtotime($_SESSION['stDt']."- $diffDay_st days");
$stDt2 = date("Y-m-d", $mod_date) ;
$mod_date2 = strtotime($stDt2."+ $diffDay days");
$enDt2 = date("Y-m-d",$mod_date2);

$st_dates = array($_SESSION['stDt'], $stDt2);
$en_dates = array($_SESSION['enDt'], $enDt2);
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.x_panel { padding:5px; }
.blue { cursor:pointer; }
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
                    <h2><?php echo $_GET['name'].' - Ads Dashboard'; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      
                    </ul>
                    <form method="post" action="">
                    <ul class="nav navbar-right panel_toolbox">
                      <li>Filter : &nbsp;
                      </li>
                      <li>
                      		     <div id="reportrange_right" class="pull-right1" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                                      <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                                      <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                                 </div>
                                 <input type="hidden" id="stDt" name="start" value="<?php echo $_SESSION['stDt']; ?>">
								<input type="hidden" id="enDt" name="end" value="<?php echo $_SESSION['enDt']; ?>"> 
                      </li>
                      <li><input type="submit" name="dt_submit" value="Submit" class="btn btn-primary"></li>
                    </ul>
                    </form>
                    
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                    <div id="btn-group" style="float:right;">
                      <a class="blue" id="fb">Facebook</a> |  
                      <a class="blue" id="g">Google</a> |                      
                      <a class="blue" id="in">Linkedin</a>
                    </div>
                    <div class="clearfix"></div>
                    <br>
                  	<div class="row">
             			
        				<?php 
									$getV["fb_acc"] = $_GET['act'];	
										if(isset($getV["fb_acc"]) && $getV["fb_acc"]!='') 
							{
											foreach ($st_dates as $key => $value) 
											{
												$fd = $getV["fb_acc"];
												$cirRes = mysqli_query($conn, "select report_1 from ads_weekly WHERE uid='2' AND acc_type='FB' AND acc_id='".$fd."' AND st_dt='".strtotime($st_dates[$key])."' AND en_dt='".strtotime($en_dates[$key])."'");
												//if(mysqli_num_rows($cirRes)==0) 
												//{
													//echo implode(",",$fb_objective); exit;											
													$request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$fd.'/insights?level=campaign&fields=objective,campaign_name,reach,spend,actions,ctr,cpc,cpm,impressions,clicks,outbound_clicks,video_play_actions,video_p50_watched_actions,video_p75_watched_actions,video_p95_watched_actions,video_p100_watched_actions&time_range[since]='.date("Y-m-d", strtotime($st_dates[$key])).'&time_range[until]='.date("Y-m-d", strtotime($en_dates[$key])).'&access_token='.$access_token; 
													 
													$fbData[$fd][$key] = get_data($request_url);
													
													//$cirSql_fb = "INSERT INTO ads_weekly (uid, acc_type, acc_id, report_1, st_dt, en_dt, created) VALUES ('2', 'FB', '".$fd."', '".mysqli_real_escape_string($conn, serialize($fbData[$fd][$key]))."', '".strtotime($st_dates[$key])."', '".strtotime($en_dates[$key])."', now());"; 
													//mysqli_query($conn, $cirSql_fb) or die(mysqli_error());
												//} 
												/*else {
													$row = mysqli_fetch_assoc($cirRes);
													$fbData[$fd][$key] = unserialize($row['report_1']);
												}*/
												if(count($fbData)>0) {
													foreach($fb_objective as $ky => $fb_o) 
													{
														$filterBy1 = $fb_o; // or Finance etc.
														if(isset($fbData[$fd][$key]['data'])) {
														$arr_fb[$fd][$key][$ky] = array_filter($fbData[$fd][$key]['data'], function ($var) use ($filterBy1) { return ($var['objective'] == $filterBy1); });
														if(count($arr_fb[$fd][$key][$ky])<1) { unset($arr_fb[$fd][$key][$ky]); }
														}
														//d($arr_lg); 
													}
												}
											}
										
										//d($arr_fb); 
										$spend = $spend_lead = 0;
										//$repVal_arr();
										for($y=0;$y<2;$y++) 
										{
											if(isset($arr_fb[$fd][$y])) {
											foreach($arr_fb[$fd][$y]  as $z => $arrFB) 
											{
												$FB_records = array_values($arrFB);		
												for ($i = 0; $i < count($FB_records); $i++) 
												{										
														$spend += $FB_records[$i]['spend'];
														$reach += $FB_records[$i]['reach'];
														$impr += $FB_records[$i]['impressions'];														
														$cpm += $FB_records[$i]['cpm'];
														$clicks += $FB_records[$i]['clicks'];
														$cpc += $FB_records[$i]['cpc'];
														$ctr += $FB_records[$i]['ctr'];
														//$rc += $FB_records[$i]['relevance_score'];
														$rc +=0;
														
														if(isset($fb_obj_act[$z]) && count($fb_obj_act[$z])>0 && $fb_objective[$z]!='VIDEO_VIEWS') 
														{
															if(!isset($totLeads_lg)) { $totLeads_lg=array();  }															
															foreach($fb_obj_act[$z] as $fk => $fv) 
															{	
																if($fv=='outbound_clicks') { 
																	$leads_lg = $FB_records[$i]['outbound_clicks'][0]['value'];
																} else {
																	$leads_lg = LeadGenTot($FB_records[$i]['actions'], $fv);																	
																}
																//echo $leads_lg[$z][$fk].'<br>';
																$totLeads_lg[$z][$fk] += @((int)$leads_lg); 
																$cpl_lg[$z][$fk] = @($FB_records[$i]['spend']/$leads_lg);
															}
														}
														if(isset($FB_records[$i]['outbound_clicks']) && isset($FB_records[$i]['outbound_clicks'][0]['value']))
														{
															$out_clikcs += $FB_records[$i]['outbound_clicks'][0]['value'];
															$out_clikcs_spend += $FB_records[$i]['spend'];
														}
														if($fb_objective[$z]=='LINK_CLICKS')
														{
															$link_clicks += $leads_lg = LeadGenTot($FB_records[$i]['actions'], 'link_click');	;
															$link_clicks_spend += $FB_records[$i]['spend'];
														}
														if($fb_objective[$z]=='LINK_CLICKS' || $fb_objective[$z]=='POST_ENGAGEMENT' || $fb_objective[$z]=='VIDEO_VIEWS') //LINK_CLICKS,POST_ENGAGEMENT,VIDEO_VIEWS
														{
															$post_reaction += $leads_lg = LeadGenTot($FB_records[$i]['actions'], 'post_reaction');	;
															$post_reaction_spend += $FB_records[$i]['spend'];
														}
														if($fb_objective[$z]=='PAGE_LIKES')
														{
															$like += $leads_lg = LeadGenTot($FB_records[$i]['actions'], 'like');	;
															$like_spend += $FB_records[$i]['spend'];
														}
												}
											}
												$cpc = @($spend/$clicks);
												$ctr = @($clicks/$impr)*100;
												$out_clikcs_cpc = @($out_clikcs_spend/$out_clikcs);
												$link_clicks_cpc = @($link_clicks_spend/$link_clicks);
												$post_reaction_cpc = @($post_reaction_spend/$post_reaction);
												$like_cpc = @($like_spend/$like);
												
												$repVal[$y] = array($spend,$totLeads_lg,$cpl_lg,$impr,$reach,$ctr,$cpm,$clicks,$cpc,$rc,$totLeads_lg,$cpl_lg,$out_clikcs,$out_clikcs_cpc,$link_clicks,$link_clicks_cpc,$post_reaction,$post_reaction_spend,$like,$like_cpc);
											}
									}
						
								?>
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                <div class="x_panel">
                                 <div class="x_title">
                                    <h2>Facebook</h2>                                    
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">                                
                                <table class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>Name</th>
                                        <th><?php echo date('d M', strtotime($st_dates[0])).' to '. date('d M Y',strtotime($en_dates[0])); ?></th>
                                    	<th><?php echo date('d M', strtotime($st_dates[1])).' to '. date('d M Y',strtotime($en_dates[1])); ?></th>
                                    </thead>
                                    <tbody>
                                    	<?php 
										foreach($fb_labels as $key => $g_val) { 
											if(($key==1 || $key==2) && isset($repVal[0][$key])) { $repVal[0][$key]= $repVal[0][$key][4][0]; $repVal[1][$key]= $repVal[1][$key][4][0]; } 
											if($key==10 || $key==11) { $repVal[0][$key]= $repVal[0][$key][10][0]; $repVal[1][$key]= $repVal[1][$key][10][0]; } 
											if(is_nan($repVal[0][$key])) { $repVal[0][$key]=0; }
											if(is_nan($repVal[1][$key])) { $repVal[1][$key]=0; }
										?>
                                    	<tr>
                                                <td><?php echo $g_val; ?></td>
                                                <td><?php echo round($repVal[0][$key],2); ?></td>
                                                <td><?php echo round($repVal[1][$key],2); ?></td>
                                  		</tr>
                                       	<?php } ?>
                                    </tbody>
                                 </table> 
                                 </div>
                            </div>                   	 
                  </div>
                <?php  } ?>
                  
                   				
                  </div>              
         			
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php //d($repVal); ?>

<?php include 'footer.php'; ?>

