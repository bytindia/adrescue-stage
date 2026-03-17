<?php include 'header.php';  //ini_set('display_errors', 1);

echo "<script>window.location = 'checklist.php';</script>";
exit();
//$conn2 = mysqli_connect('localhost', 'salesninja', 'SalesNinja#2000', 'salesninja');
if(isset($_POST['dt_submit'])){
	//d($_POST); exit;
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	
	$_SESSION['stDt2'] = $_POST['start2'];
	$_SESSION['enDt2'] = $_POST['end2'];
	
	if(isset($_POST['cmp_dt'])) { $_SESSION['cmp_dt'] = 'on'; } else { unset($_SESSION['cmp_dt']); }
	
	echo "<script>window.location = 'client-dashboard.php';</script>";
	exit();
}

if(!isset($_SESSION['stDt2'])) {
	$start2 = date('m/d/Y',strtotime('today - 61 days'));
	$end2 = date('m/d/Y',strtotime('today - 31 days'));
	$_SESSION['stDt2'] = $start2;
	$_SESSION['enDt2'] = $end2;
}

$valDt = date('d/m/Y',strtotime($_SESSION['stDt']))."<br>".date('d/m/Y',strtotime($_SESSION['enDt']));
$valDt2 = date('d/m/Y',strtotime($_SESSION['stDt2']))."<br>".date('d/m/Y',strtotime($_SESSION['enDt2']));

$fb_timeQry = " AND (st_date>='".date('Y-m-d',strtotime($_SESSION['stDt']))."' AND en_date<='".date('Y-m-d',strtotime($_SESSION['enDt']))."')";
$fb_timeQry2 = " AND (st_date>='".date('Y-m-d',strtotime($_SESSION['stDt2']))."' AND en_date<='".date('Y-m-d',strtotime($_SESSION['enDt2']))."')";

$timeQry = " AND (stDt>=".strtotime($_SESSION['stDt'])." AND enDt<=".strtotime($_SESSION['enDt']).")";
$timeQry2 = " AND (stDt>=".strtotime($_SESSION['stDt2'])." AND enDt<=".strtotime($_SESSION['enDt2']).")";
Auth();
$pgHeadline = 'Client - Dashboard';
$pgID = 8;
$err =''; 
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
if(isset($_GET['del'])) {
	mysqli_query($conn, "UPDATE client_dashboard SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'client-dashboard.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " client_dashboard WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>
<style> 
.cmp { display:none; }
.chk { float:right; <?php if(!isset($_SESSION['cmp_dt'])) { ?> display:none; <?php } ?> }
.plus { color:#32A843; }
.minus { color:#DD3739; }
/*#datatable1 img { height:30px; }*/
.dt { display:none; color:#b5babb; font-size:12px; float:left; }
.hd { text-align:center; }
div#lab { width:60px; }
div#edit { width:42px; }
.fa-spin { color:#2392f1; }
.fa { cursor:pointer; }
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
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=client-dashboard-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                
                      	</div>  
                      </li>
                      
                    </ul>
                    <form method="post" action="client-dashboard.php">
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
                      <li> &nbsp; </li>
                      <li class="cmp_dt" <?php if(!isset($_SESSION['cmp_dt'])) { ?> style="display:none"<?php } ?>>
                      			<div id="reportrange_right1" class="pull-right1" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                                      <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                                      <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                                 </div>   
                                <input type="hidden" id="stDt2" name="start2" value="<?php echo $_SESSION['stDt2']; ?>">
								<input type="hidden" id="enDt2" name="end2" value="<?php echo $_SESSION['enDt2']; ?>">                    
                      </li>
                      <li> &nbsp; </li>
                      <li>
                      			<label class="cnt" data-placement="top" data-toggle="tooltip" data-original-title="Compare">
                                 <input type="checkbox" name="cmp_dt" id="checkbox" <?php if(isset($_SESSION['cmp_dt'])) { ?>checked="checked"<?php } ?>>
                                  <span class="checkmark"></span>
                                </label>
                      </li>
                      <li><input type="submit" name="dt_submit" value="Submit" class="btn btn-primary"></li>
                    </ul>
                    </form>
                    <div class="clearfix"></div>
                  </div>
                 
                  
                  <div class="x_content">
                  
                  		<?php 
						//d($_SESSION);
										$allData = $gData = $fbData = $fbData1 = $fbData2 = $inData = $inData1 = $inData2 = $gIds = $fbIds = $inIds = array();
										$sqlRev=mysqli_query($conn, "SELECT tbl_id,client_name,fb_acc,g_acc,in_acc,chat_acc FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
										
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{
											if($sqlROW['fb_acc']!='') { $fbIds[] = $sqlROW['fb_acc']; }
											if($sqlROW['g_acc']!='') { $gIds[] = $sqlROW['g_acc']; }
											if($sqlROW['in_acc']!='') { $inIds[] = $sqlROW['in_acc']; }
											$allData[] = $sqlROW;
										}
										//d($fbData); 
										if(count($fbIds)>0) {			
										//echo "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $fb_timeQry group by fb_acc"; 						
											$rep_fb = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $fb_timeQry group by fb_acc");
											while($rw_fb =mysqli_fetch_array($rep_fb))	{	$fbData[$rw_fb['fb_acc']] = $rw_fb;	}
											
											$rep_fb1 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_lg) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='4' $fb_timeQry group by fb_acc");
											while($rw_fb1 =mysqli_fetch_array($rep_fb1))	{	$fbData1[$rw_fb1['fb_acc']] = $rw_fb1;	}
											
											$rep_fb2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_con) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='2' $fb_timeQry group by fb_acc");
											while($rw_fb2 =mysqli_fetch_array($rep_fb2))	{	$fbData2[$rw_fb2['fb_acc']] = $rw_fb2;	}
										}
										
										if(count($fbIds)>0 && isset($_SESSION['cmp_dt'])) {			
										//echo "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $fb_timeQry group by fb_acc"; 						
											$rep_fb_2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") $fb_timeQry2 group by fb_acc");
											while($rw_fb_2 =mysqli_fetch_array($rep_fb_2))	{	$fbData_2[$rw_fb_2['fb_acc']] = $rw_fb_2;	}
											
											$rep_fb1_2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_lg) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='4' $fb_timeQry2 group by fb_acc");
											while($rw_fb1_2 =mysqli_fetch_array($rep_fb1_2))	{	$fbData1_2[$rw_fb1_2['fb_acc']] = $rw_fb1_2;	}
											
											$rep_fb2_2 = mysqli_query($conn, "SELECT fb_acc, SUM(spend) as spend, SUM(leads_con) as leads  FROM `fb_reports` WHERE fb_acc in (".implode(',',$fbIds).") AND camp_ty='2' $fb_timeQry2 group by fb_acc");
											while($rw_fb2_2 =mysqli_fetch_array($rep_fb2_2))	{	$fbData2_2[$rw_fb2_2['fb_acc']] = $rw_fb2_2;	}
										}
										
										//d($fbData); d($fbData1); d($fbData2); exit;
										if(count($gIds)>0) {
											//echo "SELECT SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).")"; 
											//echo "SELECT g_acc, SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") group by g_acc"; 
											$rep_g = mysqli_query($conn, "SELECT g_acc, SUM(spend) as spend, SUM(conv) as leads FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") $timeQry group by g_acc");
											while($rw_g =mysqli_fetch_array($rep_g))		{	$gData[$rw_g['g_acc']] = $rw_g;	}
										}
										if(count($gIds)>0 && isset($_SESSION['cmp_dt'])) {
											//echo "SELECT SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).")"; 
											//echo "SELECT g_acc, SUM(spend), SUM(conv) FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") group by g_acc"; 
											$rep_g_2 = mysqli_query($conn, "SELECT g_acc, SUM(spend) as spend, SUM(conv) as leads FROM `g_reports` WHERE g_acc in (".implode(',',$gIds).") $timeQry2 group by g_acc");
											while($rw_g_2 =mysqli_fetch_array($rep_g_2))		{	$gData_2[$rw_g_2['g_acc']] = $rw_g_2;	}
										}
										
										if(count($inIds)>0) {
											$rep_in = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") $timeQry group by in_acc");
											while($rw_in =mysqli_fetch_array($rep_in))	{	$inData[$rw_in['in_acc']] = $rw_in;	}
											
											$rep_in1 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(one_click_leads) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='0' $timeQry group by in_acc");
											while($rw_in1 =mysqli_fetch_array($rep_in1))	{	$inData1[$rw_in1['in_acc']] = $rw_in1;	}
											
											$rep_in2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(web_conv) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='5' $timeQry group by in_acc");
											while($rw_in2 =mysqli_fetch_array($rep_in2))	{	$inData2[$rw_in2['in_acc']] = $rw_in2;	}
										}
										if(count($inIds)>0 && isset($_SESSION['cmp_dt'])) {
											$rep_in_2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") $timeQry2 group by in_acc");
											while($rw_in_2 =mysqli_fetch_array($rep_in_2))	{	$inData_2[$rw_in_2['in_acc']] = $rw_in_2;	}
											
											$rep_in1_2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(one_click_leads) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='0' $timeQry2 group by in_acc");
											while($rw_in1_2 =mysqli_fetch_array($rep_in1_2))	{	$inData1_2[$rw_in1_2['in_acc']] = $rw_in1_2;	}
											
											$rep_in2_2 = mysqli_query($conn, "SELECT in_acc, SUM(spend) as spend, SUM(web_conv) as leads  FROM `in_reports` WHERE in_acc in (".implode(',',$inIds).") AND camp_ty='5' $timeQry2 group by in_acc");
											while($rw_in2_2 =mysqli_fetch_array($rep_in2_2))	{	$inData2_2[$rw_in2_2['in_acc']] = $rw_in2_2;	}
										}
										//d($gData);
										//d($allData);
										//d($inData2);
								?>
                               <div id="mydiv"></div>
                               <div class="table-multi-columns table-fill">
                                <table id="datatable1" class="table table-hover table-striped table-bordered">
                                    <tr>                                        
                                        <td rowspan="2">SNo</td>
                                        <td rowspan="2" colspan="3" >Client Name</td>                                    	
                                        <td colspan="5" class="head_1 hd"><img src="images/fb-2.png" height="30" /></td>
                                        <td colspan="3" class="head_2 hd"><img src="images/google-ads.png"  height="30" /></td> 
                                        <td colspan="5" class="head_3 hd"><img src="images/in-ads.png"  height="30" /></td> 
                                        <td rowspan="2">Chat</td>
                                        <td rowspan="2"><div id="edit">Edit</div></td>      	
                                    </tr>
                                     <tr>
                                     	<!-- Facebook -->                                  	
                                        <td id="lab"><div id="lab">Spend <input type="checkbox" name="chk" class="chk" id="chk1" value="1,1" data-placement="top" data-toggle="tooltip" data-original-title="Compare Spend"></div> <span class="dt dt_11"><?php echo $valDt; ?></span></td>
                                        <td class="cmp cmp_11">Spend <span class="dt dt_11"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_11">Change</td>
                                        <td class="cmp cmp_11">Change(%)</td>
                                        
                                        <td id="lab"><div id="lab">Leads <input type="checkbox" name="chk2" class="chk" id="chk2" value="1,2"></div> <span class="dt dt_12"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_12">Leads <span class="dt dt_12"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_12">Change</td>
                                        <td class="cmp cmp_12">Change(%)</td>
                                        
                                        <td id="lab"><div id="lab">CPL <input type="checkbox" name="chk2" class="chk" id="chk2" value="1,3"></div> <span class="dt dt_13"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_13">CPL <span class="dt dt_13"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_13">Change</td>
                                        <td class="cmp cmp_13">Change(%)</td>
                                        
                                        <td><div id="lab">Conv. <input type="checkbox" name="chk2" class="chk" id="chk2" value="1,4"></div> <span class="dt dt_14"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_14">Conv. <span class="dt dt_14"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_14">Change</td>
                                        <td class="cmp cmp_14">Change(%)</td>
                                        
                                        <td><div id="lab">CPC <input type="checkbox" name="chk2" class="chk" id="chk2" value="1,5"></div> <span class="dt dt_15"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_15">CPC <span class="dt dt_15"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_15">Change</td>
                                        <td class="cmp cmp_15">Change(%)</td>
                                        
                                        <!-- Google -->
                                        <td><div id="lab">Spend  <input type="checkbox" name="chk" class="chk" id="chk1" value="2,1" ></div> <span class="dt dt_21"><?php echo $valDt; ?></td>
                                        <td class="cmp cmp_21">Spend <span class="dt dt_21"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_21">Change</td>
                                        <td class="cmp cmp_21">Change(%)</td>
                                        
                                        <td><div id="lab">Conv.   <input type="checkbox" name="chk" class="chk" id="chk1" value="2,2" ></div> <span class="dt dt_22"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_22">Conv. <span class="dt dt_22"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_22">Change</td>
                                        <td class="cmp cmp_22">Change(%)</td>
                                        
                                        <td><div id="lab">CPC   <input type="checkbox" name="chk" class="chk" id="chk1" value="2,3" ></div> <span class="dt dt_23"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_23">CPC <span class="dt dt_23"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_23">Change</td>
                                        <td class="cmp cmp_23">Change(%)</td>
                                        
                                        <!-- LinkedIn -->
                                        <td><div id="lab">Spend <input type="checkbox" name="chk2" class="chk" id="chk2" value="3,1"></div> <span class="dt dt_31"><?php echo $valDt; ?></td>
                                        <td class="cmp cmp_31">Spend <span class="dt dt_31"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_31">Change</td>
                                        <td class="cmp cmp_31">Change(%)</td>
                                        
                                        <td><div id="lab">Leads <input type="checkbox" name="chk2" class="chk" id="chk2" value="3,2"></div> <span class="dt dt_32"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_32">Leads <span class="dt dt_32"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_32">Change</td>
                                        <td class="cmp cmp_32">Change(%)</td>
                                        
                                        <td><div id="lab">CPL <input type="checkbox" name="chk2" class="chk" id="chk2" value="3,3"></div> <span class="dt dt_33"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_33">CPL <span class="dt dt_33"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_33">Change</td>
                                        <td class="cmp cmp_33">Change(%)</td>
                                        
                                        <td><div id="lab">Conv. <input type="checkbox" name="chk2" class="chk" id="chk2" value="3,4"></div> <span class="dt dt_34"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_34">Conv. <span class="dt dt_34"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_34">Change</td>
                                        <td class="cmp cmp_34">Change(%)</td>
                                        
                                        <td><div id="lab">CPC <input type="checkbox" name="chk2" class="chk" id="chk2" value="3,5"></div> <span class="dt dt_35"><?php echo $valDt; ?></td> 
                                        <td class="cmp cmp_35">CPC <span class="dt dt_35"><?php echo $valDt2; ?></span></td>
                                        <td class="cmp cmp_35">Change</td>
                                        <td class="cmp cmp_35">Change(%)</td>   	
                                    </tr>
                                    <?php $i=1;
									function changeDiff($a,$b) {
										$a = str_replace(',','',$a);
										$b = str_replace(',','',$b);
										if(is_numeric($a) && is_numeric($b) && !is_nan($a) && !is_nan($b) && !is_infinite($a) && !is_infinite($b)) { $c = round($a - $b); } else { $c = '-';}
										return $c; 
									}
									function changePer($a,$b) {
										$a = str_replace(',','',$a);
										$b = str_replace(',','',$b);
										//if($a!='-' && $b!='-') {
										if(is_numeric($a) && is_numeric($b) && !is_nan($a) && !is_nan($b) && !is_infinite($a) && !is_infinite($b)) {
										$p_v = round(@((($a - $b)/$b)*100));
										if($p_v>0) { $neg='plus'; } else { $neg='minus'; }
										$c = "<span class='".$neg."'>".$p_v."%</span>"; //round((($a - $b)/$b)*100, 2).'%'; 
										} else { $c = '-';}
										return $c; 
									}
                                    foreach($allData as $k => $v) 
									{
										
										//FACEBOOK
										$fb_spend = $fb_lead = $fb_cpc = $fb_spend_con = $fb_lead_con = $fb_cpc_con = '-';
										$fb_spend_c = $fb_lead_c = $fb_cpc_c = $fb_spend_con_c = $fb_lead_con_c = $fb_cpc_con_c = '-';
										
										if(isset($fbData[$v['fb_acc']])) { 
											$fb_spend = moneyFormatIndia(round($fbData[$v['fb_acc']]['spend']));
											$fb_lead = $fbData1[$v['fb_acc']]['leads'];
											$fb_cpc = round(@($fbData1[$v['fb_acc']]['spend']/$fbData1[$v['fb_acc']]['leads']));
										}
										if(isset($fbData2[$v['fb_acc']])) { 
											$fb_lead_con = $fbData2[$v['fb_acc']]['leads'];
											$fb_cpc_con = round(@($fbData2[$v['fb_acc']]['spend']/$fbData2[$v['fb_acc']]['leads']));
										}										
										if(isset($fbData_2[$v['fb_acc']])) { 
											$fb_spend_c = moneyFormatIndia(round($fbData_2[$v['fb_acc']]['spend']));
											$fb_lead_c = $fbData1_2[$v['fb_acc']]['leads'];
											$fb_cpc_c = round(@($fbData1_2[$v['fb_acc']]['spend']/$fbData1_2[$v['fb_acc']]['leads']));
										}
										if(isset($fbData2_2[$v['fb_acc']])) { 
											$fb_lead_con_c = $fbData2_2[$v['fb_acc']]['leads'];
											$fb_cpc_con_c = round(@($fbData2_2[$v['fb_acc']]['spend']/$fbData2_2[$v['fb_acc']]['leads']));
										}
										
										//GOOGLE
										$g_spend = $g_lead = $g_cpc = '-';
										$g_spend_c = $g_lead_c = $g_cpc_c = '-';
										
										if(isset($gData[$v['g_acc']])) { 
											$g_spend = moneyFormatIndia(round($gData[$v['g_acc']]['spend']/1000000));
											$g_lead = round(@($gData[$v['g_acc']]['leads']));
											$g_cpc = round(@(($gData[$v['g_acc']]['spend']/1000000)/$gData[$v['g_acc']]['leads']));
										}
										if(isset($gData_2[$v['g_acc']])) { 
											$g_spend_c = moneyFormatIndia(round($gData_2[$v['g_acc']]['spend']/1000000));
											$g_lead_c = round(@($gData_2[$v['g_acc']]['leads']));
											$g_cpc_c = round(@(($gData_2[$v['g_acc']]['spend']/1000000)/$gData_2[$v['g_acc']]['leads']));
										}
										
										//LINKEDIN
										$in_spend = $in_lead = $in_cpc = $in_spend_con = $in_lead_con = $in_cpc_con = '-';
										$in_spend_c = $in_lead_c = $in_cpc_c = $in_spend_con_c = $in_lead_con_c = $in_cpc_con_c = '-';
										
										if(isset($inData[$v['in_acc']])) { 
											$in_spend = moneyFormatIndia(round($inData[$v['in_acc']]['spend']));
											$in_lead = $inData1[$v['in_acc']]['leads'];
											$in_cpc = round(@($inData1[$v['in_acc']]['spend']/$inData1[$v['in_acc']]['leads']));
										}
										if(isset($inData2[$v['in_acc']])) { 
											$in_lead_con = $inData2[$v['in_acc']]['leads'];
											$in_cpc_con = round(@($inData2[$v['in_acc']]['spend']/$inData2[$v['in_acc']]['leads']));
										}
										if(isset($inData_2[$v['in_acc']])) { 
											$in_spend_c = moneyFormatIndia(round($inData_2[$v['in_acc']]['spend']));
											$in_lead_c = $inData1_2[$v['in_acc']]['leads'];
											$in_cpc_c = round(@($inData1_2[$v['in_acc']]['spend']/$inData1_2[$v['in_acc']]['leads']));
										}
										if(isset($inData2_2[$v['in_acc']])) { 
											$in_lead_con_c = $inData2_2[$v['in_acc']]['leads'];
											$in_cpc_con_c = round(@($inData2_2[$v['in_acc']]['spend']/$inData2_2[$v['in_acc']]['leads']));
										}
										
										$wa_fb = "['$fb_spend', '$fb_lead', '$fb_cpc', '$fb_lead_con', '$fb_cpc_con']";
										$wa_g= "['$g_spend', '$g_lead', '$g_cpc']";
										$wa_in = "['$in_spend', '$in_lead', '$in_cpc', '$in_lead_con', '$in_cpc_con']";
									?>
                                    <tr>                                  	
                                        <td><?php echo $i; ?></td>
                                        <td><a href="loading.php?pg=client-dashboard-view.php?id=<?php echo $v["tbl_id"]; ?>" target="_blank" class="blue"><?php echo $v['client_name']; ?></a> </td> 
                                        <td><a onClick="return whatsApp(<?php echo $wa_fb; ?>,<?php echo $wa_g; ?>,<?php echo $wa_in; ?>,'<?php echo str_replace("'", "", $v['client_name']); ?>','<?php echo $valDt; ?>');"><i class="fa fa-whatsapp fa-lg" style="color: #45bb09"></i></a></td>
                                        <td><!--<a onClick="return ajaxLive(<?php echo $v["tbl_id"]; ?>,<?php echo $_SESSION['uid']; ?>);" class="ref<?php echo $v["tbl_id"]; ?>" ><i id="ref<?php echo $v["tbl_id"]; ?>" class="fa fa-refresh fa-lg" style="color: #9ea2a2"></i></a>--></td>
                                        
                                        <!-- FB <?php echo $v['client_name']; ?>-->
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_0_0"><?php echo $fb_spend; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_0_1 cmp cmp_11"><?php echo $fb_spend_c; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_0_2 cmp cmp_11"><?php echo changeDiff($fb_spend,$fb_spend_c); ?>  </td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_0_3 cmp cmp_11"><?php echo changePer($fb_spend,$fb_spend_c); ?> </td>
                                        
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_1_0"><?php echo $fb_lead; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_1_1 cmp cmp_12"><?php echo $fb_lead_c; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_1_2 cmp cmp_12"><?php echo changeDiff($fb_lead,$fb_lead_c); ?>  </td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_1_3 cmp cmp_12"><?php echo changePer($fb_lead,$fb_lead_c); ?> </td>
                                        
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_2_0"><?php echo $fb_cpc; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_2_1 cmp cmp_13"><?php echo $fb_cpc_c; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_2_2 cmp cmp_13"><?php echo changeDiff($fb_cpc,$fb_cpc_c); ?>  </td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_2_3 cmp cmp_13"><?php echo changePer($fb_cpc,$fb_cpc_c); ?> </td>
                                        
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_3_0"><?php echo $fb_lead_con; ?></td>                                        
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_3_1 cmp cmp_14"><?php echo $fb_lead_con_c; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_3_2 cmp cmp_14"><?php echo changeDiff($fb_lead_con,$fb_lead_con_c); ?>  </td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_3_3 cmp cmp_14"><?php echo changePer($fb_lead_con,$fb_lead_con_c); ?> </td>
                                        
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_4_0"><?php echo $fb_cpc_con; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_4_1 cmp cmp_15"><?php echo $fb_cpc_con_c; ?></td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_4_2 cmp cmp_15"><?php echo changeDiff($fb_cpc_con,$fb_cpc_con_c); ?>  </td>
                                        <td class="fb<?php echo $v["tbl_id"]; ?>_4_3 cmp cmp_15"><?php echo changePer($fb_cpc_con,$fb_cpc_con_c); ?> </td>
                                        
                                        
                                        <!-- Google -->
                                        <td class="g<?php echo $v["tbl_id"]; ?>_0_0"><?php echo $g_spend; ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_0_1 cmp cmp_21"><?php echo $g_spend_c; ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_0_2 cmp cmp_21"><?php echo changeDiff($g_spend,$g_spend_c); ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_0_3 cmp cmp_21"><?php echo changePer($g_spend,$g_spend_c); ?></td>
                                        
                                        <td class="g<?php echo $v["tbl_id"]; ?>_1_0"><?php echo $g_lead; ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_1_1 cmp cmp_22"><?php echo $g_lead_c; ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_1_2 cmp cmp_22"><?php echo changeDiff($g_lead,$g_lead_c); ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_1_3 cmp cmp_22"><?php echo changePer($g_lead,$g_lead_c); ?></td>
                                        
                                        <td class="g<?php echo $v["tbl_id"]; ?>_2_0"><?php echo $g_cpc; ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_2_1 cmp cmp_23"><?php echo $g_cpc_c; ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_2_2 cmp cmp_23"><?php echo changeDiff($g_cpc,$g_cpc_c); ?></td>
                                        <td class="g<?php echo $v["tbl_id"]; ?>_2_3 cmp cmp_23"><?php echo changePer($g_cpc,$g_cpc_c); ?></td>
                                        
                                        
                                        
                                        <!-- LinkedIn -->
                                        <td class="in<?php echo $v["tbl_id"]; ?>_0_0"><?php echo $in_spend; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_0_1 cmp cmp_31"><?php echo $in_spend_c; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_0_2 cmp cmp_31"><?php echo changeDiff($in_spend,$in_spend_c); ?>  </td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_0_3 cmp cmp_31"><?php echo changePer($in_spend,$in_spend_c); ?> </td>
                                        
                                        <td class="in<?php echo $v["tbl_id"]; ?>_1_0"><?php echo $in_lead; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_1_1 cmp cmp_32"><?php echo $in_lead_c; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_1_2 cmp cmp_32"><?php echo changeDiff($in_lead,$in_lead_c); ?>  </td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_1_3 cmp cmp_32"><?php echo changePer($in_lead,$in_lead_c); ?> </td>
                                        
                                        <td class="in<?php echo $v["tbl_id"]; ?>_2_0"><?php echo $in_cpc; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_2_1 cmp cmp_33"><?php echo $in_cpc_c; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_2_2 cmp cmp_33"><?php echo changeDiff($in_cpc,$in_cpc_c); ?>  </td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_2_3 cmp cmp_33"><?php echo changePer($in_cpc,$in_cpc_c); ?> </td>
                                        
                                        <td class="in<?php echo $v["tbl_id"]; ?>_3_0"><?php echo $in_lead_con; ?></td>                                        
                                        <td class="in<?php echo $v["tbl_id"]; ?>_3_1 cmp cmp_34"><?php echo $in_lead_con_c; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_3_2 cmp cmp_34"><?php echo changeDiff($in_lead_con,$in_lead_con_c); ?>  </td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_3_3 cmp cmp_34"><?php echo changePer($in_lead_con,$in_lead_con_c); ?> </td>
                                        
                                        <td class="in<?php echo $v["tbl_id"]; ?>_4_0"><?php echo $in_cpc_con; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_4_1 cmp cmp_35"><?php echo $in_cpc_con_c; ?></td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_4_2 cmp cmp_35"><?php echo changeDiff($in_cpc_con,$in_cpc_con_c); ?>  </td>
                                        <td class="in<?php echo $v["tbl_id"]; ?>_4_3 cmp cmp_35"><?php echo changePer($in_cpc_con,$in_cpc_con_c); ?> </td>
                                        
                                        
                                        <!-- Edit / Delete --> 
                                        <td>
                                        	<?php
											if(isset($v['chat_acc']) && $v['chat_acc']!='') { 
											mysqli_select_db($conn2,'salesninja');
											//echo "select * from chat_leads WHERE client='".$v['chat_acc']."' AND created >= '".date('Y-m-d',strtotime($_SESSION['stDt']))."' AND created <= '".date('Y-m-d',strtotime($_SESSION['stDt']))."'"; 
											$chkRes = mysqli_query($conn2, "select * from chat_leads WHERE client='".$v['chat_acc']."' AND created >= '".date('Y-m-d',strtotime($_SESSION['stDt']))."' AND created <= '".date('Y-m-d',strtotime($_SESSION['enDt']))."'");						
											echo $chatTot = mysqli_num_rows($chkRes);
											}
											?>
                                        
                                        </td>
                                        <td><a href="loading.php?pg=client-dashboard-add.php?id=<?php echo $v["tbl_id"]; ?>" target="_blank"><i class="fa fa-edit fa-lg" style="color: #0289DE"></i></a> | <a href="loading.php?pg=?del=<?php echo $v["tbl_id"]; ?>"  onclick="return confirm('Are you sure you want to remove this?');" ><i class="fa fa-trash fa-lg" style="color: #E4484B"></i></a></td>  	
                                    </tr>
                                    <?php $i++;
									}
									?>
                                  
                                </table>
                                </div>
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<style>

 
/* The container */
.cnt {
  display: block;
  position: relative;
  padding-left: 35px;
  margin-bottom: 12px;
  cursor: pointer;
  font-size: 22px;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Hide the browser's default checkbox */
.cnt input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

/* Create a custom checkbox */
.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 30px;
  width: 30px;
  background-color: #eee;
}

/* On mouse-over, add a grey background color */
.cnt:hover input ~ .checkmark {
  background-color: #ccc;
}

/* When the checkbox is checked, add a blue background */
.cnt input:checked ~ .checkmark {
  background-color: #2196F3;
}

/* Create the checkmark/indicator (hidden when not checked) */
.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

/* Show the checkmark when checked */
.cnt input:checked ~ .checkmark:after {
  display: block;
}

/* Style the checkmark/indicator */
.cnt .checkmark:after {
  left: 11px;
    top: 3px;
    width: 9px;
    height: 18px;
  border: solid white;
  border-width: 0 3px 3px 0;
  -webkit-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  transform: rotate(45deg);
}
</style>
<script src="js/freeze-table.js"></script>
<script>
function ajaxLive(tblId,uId) {	
	//alert(tblId+','+uId); return false;
	$('#ref'+tblId+'').addClass('fa-spin').css("color", "#2392f1");
	$( "td[class^='fb"+tblId+"_'], td[class^='g"+tblId+"_'], td[class^='in"+tblId+"_']" ).html("<img src='images/ajax-loader4.gif'  />");	
	$.ajax({            
            url: 'ajaxLive.php',
			type: "POST",
			dataType: "JSON",
			data: {"uId": uId,"tblId": tblId},		 
            success: function(response)
            {
                
				//var jsonData = JSON.parse(response);
 				//alert('success');
				var cls ;
				$('#ref'+tblId+'').removeClass('fa-spin fa-refresh').css("color", "#9ea2a2");
				$('#ref'+tblId+'').addClass('fa-check').css("color", "#6ec9e4");
				$('.ref'+tblId+'').attr("onclick", "").unbind("click")
				
				var k=0;
				for(var l = 0; l < 3; l++){
					//if(k<20) {
					//alert('0: '+response.length);
					if(k<20) {  cls='fb'; }  
					if(k>=20 && k<=31) {  cls='g'; }  
					if(k>31) {  cls='in'; }
					//alert(2);
					
					
					//alert(3);
					for(var i = 0; i < response[l].length; i++){
						//alert(value.length);
						  //console.log(key,value);
						  for(var j = 0; j < 4; j++) {
							  console.log(k+' .'+cls+''+tblId+'_'+i+'_'+j+', '+response[l][i][j]);
							 //alert(value[i]);
							 //alert('.fb'+td+'_'+j+'_'+i+'');
							 $('.'+cls+''+tblId+'_'+i+'_'+j+'').html(response[l][i][j]);
							 //console.log(key+', '+value+': .'+cls+''+tblId+'_'+j+'_'+i+'', value[i]);
							 k++
						  }
						  //j++;
					}
					//}
					
				}
				//alert(response[0].length+', '+JSON.stringify(response[0]['spend'][0]));
                
           },
		   error: function (jqXHR, exception) {
			    $('#ref'+tblId+'').removeClass('fa-spin').css("color", "#9ea2a2");				
				var msg = '';
				if (jqXHR.status === 0) {
					msg = 'Not connect.\n Verify Network.';
				} else if (jqXHR.status == 404) {
					msg = 'Requested page not found. [404]';
				} else if (jqXHR.status == 500) {
					msg = 'Internal Server Error [500].';
				} else if (exception === 'parsererror') {
					msg = 'Requested JSON parse failed.';
				} else if (exception === 'timeout') {
					msg = 'Time out error.';
				} else if (exception === 'abort') {
					msg = 'Ajax request aborted.';
				} else {
					msg = 'Uncaught Error.\n' + jqXHR.responseText;
				}
				$( "td[class^='fb"+tblId+"_'], td[class^='g"+tblId+"_'], td[class^='in"+tblId+"_']" ).html("-");
				alert(msg);
		  }
       });
}
var lab_fb = ['Spend', 'Leads', 'CPL', 'Conv.', 'CPC'];
var lab_g =  ['Spend', 'Conv.', 'CPC'];
var lab_in=  ['Spend', 'Leads', 'CPL', 'Conv.', 'CPC'];

function whatsApp(fb,g,li,cl,dt) {
	//alert(fb.toString());
	var msg = "*"+cl+"* %0A```"+dt.replace('<br>', ' to ')+"```%0A%0A";
	if(fb[0]!='-') {
		msg += "*FACEBOOK*%0A";
		for(i = 0; i < fb.length; i++){	
		   msg += lab_fb[i]+": "+fb[i]+"%0A";	
		}
	}
	if(g[0]!='-') {
		msg += "%0A*GOOGLE*%0A";
		for(i = 0; i < g.length; i++){	
		   msg +=  lab_g[i]+": "+g[i]+"%0A";	
		}
	}
	if(li[0]!='-') {
		msg += "%0A*LINKEDIN*%0A";
		for(i = 0; i < li.length; i++){	
		   msg += lab_in[i]+": "+li[i]+"%0A";	
		}
	}
	//alert('https://api.whatsapp.com/send?text='+msg);
	window.open('https://api.whatsapp.com/send?text='+msg+'','_blank');

}
var ckbox = $('#checkbox');
var chk = $('.chk');
$(document).ready(function () {
	$('#checkbox').on('click',function () {    
        if (ckbox.is(':checked')) {
            //alert('You have Checked it');
			$('.cmp_dt').show();
        } else {
			$('.cmp_dt').hide();
            //alert('You Un-Checked it');
        }
    });
	$('#datatable1 input[type=checkbox]').change(function () {
    	//alert('.cmp_'+clsId[0]+''+clsId[1]);
		var clsId = $(this).val();
		var clsId = clsId.split(',');
		var colsNo = $('.head_'+clsId[0]).attr("colspan");
		
		//alert(colsNo);
		//$('.dt').hide();
		if ($(this).is(':checked')) {
            //alert('You have Checked it'+$(this).val());
			colsNo = parseInt(colsNo)+3;
			$('.cmp_'+clsId[0]+''+clsId[1]).show();
			$('.head_'+clsId[0]).attr('colspan',colsNo);
			$('.dt_'+clsId[0]+''+clsId[1]).show();
			//$('.cmp_'+clsId[0]+''+clsId[1]).css("background-color", "red").fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
			$('.cmp_'+clsId[0]+''+clsId[1]).css("background-color", "#f5f5c36e").animate({ backgroundColor: "#FFFFFF"}, 1500);
			//alert('.cmp_'+clsId[0]+''+clsId[1]+','+'.head_'+clsId[0]);
        } else {
			colsNo = parseInt(colsNo)-3;
			$('.cmp_'+clsId[0]+''+clsId[1]).hide(colsNo);
			$('.head_'+clsId[0]).attr('colspan',colsNo);
			$('.dt_'+clsId[0]+''+clsId[1]).hide();
			//$('.head_'+clsId).attr('colspan',colsNo-3);
            //alert('You Un-Checked it'+$(this).val());
        }
	});
	$(".table-multi-columns").freezeTable({
    	'columnNum' : 2,
  	});
});
</script>
<!-- Switchery -->
<script src="vendors/switchery/dist/switchery.min.js"></script>