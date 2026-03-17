<?php
$check_every = array(
    1 => '15 mins',
    2 => '30 mins',
    3 => '1 hour',
    4 => '2 hours',
    5 => '3 hours',
    6 => '6 hours',
    7 => '12 hours',
    8 => '24 hours',
);
$check_rep = array(
  1=> 'Today',
  2=> 'Last 2d',
  3=> 'Last 3d',
  4=> 'Last 5d',
  5=> 'This week',
  6=> 'This Month',
);
$rule_match = array(
  1=> 'Match All',
  2=> 'Match Any',
);
$metrics = array(
    'spend' => 'Spend',
    'leadgen_grouped' => 'Leads (LG)',
    'cpl' => 'CPL',
    'conversions' => 'Conversions',
    'clicks' => 'Clicks',
    'reach' => 'Reach',
    'impressions' => 'Impressions',
    'post_engagement' => 'Post Engagement',
    'link_click' => 'Link Clicks',
    'cpc' => 'CPC',
    'cpm' => 'CPM',
    'ctr' => 'CTR',
    'like' => 'Likes',
    'comment' => 'Comments',
    'video_view' => 'Video View',
    'page_engagement' => 'Page Engagement'
  );

  $metrics = array(
    'spend' => 'Spend',
    'leadgen_grouped' => 'Leads (LG)',
    'cpl' => 'CPL',
    'roas' => 'ROAS',
    'conversions' => 'Conversions (WC)',
    'purchase_roas' => 'Purchase ROAS',
  );
  
  $operation = array(
      '<' => '< less than',
      '>' => '> greater than',
      '<=' => '<= less than or equal',
      '>=' => '>= greater than or equal',
      '=' => '= equal',
      '!=' => '!= not equal',
  );

$ad_level = array(1=>'Campaign', 2=>'AdSet', 3=>'Ad'); 
$ad_action = array(1=>'Pause', 2=>'Budget Increase', 3=>'Budget Reduce'); 

$obj_arr = array(
    'POST_ENGAGEMENT' => 'post_engagement', 
    'LINK_CLICKS' => 'link_click',
    'VIDEO_VIEWS' => 'video_view',
    'LEAD_GENERATION' => 'leadgen_grouped',
    'CONVERSIONS' => 'offsite_conversion.fb_pixel_lead',
    'MESSAGES' => 'onsite_conversion.messaging_block',
    'OUTCOME_LEADS' => 'lead',
    'OUTCOME_SALES' => 'offsite_conversion.fb_pixel_purchase'
);

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

$fbAccN = array();
$sqlRev1=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='2' order by name asc");
while($sqlROW1=mysqli_fetch_array($sqlRev1)) { $fbAccN[$sqlROW1["account_id"]] = $sqlROW1["name"]; }


$check_rep_dt = array(
    1=> array(date('Y-m-d'), date('Y-m-d')),
    2=> array(date('Y-m-d'), date('Y-m-d',strtotime('-1 days'))),
    3=> array(date('Y-m-d'), date('Y-m-d',strtotime('-2 days'))),
    4=> array(date('Y-m-d'), date('Y-m-d',strtotime('-4 days'))),
    5=> array(date('Y-m-d'), date('Y-m-d',strtotime('-5 days'))),
    6=> array(date('Y-m-d',strtotime('monday this week')), date('Y-m-d')),
    7=> array(date('Y-m-d',strtotime('first day of this month')), date('Y-m-d')),
);

$acc_type = array(1=>'Real Estate', 2=>'Coaching', 3=>'Education', 4=>'Ecommerce', 5=>'Others');

$cam_obj = array(
  1=>array('CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS'),
  2=>array('CONVERSIONS', 'OUTCOME_LEADS', 'OUTCOME_SALES'),
  3=>array('CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS'),
  4=>array('CONVERSIONS', 'OUTCOME_SALES', 'PRODUCT_CATALOG_SALES'),
  5=>array('CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS')
);

$cam_obj2 = array(
  1=>"'CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS'",
  2=>"'CONVERSIONS', 'OUTCOME_LEADS', 'OUTCOME_SALES'",
  3=>"'CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS'",
  4=>"'CONVERSIONS', 'OUTCOME_SALES', 'PRODUCT_CATALOG_SALES'",
  5=>"'CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS'"
);

$metrics_obj = array(
    'leadgen_grouped' => array('LEAD_GENERATION', 'OUTCOME_LEADS'),
    'roas' => array('CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS', 'OUTCOME_SALES'),
    'conversions' => array('CONVERSIONS'),
    'purchase_roas' => array('CONVERSIONS', 'LEAD_GENERATION', 'OUTCOME_LEADS'),
);