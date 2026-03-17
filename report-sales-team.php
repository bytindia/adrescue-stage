<?php

include 'db.php'; 

$cqAr = $salIds = $cqRep = array(); 
$q = $conn->query("SELECT id, txt FROM callqty_text where $cIdQ order by id desc");
while($r = $q->fetch_assoc()) { $cqAr[] = $r;  }

$sql = "SELECT * FROM sales_ninja_users where $cIdQ order by id desc";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) 
{ 
	$salIds[] = $row['id'];
}
if(count($salIds)==0) { $salIds=array(0); }
//echo "SELECT count(*) as num, sal_ex, cq_id FROM `sales_ninja` where sal_ex in(".implode(',',$salIds).") AND cq_id!='' group by cq_id,sal_ex";
$qsal = $conn->query("SELECT count(*) as num, sal_ex, cq_id FROM `sales_ninja` $extQry2 AND sal_ex in(".implode(',',$salIds).") AND cq_id!='' group by cq_id,sal_ex");
while($rsal = $qsal->fetch_assoc()) { $cqRep[$rsal['sal_ex']][$rsal['cq_id']] = $rsal['num'];  }

?>
 <table id="datatable" class="table table-striped table-striped projects">
                      <thead>
                              <tr>                              	
                                <th>SNo</th>
                                <th>Name</th>
                                <th>Leads</th>
                                <?php
								foreach($cqAr as $key => $value) {	echo "<th>{$cqAr[$key]['txt']}</th>"; }
								?>                             
                                <th>Edit</th>
                                <th>Stats</th>
                                <th>Assign Leads</th>
                                <th>Whatsapp</th>
                              </tr>
                      </thead>


                      <tbody>
                      <?php  					 
					    $q0 = $conn->query("SELECT sal_ex, COUNT(*) as tot FROM sales_ninja $extQry2 GROUP BY sal_ex");
					  	while($r0 = $q0->fetch_assoc()) { $d0[$r0['sal_ex']] = $r0['tot']; }
						  
					  	$q1 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_calls where $cIdQ AND  $extQry callDuration=0 AND callType=1 GROUP BY uId");
					  	while($r1 = $q1->fetch_assoc()) { $d1[$r1['uId']] = $r1['tot']; }
						
						$q2 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_callqty where $cIdQ AND  $extQry leadQty=0 GROUP BY uId");
					  	while($r2 = $q2->fetch_assoc()) { $d2[$r2['uId']] = $r2['tot']; }
						
						$q3 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_callqty where $cIdQ AND  $extQry leadQty=1 GROUP BY uId");
					  	while($r3 = $q3->fetch_assoc()) { $d3[$r3['uId']] = $r3['tot']; }
						
						$q4 = $conn->query("SELECT uId, COUNT(*) as tot FROM sn_callqty where $cIdQ AND $extQry leadQty=2 GROUP BY uId");
					  	while($r4 = $q4->fetch_assoc()) { $d4[$r4['uId']] = $r4['tot']; }	
						
						$q5 = $conn->query("SELECT added, COUNT(*) as tot FROM sales_ninja where $cIdQ AND $extQry id!=0 GROUP BY added");
					  	while($r5 = $q5->fetch_assoc()) { $d5[$r5['added']] = $r5['tot']; }		
						
						//print_r($data1);
								$i=1; 
								$sql = "SELECT * FROM sales_ninja_users where $cIdQ order by id desc";
								$result = $conn->query($sql);
								//if ($result->num_rows == 0) { echo 'No records found!'; }
								while($row = $result->fetch_assoc()) 
								{
									$prog = rand(20,100);
                  if($row['active']==1) {
                    $table_wa = 'SalesNinja: '.$row['name'].' account has been activated';
                    $actStatus = 'Active';
                  } else {
                    $table_wa = 'SalesNinja: '.$row['name'].' account has been de-activated';
                    $actStatus = 'De-Active';
                  }
							  ?>
                        	<tr>
                                <td><?php echo $i; ?></td>
                                <td><a href="loading.php?pg=sales-user-stats.php?id=<?php echo $row["id"]; ?>" target="_blank" class="blue"><?php echo $row["name"]; ?></a></td>
                                <!--<td><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="0" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php if(isset($d0[$row["id"]])) { echo $d0[$row["id"]]; } else { echo 0; } ?></a></td>-->
                                <td><a href="loading.php?pg=leads.php?uId=<?php echo $row["id"]; ?>" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" target="_blank" class="blue"><?php if(isset($d0[$row["id"]])) { echo $d0[$row["id"]]; } else { echo 0; } ?></a></td>
                                <?php
								foreach($cqAr as $key => $value) {	
									if(isset($cqRep[$row["id"]][$cqAr[$key]['id']])) {
										//echo "<td>".$cqRep[$row["id"]][$cqAr[$key]['id']]."</td>"; 
										?>
										<td><a href="loading.php?pg=#" data-toggle="modal" data-id="<?php echo $row["id"]; ?>" data-ref="<?php echo $cqAr[$key]['id']; ?>" data-target=".bs-example-modal-lg" class="btn-block1 blue"><?php echo $cqRep[$row["id"]][$cqAr[$key]['id']]; ?></a></td>
										<?php
									} else {
										echo "<td>-</td>"; 
									}
								}
								?>
                                <td>                                	
                                    <a href="loading.php?pg=sales-user-add.php?id=<?php echo $row["id"]; ?>" class="btn btn-info btn-s"><i class="fa fa-pencil"></i> Edit </a>
                                    
                                </td>
                                <td><a href="loading.php?pg=sales-user-stats.php?id=<?php echo $row["id"]; ?>" target="_blank" class="btn btn-primary btn-s">View Stats</a></td>
                                
                                <td><?php if($row["active"]==1) { ?><a href="loading.php?pg=sales-user-assign2.php?id=<?php echo $row["id"]; ?>" class="btn btn-success btn-s">Assign Leads</a><?php } else { ?><a href="loading.php?pg=sales-user.php?id=<?php echo $row["id"]; ?>&act=1" class="btn btn-info btn-s">Activate</a>
								<?php } ?></td>
                                <td><a class="btn btn-s btn-success full-width" href="https://api.whatsapp.com/send?text=<?php echo $table_wa; ?>" target="_blank">Whatsapp</a> </td>
                            </tr>
                              <?php $i++; } ?>
                      </tbody>
                    </table>