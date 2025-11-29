<?php
// admin/commissions.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$rows = $db->query("SELECT c.*, a.affiliate_id AS aff_code, a.full_name AS aff_name, q.customer_name
                    FROM commissions c
                    JOIN affiliates a ON c.affiliate_id = a.id
                    JOIN quotations q ON c.quotation_id = q.id
                    ORDER BY c.created_at DESC")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Commissions</title></head><body>
<h2>Commissions</h2>
<table border=1 cellpadding=6>
<thead><tr><th>#</th><th>Affiliate</th><th>Quotation</th><th>Gross</th><th>Withholding</th><th>Net</th><th>Status</th><th>Created</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?=$r['id']?></td>
  <td><?=htmlspecialchars($r['aff_code'].' - '.$r['aff_name'])?></td>
  <td><?=htmlspecialchars($r['quotation_id'].' ('.($r['customer_name']??'').')')?></td>
  <td><?=htmlspecialchars($r['gross_commission'])?></td>
  <td><?=htmlspecialchars($r['withholding_tax'])?></td>
  <td><?=htmlspecialchars($r['net_commission'])?></td>
  <td><?=htmlspecialchars($r['payment_status'])?></td>
  <td><?=htmlspecialchars($r['created_at'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
