<?php
ini_set('log_errors', 1); 
ini_set('error_log', __DIR__ . '/../logs/php_mail_error.log');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1️⃣ Autoload FIRST
require_once __DIR__ . '/../vendor/autoload.php';

// 2️⃣ Use statements MUST be here (global namespace, not inside function)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3️⃣ Include your functions AFTER autoload
require_once __DIR__ . '/../includes/functions.php';

$fakeAffiliate = [
    'email' => 'amzhuwao@gmail.com',
    'full_name' => 'Test User',
    'affiliate_id' => 'AFF000'
];

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'amzhuwao@gmail.com';
    $mail->Password   = 'cenfrqxkoyqpposc'; // Replace with App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('amzhuwao@gmail.com', 'Affiliates Support');
    $mail->addAddress('azaways@gmail.com', 'Recipient');

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Successful!';
    $mail->Body    = 'Your PHPMailer SMTP settings are correct.';

    $mail->send();
    echo '<p style="color: green;">Email sent successfully 🎉</p>';

} catch (Exception $e) {
    echo '<p style="color: red;">Mailer Error: ' . $mail->ErrorInfo . '</p>';
}
