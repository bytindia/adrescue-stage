<?php session_start();
include 'db.php';
function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($data,true);
	return $data;
}

if(isset($_POST['fb_pg']) && $_POST['fb_pg']!='') {
	$page_id=$_POST['fb_pg'];
	$userRes2 = mysqli_query($conn, "select pg_token from pages WHERE pg_id='".$page_id."' and uid=".$_SESSION['uid']."");	
	$getRw2 = mysqli_fetch_assoc($userRes2);
	$pg_access_token = $getRw2['pg_token'];						
	
	$val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$page_id.'/leadgen_forms?access_token='.$pg_access_token.'&limit=500');
	//d($val);
	echo '<option value="">Select Lead Form</option>';
	foreach($val['data'] as $v) {
		echo '<option value="'.$v['id'].'">'.$v['name'].'</option>';
	}
}

?>