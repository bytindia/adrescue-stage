<?php include 'header.php'; 

function getTotalrows($tbl, $conn) {
	$sql = "SELECT id FROM $tbl order by id desc";
	$result = $conn->query($sql);
	return $result->num_rows;
}
?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        

        <!-- page content -->
        <div class="right_col" role="main">       
          <!-- top tiles -->
          <div class="row tile_count">
            <a href="loading.php?pg=leads.php">
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top">Leads</span>
              <div class="count purple"><i class="fa fa-list"></i> <?php echo getTotalrows('sales_ninja', $conn); ?></div>
              <span class="count_bottom"><i class="green">4% </i> From last Week</span>
            </div>
            </a>
            <a href="loading.php?pg=sales-user.php">
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"> Sales Team</span>
              <div class="count green"><i class="fa fa-users"></i> <?php echo getTotalrows('sales_ninja_users', $conn); ?></div>
              <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>34% </i> From last Week</span>
            </div>
            </a>
            <a href="loading.php?pg=stats.php">
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top">Calls </span>
              <div class="count red"><i class="fa fa-phone"></i> <?php echo getTotalrows('sn_calls', $conn); ?></div>
              <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>3% </i> From last Week</span>
            </div>  
            </a>
            <a href="loading.php?pg=stats.php?ty=2">          
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top">SMS </span>
              <div class="count aero"><i class="fa fa-envelope"></i> <?php echo getTotalrows('sn_sms', $conn); ?></div>
              <span class="count_bottom"><i class="red"><i class="fa fa-sort-desc"></i>12% </i> From last Week</span>
            </div>
            </a>
            <a href="loading.php?pg=stats.php?ty=3">
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top">Follow-ups</span>
              <div class="count blue"><i class="fa fa-calendar"></i> <?php echo getTotalrows('sn_reminder', $conn); ?></div>
              <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>34% </i> From last Week</span>
            </div>
            </a>
            <a href="loading.php?pg=stats.php?ty=4">
            <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
              <span class="count_top"> Lead Quality</span>
              <div class="count text-success"><i class="fa fa-thumbs-up"></i> <?php echo getTotalrows('sn_callqty', $conn); ?></div>
              <span class="count_bottom"><i class="green"><i class="fa fa-sort-asc"></i>34% </i> From last Week</span>
            </div>
            </a>
          </div>
          <!-- /top tiles -->

          

          <div class="row">


            <div class="col-md-4 col-sm-4 col-xs-12">
              <div class="x_panel tile fixed_height_320">
                <div class="x_title">
                  <h2>Calls</h2>
                  
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <h4>Sales Team (Dummy Chart)</h4>
                  <div class="widget_summary">
                    <div class="w_left w_25">
                      <span>Prabhu</span>
                    </div>
                    <div class="w_center w_55">
                      <div class="progress">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 66%;">
                          <span class="sr-only">60% Complete</span>
                        </div>
                      </div>
                    </div>
                    <div class="w_right w_20">
                      <span>60k</span>
                    </div>
                    <div class="clearfix"></div>
                  </div>

                  <div class="widget_summary">
                    <div class="w_left w_25">
                      <span>Faheem</span>
                    </div>
                    <div class="w_center w_55">
                      <div class="progress">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 45%;">
                          <span class="sr-only">60% Complete</span>
                        </div>
                      </div>
                    </div>
                    <div class="w_right w_20">
                      <span>53k</span>
                    </div>
                    <div class="clearfix"></div>
                  </div>
                  <div class="widget_summary">
                    <div class="w_left w_25">
                      <span>Ramesh</span>
                    </div>
                    <div class="w_center w_55">
                      <div class="progress">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 25%;">
                          <span class="sr-only">60% Complete</span>
                        </div>
                      </div>
                    </div>
                    <div class="w_right w_20">
                      <span>23k</span>
                    </div>
                    <div class="clearfix"></div>
                  </div>
                  <div class="widget_summary">
                    <div class="w_left w_25">
                      <span>Mafaz</span>
                    </div>
                    <div class="w_center w_55">
                      <div class="progress">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 5%;">
                          <span class="sr-only">60% Complete</span>
                        </div>
                      </div>
                    </div>
                    <div class="w_right w_20">
                      <span>3k</span>
                    </div>
                    <div class="clearfix"></div>
                  </div>
                  <div class="widget_summary">
                    <div class="w_left w_25">
                      <span>Jai Shankar</span>
                    </div>
                    <div class="w_center w_55">
                      <div class="progress">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 2%;">
                          <span class="sr-only">60% Complete</span>
                        </div>
                      </div>
                    </div>
                    <div class="w_right w_20">
                      <span>1k</span>
                    </div>
                    <div class="clearfix"></div>
                  </div>

                </div>
              </div>
            </div>

            <div class="col-md-4 col-sm-4 col-xs-12">
              <div class="x_panel tile fixed_height_320 overflow_hidden">
                <div class="x_title">
                  <h2>Stats</h2>
                  
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <table class="" style="width:100%">
                    <tr>
                      <th style="width:37%;">
                        <p>Top 5</p>
                      </th>
                      <th>
                        <div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">
                          <p class="">Actions</p>
                        </div>
                        <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                          <p class="">Progress</p>
                        </div>
                      </th>
                    </tr>
                    <tr>
                      <td>
                        <canvas class="canvasDoughnut" height="140" width="140" style="margin: 15px 10px 10px 0"></canvas>
                      </td>
                      <td>
                        <table class="tile_info">
                          <tr>
                            <td>
                              <p><i class="fa fa-square blue"></i>Calls </p>
                            </td>
                            <td>30%</td>
                          </tr>
                          <tr>
                            <td>
                              <p><i class="fa fa-square green"></i>SMS </p>
                            </td>
                            <td>10%</td>
                          </tr>
                          <tr>
                            <td>
                              <p><i class="fa fa-square purple"></i>Follow-ups </p>
                            </td>
                            <td>20%</td>
                          </tr>
                          <tr>
                            <td>
                              <p><i class="fa fa-square aero"></i>Call Quality </p>
                            </td>
                            <td>15%</td>
                          </tr>
                          <tr>
                            <td>
                              <p><i class="fa fa-square red"></i>Others </p>
                            </td>
                            <td>30%</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                  <small class="pull-right">Dummy Chart</small>
                </div>
              </div>
            </div>


            <div class="col-md-4 col-sm-4 col-xs-12">
              <div class="x_panel tile fixed_height_320">
                <div class="x_title">
                  <h2>Quick Links</h2>
                  
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <div class="dashboard-widget-content">
                    <ul class="quick-list">                      
                      <li><i class="fa fa-bars"></i><a href="loading.php?pg=sales-user.php">Sales Team</a>
                      </li>
                      <li><i class="fa fa-bar-chart"></i><a href="loading.php?pg=sales-user-add.php">Add Sales User</a> </li>
                      <li><i class="fa fa-line-chart"></i><a href="loading.php?pg=leads.php">Leads</a>
                      </li>
                      <li><i class="fa fa-bar-chart"></i><a href="loading.php?pg=leads-upload.php">Upload Leads</a> </li>
                      <li><i class="fa fa-line-chart"></i><a href="loading.php?pg=sales-user-assign.php">Assign Lead</a>
                      </li>
                      <li><i class="fa fa-area-chart"></i><a href="loading.php?pg=logout.php">Logout</a>
                      </li>
                    </ul>

                    <div class="sidebar-widget">
                        <h4>Call Duration</h4>
                        <canvas width="150" height="80" id="chart_gauge_01" class="" style="width: 160px; height: 100px;"></canvas>
                        <div class="goal-wrapper">
                          <span id="gauge-text" class="gauge-value pull-left">0</span>
                          <span class="gauge-value pull-left"> &nbsp; in sec</span>
                          <span id="goal-text" class="goal-value pull-right">100%</span>
                        </div>
                    </div>
                    <small class="pull-right">Dummy Chart</small>
                  </div>
                </div>
              </div>
            </div>

          </div>


         
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>