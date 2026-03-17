<?php include 'header.php'; 
date_default_timezone_set('America/New_York');
if(!isset($_SESSION['proj'])) { $_SESSION['proj'] = 'All'; }
//SELECT * FROM leads WHERE page_id='276254866158440' AND (created_time >=1603377000 AND created_time <=1603463400) order by tbl_id desc

	
if(isset($_POST['dt_submit'])){
	$start = $_POST['start'];
	$end =  $_POST['end'];
	$_SESSION['stDt'] = $start ;
	$_SESSION['enDt'] = $end ;
	$_SESSION['proj'] = $_POST['proj'];
	echo "<script>window.location = 'leads-view.php?id=".$_GET['id']."';</script>";
	exit();
}

//Auth();
$query = "SELECT client_name, pg_name FROM leads_acc WHERE pg_id='".$_GET['id']."' limit 0,1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$client_name = $row['client_name']; 

$pgHeadline = $client_name.' - Leads';
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

include '../pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;

$projQ = '';
if(isset($_SESSION['proj']) && $_SESSION['proj']!='All') { $projQ = "AND refName='".strtolower($_SESSION['proj'])."'"; }
//echo 'AND (created_time >='.(strtotime($_SESSION['stDt'])).' AND created_time <='.((strtotime($_SESSION['enDt']) + 60*60*11)).')'; 
$dtQ = 'AND (created_time >='.(strtotime($_SESSION['stDt'])-34200).' AND created_time <='.((strtotime($_SESSION['enDt']) + 60*60*11)+12600).')'; 

$statement = " leads WHERE page_id='".$_GET['id']."' $dtQ $projQ";


function contains($str, array $arr)
{
    foreach($arr as $a) {
        if (stripos($str,$a) !== false) return $a;
    }
    return '';
}


?>
<style>
.nav-sm .container.body .right_col { margin-left:0px !important; }
.nav-sm .main_container .top_nav, .nav-sm footer { margin-left:0px !important; }
.toggle { display:none; }
	</style>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			//include '../menu-left.php';
			include '../menu-top.php'; 
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
                        	<a href="leads-acc-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
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
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="leads-download.php?id=<?php echo $_GET['id']; ?>&stDt=<?php echo $_SESSION['stDt']; ?>&enDt=<?php echo $_SESSION['enDt']; ?>&proj=<?php echo $_SESSION['proj']; ?>"  class="btn btn-success btn-sm">Download Leads</a>                        
                      	</div>  
                      </li>
                      
                    </ul>
                    <form method="post" action="leads-view.php?id=<?php echo $_GET['id']; ?>">
                    <ul class="nav navbar-right panel_toolbox">
                      <li>Filter : &nbsp;
                      </li>
                     <!-- <li>
                      <select name="proj" class="form-control">
                                 	<option value="All" <?php if($_SESSION['proj']=='All') { echo 'selected'; } ?>>All</option>
                                 	<?php foreach($words_like as $k => $v) { ?>
                                 	<option value="<?php echo $v; ?>" <?php if($_SESSION['proj']==$v) { echo 'selected'; } ?>><?php  echo $v;  ?></option>
                                    <?php } ?>                                    
                                 </select>
                                 &nbsp;
                        </li>-->
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
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc"); //exit;
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
                                        <th>Campaign</th>  
                                        <th>Model</th>      
                                        <th>Created</th>                                     
                                        <!--<th width="16%">Edit</th>-->        	
                                    </thead>
                                    <tbody>
                                    	<?php
										$model = array('Dzire','Alto','S-Presso','Swift','WagonR','Breeza','Tours Dzire','Ertiga');
										$service = array('Drving School','Pre-Owned','Service');
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											$leads = unserialize($sqlROW["lead"]);
											//d($leads);
											$modN = contains($sqlROW['formN'],$model);
											$serN = contains($sqlROW['formN'],$service);
											if(trim($modN)!='' && trim($serN)!='') { $modN = $modN.' / '.$serN; } else { $modN = $modN.''.$serN; }
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $leads['full_name']; ?></td>                                            
                                            <td><?php echo $leads['email']; ?></td> 
                                            <td><?php echo $leads['phone_number']; ?></td>
                                            <td><?php echo $sqlROW['formN']; ?></td> 
                                            <td><?php echo $sqlROW['campN']; ?></td>
                                            <td><?php echo $modN; ?></td> 
                                            <td><?php echo date('d-m-Y h:i a',$sqlROW['created_time']+34199); ?></td>    
                                            <!--<td>
                                            <a href="leads-acc-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Edit </a>
                                    <a href="leads-acc.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> Delete </a>
                                    		</td>       -->                         	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php // echo pagination($statement,$per_page,$page,$url='?id='.$_GET['id'].'&',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>