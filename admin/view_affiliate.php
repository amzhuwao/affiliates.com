<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) { exit("Affiliate ID missing"); }

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) exit("Affiliate not found");
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>View Affiliate</title></head>
<body>

<h2>Affiliate Details - <?=$user['affiliate_id']?></h2>

<p><strong>Name:</strong> <?=htmlspecialchars($user['full_name'])?></p>
<p><strong>Phone:</strong> <?=htmlspecialchars($user['phone_number'])?></p>
<p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
<p><strong>City:</strong> <?=htmlspecialchars($user['city'])?></p>
<p><strong>Status:</strong> <?=htmlspecialchars($user['status'])?></p>

<?php if($user['tax_clearance_proof']): ?>
<p><strong>Tax clearance proof:</strong> 
   <a href="/<?=htmlspecialchars($user['tax_clearance_proof'])?>" target="_blank">View file</a>
</p>
<?php endif; ?>

<p><a href="affiliates.php">Back</a></p>

</body>
</html>
