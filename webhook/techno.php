<?php error_reporting(E_ALL);
ini_set('display_errors', 1);
//echo phpinfo(); exit;
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function find_parent($array, $needle, $parent = null) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $pass = $parent;
            if (is_string($key)) {
                $pass = $key;
            }
            $found = find_parent($value, $needle, $pass);
            if ($found !== false) {
                return $found;
            }
        } else if ($key === 'id' && $value === $needle) {
            return $parent;
        }
    }

    return false;
}
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
/*

//echo json_encode($json);
$str = '{cf_affiliate_id:"", time_zone:"", utm_source:"scr1", utm_medium:"", utm_campaign:"camp1", utm_term:"", utm_content:"", cf_uvid:"null", webinar_delay:"-63848325830015", :purchase:{"payment_method_nonce":"", "order_saas_url":""}}';
$array = explode(',',$str);
//print_r($array);
$search= 'utm_source';
$key   = key(array_filter($array, function($x) use ($search){ return false!==stripos($x, $search); })); 
//$str = explode(',',$str );
print_r($key); 

$parsed = get_string_between($array[$key], '"', '"');
print_r($parsed); 
exit;
//$str = 'Hey, Welcome to geeksforgeeks';
//$substring = string_between_two_string($str, 'utm_source=>"', '","');
 
//echo $substring;

$fullstring = "this is [tag]dog[/tag], [tag]cat[/tag], [tag]lion[/tag]";

preg_match_all('"\[utm_source=>"\](.*?)\[\/"\]"si', $str, $match);

foreach($match[1] as $val){
    echo $val, ' ';
}

exit;
*/
?>


<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />

<title>VLookup - AdRescue</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Bootstrap -->
<link href="/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body, html {
    width: 80%;
    margin: 10 auto;
    font-family: Trebuchet MS, sans-serif;
}
.table { font-size: 14px;}
ul.nav.navbar-right.panel_toolbox {
    float: right;
    margin-bottom: 20px;
}
footer {
    margin-top: 25px;
    float: right;
    font-size: 14px;
}
@media (min-width: 768px) {
.form-horizontal .control-label {
  
    text-align: left !important; 
}}
tfoot, thead {
    background: #3e99e8;
    font-weight: bold;
    color: white;
}
tfoot td {
    text-align: right !important;
}
thead td {
    text-align: center !important;
}
</style>

<?php
//echo $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);


include '/home/digitalb2k/stage.adrescue.in/db.php';


$campLeadTot = array();



//d($data_g); exit;
$cqV = '';

//d($new);
//exit;

$raw_Val = $csvData = $csvData2 = $csvData3 = $raw_Keys = $sale_Keys = $sale_Val = array();

if(isset($_POST['submit'])) {
    
    //print_r($_FILES); exit;
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, "r");
    $c = 0;

   
 
    $allowed =  array('csv',);
    $filename = $_FILES['file']['name'];
     $ext = pathinfo($filename, PATHINFO_EXTENSION);
    //exit;
    $c2;
    if(!in_array($ext, $allowed) ) {
        $_SESSION['err'] = 'Please upload CSV file(raw) format!';	
        echo "<script>window.location = 'index.php';</script>";
        exit();
    }

   

    $j = 1; 
    $output = 1;
    while(($filesop = fgetcsv($handle, 50000, ",")) !== false)
	{
            //d($filesop); exit;
			if($c==0) {
				
				$csvFields[] = $filesop;
                if(isset($filesop[2]) && $filesop[2]!='') {
                    //$output = 2;
                }
			}
            echo $c.'<br>'; 
			if($c>=1) {
                //d($filesop); exit;
                if(strlen(trim($filesop[1]))>=1) {
                   
                    
                    if($output==1) {
                        $param_arr = explode(',',$filesop[9]);
                        //print_r($param_arr);
                        if(count($param_arr)>0) {
                            $src_val = $cmp_val = '';
                            $filesop[10] = $filesop[11] = $filesop[12] = '';
                            $src_search = 'utm_source';
                            $src_key   = key(array_filter($param_arr, function($x) use ($src_search){ return false!==stripos($x, $src_search); })); 
                            if($src_key!='') {
                                $src_val = get_string_between($param_arr[$src_key], '"', '"'); 
                                echo $filesop[10] = $src_val;
                            } 
    
                            $cmp_search = 'utm_campaign';
                            $cmp_key   = key(array_filter($param_arr, function($x) use ($cmp_search){ return false!==stripos($x, $cmp_search); })); 
                            if($cmp_key!='') {
                                $cmp_val = get_string_between($param_arr[$cmp_key], '"', '"');
                                $filesop[11] = $cmp_val;
                            } 
                            $med_search = 'utm_medium';
                            $med_key   = key(array_filter($param_arr, function($x) use ($med_search){ return false!==stripos($x, $med_search); })); 
                            if($med_key!='') {
                                $med_val = get_string_between($param_arr[$med_key], '"', '"');
                                $filesop[12] = $med_val;
                            } 
                        } else {
                            $filesop[10] = $filesop[11] = $filesop[12] = '';
                        }
                    } else {
                        //echo $src_val = trim($filesop[1]);
                        //echo $cmp_val = trim($filesop[2]);
                    }
                    


                    $csvData[] = $filesop;
                    
                    //$lastId = mysqli_insert_id($conn);
                }
                

                

                if($c==5) {
                   // d($csvData); exit;
                }
                
			}
			$j ++; 
			$c = $c + 1;
           
	}

   //d($csvData); exit;

   foreach($csvData as $k => $v) { 
        $cirSql = "INSERT INTO techno_leads (contact_id, funnel_id, page_id, fname, lname, email, phone, created_at, source, medium, campaign, created_tbl) VALUES ('".mysqli_real_escape_string($conn, $v[1])."', '".mysqli_real_escape_string($conn, $v[0])."', '".mysqli_real_escape_string($conn, '59016691')."', '".mysqli_real_escape_string($conn, $v[7])."', '".mysqli_real_escape_string($conn, $v[6])."', '".mysqli_real_escape_string($conn, $v[3])."', '".mysqli_real_escape_string($conn, $v[8])."', '".mysqli_real_escape_string($conn, substr($v[4], 0, 19))."', '".mysqli_real_escape_string($conn, $v[10])."', '".mysqli_real_escape_string($conn, $v[12])."', '".mysqli_real_escape_string($conn, $v[11])."',  now());";
        mysqli_query($conn, $cirSql) or die('Error: ' . mysqli_error($conn));
   } exit;
}

?>

<div class="x_title">
                 
                    <center><img src="/images/adrescue-2021.png" alt="VLookUp" style="height: 45px;"><h3>VLookUp</h3></center>
</div>
<div class="clearfix"></div>
<br />
                    
        <?php
        //include '../alert.php';
        if(isset($_POST['submit'])) {
            ?>

            <table id="datatable" class="table  table-striped table-bordered dataTable no-footer">
                <thead>
                    <tr>
                        <td>SNo</td>
                        <td>cont</td>
                        <td>funel</td>
                        <td>pg</td>
                        <td>fname</td>
                        <td>lname</td>
                        <td>email</td>
                        <td>phone</td>
                        <td>src</td>
                        <td>med</td>
                        <td>cam</td>
                        <td>created</td>
                        <td>created2</td>
                    </tr>
                </thead>
                <?php $s=1; 
                $tot_sale = $tot_lead = 0;
                foreach($csvData as $k => $v){ 
                       // $src_camp = $str_arr = explode ("<>", $v);
                        ?>
                        <tr>
                        <td><?php echo $s; ?></td>
                        <td><?php echo $v[0]; ?></td>
                        <td><?php echo $v[1]; ?></td>
                        
                        <td>59016691</td>
                        <td><?php echo $v[3]; ?></td>
                        <td><?php echo $v[4]; ?></td>
                        <td><?php echo $v[5]; ?></td>
                        <td><?php echo $v[6]; ?></td>
                        
                        <td><?php echo $v[8]; ?></td>
                        
                        <td><?php //echo $v[2]; ?></td>
                        <td><?php echo $v[9]; ?></td>
                        <td><?php echo $v[7]; ?></td>
                        
                        <td><?php //echo $v[2]; ?></td>
                </tr>
                
                <?php $s++; 
                    
                } 
                ?>
            </table>
            <br>
            <a href="" class="btn btn-danger btn-lg float-right">Upload Again</a>

<?php
       } else {
        ?>
        <div class="row col-8">

        
            <form id="demo-form2" data-parsley-validate class="form-horizontal form-label-left" enctype="multipart/form-data" method="post" action="">

            <div class="form-group">
            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Raw data: <span class="required">*</span>
            </label>                        
                                                           
                <input type="file" class="form-control has-feedback-left" name="file" required="required"  />
                
            </div>
            
            <br>
            <div class="ln_solid"></div>
            <div class="form-group">
                <button type="submit" name="submit" class="btn btn-success btn-lg">Submit</button> 
            </div>

            </form>
            </div>
        <?php
       }
            ?>
    <!-- Custom Theme Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

	<script type="text/javascript">
    var isMob = false;
    $(document).ready(function() {
    $('#datatable').DataTable( {
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copyHtml5', footer: true },
                { extend: 'excelHtml5', footer: true },
                { extend: 'csvHtml5', footer: true },
                { extend: 'pdfHtml5', footer: true },
                { extend: 'print', footer: true }
            ],
            "pageLength": 50,
            "lengthMenu": [ [20, 50, 100, -1], [20, 50, 100, "All"] ]
        } );
    } );
	</script>