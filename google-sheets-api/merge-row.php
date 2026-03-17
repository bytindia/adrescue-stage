<?php

function getSheetIdByName($service, $spreadsheetId, $sheetTabName) {
    try {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        foreach ($sheets as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetTabName) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        throw new Exception("Sheet tab '$sheetTabName' not found.");
    } catch (Exception $e) {
        echo 'Error fetching Sheet ID: ' . $e->getMessage();
        return false;
    }
}

function hexToRgb($hexColor) {
    $hexColor = ltrim($hexColor, '#');
    if (strlen($hexColor) == 3) {
        $r = hexdec(str_repeat(substr($hexColor, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hexColor, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hexColor, 2, 1), 2));
    } else {
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
    }
    return ['red' => $r / 255, 'green' => $g / 255, 'blue' => $b / 255];
}


function merge_rows_by_tab($uId, $spreadsheetId, $sheetTabName, $stRow, $enRow, $startCol, $endCol, $bgColorCode = null, $cellText = '') {
    $client = new Google_Client();
    $db = new DB();

    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = [
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
    ];

    $client->setAccessToken($accessToken);
    $service = new Google_Service_Sheets($client);

    // Get Sheet ID
    $sheetId = getSheetIdByName($service, $spreadsheetId, $sheetTabName);
    if (!$sheetId) {
        return false; // Failed to get Sheet ID
    }

    // Grid Range
    $range = new Google_Service_Sheets_GridRange();
    $range->setSheetId($sheetId);
    $range->setStartRowIndex($stRow);
    $range->setEndRowIndex($enRow);
    $range->setStartColumnIndex($startCol);
    $range->setEndColumnIndex($endCol);

    // Merge Cells Request
    $mergeRequest = new Google_Service_Sheets_Request([
        'mergeCells' => [
            'mergeType' => 'MERGE_ROWS',
            'range' => $range
        ]
    ]);

    // Cell Formatting
    $cellFormat = new Google_Service_Sheets_CellFormat();
    $cellFormat->setHorizontalAlignment('CENTER');

    // Handle Dynamic Background Color
    if (!empty($bgColorCode)) {
        $bgColor = new Google_Service_Sheets_Color();

        // If hex string passed
        if (is_string($bgColorCode)) {
            $rgb = hexToRgb($bgColorCode);
            $bgColor->setRed($rgb['red']);
            $bgColor->setGreen($rgb['green']);
            $bgColor->setBlue($rgb['blue']);
        }
        // If RGB array passed
        else if (is_array($bgColorCode) && isset($bgColorCode['red'], $bgColorCode['green'], $bgColorCode['blue'])) {
            $bgColor->setRed($bgColorCode['red']);
            $bgColor->setGreen($bgColorCode['green']);
            $bgColor->setBlue($bgColorCode['blue']);
        }

        $bgColor->setAlpha(1.0);
        $cellFormat->setBackgroundColor($bgColor);
    }

    // Cell Data (Format + Value)
    $cellData = new Google_Service_Sheets_CellData();
    $cellData->setUserEnteredFormat($cellFormat);

    if (!empty($cellText)) {
        $cellData->setUserEnteredValue(new Google_Service_Sheets_ExtendedValue([
            'stringValue' => $cellText
        ]));
    }

    $rowData = new Google_Service_Sheets_RowData();
    $rowData->setValues([$cellData]);

    // Update Cells Request
    $updateCellsRequest = new Google_Service_Sheets_Request([
        'updateCells' => [
            'range' => $range,
            'rows' => [$rowData],
            'fields' => (!empty($bgColorCode)) ? 'userEnteredFormat.horizontalAlignment,userEnteredFormat.backgroundColor,userEnteredValue' : 'userEnteredFormat.horizontalAlignment,userEnteredValue'
        ]
    ]);

    // Batch Update Request
    $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
        'requests' => [$mergeRequest, $updateCellsRequest]
    ]);

    // Execute
    try {
        $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        return $response;
    } catch (Exception $e) {
        echo 'Error merging rows: ' . $e->getMessage();
        return false;
    }
}



function clear_and_format_merged_columns($uId, $spreadsheetId, $sheetTabName, $stRow, $enRow, $startCol, $endCol, $clearFormatting = true, $clearMerge = true, $clearValues = true) {

    $client = new Google_Client();
    $db = new DB();

    $arr_token = (array) $db->get_access_token($uId);
    $accessToken = array(
        'access_token' => $arr_token['access_token'],
        'expires_in' => $arr_token['expires_in'],
    );

    $client->setAccessToken($accessToken);
    $service = new Google_Service_Sheets($client);

    // Step 1: Get Sheet ID
    $sheetId = getSheetIdByName($service, $spreadsheetId, $sheetTabName);
    if (!$sheetId) {
        return false;
    }

    // Step 2: Define Range
    $range = new Google_Service_Sheets_GridRange();
    $range->setSheetId($sheetId);
    $range->setStartRowIndex($stRow);
    $range->setEndRowIndex($enRow);
    $range->setStartColumnIndex($startCol);
    $range->setEndColumnIndex($endCol);

    $requests = [];

    // Step 3: Add Unmerge Request
    if ($clearMerge) {
        $unmergeRequest = new Google_Service_Sheets_Request([
            'unmergeCells' => [
                'range' => $range
            ]
        ]);
        $requests[] = $unmergeRequest;
    }

    // Step 4: Clear Formatting & Values Request
    if ($clearFormatting || $clearValues) {
        $fields = '';
        if ($clearFormatting) {
            $fields .= 'userEnteredFormat';
        }
        if ($clearValues) {
            if ($fields !== '') {
                $fields .= ',';
            }
            $fields .= 'userEnteredValue';
        }

        $clearFormatRequest = new Google_Service_Sheets_Request([
            'repeatCell' => [
                'range' => $range,
                'cell' => new Google_Service_Sheets_CellData(), // Empty means clear
                'fields' => $fields
            ]
        ]);
        $requests[] = $clearFormatRequest;
    }
    // ⭐️ Step 5: Add Alignment Request
    // Step 2: Define Range
    $range = new Google_Service_Sheets_GridRange();
    $range->setSheetId($sheetId);
    $range->setStartRowIndex(3);
    $range->setEndRowIndex(18);
    $range->setStartColumnIndex(1);
    $range->setEndColumnIndex(25);

    $alignmentRequest = new Google_Service_Sheets_Request([
        'repeatCell' => [
            'range' => $range,
            'cell' => [
                'userEnteredFormat' => [
                    'horizontalAlignment' => 'RIGHT', // Options: LEFT, CENTER, RIGHT
                    'verticalAlignment' => 'MIDDLE'    // Options: TOP, MIDDLE, BOTTOM
                ]
            ],
            'fields' => 'userEnteredFormat(horizontalAlignment,verticalAlignment)'
        ]
    ]);
    $requests[] = $alignmentRequest;
    // Step 5: Batch Update
    $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
        'requests' => $requests
    ]);

    try {
        $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        return $response;
    } catch (Exception $e) {
        echo 'Error clearing formatting/unmerging/values: ' . $e->getMessage();
        return false;
    }
}

