<?php include 'header.php'; 


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Post Promotion - Automation';
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
	mysqli_query($conn, "UPDATE post_promotion SET del_status='1' where tbl_id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'post-promotion.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " post_promotion WHERE uid='".$_SESSION['uid']."' AND del_status=0";

?>
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
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=post-promotion-add.php"  class="btn btn-success btn-sm">Create New</a>                        
                      </div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                      include 'alert.php';
                      $sqlROWs_acc =  array();
										
                      $sqlRev1=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                      while($r1=mysqli_fetch_array($sqlRev1)) { $sqlROWs_acc[$r1['account_id']] = $r1['name']; }
                      $sqlRev2=mysqli_query($conn, "SELECT * FROM pages WHERE uid='".$_SESSION['uid']."' order by pg_name asc");
                      while($r2=mysqli_fetch_array($sqlRev2)) { $sqlROWs_pg[$r2['pg_id']] = $r2['pg_name']; }

										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by tbl_id desc ");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Client</th>                                    	
                                        <th>Page</th>
                                        <th>Ad Account</th>
                                        <th>Daily budget</th>
                                        <th>Age</th>
                                        <!--<th>Page Id</th>   -->
                                        <th width="26%">Edit</th>        	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["client"]; ?></td>
                                        	                                     
                                            <td><?php echo $sqlROWs_pg[$sqlROW["fb_pg"]]; ?></td>
                                            <td><?php echo $sqlROWs_acc[$sqlROW["fb_acc"]]; ?></td>       
                                            <td><?php echo $sqlROW["d_budget"]; ?></td>
                                            <td><?php echo $sqlROW["age_min"].' - '.$sqlROW["age_max"];; ?></td>                        
                                            <td>
                                            <a href="loading.php?pg=post-promotion-add.php?id=<?php echo $sqlROW["tbl_id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Edit </a>
                                    <a href="loading.php?pg=post-promotion.php?del=<?php echo $sqlROW["tbl_id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> Delete </a>
                                    <a href="loading.php?pg=post-promo-view.php?pg_id=<?php echo $sqlROW["fb_pg"]; ?>" class="btn btn-success btn-sm"><i class="fa fa-pause"></i> Pause Ad </a>
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>