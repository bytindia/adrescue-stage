<?php 
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

include 'header.php'; 
if(isset($_SESSION['user_ty']) && $_SESSION['user_ty'] == 'ads') { echo "<script>window.location = 'loading.php?pg=index.php';</script>"; exit();	}
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
            <div class="dashboard-card-title">Spend Received</div>
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

function section_top10_retainer_and_budget($clients) {
    // Fetch Outstanding Retainers from API
    $soa_json = curl_get_contents1("https://stage.adrescue.in/soa-api.php");
    $soa_data = json_decode($soa_json, true);
    $outstandingData = [];
    if (isset($soa_data['outstanding']) && is_array($soa_data['outstanding'])) {
        $outstandingData = array_filter($soa_data['outstanding'], function($row) {
            $amt = (int)str_replace([',',' '], '', $row['outstanding']);
            return $amt > 0;
        });
        $outstandingData = array_values($outstandingData);
        // Sort by outstanding descending
        usort($outstandingData, function($a, $b) {
            $a_amt = (int)str_replace([',',' '], '', $a['outstanding']);
            $b_amt = (int)str_replace([',',' '], '', $b['outstanding']);
            return $b_amt <=> $a_amt;
        });
    }
    // Process receipts for Amount Received - This Month (or latest month if none in current)
    $amountReceivedData = [];
    if (isset($soa_data['receipts']) && is_array($soa_data['receipts'])) {
        $groupedByMonth = [];
        foreach ($soa_data['receipts'] as $receipt) {
            $dateStr = $receipt['date'];
            
            // Fix for dates with invalid year (2525 instead of 2025)
            if (preg_match('/^(\d{2})-(\d{2})-2525$/', $dateStr, $matches)) {
                $dateStr = $matches[1] . '-' . $matches[2] . '-2025';
            }
            
            $dt = DateTime::createFromFormat('d-m-Y', $dateStr);
            if (!$dt) {
                $dt = DateTime::createFromFormat('d/m/Y', $dateStr);
            }
            if ($dt) {
                $monthKey = $dt->format('m-Y');
                $client = strtoupper(trim($receipt['client']));
                $amt = (float)str_replace([',',' '], '', $receipt['amount']);
                if (!isset($groupedByMonth[$monthKey])) $groupedByMonth[$monthKey] = [];
                if (!isset($groupedByMonth[$monthKey][$client])) $groupedByMonth[$monthKey][$client] = 0;
                $groupedByMonth[$monthKey][$client] += $amt;
            }
        }
        // Use current month instead of latest month from data
        $currentMonth = date('m-Y');
        $useMonth = $currentMonth;
        $grouped = isset($groupedByMonth[$useMonth]) ? $groupedByMonth[$useMonth] : [];
        foreach ($grouped as $client => $amt) {
            $amountReceivedData[] = [ 'client' => $client, 'amount' => $amt ];
        }
        usort($amountReceivedData, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        $amountReceivedData = array_slice($amountReceivedData, 0, 10);
    }
    // Filter and sort for Top 10 Budget Received (bud_rcd > 0, high to low)
    $budgetRcdClients = array_filter($clients, function($c) {
        return isset($c['bud_rcd']) && $c['bud_rcd'] > 0 && isset($c['cc_card']) && $c['cc_card'] === 'BYT';
    });
    usort($budgetRcdClients, function($a, $b) {
        return ((float)$b['bud_rcd']) <=> ((float)$a['bud_rcd']);
    });
    $topBudgetRcd = array_slice($budgetRcdClients, 0, 10);
    // Filter and sort for Top 10 Budget Not Received (bud > 0, bud_rcd is 0 or empty)
    $budgetNotRcdClients = array_filter($clients, function($c) {
        return isset($c['bud']) && $c['bud'] > 0 && (empty($c['bud_rcd']) || $c['bud_rcd'] == 0) && isset($c['cc_card']) && $c['cc_card'] === 'BYT';
    });
    usort($budgetNotRcdClients, function($a, $b) {
        return ((float)$b['bud']) <=> ((float)$a['bud']);
    });
    $topBudgetNotRcd = array_slice($budgetNotRcdClients, 0, 10);
    
    // Calculate totals for outstanding and receivable
    $totalOutstanding = 0;
    $totalReceivable = 0;
    foreach ($outstandingData as $row) {
        $totalOutstanding += (int)str_replace([',',' '], '', $row['outstanding']);
        $receivable = !empty($row['receivable']) ? (int)str_replace([',',' '], '', $row['receivable']) : 0;
        $totalReceivable += $receivable;
    }
?>
<div class="row" style="margin-bottom: 24px;">
  <!-- Outstanding & Receivable Chart - Full Width Row -->
  <div class="col-md-12">
    <div class="x_panel" style="position:relative;">
      <div class="x_title" style="display: flex; justify-content: space-between; align-items: center;">
        <h4>Outstanding & Receivable <small> </small></h4>
        <button id="copyChartBtn" class="btn btn-sm btn-primary" style="margin-right: 10px;">
          <i class="fa fa-copy"></i> Copy as Image
        </button>
      </div>
      <div class="x_content">
        <div class="row">
          <!-- Chart Column -->
          <div class="col-md-8">
            <canvas id="retainerChart" height="150"></canvas>
          </div>
          
          <!-- Notes Table Column -->
          <div class="col-md-4">
            <?php 
            $notesWithData = array_filter($outstandingData, function($row) {
                return !empty($row['notes']) && !empty($row['receivable']) && (int)str_replace([',',' '], '', $row['receivable']) > 0;
            });
            ?>
            <h5 style="color: #333; margin-bottom: 10px; font-weight: bold; font-size: 14px;">
              <i class="fa fa-sticky-note-o" style="color: #00b894; margin-right: 5px;"></i>
              Receivable Notes
            </h5>
            
            <?php if (!empty($notesWithData)): ?>
            <div style="overflow-y: auto; border: 1px solid #e9ecef; border-radius: 4px;">
              <table class="table table-sm table-hover" style="margin-bottom: 0; font-size: 12px;">
                <thead style="background-color: #f8f9fa;">
                  <tr>
                    <th style="border-top: none; padding: 8px 6px; font-weight: bold; color: #495057; width: 25%;">Client</th>
                    <th style="border-top: none; padding: 8px 6px; font-weight: bold; color: #495057; width: 20%;">Amount</th>
                    <th style="border-top: none; padding: 8px 6px; font-weight: bold; color: #495057; width: 35%;">Note</th>
                    <th style="border-top: none; padding: 8px 6px; font-weight: bold; color: #495057; width: 20%;">Updated</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($notesWithData as $row): ?>
                  <tr>
                    <td style="padding: 6px; vertical-align: top; font-weight: bold; color: #333;">
                      <?php echo htmlspecialchars(strtoupper(trim($row['client']))); ?>
                    </td>
                    <td style="padding: 6px; vertical-align: top; color: #00b894; font-weight: bold;">
                      ₹<?php echo moneyFormatIndia((int)str_replace([',',' '], '', $row['receivable'])); ?>
                    </td>
                    <td style="padding: 6px; vertical-align: top; color: #495057;">
                      <?php echo htmlspecialchars($row['notes']); ?>
                    </td>
                    <td style="padding: 6px; vertical-align: top; color: #666; font-size: 11px;">
                      <?php echo !empty($row['date']) ? htmlspecialchars($row['date']) : '-'; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; color: #999; padding: 20px; border: 1px solid #e9ecef; border-radius: 4px; background-color: #f8f9fa;">
              <i class="fa fa-info-circle" style="font-size: 20px; margin-bottom: 8px;"></i>
              <br>
              <small>No notes available</small>
            </div>
            <?php endif; ?>
            
            <!-- Summary totals below the table -->
            <?php if (!empty($outstandingData)): ?>
            <div style="margin-top: 15px; text-align: center;">
              <div style="color: #0984e3; font-weight: bold; font-size: 14px; padding: 6px; background-color: #f8f9fa; border-radius: 4px; margin-bottom: 8px;">
                Total Outstanding: ₹<?php echo moneyFormatIndia($totalOutstanding); ?>
              </div>
              <div style="color: #00b894; font-weight: bold; font-size: 14px; padding: 6px; background-color: #f8f9fa; border-radius: 4px;">
                Total Receivable: ₹<?php echo moneyFormatIndia($totalReceivable); ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row" style="margin-bottom: 24px;">
  <!-- Column 1: Amount Received - This Month -->
  <div class="col-md-4">
    <div class="x_panel" style="position:relative;">
      <div class="x_title">
        <h4>Amount Received <small>This Month</small></h4>
      </div>
      <div class="x_content">
        <canvas id="amountReceivedChart" height="180"></canvas>
        <!-- Summary totals at bottom -->
        <?php 
        $totalSpendReceived = 0;
        foreach ($amountReceivedData as $c) { 
            $totalSpendReceived += (int)$c['amount']; 
        }
        ?>
        <?php if (!empty($amountReceivedData)): ?>
        <div class="row" style="margin-top: 15px; text-align: center; border-top: 1px solid #eee; padding-top: 15px;">
          <div class="col-md-12">
            <div style="color: #00b894; font-weight: bold; font-size: 14px; padding: 6px; background-color: #f8f9fa; border-radius: 4px;">
              Total Spend Received: ₹<?php echo moneyFormatIndia($totalSpendReceived); ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Column 2: Spend Received -->
  <div class="col-md-4">
    <div class="x_panel" style="position:relative;">
      <div class="x_title">
        <h4>Spend Received <small>This Month</small></h4>
      </div>
      <div class="x_content">
        <canvas id="budgetRcdChart" height="180"></canvas>
        <!-- Summary totals at bottom -->
        <?php 
        $totalSpendReceived2 = 0;
        foreach ($topBudgetRcd as $c) { 
            $totalSpendReceived2 += (float)$c['bud_rcd']; 
        }
        ?>
        <?php if (!empty($topBudgetRcd)): ?>
        <div class="row" style="margin-top: 15px; text-align: center; border-top: 1px solid #eee; padding-top: 15px;">
          <div class="col-md-12">
            <div style="color: #00b894; font-weight: bold; font-size: 14px; padding: 6px; background-color: #f8f9fa; border-radius: 4px;">
              Total Spend Received: ₹<?php echo moneyFormatIndia($totalSpendReceived2); ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Column 3: Spend Not Received -->
  <div class="col-md-4">
    <div class="x_panel" style="position:relative;">
      <div class="x_title">
        <h4>Spend Not Received <small>This Month</small></h4>
      </div>
      <div class="x_content">
        <canvas id="budgetNotRcdChart" height="180"></canvas>
        <!-- Summary totals at bottom -->
        <?php 
        $totalSpendNotReceived = 0;
        foreach ($topBudgetNotRcd as $c) { 
            $totalSpendNotReceived += (float)$c['bud']; 
        }
        ?>
        <?php if (!empty($topBudgetNotRcd)): ?>
        <div class="row" style="margin-top: 15px; text-align: center; border-top: 1px solid #eee; padding-top: 15px;">
          <div class="col-md-12">
            <div style="color: #e17055; font-weight: bold; font-size: 14px; padding: 6px; background-color: #f8f9fa; border-radius: 4px;">
              Total Spend Not Received: ₹<?php echo moneyFormatIndia($totalSpendNotReceived); ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<style>
</style>
<?php if (!empty($outstandingData) || !empty($topBudgetRcd) || !empty($topBudgetNotRcd) || !empty($amountReceivedData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
let retainerLabels = <?php echo json_encode(array_map(function($c){return strtoupper(trim($c['client']));}, $outstandingData)); ?>;
let retainerData = <?php echo json_encode(array_map(function($c){return (int)str_replace([',',' '], '', $c['outstanding']);}, $outstandingData)); ?>;
let retainerDataFormatted = <?php echo json_encode(array_map(function($c){return moneyFormatIndia((int)str_replace([',',' '], '', $c['outstanding']));}, $outstandingData)); ?>;
let retainerReceivableData = <?php echo json_encode(array_map(function($c){return !empty($c['receivable']) ? (int)str_replace([',',' '], '', $c['receivable']) : 0;}, $outstandingData)); ?>;
let retainerReceivableDataFormatted = <?php echo json_encode(array_map(function($c){return !empty($c['receivable']) ? moneyFormatIndia((int)str_replace([',',' '], '', $c['receivable'])) : '0';}, $outstandingData)); ?>;
let retainerNotes = <?php echo json_encode(array_map(function($c){return !empty($c['notes']) ? $c['notes'] : '';}, $outstandingData)); ?>;
let budgetRcdLabels = <?php echo json_encode(array_map(function($c){return isset($c['tag']) ? strtoupper($c['tag']) : '';}, $topBudgetRcd)); ?>;
let budgetRcdData = <?php echo json_encode(array_map(function($c){return (float)$c['bud_rcd'];}, $topBudgetRcd)); ?>;
let budgetRcdDataFormatted = <?php echo json_encode(array_map(function($c){return moneyFormatIndia((float)$c['bud_rcd']);}, $topBudgetRcd)); ?>;
let budgetNotRcdLabels = <?php echo json_encode(array_map(function($c){return isset($c['tag']) ? strtoupper($c['tag']) : '';}, $topBudgetNotRcd)); ?>;
let budgetNotRcdData = <?php echo json_encode(array_map(function($c){return (float)$c['bud'];}, $topBudgetNotRcd)); ?>;
let budgetNotRcdDataFormatted = <?php echo json_encode(array_map(function($c){return moneyFormatIndia((float)$c['bud']);}, $topBudgetNotRcd)); ?>;
let amountReceivedLabels = <?php echo json_encode(array_map(function($c){return $c['client'];}, $amountReceivedData)); ?>;
let amountReceivedDataArr = <?php echo json_encode(array_map(function($c){return (int)$c['amount'];}, $amountReceivedData)); ?>;
let amountReceivedDataFormatted = <?php echo json_encode(array_map(function($c){return moneyFormatIndia((int)$c['amount']);}, $amountReceivedData)); ?>;
const palette = [
  '#0984e3','#fdcb6e','#00b894','#e17055','#6c5ce7','#00bcd4','#ff7675','#636e72','#fd79a8','#fab1a0'
];
const retainerColors = retainerLabels.map((_,i) => palette[i % palette.length]);
const budgetRcdColors = budgetRcdLabels.map((_,i) => palette[i % palette.length]);
const budgetNotRcdColors = budgetNotRcdLabels.map((_,i) => palette[i % palette.length]);
const amountReceivedColors = amountReceivedLabels.map((_,i) => palette[i % palette.length]);

function formatShortNumber(num) {
  if (num >= 100000) {
    return (num/100000).toFixed(1).replace(/\.0$/, '') + 'L';
  } else if (num >= 1000) {
    return (num/1000).toFixed(1).replace(/\.0$/, '') + 'K';
  } else {
    return num;
  }
}

// Calculate max values for y-axis
const retainerMax = Math.max(...retainerData, ...retainerReceivableData, 0);
const amountReceivedMax = Math.max(...amountReceivedDataArr, 0);
const budgetRcdMax = Math.max(...budgetRcdData, 0);
const budgetNotRcdMax = Math.max(...budgetNotRcdData, 0);

if (document.getElementById('retainerChart')) {
  new Chart(document.getElementById('retainerChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: retainerLabels,
      datasets: [
        {
          label: 'Outstanding (₹)',
          data: retainerData,
          backgroundColor: '#0984e3',
          borderColor: '#0984e3',
          borderWidth: 1,
          stack: 'Stack 0'
        },
        {
          label: 'Receivable (₹)',
          data: retainerReceivableData,
          backgroundColor: '#00b894',
          borderColor: '#00b894',
          borderWidth: 1,
          stack: 'Stack 0'
        }
      ]
    },
    options: {
      indexAxis: 'x',
      scales: { 
        y: { 
          beginAtZero: true, 
          max: retainerMax > 0 ? Math.ceil(retainerMax * 1.1) : undefined,
          stacked: true
        },
        x: {
          stacked: true
        }
      },
      plugins: {
        legend: { 
          display: true,
          position: 'top',
          labels: {
            usePointStyle: true,
            padding: 20
          }
        },
        datalabels: {
          anchor: 'center',
          align: 'center',
          color: '#fff',
          font: { weight: 'bold', size: 10 },
          formatter: function(value, context) {
            if (value > 0) {
              return formatShortNumber(value);
            }
            return '';
          }
        },
        tooltip: {
          callbacks: {
            title: function(context) {
              return context[0].label;
            },
            label: function(context) {
              let idx = context.dataIndex;
              let datasetIndex = context.datasetIndex;
              let tooltipText = '';
              
              if (datasetIndex === 0) {
                tooltipText = 'Outstanding: ₹' + retainerDataFormatted[idx];
              } else if (datasetIndex === 1) {
                tooltipText = 'Receivable: ₹' + retainerReceivableDataFormatted[idx];
                if (retainerNotes[idx]) {
                  tooltipText += '\nNotes: ' + retainerNotes[idx];
                }
              }
              
              return tooltipText;
            }
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
}

// Copy chart as image functionality
document.addEventListener('DOMContentLoaded', function() {
  const copyBtn = document.getElementById('copyChartBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function() {
      const chartPanel = document.querySelector('.x_panel');
      if (chartPanel) {
        // Show loading state
        copyBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Capturing...';
        copyBtn.disabled = true;
        
        html2canvas(chartPanel, {
          backgroundColor: '#ffffff',
          scale: 2,
          useCORS: true,
          allowTaint: true,
          logging: false,
          width: chartPanel.offsetWidth,
          height: chartPanel.offsetHeight
        }).then(function(canvas) {
          // Convert canvas to blob
          canvas.toBlob(function(blob) {
            // Create clipboard item
            const clipboardItem = new ClipboardItem({
              'image/png': blob
            });
            
            // Copy to clipboard
            navigator.clipboard.write([clipboardItem]).then(function() {
              // Success feedback
              copyBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
              copyBtn.className = 'btn btn-sm btn-success';
              
              // Reset button after 2 seconds
              setTimeout(function() {
                copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy as Image';
                copyBtn.className = 'btn btn-sm btn-primary';
                copyBtn.disabled = false;
              }, 2000);
            }).catch(function(err) {
              // Fallback: download image
              const link = document.createElement('a');
              link.download = 'outstanding-receivable-chart.png';
              link.href = canvas.toDataURL();
              link.click();
              
              copyBtn.innerHTML = '<i class="fa fa-download"></i> Downloaded!';
              copyBtn.className = 'btn btn-sm btn-success';
              
              setTimeout(function() {
                copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy as Image';
                copyBtn.className = 'btn btn-sm btn-primary';
                copyBtn.disabled = false;
              }, 2000);
            });
          }, 'image/png');
        }).catch(function(err) {
          console.error('Error capturing chart:', err);
          copyBtn.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error';
          copyBtn.className = 'btn btn-sm btn-danger';
          
          setTimeout(function() {
            copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy as Image';
            copyBtn.className = 'btn btn-sm btn-primary';
            copyBtn.disabled = false;
          }, 2000);
        });
      }
    });
  }
});

if (document.getElementById('amountReceivedChart')) {
  new Chart(document.getElementById('amountReceivedChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: amountReceivedLabels,
      datasets: [{
        label: 'Spend Received (₹)',
        data: amountReceivedDataArr,
        backgroundColor: amountReceivedColors,
        borderColor: amountReceivedColors,
        borderWidth: 1
      }]
    },
    options: {
      indexAxis: 'x',
      scales: { y: { beginAtZero: true, max: amountReceivedMax > 0 ? Math.ceil(amountReceivedMax * 1.1) : undefined } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'center',
          align: 'center',
          color: '#fff',
          font: { weight: 'bold', size: 11 },
          formatter: function(value) {
            return formatShortNumber(value);
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              let idx = context.dataIndex;
              return 'Spend Received: ₹' + amountReceivedDataFormatted[idx];
            }
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
}

if (document.getElementById('budgetRcdChart')) {
  new Chart(document.getElementById('budgetRcdChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: budgetRcdLabels,
      datasets: [{
        label: 'Spend Received (₹)',
        data: budgetRcdData,
        backgroundColor: budgetRcdColors,
        borderColor: budgetRcdColors,
        borderWidth: 1
      }]
    },
    options: {
      indexAxis: 'x',
      scales: { y: { beginAtZero: true, max: budgetRcdMax > 0 ? Math.ceil(budgetRcdMax * 1.1) : undefined } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'center',
          align: 'center',
          color: '#fff',
          font: { weight: 'bold', size: 11 },
          formatter: function(value) {
            return formatShortNumber(value);
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              let idx = context.dataIndex;
              return 'Spend Received: ₹' + budgetRcdDataFormatted[idx];
            }
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
}

if (document.getElementById('budgetNotRcdChart')) {
  new Chart(document.getElementById('budgetNotRcdChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: budgetNotRcdLabels,
      datasets: [{
        label: 'Spend Not Received (₹)',
        data: budgetNotRcdData,
        backgroundColor: budgetNotRcdColors,
        borderColor: budgetNotRcdColors,
        borderWidth: 1
      }]
    },
    options: {
      indexAxis: 'x',
      scales: { y: { beginAtZero: true, max: budgetNotRcdMax > 0 ? Math.ceil(budgetNotRcdMax * 1.1) : undefined } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'center',
          align: 'center',
          color: '#fff',
          font: { weight: 'bold', size: 11 },
          formatter: function(value) {
            return formatShortNumber(value);
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              let idx = context.dataIndex;
              return 'Spend Not Received: ₹' + budgetNotRcdDataFormatted[idx];
            }
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
}

// Copy chart as image functionality
document.addEventListener('DOMContentLoaded', function() {
  const copyBtn = document.getElementById('copyChartBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function() {
      const chartPanel = document.querySelector('.x_panel');
      if (chartPanel) {
        // Show loading state
        copyBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Capturing...';
        copyBtn.disabled = true;
        
        html2canvas(chartPanel, {
          backgroundColor: '#ffffff',
          scale: 2,
          useCORS: true,
          allowTaint: true,
          logging: false,
          width: chartPanel.offsetWidth,
          height: chartPanel.offsetHeight
        }).then(function(canvas) {
          // Convert canvas to blob
          canvas.toBlob(function(blob) {
            // Create clipboard item
            const clipboardItem = new ClipboardItem({
              'image/png': blob
            });
            
            // Copy to clipboard
            navigator.clipboard.write([clipboardItem]).then(function() {
              // Success feedback
              copyBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
              copyBtn.className = 'btn btn-sm btn-success';
              
              // Reset button after 2 seconds
              setTimeout(function() {
                copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy as Image';
                copyBtn.className = 'btn btn-sm btn-primary';
                copyBtn.disabled = false;
              }, 2000);
            }).catch(function(err) {
              // Fallback: download image
              const link = document.createElement('a');
              link.download = 'outstanding-receivable-chart.png';
              link.href = canvas.toDataURL();
              link.click();
              
              copyBtn.innerHTML = '<i class="fa fa-download"></i> Downloaded!';
              copyBtn.className = 'btn btn-sm btn-success';
              
              setTimeout(function() {
                copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy as Image';
                copyBtn.className = 'btn btn-sm btn-primary';
                copyBtn.disabled = false;
              }, 2000);
            });
          }, 'image/png');
        }).catch(function(err) {
          console.error('Error capturing chart:', err);
          copyBtn.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error';
          copyBtn.className = 'btn btn-sm btn-danger';
          
          setTimeout(function() {
            copyBtn.innerHTML = '<i class="fa fa-copy"></i> Copy as Image';
            copyBtn.className = 'btn btn-sm btn-primary';
            copyBtn.disabled = false;
          }, 2000);
        });
      }
    });
  }
});
</script>
<?php endif; ?>
<?php
}

function section_budget_and_cashflow_tabs_iframe() {
    ?>
    <div class="row" style="margin-bottom: 24px;">
      <div class="col-md-12">
        <div class="x_panel">
          <div class="x_title" style="display:flex; align-items:center; justify-content:space-between;">
            <h4 style="margin:0;">Budget and Cashflow</h4>
          </div>
          <div class="x_content" style="padding:0;">
            <ul class="nav nav-tabs" id="budgetCashflowTabs" role="tablist">
              <li class="active"><a href="#budgetTab" role="tab" data-toggle="tab">Budget</a></li>
              <li><a href="#cashflowTab" role="tab" data-toggle="tab">Cashflow</a></li>
            </ul>
            <div class="tab-content" style="margin-top:20px; min-height:70vh;">
              <div class="tab-pane fade in active" id="budgetTab">
                <iframe src="budget4.php?menu=hide" style="width:100%;height:70vh;border:none;"></iframe>
              </div>
              <div class="tab-pane fade" id="cashflowTab">
                <iframe src="cashflow-2025.php?menu=hide" style="width:100%;height:70vh;border:none;"></iframe>
              </div>
            </div>
            <script>
            $(document).ready(function() {
              $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable && $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
              });
            });
            </script>
            <style>
            #budgetCashflowTabs > li > a { font-weight:600; font-size:15px; }
            .tab-content iframe { min-height:70vh; }
            small {
                  font-size: 65% !important;
                  float: right;
              }
            </style>
          </div>
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
        if (isset($data['byt']) && !isset($_SESSION['ads'])) {
            dashboard_budget_cards($data['byt']);
        }
        // Top 10 High Retainer Fee and Top 10 Budget Received
        $clients = isset($data['client']) && is_array($data['client']) ? $data['client'] : [];
        if (!function_exists('formatShortNumberPHP')) {
          function formatShortNumberPHP($num) {
            if ($num >= 100000) {
              return round($num/100000, 1) . 'L';
            } elseif ($num >= 1000) {
              return round($num/1000, 1) . 'K';
            } else {
              return $num;
            }
          }
        }
        section_top10_retainer_and_budget($clients);
        // Budget and Cashflow section (tabs with iframe)
        section_budget_and_cashflow_tabs_iframe();
        ?>
      </div>
      <!-- /page content -->
    </div>
  </div>
  <?php include 'footer.php'; ?>


