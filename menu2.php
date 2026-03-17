<?php
$query = "SELECT tbl_id, name, fb_id, g_id, access_token, g_token, g_refresh_token, g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token'] ?? '';

$log_user = isset($all_user[$_SESSION['user_id']]) ? trim($all_user[$_SESSION['user_id']]) : '';
$firstLetter = !empty($log_user) ? strtoupper(substr($log_user, 0, 1)) : 'A';

if (!isset($_GET['menu'])) {
?>


<nav class="mega-menu-nav">
  <a href="loading.php?pg=index.php" class="mega-menu-logo" aria-label="AdRescue Home">
    <img src="images/adRes-b.png" alt="AdRescue" height="25">
  </a>
  <button class="hamburger" type="button" aria-label="Toggle menu" aria-expanded="false">
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
  </button>

  <ul class="mega-menu-list desktop-nav-list">
    <?php if ($_SESSION['user_ty'] == 'acc') { ?>
    <!-- ACC: Accounting / Financials navigation -->
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="loading.php?pg=index-acc.php"><span class="nav-icon"><i class="fa fa-dashboard"></i></span><span class="nav-label">Home</span></a>
      <div class="mega-dropdown"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Home</h4><ul>
          <li><a href="loading.php?pg=index-acc.php">Home</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item"><a href="dashboard.php?page=accounts"><span class="nav-icon"><i class="fa fa-dashboard"></i></span><span class="nav-label">Financials</span></a></li>
    <li class="mega-menu-item"><a href="loading.php?pg=invoice-outstanding.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span><span class="nav-label">Outstandings</span></a></li>
    <li class="mega-menu-item"><a href="budget4.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span><span class="nav-label">Budget</span></a></li>
    <li class="mega-menu-item"><a href="cashflow-2025.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span><span class="nav-label">Cashflow</span></a></li>
    <li class="mega-menu-item"><a href="cards.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span><span class="nav-label">Cards</span></a></li>
    <li class="mega-menu-item"><a href="dashboard.php?page=client-performance"><span class="nav-icon"><i class="fa fa-credit-card"></i></span><span class="nav-label">Client Perf</span></a></li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-line-chart"></i></span><span class="nav-label">Ad Reports</span></a>
      <div class="mega-dropdown mega-dropdown-wide"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Dashboards</h4><ul>
          <li><a href="dashboard.php?page=client-performance">Client Performance</a></li>
          <li><a href="dashboard-clients.php">Clients Dashboard</a></li>
          <li><a href="dashboard.php?page=multi-client">Multi-Client Dashboard</a></li>
          <li><a href="dashboard.php?page=multi-client3">Multi-Client & daterange</a></li>
          <li><a href="dashboard.php?page=multi-client2">Multi-Client Dashboard - V2</a></li>
          <li><a href="dashboard.php?page=management">Management Dashboard</a></li>
        </ul></div>
        <div class="mega-column"><h4>Reports</h4><ul>
          <li><a href="dashboard.php?page=camp-reports">Campaign-wise Report</a></li>
          <li><a href="dashboard.php?page=adset-reports">AdSet-wise Report</a></li>
          <li><a href="dashboard.php?page=adv-targeting">Similar Audience Ads</a></li>
          <li><a href="dashboard.php?page=audit">Audit Ad Account</a></li>
          <li><a href="ads-report-summary.php">Ads Summary - Download</a></li>
          <li><a href="RLD-leads-merge.php">RLD - Leads</a></li>
        </ul></div>
        <div class="mega-column"><h4>Tools</h4><ul>
          <li><a href="topup.php">Topup Calculator</a></li>
          <li><a href="dashboard.php?page=troubleshoot">Troubleshoot</a></li>
          <li><a href="invoice-filter.php">Email Reports</a></li>
          <li><a href="pixcel-check.php">Pixel code checker</a></li>
          <li><a href="post-promotion.php">Post Automation</a></li>
          <li><a href="kpi.php">KPI</a></li>
          <li><a href="dashboard.php?page=da-vlookup">DA - Vlookup</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-folder-open"></i></span><span class="nav-label">Leads</span></a>
      <div class="mega-dropdown mega-dropdown-two-col"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Setup &amp; Feedback</h4><ul>
          <li><a href="loading.php?pg=leads-acc.php">Leads - Setup</a></li>
          <li><a href="dashboard.php?page=lead-placements">Leads Placements</a></li>
          <li><a href="dashboard.php?page=feedback">Leads Feedback Dashboard</a></li>
        </ul></div>
        <div class="mega-column"><h4>Analysis &amp; Data</h4><ul>
          <li><a href="dashboard.php?page=lead-analysis">Leads Analysis - ChatGPT</a></li>
          <li><a href="dashboard.php?page=lead-download">Leads Download - Meta</a></li>
          <li><a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-file-text-o"></i></span><span class="nav-label">Invoice</span></a>
      <div class="mega-dropdown mega-dropdown-narrow"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Invoice</h4><ul>
          <li><a href="invoice.php">Send</a></li>
          <li><a href="loading.php?pg=invoice-list-filter.php">Sent</a></li>
          <li><a href="loading.php?pg=invoice-list.php">Sent : Detailed View</a></li>
          <li><a href="loading.php?pg=invoice-outstanding.php">Outstandings</a></li>
          <li><a href="invoice-meta.php">Meta</a></li>
        </ul></div></div></div>
    </li>
    <?php } ?>

    <?php if ($_SESSION['user_ty'] == 'ads') { ?>
    <!-- ADS: Ads / Marketing navigation -->
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="loading.php?pg=index.php"><span class="nav-icon"><i class="fa fa-home"></i></span><span class="nav-label">Home</span></a>
      <div class="mega-dropdown"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Overview</h4><ul>
          <li><a href="loading.php?pg=index.php">Home</a></li>
          <li><a href="dashboard-clients.php">Clients Dashboard</a></li>
          <li><a href="deliverable.php">Deliverables</a></li>
          <li><a href="checklist.php">Ads Overview</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-line-chart"></i></span><span class="nav-label">Ad Reports</span></a>
      <div class="mega-dropdown mega-dropdown-wide"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Dashboards</h4><ul>
          <li><a href="dashboard.php?page=client-performance">Client Performance</a></li>
          <li><a href="dashboard-clients.php">Clients Dashboard</a></li>
          <li><a href="dashboard.php?page=multi-client">Multi-Client</a></li>
          <li><a href="dashboard.php?page=multi-client3">Multi-Client & daterange</a></li>
          <li><a href="dashboard.php?page=multi-client2">Multi-Client V2</a></li>
          <li><a href="dashboard.php?page=management">Management Dashboard</a></li>
        </ul></div>
        <div class="mega-column"><h4>Reports</h4><ul>
          <li><a href="dashboard.php?page=camp-reports">Campaign-wise</a></li>
          <li><a href="dashboard.php?page=adset-reports">AdSet-wise</a></li>
          <li><a href="dashboard.php?page=adv-targeting">Similar Audience Ads</a></li>
          <li><a href="dashboard.php?page=audit">Audit Ad Account</a></li>
          <li><a href="ads-report-summary.php">Ads Summary Download</a></li>
          <li><a href="RLD-leads-merge.php">RLD - Leads</a></li>
        </ul></div>
        <div class="mega-column"><h4>Tools</h4><ul>
          <li><a href="topup.php">Topup Calculator</a></li>
          <li><a href="dashboard.php?page=troubleshoot">Troubleshoot</a></li>
          <li><a href="invoice-filter.php">Email Reports</a></li>
          <li><a href="pixcel-check.php">Pixel checker</a></li>
          <li><a href="post-promotion.php">Post Automation</a></li>
          <li><a href="kpi.php">KPI</a></li>
          <li><a href="dashboard.php?page=da-vlookup">DA - Vlookup</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-credit-card"></i></span><span class="nav-label">Budget</span></a>
      <div class="mega-dropdown mega-dropdown-narrow"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Budget</h4><ul>
          <li><a href="budget4.php">Budget</a></li>
          <li><a href="budget-add-wa.php">WA Alert Budget</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-folder-open"></i></span><span class="nav-label">Leads</span></a>
      <div class="mega-dropdown mega-dropdown-two-col"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Setup &amp; Feedback</h4><ul>
          <li><a href="loading.php?pg=leads-acc.php">Leads - Setup</a></li>
          <li><a href="dashboard.php?page=lead-placements">Leads Placements</a></li>
          <li><a href="dashboard.php?page=feedback">Leads Feedback</a></li>
        </ul></div>
        <div class="mega-column"><h4>Analysis &amp; Data</h4><ul>
          <li><a href="dashboard.php?page=lead-analysis">Leads Analysis - ChatGPT</a></li>
          <li><a href="dashboard.php?page=lead-download">Leads Download - Meta</a></li>
          <li><a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-globe"></i></span><span class="nav-label">Channels</span></a>
      <div class="mega-dropdown mega-dropdown-two-col"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Meta &amp; Google</h4><ul>
          <li><a href="leads-acc.php">Meta - Leads</a></li>
          <li><a href="pages.php">Meta - Pages</a></li>
          <li><a href="ad-accounts.php">Meta - Ad Accounts</a></li>
          <li><a href="ad-accounts-g.php">Google - Ad Accounts</a></li>
        </ul></div>
        <div class="mega-column"><h4>Other &amp; Invoice</h4><ul>
          <li><a href="ad-accounts-in.php">LinkedIn - Ad Accounts</a></li>
          <li><a href="ad-accounts-ta.php">Taboola - Ad Accounts</a></li>
          <li><a href="invoice-meta.php">Meta - Invoice</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-cogs"></i></span><span class="nav-label">Automation</span></a>
      <div class="mega-dropdown mega-dropdown-narrow"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Automation</h4><ul>
          <li><a href="post-promotion.php">Post Promotion</a></li>
          <li><a href="audiences.php">Custom Audiences</a></li>
          <li><a href="https://stage.adrescue.in/home" target="_blank">AdTracker</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-calendar"></i></span><span class="nav-label">Schedule</span></a>
      <div class="mega-dropdown mega-dropdown-narrow"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Schedule</h4><ul>
          <li><a href="budget.php">Ads Budget</a></li>
          <li><a href="ads-report-weekly.php">Weekly Report</a></li>
          <li><a href="ads-report-summary.php">Summary Report</a></li>
        </ul></div></div></div>
    </li>
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><i class="fa fa-dashboard"></i></span><span class="nav-label">Dashboards</span></a>
      <div class="mega-dropdown mega-dropdown-narrow"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Client</h4><ul>
          <li><a href="dashboard.php?page=sagehil-lead-dash">Sagehill</a></li>
        </ul></div></div></div>
    </li>
    <?php } ?>

    <!-- Profile / Account (shared for both acc and ads) -->
    <li class="mega-menu-item mega-menu-has-dropdown">
      <a href="#"><span class="nav-icon"><?php echo htmlspecialchars($firstLetter); ?></span><span class="nav-label">Account</span></a>
      <div class="mega-dropdown mega-dropdown-narrow"><div class="mega-dropdown-content">
        <div class="mega-column"><h4>Account</h4><ul>
          <li><a href="users.php">Users</a></li>
          <li><a href="user-log.php">User Logs</a></li>
          <li><a href="loading.php?pg=fb-login.php?update=1">Meta Login (Update)</a></li>
          <li><a href="loading.php?pg=linkedin-login.php?update=1">LI Login (Update)</a></li>
          <li><a href="loading.php?pg=google-sheets-api/callback.php?uid=<?php echo $_SESSION['uid']; ?>">Googlesheet (Update)</a></li>
          <li><a href="#" onclick="navigator.clipboard.writeText('<?php echo addslashes($access_token); ?>'); alert('Copied!'); return false;">Copy Access Token</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul></div></div></div>
    </li>
  </ul>

  <!-- Mobile overlay -->
  <div class="mobile-menu-overlay">
    <div class="mobile-menu-panels">
      <a href="loading.php?pg=index.php" class="mobile-menu-logo" aria-label="AdRescue Home">
        <img src="images/adRes-b.png" alt="AdRescue" height="25">
      </a>
      <button class="mobile-close" type="button" aria-label="Close menu">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <div class="mobile-menu-left">
        <ul class="mega-menu-list mobile-nav-list">
          <?php if ($_SESSION['user_ty'] == 'acc') { ?>
          <li class="mega-menu-item mega-menu-has-dropdown active" data-mobile-panel="acc-home"><a href="#">Home <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item"><a href="dashboard.php?page=accounts">Financials</a></li>
          <li class="mega-menu-item"><a href="loading.php?pg=invoice-outstanding.php">Outstandings</a></li>
          <li class="mega-menu-item"><a href="budget4.php">Budget</a></li>
          <li class="mega-menu-item"><a href="cashflow-2025.php">Cashflow</a></li>
          <li class="mega-menu-item"><a href="cards.php">Cards</a></li>
          <li class="mega-menu-item"><a href="dashboard.php?page=client-performance">Client Perf</a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="acc-reports"><a href="#">Ad Reports <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="acc-leads"><a href="#">Leads <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="acc-invoice"><a href="#">Invoice <span class="chevron">▼</span></a></li>
          <?php } ?>
          <?php if ($_SESSION['user_ty'] == 'ads') { ?>
          <li class="mega-menu-item mega-menu-has-dropdown active" data-mobile-panel="ads-home"><a href="#">Home <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-reports"><a href="#">Ad Reports <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-budget"><a href="#">Budget <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-leads"><a href="#">Leads <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-channels"><a href="#">Channels <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-automation"><a href="#">Automation <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-schedule"><a href="#">Schedule <span class="chevron">▼</span></a></li>
          <li class="mega-menu-item mega-menu-has-dropdown" data-mobile-panel="ads-dashboards"><a href="#">Dashboards <span class="chevron">▼</span></a></li>
          <?php } ?>
          <li class="mega-menu-item mega-menu-cta" data-mobile-panel="account"><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
      </div>
      <div class="mobile-menu-right">
        <?php if ($_SESSION['user_ty'] == 'ads') { ?>
        <div class="mobile-panel mobile-panel-ads-home active" id="mobile-panel-ads-home">
          <h4 class="mobile-section-label">OVERVIEW:</h4>
          <ul><li><a href="loading.php?pg=index.php">Home</a></li><li><a href="dashboard-clients.php">Clients Dashboard</a></li><li><a href="deliverable.php">Deliverables</a></li><li><a href="checklist.php">Ads Overview</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-reports" id="mobile-panel-ads-reports">
          <h4 class="mobile-section-label">AD REPORTS:</h4>
          <ul><li><a href="dashboard.php?page=client-performance">Client Performance</a></li><li><a href="dashboard-clients.php">Clients Dashboard</a></li><li><a href="dashboard.php?page=multi-client">Multi-Client</a></li><li><a href="dashboard.php?page=camp-reports">Campaign-wise</a></li><li><a href="dashboard.php?page=adset-reports">AdSet-wise</a></li><li><a href="ads-report-summary.php">Ads Summary</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-budget" id="mobile-panel-ads-budget">
          <h4 class="mobile-section-label">BUDGET:</h4>
          <ul><li><a href="budget4.php">Budget</a></li><li><a href="budget-add-wa.php">WA Alert Budget</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-leads" id="mobile-panel-ads-leads">
          <h4 class="mobile-section-label">LEADS:</h4>
          <ul><li><a href="loading.php?pg=leads-acc.php">Leads - Setup</a></li><li><a href="dashboard.php?page=lead-placements">Leads Placements</a></li><li><a href="dashboard.php?page=feedback">Leads Feedback</a></li><li><a href="dashboard.php?page=lead-analysis">Leads Analysis</a></li><li><a href="dashboard.php?page=lead-download">Leads Download</a></li><li><a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-channels" id="mobile-panel-ads-channels">
          <h4 class="mobile-section-label">CHANNELS:</h4>
          <ul><li><a href="leads-acc.php">Meta - Leads</a></li><li><a href="pages.php">Meta - Pages</a></li><li><a href="ad-accounts.php">Meta - Ad Accounts</a></li><li><a href="ad-accounts-g.php">Google - Ad Accounts</a></li><li><a href="ad-accounts-in.php">LinkedIn</a></li><li><a href="ad-accounts-ta.php">Taboola</a></li><li><a href="invoice-meta.php">Meta - Invoice</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-automation" id="mobile-panel-ads-automation">
          <h4 class="mobile-section-label">AUTOMATION:</h4>
          <ul><li><a href="post-promotion.php">Post Promotion</a></li><li><a href="audiences.php">Custom Audiences</a></li><li><a href="https://stage.adrescue.in/home" target="_blank">AdTracker</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-schedule" id="mobile-panel-ads-schedule">
          <h4 class="mobile-section-label">SCHEDULE:</h4>
          <ul><li><a href="budget.php">Ads Budget</a></li><li><a href="ads-report-weekly.php">Weekly Report</a></li><li><a href="ads-report-summary.php">Summary Report</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-ads-dashboards" id="mobile-panel-ads-dashboards">
          <h4 class="mobile-section-label">DASHBOARDS:</h4>
          <ul><li><a href="dashboard.php?page=sagehil-lead-dash">Sagehill</a></li></ul>
        </div>
        <?php } ?>
        <?php if ($_SESSION['user_ty'] == 'acc') { ?>
        <div class="mobile-panel mobile-panel-acc-home active" id="mobile-panel-acc-home">
          <h4 class="mobile-section-label">HOME:</h4>
          <ul><li><a href="loading.php?pg=index-acc.php">Home</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-acc-reports" id="mobile-panel-acc-reports">
          <h4 class="mobile-section-label">AD REPORTS:</h4>
          <ul><li><a href="dashboard.php?page=client-performance">Client Performance</a></li><li><a href="dashboard-clients.php">Clients Dashboard</a></li><li><a href="dashboard.php?page=camp-reports">Campaign-wise</a></li><li><a href="dashboard.php?page=adset-reports">AdSet-wise</a></li><li><a href="ads-report-summary.php">Ads Summary</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-acc-leads" id="mobile-panel-acc-leads">
          <h4 class="mobile-section-label">LEADS:</h4>
          <ul><li><a href="loading.php?pg=leads-acc.php">Leads - Setup</a></li><li><a href="dashboard.php?page=lead-placements">Leads Placements</a></li><li><a href="dashboard.php?page=feedback">Leads Feedback</a></li><li><a href="dashboard.php?page=lead-analysis">Leads Analysis</a></li><li><a href="dashboard.php?page=lead-download">Leads Download</a></li><li><a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a></li></ul>
        </div>
        <div class="mobile-panel mobile-panel-acc-invoice" id="mobile-panel-acc-invoice">
          <h4 class="mobile-section-label">INVOICE:</h4>
          <ul><li><a href="invoice.php">Send</a></li><li><a href="loading.php?pg=invoice-list-filter.php">Sent</a></li><li><a href="loading.php?pg=invoice-list.php">Sent : Detailed View</a></li><li><a href="loading.php?pg=invoice-outstanding.php">Outstandings</a></li><li><a href="invoice-meta.php">Meta</a></li></ul>
        </div>
        <?php } ?>
        <div class="mobile-panel mobile-panel-account" id="mobile-panel-account">
          <h4 class="mobile-section-label">ACCOUNT:</h4>
          <ul><li><a href="users.php">Users</a></li><li><a href="user-log.php">User Logs</a></li><li><a href="logout.php">Logout</a></li></ul>
        </div>
      </div>
    </div>
  </div>
</nav>

<script>
(function() {
  var hamburger = document.querySelector('.mega-menu-nav .hamburger');
  var overlay = document.querySelector('.mobile-menu-overlay');
  var mobileClose = document.querySelector('.mobile-close');
  if (!hamburger || !overlay) return;

  function openOverlay() {
    overlay.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function closeOverlay() {
    overlay.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', openOverlay);
  mobileClose && mobileClose.addEventListener('click', closeOverlay);
  overlay.addEventListener('click', function(e) { if (e.target === overlay) closeOverlay(); });

  // Mobile: left item click switches right panel
  document.querySelectorAll('.mobile-nav-list .mega-menu-has-dropdown, .mobile-nav-list .mega-menu-item:not(.mega-menu-cta)').forEach(function(item) {
    var a = item.querySelector('a');
    if (!a || item.classList.contains('mega-menu-cta')) return;
    a.addEventListener('click', function(e) {
      var panelId = item.dataset.mobilePanel;
      if (panelId) {
        e.preventDefault();
        document.querySelectorAll('.mobile-nav-list .mega-menu-item').forEach(function(i) { i.classList.remove('active'); });
        document.querySelectorAll('.mobile-panel').forEach(function(p) { p.classList.remove('active'); });
        item.classList.add('active');
        var panel = document.getElementById('mobile-panel-' + panelId);
        if (panel) panel.classList.add('active');
      }
    });
  });

  // Desktop: hover dropdowns
  var dropdownTimer;
  document.querySelectorAll('.desktop-nav-list .mega-menu-has-dropdown').forEach(function(item) {
    item.addEventListener('mouseenter', function() {
      clearTimeout(dropdownTimer);
      document.querySelectorAll('.desktop-nav-list .mega-menu-has-dropdown').forEach(function(i) { i.classList.remove('open'); });
      item.classList.add('open');
    });
    item.addEventListener('mouseleave', function() {
      dropdownTimer = setTimeout(function() { item.classList.remove('open'); }, 100);
    });
  });
})();
</script>
<?php } ?>
<?php if (isset($_GET['menu']) && $_GET['menu'] == 'hide') { ?>
<style>body { padding-top: 0px; }</style>
<?php } ?>
