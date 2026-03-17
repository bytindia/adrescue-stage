<?php
session_start();
include '../db.php';
// Dummy credentials for login
if(!isset($_SESSION['log'])) {
    echo "<script>window.location = 'login.php';</script>";
	exit();
}
$user_id = $_SESSION['user_id'];
if(isset($_GET['del'])) {
                $cirSql2 = "UPDATE camp_report SET delete_status='1' WHERE id=".$_GET['del']."";
                mysqli_query($conn, $cirSql2) or die(mysqli_error($conn));
                $_SESSION['suc'] = 'Successfully Deleted!';	
                echo "<script>window.location = 'accounts.php';</script>";
                exit();
}
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];
$qry_str_ad = '&filter_set=SEARCH_BY_ADGROUP_IDS-STRING_SET%1EANY%1E[%22';
$qry_str_adset = '&filter_set=SEARCH_BY_CAMPAIGN_IDS-STRING_SET%1EANY%1E[%22';
$qry_str_camp = '&filter_set=SEARCH_BY_CAMPAIGN_GROUP_IDS-STRING_SET%1EANY%1E[%22';

$fb_url_ad = 'https://adsmanager.facebook.com/adsmanager/manage/ads?act=';
$fb_url_adset = 'https://adsmanager.facebook.com/adsmanager/manage/adsets?act=';
$fb_url_camp = 'https://adsmanager.facebook.com/adsmanager/manage/campaigns?act=';

$dt_qry_30 = '%22]&date='.date("Y-m-d", strtotime('first day of this month')).'_'.date('Y-m-d',strtotime('today'));
$dt_qry_3 = '%22]&date='.date("Y-m-d", strtotime('first day of this month')).'_'.date('Y-m-d',strtotime('today'));

$client_name = $last30d_cpl = array();
$sqlRev=mysqli_query($conn, "SELECT acc_id,acc_name FROM camp_report WHERE  delete_status=0");                                       
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $client_name[$sqlROW['acc_id']] = $sqlROW['acc_name'];
}

?>


<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">

    <title>AdRescue - Rescue Your Campaigns</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" rel="stylesheet">

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">

<!-- DataTables Buttons CSS -->
<link href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css" rel="stylesheet">
   <!-- Bootstrap CSS -->
   <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
   <!-- DataTable CSS -->
   <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/4.0/examples/navbar-fixed/navbar-top-fixed.css" rel="stylesheet">
  </head>
<style>
    #table1 td:nth-child(n+3) {
        text-align: right;
    }
    #table2 td:nth-child(n+5) {
        text-align: right;
    }
</style>
  <body>

    <?php include 'menu.php'; ?>

    <main class="container">
        <center><a href="loading.php?pg=add-account.php" class="btn btn-sm btn-warning float-right">Add Accounts</a></center><br>
        <?php if(isset($_SESSION['suc'])) { echo $_SESSION['suc']; unset($_SESSION['suc']); } ?>
                    <?php
                    $extQ = '';
                    if($user_id!=0) { $extQ = 'user_id='.$user_id.' AND '; }
                    $acc_data = [];
                    //echo "SELECT * FROM camp_report  WHERE {$extQ} delete_status=0";
                    $sqlRev=mysqli_query($conn, "SELECT * FROM camp_report  WHERE {$extQ} delete_status=0");                                       
                    while($sqlROW=mysqli_fetch_array($sqlRev))
                    {
                        $acc_data[$sqlROW['acc_id']] = array('spend'=>round($sqlROW['spend']), 'leads'=>round($sqlROW['leads']), 'cpl'=>round($sqlROW['cpl']));
                    }
                    ?>

                    <h4 style="text-align: center;" class="mt-5">Your Accounts</h4>

                    

                    <table id="table2" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Acc Id</th>
                                <th>No. of Campaigns</th>
                                <th>Objective</th>
                                <th>Media Buyer</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $bytUser = array('admin', 'shaheena','mughil','charan','simin');
                        $extQ = '';  if($user_id!=0) { $extQ = ' user_id='.$user_id.' AND '; }
                        //echo "SELECT * FROM camp_report   {$extQ} delete_status=0"; 
                        $sqlRev=mysqli_query($conn, "SELECT * FROM camp_report  WHERE {$extQ} delete_status=0");                                   
                                while($sqlROW=mysqli_fetch_array($sqlRev))
                                {
                                    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
                                    /*$pecentage =  round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100);
                                    
                                    $bg='';
                                    if($pecentage>0 && $pecentage<25) { $bg= 'bg0'; } 
                                    elseif($pecentage>24 && $pecentage<50) { $bg= 'bg25'; } 
                                    elseif($pecentage>49 && $pecentage<75) { $bg= 'bg50'; } 
                                    elseif($pecentage>74) { $bg= 'bg75'; } */
                                    //$as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
                                    $campIds = unserialize($sqlROW['camp_ids']);
                                ?>
                                <tr >  
                                    <td><?php echo $sqlROW['acc_name']; ?></td>
                                    <td><?php echo $sqlROW['acc_id']; ?></td>
                                    <td><?php echo is_array($campIds) ? count($campIds) : 0; ?></td>
                                    <td><?php echo $sqlROW['obj']; ?></td>
                                    <td><?php echo $bytUser[$sqlROW['user_id']]; ?></td>
                                    <td><a href="accounts.php?del=<?php echo $sqlROW['id']; ?>" class="btn btn-sm btn-danger"  onclick="return confirm('are you sure?')">Delete</a></td>
                                </tr> 
                                <?php } ?>
                        </tbody>
                    </table>
    </main>
   
    <!-- Bootstrap JS, Popper.js, jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTables with buttons
        var table1 = $('#table1').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            lengthMenu: [25, 50, 100, 100],
        });

        var table2 = $('#table2').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            lengthMenu: [25, 50, 100, 100],
        });

       
    });
</script>

  </body>
</html>
