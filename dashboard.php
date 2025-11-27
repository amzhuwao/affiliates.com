<?php
// public/dashboard.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
<h2>Welcome, <?=htmlspecialchars($_SESSION['full_name'])?></h2>
<p>Your Affiliate ID: <?=htmlspecialchars($user['affiliate_id'])?></p>
<p>Your WhatsApp referral link: <a href="<?=htmlspecialchars($user['referral_link'])?>" target="_blank"><?=htmlspecialchars($user['referral_link'])?></a></p>
<p><a href="logout.php">Logout</a></p>
<?php if ($_SESSION['role'] === 'admin'): ?>
  <p><a href="/admin/affiliates.php">Admin: View Affiliates</a></p>
<?php endif; ?>
</body>
</html>
