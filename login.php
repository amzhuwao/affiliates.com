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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Affiliates Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="logo">
                <h1>Welcome Back</h1>
                <p>Sign in to access your affiliate dashboard</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $e): ?>
                        <?php echo htmlspecialchars($e); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <div class="input-wrapper">
                        <input 
                            type="tel" 
                            id="phone_number" 
                            name="phone_number" 
                            placeholder="e.g., +263771234567"
                            required
                            autocomplete="tel"
                            pattern="^\+?[0-9]+$"
                        >
                        <div class="input-error" id="phone-error">
                            Please enter a valid phone number (+ followed by numbers only)
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>

                <button type="submit" class="btn">Sign In</button>
            </form>

            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Create one</a></p>
            </div>
        </div>
    </div>

    <script>
        const phoneInput = document.getElementById('phone_number');
        const phoneError = document.getElementById('phone-error');
        const form = document.querySelector('form');
        
        function validatePhoneNumber(value) {
            const phoneRegex = /^\+?[0-9]+$/;
            return phoneRegex.test(value);
        }
        
        phoneInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            
            if (value && !validatePhoneNumber(value)) {
                phoneInput.classList.add('invalid');
                phoneError.classList.add('show');
            } else {
                phoneInput.classList.remove('invalid');
                phoneError.classList.remove('show');
            }
        });
        
        phoneInput.addEventListener('blur', function(e) {
            const value = e.target.value.trim();
            
            if (value && !validatePhoneNumber(value)) {
                phoneInput.classList.add('invalid');
                phoneError.classList.add('show');
            }
        });
        
        form.addEventListener('submit', function(e) {
            const phoneValue = phoneInput.value.trim();
            
            if (!validatePhoneNumber(phoneValue)) {
                e.preventDefault();
                phoneInput.classList.add('invalid');
                phoneError.classList.add('show');
                phoneInput.focus();
                return false;
            }
        });
        
        phoneInput.addEventListener('focus', function() {
            if (phoneInput.classList.contains('invalid')) {
                phoneError.classList.add('show');
            }
        });
    </script>
</body>
</html>
