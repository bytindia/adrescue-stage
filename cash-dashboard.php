<?php
/**
 * Cash Exposure Dashboard — "Out of Our Hand Cash"
 *
 * Sources:
 *  - budget4.php logic  → budget_reminder table (client, cc_card, spend, budget_received)
 *  - index-acc.php logic → soa-api.php JSON (outstanding)
 *  - dash/multi-client.php → current spend per platform
 *
 * Rules:
 *  cc_card = 1 (BYT)    → company money at risk
 *  cc_card = 2 (Client) → client's own money, no company exposure
 *
 * Reuses: header.php, db.php, menu-left.php, Bootstrap, DataTables
 */
include 'header.php';
Auth();

$pgHeadline = 'Cash Exposure Dashboard';
$pgID       = 8;

// ── Helper ───────────────────────────────────────────────────────────────────
function moneyFormatIndia2($num) {
    if ($num < 0) return '-' . moneyFormatIndia2(abs($num));
    $num = round($num);
    if (strlen((string)$num) > 3) {
        $lastthree  = substr($num, -3);
        $restunits  = substr($num, 0, -3);
        $restunits  = (strlen($restunits) % 2 == 1) ? '0' . $restunits : $restunits;
        $expunit    = str_split($restunits, 2);
        $result = '';
        foreach ($expunit as $i => $v) {
            $result .= ($i == 0) ? (int)$v . ',' : $v . ',';
        }
        return $result . $lastthree;
    }
    return (string)$num;
}

function soa_amount_to_int($str) {
    return (int)str_replace([',', ' '], '', $str);
}

// ── 1. Load spend data from budget_reminder ──────────────────────────────────
$tblN = 'budget_reminder';
if (isset($_GET['last_mon'])) { $tblN = 'budget_reminder_last_mon'; }

$uid = (int)$_SESSION['uid'];
$sqlRev = mysqli_query($conn, "SELECT * FROM $tblN WHERE uid=$uid AND delete_status=0 ORDER BY cc_card ASC");

$clients_spend = [];
while ($row = mysqli_fetch_assoc($sqlRev)) {
    // Sum all platform spends (multi-value comma lists)
    $fb_sp = $g_sp = $in_sp = $ta_sp = 0;
    if ($row['fb_spent'] != '') { $fb_sp = array_sum(array_filter(explode(',', $row['fb_spent']))); }
    if ($row['g_spent']  != '') { $g_sp  = array_sum(array_filter(explode(',', $row['g_spent']))); }
    if ($row['in_spent'] != '') { $in_sp = array_sum(array_filter(explode(',', $row['in_spent']))); }
    if ($row['ta_spent'] != '') { $ta_sp = array_sum(array_filter(explode(',', $row['ta_spent']))); }
    $tot_spend = $fb_sp + $g_sp + $in_sp + $ta_sp;

    // Budget received
    $bud_received = 0;
    if (!empty($row['budget_received'])) {
        $bud_received = array_sum(array_filter(explode(',', $row['budget_received'])));
    }

    $retainer      = (float)($row['retainer_fee'] ?? 0);
    $total_budget  = (float)($row['total_budget']  ?? 0);
    $cc_card       = (int)$row['cc_card'];    // 1=BYT, 2=Client
    $source_label  = ($cc_card == 2) ? 'Client CC' : 'BYT CC';

    // Out of Hand Cash = total spend in platform not yet received from client
    // For BYT CC: company paid upfront → exposure = spend - received
    // For Client CC: client paid → no BYT exposure
    $out_of_hand   = ($cc_card == 1) ? max(0, $tot_spend - $bud_received) : 0;
    $exposure_note = ($cc_card == 1) ? $out_of_hand : 0;

    $clients_spend[] = [
        'tbl_id'       => $row['tbl_id'],
        'client_name'  => $row['client_name'],
        'cc_card'      => $cc_card,
        'source_label' => $source_label,
        'total_budget' => $total_budget,
        'tot_spend'    => $tot_spend,
        'bud_received' => $bud_received,
        'retainer'     => $retainer,
        'out_of_hand'  => $out_of_hand,
        'exposure'     => $exposure_note,
        'outstanding'  => 0,  // filled below from SOA
    ];
}

// ── 2. Load Outstanding from soa-api.php ────────────────────────────────────
// ── ⚑ REPLACE URL with your actual soa-api.php endpoint
$soa_url  = 'https://stage.adrescue.in/soa-api.php';
$soa_json = @file_get_contents($soa_url);
$soa_data = $soa_json ? json_decode($soa_json, true) : [];

// Build a lookup: client_name (normalized) => outstanding amount
$outstanding_lookup = [];
if (isset($soa_data['outstanding']) && is_array($soa_data['outstanding'])) {
    foreach ($soa_data['outstanding'] as $orow) {
        $client_key = strtolower(trim($orow['client'] ?? ''));
        $amt        = soa_amount_to_int($orow['outstanding'] ?? '0');
        $outstanding_lookup[$client_key] = $amt;
    }
}

// ── 3. Merge outstanding into clients_spend ───────────────────────────────────
foreach ($clients_spend as &$c) {
    $key = strtolower(trim($c['client_name']));
    // Try exact match, then partial
    if (isset($outstanding_lookup[$key])) {
        $c['outstanding'] = $outstanding_lookup[$key];
    } else {
        foreach ($outstanding_lookup as $okey => $oval) {
            if (strpos($okey, $key) !== false || strpos($key, $okey) !== false) {
                $c['outstanding'] = $oval; break;
            }
        }
    }
    // Money at Risk = outstanding + out_of_hand_cash (for BYT accounts)
    $c['money_at_risk'] = ($c['cc_card'] == 1) ? $c['out_of_hand'] + $c['outstanding'] : 0;
    // Risk Badge
    if ($c['cc_card'] == 2) {
        $c['risk_badge'] = '<span class="badge" style="background:#5bc0de;">Client Money</span>';
    } elseif ($c['money_at_risk'] > 500000) {
        $c['risk_badge'] = '<span class="badge" style="background:#d9534f;">High Risk</span>';
    } elseif ($c['money_at_risk'] > 100000) {
        $c['risk_badge'] = '<span class="badge" style="background:#f0ad4e;">Medium Risk</span>';
    } else {
        $c['risk_badge'] = '<span class="badge" style="background:#5cb85c;">Low Risk</span>';
    }
}
unset($c);

// ── 4. Totals ────────────────────────────────────────────────────────────────
$grand_spend   = array_sum(array_column($clients_spend, 'tot_spend'));
$grand_ooh     = array_sum(array_column($clients_spend, 'out_of_hand'));
$grand_outst   = array_sum(array_column($clients_spend, 'outstanding'));
$grand_risk    = array_sum(array_column($clients_spend, 'money_at_risk'));
$byt_count     = count(array_filter($clients_spend, fn($c) => $c['cc_card'] == 1));
$client_count  = count(array_filter($clients_spend, fn($c) => $c['cc_card'] == 2));
?>

<body class="nav-md">
<div class="container body">
  <div class="main_container">
    <?php include 'menu-left.php'; include 'menu-top.php'; ?>

    <div class="right_col" role="main">
      <div class="row">
        <div class="col-md-12">

          <!-- ── Summary Cards ─────────────────────────────────────────────── -->
          <div class="row" style="margin-bottom:20px;">
            <?php
            $cards = [
              ['label'=>'Total Clients',       'val'=> count($clients_spend),    'color'=>'#337ab7', 'icon'=>'fa-users'],
              ['label'=>'BYT CC Clients',      'val'=> $byt_count,               'color'=>'#d9534f', 'icon'=>'fa-credit-card'],
              ['label'=>'Client CC Clients',   'val'=> $client_count,            'color'=>'#5bc0de', 'icon'=>'fa-user'],
              ['label'=>'Total Spend',         'val'=> '₹'.moneyFormatIndia2($grand_spend), 'color'=>'#5cb85c', 'icon'=>'fa-bar-chart'],
              ['label'=>'Out of Hand Cash',    'val'=> '₹'.moneyFormatIndia2($grand_ooh),   'color'=>'#f0ad4e', 'icon'=>'fa-money'],
              ['label'=>'Total Outstanding',   'val'=> '₹'.moneyFormatIndia2($grand_outst), 'color'=>'#e67e22', 'icon'=>'fa-exclamation-circle'],
              ['label'=>'Company Exposure',    'val'=> '₹'.moneyFormatIndia2($grand_risk),  'color'=>'#d9534f', 'icon'=>'fa-shield'],
            ];
            foreach ($cards as $c):
            ?>
            <div class="col-md-1 col-sm-4 col-xs-6" style="margin-bottom:10px;">
              <div class="x_panel tile" style="border-top:3px solid <?php echo $c['color']; ?>; text-align:center; padding:12px 6px;">
                <i class="fa <?php echo $c['icon']; ?>" style="font-size:22px; color:<?php echo $c['color']; ?>"></i>
                <div style="font-size:17px; font-weight:700; margin-top:5px;"><?php echo $c['val']; ?></div>
                <div style="font-size:11px; color:#888;"><?php echo $c['label']; ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div><!-- /summary cards -->

          <!-- ── Filter Tabs ──────────────────────────────────────────────── -->
          <div class="x_panel">
            <div class="x_title">
              <h2><i class="fa fa-money"></i> Cash Exposure — Per Client
                <?php if (isset($_GET['last_mon'])): ?>
                  <small>Last Month</small> <a href="cash-dashboard.php" class="btn btn-xs btn-default">View This Month</a>
                <?php else: ?>
                  <small>This Month</small> <a href="cash-dashboard.php?last_mon=1" class="btn btn-xs btn-default">View Last Month</a>
                <?php endif; ?>
              </h2>
              <ul class="nav navbar-right panel_toolbox">
                <li><a href="#" class="btn btn-default btn-sm" onclick="filterTable('all')">All</a></li>
                <li><a href="#" class="btn btn-danger  btn-sm" onclick="filterTable('byt')">Company Money (BYT)</a></li>
                <li><a href="#" class="btn btn-info    btn-sm" onclick="filterTable('client')">Client Money</a></li>
                <li><a href="#" class="btn btn-warning btn-sm" onclick="filterTable('highrisk')">High Risk</a></li>
              </ul>
              <div class="clearfix"></div>
            </div><!-- /x_title -->

            <div class="x_content">
              <div class="table-responsive">
                <table id="cashDashTable" class="table table-hover table-striped table-bordered datatable" style="width:100%;">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Client Name</th>
                      <th>Source</th>
                      <th>Current Spend</th>
                      <th>Budget Recd.</th>
                      <th>Outstanding</th>
                      <th>Out of Hand Cash</th>
                      <th>Company Exposure</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1; foreach ($clients_spend as $c): ?>
                    <tr data-source="<?php echo $c['cc_card'] == 1 ? 'byt' : 'client'; ?>"
                        data-risk="<?php echo $c['money_at_risk'] > 500000 ? 'highrisk' : 'normal'; ?>">
                      <td><?php echo $i++; ?></td>
                      <td><strong><?php echo htmlspecialchars($c['client_name']); ?></strong></td>
                      <td><?php echo $c['source_label']; ?></td>
                      <td class="text-right">₹<?php echo moneyFormatIndia2($c['tot_spend']); ?></td>
                      <td class="text-right">₹<?php echo moneyFormatIndia2($c['bud_received']); ?></td>
                      <td class="text-right">
                        <?php if ($c['outstanding'] > 0): ?>
                          <span style="color:#d9534f; font-weight:600;">₹<?php echo moneyFormatIndia2($c['outstanding']); ?></span>
                        <?php else: ?> — <?php endif; ?>
                      </td>
                      <td class="text-right">
                        <?php if ($c['cc_card'] == 1 && $c['out_of_hand'] > 0): ?>
                          <span style="color:#f0ad4e; font-weight:600;">₹<?php echo moneyFormatIndia2($c['out_of_hand']); ?></span>
                        <?php elseif ($c['cc_card'] == 2): ?>
                          <span class="text-muted">N.A.</span>
                        <?php else: ?> — <?php endif; ?>
                      </td>
                      <td class="text-right">
                        <?php if ($c['cc_card'] == 1): ?>
                          <span style="color:<?php echo $c['money_at_risk'] > 0 ? '#d9534f' : '#888'; ?>; font-weight:600;">
                            ₹<?php echo moneyFormatIndia2($c['money_at_risk']); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">N.A.</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo $c['risk_badge']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr style="font-weight:700; background:#f5f5f5;">
                      <td colspan="3">TOTAL</td>
                      <td class="text-right">₹<?php echo moneyFormatIndia2($grand_spend); ?></td>
                      <td></td>
                      <td class="text-right">₹<?php echo moneyFormatIndia2($grand_outst); ?></td>
                      <td class="text-right">₹<?php echo moneyFormatIndia2($grand_ooh); ?></td>
                      <td class="text-right" style="color:#d9534f;">₹<?php echo moneyFormatIndia2($grand_risk); ?></td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div><!-- /table-responsive -->
            </div><!-- /x_content -->
          </div><!-- /x_panel -->

        </div><!-- /col-md-12 -->
      </div><!-- /row -->
    </div><!-- /right_col -->
  </div><!-- /main_container -->
</div><!-- /container body -->

<script>
$(function () {
    var dt = $('#cashDashTable').DataTable({
        pageLength: 50,
        order: [[7, 'desc']],
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'print']
    });

    window.filterTable = function (f) {
        dt.rows().every(function () {
            var $row = $(this.node());
            var src  = $row.data('source');
            var risk = $row.data('risk');
            if (f === 'all')      { this.node().style.display = ''; }
            else if (f === 'byt') { this.node().style.display = (src === 'byt')    ? '' : 'none'; }
            else if (f === 'client') { this.node().style.display = (src === 'client') ? '' : 'none'; }
            else if (f === 'highrisk') { this.node().style.display = (risk === 'highrisk') ? '' : 'none'; }
        });
        dt.draw();
    };
});
</script>

<?php include 'footer.php'; ?>
