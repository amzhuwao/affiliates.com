<?php
// includes/functions.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/smtp_config.php'; 
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
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

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

