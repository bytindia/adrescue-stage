<?
header('Content-Type: application/json');

if (isset($_POST)) {
    /*$input = file_get_contents('php://input');
    $body = json_decode($input);
    echo json_encode($body);*/
    echo json_encode([
        'code' => 200,
        'message' => 'lead captured',
    ]);
   // exit;
} else {
    echo json_encode([
        'code' => 201,
        'message' => 'failed',
    ]);
}