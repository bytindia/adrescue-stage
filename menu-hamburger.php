<?php
/**
 * Hamburger sidebar menu - uses same navigation as menu-top.php
 * Fixed position while scrolling, responsive for mobile, tablet, desktop
 */
if (!isset($_SESSION['user_ty'])) {
  $_SESSION['user_ty'] = 'ads';
}
if (isset($conn)) {
  $at_query = "SELECT access_token FROM users WHERE tbl_id=2 LIMIT 1";
  $at_res = @mysqli_query($conn, $at_query);
  $at_row = $at_res ? mysqli_fetch_assoc($at_res) : null;
  $access_token = $at_row['access_token'] ?? '';
} else {
  $access_token = '';
}
$all_user = isset($all_user) ? $all_user : [];
$log_user = isset($_SESSION['user_id'], $all_user[$_SESSION['user_id']]) ? trim($all_user[$_SESSION['user_id']]) : '';
$firstLetter = !empty($log_user) ? strtoupper(substr($log_user, 0, 1)) : 'A';
$user_ty = $_SESSION['user_ty'] ?? 'ads';
?>
<button class="sidebar-menu-trigger" id="sidebarMenuTrigger" aria-label="Open menu" type="button">
  <span class="hamburger-icon">
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
  </span>
  <span class="close-icon"><i class="fa fa-times"></i></span>
</button>

<div class="sidebar-menu-overlay" id="sidebarMenuOverlay">
  <div class="sidebar-menu-panel">
    <div class="sidebar-menu-header">
      <span class="sidebar-menu-brand"><img src="images/adRes-b.png" alt="AdRescue" height="25"></span>
    </div>

    <?php if ($user_ty == 'acc') { ?>
    <!-- ACC navigation -->
    <ul class="sidebar-nav-list">
      <li class="sidebar-nav-item"><a href="loading.php?pg=index-acc.php"><span class="nav-icon"><i class="fa fa-dashboard"></i></span> Home</a></li>
      <li class="sidebar-nav-item"><a href="dashboard.php?page=accounts"><span class="nav-icon"><i class="fa fa-dashboard"></i></span> Financials</a></li>
      <li class="sidebar-nav-item"><a href="loading.php?pg=invoice-outstanding.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Outstandings</a></li>
      <li class="sidebar-nav-item"><a href="budget4.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Budget</a></li>
      <li class="sidebar-nav-item"><a href="cashflow-2025.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Cashflow</a></li>
      <li class="sidebar-nav-item"><a href="cards.php"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Cards</a></li>
      <li class="sidebar-nav-item"><a href="dashboard.php?page=client-performance"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Client Perf</a></li>
    </ul>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon orange"><i class="fa fa-line-chart"></i></span> Ad Reports <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="dashboard.php?page=client-performance">Client Performance</a></li>
          <li class="sidebar-sub-item"><a href="dashboard-clients.php">Clients Dashboard</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=multi-client">Multi-Client Dashboard</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=camp-reports">Campaign-wise</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=adset-reports">AdSet-wise</a></li>
          <li class="sidebar-sub-item"><a href="ads-report-summary.php">Ads Summary</a></li>
          <li class="sidebar-sub-item"><a href="topup.php">Topup Calculator</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=troubleshoot">Troubleshoot</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon green"><i class="fa fa-folder-open"></i></span> Leads <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="loading.php?pg=leads-acc.php">Leads - Setup</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-placements">Leads Placements</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=feedback">Leads Feedback</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-analysis">Leads Analysis</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-download">Leads Download</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon purple"><i class="fa fa-file-text-o"></i></span> Invoice <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="invoice.php">Send</a></li>
          <li class="sidebar-sub-item"><a href="loading.php?pg=invoice-list-filter.php">Sent</a></li>
          <li class="sidebar-sub-item"><a href="loading.php?pg=invoice-list.php">Sent : Detailed View</a></li>
          <li class="sidebar-sub-item"><a href="loading.php?pg=invoice-outstanding.php">Outstandings</a></li>
          <li class="sidebar-sub-item"><a href="invoice-meta.php">Meta</a></li>
        </ul>
      </div>
    </div>
    <?php } ?>

    <?php if ($user_ty == 'ads') { ?>
    <!-- ADS navigation -->
    <ul class="sidebar-nav-list">
      <li class="sidebar-nav-item"><a href="loading.php?pg=index.php"><span class="nav-icon"><i class="fa fa-home"></i></span> Home</a></li>
    </ul>
    <div class="sidebar-accordion-section expanded" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon orange"><i class="fa fa-home"></i></span> Overview <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="loading.php?pg=index.php">Home</a></li>
          <li class="sidebar-sub-item"><a href="dashboard-clients.php">Clients Dashboard</a></li>
          <li class="sidebar-sub-item"><a href="deliverable.php">Deliverables</a></li>
          <li class="sidebar-sub-item"><a href="checklist.php">Ads Overview</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon orange"><i class="fa fa-line-chart"></i></span> Ad Reports <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="dashboard.php?page=client-performance">Client Performance</a></li>
          <li class="sidebar-sub-item"><a href="dashboard-clients.php">Clients Dashboard</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=multi-client">Multi-Client</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=multi-client3">Multi-Client &amp; daterange</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=multi-client2">Multi-Client V2</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=management">Management Dashboard</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=camp-reports">Campaign-wise</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=adset-reports">AdSet-wise</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=adv-targeting">Similar Audience Ads</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=audit">Audit Ad Account</a></li>
          <li class="sidebar-sub-item"><a href="ads-report-summary.php">Ads Summary Download</a></li>
          <li class="sidebar-sub-item"><a href="RLD-leads-merge.php">RLD - Leads</a></li>
          <li class="sidebar-sub-item"><a href="topup.php">Topup Calculator</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=troubleshoot">Troubleshoot</a></li>
          <li class="sidebar-sub-item"><a href="invoice-filter.php">Email Reports</a></li>
          <li class="sidebar-sub-item"><a href="pixcel-check.php">Pixel checker</a></li>
          <li class="sidebar-sub-item"><a href="post-promotion.php">Post Automation</a></li>
          <li class="sidebar-sub-item"><a href="kpi.php">KPI</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=da-vlookup">DA - Vlookup</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon green"><i class="fa fa-credit-card"></i></span> Budget <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="budget4.php">Budget</a></li>
          <li class="sidebar-sub-item"><a href="budget-add-wa.php">WA Alert Budget</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon green"><i class="fa fa-folder-open"></i></span> Leads <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="loading.php?pg=leads-acc.php">Leads - Setup</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-placements">Leads Placements</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=feedback">Leads Feedback</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-analysis">Leads Analysis - ChatGPT</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-download">Leads Download - Meta</a></li>
          <li class="sidebar-sub-item"><a href="dashboard.php?page=lead-capi">Leads - CAPI Update</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon purple"><i class="fa fa-globe"></i></span> Channels <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="leads-acc.php">Meta - Leads</a></li>
          <li class="sidebar-sub-item"><a href="pages.php">Meta - Pages</a></li>
          <li class="sidebar-sub-item"><a href="ad-accounts.php">Meta - Ad Accounts</a></li>
          <li class="sidebar-sub-item"><a href="ad-accounts-g.php">Google - Ad Accounts</a></li>
          <li class="sidebar-sub-item"><a href="ad-accounts-in.php">LinkedIn - Ad Accounts</a></li>
          <li class="sidebar-sub-item"><a href="ad-accounts-ta.php">Taboola - Ad Accounts</a></li>
          <li class="sidebar-sub-item"><a href="invoice-meta.php">Meta - Invoice</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon purple"><i class="fa fa-cogs"></i></span> Automation <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="post-promotion.php">Post Promotion</a></li>
          <li class="sidebar-sub-item"><a href="audiences.php">Custom Audiences</a></li>
          <li class="sidebar-sub-item"><a href="https://stage.adrescue.in/home" target="_blank">AdTracker</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon purple"><i class="fa fa-calendar"></i></span> Schedule <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="budget.php">Ads Budget</a></li>
          <li class="sidebar-sub-item"><a href="ads-report-weekly.php">Weekly Report</a></li>
          <li class="sidebar-sub-item"><a href="ads-report-summary.php">Summary Report</a></li>
        </ul>
      </div>
    </div>
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon purple"><i class="fa fa-dashboard"></i></span> Dashboards <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="dashboard.php?page=sagehil-lead-dash">Sagehill</a></li>
        </ul>
      </div>
    </div>
    <?php } ?>

    <!-- Account (shared) -->
    <div class="sidebar-accordion-section" data-accordion>
      <button class="sidebar-accordion-trigger" type="button"><span class="section-icon green"><?php echo htmlspecialchars($firstLetter); ?></span> Account <span class="sidebar-accordion-chevron"><i class="fa fa-chevron-down"></i></span></button>
      <div class="sidebar-accordion-content">
        <ul class="sidebar-sub-list">
          <li class="sidebar-sub-item"><a href="users.php">Users</a></li>
          <li class="sidebar-sub-item"><a href="user-log.php">User Logs</a></li>
          <li class="sidebar-sub-item"><a href="loading.php?pg=fb-login.php?update=1">Meta Login (Update)</a></li>
          <li class="sidebar-sub-item"><a href="loading.php?pg=linkedin-login.php?update=1">LI Login (Update)</a></li>
          <?php if ($access_token) { ?><li class="sidebar-sub-item"><a href="#" onclick="navigator.clipboard.writeText('<?php echo addslashes($access_token); ?>'); alert('Copied!'); return false;">Copy Access Token</a></li><?php } ?>
          <li class="sidebar-nav-item sidebar-menu-cta"><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var trigger = document.getElementById('sidebarMenuTrigger');
  var overlay = document.getElementById('sidebarMenuOverlay');
  if (!trigger || !overlay) return;
  function openMenu() { overlay.classList.add('open'); trigger.classList.add('open'); trigger.setAttribute('aria-label','Close menu'); document.body.style.overflow = 'hidden'; }
  function closeMenu() { overlay.classList.remove('open'); trigger.classList.remove('open'); trigger.setAttribute('aria-label','Open menu'); document.body.style.overflow = ''; }
  trigger.addEventListener('click', function() {
    if (overlay.classList.contains('open')) closeMenu(); else openMenu();
  });
  overlay.addEventListener('click', function(e) { if (e.target === overlay) closeMenu(); });
  document.querySelectorAll('[data-accordion]').forEach(function(s) {
    var btn = s.querySelector('.sidebar-accordion-trigger');
    if (btn) btn.addEventListener('click', function() {
      var wasExpanded = s.classList.contains('expanded');
      document.querySelectorAll('[data-accordion]').forEach(function(o) { o.classList.remove('expanded'); });
      if (!wasExpanded) s.classList.add('expanded');
    });
  });
})();
</script>
