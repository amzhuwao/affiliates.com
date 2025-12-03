<?php
// admin/affiliates.php
require_once __DIR__ . '/../includes/functions.php';
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
$sql = "SELECT id, affiliate_id, full_name, phone_number, email, program, created_at, status
        FROM affiliates $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Management - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/affiliates.css">
    <link rel="stylesheet" href="../css/affiliates_modal.css">
    <link rel="stylesheet" href="../css/premium_table.css">
</head>

<body>

    <div class="affiliates-container">
        <div class="affiliates-card">
            <!-- Back navigation -->
            <a href="../dashboard.php" class="back-link">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Back to Dashboard
            </a>

            <!-- Page header -->
            <div class="page-header">
                <h1 class="page-title">Affiliate Management</h1>
                <p class="page-subtitle">Manage and monitor all affiliate accounts</p>
            </div>

            <!-- Success/Error messages -->
            <?php if ($msg === 'updated'): ?>
                <div class="message success">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.25 5L7.5 13.75L3.75 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Affiliate updated successfully.
                </div>
            <?php endif; ?>

            <?php if ($msg === 'deleted'): ?>
                <div class="message info">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 17.5C14.1421 17.5 17.5 14.1421 17.5 10C17.5 5.85786 14.1421 2.5 10 2.5C5.85786 2.5 2.5 5.85786 2.5 10C2.5 14.1421 5.85786 17.5 10 17.5Z" stroke="currentColor" stroke-width="2" />
                        <path d="M10 6.25V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <circle cx="10" cy="13.125" r="0.625" fill="currentColor" />
                    </svg>
                    Affiliate marked as deleted.
                </div>
            <?php endif; ?>

            <?php if ($msg === 'reactivated'): ?>
                <div class="message success">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.25 5L7.5 13.75L3.75 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Affiliate reactivated successfully.
                </div>
            <?php endif; ?>

            <!-- Toolbar: Search and Export -->
            <div class="toolbar">
                <form method="get" class="search-form">
                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="🔍 Search by name, ID or phone"
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn">Search</button>
                </form>
                <a href="export_csv.php?search=<?= urlencode($search) ?>" class="export-link">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.75 11.25V14.25C15.75 14.6478 15.592 15.0294 15.3107 15.3107C15.0294 15.592 14.6478 15.75 14.25 15.75H3.75C3.35218 15.75 2.97064 15.592 2.68934 15.3107C2.40804 15.0294 2.25 14.6478 2.25 14.25V11.25M5.25 7.5L9 11.25M9 11.25L12.75 7.5M9 11.25V2.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Export CSV
                </a>
            </div>

            <!-- Affiliates table -->
            <?php if (!empty($rows)): ?>
                <div style="overflow-x: auto; margin: 0 -24px; padding: 0 24px;">
                    <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">#</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Affiliate ID</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Name</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Phone</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Email</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Program</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Created</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                                <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border); transition: all 0.3s ease;">
                                    <td data-label="#" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($r['id']) ?></td>
                                    <td data-label="Affiliate ID" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($r['affiliate_id']) ?></td>
                                    <td data-label="Name" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($r['full_name']) ?></td>
                                    <td data-label="Phone" style="padding: 20px 16px; font-size: 14px; color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars($r['phone_number']) ?></td>
                                    <td data-label="Email" style="padding: 20px 16px; font-size: 14px; color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars($r['email'] ?: '—') ?></td>
                                    <td data-label="Program" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;"><?= ($r['program'] === 'GS' ? 'GetSolar' : 'TechVouch') ?></td>
                                    <td data-label="Created" style="padding: 20px 16px; font-size: 13px; color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars(date('M d, Y', strtotime($r['created_at']))) ?></td>
                                    <td data-label="Status" style="padding: 20px 16px; font-size: 14px;">
                                        <span class="status-badge <?= htmlspecialchars($r['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($r['status'])) ?>
                                        </span>
                                    </td>
                                    <td data-label="Actions" style="padding: 20px 16px;">
                                        <div class="actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            <a href="view_affiliate.php?id=<?= htmlspecialchars($r['id']) ?>" class="action-link" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: var(--text-primary); background: var(--bg-card); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid var(--border);">
                                                View
                                            </a>
                                            <a href="edit_affiliate.php?id=<?= htmlspecialchars($r['id']) ?>" class="action-link" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: var(--text-primary); background: var(--bg-card); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid var(--border);">
                                                Edit
                                            </a>
                                            <?php if ($r['status'] === 'active'): ?>
                                                <a href="toggle_status.php?id=<?= htmlspecialchars($r['id']) ?>&to=suspended"
                                                    class="action-link suspend" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: #ff9500; background: rgba(255, 149, 0, 0.1); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid rgba(255, 149, 0, 0.2);">
                                                    Suspend
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_status.php?id=<?= htmlspecialchars($r['id']) ?>&to=active"
                                                    class="action-link reactivate" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: #34c759; background: rgba(52, 199, 89, 0.1); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid rgba(52, 199, 89, 0.2);">
                                                    Reactivate
                                                </a>
                                            <?php endif; ?>
                                            <a href="delete_affiliate.php?id=<?= htmlspecialchars($r['id']) ?>"
                                                class="action-link delete" style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: #ff453a; background: rgba(255, 69, 58, 0.1); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid rgba(255, 69, 58, 0.2);">
                                                Delete
                                            </a>
                                            <?php
                                            // Build WA link and JS-safe value
                                            $waLink = buildProgramWaLink($r['affiliate_id'], $r['program']);
                                            $waHref = htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8');
                                            $waJs = json_encode($waLink);
                                            // Create a short label like "wa.me/.../AFF001" for display
                                            $waHost = parse_url($waLink, PHP_URL_HOST) ?: 'wa.me';
                                            $waLabel = $waHost . '/.../' . $r['affiliate_id'];
                                            ?>
                                            <div class="action-extra">
                                                <a href="<?= $waHref ?>" target="_blank" class="wa-text-link"><?= htmlspecialchars($waLabel, ENT_QUOTES, 'UTF-8') ?></a>
                                                <button onclick="copyLink(<?= $waJs ?>)" class="action-link action-link--copy" type="button">Copy</button>
                                            </div>

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
                    <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No affiliates found</p>
                    <p style="font-size: 14px; margin-bottom: 0;">Try adjusting your search criteria</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= ($page - 1) ?>" class="pagination-link">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Previous
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">Page <?= $page ?> of <?= $pages ?></span>

                    <?php if ($page < $pages): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= ($page + 1) ?>" class="pagination-link">
                            Next
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 4L10 8L6 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Premium Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content">
            <div class="modal-icon" id="modalIcon">
                <svg id="modalIconSvg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="modal-title" id="modalTitle">Confirm Action</h2>
            <p class="modal-message" id="modalMessage">Are you sure you want to proceed?</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" id="modalCancel">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="modalConfirm">Confirm</button>
            </div>
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

        // Premium Confirmation Modal System
        const confirmModal = {
            overlay: document.getElementById('confirmModal'),
            icon: document.getElementById('modalIcon'),
            title: document.getElementById('modalTitle'),
            message: document.getElementById('modalMessage'),
            confirmBtn: document.getElementById('modalConfirm'),
            cancelBtn: document.getElementById('modalCancel'),
            currentCallback: null,

            show: function(options) {
                const {
                    title = 'Confirm Action',
                        message = 'Are you sure you want to proceed?',
                        type = 'warning', // 'warning' or 'danger'
                        confirmText = 'Confirm',
                        cancelText = 'Cancel',
                        onConfirm = () => {}
                } = options;

                this.title.textContent = title;
                this.message.textContent = message;
                this.confirmBtn.textContent = confirmText;
                this.cancelBtn.textContent = cancelText;
                this.currentCallback = onConfirm;

                // Set icon and button style based on type
                this.icon.className = `modal-icon ${type}`;
                this.confirmBtn.className = `modal-btn modal-btn-confirm ${type}`;

                // Show modal
                this.overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            },

            hide: function() {
                this.overlay.classList.remove('active');
                document.body.style.overflow = '';
                this.currentCallback = null;
            },

            confirm: function() {
                if (this.currentCallback) {
                    this.currentCallback();
                }
                this.hide();
            },

            cancel: function() {
                this.hide();
            }
        };

        // Event listeners
        confirmModal.confirmBtn.addEventListener('click', () => confirmModal.confirm());
        confirmModal.cancelBtn.addEventListener('click', () => confirmModal.cancel());

        // Close on overlay click
        confirmModal.overlay.addEventListener('click', (e) => {
            if (e.target === confirmModal.overlay) {
                confirmModal.cancel();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && confirmModal.overlay.classList.contains('active')) {
                confirmModal.cancel();
            }
        });

        // Replace all action links with custom confirmations
        document.addEventListener('DOMContentLoaded', function() {
            // Suspend/Reactivate actions
            document.querySelectorAll('.action-link.suspend, .action-link.reactivate').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isSuspend = this.classList.contains('suspend');

                    confirmModal.show({
                        title: isSuspend ? 'Suspend Affiliate?' : 'Reactivate Affiliate?',
                        message: isSuspend ?
                            'This will temporarily suspend the affiliate account. They will not be able to access their dashboard until reactivated.' : 'This will restore the affiliate account to active status. They will regain full access to their dashboard.',
                        type: isSuspend ? 'warning' : 'warning',
                        confirmText: isSuspend ? 'Yes, Suspend' : 'Yes, Reactivate',
                        cancelText: 'Cancel',
                        onConfirm: () => {
                            window.location.href = this.href;
                        }
                    });
                });
            });

            // Delete actions
            document.querySelectorAll('.action-link.delete').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    confirmModal.show({
                        title: 'Delete Affiliate?',
                        message: 'This will mark the affiliate as deleted. This action can be reversed, but the affiliate will lose access immediately.',
                        type: 'danger',
                        confirmText: 'Yes, Delete',
                        cancelText: 'Cancel',
                        onConfirm: () => {
                            window.location.href = this.href;
                        }
                    });
                });
            });
        });

        function copyLink(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert("Referral link copied!");
            }).catch(err => {
                console.error("Failed to copy: ", err);
            });
        }
    </script>

</body>

</html>