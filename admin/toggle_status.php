<?php
// admin/toggle_status.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$to = $_GET['to'] ?? 'active';
$adminName = $_SESSION['full_name'] ?? 'Admin';

if (!$id) { header('Location: affiliates.php'); exit; }

// fetch current affiliate record
$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$affiliate = $stmt->fetch();
if (!$affiliate) { header('Location: affiliates.php'); exit; }

$oldStatus = $affiliate['status'];

// update status
$stmt = $db->prepare("UPDATE affiliates SET status = :status WHERE id = :id");
$stmt->execute([':status' => $to, ':id' => $id]);

// send notification email (best-effort)
sendStatusEmail($affiliate, $oldStatus, $to, $adminName);

$msg = ($to === 'active') ? 'reactivated' : 'updated';
header("Location: affiliates.php?msg=" . $msg);
exit;
