<?php ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once "Mail-master/Mail.php";

$from = '<admissions@crescent.education>'; //change this to your email address
$to = '<prabhu@bytindia.com>'; // change to address
$subject = 'Insert subject here'; // subject of mail
$body = "Hello world! this is the content of the email"; //content of mail

$headers = array(
    'From' => $from,
    'To' => $to,
    'Subject' => $subject
);

$smtp = Mail::factory('smtp', array(
    'host' => 'ssl://smtp.gmail.com',
    'port' => '465',
    'auth' => true,
        'username' => 'admissions@crescent.education', //your gmail account
        'password' => 'admissions@2019' // your password
    ));

// Send the mail
$mail = $smtp->send($to, $headers, $body);

//check mail sent or not
if (PEAR::isError($mail)) {
    echo '<p>'.$mail->getMessage().'</p>';
} else {
    echo '<p>Message successfully sent!</p>';
}