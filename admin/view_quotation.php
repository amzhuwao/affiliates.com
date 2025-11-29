<?php
// admin/view_quotation.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) exit("Missing id");

$stmt = $db->prepare("SELECT q.*, a.full_name AS aff_name, a.affiliate_id AS aff_code, a.tax_clearance
                      FROM quotations q JOIN affiliates a ON q.affiliate_id = a.id WHERE q.id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$q = $stmt->fetch();
if (!$q) exit("Quotation not found");
?>
<!doctype html><html><head><meta charset="utf-8"><title>View Quotation</title></head><body>
<h2>Quotation #<?=htmlspecialchars($q['id'])?></h2>
<p><strong>Affiliate:</strong> <?=htmlspecialchars($q['aff_code'].' - '.$q['aff_name'])?></p>
<p><strong>Customer:</strong> <?=htmlspecialchars($q['customer_name'].' ('.$q['customer_phone'].')')?></p>
<p><strong>Description:</strong><br><?=nl2br(htmlspecialchars($q['description']))?></p>
<p><strong>Estimated Value:</strong> <?=htmlspecialchars($q['estimated_value'])?></p>
<p><strong>Quoted Amount:</strong> <?=htmlspecialchars($q['quoted_amount'])?></p>
<p><strong>Commission Rate (%):</strong> <?=htmlspecialchars($q['commission_rate'] ?? DEFAULT_COMMISSION_RATE)?></p>
<p><strong>Status:</strong> <?=htmlspecialchars($q['status'])?></p>

<p>
  <a href="edit_quotation.php?id=<?= $q['id'] ?>">Edit / Approve</a> |
  <a href="quotations.php">Back</a>
</p>
</body></html>
