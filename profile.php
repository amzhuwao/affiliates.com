<?php
// public/profile.php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
requireLogin();

$errors = [];
$success = '';

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name required.';
    if ($phone_number === '') $errors[] = 'Phone number required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    // Optional password update
    $passwordSql = '';
    $params = [
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone_number' => $phone_number,
        ':city' => $city,
        ':id' => $_SESSION['user_id']
    ];
    if ($password !== '') {
        $passwordSql = ", password = :password";
        $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    // File upload
    $uploadPath = $user['tax_clearance_proof'];
    if (isset($_FILES['tax_clearance_proof']) && $_FILES['tax_clearance_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['tax_clearance_proof']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Tax clearance proof must be PDF or JPG/PNG.';
        } else {
            $ext = pathinfo($_FILES['tax_clearance_proof']['name'], PATHINFO_EXTENSION);
            $fname = uniqid('clear_') . '.' . $ext;
            $destDir = __DIR__ . '/../uploads/clearance_docs/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $dest = $destDir . $fname;

            if (move_uploaded_file($_FILES['tax_clearance_proof']['tmp_name'], $dest)) {
                $uploadPath = 'uploads/clearance_docs/' . $fname;
            } else {
                $errors[] = 'Failed to save uploaded file.';
            }
        }
    }

    // Update profile
    if (empty($errors)) {
        $sql = "UPDATE affiliates SET 
                full_name = :full_name,
                email = :email,
                phone_number = :phone_number,
                city = :city,
                tax_clearance_proof = :tax_clearance_proof
                {$passwordSql}
                WHERE id = :id";
        $params[':tax_clearance_proof'] = $uploadPath;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $success = 'Profile updated successfully!';

        // Refresh session data
        $_SESSION['full_name'] = $full_name;

        // Refresh user data
        $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Affiliates Portal</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
<div class="dashboard-container">

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <h1 class="page-title">My Profile</h1>
<a href="dashboard.php" class="btn-submit" style="float:right;margin-top:-50px;">
    ⬅ Dashboard
</a>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endforeach; ?>

        <div class="form-card">
        <form method="post" enctype="multipart/form-data" id="profileForm">

            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-input"
                       value="<?= htmlspecialchars($user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-input"
                       value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone_number" class="form-input"
                       value="<?= htmlspecialchars($user['phone_number']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-input"
                       value="<?= htmlspecialchars($user['city']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Tax Clearance Proof</label>
                <input type="file" name="tax_clearance_proof" class="form-input"
                       accept=".pdf,.jpg,.jpeg,.png">
                <?php if ($user['tax_clearance_proof']): ?>
                    <a href="/<?= htmlspecialchars($user['tax_clearance_proof']); ?>" target="_blank" class="current-file">
                        View Current Document
                    </a>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-input"
                       placeholder="Leave blank to keep current password">
            </div>

            <button type="submit" class="btn-submit">Save Changes</button>
        </form>
        </div>

        <div class="danger-zone">
            <h3>Danger Zone</h3>
            <p>Deleting your account will disable your access. Admin can restore it later.</p>
            <a href="delete_account.php" class="btn-danger">Delete My Account</a>
        </div>

    </main>
</div>
</body>
</html>
