<?php
session_start();
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION['uid'] = 2;
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit();
}

include '../db.php';

// Create table if not exists
$create_sql = "CREATE TABLE IF NOT EXISTS iyra_leads_upload (
    tbl_id INT(25) NOT NULL AUTO_INCREMENT,
    lead_id VARCHAR(100) NULL,
    created DATETIME NULL,
    name VARCHAR(250) NULL,
    phone VARCHAR(250) NULL,
    source VARCHAR(250) NULL,
    lead_stage VARCHAR(250) NULL,
    qualify_lead VARCHAR(250) NULL,
    lead_status VARCHAR(250) NULL,
    lead_type VARCHAR(250) NULL,
    project VARCHAR(250) NULL,
    PRIMARY KEY (tbl_id),
    KEY idx_project (project),
    KEY idx_phone_project (phone(50), project(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
mysqli_query($conn, $create_sql);

$projects = ['iyra city' => 'Iyra City', 'iyra amara' => 'Iyra Amara', 'iyra spire' => 'Iyra Spire'];
$msg = '';
$msgType = '';

// Flexible column name mapping (case-insensitive, handles variations)
$col_map = [
    'lead_id' => ['lead id', 'leadid'],
    'capture_date' => ['capture date', 'capturedate', 'date'],
    'capture_time' => ['capture time', 'capturetime', 'time'],
    'name' => ['name'],
    'contact_number' => ['contact number', 'contactnumber', 'phone', 'phone number'],
    'lead_source' => ['lead source', 'leadsource', 'source'],
    'lead_stage' => ['lead stage', 'leadstage', 'stage'],
    'qualify_lead' => ['qualify lead', 'qualifylead'],
    'lead_status' => ['lead status', 'leadstatus', 'status'],
    'lead_type' => ['lead type', 'leadtype', 'type'],
];

function findColIndex($headers, $map_keys) {
    $normalize = function($s) { return preg_replace('/\s+/', '', strtolower(trim($s))); };
    $hNorm = array_map($normalize, $headers);
    foreach ($map_keys as $key) {
        $kNorm = $normalize($key);
        foreach ($hNorm as $i => $hn) {
            if ($hn === $kNorm || strpos($hn, $kNorm) === 0 || strpos($kNorm, $hn) === 0) {
                return $i;
            }
        }
    }
    return -1;
}

function parseDate($dateStr, $timeStr = '') {
    if (empty($dateStr)) return null;
    $dateStr = trim($dateStr);
    $timeStr = trim($timeStr);
    $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'];
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $dateStr);
        if ($d) {
            $out = $d->format('Y-m-d');
            if (!empty($timeStr)) {
                $t = date_parse($timeStr);
                if ($t && $t['hour'] !== false) {
                    $out .= ' ' . sprintf('%02d:%02d:%02d', $t['hour'], $t['minute'] ?? 0, $t['second'] ?? 0);
                } else {
                    $out .= ' 00:00:00';
                }
            } else {
                $out .= ' 00:00:00';
            }
            return $out;
        }
    }
    $ts = strtotime($dateStr . ' ' . $timeStr);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    $project = isset($_POST['project']) ? trim($_POST['project']) : '';
    if (empty($project) || !isset($projects[$project])) {
        $msg = 'Please select a project.';
        $msgType = 'danger';
    } elseif (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $msg = 'Please select a CSV file.';
        $msgType = 'danger';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            $msg = 'Could not read the CSV file.';
            $msgType = 'danger';
        } else {
            $project_esc = mysqli_real_escape_string($conn, $project);
            mysqli_query($conn, "DELETE FROM iyra_leads_upload WHERE project = '$project_esc'");

            $headers = fgetcsv($handle);
            if (!$headers) {
                $msg = 'CSV file is empty or invalid.';
                $msgType = 'danger';
            } else {
                $idx_lead_id = findColIndex($headers, array_merge($col_map['lead_id'], ['lead id']));
                $idx_date = findColIndex($headers, array_merge($col_map['capture_date'], ['capture date']));
                $idx_time = findColIndex($headers, array_merge($col_map['capture_time'], ['capture time']));
                $idx_name = findColIndex($headers, array_merge($col_map['name'], ['name']));
                $idx_phone = findColIndex($headers, array_merge($col_map['contact_number'], ['contact number', 'phone']));
                $idx_source = findColIndex($headers, array_merge($col_map['lead_source'], ['lead source', 'source']));
                $idx_stage = findColIndex($headers, array_merge($col_map['lead_stage'], ['lead stage', 'stage']));
                $idx_qualify = findColIndex($headers, array_merge($col_map['qualify_lead'], ['qualify lead']));
                $idx_status = findColIndex($headers, array_merge($col_map['lead_status'], ['lead status', 'status']));
                $idx_type = findColIndex($headers, array_merge($col_map['lead_type'], ['lead type', 'type']));

                $inserted = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    $lead_id = ($idx_lead_id >= 0 && isset($row[$idx_lead_id])) ? trim($row[$idx_lead_id]) : '';
                    $dateVal = ($idx_date >= 0 && isset($row[$idx_date])) ? trim($row[$idx_date]) : '';
                    $timeVal = ($idx_time >= 0 && isset($row[$idx_time])) ? trim($row[$idx_time]) : '';
                    $name = ($idx_name >= 0 && isset($row[$idx_name])) ? trim($row[$idx_name]) : '';
                    $phone = ($idx_phone >= 0 && isset($row[$idx_phone])) ? trim($row[$idx_phone]) : '';
                    $source = ($idx_source >= 0 && isset($row[$idx_source])) ? trim($row[$idx_source]) : '';
                    $lead_stage = ($idx_stage >= 0 && isset($row[$idx_stage])) ? trim($row[$idx_stage]) : '';
                    $qualify_lead = ($idx_qualify >= 0 && isset($row[$idx_qualify])) ? trim($row[$idx_qualify]) : '';
                    $lead_status = ($idx_status >= 0 && isset($row[$idx_status])) ? trim($row[$idx_status]) : '';
                    $lead_type = ($idx_type >= 0 && isset($row[$idx_type])) ? trim($row[$idx_type]) : '';

                    $created = parseDate($dateVal, $timeVal);
                    if (!$created && $dateVal) $created = date('Y-m-d H:i:s', strtotime($dateVal . ' ' . $timeVal));

                    $lead_id = mysqli_real_escape_string($conn, $lead_id);
                    $created = $created ? "'" . mysqli_real_escape_string($conn, $created) . "'" : "NULL";
                    $name = mysqli_real_escape_string($conn, $name);
                    $phone = mysqli_real_escape_string($conn, $phone);
                    $source = mysqli_real_escape_string($conn, $source);
                    $lead_stage = mysqli_real_escape_string($conn, $lead_stage);
                    $qualify_lead = mysqli_real_escape_string($conn, $qualify_lead);
                    $lead_status = mysqli_real_escape_string($conn, $lead_status);
                    $lead_type = mysqli_real_escape_string($conn, $lead_type);

                    $sql = "INSERT INTO iyra_leads_upload (lead_id, created, name, phone, source, lead_stage, qualify_lead, lead_status, lead_type, project) 
                            VALUES ('$lead_id', $created, '$name', '$phone', '$source', '$lead_stage', '$qualify_lead', '$lead_status', '$lead_type', '$project_esc')";
                    if (mysqli_query($conn, $sql)) $inserted++;
                }
                fclose($handle);
                $msg = "Uploaded successfully. $inserted rows stored for project: " . $projects[$project] . ". Previous data for this project was cleared.";
                $msgType = 'success';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Iyra Leads - CSV Upload</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <script src="/vendors/jquery/dist/jquery.min.js"></script>
    <script src="/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container" style="margin-top: 30px;">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h5>Iyra Leads - CSV Upload</h5></div>
                    <div class="card-body">
                        <?php if ($msg): ?>
                            <div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Select CSV File</label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                <small class="form-text text-muted">Expected columns: Lead ID, Capture Date, Capture Time, Name, Contact Number, Lead Source, Lead Stage, Qualify Lead, Lead status, Lead Type</small>
                            </div>
                            <div class="form-group">
                                <label>Project</label>
                                <select name="project" class="form-control" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $val => $label): ?>
                                        <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="text-warning"><strong>Note:</strong> Uploading will replace all existing data for the selected project.</p>
                            <button type="submit" name="upload_csv" class="btn btn-primary"><i class="fa fa-upload"></i> Upload</button>
                            <a href="iyra-leads-dash.php" class="btn btn-secondary">Go to Dashboard</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
