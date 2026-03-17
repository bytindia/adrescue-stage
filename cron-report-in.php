<?php session_start(); 
date_default_timezone_set('Asia/Kolkata');

include 'db.php';
include 'functions-report.php'; 
//include 'config.php';
//LINKEDIN
$client_id = '819hf4iznbxt3r';
$client_secret = getenv('LINKEDIN_CLIENT_SECRET');

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 
$uId = $row['tbl_id'];
$_SESSION['name'] = $row['name'];
$_SESSION['fb_id'] = $row['fb_id'];
$_SESSION['g_id'] = $row['g_id'];
$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
$_SESSION['g_token'] = $row['g_token'];
$_SESSION['g_mcc'] = $row['g_mcc'];

$g_mcc = $row['g_mcc']; 
$g_refresh_token = $row['g_refresh_token'];

$userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='2'");	
$getRw2 = mysqli_fetch_assoc($userRes2);
$_SESSION['in_id'] = $getRw2['in_id'];
$_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
					
require_once $server_path .'vendor-linkedin/autoload.php';
$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";				
$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
  $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
}


$inIds = array();
$accQry = $accQry2 = '';
$today =  date('Y-m-d');
$last90 =  date('Y-m-d', strtotime("-60 days")); 

$sqlRev=mysqli_query($conn, "SELECT tbl_id,client_name,fb_acc,g_acc,in_acc FROM client_dashboard WHERE uid='".$uId."' AND delete_status=0");
										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
	if($sqlROW['in_acc']!='') { $inIds[] = $sqlROW['in_acc']; }
}

if(count($inIds)>0) 
{			
			$inIds = array_unique($inIds);		
			foreach($inIds as $k1 => $v1) {
				$accQry.='accounts['.$k1.']=urn:li:sponsoredAccount:'.$v1.'&';	
				$accQry2.='search.account.values['.$k1.']=urn:li:sponsoredAccount:'.$v1.'&';	
							
			}
			//echo $accQry;
			
			require_once 'vendor-linkedin/autoload.php';
			$linkedURL ="https://www.linkedin.com/oauth/v2/authorization";
			$linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
			if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
			  $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
			}
			
			$in_obj =  array(0=>'LEAD_GENERATION', 1=>'CREATIVE_ENGAGEMENT', 2=>'WEBSITE_TRAFFIC', 3=>'VIDEO_VIEW', 4=>'BRAND_AWARENESS', 5=>'WEBSITE_CONVERSION', 6=>'WEBSITE_VISIT', 7=>'ENGAGEMENT', 8=>'VIDEO_VIEWS');
			
			$stDt = "dateRange.start.day=".date('d',strtotime($last90))."&dateRange.start.month=".date('m',strtotime($last90))."&dateRange.start.year=".date('Y',strtotime($last90))."&";
			$enDt = "dateRange.end.day=".date('d',strtotime($today))."&dateRange.end.month=".date('m',strtotime($today))."&dateRange.end.year=".date('Y',strtotime($today));
			
			$cg_arr = $cg_arr2 = array();		
			$val['elements'] = $val2['elements'] = array();
			
			$valAcc = $linkedIn->get('v2/adCampaignGroupsV2?q=search&'.$accQry2.'sort.field=ID&fields=id,name,account');
			d($valAcc); exit;
			foreach($valAcc['elements'] as $key => $v1) 
			{
				//$cg_arr[$v1['id']] = $v1['name'];
					$in_acc = str_replace("urn:li:sponsoredAccount:", "", $v1['account']);
					$cirRes = mysqli_query($conn, "select * from in_campaigns_group WHERE camp_id='".$v1['id']."' AND in_acc='".$in_acc."' AND uid='".$uId."'");	
					
					if(mysqli_num_rows($cirRes)==0) {
							$cirSql = "INSERT INTO in_campaigns_group (uid, in_acc, camp_id, camp_name, created) VALUES ('".$uId."', '".$in_acc."', '".mysqli_real_escape_string($conn, $v1['id'])."', '".mysqli_real_escape_string($conn, $v1['name'])."', now());"; 
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					} 	
			}
			
			$valAcc2 = $linkedIn->get('v2/adCampaignsV2?q=search&&'.$accQry2.'sort.field=ID&fields=id,name,objectiveType,campaignGroup,account');
			foreach($valAcc2['elements'] as $key2 => $v2) 
			{
					$cmpObj[$v2['id']] = array_search($v2['objectiveType'],$in_obj);					
					$in_acc = str_replace("urn:li:sponsoredAccount:", "", $v2['account']);
					$cmpAcc[$v2['id']] = $in_acc;
					//$cg_arr2[$v2['id']] = $v2['name'];
					$cg = str_replace("urn:li:sponsoredCampaignGroup:", "", $v2['campaignGroup']);
					$cirRes2 = mysqli_query($conn, "select * from in_campaigns WHERE camp_id='".$cKey2."' AND in_acc='".$in_acc."' AND uid='".$uId."'");	
					
					if(mysqli_num_rows($cirRes2)==0) {
							$cirSql2 = "INSERT INTO in_campaigns (uid, in_acc, cg_id, camp_id, camp_name, camp_obj, created) VALUES ('".$uId."', '".$in_acc."', '".$cg."', '".mysqli_real_escape_string($conn, $v2['id'])."', '".mysqli_real_escape_string($conn, $v2['name'])."', '".mysqli_real_escape_string($conn, $v2['objectiveType'])."', now());"; 
							mysqli_query($conn, $cirSql2) or die(mysqli_error()); 
					} 
			}	
			
			//d($valAcc2); exit;
			
			$val = $linkedIn->get('v2/adAnalyticsV2?'.$accQry.'q=analytics&pivot=CAMPAIGN&timeGranularity=DAILY&fields=oneClickLeads,externalWebsiteConversions,likes,clicks,shares,totalEngagements,actionClicks,impressions,comments,dateRange,costInLocalCurrency,costInUsd,pivotValue&'.$stDt.''.$enDt);
			//d($val); exit;
			foreach ($val['elements'] as $k => $v) 
			{		
					//$cmpDetail[$v[0]] = $v[1];
					//$cmpObjDetail[$v[0]] = $v[13];
					
					$st = DateTime::createFromFormat('m-d-Y', $v['dateRange']['start']['month'].'-'.$v['dateRange']['start']['day'].'-'.$v['dateRange']['start']['year'])->format('Y-m-d');
					$en = DateTime::createFromFormat('m-d-Y', $v['dateRange']['end']['month'].'-'.$v['dateRange']['end']['day'].'-'.$v['dateRange']['end']['year'])->format('Y-m-d');
					$cgId = str_replace("urn:li:sponsoredCampaign:", "", $v['pivotValue']);
					$in_acc = $cmpAcc[$cgId];
					
					$cirRes = mysqli_query($conn, "select * from in_reports WHERE camp_id='".$cgId."' AND in_acc='".$in_acc."' AND uid='".$uId."' AND stDt='".strtotime($st)."' AND enDt='".strtotime($en)."'");	
					if(mysqli_num_rows($cirRes)==0) {
							$cirSql = "INSERT INTO in_reports (uid, in_acc, camp_id, camp_ty, spend, imp, web_conv, clicks, cost_usd, act_clicks, one_click_leads, shares, comment, engagements, likes, stDt, enDt, created) VALUES ('".$uId."', '".$in_acc."', '".mysqli_real_escape_string($conn, $cgId)."', '".mysqli_real_escape_string($conn, $cmpObj[$cgId])."', '".mysqli_real_escape_string($conn, $v['costInLocalCurrency'])."', '".mysqli_real_escape_string($conn, $v['impressions'])."', '".mysqli_real_escape_string($conn, $v['externalWebsiteConversions'])."', '".mysqli_real_escape_string($conn, $v['clicks'])."', '".mysqli_real_escape_string($conn, $v['costInUsd'])."', '".mysqli_real_escape_string($conn, $v['actionClicks'])."', '".mysqli_real_escape_string($conn, $v['oneClickLeads'])."', '".mysqli_real_escape_string($conn, $v['shares'])."', '".mysqli_real_escape_string($conn, $v['comments'])."', '".mysqli_real_escape_string($conn, $v['totalEngagements'])."', '".mysqli_real_escape_string($conn, $v['likes'])."', '".strtotime($st)."', '".strtotime($en)."', now());"; 
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
						} else {
							$cirSql = "UPDATE in_reports SET camp_id='".mysqli_real_escape_string($conn, $cgId)."', camp_ty='".mysqli_real_escape_string($conn, $cmpObj[$cgId])."', spend='".mysqli_real_escape_string($conn, $v['costInLocalCurrency'])."', imp='".mysqli_real_escape_string($conn, $v['impressions'])."', web_conv='".mysqli_real_escape_string($conn, $v['externalWebsiteConversions'])."', clicks='".mysqli_real_escape_string($conn, $v['clicks'])."', cost_usd='".mysqli_real_escape_string($conn, $v['costInUsd'])."', act_clicks='".mysqli_real_escape_string($conn, $v['actionClicks'])."', one_click_leads='".mysqli_real_escape_string($conn, $v['oneClickLeads'])."', shares='".mysqli_real_escape_string($conn, $v['shares'])."', comment='".mysqli_real_escape_string($conn, $v['comments'])."', engagements='".mysqli_real_escape_string($conn, $v['totalEngagements'])."', likes='".mysqli_real_escape_string($conn, $v['likes'])."', updated=now() WHERE camp_id='".$cgId."' AND in_acc='".$in_acc."' AND uid='".$uId."' AND stDt='".strtotime($st)."' AND enDt='".strtotime($en)."'";
							mysqli_query($conn, $cirSql) or die(mysqli_error()); 
					}
					
					
			} 
			
			//include 'linkedin-report.php';
}
echo 'success';


