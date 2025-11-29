<?php
// includes/functions.php
/* use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/smtp_config.php'; */
require __DIR__ . '/vendor/autoload.php';  // Adjust path if your script is elsewhere
require_once __DIR__ . '/db.php';

function generateAffiliateId($db) {
    $stmt = $db->query("SELECT affiliate_id FROM affiliates ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    if (!$row) return 'AFF' . str_pad(1, 3, '0', STR_PAD_LEFT);
    if (preg_match('/AFF0*([0-9]+)/', $row['affiliate_id'], $m)) {
        $num = intval($m[1]) + 1;
    } else {
        $num = 1;
    }
    return 'AFF' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

function buildReferralLink($affiliateId) {
    $company = COMPANY_WHATSAPP;
    $text = "I've been referred by Affiliate " . $affiliateId;
    return "https://wa.me/{$company}?text=" . urlencode($text);
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Send account status notification email to an affiliate.
 *
 * @param array $affiliate Row from affiliates table with at least email, full_name, affiliate_id
 * @param string $oldStatus
 * @param string $newStatus
 * @param string $adminName (optional) - who performed action
 * @return bool
 */
function sendStatusEmail(array $affiliate, string $oldStatus, string $newStatus, string $adminName = 'Admin') {
    if (empty($affiliate['email'])) return false; // no email to send to

    $to = $affiliate['email'];
    $subject = "Account status changed: {$newStatus}";
    $time = date('Y-m-d H:i:s');
    $message = "Hello " . ($affiliate['full_name'] ?? $affiliate['affiliate_id']) . ",\n\n";
    $message .= "This is to inform you that your affiliate account (ID: {$affiliate['affiliate_id']}) ";
    $message .= "has been changed from '{$oldStatus}' to '{$newStatus}' by {$adminName} on {$time}.\n\n";

    if ($newStatus === 'suspended') {
        $message .= "While your account is suspended you will not be able to log in or receive commission payouts.\n\n";
    } elseif ($newStatus === 'deleted') {
        $message .= "Your account has been marked as deleted (soft-delete). If this is a mistake please contact support.\n\n";
    } elseif ($newStatus === 'active') {
        $message .= "Your account is now active. You may log in and continue using your referral link.\n\n";
    }

    $message .= "Regards,\n";
    $message .= $adminName . "\n";

    // Prepare headers
    $headers = [];
    $headers[] = 'From: ' . MAIL_FROM;
    $headers[] = 'Reply-To: ' . MAIL_FROM;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    // Use mail() - on many local environments mail() is not configured;
    // If mail() doesn't work on your host, consider setting up SMTP (PHPMailer) or an external provider.
    return @mail($to, $subject, $message, implode("\r\n", $headers));
}
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}