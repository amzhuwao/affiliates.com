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

if (sendStatusEmail($fakeAffiliate, 'active', 'test', 'System Test')) {
    echo "Email sent successfully!";
} else {
    echo "Email sending failed — check logs.";
}
