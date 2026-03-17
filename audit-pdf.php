<?php session_start(); 
if($_SERVER["HTTPS"] != "on")
{
   // header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    //exit();
}
include 'db.php'; //auditAuth(); 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
//d($_SESSION);
if(isset($_GET['permission']) && $_GET['permission']==1 && $_SESSION['fb_id']!='') {
	
	$query = "SELECT tbl_id, name, fb_id, fb_token FROM audit_users WHERE tbl_id='".$_SESSION['uid']."'"; 
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$url = "https://graph.facebook.com/".$api_ver."/".$row['fb_id']."/permissions?access_token=".$row['fb_token'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	
    $_SESSION = array();
	unset($_SESSION);
	session_destroy();
	
	
	$_SESSION['suc'] = "Successfully Logged Out!";
	echo "<script>window.location = 'login.php';</script>";
	exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="fixed sidebar-left-collapsed">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="images/favicon.ico" type="image/ico" />
	<?php if(!isset($pgHeadline)) { $pgHeadline = 'AdRescue'; } ?>
    <title><?php echo $pgHeadline; ?></title>

    <!-- Bootstrap -->
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	
    <!-- bootstrap-progressbar -->
    <link href="/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="/vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="/build/css/custom.css" rel="stylesheet">
    <link href="/web/pagination.css" rel="stylesheet">
    <link href="/assets/css/pagination.css" rel="stylesheet">
  </head>
 <?php
//include 'db.php'; 
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

//auditAuth();
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

/*$html .= '<style>'.curl_get_file_contents('/home/digitalb2k/stage.adrescue.in/assets2/css/bootstrap.css').'</style>';
$html .= '<style>'.curl_get_file_contents('/home/digitalb2k/stage.adrescue.in/vendors/bootstrap/dist/css/bootstrap.min.css').'</style>';
$html .= '<style>'.curl_get_file_contents('/home/digitalb2k/stage.adrescue.in/vendors/font-awesome/css/font-awesome.min.css').'</style>';
$html .= '<style>'.curl_get_file_contents('/home/digitalb2k/stage.adrescue.in/assets2/fonts/icomoon.css').'</style>';
$html .= '<style>'.curl_get_file_contents('/home/digitalb2k/stage.adrescue.in/assets2/css/statistics-card.css').'</style>';
$html .= '<style>'.curl_get_file_contents('/home/digitalb2k/stage.adrescue.in/assets2/css/colors.css').'</style>';
echo $html;*/

?>
<link rel="stylesheet" href="./chart/_styles/style.css" type="text/css">
<link rel="stylesheet" type="text/css" href="/assets2/fonts/icomoon.css">
<link rel="stylesheet" type="text/css" href="/assets2/css/statistics-card.css"> 
<link rel="stylesheet" type="text/css" href="/assets2/css/colors.css">
 
    <link rel="stylesheet" href="./chart/_styles/simple-donut.css" type="text/css">
    <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js"></script>
    <script type="text/javascript" src="./chart/_scripts/simple-donut-jquery.js"></script>
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

<!--<link rel="stylesheet" type="text/css" href="/assets2/css/bootstrap.css">-->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
    <!--<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" media="screen">-->
    
<!--<style> 
.nav-sm .main_container .top_nav, .nav-sm .container.body .right_col, .nav-sm footer { margin-left:0px; }
#menu_toggle { display:none; }
.blue { cursor:pointer; } 

tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
.main_container .top_nav, .nav-md .container.body .right_col { margin-left:0px; }
.scrollClass { height:188px; overflow-y: scroll; }
.list-group-item.active, .list-group-item.active:focus, .list-group-item.active:hover {
    z-index: 2;
    color: #fff;
    text-decoration: none;
    background-color: #2283f3;
    border-color: #2283f3;
	font-size:22px;
	color:#fff;
}
.x_title h2 { font-size:30px; }
.card-block { padding-right: 0px; }
.container {
    max-width: 100%;
}
.top_nav .navbar-right { width:auto; }
.fa-info-circle { color:#a2a0a0; }
.card-body { padding: .25rem; }  
.profile h3 {     margin-top: -30px; }
table {
  border-collapse: collapse;
}

table, th, td {
  border: 1px solid #c3bdbd;
    padding: 3px 10px 3px 10px;
}
th { font-weight:bold; width: 100%; }
table.paleBlueRows thead th:first-child {
  border-left: none;
  width: 8em;
  min-width: 8em;
  max-width: 8em;
}
.scrollClass a {
    color: #3498DB;
}
.pull-right { margin-right:25px; color: #8e8989;  font-size: 14px; }
table.dataTable { border-collapse:  collapse !important; }
.dataTable { border-collapse:  collapse !important; }
li{  padding:5px 0 !important; }
input[type=search] {
    -webkit-appearance: none;
    border: 1px solid;
    border-radius: 5px;
}
</style>-->
<style>
body{
	background:#fff;
	color:#73879C;
}
.scrollClass { height:188px; overflow-y: scroll; }
header .user-profile{
	display: inline-block;
	border: 1px solid #2283f3;
    margin: 0px;
	background-color:#fff;
}

header .navbar-nav button{
	padding: 4px 7px;
    border: 1px solid transparent;
    position: relative;
    border-radius: 50%;
    margin: 0px;
    display: inline-block;
	background: #eee;
}
header .navbar-nav button:hover{
	background-color:#2283f3;
	color:#fff;
}
header .nav_menu{
	float:none;
	margin:0px;
}
header .navbar-nav{
	display: flex;
    align-items: center;
	float:none !important;
	justify-content: flex-end;
	flex-direction:row;
}
header ul.navbar-nav>li{
	float:none;
	margin: 5px 10px 5px 5px;
}
header .badge-danger {
    color: #fff;
    background-color: #dc3545;
}
.navbar-nav .open .dropdown-menu{
	margin-top:5px;
}
header .navbar-nav .btn .badge{
	 width: 17px;
    height: 17px;
    line-height: 17px;
    font-size: 10px;
    display: block;
    /* text-indent: -999px; */
    overflow: hidden;
    position: absolute;
    padding: 0px;
    right: -8px;
    top: -9px;
    text-align: center;
}
.page-title{
	text-align:center;
	padding:0px 10px;
}
header .nav.navbar-nav>li>a,header .nav.navbar-nav>li>button{
	padding: 3px;
    width: 45px;
    height: 45px;
    display: block;
	border-radius:50%;
}
.dropdown-usermenu>li>a{
	padding:10px 20px;
}
header{
	/*background: #fbfbfb;
	box-shadow: 0px 0px 20px #d6d5d5;*/
	padding: 5px;
    margin-bottom: 10px;
	border-bottom: 1px solid #f1eaea;
    
}
header .logo img{
	max-width:110px;
}
header nav{
	display: flex;
    align-items: center;
    width: 100%;
    justify-content: space-between;
}
header .nav_menu{
	border:none;
	background-color:transparent;
}
.navbar-nav>li>.dropdown-menu {
    position: absolute;
    background: #fff;
    margin-top: 0;
    border: 1px solid #D9DEE4;
    -webkit-box-shadow: none;
    right: 0;
    left: auto;
    width: 220px;
}
.dropdown-usermenu>li>a {
    padding: 10px 20px;
}
.dropdown-toggle::after{
display:none;
}
.card{
	border:1px solid #2283f3;
	box-shadow:none;
}
h2 { font-weight:200; font-size:30px; }
.dataTables_wrapper { padding:0; }
h5 { font-weight:400; font-size:18px; }
.card-body { padding: 1.25rem !important; }
.table { color:#73879C; }
</style>
  <body class="nav-md">
    <div >
      <div class="main_container container-fluid">
        <?php 
			//include 'menu-left.php';
			include 'menu-top.php'; 
			
			$audObj = array(
				1=> array(),
				2=> array()
			);
			
			$sqlR = mysqli_query($conn, "SELECT pg_name, pg_img, marks, rep_obj from audit_reports where tbl_id=".$_GET['tbl_id']."");
								
								while($sqlROW=mysqli_fetch_array($sqlR))
								{ 
									$rowR  = $sqlROW;
								}
								
								if($rowR['rep_obj']=='') { $rowR['rep_obj']=3; }
		?>

        

        <!-- page content -->
         <div  role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div >                  
                  <div class="x_title1 text-center">
                  	<!--<a href="loading.php?pg=index.php"><img src="images/ads-rescue.png" height="250" > </a>-->
                    <h2>Here is your Ads audit report <?php echo $rowR['pg_name']; ?></h2>                    
                  </div>   
                  
                  <div class="x_content">
                  		<?php
								$fields = array(
									0 => array('Account Age', '', 'How Old is Ad Account'),
									1 => array('Account Status', '', 'Ad Account Active/ Inactive'),
									2 => array('Total No. of Campaigns', '', 'No of campaigns on your ad account'),
									3 => array('CBO Campaigns', '', 'Facebook campaign optimizations will be made from the campaign level.'),
									4 => array('Pixel Codes', '', 'If Pixel Code is installed'),
									5 => array('Custom Conversions', '', 'Custom conversions allows you to track & optimise your ad performance'),
									6 => array('Remarketing List', 'Create remarketing list & Start ads', 'Whom you are targeting in your remarketing ads'),
									7 => array('Top 25% Website visitors', 'Create LA of Top 25% website visitors list & Start ads', 'Whether Most Frequently Visited Visitors List is Updated'),
									8 => array('Look Alike of Top 25% Website visitors', '', 'Whether Look Alike of Top 25% Website Visitors Targeted'),
									9 => array('Rules', 'Create Rules & control the cost per result', 'Did you set any rules while targeting?'),
									10 => array('Custom Audience', 'Upload custom audience list & Start Ads', 'Manually added custom audiences list'),
									11 => array('Look-alike Audiences', 'Create LA of custom audience & start Ads', 'Are you targeting any look-alike audiences'),
									12 => array('Narrow Audiences', 'Remove Narrow audience in your target. It will improve your leads quality', 'Group of narrowed down audience'),
									13 => array('Budget type', '', 'Daily budget/ Lifetime budget'),
									14 => array('Day parting (Lifetime budget)', '', ''),
									15 => array('Linked Instagram Account', 'Connect Instagram account & improve your branding value', 'If Instagram Account is Linked to Ad Account'),
									16 => array('Instagram Followers', '', 'No. of followers in instagram account'),
									17 => array('Is Audience Expansion Off?', '', 'Is audience expansion Off?'),
									18 => array('Dynamic Creative Ads', 'Start dynamic creative ads. It will help to reduce your lead cost', 'Automatically generated ad creatives by facebook by using the images, text, videos already available'),
									19 => array('New visual updated on', 'You should change ad visuals every 2 weeks once', 'When is the last time you updated ad creatives'),
									
									20 => array('Location Targets', '', 'List of locations you are targeting'),
									21 => array('Bidding Type', 'Start manual bidding. It will control your cost per result', ''),
									22 => array('Placement Type (Auto/Manual)', '', 'Automatic/ Manual'),
									
									23 => array('Age Group Targets', '', 'List of age groups you are targeting'),
									24 => array('Ad Assets', 'Please start video ads as well as', 'Which types of ads you are currently running'),
									25 => array('Results based on Ad Assets', '', 'Results based on Ad Assets'),
									
									26 => array('Ad Quality Ranking', '', 'Quality of your ad vs competitors ad with a similar audience type'),
									
									27 => array('Objective wise cost per results', '', 'Objective wise cost per results'),
									28 => array('High CPL - Campaigns (LG)', '', 'Highest amount set to pay per campaign'),
									29 => array('High CPL - Campaigns (Conversions)', '', 'Highest amount set to pay per campaign'),
									
									30 => array('Leads=0 AdSets (LG)', '', ''),
									31 => array('Leads=0 AdSets (Conversions)', '', ''),
									32 => array('CPL>25% AdSets (LG)', '', ''),
									33 => array('CPL>25% AdSets (Conversions)', '', ''),
									34 => array('CPL<25% AdSets (LG)', '', ''),
									35 => array('CPL<25% AdSets (Conversions)', '', ''),
									
									36 => array('Landing Pages', '', 'Whether any landing page connected'),
									37 => array('Lead Form Questions', 'Add Questions (like budget, location, interest & etc.) in the lead form. It will improve your leads quality', 'List of questions you have asked your leads'),
									
									38 => array('Interest-based Targets', '', 'Which interests are you targeting in your ads'),
									39 => array('Work Employer Targets', '', 'List of Work Employe you are targeting'),
									40 => array('Work Position Targets', '', 'List of work Position you are targeting'),
									
									
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
									37 => 'Add Questions (like budget, location, interest & etc.) in the lead form. It will improve your leads quality',
									20 => 'You should change ad visuals every 2 weeks once',
								);
								
								
								
								$sqlRev = mysqli_query($conn, "SELECT a.age, a.active, a.camp_tot, a.cbo_camp, a.pix_act, a.cust_conv, a.remarket, a.top_web_25, a.top_la_25, a.rules, a.cust_aud, a.la_aud, a.nar_aud, a.daily_life, a.day_part, a.insta_acc, a.ig_followers, a.exp_on, a.dynamic, a.img_updated, a.loc_type, a.bid_type, a.placement, a.age_group, a.adset_type, a.ad_type_res, a.ad_qty, a.obj_cost_result, a.high_cpl, a.high_cpl_conv, b.lg_lead_0, b.conv_lead_0, b.lg_cpl_less25, b.conv_cpl_less25, b.lg_cpl_high25, b.conv_cpl_high25, a.lp_url, a.lead_qus, a.interests, a.work_emp, a.work_pos FROM audit_data as a, audit_adtracker as b where a.rep_id = b.rep_id AND a.rep_id='".$_GET['tbl_id']."'");
								
								while($sqlROW=mysqli_fetch_array($sqlRev))
								{ 
									$ans  = $sqlROW;
								}
								//d($d); 
						?>
                                <form method="post" action="">
							
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
										  if($k<=26) {
											
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											
											$ans[$k] = str_replace('<>', '<br>', $ans[$k]);
											
											if($v[1]!='' && in_array(strtolower($ans[$k]), $inactive_or_no) 
											   || (($k+1)==22) && (strpos(strtolower($ans[$k]), 'manual:') == false) 
											   || (($k+1)==25) && (strpos(strtolower($ans[$k]), 'video:') == false)
											   || (($k+1)==19 && strpos(strtolower($ans[$k]), 'yes:') == false)
											   || (($k+1)==37 && trim($ans[$k])=='')
											) { $Recom_list[($k+1)] = $v[1]; } 
											if($v[1]!='' && ($k+1)==20) {
												$now = time(); // or your date as well
												$your_date = strtotime($ans[$k]);
												$datediff = $now - $your_date;
												$dateDif = round($datediff / (60 * 60 * 24));
												if($dateDif>30) { $Recom_list[($k+1)] = $v[1]; } 
											}
											
										  if($rowCount % $numOfCols == 0) { ?> <div class="row"> <?php } 
											$rowCount++; 
											
											if(in_array(strtolower($ans[$k]), $active_or_yes)) { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; } else if(in_array(strtolower($ans[$k]), $inactive_or_no)) { $cls="class='deep-orange'"; $clr='deep-orange'; $icon="icon-close"; } else { $cls=""; $clr=''; $icon=""; $showAns=false; }
											if((($k+1)==5 || ($k+1)==6) && $ans[$k]!='' && $ans[$k]!='No') { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; $showAns=true; } else { $showAns=false; }
											//if(($k+1)==18 && in_array(strtolower($ans[$k]), $active_or_yes)) { $clr='deep-orange'; }
											//if(($k+1)==18 && in_array(strtolower($ans[$k]), $inactive_or_no)) { $clr='teal'; } 
											if(trim($ans[$k])=='') { $ans[$k]='Data Not Found!'; }
											?>  
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6  col-xs-12" id="r<?php echo ($k+1); ?>">  
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="card-block">
                                                                <div class="media">
                                                                    <div class="media-body text-xs-left">
                                                                        <h5><?php echo ($k+1).'. '.$v[0]; ?> <i data-toggle="tooltip" data-placement="top" title="<?php echo $v[2]; ?>" class="fa fa-info-circle" aria-hidden="true"></i></h5>
                                                            			 <?php if($icon=='' || $showAns===true) { ?><span><?php echo $ans[$k]; ?></span><?php } ?>
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
											if($rowCount % $numOfCols == 0 || $k==26) { ?> </div> <?php } 
										  } 
										}
										
										$numOfCols = 1;
										$rowCount = 0;
										$bootstrapColWidth = 12 / $numOfCols;
										foreach ($fields as $k => $v)
										{ 
											if($k>=27) {
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											
											$ans[$k] = str_replace('<>', '<br>', $ans[$k]);
											
											if($v[1]!='' && in_array(strtolower($ans[$k]), $inactive_or_no) 
											   || (($k+1)==22) && (strpos(strtolower($ans[$k]), 'manual:') == false) 
											   || (($k+1)==25) && (strpos(strtolower($ans[$k]), 'video:') == false)
											   || (($k+1)==19 && strpos(strtolower($ans[$k]), 'yes:') == false)
											   || (($k+1)==37 && trim($ans[$k])=='')
											) { $Recom_list[($k+1)] = $v[1]; } 
											if($v[1]!='' && ($k+1)==20) {
												$now = time(); // or your date as well
												$your_date = strtotime($ans[$k]);
												$datediff = $now - $your_date;
												$dateDif = round($datediff / (60 * 60 * 24));
												if($dateDif>14) { $Recom_list[($k+1)] = $v[1]; } 
											}
											
										  if(($rowR['rep_obj']==1 && ($k+1)!=31) || ($rowR['rep_obj']==2 && ($k+1)!=32) || $rowR['rep_obj']==3) 
											{
												
										  if($rowCount % $numOfCols == 0) { ?> <div class="row"> <?php } 
											$rowCount++; 
											
											if(in_array(strtolower($ans[$k]), $active_or_yes)) { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; } else if(in_array(strtolower($ans[$k]), $inactive_or_no)) { $cls="class='deep-orange'"; $clr='deep-orange'; $icon="icon-close"; } else { $cls=""; $clr=''; $icon=""; }
											
											if(trim($ans[$k])=='') { $ans[$k]='Data Not Found!'; }
											if(trim($ans[$k])!='' && ($k+1)>=39 && ($k+1)<=41) { $ans[$k]='<ol><li>'.$ans[$k].'</li></ol>'; }
											$rightLabel = '';
											if(($k+1)>=31 && ($k+1)<=36) { $rightLabel = 'Lifetime'; }
											if(($k+1)>=28 && ($k+1)<=30) { $rightLabel = 'Lifetime'; }
											
											?>  
                                                <div class="col-lg-12 col-md-12 col-sm-12" id="r<?php echo ($k+1); ?>">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="card-block">
                                                                <div class="media">
                                                                    <div class="media-body text-xs-left">
                                                                        <span class="pull-left">
                                                                        <h5><?php echo ($k+1).'. '.$v[0]; ?> <i data-toggle="tooltip" data-placement="top" title="<?php echo $v[2]; ?>" class="fa fa-info-circle" aria-hidden="true"></i></h5>
                                                                        </span>
                                                                        <?php if($rightLabel!='') { ?>
                                                                        <span class="pull-right">
                                                                            <?php echo $rightLabel; ?>
                                                                        </span><?php } ?>
                                                                        <div class="clearfix"></div>
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
										}
										
										//d($Recom_list);
										?>                                    	                                   
                               
	
                                <br><br>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

						
         
        				</div>
                        <div class="row">
                          <div class="col-xs-12 col-sm-12 col-md-7 col-lg-8">
                          	<?php 
							$Recom_list = array_filter($Recom_list);                 
							echo '<div class="list-group">
							<a class="list-group-item list-group-item-action active" style="color:#fff;">Recommendations to Improve Your Ads:</a>';
							if(count($Recom_list)>0) {
								foreach($Recom_list as $k => $v) {
									echo '<a href="loading.php?pg=#r'.$k.'" class="list-group-item list-group-item-action">'.$k.'. '.$v.'</a>';
								}
							} else {
									echo '<a class="list-group-item list-group-item-action">No Recommendation list</a>';
							}
							echo '</div>';
							?>
                            <!--
                            <div class="list-group">
								<a class="list-group-item list-group-item-action active" style="color:#fff;">Recommendations to Improve Your Ads:</a>
                                <a class="list-group-item list-group-item-action">1. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">2. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">3. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">4. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">5. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">6. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">7. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">8. Create Rules to reduce cost per result & increase your ads grading score</a>
                                <a class="list-group-item list-group-item-action">9. Create Rules to reduce cost per result & increase your ads grading score</a> 
                          </div>-->
                          </div>
						  <div class="col-xs-12 col-sm-12 col-md-5 col-lg-4">
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
															<img src="<?php echo $rowR['pg_img']; ?>" alt=""/>
															<h3><?php echo $rowR['pg_name']; ?></h3>
															<h5>You have scored</h5>
															<div class="score"><span><?php echo $rowR['marks']+25; ?></span>/100</div>
														</div>
													  <img src="images/logo-ads.png" alt="" class="adsLogo" />
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
                        </div>	

                </div>
                 <div class="clearfix"></div>
                        <div class="clearfix"></div>
                        <hr>
                        
                        <div class="text-center">
                        	<H3>Made with ❤️ by BYT</H3>
                        </div>
                        <div class="clearfix"></div>
                        <div class="clearfix"></div>
                        <br>
                        <div class="text-center">
                        	<p>Copyright © 2020 BYT. All rights reserved. Privacy & Legal Policies</p>
                        </div>
        		</div>
              </div>
             
        </div>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
 <style>
	.bs-example{
    	margin: 100px 60px;
    }
	.dataTable { border-collapse: collapse !important; }
</style>
<script>
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
		
		$("table[id^='TABLE']").DataTable( {
        "scrollCollapse": false,
        "searching": true,
        "paging": false,
		"stripeClasses": [],
		"oLanguage": {
			   "sSearch": "",
				"sSearchPlaceholder": "Search"
			},
    	} );
    });
</script>
<script>
updateDonutChart('#specificChart', <?php echo $rowR['marks']+25; ?>, true);

</script>
<?php //include 'footer.php'; ?>