<?php
 $src_text = array(
    'Facebook' => 'FB',
    'Reels' => 'RL',
    'Desktop' => 'DT',
    'Search' => 'SR',
    'Feed' => 'FD',
    'Mobile App' => 'Mb A',
    'Explore' => 'Ex',
    'Marketplace' => 'Mkpl',
    'story' => 'Sty',
    'Instant Article' => 'IN Art',
    'Stories' => 'Sty',
    'Overlay' => 'Ov',
    'Video' => 'V',
    'Instagram' => 'IG',
    'Unknown' => 'UKN');

$color_arr = array('primary','danger','success','primary','warning','info','danger','success','warning','info','primary','danger','success','danger','warning');$i=0;



if(!isset($projURL)) {
    $projURL = 'placement-lg.php';
}


$ad_acc = array(
  0 =>2516969615147829,
  1 => 735957617015640
);
$ad_acc[0] = 2516969615147829;
$ad_acc[1] = 735957617015640;
$proj_list = $_SESSION['proj_list'];
/*
$proj_list = array(
  'HANFORD',
  'PLATINUM',
  'AQUENE',
  'ARIA',
  'ASPIRES'
);*/
$fb_pg = array(
  133323657231697 =>'Eden Park Life',
  116025885081757=>'BYT Digital',
);
//echo date('l');
//$date = date('d-m-Y'); //or //23-02-2015
//echo date('d-m-Y', strtotime('-1 week', strtotime($date)));
$currentPage = basename($_SERVER['SCRIPT_NAME']);
if(date('l')=='Monday') {
  $last_wk = 'loading.php?pg='.$currentPage.'&st='.date("d/m/Y", strtotime("-13 days")).'&en='.date("d/m/Y", strtotime("-7 days"));
  $this_wk = 'loading.php?pg='.$currentPage.'&st='.date("d/m/Y", strtotime("last week tuesday")).'&en='.date("d/m/Y", strtotime("today"));
  $this_wk_st_dt = date("d-m-Y", strtotime("last week tuesday")); 
  $this_wk_en_dt = date("d-m-Y", strtotime("today")); 
} else {
  $last_wk = 'loading.php?pg='.$currentPage.'&st='.date("d/m/Y", strtotime("last week tuesday")).'&en='.date("d/m/Y", strtotime("this week monday"));
  $this_wk = 'loading.php?pg='.$currentPage.'&st='.date("d/m/Y", strtotime("this week tuesday")).'&en='.date("d/m/Y", strtotime("next week monday"));
  $this_wk_st_dt = date("d-m-Y", strtotime("this week tuesday")); 
  $this_wk_en_dt = date("d-m-Y", strtotime("next week monday")); 
}

if(date("d/m/Y")){

}

?>
<div class="nav">
  <?php 
  
    foreach($proj_list as $k => $v) 
    { //shuffle($color_arr); ?>
     <!--<a class="btn btn-sm btn-<?php echo $color_arr[$i]; ?>" href="loading.php?pg=<?php echo $projURL; ?>&act_id=<?php echo $k; ?>"><?php echo $v; ?></a>-->
    <?php $i++; } ?>
    <?php if($_SESSION['client']=='casa') { ?>
     <!--   <a class="btn btn-sm btn-success" href="loading.php?pg=placement-casa.php">Placements</a> -->
    <a class="btn btn-sm btn-primary" href="loading.php?pg=spend.php">Spend</a>
     <!--<a class="btn btn-sm btn-success" href="projects-add.php">Projects <i class="fa fa-plus" style="padding: 2px 0;"></i></a>
   <a class="btn btn-sm btn-warning" href="loading.php?pg=tracker.php">Tracker</a>
    <a class="btn btn-sm btn-info" href="loading.php?pg=budget.php">Budget</a>
    
    <a class="btn btn-sm btn-danger" href="loading.php?pg=pause-ad.php">Pause Ad</a>
    <a class="btn btn-sm btn-danger" href="loading.php?pg=create.php">Create Ad</a>
    <a class="btn btn-sm btn-primary" href="loading.php?pg=breakdown.php" data-toggle="tooltip" title="Hourly breadown" data-placement="bottom">Breakdown <i class="fa fa-clock-o" style="padding: 2px 0;"></i></a>
    <a class="btn btn-sm btn-info" href="loading.php?pg=breakdown-chart3.php" data-toggle="tooltip" title="Hourly breadown chart" data-placement="bottom">Chart <i class="fa fa-line-chart" style="padding: 2px 0;"></i></a>
    -->
    <?php } ?>
    
    </div>

    <?php if(!isset($header_no)) { ?>
  <div class="right_col" role="main">
      <div class="row" style="margin-right: 0px; margin-bottom: 10px;">
          <div class="col-12">
          <span class="pull-right">
          
          </span>
          </div>   
      </div>
  
      <div class="row">
        <div class="col-2">
        <span style="float:left; margin-left: 15px; font-size:25px;">
        <?php //echo $pgName; ?> 
        </span>
        </div>
        
        <div class="col-6 pull-right">
          
        <button id="submitComparison" class="btn btn-primary pull-right" style="margin-right: 10px;">Submit</button>
        <div id="reportrange2" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; margin-right: 10px;">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>Date Range 2</span> <b class="caret"></b>
                        </div>
                        <div id="reportrange1" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; margin-right: 10px;">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>Date Range 1</span> <b class="caret"></b>
                        </div>
                         <div class="pull-right" style="margin-right: 15px;">
          <select id="clientDropdown" class="form-control" style="display: inline-block; width: auto; min-width: 150px;" onchange="if(this.value) window.location.href=this.value;">
            <option value="">Select Client</option>
            <?php
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'  || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $basename = basename(parse_url($currentUrl, PHP_URL_PATH));

            $clientQuery = mysqli_query($conn, "SELECT tbl_id, client_name FROM dashboard_accounts WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ORDER BY client_name ASC");
            $selectedClient = '';
            if(isset($_GET['tbl_id'])) {
              $selectedClient = $_GET['tbl_id'];
            }
            while($clientRow = mysqli_fetch_array($clientQuery)) {
              $selected = ($selectedClient == $clientRow['tbl_id']) ? 'selected' : '';
              echo '<option value="loading.php?pg='.$basename.'?tbl_id='.$clientRow['tbl_id'].'" '.$selected.'>'.htmlspecialchars($clientRow['client_name']).'</option>';
            }
            ?>
          </select>
        </div>
        
        </div>       
      </div>
  </div>
  <?php } ?> 
  
  <style>
    .adrescue_logo { display: none !important; }
    tfoot { font-weight: bold; }
    .report-header {
  display: flex;
  justify-content: space-between; /* Push h4 left, span right */
  align-items: center;           /* Align vertically */
  flex-wrap: wrap;               /* Allow wrapping on smaller screens */
}

.report-header h4 {
  margin: 0;  /* Remove default spacing */
}

.report-header span {
  font-size: 14px;
  text-align: right;
}
  </style>
  
  <script src="/vendors/moment/min/moment.min.js"></script>
  <script src="/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
  <link href="/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
  
  <script>
  $(document).ready(function() {
    // Get URL parameters for date ranges
    var urlParams = new URLSearchParams(window.location.search);
    var st1 = urlParams.get('st1');
    var en1 = urlParams.get('en1');
    var st2 = urlParams.get('st2');
    var en2 = urlParams.get('en2');
    
    // Set default dates or use URL parameters
    var startDate1 = st1 ? moment(st1, 'DD/MM/YYYY') : moment().startOf('month');
    var endDate1 = en1 ? moment(en1, 'DD/MM/YYYY') : moment().endOf('month');
    var startDate2 = st2 ? moment(st2, 'DD/MM/YYYY') : moment().subtract(1, 'month').startOf('month');
    var endDate2 = en2 ? moment(en2, 'DD/MM/YYYY') : moment().subtract(1, 'month').endOf('month');
    
    // Initialize Date Range 1
    $('#reportrange1').daterangepicker(
      {  
        dateLimit: { days: 1000 },
        showDropdowns: true,
        showWeekNumbers: true,
        timePicker: false,
        timePickerIncrement: 1,
        timePicker12Hour: true,
        startDate: startDate1,
        endDate: endDate1,
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
        $('#reportrange1 span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        // Update the daterangepicker instance with the new dates
        $('#reportrange1').data('daterangepicker').setStartDate(start);
        $('#reportrange1').data('daterangepicker').setEndDate(end);
      }
    );

    // Initialize Date Range 2
    $('#reportrange2').daterangepicker(
      {  
        dateLimit: { days: 1000 },
        showDropdowns: true,
        showWeekNumbers: true,
        timePicker: false,
        timePickerIncrement: 1,
        timePicker12Hour: true,
        startDate: startDate2,
        endDate: endDate2,
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
        $('#reportrange2 span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        // Update the daterangepicker instance with the new dates
        $('#reportrange2').data('daterangepicker').setStartDate(start);
        $('#reportrange2').data('daterangepicker').setEndDate(end);
      }
    );

    // Submit button functionality
    $('#submitComparison').click(function() {
      var range1 = $('#reportrange1 span').text();
      var range2 = $('#reportrange2 span').text();
      
      if (range1 !== 'Date Range 1' && range2 !== 'Date Range 2') {
        // Extract dates from the display text
        var dates1 = range1.split(' - ');
        var dates2 = range2.split(' - ');
        
        if (dates1.length === 2 && dates2.length === 2) {
          // Redirect to comparison page with both date ranges
          var url = 'loading.php?pg=spend-compare.php&tbl_id=<?php echo $tbl_id; ?>&st1=' + dates1[0] + '&en1=' + dates1[1] + '&st2=' + dates2[0] + '&en2=' + dates2[1];
          window.location = url;
        }
      } else {
        alert('Please select both date ranges before submitting.');
      }
    });

    // Set initial display based on URL parameters or defaults
    $('#reportrange1 span').html(startDate1.format('DD/MM/YYYY') + ' - ' + endDate1.format('DD/MM/YYYY'));
    $('#reportrange2 span').html(startDate2.format('DD/MM/YYYY') + ' - ' + endDate2.format('DD/MM/YYYY'));
  });
  </script>
  
  <?php //exit; ?>