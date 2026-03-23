/**
 * ============================================================
 * PATCH FILE: invoice-preview.php — Custom Attachments Section
 * ============================================================
 *
 * HOW TO APPLY:
 * 1. Open your existing invoice-preview.php
 * 2. Find the "attachment-buttons" div (around line 455 in original)
 * 3. Paste the CUSTOM ATTACHMENTS BLOCK below that div, before </div> closing of col-md-5
 * 4. Find the <form> submit block (isset($_POST['submit'])) and paste the
 *    EMAIL ATTACHMENT PATCH inside email/invoice2025.php (see bottom of this file)
 *
 * ============================================================
 */

// ── PATCH 1: Add below existing attachment buttons div in invoice-preview.php ──
// Find this line in your file:
//    <input type="hidden" name="soa_pi_file" ...>
// And AFTER that input, paste:

?>
<!-- ── Custom Attachments Section ────────────────────────────────────────── -->
<div class="mt-3" id="custom-attachments-panel">
  <hr>
  <label class="form-label fw-bold">Custom Attachments:</label>

  <!-- Upload Form -->
  <div class="mb-2">
    <div class="input-group input-group-sm">
      <input type="text" id="attachLabel" placeholder="Label (optional, e.g. TDS Certificate)" class="form-control">
      <input type="file"  id="attachFile"  accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.zip">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="uploadCustomAttachment()">
        <i class="fa fa-upload"></i> Upload
      </button>
    </div>
    <small class="text-muted">Allowed: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG, ZIP</small>
  </div>

  <!-- Uploaded list -->
  <div id="custom-attach-list"></div>
  <!-- Hidden field: comma-separated IDs to include in email -->
  <input type="hidden" name="custom_attach_ids" id="custom_attach_ids" value="">
</div>
<!-- ── /Custom Attachments ────────────────────────────────────────────────── -->

<?php
// ── PATCH 1 End ──────────────────────────────────────────────────────────────
// ── PATCH 2: Paste inside invoice-preview.php <script> block ─────────────────
?>
<script>
// Custom Attachments JS — paste inside existing <script> at end of invoice-preview.php

var customAttachments = [];  // Track uploaded attachment IDs
var invId = <?php echo (int)($tbl_id ?? 0); ?>;

function uploadCustomAttachment() {
    var fileInput = document.getElementById('attachFile');
    var label     = document.getElementById('attachLabel').value.trim();
    var file      = fileInput.files[0];
    if (!file) { alert('Please choose a file first.'); return; }

    var fd = new FormData();
    fd.append('action',     'upload');
    fd.append('inv_id',     invId);
    fd.append('label',      label);
    fd.append('attachment', file);

    fetch('invoice-attachment-handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(res) {
            if (res.success) {
                customAttachments.push(res.id);
                updateCustomAttachIds();
                renderAttachItem(res);
                fileInput.value = '';
                document.getElementById('attachLabel').value = '';
            } else {
                alert('Upload failed: ' + res.msg);
            }
        })
        .catch(function(e) { alert('Upload error: ' + e); });
}

function renderAttachItem(item) {
    var list = document.getElementById('custom-attach-list');
    var div  = document.createElement('div');
    div.id   = 'attach-item-' + item.id;
    div.className = 'btn-group mar-left mb-1';
    div.innerHTML =
        '<button type="button" class="btn btn-outline-success btn-sm" onclick="previewAttachment(\'invoice-attachment-handler.php?action=download&id=' + item.id + '\')" data-bs-toggle="modal" data-bs-target="#previewModal">' +
        '<i class="fa fa-paperclip"></i> ' + (item.label ? item.label : item.orig_name) + '</button>' +
        '<button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteCustomAttachment(' + item.id + ')" title="Remove"><i class="fa-solid fa-close"></i></button>';
    list.appendChild(div);
}

function deleteCustomAttachment(id) {
    if (!confirm('Remove this attachment?')) return;
    fetch('invoice-attachment-handler.php?action=delete&id=' + id)
        .then(r => r.json())
        .then(function(res) {
            if (res.success) {
                customAttachments = customAttachments.filter(i => i !== id);
                updateCustomAttachIds();
                var el = document.getElementById('attach-item-' + id);
                if (el) el.remove();
            } else {
                alert('Delete failed: ' + res.msg);
            }
        });
}

function updateCustomAttachIds() {
    document.getElementById('custom_attach_ids').value = customAttachments.join(',');
}

// Load existing unsent attachments on page load
(function loadExistingAttachments() {
    if (!invId) return;
    fetch('invoice-attachment-handler.php?action=list&inv_id=' + invId + '&sent=0')
        .then(r => r.json())
        .then(function(res) {
            if (res.success && res.items.length > 0) {
                res.items.forEach(function(item) {
                    customAttachments.push(item.id);
                    renderAttachItem(item);
                });
                updateCustomAttachIds();
            }
        });
})();
</script>

<?php
// ── PATCH 3: In invoice-preview.php POST handler ──────────────────────────────
// After the line: include 'email/invoice2025.php';
// The invoice2025.php already handles standard attachments.
// The custom_attach_ids are passed via POST and handled IN invoice2025.php (see patch 4).
//
// Also add this BEFORE include 'email/invoice2025.php':
//
//   // Mark custom attachments as sent
//   if (!empty($_POST['custom_attach_ids'])) {
//       $attach_ids = array_filter(array_map('intval', explode(',', $_POST['custom_attach_ids'])));
//       if (!empty($attach_ids)) {
//           $ids_str = implode(',', $attach_ids);
//           mysqli_query($conn, "UPDATE invoice_custom_attachments SET sent=1, sent_at=NOW() WHERE id IN ($ids_str)");
//       }
//   }
//
// ── PATCH 4: In email/invoice2025.php ─────────────────────────────────────────
// After the existing $mail->addAttachment lines, add:
//
//   // Custom Attachments
//   $root_path_attach = '/home/digitalb2k/stage.adrescue.in/download/invoice-attachments/';
//   // ── ⚑ REPLACE with your actual path if different
//   if (!empty($_POST['custom_attach_ids'])) {
//       $ca_ids = array_filter(array_map('intval', explode(',', $_POST['custom_attach_ids'])));
//       if (!empty($ca_ids)) {
//           $ca_ids_str = implode(',', $ca_ids);
//           $ca_res = mysqli_query($conn, "SELECT * FROM invoice_custom_attachments WHERE id IN ($ca_ids_str)");
//           while ($ca_row = mysqli_fetch_assoc($ca_res)) {
//               $ca_path = $root_path_attach . $ca_row['stored_name'];
//               if (file_exists($ca_path)) {
//                   $mail->addAttachment($ca_path, $ca_row['orig_name']);
//               }
//           }
//       }
//   }
//
// ── PATCH 5: Sent Items preview ──────────────────────────────────────────────
// In invoice-past.php / invoice-list.php (wherever sent items are shown),
// add this to load sent attachments for an invoice:
//
//   $res_ca = mysqli_query($conn, "SELECT * FROM invoice_custom_attachments WHERE inv_id=$inv_id AND sent=1 ORDER BY id ASC");
//   while ($ca = mysqli_fetch_assoc($res_ca)) {
//       echo '<a href="invoice-attachment-handler.php?action=download&id='.$ca['id'].'"
//               class="btn btn-sm btn-outline-secondary">'
//           .'<i class="fa fa-paperclip"></i> '
//           .htmlspecialchars($ca['label'] ?: $ca['orig_name']).'</a> ';
//   }
//
?>
