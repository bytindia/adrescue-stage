
<?php  
if(isset($_GET['new-menu'])) { 
    ?><link href="css/menu-hamburger.css" rel="stylesheet"><?php
    include 'menu-hamburger.php';
} else {
  $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$access_token = $row['access_token']; 
  
  $log_user = isset($all_user[$_SESSION['user_id']]) ? trim($all_user[$_SESSION['user_id']]) : '';
  $firstLetter = !empty($log_user) ? strtoupper(substr($log_user, 0, 1)) : 'A';
  
  if(!isset($_GET['menu'])) { 
  
?>



<div class="menu-container">
	<div class="menu-left">
		<a href="loading.php?pg=index.php" class="menu-logo">
			<img src="images/adRes-w.png"  alt="AdRescue Logo">
		</a>
	</div>
	<div class="menu-right">
		<div class="menu" id="myMenu">
             <?php if($_SESSION['user_ty']=='acc') { ?>
             <a href="loading.php?pg=index-acc.php" class="item"><i class="fa fa-dashboard"></i> Home</a>
             <a href="dashboard.php?page=accounts" class="item"><i class="fa fa-dashboard"></i> Financials</a>
             <a href="loading.php?pg=invoice-outstanding.php" class="item"><i class="fa fa-credit-card"></i> Outstandings</a>
             <a href="budget4.php" class="item"><i class="fa fa-credit-card"></i> Budget</a>
             <a href="cashflow-2025.php" class="item"><i class="fa fa-credit-card"></i> Cashflow</a>
             <a href="cards.php" class="item"><i class="fa fa-credit-card"></i> Cards</a>
             <a href="dashboard.php?page=client-performance" class="item"><i class="fa fa-credit-card"></i> Client Performance</a>
             <div class="item">
                <a class="dropbtn"><i class="fa fa-line-chart"></i> Ad Reports <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="dashboard.php?page=client-performance">Client Performance</a>
                    <a href="dashboard.php?page=client-performance-roas">Client Performance - ROAS SV</a>
                    <a href="dashboard-clients.php">Clients Dashboard</a>
                    <a href="dashboard.php?page=multi-client">Multi-Client Dashboard</a>
                    <a href="dashboard.php?page=multi-client3">Multi-Client & daterange</a>
                    <a href="dashboard.php?page=multi-client2">Multi-Client Dashboard - V2</a>
                    <a href="dashboard.php?page=management">Management Dashboard</a>
                    <a href="dashboard.php?page=camp-reports">Campaign-wise Report</a>
                    <a href="dashboard.php?page=adset-reports">AdSet-wise Report</a>
                    <a href="dashboard.php?page=adv-targeting">Similar Audience Ads</a>
                    <a href="dashboard.php?page=audit">Audit Ad Account</a>
                    <a href="topup.php">Topup Calculator</a>
                    <a href="dashboard.php?page=troubleshoot">Troubleshoot</a>
                    <a href="invoice-filter.php">Email Reports</a>
                    <a href="ads-report-summary.php">Ads Summary - Download</a>
                    <a href="pixcel-check.php">Pixel code checker</a>  
                    <a href="post-promotion.php">Post Automation</a>
                    <a href="kpi.php">KPI</a>
                    <a href="dashboard.php?page=da-vlookup">DA - Vlookup</a>
                    <a href="RLD-leads-merge.php">RLD - Leads</a>
                </div>
            </div>  
            <div class="item">
                <a class="dropbtn"><i class="fa fa-folder-open"></i> Leads <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="loading.php?pg=leads-acc.php">Leads - Setup</a>
                    <a href="dashboard.php?page=lead-placements">Leads Placements</a>
                    <a href="dashboard.php?page=feedback">Leads Feedback Dashboard</a>
                    <a href="dashboard.php?page=lead-analysis">Leads Analysis - ChatGPT</a>
                    <a href="dashboard.php?page=lead-download">Leads Download - Meta</a>
                    <a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a>
                </div>
            </div>         
             <div class="item">
                <a class="dropbtn"><i class="fa fa-file-text-o"></i> Invoice <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="invoice.php">Send Invoice</a>
                    <a href="loading.php?pg=invoice-list-filter.php">Sent Items</a>
                    <a href="loading.php?pg=invoice-list.php">Sent : Detailed View</a>
                    <a href="loading.php?pg=invoice-outstanding.php" class="item"><i class="fa fa-credit-card"></i> Outstandings</a>
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
                <a class="dropbtn"><i class="fa fa-line-chart"></i> Ad Reports <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="dashboard.php?page=client-performance">Client Performance</a>
                    <a href="dashboard.php?page=client-performance-roas">Client Performance - ROAS SV</a>
                    <a href="dashboard-clients.php">Clients Dashboard</a>
                    <a href="dashboard.php?page=multi-client">Multi-Client Dashboard</a>
                    <a href="dashboard.php?page=multi-client3">Multi-Client & daterange</a>
                    <a href="dashboard.php?page=multi-client2">Multi-Client Dashboard - V2</a>
                    <a href="dashboard.php?page=management">Management Dashboard</a>
                    <a href="dashboard.php?page=camp-reports">Campaign-wise Report</a>
                    <a href="dashboard.php?page=adset-reports">AdSet-wise Report</a>
                    <a href="dashboard.php?page=adv-targeting">Similar Audience Ads</a>
                    <a href="dashboard.php?page=audit">Audit Ad Account</a>
                    <a href="topup.php">Topup Calculator</a>
                    <a href="dashboard.php?page=troubleshoot">Troubleshoot</a>
                    <a href="invoice-filter.php">Email Reports</a>
                    <a href="ads-report-summary.php">Ads Summary - Download</a>
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
                <a class="dropbtn"><i class="fa fa-globe"></i> Channels <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="leads-acc.php">Meta - Leads</a>
                    <a href="pages.php">Meta - Pages</a>
                    <a href="ad-accounts.php">Meta - Ad Accounts</a>
                    <a href="ad-accounts-g.php">Google - Ad Accounts</a>
                    <a href="ad-accounts-in.php">LinkedIn - Ad Accounts</a>
                    <a href="ad-accounts-ta.php">Taboola - Ad Accounts</a>
                    <a href="invoice-meta.php">Meta - Invoice</a>
                </div>
            </div>
            <!--<a href="loading.php?pg=leads-acc.php" class="item"><i class="fa fa-folder-open"></i> Leads</a>-->
            <div class="item">
                <a class="dropbtn"><i class="fa fa-folder-open"></i> Leads <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="loading.php?pg=leads-acc.php">Leads - Setup</a>
                    <a href="dashboard.php?page=lead-placements">Leads Placements</a>
                    <a href="dashboard.php?page=feedback">Leads Feedback Dashboard</a>
                    <a href="dashboard.php?page=lead-analysis">Leads Analysis - ChatGPT</a>
                    <a href="dashboard.php?page=lead-download">Leads Download - Meta</a>
                    <a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a>
                </div>
            </div>
            
             <div class="item">
                <a class="dropbtn"><i class="fa fa-folder-open"></i> Dashboard <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    
                    <a href="dashboard.php?page=sagehil-lead-dash">Sagehill</a>
                    <a href="https://byt.ink/soundsgood/">Soundsgood</a>
                    <a href="dashboard.php?page=iyra-lead-dash">iYRA</a>
                </div>
               
            </div>
            
            <div class="item">
                <a class="dropbtn"><i class="fa fa-calendar-check-o"></i> Schedule <i class="fa fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="budget.php">Ads Budget</a>
                    <a href="ads-report-weekly.php">Weekly Report</a>
                    <a href="ads-report-summary.php">Summary Report</a>
                </div>
            </div>
            <a href="invoice.php" class="item"><i class="fa fa-dashboard"></i> Invoice</a>
            <?php }  ?>
            <div class="item notif-bell-wrap">
                <a href="javascript:void(0);" class="dropbtn notif-bell-btn" id="notifBellBtn" title="Notifications">
                    <i class="fa fa-bell-o"></i>
                    <span class="notif-badge" id="notifBadge">99+</span>
                </a>
                <div class="dropdown-content notif-dropdown" id="notifDropdown">
                    <div class="notif-header">Notifications</div>
                    <div class="notif-list" id="notifList"></div>
                    <div class="notif-footer">
                        <div class="notif-load-more" id="notifLoadMore" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
                        <button type="button" class="btn btn-success btn-sm notif-view-more" id="notifViewMore">View more</button>
                    </div>
                </div>
            </div>
            <div class="item">
                <a class="dropbtn"><span class="profile-icon"><?php echo $firstLetter; ?></span> <i class="fa fa-caret-down"></i></a>
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
let lastScrollTop = 0;
let isScrolling = false;
let scrollTimeout;

function toggleMenu() {
	var menu = document.getElementById("myMenu");
	if (menu.classList.contains("show")) {
		menu.classList.remove("show");
	} else {
		menu.classList.add("show");
	}
}

// Scroll detection for hiding/showing menu
function handleScroll() {
	if (isScrolling) return;
	
	isScrolling = true;
	requestAnimationFrame(function() {
		const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
		const menuContainer = document.querySelector('.menu-container');
		const menu = document.getElementById("myMenu");
		
		// Only hide/show menu if scrolled more than 50px
		if (Math.abs(scrollTop - lastScrollTop) > 50) {
			if (scrollTop > lastScrollTop && scrollTop > 100) {
				// Scrolling down - hide menu
				menuContainer.classList.add('menu-hidden');
				menu.classList.remove("show");
			} else {
				// Scrolling up - show menu
				menuContainer.classList.remove('menu-hidden');
			}
			lastScrollTop = scrollTop;
		}
		
		isScrolling = false;
	});
}

// Throttled scroll event listener
window.addEventListener('scroll', function() {
	clearTimeout(scrollTimeout);
	scrollTimeout = setTimeout(handleScroll, 10);
}, { passive: true });

// Close menu when clicking outside
document.addEventListener('click', function(event) {
	var menu = document.getElementById("myMenu");
	var menuIcon = document.querySelector('.menu-icon');
	if (!menu.contains(event.target) && !menuIcon.contains(event.target)) {
		menu.classList.remove("show");
	}
	// Close notification dropdown when clicking outside
	var notifWrap = document.querySelector('.notif-bell-wrap');
	var notifDropdown = document.getElementById('notifDropdown');
	if (notifWrap && notifDropdown && !notifWrap.contains(event.target)) {
		notifWrap.classList.remove('notif-open');
	}
});

// Notification bell - load only on click
(function() {
	var btn = document.getElementById('notifBellBtn');
	var wrap = document.querySelector('.notif-bell-wrap');
	var dropdown = document.getElementById('notifDropdown');
	var list = document.getElementById('notifList');
	var viewMore = document.getElementById('notifViewMore');
	var loadMore = document.getElementById('notifLoadMore');
	var offset = 0;
	var loading = false;
	var hasMore = true;

	function getNotifUrl() {
		var p = window.location.pathname;
		var base = p.substring(0, p.lastIndexOf('/') + 1) || '/';
		return base + 'ajax-notifications.php';
	}

	function loadNotifs(append) {
		if (loading) return;
		loading = true;
		if (!append) {
			list.innerHTML = '<div class="notif-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
			offset = 0;
		} else {
			if (loadMore) loadMore.style.display = 'block';
			if (viewMore) viewMore.disabled = true;
		}

		var url = getNotifUrl() + '?limit=10&offset=' + offset;
		var req = new XMLHttpRequest();
		req.open('GET', url);
		req.onload = function() {
			loading = false;
			if (loadMore) loadMore.style.display = 'none';
			try {
				var r = JSON.parse(req.responseText);
				if (!append) list.innerHTML = '';
				if (r.html) list.insertAdjacentHTML(append ? 'beforeend' : 'afterbegin', r.html);
				else if (!append) list.innerHTML = '<div class="notif-empty">No notifications</div>';
				offset += r.count || 0;
				hasMore = r.has_more;
				if (viewMore) {
					viewMore.style.display = hasMore ? 'block' : 'none';
					viewMore.disabled = false;
				}
				if (!append) {
					var badge = document.getElementById('notifBadge');
					if (badge) {
						if (r.total > 0) { badge.textContent = r.total > 99 ? '99+' : r.total; badge.style.display = 'inline-block'; }
						else { badge.style.display = 'none'; }
					}
				}
			} catch(e) { if (!append) list.innerHTML = '<div class="notif-empty">Unable to load</div>'; if (viewMore) viewMore.disabled = false; }
		};
		req.onerror = function() {
			loading = false;
			if (loadMore) loadMore.style.display = 'none';
			if (!append) list.innerHTML = '<div class="notif-empty">Error loading</div>';
			if (viewMore) viewMore.disabled = false;
		};
		req.send();
	}

	if (btn && wrap && dropdown && list) {
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			wrap.classList.toggle('notif-open');
			if (wrap.classList.contains('notif-open') && list.children.length === 0) loadNotifs(false);
		});
		dropdown.addEventListener('click', function(e) { e.stopPropagation(); });
	}
	if (viewMore) {
		viewMore.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			if (hasMore && !loading) loadNotifs(true);
		});
	}
})();

// Show menu when hamburger is clicked (override scroll behavior temporarily)
document.querySelector('.menu-icon').addEventListener('click', function() {
	document.querySelector('.menu-container').classList.remove('menu-hidden');
});
</script>
<?php } ?>
<?php  if(isset($_GET['menu']) && $_GET['menu']=='hide') { ?><style>body { padding-top: 0px; }</style><?php } } ?>
