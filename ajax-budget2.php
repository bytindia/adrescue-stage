<?php
include 'db.php';

//echo $_POST['ty'];
//echo $_POST['id'];

setlocale(LC_MONETARY, 'en_IN');
if($_POST['ty']==1) {
			$tot_bal = $tot = 0;
			$sqlRev=mysqli_query($conn, "SELECT * FROM budget_reminder where tbl_id={$_POST['id']}");
			//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");
			echo '<table  class="table table-hover table-striped table-bordered">';
			echo '<thead><th>Label</td><th>Amount </th></thead>';
			while($sqlROW=mysqli_fetch_array($sqlRev))
			{ 
					$fb_spent = $g_spent = $in_spent = $ta_spent = 0;
					$txt = '';
											
					if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); echo '<tr><td>Facebook</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$fb_spent).'</td></tr>'; }
					if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); echo '<tr><td>Google</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$g_spent).'</td></tr>'; }
					if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); echo '<tr><td>LinkedIn</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$in_spent).'</td></tr>'; }
					if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); echo '<tr><td>Taboola</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$ta_spent).'</td></tr>'; }
					
					$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
					
					$tot_bal = $sqlROW["total_budget"] - $tot_spend + $sqlROW["tot_penalty"];
					
					echo '<tr class="blue_txt"><td><b>Total Spent (A)</b></td><td><b><i class="fa fa-inr"></i> '.money_format('%!i',$tot_spend).'</b></td></tr>';
					
					if($sqlROW["total_budget"]!='') { echo '<tr><td>Total Budget (B)</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$sqlROW["total_budget"]).' </td></tr>'; }
					if($sqlROW["tot_penalty"]!='' && $sqlROW["tot_penalty"]!=0) { echo '<tr><td>Payment Penalty (C)</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$sqlROW["tot_penalty"]).'</td></tr>'; $txt.=' + C';}
					echo '<tfoot><td><b>Total Balance (B - A'.$txt.')</b></td><td><b><i class="fa fa-inr"></i> '.money_format('%!i',$tot_bal).'</b></td></tfoot>';
					
			}						
			echo '</table>';		
}

if($_POST['ty']==2) {
			$sqlRev=mysqli_query($conn, "SELECT amount,date FROM cashflow_payments where cashflow_id={$_POST['id']} limit 0,1");
			$sqlROW = mysqli_fetch_assoc($sqlRev);
			$amount = $sqlROW["amount"];
			$date = $sqlROW["date"];
			if($amount!='') {
				$amount_paid = unserialize($amount);
				$date_paid = unserialize($date);
				$tot=0;
				echo '<table  class="table table-hover table-striped table-bordered">';
				echo '<thead><th>Paid On</th><th>Amount</th></thead>';
				foreach($amount_paid as $key => $item) {
					
					//echo '<table  class="table table-hover table-striped table-bordered">';
					
						echo '<tr><td>'.$date_paid[$key].'</td><td><i class="fa fa-inr"></i> '.money_format('%!i',$item).'</td></tr>';
						
							/*$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
							$tot_bal = $sqlROW["tot_paid"] - $tot_spend;
							if($sqlROW["fb_spent"]!='') { echo '<tr><td>Facebook Spent</td><td>'.money_format('%!i',$sqlROW["fb_spent"]).'</td></tr>'; }
							if($sqlROW["g_spent"]!='') { echo '<tr><td>Google Spent</td><td>'.money_format('%!i',$sqlROW["g_spent"]).'</td></tr>'; } 
							if($sqlROW["in_spent"]!='') { echo '<tr><td>LinkedIn Spent</td><td>'.money_format('%!i',$sqlROW["in_spent"]).'</td></tr>'; }
							if($sqlROW["ta_spent"]!='') { echo '<tr><td>Taboola Spent</td><td>'.money_format('%!i',$sqlROW["ta_spent"]).'</td></tr>'; }*/
							$tot = $item + $tot;
					
				}
				echo '<tfoot><td><b>Total</b></td><td><b><i class="fa fa-inr"></i> '.money_format('%!i',$tot).'</b></td></tfoot>';
				echo '</table>';
			}
			/*echo '<table  class="table table-hover table-striped table-bordered">';
			while($sqlROW=mysqli_fetch_array($sqlRev))
			{ 
					$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
					$tot_bal = $sqlROW["tot_paid"] - $tot_spend;
					if($sqlROW["fb_spent"]!='') { echo '<tr><td>Facebook Spent</td><td>'.money_format('%!i',$sqlROW["fb_spent"]).'</td></tr>'; }
					if($sqlROW["g_spent"]!='') { echo '<tr><td>Google Spent</td><td>'.money_format('%!i',$sqlROW["g_spent"]).'</td></tr>'; } 
					if($sqlROW["in_spent"]!='') { echo '<tr><td>LinkedIn Spent</td><td>'.money_format('%!i',$sqlROW["in_spent"]).'</td></tr>'; }
					if($sqlROW["ta_spent"]!='') { echo '<tr><td>Taboola Spent</td><td>'.money_format('%!i',$sqlROW["ta_spent"]).'</td></tr>'; }
					
			}						
			echo '</table>';		*/
}


?>