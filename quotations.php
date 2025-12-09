<?php
// public/quotations.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

// Fetch user info
$stmtUser = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $_SESSION['user_id']]);
$user = $stmtUser->fetch();

// Fetch quotations
$stmt = $db->prepare("SELECT * FROM quotations WHERE affiliate_id = :aid ORDER BY created_at DESC");
$stmt->execute([':aid' => $_SESSION['user_id']]);
$rows = $stmt->fetchAll();

// Count quotations by status
$stmtPending = $db->prepare("SELECT COUNT(*) FROM quotations WHERE affiliate_id = :aid AND status = 'pending'");
$stmtPending->execute([':aid' => $_SESSION['user_id']]);
$pendingCount = $stmtPending->fetchColumn();

$stmtApproved = $db->prepare("SELECT COUNT(*) FROM quotations WHERE affiliate_id = :aid AND (status = 'approved' OR status = 'converted')");
$stmtApproved->execute([':aid' => $_SESSION['user_id']]);
$approvedCount = $stmtApproved->fetchColumn();

$stmtRejected = $db->prepare("SELECT COUNT(*) FROM quotations WHERE affiliate_id = :aid AND status = 'declined'");
$stmtRejected->execute([':aid' => $_SESSION['user_id']]);
$rejectedCount = $stmtRejected->fetchColumn();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quotations - Affiliates Portal</title>
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
            <h1 class="mobile-title">My Quotations</h1>
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

                <a href="quotations.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        <path d="M9 12h6m-6 4h6"></path>
                    </svg>
                    <span>My Quotations</span>
                </a>

                <a href="commisions.php" class="nav-item">
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
                    <h1 class="page-title">My Quotations</h1>
                    <p class="page-subtitle">View quotations assigned to you by administrators</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon suspended">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-value"><?php echo $pendingCount; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Approved</p>
                        <h3 class="stat-value"><?php echo $approvedCount; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon deleted">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Rejected</p>
                        <h3 class="stat-value"><?php echo $rejectedCount; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Quotations Table -->
            <div class="quick-actions">
                <h2 class="section-title">All Quotations</h2>

                <?php if (count($rows) > 0): ?>
                    <div style="overflow-x: auto; margin: 0 -24px; padding: 0 24px;">
                        <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                            <thead>
                                <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">#</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Customer</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Phone</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Estimate</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Quoted</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                                    <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border); transition: all 0.3s ease;">
                                        <td data-label="#" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($r['id']); ?></td>
                                        <td data-label="Customer" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($r['customer_name']); ?></td>
                                        <td data-label="Phone" style="padding: 20px 16px; font-size: 14px; color: var(--text-secondary); font-weight: 500;"><?php echo htmlspecialchars($r['customer_phone']); ?></td>
                                        <td data-label="Estimate" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?php echo $r['estimated_value'] ? '$' . number_format($r['estimated_value'], 2) : '-'; ?></td>
                                        <td data-label="Quoted" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;"><?php echo $r['quoted_amount'] ? '$' . number_format($r['quoted_amount'], 2) : '-'; ?></td>
                                        <td data-label="Status" style="padding: 20px 16px; font-size: 14px;">
                                            <?php
                                            $status = htmlspecialchars($r['status']);
                                            $statusClass = '';
                                            if ($status === 'pending') $statusClass = 'suspended';
                                            elseif ($status === 'approved' || $status === 'converted') $statusClass = 'active';
                                            elseif ($status === 'declined') $statusClass = 'deleted';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td data-label="Created" style="padding: 20px 16px; font-size: 13px; color: var(--text-secondary); font-weight: 500;"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.25;">📋</div>
                        <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">No quotations yet</p>
                        <p style="font-size: 14px; margin-bottom: 0;">Quotations assigned to you by administrators will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        /* Mobile responsive table styles */
        @media (max-width: 768px) {

            /* Hide table on overflow container */
            div[style*="overflow-x: auto"] {
                overflow-x: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Hide table header on mobile */
            table thead {
                display: none;
            }

            /* Convert table elements to block */
            table,
            table tbody,
            table tr,
            table td {
                display: block;
                width: 100%;
            }

            /* Style each row as a card */
            table tbody tr {
                margin-bottom: 20px;
                border-radius: 16px !important;
                border: 1px solid var(--border) !important;
                padding: 0;
                background: var(--bg-secondary) !important;
                position: relative;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            /* Add top accent bar to each card */
            table tbody tr::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, var(--accent), var(--accent-dark));
            }

            /* Remove hover effects on mobile */
            table tbody tr:hover {
                transform: none !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
            }

            /* Style first cell (ID) as card header */
            table td:first-child {
                padding: 20px 20px 16px !important;
                margin: 0;
                border-bottom: 1px solid var(--border) !important;
                display: flex !important;
                align-items: center;
                justify-content: center;
                background: var(--bg-card) !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: var(--text-secondary) !important;
            }

            table td:first-child::before {
                content: 'ID #';
                font-size: 12px;
                font-weight: 600;
                color: var(--text-secondary);
                margin-right: 6px;
            }

            /* Style content rows with labels */
            table td:not(:first-child) {
                padding: 16px 20px !important;
                border: none !important;
                display: grid !important;
                grid-template-columns: 100px 1fr;
                gap: 12px;
                align-items: center;
                border-bottom: 1px solid rgba(44, 44, 46, 0.3) !important;
            }

            table td:not(:first-child)::before {
                content: attr(data-label);
                font-size: 11px;
                font-weight: 700;
                color: var(--text-secondary);
                text-transform: uppercase;
                letter-spacing: 0.8px;
                text-align: left;
            }

            /* Last cell should not have border */
            table td:last-child {
                border-bottom: none !important;
            }

            /* Customer name styling */
            table td:nth-child(2) {
                padding-top: 20px !important;
                font-weight: 600 !important;
                font-size: 15px !important;
            }

            /* Status badge alignment */
            table td[data-label="Status"] {
                display: grid !important;
                align-items: center;
                justify-items: start;
            }

            table td[data-label="Status"] .status-badge {
                justify-self: start;
            }
        }
    </style>

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