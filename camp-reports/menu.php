<style>
        /* Full width navbar */
        .navbar {
            width: 100%;     padding: 0;
        }
        .navbar-dark .navbar-nav .nav-link {
            color: white;
        }
        .navbar-expand-md .navbar-nav { margin: 0 auto; }
        .navbar-expand-md .navbar-nav .nav-link {         padding-left: 3rem; }
        thead {
    background: #2196f3;
    color: white;
}
    </style>
<nav class="navbar navbar-expand-md navbar-dark fixed-top" style="background: #357ebd;">
      
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav mr-auto">
        <li class="nav-item active">
                    <a class="nav-link" href="loading.php?pg=index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="loading.php?pg=add-account.php">Add Account</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="loading.php?pg=cron-camp-report.php?refresh=1&user_id=<?php echo $user_id; ?>">Fetch live Report</a>
                </li>
                <!--<li class="nav-item">
                    <a class="nav-link" href="loading.php?pg=logout.php">Logout</a>
                </li>-->
        </ul>
       
      </div>
    </nav>
