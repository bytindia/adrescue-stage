<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
echo 'Loading...';

include 'db.php';  // Include your database connection file


require_once 'google-sheets-api/vendor/autoload.php';
require_once 'google-sheets-api/class-db.php';
require_once 'google-sheets-api/config.php';
require_once 'google-sheets-api/create-sheet-tab.php';
include 'google-sheets-api/insert-row.php';

$query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$access_token = $row['access_token']; 

$sqlRev=mysqli_query($conn, "SELECT account_id,name FROM adAccounts WHERE uid='2' order by name asc");
$fbAccN = $gAccN =  array();
while($sqlROW=mysqli_fetch_array($sqlRev)) { $fbAccN[$sqlROW["account_id"]] = $sqlROW["name"]; }

function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
}


$uId = 2;
//$sheetName =  date('M Y', strtotime('first day of last month'));
$tbl_id = 2;
$sheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk';

$businessId = 866116510072687;




if(isset($_GET['month']) && $_GET['month'] != '') {
    $month = $_GET['month'];
    $monthInput = date('M Y', strtotime('first day of -'.$month.' months'));;
} else {
    $monthInput = date('M Y', strtotime('first day of -1 months'));;
}
    //create_spreadsheet_tab($uId, $tbl_id, $sheetId, $sheetName, $proj=array(), $repId='', $repMon='');


    $url = 'https://graph.facebook.com/'.$api_ver.'/me/businesses?access_token=' . $access_token;

    $requests = curl_get_file_contents($url);
    $fb_response = json_decode($requests,true); 

        
     // e.g., "May 2025"

    // Convert "May 2025" to start and end date
    $startDate = date('Y-m-01', strtotime($monthInput));        // e.g., 2025-05-01
    $endDate   = date('Y-m-t', strtotime($monthInput));         // e.g., 2025-05-31
    $monthShort = date('F', strtotime($monthInput));
    // Construct the Meta Graph API URL
    $url = "https://graph.facebook.com/$api_ver/$businessId/business_invoices?access_token=$access_token" .
           "&fields=ad_account_ids,amount,invoice_id,advertiser_name,amount_due," .
           "billing_period,download_uri,invoice_date&" .
           "start_date=$startDate&end_date=$endDate&limit=100";
    
    $requests = curl_get_file_contents($url);
    $fb_response = json_decode($requests,true); 
    $invoice_data = [];
	if(isset($fb_response['data']) && count($fb_response['data'])>0){
        $invoice_data = $fb_response['data'];
    }
    $invoice_sheet_data = [];
    //$invoice_sheet_data[] = array('-', '', '', '', '', '','', '', '', '', '', '-');
    $invoice_sheet_data[] = array($monthShort,  '', '','', '', '', '', '', '', '', '', '-');
    //$invoice_sheet_data[] = array('SNo', 'Advertiser', 'Invoice No.', 'Ad Account', 'Invoice Date', 'Taxable Value', 'GST-18%', 'Invoice Value', 'TDS', 'Payable Amount', 'Paid Date', 'Due Date');

if(count($invoice_data) > 0) {
  

    $total_taxable = $total_gst = $total_invoice = $total_tds = $total_payable = 0;
    $sno = 1;

    foreach ($invoice_data as $invoice) {
        // Get ad account name or fallback
        $ad_acc = isset($fbAccN[$invoice['ad_account_ids'][0]]) ? $fbAccN[$invoice['ad_account_ids'][0]] : $invoice['ad_account_ids'][0];

        // Ensure numeric computation
        //$raw_amount = preg_replace('/[^0-9]/', '', $invoice['amount_due']['amount_in_hundredths']); // remove any commas or invalid characters
        $raw_amount = preg_replace('/[^0-9]/', '', $invoice['amount']);
        $invoice_value = floatval($raw_amount) / 100;

        // Calculate components
        $taxable  = round($invoice_value / 1.18, 2);
        $gst      = round($invoice_value - $taxable, 2);
        $tds      = round($taxable * 0.02, 2);
        $payable  = round($invoice_value - $tds, 2);

        // Totals
        $total_taxable += $taxable;
        $total_gst     += $gst;
        $total_invoice += $invoice_value;
        $total_tds     += $tds;
        $total_payable += $payable;

        // Add to sheet
        $invoice_sheet_data[] = array(
            $sno++,
            //$invoice['advertiser_name'],
            $invoice['invoice_id'],
            $ad_acc,
            date('d M Y', strtotime($invoice['invoice_date'])),
            number_format($taxable, 2),
            number_format($gst, 2),
            number_format($invoice_value, 2),
            number_format($tds, 2),
            number_format($payable, 2),
            '', // Paid Date
            '', // Due Date
            '=HYPERLINK("' . $invoice['download_uri']. '", "Download")'
        );
    }

    // Add total row
    $invoice_sheet_data[] = array(
        '', 'Total',  '', '', 
        number_format($total_taxable, 2),
        number_format($total_gst, 2),
        number_format($total_invoice, 2),
        number_format($total_tds, 2),
        number_format($total_payable, 2),
        '', '', '-'
    );

}


    $sheetName = 'Meta Invoices 2025';
    append_to_sheet($uId, $tbl_id, $invoice_sheet_data, $sheetId, $sheetName);
   // d($invoice_sheet_data);
   echo '<br><br>Completed successfully'; 