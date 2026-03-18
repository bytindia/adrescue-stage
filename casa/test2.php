<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
  <i class="fa fa-calendar"></i>&nbsp;
  <span></span> <i class="fa fa-caret-down"></i>
</div>


<script>$(function() {

var currentYear = moment().year(); // This Year

var currentYearStart = moment({
  years: currentYear,
  months: '0',
  date: '1'
}); // 1st Jan this year

var currentYearEnd = moment({
  years: currentYear,
  months: '11',
  date: '31'
}); // 31st Dec this year

var start = moment().subtract(29, 'days'); // Subtract 29 days from today

var end = moment(); // Today
alert(end);
function cb(start, end) {
  $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
}
var dateRange = {};
dateRange["Today"] = [moment(), moment()];
dateRange["Last 30 Days"] = [moment().subtract(29, 'days'), moment()];
dateRange["Year " + (currentYear - 1)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2017
dateRange["Year " + (currentYear - 2)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2016
dateRange["Year " + (currentYear - 3)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2015
dateRange["Year " + (currentYear - 4)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2014
$('#reportrange').daterangepicker({
  startDate: start,
  endDate: end,
  ranges: {
    
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                'Last 7 Days': [moment().subtract('days', 6), moment()],
                'Last 30 Days': [moment().subtract('days', 29), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
  }
}, cb);
cb(start, end);
});
</script>