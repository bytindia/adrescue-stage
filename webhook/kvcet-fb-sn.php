<?php
date_default_timezone_set("Asia/Kolkata");
$qus = strtolower('what is your prefered course of choice?');
                           // if(strpos($qus, 'course') !== FALSE) { $projectKey = 'b.e mechanical engineering'; }
							//echo $projectKey ; exit;
if(isset($_POST['pageId']) && $_POST['pageId']!='')
{
		$lead = $_POST['lead'];
		$src = 'Facebook - LG';
		$source = $_POST['formName']; //.'/'.$_POST['medium'];
		$medium = $_POST['ad'].' / '.$_POST['adset'];
		$campaign = $_POST['campaign'];
		
		$user = unserialize($lead);
		$name = $user['full_name'];
		$phone = $user['phone_number'];
		$email = $user['email'];
        $projectKey = '';
        
        foreach($user as $attr=>$val) 
		{
						$fields = ucwords(str_replace("_"," ",$attr));
						if($attr=='full_name') { $name = $val; }
						else if($attr=='full name') { $name = $val; }
						else if($attr=='email') { $email = $val;  }
						else if($attr=='phone_number') { $phone = $val;  }
						else { //$lCustom .= $attr.': '.$val.' '; 
                            $qus = strtolower($fields);
                            if(strpos($qus, 'course') !== FALSE || strpos($qus, 'degree') !== FALSE) { $projectKey = $val; }
						} 
		}

        $cId = 13;
        //$projectKey = $_POST['course_interest'];

        $post = ['name' => $name, 'email' => $email, 'phone' =>  str_replace(' ', '', $phone), 'course' => $projectKey, 'src' => 'Facebook LG', 'sub_src' => $campaign, 'sub_src2' => $source, 'cId' => $cId, 'project' => trim($projectKey), 'src_F_G' => 'F', 'channel' =>'BYT', 'city' => '', 'page' => ''];

        $ch = curl_init('http://adrescue.in/sn-api/addlead');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $response = curl_exec($ch);
        curl_close($ch);	
}
