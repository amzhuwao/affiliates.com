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
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name required.';
    // handle optional password change
    $passwordSql = '';
    $params = [':full_name'=>$full_name, ':email'=>$email, ':city'=>$city, ':id'=>$_SESSION['user_id']];
    if ($password !== '') {
        $passwordSql = ", password = :password";
        $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    // handle file upload
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
        $sql = "UPDATE affiliates SET full_name = :full_name, email = :email, city = :city, tax_clearance_proof = :tax_clearance_proof {$passwordSql} WHERE id = :id";
        $params[':tax_clearance_proof'] = $uploadPath;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $success = 'Profile updated.';
        // refresh
        $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['full_name'] = $user['full_name'];
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>My Profile</title></head><body>
<h2>My Profile</h2>
<?php foreach($errors as $e) echo "<p style='color:red'>$e</p>"; ?>
<?php if($success) echo "<p style='color:green'>$success</p>"; ?>
<form method="post" enctype="multipart/form-data">
<label>Full name: <input name="full_name" value="<?=htmlspecialchars($user['full_name'])?>"></label><br>
<label>Email: <input name="email" value="<?=htmlspecialchars($user['email'])?>"></label><br>
<label>City: <input name="city" value="<?=htmlspecialchars($user['city'])?>"></label><br>
<label>Upload tax clearance proof: <input type="file" name="tax_clearance_proof"></label><br>
<?php if($user['tax_clearance_proof']): ?><a href="/<?=htmlspecialchars($user['tax_clearance_proof'])?>" target="_blank">Current proof</a><br><?php endif; ?>
<label>New password (leave blank to keep): <input type="password" name="password"></label><br>
<button type="submit">Save</button>
</form>
<p><a href="dashboard.php">Back to dashboard</a></p>
<p><a href="delete_account.php" style="color:red">Delete my account</a></p>
</body></html>