<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
<title>Altis - Status Vs Campaign wise report</title>
<style>
body, html {
    width: 80%;
    margin: 10 auto;
}
ul.nav.navbar-right.panel_toolbox {
    float: right;
    margin-bottom: 20px;
}
footer {
    margin-top: 25px;
    float: right;
    font-size: 14px;
}
</style>

<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
//echo $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

function d($d){
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

include '/home/digitalb2k/public_html/salesninja.app/altis/db.php';
$calQty = $data_g = array();
$sql = "SELECT * FROM callqty_text where cId =12 AND admin_del=0";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $calQty[$row['id']] = $row['txt'];
}

$sql_g = "SELECT COUNT(*) as tot,source,sub_source,cq_id FROM `sales_ninja` WHERE cId=12 AND source LIKE '%google%' GROUP BY sub_source,cq_id order by cq_id desc";
$result_g = $conn->query($sql_g);
while($row_g = $result_g->fetch_assoc()) {
    if($row_g['cq_id']!='' && isset($calQty[$row_g['cq_id']])) {
         $row_g['cq_txt'] = $calQty[$row_g['cq_id']];
    } else {
         $row_g['cq_txt'] = 'Others';
    }
    $data_g[] = $row_g;
}

//d($data_g);
$cqV = '';
$filterBy = 66;
$new = array_filter($data_g, function ($var) use ($filterBy) {
    return ($var['cq_id'] == $filterBy);
});
//d($new);
//exit;
?>
<div class="x_title">
                    
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=altis-utm.php" class="btn btn-success btn-sm">Google</a> 
                      	</div>    
                      </li>
                      <li> &nbsp;
                      </li>
                      <li>
                        <div class="btn-group  btn-group-sm">
                        <a href="loading.php?pg=altis-utm-fb.php" class="btn btn-primary btn-sm">Facebook</a> 
                      	</div>    
                      </li>                      
                    </ul>                   
                    <div class="clearfix"></div>
                    <h4>Altis - Status Vs Campaign wise report (Google)</h4>
</div>
<div class="accordion" id="accordionExample">
    <?php 
    
    foreach($calQty as $key => $value) {
        //if($cqV!=$value['cq_id']) {
            //$string_cq = $key;
            $filterBy = $key;
            $Camp_data = array_filter($data_g, function ($var) use ($filterBy) {
                                return ($var['cq_id'] == $filterBy);
            });
            ?>

                <div class="accordion-item" >
                        <h1 class="accordion-header" id="heading<?php echo $key; ?>">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $key; ?>" aria-expanded="true" aria-controls="collapse<?php echo $key; ?>">
                            <?php echo $calQty[$key]; ?>
                        </button>
        </h1>
                        <div id="collapse<?php echo $key; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $key; ?>" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                        <table  class="table table-hover table-striped table-bordered dataTable no-footer" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Leads</th>
                                </tr>
                            </thead>
                            <?php
                                foreach($Camp_data as $key_c => $val_c) {
                                    ?>
                                         <tr>
                                            <td><?php echo $val_c['sub_source']; ?></td>
                                            <td><?php echo $val_c['tot']; ?></td>
                                        </tr>
                                    <?php
                                }
                            ?>
                        </table>
                        </div>
                        </div>
                </div>
                
            <?php 
           // $cqV = $value['cq_id'];
        //}
    } 
    include 'footer.php';
    ?>
    
   
</div>

<script>
    //$(document).ready(function () {
        $('table.table').dataTable();
//});
</script>
