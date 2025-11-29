<?php
// admin/edit_affiliate.php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
requireAdmin();

$errors = [];
$success = '';

$id = $_GET['id'] ?? null;
if (!$id) { echo 'Missing id'; exit; }

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) { echo 'Affiliate not found'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect all fields (admin can edit all)
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $tax_clearance = isset($_POST['tax_clearance']) ? 1 : 0;
    $authentication_code = trim($_POST['authentication_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'none';
    $ecocash_number = trim($_POST['ecocash_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $role = $_POST['role'] ?? 'affiliate';
    $password = $_POST['password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name required.';
    if ($phone === '') $errors[] = 'Phone required.';

    // Optional password change
    $passwordSql = '';
    $passwordParams = [];
    if ($password !== '') {
        $passwordSql = ", password = :password";
        $passwordParams[':password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    // Handle file upload if provided
    $uploadPath = $user['tax_clearance_proof'];
    if (isset($_FILES['tax_clearance_proof']) && $_FILES['tax_clearance_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['tax_clearance_proof']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Tax clearance proof must be PDF or JPG/PNG.';
        } else {
            $ext = pathinfo($_FILES['tax_clearance_proof']['name'], PATHINFO_EXTENSION);
            $fname = uniqid('clear_') . '.' . $ext;
            $destDir = __DIR__ . '/../uploads/clearance_docs/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $dest = $destDir . $fname;
            if (move_uploaded_file($_FILES['tax_clearance_proof']['tmp_name'], $dest)) {
                $uploadPath = 'uploads/clearance_docs/' . $fname;
            } else {
                $errors[] = 'Failed to save uploaded file.';
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE affiliates SET
            full_name = :full_name,
            phone_number = :phone,
            email = :email,
            city = :city,
            national_id = :national_id,
            tax_clearance = :tax_clearance,
            authentication_code = :authentication_code,
            tax_clearance_proof = :tax_clearance_proof,
            payment_method = :payment_method,
            ecocash_number = :ecocash_number,
            bank_name = :bank_name,
            account_name = :account_name,
            account_number = :account_number,
            status = :status,
            role = :role
            {$passwordSql}
            WHERE id = :id
        ";
        $params = [
            ':full_name'=>$full_name, ':phone'=>$phone, ':email'=>$email,
            ':city'=>$city, ':national_id'=>$national_id, ':tax_clearance'=>$tax_clearance,
            ':authentication_code'=>$authentication_code, ':tax_clearance_proof'=>$uploadPath,
            ':payment_method'=>$payment_method, ':ecocash_number'=>$ecocash_number,
            ':bank_name'=>$bank_name, ':account_name'=>$account_name, ':account_number'=>$account_number,
            ':status'=>$status, ':role'=>$role, ':id'=>$id
        ];
        $params = array_merge($params, $passwordParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $success = "Affiliate updated successfully.";
        // refresh user data
        $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Affiliate - <?=htmlspecialchars($user['affiliate_id'])?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/edit_affiliate.css">
</head>
<body>

<div class="edit-container">
    <a href="affiliates.php" class="back-link">Back to Affiliates</a>

    <div class="edit-card">
        <div class="card-header">
            <div class="affiliate-id-badge"><?=htmlspecialchars($user['affiliate_id'])?></div>
            <h1 class="page-title">Edit Affiliate</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <?php foreach($errors as $e): ?>
                <div class="message error"><?=htmlspecialchars($e)?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="message success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <!-- Personal Information Section -->
            <div class="form-section">
                <div class="section-title">Personal Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?=htmlspecialchars($user['full_name'])?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" value="<?=htmlspecialchars($user['phone_number'])?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?=htmlspecialchars($user['email'])?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?=htmlspecialchars($user['city'])?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="national_id">National ID</label>
                        <input type="text" id="national_id" name="national_id" value="<?=htmlspecialchars($user['national_id'])?>">
                    </div>
                </div>
            </div>

            <!-- Tax Information Section -->
            <div class="form-section">
                <div class="section-title">Tax Information</div>
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="tax_clearance" name="tax_clearance" value="1" <?=($user['tax_clearance']? 'checked':'')?>>
                    <label for="tax_clearance">Has Tax Clearance</label>
                </div>
                <div class="form-group">
                    <label for="authentication_code">Authentication Code</label>
                    <input type="text" id="authentication_code" name="authentication_code" value="<?=htmlspecialchars($user['authentication_code'])?>">
                </div>
                <div class="form-group file-upload-wrapper">
                    <label for="tax_clearance_proof">Tax Clearance Proof</label>
                    <input type="file" id="tax_clearance_proof" name="tax_clearance_proof" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="hint-text">Leave blank to keep current file. Accepted: PDF, JPG, PNG</div>
                    <?php if($user['tax_clearance_proof']): ?>
                        <a href="/<?=htmlspecialchars($user['tax_clearance_proof'])?>" target="_blank" class="current-file">
                            View Current Proof
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Information Section -->
            <div class="form-section">
                <div class="section-title">Payment Information</div>
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method">
                        <option value="none" <?=($user['payment_method']=='none'?'selected':'')?>>None</option>
                        <option value="ecocash" <?=($user['payment_method']=='ecocash'?'selected':'')?>>EcoCash</option>
                        <option value="bank" <?=($user['payment_method']=='bank'?'selected':'')?>>Bank Transfer</option>
                    </select>
                </div>

                <div class="payment-fields" id="ecocash-fields">
                    <div class="form-group">
                        <label for="ecocash_number">EcoCash Number</label>
                        <input type="text" id="ecocash_number" name="ecocash_number" value="<?=htmlspecialchars($user['ecocash_number'])?>" placeholder="e.g., 0771234567">
                    </div>
                </div>

                <div class="payment-fields" id="bank-fields">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" value="<?=htmlspecialchars($user['bank_name'])?>" placeholder="e.g., ABC Bank">
                        </div>
                        <div class="form-group">
                            <label for="account_name">Account Name</label>
                            <input type="text" id="account_name" name="account_name" value="<?=htmlspecialchars($user['account_name'])?>" placeholder="Account holder name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="account_number">Account Number</label>
                        <input type="text" id="account_number" name="account_number" value="<?=htmlspecialchars($user['account_number'])?>" placeholder="Bank account number">
                    </div>
                </div>
            </div>

            <!-- Account Settings Section -->
            <div class="form-section">
                <div class="section-title">Account Settings</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Account Status</label>
                        <select id="status" name="status">
                            <option value="active" <?=($user['status']=='active'?'selected':'')?>>Active</option>
                            <option value="suspended" <?=($user['status']=='suspended'?'selected':'')?>>Suspended</option>
                            <option value="deleted" <?=($user['status']=='deleted'?'selected':'')?>>Deleted</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="role">User Role</label>
                        <select id="role" name="role">
                            <option value="affiliate" <?=($user['role']=='affiliate'?'selected':'')?>>Affiliate</option>
                            <option value="admin" <?=($user['role']=='admin'?'selected':'')?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">New Password (Optional)</label>
                    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                    <div class="hint-text">Only fill this field if you want to change the password</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='affiliates.php'">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle payment method conditional fields
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('payment_method');
    const ecocashFields = document.getElementById('ecocash-fields');
    const bankFields = document.getElementById('bank-fields');

    function updatePaymentFields() {
        const method = paymentMethod.value;
        ecocashFields.classList.remove('active');
        bankFields.classList.remove('active');

        if (method === 'ecocash') {
            ecocashFields.classList.add('active');
        } else if (method === 'bank') {
            bankFields.classList.add('active');
        }
    }

    paymentMethod.addEventListener('change', updatePaymentFields);
    updatePaymentFields(); // Initialize on page load
});
</script>

</body>
</html>