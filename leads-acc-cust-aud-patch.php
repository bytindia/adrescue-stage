/**
 * ============================================================
 * PATCH FILE: leads-acc.php & leads-acc-add.php
 * Custom Audience Automation — UI Changes
 * ============================================================
 *
 * HOW TO APPLY:
 * 1. leads-acc.php  — add a "Cust. Audience" column to the table
 * 2. leads-acc-add.php — add the audience setup section to the form
 *
 * ============================================================
 *
 * PATCH A: leads-acc.php — Add column header after existing <th> list
 * Find: <th>Email IDs</th>   (or similar last column)
 * Add AFTER IT:
 *   <th>Cust. Audience</th>
 *
 * And in the table body row, after the existing <td> columns, add:
 *
 *   <td>
 *     <?php if ($sqlROW['cust_aud_enabled'] == 1): ?>
 *       <span class="badge" style="background:#5cb85c;">
 *         <i class="fa fa-users"></i>
 *         <?php echo htmlspecialchars($sqlROW['cust_aud_name'] ?: 'Enabled'); ?>
 *       </span>
 *     <?php else: ?>
 *       <span class="text-muted">—</span>
 *     <?php endif; ?>
 *   </td>
 *
 * ============================================================
 * PATCH B: leads-acc-add.php
 * Paste the FORM BLOCK below INSIDE the existing <form> just before
 * the submit button. Works for both INSERT and UPDATE modes.
 * ============================================================
 */
?>
<!-- ── Custom Audience Automation Section ────────────────────────────────── -->
<div class="panel panel-default" style="margin-top:20px;">
  <div class="panel-heading">
    <strong><i class="fa fa-users"></i> Custom Audience Automation</strong>
  </div>
  <div class="panel-body">

    <div class="form-group">
      <label>
        <input type="checkbox" name="cust_aud_enabled" id="cust_aud_enabled" value="1"
          <?php if (!empty($editRow['cust_aud_enabled']) && $editRow['cust_aud_enabled'] == 1) echo 'checked'; ?>>
        &nbsp; Enable Custom Audience Automation
      </label>
      <small class="form-text text-muted">If enabled, each new lead received via webhook will be hashed and pushed to the selected Meta Custom Audience.</small>
    </div>

    <div id="cust_aud_options" style="<?php echo (!empty($editRow['cust_aud_enabled']) && $editRow['cust_aud_enabled']) ? '' : 'display:none;'; ?>">

      <!-- Ad Account -->
      <div class="form-group">
        <label>Meta Ad Account <span class="text-danger">*</span></label>
        <select name="cust_aud_ad_account" id="cust_aud_ad_account" class="form-control selectpicker" data-live-search="true" title="-- Select Ad Account --">
          <?php
          $adAccRes = mysqli_query($conn, "SELECT account_id, name FROM adAccounts WHERE uid='".$_SESSION['uid']."' ORDER BY name ASC");
          while ($aRow = mysqli_fetch_assoc($adAccRes)):
              $sel = (!empty($editRow['cust_aud_ad_account']) && $editRow['cust_aud_ad_account'] == $aRow['account_id']) ? 'selected' : '';
          ?>
          <option value="<?php echo $aRow['account_id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($aRow['name']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Create vs Use Existing -->
      <div class="form-group">
        <label>Audience Option</label>
        <div>
          <label class="radio-inline">
            <input type="radio" name="aud_option" value="existing" id="aud_opt_existing" checked> Use Existing Audience
          </label>
          &nbsp;&nbsp;
          <label class="radio-inline">
            <input type="radio" name="aud_option" value="new" id="aud_opt_new"> Create New Audience
          </label>
        </div>
      </div>

      <!-- Existing Audience -->
      <div id="div_existing_aud">
        <div class="form-group">
          <label>Select Audience</label>
          <div class="input-group">
            <select name="cust_aud_id" id="cust_aud_id_select" class="form-control selectpicker" data-live-search="true" title="-- Load audiences first --">
              <?php if (!empty($editRow['cust_aud_id'])): ?>
                <option value="<?php echo $editRow['cust_aud_id']; ?>" selected><?php echo htmlspecialchars($editRow['cust_aud_name'] ?: $editRow['cust_aud_id']); ?></option>
              <?php endif; ?>
            </select>
            <span class="input-group-btn">
              <button type="button" class="btn btn-default" onclick="loadAudiences()">
                <i class="fa fa-refresh"></i> Load
              </button>
            </span>
          </div>
          <input type="hidden" name="cust_aud_name" id="cust_aud_name_hidden" value="<?php echo htmlspecialchars($editRow['cust_aud_name'] ?? ''); ?>">
        </div>
      </div>

      <!-- New Audience -->
      <div id="div_new_aud" style="display:none;">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>New Audience Name <span class="text-danger">*</span></label>
              <input type="text" name="new_aud_name" id="new_aud_name" class="form-control" placeholder="e.g. ProjectName - Leads 2025">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Description</label>
              <input type="text" name="new_aud_desc" id="new_aud_desc" class="form-control" placeholder="Optional description">
            </div>
          </div>
        </div>
        <div class="form-group">
          <button type="button" class="btn btn-warning" onclick="createAudience()">
            <i class="fa fa-plus"></i> Create Audience on Meta
          </button>
          <span id="aud_create_status" style="margin-left:10px; font-weight:bold;"></span>
        </div>
        <!-- Hidden fields filled after creation -->
        <input type="hidden" name="cust_aud_id"   id="cust_aud_id_new"   value="">
      </div>
    </div><!-- /cust_aud_options -->

  </div><!-- /panel-body -->
</div><!-- /panel -->

<script>
// Toggle audience options
$('#cust_aud_enabled').on('change', function () {
    if ($(this).is(':checked')) { $('#cust_aud_options').show(); }
    else                        { $('#cust_aud_options').hide(); }
});

// Toggle existing vs new
$('input[name="aud_option"]').on('change', function () {
    if ($(this).val() === 'new') {
        $('#div_existing_aud').hide();
        $('#div_new_aud').show();
    } else {
        $('#div_existing_aud').show();
        $('#div_new_aud').hide();
    }
});

// Load Audiences from Meta
function loadAudiences() {
    var ad_account = $('#cust_aud_ad_account').val();
    if (!ad_account) { alert('Please select an Ad Account first.'); return; }
    $('#cust_aud_id_select').html('<option>Loading...</option>');
    $.post('ajax-cust-aud-setup.php', { action: 'get_audiences', ad_account: ad_account }, function (res) {
        if (res.success) {
            var opts = '<option value="">-- Select Audience --</option>';
            $.each(res.audiences, function (_, a) {
                opts += '<option value="' + a.id + '" data-name="' + $('<div>').text(a.name).html() + '">' + a.name + ' (ID:' + a.id + ')</option>';
            });
            $('#cust_aud_id_select').html(opts);
            if ($.fn.selectpicker) { $('#cust_aud_id_select').selectpicker('refresh'); }
        } else {
            alert('Failed to load audiences: ' + res.msg);
        }
    }, 'json');
}

// Track selected audience name
$('#cust_aud_id_select').on('change', function () {
    var name = $(this).find(':selected').data('name') || '';
    $('#cust_aud_name_hidden').val(name);
});

// Create New Audience via Meta API
function createAudience() {
    var ad_account = $('#cust_aud_ad_account').val();
    var aud_name   = $('#new_aud_name').val().trim();
    var aud_desc   = $('#new_aud_desc').val().trim();
    if (!ad_account || !aud_name) { alert('Ad account and audience name are required.'); return; }

    $('#aud_create_status').html('<i class="fa fa-spinner fa-spin"></i> Creating...');
    $.post('ajax-cust-aud-setup.php', {
        action: 'create_audience', ad_account: ad_account, aud_name: aud_name, aud_desc: aud_desc
    }, function (res) {
        if (res.success) {
            $('#cust_aud_id_new').val(res.audience_id);
            $('#cust_aud_name_hidden').val(res.audience_name);
            $('#aud_create_status').html('<span style="color:green;"><i class="fa fa-check"></i> Created! ID: ' + res.audience_id + '</span>');
        } else {
            $('#aud_create_status').html('<span style="color:red;"><i class="fa fa-times"></i> ' + res.msg + '</span>');
        }
    }, 'json');
}
</script>

<?php
/**
 * ============================================================
 * PATCH C: leads-acc-add.php — Handle POST Save for Audience Settings
 * In the existing INSERT/UPDATE SQL block, ADD these columns:
 *
 * For UPDATE:
 *   cust_aud_enabled    = '".intval($_POST['cust_aud_enabled'] ?? 0)."',
 *   cust_aud_ad_account = '".mysqli_real_escape_string($conn, $_POST['cust_aud_ad_account'] ?? '')."',
 *   cust_aud_id         = '".mysqli_real_escape_string($conn, $_POST['cust_aud_id'] ?? $_POST['cust_aud_id_new'] ?? '')."',
 *   cust_aud_name       = '".mysqli_real_escape_string($conn, $_POST['cust_aud_name_hidden'] ?? '')."',
 *
 * For INSERT, add after existing fields:
 *   cust_aud_enabled, cust_aud_ad_account, cust_aud_id, cust_aud_name
 *
 * And values:
 *   '".intval($_POST['cust_aud_enabled'] ?? 0)."',
 *   '".mysqli_real_escape_string($conn, $_POST['cust_aud_ad_account'] ?? '')."',
 *   '".mysqli_real_escape_string($conn, $_POST['cust_aud_id'] ?? $_POST['cust_aud_id_new'] ?? '')."',
 *   '".mysqli_real_escape_string($conn, $_POST['cust_aud_name_hidden'] ?? '')."',
 *
 * ============================================================
 * NOTE: Also load $editRow when editing — inside the "if(isset($_GET['id']))" block:
 *
 *   $editRes = mysqli_query($conn, "SELECT * FROM leads_acc WHERE tbl_id=".(int)$_GET['id']." LIMIT 1");
 *   $editRow = mysqli_fetch_assoc($editRes);
 *
 * ============================================================
 */
?>
