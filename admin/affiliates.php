<?php
// admin/affiliates.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$stmt = $db->query("SELECT id, affiliate_id, full_name, phone_number, email, referral_link, created_at, status FROM affiliates ORDER BY id DESC");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Affiliates</title></head>
<body>
<h2>Affiliates (Admin)</h2>
<p><a href="/../dashboard.php">Back to dashboard</a></p>
<table border="1" cellpadding="6" cellspacing="0">
  <thead><tr><th>ID</th><th>Affiliate ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Referral Link</th><th>Created</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?=htmlspecialchars($r['id'])?></td>
      <td><?=htmlspecialchars($r['affiliate_id'])?></td>
      <td><?=htmlspecialchars($r['full_name'])?></td>
      <td><?=htmlspecialchars($r['phone_number'])?></td>
      <td><?=htmlspecialchars($r['email'])?></td>
      <td><a href="<?=htmlspecialchars($r['referral_link'])?>" target="_blank">Open</a></td>
      <td><?=htmlspecialchars($r['created_at'])?></td>
      <td><?=htmlspecialchars($r['status'])?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
