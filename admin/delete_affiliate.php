<?php
// admin/delete_affiliate.php (soft delete + email)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$adminName = $_SESSION['full_name'] ?? 'Admin';

if (!$id) { header('Location: affiliates.php'); exit; }

// fetch affiliate
$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$affiliate = $stmt->fetch();
if ($affiliate) {
    $old = $affiliate['status'];
    // soft delete
    $stmt = $db->prepare("UPDATE affiliates SET status = 'deleted' WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // notify affiliate
    sendStatusEmail($affiliate, $old, 'deleted', $adminName);
}

header('Location: affiliates.php?msg=deleted');
exit;
