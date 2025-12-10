<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// includes/functions.php
// 1. Define the correct path to the PHPMailer files
// This assumes the structure: email_test/PHPMailer/src/
$path_to_phpmailer = __DIR__ . '/../PHPMailer/src/';

// Use namespaces for clarity
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Load the necessary PHPMailer classes
require $path_to_phpmailer . 'Exception.php';
require $path_to_phpmailer . 'PHPMailer.php';
require $path_to_phpmailer . 'SMTP.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/smtp_config.php';

// Define the sender's details (constants with safe fallback)
if (!defined('MAIL_FROM')) {
    // Prefer SMTP_USERNAME if present to avoid sender mismatch rejections
    $defaultFrom = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'no-reply@example.com';
    define('MAIL_FROM', $defaultFrom);
}
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Affiliate Program');

function generateAffiliateId($db)
{
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

function buildReferralLink($affiliateId)
{
    $company = COMPANY_WHATSAPP;
    $text = "Hi, I've been referred by Affiliate " . $affiliateId . ". Interested in Get-Solar Promotion.";
    return "https://wa.me/{$company}?text=" . urlencode($text);
}

// Get program WhatsApp number
function getProgramWhatsApp($program)
{
    global $db;
    $stmt = $db->prepare("SELECT whatsapp_number FROM program_settings WHERE program = :program LIMIT 1");
    $stmt->execute([':program' => $program]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['whatsapp_number'] ?? null;
}

// Build WA referral link based on program
function buildProgramWaLink($affiliate_id, $program)
{
    $wa = getProgramWhatsApp($program);
    if (!$wa) return '#';

    $message = urlencode("Hi, I've been referred by Affiliate " . $affiliate_id . ". Interested in Get-Solar Promotion.");
    return "https://wa.me/{$wa}?text={$message}";
}


function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Determine commission rate to use for a quotation.
 * If quotation has commission_rate (non-null) return it, otherwise use default.
 */
function getCommissionRateForQuotation(array $quotation): float
{
    if (!empty($quotation['commission_rate']) && is_numeric($quotation['commission_rate'])) {
        return (float)$quotation['commission_rate'];
    }
    return (float) DEFAULT_COMMISSION_RATE;
}

/**
 * Calculate commission details given deal amount and affiliate info.
 * Returns array: gross_commission, withholding_tax, net_commission, commission_rate
 */
function calculateCommission(float $dealAmount, array $affiliate, ?float $overrideRate = null): array
{
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
function createCommissionRecord(PDO $db, int $quotationId, int $affiliateId, float $gross, float $withholding, float $net, float $rate)
{
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

function getAffiliateById(PDO $db, int $affiliateId)
{
    $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id");
    $stmt->execute([':id' => $affiliateId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sendQuotationStatusEmail(array $affiliate, array $quotation, string $oldStatus, string $newStatus, string $adminName = 'Admin'): bool
{
    if (empty($affiliate['email'])) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Setup
        $mail->SMTPDebug = 0; // change to SMTP::DEBUG_SERVER to enable debug output
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

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
        echo ("Quote Mail error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendStatusEmail(array $affiliate, string $oldStatus, string $newStatus, string $adminName = 'Admin')
{
    if (empty($affiliate['email'])) {
        error_log('sendStatusEmail: affiliate has no email. ID=' . ($affiliate['id'] ?? $affiliate['affiliate_id'] ?? 'unknown'));
        return false; // no email to send to
    }

    $to = $affiliate['email'];
    error_log('sendStatusEmail: starting send to ' . $to . ' for affiliate ' . ($affiliate['affiliate_id'] ?? $affiliate['id'] ?? 'unknown'));
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
        // Send via SMTP with minimal connection-level debug to error_log for diagnostics
        $mail->Debugoutput = 'error_log';
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $fromAddress = MAIL_FROM;
        $fromName = MAIL_FROM_NAME;
        // Safety: if MAIL_FROM is empty but SMTP_USERNAME exists, use that as sender
        if (empty($fromAddress) && defined('SMTP_USERNAME')) {
            $fromAddress = SMTP_USERNAME;
        }
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($to, $affiliate['full_name'] ?? '');

        // Also notify admin if ADMIN_EMAIL is configured
        if (defined('ADMIN_EMAIL') && !empty(ADMIN_EMAIL)) {
            $mail->addAddress(ADMIN_EMAIL, 'Admin');
        }

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        error_log('sendStatusEmail: Mail sent to ' . $to . ' for affiliate ' . $affiliate['affiliate_id'] . ' status ' . $oldStatus . ' -> ' . $newStatus);
        return true;
    } catch (Exception $e) {
        // Log both the Exception message and PHPMailer's ErrorInfo for diagnosis
        error_log('sendStatusEmail: Mail error: ' . $e->getMessage() . ' | PHPMailer: ' . $mail->ErrorInfo);
        // Fallback to PHP mail() with proper headers
        $headers = [];
        $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
        $headers[] = 'Reply-To: ' . MAIL_FROM;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=utf-8';
        $ok = @mail($to, $subject, $message, implode("\r\n", $headers));
        if (!$ok) {
            error_log('sendStatusEmail: Fallback mail() failed for ' . $to);
        }
        return $ok;
    }
}

/**
 * Send a program change request email to the administrator.
 * Returns true on success, false on failure.
 */
function sendProgramChangeRequest(array $affiliate, string $requestedProgramCode): bool
{
    $programLabel = ($requestedProgramCode === 'TV') ? 'TechVouch' : 'GetSolar';

    $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : null;
    if (empty($adminEmail)) {
        error_log('sendProgramChangeRequest: no admin email configured (ADMIN_EMAIL)');
        return false;
    }

    $subject = "Program change request: " . ($affiliate['affiliate_id'] ?? $affiliate['id'] ?? 'unknown');
    $message = "Program change request\n\n";
    $message .= "Affiliate: " . ($affiliate['full_name'] ?? '') . "\n";
    $message .= "Affiliate ID: " . ($affiliate['affiliate_id'] ?? $affiliate['id'] ?? '') . "\n";
    $message .= "Email: " . ($affiliate['email'] ?? '') . "\n";
    $message .= "Phone: " . ($affiliate['phone_number'] ?? '') . "\n";
    $message .= "Requested Program: " . $programLabel . " ({$requestedProgramCode})\n\n";
    $message .= "Please review this request in the admin panel and update the affiliate's program as appropriate.\n";

    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($adminEmail);
        // allow admin to reply directly to the affiliate
        if (!empty($affiliate['email'])) {
            $mail->addReplyTo($affiliate['email'], $affiliate['full_name'] ?? '');
        }

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Program change mail error: ' . $e->getMessage() . ' | PHPMailer: ' . $mail->ErrorInfo);
        // fallback to PHP mail()
        $headers = [];
        $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
        if (!empty($affiliate['email'])) $headers[] = 'Reply-To: ' . $affiliate['email'];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=utf-8';
        $ok = @mail($adminEmail, $subject, $message, implode("\r\n", $headers));
        if (!$ok) error_log('sendProgramChangeRequest: fallback mail() failed');
        return $ok;
    }
}
