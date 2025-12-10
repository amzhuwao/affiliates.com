<?php
// includes/config.php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'affiliates_db');
define('DB_USER', 'admin');
define('DB_PASS', '%Uy[SV=k2aHF');

// Company WhatsApp number (international, no +): e.g. 263771234567
define('COMPANY_WHATSAPP', '263771234567');

// Default commission percent (e.g., 10.00 = 10%)
define('DEFAULT_COMMISSION_RATE', 10.00);

// Default withholding tax percent applied only if affiliate has no tax_clearance
define('DEFAULT_WITHHOLDING_RATE', 15.00); // adjust to local tax rule