<?php
error_log("PHP logging test!");
ini_set('log_errors', 1);
//ini_set('error_log', __DIR__ . '/../logs/php_mail_error.log');
ini_set('error_log', '/var/www/affiliates.com/logs/php_mail_error.log');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/functions.php';

$fakeAffiliate = [
    'email' => 'amzhuwao@gmail.com',
    'full_name' => 'Test User',
    'affiliate_id' => 'AFF000'
];


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php'; // adjust path to Composer autoload

$mail = new PHPMailer(true);

try {
    // Debug output on page
    $mail->SMTPDebug = 2;           // 0 = off, 2 = detailed SMTP logs
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com';  // your SMTP host
    $mail->SMTPAuth   = true;
    $mail->Username   = 'amzhuwao@gmail.com';
    $mail->Password   = 'cenfrqxkoyqpposc';
    $mail->SMTPSecure = 'tls';               // or 'ssl'
    $mail->Port       = 587;                 // TLS = 587, SSL = 465

    $mail->setFrom('amzhuwao@gmail.com', 'Your Name');
    $mail->addAddress('azaways@gmail.com', 'Recipient Name');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo '<p style="color: green;">Email sent successfully!</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">Email sending failed: ' . $mail->ErrorInfo . '</p>';
}
