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

$response = array('success' => false, 'forms' => array(), 'message' => '');

if(isset($_POST['page_id']) && $_POST['page_id'] != '') {
    $page_id = $_POST['page_id'];
    
    // Get page access token from pages table using page_id
    $query = "SELECT pg_token FROM pages WHERE pg_id='".$page_id."' AND uid='".$_SESSION['uid']."'";
    $userRes = mysqli_query($conn, $query);
    
    if($userRes && mysqli_num_rows($userRes) > 0) {
        $getRow = mysqli_fetch_assoc($userRes);
        $pg_access_token = $getRow['pg_token'];
        
        if($pg_access_token) {
            // Fetch forms from Facebook API
            $val = get_data('https://graph.facebook.com/'.$api_ver.'/'.$page_id.'/leadgen_forms?access_token='.$pg_access_token.'&limit=500');
            
            // Debug: Log the response for troubleshooting
            error_log("Forms API Response for page $page_id: " . print_r($val, true));
            
            if(isset($val['data']) && !empty($val['data'])) {
                $response['success'] = true;
                foreach($val['data'] as $form) {
                    $response['forms'][] = array(
                        'id' => $form['id'],
                        'name' => $form['name']
                    );
                }
                
                // Store forms in session for this page
                $_SESSION['page_forms'][$page_id] = $response['forms'];
            } else {
                $response['message'] = 'No forms found for this page';
                if(isset($val['error'])) {
                    $response['message'] .= ': ' . $val['error']['message'];
                }
            }
        } else {
            $response['message'] = 'Page access token not found';
        }
    } else {
        $response['message'] = 'Page not found or access denied';
    }
} else {
    $response['message'] = 'Page ID is required';
}

echo json_encode($response);
?>
