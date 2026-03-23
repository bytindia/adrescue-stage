<?php
/**
 * VLOOKUP Module
 * Matches Meta leads (from CSV upload OR existing MySQL DB) against
 * a feedback sheet CSV, then shows grouped analytics by campaign/adset/ad.
 *
 * Reuses: header.php, db.php, menu-left.php, menu-top.php, Bootstrap, DataTables
 */
include 'header.php';
include_once 'includes/vlookup-functions.php';
Auth();

$pgHeadline = 'VLOOKUP - Leads Feedback Matcher';
$pgID       = 8;

// Load pages for DB source dropdown
$pages = vlookup_get_pages($conn, $_SESSION['uid']);
?>

<body class="nav-md">
<div class="container body">
  <div class="main_container">
    <?php include 'menu-left.php'; include 'menu-top.php'; ?>

    <div class="right_col" role="main">
      <div class="row">
        <div class="col-md-12">

          <!-- ── STEP 1: Choose Source + Upload Feedback ── -->
          <div class="x_panel" id="step1-panel">
            <div class="x_title">
              <h2><i class="fa fa-table"></i> VLOOKUP – Leads Feedback Matcher</h2>
              <div class="clearfix"></div>
            </div>
            <div class="x_content">
              <?php include 'alert.php'; ?>

              <form method="post" action="vlookup-process.php" enctype="multipart/form-data" id="vlookupForm">
                <div class="row">

                  <!-- LEFT: Lead Source -->
                  <div class="col-md-5">
                    <div class="panel panel-default">
                      <div class="panel-heading"><strong>Step 1: Choose Lead Source</strong></div>
                      <div class="panel-body">
                        <div class="form-group">
                          <label>Lead Source</label>
                          <div>
                            <label class="radio-inline">
                              <input type="radio" name="lead_source" value="csv" id="src_csv" checked> Upload CSV
                            </label>
                            &nbsp;&nbsp;
                            <label class="radio-inline">
                              <input type="radio" name="lead_source" value="db" id="src_db"> Use Meta Leads from DB
                            </label>
                          </div>
                        </div>

                        <!-- CSV Upload -->
                        <div id="div_leads_csv">
                          <div class="form-group">
                            <label>Upload Leads CSV <span class="text-danger">*</span></label>
                            <input type="file" name="leads_csv" id="leads_csv" accept=".csv" class="form-control">
                            <small class="text-muted">Must have a phone column. Headers detected automatically.</small>
                          </div>
                        </div>

                        <!-- DB Source -->
                        <div id="div_leads_db" style="display:none;">
                          <div class="form-group">
                            <label>Select Page / Client <span class="text-danger">*</span></label>
                            <select name="page_id" class="form-control selectpicker" data-live-search="true" title="-- Select Page --">
                              <?php foreach ($pages as $pg): ?>
                                <option value="<?php echo $pg['pg_id']; ?>">
                                  <?php echo htmlspecialchars($pg['client_name'] . ' — ' . $pg['pg_name']); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="row">
                            <div class="col-xs-6">
                              <div class="form-group">
                                <label>From Date</label>
                                <input type="text" name="stDt" id="stDt_vl" class="form-control datepicker"
                                       value="<?php echo date('m/d/Y', strtotime('-30 days')); ?>">
                              </div>
                            </div>
                            <div class="col-xs-6">
                              <div class="form-group">
                                <label>To Date</label>
                                <input type="text" name="enDt" id="enDt_vl" class="form-control datepicker"
                                       value="<?php echo date('m/d/Y'); ?>">
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div><!-- /col-md-5 -->

                  <!-- RIGHT: Feedback Sheet -->
                  <div class="col-md-7">
                    <div class="panel panel-default">
                      <div class="panel-heading"><strong>Step 2: Upload Feedback Sheet CSV</strong></div>
                      <div class="panel-body">
                        <div class="form-group">
                          <label>Feedback CSV <span class="text-danger">*</span></label>
                          <input type="file" name="feedback_csv" id="feedback_csv" accept=".csv" class="form-control" required>
                          <small class="text-muted">
                            The system will read headers dynamically and ask you to map columns below.
                          </small>
                        </div>

                        <!-- Dynamic Column Mapping — shown after file selected via JS preview -->
                        <div id="col_mapping_section" style="display:none;">
                          <hr>
                          <h4>Step 3: Map Columns</h4>
                          <small class="text-muted">Select which column in your feedback sheet corresponds to each field.</small>
                          <div class="row" id="col_mapping_rows" style="margin-top:10px;">
                            <!-- Populated by JS after CSV headers are sniffed client-side -->
                          </div>
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                          <button type="submit" name="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fa fa-search"></i> Run VLOOKUP
                          </button>
                        </div>
                      </div>
                    </div>
                  </div><!-- /col-md-7 -->

                </div><!-- /row -->
              </form><!-- /form -->
            </div><!-- /x_content -->
          </div><!-- /x_panel -->

        </div><!-- /col-md-12 -->
      </div><!-- /row -->
    </div><!-- /right_col -->
  </div><!-- /main_container -->
</div><!-- /container body -->

<script>
// ── Toggle Lead Source ──────────────────────────────────────────────────────
$('input[name="lead_source"]').on('change', function () {
    if ($(this).val() === 'csv') {
        $('#div_leads_csv').show();
        $('#div_leads_db').hide();
        $('#leads_csv').prop('required', true);
        $('select[name="page_id"]').prop('required', false);
    } else {
        $('#div_leads_csv').hide();
        $('#div_leads_db').show();
        $('#leads_csv').prop('required', false);
        $('select[name="page_id"]').prop('required', true);
    }
});

// ── Parse CSV Headers Client-side for Column Mapping ──────────────────────
$('#feedback_csv').on('change', function (e) {
    var file = e.target.files[0];
    if (!file) { $('#col_mapping_section').hide(); return; }

    var reader = new FileReader();
    reader.onload = function (ev) {
        var text  = ev.target.result;
        var lines = text.split('\n');
        if (lines.length === 0) return;
        // Parse first row as headers (handle quoted CSV)
        var headers = parseCSVLine(lines[0]);

        buildMappingUI(headers);
        $('#col_mapping_section').show();
    };
    reader.readAsText(file);
});

function parseCSVLine(line) {
    var result = [], current = '', inQ = false;
    for (var i = 0; i < line.length; i++) {
        var ch = line[i];
        if (ch === '"') { inQ = !inQ; }
        else if (ch === ',' && !inQ) { result.push(current.trim()); current = ''; }
        else { current += ch; }
    }
    result.push(current.trim());
    return result;
}

function buildMappingUI(headers) {
    var opts = '<option value="">-- Skip --</option>';
    $.each(headers, function (i, h) {
        opts += '<option value="' + i + '">' + i + ': ' + $('<div>').text(h).html() + '</option>';
    });

    // Auto-detect common columns
    function autoSelect(keywords) {
        var found = '';
        $.each(headers, function (i, h) {
            var hl = h.toLowerCase();
            $.each(keywords, function (_, kw) {
                if (hl.indexOf(kw) !== -1 && found === '') { found = i; }
            });
        });
        return found;
    }

    var fields = [
        { name: 'col_phone',     label: 'Phone / Mobile <span class="text-danger">*</span>', hint: 'required', kw: ['phone','mobile','contact','mob'] },
        { name: 'col_status',    label: 'Lead Status',        kw: ['status','stage','disposition','outcome','remark'] },
        { name: 'col_contacted', label: 'Contacted Flag',     kw: ['contact','call','reach','attempt'] },
        { name: 'col_qualified', label: 'Qualified Flag',     kw: ['qualif','hot','interest','prospect'] },
        { name: 'col_visit',     label: 'Visit / Demo Flag',  kw: ['visit','demo','meeting','walk'] },
        { name: 'col_closed',    label: 'Closed / Sale',      kw: ['close','sale','book','won','paid','convert'] },
    ];

    var html = '';
    $.each(fields, function (_, f) {
        var auto = autoSelect(f.kw);
        var selOpts = opts.replace('value="' + auto + '"', 'value="' + auto + '" selected');
        html += '<div class="col-md-6"><div class="form-group">';
        html += '<label>' + f.label + '</label>';
        html += '<select name="' + f.name + '" class="form-control">' + selOpts + '</select>';
        html += '</div></div>';
    });

    $('#col_mapping_rows').html(html);
}

// ── Submit Guard ────────────────────────────────────────────────────────────
$('#vlookupForm').on('submit', function () {
    if ($('#col_mapping_section').is(':visible') && $('select[name="col_phone"]').val() === '') {
        alert('Please map the Phone column before running VLOOKUP.');
        return false;
    }
    $('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
});

// ── Date Pickers ────────────────────────────────────────────────────────────
$(function(){
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({ dateFormat: 'mm/dd/yy' });
    }
});
</script>

<?php include 'footer.php'; // adjust to your actual footer include if different ?>
