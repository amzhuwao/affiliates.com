<?php
// public/dashboard.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

$isAdmin = ($_SESSION['role'] === 'admin');

// Fetch stats for admin dashboard
if ($isAdmin) {
    $totalStmt = $db->query("SELECT COUNT(*) as total FROM affiliates");
    $totalAffiliates = $totalStmt->fetchColumn();
    
    $activeStmt = $db->query("SELECT COUNT(*) as total FROM affiliates WHERE status = 'active'");
    $activeAffiliates = $activeStmt->fetchColumn();
    
    $suspendedStmt = $db->query("SELECT COUNT(*) as total FROM affiliates WHERE status = 'suspended'");
    $suspendedAffiliates = $suspendedStmt->fetchColumn();
    
    $deletedStmt = $db->query("SELECT COUNT(*) as total FROM affiliates WHERE status = 'deleted'");
    $deletedAffiliates = $deletedStmt->fetchColumn();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdmin ? 'Admin Dashboard' : 'Dashboard'; ?> - Affiliates Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php if ($isAdmin): ?>
        <!-- Admin Dashboard Layout with Sidebar -->
        <div class="dashboard-container">
            <!-- Mobile Header -->
            <div class="mobile-header">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="mobile-title">Admin Panel</h1>
            </div>

            <!-- Sidebar Navigation -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <h2 class="sidebar-logo">Admin Portal</h2>
                    <p class="sidebar-subtitle">Management Dashboard</p>
                </div>

                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="admin/affiliates.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Affiliate Management</span>
                    </a>

                    <a href="admin/export_csv.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Export Data</span>
                    </a>

                    <a href="admin/test_email.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span>Test Email</span>
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                            <p class="user-role">Administrator</p>
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
                        <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
                        <p class="page-subtitle">Here's what's happening with your affiliate network today</p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card" data-stat="total">
                        <div class="stat-icon total">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Affiliates</p>
                            <h3 class="stat-value" data-target="<?php echo $totalAffiliates; ?>">0</h3>
                        </div>
                    </div>

                    <div class="stat-card" data-stat="active">
                        <div class="stat-icon active">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Active</p>
                            <h3 class="stat-value" data-target="<?php echo $activeAffiliates; ?>">0</h3>
                        </div>
                    </div>

                    <div class="stat-card" data-stat="suspended">
                        <div class="stat-icon suspended">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Suspended</p>
                            <h3 class="stat-value" data-target="<?php echo $suspendedAffiliates; ?>">0</h3>
                        </div>
                    </div>

                    <div class="stat-card" data-stat="deleted">
                        <div class="stat-icon deleted">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Deleted</p>
                            <h3 class="stat-value" data-target="<?php echo $deletedAffiliates; ?>">0</h3>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="action-grid">
                        <a href="admin/affiliates.php" class="action-card">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </div>
                            <h3 class="action-title">Manage Affiliates</h3>
                            <p class="action-desc">View, edit, or remove affiliates from the system</p>
                        </a>

                        <a href="admin/export_csv.php" class="action-card">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                            </div>
                            <h3 class="action-title">Export Data</h3>
                            <p class="action-desc">Download affiliate data in CSV format</p>
                        </a>

                        <a href="admin/test_email.php" class="action-card">
                            <div class="action-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </div>
                            <h3 class="action-title">Send Test Email</h3>
                            <p class="action-desc">Test email notifications and templates</p>
                        </a>
                    </div>
                </div>
            </main>
        </div>

    <?php else: ?>
        <!-- Regular Affiliate Dashboard -->
        <div class="affiliate-container">
            <div class="affiliate-card">
                <div class="card-header">
                    <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
                    <p class="welcome-subtitle">Your affiliate dashboard</p>
                </div>

                <div class="info-section">
                    <div class="info-item">
                        <p class="info-label">Affiliate ID</p>
                        <p class="info-value"><?php echo htmlspecialchars($user['affiliate_id']); ?></p>
                    </div>

                    <div class="info-item">
                        <p class="info-label">Status</p>
                        <span class="status-badge <?php echo htmlspecialchars($user['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                        </span>
                    </div>
                </div>

                <div class="referral-section">
                    <h2 class="section-title">Your Referral Link</h2>
                    <div class="referral-box">
                        <input type="text" 
                               class="referral-input" 
                               value="<?php echo htmlspecialchars($user['referral_link']); ?>" 
                               readonly 
                               id="referralLink">
                        <button class="copy-btn" onclick="copyReferralLink()">
                            <svg class="copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <p class="referral-hint">Share this link to earn commissions</p>
                </div>

                <div class="actions-section">
                    <a href="logout.php" class="logout-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Counter animation for stats
        function animateValue(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                element.textContent = value;
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Animate stat values on page load
        document.addEventListener('DOMContentLoaded', function() {
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach((stat, index) => {
                const target = parseInt(stat.getAttribute('data-target'));
                setTimeout(() => {
                    animateValue(stat, 0, target, 1200);
                }, index * 100);
            });

            // Mobile menu toggle
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
        });

        // Copy referral link function
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
            if (input) {
                input.select();
                input.setSelectionRange(0, 99999);
                document.execCommand('copy');
                
                const btn = event.currentTarget;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>Copied!';
                btn.classList.add('copied');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            }
        }
    </script>
</body>
</html>
