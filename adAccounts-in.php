<? session_start();
$redirect = 'yes'; if(isset($_GET['cron'])) { $_SESSION['uid'] = 2; $redirect = 'no'; }
include 'db.php';
include 'config.php';

require_once 'vendor-linkedin/autoload.php';
$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";
$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);

if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
  $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
}


if ($linkedIn->isAuthenticated()) 
{
	
	$adData = $linkedIn->get('v2/adAccountsV2?q=search&search.type.values[0]=BUSINESS&search.type.values[1]=ENTERPRISE&search.status.values[0]=ACTIVE&search.status.values[1]=CANCELED&sort.field=ID&sort.order=DESCENDING');
  //d($adData['elements']); exit;
 
 if(isset($adData['elements']) && count($adData['elements'])>0) 
 {
  
  	foreach ($adData['elements'] as $key => $response) 
	{			
	
				//echo $key.'ID: ' . $response['id']. '<br />'; exit;
				//echo "select * from adAccounts_in WHERE account_id='".$response['id']."' AND in_id='".$_SESSION['in_id']."' AND uid='".$_SESSION['uid']."'";
				$cirRes = mysqli_query($conn, "select * from adAccounts_in WHERE account_id='".$response['id']."' AND in_id='".$_SESSION['in_id']."' AND uid='".$_SESSION['uid']."'");						
				
				//echo "select * from circuit where (editID='".$_POST['id']."' || connID='".$_POST['id']."') AND (editID='".$value."' || connID='".$value."')";
				if(mysqli_num_rows($cirRes)==0) {
						$cirSql = "INSERT INTO adAccounts_in (uid, in_id, id, name, account_id, account_status, currency, created) VALUES ('".$_SESSION['uid']."', '".$_SESSION['in_id']."', '".mysqli_real_escape_string($conn, $response['id'])."', '".mysqli_real_escape_string($conn, $response['name'])."', '".mysqli_real_escape_string($conn, $response['id'])."', '".mysqli_real_escape_string($conn, $response['status'])."', '".mysqli_real_escape_string($conn,$response['currency'])."', now());";  
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					} else {
						$cirSql = "UPDATE adAccounts_in SET name='".mysqli_real_escape_string($conn,$response['name'])."', account_status='".mysqli_real_escape_string($conn, $response['status'])."', updated=now() WHERE account_id='".$response['id']."' AND in_id='".$_SESSION['in_id']."'";
						mysqli_query($conn, $cirSql) or die(mysqli_error()); 
				}
		}  
 }
	//$request_url = "https://graph.facebook.com/v11.0/me/adaccounts?access_token=".$access_token."&fields=id,name,account_id,currency,account_status&limit=50";
	//adAccounts_in($request_url, $conn);
}
if($redirect == 'yes') {
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'ad-accounts-in.php';</script>";
	exit();
}

//d($fb_response->paging->next);