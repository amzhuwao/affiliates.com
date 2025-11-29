<?php
// includes/functions.php (or wherever your function is defined)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//require __DIR__ . '/../vendor/autoload.php'; // adjust path to Composer autoload

// Define sender info
define('MAIL_FROM', 'your_gmail@gmail.com'); // your Gmail address
define('MAIL_FROM_NAME', 'Your Name');

function sendStatusEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Debug output for page
        $mail->SMTPDebug = 2;           
        $mail->Debugoutput = 'html';

        // SMTP configuration for Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;  
        $mail->Password   = 'cenfrqxkoyqpposc'; // Gmail app password if 2FA is on
        $mail->SMTPSecure = 'tls';       // Use TLS
        $mail->Port       = 587;

        // Sender & recipient
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo '<p style="color: green;">Email sent successfully to ' . htmlspecialchars($toEmail) . '!</p>';
    } catch (Exception $e) {
        echo '<p style="color: red;">Email sending failed: ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
    }
}
