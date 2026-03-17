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
 
    <a class="btn btn-md btn-default float-right1" href="loading.php?pg=multi-select.php" data-toggle="tooltip" title="Logout" data-placement="bottom"><i class="fa fa-arrow-left" style="padding: 2px 0;"></i> Go Back</a>
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