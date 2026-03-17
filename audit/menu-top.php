<!-- top navigation -->
<style>
#menu_toggle { display:block; }
#menu_toggle img { margin-bottom: 10px; margin-top:-10px; }
html, body { padding:0px; } 
.toggle { width:auto; } 
.main_container .top_nav, .nav-md .container.body .right_col, footer { margin-left:0px; }
</style>
<script>$('#menu_toggle').click(); </script>
        <div class="top_nav">
          <div class="nav_menu">
            <nav>
              <div class="nav toggle">
                <a href="index.php" id="menu_toggle"><img src="images/adrescue-logo.png" alt="..." height="150"></a>
              </div>

              <ul class="nav navbar-nav navbar-right">
                <li class="">
                  <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <img src="images/user.png" alt=""><?php if(isset($_SESSION['name'])) { echo $_SESSION['name']; } else { echo 'Admin'; } ?>
                    <span class=" fa fa-angle-down"></span>
                  </a>
                  <ul class="dropdown-menu dropdown-usermenu pull-right" style="right:0px !important;">
                    <!--<li><a href="fb-login.php?update=1"> FB Login (Update)</a></li>-->
                    <li><a href="?permission=1"> Remove FB Permissions</a></li>
                    <li><a href="logout.php"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                  </ul>
                </li>

                
              </ul>
            </nav>
          </div>
        </div>
        <!-- /top navigation -->