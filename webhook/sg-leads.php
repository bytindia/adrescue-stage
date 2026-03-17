<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$dirPath = '/home/digitalb2k/stage.adrescue.in/';
include $dirPath.'db.php';
require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
include $dirPath.'google-sheets-api/insert-row.php';
$uId=2; 
$tbl_id=2;  
$spreadsheetId='1urKxZu7aaDHF-7SLX1mMZLDyRC9Vg-QeGgRqHTYGNJU'; 

$sheetTab='Leads';


$input = file_get_contents('php://input');
$lead = $_REQUEST;

//print_r($lead);



if(isset($_POST['pageId']) && $_POST['pageId']!='')
{
		$lead = $_POST['lead'];
		$src = 'FB';
        $ad = $_POST['ad'];
		$source = $_POST['formName']; //.'/'.$_POST['medium'];
		$medium = $_POST['ad'].' / '.$_POST['adset'];
		$campaign = $_POST['campaign'];
		
		$user = unserialize($lead);
		$name = $user['full_name'];
		$phone = $user['phone_number'];
		$email = $user['email'];
        $projectKey = '';
        $j = 1;
        $lCustom = '';
        foreach($user as $attr=>$val) 
		{
						$fields = ucwords(str_replace("_"," ",$attr));
						if($attr=='full_name') { $name = $val; }
						else if($attr=='full name') { $name = $val; }
						else if($attr=='email') { $email = $val;  }
						else if($attr=='phone_number') { $phone = $val;  }
                        else { //$lCustom .= $attr.': '.$val.' '; 
							$lCustom .= '['.date('d-m-Y').'] [Q'.$j.']: '.str_replace("_"," ",$attr).' = '.str_replace("_"," ",$val).' ';
							$j++;
							//if(count($lead['lead'])!=$j) { $lCustom .=', '; }
						} 
		}

        $cId = 14;
        //$projectKey = $_POST['course_interest'];

        

        $leadV = [[
            date('F'),
            date('m/d/Y'),
            '',
            '',
            mysqli_real_escape_string($conn, $name),
            mysqli_real_escape_string($conn, $phone),
            '',
            '',
            '',
            mysqli_real_escape_string($conn, $src),
            mysqli_real_escape_string($conn, $source), 
            mysqli_real_escape_string($conn, $ad), 
            '',
            '',
            mysqli_real_escape_string($conn, $lCustom), 
            '',
            '',
            '',
            '',
            '',
            '',
            mysqli_real_escape_string($conn, $email)
            ]];
        append_to_sheet($uId, $tbl_id, $leadV, $spreadsheetId, $sheetTab);
        
        $data = array('code'=>200, 'response'=>"success");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
}