<?php
// includes/functions.php
// 1. Define the correct path to the PHPMailer files
// This assumes the structure: email_test/PHPMailer/src/
$path_to_phpmailer = __DIR__ . '/../PHPMailer/src/';

// Use namespaces for clarity
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. Load the necessary PHPMailer classes
require $path_to_phpmailer . 'Exception.php';
require $path_to_phpmailer . 'PHPMailer.php';
require $path_to_phpmailer . 'SMTP.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/smtp_config.php';

// Define the sender's details (constants with safe fallback)
if (!defined('MAIL_FROM')) define('MAIL_FROM', 'amzhuwao@gmail.com'); // This should usually match your SMTP username
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Affiliate Program');

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
 * Determine commission rate to use for a quotation.
 * If quotation has commission_rate (non-null) return it, otherwise use default.
 */
function getCommissionRateForQuotation(array $quotation): float {
    if (!empty($quotation['commission_rate']) && is_numeric($quotation['commission_rate'])) {
        return (float)$quotation['commission_rate'];
    }
    return (float) DEFAULT_COMMISSION_RATE;
}

/**
 * Calculate commission details given deal amount and affiliate info.
 * Returns array: gross_commission, withholding_tax, net_commission, commission_rate
 */
function calculateCommission(float $dealAmount, array $affiliate, ?float $overrideRate = null): array {
    $rate = is_null($overrideRate) ? (float)DEFAULT_COMMISSION_RATE : (float)$overrideRate;
    // If affiliate has commission_rate in quoting, pass it instead of override
    $gross = round($dealAmount * ($rate / 100.0), 2);

    // Withholding applies if tax_clearance is false/0
    $withholding = 0.00;
    if (empty($affiliate['tax_clearance'])) {
        $withholding = round($gross * (DEFAULT_WITHHOLDING_RATE / 100.0), 2);
    }

    $net = round($gross - $withholding, 2);
    return [
        'commission_rate' => $rate,
        'gross_commission' => $gross,
        'withholding_tax'   => $withholding,
        'net_commission'    => $net
    ];
}

/**
 * Create commission record after a quotation is converted.
 * Will insert into commissions table.
 */
function createCommissionRecord(PDO $db, int $quotationId, int $affiliateId, float $gross, float $withholding, float $net, float $rate) {
    $stmt = $db->prepare("INSERT INTO commissions
        (quotation_id, affiliate_id, gross_commission, withholding_tax, net_commission, commission_rate, created_at)
        VALUES (:qid, :aid, :gross, :with, :net, :rate, NOW())");
    $stmt->execute([
        ':qid' => $quotationId,
        ':aid' => $affiliateId,
        ':gross' => $gross,
        ':with' => $withholding,
        ':net' => $net,
        ':rate' => $rate
    ]);
    return $db->lastInsertId();
}
function getAffiliateById(PDO $db, int $affiliateId) {
    $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id");
    $stmt->execute([':id' => $affiliateId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sendQuotationStatusEmail(array $affiliate, array $quotation, string $oldStatus, string $newStatus, string $adminName = 'Admin'): bool {
    if (empty($affiliate['email'])) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Setup
        $mail->SMTPDebug = 0; // change to SMTP::DEBUG_SERVER to enable debug output
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth = (defined('SMTP_USERNAME') && defined('SMTP_PASSWORD')) ? true : false;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        // Map textual secure setting to PHPMailer constants
        if (defined('SMTP_SECURE')) {
            $secure = strtolower(SMTP_SECURE);
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls' || $secure === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        }
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 25;

        // Email Headers
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($affiliate['email'], $affiliate['full_name']);

        // Email Content
        $mail->isHTML(false);
        $mail->Subject = "Quotation Status Update (#{$quotation['id']})";

        $message = "Hello {$affiliate['full_name']},\n\n";
        $message .= "Your quotation request (#{$quotation['id']}) status has changed.\n";
        $message .= "Old Status: {$oldStatus}\n";
        $message .= "New Status: {$newStatus}\n\n";

        if ($newStatus === 'approved') {
            $message .= "An administrator has approved your quotation. Please standby for final pricing.";
        } elseif ($newStatus === 'declined') {
            $message .= "Unfortunately, your quotation request was declined.\nYou may request again or contact support for more details.";
        } elseif ($newStatus === 'converted') {
            $message .= "Congratulations! This quotation has been converted to a sale.\nYour commission has been processed as per program rules.";
        }

        $message .= "\n\nRegards,\n{$adminName}\n";

        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Quote Mail error: {$mail->ErrorInfo}");
        return false;
    }
}

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

    // Try sending via PHPMailer (SMTP) first
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0; // set to SMTP::DEBUG_SERVER for debugging
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth = (defined('SMTP_USERNAME') && defined('SMTP_PASSWORD')) ? true : false;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        if (defined('SMTP_SECURE')) {
            $secure = strtolower(SMTP_SECURE);
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls' || $secure === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        }
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 25;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $affiliate['full_name'] ?? '');
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log both the Exception message and PHPMailer's ErrorInfo for diagnosis
        error_log("Status Mail error: " . $e->getMessage() . " | PHPMailer: " . $mail->ErrorInfo);
        // Fallback to PHP mail() with proper headers
        $headers = [];
        $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
        $headers[] = 'Reply-To: ' . MAIL_FROM;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=utf-8';
        $ok = @mail($to, $subject, $message, implode("\r\n", $headers));
        if (!$ok) {
            error_log("Fallback mail() failed for {$to}");
        }
        return $ok;
    }
}