<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$tbl_id = $_GET['id'];
include '/home/digitalb2k/stage.adrescue.in/db.php';
$dirPath = '/home/digitalb2k/stage.adrescue.in/';

require_once $dirPath.'google-sheets-api/vendor/autoload.php';
require_once $dirPath.'google-sheets-api/class-db.php';
require_once $dirPath.'google-sheets-api/config.php';
require_once $dirPath.'google-sheets-api/read-sheet.php';
require($root_path.'PHPExcel-1.8/Classes/PHPExcel.php');
$uId=2; 
$tbl_id2=2;  


$fileN_SOA = '';
$sheetName = isset($_GET['sheet']) ? $_GET['sheet'] : (isset($_GET['tab']) ? $_GET['tab'] : '');

if($sheetName !== '') {
    
    $sheetId = '12shJ43Oiz56wmJOEk1lN0upd-9ySMbp03tOFoltCOYk'; // Default sheet ID
    //if($pi_soa == 'yes') { $sheetName = $sheetName.' - PI'; } 

    $raw = read_sheet($uId, $sheetId, $sheetName);
    if (isset($raw['data'])) {
        $soa_sheet = $raw['data']; // ✅ extract the actual rows

        foreach ($soa_sheet as &$soa_data) {
            $soa_data = array_slice(array_pad($soa_data, 14, ''), 0, 14);
        }
        unset($soa_data);

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('SOA Report');

        // Write data
        $rowNum = 1;
        foreach ($soa_sheet as $row) {
            $col = 0;
            foreach ($row as $cell) {
                $sheet->setCellValueByColumnAndRow($col, $rowNum, $cell);
                $col++;
            }
            $rowNum++;
        }

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

        // Sanitize and generate filename
        $fileN = preg_replace('/[^a-zA-Z0-9]/', '-', $sheetName);
        $fileN_SOA = 'SOA-'.$fileN. '_'.date('d-m-Y').'.xlsx';

        // Stream directly to browser without saving on server
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileN_SOA . '"');
        header('Cache-Control: max-age=0');
        if (ob_get_length()) { ob_end_clean(); }
        $objWriter->save('php://output');
        exit;
     } else {
        $fileN_SOA = '';
     }

   
}