<?php
// admin/view_quotation.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) exit("Missing id");

$stmt = $db->prepare("SELECT q.*, a.full_name AS aff_name, a.affiliate_id AS aff_code, a.tax_clearance
                      FROM quotations q JOIN affiliates a ON q.affiliate_id = a.id WHERE q.id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$q = $stmt->fetch();
if (!$q) exit("Quotation not found");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Quotation #<?=htmlspecialchars($q['id'])?> - Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/affiliates.css">
<link rel="stylesheet" href="../css/view_quotation.css">
</head>
<body>

<div class="affiliates-container">
    <div class="affiliates-card">
        <!-- Back navigation -->
        <a href="quotations.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Quotations
        </a>

        <!-- Page header -->
        <div class="page-header">
            <h1 class="page-title">Quotation #<?=htmlspecialchars($q['id'])?></h1>
            <p class="page-subtitle">Detailed view of quotation information</p>
        </div>

        <!-- Quotation Details Grid -->
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Affiliate
                </div>
                <div class="detail-value">
                    <?=htmlspecialchars($q['aff_code'])?>
                    <div style="font-size: 14px; color: var(--text-secondary); font-weight: 500; margin-top: 4px;">
                        <?=htmlspecialchars($q['aff_name'])?>
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                    Customer
                </div>
                <div class="detail-value">
                    <?=htmlspecialchars($q['customer_name'])?>
                    <div style="font-size: 14px; color: var(--text-secondary); font-weight: 500; margin-top: 4px;">
                        <?=htmlspecialchars($q['customer_phone'])?>
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                    Status
                </div>
                <div class="detail-value">
                    <span class="status-badge <?=htmlspecialchars(strtolower($q['status']))?>">
                        <?=htmlspecialchars($q['status'])?>
                    </span>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M2 12h20"></path>
                    </svg>
                    Estimated Value
                </div>
                <div class="detail-value money">
                    $<?=htmlspecialchars(number_format($q['estimated_value'], 2))?>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Quoted Amount
                </div>
                <div class="detail-value large money">
                    $<?=htmlspecialchars(number_format($q['quoted_amount'], 2))?>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                        <path d="M9 12h6"></path>
                    </svg>
                    Commission Rate
                </div>
                <div class="detail-value">
                    <?=htmlspecialchars($q['commission_rate'] ?? DEFAULT_COMMISSION_RATE)?>%
                </div>
            </div>
        </div>

        <!-- Description Section -->
        <div class="description-section">
            <div class="detail-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Description
            </div>
            <div class="description-text"><?=nl2br(htmlspecialchars($q['description']))?></div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="edit_quotation.php?id=<?=htmlspecialchars($q['id'])?>" class="btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit / Approve
            </a>
            <a href="quotations.php" class="btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>
</div>

</body>
</html>
