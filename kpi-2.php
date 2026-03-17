
<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'WhatsApp Message';
$pgID = 8;
$err =''; 
$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 50; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " kpi WHERE uid='".$_SESSION['uid']."' AND delete_status=0 ";

$acc_type = array(1=>'Real Estate', 2=>'Coaching', 3=>'Education', 4=>'Ecommerce', 5=>'Others');

                                        $label = array(
                                            1 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'SV',	'CSV', 'Sales'),
                                            2 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Attendees', 'Sale', 'CPS','ROAS'),
                                            3 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'Applications',	'Admission'),
                                            4 => array('Client', 'Spend', 'Budget', 'Sale', 'CPS', 'ATC',	'CATC',	'Purchase Value','ROAS'),
                                            5 => array('Client', 'Spend', 'Budget', 'Lead', 'CPL', 'Qualified Leads', 'Q. Leads in %', 'SV',	'CSV', 'Sales')
                                        );
?>
<style>
.blue { cursor:pointer; } 
thead {color:green; background:#fff; }
tfoot {color:red;}
.even { background:#fff; }
.blue_txt { color: blue; font-weight:bold; }
input[type="text"] {
    width: 100px;
}
</style>
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
                 <?php 
                      $sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." order by acc_cat asc");
                      $i = (($page-1) * $per_page ) + 1;
                      $totRows = mysqli_num_rows($sqlRev);
                      //$grandRecived = $grandSpent = $grandBalance = 0;
                      $acc_cat = 'no';
                      while($sqlROW=mysqli_fetch_array($sqlRev))
                      {
                            //d($sqlROW);
                            $metrics = unserialize($sqlROW['metrics']);
                            //
                            //d($metrics);
                            //echo $acc_cat.'-'.$sqlROW["acc_cat"];
                            if($acc_cat!='no' && $acc_cat != $sqlROW["acc_cat"]) {
                                echo '</tbody></table> '; echo 'tbl en: '.$acc_cat.'_'.$sqlROW["acc_cat"];
                            }
                            
                            if($acc_cat != $sqlROW["acc_cat"]) { //echo 33; 
                                echo 'tbl st: '.$acc_cat.'_'.$sqlROW["acc_cat"];
                                echo '<h4 class="x_title" style="text-align: center;">'.$acc_type[$sqlROW['acc_cat']].'</h4> <div class="clearfix"></div>';
                                echo '<table class="table table-hover table-striped table-bordered"><thead>';
                                foreach($label[$sqlROW['acc_cat']] as $k => $v){ 
                                echo '<th>'.$v.'</th>';
                                }
                                echo '<th>Edit</th>';
                                echo '</thead><tbody>';
                            }
                            
                            echo '<tr data-id="'.$sqlROW['tbl_id'].'"><td data-field="name">'.$acc_cat.'_'.$sqlROW["acc_cat"].' <a href="loading.php?pg=kpi-add.php?id='.$sqlROW["tbl_id"].'" style="float:right;"><i class="fa fa-pencil" data-html="true" data-toggle="tooltip" data-original-title="Edit"></i></a></td>';
                            echo '<td data-field="spend">'.$sqlROW['spend'].' </td>';
                            $in=1;
                            foreach($metrics as $k => $v){ 
                            echo '<td data-field="metrics_'.$in.'">'.$v.'</td>';
                            $in++;
                            }
                            echo '<td><a class="btn btn-md edit" title="Edit"><i class="fa fa-pencil"></i></a></td>';
                            echo '</tr>';

                            
                           // echo $acc_cat.'_'.$sqlROW["acc_cat"];
                            //echo $acc_cat.'_'.$sqlROW["acc_cat"]; 
                            $acc_cat = $sqlROW["acc_cat"];
                      }

								     ?>
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>
<script src='https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.7/js/intlTelInput.js'></script>
<script>
  

        $("#phone").intlTelInput({
          initialCountry: "auto",
          separateDialCode: true,
            preferredCountries:["in"],
            hiddenInput: "full",
          geoIpLookup: function(callback) {
              
            $.get('https://ipinfo.io', function() {}, "jsonp").always(function(resp) {
              var countryCode = (resp && resp.country) ? resp.country : "";
              callback(countryCode);
            });
          },
          utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.6/js/utils.js" // just for formatting/placeholders etc
        });

  </script>  
<script>
  
$(document).ready(function () {
    var input = $("#phone");
    input.intlTelInput();

    input.on("countrychange", function() {
        $("#countryCode").val($("#phone").intlTelInput("getSelectedCountryData").dialCode);
    });
});	


</script>
<script>
  $(document).ready(function() {
    
        $('[data-toggle="tooltip"]').tooltip()
        $(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');  
  });
</script>