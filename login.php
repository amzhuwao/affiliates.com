<?php
// public/login.php
require_once __DIR__ . '/includes/db.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '' || $password === '') $errors[] = 'Phone and password required.';

    if (empty($errors)) {
        $stmt = $db->prepare("SELECT * FROM affiliates WHERE phone_number = :phone LIMIT 1");
        $stmt->execute([':phone' => $phone]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['affiliate_id'] = $user['affiliate_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
<h2>Login</h2>
<?php foreach ($errors as $e) echo "<p style='color:red'>$e</p>"; ?>
<form method="post">
  <label>Phone: <input name="phone_number" required></label><br>
  <label>Password: <input type="password" name="password" required></label><br>
  <button type="submit">Login</button>
</form>
<p><a href="register.php">Register</a></p>
</body>
</html>
