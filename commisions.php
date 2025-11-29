<?php
// public/commissions.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$stmtTotal = $db->prepare("SELECT
    COALESCE(SUM(net_commission), 0) AS total_net,
    SUM(CASE WHEN payment_status = 'pending' THEN net_commission ELSE 0 END) AS pending_net,
    SUM(CASE WHEN payment_status = 'paid' THEN net_commission ELSE 0 END) AS paid_net
    FROM commissions
    WHERE affiliate_id = :aid");
$stmtTotal->execute([':aid' => $_SESSION['user_id']]);
$totals = $stmtTotal->fetch();

$stmt = $db->prepare("SELECT c.*, q.customer_name, q.customer_phone
    FROM commissions c
    JOIN quotations q ON c.quotation_id = q.id
    WHERE c.affiliate_id = :aid
    ORDER BY c.created_at DESC");
$stmt->execute([':aid' => $_SESSION['user_id']]);
$list = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>My Commissions</title></head>
<body>

<h2>Commission Summary</h2>

<table border="1" cellpadding="6">
<tr><th>Total Earned</th><td><?=htmlspecialchars($totals['total_net'])?></td></tr>
<tr><th>Pending</th><td><?=htmlspecialchars($totals['pending_net'])?></td></tr>
<tr><th>Paid</th><td><?=htmlspecialchars($totals['paid_net'])?></td></tr>
</table>

<h2>Commission Details</h2>
<table border="1" cellpadding="6">
<thead>
<tr>
  <th>#</th>
  <th>Customer</th>
  <th>Gross</th>
  <th>Tax</th>
  <th>Net</th>
  <th>Status</th>
  <th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach ($list as $row): ?>
<tr>
  <td><?=$row['id']?></td>
  <td><?=htmlspecialchars($row['customer_name'])?> (<?=htmlspecialchars($row['customer_phone'])?>)</td>
  <td><?=$row['gross_commission']?></td>
  <td><?=$row['withholding_tax']?></td>
  <td><?=$row['net_commission']?></td>
  <td><?=$row['payment_status']?></td>
  <td><?=$row['created_at']?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p><a href="dashboard.php">Back</a></p>

</body></html>
