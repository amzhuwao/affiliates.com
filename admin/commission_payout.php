<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// Handle payout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $method = trim($_POST['payment_method']);
    $ref = trim($_POST['payment_ref']);
    $note = trim($_POST['admin_note']);

    $stmt = $db->prepare("UPDATE commissions 
        SET payment_status = 'paid',
            paid_at = NOW(),
            payment_method = :m,
            payment_ref = :r,
            admin_note = :note
        WHERE id = :id");
    $stmt->execute([
        ':m' => $method,
        ':r' => $ref,
        ':note' => $note,
        ':id' => $id
    ]);
}

$stmt = $db->query("SELECT c.*, a.full_name
    FROM commissions c
    JOIN affiliates a ON c.affiliate_id = a.id
    WHERE c.payment_status = 'pending'
    ORDER BY c.created_at DESC");
$pending = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Commission Payouts - Admin</title>
</head>
<body>

<h2>Commission Payouts</h2>
<p><a href="dashboard.php">Back to dashboard</a></p>

<table border="1" cellpadding="8">
<thead>
<tr>
  <th>ID</th>
  <th>Affiliate</th>
  <th>Gross</th>
  <th>Tax</th>
  <th>Net</th>
  <th>Created</th>
  <th>Action</th>
</tr>
</thead>
<tbody>

<?php foreach ($pending as $c): ?>
<tr>
  <td><?=$c['id']?></td>
  <td><?=htmlspecialchars($c['full_name'])?></td>
  <td><?=$c['gross_commission']?></td>
  <td><?=$c['withholding_tax']?></td>
  <td><?=$c['net_commission']?></td>
  <td><?=$c['created_at']?></td>
  <td>
    <form method="post" style="display:inline;">
        <input type="hidden" name="id" value="<?=$c['id']?>">
        <input type="text" name="payment_method" placeholder="Method" required>
        <input type="text" name="payment_ref" placeholder="Reference" required>
        <input type="text" name="admin_note" placeholder="Notes">
        <button type="submit">Mark Paid</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</body>
</html>
