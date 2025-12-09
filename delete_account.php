<?php
// public/delete_account.php (affiliate soft delete)
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE affiliates SET status = 'deleted' WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    session_unset(); session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Delete Account</title><link rel="icon" type="image/png" href="branding/anako favicon.png"></head><body>
<h2>Delete my account</h2>
<p>This will mark your account as deleted. This is reversible by an admin.</p>
<form method="post"><button type="submit" onclick="return confirm('Are you sure?')">Confirm Delete</button></form>
<p><a href="profile.php">Cancel</a></p>
</body></html>