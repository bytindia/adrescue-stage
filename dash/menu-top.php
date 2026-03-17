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
    <!--<a class="btn btn-md btn-default float-right1" href="logout.php?placement" data-toggle="tooltip" title="Logout" data-placement="bottom"><i class="fa fa-sign-out" style="padding: 2px 0;"></i></a>-->
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
          
        <!-- Client Dropdown -->
        
        
        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
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
              echo '<option value="loading.php?pg='.$basename.'&tbl_id='.$clientRow['tbl_id'].'" '.$selected.'>'.htmlspecialchars($clientRow['client_name']).'</option>';
            }
            ?>
          </select>
        </div>
        </div>       
      </div>
  </div>
  <?php } ?> 
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
     body { font-family: "Nunito", sans-serif; }
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
  <?php //exit; ?>
  