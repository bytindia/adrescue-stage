<?php

$post = [
    'name' => 'Test',
    'email' => 'prabhu@byt.com',
    'phone'   => 9176299010,
    'project'   => 'RWD Corniche'
];

$ch = curl_init('https://stage.adrescue.in/rwd/rwd-spreadsheet.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

// execute!
$response = curl_exec($ch);

// close the connection, release resources used
curl_close($ch);

// do anything you want with your response
var_dump($response);