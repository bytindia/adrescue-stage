<? 
$conn =  mysqli_connect('localhost', 'digitalb2k_adsninja', getenv('DB_PASS'), 'digitalb2k_adsninja'); 
mysqli_select_db($conn,'digitalb2k_adsninja');

function auditAuth()
{
	if(!isset($_SESSION['uid'])) {
		$_SESSION['error'] = 'Please Login!';
		echo "<script>window.location = 'admin.php';</script>";
		exit();
	}
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

$server_path = '/home/digitalb2k/stage.adrescue.in/';

$api_ver = 'v15.0';