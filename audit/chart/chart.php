<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simple Donut jQuery Demo</title>
    <link href="http://www.jqueryscript.net/css/jquerysctipttop.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="_styles/style.css" type="text/css">
    <link rel="stylesheet" href="_styles/simple-donut.css" type="text/css">
    <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js"></script>
    <script type="text/javascript" src="_scripts/simple-donut-jquery.js"></script>
    <style>
    .btns { text-align:center; margin:30px auto;}
    button { padding:10px 20px; border:0; border-bottom:3px solid #DA4453; background-color:#ED5565; color:#fff; text-align:center; border-radius:3px;}
    </style>
  </head>
  <body>
<div style="height:300">
  <div id="specificChart" class="chart_wrapper">
	<div>

		<div class="pie-wrapper">
			  <div class="label">			    
				<div class="pie">
				  <div class="left-side half-circle"></div>
				  <div class="right-side half-circle"></div>
				</div>
				<div class="shadow inner-pie ">
					<div class="profile">
						<img src="images/user.png" alt=""/>
						<h3>Faheem Ahmed</h3>
					</div>
				  <h5>You have scored</h5>
				  <div class="score"><span>75</span>/100</div>    
				</div>
			</div>
		</div>
		<div class="bottom-text">
			<h3>AdsRescue</h3>
			<p>Grading Scale<p>
		</div>
		<div class="footer">
			<img src="images/byt-logo.png" />
		</div>
	</div>
  </div>
    <div class="btns">
<button id='update1'>57%
</button>
<button id='update2'>77%
</button>
<button id='update3'>100%
</button>
</div>
</div>
  <script>
	$( "#update1" ).click(function() {
  updateDonutChart('#specificChart', 57, true);
});
	$( "#update2" ).click(function() {
  updateDonutChart('#specificChart', 77, true);
});
	$( "#update3" ).click(function() {
  updateDonutChart('#specificChart', 100, true);
});
      
    </script>

  </body>
</html>
