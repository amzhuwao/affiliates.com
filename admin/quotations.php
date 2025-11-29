<?php
// admin/quotations.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$rows = $db->query("SELECT q.*, a.affiliate_id AS aff_code, a.full_name AS aff_name, a.phone_number AS aff_phone
                    FROM quotations q
                    JOIN affiliates a ON q.affiliate_id = a.id
                    ORDER BY q.created_at DESC")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Quotations</title></head><body>
<h2>Quotations (Admin)</h2>
<p><a href="/public/dashboard.php">Back to dashboard</a></p>
<table border=1 cellpadding=6>
<thead><tr><th>#</th><th>Affiliate</th><th>Customer</th><th>Estimate</th><th>Quoted</th><th>Rate%</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['id'])?></td>
  <td><?=htmlspecialchars($r['aff_code'])?> - <?=htmlspecialchars($r['aff_name'])?></td>
  <td><?=htmlspecialchars($r['customer_name'])?> (<?=htmlspecialchars($r['customer_phone'])?>)</td>
  <td><?=htmlspecialchars($r['estimated_value'])?></td>
  <td><?=htmlspecialchars($r['quoted_amount'])?></td>
  <td><?=htmlspecialchars($r['commission_rate'] ?? DEFAULT_COMMISSION_RATE)?></td>
  <td><?=htmlspecialchars($r['status'])?></td>
  <td>
    <a href="view_quotation.php?id=<?= $r['id'] ?>">View</a> |
    <a href="edit_quotation.php?id=<?= $r['id'] ?>">Edit</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
