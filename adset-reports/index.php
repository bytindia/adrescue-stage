<?php
session_start();
include '../db.php';
// Dummy credentials for login
$_SESSION['user_id'] = 0;
        
         
        $_SESSION['log']=1;      
if(!isset($_SESSION['log'])) {
    echo "<script>window.location = 'login.php';</script>";
	exit();
}
$user_id = 0;
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'LG', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
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
$sqlRev=mysqli_query($conn, "SELECT acc_id,acc_name FROM adset_report WHERE  delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $client_name[$sqlROW['acc_id']] = $sqlROW['acc_name'];
}

if(!isset($_SESSION['st']) && !isset($_SESSION['en'])) {
    $start = date('d/m/Y',strtotime('first day of this month'));
	$end = date('d/m/Y');
    $_SESSION['st'] = $start;
    $_SESSION['en'] = $end;
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
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/vendors/moment/min/moment.min.js"></script>
    <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	
    <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
   

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
    small.ecom {
        color: #04a3ef;
    }
</style>
  <body>

    <?php include 'menu.php'; ?>
    <?php
    $report_dt = '';
                    $extQ = '';
                    if($user_id!=0) { $extQ = 'user_id='.$user_id.' AND '; }
                    $acc_data = [];
                    //echo "SELECT * FROM adset_report  WHERE {$extQ} delete_status=0";
                    $sqlRev=mysqli_query($conn, "SELECT * FROM adset_report  WHERE {$extQ} delete_status=0");										
                    while($sqlROW=mysqli_fetch_array($sqlRev))
                    {
                        $acc_data[$sqlROW['acc_id']] = array('spend'=>round($sqlROW['spend']), 'leads'=>round($sqlROW['leads']), 'cpl'=>round($sqlROW['cpl']));
                        $report_dt = date('d-m-Y h:i a', strtotime($sqlROW['updated']));
                    }
    ?>
    <main class="container">
        <center><small><i>Last Updated: <?php echo $report_dt; ?> | <a href="loading.php?pg=cron-adset-report.php?refresh=1&user_id=<?php echo $user_id; ?>" class="btn btn-md btn-warning">Fetch live Report</a></i></small></center><br>
        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                        </div>
        </div>  
                    <?php
                    $extQ = '';
                    if($user_id!=0) { $extQ = 'user_id='.$user_id.' AND '; }
                    $acc_data = [];
                    //echo "SELECT * FROM adset_report  WHERE {$extQ} delete_status=0";
                    $sqlRev=mysqli_query($conn, "SELECT * FROM adset_report  WHERE {$extQ} delete_status=0");										
                    while($sqlROW=mysqli_fetch_array($sqlRev))
                    {
                        $ecom_cpl = 0;
                        if($sqlROW['ecom_lead']>0 && $sqlROW['spend']>0) { $ecom_cpl = $sqlROW['ecom_lead']/$sqlROW['spend']; }
                        $acc_data[$sqlROW['acc_id']] = array('spend'=>round($sqlROW['spend']), 'leads'=>round($sqlROW['leads']), 'cpl'=>round($sqlROW['cpl']), 'ecom_lead'=>round($sqlROW['ecom_lead']),'ecom_cpl'=>round($ecom_cpl));
                    }
                    //d($acc_data);
                    ?>

                    <h4 style="text-align: center;" class="mt-5">Adset wise report</h4>

                    

                    <table id="table2" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>AdSet</th>
                                <th>Campaign</th>
                                <th>Objective</th>
                                <th>Spend</th>
                                <th>Results</th>
                                <th>CPA</th>
                                <th>Spend(Acc)</th>
                                <th>Results(Acc)</th>
                                <th>CPA(Acc)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $extQ = '';  if($user_id!=0) { $extQ = 'WHERE user_id='.$user_id.''; }
                                $sqlRev=mysqli_query($conn, "SELECT * FROM adset_report_data $extQ ");										
                                while($sqlROW=mysqli_fetch_array($sqlRev))
                                {
                                    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
                                    /*$pecentage =  round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100);
                                    
                                    $bg='';
                                    if($pecentage>0 && $pecentage<25) { $bg= 'bg0'; } 
                                    elseif($pecentage>24 && $pecentage<50) { $bg= 'bg25'; } 
                                    elseif($pecentage>49 && $pecentage<75) { $bg= 'bg50'; } 
                                    elseif($pecentage>74) { $bg= 'bg75'; } */
                                    $ecom_lead = $ecom_cpl = $ecom_lead_acc = $ecom_cpl_acc = '';
                                    $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
                                    if($sqlROW['obj']=='OUTCOME_SALES' && $sqlROW['ecom_lead']!='' && $sqlROW['spend']>0) {
                                        $ecom_lead = '<br><small class="ecom">'.$sqlROW['ecom_lead'].'</small>';
                                        $ecom_cpl = '<br><small class="ecom">'.round($sqlROW['spend']/$sqlROW['ecom_lead']).'</small>';
                                        
                                    }
                                    if($sqlROW['obj']=='OUTCOME_SALES' && $acc_data[$sqlROW['acc_id']]['ecom_lead']>0) {
                                        $ecom_lead_acc = '<br><small class="ecom">'.$acc_data[$sqlROW['acc_id']]['ecom_lead'].'</small>';
                                        $ecom_cpl_acc = '<br><small class="ecom">'.round($acc_data[$sqlROW['acc_id']]['spend']/$acc_data[$sqlROW['acc_id']]['ecom_lead']).'</small>';
                                    }
                                    $st_clour = '#db3f3fde';
                                    if($sqlROW['adset_status']=='yes'){ $st_clour = '#58d558'; }
                                ?>
                                <tr class="<?php echo $bg; ?>">  
                                    <td><?php echo '<b>'.$sqlROW['client'].'</b>'; ?></td>
                                    <td><i class="glyphicon glyphicon-calendar fa fa-circle" style="color:<?php echo $st_clour; ?>;"></i> <?php echo '<a href="'.$as_link.'" target="_blank">'.$sqlROW['adset_name'].'</a>'; ?></td>
                                    <td><?php echo $sqlROW['camp_name']; ?></td>
                                    <td><?php echo $objectives[$sqlROW['obj']]['name']; ?></td>
                                    <td><?php echo $fmt->format(round($sqlROW['spend'])); ?></td>
                                    <td data-sort="<?php echo $sqlROW['leads']; ?>"><?php echo $fmt->format($sqlROW['leads']).''.$ecom_lead; ?></td>
                                    <td data-sort="<?php echo round($sqlROW['cpl']); ?>"><?php echo $fmt->format(round($sqlROW['cpl'])).''.$ecom_cpl; ?></td>
                                    <td><?php echo $fmt->format(round($acc_data[$sqlROW['acc_id']]['spend'])); ?></td>
                                    <td data-sort="<?php echo $acc_data[$sqlROW['acc_id']]['leads']; ?>"><?php echo $fmt->format($acc_data[$sqlROW['acc_id']]['leads']).''.$ecom_lead_acc; ?></td>
                                    <td data-sort="<?php echo round($acc_data[$sqlROW['acc_id']]['cpl']); ?>"><?php echo $fmt->format(round($acc_data[$sqlROW['acc_id']]['cpl'])).''.$ecom_cpl_acc; ?></td>
                                </tr> 
                                <?php } ?>
                        </tbody>
                    </table>
    </main>
   
    <!-- Bootstrap JS, Popper.js, jQuery -->

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
<script>
$(document).ready(function() {
$('[data-toggle="tooltip"]').tooltip();
	
var defSt = '01/01/2018';
var defEnd = '01/01/2024';
var d1 = '<?php echo $_SESSION['st']; ?>';
var d2 = '<?php echo $_SESSION['en']; ?>';
var start = moment(d1.split(' ')[0].split("/").reverse().join("-"));
var end = moment(d2.split(' ')[0].split("/").reverse().join("-"));
$('#reportrange span').html(d1 + ' - ' + d2);
        $('#reportrange').daterangepicker(
        {  
           
            dateLimit: { days: 1000 },
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: false,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                'Last 7 Days': [moment().subtract('days', 6), moment()],
                'Last 30 Days': [moment().subtract('days', 29), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
            },
            opens: 'left',
            buttonClasses: ['btn btn-default'],
            applyClass: 'btn-small btn-primary',
            cancelClass: 'btn-small',
            format: 'DD/MM/YYYY',
            separator: ' to ',
            locale: {
                applyLabel: 'Submit',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom Range',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: 1
            }
        },
        function(start, end) {
            if(start.format('DD/MM/YYYY')==defSt) {
                console.log("Callback has been called!");
                $('#reportrange span').html(''); 
                $('#stDt_upd').val('');
                $('#enDt_upd').val('');
                window.location = 'loading.php?pg=cron-adset-report.php&refresh=1&user_id=<?php echo $user_id; ?>';
            
            } else {
                //alert(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                window.location = 'loading.php?pg=cron-adset-report.php&refresh=1&user_id=<?php echo $user_id; ?>&st='+start.format('DD/MM/YYYY')+'&en='+end.format('DD/MM/YYYY');
                startDate = start;
                endDate = end;   
                $('#stDt_upd').val(moment(startDate).format('MM/DD/Y'));
                $('#enDt_upd').val(moment(endDate).format('MM/DD/Y'));
        
            }
        }
        );


});
					
	</script>
  </body>
</html>
