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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0d0d0f;
            --bg-secondary: #16171a;
            --bg-card: #1c1d21;
            --accent: #c9cbd8;
            --accent-dark: #a8aab5;
            --accent-hover: #e0e1e8;
            --text-primary: #f5f5f7;
            --text-secondary: #8e8e93;
            --border: #2c2c2e;
            --border-light: #3a3a3c;
            --error: #ff453a;
            --success: #30d158;
            --warning: #ff9f0a;
            --info: #0a84ff;
            --shadow-sm: rgba(0, 0, 0, 0.3);
            --shadow-lg: rgba(0, 0, 0, 0.6);
        }

        html {
            overflow-x: hidden;
            width: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            width: 100%;
            position: relative;
            overflow-x: hidden;
            padding: 40px 20px;
        }

        /* Animated background effects */
        body::before {
            content: '';
            position: fixed;
            top: -30%;
            right: -15%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(201, 203, 216, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 25s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -25%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(201, 203, 216, 0.03) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 20s ease-in-out infinite reverse;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(-40px, 40px) scale(1.05);
            }
        }

        /* Main container */
        .edit-container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 24px;
        }

        .back-link:hover {
            background: var(--bg-primary);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateX(-4px);
        }

        .back-link::before {
            content: '←';
            font-size: 18px;
        }

        /* Premium card wrapper */
        .edit-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px var(--shadow-lg), 0 0 1px rgba(201, 203, 216, 0.1);
            backdrop-filter: blur(20px);
            animation: slideUp 0.6s ease-out;
            position: relative;
        }

        .edit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0.3;
            border-radius: 20px 20px 0 0;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header section */
        .card-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--border);
        }

        .affiliate-id-badge {
            display: inline-block;
            padding: 10px 24px;
            background: linear-gradient(135deg, rgba(201, 203, 216, 0.12) 0%, rgba(201, 203, 216, 0.05) 100%);
            border: 1px solid rgba(201, 203, 216, 0.2);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-shadow: 0 0 30px rgba(201, 203, 216, 0.15);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        /* Messages */
        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            animation: messageSlide 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background: rgba(48, 209, 88, 0.08);
            border: 1px solid rgba(48, 209, 88, 0.25);
            color: var(--success);
        }

        .message.error {
            background: rgba(255, 69, 58, 0.08);
            border: 1px solid rgba(255, 69, 58, 0.25);
            color: var(--error);
        }

        .message::before {
            font-size: 20px;
        }

        .message.success::before {
            content: '✓';
        }

        .message.error::before {
            content: '⚠';
        }

        /* Form sections */
        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            width: 3px;
            height: 20px;
            background: var(--accent);
            border-radius: 2px;
        }

        /* Form grid */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
            transition: color 0.2s ease;
        }

        .form-group:focus-within label {
            color: var(--accent);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 15px 18px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-primary);
            font-family: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            font-weight: 500;
        }

        input:hover,
        select:hover {
            border-color: var(--border-light);
        }

        input:focus,
        select:focus {
            border-color: var(--accent);
            background: var(--bg-card);
            box-shadow: 0 0 0 4px rgba(201, 203, 216, 0.08);
            transform: translateY(-1px);
        }

        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.4;
            font-weight: 400;
        }

        /* Checkbox styling */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 24px;
        }

        .checkbox-wrapper:hover {
            border-color: var(--border-light);
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: var(--accent);
        }

        .checkbox-wrapper label {
            margin: 0;
            cursor: pointer;
            text-transform: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: 0;
        }

        /* File upload styling */
        .file-upload-wrapper {
            margin-bottom: 24px;
        }

        .file-input {
            width: 100%;
            padding: 15px 18px;
            background: var(--bg-secondary);
            border: 1.5px dashed var(--border);
            border-radius: 12px;
            font-size: 14px;
            color: var(--text-secondary);
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input:hover {
            border-color: var(--border-light);
            background: var(--bg-card);
        }

        .file-input:focus {
            border-color: var(--accent);
            border-style: solid;
            box-shadow: 0 0 0 4px rgba(201, 203, 216, 0.08);
        }

        .current-file {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 8px 16px;
            background: rgba(10, 132, 255, 0.1);
            border: 1px solid rgba(10, 132, 255, 0.3);
            border-radius: 8px;
            color: var(--info);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .current-file:hover {
            background: rgba(10, 132, 255, 0.15);
            transform: translateY(-2px);
        }

        .current-file::before {
            content: '📄';
            font-size: 16px;
        }

        .hint-text {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
            font-weight: 400;
        }

        /* Payment method conditional fields */
        .payment-fields {
            display: none;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            animation: fadeIn 0.4s ease-out;
        }

        .payment-fields.active {
            display: block;
        }

        /* Button styling */
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
        }

        .btn {
            flex: 1;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--bg-primary);
            box-shadow: 0 4px 16px rgba(201, 203, 216, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background: var(--accent-hover);
            box-shadow: 0 8px 24px rgba(201, 203, 216, 0.25);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1.5px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg-primary);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Status indicators in selects */
        select option {
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 24px 16px;
            }

            .edit-card {
                padding: 32px 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .btn-group {
                flex-direction: column;
            }

            .page-title {
                font-size: 24px;
            }

            .affiliate-id-badge {
                font-size: 14px;
                padding: 8px 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 12px;
            }

            .edit-card {
                padding: 24px 20px;
                border-radius: 16px;
            }

            .card-header {
                margin-bottom: 28px;
                padding-bottom: 24px;
            }

            .page-title {
                font-size: 20px;
            }

            .back-link {
                padding: 8px 16px;
                font-size: 13px;
            }

            body::before,
            body::after {
                width: 400px;
                height: 400px;
            }
        }
    </style>
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