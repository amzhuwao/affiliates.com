<?php
// SMTP settings for PHPMailer
// Load from environment variables where possible. Example env names:
// SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USERNAME, SMTP_PASSWORD,
// MAIL_FROM, MAIL_FROM_NAME

// simple env getter: prefer getenv(), fallback to $_ENV, then default
$env = function(string $key, $default = null) {
	$v = getenv($key);
	if ($v === false || $v === null) {
		if (array_key_exists($key, $_ENV)) return $_ENV[$key];
		return $default;
	}
	return $v;
};

define('SMTP_HOST', $env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)$env('SMTP_PORT', 587));
define('SMTP_SECURE', $env('SMTP_SECURE', 'tls')); // 'ssl', 'tls', or 'starttls'

// Credentials: leave empty by default to avoid committing secrets
define('SMTP_USERNAME', $env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', $env('SMTP_PASSWORD', ''));

// From address/name: default to SMTP_USERNAME if not provided
define('MAIL_FROM', $env('MAIL_FROM', $env('SMTP_USERNAME', 'no-reply@example.com')));
define('MAIL_FROM_NAME', $env('MAIL_FROM_NAME', 'Affiliates Program'));
