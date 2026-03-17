<?php
//print_r($_POST);
include 'db.php';

if($_POST['ty']!='' && $_POST['id']!=0) {
    //echo "SELECT * FROM deliverable_".$_POST['ty']." WHERE id=".$_POST['id']." "; 
    $sqlRev=mysqli_query($conn, "SELECT name FROM deliverable_".$_POST['ty']." WHERE tbl_id=".$_POST['id']." ");

    while($Rdata=mysqli_fetch_assoc($sqlRev)) {
        $editData = $Rdata;
    }
}

//print_r($editData); //exit;


?>

<form method="post" action="">
<input type="hidden" name="tbl_id" value="<?php echo $_POST['id']; ?>">
<input type="hidden" name="ty" value="<?php echo $_POST['ty']; ?>">
<div class="content">
     <div class="container-fluid">
       <div class="row">		
       <!--<div class="change-message">You have unsaved changes.</div>-->
       <div class="col-md-8">     
                                                	<label> Name: </label>
                                                    <input type="text" name="name" class="form-control" <?php if(isset($editData['name'])) { ?> value="<?php echo $editData['name']; ?>" <?php } ?> required>                                                    	
                                                    <br />   
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-success">
                                                 
                                                 <br />
                                                </div>
         </div>
     </div>
 </div>
 </form>
 