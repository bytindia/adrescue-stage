<?php include 'header.php';  


if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Ad Rules - Setup';
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
	//mysqli_query($conn, "UPDATE ad_rule SET del_status='y' where id=".$_GET['del']."");
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'ad-rules.php';</script>";
	exit();
}

include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " ad_rule WHERE uid='".$_SESSION['uid']."' AND del_status='n'";

$ad_level = array(1=>'Campaign', 2=>'AdSet', 3=>'Ad'); 
$ad_action = array(1=>'Pause', 2=>'Budget Increase', 3=>'Budget Reduce'); 
$check_every = array(
    '15m' => '15 mins',
    '30m' => '30 mins',
    '1h' => '1 hour',
    '2h' => '2 hours',
    '3h' => '3 hours',
    '12h' => '12 hours',
    '24h' => '24 hours',
);
$metrics = array(
    'spend' => 'Spend',
    'leadgen_grouped' => 'Leads (LG)',
    'cpl' => 'CPL',
    'conversions' => 'Conversions',
    'clicks' => 'Clicks',
    'reach' => 'Reach',
    'impressions' => 'Impressions',
    'post_engagement' => 'Post Engagement',
    'link_click' => 'Link Clicks',
    'cpc' => 'CPC',
    'cpm' => 'CPM',
    'ctr' => 'CTR',
    'like' => 'Likes',
    'comment' => 'Comments',
    'video_view' => 'Video View',
    'page_engagement' => 'Page Engagement'
  );
?>
<style>
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
</style>
<style>


[data-role="dynamic-fields"] > .form-inline + .form-inline {
    margin-top: 0.5em;
}

[data-role="dynamic-fields"] > .form-inline [data-role="add"] {
    display: none;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="add"] {
    display: inline-block;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"] {
    display: none;
}

.not-first [data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"] {
    display: inline-block;
}
.selCls { width:100% !important; }

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
          <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Modal title</h4>
                    </div>
                    <div class="modal-body">
                    <!--<h4>Text in a modal</h4>
                    <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.</p>
                    <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>-->
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
          </div>
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
                        	<a href="loading.php?pg=ad-rule-add.php"  class="btn btn-success btn-sm">Add Accounts</a>                        
                      </div>    
                      </li>
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=cron-budget3.php?refresh=1"  class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to Email Cashflow report?');" ><i class="fa fa-refresh"></i> Refresh All</a>                        
                      </div>    
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);
                       $fbAccN = $gAccN = $inAccN = $taAccN = array();
										
                       $sqlRev1=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                       
                       $sqlRev2=mysqli_query($conn, "SELECT account_id,name FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
                       
                       $sqlRev3=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_in WHERE uid='".$_SESSION['uid']."' order by name asc");
                       
                       $sqlRev4=mysqli_query($conn, "SELECT account_id,name FROM adAccounts_ta WHERE uid='".$_SESSION['uid']."' order by name asc");

                       while($sqlROW1=mysqli_fetch_array($sqlRev1)) { $fbAccN[$sqlROW1["account_id"]] = $sqlROW1["name"]; }
                       while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $gAccN[$sqlROW2["account_id"]] = $sqlROW2["name"]; }
                       while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $inAccN[$sqlROW3["account_id"]] = $sqlROW3["name"]; }
                       while($sqlROW4=mysqli_fetch_array($sqlRev4)) { $taAccN[$sqlROW4["account_id"]] = $sqlROW4["name"]; }

										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by id desc LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
								?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>                                        
                                        <th>SNo</th>
                                        <th>Rule Name</th>                                    	
                                        <th>Ad Account</th>
                                        <th>Action</th>
                                        <th>Level</th>
                                        <th>Check (every)</th> 
                                        <th>Conditions</th>
                                        <th>WA Alert</th>
                                        <th width="16%">Edit</th>     	
                                    </thead>
                                    <tbody>
                                    	<?php
										setlocale(LC_MONETARY, 'en_IN');
										//$amount = money_format('%.0n', $amount);
                    $totRows = mysqli_num_rows($sqlRev);
                    $grandBudget = $grandSpent = $grandBalance = 0;
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
											//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
											//d($sqlROW);
											$act_icon = '';
                                            if($sqlROW["action"]==1) { $act_icon = ' <i class="fa fa-pause"></i> '; }
                                            if($sqlROW["action"]==2) { $act_icon = ' <i class="fa fa-arrow-up"></i> '; }
                                            if($sqlROW["action"]==3) { $act_icon = ' <i class="fa fa-arrow-down"></i> '; }
                                            $bud_val = '';
                                            if($sqlROW["action"]!=1) { $bud_val = '  - '.$sqlROW["bud_val"]. '%'; }
										?>
                                        <tr>                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["rule_name"]; ?><!-- <i class="fa fa-facebook"></i> <i class="fa fa-google"></i> <i class="fa fa-linkedin"></i> <i class="fa fa-taboola"></i>  --></td>   
                                            <td><?php echo $fbAccN[$sqlROW["acc_id"]]; ?></td>   
                                            <td><?php echo $ad_action[$sqlROW["action"]].' '.$bud_val; //.' '.$act_icon. ' '.$ad_level[$sqlROW["acc_level"]];  ?></td>   
                                            <td><?php echo $ad_level[$sqlROW["acc_level"]]; ?></td>
                                            <td><?php echo $check_every[$sqlROW["check_time"]]; ?></td>   
                                            <td>
                                              
                                              <?php
                                              $rules_metrics = unserialize($sqlROW['cond_metrics']);
                                              $rules_opr = unserialize($sqlROW['cond_opr']);
                                              $rules_val = unserialize($sqlROW['cond_val']); 

                                              if(count($rules_metrics)>0) {
                                                $f=1;
                                                $f_count = count($rules_metrics);
                                                
                                                foreach($rules_metrics as $r_key => $r_val) {
                                                    echo "&#x2022 ". $metrics[$r_val]. ' '.$rules_opr[$r_key]. ' '.$rules_val[$r_key]. '<br> '; 
                                                }
                                            }
                                            ?>
                                            <small style="float: right;">- match: <?php echo ucfirst($sqlROW["cond_match"]); ?> -</small>
                                            </td>
                                            <td><?php echo $sqlROW["wa_alert"]; ?></td>   
                                            <td> 
                                                <a href="loading.php?pg=ad-rule-add.php?id=<?php echo $sqlROW["id"]; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> </a>
                                    <a href="loading.php?pg=ad-rules.php?del=<?php echo $sqlROW["id"]; ?>" onclick="return confirm('Are you sure you want to delete this?');"  class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i></a>
                                    
                                    		</td>                              	
                                        </tr>  
                                        <?php $i++;
										} 
                    
                    ?>                                      
                                    </tbody>
                                </table>
                                
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

								  
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->
        <?php include 'footer.php'; ?>



<script>
 
function onAjax(id,ty,clName) {
					//var id = $(this).attr('data-id');
					//alert(id);
					$('.modal-body').html('loading');
					$.ajax({
						type: 'POST',
						url: 'ajax-ad-rules.php',
						data:{id: id, ty: ty},
						success: function(data) {
							//alert(data);
						  $('#myModalLabel').html(clName);
						  $('.modal-body').html(data);
						},
						error:function(err){
						  alert("error"+JSON.stringify(err));
						}
					});
					
};
</script>

