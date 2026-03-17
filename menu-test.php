
<?php  
  $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$access_token = $row['access_token']; 
  
  if(!isset($_GET['menu'])) { ?>

<style>

/* Match menu-top.php: white background, dark text, blue hover */
.menu-container {
	display: flex;
	align-items: center;
	justify-content: space-between;
	flex-wrap: wrap;
	background: #337ab7; /* requested background */
	border-bottom: 1px solid #2b68a0;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.menu-left {
	display: flex;
	align-items: center;
}
.menu-logo {
	display:inline-block;
	vertical-align:middle;
	margin-left:18px;
	margin-right:8px;
}
.menu-logo img { height:20px; width:auto; display:block; }

.menu-right {
	display:flex;
	align-items:center;
	justify-content:flex-end; /* nav on right */
	flex:1;
	position: relative; /* anchor mobile dropdown */
}
.menu-right .menu {
	display:flex;
	flex-wrap:wrap;
	align-items:center;
	justify-content:flex-end;
}
.menu a {
	display:block;
	color:#fff; /* requested text color */
	padding:12px 14px;
	text-decoration:none;
	
	background:#337ab7;
	border:0;
	cursor:pointer;
}
.menu .dropbtn a {
    display:block;
	color:#fff; /* requested text color */
	padding:12px 14px;
	text-decoration:none;
	
	background: transparent;
	border:0;
	cursor:pointer;
}
.menu .item { position:relative; }
.menu .dropdown-content {
	position:absolute;
	right:0; left:auto;
	
	background:#e3f0fb; /* light blue dropdown */
	border:1px solid #c9def2;
	border-radius:4px;
	box-shadow:0 2px 8px rgba(0,0,0,0.08);
	z-index:9999;
	display:none;
}
.menu .dropdown-content a { color:#0f1a2b; background: transparent; border-bottom: 1px solid #d7d5d5; }
.menu .item:hover { background: rgba(255,255,255,0.12) !important; }
.menu .dropdown-content a:hover {
	background: #51a4ecff !important;
	color:#fff !important;
	transition: background 0.2s;
}
.menu .item:hover .dropdown-content { display:block; }

/* Mobile hamburger menu */
.menu-icon {
	display: none;
	font-size: 20px;
	cursor: pointer;
	padding: 12px 16px;
	color: #fff; /* white hamburger icon */
}

/* Mobile responsive */
@media (max-width: 600px) {
	.menu-icon {
		display: block;
	}
	.menu-right .menu {
		display: none;
		position: absolute;
		top: 100%;
		left: 0;
		right: 0;
		background: #337ab7; /* same blue for mobile menu */
		flex-direction: column;
		box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		z-index: 1000;
	}
	.menu-right .menu.show {
		display: flex;
	}
	.menu-right .menu .item {
		width: 100%;
		text-align: left;
	}
	.menu-right .menu .dropdown-content {
		position: static;
		display: none;
		box-shadow: none;
		border: none;
		background: #e3f0fb;
	}
	.menu-right .menu .item:hover .dropdown-content {
		display: block;
	}
}
</style>

<div class="menu-container">
	<div class="menu-left">
		<a href="loading.php?pg=index.php" class="menu-logo">
			<img src="images/logo-v1.png" alt="Adrescue Logo">
		</a>
	</div>
	<div class="menu-right">
		<div class="menu" id="myMenu">
             <?php if($_SESSION['user_ty']=='acc') { ?>
             <a href="loading.php?pg=index-acc.php" class="item"><i class="fa fa-dashboard"></i> Home</a>
             <a href="loading.php?pg=invoice-outstanding.php" class="item"><i class="fa fa-credit-card"></i> Outstandings</a>
             <a href="budget4.php" class="item"><i class="fa fa-credit-card"></i> Budget</a>
             <a href="cashflow-2025.php" class="item"><i class="fa fa-credit-card"></i> Cashflow</a>
             <a href="cards.php" class="item"><i class="fa fa-credit-card"></i> Cards</a>
             <div class="item">
                <a class="dropbtn"><i class="fa fa-line-chart"></i> Ads Reports <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="dashboard.php?page=multi-client">Multi-Account Dashboard</a>
                    <a href="dashboard-clients.php">Clients Dashboard</a>
                    <a href="invoice-filter.php">Email Reports</a>
                </div>
            </div>           
             <div class="item">
                <a class="dropbtn"><i class="fa fa-file-text-o"></i> Invoice <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="invoice.php">Send</a>
                    <a href="loading.php?pg=invoice-list-filter.php">Sent</a>
                    <a href="loading.php?pg=invoice-list.php">Sent (Month View)</a>
                    <a href="invoice-meta.php">Meta</a>
                </div>
            </div>
            <?php } 
             if($_SESSION['user_ty']=='ads') { ?>
            <div class="item">
                <a class="dropbtn"><i class="fa fa-dashboard"></i> Home <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="loading.php?pg=index.php">Home</a>
                    <a href="dashboard-clients.php">Clients Dashboard</a>
                    <a href="deliverable.php">Deliverables</a>
                    <a href="checklist.php">Ads Overview</a>
                </div>
            </div>

            <div class="item">
                <a class="dropbtn"><i class="fa fa-line-chart"></i> Reports <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="dashboard-clients.php">Clients Dashboard</a>
                    <a href="dashboard.php?page=multi-client">Multi-Account Dashboard</a>
                    <a href="dashboard.php?page=multi-client3">Multi-Account Multi-daterange</a>
                    <a href="dashboard.php?page=multi-client2">Multi-Account Dashboard - V2</a>
                    <a href="dashboard.php?page=management">Management Dashboard</a>
                    <a href="dashboard.php?page=camp-reports">Campaign-wise Report</a>
                    <a href="dashboard.php?page=adset-reports">AdSet-wise Report</a>
                    <a href="dashboard.php?page=adv-targeting">Similar Audience Ads</a>
                    <a href="dashboard.php?page=audit">Audit Ad Account</a>
                    <a href="topup.php">Topup Calculator</a>
                    <a href="dashboard.php?page=troubleshoot">Troubleshoot</a>
                    <a href="invoice-filter.php">Email Reports</a>
                    <a href="ads-report-summary.php">Ads Summary - Download Report</a>
                    <a href="pixcel-check.php">Pixel code checker</a>  
                    <a href="post-promotion.php">Post Automation</a>
                    <a href="kpi.php">KPI</a>
                    <a href="dashboard.php?page=da-vlookup">DA - Vlookup</a>
                    <a href="RLD-leads-merge.php">RLD - Leads</a>
                </div>
            </div>

            <div class="item">
                <a class="dropbtn"><i class="fa fa-credit-card"></i> Budget <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="budget4.php">Budget</a>
                    <a href="budget-add-wa.php">WA Alert Budget</a>
                </div>
            </div>
            <div class="item">
                <a class="dropbtn"><i class="fa fa-line-chart"></i> Automation <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="post-promotion.php">Post Promotion</a>
                    <a href="audiences.php">Custom Audiences</a>     
                    <a href="https://stage.adrescue.in/home">AdTracker</a> 
                </div>
            </div>
            <div class="item">
                <a class="dropbtn"><i class="fa fa-globe"></i> Platforms <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="leads-acc.php">Meta - Leads</a>
                    <a href="pages.php">Meta - Pages</a>
                    <a href="invoice-meta.php">Meta - Invoice</a>
                    <a href="ad-accounts.php">Meta - Ad Accounts</a>
                    <a href="ad-accounts-g.php">Google - Ad Accounts</a>
                    <a href="ad-accounts-in.php">LinkedIn - Ad Accounts</a>
                    <a href="ad-accounts-ta.php">Taboola - Ad Accounts</a>
                </div>
            </div>
            <a href="loading.php?pg=leads-acc.php" class="item"><i class="fa fa-folder-open"></i> Leads</a>

            <div class="item">
                <a class="dropbtn"><i class="fa fa-calendar-check-o"></i> Schedule <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="budget.php">Ads Budget</a>
                    <a href="ads-report-weekly.php">Weekly Report</a>
                    <a href="ads-report-summary.php">Summary Report</a>
                </div>
            </div>
            <?php }  ?>
            <div class="item">
                <a class="dropbtn"><i class="fa fa-cog"></i></a>
                <div class="dropdown-content">
                    <a href="users.php">Users</a>
                    <a href="user-log.php">User Logs</a>
                    <a href="loading.php?pg=fb-login.php?update=1"> Meta Login (Update)</a>
                    <a href="loading.php?pg=linkedin-login.php?update=1"> LI Login (Update)</a>
                    <a href="loading.php?pg=google-sheets-api/callback.php?uid=<?php  echo $_SESSION['uid'];?>"> Googlesheet (Update)</a>
                    <a href="#" onclick="navigator.clipboard.writeText('<?php echo $access_token; ?>'); alert('Copied!'); return false;">Copy Access Token</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
		</div>
		<a href="javascript:void(0);" class="menu-icon" onclick="toggleMenu()">
			<i class="fa fa-bars"></i>
		</a>
	</div>
</div>

<script>
function toggleMenu() {
	var menu = document.getElementById("myMenu");
	if (menu.classList.contains("show")) {
		menu.classList.remove("show");
	} else {
		menu.classList.add("show");
	}
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
	var menu = document.getElementById("myMenu");
	var menuIcon = document.querySelector('.menu-icon');
	if (!menu.contains(event.target) && !menuIcon.contains(event.target)) {
		menu.classList.remove("show");
	}
});
</script>
<?php } ?>
<?php  if(isset($_GET['menu']) && $_GET['menu']=='hide') { ?><style>body { padding-top: 0px; }</style><?php } ?>
