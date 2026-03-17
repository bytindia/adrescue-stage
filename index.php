<?php 
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

include 'header.php'; 
if(isset($_SESSION['user_ty']) && ($_SESSION['user_ty'] == 'acc' || $_SESSION['user_ty'] == 'admin')) { echo "<script>window.location = 'loading.php?pg=index-acc.php';</script>"; exit();	}
?>
<link rel="stylesheet" href="css/index.css?1.0">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php

function getTotalrows($tbl, $cond, $conn) {
	$sql = "SELECT * FROM $tbl $cond";
	$result = $conn->query($sql);
	return $result->num_rows;
}

function moneyFormatIndia($num) {
    if($num < 0) {
        return '-'.moneyFormatIndia(abs($num));
    }
    $explrestunits = "";
    if(strlen($num) > 3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3);
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits;
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].",";
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash;
}

function percent($num, $den) {
    if ($den == 0) return '0%';
    $percentage = round(($num / $den) * 100, 1);
    $sign = $percentage >= 0 ? '+' : ''; // Only add + for positive numbers
    return $sign . $percentage . '%';
}
//d($_SESSION);
function dashboard_budget_cards($byt) {
?>
<div class="row mb-8 mt-10">
    <!-- Total Budget -->
    <div class="col-md-2 col-sm-6">
        <div class="dashboard-card" style="border-left: 4px solid #00c6ff;">
            <div class="dashboard-card-title">Total Budget</div>
            <div class="dashboard-card-value dashboard-card-value-primary">₹<?php echo moneyFormatIndia($byt['bud']); ?></div>
            <div class="text-muted text-small">This Month (BYT CC)</div>
            <div class="dashboard-card-icon dashboard-card-icon-money">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>
    <!-- Budget Received -->
    <div class="col-md-2 col-sm-6">
        <div class="dashboard-card" style="border-left: 4px solid #ff4e50;">
            <div class="dashboard-card-title">Budget Received</div>
            <div class="dashboard-card-value dashboard-card-value-danger">₹<?php echo moneyFormatIndia($byt['bud_rcd']); ?></div>
            <div class="text-muted text-small"><?php echo percent($byt['bud_rcd'], $byt['bud']); ?> of total</div>
            <div class="dashboard-card-icon dashboard-card-icon-arrow">
                <i class="fa fa-arrow-circle-down"></i>
            </div>
        </div>
    </div>
    <!-- Spend -->
    <div class="col-md-2 col-sm-6">
        <div class="dashboard-card" style="border-left: 4px solid #00b894;">
            <div class="dashboard-card-title">Spend</div>
            <div class="dashboard-card-value dashboard-card-value-success">₹<?php echo moneyFormatIndia($byt['spend']); ?></div>
            <div class="text-muted text-small"><?php echo percent($byt['spend'], $byt['bud']); ?> of total</div>
            <div class="dashboard-card-icon dashboard-card-icon-credit">
                <i class="fa fa-credit-card"></i>
            </div>
        </div>
    </div>
    <!-- Budget Remaining -->
    <div class="col-md-2 col-sm-6">
        <div class="dashboard-card" style="border-left: 4px solid #fbc531;">
            <div class="dashboard-card-title">Budget Remaining</div>
            <div class="dashboard-card-value dashboard-card-value-warning">₹<?php echo moneyFormatIndia($byt['bal']); ?></div>
            <div class="text-muted text-small"><?php 
                $bal_percent = ($byt['bal'] / $byt['bud']) * 100;
                echo round($bal_percent, 1) . '% of total'; 
            ?></div>
            <div class="dashboard-card-icon dashboard-card-icon-balance">
                <i class="fa fa-balance-scale"></i>
            </div>
        </div>
    </div>
    <!-- Spend Est. Month End -->
    <div class="col-md-2 col-sm-6">
        <div class="dashboard-card" style="border-left: 4px solid #fd9644;">
            <div class="dashboard-card-title">Spend Est. Month End</div>
            <div class="dashboard-card-value dashboard-card-value-orange">₹<?php echo moneyFormatIndia($byt['spend_mon_end']); ?></div>
            <div class="text-muted text-small"><?php echo percent($byt['spend_mon_end'], $byt['bud']); ?> of total</div>
            <div class="dashboard-card-icon dashboard-card-icon-line">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>
    <!-- Yesterday's Spend -->
    <div class="col-md-2 col-sm-6">
        <div class="dashboard-card" style="border-left: 4px solid #6c5ce7;">
            <div class="dashboard-card-title">Yesterday's Spend</div>
            <div class="dashboard-card-value dashboard-card-value-purple">₹<?php echo moneyFormatIndia($byt['spend_y']); ?></div>
            <div class="text-muted text-small">Yesterday</div>
            <div class="dashboard-card-icon dashboard-card-icon-calendar">
                <i class="fa fa-calendar-check-o"></i>
            </div>
        </div>
    </div>
</div>
<?php
}
// --- End Dashboard Cards Function ---

function section_quick_links_cards() {
?>
<div class="row mb-8 mt-0">
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('dashboard-clients.php?menu=hide')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #1976d2;">
        <div class="dashboard-card-title dashboard-card-title-primary font-semibold">Client Dashboard</div>
        <div class="dashboard-card-icon dashboard-card-icon-users">
          <i class="fa fa-users"></i>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('https://stage.adrescue.in/dash/multi-client.php')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #43a047;">
        <div class="dashboard-card-title dashboard-card-title-success font-semibold">Multi-Client Report</div>
        <div class="dashboard-card-icon dashboard-card-icon-envelope">
          <i class="fa fa-line-chart"></i>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('https://stage.adrescue.in/dash/adv-targeting.php')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #00bcd4;">
        <div class="dashboard-card-title dashboard-card-title-info font-semibold">Similar Audience Ads</div>
        <div class="dashboard-card-icon dashboard-card-icon-bullseye">
          <i class="fa fa-bullseye"></i>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('https://stage.adrescue.in/vlookup-da2')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #ff9800;">
        <div class="dashboard-card-title dashboard-card-title-warning font-semibold">DA VLookup</div>
        <div class="dashboard-card-icon dashboard-card-icon-budget">
          <i class="fa fa-search"></i>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('camp-reports/')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #8e24aa;">
        <div class="dashboard-card-title dashboard-card-title-purple font-semibold">Campaigns Report</div>
        <div class="dashboard-card-icon dashboard-card-icon-list">
          <i class="fa fa-list-alt"></i>
        </div>
      </div>
    </a>
  </div>
  <!--
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('https://stage.adrescue.in/home')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #00bcd4;">
        <div class="dashboard-card-title dashboard-card-title-info font-semibold">Similar Audience Ads</div>
        <div class="dashboard-card-icon dashboard-card-icon-bullseye">
          <i class="fa fa-bullseye"></i>
        </div>
      </div>
    </a>
  </div>-->
  <div class="col-md-2 col-sm-6">
    <a href="javascript:void(0);" onclick="openInModal('management.php?menu=hide')" class="dashboard-card-link">
      <div class="dashboard-card dashboard-card-quick" style="border-left: 4px solid #d84315;">
        <div class="dashboard-card-title dashboard-card-title-danger font-semibold">Management</div>
        <div class="dashboard-card-icon dashboard-card-icon-cogs">
          <i class="fa fa-cogs"></i>
        </div>
      </div>
    </a>
  </div>
</div>

<!-- Modal Container -->
<div id="pageModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" style="width: 90%; max-width: 90%;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Loading...</h4>
      </div>
      <div class="modal-body" style="padding: 0;">
        <div id="modalLoadingMessage" style="display: flex; justify-content: center; align-items: center; height: 85vh; background: #f8f9fa;">
          <div style="text-align: center;">
            <i class="fa fa-spinner fa-spin" style="font-size: 2em; color: #2196f3; margin-bottom: 15px;"></i>
            <div style="font-size: 16px; color: #666; font-weight: 500;">Loading content...</div>
            <div style="font-size: 12px; color: #999; margin-top: 5px;">Please wait while the page loads</div>
          </div>
        </div>
        <iframe id="modalIframe" style="width: 100%; height: 85vh; border: none; display: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<script>
function openInModal(url) {
  let title = 'Loading...';

  if (url.startsWith('dashboard-clients.php')) {
    title = 'Client Dashboard';
  } else if (url.startsWith('invoice-filter.php')) {
    title = 'Email Report';
  } else if (url.startsWith('budget4.php')) {
    title = 'Budget';
  } else if (url.startsWith('camp-reports/')) {
    title = 'Campaign wise Report';
  } else if (url.startsWith('https://stage.adrescue.in/home')) {
    title = 'AdTracker';
  } else if (url.startsWith('https://stage.adrescue.in/vlookup-da2')) {
    title = 'Digital Azadi VLookup';
  } else if (url.startsWith('https://stage.adrescue.in/dash/multi-client.php')) {
    title = 'Multi-Client Ads Report';
  } else if (url.startsWith('https://stage.adrescue.in/dash/adv-targeting.php')) {
    title = 'Similar Audience Ads';
  } else if (url.startsWith('management.php')) {
    title = 'Management Dashboard';
  }

  $('#pageModal .modal-title').text(title);
  
  // Show loading message
  $('#modalIframe').hide();
  $('#modalLoadingMessage').show();
  
  // Set iframe src and show modal
  $('#modalIframe').attr('src', url);
  $('#pageModal').modal('show');
}

// Handle iframe load events
document.getElementById('modalIframe').onload = function() {
  // Hide loading message and show iframe
  $('#modalLoadingMessage').hide();
  $('#modalIframe').show();
  
  try {
    // Try to access iframe content to check if it loaded successfully
    this.contentWindow.document;
  } catch (e) {
    // If access is denied due to CORS, show an error message
    if (e.name === 'SecurityError') {
      this.style.display = 'none';
      this.parentNode.innerHTML = '<div class="alert alert-warning" style="margin: 20px;">This content cannot be displayed in a modal due to security restrictions. <a href="' + this.src + '" target="_blank">Click here to open in a new tab</a>.</div>';
    }
  }
};

// Handle modal show event to reset loading state
$('#pageModal').on('show.bs.modal', function() {
  $('#modalIframe').hide();
  $('#modalLoadingMessage').show();
});
</script>

<style>
.modal-xl {
  width: 90%;
  max-width: 90%;
}
.modal-body {
  padding: 0;
}
#modalIframe {
  width: 100%;
  height: 85vh;
  border: none;
}
.modal-header {
  padding: 10px 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.modal-header .close {
  margin-top: 2px;
}
.modal-title {
  font-weight: 600;
  color: #333;
}
</style>
<?php
}

function section_daily_budget_chart($data) {
?>
<!-- Daily Budget & No Lead Adsets Charts Row -->
<div class="row" style="margin-bottom: 24px;">
  <!-- Column 1: Daily Budget Chart -->
  <div class="col-md-6 col-sm-12">
    <div class="x_panel">
      <div class="x_title" style="display:flex; align-items:center; justify-content:space-between;">
        <h4 style="margin:0;">Daily Budget: Today</h4>
        <button id="refreshDailyBudget" class="btn btn-sm btn-outline-primary" style="margin-left:auto;" title="Refresh">
          <i class="fa fa-refresh"></i>
        </button>
      </div>
      <div class="x_content">
        <canvas id="combinedBudgetChart" height="160"></canvas>
        <div id="budgetTotals" style="margin-top:24px; display:flex; justify-content:center; gap:40px;"></div>
      </div>
    </div>
  </div>
  <!-- Column 2: No Lead Adsets Chart -->
  <div class="col-md-6 col-sm-12">
    <div class="x_panel">
      <div class="x_title"><h4>No Lead Adsets</h4></div>
      <div class="x_content">
        <canvas id="noLeadAdsetsChart" height="160"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
function moneyFormatIndia(num) {
    num = Math.floor(num);
    var x = num.toString();
    var lastThree = x.substring(x.length-3);
    var otherNumbers = x.substring(0,x.length-3);
    if(otherNumbers != '')
        lastThree = ',' + lastThree;
    return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree;
}

let dailyBudgetData = <?php echo json_encode($data['daily_budget'] ?? []); ?>;
let noLeadCountsData = <?php echo json_encode($data['no_lead_counts'] ?? []); ?>;
let combinedBudgetChart = null;

function renderDailyBudgetSection(dailyBudget, noLeadCountsData) {
  // --- Chart Data Preparation ---
  dailyBudget = dailyBudget.filter(row => (Number(row.fb_budget) || 0) > 0 || (Number(row.g_budget) || 0) > 0);
  const labels = dailyBudget.map(row => {
    let tag = (row.tags || '').split(',')[0].trim();
    if (!tag) tag = row.client;
    return tag.toUpperCase();
  });
  const metaData = dailyBudget.map(row => Number(row.fb_budget) || 0);
  const googleData = dailyBudget.map(row => Number(row.g_budget) || 0);
  const today = new Date().getDate();
  const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
  const estBudgetData = dailyBudget.map(row => {
    const total_budget = Number(row.total_budget) || 0;
    const tot_spend = Number(row.tot_spend) || 0;
    const remaining_days = daysInMonth - today;
    const remaining_budget = total_budget - tot_spend;
    return (remaining_days > 0) ? Math.round(remaining_budget / remaining_days) : 0;
  });
  // --- Chart Colors ---
  const metaColor = 'rgba(33, 150, 243, 0.85)';
  const googleColor = 'rgba(255, 167, 38, 0.85)';
  const estLineColor = 'rgba(67, 160, 71, 1)';
  // --- Chart Rendering ---
  if (combinedBudgetChart) combinedBudgetChart.destroy();
  combinedBudgetChart = new Chart(document.getElementById('combinedBudgetChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Meta',
          data: metaData,
          backgroundColor: metaColor,
          borderColor: 'rgba(33, 150, 243, 1)',
          borderWidth: 1,
          datalabels: {
            color: '#fff',
            anchor: 'center',
            align: 'center',
            font: { weight: 'bold', size: 11 },
            formatter: function(value) { return value > 0 ? value : ''; }
          }
        },
        {
          label: 'Google',
          data: googleData,
          backgroundColor: googleColor,
          borderColor: '#ffb446',
          borderWidth: 1,
          datalabels: {
            color: '#fff',
            anchor: 'center',
            align: 'center',
            font: { weight: 'bold', size: 11 },
            formatter: function(value) { return value > 0 ? value : ''; }
          }
        },
        {
          label: 'EST Budget',
          data: estBudgetData,
          type: 'line',
          borderColor: estLineColor,
          backgroundColor: estLineColor,
          borderWidth: 3,
          fill: false,
          pointRadius: 4,
          pointBackgroundColor: estLineColor,
          yAxisID: 'y',
          datalabels: {
            color: estLineColor,
            anchor: 'end',
            align: 'top',
            font: { weight: 'bold', size: 11 },
            formatter: function(value) { return value > 0 ? value : ''; }
          }
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: true },
        datalabels: {
          display: true,
          anchor: 'center',
          align: 'center',
        }
      },
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Budget (₹)' } }
      }
    },
    plugins: [
      ChartDataLabels,
      {
        id: 'stackTotalLabels',
        afterDatasetsDraw(chart, args, pluginOptions) {
          const {ctx} = chart;
          const meta = chart.getDatasetMeta(1); // Google is on top
          chart.data.labels.forEach((label, i) => {
            const data = meta.data[i];
            if (data) {
              const total = metaData[i] + googleData[i];
              if (total > 0) {
                ctx.save();
                ctx.font = 'bold 13px sans-serif';
                ctx.fillStyle = '#222';
                ctx.textAlign = 'center';
                ctx.fillText(total, data.x, data.y - 8);
                ctx.restore();
              }
            }
          });
        }
      }
    ]
  });
  // --- Totals ---
  const metaTotalBudget = metaData.reduce((a, b) => a + b, 0);
  const googleTotalBudget = googleData.reduce((a, b) => a + b, 0);
  const grandTotal = metaTotalBudget + googleTotalBudget;
  const estBudgetTotal = estBudgetData.reduce((a, b) => a + b, 0);
  if (document.getElementById('budgetTotals')) {
    document.getElementById('budgetTotals').innerHTML = `
      <div style="display:flex; width:100%; justify-content:space-between; align-items:flex-start;">
        <div style="display:flex; gap:40px;">
          <div style="text-align:center;">
            <div style="font-size:13px; color:#888;">Meta Total</div>
            <div style="font-size:1.7em; color:#2196f3; font-weight:700;">₹${moneyFormatIndia(metaTotalBudget)}</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:13px; color:#888;">Google Total</div>
            <div style="font-size:1.7em; color:#e17055; font-weight:700;">₹${moneyFormatIndia(googleTotalBudget)}</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:13px; color:#888;">Grand Total</div>
            <div style="font-size:1.7em; color:#222; font-weight:700;">₹${moneyFormatIndia(grandTotal)}</div>
          </div>
        </div>
        <div style="text-align:center; min-width:160px;">
          <div style="font-size:13px; color:#43a047;">EST Budget Total</div>
          <div style="font-size:1.7em; color:#43a047; font-weight:700;">₹${moneyFormatIndia(estBudgetTotal)}</div>
        </div>
      </div>
    `;
  }

  // --- No Lead Adsets Chart ---
  let noLeadCounts = noLeadCountsData || [];
  noLeadCounts = noLeadCounts.filter(row => (row.meta_zero_leads || 0) > 0 || (row.google_zero_leads || 0) > 0);
  const noLeadLabels = noLeadCounts.map(row => {
    if (row.tags && row.tags.trim() !== '') {
      return row.tags.toUpperCase();
    } else if (row.client && row.client.indexOf(',') !== -1) {
      return row.client.split(',')[0].trim().toUpperCase();
    } else {
      return (row.client || '').toUpperCase();
    }
  });
  const metaZeroLeads = noLeadCounts.map(row => row.meta_zero_leads || 0);
  const googleZeroLeads = noLeadCounts.map(row => row.google_zero_leads || 0);
  const metaTotalNoLead = noLeadCounts.map(row => row.meta_total_adsets || 0);
  const googleTotalNoLead = noLeadCounts.map(row => row.google_total_adsets || 0);

  if (window.noLeadAdsetsChart && typeof window.noLeadAdsetsChart.destroy === 'function') {
    window.noLeadAdsetsChart.destroy();
  }
  if (document.getElementById('noLeadAdsetsChart')) {
    window.noLeadAdsetsChart = new Chart(document.getElementById('noLeadAdsetsChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: noLeadLabels,
        datasets: [
          {
            label: 'Meta',
            data: metaZeroLeads,
            backgroundColor: 'rgba(33, 150, 243, 0.85)',
            borderColor: 'rgba(33, 150, 243, 1)',
            borderWidth: 1
          },
          {
            label: 'Google',
            data: googleZeroLeads,
            backgroundColor: 'rgba(255, 167, 38, 0.85)',
            borderColor: 'rgba(255, 167, 38, 1)',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true },
          datalabels: {
            display: true,
            anchor: 'end',
            align: 'top',
            font: { weight: 'bold', size: 11 },
            color: '#222',
            formatter: function(value, ctx) {
              // Show value only if > 0
              return value > 0 ? value : '';
            }
          },
          tooltip: {
            callbacks: {
              afterLabel: function(context) {
                // Show total adsets in tooltip
                const idx = context.dataIndex;
                if (context.dataset.label === 'Meta') {
                  return ' / ' + metaTotalNoLead[idx] + ' adsets';
                } else if (context.dataset.label === 'Google') {
                  return ' / ' + googleTotalNoLead[idx] + ' adsets';
                }
                return '';
              }
            }
          }
        },
        scales: {
          x: { stacked: false },
          y: { beginAtZero: true, title: { display: true, text: 'No Lead Adsets' } }
        }
      },
      plugins: [ChartDataLabels]
    });
  }
}

// Initial render
renderDailyBudgetSection(dailyBudgetData, noLeadCountsData);

// Refresh button logic
$('#refreshDailyBudget').on('click', function() {
  var btn = $(this);
  btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i>');
  $.ajax({
    url: 'https://stage.adrescue.in/budget-api-json.php',
    method: 'GET',
    dataType: 'json',
    cache: false,
    success: function(resp) {
      //alert('don');
      console.log(resp);
      dailyBudgetData = resp.daily_budget || [];
      noLeadCountsData = resp.no_lead_counts || [];
      renderDailyBudgetSection(dailyBudgetData, noLeadCountsData);
      alert('render called');
    },
    complete: function() {
      btn.prop('disabled', false).html('<i class="fa fa-refresh"></i>');
    },
    error: function() {
      alert('Failed to refresh data.');
    }
  });
});
</script>
<?php
}

function section_summary_cards($all) {
?>
<div class="row" style="margin-bottom: 24px;">
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #0984e3; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Meta Leads</div>
      <div style="font-size:2em; color:#0984e3; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(round($all['fb_leads'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #00b894; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Meta CPL</div>
      <div style="font-size:2em; color:#00b894; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(round($all['fb_cpl'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #fd9644; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Google Leads</div>
      <div style="font-size:2em; color:#fd9644; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(round($all['g_leads'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #6c5ce7; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Google CPL</div>
      <div style="font-size:2em; color:#6c5ce7; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(round($all['g_cpl'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #00bcd4; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Total Leads</div>
      <div style="font-size:2em; color:#00bcd4; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(round($all['tot_leads'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #e17055; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Total CPL</div>
      <div style="font-size:2em; color:#e17055; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(round($all['tot_cpl'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
</div>
<?php
}

function section_high_cpl_spends($topCPL, $topSpends, $data) {
?>
<div class="row" style="margin-bottom: 24px;">
  <!-- Column 1: Media Buyer Stats -->
  <div class="col-md-4">
    <div class="x_panel">
      <div class="x_title">
        <h4>Buyer Vs Budget</h4>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
        <?php
        if (isset($data['media_buyer']) && is_array($data['media_buyer'])) {
          $palette = ['#0984e3', '#fdcb6e', '#00b894', '#e17055', '#6c5ce7', '#00bcd4', '#ff7675', '#636e72'];
          $i = 0;
          foreach ($data['media_buyer'] as $mb) {
            $color = $palette[$i % count($palette)];
            $usos = ($mb['sp_type'] === 'Over') ? 'OS' : 'US';
            $usos_color = ($mb['sp_type'] === 'Over') ? '#e74c3c' : '#27ae60';
        ?>
        <div style="display:flex; align-items:center; margin-bottom:2px;">
          <span style="font-weight:600; min-width:90px;"><?php echo htmlspecialchars($mb['name']); ?></span>
          <span style="margin-left:auto; font-weight:600;"><?php echo $mb['est_diff_per']; ?>%</span>
        </div>
        <div style="font-size:12px; margin-bottom:4px; color:<?php echo $usos_color; ?>;">
          <?php echo $usos; ?> <span style="color:#888;">(<?php echo $mb['sp_type']; ?> Spend)</span>
        </div>
        <div style="height:8px; background:#eee; border-radius:4px; margin-bottom:16px; overflow:hidden;">
          <div style="height:100%; width:<?php echo abs($mb['est_diff_per']); ?>%; background:<?php echo $color; ?>; border-radius:4px;"></div>
        </div>
        <?php $i++; }} ?>
      </div>
    </div>
  </div>
  <!-- Column 2: Top 10 High CPL Chart -->
  <div class="col-md-4">
    <div class="x_panel">
      <div class="x_title">
        <h4>High CPL: Meta</h4>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
        <canvas id="cplChart" height="180"></canvas>
      </div>
    </div>
  </div>
  <!-- Column 3: Top 10 Spends Chart -->
  <div class="col-md-4">
    <div class="x_panel">
      <div class="x_title">
        <h4>High Spent: (M+G)</h4>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
        <canvas id="spendChart" height="180"></canvas>
      </div>
    </div>
  </div>
</div>
<?php if (!empty($topCPL) || !empty($topSpends)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let cplLabels = <?php echo json_encode(array_column($topCPL, 'tag')); ?>;
cplLabels = cplLabels.map(label => label.toUpperCase());
const cplData = <?php echo json_encode(array_map(function($c){return round($c['cpl'],2);}, $topCPL)); ?>;
const cplPalette = [
  '#0984e3','#fdcb6e','#00b894','#e17055','#6c5ce7','#00bcd4','#ff7675','#636e72','#fd79a8','#fab1a0'
];
const cplColors = cplLabels.map((_,i) => cplPalette[i % cplPalette.length]);
let spendLabels = <?php echo json_encode(array_column($topSpends, 'tag')); ?>;
spendLabels = spendLabels.map(label => label.toUpperCase());
const spendData = <?php echo json_encode(array_map(function($c){return round($c['spend']);}, $topSpends)); ?>;
const spendColors = [
  '#0984e3','#fdcb6e','#00b894','#e17055','#6c5ce7','#00bcd4','#ff7675','#636e72','#fd79a8','#fab1a0'
];
if (document.getElementById('cplChart')) {
  new Chart(document.getElementById('cplChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: cplLabels,
      datasets: [{
        label: 'CPL (₹)',
        data: cplData,
        backgroundColor: cplColors,
        borderColor: cplColors,
        borderWidth: 1
      }]
    },
    options: {
      indexAxis: 'x',
      scales: { y: { beginAtZero: true } },
      plugins: {
        legend: { display: false }
      }
    }
  });
}
if (document.getElementById('spendChart')) {
  new Chart(document.getElementById('spendChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: spendLabels,
      datasets: [{
        label: 'Spend (₹)',
        data: spendData,
        backgroundColor: spendColors,
        borderColor: spendColors,
        borderWidth: 1
      }]
    },
    options: {
      indexAxis: 'x',
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { display: false } }
    }
  });
}
</script>
<?php endif; ?>
<?php
}

function section_troubleshoot_adtracker($troubleshoot, $data) {
?>
<div class="row" style="margin-bottom: 24px;">
  <!-- Full Width Column -->
  <div class="col-md-12">
    <div class="x_panel">
      <div class="x_title" style="display:flex; align-items:center; justify-content:space-between;">
        <h4 style="margin:0;">Performance Overview</h4>
      </div>
      <div class="x_content" style="padding:0;">
        <!-- Tabs for both sections -->
        <ul class="nav nav-tabs" role="tablist">
          <li class="active"><a href="#troubleshootTab" role="tab" data-toggle="tab">Troubleshoot</a></li>
          <li><a href="#adTrackerTab" role="tab" data-toggle="tab">AdTracker</a></li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content">
          <!-- Troubleshoot Tab -->
          <div class="tab-pane active" id="troubleshootTab">
            <div style="max-height:442px; overflow-y:auto; padding:0;">
              <table id="troubleshootTable" class="table table-striped" style="margin-bottom:0; width:100%;">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Adset</th>
                    <th>Objective</th>
                    <th>Spend</th>
                    <th>CPL</th>
                    <th>CPL(A/c)</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($troubleshoot as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['adset']); ?></td>
                    <td><?php echo htmlspecialchars($row['obj']); ?></td>
                    <td><?php echo htmlspecialchars($row['spend']); ?></td>
                    <td><?php echo htmlspecialchars($row['cpa']); ?></td>
                    <td><?php echo htmlspecialchars($row['cpa_acc']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- AdTracker Tab -->
          <div class="tab-pane" id="adTrackerTab">
            <div style="max-height:442px; overflow-y:auto;">
              <ul class="nav nav-tabs" id="adtrackerSubTab">
                <li class="active"><a href="#tab0" data-toggle="tab">0 Leads</a></li>
                <li><a href="#tabHigh" data-toggle="tab">High CPL</a></li>
                <li><a href="#tabLow" data-toggle="tab">Low CPL</a></li>
              </ul>
              <div class="tab-content" id="adtrackerTabContent">
                <!-- 0 Leads Tab -->
                <div class="tab-pane fade in active" id="tab0">
                  <table id="adtracker0" class="table table-striped table-bordered" style="width:100%;">
                    <thead>
                      <tr>
                        <th>Client</th>
                        <th>Adset</th>
                        <th>Objective</th>
                        <th>Campaign</th>
                        <th>Spend</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($data['zero_lead'] as $row): ?>
                      <tr>
                        <td><?= htmlspecialchars($row['client']) ?></td>
                        <td><?= htmlspecialchars($row['ad_set']) ?></td>
                        <td><?= htmlspecialchars($row['objective']) ?></td>
                        <td><?= htmlspecialchars($row['campaign']) ?></td>
                        <td><?= htmlspecialchars($row['spend']) ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <!-- High CPL Tab -->
                <div class="tab-pane fade" id="tabHigh">
                  <table id="adtrackerHigh" class="table table-striped table-bordered" style="width:100%;">
                    <thead>
                      <tr>
                        <th>Client</th>
                        <th>Adset</th>
                        <th>Objective</th>
                        <th>Campaign</th>
                        <th>Spend</th>
                        <th>Leads</th>
                        <th>CPL</th>
                        <th>CPL(L30d)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($data['high_cpl'] as $row): ?>
                      <tr>
                        <td><?= htmlspecialchars($row['client']) ?></td>
                        <td><?= htmlspecialchars($row['ad_set']) ?></td>
                        <td><?= htmlspecialchars($row['objective']) ?></td>
                        <td><?= htmlspecialchars($row['campaign']) ?></td>
                        <td><?= htmlspecialchars($row['spend']) ?></td>
                        <td><?= htmlspecialchars($row['leads']) ?></td>
                        <td><?= htmlspecialchars($row['cpl']) ?></td>
                        <td><?= htmlspecialchars($row['last30d_cpl']) ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <!-- Low CPL Tab -->
                <div class="tab-pane fade" id="tabLow">
                  <table id="adtrackerLow" class="table table-striped table-bordered" style="width:100%;">
                    <thead>
                      <tr>
                        <th>Client</th>
                        <th>Adset</th>
                        <th>Objective</th>
                        <th>Campaign</th>
                        <th>Spend</th>
                        <th>Leads</th>
                        <th>CPL</th>
                        <th>CPL(L30d)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($data['low_cpl'] as $row): ?>
                      <tr>
                        <td><?= htmlspecialchars($row['client']) ?></td>
                        <td><?= htmlspecialchars($row['ad_set']) ?></td>
                        <td><?= htmlspecialchars($row['objective']) ?></td>
                        <td><?= htmlspecialchars($row['campaign']) ?></td>
                        <td><?= htmlspecialchars($row['spend']) ?></td>
                        <td><?= htmlspecialchars($row['leads']) ?></td>
                        <td><?= htmlspecialchars($row['cpl']) ?></td>
                        <td><?= htmlspecialchars($row['last30d_cpl']) ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Main section tabs */
.nav-tabs > li > a {
  color: #2196f3;
  font-weight: 600;
  padding: 10px 20px;
}
.nav-tabs > li.active > a,
.nav-tabs > li.active > a:focus,
.nav-tabs > li.active > a:hover {
  background: #2196f3 !important;
  color: #fff !important;
  border-radius: 4px 4px 0 0;
  border: none;
}

/* AdTracker subtabs */
#adtrackerSubTab {
  background: #f8f9fa;
  padding: 10px 10px 0;
  border-bottom: 1px solid #dee2e6;
}
#adtrackerSubTab > li > a {
  padding: 8px 15px;
  font-size: 13px;
}

/* Table styles */
.table {
  margin-bottom: 0;
}
.table > thead > tr > th {
  background: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}

/* DataTables overrides */
.dataTables_wrapper {
  padding: 15px;
}
.dataTables_scrollBody {
  border-bottom: none !important;
}
</style>

<?php if (!empty($troubleshoot)): ?>
<script>
$(document).ready(function() {
  // Initialize main DataTables
  $('#troubleshootTable').DataTable({
    paging: false,
    info: false,
    searching: true,
    scrollY: 380,
    scrollCollapse: true,
    order: []
  });
  
  $('#adtracker0').DataTable({
    paging: false,
    info: false,
    searching: true,
    scrollY: 300,
    scrollCollapse: true
  });
  
  $('#adtrackerHigh').DataTable({
    paging: false,
    info: false,
    searching: true,
    scrollY: 300,
    scrollCollapse: true
  });
  
  $('#adtrackerLow').DataTable({
    paging: false,
    info: false,
    searching: true,
    scrollY: 300,
    scrollCollapse: true
  });

  // Handle tab changes for DataTables
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
  });
});
</script>
<?php endif; ?>
<?php
}

function section_stats_cards($all) {
?>
<div class="row" style="margin-bottom: 24px;">
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #1e90ff; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Clicks</div>
      <div style="font-size:2em; color:#1e90ff; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia($all['tot_clicks'] ?? 0); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #43a047; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Impressions</div>
      <div style="font-size:2em; color:#43a047; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia($all['tot_impr'] ?? 0); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #ff9800; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">CTR</div>
      <div style="font-size:2em; color:#ff9800; font-weight:700; letter-spacing:1px;">
        <?php echo isset($all['tot_ctr']) ? $all['tot_ctr'] . '%' : '0%'; ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">This Month</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #8e24aa; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Clicks Yesterday</div>
      <div style="font-size:2em; color:#8e24aa; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(($all['clicks_fb_y'] ?? 0) + ($all['clicks_g_y'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">Yesterday</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #00acc1; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">Impr. Yesterday</div>
      <div style="font-size:2em; color:#00acc1; font-weight:700; letter-spacing:1px;">
        <?php echo moneyFormatIndia(($all['impr_fb_y'] ?? 0) + ($all['impr_g_y'] ?? 0)); ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">Yesterday</div>
    </div>
  </div>
  <div class="col-md-2 col-sm-4">
    <div class="card" style="border:2px solid #d84315; border-radius:0; background:#fff; padding:14px 8px 10px 8px; margin-bottom:16px; text-align:center; min-height:110px;">
      <div style="font-size:15px; color:#888; margin-bottom:8px; font-weight:600;">CTR Yesterday</div>
      <div style="font-size:2em; color:#d84315; font-weight:700; letter-spacing:1px;">
        <?php 
        $clicks_y = ($all['clicks_fb_y'] ?? 0) + ($all['clicks_g_y'] ?? 0);
        $impr_y = ($all['impr_fb_y'] ?? 0) + ($all['impr_g_y'] ?? 0);
        $ctr_y = ($impr_y > 0) ? round(($clicks_y / $impr_y) * 100, 2) : 0;
        echo $ctr_y . '%';
        ?>
      </div>
      <div style="font-size:10px; color:#aaa; margin-top:4px;">Yesterday</div>
    </div>
  </div>
</div>
<?php
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
        <?php
        // Fetch budget data from API
        function curl_get_contents1($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }

        $json = curl_get_contents1("https://stage.adrescue.in/budget-api-json.php");
        $data = json_decode($json, true);
        if ($_SESSION['user_ty']=='acc') {
            dashboard_budget_cards($data['byt']);
        } else {
          section_quick_links_cards();
        }

        
        section_daily_budget_chart($data);
        $all = isset($data['all']) ? $data['all'] : [];
        section_summary_cards($all);
        // Compute $topCPL, $topSpends
        $topCPL = [];
        $topSpends = [];
        if (isset($data['client']) && is_array($data['client'])) {
            foreach ($data['client'] as $client) {
                if (isset($client['cc_card']) && $client['cc_card'] === 'BYT') {
                    $fb_cpl = isset($client['fb_cpl']) ? floatval($client['fb_cpl']) : 0;
                    if ($fb_cpl > 0) {
                        $topCPL[] = [
                            'tag' => $client['tag'],
                            'cpl' => $fb_cpl,
                            'sp_type' => isset($client['sp_type']) ? $client['sp_type'] : 'Under'
                        ];
                    }
                }
            }
            usort($topCPL, function($a, $b) { return $b['cpl'] <=> $a['cpl']; });
            $topCPL = array_slice($topCPL, 0, 10);
            foreach ($data['client'] as $client) {
                $spend = floatval($client['spend']);
                if ($spend > 0 && isset($client['cc_card']) && $client['cc_card'] === 'BYT') {
                    $topSpends[] = [
                        'tag' => $client['tag'],
                        'spend' => $spend
                    ];
                }
            }
            usort($topSpends, function($a, $b) { return $b['spend'] <=> $a['spend']; });
            $topSpends = array_slice($topSpends, 0, 10);
        }
        section_high_cpl_spends($topCPL, $topSpends, $data);
        // Compute $troubleshoot
        $troubleshoot = isset($data['troubleshoot']) && is_array($data['troubleshoot']) ? $data['troubleshoot'] : [];
        section_troubleshoot_adtracker($troubleshoot, $data);
        section_stats_cards($all);
        ?>
      </div>
      <!-- /page content -->
    </div>
  </div>
  <?php include 'footer.php'; ?>

