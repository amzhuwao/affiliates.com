<?php
// includes/functions.php
require_once __DIR__ . '/db.php';

function generateAffiliateId(PDO $db) {
    // Get last numeric part and increment. Safer than COUNT to avoid race issues in bigger apps.
    $stmt = $db->query("SELECT affiliate_id FROM affiliates ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    if (!$row) {
        $num = 1;
    } else {
        // assume pattern AFF### or AFF000 etc.
        if (preg_match('/AFF0*([0-9]+)/', $row['affiliate_id'], $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
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
