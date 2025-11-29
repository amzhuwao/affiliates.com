<?php
// admin/edit_quotation.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) exit("Missing id");

$stmt = $db->prepare("SELECT q.*, a.full_name AS aff_name, a.affiliate_id AS aff_code, a.tax_clearance, a.id AS aff_db_id
                      FROM quotations q JOIN affiliates a ON q.affiliate_id = a.id WHERE q.id = :id LIMIT 1");

// Reload affiliate for email
$stmtAff2 = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmtAff2->execute([':id' => $q['affiliate_id']]);
$affiliateInfo = $stmtAff2->fetch();

// Send status update email
sendQuotationStatusEmail($affiliateInfo, $q, $q['status'], $status);

$stmt->execute([':id' => $id]);
$q = $stmt->fetch();
if (!$q) exit("Quotation not found");

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quoted_amount = $_POST['quoted_amount'] ?: null;
    $commission_rate = $_POST['commission_rate'] ?: null; // admin may override
    $status = $_POST['status'] ?? $q['status'];

    // basic validation
    if ($status === 'converted' && (empty($quoted_amount) && $quoted_amount !== "0")) {
        $errors[] = 'Quoted amount is required when converting to sale.';
    }

    if (empty($errors)) {
        // update quotation
        $stmt2 = $db->prepare("UPDATE quotations SET quoted_amount = :qa, commission_rate = :cr, status = :st, updated_at = NOW() WHERE id = :id");
        $stmt2->execute([
            ':qa' => $quoted_amount,
            ':cr' => $commission_rate,
            ':st' => $status,
            ':id' => $id
        ]);

        // if converted -> calculate commission and create commission record
        if ($status === 'converted') {
            // reload affiliate info
            $stmtAff = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
            $stmtAff->execute([':id' => $q['affiliate_id']]);
            $affiliate = $stmtAff->fetch();

            $rateToUse = !empty($commission_rate) ? (float)$commission_rate : getCommissionRateForQuotation($q);
            $calc = calculateCommission((float)$quoted_amount, $affiliate, $rateToUse);

            // update quotation with commission values
            $stmt3 = $db->prepare("UPDATE quotations SET commission_amount = :comm, withholding_tax = :with, net_commission = :net, updated_at = NOW() WHERE id = :id");
            $stmt3->execute([
                ':comm' => $calc['gross_commission'],
                ':with' => $calc['withholding_tax'],
                ':net'  => $calc['net_commission'],
                ':id'   => $id
            ]);

            // insert into commissions table
            createCommissionRecord($db, $id, $q['affiliate_id'], $calc['gross_commission'], $calc['withholding_tax'], $calc['net_commission'], $calc['commission_rate']);
        }

        $success = "Quotation updated.";
        // refresh q
        $stmt = $db->prepare("SELECT q.*, a.full_name AS aff_name, a.affiliate_id AS aff_code FROM quotations q JOIN affiliates a ON q.affiliate_id = a.id WHERE q.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $q = $stmt->fetch();
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Edit Quotation</title></head><body>
<h2>Edit Quotation #<?=htmlspecialchars($q['id'])?></h2>
<?php foreach ($errors as $e) echo "<p style='color:red'>$e</p>"; ?>
<?php if ($success) echo "<p style='color:green'>$success</p>"; ?>

<form method="post">
  <p><strong>Affiliate:</strong> <?=htmlspecialchars($q['aff_code'].' - '.$q['aff_name'])?></p>
  <p><strong>Customer:</strong> <?=htmlspecialchars($q['customer_name'].' ('.$q['customer_phone'].')')?></p>
  <p><strong>Description:</strong><br><?=nl2br(htmlspecialchars($q['description']))?></p>

  <label>Quoted amount (final sale value): <input type="number" step="0.01" name="quoted_amount" value="<?=htmlspecialchars($q['quoted_amount'])?>"></label><br>
  <label>Commission rate (%) (leave blank to use default): <input type="number" step="0.01" name="commission_rate" value="<?=htmlspecialchars($q['commission_rate'])?>"></label><br>

  <label>Status:
    <select name="status">
      <option value="pending" <?=($q['status']=='pending'?'selected':'')?>>Pending</option>
      <option value="approved" <?=($q['status']=='approved'?'selected':'')?>>Approved</option>
      <option value="declined" <?=($q['status']=='declined'?'selected':'')?>>Declined</option>
      <option value="converted" <?=($q['status']=='converted'?'selected':'')?>>Converted (Sale)</option>
    </select>
  </label><br>

  <button type="submit">Save</button>
</form>

<p><a href="quotations.php">Back to list</a></p>
</body></html>
