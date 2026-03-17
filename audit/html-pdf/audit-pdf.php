<?php session_start(); 
require_once('tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 061');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 061', PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

if($_SERVER["HTTPS"] != "on")
{
   // header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    //exit();
}
include '../db.php'; //auditAuth(); 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
//auditAuth();
$pgHeadline = 'Partha Sarathy - Facebook - Audit';
$pgID = 3;
$err =''; 


//include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " adAccounts WHERE uid='".$_SESSION['uid']."'";

//$val = (new AdAccount($sqlROW["id"]))->getInsights($fields, $params)->getResponse()->getContent();

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
$ac_status = array(
1 => 'ACTIVE',
2 => 'DISABLED',
3 => 'UNSETTLED',
7 => 'PENDING_RISK_REVIEW',
8 => 'PENDING_SETTLEMENT',
9 => 'IN_GRACE_PERIOD',
100 => 'PENDING_CLOSURE',
101 => 'CLOSED',
201 => 'ANY_ACTIVE',
202 => 'ANY_CLOSED'
);
function dateDiffInDays($date1, $date2)  
{ 
    // Calulating the difference in timestamps 
    $diff = strtotime($date2) - strtotime($date1); 
      
    // 1 day = 24 hours 
    // 24 * 60 * 60 = 86400 seconds 
    return abs(round($diff / 86400)); 
} 
$html .= '<style>'.file_get_contents('/home/faheems/webapps/adsninja/assets2/css/bootstrap.css').'</style>';
$html .= '<style>'.file_get_contents('/home/faheems/webapps/adsninja/assets2/fonts/icomoon.css').'</style>';
$html .= '<style>'.file_get_contents('/home/faheems/webapps/adsninja/assets2/css/statistics-card.css').'</style>';
$html .= '<style>'.file_get_contents('/home/faheems/webapps/adsninja/assets2/css/colors.css').'</style>';
$html .= <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
<style>
.nav-sm .main_container .top_nav, .nav-sm .container.body .right_col, .nav-sm footer { margin-left:0px; }
#menu_toggle { display:none; }
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
.text-xs-left { vertical-align:middle; }
.scrollClass { height:188px; overflow-y: scroll; }
.card-block { padding: 10px 2px 10px 10px; }
</style>
EOF;
//echo $html;
//echo $html; 
$html .= '
<body class="nav-md">
    <div class="container body">
      <div class="main_container">
';
			//include 'menu-left.php';
			//include 'menu-top.php'; 
			
			$sqlR = mysqli_query($conn, "SELECT audit_name from audit_reports where tbl_id=".$_GET['tbl_id']."");
								
								while($sqlROW=mysqli_fetch_array($sqlR))
								{ 
									$rowR  = $sqlROW;
								}
		

        
$html .= '
        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2>'.$rowR['audit_name'].' - Audit Report</h2>
                    
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
 ';
								$fields = array(
									0 => 'Account Age',
									1 => 'Account Status',
									2 => 'No. of Active Campaigns',
									3 => 'CBO Campaigns',
									4 => 'Pixel Codes',
									5 => 'Custom Conversions',
									6 => 'Remarketing List',
									7 => 'Top 25% Website visitors',
									8 => 'Look Alike of Top 25% Website visitors',
									9 => 'Rules',
									10 => 'Custom Audience',
									11 => 'Look-alike Audiences',
									12 => 'Narrow Audiences',
									13 => 'Budget type',
									14 => 'Day parting (Lifetime budget)',
									15 => 'Linked Instagram Account',
									16 => 'Instagram Followers',
									17 => 'Audience Expansion',
									18 => 'Dynamic Creative Ads',
									19 => 'Last Visuals Update Date',
									
									20 => 'Location Targets',
									21 => 'Bidding Type',
									22 => 'Placement Type (Auto/Manual)',
									
									23 => 'Age Group Targets',
									24 => 'Ad Types',
									25 => 'Results based on Ad Types',
									26 => 'Best Performing Ad Type',
									27 => 'Ad Quality Ranking',
									
									28 => 'Objective wise cost per results',
									29 => 'Campaigns - High CPL',
									30 => 'Landing Pages',
									31 => 'Lead Form Questions',
									
									32 => 'Interest-based Targets',
									33 => 'Work Employer Targets',
									34 => 'Work Position Targets',
									
								);
								
								$ans = array(
									0 =>'',
									1 =>'',
									2 =>'',
									3 =>'',
									4 =>'',
									5 =>'',
									6 =>'',
									7 =>'',
									8 =>'',
									9 =>'',
									10 =>''
								);
								$sqlRev = mysqli_query($conn, "SELECT age,active, camp_tot, cbo_camp, pix_act, cust_conv, remarket, top_web_25, top_la_25, rules, cust_aud, la_aud, nar_aud, daily_life, day_part, insta_acc, ig_followers, exp_on, dynamic, img_updated, loc_type, bid_type, placement, age_group, adset_type, ad_type_res, ad_type_best, ad_qty, obj_cost_result, high_cpl, lp_url, lead_qus, interests, work_emp, work_pos FROM audit_data where rep_id='".$_GET['tbl_id']."'");
								
								while($sqlROW=mysqli_fetch_array($sqlRev))
								{ 
									$ans  = $sqlROW;
								}
								
										$active_or_yes = array('active', 'yes');
										$inactive_or_no = array('inactive', 'no');
										
										
										
										//Columns must be a factor of 12 (1,2,3,4,6,12)
										$numOfCols = 4;
										$rowCount = 0;
										$bootstrapColWidth = 12 / $numOfCols;
										foreach ($fields as $k => $v)
										{ 
											if($k<=27) {
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											
											$ans[$k] = str_replace('<>', '<br>', $ans[$k]);
											
											
										  if($rowCount % $numOfCols == 0) { $html .= '<div class="row">'; } 
											$rowCount++; 
											
											if(in_array(strtolower($ans[$k]), $active_or_yes)) { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; } else if(in_array(strtolower($ans[$k]), $inactive_or_no)) { $cls="class='deep-orange'"; $clr='deep-orange'; $icon="icon-close"; } else { $cls=""; $clr=''; $icon=""; }
											
											$html .= ' 
                                                <div class="col-xl-3 col-lg-8 col-xs-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="card-block">
                                                                <div class="media">
                                                                    <div class="media-body text-xs-left">
                                                                        <h5>'.$v.'</h5>';
                                                            			 if($icon=='') { $html .= '<span '.$cls.'>'.$ans[$k].'</span>'; } 
                                                                    $html .= '</div>';
                                                                    if($icon!='') { 
                                                                    $html .= '
																	<div class="media-left media-middle">
                                                                        <i class="'.$icon.' '.$clr.' font-large-2 float-xs-right"></i>
                                                                    </div>';
                                                                     } 
                                                               $html .= ' </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>';
										
											if($rowCount % $numOfCols == 0) { $html .= ' </div> '; } } 
										}
										$numOfCols = 2;
										$rowCount = 0;
										$bootstrapColWidth = 12 / $numOfCols;
										foreach ($fields as $k => $v)
										{ 
											if($k>=28) {
											if($k==1) { $ans[$k] = $ac_status[$ans[$k]]; }
											if($k==0) { $ans[$k] = dateDiffInDays($ans[$k], date('Y-m-d')).' days';  }
											if($k>11 && $k<18 && $k!=15) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											if($k>20 && $k<28) { $ans[$k] = str_replace('<>', '<br>', $ans[$k]); }
											
											$ans[$k] = str_replace('<>', '<br>', $ans[$k]);
											
											
										  if($rowCount % $numOfCols == 0) { $html .= ' <div class="row">'; } 
											$rowCount++; 
											
											if(in_array(strtolower($ans[$k]), $active_or_yes)) { $cls="class='teal'"; $clr='teal'; $icon="icon-check"; } else if(in_array(strtolower($ans[$k]), $inactive_or_no)) { $cls="class='deep-orange'"; $clr='deep-orange'; $icon="icon-close"; } else { $cls=""; $clr=''; $icon=""; }
											
											$html .= '
                                                <div class="col-xl-6 col-lg-8 col-xs-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="card-block">
                                                                <div class="media">
                                                                    <div class="media-body text-xs-left">
                                                                        <h5>'.$v.'</h5>';
                                                            			 if($icon=='') { $html .= '<div class="scrollClass" '.$cls.'>'.$ans[$k].'</div>'; } 
                                                                    $html .= '</div>';
                                                                    if($icon!='') { 
																	$html .= '
                                                                    <div class="media-left media-middle">
                                                                        <i class="'.$icon.' '.$clr.' font-large-2 float-xs-right"></i>
                                                                    </div>';
                                                                     } 
                                                                $html .= '</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>';
										
											if($rowCount % $numOfCols == 0) { $html .= '</div>'; } } 
										}
										

                                    	                                   
                                   
	$html .= '
                                <br><br>
								

						
         
        				</div>
                </div>
              </div>
        </div>
        </div>';
		
$pdf->writeHTML($html, true, false, true, false, '');

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------
$pdf->Output('/home/faheems/webapps/adsninja/audit/html-pdf/save-pdf/2.pdf', 'F');