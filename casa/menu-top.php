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

?>
<div class="nav">
  <?php 
  
    foreach($ad_acc as $k => $v) 
    { //shuffle($color_arr); ?>
    <a class="btn btn-sm btn-<?php echo $color_arr[$i]; ?>" href="loading.php?pg=<?php echo $projURL; ?>&act_id=<?php echo $k; ?>"><?php echo $v; ?></a>
    <?php $i++; } ?>
    <?php if($_SESSION['client']=='casa') { ?>
        <a class="btn btn-sm btn-success" href="loading.php?pg=placement-casa.php">Placements</a>
    <a class="btn btn-sm btn-danger" href="loading.php?pg=spend-casa.php">Spend</a>
    <a class="btn btn-sm btn-warning" href="loading.php?pg=tracker.php">Tracker</a>
    <a class="btn btn-sm btn-info" href="loading.php?pg=spend-casa2.php">Budget</a>
    <a class="btn btn-sm btn-success" href="loading.php?pg=create.php">Create Ad</a>
  <a class="btn btn-sm btn-danger" href="loading.php?pg=pause-ad.php">Stop Ad</a>
    <a class="btn btn-sm btn-primary" href="loading.php?pg=breakdown.php" data-toggle="tooltip" title="Hourly breadown" data-placement="bottom">Breakdown <i class="fa fa-clock-o" style="padding: 2px 0;"></i></a>
    <a class="btn btn-sm btn-info" href="loading.php?pg=breakdown-chart3.php" data-toggle="tooltip" title="Hourly breadown chart" data-placement="bottom">Chart <i class="fa fa-line-chart" style="padding: 2px 0;"></i></a>
    <?php } ?>
    <a class="btn btn-md btn-default float-right1" href="logout.php?placement" data-toggle="tooltip" title="Logout" data-placement="bottom"><i class="fa fa-sign-out" style="padding: 2px 0;"></i></a>
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
        <div class="col-4">
        <span style="float:left; margin-left: 15px; font-size:25px;">
        <?php echo $pgName; ?> 
        </span>
        </div>
        <div class="col-8">
        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>
                            <span>December 30, 2014 - January 28, 2015</span> <b class="caret"></b>
                        </div>
        </div>       
      </div>
  </div>
  <?php } ?> 