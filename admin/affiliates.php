<?php
// admin/affiliates.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$msg = $_GET['msg'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20; // as requested (option B)

// Build filter
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE full_name LIKE :s OR affiliate_id LIKE :s OR phone_number LIKE :s";
    $params[':s'] = "%{$search}%";
}

// count total
$countSql = "SELECT COUNT(*) as cnt FROM affiliates $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = (int) ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

// fetch page rows
$sql = "SELECT id, affiliate_id, full_name, phone_number, email, referral_link, created_at, status
        FROM affiliates $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Affiliate Management</title>
</head>
<body>

<h2>Affiliate Management</h2>

<?php if ($msg === 'updated'): ?>
<p style="color:green">Affiliate updated successfully.</p>
<?php endif; ?>

<?php if ($msg === 'deleted'): ?>
<p style="color:orange">Affiliate marked as deleted.</p>
<?php endif; ?>

<?php if ($msg === 'reactivated'): ?>
<p style="color:green">Affiliate reactivated successfully.</p>
<?php endif; ?>

<p><a href="/../dashboard.php">⬅ Back to Admin Dashboard</a></p>

<form method="get" style="display:inline-block; margin-right:10px;">
    <input type="text" name="search" placeholder="Search by name, ID or phone" value="<?=htmlspecialchars($search)?>">
    <button type="submit">Search</button>
</form>

<!-- Export current filtered results -->
<a href="export_csv.php?search=<?=urlencode($search)?>" style="margin-left:8px;">Export CSV (filtered)</a>

<br><br>

<table border="1" cellpadding="6">
<thead>
<tr>
    <th>#</th>
    <th>Affiliate ID</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Email</th>
    <th>Created</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php if (empty($rows)): ?>
<tr><td colspan="8">No affiliates found.</td></tr>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?=htmlspecialchars($r['id'])?></td>
    <td><?=htmlspecialchars($r['affiliate_id'])?></td>
    <td><?=htmlspecialchars($r['full_name'])?></td>
    <td><?=htmlspecialchars($r['phone_number'])?></td>
    <td><?=htmlspecialchars($r['email'])?></td>
    <td><?=htmlspecialchars($r['created_at'])?></td>
    <td><?=htmlspecialchars($r['status'])?></td>
    <td>
        <a href="view_affiliate.php?id=<?=htmlspecialchars($r['id'])?>">View</a> |
        <a href="edit_affiliate.php?id=<?=htmlspecialchars($r['id'])?>">Edit</a> |
        <?php if ($r['status'] === 'active'): ?>
            <a href="toggle_status.php?id=<?=htmlspecialchars($r['id'])?>&to=suspended"
               onclick="return confirm('Suspend this affiliate?');">Suspend</a>
        <?php else: ?>
            <a href="toggle_status.php?id=<?=htmlspecialchars($r['id'])?>&to=active"
               onclick="return confirm('Reactivate this affiliate?');">Reactivate</a>
        <?php endif; ?>
         | 
        <a href="delete_affiliate.php?id=<?=htmlspecialchars($r['id'])?>" onclick="return confirm('Mark as deleted?');">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Pagination -->
<?php if ($pages > 1): ?>
    <p>
    <?php if ($page > 1): ?>
        <a href="?search=<?=urlencode($search)?>&page=<?=($page-1)?>">« Prev</a>
    <?php endif; ?>

    Page <?= $page ?> of <?= $pages ?>

    <?php if ($page < $pages): ?>
        <a href="?search=<?=urlencode($search)?>&page=<?=($page+1)?>">Next »</a>
    <?php endif; ?>
    </p>
<?php endif; ?>

</body>
</html>
