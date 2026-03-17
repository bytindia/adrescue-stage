<?php session_start(); $no_auth=1; include 'header.php'; 

if(isset($_POST['pass_submit']) && $_POST['pass']=='Ep!e@d$2o2!'){
	$_SESSION['auth'] = 1 ;
	echo "<script>window.location = 'ep-leads-view.php';</script>";
	exit();
}

if(!isset($_SESSION['auth']) && $_SESSION['auth']!=1){ ?>
<form action="" method="post">
<br>&nbsp;&nbsp;<input type="password" name="pass" placeholder="Enter Password"> &nbsp;
<input type="submit" name="pass_submit" value="Submit">
</form>

<?php
	
} else {
date_default_timezone_set('America/New_York');
if(!isset($_SESSION['proj'])) { $_SESSION['proj'] = 'All'; }


if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	$_SESSION['proj'] = $_POST['proj'];
	echo "<script>window.location = 'ep-leads-view.php';</script>";
	exit();
}

//Auth();
$query = "SELECT client_name, pg_name FROM leads_acc WHERE pg_id='".$_GET['id']."' limit 0,1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$client_name = $row['client_name']; 

$pgHeadline = $client_name.' Eden Park - Leads';
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

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;

$projQ = '';
if(isset($_SESSION['proj']) && $_SESSION['proj']!='All') { $projQ = "AND src='".strtolower($_SESSION['proj'])."'"; }
//echo 'AND (created_time >='.(strtotime($_SESSION['stDt'])).' AND created_time <='.((strtotime($_SESSION['enDt']) + 60*60*11)).')'; 
$dtQ = ' (created >=\''.date('Y-m-d	H:i:s', strtotime($_SESSION['stDt'])).'\' AND created <=\''.date('Y-m-d	23:59:59', strtotime($_SESSION['enDt'])).'\')'; 

$statement = " ep_leads WHERE $dtQ $projQ";
?>
<style>
.nav-md .container.body .right_col { margin-left: 0px; }

footer {
    margin-left: 0px;
}
div.dataTables_wrapper div.dataTables_info, div.dataTables_wrapper div.dataTables_paginate  { display:none; }
#pagDiv { float:right; }
</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include 'menu-left.php';
			//include 'menu-top.php'; 
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
				  
				  //echo "SELECT src, count(*) as tot, created FROM ep_leads WHERE $dtQ $projQ GROUP by DATE(created),src order by created asc"; 
				  $q3 = "SELECT src, count(*) as tot, created FROM ep_leads GROUP by DATE(created),src order by DATE(created) asc";
				  $r3 = mysqli_query($conn, $q3);
				  if(mysqli_num_rows($r3)>0) { 
				  	while($row=mysqli_fetch_assoc($r3))
					{
						$srcGroup[] = $row['src'];
					}
				  } 
				  $words_like = array_unique($srcGroup);
				  
				  $dt = '';
				  $wp_msg = '*Eden Park - Leads*%0A%0A';
				  //echo "SELECT src, count(*) as tot, created FROM ep_leads WHERE $dtQ $projQ GROUP by DATE(created) order by DATE(created) asc";
				  $q4 = "SELECT src, count(*) as tot, created FROM ep_leads WHERE $dtQ $projQ GROUP by DATE(created),src order by DATE(created) asc";
				  $r4 = mysqli_query($conn, $q4);
				  if(mysqli_num_rows($r4)>0) { 
				  	while($row=mysqli_fetch_assoc($r4))
					{
						$created = date('M d, Y', strtotime($row['created']));
						if($dt=='' || $dt!=$created) { $wp_msg .= '%0A*'.$created.'* %0A'; }
						$wp_msg .= $row['src'].' - '.$row['tot'].'%0A';
						$dt = $created;
					}
				  } 	
				  //d($words_like);
				  ?>
                  <!--<a href="loading.php?pg=https://api.whatsapp.com/send?text=<?php echo $wp_msg; ?>" target="_blank">WA</a>-->
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                       		<a href="loading.php?pg=https://api.whatsapp.com/send?text=<?php echo $wp_msg; ?>" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-whatsapp"></i> WhatsApp</a>  
                        	<a href="loading.php?pg=ep-leads-download.php?stDt=<?php echo $_SESSION['stDt']; ?>&enDt=<?php echo $_SESSION['enDt']; ?>&proj=<?php echo $_SESSION['proj']; ?>" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-download"></i> Download Leads</a>                 
                      	</div>  
                      </li>
                      
                    </ul>
                    <form method="post" action="ep-leads-view.php"> 
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
                    </form>
                    
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
										//echo "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}"; 
										//$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}");
										//echo "SELECT * FROM ".$statement." order by tbl_id desc"; 
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc LIMIT {$startpoint} , {$per_page}"); //exit;
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>               	
                                        <th>Name</th>
                                        <th>Email</th>   
                                        <th>Phone</th>
                                        <th>Form</th>
                                        <th>Source</th>   
                                        <th>Campaign</th>   
                                        <th>Created</th>                                     
                                        <!--<th width="16%">Edit</th>-->        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											$leads = unserialize($sqlROW["lead"]);
											//d($leads);
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW['name']; ?></td>                                            
                                            <td><?php echo $sqlROW['email']; ?></td> 
                                            <td><?php echo $sqlROW['phone']; ?></td>
                                            <td><?php echo $sqlROW['src']; ?></td> 
                                            <td><?php echo $sqlROW['source']; ?></td>
                                            <td><?php echo $sqlROW['camp']; ?></td>
                                            <td><?php echo date('d-m-Y h:i a', strtotime($sqlROW['created'])); ?></td>    
                                            <!--<td>
                                            <a href="loading.php?pg=leads-acc-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Edit </a>
                                    <a href="loading.php?pg=leads-acc.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> Delete </a>
                                    		</td>       -->                         	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php  echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; 
}
?>