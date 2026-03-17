<?php

$fileid = "1E4g6R0NTx_9AKO3oJn3Uqisx9c8d8vNpBRkdrxxQqog";
$url = "https://docs.google.com/spreadsheets/d/".$fileid."/export?format=csv&id=".$fileid;

$newfilename = "rwd-leads";

$file = fopen($url,"r");

$output = fopen($newfilename.'.csv', 'wb');

while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
       fputcsv($output, $data);
}

fclose($file);
fclose($output);