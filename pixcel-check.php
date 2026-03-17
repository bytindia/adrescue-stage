<?php include 'header.php'; 

if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}

Auth();
$pgHeadline = 'Pixel check';
$pgID = 8;
$err =''; 

$txtMsg = $url = "";

if(isset($_POST['submit'])){
	
	if($_POST['url']!='') {
		
		
		//print_r($_POST);  exit;
        //if()
        $url = $_POST['url'];
        $contents = file_get_contents($url);
        $search1   = <<<EOF
        connect.facebook.net
        EOF;
        $search2   = <<<EOF
        https://www.googletagmanager.com/
        EOF;

        if (strpos($contents, $search1) === false) {
            $txtMsg .= 'Facebook: Pixel code not found!<br><br>'; 
        } else {
            $txtMsg .= 'Facebook: Pixel code found!<br><br>'; 
        }
        if (strpos($contents, $search2) === false) {
            $txtMsg .= 'Google: Pixel code not found!<br><br>'; 
        } else {
            $txtMsg .= 'Google: Pixel code found!<br><br>'; 
        }
       
            
		
		
	} 
}
?>
<style>


[data-role="dynamic-fields"] > .form-inline + .form-inline {
    margin-top: 0.5em;
}

[data-role="dynamic-fields"] > .form-inline [data-role="add"] {
    display: none;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="add"] {
    display: inline-block;
}

[data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"] {
    display: none;
}

.not-first [data-role="dynamic-fields"] > .form-inline:last-child [data-role="remove"] {
    display: inline-block;
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
                      <li>
                       
                          
                      </li>
                    </ul>
                   
                    <div class="clearfix"></div>
                  </div>
                  
                  <div class="x_content">
                  		<?php 
									$editData = array();
									$editData['email_notif'] =0;
									if(isset($_GET['id'])) {
										//echo "SELECT * FROM budget WHERE fb_id='".$_GET['id']."'"; 
                   // echo "SELECT client_name, acc_name, acc_id, email_ids, words_like, emails_to, webhook_url FROM checklist WHERE tbl_id='".$_GET['id']."'"; 
										 $sqlD=mysqli_query($conn, "SELECT client_name, acc_name, acc_id, budget,g_acc FROM checklist WHERE tbl_id='".$_GET['id']."'");
										while($Rdata=mysqli_fetch_array($sqlD)) {
											$editData = $Rdata;
										}
									}
                  //d($editData);
										//print_r($editData['words_like']);
										//$words_like = unserialize($editData['words_like']);
										//print_r($words_like);
										$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										$sqlRev2=mysqli_query($conn, "SELECT * FROM gaccounts WHERE uid='".$_SESSION['uid']."' order by name asc");
										
								?>
                                <form method="post" action="">
                               <div class="content">
                                    <div class="container-fluid">
                                      <div class="row">		
                                                <div class="col-md-1"></div>
                                                	
                                                <div class="col-md-8">     
                                                	<label>LP URL: </label>
                                                    <input type="text" name="url" class="form-control" value="<?php echo $url; ?>">                                                    	
                                                    <br />      
                                                   <?php
                                                   echo $txtMsg; 
                                                   ?>
                                                    <input type="submit" name="submit" value="Submit" class="btn btn-info">
                                                 
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
<script>
$(function() {
	//$('[data-role="dynamic-fields"] > .form-inline [data-role="add"]').click();
    // Remove button click
    $(document).on('click', '[data-role="dynamic-fields"] > .form-inline [data-role="remove"]', function(e) {
            e.preventDefault();
            $(this).closest('.form-inline').remove();
        }
    );
    // Add button click
    $(document).on('click',  '[data-role="dynamic-fields"] > .form-inline [data-role="add"]', function(e) {
            e.preventDefault();
            var container = $(this).closest('[data-role="dynamic-fields"]');
            new_field_group = container.children().filter('.form-inline:first-child').clone();
            new_field_group.find('input').each(function(){
                $(this).val('');
            });
            container.append(new_field_group);
        }
    );
});


$(document).ready(function () {
	$( ".cls_f" ).hide();  $( ".cls_g" ).hide(); 
	//alert($( "#fb_acc" ).val()); 
	if($( "#fb_acc" ).val()!='') { $( ".cls_f" ).show(); }
	if($( "#g_acc" ).val()!='') { $( ".cls_g" ).show(); }
	//alert($( "#rad_em2" ).val());
	var $radios = $('#rad_em2');
	var $radios3 = $('#rad_em3'); 
    if($radios.is(':checked') === true || $radio3s.is(':checked') === true) {
       $( ".cls_em" ).show(); 
    }
	<?php if(isset($editData['email_notif']) && ($editData['email_notif']==2 || $editData['email_notif']==1)) { ?> $( ".cls_em" ).show();  <?php } else { ?> $( ".cls_em" ).hide(); <?php } ?>
	//if($( "#fb_acc" ).val()!='') { $( "#cls_f" ).show(); }
});	

$( "#fb_acc" ).change(function() {  if($( "#fb_acc" ).val()=='') {   $( ".cls_f" ).hide();  } else {   $( ".cls_f" ).show();  } });
$( "#g_acc" ).change(function() {  if($( "#g_acc" ).val()=='') {   $( ".cls_g" ).hide();  } else {   $( ".cls_g" ).show();  } });
$('input[type=radio][name=email_notif]').change(function() {  if(this.value==0) { $( ".cls_em" ).hide(); } else {   $( ".cls_em" ).show();  } });

</script>