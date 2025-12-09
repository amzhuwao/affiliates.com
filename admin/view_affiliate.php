<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) {
    exit("Affiliate ID missing");
}

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) exit("Affiliate not found");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Affiliate - <?= htmlspecialchars($user['affiliate_id']) ?></title>
    <link rel="icon" type="image/png" href="../branding/anako favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/view_affiliate.css">
</head>

<body>

    <div class="view-container">
        <!-- Logo and Back Link -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <img src="../branding/ANAKO LOGO.png" alt="ANAKO Logo" style="height: 40px; width: auto;">
            <a href="affiliates.php" class="back-link">Back to Affiliates</a>
        </div>

        <div class="details-card">
            <div class="card-header">
                <div class="affiliate-id-badge"><?= htmlspecialchars($user['affiliate_id']) ?></div>
                <h1 class="page-title">Affiliate Details</h1>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Full Name
                    </div>
                    <div class="info-value"><?= htmlspecialchars($user['full_name']) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Phone Number
                    </div>
                    <div class="info-value"><?= htmlspecialchars($user['phone_number']) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email Address
                    </div>
                    <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Program
                    </div>
                    <div class="info-value">
                        <?php
                        $programMap = ['GS' => 'GetSolar', 'TV' => 'TechVouch'];
                        $programLabel = $programMap[$user['program']] ?? ($user['program'] ?: '—');
                        ?>
                        <?= htmlspecialchars($programLabel) ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        City
                    </div>
                    <div class="info-value"><?= htmlspecialchars($user['city']) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Account Status
                    </div>
                    <div class="info-value">
                        <span class="status-badge <?= strtolower($user['status']) ?>">
                            <?= htmlspecialchars($user['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($user['tax_clearance_proof']): ?>
                <div class="document-section">
                    <div class="document-title">Tax Clearance Documentation</div>
                    <a href="/<?= htmlspecialchars($user['tax_clearance_proof']) ?>" target="_blank" class="document-link">
                        View Tax Clearance Proof
                    </a>
                </div>
            <?php else: ?>
                <div class="document-section">
                    <div class="document-title">Tax Clearance Documentation</div>
                    <p class="no-document">No tax clearance proof uploaded</p>
                </div>
            <?php endif; ?>

            <div class="actions-section">
                <a href="affiliates.php" class="btn">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </div>

</body>

</html>