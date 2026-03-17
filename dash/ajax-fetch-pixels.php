<?php 
session_start();
include '../db.php';
include 'overview-config.php';

header('Content-Type: application/json');

function get_data($url) {
    $data = file_get_contents_curl($url);
    $data = json_decode($data, true);
    return $data;
}

$response = array('success' => false, 'pixels' => array(), 'message' => '');

if(isset($_POST['ad_account_id']) && $_POST['ad_account_id'] != '') {
    $ad_account_id = $_POST['ad_account_id'];
    
    // Get access token from users table
    $query = "SELECT access_token FROM users WHERE tbl_id='".$_SESSION['uid']."'";
    $userRes = mysqli_query($conn, $query);
    
    if($userRes && mysqli_num_rows($userRes) > 0) {
        $getRow = mysqli_fetch_assoc($userRes);
        $access_token = $getRow['access_token'];
        
        if($access_token) {
            // Fetch pixels from Facebook API
            // Remove 'act_' prefix if present
            $account_id = str_replace('act_', '', $ad_account_id);
            $pixel_url = 'https://graph.facebook.com/'.$api_ver.'/act_'.$account_id.'/adspixels?fields=name,id&access_token='.$access_token.'&limit=500';
            
            $val = get_data($pixel_url);
            
            // Debug: Log the response for troubleshooting
            error_log("Pixels API Response for account $ad_account_id: " . print_r($val, true));
            
            if(isset($val['data']) && !empty($val['data'])) {
                $response['success'] = true;
                foreach($val['data'] as $pixel) {
                    $response['pixels'][] = array(
                        'id' => $pixel['id'],
                        'name' => isset($pixel['name']) ? $pixel['name'] : $pixel['id']
                    );
                }
            } else {
                $response['message'] = 'No pixels found for this ad account';
                if(isset($val['error'])) {
                    $response['message'] .= ': ' . $val['error']['message'];
                }
            }
        } else {
            $response['message'] = 'Access token not found';
        }
    } else {
        $response['message'] = 'User not found or access denied';
    }
} else {
    $response['message'] = 'Ad account ID is required';
}

echo json_encode($response);
?>

