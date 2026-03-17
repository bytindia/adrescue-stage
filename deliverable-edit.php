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
$sql_report=mysqli_query($conn, "SELECT * FROM deliverable_reports WHERE client_id='".$_POST['id']."' AND date='".$_POST['date']."' AND label_id='". $_POST['lab_id']."'");
while($Rdata=mysqli_fetch_array($sql_report)) {
	$editData = $Rdata;
}
?>

<form method="post" action="">
<div class="content">
     <div class="container-fluid">
       <div class="row">		
      <!--<div class="change-message">You have unsaved changes.</div>-->
              <div class="col-md-11">   <br>
                <div class="row">
                  <div class="col-md-3">
                    <label>Reports on</label>
                    <input class="form-control date" class="date" id="date" name="rep_date" placeholder="dd-mm-yyyy" value="<?php echo $_POST['date']; ?>" type="text" onchange="changeDt(<?php echo $_POST['id']; ?>, '<?php echo $_POST['lab_id']; ?>', '<?php echo $_POST['clName']; ?>', this);">
                  </div>
                  <div class="col-md-3">
                    <label>SM Team</label>
                    <input type="hidden" name="client_id" class="form-control" value="<?php echo $_POST['id']; ?>"/>
                    <input type="hidden" name="label_id" class="form-control" value="<?php echo $_POST['lab_id']; ?>"/>
                    <input type="hidden" name="date" class="form-control" value="<?php echo $_POST['date']; ?>"/>
                    <select id="manager" name="manager" class="form-control" required>
                      <option value="">Manager</option>
                      <?php foreach($managers as $k => $v){  ?>
                      <option value="<?php echo $k; ?>" <?php if(isset($editData['manager_id']) && $k==$editData['manager_id']) { ?> selected <?php } ?>><?php echo $v; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label> <?php echo $labels[$_POST['lab_id']]; ?> : Delivered</label>
                    <input type="number" name="deliver" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editData['delivered'])) {  echo 'value="'.$editData['delivered'].'"';  } ?> />
                  </div>
                  <div class="col-md-3">
                    <label> <?php echo $labels[$_POST['lab_id']]; ?> : Adapt.</label>
                    <input type="number" name="adapt" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editData['adapt'])) {  echo 'value="'.$editData['adapt'].'"';  } ?> />
                  </div>
              </div>

                 <div class="clearfix"></div>  
                 <div class="col-md-11">  <br>
                 <label>Designers: </label><div class="clearfix"></div>
                    <?php 
                        if(isset($editData['designer_id'])) { $editDesign = unserialize($editData['designer_id']); } 
                        if(isset($editData['designer_id2'])) { $effort = unserialize($editData['designer_id2']); } 
                    ?>
                    <table class="table table-bordered" style="background:#fff;">
                      <thead>
                        <tr>
                          <th style="width:50%;">Name</th>
                          <th style="width:25%;">Delivered</th>
                          <th style="width:25%;">Effort</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach($designers as $d_k => $d_v){  ?>
                        <tr>
                          <td><?php echo $d_v; ?></td>
                          <td>
                            <input type="number" name="designer[<?php echo $d_k; ?>]" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editDesign[$d_k])) { ?> value="<?php echo $editDesign[$d_k]; ?>" <?php } ?>/>
                          </td>
                          <td>
                            <input type="number" name="effort[<?php echo $d_k; ?>]" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($effort[$d_k])) { ?> value="<?php echo $effort[$d_k]; ?>" <?php } ?>/>
                          </td>
                        </tr>
                        <?php }  ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="clearfix"></div>  
                 <div class="col-md-11">  <br>
                 <div class="clearfix"></div>
                    <div class="col-md-10">    
                        <label>Resons</label>
                        <textarea  name="reason" class="form-control"><?php if(isset($editData['reasons'])) {  echo $editData['reasons'];  } ?></textarea>
                    </div>  <br> <br>
                    <div class="clearfix"></div><br>
                    <div class="col-md-10">    
                        <label>Scope</label>
                        <textarea type="text" name="scope" class="form-control"><?php if(isset($editData['scope'])) {  echo $editData['scope'];  } ?></textarea>
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
 