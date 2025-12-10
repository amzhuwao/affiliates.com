<?php
// SMTP settings for PHPMailer (static values defined here; environment variables are ignored)
define('SMTP_HOST', 'affiliates.anako.tech');
define('SMTP_PORT', 465); // default SMTP port, adjust as needed

// Credentials
define('SMTP_USERNAME', 'system@affiliates.anako.tech');
define('SMTP_PASSWORD', '3Objf{RiAt8K');

// From address/name: default to SMTP_USERNAME if not provided
define('MAIL_FROM', 'system@affiliates.anako.tech');
define('MAIL_FROM_NAME', 'Affiliates Program');

// Admin email for receiving system notifications (program change requests, etc.)
define('ADMIN_EMAIL', 'affiliates@anako.tech');
