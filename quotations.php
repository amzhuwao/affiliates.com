<?php
// public/quotations.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$stmt = $db->prepare("SELECT * FROM quotations WHERE affiliate_id = :aid ORDER BY created_at DESC");
$stmt->execute([':aid' => $_SESSION['user_id']]);
$rows = $stmt->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>My Quotations</title></head><body>
<h2>My Quotations</h2>
<p><a href="new_quotation.php">Submit New Quotation</a></p>
<table border=1 cellpadding=6>
<thead><tr><th>#</th><th>Customer</th><th>Estimate</th><th>Quoted</th><th>Status</th><th>Created</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['id'])?></td>
  <td><?=htmlspecialchars($r['customer_name'])?> (<?=htmlspecialchars($r['customer_phone'])?>)</td>
  <td><?=htmlspecialchars($r['estimated_value'])?></td>
  <td><?=htmlspecialchars($r['quoted_amount'])?></td>
  <td><?=htmlspecialchars($r['status'])?></td>
  <td><?=htmlspecialchars($r['created_at'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p><a href="dashboard.php">Back</a></p>
</body></html>
