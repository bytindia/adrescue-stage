<?php ini_set('display_errors', 0); ?>
<!-- Include Required Prerequisites -->
<script type="text/javascript" src="//cdn.jsdelivr.net/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap/3/css/bootstrap.css" />
 
<!-- Include Date Range Picker -->
<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php  
function moneyFormatIndia($num) {
    $num = round($num);
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
    if($thecash==0) { $thecash='-'; }
    return $thecash; // writes the final format where $currency is the currency symbol.
}

function contains($str, array $arr)
{
    foreach($arr as $a) {
      //print_r($a);
        if (stripos($str,$a) !== false) { return $a; }
    }
    return false;
}

if(isset($_GET['st'])) { 
        $stDt =  $_GET['st']; 
        $enDt =  $_GET['en']; 
        $filter = 'yes';
        $dtRange = $_GET['st'].' - '.$_GET['en']; 
        $dtRange1 = str_replace('/', '-', $_GET['st']); $dtRange2 = str_replace('/', '-', $_GET['en']);
        $urlParam = '?st='.$stDt.'&en='.$enDt;
    } else { 
        $stDt = date('d/m/Y');
        $enDt = date('d/m/Y'); 
        $filter = 'no';

        $d = new DateTime('first day of this month');
        //$d = new DateTime('first day of this month');
        //echo $d->format('d/m/Y');

        $stDt = $d->format('d/m/Y');
        $enDt = date('d/m/Y');
        
        $dtRange = 'this month';
        //$dtRange1 = '-29 days'; $dtRange2 = '0 days';
        $dtRange1 = $d->format('Y-m-d'); 
        $dtRange2 = '0 days';
        $urlParam = '';
   } 
  // $urlParam = '';
if(isset($overview) && !isset($_GET['st']))
{
    $d = new DateTime('first day of this month');
    //$d = new DateTime('first day of this month');
    //echo $d->format('d/m/Y');

    $stDt = $d->format('d/m/Y');
    $enDt = date('d/m/Y');
}

//echo "time_range[since]=".date("Y-m-d", strtotime($dtRange1))."&time_range[until]=".date("Y-m-d", strtotime($dtRange2)); exit;
?>
<style>
    .rep_title { float:left; margin-left: 15px; font-size:25px; }
    .percent { color: #2d89d7;  font-size: 12px; font-style: italic; }
    .font-italic b, strong {font-weight: 700; color: #13a911; font-size: 13px;}
</style>
<body>

<div class="loader">
  <div class="loader-wheel"></div>
  <div class="loader-text"></div>
</div>
<?php if(!isset($header_no)) { ?>
<div class="right_col" role="main">
    <div class="row" style="margin-right: 0px; margin-bottom: 10px;">
        <div class="col-12">
        <span class="pull-right">
        <a href="overview.php" target="_top">Overview</a> 
        <?php if(count($fb_acc_ids)>0) { ?>| <a href="loading.php?pg=facebook.php" target="_top">Facebook</a> <?php } ?>
        <?php if(count($g_acc_ids)>0) { ?>| <a href="loading.php?pg=google.php" target="_top">Google</a> <?php } ?>
        <?php if(count($in_acc_ids)>0) { ?>| <a href="loading.php?pg=linkedin.php" target="_top">LinkedIn</a> <?php } ?>
        <?php if(count($ta_acc_ids)>0) { ?>| <a href="loading.php?pg=taboola.php" target="_top">Taboola</a> <?php } ?>
        | <a href="logout.php">Logout</a>
        </span>
        </div>   
    </div>

    <div class="row">
      <div class="col-4">
      <span class="rep_title"> 
      <?php echo $pgName; ?> 
      </span>
      </div>
      <div class="col-8">
        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; margin-right: 15px;">
                                                <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
                                                <span><?php echo $stDt.' - '.$enDt; ?></span> <b class="caret"></b>
        </div>
      </div>       
    </div>
    <center><span class="font-italic percent">Reports on: <b><?php echo date("d-m-Y", strtotime($dtRange1)).'</b> to <b>'.date("d-m-Y", strtotime($dtRange2)); ?></b>.  Reporting time: <b><?php echo date("d-m-Y, h:i a"); ?></b></span></center><br>
</div>
<?php } ?> 
