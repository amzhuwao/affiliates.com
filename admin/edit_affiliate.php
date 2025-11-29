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
<!doctype html><html><head><meta charset="utf-8"><title>Edit Affiliate</title></head><body>
<h2>Edit Affiliate <?=$user['affiliate_id']?></h2>
<?php foreach($errors as $e) echo "<p style='color:red'>$e</p>"; ?>
<?php if($success) echo "<p style='color:green'>$success</p>"; ?>
<form method="post" enctype="multipart/form-data">
<label>Full name: <input name="full_name" value="<?=htmlspecialchars($user['full_name'])?>"></label><br>
<label>Phone: <input name="phone_number" value="<?=htmlspecialchars($user['phone_number'])?>"></label><br>
<label>Email: <input name="email" value="<?=htmlspecialchars($user['email'])?>"></label><br>
<label>City: <input name="city" value="<?=htmlspecialchars($user['city'])?>"></label><br>
<label>National ID: <input name="national_id" value="<?=htmlspecialchars($user['national_id'])?>"></label><br>
<label>Tax clearance: <input type="checkbox" name="tax_clearance" value="1" <?=($user['tax_clearance']? 'checked':'')?>></label><br>
<label>Authentication code: <input name="authentication_code" value="<?=htmlspecialchars($user['authentication_code'])?>"></label><br>
<label>Tax clearance proof (leave blank to keep current): <input type="file" name="tax_clearance_proof"></label><br>
<?php if($user['tax_clearance_proof']): ?><a href="/<?=htmlspecialchars($user['tax_clearance_proof'])?>" target="_blank">Current proof</a><br><?php endif; ?>
<label>Payment method:
<select name="payment_method">
<option value="none" <?=($user['payment_method']=='none'?'selected':'')?>>None</option>
<option value="ecocash" <?=($user['payment_method']=='ecocash'?'selected':'')?>>EcoCash</option>
<option value="bank" <?=($user['payment_method']=='bank'?'selected':'')?>>Bank</option>
</select></label><br>
<label>EcoCash number: <input name="ecocash_number" value="<?=htmlspecialchars($user['ecocash_number'])?>"></label><br>
<label>Bank name: <input name="bank_name" value="<?=htmlspecialchars($user['bank_name'])?>"></label><br>
<label>Account name: <input name="account_name" value="<?=htmlspecialchars($user['account_name'])?>"></label><br>
<label>Account number: <input name="account_number" value="<?=htmlspecialchars($user['account_number'])?>"></label><br>
<label>Status:
<select name="status">
  <option value="active" <?=($user['status']=='active'?'selected':'')?>>Active</option>
  <option value="suspended" <?=($user['status']=='suspended'?'selected':'')?>>Suspended</option>
  <option value="deleted" <?=($user['status']=='deleted'?'selected':'')?>>Deleted</option>
</select></label><br>
<label>Role:
<select name="role">
  <option value="affiliate" <?=($user['role']=='affiliate'?'selected':'')?>>Affiliate</option>
  <option value="admin" <?=($user['role']=='admin'?'selected':'')?>>Admin</option>
</select></label><br>
<label>New password (leave blank to keep current): <input type="password" name="password"></label><br>
<button type="submit">Save</button>
</form>
<p><a href="/admin/affiliates.php">Back to list</a></p>
</body></html>