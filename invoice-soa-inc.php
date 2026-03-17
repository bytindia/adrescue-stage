<?php

$fileN_SOA = $fileN_SOA_PI = '';

if(!isset($sheetName) || $sheetName != '') {
    
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
        $fileN_SOA = 'SOA-'.$fileN. '.xlsx';

        // Ensure the download directory exists
        $downloadDir = $dirPath . 'download/';
        if (!file_exists($downloadDir)) {
            mkdir($downloadDir, 0775, true);
        }

        // Full path to save
        $fullPath = $downloadDir . $fileN_SOA;
        $objWriter->save($fullPath);
     } else {
        $fileN_SOA = '';
    }

    //pi_soa
    $raw_pi = read_sheet($uId, $sheetId, $sheetName.' - PI');
    if (isset($raw_pi['data'])) {
        $soa_sheet = $raw_pi['data']; // ✅ extract the actual rows

        foreach ($soa_sheet as &$soa_data) {
            $soa_data = array_slice(array_pad($soa_data, 14, ''), 0, 14);
        }
        unset($soa_data);

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('SOA - PI Report');

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
        $fileN_SOA_PI = 'SOA-PI-'.$fileN. '.xlsx';

        // Ensure the download directory exists
        $downloadDir = $dirPath . 'download/';
        if (!file_exists($downloadDir)) {
            mkdir($downloadDir, 0775, true);
        }

        // Full path to save
        $fullPath = $downloadDir . $fileN_SOA_PI;
        $objWriter->save($fullPath);
     } else {
        $fileN_SOA_PI = '';
    }
}