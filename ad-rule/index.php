<?php session_start();
if(!isset($_SESSION['logged'])) {
	
	echo "<script>window.location = 'login.php';</script>";
	exit();
} 
include '../db.php'; 
include 'config.php';
include 'functions.php';
if(isset($_GET['id'])) {
	if(isset($_GET['del'])) {
		mysqli_query($conn, "UPDATE ad_rules SET del_admin='yes' where tbl_id=".$_GET['id']."");
	} else if(isset($_GET['act'])) {
		if($_GET['act']==1) { $act='no'; } else { $act='yes'; }
		mysqli_query($conn, "UPDATE ad_rules SET activate='".$act."' where tbl_id=".$_GET['id']."");
	}
	$_SESSION['suc'] = 'Successfully Deleted!';	
	echo "<script>window.location = 'index.php';</script>";
	exit();
}
?>
<html>
<head>
  <title>AdRescue - AdRule</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/all.css">
  <style>
    body {margin:2em;}
    td:last-child {text-align:center;}
	a:hover { text-decoration: none !important; }
	.fa { color: #2cb1f1; }
    </style>
  </head>

<body>
<div class="container">
<br><br>
<div class="card">
<article class="card-body mx-auto" style="width:90%;">
<div class="float-right"><a type="button" class="btn btn btn btn-outline-success" href="edit.php">Add New Rule</a>  <a type="button" class="btn btn btn-outline-danger ml-2" href="logout.php">Logout</a></div>
<h5>Ad Pause - Rule Setup</h5><br>
  <table id="example" class="table table-striped table-bordered" cellspacing="0" width="100%">
	<thead>
		<tr>
			<th>SNo</th>
			<th>Rule Name</th>
			<th>Ad Account</th>
			<th>Trigger (Every)</th>
			<th>Check Rep.</th>
			<th style="text-align:center;width:100px;">
			Edit</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$sqlRev=mysqli_query($conn, "SELECT * FROM ad_rules WHERE del_admin='no' order by activate desc");
	$i = 1;

										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
		<tr>
			<td><?php echo $i; ?></td>
			<td><span class="fa fa-circle" aria-hidden="true" style="font-size:11px; color:<?php if($sqlROW["activate"] =='yes') { echo '#17c917;'; } else { echo '#ef894d;'; } ?>"></span> <?php echo $sqlROW["rule_name"]; ?></td>
			<td><?php echo $fbAccN[$sqlROW["ad_acc"]]; ?></td>
			<td><?php echo $check_every[$sqlROW["run_script"]]; ?></td>
			<td><?php echo $check_rep[$sqlROW["check_rep"]]; ?></td>
			<td  class="form-inline">
				<div class="btn-group" role="group" aria-label="Basic example">
									<?php if($sqlROW["activate"] =='yes') { ?>
									<button type="button" class="btn btn-outline-light"><a href="index.php?id=<?php echo $sqlROW["tbl_id"]; ?>&act=1"><i class="fa fa-pause"></i> </span></a></button>
									<?php } else { ?>
									<button type="button" class="btn btn-outline-light"><a href="index.php?id=<?php echo $sqlROW["tbl_id"]; ?>&act=2"><i class="fa fa-play"></i> </span></a></button>
									<?php } ?>
									<button type="button" class="btn btn-outline-light"><a href="edit.php?id=<?php echo $sqlROW["tbl_id"]; ?>"><i class="fa fa-edit"></i> </span></a></button>
									<button type="button" class="btn btn-outline-light"><a href="index.php?id=<?php echo $sqlROW["tbl_id"]; ?>&del=1" onclick="return confirm('Are you sure you want to Delete?');"><i class="fa fa-trash" style="color:#f36759;"></i> </span></a></button>
				</div>
			</td>
		</tr>
		<? $i++; } ?>
	</tbody>
</table>
</article>
</div>
</div>

<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
  <script  src="https://cdpn.io/cpe/boomboom/pen.js?key=pen.js-92a31363-c75d-8c68-f680-338e099f9bbf" crossorigin></script>
</body>

</html>
<script>
    $(document).ready(function() {
	//Only needed for the filename of export files.
	//Normally set in the title tag of your page.
	//document.title='Simple DataTable';
	// DataTable initialisation
	$('#example').DataTable(
		{
			"dom": '<"dt-buttons"Bf><"clear">lirtp',
			"paging": false,
			"autoWidth": true,
			"columnDefs": [
				{ "orderable": false, "targets": 5 }
			],
			searching: false, paging: false, info: false
			
		}
	);
	
});
</script>