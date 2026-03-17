<?php //print_r($_POST);
if(isset($_POST)) {

    include '../db.php'; 
    include 'config.php';
    function loopAdRep($url) {
        global $output;
        $requests = file_get_contents_curl($url);
        $fb_response = json_decode($requests,true);
        if(isset($output) && count($output)>0 && isset($fb_response['data']) && count($fb_response['data'])>0) {
            $output = array_merge($output, $fb_response['data']);
        } else if(isset($fb_response['data']) && count($fb_response['data'])>0) {
            $output = $fb_response['data']; 
        }
        
        if(isset($fb_response['paging']['next'])) {
            loopAdRep($fb_response['paging']['next']);
        } else { 
            return $output['data'] = $output; 
        }
    }
    $val = $_POST['act'];
    $rule_id = $_POST['rule_id'];
    $url = "https://graph.facebook.com/".$api_ver."/act_".$val."/customconversions?fields=name,default_conversion_value,is_archived,is_unavailable,custom_event_type,rule&filtering=[{'field':'is_archived','operator':'EQUAL','value':false}]&access_token=".$access_token."&limit=1750";
            //$req = file_get_contents_curl($url);
            
    $output = $res = array();
    loopAdRep($url);  
    $res = $output;
    //d($res['data']);
    if(isset($res['data']) && count($res['data'])>0){ 
    ?>
    
                <select name="cust_con[<?php echo $rule_id; ?>]" class="form-control">
                    <option value="">Select Conversion</option>
                    <?php foreach($res['data'] as $k => $v) {   ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo $v['name']; ?></option>
                    <?php } ?>
                </select>
    <?
    } else {
        echo 'No custom conversion data';
    }
    
}
