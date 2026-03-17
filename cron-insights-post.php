<?php 
date_default_timezone_set('Asia/Kolkata');

include 'db.php';

setlocale(LC_MONETARY, 'en_IN');

$query = "SELECT access_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$access_token = $row['access_token']; 
	
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

$start = date('m/d/Y', strtotime("first day of this month")); //date('m/01/Y');
$end = date('m/d/Y'); 


$getData = $fbStats = $gStats = array();
$fbStats = $fb_name = array();
$gStats = $g_name = array();

if(!isset($_GET['id'])) { $_GET['id']=1; }

$sqlRev2 = mysqli_query($conn, "SELECT tbl_id,uid,pg_id,pg_token,ins_post_update FROM pages WHERE uid='2' AND active='1'");

while($row=mysqli_fetch_assoc($sqlRev2)) { 
	$getData[] = $row;
}
//d($getData); exit;
$val_lm['data'] = array();

foreach($getData as $d) {
	if($d['ins_post_update']=='') { $start= date('m/d/Y', strtotime('-1 days',strtotime("first day of last month"))); } else { $start=$d['ins_post_update']; }
	
	$request_url = "https://graph.facebook.com/".$api_ver."/".$d['pg_id']."/posts?access_token=".$d['pg_token']."&since=".$start."&until=".$end."&fields=id,name,created_time,updated_time,type,object_id,insights.metric(post_clicks,post_activity,post_activity_by_action_type).fields(values)&limit=100";
	echo $request_url.'<br>';
	$val_lm = get_data($request_url);	
	//d($val_lm);  exit;
	//echo sizeof($val_lm['data'][0]['values']); 
	if(isset($val_lm['data'])) {
		echo sizeof($val_lm['data']).'<br>';
		for($k=0; $k < sizeof($val_lm['data']); $k++)
		{	
			/*echo $val_lm['data'][0]['values'][$k]['value'].' - '.$val_lm['data'][1]['values'][$k]['value'].' - '.$val_lm['data'][2]['values'][$k]['value'].' - '.$val_lm['data'][0]['values'][$k]['end_time']; exit;
			echo '<br>';*/
			//echo "select * from post_insights WHERE uid='".$d['uid']."' AND pg_id='".$d['pg_id']."' AND post_id='".$val_lm['data'][$k]['id']."'";
			$chkRes = mysqli_query($conn, "select * from post_insights WHERE uid='".$d['uid']."' AND pg_id='".$d['pg_id']."' AND post_id='".$val_lm['data'][$k]['id']."'");						
			 if(mysqli_num_rows($chkRes)==0) { 
			 
				 if(isset($val_lm['data'][$k]['insights']['data'][2]['values'][0]['value']['like'])) { $pLike=$val_lm['data'][$k]['insights']['data'][2]['values'][0]['value']['like']; } else { $pLike=0; }
				 if(isset($val_lm['data'][$k]['insights']['data'][2]['values'][0]['value']['share'])) { $pShare=$val_lm['data'][$k]['insights']['data'][2]['values'][0]['value']['share']; } else { $pShare=0; }
				 if(isset($val_lm['data'][$k]['insights']['data'][2]['values'][0]['value']['comment'])) { $pComnt=$val_lm['data'][$k]['insights']['data'][2]['values'][0]['value']['comment']; } else { $pComnt=0; }
				   
				 $cirSql = "INSERT INTO post_insights (uid, pg_id, post_id, post_type, post_clicks, post_activity, post_like, post_share, post_comment, created_time, created_time_unix, created) VALUES ('".$d['uid']."', '".$d['pg_id']."', '".$val_lm['data'][$k]['id']."', '".$val_lm['data'][$k]['type']."', '".$val_lm['data'][$k]['insights']['data'][0]['values'][0]['value']."', '".$val_lm['data'][$k]['insights']['data'][1]['values'][0]['value']."', '".$pLike."', '".$pShare."', '".$pComnt."', '".$val_lm['data'][$k]['created_time']."', '".strtotime($val_lm['data'][$k]['created_time'])."',  now());"; 
				 
				 mysqli_query($conn, $cirSql) or die(mysqli_error());
			 } 
		}
		$cirSql_x = "UPDATE pages SET ins_post_update='".$end."' WHERE tbl_id=".$d['tbl_id']."";
		mysqli_query($conn, $cirSql_x) or die(mysqli_error()); 
	}
}



echo 'cron-insights-post.php => success';
	