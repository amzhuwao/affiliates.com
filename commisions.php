<?php
// public/commissions.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

// Fetch user info
$stmtUser = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $_SESSION['user_id']]);
$user = $stmtUser->fetch();

// Fetch commission totals
$stmtTotal = $db->prepare("SELECT
    COALESCE(SUM(net_commission), 0) AS total_net,
    SUM(CASE WHEN payment_status = 'pending' THEN net_commission ELSE 0 END) AS pending_net,
    SUM(CASE WHEN payment_status = 'paid' THEN net_commission ELSE 0 END) AS paid_net
    FROM commissions
    WHERE affiliate_id = :aid");
$stmtTotal->execute([':aid' => $_SESSION['user_id']]);
$totals = $stmtTotal->fetch();

// Fetch commission details
$stmt = $db->prepare("SELECT c.*, q.customer_name, q.customer_phone
    FROM commissions c
    JOIN quotations q ON c.quotation_id = q.id
    WHERE c.affiliate_id = :aid
    ORDER BY c.created_at DESC");
$stmt->execute([':aid' => $_SESSION['user_id']]);
$list = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Commissions - Affiliates Portal</title>
    <link rel="icon" type="image/png" href="branding/anako favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=2.0">
</head>

<body>
    <div class="dashboard-container">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1 class="mobile-title">My Commissions</h1>
        </div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="branding/ANAKO LOGO.png" alt="ANAKO" class="brand-logo">
            </div>
            <div class="sidebar-header">
                <h2 class="sidebar-title">Affiliate Portal</h2>
                <p class="sidebar-subtitle">Your Dashboard</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="quotations.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        <path d="M9 12h6m-6 4h6"></path>
                    </svg>
                    <span>My Quotations</span>
                </a>

                <a href="commisions.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>My Commissions</span>
                </a>

                <a href="profile.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p class="user-role"><?php if ($user['program'] = "TV") {
                                                    echo "TechVouch";
                                                } else {
                                                    echo "GetSolar";
                                                }; ?></p>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="content-header">
                <div class="header-text">
                    <h1 class="page-title">My Commissions</h1>
                    <p class="page-subtitle">Track your earnings and payment status</p>
                </div>
            </div>

            <!-- Stats Grid - Commission Summary -->
            <div class="stats-grid">
                <div class="stat-card" data-stat="total">
                    <div class="stat-icon total">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Earned</p>
                        <h3 class="stat-value" data-target="<?php echo number_format($totals['total_net'], 0); ?>">$<?php echo number_format($totals['total_net'], 2); ?></h3>
                    </div>
                </div>

                <div class="stat-card" data-stat="pending">
                    <div class="stat-icon suspended">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-value" data-target="<?php echo number_format($totals['pending_net'], 0); ?>">$<?php echo number_format($totals['pending_net'], 2); ?></h3>
                    </div>
                </div>

                <div class="stat-card" data-stat="paid">
                    <div class="stat-icon active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Paid</p>
                        <h3 class="stat-value" data-target="<?php echo number_format($totals['paid_net'], 0); ?>">$<?php echo number_format($totals['paid_net'], 2); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Commission Details Table -->
            <div class="quick-actions">
                <h2 class="section-title">Commission History</h2>

                <?php if (count($list) > 0): ?>
                    <div style="overflow-x: auto; margin: 0 -24px; padding: 0 24px;">
                        <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                            <thead>
                                <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">#</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Customer</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Phone</th>
                                    <th style="padding: 18px 16px; text-align: right; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Gross</th>
                                    <th style="padding: 18px 16px; text-align: right; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Tax</th>
                                    <th style="padding: 18px 16px; text-align: right; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Net</th>
                                    <th style="padding: 18px 16px; text-align: center; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $row): ?>
                                    <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border); transition: all 0.3s ease;">
                                        <td style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td style="padding: 20px 16px; font-size: 14px; color: var(--text-secondary); font-weight: 500;"><?php echo htmlspecialchars($row['customer_phone']); ?></td>
                                        <td style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500; text-align: right;">$<?php echo number_format($row['gross_commission'], 2); ?></td>
                                        <td style="padding: 20px 16px; font-size: 14px; color: var(--error); font-weight: 500; text-align: right;">$<?php echo number_format($row['withholding_tax'], 2); ?></td>
                                        <td style="padding: 20px 16px; font-size: 14px; color: var(--success); font-weight: 700; text-align: right;">$<?php echo number_format($row['net_commission'], 2); ?></td>
                                        <td style="padding: 20px 16px; font-size: 14px; text-align: center;">
                                            <?php
                                            $status = htmlspecialchars($row['payment_status']);
                                            $statusClass = '';
                                            if ($status === 'pending') $statusClass = 'suspended';
                                            elseif ($status === 'paid') $statusClass = 'active';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 20px 16px; font-size: 13px; color: var(--text-secondary); font-weight: 500;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.25;">💰</div>
                        <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">No commissions yet</p>
                        <p style="font-size: 14px; margin-bottom: 0;">Commissions will appear here when quotations are converted to sales</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info Box -->
            <div style="margin-top: 32px; padding: 20px 24px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; border-left: 3px solid var(--accent);">
                <div style="display: flex; gap: 16px; align-items: start;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: var(--accent); flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">About your commissions</p>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 8px;">
                            <strong style="color: var(--text-primary);">Gross:</strong> Total commission before tax deductions<br>
                            <strong style="color: var(--text-primary);">Tax:</strong> Withholding tax deducted from gross amount<br>
                            <strong style="color: var(--text-primary);">Net:</strong> Final amount you'll receive after tax
                        </p>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                            Commissions are marked as <strong style="color: var(--warning);">Pending</strong> until payment is processed, then updated to <strong style="color: var(--success);">Paid</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    menuToggle.classList.toggle('active');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                            sidebar.classList.remove('active');
                            menuToggle.classList.remove('active');
                        }
                    }
                });
            }

            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.background = 'var(--bg-card)';
                    this.style.transform = 'scale(1.01)';
                    this.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
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