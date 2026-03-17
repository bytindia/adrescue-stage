<?php
//print_r($_POST);
include 'db.php';

$sqlRev=mysqli_query($conn, "SELECT * FROM deliverable_labels WHERE status=0 ");
while($sqlROW=mysqli_fetch_array($sqlRev)) { $labels[$sqlROW['tbl_id']] = $sqlROW['name']; }

$sqlRev3=mysqli_query($conn, "SELECT * FROM deliverable_designer WHERE  status=0 order by name asc");
while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $designers[$sqlROW3['tbl_id']] = $sqlROW3['name']; }

$sqlRev2=mysqli_query($conn, "SELECT * FROM deliverable_manager WHERE  status=0 order by name asc");
while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $managers[$sqlROW2['tbl_id']] = $sqlROW2['name']; }

$weeks_filter = array('1'=>'1st week','2'=>'2st week','3'=>'3rd week','4'=>'4th week','5'=>'5th week');
$month_filter = array('1'=>'Jan','2'=>'Feb','3'=>'Mar','4'=>'Apr','5'=>'May','6'=>'Jun','7'=>'Jul','8'=>'Aug','9'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec');
$year_filter = array(2023=>2023, 2024=>2024);

//echo "SELECT * FROM deliverable_reports WHERE client_id='".$_POST['id']."' AND week='".$_POST['wk']."' AND month='".$_POST['mon']."' AND year='".$_POST['yr']."'"; 
$sql_report=mysqli_query($conn, "SELECT * FROM deliverable_reports WHERE client_id='".$_POST['id']."' AND week='".$_POST['wk']."' AND month='".$_POST['mon']."' AND year='".$_POST['yr']."'");
while($Rdata=mysqli_fetch_array($sql_report)) {
	$editData = $Rdata;
}
?>

<form method="post" action="">
<div class="content">
     <div class="container-fluid">
       <div class="row">		
       <!--<div class="change-message">You have unsaved changes.</div>-->
                 <div class="col-md-11">   
                 <label>Reports on</label>  <div class="clearfix"></div>
                 <input type="hidden" name="client_id" class="form-control" value="<?php echo $_POST['id']; ?>"/>
                 <div class="col-md-3">            
                                    <select id="manager" name="manager" class="form-control" required>
                                        <option value="">Manager</option>
                                        <?php foreach($managers as $k => $v){  ?>
                                        <option value="<?php echo $k; ?>" <?php if(isset($editData['manager_id']) && $k==$editData['manager_id']) { ?> selected <?php } ?>><?php echo $v; ?></option>
                                        <?php } ?>
                                    </select>
                   
                 </div>

                 <div class="col-md-3">  
                        
                                    <select name="week" class="form-control" required>
                                        <option value="">Week</option>
                                        <?php foreach($weeks_filter as $k => $v){  ?>
                                        <option value="<?php echo $k; ?>" <?php if(isset($editData['week']) && $k==$editData['week']) { ?> selected <?php } ?>><?php echo $v; ?></option>
                                        <?php } ?>
                                    </select>
                 </div>
                 <div class="col-md-3">        
                                    <select name="month" class="form-control" required>
                                        <option value="">Month</option>
                                        <?php foreach($month_filter as $k => $v){  ?>
                                        <option value="<?php echo $k; ?>" <?php if(isset($editData['month']) && $k==$editData['month']) { ?> selected <?php } ?>><?php echo $v; ?></option>
                                        <?php } ?>
                                    </select>
                  </div>
                 <div class="col-md-3">            
                                    <select name="year" class="form-control" required>
                                        <option value="">Year</option>
                                        <?php foreach($year_filter as $k => $v){  ?>
                                        <option value="<?php echo $k; ?>" <?php if(isset($editData['year']) && $k==$editData['year']) { ?> selected <?php } ?>><?php echo $v; ?></option>
                                        <?php } ?>
                                    </select>
                   
                 </div>
                    
                    <div class="clearfix"></div>
                    <br>
                    <table  class="table table-hover table-striped table-bordered">
                        <thead>
                            <th>Label</th>
                            <th>Commited</th>
                            <th>Delivered</th>
                        </thead>
                        <?php 
                            if(isset($editData['committed'])) { $editComit = unserialize($editData['committed']); }
                            if(isset($editData['delivered'])) { $editDeliv = unserialize($editData['delivered']); }
                            foreach($labels as $k => $v) { ?>
                        <tr>
                            <td><?php echo $v; ?></td>
                                <td><input type="number" name="comit[<?php echo $k; ?>]" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editComit[$k])) { ?> value="<?php echo $editComit[$k]; ?>" <?php } ?> /></td>
                                <td><input type="number" name="deliver[<?php echo $k; ?>]" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editDeliv[$k])) { ?> value="<?php echo $editDeliv[$k]; ?>" <?php } ?> /></td>
                        </tr>
                        <?php } ?>
                    </table>
                    
                    <?php if(isset($editData['designer_id'])) { $editDesign = unserialize($editData['designer_id']); }
                    
                        foreach($designers as $d_k => $d_v){  ?>
                        <div class="col-md-2">    
                        <label><?php echo $d_v; ?></label>
                        <input type="number" name="designer[<?php echo $d_k; ?>]" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editDesign[$d_k])) { ?> value="<?php echo $editDesign[$d_k]; ?>" <?php } ?>/>
                        </div>
                    <?php }  ?>
                    <div class="col-md-10">    
                        <label>Resons</label>
                        <input type="text" name="reason" class="form-control" <?php if(isset($editData['reasons'])) { ?> value="<?php echo $editData['reasons']; ?>" <?php } ?>/>
                    </div>
                    <div class="col-md-10">    
                        <label>Scope</label>
                        <input type="text" name="scope" class="form-control" <?php if(isset($editData['scope'])) { ?> value="<?php echo $editData['scope']; ?>" <?php } ?> />
                    </div>
                    <div class="clearfix"></div><br>
                    <div class="col-md-10">    
                    <div class="change-message"><input type="submit" name="submit" value="Submit" class="btn btn-primary"></div>
                    </div>
                 </div>
                 
         </div>
     </div>
 </div>
 </form>
 