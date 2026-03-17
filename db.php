<?
require_once __DIR__ . '/env_loader.php';

$conn = mysqli_connect(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER'),
    getenv('DB_PASS'),
    getenv('DB_NAME')
);
mysqli_select_db($conn, getenv('DB_NAME'));
$fmt = new NumberFormatter($locale = 'en_IN', NumberFormatter::DECIMAL);
$fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 0);
$all_user = array('shaheena','mughil','charan','simin', 'karthik', 'ramesh', 'radhika', 'nida', 'lincy', 'accounts', 'faheem', 'admin');

function Auth()
{
	if(!isset($_SESSION['uid'])) {
		$_SESSION['error'] = 'Please Login!';
        $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		echo "<script>window.location = 'https://stage.adrescue.in/login.php?redirect=".$fullUrl."';</script>";
		exit();
	} else {
        if(isset($_SESSION['user_ty']) && $_SESSION['user_ty'] == 'ads') {
            $conn = mysqli_connect(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
            /*
            //$_SESSION['error'] = 'Unauthorized Access!';
            $cpStats = ['total_clients' => 0, 'missing_custom_label' => 0, 'has_custom_label' => 0];
            $uidEsc = 2;
            $statsSql = "SELECT 
            COUNT(*) AS total_clients,
            SUM(CASE WHEN cp.account_tbl_id IS NULL THEN 1 ELSE 0 END) AS missing_custom_label,
            SUM(CASE WHEN cp.account_tbl_id IS NOT NULL THEN 1 ELSE 0 END) AS has_custom_label
            FROM dashboard_accounts da
            INNER JOIN (
            SELECT s.account_tbl_id
            FROM client_performance_snapshot s
            INNER JOIN (
                SELECT account_tbl_id, MAX(period_end) AS max_end
                FROM client_performance_snapshot
                GROUP BY account_tbl_id
            ) latest ON s.account_tbl_id = latest.account_tbl_id AND s.period_end = latest.max_end
            WHERE s.total_spend > 0
            ) snap ON snap.account_tbl_id = da.tbl_id
            LEFT JOIN (
            SELECT DISTINCT account_tbl_id
            FROM clients_performance
            WHERE TRIM(IFNULL(custom_label, '')) != '' OR TRIM(IFNULL(`value`, '')) != ''
            ) cp ON cp.account_tbl_id = da.tbl_id
            WHERE da.uid='" . $uidEsc . "' 
            AND da.delete_status=0 
            AND da.client_ty IS NOT NULL 
            AND TRIM(da.client_ty) != ''";
            $statsRes = mysqli_query($conn, $statsSql);
            if ($statsRes && $row = mysqli_fetch_assoc($statsRes)) {
                $cpStats['total_clients'] = (int)($row['total_clients'] ?? 0);
                $cpStats['missing_custom_label'] = (int)($row['missing_custom_label'] ?? 0);
                $cpStats['has_custom_label'] = (int)($row['has_custom_label'] ?? 0);
            }
            $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            if($cpStats['total_clients'] > 0 && $cpStats['total_clients']!=$cpStats['has_custom_label'] && $actual_link != 'https://stage.adrescue.in/dashboard.php?page=client-performance' && $actual_link != 'https://stage.adrescue.in/dash/client-performance.php') {
                //echo "<script>window.location = 'https://stage.adrescue.in/dashboard.php?page=client-performance';</script>"; exit();
            }
            //echo "<script>window.location = 'https://stage.adrescue.in/dashboard.php?page=client-performance';</script>";
            //exit();
            */
            $media_key = isset($_SESSION['media_key']) 
                ? (int)$_SESSION['media_key'] 
                : 0;

            if ($media_key > 0) {

                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd   = date('Y-m-d 23:59:59');

                /* ==========================================
                1️⃣ TOTAL ACTIVE ACCOUNTS WITH SPEND > 0
                ========================================== */
                $totalSql = "
                    SELECT COUNT(DISTINCT da.tbl_id) AS total_accounts
                    FROM dashboard_accounts da
                    INNER JOIN client_performance_snapshot cps
                        ON cps.account_tbl_id = da.tbl_id
                    WHERE da.med_by = $media_key
                    AND da.delete_status = 0
                    AND cps.total_spend > 0
                ";

                $totalRes = mysqli_query($conn, $totalSql);
                $totalRow = mysqli_fetch_assoc($totalRes);
                $totalAccounts = (int)$totalRow['total_accounts'];

                /* ==========================================
                2️⃣ UPDATED TODAY (ONLY THOSE ACCOUNTS)
                ========================================== */
                $updatedSql = "
                    SELECT COUNT(DISTINCT da.tbl_id) AS updated_accounts
                    FROM dashboard_accounts da
                    INNER JOIN client_performance_snapshot cps
                        ON cps.account_tbl_id = da.tbl_id
                    INNER JOIN clients_performance cp
                        ON cp.account_tbl_id = da.tbl_id
                    WHERE da.med_by = $media_key
                    AND da.delete_status = 0
                    AND cps.total_spend > 0
                    AND cp.updated BETWEEN '$todayStart' AND '$todayEnd'
                ";

                $updatedRes = mysqli_query($conn, $updatedSql);
                $updatedRow = mysqli_fetch_assoc($updatedRes);
                $updatedAccounts = (int)$updatedRow['updated_accounts'];

                $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

                /* ==========================================
                3️⃣ REDIRECT IF NOT ALL UPDATED
                ========================================== */
                //echo $updatedAccounts.'/'.$totalAccounts; 
                if (
                    $totalAccounts > 0 &&
                    $totalAccounts != $updatedAccounts &&
                    $actual_link != 'https://stage.adrescue.in/dashboard.php?page=client-performance' &&
                    $actual_link != 'https://stage.adrescue.in/dash/client-performance.php'
                ) {
                   // header("Location: https://stage.adrescue.in/dashboard.php?page=client-performance");
                   // exit();
                }
            }
        } 
    }
}  

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $page_url = $_SERVER['REQUEST_URI'];
    
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, page_url, action) VALUES (?, ?, 'page visit')");
    $stmt->bind_param("is", $user_id, $page_url);
    $stmt->execute();
}
function d($d)
{
	echo '<pre>';
	print_r($d);
	echo '</pre>';
} 

function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

$server_path = getenv('SERVER_PATH') ?: '/home/digitalb2k/stage.adrescue.in/';

$api_ver = getenv('API_VERSION') ?: 'v24.0';

global $filtering_param_g;	$filtering_param_g = '';


function file_get_contents_curl2($url) {

    $allData = [];
    $firstResponse = null;

    while ($url) {

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            break;
        }

        if ($firstResponse === null) {
            $firstResponse = $response;
        }

        $json = json_decode($response, true);

        if (!isset($json['data'])) {
            // Not a paginated FB response → return raw JSON
            return $firstResponse;
        }

        $allData = array_merge($allData, $json['data']);

        if (isset($json['paging']['next'])) {
            $url = $json['paging']['next'];
            usleep(200000);
        } else {
            break;
        }
    }

    return $allData;
}
