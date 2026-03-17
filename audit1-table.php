<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Partha Sarathy - Facebook - Audit';
$pgID = 3;
$err =''; 


include 'pagination.php';

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
?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
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
                    
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php
								$fields = array(
									0 => 'What is account age?',
									1 => 'Is account active?',
									2 => 'How many campaigns are placed?',
									3 => 'Are Pixel codes active?',
									4 => 'Are they using custom conversions?',
									5 => 'Remarketing list created?',
									6 => 'Top 25% website visitors list created?',
									7 => 'LA of remarketing & top 25% website visitors created?',
									8 => 'Is there using Rules?',
									9 => 'Using custom audience?',
									10 => 'Using Lookalike audience?',
									11 => 'Is Narrow audience using?',
									12 => 'Age group?',
									13 => 'Which location type is selected?',
									14 => 'daily budget or lifetime budget?',
									15 => 'If lifetime budget, is there using day parting?',
									16 => 'which bidding type using?',
									17 => 'Which types of ads are using?',
									18 => 'If there using dynamic creative ads?',
									19 => 'Which interests are using?',
									20 => 'Is there connected Instagram account?',
									21 => 'Questions in lead ads?',
									22 => 'Which Work Employer?',
									23 => 'Which Work Position?',
									24 => 'When was image changed?',
									25 => 'Auto placement or Manual placement?',
									26 => 'Objective wise campaigns result & cost per result?',
									27 => 'Landing Page URLs?',
									28 => 'Is Audience expansion on?'
									
								);
								
								$ans = array(
									0 =>'',
									1 =>'',
									2 =>'',
									3 =>'',
									4 =>'',
									5 =>'',
									6 =>'',
									7 =>'',
									8 =>'',
									9 =>'',
									10 =>''
								);
								$sqlRev = mysqli_query($conn, "SELECT age,active,camp_tot,pix_act,cust_conv,remarket,top_web_25,top_la_25,rules,cust_aud,la_aud,nar_aud,age_group,loc_type,daily_life,day_part,bid_type,adset_type,dynamic,interests, insta_acc, lead_qus, work_emp, work_pos, img_updated, placement, obj_cost_result, lp_url, exp_on FROM audit_data where acc_id='107704242648697'");
								
								while($sqlROW=mysqli_fetch_array($sqlRev))
								{ 
									$ans  = $sqlROW;
								}
								//d($d); 
						?>
                                <form method="post" action="">
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        <th>SNo</th>
                                        <th width="50%">Audit Name</th>
                                    	<th>Values</th>                                                        	
                                    </thead>
                                    <tbody>
                                    	<?php $i=1;
										foreach ($fields as $k => $v)
										{ 
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
										?>
                                        <tr>
                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $v; ?></td>
                                        	<td><?php echo $ans[$k]; ?></td>                                                                           	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                <br><br>
								<div id="pagDiv"><?php //echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

						
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>