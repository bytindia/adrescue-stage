<?php session_start(); 
$pgHeadline = 'Login';
$pgID = 1;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; 

$pgHeadline = 'Login';
$pgID = 1;
if(isset($_SESSION['uid'])) {
	$pg = 'dashboard.php?page=multi-client';
	echo "<script>window.location = 'loading.php?pg=$pg';</script>";
	exit();
}


$media_by = array(1=>'Ramesh', 14=>'Charan', 15=>'Mughil', 16=>'Karthik');
$bytUser = array('shaheena','mughil','charan','simin', 'karthik', 'ramesh', 'radhika', 'nida', 'lincy');
$bytPw = array('X@e)6%','Zb7c(p','6kCD-c','E2Ba}q','Xb3c#p','@d$Byt2024', 'xWP84*9a5b');

$accUser = array('accounts', 'faheem', 'admin');
$accPw = array('Ph*oEk2!*6ScoVUiV');
//$accPw = array('X@e)6%','Zb7c(p','6kCD-c','E2Ba}q','Xb3c#p','@d$Byt2024', 'xWP84*9a5b', 'Ph*oEk2!*6ScoVUiV');

if(isset($_POST['submit'])) {
	$uname = 	$_POST['email'];
	$pw = 	$_POST['password'];
	
	if (in_array($uname, $bytUser) && in_array($pw, $bytPw)) {
		$cirRes = mysqli_query($conn, query: "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc  FROM users WHERE tbl_id=2");
		//$_SESSION['guest'] = 1;
		$med_by_name = ucfirst(strtolower(trim($uname)));
		$media_key = array_search($med_by_name, $media_by);
		if ($media_key !== false) { $_SESSION['media_key'] = $media_key; }

    	$_SESSION['user_ty'] = 'ads'; 	$pg = 'dashboard.php?page=multi-client';	
	} else if (in_array($uname, $accUser) && in_array($pw, $accPw)) {
		$cirRes = mysqli_query($conn, "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc  FROM users WHERE tbl_id=2");
		//$_SESSION['guest'] = 1;
    	$_SESSION['user_ty'] = 'acc';
		$pg = 'index-acc.php';			
	} else {
		$cirRes = mysqli_query($conn, "SELECT tbl_id, g_id, access_token, fb_id FROM users WHERE username='$uname' and password='$pw'");
		$_SESSION['user_ty'] = 'acc'; $pg = 'index-acc.php';	
	}
	//$results = $conn->query("SELECT tbl_id, g_id, access_token, fb_id FROM users WHERE username='$uname' and password='$pw'");
	//$username_exist = $results->num_rows;
	
	
	//if($uname=='admin' && $pw=='bytads2019')
	if(mysqli_num_rows($cirRes)==1)
	{
				//$row = mysqli_fetch_array($results);				
				//echo $row['id']; exit;
				//$row = $results->fetch_assoc();
				//print_r($row); exit;				
				//$query = "SELECT tbl_id, g_id, access_token, fb_id FROM users WHERE tbl_id=2";
				//$result = mysqli_query($conn, $query);
				$row = mysqli_fetch_assoc($cirRes);
				$_SESSION['uid'] = $row['tbl_id'];
				$_SESSION['name'] = $row['name'];
				$_SESSION['fb_id'] = $row['fb_id'];
				$_SESSION['g_id'] = $row['g_id'];
				$_SESSION['g_refresh_token'] = $row['g_refresh_token'];
				$_SESSION['g_token'] = $row['g_token'];
				$_SESSION['g_mcc'] = $row['g_mcc'];
				$access_token = $row['access_token']; 
				
				$userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='".$_SESSION['uid']."'");	
			    $getRw2 = mysqli_fetch_assoc($userRes2);
			    if(mysqli_num_rows($cirRes)==1)
				{		   
					$_SESSION['in_id'] = $getRw2['in_id'];
					$_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
				}
				if(isset($_GET['redirect']) && $_GET['redirect']!=''){
				$pg = $_GET['redirect'];		
				}
						
				//if(isset($_SESSION['pg'])) { $pg=$_SESSION['pg']; } else { $pg = '/seller-home'; }
				//header('Location:'.$pg);
        
				$user['id'] = array_search($uname, $all_user); //$user['id'];
				$_SESSION['user_id'] = $user['id'];
				// Log login event
				$ip = $_SERVER['REMOTE_ADDR'];
				$user_agent = $_SERVER['HTTP_USER_AGENT'];
				$stmt = $conn->prepare("INSERT INTO user_logs (user_id, ip_address, user_agent, action) VALUES (?, ?, ?, 'login')");
				$stmt->bind_param("iss", $user['id'], $ip, $user_agent);
				$stmt->execute();

				$sql = "DELETE FROM user_logs WHERE timestamp < NOW() - INTERVAL 6 MONTH";
				if ($conn->query($sql) === TRUE) {
				// echo "Old logs deleted successfully!";
				} 

				echo "<script>window.location = 'loading.php?pg=$pg';</script>";
				exit();
	} else {
	
				$_SESSION['err'] = 'Invalid Login';		
				echo "<script>window.location = 'login.php';</script>";
				exit();		
				
	}
}
?> 
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="images/favicon.ico" type="image/ico" />

    <title>adRescue | Rescue Your Campaigns</title>

    <script>
		// Page is inside an iframe
		if (window.self !== window.top) { window.top.location.href = "login.php"; } 
	</script>
 

    <!-- Bootstrap CSS -->
    <link href="css/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/assets/css/app.css" rel="stylesheet">
    
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	
	<!-- Slick CSS -->
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
	
	<style>
		.feature-slider {
			width: 100%;
			max-width: 500px;
			margin: 20px auto;
			text-align: center;
		}

		.feature-slide {
			padding: 20px 10px;
			display: flex !important;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			text-align: center;
		}

		.feature-slide .slide-image {
			
			height: 250px;
			object-fit: contain;
			margin-bottom: 15px;
			border-radius: 8px;
		}

		.feature-slide i {
			font-size: 40px;
			color: #2575fc;
			margin-bottom: 10px;
		}

		.feature-slide h4 {
			font-size: 1.2rem;
			font-weight: 600;
			margin-bottom: 8px;
			color: #111;
		}

		.feature-slide p {
			font-size: 0.9rem;
			color: #444;
			max-width: 400px;
			margin: auto;
		}

		/* Slick dots custom style */
		.slick-dots {
			bottom: -30px;
			text-align: center;
		}
		.slick-dots li {
			display: inline-block;
			margin: 0 4px;
		}
		.slick-dots li button:before {
			font-size: 10px;
			color: #ccc;
			opacity: 1;
		}
		.slick-dots li.slick-active button:before {
			color: #2575fc;
		}
		
		.slider-container {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			width: 90%;
			max-width: 500px;
		}
	</style>

<body class="login">
	<!--wrapper-->
	<div class="wrapper">
		<div class="section-authentication-cover">
			<div class="">
				<div class="row g-0">

					<div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex" Style="background-color:#f1faff; position: relative;">

						
						<!-- Feature Slider -->
						<div class="slider-container">
							<div class="feature-slider">
								<div class="feature-slide">
									<img src="images/ad29.png" class="slide-image" alt="All-in-One Ads Management" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-layer-group" style="display:none;"></i>
									<h4>All-in-One Ads Management</h4>
									<p>Seamlessly manage campaigns across Meta, Google, LinkedIn, and more — all from one unified interface.</p>
								</div>
								<!--<div class="feature-slide">
									<img src="images/ad28.png" class="slide-image" alt="Multi-Account Dashboard" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-chart-line" style="display:none;"></i>
									<h4>Multi-Account Dashboard</h4>
									<p>Get a bird's-eye view of all client ad accounts with consolidated insights, KPIs, and spend analytics.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad9.png" class="slide-image" alt="Smart Client Dashboards" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-desktop" style="display:none;"></i>
									<h4>Smart Client Dashboards</h4>
									<p>Present performance with clarity — visualize MoM trends, ROI insights, and client-ready growth reports.</p>
								</div>-->
								<div class="feature-slide">
									<img src="images/ad9.png" class="slide-image" alt="Multi & Smart Client Dashboard" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-chart-line" style="display:none;"></i>
									<h4>Multi & Smart Client Dashboard</h4>
									<p>View all client ad accounts with insights, MoM trends, ROI, leads, CPL, conversions, and ready-to-share reports.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad21.png" class="slide-image" alt="Smart Invoicing Suite" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-file-invoice-dollar" style="display:none;"></i>
									<h4>Smart Invoicing Suite</h4>
									<p>Automate billing with invoicing, tax, ad spend, ad report & SOA attachments, and WhatsApp updates.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad7.png" class="slide-image" alt="Download or Email Reports" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-download" style="display:none;"></i>
									<h4>Download & Email Reports</h4>
									<p>Instantly export or email performance reports in Excel, PDF, or CSV for easy sharing and review.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad6.png" class="slide-image" alt="AdTracker Pro" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-bullseye" style="display:none;"></i>
									<h4>AdTracker Pro</h4>
									<p>Monitor campaign performance in real-time — track audiences, budgets, and creative results effortlessly.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad24.png" class="slide-image" alt="Audit Ad Accounts" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-search-dollar" style="display:none;"></i>
									<h4>Audit Ad Accounts</h4>
									<p>Run quick audits on ad accounts to detect inefficiencies, wasted spend, and optimization opportunities.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad27.png" class="slide-image" alt="Powerful Automations" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-robot" style="display:none;"></i>
									<h4>Powerful Automations</h4>
									<p>Smart automations for ads — adjust budgets, promotions and audiences in real time.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad26.png" class="slide-image" alt="Budget & Cashflow Alerts" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-bell" style="display:none;"></i>
									<h4>Budget Planning & Cashflow</h4>
									<p>Control ad spend, track account balances, and forecast campaign budgets with real-time alerts.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad22.png" class="slide-image" alt="Setup Leads" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-users" style="display:none;"></i>
									<h4>Lead Integration</h4>
									<p>Instantly route leads to Sheets, CRMs, or ERPs with webhooks, email alerts, and auto-push integrations.</p>
								</div>
								<div class="feature-slide">
									<img src="images/ad19.png" class="slide-image" alt="Custom VLOOKUP Tool" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
									<i class="fas fa-table" style="display:none;"></i>
									<h4>Custom VLOOKUP Tool</h4>
									<p>Upload sheets, cross-match data, and generate actionable insights with advanced comparison logic.</p>
								</div>
							</div>
						</div>
						
					</div>

					<div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
						<div class="rounded-0 m-3 shadow-none bg-transparent mb-0">
							<div class="card-body p-sm-5">
								<div class="">
									<div class="mb-3 text-center">
										<img src="images/adRes-b.png" width="160px" alt="AdRescue Logo">
									</div>
                  <?php include 'alert.php'; ?>
									<div class="text-center mb-4">
										
									</div>
									<div class="form-body">
										<form class="row g-3" method="post" action="" id="login-form">
										 

											<div class="col-12">
												<label for="inputEmailAddress" class="form-label">Username</label>
												<input type="text" name="email" class="form-control" id="inputEmailAddress" placeholder="User Name" required>
											</div>
											<div class="col-12">
												<label for="inputChoosePassword" class="form-label">Password</label>
												<div class="input-group" id="show_hide_password">
													<input type="password" name="password" class="form-control border-end-0" id="inputChoosePassword"  placeholder="Enter Password" required> <a href="javascript:;" class="input-group-text bg-transparent"><i class="fa-regular fa-eye-slash"></i></a>
												</div>
											</div>
											<!-- <div class="col-md-6">
												<div class="form-check form-switch">
													<input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked">
													<label class="form-check-label" for="flexSwitchCheckChecked">Remember Me</label>
												</div>
											</div>
											<div class="col-md-6 text-end">	<a href="authentication-forgot-password.html">Forgot Password ?</a>
											</div> -->
											<div class="col-12">
												<div class="d-grid">
													<button type="submit" name="submit" class="btn btn-primary" value="Login" >Log in</button>
												</div>
											</div>
											
										</form>
									</div>
									<!-- <div class="login-separater text-center mb-5"> <span>OR SIGN IN WITH</span>
										<hr>
									</div>
									<div class="list-inline contacts-social text-center">
										<a href="javascript:;" class="list-inline-item bg-facebook text-white border-0 rounded-3"><i class="bx bxl-facebook"></i></a>
										<a href="javascript:;" class="list-inline-item bg-twitter text-white border-0 rounded-3"><i class="bx bxl-twitter"></i></a>
										<a href="javascript:;" class="list-inline-item bg-google text-white border-0 rounded-3"><i class="bx bxl-google"></i></a>
										<a href="javascript:;" class="list-inline-item bg-linkedin text-white border-0 rounded-3"><i class="bx bxl-linkedin"></i></a>
									</div> -->

								</div>
							</div>
						</div>
					</div>

				</div>
				<!--end row-->
			</div>
		</div>
	</div>
	<!--end wrapper-->
	<style>.alert-fixed { position: relative !important; top: 0; } </style>
	<!-- Bootstrap JS -->
	 <script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
	<!--plugins-->
	<script src="css/assets/js/jquery.min.js"></script>
	<!--Password show & hide js -->
	<script>
		$(document).ready(function () {
			$("#show_hide_password a").on('click', function (event) {
				event.preventDefault();
				if ($('#show_hide_password input').attr("type") == "text") {
					$('#show_hide_password input').attr('type', 'password');
					$('#show_hide_password i').addClass("fa-eye-slash");
					$('#show_hide_password i').removeClass("fa-eye");
				} else if ($('#show_hide_password input').attr("type") == "password") {
					$('#show_hide_password input').attr('type', 'text');
					$('#show_hide_password i').removeClass("fa-eye-slash");
					$('#show_hide_password i').addClass("fa-eye");
				}
			});
		});
	</script>
	<!--app JS-->
	<script src="css/assets/js/app.js"></script>
	
	<!-- Slick JS -->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
	
	<script>
		$(document).ready(function(){
			$('.feature-slider').slick({
				dots: true,
				arrows: false,
				autoplay: true,
				autoplaySpeed: 2500,
				infinite: true,
				speed: 800,
				cssEase: 'ease-in-out',
				adaptiveHeight: true
			});
		});
	</script>

</body>

</html>