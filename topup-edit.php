<?php include 'header.php'; 
// Enable error reporting for debugging (only for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'];
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $daily_budget = $_POST['daily_budget'];
    $bud_bal = $_POST['bud_bal'];
    $tbl_id = $id; // make sure this is passed from form

    $stmt = $conn->prepare("UPDATE topup SET daily_budget = ?, bud_bal = ? WHERE tbl_id = ?");
    $stmt->bind_param("ddi", $daily_budget, $bud_bal, $tbl_id);
    $stmt->execute();

    header("Location: topup.php");
    exit();
} else {
    $res = $conn->query("SELECT * FROM topup WHERE tbl_id = $id");
    $data = $res->fetch_assoc();
}



$result = $conn->query("SELECT * FROM topup");

if (!$result) {
    die("Query failed: " . $conn->error);
}
// Optional: Enable custom logging to a file
ini_set("log_errors", 1);
ini_set("error_log", "logs/php-error.log"); // Make sure this path is writable

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
$dt_q ='';
if(isset($_GET['st']) && $_GET['st']!=''){
  $dt_q = '&st='.$_GET['st'].'&en='.$_GET['en'];
}

Auth();
$pgHeadline = 'Edit Topup';
$pgID = 8;
$err =''; 
function moneyFormatIndia($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}
// if(isset($_GET['del'])) {
// 	mysqli_query($conn, "UPDATE card_changes SET delete_status='1' where tbl_id=".$_GET['del']."");
// 	$_SESSION['suc'] = 'Successfully Deleted!';	
// 	echo "<script>window.location = 'cards.php';</script>";
// 	exit();
// }

// include 'pagination.php';

// $page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
// if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

// $startpoint = ($page * $per_page) - $per_page;
// $statement = " card WHERE uid='".$_SESSION['uid']."' AND delete_status=0";

?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php 
			include 'menu-left.php';
			include 'menu-top.php'; 
		?>

        
        <!-- page content -->
         <div class="right_col" role="main">
          
         <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                 
                  <div class="x_title">
                    <h2><?php echo $pgHeadline; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li> &nbsp;
                      </li>                      
                    </ul>                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                        <?php include 'alert.php'; ?>
                        <form method="post" action="">
                           <div class="content">
                                <div class="container-fluid">
                                  <div class="row">      
                                            <div class="col-md-1"></div>
                                                
                                            <div class="col-md-8">     
                                                <label> Client: </label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['client']); ?>" disabled>
                                                <br />
                                                <label> Daily Budget: </label>
                                                <input type="text" name="daily_budget" class="form-control" value="<?php echo htmlspecialchars($data['daily_budget']); ?>" required>
                                                <br />
                                                <label> Balance: </label>
                                                <input type="number" name="bud_bal" class="form-control" value="<?php echo htmlspecialchars($data['bud_bal']); ?>" required>
                                                <br />
                                                <input type="submit" name="submit" value="Update Topup" class="btn btn-info">
                                             
                                             <br />
                                            </div>
                                          
                                        </div>
                                    </div>
                                </div>
                                </form>
                                
         
                        </div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
