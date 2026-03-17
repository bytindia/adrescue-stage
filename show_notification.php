<?php

include "db.php";

$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

if($last_id == 0){

    $q = mysqli_query($conn,"
    SELECT tbl_id 
    FROM notification_alert
    ORDER BY tbl_id DESC
    LIMIT 1
    ");

    $row = mysqli_fetch_assoc($q);

    echo json_encode([
        "status"=>"init",
        "id"=>$row['tbl_id'] ?? 0
    ]);

    exit;

}

$q = mysqli_query($conn,"
SELECT tbl_id,client_name,notify_msg
FROM notification_alert
WHERE tbl_id > '$last_id'
ORDER BY tbl_id ASC
LIMIT 1
");

if(mysqli_num_rows($q)>0){

$row = mysqli_fetch_assoc($q);

echo json_encode([
"status"=>"new",
"id"=>$row['tbl_id'],
"client_name"=>$row['client_name'],
"notify_msg"=>$row['notify_msg']
]);

}else{

echo json_encode([
"status"=>"none"
]);

}