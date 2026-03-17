<?php
 // INCLUDE THE phpToPDF.php FILE
require("phpToPDF.php"); 
date_default_timezone_set('Asia/Kolkata');
require '/home/digitalb2k/stage.adrescue.in/email/vendor/autoload.php';
include '/home/digitalb2k/stage.adrescue.in/db.php';
include '/home/digitalb2k/stage.adrescue.in/email/config.php';

$sqlRev = mysqli_query($conn, "SELECT * FROM audit_reports where tbl_id='".$_GET['id']."'");
$ans = array();							
while($sqlROW=mysqli_fetch_array($sqlRev))
{ 
	$ans  = $sqlROW;
}
//d($ans);// exit;

// SET YOUR PDF OPTIONS
// FOR ALL AVAILABLE OPTIONS, VISIT HERE:  http://phptopdf.com/documentation/
$pdf_options = array(
  "source_type" => 'url',
  "source" => 'https://adsninja.bytsocial.com/audit/audit-pdf.php?tbl_id='.$ans['tbl_id'],
  "action" => 'save',
  "save_directory" => '/home/digitalb2k/stage.adrescue.in/audit/pdf-files/',
  "file_name" => $ans['audit_name'].'.pdf');

// CALL THE phptopdf FUNCTION WITH THE OPTIONS SET ABOVE
//phptopdf($pdf_options);

// OPTIONAL - PUT A LINK TO DOWNLOAD THE PDF YOU JUST CREATED
//echo ("<a href='url_google.pdf'>Download Your PDF</a>");


//SENDING email
$pdf_path = '/home/digitalb2k/stage.adrescue.in/audit/pdf-files/'.$ans['audit_name'].'.pdf';
$emailIds = $ans['email_ids'];
$clientN = $ans['audit_name'];
include '/home/digitalb2k/stage.adrescue.in/email/mail-audit.php'; 

echo "<script>window.location = 'index.php';</script>";
exit();
?>