
<script type="text/javascript">
$(window).load(function() {
    $('.loader').hide();
});
$(function() {

    var start_t = moment().subtract(0, 'days');
    var start_y = moment().subtract(0, 'days');
    var start, end; 
    
        var start ='<?php echo $stDt; ?>';
        var end ='<?php echo $enDt; ?>';
    
    function cb(start, end) {
       // alert(end);
        if(end!='Invalid date') {
            $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        // alert(start.format('DD/MM/YYYY')+', '+start_t.format('DD/MM/YYYY'));
            if((start_t.format('DD/MM/YYYY')!=start.format('DD/MM/YYYY') && end.format('DD/MM/YYYY')!=start_t.format('DD/MM/YYYY')) || (start_y.format('DD/MM/YYYY')!=start.format('DD/MM/YYYY') && end!=start_y.format('DD/MM/YYYY')))
            {
                window.location = '?st='+start.format('DD/MM/YYYY')+'&en='+ end.format('DD/MM/YYYY');
            } else {
                <?php if(isset($overview)) { ?>
                    window.location = '?st='+start.format('DD/MM/YYYY')+'&en='+ end.format('DD/MM/YYYY');
                <?php } else { ?>
                window.location = '?';
                <?php } ?>
            }
        }
        <?php if(!isset($_SESSION['st'])) { ?>
       // window.location = '?st='+start.format('DD/MM/YYYY')+'&en='+ end.format('DD/MM/YYYY');
        <?php } ?>
    }
    
    $('#reportrange').daterangepicker({
          startDate: moment(start, 'DD/MM/YYYY').toDate(),
          endDate: moment(end, 'DD/MM/YYYY').toDate(),
          format: 'DD/MM/YYYY',
          ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);
    //$('#reportrange span').html(moment(d1).format('DD/MM/YYYY') + ' - ' + moment(d2).format('DD/MM/YYYY'));
    cb(start, end, start_t, start_y);
});
</script>
</body>