<?php
// admin/edit_quotation.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) exit("Missing id");

// --- FIXED SECTION: Prepare the main query ---
$stmt = $db->prepare("SELECT q.*, a.full_name AS aff_name, a.affiliate_id AS aff_code, a.tax_clearance, a.id AS aff_db_id
                      FROM quotations q JOIN affiliates a ON q.affiliate_id = a.id WHERE q.id = :id LIMIT 1");
                      
// -------------------------------------------------------------
// *** CRITICAL FIX START: Execute and fetch $q before using it. ***
// -------------------------------------------------------------
$stmt->execute([':id' => $id]);
$q = $stmt->fetch();
if (!$q) exit("Quotation not found");
// -------------------------------------------------------------
// *** CRITICAL FIX END: $q is now defined for the rest of the script. ***
// -------------------------------------------------------------

// We don't need $stmtAff2 or sendQuotationStatusEmail here anymore
// because the email should only be sent AFTER a successful update via POST.

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_status = $q['status']; // Store the current status for the email comparison
    $quoted_amount = $_POST['quoted_amount'] ?: null;
    $commission_rate = $_POST['commission_rate'] ?: null; // admin may override
    $status = $_POST['status'] ?? $old_status; // New status is set here

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
        
        // --- ADDED EMAIL LOGIC HERE (After successful update) ---
        // 1. Refresh $q to get the latest database values for display/email
        $stmt->execute([':id' => $id]);
        $q = $stmt->fetch();
        
        // 2. Reload affiliate info for the email function
        $stmtAff2 = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
        $stmtAff2->execute([':id' => $q['affiliate_id']]);
        $affiliateInfo = $stmtAff2->fetch();
        
        // 3. Send status update email, using $old_status and the $status that was just saved
        if ($old_status !== $status) {
            sendQuotationStatusEmail($affiliateInfo, $q, $old_status, $status);
        }
        // --------------------------------------------------------

    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation #<?=htmlspecialchars($q['id'])?> - Admin Panel</title>
    <link rel="stylesheet" href="../css/edit_quotation.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="edit-container">
        <!-- Back Link -->
        <a href="quotations.php" class="back-link">Back to Quotations</a>

        <!-- Main Card -->
        <div class="edit-card">
            <!-- Header -->
            <div class="card-header">
                <div class="quotation-id-badge">QUOTATION #<?=htmlspecialchars($q['id'])?></div>
                <h1 class="page-title">Edit Quotation</h1>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $e): ?>
                    <div class="message error"><?=htmlspecialchars($e)?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="message success"><?=htmlspecialchars($success)?></div>
            <?php endif; ?>

            <!-- Quotation Info Section -->
            <div class="info-section">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Affiliate</div>
                        <div class="info-value"><?=htmlspecialchars($q['aff_code'].' - '.$q['aff_name'])?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value"><?=htmlspecialchars($q['customer_name'])?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Customer Phone</div>
                        <div class="info-value"><?=htmlspecialchars($q['customer_phone'])?></div>
                    </div>
                </div>
                
                <div class="description-box">
                    <div class="info-label">Description</div>
                    <div class="description-text"><?=nl2br(htmlspecialchars($q['description']))?></div>
                </div>
            </div>

            <!-- Edit Form -->
            <form method="post" class="form-section">
                <div class="section-title">Update Quotation Details</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quoted_amount">Quoted Amount (Final Sale Value)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="quoted_amount" 
                            id="quoted_amount"
                            value="<?=htmlspecialchars($q['quoted_amount'])?>"
                            placeholder="Enter final sale amount">
                        <div class="hint-text">The total sale value of the quotation</div>
                    </div>

                    <div class="form-group">
                        <label for="commission_rate">Commission Rate (%)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="commission_rate" 
                            id="commission_rate"
                            value="<?=htmlspecialchars($q['commission_rate'])?>"
                            placeholder="Leave blank for default rate">
                        <div class="hint-text">Optional: Override the default commission rate</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Quotation Status</label>
                        <select name="status" id="status">
                            <option value="pending" <?=($q['status']=='pending'?'selected':'')?>>⏳ Pending</option>
                            <option value="approved" <?=($q['status']=='approved'?'selected':'')?>>✓ Approved</option>
                            <option value="declined" <?=($q['status']=='declined'?'selected':'')?>>✗ Declined</option>
                            <option value="converted" <?=($q['status']=='converted'?'selected':'')?>>💰 Converted (Sale)</option>
                        </select>
                        <div class="hint-text">Change status to "Converted" to calculate commission</div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>