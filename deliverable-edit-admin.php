<?php session_start();
//print_r($_POST);
include 'db.php';
//$_SESSION['admin'] =1;
$sqlRev=mysqli_query($conn, "SELECT * FROM deliverable_labels WHERE status=0 ");
while($sqlROW=mysqli_fetch_array($sqlRev)) { $labels[$sqlROW['tbl_id']] = $sqlROW['name']; }

$sqlRev3=mysqli_query($conn, "SELECT * FROM deliverable_designer WHERE  status=0 order by name asc");
while($sqlROW3=mysqli_fetch_array($sqlRev3)) { $designers[$sqlROW3['tbl_id']] = $sqlROW3['name']; }

$sqlRev2=mysqli_query($conn, "SELECT * FROM deliverable_manager WHERE  status=0 order by name asc");
while($sqlROW2=mysqli_fetch_array($sqlRev2)) { $managers[$sqlROW2['tbl_id']] = $sqlROW2['name']; }

$weeks_filter = array('1'=>'1st week','2'=>'2st week','3'=>'3rd week','4'=>'4th week','5'=>'5th week');
$month_filter = array('1'=>'Jan','2'=>'Feb','3'=>'Mar','4'=>'Apr','5'=>'May','6'=>'Jun','7'=>'Jul','8'=>'Aug','9'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec');
$year_filter = array(2023=>2023, 2024=>2024);

if($_POST['mon']<10 && substr($_POST['mon'], 0, 1) != '0') {
    $_POST['mon'] = '0'.$_POST['mon'];
}

//echo "SELECT * FROM deliverable_reports WHERE client_id='".$_POST['id']."' AND week='".$_POST['wk']."' AND month='".$_POST['mon']."' AND year='".$_POST['yr']."'"; 
$sql_report=mysqli_query($conn, "SELECT * FROM deliverable_comit_data WHERE cId='".$_POST['id']."' AND month='".$_POST['mon']."' AND year='".$_POST['yr']."'");
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
                 
                 <input type="hidden" name="client_id" class="form-control" value="<?php echo $_POST['id']; ?>"/>
                 <input type="hidden" name="month" class="form-control" value="<?php echo $_POST['mon']; ?>"/>
                 <input type="hidden" name="year" class="form-control" value="<?php echo $_POST['yr']; ?>"/>
                 
                    <div class="col-md-11">   <br>
                    <label>Reports on</label>  <div class="clearfix"></div> 
                    <div class="col-md-3">  
                        <input class="form-control date" class="date" id="date" name="rep_date" value="<?php echo $_POST['mon'].'-'.$_POST['yr']; ?>" placeholder="mm-yyyy" type="text"  onchange="changeMon(<?php echo $_POST['id']; ?>, this, '<?php echo $_POST['clName']; ?>');"> 
                 
                    </div>
                    </div><div id="custom-pos"></div>
                    <div class="col-md-11">  <br>
                    <label>Committed: </label><div class="clearfix"></div>
                        <?php if(isset($editData['comit'])) { $editDesign = unserialize($editData['comit']); }
                            foreach($labels as $d_k => $d_v){  ?>
                            <div class="col-md-2">    
                            <label><?php echo $d_v; ?></label>
                            <input type="number" name="comit[<?php echo $d_k; ?>]" class="form-control" onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')" <?php if(isset($editDesign[$d_k])) { ?> value="<?php echo $editDesign[$d_k]; ?>" <?php } ?>/>
                            </div>
                        <?php }  ?>
                    </div>
                   
                    <div class="clearfix"></div><br>
                    <div class="col-md-10">    
                    <div class="change-message"><input type="submit" name="submit_comit" value="Submit" class="btn btn-primary"></div>
                    </div>
                 </div>
                 
         </div>
     </div>
 </div>
 </form>
 