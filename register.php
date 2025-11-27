<?php
// public/register.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = '';

if (isPost()) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $city = trim($_POST['city'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $tax_clearance = isset($_POST['tax_clearance']) ? 1 : 0;
    $authentication_code = trim($_POST['authentication_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'none';
    $ecocash_number = trim($_POST['ecocash_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');

    // Basic validation
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($phone === '') $errors[] = 'Phone is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

    // Check unique phone
    $stmt = $db->prepare("SELECT id FROM affiliates WHERE phone_number = :phone LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    if ($stmt->fetch()) $errors[] = 'Phone number already registered.';

    // Handle file upload (tax clearance proof)
    $uploadPath = null;
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
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $dest = $destDir . $fname;
            if (!move_uploaded_file($_FILES['tax_clearance_proof']['tmp_name'], $dest)) {
                $errors[] = 'Failed to save uploaded file.';
            } else {
                $uploadPath = 'uploads/clearance_docs/' . $fname;
            }
        }
    }

    if (empty($errors)) {
        $affiliateId = generateAffiliateId($db);
        $refLink = buildReferralLink($affiliateId);
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO affiliates
            (affiliate_id, full_name, phone_number, email, password, city, national_id, tax_clearance,
             authentication_code, tax_clearance_proof, payment_method, ecocash_number, bank_name,
             account_name, account_number, referral_link)
            VALUES
            (:affiliate_id, :full_name, :phone, :email, :password, :city, :national_id, :tax_clearance,
             :authentication_code, :tax_clearance_proof, :payment_method, :ecocash_number, :bank_name,
             :account_name, :account_number, :referral_link)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':affiliate_id' => $affiliateId,
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':email' => $email,
            ':password' => $passwordHash,
            ':city' => $city,
            ':national_id' => $national_id,
            ':tax_clearance' => $tax_clearance,
            ':authentication_code' => $authentication_code,
            ':tax_clearance_proof' => $uploadPath,
            ':payment_method' => $payment_method,
            ':ecocash_number' => $ecocash_number,
            ':bank_name' => $bank_name,
            ':account_name' => $account_name,
            ':account_number' => $account_number,
            ':referral_link' => $refLink
        ]);

        $success = "Registered successfully. Your Affiliate ID: $affiliateId";
    }
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Affiliate Registration</title></head>
<body>
<h2>Affiliate Registration</h2>
<?php if ($errors): foreach ($errors as $e) echo "<p style='color:red'>$e</p>"; endif; ?>
<?php if ($success) echo "<p style='color:green'>$success</p>"; ?>
<form method="post" enctype="multipart/form-data">
  <label>Full name: <input name="full_name" required></label><br>
  <label>Phone: <input name="phone_number" required></label><br>
  <label>Email: <input name="email"></label><br>
  <label>Password: <input type="password" name="password" required></label><br>
  <label>Confirm password: <input type="password" name="password_confirm" required></label><br>
  <label>City: <input name="city"></label><br>
  <label>National ID: <input name="national_id"></label><br>
  <label>Tax clearance: <input type="checkbox" name="tax_clearance" value="1"></label><br>
  <label>Authentication code: <input name="authentication_code"></label><br>
  <label>Tax clearance proof (PDF/JPG/PNG): <input type="file" name="tax_clearance_proof"></label><br>
  <label>Payment method:
    <select name="payment_method">
      <option value="none">None</option>
      <option value="ecocash">EcoCash</option>
      <option value="bank">Bank</option>
    </select>
  </label><br>
  <label>EcoCash number: <input name="ecocash_number"></label><br>
  <label>Bank name: <input name="bank_name"></label><br>
  <label>Account name: <input name="account_name"></label><br>
  <label>Account number: <input name="account_number"></label><br>
  <button type="submit">Register</button>
</form>
<p><a href="login.php">Login</a></p>
</body>
</html>
