<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* DATABASE CONFIGURATION */ 
$servername = "localhost"; // Your hostname eg. localhost
$username = "digitalb2k_adsninja"; // database user name
$password = getenv('DB_PASS'); // database password
$database = "digitalb2k_adsninja"; // database name
$table = "leads"; // table name

$conn = mysqli_connect($servername, $username, $password, $database);
function d($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

if (mysqli_connect_error()) {
    die("Database connection failed: " . mysqli_connect_error());
}
$extQry =  "page_id='505567312893486' AND created >= DATE(NOW()) - INTERVAL 0 DAY";
$sql = "SELECT * from $table where $extQry";
$result = $conn->query($sql);
echo 'Total leads: '.$result->num_rows; 
echo '<br>';
//if ($result->num_rows == 0) { echo 'No records found!'; }
while($row = $result->fetch_assoc()) 
{
    
    $leads = unserialize($row["lead"]);
    $name = $leads['full_name'];
	if(isset($leads['email'])) { $email = $leads['email']; } else { $email =''; }
	$phone = $leads['phone_number'];
    $phonenos[] = $leads['phone_number'];
    $name = $leads['full_name'];		
	$phone = $leads['phone_number'];		
	if(isset($leads['email'])) { $email = $leads['email']; } else { $email =''; }
	$src = 'Facebook';	
	$sub_src = $row['formN'];
	$created_unix = $row['created_time'];
    $created = $row['created'];

	$commaSep = $extraQus ='';
	foreach($leads as $attr => $val) 
	{
		if($attr != 'full_name' && $attr != 'phone_number' && $attr != 'email') {
			//$fields += ucwords(str_replace("_"," ",$attr));
			if($extraQus!='') { $commaSep=', '; }
			$extraQus.= $val."".$commaSep;
		}
	}
	
	

	$projectKey = "Ashraya"; 
	//$projectKey = 'level_1';
	
	$cId = 12;
	$post = ['name' => $name, 'email' => $email, 'phone' => $phone, 'src' => $src, 'sub_src' => $sub_src, 'cId' => $cId, 'course' => $extraQus, 'project' => trim($projectKey), 'src_F_G' => 'Facebook', 'created' => $created, 'dup' => 'no'];
   // d($post);
    
	$ch = curl_init('http://adrescue.in/sn-api/addlead');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	$response = curl_exec($ch);
	curl_close($ch);
    echo $phone.' -> '. $response. '<br>'; 
}
//echo implode(',',$phonenos);
//print_r($post);