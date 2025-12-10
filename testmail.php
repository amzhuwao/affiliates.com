<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // SMTP setup
    $mail->isSMTP();
    $mail->Host       = 'affiliates.anako.tech';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'system@affiliates.anako.tech';
    $mail->Password   = '3Objf{RiAt8K';   // system password
    //$mail->Password   = 'Z4Sl{3ROZpm(';
    // Encryption + Port (as cPanel recommends)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;

    // Who emails are sent from
    $mail->setFrom('system@affiliates.anako.tech', 'Website Form');

    // Who receives them
    $mail->addAddress('affiliates@anako.tech'); // replace with your target inbox

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'New Website Form Message';
    $mail->Body    = 'test form message at 23:37';

    $mail->send();
    echo "Message sent successfully!";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
