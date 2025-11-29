<?php
// admin/export_csv.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE full_name LIKE :s OR affiliate_id LIKE :s OR phone_number LIKE :s";
    $params[':s'] = "%{$search}%";
}

$sql = "SELECT affiliate_id, full_name, phone_number, email, city, status, created_at FROM affiliates $where ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'affiliates_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
// header row
fputcsv($out, ['Affiliate ID','Full Name','Phone','Email','City','Status','Created At']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['affiliate_id'],
        $r['full_name'],
        $r['phone_number'],
        $r['email'],
        $r['city'],
        $r['status'],
        $r['created_at'],
    ]);
}
fclose($out);
exit;
