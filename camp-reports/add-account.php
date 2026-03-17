<?php
session_start();
include '../db.php';
// Dummy credentials for login
if(!isset($_SESSION['log'])) {
    echo "<script>window.location = 'login.php';</script>";
	exit();
}
$user_id = $_SESSION['user_id'];
unset($_SESSION['err']);
if(isset($_POST['submit'])){
    //d($_POST); exit;
    $campaign_ids = serialize($_POST['campaigns']);
    $cust_conv_id ='';
    if(isset($_POST['cust_con_check']) && isset($_POST['cust_con']) && $_POST['cust_con']!='') {
        $cust_conv_id = $_POST['cust_con'];
    }
    $cirSql = "INSERT INTO camp_report (acc_id, user_id, acc_name, obj, camp_ids, cust_conv, created,updated) VALUES ('".$_POST['category']."', '".$user_id."', '".$_POST['client']."', '".$_POST['obj']."', '".mysqli_real_escape_string($conn, $campaign_ids)."', '".mysqli_real_escape_string($conn, $cust_conv_id)."', now(), now());"; 
             mysqli_query($conn, $cirSql) or die(mysqli_error($conn));
			 $lastId = mysqli_insert_id($conn);

        $_SESSION['suc'] = 'Successfully Updated!';	
		echo "<script>window.location = 'add-account.php';</script>";
		exit();
}
$sqlROWs = array();
$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='2' order by name asc");
while($sqlROW=mysqli_fetch_array($sqlRev)) { $sqlROWs[] = $sqlROW; }
//d($sqlROWs);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">

    <title>AdRescue - Rescue Your Campaigns</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/navbar-fixed/">

    <!-- Bootstrap core CSS -->
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Custom styles for this template -->
    <link href="https://getbootstrap.com/docs/4.0/examples/navbar-fixed/navbar-top-fixed.css" rel="stylesheet">
  </head>
  <style>
        .container {
            margin-top: 20px;
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
  <body>

  <main role="main" class="container">
<?php include 'menu.php'; ?>
<a href="loading.php?pg=accounts.php" class="btn btn-sm btn-warning float-right">View Your Accounts</a>
    <h3>Add Account</h3>
    <?php if(isset($_SESSION['suc'])) { echo $_SESSION['suc']; unset($_SESSION['suc']); } ?>
    <form method="post" action="">
    <!-- First Dropdown to Select Category -->
    <label for="category">Client Name:</label>
    <input type="text"  name="client" class="form-control" required>
    <br>
    <label for="category">Select Ad Account:</label>
    <select id="category" class="form-control" name="category" required>
        <option value="">-- Select Ad Account --</option>
        <?php foreach($sqlROWs as $sqlROW){  ?>
					<option value="<?php echo $sqlROW["account_id"]; ?>" <?php if(isset($fb_val) && $fb_val==$sqlROW["account_id"]) { echo "selected='selected'"; } ?> ><?php echo $sqlROW["name"].' ('.$sqlROW["account_id"].')'; ?></option>
		<?php } ?>
    </select>
    <br>
    <label for="category">Select Objective:</label>
    <select id="obj" class="form-control" name="obj" required>
        <option value="LEAD_GENERATION">Lead Generation</option>
        <option value="CONVERSIONS">Website Conversion</option>
        <option value="OUTCOME_SALES">Sale</option>
    </select>
    <br>
    <!-- Multiselect Dropdown (Initially Hidden) -->
    
    <label for="example" id="multiselectLabel" class="mt-3" style="display:none;">Select Campaigns:</label>
    <select name="campaigns[]" id="example" multiple="multiple" class="form-control" style="display:none;" required>
        <!-- Options will be dynamically loaded here -->
    </select>
    
    <div class="col-md-2 form-inline mb-3">
    <br><br>
                                <div class="form-group input-group">
                                        <label> Cust. Conversion 
                                        <input type="checkbox"  class="form-control cust_con_check ml-3 mr-3" name="cust_con_check" value="0" /></label>
                                        <span class="cust_con_sel_0 cust-conv-sel"></span>
                                </div>
                                <div class="form-group input-group"></div>                 
                            </div>
    <br><br>
    <button type="submit" name="submit" class="btn btn-primary">Submit</button>
    </form>
        </main>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Select2 JS for Multiselect -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // When the category is selected
    $('#category').change(function() {
        var selectedCategory = $(this).val();

        // Only proceed if a valid category is selected
        if (selectedCategory) {
            // Show the multiselect dropdown and label
            $('#example').hide();
            $('#example').empty();
            $('#multiselectLabel').html('Fetching Campaigns...');

            // Make an Ajax request to fetch additional options based on category
            $.ajax({
                url: 'get_campaigns_data.php', // PHP endpoint to fetch options
                type: 'GET',
                dataType: 'json',
                data: { category: selectedCategory },
                success: function(data) {
                    // Clear any existing options
                    $('#multiselectLabel').html('Select Campaigns:');
                    $('#example').show();
                    $('#multiselectLabel').show();
                    $('#example').empty();
                    // Append new options based on the returned data
                    $.each(data, function(index, item) {
                        $('#example').append('<option value="' + item.value + '">' + item.text + '</option>');
                    });

                    // Initialize the Select2 multiselect dropdown
                    $('#example').select2({
                        placeholder: 'Select options',
                        allowClear: true,
                        width: '100%'
                    });
                },
                error: function(xhr, status, error) {
                    console.log('Error:', error);
                }
            });
        } else {
            // Hide the multiselect if no category is selected
            $('#example').hide();
            $('#multiselectLabel').hide();
        }
    });
    $('body').on('change', '.cust_con_check',function (e) {
        //$('.cust_con_check').change(function() {
        var rule_id = 0;
        var act = $("select[name=category]").val();
        //alert(act);
        if(act!='') {
            if($(this).is(":checked")) {
                //$('.cust_con_sel_'+rule_id).show();
                //$('.cust_con_sel_'+rule_id).html('<img src="loading-bar2.gif" />');
                $.ajax({
                    url: "cust-conv-ajax.php",
                    type: "post",
                    data: {act: act, rule_id: rule_id},
                    success: function (response) {
                        //alert(act);
                        $('.cust_con_sel_'+rule_id).show();
                        $('.cust_con_sel_'+rule_id).html(response);
                    
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('.cust_con_sel_'+rule_id).prop('checked', false);
                        alert(textStatus, errorThrown);
                    }
                });
            } else {
                $('.cust_con_sel_'+rule_id).hide();
            }    
        } else {
            alert('Select Ad account!');
            $(this).prop('checked', false);
            $('.cust_con_sel_'+rule_id).hide();
        }
    });
});
</script>

</body>
</html>
