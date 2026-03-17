<?php
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

if(isset($_POST['id'])){
     $query = "SELECT access_token FROM users WHERE tbl_id='".$_POST['uid']."'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	
	$access_token = $row['access_token']; 

    $request_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$_POST['id'].'/customaudiences?fields=name,id&access_token='.$access_token.'&limit=500';
	$res = get_data($request_url);
    if(isset($res['data']) && count($res['data'])>0) {
       // d($res['data']);
    ?>
	<div class="form-group col-md-6">
		<label>Audience: </label>
     <select name="cust_aud[]" id="cust_aud" class="form-control selectpicker" title="Choose Audience list" multiple='multiple'>
                                                    	<?php foreach($res['data'] as $v){  ?>
																				<option value="<?php echo $v['id']; ?>" ><?php echo $v["name"]; ?></option>
																				 <?php } ?>
                                                    </select> 
	</div>
    <? 
    } else {
        echo 'no-aud';
    }
}