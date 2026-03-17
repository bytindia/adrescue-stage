<?php
/**
 * Gmail Dashboard API - reads Excel/CSV, sends to ChatGPT to generate dashboard HTML.
 * Called via AJAX from gmail-dashboard.php
 */

header('Content-Type: application/json');

$authUser = 'basics';
$authPass = '9fQTSyN8Xu$Ch';
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $authUser || $_SERVER['PHP_AUTH_PW'] !== $authPass) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$filePath = $_POST['path'] ?? $_GET['path'] ?? '';
$sheetIndex = isset($_POST['sheet']) ? (int)$_POST['sheet'] : (isset($_GET['sheet']) ? (int)$_GET['sheet'] : 0);

$baseDir = __DIR__;
$fullPath = $baseDir . '/' . ltrim($filePath, '/');

/* Security: must be under attachments/, no directory traversal */
if (strpos($filePath, 'attachments/') !== 0 || strpos($filePath, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Invalid path']);
    exit;
}

if (!is_file($fullPath)) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$isExcel = in_array($ext, ['xlsx', 'xls']);
$isCsv = $ext === 'csv';

if (!$isExcel && !$isCsv) {
    echo json_encode(['success' => false, 'error' => 'Only Excel or CSV files supported']);
    exit;
}

/*
|--------------------------------------------------------------------------
| PHPExcel loader - try multiple paths
|--------------------------------------------------------------------------
*/
$phpExcelPaths = [
    __DIR__ . '/../PHPExcel-1.8/Classes/PHPExcel.php',
    __DIR__ . '/../../adsninja/PHPExcel-1.8/Classes/PHPExcel.php',
    '/home/digitalb2k/stage.adrescue.in/PHPExcel-1.8/Classes/PHPExcel.php',
];

$phpExcelLoaded = false;
foreach ($phpExcelPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $phpExcelLoaded = true;
        break;
    }
}

/*
|--------------------------------------------------------------------------
| ACTION: get_sheets - return sheet names for Excel file
|--------------------------------------------------------------------------
*/
if ($action === 'get_sheets' && $isExcel) {
    if (!$phpExcelLoaded) {
        echo json_encode(['success' => false, 'error' => 'PHPExcel library not found. Place PHPExcel-1.8 in project root or configure path.']);
        exit;
    }

    try {
        $objReader = PHPExcel_IOFactory::createReader(PHPExcel_IOFactory::identify($fullPath));
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($fullPath);
        $sheetNames = $objPHPExcel->getSheetNames();
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);

        echo json_encode([
            'success' => true,
            'sheets' => $sheetNames,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to read Excel: ' . $e->getMessage()]);
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| Read file data (CSV or Excel sheet)
|--------------------------------------------------------------------------
*/
function readCsvData($path) {
    $rows = [];
    $handle = fopen($path, 'r');
    if (!$handle) return $rows;

    // Auto-detect delimiter
    $firstLine = fgets($handle);
    rewind($handle);
    $delimiter = ',';
    if (preg_match('/\t/', $firstLine) && substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
        $delimiter = "\t";
    }
    if (strpos($firstLine, ';') !== false && substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
        $delimiter = ';';
    }

    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function readExcelSheetData($path, $sheetIndex = 0) {
    $objReader = PHPExcel_IOFactory::createReader(PHPExcel_IOFactory::identify($path));
    $objReader->setReadDataOnly(true);
    $objPHPExcel = $objReader->load($path);
    $objPHPExcel->setActiveSheetIndex($sheetIndex);
    $sheet = $objPHPExcel->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = PHPExcel_Cell::columnIndexFromString($highestCol);

    $rows = [];
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 0; $col <= $highestColIndex; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            // Use getCalculatedValue() to get computed result, not formula text
            $val = $cell->getCalculatedValue();
            if ($val instanceof PHPExcel_RichText) {
                $val = $val->getPlainText();
            }
            $rowData[] = $val === null ? '' : (string)$val;
        }
        $rows[] = $rowData;
    }

    $objPHPExcel->disconnectWorksheets();
    unset($objPHPExcel);
    return $rows;
}

$rows = [];
if ($isCsv) {
    $rows = readCsvData($fullPath);
} elseif ($isExcel && $phpExcelLoaded) {
    try {
        $rows = readExcelSheetData($fullPath, $sheetIndex);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Excel read error: ' . $e->getMessage()]);
        exit;
    }
} elseif ($isExcel) {
    echo json_encode(['success' => false, 'error' => 'PHPExcel library not found']);
    exit;
}

if (empty($rows)) {
    echo json_encode(['success' => false, 'error' => 'No data in file']);
    exit;
}

/*
|--------------------------------------------------------------------------
| ChatGPT API - generate dashboard from data
|--------------------------------------------------------------------------
*/
$OPENAI_API_KEY = getenv('OPENAI_API_KEY');
$OPENAI_MODEL = "gpt-4o-mini";

// Limit rows to avoid token overflow (first 50 rows + header is usually enough)
$maxRows = 50;
$sampleRows = array_slice($rows, 0, $maxRows);

$dataJson = json_encode($sampleRows);

$prompt = <<<PROMPT
You are a dashboard designer. I have tabular data (array of arrays - first row is headers) from an Excel/CSV file.
Generate a complete, self-contained HTML document that visualizes this data as a dashboard.

DATA (JSON format):
$dataJson

REQUIREMENTS:
1. Output a COMPLETE HTML document: <!DOCTYPE html><html><head><body>...</body></html>
2. In <head>: include Bootstrap 5 CSS (https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css) and Chart.js (https://cdn.jsdelivr.net/npm/chart.js)
3. In <body>: Create a responsive dashboard with:
   - Summary metric cards at the top (identify numeric columns - show sum/avg/count as appropriate)
   - Charts using Chart.js (bar, line, pie, or doughnut - choose based on data type)
   - A Bootstrap table showing the data
4. Analyze columns: numeric=charts and stats, categorical=pie charts, dates=time series
5. Use inline <script> at end of body to create Chart.js instances. Example: new Chart(document.getElementById('myChart'), { type: 'bar', data: {...} });
6. Keep output under 10000 characters. Be concise.
7. No markdown, no code fences - raw HTML only.
8. Use container-fluid and row/col for layout.

Output the complete HTML document only.
PROMPT;

function curlChatGPT($apiKey, $model, $content) {
    $payload = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $content]],
        'temperature' => 0.3,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$res, $err, $http];
}

[$res, $err, $http] = curlChatGPT($OPENAI_API_KEY, $OPENAI_MODEL, $prompt);

if ($err || $http >= 400) {
    echo json_encode([
        'success' => false,
        'error' => 'ChatGPT API error: ' . ($err ?: 'HTTP ' . $http),
    ]);
    exit;
}

$json = json_decode($res, true);
$content = $json['choices'][0]['message']['content'] ?? '';

// Strip markdown code fences if ChatGPT added them
$content = preg_replace('/^```(?:html)?\s*/', '', $content);
$content = preg_replace('/\s*```\s*$/', '', $content);
$content = trim($content);

echo json_encode([
    'success' => true,
    'html' => $content,
]);
