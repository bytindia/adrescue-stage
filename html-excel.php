<?php session_start(); 
//echo '<span id="msg">Preparing report... Please wait!!</span>';
date_default_timezone_set('Asia/Kolkata');
require __DIR__ . '/email/vendor/autoload.php';

include 'db.php';
include 'email/config.php';

setlocale(LC_MONETARY, 'en_IN');

require('PHPExcel-1.8/Classes/PHPExcel.php');
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);

$filename = "DownloadReport.xls";
$table    = '<table><tr><td>Hi 1</td><td>Hi 2</td></tr></table>';

// save $table inside temporary file that will be deleted later
$tmpfile = tempnam(sys_get_temp_dir(), 'html');
file_put_contents($tmpfile, $table);

// insert $table into $objPHPExcel's Active Sheet through $excelHTMLReader
$objPHPExcel     = new PHPExcel();
$excelHTMLReader = PHPExcel_IOFactory::createReader('HTML');
$excelHTMLReader->loadIntoExisting($tmpfile, $objPHPExcel);
$objPHPExcel->getActiveSheet()->setTitle('any name you want'); // Change sheet's title if you want

unlink($tmpfile); // delete temporary file because it isn't needed anymore

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // header for .xlxs file
header('Content-Disposition: attachment;filename='.$filename); // specify the download file name
header('Cache-Control: max-age=0');

// Creates a writer to output the $objPHPExcel's content
$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$writer->save('php://output');
exit;