<?php
// admin/quotations.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$rows = $db->query("SELECT q.*, a.affiliate_id AS aff_code, a.full_name AS aff_name, a.phone_number AS aff_phone
                    FROM quotations q
                    JOIN affiliates a ON q.affiliate_id = a.id
                    ORDER BY q.created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quotations Management - Admin Portal</title>
<link rel="icon" type="image/png" href="../branding/anako favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/affiliates.css">
<link rel="stylesheet" href="../css/premium_table.css">
</head>
<body>

<div class="affiliates-container">
    <div class="affiliates-card">
        <!-- Logo and Back navigation -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <img src="../branding/ANAKO LOGO.png" alt="ANAKO Logo" style="height: 40px; width: auto;">
            <a href="../dashboard.php" class="back-link">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Page header -->
        <div class="page-header">
            <h1 class="page-title">Quotations Management</h1>
            <p class="page-subtitle">View and manage all quotations from affiliates</p>
        </div>

        <!-- Action Button -->
        <div style="margin-bottom: 24px;">
            <a href="../new_quotation.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 24px; background: var(--accent); border: none; border-radius: 12px; font-size: 14px; font-weight: 600; color: var(--bg-primary); text-decoration: none; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 16px rgba(201, 203, 216, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create New Quotation
            </a>
        </div>

        <!-- Quotations table -->
        <?php if (!empty($rows)): ?>
        <div style="overflow-x: auto; margin: 0 -24px; padding: 0 24px;">
            <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                <thead>
                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">#</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Affiliate</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Customer</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Estimate</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Quoted</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Rate %</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border); transition: all 0.3s ease;">
                        <td data-label="#" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars($r['id'])?></td>
                        <td data-label="Affiliate" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary);">
                            <strong style="font-weight: 600; display: block; margin-bottom: 4px;"><?=htmlspecialchars($r['aff_code'])?></strong>
                            <span style="font-size: 13px; color: var(--text-secondary); font-weight: 500;"><?=htmlspecialchars($r['aff_name'])?></span>
                        </td>
                        <td data-label="Customer" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary);">
                            <strong style="font-weight: 600; display: block; margin-bottom: 4px;"><?=htmlspecialchars($r['customer_name'])?></strong>
                            <span style="font-size: 13px; color: var(--text-secondary); font-weight: 500;"><?=htmlspecialchars($r['customer_phone'])?></span>
                        </td>
                        <td data-label="Estimate" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;">$<?=htmlspecialchars(number_format($r['estimated_value'], 2))?></td>
                        <td data-label="Quoted" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;">$<?=htmlspecialchars(number_format($r['quoted_amount'], 2))?></td>
                        <td data-label="Rate %" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars($r['commission_rate'] ?? DEFAULT_COMMISSION_RATE)?>%</td>
                        <td data-label="Status" style="padding: 20px 16px; font-size: 14px;">
                            <span class="status-badge <?=htmlspecialchars(strtolower($r['status']))?>">
                                <?=ucfirst(htmlspecialchars($r['status']))?>
                            </span>
                        </td>
                        <td data-label="Actions" style="padding: 20px 16px;">
                            <div class="actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="view_quotation.php?id=<?=htmlspecialchars($r['id'])?>" class="action-link" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: var(--text-primary); background: var(--bg-card); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid var(--border);">
                                    View
                                </a>
                                <a href="edit_quotation.php?id=<?=htmlspecialchars($r['id'])?>" class="action-link" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: var(--text-primary); background: var(--bg-card); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid var(--border);">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.3;">📋</div>
            <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No quotations found</p>
            <p style="font-size: 14px; margin-bottom: 0;">Quotations will appear here when affiliates submit them</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Add hover effect to table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.background = 'var(--bg-card)';
            this.style.transform = 'scale(1.01)';
            this.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            this.style.zIndex = '1';
        });
        row.addEventListener('mouseleave', function() {
            this.style.background = 'var(--bg-secondary)';
            this.style.transform = 'scale(1)';
            this.style.boxShadow = 'none';
            this.style.zIndex = 'auto';
        });
    });
});
</script>

</body>
</html>
