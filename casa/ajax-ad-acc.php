<?php
include '../db.php';

			$tot_bal = $tot = 0;
			$sqlRev=mysqli_query($conn, "SELECT name as text, account_id as value, name as continent FROM adAccounts WHERE uid='2'");
			//$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow as c, cashflow_payments as cp where c.tbl_id=9 AND c.tbl_id=cp.cashflow_id");
			
			while($sqlROW=mysqli_fetch_assoc($sqlRev))
			{ 
				$sqlROW['text'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['text'])).' (' .$sqlROW['value'].')';
				$sqlROW['continent'] = mysqli_real_escape_string($conn, preg_replace('/[^A-Za-z0-9\-]/', '', $sqlROW['continent']));
                $acc_data[] = $sqlROW;
					
					
			}						
		echo json_encode($acc_data);
?>