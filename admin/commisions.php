<?php
// admin/commissions.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$rows = $db->query("SELECT c.*, a.affiliate_id AS aff_code, a.full_name AS aff_name, q.customer_name
                    FROM commissions c
                    JOIN affiliates a ON c.affiliate_id = a.id
                    JOIN quotations q ON c.quotation_id = q.id
                    ORDER BY c.created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commissions Management - Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/affiliates.css">
<link rel="stylesheet" href="../css/premium_table.css">
</head>
<body>

<div class="affiliates-container">
    <div class="affiliates-card">
        <!-- Back navigation -->
        <a href="../dashboard.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Dashboard
        </a>

        <!-- Page header -->
        <div class="page-header">
            <h1 class="page-title">Commissions Management</h1>
            <p class="page-subtitle">View and manage all commission records from affiliates</p>
        </div>

        <!-- Commissions table -->
        <?php if (!empty($rows)): ?>
        <div style="overflow-x: auto; margin: 0 -24px; padding: 0 24px;">
            <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                <thead>
                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">#</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Affiliate</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Quotation</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Gross</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Withholding</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Net</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Created</th>
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
                        <td data-label="Quotation" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary);">
                            <strong style="font-weight: 600; display: block; margin-bottom: 4px;">#<?=htmlspecialchars($r['quotation_id'])?></strong>
                            <span style="font-size: 13px; color: var(--text-secondary); font-weight: 500;"><?=htmlspecialchars($r['customer_name']??'N/A')?></span>
                        </td>
                        <td data-label="Gross" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;">$<?=htmlspecialchars(number_format($r['gross_commission'], 2))?></td>
                        <td data-label="Withholding" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;">$<?=htmlspecialchars(number_format($r['withholding_tax'], 2))?></td>
                        <td data-label="Net" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;">$<?=htmlspecialchars(number_format($r['net_commission'], 2))?></td>
                        <td data-label="Status" style="padding: 20px 16px; font-size: 14px;">
                            <span class="status-badge <?=htmlspecialchars(strtolower($r['payment_status']))?>">
                                <?=ucfirst(htmlspecialchars($r['payment_status']))?>
                            </span>
                        </td>
                        <td data-label="Created" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars(date('M d, Y', strtotime($r['created_at'])))?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.3;">💰</div>
            <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No commissions found</p>
            <p style="font-size: 14px; margin-bottom: 0;">Commission records will appear here when affiliates complete quotations</p>
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
