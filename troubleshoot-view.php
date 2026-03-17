<?php include 'header.php';  
date_default_timezone_set('America/New_York');
if(!isset($_SESSION['proj'])) { $_SESSION['proj'] = 'All'; }
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	$_SESSION['proj'] = $_POST['proj'];
	echo "<script>window.location = 'leads-view.php?id=".$_GET['id']."';</script>";
	exit();
}

Auth();


$pgHeadline = 'AdSet - AdTracker';
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
	mysqli_query($conn, "UPDATE leads_acc SET delete_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'leads-acc.php';</script>";
	exit();
}

?>
<style>
  .percent1 {
    color: #2d89d7;
    font-size: 12px;
    font-style: italic;
    font-weight: bold;
    float: right;
}
.font-italic b, strong {
    font-weight: 700;
    color: #13a911;
    font-size: 13px;
}
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
                 <!--
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=leads-acc-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
                      	</div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  -->
                  <?php
				  $words_like = array();
				  $q3 = "SELECT words_like FROM leads_acc WHERE uid='".$_SESSION['uid']."' && pg_id='".$_GET['id']."'";
				  $r3 = mysqli_query($conn, $q3);
				  if(mysqli_num_rows($r3)>0) { $row3 = mysqli_fetch_assoc($r3); $words_like = unserialize($row3['words_like']); } 
				  $words_like = array_filter($words_like)
				 // d($words_like);
				  ?>
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?> 
                    	
                     </h2>  
                    <ul class="nav navbar-right panel_toolbox">
                    <li> &nbsp; <div class="btn-group  btn-group-sm">
                              <a href="loading.php?pg=cron-troubleshoot.php"  class="btn btn-danger btn-sm">Fetch Live Report</a>                            
                        </div>
                        </li>
                        <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=troubleshoot-list.php"  class="btn btn-success btn-sm">View Accounts</a>                            
                      	</div> 
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=troubleshoot-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                            
                      	</div> 
                         
                      </li>
                      
                    </ul>
                    <!--<form method="post" action="leads-view.php?id=<?php echo $_GET['id']; ?>">
                    <ul class="nav navbar-right panel_toolbox">
                      <li>Filter : &nbsp;
                      </li>
                      <li>
                      <select name="proj" class="form-control">
                                 	<option value="All" <?php if($_SESSION['proj']=='All') { echo 'selected'; } ?>>All</option>
                                 	<?php foreach($words_like as $k => $v) { ?>
                                 	<option value="<?php echo $v; ?>" <?php if($_SESSION['proj']==$v) { echo 'selected'; } ?>><?php  echo $v;  ?></option>
                                    <?php } ?>                                    
                                 </select>
                                 &nbsp;
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
                    </form>-->
                    
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  <span class="font-italic percent1">
                  LG - <b>Last 3d</b>,
                  WC</b> - <b>Last 5d.</b></span>
                  <?php
				  		$cpl_lg = $cpl_lg2 = $cpl_con = $cpl_con2 = '';
				  		$sqlRev=mysqli_query($conn, "SELECT acc_id,life_bud_lg, life_leads, life_bud_con, life_conv,target_ctr  FROM troubleshoot WHERE uid='".$_SESSION['uid']."' AND delete_status=0");
						while($sqlROW=mysqli_fetch_array($sqlRev))
						{
							//if($sqlROW['acc_id']!='') { $fbIds[] = $sqlROW['acc_id']; }
							$target_ctr[$sqlROW['acc_id']] = $sqlROW['target_ctr'];
							if($sqlROW['life_bud_lg']!='' && $sqlROW['life_leads']>0) {  
								  if(is_infinite(@($sqlROW['life_bud_lg']/$sqlROW['life_leads']))===false) {
								  $cpl_lg .= "(acc_id='".$sqlROW['acc_id']."' AND cpl>".round(($sqlROW['life_bud_lg']/$sqlROW['life_leads'])*1.25)." ) OR ";
								  $cpl_lg2 .= "(acc_id='".$sqlROW['acc_id']."' AND cpl<".round(($sqlROW['life_bud_lg']/$sqlROW['life_leads'])*0.75)." ) OR ";
								  $cpl_val_lg[$sqlROW['acc_id']] = round($sqlROW['life_bud_lg']/$sqlROW['life_leads']);
                  $target_ctr[$sqlROW['acc_id']] = $sqlROW['target_ctr'];
								  }
							 }
							
							if($sqlROW['life_bud_con']!='' && $sqlROW['life_conv']>0) { 
								 if(is_infinite(@($sqlROW['life_bud_con']/$sqlROW['life_conv']))===false) {
								 $cpl_con .= "(acc_id='".$sqlROW['acc_id']."' AND cpl>".round(($sqlROW['life_bud_con']/$sqlROW['life_conv'])*1.25)." ) OR "; 
								 $cpl_con2 .= "(acc_id='".$sqlROW['acc_id']."' AND cpl<".round(($sqlROW['life_bud_con']/$sqlROW['life_conv'])*0.75)." ) OR "; 
								 $cpl_val_con[$sqlROW['acc_id']] = round($sqlROW['life_bud_con']/$sqlROW['life_conv']);
								 }
							 }
						}
						
				  
                  		$today =  date('Y-m-d 23:59:59');
						$last_3 =  date('Y-m-d 00:00:00', strtotime("-3 days")); 
						$last_5 =  date('Y-m-d 00:00:00', strtotime("-5 days")); 
										
						$lg_q = "objective IN ('LEAD_GENERATION','OUTCOME_LEADS') AND created_time < ".strtotime($last_3)." AND stDt >= ".strtotime($last_3)." AND enDt <= ".strtotime($today)."";
						$con_q = "objective='CONVERSIONS' AND created_time < ".strtotime($last_5)." AND stDt >= ".strtotime($last_5)." AND enDt <= ".strtotime($today)."";
						$con_q = "objective='CONVERSIONS'";

            //echo "SELECT  *, sum(spend) as s , sum(leads_lg) as l, sum(spend) / sum(leads_lg) as cpl  FROM troubleshoot_reports where $lg_q group by adset_id HAVING l>0 AND ".substr($cpl_lg, 0, -3)."";
				  ?>
                  		<div class="" role="tabpanel" data-example-id="togglable-tabs">
                      <ul id="myTab" class="nav nav-tabs bar_tabs percent1" role="tablist">
                        <li role="presentation" class="active"><a href="loading.php?pg=#tab_content1" id="tab1" role="tab" data-toggle="tab" aria-expanded="true" >Lead = 0 (LG)</a>
                        </li>
                        <li role="presentation" class=""><a href="loading.php?pg=#tab_content2" role="tab" id="tab2" data-toggle="tab" aria-expanded="false">CPL > 25% (LG)</a>
                        </li>
                        <li role="presentation" class=""><a href="loading.php?pg=#tab_content3" role="tab" id="tab3" data-toggle="tab" aria-expanded="false">CPL < 25% (LG)</a>
                        </li>
                        <li role="presentation" class=""><a href="loading.php?pg=#tab_content4" id="tab4" role="tab" data-toggle="tab" aria-expanded="true">Lead = 0 (WC)</a>
                        </li>
                        <li role="presentation" class=""><a href="loading.php?pg=#tab_content5" role="tab" id="tab5" data-toggle="tab" aria-expanded="false">CPL > 25% (WC)</a>
                        </li>
                        <li role="presentation" class=""><a href="loading.php?pg=#tab_content6" role="tab" id="tab6" data-toggle="tab" aria-expanded="false">CPL < 25% (WC)</a>
                        </li>
                      </ul>
                      <div id="myTabContent" class="tab-content">
                        <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="tab1">
                          <p>
                          		<?php 
										//echo "SELECT  *, sum(leads_lg) as tot FROM troubleshoot_reports where $lg_q group by adset_id HAVING tot = 0"; // exit;
										$sqlRev=mysqli_query($conn, "SELECT  *, sum(leads_lg) as tot FROM troubleshoot_reports where $lg_q group by adset_id HAVING tot = 0");
										$i = 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Account</th>                                    	
                                        <th>Campaign</th>
                                        <th>Adset</th>
                                        <th>View</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["camp_name"]; ?></td>
                                            <td><?php echo $sqlROW["adset_name"]; ?></td>                                           
                                            <td>
                                            <a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act=<?php echo str_replace('act_','',$sqlROW["acc_id"]); ?>&selected_campaign_ids=<?php echo $sqlROW["camp_id"]; ?>&selected_adset_ids=<?php echo $sqlROW["adset_id"]; ?>"  target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View Adset</a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                          
                          </p>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="tab2">
                          <p>
                          		<?php 
										//echo "SELECT  *, sum(spend) as s , sum(leads_lg) as l, sum(spend) / sum(leads_lg) as cpl  FROM troubleshoot_reports where $lg_q group by adset_id HAVING l>0";// exit;
										$sqlRev=mysqli_query($conn, "SELECT  *, sum(spend) as s , sum(leads_lg) as l, sum(spend) / sum(leads_lg) as cpl, sum(ctr) as ctr   FROM troubleshoot_reports where $lg_q group by adset_id HAVING l>0 AND ".substr($cpl_lg, 0, -3)."");
										$i = 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Account</th>                                    	
                                        <th>Campaign</th>
                                        <th>Adset</th>                                        
                                        <th>CPL</th>
                                        <th>CPL (LT)</th>
                                        <th width="70">Diff. %</th>
                                        <th>CTR</th>
                                        <th>Target CTR</th>
                                        <th>View</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["camp_name"]; ?></td>
                                            <td><?php echo $sqlROW["adset_name"]; ?></td>   
                                            <td><?php echo round($sqlROW["cpl"]); ?></td> 
                                            <td><?php echo $cpl_val_lg[$sqlROW["acc_id"]] ; ?></td>    
                                            <td><?php echo round( (round($sqlROW["cpl"])/$cpl_val_lg[$sqlROW["acc_id"]]) * 100) ?>% <i class="fa fa-arrow-up" style="color:#ff5f5f"></i></td>                                              
                                           
                                            <td><?php echo round($sqlROW["ctr"],2); ?></td>  
                                            <td><?php echo $target_ctr[$sqlROW["acc_id"]] ; ?></td>   
                                            <td>
                                            <a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act=<?php echo str_replace('act_','',$sqlROW["acc_id"]); ?>&selected_campaign_ids=<?php echo $sqlROW["camp_id"]; ?>&selected_adset_ids=<?php echo $sqlROW["adset_id"]; ?>"  target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View Adset</a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                          </p>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="tab3">
                          <p>
                          		<?php 
										//echo "SELECT  *, sum(spend) as s , sum(leads_lg) as l, sum(spend) / sum(leads_lg) as cpl, sum(ctr) as ctr   FROM troubleshoot_reports where $lg_q group by adset_id HAVING l>0 AND ".substr($cpl_lg2, 0, -3).""; 
										$sqlRev=mysqli_query($conn, "SELECT  *, sum(spend) as s , sum(leads_lg) as l, sum(spend) / sum(leads_lg) as cpl, sum(ctr) as ctr   FROM troubleshoot_reports where $lg_q group by adset_id HAVING l>0 AND ".substr($cpl_lg2, 0, -3)."");
										$i = 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Account</th>                                    	
                                        <th>Campaign</th>
                                        <th>Adset</th>
                                        <th>CPL</th>
                                        <th>CPL (LT)</th>
                                        <th width="70">Diff. %</th>
                                        <th>CTR</th>
                                        <th>Target CTR</th>
                                        <th>View</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["camp_name"]; ?></td>
                                            <td><?php echo $sqlROW["adset_name"]; ?></td>    
                                            <td><?php echo round($sqlROW["cpl"]); ?></td> 
                                            <td><?php echo $cpl_val_lg[$sqlROW["acc_id"]] ; ?></td>   
                                            <td><?php echo round( (round($sqlROW["cpl"])/$cpl_val_lg[$sqlROW["acc_id"]]) * 100) ?>% <i class="fa fa-arrow-down" style="color:#06c54a"></i></td>                                       
                                            <td>
                                            <td><?php echo $sqlROW["ctr"]; ?></td>  
                                            <td><?php echo $target_ctr[$sqlROW["acc_id"]] ; ?></td>   
                                            <a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act=<?php echo str_replace('act_','',$sqlROW["acc_id"]); ?>&selected_campaign_ids=<?php echo $sqlROW["camp_id"]; ?>&selected_adset_ids=<?php echo $sqlROW["adset_id"]; ?>"  target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View Adset</a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                          </p>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="tab_content4" aria-labelledby="tab4">
                          <p>
                          		<?php 
										//echo "SELECT  *, sum(leads_con) as tot FROM troubleshoot_reports where $con_q group by adset_id HAVING tot = 0"; //exit;
										$sqlRev=mysqli_query($conn, "SELECT  *, sum(leads_con) as tot FROM troubleshoot_reports where $con_q group by adset_id HAVING tot = 0");
										$i = 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Account</th>                                    	
                                        <th>Campaign</th>
                                        <th>Adset</th>
                                        <th>View</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["camp_name"]; ?></td>
                                            <td><?php echo $sqlROW["adset_name"]; ?></td>                                           
                                            <td>
                                             <a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act=<?php echo str_replace('act_','',$sqlROW["acc_id"]); ?>&selected_campaign_ids=<?php echo $sqlROW["camp_id"]; ?>&href=<?php echo $sqlROW["adset_id"]; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View Adset</a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                          </p>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="tab_content5" aria-labelledby="tab5">
                          <p>
                          		<?php 
										//echo "SELECT  *, sum(spend) as s , sum(leads_con) as l, sum(spend) / sum(leads_con) as cpl  FROM troubleshoot_reports where $con_q group by adset_id HAVING l>0 AND ".substr($cpl_con, 0, -3)."";
										$sqlRev=mysqli_query($conn, "SELECT  *, sum(spend) as s , sum(leads_con) as l, sum(spend) / sum(leads_con) as cpl, sum(ctr) as ctr   FROM troubleshoot_reports where $con_q group by adset_id HAVING l>0 AND ".substr($cpl_con, 0, -3)."");
										$i = 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Account</th>                                    	
                                        <th>Campaign</th>
                                        <th>Adset</th>
                                        <th>CPL</th>
                                        <th>CPL (LT)</th>
                                        <th width="70">Diff. %</th>
                                        <th>CTR</th>
                                        <th>Target CTR</th>
                                        <th>View</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["camp_name"]; ?></td>
                                            <td><?php echo $sqlROW["adset_name"]; ?></td>  
                                            <td><?php echo round($sqlROW["cpl"]); ?></td> 
                                            <td><?php echo $cpl_val_con[$sqlROW["acc_id"]] ; ?></td>      
                                            <td><?php echo round( (round($sqlROW["cpl"])/$cpl_val_con[$sqlROW["acc_id"]]) * 100) ?>% <i class="fa fa-arrow-up" style="color:#ff5f5f"></i></td>                                      
                                            <td>
                                            <td><?php echo $sqlROW["ctr"]; ?></td>  
                                            <td><?php echo $target_ctr[$sqlROW["acc_id"]] ; ?></td>   
                                            <a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act=<?php echo str_replace('act_','',$sqlROW["acc_id"]); ?>&selected_campaign_ids=<?php echo $sqlROW["camp_id"]; ?>&selected_adset_ids=<?php echo $sqlROW["adset_id"]; ?>"  target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View Adset</a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                          </p>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="tab_content6" aria-labelledby="tab6">
                          <p>
                          		<?php 
										//echo "SELECT  *, sum(spend) as s , sum(leads_con) as l, sum(spend) / sum(leads_con) as cpl  FROM troubleshoot_reports where $con_q group by adset_id HAVING l>0 AND ".substr($cpl_con2, 0, -3).""; 
										$sqlRev=mysqli_query($conn, "SELECT  *, sum(spend) as s , sum(leads_con) as l, sum(spend) / sum(leads_con) as cpl, sum(ctr) as ctr   FROM troubleshoot_reports where $con_q group by adset_id HAVING l>0 AND ".substr($cpl_con2, 0, -3)."");
										$i = 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Account</th>                                    	
                                        <th>Campaign</th>
                                        <th>Adset</th>
                                        <th>CPL</th>
                                        <th>CPL (LT)</th>
                                        <th width="70">Diff. %</th>
                                        <th>CTR</th>
                                        <th>Target CTR</th>
                                        <th>View</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["acc_name"]; ?></td>
                                            <td><?php echo $sqlROW["camp_name"]; ?></td>
                                            <td><?php echo $sqlROW["adset_name"]; ?></td>   
                                            <td><?php echo round($sqlROW["cpl"]); ?></td> 
                                            <td><?php echo $cpl_val_con[$sqlROW["acc_id"]] ; ?></td>   
                                            <td><?php echo round( (round($sqlROW["cpl"])/$cpl_val_con[$sqlROW["acc_id"]]) * 100) ?>% <i class="fa fa-arrow-down" style="color:#06c54a"></i></td>                                        
                                            <td>
                                            <td><?php echo $sqlROW["ctr"]; ?></td>  
                                            <td><?php echo $target_ctr[$sqlROW["acc_id"]] ; ?></td>   
                                            <a href="loading.php?pg=https://www.facebook.com/adsmanager/manage/ads?act=<?php echo str_replace('act_','',$sqlROW["acc_id"]); ?>&selected_campaign_ids=<?php echo $sqlROW["camp_id"]; ?>&selected_adset_ids=<?php echo $sqlROW["adset_id"]; ?>"  target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> View Adset</a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                          </p>
                        </div>
                      </div>
                    </div>

                  </div>
								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>