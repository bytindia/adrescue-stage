<?php include 'header.php'; 


Auth();
$pgHeadline = 'Custom Audiences';
$pgID = 3;
$err =''; 

if(isset($_POST['submit'])){
	
	
	$_SESSION['suc'] = 'Successfully Updated!';	
	echo "<script>window.location = 'pages.php';</script>";
	exit();
}


include 'config.php';


include 'pagination.php';

$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
if ($page <= 0) $page = 1;

$per_page = 500; // Set how many records do you want to display per page.

$startpoint = ($page * $per_page) - $per_page;
$statement = " audiences WHERE uid='".$_SESSION['uid']."'";


?>
<style>
.br_t { border-top:1px solid #ccc; }
.br_l { border-left:1px solid #ccc; }
.br_r { border-right:1px solid #ccc; }
.br_bottom { border-bottom:1px solid #ccc; }
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
                    <?php if(isset($_SESSION['g_id']) || $_SESSION['g_id']!='') 	{ ?>
                    <ul class="nav navbar-right panel_toolbox">                     
                      <li> &nbsp;
                       <div class="btn-group  btn-group-sm">
                        	<a href="loading.php?pg=audiences-add.php"  class="btn btn-success btn-sm">Create Custom Audience</a>                
                      	</div>  
                      </li>                      
                    </ul>
                     <?php } ?>
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
						if(!isset($_SESSION['fb_id']) || $_SESSION['fb_id']=='') 
						{
						?>
							<div class="text-center">
                                <h4>
                                 <a href="loading.php?pg=fb-login.php">
                                      <img src="images/fb-login.png">
                                 </a>
                                 </h4>
                           </div>
						<?php
						} 
						else {
										$sqlRev2=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
										$tblIds = array();
										while($sqlROW2=mysqli_fetch_array($sqlRev2))
										{
											 $tblIds[] = $sqlROW2["tbl_id"];
										}
										if(count($tblIds)>0) { $tblIds=implode(",",$tblIds); } else { $tblIds=''; }
										
										$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." LIMIT {$startpoint} , {$per_page}");
										$i = (($page-1) * $per_page ) + 1;
										
								?>
                               
                                <table id="datatable" class="table table-hover table-striped table-bordered">
                                    <thead>
                                        
                                        <th>SNo</th>
                                        <th>Client Name</th>
                                        <th>Audience Name</th>
                                        <th>Edit</th>                                                  	
                                    </thead>
                                    <tbody>
                                    	<?php
										while($sqlROW=mysqli_fetch_array($sqlRev))
										{ 
										?>
                                        <tr>
                                        	
                                            <td><?php echo $i; ?></td>
                                        	<td><?php echo $sqlROW["client_name"]; ?></td></td>
                                            <td><?php echo $sqlROW["audience_name"]; ?></td>  
                                        	<td><?php echo $sqlROW["pg_cat"]; ?></td>                                                                           	
                                        </tr>  
                                        <?php $i++;
										} ?>                                      
                                    </tbody>
                                </table>
                                <br><br>
                                
								<div id="pagDiv"><?php echo pagination($statement,$per_page,$page,$url='?',''); ?></div>

					<?php	}	?>				
         
        				</div>
                </div>
              </div>
        </div>
        </div>
        <!-- /page content -->

<?php include 'footer.php'; ?>


    <script type="text/javascript">
		/*var nameArr = $('#selectIds').val().split(',');
		
        $(document).ready(function(){

            $('input[type="checkbox"]').click(function(){

                if($(this).prop("checked") == true){

					nameArr.push($(this).val());
					$('#selectIds').val(nameArr.join(","));
                    alert("Checkbox is checked."+$(this).val());

                }

                else if($(this).prop("checked") == false){
					for( var i = 0; i < nameArr.length; i++){ 
					   if ( nameArr[i] === $(this).val()) {
						 nameArr.splice(i, 1); 
					   }
					}
					$('#selectIds').val(nameArr.join(","));
                    alert("Checkbox is unchecked."+$(this).val());

                }

            });

        });
*/
</script>

