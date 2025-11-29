<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// public/new_quotation.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $estimated_value = $_POST['estimated_value'] ?? null;

    if ($customer_name === '') $errors[] = 'Customer name required.';
    if ($customer_phone === '') $errors[] = 'Customer phone required.';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO quotations
            (affiliate_id, customer_name, customer_phone, description, estimated_value, status, created_at)
            VALUES (:aid, :cname, :cphone, :desc, :est, 'pending', NOW())");
        $stmt->execute([
            ':aid' => $_SESSION['user_id'],
            ':cname' => $customer_name,
            ':cphone' => $customer_phone,
            ':desc' => $description,
            ':est' => $estimated_value
        ]);
        $success = "Quotation submitted successfully. Waiting for admin review.";
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>New Quotation</title></head><body>
<h2>Submit Quotation Request</h2>
<?php foreach ($errors as $e) echo "<p style='color:red'>$e</p>"; ?>
<?php if ($success) echo "<p style='color:green'>$success</p>"; ?>
<form method="post">
  <label>Customer Name: <input name="customer_name" required></label><br>
  <label>Customer Phone: <input name="customer_phone" required></label><br>
  <label>Description:<br><textarea name="description" rows="4"></textarea></label><br>
  <label>Estimated Value: <input type="number" step="0.01" name="estimated_value"></label><br>
  <button type="submit">Submit</button>
</form>
<p><a href="dashboard.php">Back</a></p>
</body></html>
