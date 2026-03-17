<?php
include 'db.php';
$metrics_arr = array();
if(isset($_POST['id']) && $_POST['id']!='') {
    $tbl_id = $_POST['id'];
    $val = $_POST['values'];
    foreach($val as $k => $v){ 
        if($k!='name' && $k!='spend'){
            $metrics_arr[] = $v;
        }
    }
    $metrics = serialize($metrics_arr);
    echo $cirSql = "UPDATE kpi SET client='".mysqli_real_escape_string($conn, $val['name'])."', spend='".mysqli_real_escape_string($conn, $val['spend'])."', metrics='".mysqli_real_escape_string($conn, $metrics)."', updated=now() WHERE tbl_id=".$tbl_id."";
	mysqli_query($conn, $cirSql) or die(mysqli_error());
	//$lastId = mysqli_insert_id($conn);

}

print_r($_POST);