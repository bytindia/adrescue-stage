<div class="show-notification" id="notifyBox">
    <span id="notifyText"></span>
    <span class="close-notification" onclick="closeNotification()">×</span>
</div>
<?php  if(!isset($_GET['menu'])) { ?>
<!-- footer content -->
<div style="text-align: center; padding: 5px 0; background: #2a3f54;">
    <div style="font-size:12px; color:#f5e4e4; letter-spacing:0.5px;">
        &copy; <?php echo date('Y'); ?> BYT Digital. All rights reserved.
    </div>
</div>
<?php } ?>
<!-- /footer content -->
      </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
        const h2 = document.querySelector('.x_title h2');
        const defaultTitle = "adRescue | Rescue Your Campaigns"; // ← change as needed
        const siteSuffix = " | adRescue"; 

        if (h2 && h2.textContent.trim() !== '') {
            document.title = h2.textContent.trim() + siteSuffix;
        } else {
            document.title = defaultTitle;
        }
        });

        // Notification checking script
     let lastNotificationId = 0;

function checkNotification(){

fetch("show_notification.php?last_id="+lastNotificationId)
.then(res => res.json())
.then(data => {

if(data.status === "init"){
    lastNotificationId = data.id;
    return;
}

if(data.status === "new"){

    lastNotificationId = data.id;

    document.getElementById("notifyText").innerText =
    data.client_name + " - " + data.notify_msg;

    let box = document.getElementById("notifyBox");

    box.classList.add("show");

    setTimeout(()=>{
        box.classList.remove("show");
    },8000);

}

});

}

setInterval(checkNotification,15000);
checkNotification();
        function closeNotification(){
document.getElementById("notifyBox").classList.remove("show");
}
    </script>
    <link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
   <!-- jQuery -->
    <script src="vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="vendors/nprogress/nprogress.js"></script>
    <!-- Chart.js -->
    <script src="vendors/Chart.js/dist/Chart.min.js"></script>
    <!-- gauge.js -->
    <script src="vendors/gauge.js/dist/gauge.min.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="vendors/iCheck/icheck.min.js"></script>
    <!-- Skycons -->
    <script src="vendors/skycons/skycons.js"></script>
    <!-- Flot -->
    <script src="vendors/Flot/jquery.flot.js"></script>
    <script src="vendors/Flot/jquery.flot.pie.js"></script>
    <script src="vendors/Flot/jquery.flot.time.js"></script>
    <script src="vendors/Flot/jquery.flot.stack.js"></script>
    <script src="vendors/Flot/jquery.flot.resize.js"></script>
    <!-- Flot plugins -->
    <script src="vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
    <script src="vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
    <script src="vendors/flot.curvedlines/curvedLines.js"></script>
    <!-- DateJS -->
    <script src="vendors/DateJS/build/date.js"></script>
    <!-- JQVMap -->
    <script src="vendors/jqvmap/dist/jquery.vmap.js"></script>
    <script src="vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
    <script src="vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	<script src="vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="build/js/custom.js"></script>
    <link href="//cdn.datatables.net/buttons/1.5.6/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.3.1/js/dataTables.buttons.min.js"></script> 
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.3.1/js/buttons.html5.min.js"></script>
	<script type="text/javascript">
	var isMob = false;
	if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
	 isMob = true;
	} 
	/*
	$('#datatable').dataTable({
    "pageLength": 50,
    "searching": true,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    dom: 'Bfrtip',
    buttons: [
        {
            extend: 'excelHtml5',
            text: 'Download Excel', 
            title: 'AdRescue Budget',
            exportOptions: {
                // Export only visible columns, excluding those with the 'no-export' class
                columns: ':not(.no-export)',
            },
            customize: function (xlsx) {
                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                // Find and remove the dropdown content (the <div class="dropdown">)
                $(sheet).find('td').each(function() {
                    // If this <td> contains a div with the class 'dropdown-to-hide', hide it
                    if ($(this).find('.dropdown-to-hide').length > 0) {
                        $(this).html(''); // Clear the content of the td (hide the dropdown)
                    }
                });
            }
        }
    ],
    <?php if (isset($budget_pg)) { ?> 
    order: [[2, 'asc']],
    aoColumnDefs: [
        {
            bSortable: false,
            aTargets: [0]  // Disable sorting for the first column
        }
    ]
    <?php } ?>
});
	*/

  $('#example').dataTable( {
        'dom': 'lBfrtip',
        'buttons': [
        'excel', 'csv', 'pdf', 'print'
        ],
        "pageLength": 20,
        "lengthMenu": [ [20, 50, 100, -1], [20, 50, 100, "All"] ]
      } );
      
	$('#datatable').on( 'page.dt', function () {
		$('html, body').animate({
			scrollTop: 0
		}, 300);
	} );
		
var startDate, startDate2;
var endDate, endDate2;

var d1 = '<?php echo $_SESSION['stDt']; ?>';
var d2 = '<?php echo $_SESSION['enDt']; ?>';

<?php if(isset($_SESSION['stDt2'])) { ?>
var d12 = '<?php echo $_SESSION['stDt2']; ?>';
var d22 = '<?php echo $_SESSION['enDt2']; ?>';
<?php } ?>

$(document).ready(function() {
    setTimeout(()=>$('.alert-success, .alert-danger').fadeOut('slow'),5000);
	$('[data-tt="tt"]').tooltip();  
	if(isMob==false) { $('#menu_toggle').click(); }
	
    $('#reportrange_right').daterangepicker(
       {
          startDate: moment(d1).toDate(),
          endDate: moment(d2).toDate(),
         
          dateLimit: { days: 180 },
          showDropdowns: true,
          showWeekNumbers: true,
          timePicker: false,
          timePickerIncrement: 1,
          timePicker12Hour: true,
          ranges: {
             'Today': [moment(), moment()],
             'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
             'Last 7 Days': [moment().subtract('days', 6), moment()],
             'Last 30 Days': [moment().subtract('days', 29), moment()],
             'This Month': [moment().startOf('month'), moment().endOf('month')],
             'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
          },
          opens: 'left',
          buttonClasses: ['btn btn-default'],
          applyClass: 'btn-small btn-primary',
          cancelClass: 'btn-small',
          format: 'DD/MM/YYYY',
          separator: ' to ',
          locale: {
              applyLabel: 'Submit',
              fromLabel: 'From',
              toLabel: 'To',
              customRangeLabel: 'Custom Range',
              daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
              monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
              firstDay: 1
          }
       },
       function(start, end) {
        console.log("Callback has been called!");
        $('#reportrange_right span').html(start.format('D MMMM YYYY') + ' - ' + end.format('D MMMM YYYY'));
         startDate = start;
         endDate = end;   
         <?php if(isset($datePick)) { ?>
          window.location = 'ads-report-weekly.php?st='+start.format('MM/DD/Y')+'&en='+end.format('MM/DD/Y');
         //alert(start.format('D MMMM YYYY') + ' - ' + end.format('D MMMM YYYY'));
         <?php } ?>
        $('#stDt').val(moment(startDate).format('MM/DD/Y'));
        $('#enDt').val(moment(endDate).format('MM/DD/Y'));

       }
    );
	$('#reportrange_right span').html(moment(d1).format('D MMMM YYYY') + ' - ' + moment(d2).format('D MMMM YYYY'));
	
	<?php if(isset($_SESSION['stDt2'])) { ?>
	$('#reportrange_right1').daterangepicker(
       {
          startDate: moment(d12).toDate(),
          endDate: moment(d22).toDate(),
         
          dateLimit: { days: 180 },
          showDropdowns: true,
          showWeekNumbers: true,
          timePicker: false,
          timePickerIncrement: 1,
          timePicker12Hour: true,
          ranges: {
             'Today': [moment(), moment()],
             'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
             'Last 7 Days': [moment().subtract('days', 6), moment()],
             'Last 30 Days': [moment().subtract('days', 29), moment()],
             'This Month': [moment().startOf('month'), moment().endOf('month')],
             'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
          },
          opens: 'left',
          buttonClasses: ['btn btn-default'],
          applyClass: 'btn-small btn-primary',
          cancelClass: 'btn-small',
          format: 'DD/MM/YYYY',
          separator: ' to ',
          locale: {
              applyLabel: 'Submit',
              fromLabel: 'From',
              toLabel: 'To',
              customRangeLabel: 'Custom Range',
              daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
              monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
              firstDay: 1
          }
       },
       function(start2, end2) {
        console.log("Callback has been called!");
        $('#reportrange_right1 span').html(start2.format('D MMMM YYYY') + ' - ' + end2.format('D MMMM YYYY'));
          startDate = start2;
          endDate = end2;   
		  $('#stDt2').val(moment(startDate).format('MM/DD/Y'));
		  $('#enDt2').val(moment(endDate).format('MM/DD/Y'));

       }
    );
    //Set the initial state of the picker label
	//alert(moment());
    
	$('#reportrange_right1 span').html(moment(d12).format('D MMMM YYYY') + ' - ' + moment(d22).format('D MMMM YYYY'));
	<?php } ?>
	
    $('#saveBtn').click(function(){
        console.log(startDate.format('D MMMM YYYY') + ' - ' + endDate.format('D MMMM YYYY'));
    });

 });
</script> 
  </body>
</html>
