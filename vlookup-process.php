<?php
/**
 * VLOOKUP Process Page
 * Handles form submission from vlookup.php:
 *  - Parses leads (CSV or DB)
 *  - Parses feedback CSV
 *  - Matches by phone
 *  - Groups & renders results
 *  - Handles export downloads
 *
 * Reuses: header.php, db.php, Bootstrap, DataTables
 */
include 'header.php';
include_once 'includes/vlookup-functions.php';
Auth();

$pgHeadline = 'VLOOKUP – Results';
$pgID       = 8;

// ── Handle Export Downloads before HTML output ───────────────────────────────
if (isset($_GET['export']) && isset($_SESSION['vlookup_result'])) {
    $res    = $_SESSION['vlookup_result'];
    $export = $_GET['export'];

    switch ($export) {
        case 'matched':
            $headers = array_merge(['Phone', 'Name', 'Email', 'Campaign', 'Adset', 'Ad', 'Form', 'Date', 'Status'], $res['fb_headers']);
            $rows = [];
            foreach ($res['matched'] as $l) {
                $base = [$l['phone_raw'] ?? $l['phone'], $l['full_name'], $l['email'], $l['campN'], $l['adsetN'], $l['adN'], $l['formN'], $l['created'] ?? '', $l['status']];
                $rows[] = array_merge($base, $l['fb_row'] ?? []);
            }
            vlookup_output_csv('vlookup_matched.csv', $headers, $rows);
            break;

        case 'unmatched':
            $headers = ['Phone', 'Name', 'Email', 'Campaign', 'Adset', 'Ad', 'Form', 'Date'];
            $rows = [];
            foreach ($res['unmatched'] as $l) {
                $rows[] = [$l['phone_raw'] ?? $l['phone'], $l['full_name'], $l['email'], $l['campN'], $l['adsetN'], $l['adN'], $l['formN'], $l['created'] ?? ''];
            }
            vlookup_output_csv('vlookup_unmatched.csv', $headers, $rows);
            break;

        case 'campaign_summary':
            $headers = ['Campaign', 'Total Leads', 'Matched', 'Unmatched', 'Contacted', 'Qualified', 'Visit/Demo', 'Closed/Sale'];
            $rows = [];
            foreach ($res['by_campaign'] as $g) {
                $b = vlookup_bucket_counts($g['statuses']);
                $rows[] = [$g['label'], $g['total'], $g['matched'], $g['unmatched'], $b['contacted'], $b['qualified'], $b['visit'], $b['closed']];
            }
            vlookup_output_csv('vlookup_campaign_summary.csv', $headers, $rows);
            break;

        case 'adset_summary':
            $headers = ['Adset', 'Total Leads', 'Matched', 'Unmatched', 'Contacted', 'Qualified', 'Visit/Demo', 'Closed/Sale'];
            $rows = [];
            foreach ($res['by_adset'] as $g) {
                $b = vlookup_bucket_counts($g['statuses']);
                $rows[] = [$g['label'], $g['total'], $g['matched'], $g['unmatched'], $b['contacted'], $b['qualified'], $b['visit'], $b['closed']];
            }
            vlookup_output_csv('vlookup_adset_summary.csv', $headers, $rows);
            break;

        case 'ad_summary':
            $headers = ['Ad', 'Total Leads', 'Matched', 'Unmatched', 'Contacted', 'Qualified', 'Visit/Demo', 'Closed/Sale'];
            $rows = [];
            foreach ($res['by_ad'] as $g) {
                $b = vlookup_bucket_counts($g['statuses']);
                $rows[] = [$g['label'], $g['total'], $g['matched'], $g['unmatched'], $b['contacted'], $b['qualified'], $b['visit'], $b['closed']];
            }
            vlookup_output_csv('vlookup_ad_summary.csv', $headers, $rows);
            break;
    }
}

// ── Process POST Submission ───────────────────────────────────────────────────
$error   = '';
$result  = null;

if (isset($_POST['submit'])) {

    // 1. Column mapping from form
    $col_map = [
        'phone'     => isset($_POST['col_phone'])     ? (int)$_POST['col_phone']     : 0,
        'status'    => isset($_POST['col_status'])    && $_POST['col_status'] !== '' ? (int)$_POST['col_status']    : -1,
        'contacted' => isset($_POST['col_contacted']) && $_POST['col_contacted'] !== '' ? (int)$_POST['col_contacted'] : -1,
        'qualified' => isset($_POST['col_qualified']) && $_POST['col_qualified'] !== '' ? (int)$_POST['col_qualified'] : -1,
        'visit'     => isset($_POST['col_visit'])     && $_POST['col_visit'] !== '' ? (int)$_POST['col_visit']     : -1,
        'closed'    => isset($_POST['col_closed'])    && $_POST['col_closed'] !== '' ? (int)$_POST['col_closed']   : -1,
    ];

    // 2. Parse Feedback CSV
    if (!isset($_FILES['feedback_csv']) || $_FILES['feedback_csv']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Feedback CSV upload failed. Please try again.';
    } else {
        $fb_tmp = $_FILES['feedback_csv']['tmp_name'];
        [$fb_headers, $fb_rows] = vlookup_parse_csv($fb_tmp);

        if (empty($fb_headers)) {
            $error = 'Could not parse feedback CSV. Check file format.';
        }
    }

    if (!$error) {
        // 3. Load Leads
        $leads = [];
        $lead_source = $_POST['lead_source'] ?? 'csv';

        if ($lead_source === 'csv') {
            if (!isset($_FILES['leads_csv']) || $_FILES['leads_csv']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Leads CSV upload failed. Please try again.';
            } else {
                $lcsv_tmp = $_FILES['leads_csv']['tmp_name'];
                [$lcsv_headers, $lcsv_rows] = vlookup_parse_csv($lcsv_tmp);
                // Auto-detect phone column in leads CSV
                $phone_idx_leads = 0;
                foreach ($lcsv_headers as $i => $h) {
                    if (preg_match('/phone|mobile|contact|mob/i', $h)) { $phone_idx_leads = $i; break; }
                }
                $name_idx  = 0; $email_idx = -1; $camp_idx = -1; $adset_idx = -1; $ad_idx = -1; $form_idx = -1;
                foreach ($lcsv_headers as $i => $h) {
                    if (preg_match('/name/i', $h))     $name_idx  = $i;
                    if (preg_match('/email/i', $h))    $email_idx = $i;
                    if (preg_match('/campaign/i', $h)) $camp_idx  = $i;
                    if (preg_match('/adset/i', $h))    $adset_idx = $i;
                    if (preg_match('/\bad\b|^ad$/i', $h)) $ad_idx = $i;
                    if (preg_match('/form/i', $h))     $form_idx  = $i;
                }
                foreach ($lcsv_rows as $row) {
                    $phone = isset($row[$phone_idx_leads]) ? $row[$phone_idx_leads] : '';
                    $leads[] = [
                        'phone'     => vlookup_normalize_phone($phone),
                        'phone_raw' => $phone,
                        'full_name' => $name_idx  >= 0 && isset($row[$name_idx])  ? $row[$name_idx]  : '',
                        'email'     => $email_idx >= 0 && isset($row[$email_idx]) ? $row[$email_idx] : '',
                        'campN'     => $camp_idx  >= 0 && isset($row[$camp_idx])  ? $row[$camp_idx]  : '',
                        'adsetN'    => $adset_idx >= 0 && isset($row[$adset_idx]) ? $row[$adset_idx] : '',
                        'adN'       => $ad_idx    >= 0 && isset($row[$ad_idx])    ? $row[$ad_idx]    : '',
                        'formN'     => $form_idx  >= 0 && isset($row[$form_idx])  ? $row[$form_idx]  : '',
                        'created'   => '',
                    ];
                }
            }
        } else {
            // DB source
            $page_id = $_POST['page_id'] ?? '';
            $stDt    = $_POST['stDt']    ?? date('m/d/Y', strtotime('-30 days'));
            $enDt    = $_POST['enDt']    ?? date('m/d/Y');
            if (empty($page_id)) {
                $error = 'Please select a page.';
            } else {
                $leads = vlookup_get_db_leads($conn, $page_id, $stDt, $enDt);
            }
        }
    }

    if (!$error && empty($leads)) {
        $error = 'No leads found to process.';
    }

    if (!$error) {
        // 4. Match
        $match_res   = vlookup_match_leads($leads, $fb_headers, $fb_rows, $col_map);
        $matched     = $match_res['matched'];
        $unmatched   = $match_res['unmatched'];
        $all_statuses= $match_res['all_statuses'];

        // 5. Group
        $by_campaign = vlookup_group_results($matched, $unmatched, 'campN',  $col_map, $all_statuses);
        $by_adset    = vlookup_group_results($matched, $unmatched, 'adsetN', $col_map, $all_statuses);
        $by_ad       = vlookup_group_results($matched, $unmatched, 'adN',    $col_map, $all_statuses);

        // Store in session for export
        $result = [
            'matched'      => $matched,
            'unmatched'    => $unmatched,
            'all_statuses' => $all_statuses,
            'by_campaign'  => $by_campaign,
            'by_adset'     => $by_adset,
            'by_ad'        => $by_ad,
            'fb_headers'   => $fb_headers,
            'col_map'      => $col_map,
        ];
        $_SESSION['vlookup_result'] = $result;
    }

} elseif (isset($_SESSION['vlookup_result'])) {
    $result = $_SESSION['vlookup_result'];
}

// Pull data for display
$matched      = $result['matched']      ?? [];
$unmatched    = $result['unmatched']    ?? [];
$all_statuses = $result['all_statuses'] ?? [];
$by_campaign  = $result['by_campaign']  ?? [];
$by_adset     = $result['by_adset']     ?? [];
$by_ad        = $result['by_ad']        ?? [];

$total_leads  = count($matched) + count($unmatched);
$match_pct    = $total_leads > 0 ? round((count($matched) / $total_leads) * 100, 1) : 0;
$buckets      = vlookup_bucket_counts($all_statuses);
?>

<body class="nav-md">
<div class="container body">
  <div class="main_container">
    <?php include 'menu-left.php'; include 'menu-top.php'; ?>

    <div class="right_col" role="main">
      <div class="row">
        <div class="col-md-12">

          <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
              <a href="vlookup.php" class="btn btn-sm btn-default" style="margin-left:10px;"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
          <?php endif; ?>

          <?php if ($result && !$error): ?>

          <!-- ── Summary Cards ── -->
          <div class="row" style="margin-bottom:20px;">
            <?php
            $cards = [
              ['icon'=>'fa-users',       'color'=>'#5bc0de', 'label'=>'Total Leads',    'val'=> $total_leads],
              ['icon'=>'fa-check-circle','color'=>'#5cb85c', 'label'=>'Matched',        'val'=> count($matched).' ('.$match_pct.'%)'],
              ['icon'=>'fa-times-circle','color'=>'#d9534f', 'label'=>'Unmatched',      'val'=> count($unmatched)],
              ['icon'=>'fa-phone',       'color'=>'#f0ad4e', 'label'=>'Contacted',      'val'=> $buckets['contacted']],
              ['icon'=>'fa-star',        'color'=>'#337ab7', 'label'=>'Qualified',      'val'=> $buckets['qualified']],
              ['icon'=>'fa-building',    'color'=>'#9b59b6', 'label'=>'Visit/Demo',     'val'=> $buckets['visit']],
              ['icon'=>'fa-trophy',      'color'=>'#2ecc71', 'label'=>'Closed/Booked',  'val'=> $buckets['closed']],
            ];
            foreach ($cards as $c):
            ?>
            <div class="col-md-1 col-sm-3 col-xs-6">
              <div class="x_panel tile" style="border-top:3px solid <?php echo $c['color']; ?>; text-align:center; padding:12px 8px;">
                <i class="fa <?php echo $c['icon']; ?>" style="font-size:24px; color:<?php echo $c['color']; ?>"></i>
                <div style="font-size:20px; font-weight:700; margin-top:5px;"><?php echo $c['val']; ?></div>
                <div style="font-size:11px; color:#888;"><?php echo $c['label']; ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Dynamic Status Counts -->
          <?php if (!empty($all_statuses)): ?>
          <div class="x_panel" style="margin-bottom:20px;">
            <div class="x_title"><h2><i class="fa fa-bar-chart"></i> Status Distribution</h2><div class="clearfix"></div></div>
            <div class="x_content">
              <div class="row">
                <?php arsort($all_statuses); foreach ($all_statuses as $st => $cnt): ?>
                <div class="col-md-2 col-sm-3 col-xs-4" style="margin-bottom:8px;">
                  <div class="badge" style="background:#337ab7; font-size:13px; width:100%; display:block; text-align:left; padding:6px 10px;">
                    <?php echo htmlspecialchars($st); ?>: <strong><?php echo $cnt; ?></strong>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- ── Export Buttons ── -->
          <div class="x_panel" style="margin-bottom:20px;">
            <div class="x_title"><h2><i class="fa fa-download"></i> Export Results</h2><div class="clearfix"></div></div>
            <div class="x_content">
              <a href="vlookup-process.php?export=matched"         class="btn btn-success btn-sm"><i class="fa fa-download"></i> Matched Leads</a>
              <a href="vlookup-process.php?export=unmatched"       class="btn btn-danger  btn-sm"><i class="fa fa-download"></i> Unmatched Leads</a>
              <a href="vlookup-process.php?export=campaign_summary" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Campaign Summary</a>
              <a href="vlookup-process.php?export=adset_summary"   class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Adset Summary</a>
              <a href="vlookup-process.php?export=ad_summary"      class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Ad Summary</a>
              <a href="vlookup.php" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> New VLOOKUP</a>
            </div>
          </div>

          <!-- ── Grouped Results Tabs ── -->
          <div class="x_panel">
            <div class="x_title">
              <h2><i class="fa fa-table"></i> Grouped Results</h2>
              <div class="clearfix"></div>
            </div>
            <div class="x_content">
              <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#tab-campaign" data-toggle="tab">Campaign Wise</a></li>
                <li role="presentation"><a href="#tab-adset"    data-toggle="tab">Adset Wise</a></li>
                <li role="presentation"><a href="#tab-ad"       data-toggle="tab">Ad Wise</a></li>
              </ul>
              <div class="tab-content" style="margin-top:15px;">

                <!-- Campaign Tab -->
                <div role="tabpanel" class="tab-pane active" id="tab-campaign">
                  <?php echo vlookup_render_group_table($by_campaign, $all_statuses, 'Campaign'); ?>
                </div>

                <!-- Adset Tab -->
                <div role="tabpanel" class="tab-pane" id="tab-adset">
                  <?php echo vlookup_render_group_table($by_adset, $all_statuses, 'Adset'); ?>
                </div>

                <!-- Ad Tab -->
                <div role="tabpanel" class="tab-pane" id="tab-ad">
                  <?php echo vlookup_render_group_table($by_ad, $all_statuses, 'Ad'); ?>
                </div>

              </div><!-- /tab-content -->
            </div><!-- /x_content -->
          </div><!-- /x_panel -->

          <?php elseif (!$error): ?>
            <div class="alert alert-info">Please go back and submit the VLOOKUP form.
              <a href="vlookup.php" class="btn btn-primary btn-sm" style="margin-left:10px;">Go Back</a>
            </div>
          <?php endif; ?>

        </div><!-- /col-md-12 -->
      </div><!-- /row -->
    </div><!-- /right_col -->
  </div><!-- /main_container -->
</div><!-- /container body -->

<?php

/**
 * Render a DataTable for a grouped result set.
 */
function vlookup_render_group_table($groups, $all_statuses, $label_col) {
    ob_start();
    $tbl_id = 'dt_' . strtolower($label_col);
    ?>
    <div class="table-responsive">
      <table id="<?php echo $tbl_id; ?>" class="table table-hover table-striped table-bordered datatable" style="width:100%;">
        <thead>
          <tr>
            <th><?php echo $label_col; ?></th>
            <th>Total</th>
            <th>Matched</th>
            <th>Unmatched</th>
            <th>Match %</th>
            <th>Contacted</th>
            <th>Qualified</th>
            <th>Visit/Demo</th>
            <th>Closed/Sale</th>
            <?php foreach (array_keys($all_statuses) as $st): ?>
              <th><?php echo htmlspecialchars($st); ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groups as $g):
              $b   = vlookup_bucket_counts($g['statuses']);
              $pct = $g['total'] > 0 ? round(($g['matched'] / $g['total']) * 100, 1) : 0;
          ?>
          <tr>
            <td><?php echo htmlspecialchars($g['label']); ?></td>
            <td><?php echo $g['total']; ?></td>
            <td><?php echo $g['matched']; ?></td>
            <td><?php echo $g['unmatched']; ?></td>
            <td><?php echo $pct; ?>%</td>
            <td><?php echo $b['contacted']; ?></td>
            <td><?php echo $b['qualified']; ?></td>
            <td><?php echo $b['visit']; ?></td>
            <td><?php echo $b['closed']; ?></td>
            <?php foreach (array_keys($all_statuses) as $st): ?>
              <td><?php echo $g['statuses'][$st] ?? 0; ?></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <script>
    $(function(){
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#<?php echo $tbl_id; ?>')) {
            $('#<?php echo $tbl_id; ?>').DataTable({
                pageLength: 25, order: [[1, 'desc']],
                dom: 'Bfrtip', buttons: ['copy', 'excel', 'print']
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
?>
<?php include 'footer.php'; ?>
